<?php
/**
 * Course editor admin REST coverage (editorial routes).
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Courses\Capabilities;
use Burbank_LMS\Courses\Editorial;
use Burbank_LMS\Courses\Registry;
use Burbank_LMS\Courses\Schema;
use Burbank_LMS\Courses\Validator;

/** Exercises burbank-lms/v1 course editorial admin endpoints. */
class CourseEditorRestTest extends WP_UnitTestCase {
	/** Schema, registry, caps, and a fresh REST server before each case. */
	public function set_up(): void {
		parent::set_up();
		global $wpdb;
		$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB -- Registry/editorial transactions commit.
		pods_api()->cache_flush_pods();
		delete_option( Schema::VERSION_OPTION );
		( new Schema() )->register();
		( new Registry() )->install();
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_course_registry" ); // phpcs:ignore WordPress.DB -- Isolated fixture.
		( new Capabilities() )->install();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );
	}

	/** Clean fixtures committed by registry / approval transactions. */
	public function tear_down(): void {
		global $wpdb;
		bfc_test_purge_courses();
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_course_registry" ); // phpcs:ignore WordPress.DB -- Isolated fixture.
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
}
