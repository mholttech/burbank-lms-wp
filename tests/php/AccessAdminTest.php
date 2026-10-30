<?php
/**
 * Admin invite, enrollment, and training-profile handler coverage.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Admin as AccessAdmin;
use Burbank_LMS\Access\Invites;
use Burbank_LMS\Access\Learner;
use Burbank_LMS\Access\Profile;
use Burbank_LMS\Access\Tables as AccessTables;
use Burbank_LMS\Courses\Capabilities;
use Burbank_LMS\Enrollment\Admin as EnrollmentAdmin;
use Burbank_LMS\Enrollment\Repository;
use Burbank_LMS\Enrollment\Tables as EnrollmentTables;

/** Capability, nonce, and role gates for training admin UI handlers. */
class AccessAdminTest extends WP_UnitTestCase {
	/** Install tables and grant enroll capability to administrators. */
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

	/** Administrators with enroll_bfc_courses may create site invites. */
	public function test_invite_create_requires_enroll_capability(): void {
		$admin  = new AccessAdmin();
		$result = $admin->dispatch_create_invite(
			'site',
			array(
				'label' => 'Volunteer batch',
				'kind'  => 'multi',
			),
			wp_create_nonce( 'bfc_invite_create' )
		);
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertArrayHasKey( 'plaintext', $result );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$denied = $admin->dispatch_create_invite(
			'site',
			array(
				'label' => 'Editor attempt',
				'kind'  => 'multi',
			),
			wp_create_nonce( 'bfc_invite_create' )
		);
		$this->assertWPError( $denied );
		$this->assertSame( 403, $denied->get_error_data()['status'] ?? 0 );
	}

	/** Invite create rejects a missing or invalid nonce. */
	public function test_invite_create_requires_nonce(): void {
		$admin = new AccessAdmin();
		$this->assertWPError(
			$admin->dispatch_create_invite(
				'site',
				array(
					'label' => 'No nonce',
					'kind'  => 'multi',
				),
				''
			)
		);
		$this->assertWPError(
			$admin->dispatch_create_invite(
				'site',
				array(
					'label' => 'Bad nonce',
					'kind'  => 'multi',
				),
				'not-a-valid-nonce'
			)
		);
	}

	/** Editors cannot revoke invites even with a valid nonce. */
	public function test_invite_revoke_requires_enroll_capability(): void {
		$created = ( new Invites() )->create(
			'site',
			array(
				'label' => 'Revoke me',
				'kind'  => 'once',
			)
		);
		$this->assertIsArray( $created );
		$admin = new AccessAdmin();
		$this->assertTrue(
			$admin->dispatch_revoke_invite(
				(int) $created['id'],
				wp_create_nonce( 'bfc_invite_revoke_' . $created['id'] )
			)
		);

		$second = ( new Invites() )->create(
			'site',
			array(
				'label' => 'Editor revoke',
				'kind'  => 'once',
			)
		);
		$this->assertIsArray( $second );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$denied = $admin->dispatch_revoke_invite(
			(int) $second['id'],
			wp_create_nonce( 'bfc_invite_revoke_' . $second['id'] )
		);
		$this->assertWPError( $denied );
		$this->assertSame( 403, $denied->get_error_data()['status'] ?? 0 );
		$this->assertNotNull( ( new Invites() )->find_active( $second['plaintext'], 'site' ) );
	}

