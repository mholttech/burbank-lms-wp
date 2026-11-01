<?php
/**
 * Branded wp-login.php coverage.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Login;
use Burbank_LMS\Branding\Site;

/** Ensures login assets, body class, and register URL match the portal brand. */
class LoginBrandingTest extends WP_UnitTestCase {
	/** Clear the login stylesheet between cases. */
	public function set_up(): void {
		parent::set_up();
		wp_dequeue_style( Login::HANDLE );
		wp_deregister_style( Login::HANDLE );
	}

	/** Login stylesheet enqueues when the built CSS exists. */
	public function test_enqueues_login_stylesheet(): void {
		$css = BURBANK_LMS_PATH . 'build/' . Login::HANDLE . '.css';
		if ( ! file_exists( $css ) ) {
			$this->markTestSkipped( 'build/burbank-lms-login.css missing; run npm run build.' );
		}

		( new Login() )->enqueue_assets();
		$this->assertTrue( wp_style_is( Login::HANDLE, 'enqueued' ) );
	}

	/** Login body receives the bfc-login class. */
	public function test_body_class_includes_bfc_login(): void {
		$classes = ( new Login() )->body_class( array( 'login-action-login' ) );
		$this->assertContains( 'bfc-login', $classes );
		$this->assertContains( 'login-action-login', $classes );
	}

	/** Register URL points at the Burbank register page. */
	public function test_register_url_uses_register_page(): void {
		$url = ( new Login() )->register_url( 'https://example.com/wp-login.php?action=register' );
		$this->assertSame( home_url( '/register/' ), $url );
	}

	/** Header helpers expose product name and home URL. */
	public function test_header_url_and_text(): void {
		$login = new Login();
		$this->assertSame( home_url( '/' ), $login->header_url() );
		$this->assertSame( ( new Site() )->product_name(), $login->header_text() );
	}

	/** Header and footer print Training caption and register CTA. */
	public function test_header_and_footer_markup(): void {
		$login = new Login();
		ob_start();
		$login->render_header();
		$header = ob_get_clean();
		$this->assertStringContainsString( 'bfc-login__caption', $header );
		$this->assertStringContainsString( 'Training', $header );
		$this->assertStringContainsString( 'bfc-login__lede', $header );

		ob_start();
		$login->render_footer();
		$footer = ob_get_clean();
		$this->assertStringContainsString( 'bfc-login__register', $footer );
		$this->assertStringContainsString( home_url( '/register/' ), $footer );
		$this->assertStringContainsString( 'Create a training account', $footer );
	}

	/** Site branding exposes a product name and optional logo filter. */
	public function test_site_branding_filters(): void {
		$site = new Site();
		$this->assertSame( 'Training', $site->kicker() );
		$this->assertNotSame( '', $site->product_name() );

		add_filter(
			'burbank_lms_logo_url',
			static fn(): string => 'https://example.com/logo.png'
		);
		$this->assertSame( 'https://example.com/logo.png', $site->logo_url() );
	}
}
