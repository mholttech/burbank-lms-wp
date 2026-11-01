<?php
/**
 * Branded WordPress login screen.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Access;

use Burbank_LMS\Branding\Site;

/**
 * Skins `wp-login.php` to match the portal and points Register at `/register/`.
 */
class Login {
	/** Webpack CSS/JS entry / asset handle. */
	public const HANDLE = 'burbank-lms-login';

	/**
	 * Enqueue the login stylesheet, script, and logo inline CSS.
	 */
	public function enqueue_assets(): void {
		$css_file = BURBANK_LMS_PATH . 'build/' . self::HANDLE . '.css';
		$js_file  = BURBANK_LMS_PATH . 'build/' . self::HANDLE . '.js';
		$version  = file_exists( $css_file ) ? (string) filemtime( $css_file ) : null;

		if ( file_exists( $css_file ) ) {
			wp_enqueue_style(
				self::HANDLE,
				BURBANK_LMS_URL . 'build/' . self::HANDLE . '.css',
				array(),
				$version
			);

			$logo = ( new Site() )->logo_url();
			if ( '' !== $logo ) {
				$safe = esc_url_raw( $logo );
				if ( '' !== $safe ) {
					wp_add_inline_style(
						self::HANDLE,
						sprintf(
							'body.login.bfc-login #login h1 a{background-image:url(%s);}',
							esc_url( $safe )
						)
					);
				}
			}
		}

		if ( file_exists( $js_file ) ) {
			wp_enqueue_script(
				self::HANDLE,
				BURBANK_LMS_URL . 'build/' . self::HANDLE . '.js',
				array(),
				file_exists( $css_file ) ? $version : (string) filemtime( $js_file ),
				true
			);
		}
	}

	/**
	 * Set appearance before paint so light/dark matches the CERT theme preference.
	 */
	public function print_theme_boot(): void {
		echo "<script>(function(){try{var s=localStorage.getItem('burbankcert-appearance');var m=(s==='light'||s==='dark')?s:(window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');document.documentElement.setAttribute('data-bc-theme',m);}catch(e){document.documentElement.setAttribute('data-bc-theme','light');}})();</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed boot script; no user input.
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
	 * Print theme toggle and a short lede above the login form.
	 */
	public function render_header(): void {
		printf(
			'<button type="button" class="bfc-login__theme-toggle" aria-pressed="false" aria-label="%1$s"><span class="bfc-login__theme-icon" aria-hidden="true">☾</span></button><p class="bfc-login__lede">%2$s</p>',
			esc_attr__( 'Switch to dark mode', 'burbank-lms' ),
			esc_html__( 'Sign in to continue.', 'burbank-lms' )
		);
	}

	/**
	 * Print a register CTA beneath the login form.
	 */
	public function render_footer(): void {
		printf(
			'<p class="bfc-login__register"><a href="%1$s">%2$s</a></p>',
			esc_url( home_url( '/register/' ) ),
			esc_html__( 'Create an account', 'burbank-lms' )
		);
	}
}
