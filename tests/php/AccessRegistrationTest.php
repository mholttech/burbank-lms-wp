<?php
/**
 * Invite-gated registration and email verification coverage.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Invites;
use Burbank_LMS\Access\Learner;
use Burbank_LMS\Access\Profile;
use Burbank_LMS\Access\Registration;
use Burbank_LMS\Access\Tables as AccessTables;

/** Exercises Registration::register, verify_email, and open-registration blocking. */
class AccessRegistrationTest extends WP_UnitTestCase {
	/** Install invite tables before each case. */
	public function set_up(): void {
		parent::set_up();
		global $wpdb;
		$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB -- Invite redeem commits outside PHPUnit's wrapper.
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}bfc_invite_codes" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		delete_option( AccessTables::VERSION_OPTION );
		$this->assertTrue( ( new AccessTables() )->install() );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/** Restore PHPUnit transaction wrapping after real commits. */
	public function tear_down(): void {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_invite_codes" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB -- Transaction integration fixture.
		parent::tear_down();
	}

	/**
	 * Create a site invite and return plaintext + id.
	 *
	 * @param array<string, mixed> $args Invite create options.
	 * @return array{id:int,plaintext:string}
	 */
	private function site_invite( array $args = array() ): array {
		$created = ( new Invites() )->create(
			'site',
			array_merge(
				array(
					'label' => 'Batch',
					'kind'  => 'multi',
				),
				$args
			)
		);
		$this->assertIsArray( $created );
		return $created;
	}

	/** Build Registration with default collaborators. */
	private function registration(): Registration {
		return new Registration();
	}

	/** Register without invite fails. */
	public function test_register_without_invite_fails(): void {
		$result = $this->registration()->register(
			array(
				'invite'       => '',
				'email'        => 'volunteer@example.com',
				'password'     => 'CorrectHorseBatteryStaple1!',
				'legal_name'   => 'Alex Volunteer',
				'display_name' => 'Alex',
			)
		);
		$this->assertWPError( $result );
	}

	/** Register with a bad invite fails. */
	public function test_register_with_bad_invite_fails(): void {
		$result = $this->registration()->register(
			array(
				'invite'       => 'not-a-real-invite-code',
				'email'        => 'volunteer@example.com',
				'password'     => 'CorrectHorseBatteryStaple1!',
				'legal_name'   => 'Alex Volunteer',
				'display_name' => 'Alex',
			)
		);
		$this->assertWPError( $result );
	}

	/** Register without legal name fails. */
	public function test_register_missing_legal_name_fails(): void {
		$invite = $this->site_invite();
		$result = $this->registration()->register(
			array(
				'invite'       => $invite['plaintext'],
				'email'        => 'volunteer@example.com',
				'password'     => 'CorrectHorseBatteryStaple1!',
				'legal_name'   => '',
				'display_name' => 'Alex',
			)
		);
		$this->assertWPError( $result );
	}

	/** Bound-email invites reject mismatched registration email. */
	public function test_register_rejects_mismatched_bound_email(): void {
		$invite = $this->site_invite( array( 'bound_email' => 'bound@example.com' ) );
		$result = $this->registration()->register(
			array(
				'invite'       => $invite['plaintext'],
				'email'        => 'other@example.com',
				'password'     => 'CorrectHorseBatteryStaple1!',
				'legal_name'   => 'Alex Volunteer',
				'display_name' => 'Alex',
			)
		);
		$this->assertWPError( $result );
	}

