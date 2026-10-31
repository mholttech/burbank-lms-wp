<?php
/**
 * PressPrimer quiz/certificate gate coverage.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Invites;
use Burbank_LMS\Access\Learner;
use Burbank_LMS\Access\Profile;
use Burbank_LMS\Access\Registration;
use Burbank_LMS\Access\Tables as AccessTables;
use Burbank_LMS\Courses\Capabilities;
use Burbank_LMS\Enrollment\Service as EnrollmentService;
use Burbank_LMS\Enrollment\Tables as EnrollmentTables;
use Burbank_LMS\Integrations\PressPrimer_Certificate;
use Burbank_LMS\Integrations\PressPrimer_Quiz;
use Burbank_LMS\Progress\Service as ProgressService;

/** Gates mapped quizzes and certificate issuance; leaves unmapped quizzes alone. */
class PressPrimerGateTest extends WP_UnitTestCase {
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

	/** Mapped quiz without enrollment cannot start. */
	public function test_can_start_false_without_enrollment(): void {
		$fixture = $this->mapped_course_and_learner( false );
		wp_set_current_user( $fixture['user_id'] );

		$result = ( new PressPrimer_Quiz() )->can_start( true, $fixture['quiz'] );

		$this->assertWPError( $result );
	}

	/** Learner without training access cannot start a mapped quiz even when enrolled. */
	public function test_can_start_false_without_training_access(): void {
		$fixture = $this->mapped_course_and_learner( true );
		( new Learner() )->revoke_access( $fixture['user_id'] );
		wp_set_current_user( $fixture['user_id'] );

		$result = ( new PressPrimer_Quiz() )->can_start( true, $fixture['quiz'] );

		$this->assertWPError( $result );
	}

	/** Suspended learner cannot start a mapped quiz. */
	public function test_can_start_false_when_suspended(): void {
		$fixture = $this->mapped_course_and_learner( true );
		( new Learner() )->suspend( $fixture['user_id'] );
		wp_set_current_user( $fixture['user_id'] );

		$result = ( new PressPrimer_Quiz() )->can_start( true, $fixture['quiz'] );

		$this->assertWPError( $result );
	}

	/** Active enrollment on a mapped quiz allows start. */
	public function test_can_start_true_with_active_enrollment(): void {
		$fixture = $this->mapped_course_and_learner( true );
		wp_set_current_user( $fixture['user_id'] );

		$result = ( new PressPrimer_Quiz() )->can_start( true, $fixture['quiz'] );

		$this->assertTrue( $result );
	}

	/** Quizzes with no published Burbank course stay ungated. */
	public function test_unmapped_quiz_not_gated(): void {
		PressPrimer_Quiz_Activator::activate();
		$quiz_id = PressPrimer_Quiz_Quiz::create(
			array(
				'title'  => 'Standalone',
				'status' => 'published',
			)
		);
		$quiz    = PressPrimer_Quiz_Quiz::get( $quiz_id );
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$result = ( new PressPrimer_Quiz() )->can_start( true, $quiz );

		$this->assertTrue( $result );
	}

	/** Issue validation fails without an active enrollment. */
	public function test_issue_validation_fails_without_enrollment(): void {
		$fixture = $this->mapped_course_and_learner( false );
		$context = $this->issue_context( $fixture );
		$adapter = new PressPrimer_Certificate( new ProgressService(), new EnrollmentService(), new Profile() );

		$result = $adapter->issue_validation( true, $context );

		$this->assertWPError( $result );
	}

	/** Issue validation fails without quiz_passed_at. */
	public function test_issue_validation_fails_without_quiz_pass(): void {
		$fixture = $this->mapped_course_and_learner( true );
		$context = $this->issue_context( $fixture );
		$adapter = new PressPrimer_Certificate( new ProgressService(), new EnrollmentService(), new Profile() );

		$result = $adapter->issue_validation( true, $context );

		$this->assertWPError( $result );
	}

	/** Issue validation succeeds with active enrollment and quiz pass. */
	public function test_issue_validation_succeeds_with_enrollment_and_pass(): void {
		$fixture = $this->mapped_course_and_learner( true );
		( new ProgressService() )->record_quiz_passed( $fixture['user_id'], $fixture['course_id'] );
		$context = $this->issue_context( $fixture );
		$adapter = new PressPrimer_Certificate( new ProgressService(), new EnrollmentService(), new Profile() );

		$result = $adapter->issue_validation( true, $context );

		$this->assertTrue( $result );
	}

