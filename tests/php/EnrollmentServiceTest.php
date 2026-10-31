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
use Burbank_LMS\Enrollment\Repository;
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
	 * Register a learner with training access; return user id.
	 *
	 * @param string $email Learner email address.
	 */
	private function verified_learner( string $email = 'enroll-learner@example.com' ): int {
		$created = ( new Invites() )->create(
			'site',
			array(
				'label' => 'Batch',
				'kind'  => 'multi',
			)
		);
		$this->assertIsArray( $created );
		$user_id = ( new Registration() )->register(
			array(
				'invite'       => $created['plaintext'],
				'email'        => $email,
				'password'     => 'CorrectHorseBatteryStaple1!',
				'legal_name'   => 'Jamie Learner',
				'display_name' => 'Jamie',
			)
		);
		$this->assertIsInt( $user_id );
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

	/** Course invites with bound_email reject learners whose user_email does not match. */
	public function test_restricted_bound_email_mismatch_fails(): void {
		$user_id   = $this->verified_learner( 'bound-mismatch@example.com' );
		$course_id = $this->published_course();
		$created   = ( new Invites() )->create(
			'course',
			array(
				'course_id'   => $course_id,
				'label'       => 'Bound seat',
				'kind'        => 'multi',
				'bound_email' => 'someone-else@example.com',
			)
		);
		$this->assertIsArray( $created );

		$result = $this->service()->self_enroll( $user_id, $course_id, $created['plaintext'] );
		$this->assertWPError( $result );
		$this->assertSame( 'burbank_enrollment_bound_email', $result->get_error_code() );
		$this->assertNull( $this->service()->get_active( $user_id, $course_id ) );
	}

	/** Course invites with bound_email accept when learner user_email matches. */
	public function test_restricted_bound_email_match_succeeds(): void {
		$user_id   = $this->verified_learner( 'bound-match@example.com' );
		$course_id = $this->published_course( 'rev-bound' );
		$created   = ( new Invites() )->create(
			'course',
			array(
				'course_id'   => $course_id,
				'label'       => 'Bound seat',
				'kind'        => 'multi',
				'bound_email' => 'bound-match@example.com',
			)
		);
		$this->assertIsArray( $created );

		$result = $this->service()->self_enroll( $user_id, $course_id, $created['plaintext'] );
		$this->assertTrue( $result );
		$row = $this->service()->get_active( $user_id, $course_id );
		$this->assertNotNull( $row );
		$this->assertSame( 'invite', $row->source );
	}

	/**
	 * Redeem failure must leave no enrollment row (shared transaction rollback).
	 *
	 * Repository refuses compensate-delete so only a real ROLLBACK (not post-hoc
	 * cleanup) can clear the insert. Invite find succeeds; redeem always fails.
	 */
	public function test_redeem_failure_rolls_back_enrollment_insert(): void {
		$user_id   = $this->verified_learner( 'atomic-redeem@example.com' );
		$course_id = $this->published_course( 'rev-atomic' );
		$created   = ( new Invites() )->create(
			'course',
			array(
				'course_id' => $course_id,
				'label'     => 'Once seat',
				'kind'      => 'once',
			)
		);
		$this->assertIsArray( $created );

		$invites = new class() extends Invites {
			/**
			 * Always fail redeem so the enrollment transaction must roll back.
			 *
			 * @param int  $id                  Invite row ID.
			 * @param bool $manage_transaction Whether this call opens/commits a transaction.
			 */
			public function redeem( int $id, bool $manage_transaction = true ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Signature matches Invites::redeem.
				return false;
			}
		};

		$repository = new class() extends Repository {
			/**
			 * Refuse compensate-delete so leftover rows prove missing ROLLBACK.
			 *
			 * @param int $id Enrollment row ID.
			 */
			public function delete( int $id ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Signature matches Repository::delete.
				return false;
			}
		};

		$service = new Service( $repository, $invites );
		$result  = $service->self_enroll( $user_id, $course_id, $created['plaintext'] );
		$this->assertWPError( $result );
		$this->assertNull( $service->get_active( $user_id, $course_id ) );

		global $wpdb;
		// phpcs:disable WordPress.DB -- Assert no leftover enrollment after failed redeem.
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}bfc_enrollments WHERE user_id = %d AND course_id = %d",
				$user_id,
				$course_id
			)
		);
		// phpcs:enable WordPress.DB
		$this->assertSame( 0, $count );
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

	/** Learners without access or with suspension cannot self-enroll. */
	public function test_without_access_and_suspended_fail_self_enroll(): void {
		$course_id = $this->published_course();
		$learner   = new Learner();

		$without_access = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->assertFalse( $learner->can_learn( $without_access ) );
		$this->assertWPError( $this->service()->self_enroll( $without_access, $course_id ) );

		$suspended = $this->verified_learner( 'suspended@example.com' );
		$learner->suspend( $suspended );
		$this->assertFalse( $learner->can_learn( $suspended ) );
		$this->assertWPError( $this->service()->self_enroll( $suspended, $course_id ) );
		$this->assertNull( $this->service()->get_active( $suspended, $course_id ) );
	}
}
