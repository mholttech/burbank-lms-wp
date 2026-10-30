<?php
/**
 * Course enrollment service coverage.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Invites;
use Burbank_LMS\Access\Learner;
use Burbank_LMS\Access\Registration;
use Burbank_LMS\Access\Tables as AccessTables;
use Burbank_LMS\Courses\Capabilities;
use Burbank_LMS\Enrollment\Service;
use Burbank_LMS\Enrollment\Tables as EnrollmentTables;

/** Exercises open/restricted self-enroll, admin enroll, withdraw, and re-enroll. */
class EnrollmentServiceTest extends WP_UnitTestCase {
	/** Install access + enrollment tables and authenticate an admin for fixtures. */
	public function set_up(): void {
		parent::set_up();
		global $wpdb;
		$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB -- Invite redeem commits outside PHPUnit's wrapper.
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}bfc_invite_codes" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}bfc_enrollments" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		delete_option( AccessTables::VERSION_OPTION );
		delete_option( EnrollmentTables::VERSION_OPTION );
		$this->assertTrue( ( new AccessTables() )->install() );
		$this->assertTrue( ( new EnrollmentTables() )->install() );
		( new Capabilities() )->install();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/** Restore PHPUnit transaction wrapping after real commits. */
	public function tear_down(): void {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_invite_codes" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_enrollments" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB -- Transaction integration fixture.
		parent::tear_down();
	}

	/** Return the enrollment service under test. */
	private function service(): Service {
		return new Service();
	}

	/**
	 * Register and verify a learner; return user id.
	 *
	 * @param string $email Learner email address.
	 */
	private function verified_learner( string $email = 'enroll-learner@example.com' ): int {
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

	/**
	 * Create a published course with catalog meta.
	 *
	 * Publication policy forces draft without a full approval chain; enrollment
	 * fixtures only need a published post_status plus catalog meta.
	 *
	 * @param string $revision      Course revision string.
	 * @param string $catalog_state Catalog state meta.
	 */
	private function published_course( string $revision = '1.0', string $catalog_state = 'published' ): int {
		$course_id = (int) self::factory()->post->create(
			array(
				'post_type'   => 'bfc_course',
				'post_status' => 'draft',
				'post_title'  => 'Enrollment Course',
				'meta_input'  => array(
					'course_revision' => $revision,
					'catalog_state'   => $catalog_state,
				),
			)
		);
		global $wpdb;
		$wpdb->update( // phpcs:ignore WordPress.DB -- Bypass publication guards for enrollment fixtures.
			$wpdb->posts,
			array( 'post_status' => 'publish' ),
			array( 'ID' => $course_id ),
			array( '%s' ),
			array( '%d' )
		);
		clean_post_cache( $course_id );
		$this->assertSame( 'publish', get_post_status( $course_id ) );
		return $course_id;
	}

	/** Open courses accept self-enroll and snapshot the current revision. */
	public function test_open_course_self_enroll_snapshots_revision(): void {
		$user_id   = $this->verified_learner();
		$course_id = $this->published_course( 'rev-open' );

		$result = $this->service()->self_enroll( $user_id, $course_id );
		$this->assertTrue( $result );

		$row = $this->service()->get_active( $user_id, $course_id );
		$this->assertNotNull( $row );
		$this->assertSame( 'active', $row->status );
		$this->assertSame( 'self', $row->source );
		$this->assertSame( 'rev-open', $row->course_revision_snapshot );
		$this->assertNull( $row->invite_code_id );
	}

	/** Restricted courses reject self-enroll without a course invite. */
	public function test_restricted_without_code_fails(): void {
		$user_id   = $this->verified_learner( 'restricted-fail@example.com' );
		$course_id = $this->published_course();
		$created   = ( new Invites() )->create(
			'course',
			array(
				'course_id' => $course_id,
				'label'     => 'Restricted',
				'kind'      => 'multi',
			)
		);
		$this->assertIsArray( $created );

		$result = $this->service()->self_enroll( $user_id, $course_id );
		$this->assertWPError( $result );
		$this->assertNull( $this->service()->get_active( $user_id, $course_id ) );
	}

	/** Restricted courses accept a valid course invite and record source=invite. */
	public function test_restricted_with_code_succeeds(): void {
		$user_id   = $this->verified_learner( 'restricted-ok@example.com' );
		$course_id = $this->published_course( 'rev-invite' );
		$created   = ( new Invites() )->create(
			'course',
			array(
				'course_id' => $course_id,
				'label'     => 'Restricted',
				'kind'      => 'multi',
			)
		);
		$this->assertIsArray( $created );

		$result = $this->service()->self_enroll( $user_id, $course_id, $created['plaintext'] );
		$this->assertTrue( $result );

		$row = $this->service()->get_active( $user_id, $course_id );
		$this->assertNotNull( $row );
		$this->assertSame( 'invite', $row->source );
		$this->assertSame( (int) $created['id'], (int) $row->invite_code_id );
		$this->assertSame( 'rev-invite', $row->course_revision_snapshot );
	}

	/** Site invites never satisfy a restricted course enroll gate. */
	public function test_site_invite_cannot_enroll_into_course(): void {
		$user_id   = $this->verified_learner( 'site-invite@example.com' );
		$course_id = $this->published_course();
		$course    = ( new Invites() )->create(
			'course',
			array(
				'course_id' => $course_id,
				'label'     => 'Restricted',
				'kind'      => 'multi',
			)
		);
		$this->assertIsArray( $course );
		$site = ( new Invites() )->create(
			'site',
			array(
				'label' => 'Site only',
				'kind'  => 'multi',
			)
		);
		$this->assertIsArray( $site );

		$result = $this->service()->self_enroll( $user_id, $course_id, $site['plaintext'] );
		$this->assertWPError( $result );
		$this->assertNull( $this->service()->get_active( $user_id, $course_id ) );
	}

	/** Withdrawn learners may re-enroll by reactivating the unique course/user row. */
	public function test_withdraw_then_reenroll_reactivates_row(): void {
		$user_id   = $this->verified_learner( 'reenroll@example.com' );
		$course_id = $this->published_course( 'rev-1' );
		$this->assertTrue( $this->service()->self_enroll( $user_id, $course_id ) );

		$first = $this->service()->get_active( $user_id, $course_id );
		$this->assertNotNull( $first );
		$first_id = (int) $first->id;

		$this->assertTrue( $this->service()->withdraw( $user_id, $user_id, $course_id ) );
		$this->assertNull( $this->service()->get_active( $user_id, $course_id ) );

		global $wpdb;
		// phpcs:ignore WordPress.DB -- Bypass published assessed-field guards for revision fixture.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE post_id = %d AND meta_key = %s",
				'rev-2',
				$course_id,
				'course_revision'
			)
		);
		clean_post_cache( $course_id );
		$this->assertSame( 'rev-2', (string) get_post_meta( $course_id, 'course_revision', true ) );

