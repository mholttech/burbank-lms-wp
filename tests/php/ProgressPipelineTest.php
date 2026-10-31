<?php
/**
 * Quiz pass → certificate → course completion pipeline.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Invites;
use Burbank_LMS\Access\Profile;
use Burbank_LMS\Access\Registration;
use Burbank_LMS\Access\Tables as AccessTables;
use Burbank_LMS\Courses\Capabilities;
use Burbank_LMS\Enrollment\Service as EnrollmentService;
use Burbank_LMS\Enrollment\Tables as EnrollmentTables;
use Burbank_LMS\Integrations\PressPrimer_Certificate;
use Burbank_LMS\Integrations\PressPrimer_Quiz;
use Burbank_LMS\Progress\Service as ProgressService;

/** Records quiz pass once and completes enrollment when a certificate issues. */
class ProgressPipelineTest extends WP_UnitTestCase {
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

	/** First pass stamps quiz_passed_at; a second pass leaves the stamp and completion alone. */
	public function test_pass_sets_quiz_passed_at_once(): void {
		$fixture  = $this->enrolled_mapped_course();
		$progress = new ProgressService();
		$quiz     = new PressPrimer_Quiz( $progress, new EnrollmentService() );

		$attempt = (object) array(
			'user_id' => $fixture['user_id'],
			'quiz_id' => $fixture['quiz_id'],
		);
		$quiz->quiz_passed( $attempt, $fixture['quiz'] );

		$row = ( new EnrollmentService() )->get_active( $fixture['user_id'], $fixture['course_id'] );
		$this->assertNotNull( $row );
		$this->assertNotEmpty( $row->quiz_passed_at );
		$first = $row->quiz_passed_at;

		$quiz->quiz_passed( $attempt, $fixture['quiz'] );
		$row = ( new EnrollmentService() )->get_active( $fixture['user_id'], $fixture['course_id'] );
		$this->assertNotNull( $row );
		$this->assertSame( $first, $row->quiz_passed_at );

		$progress->complete_with_credential( $fixture['user_id'], $fixture['course_id'], 99 );
		$quiz->quiz_passed( $attempt, $fixture['quiz'] );

		global $wpdb;
		$completed = $wpdb->get_row( // phpcs:ignore WordPress.DB -- Enrollment assertion after completion.
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}bfc_enrollments WHERE user_id = %d AND course_id = %d",
				$fixture['user_id'],
				$fixture['course_id']
			)
		);
		$this->assertSame( 'completed', $completed->status );
		$this->assertSame( $first, $completed->quiz_passed_at );
		$this->assertSame( 99, (int) $completed->credential_id );
	}

	/** Certificate issued completes enrollment and fires burbank_lms_course_completed. */
	public function test_certificate_issued_completes_and_fires_action(): void {
		$fixture  = $this->enrolled_mapped_course();
		$progress = new ProgressService();
		$progress->record_quiz_passed( $fixture['user_id'], $fixture['course_id'] );

		$seen = array();
		add_action(
			'burbank_lms_course_completed',
			static function ( int $user_id, int $course_id, object $enrollment ) use ( &$seen ): void {
				$seen[] = array( $user_id, $course_id, $enrollment );
			},
			10,
			3
		);

		$adapter = new PressPrimer_Certificate( $progress, new EnrollmentService(), new Profile() );
		$adapter->certificate_issued(
			42,
			array(
				'template_id'  => $fixture['template_id'],
				'recipient_id' => $fixture['user_id'],
				'trigger_type' => 'ppq_quiz',
				'source_type'  => 'ppq_quiz',
				'source_ref'   => (string) $fixture['quiz_id'],
			)
		);

		global $wpdb;
		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB -- Enrollment assertion after completion.
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}bfc_enrollments WHERE user_id = %d AND course_id = %d",
				$fixture['user_id'],
				$fixture['course_id']
			)
		);
		$this->assertSame( 'completed', $row->status );
		$this->assertSame( 42, (int) $row->credential_id );
		$this->assertNotEmpty( $row->completed_at );
		$this->assertCount( 1, $seen );
		$this->assertSame( $fixture['user_id'], $seen[0][0] );
		$this->assertSame( $fixture['course_id'], $seen[0][1] );
		$this->assertSame( 'completed', $seen[0][2]->status );
	}

	/**
	 * Create a published mapped course with an active enrollment.
	 *
	 * @return array{user_id:int,course_id:int,quiz:object,quiz_id:int,template_id:int}
	 */
	private function enrolled_mapped_course(): array {
		$mapping   = bfc_test_mapping();
		$course_id = (int) self::factory()->post->create(
			array(
				'post_type'   => 'bfc_course',
				'post_status' => 'draft',
				'post_title'  => 'Progress Course',
				'meta_input'  => array(
					'course_revision' => '1.0',
					'catalog_state'   => 'published',
				),
			)
		);
		global $wpdb;
		foreach ( array(
			'bfc_code'                => 'BFC-202',
			'subject_category'        => 'safety_field_awareness',
			'course_outcome'          => 'independent_study',
			'pressprimer_quiz_id'     => (string) $mapping['pressprimer_quiz_id'],
			'certificate_template_id' => (string) $mapping['certificate_template_id'],
		) as $key => $value ) {
			$wpdb->replace( // phpcs:ignore WordPress.DB,WordPress.DB.SlowDBQuery -- Bypass identity/mapping guards for progress fixtures.
				$wpdb->postmeta,
				array(
					'post_id'    => $course_id,
					'meta_key'   => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Fixture write.
					'meta_value' => $value, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Fixture write.
				),
				array( '%d', '%s', '%s' )
			);
		}
		$wpdb->update( // phpcs:ignore WordPress.DB -- Bypass publication guards for progress fixtures.
			$wpdb->posts,
			array( 'post_status' => 'publish' ),
			array( 'ID' => $course_id ),
			array( '%s' ),
			array( '%d' )
		);
		clean_post_cache( $course_id );

		$user_id = $this->verified_learner();
		$this->assertTrue( ( new EnrollmentService() )->self_enroll( $user_id, $course_id ) );

		$quiz = PressPrimer_Quiz_Quiz::get( $mapping['pressprimer_quiz_id'] );
		$this->assertIsObject( $quiz );

		return array(
			'user_id'     => $user_id,
			'course_id'   => $course_id,
			'quiz'        => $quiz,
			'quiz_id'     => (int) $mapping['pressprimer_quiz_id'],
			'template_id' => (int) $mapping['certificate_template_id'],
		);
	}

	/**
	 * Register a learner with training access.
	 *
	 * @param string $email Learner email.
	 */
	private function verified_learner( string $email = '' ): int {
		if ( '' === $email ) {
			$email = 'progress-learner-' . wp_generate_password( 8, false ) . '@example.com';
		}
		$created = ( new Invites() )->create(
			'site',
			array(
				'label' => 'Progress',
				'kind'  => 'multi',
			)
		);
		$this->assertIsArray( $created );
		$user_id = ( new Registration() )->register(
			array(
				'invite'       => $created['plaintext'],
				'email'        => $email,
				'password'     => 'CorrectHorseBatteryStaple1!',
				'legal_name'   => 'Jamie Learner',
				'display_name' => 'Jamie',
			)
		);
		$this->assertIsInt( $user_id );
		return $user_id;
	}
}
