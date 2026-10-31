<?php
/**
 * Course enrollment admin dispatch (REST/sidebar owns the course-screen UI).
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Enrollment;

/** CSRF-checked admin-enroll dispatch for tests and REST callers. */
class Admin {
	/**
	 * Admin-enroll a user after CSRF and capability checks.
	 *
	 * @param int    $course_id Course post ID.
	 * @param int    $user_id   Target learner user ID.
	 * @param string $nonce     Course-specific enroll nonce.
	 */
	public function dispatch_enroll( int $course_id, int $user_id, string $nonce ): true|\WP_Error {
		if ( ! wp_verify_nonce( $nonce, 'bfc_enrollment_admin_enroll_' . $course_id ) ) {
			return new \WP_Error( 'burbank_enrollment_nonce', __( 'The enrollment action expired or could not be verified. Reload and try again.', 'burbank-lms' ), array( 'status' => 403 ) );
		}
		if ( ! current_user_can( 'enroll_bfc_courses' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- enroll_bfc_courses from Capabilities VERSION 2.
			return new \WP_Error( 'burbank_enrollment_forbidden', __( 'You are not allowed to enroll learners.', 'burbank-lms' ), array( 'status' => 403 ) );
		}
		return ( new Service() )->admin_enroll( (int) get_current_user_id(), $user_id, $course_id );
	}
}
