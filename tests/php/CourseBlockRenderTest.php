<?php
/**
 * Course block learner chrome render coverage.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Learner;
use Burbank_LMS\Courses\Capabilities;
use Burbank_LMS\Enrollment\Service as Enrollment_Service;
use Burbank_LMS\Enrollment\Tables as EnrollmentTables;

/** Asserts course block chrome: meta, status, quiz CTA and embed. */
class CourseBlockRenderTest extends WP_UnitTestCase {
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
	 * @return int Course post ID.
	 */
	private function published_course(): int {
		$course_id = self::factory()->post->create(
			array(
				'post_type'   => 'bfc_course',
				'post_status' => 'draft',
				'post_title'  => 'Course Block Chrome',
				'meta_input'  => array(
					'course_revision' => '1.0',
					'catalog_state'   => 'published',
				),
			)
		);
		$this->assertIsInt( $course_id );
		global $wpdb;
		foreach ( array(
			'bfc_code'            => 'BFC-998',
			'subject_category'    => 'communications',
			'course_outcome'      => 'independent_study',
			'pressprimer_quiz_id' => '1',
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

	/** Rendered course chrome shows meta, enrolled status, quiz CTA and embed. */
	public function test_render_includes_meta_status_and_quiz_chrome(): void {
		$course_id = $this->published_course();
		$user_id   = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		( new Learner() )->grant_access( $user_id );
		$this->assertTrue( ( new Enrollment_Service() )->self_enroll( $user_id, $course_id ) );
		wp_set_current_user( $user_id );

		$html = do_blocks( '<!-- wp:burbank-lms/course {"courseId":' . $course_id . '} /-->' );

		$this->assertStringContainsString( 'BFC-998', $html );
		$this->assertStringContainsString( 'Independent study', $html );
		$this->assertStringContainsString( 'Communications', $html );
		$this->assertStringContainsString( '1.0', $html );
		$this->assertStringContainsString( 'You are enrolled in this course.', $html );
		$this->assertStringContainsString( 'bfc-course__quiz-cta', $html );
		$this->assertStringContainsString( 'href="#bfc-course-quiz"', $html );
		$this->assertStringContainsString( 'bfc-course__quiz-embed', $html );
		$this->assertStringContainsString( 'id="bfc-course-quiz"', $html );
		$this->assertDoesNotMatchRegularExpression(
			'/<a\b(?![^>]*\bbfc-course__quiz-cta\b)[^>]*>\s*Take quiz\s*<\/a>/i',
			$html
		);
	}
}
