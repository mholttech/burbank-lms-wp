<?php
/**
 * Outbound plugin email helpers.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Mail;

/** Sends filterable transactional mail via wp_mail. */
class Mailer {
	/**
	 * Send the email-verification message and fire a token action for consumers/tests.
	 *
	 * @param int    $user_id User ID.
	 * @param string $email   Destination address.
	 * @param string $token   Plaintext verification token.
	 */
	public function send_verification_email( int $user_id, string $email, string $token ): bool {
		$subject = apply_filters(
			'burbank_lms_verification_email_subject',
			__( 'Verify your Burbank LMS email', 'burbank-lms' ),
			$user_id
		);
		$message = apply_filters(
			'burbank_lms_verification_email_message',
			sprintf(
				/* translators: %s: verification token */
				__( "Please verify your email for Burbank LMS.\n\nVerification token: %s\n", 'burbank-lms' ),
				$token
			),
			$user_id,
			$token
		);
		$headers = apply_filters( 'burbank_lms_verification_email_headers', array(), $user_id );
		$sent    = wp_mail( $email, $subject, $message, $headers );
		/**
		 * Fires after a verification email is attempted.
		 *
		 * @param int    $user_id User ID.
		 * @param string $token   Plaintext verification token.
		 */
		do_action( 'burbank_lms_sent_verification_email', $user_id, $token );
		return (bool) $sent;
	}

	/**
	 * Welcome message after training access is granted.
	 *
	 * @param int $user_id User ID.
	 */
	public function send_welcome_email( int $user_id ): bool {
		$user = get_userdata( $user_id );
		if ( ! $user || ! is_email( $user->user_email ) ) {
			return false;
		}
		$subject = apply_filters(
			'burbank_lms_welcome_email_subject',
			__( 'Welcome to Burbank LMS', 'burbank-lms' ),
			$user_id
		);
		$message = apply_filters(
			'burbank_lms_welcome_email_message',
			__( "Welcome to Burbank LMS.\n\nYour training access has been granted. Verify your email if prompted, then browse the course catalog to enroll.\n", 'burbank-lms' ),
			$user_id
		);
		$headers = apply_filters( 'burbank_lms_welcome_email_headers', array(), $user_id );
		$sent    = wp_mail( $user->user_email, $subject, $message, $headers );
		/**
		 * Fires after a welcome email is attempted.
		 *
		 * @param int $user_id User ID.
		 */
		do_action( 'burbank_lms_sent_welcome_email', $user_id );
		return (bool) $sent;
	}

	/**
	 * Enrollment confirmation (self, admin, or course-invite redemption).
	 *
	 * @param int $user_id   Learner user ID.
	 * @param int $course_id Course post ID.
	 */
	public function send_enrollment_email( int $user_id, int $course_id ): bool {
		$user = get_userdata( $user_id );
		if ( ! $user || ! is_email( $user->user_email ) || $course_id < 1 ) {
			return false;
		}
		$title   = $this->course_title( $course_id );
		$subject = apply_filters(
			'burbank_lms_enrollment_email_subject',
			sprintf(
				/* translators: %s: course title */
				__( 'Enrolled: %s', 'burbank-lms' ),
				$title
			),
			$user_id,
			$course_id
		);
		$message = apply_filters(
			'burbank_lms_enrollment_email_message',
			sprintf(
				/* translators: %s: course title */
				__( "You are enrolled in %s.\n\nYou can open the course from your catalog when you are ready to begin.\n", 'burbank-lms' ),
				$title
			),
			$user_id,
			$course_id
		);
		$headers = apply_filters( 'burbank_lms_enrollment_email_headers', array(), $user_id, $course_id );
		$sent    = wp_mail( $user->user_email, $subject, $message, $headers );
		/**
		 * Fires after an enrollment confirmation email is attempted.
		 *
		 * @param int $user_id   User ID.
		 * @param int $course_id Course post ID.
		 */
		do_action( 'burbank_lms_sent_enrollment_email', $user_id, $course_id );
		return (bool) $sent;
	}

	/**
	 * Course completed after certificate issuance.
	 *
	 * @param int         $user_id    Learner user ID.
	 * @param int         $course_id  Course post ID.
	 * @param object|null $enrollment Completed enrollment row.
	 */
	public function send_completion_email( int $user_id, int $course_id, $enrollment = null ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Enrollment passed for filters/templates.
		$user = get_userdata( $user_id );
		if ( ! $user || ! is_email( $user->user_email ) || $course_id < 1 ) {
			return false;
		}
		$title   = $this->course_title( $course_id );
		$subject = apply_filters(
			'burbank_lms_completion_email_subject',
			sprintf(
				/* translators: %s: course title */
				__( 'Completed: %s', 'burbank-lms' ),
				$title
			),
			$user_id,
			$course_id,
			$enrollment
		);
		$message = apply_filters(
			'burbank_lms_completion_email_message',
			sprintf(
				/* translators: %s: course title */
				__( "Congratulations — you completed %s.\n\nYour certificate has been issued for this course.\n", 'burbank-lms' ),
				$title
			),
			$user_id,
			$course_id,
			$enrollment
		);
		$headers = apply_filters( 'burbank_lms_completion_email_headers', array(), $user_id, $course_id, $enrollment );
		$sent    = wp_mail( $user->user_email, $subject, $message, $headers );
		/**
		 * Fires after a completion email is attempted.
		 *
		 * @param int         $user_id    User ID.
		 * @param int         $course_id  Course post ID.
		 * @param object|null $enrollment Completed enrollment row.
		 */
		do_action( 'burbank_lms_sent_completion_email', $user_id, $course_id, $enrollment );
		return (bool) $sent;
	}

	/**
	 * Loader callback: welcome when training access is newly granted.
	 *
	 * @param int      $user_id        User ID.
	 * @param int|null $site_invite_id Site invite row ID when applicable.
	 */
	public function on_access_granted( int $user_id, $site_invite_id = null ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Action signature.
		$this->send_welcome_email( $user_id );
	}

	/**
	 * Loader callback: enrollment confirmation (covers course-invite redemption).
	 *
	 * @param int      $user_id   User ID.
	 * @param int      $course_id Course post ID.
	 * @param string   $source    Enrollment source.
	 * @param int|null $invite_id Course invite ID when restricted.
	 */
	public function on_enrolled( int $user_id, int $course_id, string $source = '', $invite_id = null ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Action signature.
		$this->send_enrollment_email( $user_id, $course_id );
	}

	/**
	 * Loader callback: completion mail after burbank_lms_course_completed.
	 *
	 * @param int         $user_id    User ID.
	 * @param int         $course_id  Course post ID.
	 * @param object|null $enrollment Completed enrollment row.
	 */
	public function on_course_completed( int $user_id, int $course_id, $enrollment = null ): void {
		$this->send_completion_email( $user_id, $course_id, $enrollment );
	}

	/**
	 * Resolve a course title for mail copy.
	 *
	 * @param int $course_id Course post ID.
	 */
	private function course_title( int $course_id ): string {
		$post = get_post( $course_id );
		if ( $post && '' !== $post->post_title ) {
			return $post->post_title;
		}
		return __( 'your course', 'burbank-lms' );
	}
}
