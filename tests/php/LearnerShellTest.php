<?php
/**
 * Learner shell nav builder and once-per-request chrome.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Invites;
use Burbank_LMS\Access\Registration;
use Burbank_LMS\Access\Tables as AccessTables;
use Burbank_LMS\Courses\Capabilities;
use Burbank_LMS\Enrollment\Service as Enrollment_Service;
use Burbank_LMS\Enrollment\Tables as EnrollmentTables;
use Burbank_LMS\Learner\Shell;

/** Exercises Shell nav items, learner gate, chrome once-flag, and contextual actions. */
class LearnerShellTest extends WP_UnitTestCase {
	/** Install access + enrollment tables and authenticate an admin for fixtures. */
	public function set_up(): void {
		parent::set_up();
		Shell::reset_chrome_printed();
		global $wpdb;
		$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB -- Invite redeem commits outside PHPUnit's wrapper.
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}bfc_invite_codes" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}bfc_enrollments" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		delete_option( AccessTables::VERSION_OPTION );
		delete_option( EnrollmentTables::VERSION_OPTION );
		$this->assertTrue( ( new AccessTables() )->install() );
		$this->assertTrue( ( new EnrollmentTables() )->install() );
		( new Capabilities() )->install();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/** Restore PHPUnit transaction wrapping after real commits. */
	public function tear_down(): void {
		Shell::reset_chrome_printed();
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_invite_codes" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_enrollments" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB -- Transaction integration fixture.
		parent::tear_down();
	}

	/**
	 * Register a learner with training access; return user id.
	 *
	 * @param string $email Learner email address.
	 */
	private function verified_learner( string $email = 'shell-learner@example.com' ): int {
		$created = ( new Invites() )->create(
			'site',
			array(
				'label' => 'Batch',
				'kind'  => 'multi',
			)
		);
		$this->assertIsArray( $created );
		$user_id = ( new Registration() )->register(
			array(
				'invite'       => $created['plaintext'],
				'email'        => $email,
				'password'     => 'CorrectHorseBatteryStaple1!',
				'legal_name'   => 'Shell Learner',
				'display_name' => 'Shell',
			)
		);
		$this->assertIsInt( $user_id );
		return $user_id;
	}

	/**
	 * Create a published course with optional quiz meta.
	 *
	 * @param int $quiz_id PressPrimer quiz id meta (0 = none).
	 */
	private function published_course( int $quiz_id = 0 ): int {
		$meta = array(
			'course_revision' => '1.0',
			'catalog_state'   => 'published',
		);
		if ( $quiz_id > 0 ) {
			$meta['pressprimer_quiz_id'] = (string) $quiz_id;
		}
		$course_id = (int) self::factory()->post->create(
			array(
				'post_type'   => 'bfc_course',
				'post_status' => 'draft',
				'post_title'  => 'Shell Course',
				'meta_input'  => $meta,
			)
		);
		global $wpdb;
		$wpdb->update( // phpcs:ignore WordPress.DB -- Bypass publication guards for shell fixtures.
			$wpdb->posts,
			array( 'post_status' => 'publish' ),
			array( 'ID' => $course_id ),
			array( '%s' ),
			array( '%d' )
		);
		clean_post_cache( $course_id );
		$this->assertSame( 'publish', get_post_status( $course_id ) );
		return $course_id;
	}

	/** Nav builder returns catalog / my-courses / profile and marks catalog current. */
	public function test_nav_items_mark_current_catalog(): void {
		$shell = new Shell();
		$items = $shell->nav_items( 'catalog' );
		$ids   = wp_list_pluck( $items, 'id' );
		$this->assertSame( array( 'catalog', 'my-courses', 'profile' ), $ids );
		foreach ( $items as $item ) {
			if ( 'catalog' === $item['id'] ) {
				$this->assertTrue( $item['current'] );
			} else {
				$this->assertFalse( $item['current'] );
			}
		}
	}

