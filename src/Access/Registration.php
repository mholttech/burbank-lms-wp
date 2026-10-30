<?php
/**
 * Invite-gated learner registration and email verification.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Access;

use Burbank_LMS\Mail\Mailer;

/** Site-invite registration gate, verification tokens, and open-registration block. */
class Registration {
	public const META_VERIFY_TOKEN = 'burbank_lms_email_verify_token';

	public const META_VERIFY_SENT_AT = 'burbank_lms_email_verify_sent_at';

	/**
	 * Optional collaborators for tests; production uses lazy defaults.
	 *
	 * @param Invites|null $invites Invite service.
	 * @param Learner|null $learner Learner access service.
	 * @param Profile|null $profile Profile service.
	 * @param Mailer|null  $mailer  Mail sender.
	 */
	public function __construct(
		private ?Invites $invites = null,
		private ?Learner $learner = null,
		private ?Profile $profile = null,
		private ?Mailer $mailer = null,
	) {}

	/**
	 * Register a learner through an active site invite.
	 *
	 * @param array{invite?:string,email?:string,password?:string,legal_name?:string,display_name?:string} $data Registration payload.
	 */
	public function register( array $data ): int|\WP_Error {
		$invite_code  = isset( $data['invite'] ) ? (string) $data['invite'] : '';
		$email        = isset( $data['email'] ) ? sanitize_email( (string) $data['email'] ) : '';
		$password     = isset( $data['password'] ) ? (string) $data['password'] : '';
		$legal_name   = isset( $data['legal_name'] ) ? sanitize_text_field( (string) $data['legal_name'] ) : '';
		$display_name = isset( $data['display_name'] ) ? sanitize_text_field( (string) $data['display_name'] ) : '';

		if ( '' === $invite_code ) {
			return new \WP_Error( 'burbank_register_invite_required', __( 'A site invite code is required.', 'burbank-lms' ) );
		}
		if ( '' === $legal_name ) {
			return new \WP_Error( 'burbank_register_legal_name_required', __( 'Legal name is required.', 'burbank-lms' ) );
		}
		if ( ! is_email( $email ) ) {
			return new \WP_Error( 'burbank_register_email_invalid', __( 'A valid email address is required.', 'burbank-lms' ) );
		}
		if ( '' === $password ) {
			return new \WP_Error( 'burbank_register_password_required', __( 'A password is required.', 'burbank-lms' ) );
		}
		if ( email_exists( $email ) ) {
			return new \WP_Error( 'burbank_register_email_exists', __( 'Registration could not be completed.', 'burbank-lms' ) );
		}

		$invite = $this->invites()->find_active( $invite_code, 'site' );
		if ( ! $invite ) {
			return new \WP_Error( 'burbank_register_invite_invalid', __( 'Registration could not be completed.', 'burbank-lms' ) );
		}
		if ( ! empty( $invite->bound_email ) && 0 !== strcasecmp( (string) $invite->bound_email, $email ) ) {
			return new \WP_Error( 'burbank_register_bound_email', __( 'Registration could not be completed.', 'burbank-lms' ) );
		}

		$login   = $this->unique_login_from_email( $email );
		$user_id = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_email'   => $email,
				'user_pass'    => $password,
				'display_name' => '' !== $display_name ? $display_name : $legal_name,
				'role'         => 'subscriber',
			)
		);
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}
		$user_id = (int) $user_id;

		$named = $this->profile()->set_legal_name( $user_id, $legal_name );
		if ( is_wp_error( $named ) ) {
			wp_delete_user( $user_id );
			return $named;
		}

		$this->learner()->grant_access( $user_id, (int) $invite->id );

		if ( ! $this->invites()->redeem( (int) $invite->id ) ) {
			$this->learner()->revoke_access( $user_id );
			wp_delete_user( $user_id );
			return new \WP_Error( 'burbank_register_invite_redeem_failed', __( 'Registration could not be completed.', 'burbank-lms' ) );
		}

		$this->issue_verification( $user_id, $email );
		return $user_id;
	}

	/**
	 * Confirm an email verification token and enable can_learn.
	 *
	 * @param string   $token       Plaintext token from the verification email.
	 * @param int|null $expected_uid Optional user ID from the verification link.
	 */
	public function verify_email( string $token, ?int $expected_uid = null ): int|\WP_Error {
		$token = trim( $token );
		if ( '' === $token ) {
			return new \WP_Error( 'burbank_verify_token_invalid', __( 'Verification token is invalid.', 'burbank-lms' ) );
		}
		$hash  = $this->hash_token( $token );
		$users = get_users(
			array(
				'meta_key'   => self::META_VERIFY_TOKEN, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- One-time verification lookup.
				'meta_value' => $hash, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- One-time verification lookup.
				'number'     => 1,
				'fields'     => 'ID',
			)
		);
		if ( empty( $users ) ) {
			return new \WP_Error( 'burbank_verify_token_invalid', __( 'Verification token is invalid.', 'burbank-lms' ) );
		}
		$user_id = (int) $users[0];
		if ( null !== $expected_uid && $expected_uid > 0 && $expected_uid !== $user_id ) {
			return new \WP_Error( 'burbank_verify_token_invalid', __( 'Verification token is invalid.', 'burbank-lms' ) );
		}
		update_user_meta( $user_id, Learner::META_EMAIL_VERIFIED_AT, gmdate( 'c' ) );
		delete_user_meta( $user_id, self::META_VERIFY_TOKEN );
		delete_user_meta( $user_id, self::META_VERIFY_SENT_AT );
		$this->learner()->sync_quiz_cap( $user_id );
		return $user_id;
	}

	/**
	 * Front entry for email verification links (`?burbank_lms_verify=1&uid=&token=`).
	 *
	 * Wired to `template_redirect` via Loader. Redirects to the login screen with
	 * a success or error query flag; skips `exit` when redirect is cancelled (tests).
	 */
	public function handle_verification_request(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public signed verification link (HMAC token), not a form action.
		if ( ! isset( $_GET['burbank_lms_verify'] ) ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Public signed verification link.
		$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['token'] ) ) : '';
		$uid   = isset( $_GET['uid'] ) ? absint( wp_unslash( $_GET['uid'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$result = $this->verify_email( $token, $uid > 0 ? $uid : null );
		$login  = wp_login_url();
		$target = is_wp_error( $result )
			? add_query_arg(
				array(
					'burbank_lms_verify_error' => rawurlencode( $result->get_error_code() ),
				),
				$login
			)
			: add_query_arg( array( 'burbank_lms_verified' => '1' ), $login );

		if ( wp_safe_redirect( $target ) ) {
			exit;
		}
	}

	/**
	 * Reject open wp-login.php?action=register learner self-serve registration.
	 *
	 * Admin create_users / wp_insert_user paths do not use this filter.
	 *
	 * @param \WP_Error $errors Registration errors bag.
	 */
	public function block_default_registration( \WP_Error $errors ): \WP_Error {
		$errors->add(
			'burbank_register_invite_only',
			__( 'Public registration is disabled. Use an invite link to create a learner account.', 'burbank-lms' )
		);
		return $errors;
	}

	/**
	 * When a training-access user's email changes, clear verification and re-send.
	 *
	 * Hooked to `profile_update` (fires from wp_update_user after the row is saved).
	 *
	 * @param int      $user_id       User ID.
	 * @param \WP_User $old_user_data User object before the update.
	 */
	public function handle_email_change( int $user_id, \WP_User $old_user_data ): void {
		if ( $user_id < 1 || ! user_can( $user_id, Learner::CAP_ACCESS ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Learner training access.
			return;
		}
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}
		$old_email = strtolower( (string) $old_user_data->user_email );
		$new_email = strtolower( (string) $user->user_email );
		if ( '' === $new_email || $old_email === $new_email ) {
			return;
		}
		delete_user_meta( $user_id, Learner::META_EMAIL_VERIFIED_AT );
		$this->issue_verification( $user_id, $user->user_email );
		$this->learner()->sync_quiz_cap( $user_id );
	}

	/**
	 * Issue a hashed verification token and send the verification email.
	 *
	 * @param int    $user_id User ID.
	 * @param string $email   Destination email.
	 */
	public function issue_verification( int $user_id, string $email ): void {
		$plaintext = wp_generate_password( 48, false, false );
		update_user_meta( $user_id, self::META_VERIFY_TOKEN, $this->hash_token( $plaintext ) );
		update_user_meta( $user_id, self::META_VERIFY_SENT_AT, gmdate( 'c' ) );
		$this->mailer()->send_verification_email( $user_id, $email, $plaintext );
	}

	/**
	 * Hash a verification token for usermeta storage.
	 *
	 * @param string $token Plaintext token.
	 */
	private function hash_token( string $token ): string {
		return hash_hmac( 'sha256', $token, wp_salt( 'auth' ) );
	}

	/**
	 * Derive a unique user_login from an email local-part.
	 *
	 * @param string $email Email address.
	 */
	private function unique_login_from_email( string $email ): string {
		$local = sanitize_user( strstr( $email, '@', true ) ?: $email, true );
		if ( '' === $local ) {
			$local = 'learner';
		}
		$login = $local;
		$i     = 1;
		while ( username_exists( $login ) ) {
			$login = $local . $i;
			++$i;
		}
		return $login;
	}

	/** Lazy invite service. */
	private function invites(): Invites {
		return $this->invites ??= new Invites();
	}

	/** Lazy learner service. */
	private function learner(): Learner {
		return $this->learner ??= new Learner();
	}

	/** Lazy profile service. */
	private function profile(): Profile {
		return $this->profile ??= new Profile();
	}

	/** Lazy mailer. */
	private function mailer(): Mailer {
		return $this->mailer ??= new Mailer();
	}
}
