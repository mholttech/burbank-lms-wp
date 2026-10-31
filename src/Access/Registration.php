<?php
/**
 * Invite-gated learner registration.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Access;

/** Site-invite registration gate and open-registration block. */
class Registration {
	/**
	 * Optional collaborators for tests; production uses lazy defaults.
	 *
	 * @param Invites|null $invites Invite service.
	 * @param Learner|null $learner Learner access service.
	 * @param Profile|null $profile Profile service.
	 */
	public function __construct(
		private ?Invites $invites = null,
		private ?Learner $learner = null,
		private ?Profile $profile = null,
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

		return $user_id;
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
}
