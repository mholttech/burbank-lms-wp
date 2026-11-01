<?php
/**
 * Catalog block search/filter and status chip render coverage.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Learner;
use Burbank_LMS\Courses\Capabilities;
use Burbank_LMS\Enrollment\Service as Enrollment_Service;
use Burbank_LMS\Enrollment\Tables as EnrollmentTables;

/** Asserts catalog filter controls and per-item data attributes. */
class CatalogBlockTest extends WP_UnitTestCase {
	/** Install enrollment tables and course caps for fixtures. */
	public function set_up(): void {
		parent::set_up();
		global $wpdb;
		$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB -- Enrollment writes may commit outside PHPUnit's wrapper.
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}bfc_enrollments" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		delete_option( EnrollmentTables::VERSION_OPTION );
		$this->assertTrue( ( new EnrollmentTables() )->install() );
		( new Capabilities() )->install();
	}

	/** Restore PHPUnit transaction wrapping after real commits. */
	public function tear_down(): void {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_enrollments" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB -- Transaction integration fixture.
		parent::tear_down();
	}

	/**
	 * Create a published course with catalog meta (SQL bypasses publication guards).
	 *
	 * @param string $title   Course title.
	 * @param string $code    BFC code.
	 * @param string $subject Subject category slug.
	 * @return int Course post ID.
	 */
	private function published_course( string $title, string $code, string $subject ): int {
		$course_id = self::factory()->post->create(
			array(
				'post_type'   => 'bfc_course',
				'post_status' => 'draft',
				'post_title'  => $title,
				'meta_input'  => array(
					'course_revision' => '1.0',
					'catalog_state'   => 'published',
				),
			)
		);
		$this->assertIsInt( $course_id );
		global $wpdb;
		foreach ( array(
			'bfc_code'         => $code,
			'subject_category' => $subject,
			'course_outcome'   => 'independent_study',
		) as $key => $value ) {
			$wpdb->replace( // phpcs:ignore WordPress.DB,WordPress.DB.SlowDBQuery -- Bypass identity/mapping guards for block render fixtures.
				$wpdb->postmeta,
				array(
					'post_id'    => $course_id,
					'meta_key'   => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Fixture write.
					'meta_value' => $value, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Fixture write.
				),
				array( '%d', '%s', '%s' )
			);
		}
		$wpdb->update( // phpcs:ignore WordPress.DB -- Bypass publication guards for block render fixtures.
			$wpdb->posts,
			array( 'post_status' => 'publish' ),
			array( 'ID' => $course_id ),
			array( '%s' ),
			array( '%d' )
		);
		clean_post_cache( $course_id );
		return $course_id;
	}

	/** Rendered catalog includes search, subject filter, and item data attributes. */
	public function test_render_includes_search_subject_filter_and_item_data(): void {
		$alpha_id = $this->published_course( 'Alpha Communications', 'BFC-101', 'communications' );
		$this->published_course( 'Beta Safety', 'BFC-202', 'safety' );

		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		( new Learner() )->grant_access( $user_id );
		$this->assertTrue( ( new Enrollment_Service() )->self_enroll( $user_id, $alpha_id ) );
		wp_set_current_user( $user_id );

		$html = do_blocks( '<!-- wp:burbank-lms/catalog /-->' );

		$this->assertStringContainsString( 'data-bfc-catalog-search', $html );
		$this->assertStringContainsString( 'data-bfc-catalog-subject', $html );
		$this->assertStringContainsString( 'data-bfc-code="BFC-101"', $html );
		$this->assertStringContainsString( 'data-bfc-code="BFC-202"', $html );
		$this->assertStringContainsString( 'data-bfc-subject="communications"', $html );
		$this->assertStringContainsString( 'data-bfc-subject="safety"', $html );
		$this->assertStringContainsString( 'data-bfc-catalog-empty-filter', $html );
		$this->assertStringContainsString( 'No courses match your filters.', $html );
		$this->assertStringContainsString( 'Course catalog', $html );
		$this->assertStringContainsString( 'bfc-catalog__item', $html );
		$this->assertStringContainsString( 'Continue course', $html );
		$this->assertMatchesRegularExpression( '/\d+ courses? available/', $html );
	}

	/** Course details use the shared tile stack without percentage progress. */
	public function test_course_stack_has_badge_title_and_action_footer(): void {
		$course_id = $this->published_course( 'Stack course', 'BFC-303', 'safety' );
		$user_id   = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		( new Learner() )->grant_access( $user_id );
		wp_set_current_user( $user_id );
		$html = do_blocks( '<!-- wp:burbank-lms/course {"courseId":' . $course_id . '} /-->' );
		$this->assertStringContainsString( 'bfc-course__badge', $html );
		$this->assertStringContainsString( 'bfc-course__title', $html );
		$this->assertStringContainsString( 'bfc-course__foot', $html );
		$this->assertStringContainsString( 'Open enrollment', $html );
		$this->assertStringNotContainsString( '%', $html );
	}

	/** Enrolled mini-cards offer a direct return to the course. */
	public function test_enroll_card_offers_continue_for_active_enrollment(): void {
		$course_id = $this->published_course( 'Continue course', 'BFC-304', 'safety' );
		$user_id   = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		( new Learner() )->grant_access( $user_id );
		$this->assertTrue( ( new Enrollment_Service() )->self_enroll( $user_id, $course_id ) );
		wp_set_current_user( $user_id );
		$html = do_blocks( '<!-- wp:burbank-lms/enroll {"courseId":' . $course_id . '} /-->' );
		$this->assertStringContainsString( 'bfc-enroll__foot', $html );
		$this->assertStringContainsString( 'Continue course', $html );
		$course_html = do_blocks( '<!-- wp:burbank-lms/course {"courseId":' . $course_id . '} /-->' );
		$this->assertStringContainsString( 'data-bfc-withdraw', $course_html );
	}

	/** Invite-only mini-cards link to the course redemption form. */
	public function test_invite_mini_card_links_to_course(): void {
		$course_id = $this->published_course( 'Invite course', 'BFC-305', 'safety' );
		( new \Burbank_LMS\Access\Tables() )->install();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$invite = ( new \Burbank_LMS\Access\Invites() )->create(
			'course',
			array(
				'course_id' => $course_id,
				'label'     => 'Card',
				'kind'      => 'multi',
			)
		);
		$this->assertIsArray( $invite );
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		( new Learner() )->grant_access( $user_id );
		wp_set_current_user( $user_id );
		$html = do_blocks( '<!-- wp:burbank-lms/enroll {"courseId":' . $course_id . '} /-->' );
		$this->assertStringContainsString( 'Invite required', $html );
		$this->assertStringContainsString( 'View course', $html );
		$this->assertStringNotContainsString( 'type="submit"', $html );
	}

	/** Completed enrollment cards preserve credentials and never offer enrollment. */
	public function test_completed_blocks_preserve_credential_and_hide_enrollment(): void {
		$course_id = $this->published_course( 'Completed course', 'BFC-306', 'safety' );
		$user_id   = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		( new Learner() )->grant_access( $user_id );
		$service = new Enrollment_Service();
		$this->assertTrue( $service->self_enroll( $user_id, $course_id ) );
		$row = $service->get( $user_id, $course_id );
		$this->assertTrue( ( new \Burbank_LMS\Enrollment\Repository() )->complete( (int) $row->id, 731, '2026-10-31 12:00:00' ) );
		wp_set_current_user( $user_id );
		foreach ( array( 'course', 'enroll' ) as $block_name ) {
			$html = do_blocks( '<!-- wp:burbank-lms/' . $block_name . ' {"courseId":' . $course_id . '} /-->' );
			$this->assertStringNotContainsString( 'data-bfc-enroll=', $html );
			$this->assertStringNotContainsString( 'type="submit"', $html );
			$this->assertStringNotContainsString( 'data-bfc-withdraw', $html );
			$this->assertStringContainsString( 'View ', $html );
		}
		$after = $service->get( $user_id, $course_id );
		$this->assertSame( 'completed', $after->status );
		$this->assertSame( 731, (int) $after->credential_id );
	}

	/** Guests see soft-gate card instead of catalog course list. */
	public function test_catalog_render_soft_gates_guest(): void {
		$this->published_course( 'Gated Course', 'BFC-901', 'communications' );
		wp_set_current_user( 0 );
		$block = do_blocks( '<!-- wp:burbank-lms/catalog /-->' );
		$this->assertStringContainsString( 'bfc-soft-gate', $block );
		$this->assertStringNotContainsString( 'bfc-catalog__list', $block );
		$this->assertStringNotContainsString( 'data-bfc-catalog-search', $block );
		$this->assertStringNotContainsString( 'data-bfc-catalog-subject', $block );
		$this->assertStringNotContainsString( 'bfc-catalog__enroll', $block );
		$this->assertStringNotContainsString( 'BFC-901', $block );
	}

	/** Subscribers without training access see soft-gate, not catalog list. */
	public function test_catalog_render_soft_gates_no_access(): void {
		$this->published_course( 'Gated Course', 'BFC-902', 'safety' );
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );
		$block = do_blocks( '<!-- wp:burbank-lms/catalog /-->' );
		$this->assertStringContainsString( 'bfc-soft-gate', $block );
		$this->assertStringNotContainsString( 'bfc-catalog__list', $block );
		$this->assertStringNotContainsString( 'data-bfc-catalog-search', $block );
		$this->assertStringNotContainsString( 'data-bfc-catalog-subject', $block );
		$this->assertStringNotContainsString( 'bfc-catalog__enroll', $block );
		$this->assertStringNotContainsString( 'BFC-902', $block );
	}
}
