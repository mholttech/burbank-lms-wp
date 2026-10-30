<?php
/**
 * Shared front-end data for learner blocks.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Learner;

/**
 * Builds JSON config attributes for REST-backed learner block scripts.
 */
class Block_Context {
	/**
	 * Return a JSON string of REST base URL and nonce for data attributes.
	 */
	public static function rest_config_json(): string {
		$config  = array(
			'restUrl' => esc_url_raw( rest_url( 'burbank-lms/v1' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'userUrl' => esc_url_raw( rest_url( 'wp/v2/users/me' ) ),
		);
		$encoded = wp_json_encode( $config );
		return is_string( $encoded ) ? $encoded : '{}';
	}

	/**
	 * Build a quiz player shortcode URL fragment for an enrolled course.
	 *
	 * Prefers an on-page shortcode anchor; falls back to embedding via query arg
	 * on the course permalink when PressPrimer is available.
	 *
	 * @param int $course_id Course post ID.
	 */
	public static function quiz_url( int $course_id ): string {
		$quiz_id = (int) get_post_meta( $course_id, 'pressprimer_quiz_id', true );
		if ( $quiz_id < 1 ) {
			return '';
		}
		$permalink = get_permalink( $course_id );
		if ( ! is_string( $permalink ) || '' === $permalink ) {
			return '';
		}
		return $permalink . '#bfc-course-quiz';
	}
}
