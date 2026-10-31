<?php
/**
 * Plugin FSE template registration coverage.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Learner\Templates;

/** Ensures archive/single bfc_course templates register when the API exists. */
class LearnerTemplatesTest extends WP_UnitTestCase {
	/** Register course archive and single templates via register_block_template. */
	public function test_registers_course_templates_when_supported(): void {
		if ( ! function_exists( 'register_block_template' ) || ! class_exists( 'WP_Block_Templates_Registry' ) ) {
			$this->markTestSkipped( 'register_block_template requires WordPress 6.7+.' );
		}

		$registry = WP_Block_Templates_Registry::get_instance();
		if ( ! $registry->is_registered( 'burbank-lms//archive-bfc_course' ) ) {
			( new Templates() )->register();
		}

		$this->assertTrue( $registry->is_registered( 'burbank-lms//archive-bfc_course' ) );
		$this->assertTrue( $registry->is_registered( 'burbank-lms//single-bfc_course' ) );
	}

	/** Single template markup includes title, featured image, post content, and course block. */
	public function test_single_template_includes_post_content(): void {
		if ( ! function_exists( 'register_block_template' ) ) {
			$this->markTestSkipped( 'register_block_template requires WordPress 6.7+.' );
		}
		$templates = new \Burbank_LMS\Learner\Templates();
		$ref       = new ReflectionClass( $templates );
		$method    = $ref->getMethod( 'single_content' );
		$method->setAccessible( true );
		$content = (string) $method->invoke( $templates );
		$this->assertStringContainsString( 'wp:post-content', $content );
		$this->assertStringContainsString( 'wp:post-featured-image', $content );
		$this->assertStringContainsString( 'wp:burbank-lms/course', $content );
		$this->assertStringContainsString( 'wp:post-title', $content );
	}
}
