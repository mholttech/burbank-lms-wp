<?php
/**
 * Learner block title heading level (single page H1).
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Learner;
use Burbank_LMS\Learner\Block_Title;

/** Asserts profile/my-courses/register blocks never duplicate a page H1. */
class LearnerBlockTitleTest extends WP_UnitTestCase {

	/** Block title helper skips titles on slug pages that already have a page H1. */
	public function test_block_title_skips_on_learner_pages(): void {
		$title = new Block_Title();
		$this->assertFalse( $title->should_render( 'profile' ) );
		$this->assertFalse( $title->should_render( 'my-courses' ) );
		$this->assertFalse( $title->should_render( 'register' ) );
		$this->assertTrue( $title->should_render( '' ) );
	}

	/** Wrapped profile block keeps a single H1 from the page heading. */
	public function test_profile_render_with_page_h1_has_single_h1(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		( new Learner() )->grant_access( $user_id );
		wp_set_current_user( $user_id );

		$html = do_blocks(
			'<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Profile</h1>
<!-- /wp:heading -->
<!-- wp:burbank-lms/profile /-->'
		);

		$this->assertSame( 1, substr_count( strtolower( $html ), '<h1' ) );
	}

	/** Wrapped my-courses block keeps a single H1 from the page heading. */
	public function test_my_courses_render_with_page_h1_has_single_h1(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		( new Learner() )->grant_access( $user_id );
		wp_set_current_user( $user_id );

		$html = do_blocks(
			'<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">My courses</h1>
<!-- /wp:heading -->
<!-- wp:burbank-lms/my-courses /-->'
		);

		$this->assertSame( 1, substr_count( strtolower( $html ), '<h1' ) );
	}

	/** Standalone my-courses render exposes an H2 block title, not H1. */
	public function test_my_courses_standalone_uses_h2_title(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		( new Learner() )->grant_access( $user_id );
		wp_set_current_user( $user_id );

		$html = do_blocks( '<!-- wp:burbank-lms/my-courses /-->' );

		$this->assertStringContainsString( 'bfc-my-courses__heading', $html );
		$this->assertStringContainsString( '<h2', strtolower( $html ) );
		$this->assertStringNotContainsString( '<h1', strtolower( $html ) );
	}

	/** Standalone profile render exposes an H2 block title, not H1. */
	public function test_profile_standalone_uses_h2_title(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		( new Learner() )->grant_access( $user_id );
		wp_set_current_user( $user_id );

		$html = do_blocks( '<!-- wp:burbank-lms/profile /-->' );

		$this->assertStringContainsString( 'bfc-profile__title', $html );
		$this->assertStringContainsString( '<h2', strtolower( $html ) );
		$this->assertStringNotContainsString( '<h1', strtolower( $html ) );
	}

	/** Profile page context omits the block title when the page owns the H1. */
	public function test_profile_page_omits_block_title(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		( new Learner() )->grant_access( $user_id );
		wp_set_current_user( $user_id );

		$page_id = self::factory()->post->create(
			array(
				'post_type'  => 'page',
				'post_name'  => 'profile',
				'post_title' => 'Profile',
			)
		);
		$this->go_to( get_permalink( $page_id ) );

		$html = do_blocks( '<!-- wp:burbank-lms/profile /-->' );

		$this->assertStringNotContainsString( 'bfc-profile__title', $html );
	}

	/** Register block uses H2 when not on the register page. */
	public function test_register_standalone_uses_h2_title(): void {
		wp_set_current_user( 0 );

		$html = do_blocks( '<!-- wp:burbank-lms/register /-->' );

		$this->assertStringContainsString( 'bfc-register__title', $html );
		$this->assertStringContainsString( '<h2', strtolower( $html ) );
		$this->assertStringNotContainsString( '<h1', strtolower( $html ) );
	}

	/** Archive template without portal (guest) uses soft-gate H1 only. */
	public function test_archive_template_guest_soft_gate_owns_h1(): void {
		if ( ! function_exists( 'register_block_template' ) ) {
			$this->markTestSkipped( 'register_block_template requires WordPress 6.7+.' );
		}

		wp_set_current_user( 0 );
		\Burbank_LMS\Learner\Shell::reset_chrome_printed();

		$templates = new \Burbank_LMS\Learner\Templates();
		$ref       = new ReflectionClass( $templates );
		$method    = $ref->getMethod( 'archive_content' );
		$method->setAccessible( true );
		$content = (string) $method->invoke( $templates );

		$html = do_blocks( $content );

		$this->assertStringContainsString( 'bfc-soft-gate__title', $html );
		$this->assertSame( 1, substr_count( strtolower( $html ), '<h1' ) );
	}
}
