<?php
/**
 * Course list columns and editorial dispatch (REST/sidebar owns the editor UI).
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Courses;

use Burbank_LMS\Enrollment\Repository;

/** List-table columns and CSRF-checked editorial dispatch for tests and callers. */
class Admin {
	/**
	 * Per-request enrollment count cache keyed by course ID.
	 *
	 * @var array<int, array{enrolled:int,passed:int,completed:int}>
	 */
	private array $enrollment_counts = array();

	/**
	 * Validate CSRF before dispatching to capability-checked services.
	 *
	 * @param int    $course_id Course post ID.
	 * @param string $operation Explicit editorial action.
	 * @param string $nonce Action/course-specific nonce.
	 * @param string $revision New revision label.
	 */
	public function dispatch( int $course_id, string $operation, string $nonce, string $revision = '' ): \WP_Error|bool {
		if ( ! in_array( $operation, array( 'approve', 'revision', 'archive' ), true ) || ! wp_verify_nonce( $nonce, 'bfc_course_' . $operation . '_' . $course_id ) ) {
			return new \WP_Error( 'burbank_course_nonce', __( 'The course action expired or could not be verified. Reload the editor and try again.', 'burbank-lms' ), array( 'status' => 403 ) );
		}
		$editorial = new Editorial();
		return match ( $operation ) {
			'approve' => $editorial->approve( $course_id ),
			'revision' => $editorial->start_revision( $course_id, $revision ),
			'archive' => $editorial->archive( $course_id ),
		};
	}

	/**
	 * Add the editorial status columns to the course list.
	 *
	 * @param array<string,string> $columns Core columns.
	 * @return array<string,string> Course columns.
	 */
	public function columns( array $columns ): array {
		return array_merge(
			$columns,
			array(
				'bfc_code'        => __( 'BFC code', 'burbank-lms' ),
				'course_revision' => __( 'Revision', 'burbank-lms' ),
				'catalog_state'   => __( 'Catalog state', 'burbank-lms' ),
				'bfc_approval'    => __( 'Approval', 'burbank-lms' ),
				'bfc_enrolled'    => __( 'Enrolled', 'burbank-lms' ),
				'bfc_passed'      => __( 'Passed', 'burbank-lms' ),
				'bfc_completed'   => __( 'Completed', 'burbank-lms' ),
			)
		);
	}

	/**
	 * Render escaped list values and meaningful approval state.
	 *
	 * @param string $column Column key.
	 * @param int    $course_id Course post ID.
	 */
	public function column( string $column, int $course_id ): void {
		if ( 'bfc_approval' === $column ) {
			$approved = ( new Editorial() )->matches_approval( $course_id, ( new Validator() )->fields( $course_id ) ) && user_can( (int) get_post_meta( $course_id, 'approved_by', true ), 'approve_bfc_courses' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered course capability.
			echo esc_html( $approved ? __( 'Approved', 'burbank-lms' ) : __( 'Approval required', 'burbank-lms' ) );
		} elseif ( in_array( $column, array( 'bfc_enrolled', 'bfc_passed', 'bfc_completed' ), true ) ) {
			if ( ! isset( $this->enrollment_counts[ $course_id ] ) ) {
				$this->enrollment_counts[ $course_id ] = ( new Repository() )->counts_for_course( $course_id );
			}
			$counts = $this->enrollment_counts[ $course_id ];
			$key    = match ( $column ) {
				'bfc_enrolled' => 'enrolled',
				'bfc_passed' => 'passed',
				'bfc_completed' => 'completed',
			};
			echo esc_html( (string) $counts[ $key ] );
		} elseif ( in_array( $column, array( 'bfc_code', 'course_revision', 'catalog_state' ), true ) ) {
			$value = get_post_meta( $course_id, $column, true );
			echo esc_html( $value ?: '—' );
		}
	}
}
