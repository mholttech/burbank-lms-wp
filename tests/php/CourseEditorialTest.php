<?php
/**
 * Editorial workflow integration tests.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Courses\Admin;
use Burbank_LMS\Courses\Editorial;
use Burbank_LMS\Courses\Publication;
use Burbank_LMS\Courses\Registry;
use Burbank_LMS\Courses\Schema;
use Burbank_LMS\Courses\Validator;

/** Exercise real metadata, Pods and REST boundaries. */
class CourseEditorialTest extends WP_UnitTestCase {
	/** Recreate the committed registry fixture. */
	public function set_up(): void {
		parent::set_up();
		global $wpdb;
		$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB -- Registry transactions commit.
		pods_api()->cache_flush_pods();
		delete_option( Schema::VERSION_OPTION );
		( new Schema() )->register();
		( new Registry() )->install();
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_course_registry" ); // phpcs:ignore WordPress.DB -- Isolated fixture.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/** Clean fixtures committed by registry transactions. */
	public function tear_down(): void {
		global $wpdb;
		bfc_test_purge_courses();
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_course_registry" ); // phpcs:ignore WordPress.DB -- Isolated fixture.
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB -- Registry transactions commit.
		parent::tear_down();
	}

	/** Create a valid saved draft backed by real released mapping models. */
	private function course(): int {
		return wp_insert_post(
			array(
				'post_type'   => 'bfc_course',
				'post_status' => 'draft',
				'post_title'  => 'Scene safety',
				'meta_input'  => array_replace(
					array(
						'bfc_code'         => 'BFC-210',
						'course_revision'  => '1',
						'subject_category' => 'safety_field_awareness',
						'course_outcome'   => 'independent_study',
					),
					bfc_test_mapping()
				),
			)
		);
	}

	/** Editors can draft, but only an approver can create attributed snapshots. */
	public function test_permissions_and_approval_snapshot(): void {
		$this->assertTrue( class_exists( Editorial::class ), 'The editorial approval service must exist.' );
		$id    = $this->course();
		$admin = get_current_user_id();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertWPError( ( new Editorial() )->approve( $id ) );
		$this->assertSame( '', get_post_meta( $id, 'approved_by', true ) );
		wp_set_current_user( $admin );
		$this->assertTrue( ( new Editorial() )->approve( $id ) );
		$snapshots = ( new Editorial() )->snapshots( $id );
		$this->assertCount( 1, $snapshots );
		$snapshot = $snapshots[0];
		$this->assertSame( 'Scene safety', $snapshot['title'] );
		$this->assertSame( 'BFC-210', $snapshot['bfc_code'] );
		$this->assertSame( '1', $snapshot['course_revision'] );
		$this->assertSame( 'independent_study', $snapshot['course_outcome'] );
		$this->assertSame( (int) get_post_meta( $id, 'pressprimer_quiz_id', true ), $snapshot['pressprimer_quiz_id'] );
		$this->assertSame( (int) get_post_meta( $id, 'certificate_template_id', true ), $snapshot['certificate_template_id'] );
		$this->assertSame( $admin, $snapshot['approved_by'] );
		$this->assertSame( get_post_meta( $id, 'approved_at', true ), $snapshot['approved_at'] );
	}

	/** Invalid candidates and auto drafts cannot receive approval. */
	public function test_approval_requires_saved_valid_candidate(): void {
		$editorial = new Editorial();
		$this->assertWPError( $editorial->approve( 0 ) );
		$this->assertWPError( $editorial->approve( self::factory()->post->create() ) );
		$id = $this->course();
		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'auto-draft',
			)
		);
		$this->assertWPError( $editorial->approve( $id ) );
		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'draft',
			)
		);
		delete_post_meta( $id, 'certificate_template_id' );
		$this->assertWPError( $editorial->approve( $id ) );
		$this->assertSame( array(), $editorial->snapshots( $id ) );
	}

	/** No state-changing operation accepts a missing, wrong-operation or wrong-course nonce. */
	public function test_admin_actions_require_csrf_and_capability_checks(): void {
		$id    = $this->course();
		$admin = new Admin();
		foreach ( array( 'approve', 'revision', 'archive' ) as $operation ) {
			$this->assertWPError( $admin->dispatch( $id, $operation, '', '2' ) );
			$this->assertWPError( $admin->dispatch( $id, $operation, wp_create_nonce( 'bfc_course_approve_' . ( $id + 1 ) ), '2' ) );
		}
		$this->assertSame( '1', get_post_meta( $id, 'course_revision', true ) );
		$this->assertSame( '', get_post_meta( $id, 'catalog_state', true ) );
		$this->assertWPError( $admin->dispatch( $id, 'archive', wp_create_nonce( 'bfc_course_approve_' . $id ) ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertWPError( $admin->dispatch( $id, 'approve', wp_create_nonce( 'bfc_course_approve_' . $id ) ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertWPError( ( new Editorial() )->start_revision( $id, '2' ) );
		$this->assertWPError( ( new Editorial() )->archive( $id ) );
	}

	/** REST, Pods, classic metadata and row-ID writes cannot forge audit evidence. */
	public function test_clients_cannot_write_audit_fields(): void {
		$id                  = $this->course();
		$api                 = clone pods_api();
		$api->display_errors = 'wp_error';
		foreach ( array(
			'approved_by'    => 1,
			'approved_at'    => '2026-10-30',
			'approval_notes' => 'Forged',
		) as $key => $value ) {
			$this->assertFalse( update_post_meta( $id, $key, $value ) );
			$this->assertWPError(
				$api->save_pod_item(
					array(
						'pod'  => 'bfc_course',
						'id'   => $id,
						'data' => array( $key => $value ),
					)
				)
			);
			// REST treats audit metadata as read-only: submitted values are ignored, never stored.
			$request = new WP_REST_Request( 'POST', rest_get_route_for_post( $id ) );
			$request->set_param( 'meta', array( $key => $value ) );
			$this->assertSame( 200, rest_do_request( $request )->get_status() );
			$this->assertFalse( metadata_exists( 'post', $id, $key ), $key );
		}
		$this->assertTrue( ( new Editorial() )->approve( $id ) );
		$this->assertFalse( delete_post_meta( $id, 'approved_at' ) );
		$mid = add_post_meta( $id, 'unrelated', 'value' );
		$this->assertFalse( update_metadata_by_mid( 'post', $mid, 'forged', 'approved_at' ) );
	}

	/** REST meta writes are limited to course fields and tolerate clients echoing stored audit values. */
	public function test_rest_meta_is_limited_to_course_fields(): void {
		$id      = $this->course();
		$request = new WP_REST_Request( 'POST', rest_get_route_for_post( $id ) );
		$request->set_param(
			'meta',
			array(
				'matrix_requirement_slug' => 'scene-safety',
				'evil_key'                => 'injected',
				'_wp_page_template'       => 'evil.php',
			)
		);
		$this->assertSame( 200, rest_do_request( $request )->get_status() );
		$this->assertSame( 'scene-safety', get_post_meta( $id, 'matrix_requirement_slug', true ) );
		$this->assertFalse( metadata_exists( 'post', $id, 'evil_key' ) );
		$this->assertFalse( metadata_exists( 'post', $id, '_wp_page_template' ) );

		// REST responses report unset audit fields as empty defaults; echoing them back is harmless.
		$request->set_param(
			'meta',
			array(
				'matrix_requirement_slug' => 'scene-safety-2',
				'approved_by'             => 0,
				'approved_at'             => '',
				'approval_notes'          => '',
			)
		);
		$this->assertSame( 200, rest_do_request( $request )->get_status() );
		$this->assertSame( 'scene-safety-2', get_post_meta( $id, 'matrix_requirement_slug', true ) );

		$this->assertTrue( ( new Editorial() )->approve( $id ) );
		$approver = get_post_meta( $id, 'approved_by', true );
		$approved = get_post_meta( $id, 'approved_at', true );
		$request->set_param(
			'meta',
			array(
				'matrix_requirement_slug' => 'scene-safety-3',
				'approved_by'             => (int) $approver,
				'approved_at'             => $approved,
			)
		);
		$this->assertSame( 200, rest_do_request( $request )->get_status() );
		$this->assertSame( 'scene-safety-3', get_post_meta( $id, 'matrix_requirement_slug', true ) );
		$this->assertSame( $approver, get_post_meta( $id, 'approved_by', true ) );
		$this->assertSame( $approved, get_post_meta( $id, 'approved_at', true ) );
	}

	/** Each assessed field invalidates even when removed; snapshots survive. */
	public function test_assessed_metadata_changes_and_deletes_invalidate_approval(): void {
		$id        = $this->course();
		$editorial = new Editorial();
		foreach ( array(
			'course_revision'         => '2',
			'bfc_code'                => 'BFC-211',
			'pressprimer_quiz_id'     => 99999,
			'certificate_template_id' => 99999,
		) as $key => $value ) {
			$original = get_post_meta( $id, $key, true );
			$this->assertTrue( $editorial->approve( $id ) );
			$count = count( $editorial->snapshots( $id ) );
			$this->assertNotFalse( update_post_meta( $id, $key, $value ) );
			$this->assertSame( '', get_post_meta( $id, 'approved_by', true ), $key );
			$this->assertSame( '', get_post_meta( $id, 'approved_at', true ), $key );
			$this->assertCount( $count, $editorial->snapshots( $id ) );
			update_post_meta( $id, $key, $original );
		}
		foreach ( array( 'subject_category', 'course_outcome' ) as $key ) {
			$original = get_post_meta( $id, $key, true );
			$this->assertTrue( $editorial->approve( $id ) );
			$this->assertTrue( delete_post_meta( $id, $key ) );
			$this->assertSame( '', get_post_meta( $id, 'approved_by', true ), $key );
			update_post_meta( $id, $key, $original );
		}
	}

	/** A valid Pods batch invalidates; a rejected batch retains evidence. */
	public function test_pods_batch_invalidation_and_rejected_save(): void {
		$id = $this->course();
		$this->assertTrue( ( new Editorial() )->approve( $id ) );
		$history             = ( new Editorial() )->snapshots( $id );
		$api                 = clone pods_api();
		$api->display_errors = 'wp_error';
		$this->assertWPError(
			$api->save_pod_item(
				array(
					'pod'  => 'bfc_course',
					'id'   => $id,
					'data' => array( 'bfc_code' => 'BAD' ),
				)
			)
		);
		$this->assertNotEmpty( get_post_meta( $id, 'approved_by', true ) );
		$this->assertSame(
			$id,
			$api->save_pod_item(
				array(
					'pod'  => 'bfc_course',
					'id'   => $id,
					'data' => array(
						'bfc_code'         => 'BFC-310',
						'subject_category' => 'communications',
					),
				)
			)
		);
		$this->assertSame( 'communications', get_post_meta( $id, 'subject_category', true ) );
		$this->assertSame( '', get_post_meta( $id, 'approved_by', true ) );
		$this->assertSame( $history, ( new Editorial() )->snapshots( $id ) );
	}

	/** REST draft changes invalidate; publishing a different candidate cannot borrow old approval. */
	public function test_rest_assessed_edits_and_publish_request(): void {
		$id = $this->course();
		$this->assertTrue( ( new Editorial() )->approve( $id ) );
		$request = new WP_REST_Request( 'POST', rest_get_route_for_post( $id ) );
		$request->set_param( 'status', 'publish' );
		$request->set_param( 'meta', array( 'course_revision' => '2' ) );
		$this->assertSame( 400, rest_do_request( $request )->get_status() );
		$this->assertSame( '1', get_post_meta( $id, 'course_revision', true ) );
		$request->set_param( 'status', 'draft' );
		$this->assertSame( 200, rest_do_request( $request )->get_status() );
		$this->assertSame( '', get_post_meta( $id, 'approved_by', true ) );
		$this->assertCount( 1, ( new Editorial() )->snapshots( $id ) );
	}

	/** Material revision retires the live configuration and requires renewed approval. */
	public function test_material_revision_renewal_retains_both_snapshots(): void {
		$id        = $this->course();
		$editorial = new Editorial();
		$this->assertTrue( $editorial->approve( $id ) );
		$first = $editorial->snapshots( $id )[0];
		wp_publish_post( $id );
		$this->assertSame( 'publish', get_post_status( $id ) );
		foreach ( array( '', '1', str_repeat( 'a', 33 ) ) as $invalid ) {
			$this->assertWPError( $editorial->start_revision( $id, $invalid ) );
		}
		$this->assertSame( 'publish', get_post_status( $id ) );
		$approver = get_current_user_id();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertTrue( ( new Admin() )->dispatch( $id, 'revision', wp_create_nonce( 'bfc_course_revision_' . $id ), '2' ) );
		$this->assertSame( 'draft', get_post_status( $id ) );
		$this->assertSame( '2', get_post_meta( $id, 'course_revision', true ) );
		$this->assertTrue( ( new Publication() )->validate( $id, ( new Validator() )->fields( $id ) )->has_errors() );
		wp_publish_post( $id );
		$this->assertSame( 'draft', get_post_status( $id ) );
		wp_set_current_user( $approver );
		$this->assertTrue( ( new Admin() )->dispatch( $id, 'approve', wp_create_nonce( 'bfc_course_approve_' . $id ) ) );
		wp_publish_post( $id );
		$this->assertSame( 'publish', get_post_status( $id ) );
		$this->assertCount( 2, $editorial->snapshots( $id ) );
		$this->assertSame( $first, $editorial->snapshots( $id )[0] );
		$this->assertSame( '2', $editorial->snapshots( $id )[1]['course_revision'] );
	}

	/** Valid assessed edits of live courses withdraw publication until approval is renewed. */
	public function test_live_assessed_write_retires_publication(): void {
		$id = $this->course();
		$this->assertTrue( ( new Editorial() )->approve( $id ) );
		wp_publish_post( $id );
		$history = ( new Editorial() )->snapshots( $id );
		$this->assertNotFalse( update_post_meta( $id, 'course_revision', '2' ) );
		$this->assertSame( 'draft', get_post_status( $id ) );
		$this->assertSame( '', get_post_meta( $id, 'approved_by', true ) );
		$this->assertSame( $history, ( new Editorial() )->snapshots( $id ) );
	}

	/** A live Pods batch withdraws publication once, keeping every valid field it saved. */
	public function test_live_pods_assessed_batch_retires_publication(): void {
		$id = $this->course();
		$this->assertTrue( ( new Editorial() )->approve( $id ) );
		wp_publish_post( $id );
		$history             = ( new Editorial() )->snapshots( $id );
		$api                 = clone pods_api();
		$api->display_errors = 'wp_error';
		$this->assertSame(
			$id,
			$api->save_pod_item(
				array(
					'pod'  => 'bfc_course',
					'id'   => $id,
					'data' => array(
						'course_revision' => '2',
						'source_revision' => 'Rewritten module',
					),
				)
			)
		);
		$this->assertSame( 'draft', get_post_status( $id ) );
		$this->assertSame( '2', get_post_meta( $id, 'course_revision', true ) );
		$this->assertSame( 'Rewritten module', get_post_meta( $id, 'source_revision', true ) );
		$this->assertSame( '', get_post_meta( $id, 'approved_by', true ) );
		$this->assertSame( $history, ( new Editorial() )->snapshots( $id ) );
		$this->assertTrue( ( new Editorial() )->approve( $id ) );
		wp_publish_post( $id );
		$this->assertSame( 'publish', get_post_status( $id ) );
	}

	/** An assessed configuration change requires a new revision, as well as new approval. */
	public function test_changed_configuration_cannot_be_reapproved_under_old_revision(): void {
		$id        = $this->course();
		$editorial = new Editorial();
		$this->assertTrue( $editorial->approve( $id ) );
		update_post_meta( $id, 'bfc_code', 'BFC-211' );
		$result = $editorial->approve( $id );
		$this->assertWPError( $result );
		$this->assertSame( 'burbank_course_revision_required', $result->get_error_code() );
		$this->assertTrue( $editorial->start_revision( $id, '2' ) );
		$this->assertTrue( $editorial->approve( $id ) );
	}

	/** A code and revision label identify one assessed configuration across all history. */
	public function test_revision_labels_identify_one_configuration_across_history(): void {
		$id        = $this->course();
		$editorial = new Editorial();
		$this->assertTrue( $editorial->approve( $id ) );
		$this->assertTrue( $editorial->start_revision( $id, '2' ) );
		$this->assertTrue( $editorial->approve( $id ) );
		$this->assertTrue( $editorial->start_revision( $id, '3' ) );
		foreach ( array( '1', '2', '3' ) as $used ) {
			$this->assertWPError( $editorial->start_revision( $id, $used ), $used );
		}
		$this->assertSame( '3', get_post_meta( $id, 'course_revision', true ) );
		$original = array(
			'pressprimer_quiz_id'     => get_post_meta( $id, 'pressprimer_quiz_id', true ),
			'certificate_template_id' => get_post_meta( $id, 'certificate_template_id', true ),
		);
		foreach ( array_replace( bfc_test_mapping(), array( 'course_revision' => '1' ) ) as $key => $value ) {
			$this->assertNotFalse( update_post_meta( $id, $key, $value ), $key );
		}
		$result = $editorial->approve( $id );
		$this->assertWPError( $result );
		$this->assertSame( 'burbank_course_revision_required', $result->get_error_code() );
		$this->assertCount( 2, $editorial->snapshots( $id ) );
		foreach ( $original as $key => $value ) {
			$this->assertNotFalse( update_post_meta( $id, $key, $value ), $key );
		}
		$this->assertTrue( $editorial->approve( $id ) );
		$this->assertCount( 3, $editorial->snapshots( $id ) );
	}

	/** Repeated approval of the current configuration records no duplicate history. */
	public function test_reapproval_is_idempotent_until_approver_loses_authority(): void {
		$id        = $this->course();
		$editorial = new Editorial();
		$this->assertTrue( $editorial->approve( $id ) );
		$this->assertTrue( $editorial->approve( $id ) );
		$this->assertCount( 1, $editorial->snapshots( $id ) );
		$former = get_current_user_id();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		( new WP_User( $former ) )->set_role( 'editor' );
		$this->assertTrue( $editorial->approve( $id ) );
		$this->assertCount( 2, $editorial->snapshots( $id ) );
		$this->assertSame( get_current_user_id(), (int) get_post_meta( $id, 'approved_by', true ) );
	}

	/** Archive, not permanent deletion, retires a course with approval history. */
	public function test_approved_courses_cannot_be_permanently_deleted(): void {
		$id = $this->course();
		$this->assertTrue( ( new Editorial() )->approve( $id ) );
		wp_publish_post( $id );
		$history  = ( new Editorial() )->snapshots( $id );
		$approved = get_post_meta( $id, 'approved_by', true );
		$this->assertFalse( wp_delete_post( $id, true ) );
		$this->assertSame( 'publish', get_post_status( $id ) );
		$this->assertSame( $approved, get_post_meta( $id, 'approved_by', true ) );
		$this->assertSame( 'BFC-210', get_post_meta( $id, 'bfc_code', true ) );
		$this->assertSame( $history, ( new Editorial() )->snapshots( $id ) );
		wp_trash_post( $id );
		$this->assertFalse( wp_delete_post( $id, true ) );
		$this->assertSame( 'trash', get_post_status( $id ) );
		$this->assertSame( $history, ( new Editorial() )->snapshots( $id ) );
	}

	/** Courses without history delete completely, without status writes to the dying post. */
	public function test_courses_without_history_delete_without_orphans_or_invalidation(): void {
		global $wpdb;
		$id = $this->course();
		Validator::internal_write(
			static function () use ( $id ): void {
				update_post_meta( $id, 'approved_by', get_current_user_id() );
				update_post_meta( $id, 'approved_at', '2026-10-30 00:00:00' );
			}
		);
		// Stray live evidence without history is the only way invalidation could reach wp_update_post().
		$wpdb->update( $wpdb->posts, array( 'post_status' => 'publish' ), array( 'ID' => $id ) ); // phpcs:ignore WordPress.DB -- Fixture bypasses publication gates.
		clean_post_cache( $id );
		$updates = 0;
		$count   = static function () use ( &$updates ): void {
			++$updates;
		};
		add_action( 'post_updated', $count );
		try {
			$this->assertInstanceOf( WP_Post::class, wp_delete_post( $id, true ) );
		} finally {
			remove_action( 'post_updated', $count );
		}
		$this->assertSame( 0, $updates );
		$this->assertNull( get_post( $id ) );
		$this->assertSame( array(), $wpdb->get_col( $wpdb->prepare( "SELECT meta_key FROM {$wpdb->postmeta} WHERE post_id = %d", $id ) ) ); // phpcs:ignore WordPress.DB -- Authoritative orphan check.
	}

	/** Implicit submission from the revision field must start a revision, never approve. */
	public function test_enter_in_revision_field_submits_revision(): void {
		$id              = $this->course();
		$GLOBALS['post'] = get_post( $id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Admin footer reads the edited post.
		ob_start();
		( new Admin() )->controls( get_post( $id ) );
		( new Admin() )->action_form();
		$html = ob_get_clean();
		unset( $GLOBALS['post'] );
		$dom      = new DOMDocument();
		$internal = libxml_use_internal_errors( true );
		$dom->loadHTML( '<!DOCTYPE html><html><body>' . $html . '</body></html>' );
		libxml_use_internal_errors( $internal );
		$xpath = new DOMXPath( $dom );
		$form  = $xpath->query( '//input[@name="bfc_revision"]' )->item( 0 )->getAttribute( 'form' );
		$owned = sprintf( '//*[@form="%1$s"] | //form[@id="%1$s"]//*[not(@form)]', $form );
		$this->assertSame( 1, $xpath->query( sprintf( '//form[@id="%s"]', $form ) )->length );
		$default = $xpath->query( sprintf( '(%s)[self::button][not(@type) or @type="submit"]', $owned ) )->item( 0 );
		$this->assertSame( 'revision', $default->getAttribute( 'value' ) );
		$fields = array();
		foreach ( $xpath->query( sprintf( '(%s)[@name]', $owned ) ) as $node ) {
			$fields[ $node->getAttribute( 'name' ) ] = $node->getAttribute( 'value' );
		}
		$this->assertSame( 'bfc_course_editorial', $fields['action'] );
		$this->assertSame( (string) $id, $fields['bfc_course_id'] );
		$this->assertNotFalse( wp_verify_nonce( $fields['bfc_nonce_revision'], 'bfc_course_revision_' . $id ) );
	}

	/** Failed audit persistence must not leave a successful-looking historical approval. */
	public function test_failed_approval_rolls_back_snapshot_and_evidence(): void {
		$id   = $this->course();
		$fail = static fn( $check, $post_id, $key ) => $id === $post_id && 'approved_by' === $key ? false : $check;
		add_filter( 'update_post_metadata', $fail, 100, 3 );
		try {
			$this->assertWPError( ( new Editorial() )->approve( $id ) );
		} finally {
			remove_filter( 'update_post_metadata', $fail, 100 );
		}
		$this->assertSame( '', get_post_meta( $id, 'approved_by', true ) );
		$this->assertSame( '', get_post_meta( $id, 'approved_at', true ) );
		$this->assertSame( array(), ( new Editorial() )->snapshots( $id ) );
	}

	/** The registered admin endpoint rejects a CSRF attempt before changing storage. */
	public function test_registered_admin_handler_rejects_missing_nonce(): void {
		$id          = $this->course();
		$previous    = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Preserve request fixture.
		$_POST       = array(
			'bfc_course_id' => $id,
			'bfc_operation' => 'archive',
		);
		$die_handler = static function (): Closure {
			return static function ( $message ): void {
				throw new WPDieException( esc_html( $message ) );
			};
		};
		add_filter( 'wp_die_handler', $die_handler );
		try {
			do_action( 'admin_post_bfc_course_editorial' );
			$this->fail( 'Expected the registered handler to reject CSRF.' );
		} catch ( WPDieException $error ) {
			$this->assertStringContainsString( 'could not be verified', $error->getMessage() );
		} finally {
			$_POST = $previous;
			remove_filter( 'wp_die_handler', $die_handler );
		}
		$this->assertSame( '', get_post_meta( $id, 'catalog_state', true ) );
	}

	/** Ordinary post edits and identical metadata writes preserve current approval and history. */
	public function test_harmless_edits_retain_approval_and_historical_title(): void {
		$id = $this->course();
		$this->assertTrue( ( new Editorial() )->approve( $id ) );
		$history  = ( new Editorial() )->snapshots( $id );
		$approved = get_post_meta( $id, 'approved_by', true );
		wp_publish_post( $id );
		$request = new WP_REST_Request( 'POST', rest_get_route_for_post( $id ) );
		$request->set_param( 'title', 'Scene safety corrected' );
		$request->set_param( 'content', 'A spelling correction.' );
		$request->set_param( 'excerpt', 'Reworded summary.' );
		$request->set_param(
			'meta',
			array(
				'source_revision' => 'Source notes',
				'course_revision' => '1',
			)
		);
		$this->assertSame( 200, rest_do_request( $request )->get_status() );
		$this->assertSame( 'Scene safety corrected', get_post( $id )->post_title );
		$this->assertSame( $approved, get_post_meta( $id, 'approved_by', true ) );
		$this->assertSame( $history, ( new Editorial() )->snapshots( $id ) );
		$this->assertSame( 'publish', get_post_status( $id ) );
	}

	/** Snapshot rows cannot be replaced, appended or deleted by ordinary clients. */
	public function test_snapshot_history_is_immutable_through_metadata_apis(): void {
		$id = $this->course();
		$this->assertTrue( ( new Editorial() )->approve( $id ) );
		$key     = Editorial::SNAPSHOT_META;
		$history = ( new Editorial() )->snapshots( $id );
		$this->assertFalse( add_post_meta( $id, $key, array( 'forged' => true ) ) );
		$this->assertFalse( update_post_meta( $id, $key, array() ) );
		$this->assertFalse( delete_post_meta( $id, $key ) );
		$this->assertFalse( delete_post_meta_by_key( $key ) );
		global $wpdb;
		$mid = (int) $wpdb->get_var( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", $id, $key ) ); // phpcs:ignore WordPress.DB -- Row-ID bypass regression.
		$this->assertFalse( update_metadata_by_mid( 'post', $mid, array() ) );
		$this->assertFalse( delete_metadata_by_mid( 'post', $mid ) );
		$other = add_post_meta( $id, 'other_key', 'value' );
		$this->assertFalse( update_metadata_by_mid( 'post', $other, array(), $key ) );
		$this->assertSame( $history, ( new Editorial() )->snapshots( $id ) );
	}

	/** Archiving is independent of dependency health and retains published identity/history. */
	public function test_archive_preserves_status_identity_and_history(): void {
		$id = $this->course();
		$this->assertTrue( ( new Editorial() )->approve( $id ) );
		wp_publish_post( $id );
		$history      = ( new Editorial() )->snapshots( $id );
		$quiz         = PressPrimer_Quiz_Quiz::get( (int) get_post_meta( $id, 'pressprimer_quiz_id', true ) );
		$quiz->status = 'archived';
		$quiz->save();
		$this->assertTrue( ( new Admin() )->dispatch( $id, 'archive', wp_create_nonce( 'bfc_course_archive_' . $id ) ) );
		$this->assertSame( 'archived', get_post_meta( $id, 'catalog_state', true ) );
		$this->assertSame( 'publish', get_post_status( $id ) );
		$this->assertSame( $history, ( new Editorial() )->snapshots( $id ) );
		$this->assertNotEmpty( get_post_meta( $id, 'approved_by', true ) );
		$this->assertSame( '1', ( new Registry() )->get( $id )['published'] );
		$this->assertFalse( ( new Registry() )->available( 0, 'BFC-210' ) );
	}

	/** Mapping references must render as integer IDs, without locale grouping separators. */
	public function test_pods_mapping_ui_preserves_integer_reference_ids(): void {
		$field = pods_api()->load_field(
			array(
				'pod'  => 'bfc_course',
				'name' => 'pressprimer_quiz_id',
			)
		);
		$this->assertSame( '1234', PodsForm::field_method( 'number', 'format', 1234, 'pressprimer_quiz_id', $field ) );
	}

	/** Admin columns and controls expose status and CSRF-protected actions. */
	public function test_admin_columns_controls_and_bounded_pods_fields(): void {
		$id      = $this->course();
		$columns = apply_filters( 'manage_bfc_course_posts_columns', array( 'title' => 'Title' ) );
		foreach ( array( 'bfc_code', 'course_revision', 'catalog_state', 'bfc_approval' ) as $column ) {
			$this->assertArrayHasKey( $column, $columns );
		}
		ob_start();
		do_action( 'manage_bfc_course_posts_custom_column', 'bfc_code', $id );
		$this->assertStringContainsString( 'BFC-210', ob_get_clean() );
		ob_start();
		do_action( 'manage_bfc_course_posts_custom_column', 'catalog_state', $id );
		$this->assertSame( '—', ob_get_clean() );
		ob_start();
		( new Admin() )->controls( get_post( $id ) );
		$html = ob_get_clean();
		$this->assertStringContainsString( wp_create_nonce( 'bfc_course_approve_' . $id ), $html );
		$this->assertStringContainsString( 'Start material revision', $html );
		$this->assertStringContainsString( 'Archive', $html );
		$field = pods_api()->load_field(
			array(
				'pod'  => 'bfc_course',
				'name' => 'course_revision',
			)
		);
		$this->assertSame( 32, (int) $field['options']['text_max_length'] );
		$audit = pods_api()->load_field(
			array(
				'pod'  => 'bfc_course',
				'name' => 'approved_by',
			)
		);
		$this->assertNotEmpty( $audit['options']['hidden'] );
	}
}