	/**
	 * Publication policy forces draft without a full approval chain; enrollment
	 * fixtures only need a published post_status plus catalog meta.
	 */
	private function published_course(): int {
		$course_id = (int) self::factory()->post->create(
			array(
				'post_type'   => 'bfc_course',
				'post_status' => 'draft',
				'post_title'  => 'Admin enroll course',
				'meta_input'  => array(
					'course_revision' => '1',
					'catalog_state'   => 'published',
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
		return $course_id;
	}

	/** Admin enroll requires a course-specific nonce and enroll_bfc_courses. */
	public function test_admin_enroll_requires_nonce_and_capability(): void {
		$course_id  = $this->published_course();
		$learner_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$admin      = new EnrollmentAdmin();

		$this->assertWPError(
			$admin->dispatch_enroll(
				$course_id,
				$learner_id,
				''
			)
		);
		$this->assertNull( ( new Repository() )->get_active( $learner_id, $course_id ) );

		$result = $admin->dispatch_enroll(
			$course_id,
			$learner_id,
			wp_create_nonce( 'bfc_enrollment_admin_enroll_' . $course_id )
		);
		$this->assertTrue( $result );
		$this->assertNotNull( ( new Repository() )->get_active( $learner_id, $course_id ) );

		$other_learner = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$denied = $admin->dispatch_enroll(
			$course_id,
			$other_learner,
			wp_create_nonce( 'bfc_enrollment_admin_enroll_' . $course_id )
		);
		$this->assertWPError( $denied );
		$this->assertSame( 403, $denied->get_error_data()['status'] ?? 0 );
		$this->assertNull( ( new Repository() )->get_active( $other_learner, $course_id ) );
	}

	/** Profile grant/suspend/legal-name correction require enroll_bfc_courses. */
	public function test_profile_actions_require_enroll_capability(): void {
		$target = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		( new Profile() )->set_legal_name( $target, 'Original Name' );
		$admin = new AccessAdmin();

		$this->assertTrue(
			$admin->dispatch_profile(
				$target,
				'grant',
				wp_create_nonce( 'bfc_learner_profile_' . $target ),
				array()
			)
		);
		$this->assertTrue( user_can( $target, Learner::CAP_ACCESS ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Learner training access.

		$this->assertTrue(
			$admin->dispatch_profile(
				$target,
				'suspend',
				wp_create_nonce( 'bfc_learner_profile_' . $target ),
				array()
			)
		);
		$this->assertTrue( ( new Learner() )->is_suspended( $target ) );

		$this->assertTrue(
			$admin->dispatch_profile(
				$target,
				'legal_name',
				wp_create_nonce( 'bfc_learner_profile_' . $target ),
				array( 'legal_name' => 'Corrected Name' )
			)
		);
		$this->assertSame( 'Corrected Name', ( new Profile() )->get_legal_name( $target ) );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		foreach ( array( 'grant', 'revoke', 'suspend', 'reactivate', 'legal_name' ) as $operation ) {
			$denied = $admin->dispatch_profile(
				$target,
				$operation,
				wp_create_nonce( 'bfc_learner_profile_' . $target ),
				array( 'legal_name' => 'Editor Name' )
			);
			$this->assertWPError( $denied, $operation );
			$this->assertSame( 403, $denied->get_error_data()['status'] ?? 0, $operation );
		}
		$this->assertSame( 'Corrected Name', ( new Profile() )->get_legal_name( $target ) );
	}

	/** Editors cannot manage site or course invites through the admin dispatch API. */
	public function test_editors_cannot_manage_invites(): void {
		$course_id = self::factory()->post->create(
			array(
				'post_type'   => 'bfc_course',
				'post_status' => 'draft',
				'post_title'  => 'Invite course',
			)
		);
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$admin = new AccessAdmin();

		$site = $admin->dispatch_create_invite(
			'site',
			array(
				'label' => 'Editor site',
				'kind'  => 'multi',
			),
			wp_create_nonce( 'bfc_invite_create' )
		);
		$this->assertWPError( $site );

		$course = $admin->dispatch_create_invite(
			'course',
			array(
				'label'     => 'Editor course',
				'kind'      => 'multi',
				'course_id' => $course_id,
			),
			wp_create_nonce( 'bfc_invite_create' )
		);
		$this->assertWPError( $course );
		$this->assertFalse( current_user_can( 'enroll_bfc_courses' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- enroll_bfc_courses from Capabilities VERSION 2.
	}
}
