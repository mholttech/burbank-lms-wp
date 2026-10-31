<?php
/**
 * PressPrimer front-end skin enqueue coverage.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Courses\Capabilities;
use Burbank_LMS\Integrations\PressPrimer_Assets;

/** Conditional enqueue of migrated PressPrimer CSS. */
class PressPrimerAssetsTest extends WP_UnitTestCase {
	/** Clear queue state between cases. */
	public function set_up(): void {
		parent::set_up();
		wp_dequeue_style( PressPrimer_Assets::HANDLE );
		wp_deregister_style( PressPrimer_Assets::HANDLE );
	}

	/** Singular course enqueues the PressPrimer skin style. */
	public function test_enqueues_style_on_singular_course(): void {
		( new Capabilities() )->install();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$course_id = self::factory()->post->create(
			array(
				'post_type'   => 'bfc_course',
				'post_status' => 'publish',
			)
		);
		$this->go_to( get_permalink( $course_id ) );
		$this->assertTrue( is_singular( 'bfc_course' ) );

		( new PressPrimer_Assets() )->enqueue();

		$this->assertTrue( wp_style_is( 'burbank-lms-pressprimer', 'enqueued' ) );
	}

	/** Non-course, non-singular requests do not enqueue the PressPrimer skin. */
	public function test_skips_enqueue_off_course_context(): void {
		// Search is never singular; avoids static-front-page + installed PP plugins
		// matching the practical v1 “any singular while PP present” branch.
		$this->go_to( home_url( '/?s=pressprimer-assets-skip' ) );
		$this->assertFalse( is_singular() );
		$this->assertFalse( is_singular( 'bfc_course' ) );
		$this->assertFalse( is_post_type_archive( 'bfc_course' ) );

		( new PressPrimer_Assets() )->enqueue();

		$this->assertFalse( wp_style_is( 'burbank-lms-pressprimer', 'enqueued' ) );
	}
}