	/** Successful register creates user, legal name, access cap, increments invite; not can_learn until verified. */
	public function test_successful_register_grants_access_but_not_can_learn_until_verified(): void {
		$invite  = $this->site_invite();
		$user_id = $this->registration()->register(
			array(
				'invite'       => $invite['plaintext'],
				'email'        => 'volunteer@example.com',
				'password'     => 'CorrectHorseBatteryStaple1!',
				'legal_name'   => 'Alex Volunteer',
				'display_name' => 'Alex',
			)
		);
		$this->assertIsInt( $user_id );
		$this->assertGreaterThan( 0, $user_id );
		$this->assertSame( 'Alex Volunteer', ( new Profile() )->get_legal_name( $user_id ) );
		$this->assertTrue( user_can( $user_id, 'burbank_lms_access' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Learner training access.
		$this->assertSame( '1', (string) get_user_meta( $user_id, 'burbank_lms_training_access', true ) );
		$this->assertSame( (string) $invite['id'], (string) get_user_meta( $user_id, 'burbank_lms_site_invite_id', true ) );

		global $wpdb;
		$use_count = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB -- Isolated invite assertion.
			$wpdb->prepare( "SELECT use_count FROM {$wpdb->prefix}bfc_invite_codes WHERE id = %d", $invite['id'] )
		);
		$this->assertSame( 1, $use_count );

		$learner = new Learner();
		$this->assertFalse( $learner->can_learn( $user_id ) );
		$this->assertFalse( user_can( $user_id, 'pressprimer_quiz_take_quiz' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Synced quiz take cap via user_has_cap.
	}

	/** Front query-arg handler verifies email and enables can_learn. */
	public function test_public_query_handler_enables_can_learn(): void {
		$tokens = array();
		add_action(
			'burbank_lms_sent_verification_email',
			static function ( int $uid, string $token ) use ( &$tokens ): void {
				$tokens[ $uid ] = $token;
			},
			10,
			2
		);

		$invite  = $this->site_invite();
		$user_id = $this->registration()->register(
			array(
				'invite'       => $invite['plaintext'],
				'email'        => 'query-verify@example.com',
				'password'     => 'CorrectHorseBatteryStaple1!',
				'legal_name'   => 'Query Verify',
				'display_name' => 'Query',
			)
		);
		$this->assertIsInt( $user_id );
		$this->assertArrayHasKey( $user_id, $tokens );
		$this->assertFalse( ( new Learner() )->can_learn( $user_id ) );

		$_GET['burbank_lms_verify'] = '1';
		$_GET['uid']                = (string) $user_id;
		$_GET['token']              = $tokens[ $user_id ];

		$redirected = null;
		add_filter(
			'wp_redirect',
			static function ( string $location ) use ( &$redirected ) {
				$redirected = $location;
				return false;
			}
		);

		$this->registration()->handle_verification_request();

		unset( $_GET['burbank_lms_verify'], $_GET['uid'], $_GET['token'] );

		$this->assertNotNull( $redirected );
		$this->assertStringContainsString( 'burbank_lms_verified=1', $redirected );
		$this->assertTrue( ( new Learner() )->can_learn( $user_id ) );
	}

	/** After verify_email, can_learn is true and quiz take cap is present. */
	public function test_verify_email_enables_can_learn_and_quiz_cap(): void {
		$tokens = array();
		add_action(
			'burbank_lms_sent_verification_email',
			static function ( int $uid, string $token ) use ( &$tokens ): void {
				$tokens[ $uid ] = $token;
			},
			10,
			2
		);

		$invite  = $this->site_invite();
		$user_id = $this->registration()->register(
			array(
				'invite'       => $invite['plaintext'],
				'email'        => 'verify-me@example.com',
				'password'     => 'CorrectHorseBatteryStaple1!',
				'legal_name'   => 'Pat Volunteer',
				'display_name' => 'Pat',
			)
		);
		$this->assertIsInt( $user_id );
		$this->assertArrayHasKey( $user_id, $tokens );
		$this->assertNotEmpty( get_user_meta( $user_id, 'burbank_lms_email_verify_token', true ) );

		$result = $this->registration()->verify_email( $tokens[ $user_id ] );
		$this->assertSame( $user_id, $result );

		$learner = new Learner();
		$this->assertTrue( $learner->can_learn( $user_id ) );
		$this->assertTrue( user_can( $user_id, 'pressprimer_quiz_take_quiz' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Synced quiz take cap.
		$this->assertNotEmpty( get_user_meta( $user_id, 'burbank_lms_email_verified_at', true ) );
		$this->assertSame( '', (string) get_user_meta( $user_id, 'burbank_lms_email_verify_token', true ) );
	}

	/** Changing email on a verified learner clears verification until re-verify. */
	public function test_email_change_requires_reverification(): void {
		$tokens = array();
		add_action(
			'burbank_lms_sent_verification_email',
			static function ( int $uid, string $token ) use ( &$tokens ): void {
				$tokens[ $uid ] = $token;
			},
			10,
			2
		);

		$invite  = $this->site_invite();
		$user_id = $this->registration()->register(
			array(
				'invite'       => $invite['plaintext'],
				'email'        => 'before-change@example.com',
				'password'     => 'CorrectHorseBatteryStaple1!',
				'legal_name'   => 'Sam Volunteer',
				'display_name' => 'Sam',
			)
		);
		$this->assertIsInt( $user_id );
		$this->assertSame( $user_id, $this->registration()->verify_email( $tokens[ $user_id ] ) );
		$learner = new Learner();
		$this->assertTrue( $learner->can_learn( $user_id ) );
		$this->assertTrue( user_can( $user_id, 'pressprimer_quiz_take_quiz' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Synced quiz take cap.

		unset( $tokens[ $user_id ] );
		$updated = wp_update_user(
			array(
				'ID'         => $user_id,
				'user_email' => 'after-change@example.com',
			)
		);
		$this->assertSame( $user_id, $updated );
		$this->assertSame( '', (string) get_user_meta( $user_id, 'burbank_lms_email_verified_at', true ) );
		$this->assertNotEmpty( get_user_meta( $user_id, 'burbank_lms_email_verify_token', true ) );
		$this->assertArrayHasKey( $user_id, $tokens );
		$this->assertFalse( $learner->can_learn( $user_id ) );
		$this->assertFalse( user_can( $user_id, 'pressprimer_quiz_take_quiz' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Synced quiz take cap.

		$this->assertSame( $user_id, $this->registration()->verify_email( $tokens[ $user_id ] ) );
		$this->assertTrue( $learner->can_learn( $user_id ) );
		$this->assertTrue( user_can( $user_id, 'pressprimer_quiz_take_quiz' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Synced quiz take cap.
	}

	/** Open wp-login registration is rejected while admin create_users still works. */
	public function test_block_default_registration_but_admin_create_users_works(): void {
		$registration = $this->registration();
		add_filter( 'registration_errors', array( $registration, 'block_default_registration' ) );

		$errors = apply_filters( 'registration_errors', new WP_Error(), 'newuser', 'newuser@example.com' );
		$this->assertWPError( $errors );
		$this->assertNotEmpty( $errors->get_error_codes() );

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$this->assertTrue( current_user_can( 'create_users' ) );
		$created = wp_insert_user(
			array(
				'user_login' => 'admin-created-learner',
				'user_email' => 'admin-created@example.com',
				'user_pass'  => 'CorrectHorseBatteryStaple1!',
				'role'       => 'subscriber',
			)
		);
		$this->assertIsInt( $created );
		$this->assertGreaterThan( 0, $created );
	}
}