	/** Course context also highlights the catalog nav item. */
	public function test_nav_items_mark_catalog_current_for_course(): void {
		$shell = new Shell();
		$items = $shell->nav_items( 'course' );
		foreach ( $items as $item ) {
			if ( 'catalog' === $item['id'] ) {
				$this->assertTrue( $item['current'] );
			} else {
				$this->assertFalse( $item['current'] );
			}
		}
	}

	/** Guests never see shell chrome; verified learners do. */
	public function test_should_show_only_for_learners(): void {
		$shell = new Shell();
		$this->assertFalse( $shell->should_show( 0 ) );
		$subscriber = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->assertFalse( $shell->should_show( $subscriber ) );
		$this->assertTrue( $shell->should_show( $this->verified_learner() ) );
	}

	/** Chrome prints once per request unless force is set. */
	public function test_render_chrome_once_per_request(): void {
		$user_id = $this->verified_learner( 'shell-chrome@example.com' );
		wp_set_current_user( $user_id );
		$shell = new Shell();

		$first = $shell->render_chrome( array( 'current' => 'catalog' ) );
		$this->assertNotSame( '', $first );
		$this->assertStringContainsString( 'bfc-shell', $first );
		$this->assertStringContainsString( 'Training', $first );

		$second = $shell->render_chrome( array( 'current' => 'catalog' ) );
		$this->assertSame( '', $second );

		$forced = $shell->render_chrome(
			array(
				'current' => 'catalog',
				'force'   => true,
			)
		);
		$this->assertNotSame( '', $forced );
		$this->assertStringContainsString( 'bfc-shell', $forced );
	}

	/** Active enrollment with a quiz yields continue; open course yields enroll. */
	public function test_contextual_actions_continue_and_enroll(): void {
		$user_id   = $this->verified_learner( 'shell-actions@example.com' );
		$course_id = $this->published_course( 42 );
		$shell     = new Shell();

		$enroll_kinds = wp_list_pluck( $shell->contextual_actions( $course_id, $user_id ), 'kind' );
		$this->assertContains( 'enroll', $enroll_kinds );
		$this->assertNotContains( 'continue', $enroll_kinds );

		$this->assertTrue( ( new Enrollment_Service() )->self_enroll( $user_id, $course_id ) );
		$continue_kinds = wp_list_pluck( $shell->contextual_actions( $course_id, $user_id ), 'kind' );
		$this->assertContains( 'continue', $continue_kinds );
		$this->assertNotContains( 'enroll', $continue_kinds );

		$continue = null;
		foreach ( $shell->contextual_actions( $course_id, $user_id ) as $action ) {
			if ( 'continue' === $action['kind'] ) {
				$continue = $action;
				break;
			}
		}
		$this->assertIsArray( $continue );
		$this->assertStringContainsString( '#bfc-course-quiz', $continue['url'] );
	}

	/** Catalog then course as learner yields exactly one shell when outputs are concatenated. */
	public function test_learner_blocks_print_chrome_once_when_concatenated(): void {
		$user_id   = $this->verified_learner( 'shell-blocks@example.com' );
		$course_id = $this->published_course();
		wp_set_current_user( $user_id );

		$catalog  = do_blocks( '<!-- wp:burbank-lms/catalog /-->' );
		$course   = do_blocks( '<!-- wp:burbank-lms/course {"courseId":' . $course_id . '} /-->' );
		$combined = $catalog . $course;

		$this->assertSame( 1, substr_count( $combined, 'class="bfc-shell"' ) );
		$this->assertStringContainsString( 'bfc-shell', $catalog );
		$this->assertStringNotContainsString( 'bfc-shell', $course );
	}

	/** Soft-gated guests and the register block never receive shell chrome. */
	public function test_soft_gate_and_register_omit_shell(): void {
		wp_set_current_user( 0 );
		$catalog = do_blocks( '<!-- wp:burbank-lms/catalog /-->' );
		$this->assertStringContainsString( 'bfc-soft-gate', $catalog );
		$this->assertStringNotContainsString( 'bfc-shell', $catalog );

		$register = do_blocks( '<!-- wp:burbank-lms/register /-->' );
		$this->assertStringNotContainsString( 'bfc-shell', $register );
	}
}
