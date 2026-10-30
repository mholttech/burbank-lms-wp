<?php
/**
 * Soft matrix requirement slug validation on publish.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Courses\Publication;
use Burbank_LMS\Courses\Registry;
use Burbank_LMS\Courses\Schema;
use Burbank_LMS\Courses\Validator;

/** Exercise Path A matrix soft-check against optional bc_requirement posts. */
class CourseMatrixValidationTest extends WP_UnitTestCase {
	/** Recreate schema and clear reservations between cases. */
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
		$requirement_ids = get_posts(
			array(
				'post_type'              => 'bc_requirement',
				'post_status'            => 'any',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		foreach ( $requirement_ids as $requirement_id ) {
			wp_delete_post( (int) $requirement_id, true );
		}
		if ( post_type_exists( 'bc_requirement' ) ) {
			unregister_post_type( 'bc_requirement' );
		}
		bfc_test_purge_courses();
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_course_registry" ); // phpcs:ignore WordPress.DB -- Isolated fixture.
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB -- Registry transactions commit.
		parent::tear_down();
	}

	/**
	 * Create an approved draft with optional matrix slug.
	 *
	 * @param string $slug Matrix requirement slug.
	 * @param string $code BFC code.
	 */
	private function course( string $slug, string $code = 'BFC-270' ): int {
		$id = wp_insert_post(
			array(
				'post_type'   => 'bfc_course',
				'post_title'  => 'Matrix ' . $code,
				'post_status' => 'draft',
				'meta_input'  => array_replace(
					array(
						'bfc_code'                => $code,
						'subject_category'        => Validator::SUBJECTS[ (int) $code[4] ],
						'course_revision'         => '1',
						'course_outcome'          => 'independent_study',
						'matrix_requirement_slug' => $slug,
					),
					bfc_test_mapping()
				),
			)
		);
		bfc_test_approve( $id );
		return $id;
	}

	/** Unknown slug blocks publish when bc_requirement exists. */
	public function test_unknown_slug_blocks_publish_when_requirement_type_exists(): void {
		register_post_type(
			'bc_requirement',
			array(
				'public' => true,
				'label'  => 'Requirements',
			)
		);
		self::factory()->post->create(
			array(
				'post_type'   => 'bc_requirement',
				'post_status' => 'publish',
				'post_name'   => 'scene-safety',
				'post_title'  => 'Scene Safety',
			)
		);

		$id    = $this->course( 'missing-requirement' );
		$error = ( new Publication() )->validate( $id, ( new Validator() )->fields( $id ) );
		$this->assertContains( 'burbank_course_matrix_requirement', $error->get_error_codes() );
		wp_publish_post( $id );
		$this->assertSame( 'draft', get_post_status( $id ) );
	}

	/** Known published slug passes when bc_requirement exists. */
	public function test_published_slug_passes_when_requirement_type_exists(): void {
		register_post_type(
			'bc_requirement',
			array(
				'public' => true,
				'label'  => 'Requirements',
			)
		);
		self::factory()->post->create(
			array(
				'post_type'   => 'bc_requirement',
				'post_status' => 'publish',
				'post_name'   => 'scene-safety',
				'post_title'  => 'Scene Safety',
			)
		);

		$id    = $this->course( 'scene-safety', 'BFC-271' );
		$error = ( new Publication() )->validate( $id, ( new Validator() )->fields( $id ) );
		$this->assertNotContains( 'burbank_course_matrix_requirement', $error->get_error_codes() );
		$this->assertFalse( $error->has_errors() );
		wp_publish_post( $id );
		$this->assertSame( 'publish', get_post_status( $id ) );
	}

	/** Draft requirement posts do not satisfy the soft check. */
	public function test_draft_requirement_slug_blocks_publish(): void {
		register_post_type(
			'bc_requirement',
			array(
				'public' => true,
				'label'  => 'Requirements',
			)
		);
		self::factory()->post->create(
			array(
				'post_type'   => 'bc_requirement',
				'post_status' => 'draft',
				'post_name'   => 'scene-safety',
				'post_title'  => 'Scene Safety',
			)
		);

		$id    = $this->course( 'scene-safety', 'BFC-272' );
		$error = ( new Publication() )->validate( $id, ( new Validator() )->fields( $id ) );
		$this->assertContains( 'burbank_course_matrix_requirement', $error->get_error_codes() );
	}

	/** Absent bc_requirement type never fails publish for matrix reasons. */
	public function test_absent_requirement_type_does_not_fail_for_matrix(): void {
		$this->assertFalse( post_type_exists( 'bc_requirement' ) );
		$id    = $this->course( 'any-external-slug', 'BFC-273' );
		$error = ( new Publication() )->validate( $id, ( new Validator() )->fields( $id ) );
		$this->assertNotContains( 'burbank_course_matrix_requirement', $error->get_error_codes() );
		$this->assertFalse( $error->has_errors() );
		wp_publish_post( $id );
		$this->assertSame( 'publish', get_post_status( $id ) );
	}

	/** Empty matrix slug remains optional even when the type exists. */
	public function test_empty_slug_is_optional_when_requirement_type_exists(): void {
		register_post_type(
			'bc_requirement',
			array(
				'public' => true,
				'label'  => 'Requirements',
			)
		);
		$id    = $this->course( '', 'BFC-274' );
		$error = ( new Publication() )->validate( $id, ( new Validator() )->fields( $id ) );
		$this->assertNotContains( 'burbank_course_matrix_requirement', $error->get_error_codes() );
		$this->assertFalse( $error->has_errors() );
	}

	/** Already-published courses keep publish status on ordinary editorial saves even with a bad matrix slug. */
	public function test_published_course_editorial_save_ignores_bad_matrix_slug(): void {
		register_post_type(
			'bc_requirement',
			array(
				'public' => true,
				'label'  => 'Requirements',
			)
		);
		self::factory()->post->create(
			array(
				'post_type'   => 'bc_requirement',
				'post_status' => 'publish',
				'post_name'   => 'scene-safety',
				'post_title'  => 'Scene Safety',
			)
		);

		$id = $this->course( 'scene-safety', 'BFC-275' );
		wp_publish_post( $id );
		$this->assertSame( 'publish', get_post_status( $id ) );

		update_post_meta( $id, 'matrix_requirement_slug', 'missing-requirement' );
		$result = wp_update_post(
			array(
				'ID'           => $id,
				'post_title'   => 'Editorial title change',
				'post_excerpt' => 'Editorial excerpt',
				'post_status'  => 'publish',
			),
			true
		);
		$this->assertNotWPError( $result );
		$this->assertSame( 'publish', get_post_status( $id ) );
		$this->assertSame( 'Editorial title change', get_post( $id )->post_title );
		$this->assertSame( 'Editorial excerpt', get_post( $id )->post_excerpt );
		$this->assertSame( 'missing-requirement', get_post_meta( $id, 'matrix_requirement_slug', true ) );

		$draft = $this->course( 'missing-requirement', 'BFC-276' );
		wp_publish_post( $draft );
		$this->assertSame( 'draft', get_post_status( $draft ) );
		$this->assertContains(
			'burbank_course_matrix_requirement',
			( new Publication() )->validate( $draft, ( new Validator() )->fields( $draft ) )->get_error_codes()
		);
	}

	/** Approval candidate checks do not require a resolvable matrix slug. */
	public function test_validate_candidate_skips_matrix_slug(): void {
		register_post_type(
			'bc_requirement',
			array(
				'public' => true,
				'label'  => 'Requirements',
			)
		);
		$id = $this->course( 'missing-requirement', 'BFC-277' );
		$this->assertNotContains(
			'burbank_course_matrix_requirement',
			( new Publication() )->validate_candidate( $id, ( new Validator() )->fields( $id ) )->get_error_codes()
		);
		$this->assertContains(
			'burbank_course_matrix_requirement',
			( new Publication() )->validate( $id, ( new Validator() )->fields( $id ) )->get_error_codes()
		);
	}
}
