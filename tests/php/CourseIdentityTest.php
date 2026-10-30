<?php
/**
 * Permanent course identity application coverage.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Courses\Registry;
use Burbank_LMS\Courses\Validator;
use Burbank_LMS\Courses\Schema;

/** Exercises database identity and every supported write surface. */
class CourseIdentityTest extends WP_UnitTestCase {
	/** Recreate test schema and clear reservations between cases. */
	public function set_up(): void {
		parent::set_up();
		global $wpdb;
		// Registry transactions commit; opt out of PHPUnit's wrapping transaction.
		$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB -- Transaction integration fixture.
		pods_api()->cache_flush_pods();
		delete_option( Schema::VERSION_OPTION );
		( new Schema() )->register();
		( new Registry() )->install();
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_course_registry" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/** Explicit cleanup is required because registry transactions commit real fixtures. */
	public function tear_down(): void {
		global $wpdb;
		$ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'bfc_course'" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		foreach ( $ids as $id ) {
			wp_delete_post( (int) $id, true );
		}
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_course_registry" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB -- Transaction integration fixture.
		parent::tear_down();
	}

	/** A complete independent-study metadata fixture. */
	private function fields(): array {
		return array(
			'bfc_code'                => 'BFC-210',
			'subject_category'        => 'safety_field_awareness',
			'course_revision'         => '1.0',
			'course_outcome'          => 'independent_study',
			'pressprimer_quiz_id'     => 1,
			'certificate_template_id' => 2,
		);
	}

	/** Every specified subject range accepts its boundaries and rejects another subject. */
	public function test_format_and_all_subject_ranges(): void {
		$validator = new Validator();
		foreach ( Validator::SUBJECTS as $hundred => $subject ) {
			foreach ( array( $hundred * 100, $hundred * 100 + 99 ) as $number ) {
				$fields = array_replace(
					$this->fields(),
					array(
						'bfc_code'         => sprintf( 'BFC-%03d', $number ),
						'subject_category' => $subject,
					)
				);
				$this->assertFalse( $validator->validate( $fields, 0 )->has_errors() );
				$fields['subject_category'] = 'wrong';
				$this->assertTrue( $validator->validate( $fields, 0 )->has_errors() );
			}
		}
		foreach ( array( 'IS-210', 'BFC-21', 'BFC-0210', 'BFC-000', 'bfc-210', 'BFC-210-r1', "BFC-210\n" ) as $code ) {
			$this->assertTrue( $validator->validate( array_replace( $this->fields(), array( 'bfc_code' => $code ) ), 0 )->has_errors(), $code );
		}
	}

	/** Reject unsafe references, claims and unbounded text. */
	public function test_metadata_contract(): void {
		$validator = new Validator();
		foreach ( array(
			'pressprimer_quiz_id'     => 0,
			'certificate_template_id' => -1,
			'course_outcome'          => 'practical_certification',
			'course_revision'         => str_repeat( 'a', 33 ),
			'source_revision'         => str_repeat( 'a', 192 ),
			'approval_notes'          => str_repeat( 'a', 2001 ),
		) as $key => $value ) {
			$this->assertTrue( $validator->validate( array_replace( $this->fields(), array( $key => $value ) ), 0 )->has_errors(), $key );
		}
		foreach ( array( '1.5', '2e3', array( 1 ), true ) as $value ) {
			$this->assertTrue( $validator->validate( array_replace( $this->fields(), array( 'pressprimer_quiz_id' => $value ) ), 0 )->has_errors() );
		}
	}

	/** Draft moves release old numbers; published and deleted identities persist. */
	public function test_reservations_and_tombstones(): void {
		$a        = self::factory()->post->create( array( 'post_type' => 'bfc_course' ) );
		$b        = self::factory()->post->create( array( 'post_type' => 'bfc_course' ) );
		$registry = new Registry();
		$this->assertTrue( $registry->reserve( $a, 'BFC-210' ) );
		$this->assertFalse( $registry->reserve( $b, 'BFC-210' ) );
		$this->assertTrue( $registry->reserve( $a, 'BFC-220' ) );
		$this->assertTrue( $registry->reserve( $b, 'BFC-210' ) );
		$this->assertTrue( $registry->publish( $a ) );
		$this->assertTrue( $registry->publish( $a ) );
		$this->assertFalse( $registry->reserve( $a, 'BFC-230' ) );
		wp_trash_post( $a );
		$this->assertFalse( $registry->reserve( $b, 'BFC-220' ) );
		wp_delete_post( $a, true );
		$this->assertFalse( $registry->reserve( $b, 'BFC-220' ) );
		$this->assertFalse( $registry->publish( 999999 ) );
	}

