<?php
/**
 * Learner register / enroll / withdraw REST coverage.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Invites;
use Burbank_LMS\Access\Learner;
use Burbank_LMS\Access\Registration;
use Burbank_LMS\Access\Tables as AccessTables;
use Burbank_LMS\Courses\Capabilities;
use Burbank_LMS\Enrollment\Service as EnrollmentService;
use Burbank_LMS\Enrollment\Tables as EnrollmentTables;

/** Exercises burbank-lms/v1 register and enrollment endpoints. */
class LearnerRestTest extends WP_UnitTestCase {
	/** Install tables, caps, and a fresh REST server before each case. */
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

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );
	}

	/** Restore PHPUnit transaction wrapping after real commits. */
	public function tear_down(): void {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_invite_codes" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_enrollments" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB -- Transaction integration fixture.
		global $wp_rest_server;
		$wp_rest_server = null;
		parent::tear_down();
	}

	/**
	 * Create a multi-use site invite.
	 *
	 * @return array{id:int,plaintext:string}
	 */
	private function site_invite(): array {
		$created = ( new Invites() )->create(
			'site',
			array(
				'label' => 'REST batch',
				'kind'  => 'multi',
			)
		);
		$this->assertIsArray( $created );
		return $created;
	}

	/**
	 * Register and verify a learner; return user id.
	 *
	 * @param string $email Learner email.
	 */
	private function verified_learner( string $email = 'rest-learner@example.com' ): int {
		$tokens = array();
		add_action(
			'burbank_lms_sent_verification_email',
			static function ( int $uid, string $token ) use ( &$tokens ): void {
				$tokens[ $uid ] = $token;
			},
			10,
			2
		);
		$invite       = $this->site_invite();
		$registration = new Registration();
		$user_id      = $registration->register(
			array(
				'invite'       => $invite['plaintext'],
				'email'        => $email,
				'password'     => 'CorrectHorseBatteryStaple1!',
				'legal_name'   => 'Rest Learner',
				'display_name' => 'Rest',
			)
		);
		$this->assertIsInt( $user_id );
		$this->assertArrayHasKey( $user_id, $tokens );
		$this->assertSame( $user_id, $registration->verify_email( $tokens[ $user_id ] ) );
		return $user_id;
	}

	/**
	 * Create a published bfc_course for enrollment fixtures.
	 *
	 * @param string $revision Course revision snapshot.
	 */
	private function published_course( string $revision = '1.0' ): int {
		$course_id = (int) self::factory()->post->create(
			array(
				'post_type'   => 'bfc_course',
				'post_status' => 'draft',
				'post_title'  => 'REST Course',
				'meta_input'  => array(
					'course_revision' => $revision,
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

	/**
	 * Dispatch a JSON REST request.
	 *
	 * @param string               $method HTTP method.
	 * @param string               $route  Route under burbank-lms/v1.
	 * @param array<string, mixed> $body   JSON body params.
	 * @param array<string, mixed> $query  Query string params.
	 */
	private function dispatch( string $method, string $route, array $body = array(), array $query = array() ): WP_REST_Response {
		$request = new WP_REST_Request( $method, '/burbank-lms/v1' . $route );
		if ( array() !== $query ) {
			$request->set_query_params( $query );
		}
		if ( array() !== $body ) {
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body( wp_json_encode( $body ) );
		}
		return rest_do_request( $request );
	}

	/** Public register creates a user from a valid site invite. */
	public function test_register_happy_path(): void {
		wp_set_current_user( 0 );
		$invite = $this->site_invite();

		$response = $this->dispatch(
			'POST',
			'/register',
			array(
				'invite'       => $invite['plaintext'],
				'email'        => 'new-volunteer@example.com',
				'password'     => 'CorrectHorseBatteryStaple1!',
				'legal_name'   => 'New Volunteer',
				'display_name' => 'New',
			)
		);

		$this->assertSame( 201, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'user_id', $data );
		$user_id = (int) $data['user_id'];
		$this->assertGreaterThan( 0, $user_id );
		$this->assertTrue( user_can( $user_id, Learner::CAP_ACCESS ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Learner training access.
		$this->assertFalse( ( new Learner() )->can_learn( $user_id ) );
	}

	/** Register without invite returns 400 with machine code. */
	public function test_register_missing_invite_returns_400(): void {
		wp_set_current_user( 0 );

		$response = $this->dispatch(
			'POST',
			'/register',
			array(
				'email'      => 'missing-invite@example.com',
				'password'   => 'CorrectHorseBatteryStaple1!',
				'legal_name' => 'Missing Invite',
			)
		);

		$this->assertSame( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertSame( 'burbank_register_invite_required', $data['code'] );
	}

	/** Public verify-email REST path enables can_learn (not Registration::verify_email alone). */
	public function test_verify_email_public_rest_enables_can_learn(): void {
		$tokens = array();
		add_action(
			'burbank_lms_sent_verification_email',
			static function ( int $uid, string $token ) use ( &$tokens ): void {
				$tokens[ $uid ] = $token;
			},
			10,
			2
		);

		wp_set_current_user( 0 );
		$invite   = $this->site_invite();
		$response = $this->dispatch(
			'POST',
			'/register',
			array(
				'invite'       => $invite['plaintext'],
				'email'        => 'verify-rest@example.com',
				'password'     => 'CorrectHorseBatteryStaple1!',
				'legal_name'   => 'Verify Rest',
				'display_name' => 'Verify',
			)
		);
		$this->assertSame( 201, $response->get_status() );
		$user_id = (int) $response->get_data()['user_id'];
		$this->assertArrayHasKey( $user_id, $tokens );
		$this->assertFalse( ( new Learner() )->can_learn( $user_id ) );

		$verify = $this->dispatch(
			'GET',
			'/verify-email',
			array(),
			array(
				'uid'   => $user_id,
				'token' => $tokens[ $user_id ],
			)
		);
		$this->assertSame( 200, $verify->get_status() );
		$data = $verify->get_data();
		$this->assertIsArray( $data );
		$this->assertTrue( ! empty( $data['verified'] ) );
		$this->assertSame( $user_id, (int) $data['user_id'] );
		$this->assertTrue( ( new Learner() )->can_learn( $user_id ) );
	}

	/** Self-enroll succeeds for a verified learner on an open course. */
	public function test_enroll_happy_path(): void {
		$user_id   = $this->verified_learner( 'enroll-ok@example.com' );
		$course_id = $this->published_course();
		wp_set_current_user( $user_id );

		$response = $this->dispatch(
			'POST',
			'/enrollments',
			array(
				'course_id' => $course_id,
			)
		);

		$this->assertSame( 201, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertTrue( $data['enrolled'] );
		$this->assertSame( $course_id, (int) $data['course_id'] );
		$this->assertSame( $user_id, (int) $data['user_id'] );
		$this->assertNotNull( ( new EnrollmentService() )->get_active( $user_id, $course_id ) );
	}

	/** Unauthenticated enroll returns 401. */
	public function test_enroll_requires_authentication(): void {
		$course_id = $this->published_course();
		wp_set_current_user( 0 );

		$response = $this->dispatch(
			'POST',
			'/enrollments',
			array(
				'course_id' => $course_id,
			)
		);

		$this->assertSame( 401, $response->get_status() );
	}

	/** Logged-in learner without can_learn gets 403. */
	public function test_enroll_without_can_learn_returns_403(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		( new Learner() )->grant_access( $user_id );
		$course_id = $this->published_course();
		wp_set_current_user( $user_id );

		$response = $this->dispatch(
			'POST',
			'/enrollments',
			array(
				'course_id' => $course_id,
			)
		);

		$this->assertSame( 403, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertSame( 'burbank_enrollment_cannot_learn', $data['code'] );
	}

	/** Self-enroll without course_id returns 400. */
	public function test_enroll_missing_course_id_returns_400(): void {
		$user_id = $this->verified_learner( 'enroll-missing@example.com' );
		wp_set_current_user( $user_id );

		$response = $this->dispatch( 'POST', '/enrollments', array() );

		$this->assertSame( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertSame( 'burbank_enrollment_course_invalid', $data['code'] );
	}

	/** Authenticated learner can list their enrollments. */
	public function test_list_enrollments_for_current_user(): void {
		$user_id   = $this->verified_learner( 'list-enroll@example.com' );
		$course_id = $this->published_course();
		$this->assertTrue( ( new EnrollmentService() )->self_enroll( $user_id, $course_id ) );
		wp_set_current_user( $user_id );

		$response = $this->dispatch( 'GET', '/enrollments' );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'enrollments', $data );
		$this->assertCount( 1, $data['enrollments'] );
		$this->assertSame( $course_id, (int) $data['enrollments'][0]['course_id'] );
		$this->assertSame( 'active', $data['enrollments'][0]['status'] );
	}

	/** Learner may withdraw their own enrollment. */
	public function test_withdraw_happy_path(): void {
		$user_id   = $this->verified_learner( 'withdraw-ok@example.com' );
		$course_id = $this->published_course();
		$this->assertTrue( ( new EnrollmentService() )->self_enroll( $user_id, $course_id ) );
		wp_set_current_user( $user_id );

		$response = $this->dispatch( 'DELETE', '/enrollments/' . $course_id );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertTrue( $data['withdrawn'] );
		$this->assertNull( ( new EnrollmentService() )->get_active( $user_id, $course_id ) );
	}

	/** Unauthenticated withdraw returns 401. */
	public function test_withdraw_requires_authentication(): void {
		wp_set_current_user( 0 );

		$response = $this->dispatch( 'DELETE', '/enrollments/1' );

		$this->assertSame( 401, $response->get_status() );
	}

	/** Subscriber cannot withdraw another user's enrollment. */
	public function test_withdraw_other_user_forbidden_without_cap(): void {
		$owner_id  = $this->verified_learner( 'owner@example.com' );
		$other_id  = $this->verified_learner( 'other@example.com' );
		$course_id = $this->published_course();
		$this->assertTrue( ( new EnrollmentService() )->self_enroll( $owner_id, $course_id ) );
		wp_set_current_user( $other_id );

		$response = $this->dispatch(
			'DELETE',
			'/enrollments/' . $course_id,
			array(
				'user_id' => $owner_id,
			)
		);

		$this->assertSame( 403, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertSame( 'burbank_enrollment_forbidden', $data['code'] );
		$this->assertNotNull( ( new EnrollmentService() )->get_active( $owner_id, $course_id ) );
	}

	/** Admin with enroll_bfc_courses may withdraw another user's enrollment. */
	public function test_admin_withdraw_other_user_happy_path(): void {
		$learner_id = $this->verified_learner( 'admin-withdraw-target@example.com' );
		$course_id  = $this->published_course();
		$this->assertTrue( ( new EnrollmentService() )->self_enroll( $learner_id, $course_id ) );

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$response = $this->dispatch(
			'DELETE',
			'/enrollments/' . $course_id,
			array(
				'user_id' => $learner_id,
			)
		);

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertTrue( $data['withdrawn'] );
		$this->assertSame( $learner_id, (int) $data['user_id'] );
		$this->assertSame( $course_id, (int) $data['course_id'] );
		$this->assertNull( ( new EnrollmentService() )->get_active( $learner_id, $course_id ) );
	}

	/** Missing enrollment maps to 400, not 403. */
	public function test_withdraw_missing_enrollment_returns_400(): void {
		$user_id   = $this->verified_learner( 'withdraw-missing@example.com' );
		$course_id = $this->published_course();
		wp_set_current_user( $user_id );

		$response = $this->dispatch( 'DELETE', '/enrollments/' . $course_id );

		$this->assertSame( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertSame( 'burbank_enrollment_not_found', $data['code'] );
	}

	/** Admin enroll assigns a learner without a course invite. */
	public function test_admin_enroll_happy_path(): void {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$user_id   = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$course_id = $this->published_course( 'rev-admin' );

		$response = $this->dispatch(
			'POST',
			'/enrollments/admin',
			array(
				'user_id'   => $user_id,
				'course_id' => $course_id,
			)
		);

		$this->assertSame( 201, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertTrue( $data['enrolled'] );
		$this->assertSame( $user_id, (int) $data['user_id'] );
		$this->assertSame( $course_id, (int) $data['course_id'] );
		$this->assertTrue( user_can( $user_id, Learner::CAP_ACCESS ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Learner training access.
		$row = ( new EnrollmentService() )->get_active( $user_id, $course_id );
		$this->assertNotNull( $row );
		$this->assertSame( 'admin', $row->source );
	}

	/** Admin enroll without enroll_bfc_courses returns 403. */
	public function test_admin_enroll_forbidden_for_subscriber(): void {
		$user_id   = $this->verified_learner( 'no-admin@example.com' );
		$target_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$course_id = $this->published_course();
		wp_set_current_user( $user_id );

		$response = $this->dispatch(
			'POST',
			'/enrollments/admin',
			array(
				'user_id'   => $target_id,
				'course_id' => $course_id,
			)
		);

		$this->assertSame( 403, $response->get_status() );
	}

	/** Unauthenticated admin enroll returns 401. */
	public function test_admin_enroll_requires_authentication(): void {
		wp_set_current_user( 0 );

		$response = $this->dispatch(
			'POST',
			'/enrollments/admin',
			array(
				'user_id'   => 1,
				'course_id' => 1,
			)
		);

		$this->assertSame( 401, $response->get_status() );
	}
}
