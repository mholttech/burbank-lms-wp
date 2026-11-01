<?php
/**
 * My Courses block catalog CTA and certificate embed coverage.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Learner;

/** Asserts logged-in my-courses render includes catalog CTA and optional cert shortcode. */
class MyCoursesBlockTest extends WP_UnitTestCase {

	/** Logged-in render includes sections, stats strip, and catalog link. */
	public function test_logged_in_render_includes_catalog_cta(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		( new Learner() )->grant_access( $user_id );
		wp_set_current_user( $user_id );

		$type = get_post_type_object( 'bfc_course' );
		$this->assertNotNull( $type );
		$this->assertNotFalse( $type->has_archive );

		$archive = get_post_type_archive_link( 'bfc_course' );
		$this->assertIsString( $archive );
		$this->assertNotSame( '', $archive );

		$html = do_blocks( '<!-- wp:burbank-lms/my-courses /-->' );

		$this->assertStringContainsString( 'bfc-my-courses__stats', $html );
		$this->assertStringContainsString( 'data-bfc-stat="enrolled"', $html );
		$this->assertStringContainsString( 'data-bfc-stat="in-progress"', $html );
		$this->assertStringContainsString( 'data-bfc-stat="completed"', $html );
		$this->assertStringContainsString( 'Enrolled courses', $html );
		$this->assertStringContainsString( 'Browse course catalog', $html );
		$this->assertStringNotContainsString( 'bfc-my-courses__catalog-cta', $html );
		$this->assertStringContainsString( esc_url( $archive ), $html );
	}

	/** When ppcert shortcode exists, render wraps it in certificates container. */
	public function test_render_embeds_certificate_shortcode_when_registered(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		( new Learner() )->grant_access( $user_id );
		wp_set_current_user( $user_id );

		add_shortcode(
			'ppcert_my_certificates',
			static function (): string {
				return '<div class="ppcert-stub"></div>';
			}
		);

		$html = do_blocks( '<!-- wp:burbank-lms/my-courses /-->' );

		$this->assertStringContainsString( 'bfc-my-courses__certificates', $html );
		$this->assertStringContainsString( 'Certificates', $html );
		$this->assertStringContainsString( 'data-bfc-cert-valid', $html );
		$this->assertStringContainsString( 'ppcert-stub', $html );

		remove_shortcode( 'ppcert_my_certificates' );
	}
}