	/** Core metadata cannot bypass field validation, immutability or audit protection. */
	public function test_core_meta_write_guards(): void {
		$id = self::factory()->post->create( array( 'post_type' => 'bfc_course' ) );
		$this->assertFalse( update_post_meta( $id, 'bfc_code', 'IS-210' ) );
		$this->assertTrue( (bool) update_post_meta( $id, 'bfc_code', 'BFC-210' ) );
		$this->assertTrue( ( new Registry() )->publish( $id ) );
		$this->assertFalse( update_post_meta( $id, 'bfc_code', 'BFC-220' ) );
		$this->assertFalse( delete_post_meta( $id, 'bfc_code' ) );
		$this->assertFalse( add_post_meta( $id, 'bfc_code', 'BFC-230' ) );
		$this->assertFalse( update_post_meta( $id, 'approved_by', get_current_user_id() ) );
		$this->assertFalse( update_post_meta( $id, 'pressprimer_quiz_id', -1 ) );
		$this->assertSame( 'BFC-210', get_post_meta( $id, 'bfc_code', true ) );
	}

	/** Pods returns an actionable validation error before mutating any supplied fields. */
	public function test_pods_rejects_entire_invalid_save(): void {
		$id = self::factory()->post->create( array( 'post_type' => 'bfc_course' ) );
		update_post_meta( $id, 'bfc_code', 'BFC-210' );
		$api                 = clone pods_api();
		$api->display_errors = 'wp_error';
		$result              = $api->save_pod_item(
			array(
				'pod'  => 'bfc_course',
				'id'   => $id,
				'data' => array(
					'bfc_code'       => 'BFC-220',
					'course_outcome' => 'practical',
				),
			)
		);
		$this->assertWPError( $result );
		$this->assertSame( 'BFC-210', get_post_meta( $id, 'bfc_code', true ) );
		$this->assertTrue( ( new Registry() )->reserve( $id, 'BFC-230' ) );
	}

