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
}
