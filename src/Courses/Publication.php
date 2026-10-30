<?php
/**
 * Server-side course publication policy.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Courses;

use Burbank_LMS\Integrations\PressPrimer;

/** Shared checks for every publication surface. */
class Publication {
	/**
	 * Validate publication using trusted stored approval evidence.
	 *
	 * @param int                 $course_id Saved course ID.
	 * @param array<string,mixed> $fields Candidate course metadata.
	 */
	public function validate( int $course_id, array $fields ): \WP_Error {
		$error    = $this->validate_candidate( $course_id, $fields );
		$approver = (int) get_post_meta( $course_id, 'approved_by', true );
		if ( ! $approver || ! get_post_meta( $course_id, 'approved_at', true ) || ! user_can( $approver, 'approve_bfc_courses' ) || ! ( new Editorial() )->matches_approval( $course_id, $fields ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered by Courses\Capabilities.
			$error->add( 'burbank_course_approval', __( 'Obtain course approval before publishing.', 'burbank-lms' ), array( 'status' => 400 ) );
		}
		return $error;
	}

	/**
	 * Validate the saved candidate without requiring the approval being requested.
	 *
	 * @param int                 $course_id Saved course ID.
	 * @param array<string,mixed> $fields Candidate metadata.
	 */
	public function validate_candidate( int $course_id, array $fields ): \WP_Error {
		$error = ( new Validator() )->validate( $fields, $course_id );
		if ( ! $course_id || 'bfc_course' !== get_post_type( $course_id ) ) {
			$error->add( 'burbank_course_save_draft', __( 'Save this course as a draft before approval and publication.', 'burbank-lms' ), array( 'status' => 400 ) );
		}
		$mapping = ( new PressPrimer() )->validate_mapping( (int) ( $fields['pressprimer_quiz_id'] ?? 0 ), (int) ( $fields['certificate_template_id'] ?? 0 ) );
		$error->merge_from( $mapping );
		global $wpdb;
		$duplicate = $wpdb->get_var( $wpdb->prepare( "SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id WHERE p.post_type = %s AND p.post_status <> 'auto-draft' AND p.ID <> %d AND m.meta_key = %s AND CAST(m.meta_value AS UNSIGNED) = %d LIMIT 1", 'bfc_course', $course_id, 'pressprimer_quiz_id', (int) ( $fields['pressprimer_quiz_id'] ?? 0 ) ) ); // phpcs:ignore WordPress.DB -- Include drafts, trash and archives in assignment checks.
		if ( $duplicate || $wpdb->last_error ) {
			$error->add( 'burbank_course_duplicate_quiz', __( 'Assign a quiz used by no other course, including archived or trashed courses.', 'burbank-lms' ), array( 'status' => 400 ) );
		}
		$error->merge_from( $this->validate_matrix_slug( (string) ( $fields['matrix_requirement_slug'] ?? '' ) ) );
		return $error;
	}

	/**
	 * Soft-check an optional matrix slug only when bc_requirement exists.
	 *
	 * @param string $slug Candidate matrix_requirement_slug value.
	 */
	private function validate_matrix_slug( string $slug ): \WP_Error {
		$error = new \WP_Error();
		if ( '' === $slug || ! post_type_exists( 'bc_requirement' ) ) {
			return $error;
		}
		$matched = get_posts(
			array(
				'name'                   => $slug,
				'post_type'              => 'bc_requirement',
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		if ( ! $matched ) {
			$error->add(
				'burbank_course_matrix_requirement',
				__( 'Choose a published matrix requirement slug, or clear the field before publishing.', 'burbank-lms' ),
				array( 'status' => 400 )
			);
		}
		return $error;
	}
}
