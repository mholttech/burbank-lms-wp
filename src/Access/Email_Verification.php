<?php
/** Email ownership verification.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Access;

/** Expiring email-bound tokens, resend limits and public landing. */
class Email_Verification {
	/**
	 * Issue an expiring token bound to the current email.
	 *
	 * @param int $user_id User ID.
	 */
	public function issue_token( int $user_id ): string {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return '';
		}
		$token = $user_id . '.' . bin2hex( random_bytes( 32 ) );
		update_user_meta(
			$user_id,
			'burbank_lms_email_token',
			array(
				'hash'    => hash( 'sha256', $token ),
				'email'   => $user->user_email,
				'expires' => time() + DAY_IN_SECONDS,
			)
		);
		return $token;
	}

	/**
	 * Consume an email verification token.
	 *
	 * @param string $token Plain token.
	 */
	public function consume_token( string $token ): int|\WP_Error {
		$user_id = absint( explode( '.', $token )[0] );
		$record  = get_user_meta( $user_id, 'burbank_lms_email_token', true );
		$user    = get_userdata( $user_id );
		if ( ! $user || ! is_array( $record ) || $record['expires'] < time() || $record['email'] !== $user->user_email || ! hash_equals( $record['hash'], hash( 'sha256', $token ) ) ) {
			return new \WP_Error( 'burbank_verify_invalid', __( 'This verification link is invalid or expired.', 'burbank-lms' ) );
		}
		if ( ! delete_user_meta( $user_id, 'burbank_lms_email_token', $record ) ) {
			return new \WP_Error( 'burbank_verify_used', __( 'This verification link has already been used.', 'burbank-lms' ) );
		}
		( new Learner() )->mark_email_verified( $user_id );
		return $user_id;
	}

	/**
	 * Send a verification link with a per-user resend limit.
	 *
	 * @param int $user_id User ID.
	 */
	public function send( int $user_id ): bool|\WP_Error {
		if ( (int) get_user_meta( $user_id, 'burbank_lms_verify_sent', true ) > time() - 60 ) {
			return new \WP_Error( 'burbank_verify_throttled', __( 'Please wait a minute before requesting another email.', 'burbank-lms' ), array( 'status' => 429 ) );
		}
		update_user_meta( $user_id, 'burbank_lms_verify_sent', time() );
		$token = $this->issue_token( $user_id );
		return ( new \Burbank_LMS\Mail\Mailer() )->send_verification_email( $user_id, $token );
	}

	/** Invalidate verification after any WordPress email change.
	 *
	 * @param int      $user_id User ID.
	 * @param \WP_User $old_user Previous user record.
	 */
	public function on_profile_update( int $user_id, \WP_User $old_user ): void {
		$user = get_userdata( $user_id );
		if ( $user && $old_user->user_email !== $user->user_email ) {
			update_user_meta( $user_id, Learner::META_EMAIL_VERIFIED, '0' );
			delete_user_meta( $user_id, 'burbank_lms_email_token' );
			( new Learner() )->sync_quiz_cap( $user_id );
		}
	}

	/** Register authenticated verification actions. */
	public function register_routes(): void {
		foreach ( array( 'resend-verification', 'email' ) as $action ) {
			register_rest_route(
				'burbank-lms/v1',
				'/profile/' . $action,
				array(
					'methods'             => 'POST',
					'permission_callback' => static fn() => ( new Learner() )->can_learn( get_current_user_id() ),
					'callback'            => function ( \WP_REST_Request $request ) use ( $action ) {
						$id = get_current_user_id();
						if ( 'email' === $action ) {
							if ( (int) get_user_meta( $id, 'burbank_lms_verify_sent', true ) > time() - 60 ) {
								return new \WP_Error( 'burbank_verify_throttled', __( 'Please wait a minute before changing your email.', 'burbank-lms' ), array( 'status' => 429 ) );
							}
							$email = sanitize_email( (string) $request->get_param( 'email' ) );
							if ( ! is_email( $email ) || email_exists( $email ) ) {
								return new \WP_Error( 'burbank_email_invalid', __( 'Enter an available valid email address.', 'burbank-lms' ), array( 'status' => 400 ) );
							}
							$result = wp_update_user(
								array(
									'ID'         => $id,
									'user_email' => $email,
								)
							);
							if ( is_wp_error( $result ) ) {
								return $result;
							}
							update_user_meta( $id, Learner::META_EMAIL_VERIFIED, '0' );
							( new Learner() )->sync_quiz_cap( $id );
						}
						if ( ! ( new Learner() )->needs_email_verification( $id ) ) {
							return new \WP_Error( 'burbank_already_verified', __( 'Your email is already verified.', 'burbank-lms' ), array( 'status' => 400 ) );
						}
						$result = $this->send( $id );
						return is_wp_error( $result ) ? $result : rest_ensure_response(
							array(
								'sent'  => $result,
								'email' => get_userdata( $id )->user_email,
							)
						);
					},
				)
			);
		}
	}

	/** Public email link landing, outside learner shell. */
	public function landing(): void {
		if ( ! isset( $_GET['burbank_lms_verify'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- One-time bearer token.
			return;
		}
		$token  = sanitize_text_field( wp_unslash( $_GET['burbank_lms_verify'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- One-time bearer token.
		$result = $this->consume_token( $token );
		wp_die( is_wp_error( $result ) ? esc_html( $result->get_error_message() ) . ' <a href="' . esc_url( home_url( '/profile/' ) ) . '">' . esc_html__( 'Request another email', 'burbank-lms' ) . '</a>' : esc_html__( 'Email verified.', 'burbank-lms' ) . ' <a href="' . esc_url( home_url( '/my-courses/' ) ) . '">' . esc_html__( 'Continue to training', 'burbank-lms' ) . '</a>', esc_html__( 'Email verification', 'burbank-lms' ), array( 'response' => is_wp_error( $result ) ? 400 : 200 ) );
	}
}