	/** REST preflight rejects a whole batch before applying valid members. */
	public function test_rest_rejects_invalid_batch(): void {
		$id = self::factory()->post->create( array( 'post_type' => 'bfc_course' ) );
		update_post_meta( $id, 'bfc_code', 'BFC-210' );
		$request = new WP_REST_Request( 'POST', '/wp/v2/bfc_course/' . $id );
		$request->set_param(
			'meta',
			array(
				'bfc_code'       => 'BFC-220',
				'course_outcome' => 'practical',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'BFC-210', get_post_meta( $id, 'bfc_code', true ) );
	}
	/** Overlapping connections cannot both acquire the same code. */
	public function test_database_arbitrates_concurrent_reservations(): void {
		global $wpdb;
		$peer  = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
		$table = $wpdb->prefix . 'bfc_course_registry';
		// phpcs:disable WordPress.DB, WordPress.WP.AlternativeFunctions -- Real independent overlapping database sessions.
		$wpdb->query( 'START TRANSACTION' );
		$this->assertSame( 1, $wpdb->query( $wpdb->prepare( "INSERT INTO {$table} (course_id, code) VALUES (%d, %s)", 800001, 'BFC-210' ) ) );
		$this->assertTrue( mysqli_query( $peer->dbh, $peer->prepare( "INSERT INTO {$table} (course_id, code) VALUES (%d, %s)", 800002, 'BFC-210' ), MYSQLI_ASYNC ) );
		$wpdb->query( 'COMMIT' );
		// Suppress the expected duplicate-key warning, inspecting the driver errno below.
		$this->assertFalse( @mysqli_reap_async_query( $peer->dbh ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$this->assertSame( 1062, mysqli_errno( $peer->dbh ) );
		$peer->close();
		$this->assertFalse( ( new Registry() )->reserve( 800002, 'BFC-210' ) );
		// phpcs:enable
	}

	/** By-ID, duplicate-row and bulk APIs cannot bypass identity protections. */
	public function test_alternate_metadata_apis(): void {
		$id  = self::factory()->post->create( array( 'post_type' => 'bfc_course' ) );
		$mid = add_post_meta( $id, 'bfc_code', 'BFC-210' );
		$this->assertNotFalse( $mid );
		$this->assertFalse( add_post_meta( $id, 'bfc_code', 'BFC-210' ) );
		$this->assertFalse( update_metadata_by_mid( 'post', $mid, 'BFC-220' ) );
		$this->assertFalse( delete_metadata_by_mid( 'post', $mid ) );
		$this->assertFalse( delete_post_meta_by_key( 'bfc_code' ) );
		$other = add_post_meta( $id, 'unrelated', 'BFC-230' );
		$this->assertFalse( update_metadata_by_mid( 'post', $other, 'BFC-230', 'bfc_code' ) );
		$this->assertSame( array( 'BFC-210' ), get_post_meta( $id, 'bfc_code', false ) );
	}

	/** Valid Pods batches can move draft identity across subject ranges together. */
	public function test_valid_pods_batch_and_unauthorized_save(): void {
		$id                  = self::factory()->post->create( array( 'post_type' => 'bfc_course' ) );
		$api                 = clone pods_api();
		$api->display_errors = 'wp_error';
		$this->assertSame(
			$id,
			$api->save_pod_item(
				array(
					'pod'  => 'bfc_course',
					'id'   => $id,
					'data' => $this->fields(),
				)
			)
		);
		$data = array(
			'bfc_code'         => 'BFC-310',
			'subject_category' => 'communications',
		);
		$this->assertSame(
			$id,
			$api->save_pod_item(
				array(
					'pod'  => 'bfc_course',
					'id'   => $id,
					'data' => $data,
				)
			)
		);
		$this->assertSame( 'BFC-310', get_post_meta( $id, 'bfc_code', true ) );
		$this->assertSame( 'communications', get_post_meta( $id, 'subject_category', true ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertWPError(
			$api->save_pod_item(
				array(
					'pod'  => 'bfc_course',
					'id'   => $id,
					'data' => array( 'bfc_code' => 'BFC-320' ),
				)
			)
		);
		$this->assertFalse( update_post_meta( $id, 'course_revision', '2' ) );
	}

	/** Programmatic publication seals identity even after reverting the post to draft. */
	public function test_publication_seals_identity(): void {
		$id = wp_insert_post(
			array(
				'post_type'   => 'bfc_course',
				'post_title'  => 'Scene safety',
				'post_status' => 'publish',
				'meta_input'  => $this->fields(),
			)
		);
		$this->assertSame( 'publish', get_post_status( $id ) );
		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'draft',
			)
		);
		$this->assertFalse( update_post_meta( $id, 'bfc_code', 'BFC-220' ) );
		$this->assertSame( 'BFC-210', ( new Registry() )->get( $id )['code'] );
		$this->assertWPError(
			wp_insert_post(
				array(
					'post_type'  => 'bfc_course',
					'post_title' => 'Invalid',
					'meta_input' => array( 'bfc_code' => 'IS-210' ),
				),
				true
			)
		);
	}
	/** Repeated migrations retain permanent rows and failed persistence rolls back a draft move. */
	public function test_migration_and_failed_reservation(): void {
		$id = self::factory()->post->create( array( 'post_type' => 'bfc_course' ) );
		update_post_meta( $id, 'bfc_code', 'BFC-210' );
		$registry = new Registry();
		$this->assertFalse( $registry->reserve( $id, 'BFC-220', static fn (): bool => false ) );
		$this->assertSame( 'BFC-210', $registry->get( $id )['code'] );
		$this->assertTrue( $registry->publish( $id ) );
		delete_option( Registry::VERSION_OPTION );
		$this->assertTrue( $registry->install() );
		$this->assertTrue( $registry->install() );
		$this->assertFalse( $registry->reserve( $id, 'BFC-220' ) );
	}
}
