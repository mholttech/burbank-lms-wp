<?php
/**
 * Learner access lifecycle and legal-name profile coverage.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Invites;
use Burbank_LMS\Access\Learner;
use Burbank_LMS\Access\Profile;
use Burbank_LMS\Access\Registration;
use Burbank_LMS\Access\Tables as AccessTables;

/** Exercises Learner grant/suspend/reactivate and Profile legal-name guards. */
class AccessLearnerTest extends WP_UnitTestCase {
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
	 * Register and verify a learner; return user id.
	 *
	 * @param string $email Learner email address.
	 */
	private function verified_learner( string $email = 'learner@example.com' ): int {
		$tokens = array();
		add_action(
			'burbank_lms_sent_verification_email',
			static function ( int $uid, string $token ) use ( &$tokens ): void {
				$tokens[ $uid ] = $token;
			},
			10,
			2
		);
		$created = ( new Invites() )->create(
			'site',
			array(
				'label' => 'Batch',
				'kind'  => 'multi',
			)
		);
		$this->assertIsArray( $created );
		$registration = new Registration();
		$user_id      = $registration->register(
			array(
				'invite'       => $created['plaintext'],
				'email'        => $email,
				'password'     => 'CorrectHorseBatteryStaple1!',
				'legal_name'   => 'Jamie Learner',
				'display_name' => 'Jamie',
			)
		);
		$this->assertIsInt( $user_id );
		$this->assertArrayHasKey( $user_id, $tokens );
		$this->assertSame( $user_id, $registration->verify_email( $tokens[ $user_id ] ) );
		return $user_id;
	}

	/** Suspend blocks can_learn; reactivate restores when verified. */
	public function test_suspend_blocks_can_learn_and_reactivate_restores(): void {
		$user_id = $this->verified_learner();
		$learner = new Learner();
		$this->assertTrue( $learner->can_learn( $user_id ) );

		$learner->suspend( $user_id );
		$this->assertFalse( $learner->can_learn( $user_id ) );
		$this->assertFalse( user_can( $user_id, 'pressprimer_quiz_take_quiz' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Synced quiz take cap.

		$learner->reactivate( $user_id );
		$this->assertTrue( $learner->can_learn( $user_id ) );
		$this->assertTrue( user_can( $user_id, 'pressprimer_quiz_take_quiz' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Synced quiz take cap.
	}

	/** Admin grant without invite still sets access meta and cap. */
	public function test_admin_grant_access_without_invite(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$learner = new Learner();
		$learner->grant_access( $user_id );
		$this->assertTrue( user_can( $user_id, 'burbank_lms_access' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Learner training access.
		$this->assertSame( '1', (string) get_user_meta( $user_id, 'burbank_lms_training_access', true ) );
		$this->assertFalse( $learner->can_learn( $user_id ) );
	}

	/** Revoke removes access and quiz cap. */
	public function test_revoke_access_removes_caps(): void {
		$user_id = $this->verified_learner( 'revoke@example.com' );
		$learner = new Learner();
		$this->assertTrue( $learner->can_learn( $user_id ) );
		$learner->revoke_access( $user_id );
		$this->assertFalse( user_can( $user_id, 'burbank_lms_access' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Learner training access.
		$this->assertFalse( $learner->can_learn( $user_id ) );
		$this->assertFalse( user_can( $user_id, 'pressprimer_quiz_take_quiz' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Synced quiz take cap.
	}

	/** Learner cannot update legal name; admin correction writes audit. */
	public function test_learner_cannot_update_legal_name_admin_correction_audits(): void {
		$user_id = $this->verified_learner( 'profile@example.com' );
		$profile = new Profile();

		wp_set_current_user( $user_id );
		$denied = $profile->set_legal_name( $user_id, 'Hacker Name' );
		$this->assertWPError( $denied );
		$this->assertSame( 'Jamie Learner', $profile->get_legal_name( $user_id ) );

		$blocked = update_user_meta( $user_id, 'burbank_lms_legal_name', 'Meta Hack' );
		$this->assertFalse( $blocked );
		$this->assertSame( 'Jamie Learner', $profile->get_legal_name( $user_id ) );

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		( new \Burbank_LMS\Courses\Capabilities() )->install();
		wp_set_current_user( $admin_id );
		$this->assertTrue( current_user_can( 'enroll_bfc_courses' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Training admin cap.
		$result = $profile->set_legal_name( $user_id, 'Jamie Corrected', true );
		$this->assertTrue( $result );
		$this->assertSame( 'Jamie Corrected', $profile->get_legal_name( $user_id ) );

		$audit = get_user_meta( $user_id, 'burbank_lms_legal_name_audit', true );
		$this->assertIsString( $audit );
		$entries = json_decode( $audit, true );
		$this->assertIsArray( $entries );
		$this->assertNotEmpty( $entries );
		$last = end( $entries );
		$this->assertSame( 'Jamie Learner', $last['from'] );
		$this->assertSame( 'Jamie Corrected', $last['to'] );
		$this->assertSame( $admin_id, (int) $last['by'] );
	}

	/** Learners cannot delete legal name usermeta to clear-then-rewrite. */
	public function test_learner_cannot_delete_legal_name_meta(): void {
		$user_id = $this->verified_learner( 'delete-name@example.com' );
		$profile = new Profile();
		wp_set_current_user( $user_id );

		$deleted = delete_user_meta( $user_id, 'burbank_lms_legal_name' );
		$this->assertFalse( $deleted );
		$this->assertSame( 'Jamie Learner', $profile->get_legal_name( $user_id ) );

		global $wpdb;
		$mid = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB -- Locate usermeta mid for by-mid delete guard.
			$wpdb->prepare(
				"SELECT umeta_id FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s LIMIT 1",
				$user_id,
				'burbank_lms_legal_name'
			)
		);
		$this->assertGreaterThan( 0, $mid );
		$this->assertFalse( delete_metadata_by_mid( 'user', $mid ) );
		$this->assertSame( 'Jamie Learner', $profile->get_legal_name( $user_id ) );
	}

	/** Quiz-cap filter denies role-granted take-quiz until can_learn; allows after. */
	public function test_filter_quiz_cap_overrides_subscriber_role_grant(): void {
		$role = get_role( 'subscriber' );
		$this->assertNotNull( $role );
		$role->add_cap( 'pressprimer_quiz_take_quiz' );

		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$learner = new Learner();
		$learner->grant_access( $user_id );
		$this->assertFalse( $learner->can_learn( $user_id ) );
		$this->assertFalse( user_can( $user_id, 'pressprimer_quiz_take_quiz' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Filter overrides role grant.

		update_user_meta( $user_id, Learner::META_EMAIL_VERIFIED_AT, gmdate( 'c' ) );
		$learner->sync_quiz_cap( $user_id );
		$this->assertTrue( $learner->can_learn( $user_id ) );
		$this->assertTrue( user_can( $user_id, 'pressprimer_quiz_take_quiz' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- can_learn allows take quiz.

		$learner->suspend( $user_id );
		$this->assertFalse( $learner->can_learn( $user_id ) );
		$this->assertFalse( user_can( $user_id, 'pressprimer_quiz_take_quiz' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Suspended still denied.
	}
}
