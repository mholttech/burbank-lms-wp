<?php
/**
 * Catalog block search/filter and status chip render coverage.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Courses\Capabilities;
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
		$this->published_course( 'Alpha Communications', 'BFC-101', 'communications' );
		$this->published_course( 'Beta Safety', 'BFC-202', 'safety' );

		$html = do_blocks( '<!-- wp:burbank-lms/catalog /-->' );

		$this->assertStringContainsString( 'data-bfc-catalog-search', $html );
		$this->assertStringContainsString( 'data-bfc-catalog-subject', $html );
		$this->assertStringContainsString( 'data-bfc-code="BFC-101"', $html );
		$this->assertStringContainsString( 'data-bfc-code="BFC-202"', $html );
		$this->assertStringContainsString( 'data-bfc-subject="communications"', $html );
		$this->assertStringContainsString( 'data-bfc-subject="safety"', $html );
		$this->assertStringContainsString( 'data-bfc-catalog-empty-filter', $html );
		$this->assertStringContainsString( 'No courses match your filters.', $html );
	}
}
