<?php
/**
 * Learner lifecycle mail and PressPrimer branding coverage.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Invites;
use Burbank_LMS\Access\Learner;
use Burbank_LMS\Access\Registration;
use Burbank_LMS\Access\Tables as AccessTables;
use Burbank_LMS\Branding\PressPrimer as Branding;
use Burbank_LMS\Courses\Capabilities;
use Burbank_LMS\Enrollment\Service as EnrollmentService;
use Burbank_LMS\Enrollment\Tables as EnrollmentTables;
use Burbank_LMS\Mail\Mailer;
use Burbank_LMS\Progress\Service as ProgressService;

/** Asserts welcome/enroll/complete mail via hooks and optional PP branding. */
class MailerTest extends WP_UnitTestCase {
	/**
	 * Captured wp_mail attribute bags.
	 *
	 * @var list<array<string, mixed>>
	 */
	private array $mails = array();

	/**
	 * Capture callback for the pre_wp_mail filter.
	 *
	 * @var callable|null
	 */
	private $mail_capture = null;

	/** Install tables and capture wp_mail attributes. */
	public function set_up(): void {
		parent::set_up();
		global $wpdb;
		$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB -- Invite redeem commits outside PHPUnit's wrapper.
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}bfc_invite_codes" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}bfc_enrollments" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		delete_option( AccessTables::VERSION_OPTION );
		delete_option( EnrollmentTables::VERSION_OPTION );
		delete_option( Branding::OPTION );
		$this->assertTrue( ( new AccessTables() )->install() );
		$this->assertTrue( ( new EnrollmentTables() )->install() );
		( new Capabilities() )->install();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->mails        = array();
		$this->mail_capture = function ( $short_circuit, array $atts ) {
			unset( $short_circuit );
			$this->mails[] = $atts;
			return true;
		};
		add_filter( 'pre_wp_mail', $this->mail_capture, 10, 2 );
	}

	/** Clear captures and restore transaction wrapping. */
	public function tear_down(): void {
		if ( null !== $this->mail_capture ) {
			remove_filter( 'pre_wp_mail', $this->mail_capture, 10 );
		}
		delete_option( Branding::OPTION );
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_invite_codes" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_enrollments" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB -- Transaction integration fixture.
		parent::tear_down();
	}

	/**
	 * Register and verify a learner; return user id.
	 *
	 * @param string $email Learner email.
	 */
	private function verified_learner( string $email ): int {
		$tokens = array();
		add_action(
			'burbank_lms_sent_verification_email',
			static function ( int $uid, string $token ) use ( &$tokens ): void {
				$tokens[ $uid ] = $token;
			},
			10,
			2
		);
		$created = ( new Invites() )->create(
			'site',
			array(
				'label' => 'Batch',
				'kind'  => 'multi',
			)
		);
		$this->assertIsArray( $created );
		$registration = new Registration();
		$user_id      = $registration->register(
			array(
				'invite'       => $created['plaintext'],
				'email'        => $email,
				'password'     => 'CorrectHorseBatteryStaple1!',
				'legal_name'   => 'Jamie Learner',
				'display_name' => 'Jamie',
			)
		);
		$this->assertIsInt( $user_id );
		$this->assertArrayHasKey( $user_id, $tokens );
		$this->assertSame( $user_id, $registration->verify_email( $tokens[ $user_id ] ) );
		return $user_id;
	}

	/**
	 * Create a published bfc_course for enrollment fixtures.
	 *
	 * @param string $title Course title.
	 */
	private function published_course( string $title = 'Hazard Communication' ): int {
		$course_id = (int) self::factory()->post->create(
			array(
				'post_type'   => 'bfc_course',
				'post_status' => 'draft',
				'post_title'  => $title,
			)
		);
		update_post_meta( $course_id, 'catalog_state', 'published' );
		update_post_meta( $course_id, 'course_revision', '1.0' );
		update_post_meta( $course_id, 'course_outcome', 'independent_study' );
		global $wpdb;
		$wpdb->update( // phpcs:ignore WordPress.DB -- Bypass publication guards for enrollment fixtures.
			$wpdb->posts,
			array( 'post_status' => 'publish' ),
			array( 'ID' => $course_id ),
			array( '%s' ),
			array( '%d' )
		);
		clean_post_cache( $course_id );
		return $course_id;
	}

	/** Granting training access sends a welcome email. */
	public function test_welcome_email_on_access_granted(): void {
		$user_id     = self::factory()->user->create(
			array(
				'role'       => 'subscriber',
				'user_email' => 'welcome@example.com',
			)
		);
		$this->mails = array();
		( new Learner() )->grant_access( $user_id );
		$welcome = array_values(
			array_filter(
				$this->mails,
				static fn( array $mail ): bool => false !== strpos( (string) $mail['subject'], 'Welcome' )
			)
		);
		$this->assertNotEmpty( $welcome );
		$this->assertSame( 'welcome@example.com', $welcome[0]['to'] );
	}

	/** Self-enroll fires enrollment confirmation through the Loader-wired Mailer. */
	public function test_enrollment_confirmation_sends_mail(): void {
		$user_id     = $this->verified_learner( 'enroll-mail@example.com' );
		$course_id   = $this->published_course( 'Forklift Safety' );
		$this->mails = array();

		$this->assertTrue( ( new EnrollmentService() )->self_enroll( $user_id, $course_id ) );

		$enroll = array_values(
			array_filter(
				$this->mails,
				static fn( array $mail ): bool => false !== strpos( (string) $mail['subject'], 'Enrolled' )
			)
		);
		$this->assertCount( 1, $enroll );
		$this->assertSame( 'enroll-mail@example.com', $enroll[0]['to'] );
		$this->assertStringContainsString( 'Forklift Safety', (string) $enroll[0]['subject'] );
		$this->assertStringContainsString( 'Forklift Safety', (string) $enroll[0]['message'] );
	}

	/** Course completion action sends completion mail. */
	public function test_course_completed_sends_mail(): void {
		$user_id   = $this->verified_learner( 'complete-mail@example.com' );
		$course_id = $this->published_course( 'Bloodborne Pathogens' );
		$this->assertTrue( ( new EnrollmentService() )->self_enroll( $user_id, $course_id ) );
		$this->mails = array();

		( new ProgressService() )->complete_with_credential( $user_id, $course_id, 42 );

		$complete = array_values(
			array_filter(
				$this->mails,
				static fn( array $mail ): bool => false !== strpos( (string) $mail['subject'], 'Completed' )
			)
		);
		$this->assertCount( 1, $complete );
		$this->assertSame( 'complete-mail@example.com', $complete[0]['to'] );
		$this->assertStringContainsString( 'Bloodborne Pathogens', (string) $complete[0]['subject'] );
	}

	/** Direct Mailer completion helper respects filterable subject. */
	public function test_completion_email_filters_subject(): void {
		$user_id   = self::factory()->user->create(
			array(
				'role'       => 'subscriber',
				'user_email' => 'filter-complete@example.com',
			)
		);
		$course_id = $this->published_course( 'Filtered Course' );
		add_filter(
			'burbank_lms_completion_email_subject',
			static fn(): string => 'Custom Complete Subject',
			10,
			0
		);
		$this->mails = array();
		$this->assertTrue( ( new Mailer() )->send_completion_email( $user_id, $course_id ) );
		$this->assertSame( 'Custom Complete Subject', $this->mails[0]['subject'] );
	}

	/** Branding filter returns Burbank label when the option is enabled. */
	public function test_branding_filter_returns_burbank_label_when_enabled(): void {
		$branding = new Branding();
		$this->assertSame( 'PressPrimer Quiz', $branding->filter_plugin_name( 'PressPrimer Quiz' ) );

		update_option( Branding::OPTION, '1' );
		$this->assertSame( 'Burbank LMS', $branding->filter_plugin_name( 'PressPrimer Quiz' ) );
		$this->assertSame( 'Burbank LMS', apply_filters( 'pressprimer_quiz_plugin_name', 'PressPrimer Quiz' ) );
	}

	/** Branding stays off when the option is absent. */
	public function test_branding_filter_passthrough_when_disabled(): void {
		$this->assertSame( 'PressPrimer Quiz', apply_filters( 'pressprimer_quiz_plugin_name', 'PressPrimer Quiz' ) );
		$this->assertSame( 'PressPrimer Certificate', apply_filters( 'ppcert_plugin_name', 'PressPrimer Certificate' ) );
	}
}
