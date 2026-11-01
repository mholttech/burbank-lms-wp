<?php
/**
 * Portal heading ownership coverage.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Learner;
use Burbank_LMS\Learner\Portal_Headings;
use Burbank_LMS\Learner\Shell;

/** Ensures profile/my-courses page H1s yield to portal / soft-gate titles. */
class LearnerPortalHeadingsTest extends WP_UnitTestCase {
	/** Reset shell once-flag between cases. */
	public function set_up(): void {
		parent::set_up();
		Shell::reset_chrome_printed();
	}

	/** Core heading level 1 is suppressed on the profile page. */
	public function test_suppresses_profile_page_h1(): void {
		$page_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_name'   => 'profile',
				'post_status' => 'publish',
				'post_title'  => 'Profile',
			)
		);
		$this->go_to( get_permalink( $page_id ) );

		$headings = new Portal_Headings();
		$block    = array(
			'blockName' => 'core/heading',
			'attrs'     => array( 'level' => 1 ),
		);
		$this->assertSame( '', $headings->suppress_page_h1( '<h1>Profile</h1>', $block ) );
		$this->assertSame(
			'<h2>Still here</h2>',
			$headings->suppress_page_h1(
				'<h2>Still here</h2>',
				array(
					'blockName' => 'core/heading',
					'attrs'     => array( 'level' => 2 ),
				)
			)
		);
	}

	/** Suppresses core/post-title output on my-courses so portal owns the H1. */
	public function test_suppresses_my_courses_post_title(): void {
		$page_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_name'   => 'my-courses',
				'post_status' => 'publish',
				'post_title'  => 'My Courses',
			)
		);
		$this->go_to( get_permalink( $page_id ) );

		$headings = new Portal_Headings();
		$block    = array(
			'blockName' => 'core/post-title',
			'attrs'     => array(),
		);
		$this->assertSame( '', $headings->suppress_page_h1( '<h1 class="wp-block-post-title">My Courses</h1>', $block ) );
	}

	/** Learner catalog chrome uses an H1 portal title. */
	public function test_catalog_portal_title_is_h1(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		( new Learner() )->grant_access( $user_id );
		wp_set_current_user( $user_id );

		$shell = new Shell();
		$html  = $shell->render_chrome( array( 'current' => 'catalog' ) );
		$this->assertStringContainsString( '<h1 class="bfc-portal__title">', $html );
		$this->assertStringContainsString( 'bfc-portal', $html );
	}

	/** Course context keeps portal title as a paragraph so the course owns H1. */
	public function test_course_portal_title_is_paragraph(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		( new Learner() )->grant_access( $user_id );
		wp_set_current_user( $user_id );

		$shell = new Shell();
		$html  = $shell->render_chrome( array( 'current' => 'course' ) );
		$this->assertStringContainsString( '<p class="bfc-portal__title">', $html );
		$this->assertStringNotContainsString( '<h1 class="bfc-portal__title">', $html );
	}
}
