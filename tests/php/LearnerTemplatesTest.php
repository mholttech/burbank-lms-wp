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

	/** Archive template wraps catalog in the optional shell block. */
	public function test_archive_template_includes_shell_and_catalog(): void {
		if ( ! function_exists( 'register_block_template' ) ) {
			$this->markTestSkipped( 'register_block_template requires WordPress 6.7+.' );
		}
		$templates = new Templates();
		$ref       = new ReflectionClass( $templates );
		$method    = $ref->getMethod( 'archive_content' );
		$method->setAccessible( true );
		$content = (string) $method->invoke( $templates );
		$this->assertStringContainsString( 'wp:burbank-lms/shell', $content );
		$this->assertStringContainsString( 'wp:burbank-lms/catalog', $content );
		$this->assertStringNotContainsString( 'wp:heading', $content );
		$this->assertStringNotContainsString( 'Course catalog', $content );
	}

	/** Single template markup includes shell, title, featured image, post content, and course block. */
	public function test_single_template_includes_post_content(): void {
		if ( ! function_exists( 'register_block_template' ) ) {
			$this->markTestSkipped( 'register_block_template requires WordPress 6.7+.' );
		}
		$templates = new Templates();
		$ref       = new ReflectionClass( $templates );
		$method    = $ref->getMethod( 'single_content' );
		$method->setAccessible( true );
		$content = (string) $method->invoke( $templates );
		$this->assertStringContainsString( 'wp:burbank-lms/shell', $content );
		$this->assertStringContainsString( 'wp:post-content', $content );
		$this->assertStringContainsString( 'wp:post-featured-image', $content );
		$this->assertStringContainsString( 'wp:burbank-lms/course', $content );
		$this->assertStringContainsString( 'wp:post-title', $content );
	}

	/** Full single templates never disclose materials to blocked audiences. */
	public function test_full_single_template_protects_materials(): void {
		$course = self::factory()->post->create(
			array(
				'post_type'    => 'bfc_course',
				'post_content' => '<!-- wp:paragraph --><p>PRIVATE-MATERIAL-MARKER</p><!-- /wp:paragraph -->',
			)
		);
		global $post;
		$post = get_post( $course );
		setup_postdata( $post );
		$method = new ReflectionMethod( Templates::class, 'single_content' );
		$method->setAccessible( true );
		$template = $method->invoke( new Templates() );
		$id       = self::factory()->user->create();
		foreach ( array( 0, $id ) as $blocked ) {
			wp_set_current_user( $blocked );
			$this->assertStringNotContainsString( 'PRIVATE-MATERIAL-MARKER', do_blocks( $template ) );
		}
		$learner = new \Burbank_LMS\Access\Learner();
		$learner->grant_access( $id );
		update_user_meta( $id, $learner::META_EMAIL_VERIFIED, '0' );
		$this->assertStringNotContainsString( 'PRIVATE-MATERIAL-MARKER', do_blocks( $template ) );
		$learner->mark_email_verified( $id );
		$this->assertStringContainsString( 'PRIVATE-MATERIAL-MARKER', do_blocks( $template ) );
		wp_reset_postdata();
	}

	/** Unrelated page content and editor rendering stay unchanged. */
	public function test_content_guard_preserves_editor_and_unrelated_content(): void {
		$guard = new \Burbank_LMS\Learner\Course_Content();
		$this->assertSame( 'keep', $guard->guard( 'keep', array( 'blockName' => 'core/paragraph' ) ) );
		set_current_screen( 'post' );
		$this->assertSame( 'keep', $guard->guard( 'keep', array( 'blockName' => 'core/post-content' ) ) );
		set_current_screen( 'front' );
	}
}
