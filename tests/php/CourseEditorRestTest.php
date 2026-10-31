<?php
/**
 * Course editor admin REST coverage (editorial + invite/enrollment routes).
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Tables as AccessTables;
use Burbank_LMS\Courses\Capabilities;
use Burbank_LMS\Courses\Editorial;
use Burbank_LMS\Courses\Registry;
use Burbank_LMS\Courses\Schema;
use Burbank_LMS\Courses\Validator;
use Burbank_LMS\Enrollment\Service as EnrollmentService;
use Burbank_LMS\Enrollment\Tables as EnrollmentTables;

/** Exercises burbank-lms/v1 course editorial admin endpoints. */
class CourseEditorRestTest extends WP_UnitTestCase {
	/** Schema, registry, caps, tables, and a fresh REST server before each case. */
	public function set_up(): void {
		parent::set_up();
		global $wpdb;
		$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB -- Registry/editorial transactions commit.
		pods_api()->cache_flush_pods();
		delete_option( Schema::VERSION_OPTION );
		( new Schema() )->register();
		( new Registry() )->install();
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_course_registry" ); // phpcs:ignore WordPress.DB -- Isolated fixture.
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}bfc_invite_codes" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}bfc_enrollments" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		delete_option( AccessTables::VERSION_OPTION );
		delete_option( EnrollmentTables::VERSION_OPTION );
		$this->assertTrue( ( new AccessTables() )->install() );
		$this->assertTrue( ( new EnrollmentTables() )->install() );
		( new Capabilities() )->install();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );
	}

	/** Clean fixtures committed by registry / approval / invite transactions. */
	public function tear_down(): void {
		global $wpdb;
		bfc_test_purge_courses();
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_course_registry" ); // phpcs:ignore WordPress.DB -- Isolated fixture.
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_invite_codes" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_enrollments" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB -- Registry transactions commit.
		global $wp_rest_server;
		$wp_rest_server = null;
		parent::tear_down();
	}

	/**
	 * Create a valid saved draft backed by real released mapping models.
	 *
	 * @param string $code Unique BFC code.
	 */
	private function course( string $code = 'BFC-210' ): int {
		$id = wp_insert_post(
			array(
				'post_type'   => 'bfc_course',
				'post_status' => 'draft',
				'post_title'  => 'Editor REST ' . $code,
				'meta_input'  => array_replace(
					array(
						'bfc_code'         => $code,
						'course_revision'  => '1',
						'subject_category' => Validator::SUBJECTS[ (int) $code[4] ],
						'course_outcome'   => 'independent_study',
					),
					bfc_test_mapping()
				),
			),
			true
		);
		$this->assertIsInt( $id, is_wp_error( $id ) ? $id->get_error_message() : '' );
		$this->assertGreaterThan( 0, $id );
		return $id;
	}

	/**
	 * Dispatch a JSON REST request under burbank-lms/v1.
	 *
	 * @param string               $method HTTP method.
	 * @param string               $route  Route under the namespace.
	 * @param array<string, mixed> $body   JSON body params.
	 */
	private function dispatch( string $method, string $route, array $body = array() ): WP_REST_Response {
		$request = new WP_REST_Request( $method, '/burbank-lms/v1' . $route );
		if ( array() !== $body ) {
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body( wp_json_encode( $body ) );
		}
		return rest_get_server()->dispatch( $request );
	}

	/** Unauthenticated callers receive 401 on editorial routes. */
	public function test_editorial_requires_authentication(): void {
		wp_set_current_user( 0 );
		$id = $this->course( 'BFC-211' );

		foreach (
			array(
				array( 'GET', '/courses/' . $id . '/editorial' ),
				array( 'POST', '/courses/' . $id . '/approve' ),
				array( 'POST', '/courses/' . $id . '/revision' ),
				array( 'POST', '/courses/' . $id . '/archive' ),
			) as $call
		) {
			$response = $this->dispatch( $call[0], $call[1], 'POST' === $call[0] && str_contains( $call[1], 'revision' ) ? array( 'revision' => '2' ) : array() );
			$this->assertSame( 401, $response->get_status(), $call[0] . ' ' . $call[1] );
		}
	}

	/** Editors without approve_bfc_courses cannot approve. */
	public function test_approve_requires_approver_cap(): void {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );
		$id       = $this->course( 'BFC-212' );
		$request  = new WP_REST_Request( 'POST', '/burbank-lms/v1/courses/' . $id . '/approve' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 403, $response->get_status() );
	}

	/** An approver can approve a valid saved course and GET returns snapshots. */
	public function test_approver_can_approve_and_get_returns_snapshots(): void {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		$id = $this->course( 'BFC-213' );

		$get = $this->dispatch( 'GET', '/courses/' . $id . '/editorial' );
		$this->assertSame( 200, $get->get_status() );
		$data = $get->get_data();
		$this->assertFalse( $data['approved'] );
		$this->assertNull( $data['approved_by'] );
		$this->assertNull( $data['approved_at'] );
		$this->assertTrue( $data['can_approve'] );
		$this->assertSame( array(), $data['snapshots'] );

		$approve = $this->dispatch( 'POST', '/courses/' . $id . '/approve' );
		$this->assertSame( 200, $approve->get_status() );
		$payload = $approve->get_data();
		$this->assertTrue( $payload['approved'] );
		$this->assertSame( $admin, $payload['approved_by'] );
		$this->assertNotEmpty( $payload['approved_at'] );
		$this->assertTrue( $payload['can_approve'] );
		$this->assertCount( 1, $payload['snapshots'] );
		$this->assertSame( 'BFC-213', $payload['snapshots'][0]['bfc_code'] );
		$this->assertSame( '1', $payload['snapshots'][0]['course_revision'] );

		$get_after = $this->dispatch( 'GET', '/courses/' . $id . '/editorial' );
		$this->assertSame( 200, $get_after->get_status() );
		$after = $get_after->get_data();
		$this->assertTrue( $after['approved'] );
		$this->assertCount( 1, $after['snapshots'] );
		$this->assertSame( (int) get_post_meta( $id, 'approved_by', true ), $after['approved_by'] );
		$this->assertSame( get_post_meta( $id, 'approved_at', true ), $after['approved_at'] );
	}

	/** Revision and archive update course meta and return editorial payload. */
	public function test_revision_and_archive_update_meta(): void {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		$id = $this->course( 'BFC-214' );
		$this->assertTrue( ( new Editorial() )->approve( $id ) );

		$revision = $this->dispatch( 'POST', '/courses/' . $id . '/revision', array( 'revision' => '2' ) );
		$this->assertSame( 200, $revision->get_status() );
		$this->assertSame( '2', get_post_meta( $id, 'course_revision', true ) );
		$rev_data = $revision->get_data();
		$this->assertFalse( $rev_data['approved'] );
		$this->assertNull( $rev_data['approved_by'] );
		$this->assertCount( 1, $rev_data['snapshots'] );

		$archive = $this->dispatch( 'POST', '/courses/' . $id . '/archive' );
		$this->assertSame( 200, $archive->get_status() );
		$this->assertSame( 'archived', get_post_meta( $id, 'catalog_state', true ) );
		$this->assertArrayHasKey( 'snapshots', $archive->get_data() );
	}

	/** Non-course posts are rejected with 404. */
	public function test_non_course_returns_404(): void {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		$post_id = (int) self::factory()->post->create();

		$response = $this->dispatch( 'GET', '/courses/' . $post_id . '/editorial' );
		$this->assertSame( 404, $response->get_status() );
	}

	/** Editors can read editorial state but can_approve is false. */
	public function test_editor_get_editorial_without_approve_cap(): void {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );
		$id = $this->course( 'BFC-215' );

		$response = $this->dispatch( 'GET', '/courses/' . $id . '/editorial' );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertFalse( $data['can_approve'] );
		$this->assertFalse( $data['approved'] );
	}

	/**
	 * Publish a course for enrollment fixtures (bypasses publication gates).
	 *
	 * @param string $code Unique BFC code.
	 */
	private function published_course( string $code = 'BFC-220' ): int {
		$id = $this->course( $code );
		update_post_meta( $id, 'catalog_state', 'published' );
		global $wpdb;
		$wpdb->update( // phpcs:ignore WordPress.DB -- Bypass publication guards for enrollment fixtures.
			$wpdb->posts,
			array( 'post_status' => 'publish' ),
			array( 'ID' => $id ),
			array( '%s' ),
			array( '%d' )
		);
		clean_post_cache( $id );
		$this->assertSame( 'publish', get_post_status( $id ) );
		return $id;
	}

	/** Create returns plaintext once; list omits code_hash; revoke clears the invite. */
	public function test_course_invite_create_list_and_revoke(): void {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		$id = $this->course( 'BFC-221' );

		$empty = $this->dispatch( 'GET', '/courses/' . $id . '/invites' );
		$this->assertSame( 200, $empty->get_status() );
		$empty_data = $empty->get_data();
		$this->assertIsArray( $empty_data );
		$this->assertArrayHasKey( 'invites', $empty_data );
		$this->assertSame( array(), $empty_data['invites'] );

		$create = $this->dispatch(
			'POST',
			'/courses/' . $id . '/invites',
			array(
				'label' => 'Sidebar batch',
				'kind'  => 'multi',
			)
		);
		$this->assertSame( 201, $create->get_status() );
		$created = $create->get_data();
		$this->assertIsArray( $created );
		$this->assertArrayHasKey( 'id', $created );
		$this->assertArrayHasKey( 'plaintext', $created );
		$this->assertIsInt( $created['id'] );
		$this->assertNotEmpty( $created['plaintext'] );
		$this->assertArrayNotHasKey( 'code_hash', $created );

		$list = $this->dispatch( 'GET', '/courses/' . $id . '/invites' );
		$this->assertSame( 200, $list->get_status() );
		$list_data = $list->get_data();
		$this->assertCount( 1, $list_data['invites'] );
		$row = $list_data['invites'][0];
		$this->assertSame( $created['id'], (int) $row['id'] );
		$this->assertSame( 'Sidebar batch', $row['label'] );
		$this->assertSame( 'multi', $row['kind'] );
		$this->assertArrayNotHasKey( 'code_hash', $row );
		$this->assertArrayNotHasKey( 'plaintext', $row );

		$revoke = $this->dispatch( 'DELETE', '/invites/' . $created['id'] );
		$this->assertSame( 200, $revoke->get_status() );
		$revoked = $revoke->get_data();
		$this->assertTrue( $revoked['revoked'] );

		$list_after = $this->dispatch( 'GET', '/courses/' . $id . '/invites' );
		$this->assertSame( 200, $list_after->get_status() );
		$after_row = $list_after->get_data()['invites'][0];
		$this->assertNotEmpty( $after_row['revoked_at'] );
	}

	/** Course invite and enrollment routes require enroll_bfc_courses. */
	public function test_invite_and_enrollment_routes_require_enroll_cap(): void {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );
		$id = $this->course( 'BFC-222' );

		foreach (
			array(
				array( 'GET', '/courses/' . $id . '/invites' ),
				array( 'POST', '/courses/' . $id . '/invites' ),
				array( 'GET', '/courses/' . $id . '/enrollments' ),
			) as $call
		) {
			$body     = 'POST' === $call[0]
				? array(
					'label' => 'x',
					'kind'  => 'once',
				)
				: array();
			$response = $this->dispatch( $call[0], $call[1], $body );
			$this->assertSame( 403, $response->get_status(), $call[0] . ' ' . $call[1] );
		}
	}

	/** Enrollments list is empty then includes the learner after admin_enroll. */
	public function test_enrollments_list_empty_then_after_admin_enroll(): void {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		$course_id = $this->published_course( 'BFC-223' );
		$learner   = self::factory()->user->create(
			array(
				'role'         => 'subscriber',
				'display_name' => 'Sidebar Learner',
			)
		);

		$empty = $this->dispatch( 'GET', '/courses/' . $course_id . '/enrollments' );
		$this->assertSame( 200, $empty->get_status() );
		$empty_data = $empty->get_data();
		$this->assertIsArray( $empty_data );
		$this->assertArrayHasKey( 'enrollments', $empty_data );
		$this->assertSame( array(), $empty_data['enrollments'] );

		$result = ( new EnrollmentService() )->admin_enroll( $admin, $learner, $course_id );
		$this->assertTrue( $result );

		$list = $this->dispatch( 'GET', '/courses/' . $course_id . '/enrollments' );
		$this->assertSame( 200, $list->get_status() );
		$rows = $list->get_data()['enrollments'];
		$this->assertCount( 1, $rows );
		$row = $rows[0];
		$this->assertSame( $learner, (int) $row['user_id'] );
		$this->assertSame( 'Sidebar Learner', $row['display_name'] );
		$this->assertSame( 'active', $row['status'] );
		$this->assertSame( 'admin', $row['source'] );
		$this->assertNotEmpty( $row['enrolled_at'] );
		$this->assertNull( $row['quiz_passed_at'] );
		$this->assertNull( $row['completed_at'] );
	}
}
