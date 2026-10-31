<?php
/**
 * PressPrimer quiz course chrome and title demotion.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Invites;
use Burbank_LMS\Access\Registration;
use Burbank_LMS\Access\Tables as AccessTables;
use Burbank_LMS\Courses\Capabilities;
use Burbank_LMS\Enrollment\Service as EnrollmentService;
use Burbank_LMS\Enrollment\Tables as EnrollmentTables;
use Burbank_LMS\Integrations\PressPrimer_Quiz;

/** Course chrome on mapped quizzes; H1→H2 for .ppq-quiz-title. */
class PressPrimerChromeTest extends WP_UnitTestCase {
	/** Install tables and authenticate an admin for fixtures. */
	public function set_up(): void {
		parent::set_up();
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
		global $wpdb;
		bfc_test_purge_courses();
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_invite_codes" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_enrollments" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB -- Transaction integration fixture.
		parent::tear_down();
	}

	/** Mapped quiz prints course chrome with back link. */
	public function test_mapped_quiz_echoes_chrome(): void {
		$fixture = $this->mapped_course_and_learner( true );
		wp_set_current_user( $fixture['user_id'] );

		ob_start();
		( new PressPrimer_Quiz() )->before_quiz_meta( $fixture['quiz'] );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'bfc-quiz-chrome', $html );
		$this->assertStringContainsString( 'bfc-quiz-chrome__back', $html );
		$this->assertStringContainsString( 'Chrome Course', $html );
	}

	/** Unmapped quiz prints no chrome. */
	public function test_unmapped_quiz_echoes_nothing(): void {
		PressPrimer_Quiz_Activator::activate();
		$quiz_id = PressPrimer_Quiz_Quiz::create(
			array(
				'title'  => 'Standalone Chrome',
				'status' => 'published',
			)
		);
		$quiz    = PressPrimer_Quiz_Quiz::get( $quiz_id );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		ob_start();
		( new PressPrimer_Quiz() )->before_quiz_meta( $quiz );
		$html = ob_get_clean();

		$this->assertSame( '', $html );
	}

	/** Title demotion turns h1.ppq-quiz-title into h2. */
	public function test_demote_quiz_title_h1_to_h2(): void {
		$input  = '<h1 class="ppq-quiz-title">Safety Quiz</h1>';
		$output = ( new PressPrimer_Quiz() )->demote_quiz_title( $input );

		$this->assertSame( '<h2 class="ppq-quiz-title">Safety Quiz</h2>', $output );
	}

	/** Non-title H1s are left alone. */
	public function test_demote_quiz_title_leaves_other_h1(): void {
		$input  = '<h1 class="other-title">Keep</h1>';
		$output = ( new PressPrimer_Quiz() )->demote_quiz_title( $input );

		$this->assertSame( $input, $output );
	}

	/**
	 * Build a published mapped course and optional active enrollment.
	 *
	 * @param bool $enroll Whether to enroll the verified learner.
	 * @return array{user_id:int,course_id:int,quiz:object,quiz_id:int}
	 */
	private function mapped_course_and_learner( bool $enroll ): array {
		$mapping   = bfc_test_mapping();
		$course_id = (int) self::factory()->post->create(
			array(
				'post_type'   => 'bfc_course',
				'post_status' => 'draft',
				'post_title'  => 'Chrome Course',
				'meta_input'  => array(
					'course_revision' => '1.0',
					'catalog_state'   => 'published',
				),
			)
		);
		global $wpdb;
		foreach ( array(
			'bfc_code'                => 'BFC-701',
			'subject_category'        => 'safety_field_awareness',
			'course_outcome'          => 'independent_study',
			'matrix_requirement_slug' => 'req-scene-safety',
			'pressprimer_quiz_id'     => (string) $mapping['pressprimer_quiz_id'],
			'certificate_template_id' => (string) $mapping['certificate_template_id'],
		) as $key => $value ) {
			$wpdb->replace( // phpcs:ignore WordPress.DB,WordPress.DB.SlowDBQuery -- Bypass identity/mapping guards for chrome fixtures.
				$wpdb->postmeta,
				array(
					'post_id'    => $course_id,
					'meta_key'   => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Fixture write.
					'meta_value' => $value, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Fixture write.
				),
				array( '%d', '%s', '%s' )
			);
		}
		$wpdb->update( // phpcs:ignore WordPress.DB -- Bypass publication guards for chrome fixtures.
			$wpdb->posts,
			array( 'post_status' => 'publish' ),
			array( 'ID' => $course_id ),
			array( '%s' ),
			array( '%d' )
		);
		clean_post_cache( $course_id );

		$user_id = $this->verified_learner();
		if ( $enroll ) {
			$this->assertTrue( ( new EnrollmentService() )->self_enroll( $user_id, $course_id ) );
		}

		$quiz = PressPrimer_Quiz_Quiz::get( $mapping['pressprimer_quiz_id'] );
		$this->assertIsObject( $quiz );

		return array(
			'user_id'   => $user_id,
			'course_id' => $course_id,
			'quiz'      => $quiz,
			'quiz_id'   => (int) $mapping['pressprimer_quiz_id'],
		);
	}

	/**
	 * Register a learner with training access and a legal name.
	 *
	 * @param string $email Learner email.
	 */
	private function verified_learner( string $email = '' ): int {
		if ( '' === $email ) {
			$email = 'chrome-learner-' . wp_generate_password( 8, false ) . '@example.com';
		}
		$created = ( new Invites() )->create(
			'site',
			array(
				'label' => 'Chrome',
				'kind'  => 'multi',
			)
		);
		$this->assertIsArray( $created );
		$user_id = ( new Registration() )->register(
			array(
				'invite'       => $created['plaintext'],
				'email'        => $email,
				'password'     => 'CorrectHorseBatteryStaple1!',
				'legal_name'   => 'Chrome Learner',
				'display_name' => 'Chrome',
			)
		);
		$this->assertIsInt( $user_id );
		return $user_id;
	}
}
