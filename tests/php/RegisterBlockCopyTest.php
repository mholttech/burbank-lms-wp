<?php
/**
 * Register block front-end copy guards.
 *
 * @package Burbank_LMS
 */

/** Asserts registration success messaging matches product policy. */
class RegisterBlockCopyTest extends WP_UnitTestCase {

	/** Success copy must not reference deprecated email verification. */
	public function test_register_success_copy_omits_email_verification(): void {
		$js = file_get_contents( dirname( __DIR__, 2 ) . '/src/Blocks/register/view.js' );
		$this->assertIsString( $js );
		$this->assertStringNotContainsString( 'verify before enrolling', $js );
		$this->assertStringContainsString( 'Account created', $js );
	}
}