		// Ensure enrolled_at advances even when both actions share a wall-clock second.
		sleep( 1 );
		$this->assertTrue( $this->service()->self_enroll( $user_id, $course_id ) );

		$second = $this->service()->get_active( $user_id, $course_id );
		$this->assertNotNull( $second );
		$this->assertSame( $first_id, (int) $second->id );
		$this->assertSame( 'active', $second->status );
		$this->assertSame( 'rev-2', $second->course_revision_snapshot );
		$this->assertNotSame( $first->enrolled_at, $second->enrolled_at );
	}

	/** Admins may enroll without a course invite and grant missing training access. */
	public function test_admin_enroll_grants_access_and_bypasses_codes(): void {
		$admin_id  = (int) get_current_user_id();
		$user_id   = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$course_id = $this->published_course( 'rev-admin' );
		$created   = ( new Invites() )->create(
			'course',
			array(
				'course_id' => $course_id,
				'label'     => 'Restricted',
				'kind'      => 'multi',
			)
		);
		$this->assertIsArray( $created );

		$result = $this->service()->admin_enroll( $admin_id, $user_id, $course_id );
		$this->assertTrue( $result );
		$this->assertTrue( user_can( $user_id, Learner::CAP_ACCESS ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Learner training access.

		$row = $this->service()->get_active( $user_id, $course_id );
		$this->assertNotNull( $row );
		$this->assertSame( 'admin', $row->source );
		$this->assertNull( $row->invite_code_id );
		$this->assertSame( 'rev-admin', $row->course_revision_snapshot );
	}

	/** Archived catalog state rejects new self-enroll. */
	public function test_archived_rejects_self_enroll(): void {
		$user_id   = $this->verified_learner( 'archived@example.com' );
		$course_id = $this->published_course( '1.0', 'archived' );

		$result = $this->service()->self_enroll( $user_id, $course_id );
		$this->assertWPError( $result );
		$this->assertNull( $this->service()->get_active( $user_id, $course_id ) );
	}

	/** Unverified and suspended learners cannot self-enroll. */
	public function test_unverified_and_suspended_fail_self_enroll(): void {
		$course_id = $this->published_course();
		$learner   = new Learner();

		$unverified = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$learner->grant_access( $unverified );
		$this->assertFalse( $learner->can_learn( $unverified ) );
		$this->assertWPError( $this->service()->self_enroll( $unverified, $course_id ) );

		$suspended = $this->verified_learner( 'suspended@example.com' );
		$learner->suspend( $suspended );
		$this->assertFalse( $learner->can_learn( $suspended ) );
		$this->assertWPError( $this->service()->self_enroll( $suspended, $course_id ) );
		$this->assertNull( $this->service()->get_active( $suspended, $course_id ) );
	}
}