	/** Merge data includes legal name and course fields, never email. */
	public function test_merge_data_includes_legal_name_not_email(): void {
		$fixture = $this->mapped_course_and_learner( true );
		$context = $this->issue_context( $fixture );
		$adapter = new PressPrimer_Certificate( new ProgressService(), new EnrollmentService(), new Profile() );

		$fields = $adapter->register_merge_fields( array(), 'issue' );
		$this->assertArrayHasKey( 'bfc', $fields );

		$values = $adapter->merge_data( array(), $context );

		$this->assertSame( 'Jamie Learner', $values['bfc.legal_name'] );
		$this->assertSame( 'BFC-201', $values['bfc.bfc_code'] );
		$this->assertSame( 'Gate Course', $values['bfc.title'] );
		$this->assertSame( '1.0', $values['bfc.course_revision'] );
		$this->assertSame( 'independent_study', $values['bfc.course_outcome'] );
		$this->assertSame( 'safety_field_awareness', $values['bfc.subject_category'] );
		$this->assertSame( 'req-scene-safety', $values['bfc.matrix_requirement_slug'] );
		$this->assertArrayNotHasKey( 'bfc.email', $values );
		foreach ( $values as $value ) {
			$this->assertStringNotContainsString( '@', (string) $value );
		}
	}

	/**
	 * Build a published mapped course and optional active enrollment.
	 *
	 * @param bool $enroll Whether to enroll the verified learner.
	 * @return array{user_id:int,course_id:int,quiz:object,quiz_id:int,template_id:int}
	 */
	private function mapped_course_and_learner( bool $enroll ): array {
		$mapping   = bfc_test_mapping();
		$course_id = (int) self::factory()->post->create(
			array(
				'post_type'   => 'bfc_course',
				'post_status' => 'draft',
				'post_title'  => 'Gate Course',
				'meta_input'  => array(
					'course_revision' => '1.0',
					'catalog_state'   => 'published',
				),
			)
		);
		global $wpdb;
		foreach ( array(
			'bfc_code'                => 'BFC-201',
			'subject_category'        => 'safety_field_awareness',
			'course_outcome'          => 'independent_study',
			'matrix_requirement_slug' => 'req-scene-safety',
			'pressprimer_quiz_id'     => (string) $mapping['pressprimer_quiz_id'],
			'certificate_template_id' => (string) $mapping['certificate_template_id'],
		) as $key => $value ) {
			$wpdb->replace( // phpcs:ignore WordPress.DB,WordPress.DB.SlowDBQuery -- Bypass identity/mapping guards for gate fixtures.
				$wpdb->postmeta,
				array(
					'post_id'    => $course_id,
					'meta_key'   => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Fixture write.
					'meta_value' => $value, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Fixture write.
				),
				array( '%d', '%s', '%s' )
			);
		}
		$wpdb->update( // phpcs:ignore WordPress.DB -- Bypass publication guards for gate fixtures.
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
			'user_id'     => $user_id,
			'course_id'   => $course_id,
			'quiz'        => $quiz,
			'quiz_id'     => (int) $mapping['pressprimer_quiz_id'],
			'template_id' => (int) $mapping['certificate_template_id'],
		);
	}

	/**
	 * Register a learner with training access and a legal name.
	 *
	 * @param string $email Learner email.
	 */
	private function verified_learner( string $email = '' ): int {
		if ( '' === $email ) {
			$email = 'gate-learner-' . wp_generate_password( 8, false ) . '@example.com';
		}
		$created = ( new Invites() )->create(
			'site',
			array(
				'label' => 'Gate',
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

	/**
	 * Issuance context shaped like PressPrimer Certificate 2.0.2.
	 *
	 * @param array{user_id:int,quiz_id:int,template_id:int} $fixture Mapped fixture.
	 * @return array<string, mixed>
	 */
	private function issue_context( array $fixture ): array {
		return array(
			'template_id'  => $fixture['template_id'],
			'recipient_id' => $fixture['user_id'],
			'trigger_type' => 'ppq_quiz',
			'source_type'  => 'ppq_quiz',
			'source_ref'   => (string) $fixture['quiz_id'],
			'issued_by'    => 0,
		);
	}
}
