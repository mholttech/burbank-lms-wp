<?php
/**
 * Branded WordPress login screen for training learners.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Access;

use Burbank_LMS\Branding\Site;

/**
 * Skins `wp-login.php` to match the learner portal and points Register at `/register/`.
 */
class Login {
	/** Webpack CSS entry / style handle. */
	public const HANDLE = 'burbank-lms-login';

	/**
	 * Enqueue the login stylesheet and logo inline CSS.
	 */
	public function enqueue_assets(): void {
		$css_file = BURBANK_LMS_PATH . 'build/' . self::HANDLE . '.css';
		if ( ! file_exists( $css_file ) ) {
			return;
		}

		wp_enqueue_style(
			self::HANDLE,
			BURBANK_LMS_URL . 'build/' . self::HANDLE . '.css',
			array(),
			(string) filemtime( $css_file )
		);

		$logo = ( new Site() )->logo_url();
		if ( '' === $logo ) {
			return;
		}
		$safe = esc_url_raw( $logo );
		if ( '' === $safe ) {
			return;
		}
		wp_add_inline_style(
			self::HANDLE,
			sprintf(
				'body.login.bfc-login #login h1 a{background-image:url(%s);}',
				esc_url( $safe )
			)
		);
	}

	/**
	 * Add branding body classes on the login screen.
	 *
	 * @param string[] $classes Existing login body classes.
	 * @return string[]
	 */
	public function body_class( array $classes ): array {
		$classes[] = 'bfc-login';
		return $classes;
	}

	/** Point the login logo link at the site home. */
	public function header_url(): string {
		return home_url( '/' );
	}

	/** Accessible title for the login logo link. */
	public function header_text(): string {
		return ( new Site() )->product_name();
	}

	/**
	 * Send core Register links to the Burbank register page.
	 *
	 * @param string $url Upstream register URL.
	 */
	public function register_url( string $url ): string {
		unset( $url );
		return home_url( '/register/' );
	}

	/**
	 * Print Training kicker above the login form.
	 */
	public function render_header(): void {
		$site = new Site();
		printf(
			'<p class="bfc-login__caption">%s</p><p class="bfc-login__lede">%s</p>',
			esc_html( $site->kicker() ),
			esc_html__( 'Sign in with your training account.', 'burbank-lms' )
		);
	}

	/**
	 * Print a register CTA beneath the login form.
	 */
	public function render_footer(): void {
		printf(
			'<p class="bfc-login__register"><a href="%1$s">%2$s</a></p>',
			esc_url( home_url( '/register/' ) ),
			esc_html__( 'Create a training account', 'burbank-lms' )
		);
	}
}
