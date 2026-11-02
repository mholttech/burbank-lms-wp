<?php
/**
 * Register block front-end copy guards.
 *
 * @package Burbank_LMS
 */

/** Asserts registration success messaging and page structure. */
class RegisterBlockCopyTest extends WP_UnitTestCase {

	/** Success copy must not reference deprecated email verification. */
	public function test_register_success_copy_omits_email_verification(): void {
		$js = file_get_contents( dirname( __DIR__, 2 ) . '/src/Blocks/register/view.js' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local view.js, not a remote request.
		$this->assertIsString( $js );
		$this->assertStringNotContainsString( 'verify before enrolling', $js );
	}

	/** The form groups invite, certificate name, and sign-in, then offers a sign-in path. */
	public function test_register_form_groups_invite_name_and_sign_in(): void {
		wp_set_current_user( 0 );
		$html = do_blocks( '<!-- wp:burbank-lms/register /-->' );
		$this->assertStringContainsString( 'bfc-register__name', $html );
		$this->assertStringContainsString( 'data-bfc-cert-name', $html );
		$this->assertStringContainsString( 'Account created', $html );
		$this->assertStringContainsString( 'Sign in', $html );
		$this->assertStringContainsString( 'Already have an account?', $html );
		$this->assertStringContainsString( 'aria-describedby', $html );
		$this->assertStringContainsString( 'role="alert"', $html );
		$this->assertStringContainsString( 'data-bfc-meter', $html );
		$this->assertStringContainsString( 'minlength="8"', $html );
		$this->assertStringNotContainsString( 'id="bfc-register-invite"', $html );
	}
}
