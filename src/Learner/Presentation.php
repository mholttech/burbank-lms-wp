<?php
/**
 * Learner-facing labels and enrollment status projection.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Learner;

/** Humanize catalog slugs and map enrollment rows to UI status. */
class Presentation {
	/**
	 * Turn an underscore slug into title-style words.
	 *
	 * @param string $slug Raw slug (e.g. subject_category value).
	 */
	public function label( string $slug ): string {
		$slug = trim( $slug );
		if ( '' === $slug ) {
			return '';
		}
		$parts = array_values(
			array_filter(
				explode( '_', $slug ),
				static fn( string $w ): bool => '' !== $w
			)
		);
		if ( array() === $parts ) {
			return '';
		}
		$first = array_shift( $parts );
		$head  = function_exists( 'mb_strtoupper' )
			? mb_strtoupper( mb_substr( $first, 0, 1 ) ) . mb_substr( $first, 1 )
			: ucfirst( $first );
		return implode( ' ', array_merge( array( $head ), $parts ) );
	}

	/**
	 * Project an enrollment row to a stable status token for UI.
	 *
	 * @param object|null $row Enrollment row from Repository::get.
	 */
	public function enrollment_status( ?object $row ): string {
		if ( ! $row ) {
			return 'none';
		}
		$status = isset( $row->status ) ? (string) $row->status : '';
		if ( 'withdrawn' === $status ) {
			return 'withdrawn';
		}
		if ( 'completed' === $status ) {
			return 'completed';
		}
		if ( 'active' === $status && ! empty( $row->quiz_passed_at ) ) {
			return 'passed';
		}
		if ( 'active' === $status ) {
			return 'enrolled';
		}
		return 'none';
	}

	/**
	 * Translated message for a projected enrollment status.
	 *
	 * @param string $status Token from enrollment_status().
	 */
	public function status_label( string $status ): string {
		return match ( $status ) {
			'enrolled' => __( 'You are enrolled in this course.', 'burbank-lms' ),
			'passed' => __( 'Quiz passed — certificate pending.', 'burbank-lms' ),
			'completed' => __( 'Course completed.', 'burbank-lms' ),
			'withdrawn' => __( 'You withdrew from this course.', 'burbank-lms' ),
			default => '',
		};
	}
}
