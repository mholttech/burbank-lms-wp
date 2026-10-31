<?php
/**
 * PressPrimer front-end skin enqueue (migrated from theme CSS).
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Integrations;

/**
 * Conditionally loads the compiled PressPrimer skin on learner surfaces.
 */
class PressPrimer_Assets {

	/** Webpack entry / style handle. */
	public const HANDLE = 'burbank-lms-pressprimer';

	/**
	 * Enqueue the PressPrimer skin when a course or PressPrimer surface is likely.
	 */
	public function enqueue(): void {
		if ( ! $this->needed() ) {
			return;
		}

		$entry    = self::HANDLE;
		$css_file = BURBANK_LMS_PATH . "build/{$entry}.css";

		if ( ! file_exists( $css_file ) ) {
			return;
		}

		// CSS-only entry: version from filemtime (avoid require of built *.asset.php for PHPStan).
		wp_enqueue_style(
			self::HANDLE,
			BURBANK_LMS_URL . "build/{$entry}.css",
			array(),
			(string) filemtime( $css_file )
		);
	}

	/**
	 * Whether the current request should load the PressPrimer skin.
	 */
	private function needed(): bool {
		if ( is_singular( 'bfc_course' ) || is_post_type_archive( 'bfc_course' ) ) {
			return true;
		}

		if ( has_block( 'burbank-lms/course' ) || has_block( 'burbank-lms/my-courses' ) ) {
			return true;
		}

		if ( ! is_singular() ) {
			return false;
		}

		return file_exists( WP_PLUGIN_DIR . '/pressprimer-quiz/pressprimer-quiz.php' )
			|| file_exists( WP_PLUGIN_DIR . '/pressprimer-certificate/pressprimer-certificate.php' );
	}
}
