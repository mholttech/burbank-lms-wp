<?php
/**
 * Soft-gate presentation for blocked learner surfaces.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Learner;

use Burbank_LMS\Access\Learner;

/** Builds invite-only / no-access cards without leaking catalog content. */
class Soft_Gate {
	/**
	 * Render the verification panel beneath the portal heading.
	 *
	 * @param int $user_id User ID.
	 */
	public function render_verify( int $user_id ): string {
		$user = get_userdata( $user_id );
		return '<section class="bfc-soft-gate" data-bfc-verify data-bfc-rest-config="' . esc_attr( Block_Context::rest_config_json() ) . '"><p class="bfc-soft-gate__caption">' . esc_html__( 'Training', 'burbank-lms' ) . '</p><h2 class="bfc-soft-gate__title">' . esc_html__( 'Verify your email', 'burbank-lms' ) . '</h2><p class="bfc-soft-gate__body" data-bfc-verify-email>' . esc_html( $user ? $user->user_email : '' ) . '</p><button class="wp-element-button" type="button" data-bfc-resend-verify>' . esc_html__( 'Resend verification email', 'burbank-lms' ) . '</button><form data-bfc-change-email><label>' . esc_html__( 'Change email', 'burbank-lms' ) . ' <input type="email" name="email" required autocomplete="email"></label><button class="wp-element-button" type="submit">' . esc_html__( 'Send verification', 'burbank-lms' ) . '</button></form><p role="status" aria-live="polite"></p></section>';
	}

	public const AUDIENCE_LEARNER   = 'learner';
	public const AUDIENCE_GUEST     = 'guest';
	public const AUDIENCE_NO_ACCESS = 'no_access';

	/**
	 * Resolve presentation audience for a user id.
	 *
	 * @param int $user_id WordPress user id (0 = guest).
	 */
	public function audience( int $user_id ): string {
		if ( $user_id < 1 ) {
			return self::AUDIENCE_GUEST;
		}
		if ( ( new Learner() )->can_learn( $user_id ) ) {
			return self::AUDIENCE_LEARNER;
		}
		return self::AUDIENCE_NO_ACCESS;
	}

	/**
	 * Whether learner UI should show the soft-gate card instead of content.
	 *
	 * @param int $user_id WordPress user id.
	 */
	public function should_block( int $user_id ): bool {
		return self::AUDIENCE_LEARNER !== $this->audience( $user_id );
	}

	/**
	 * Escaped HTML card for guest or no-access audiences; empty for learners.
	 *
	 * @param string $audience One of AUDIENCE_* constants.
	 */
	public function render( string $audience ): string {
		if ( self::AUDIENCE_LEARNER === $audience ) {
			return '';
		}
		if ( self::AUDIENCE_GUEST === $audience ) {
			$title    = __( 'Training access required', 'burbank-lms' );
			$body     = __( 'Course training is invite-only. Log in with a training account or create one with a site invite code.', 'burbank-lms' );
			$login    = wp_login_url( get_permalink() ?: home_url( '/' ) );
			$register = home_url( '/register/' );
			$actions  = sprintf(
				'<p class="bfc-soft-gate__actions"><a class="wp-element-button" href="%1$s">%2$s</a> <a class="wp-element-button is-secondary" href="%3$s">%4$s</a></p>',
				esc_url( $login ),
				esc_html__( 'Log in', 'burbank-lms' ),
				esc_url( $register ),
				esc_html__( 'Register', 'burbank-lms' )
			);
		} else {
			$title   = __( 'Training access required', 'burbank-lms' );
			$body    = __( 'Your account does not have training access. Contact a training administrator to be invited or reactivated.', 'burbank-lms' );
			$actions = '';
		}

		return sprintf(
			'<div class="bfc-soft-gate"><p class="bfc-soft-gate__caption">%1$s</p><h1 class="bfc-soft-gate__title">%2$s</h1><p class="bfc-soft-gate__body">%3$s</p>%4$s</div>',
			esc_html__( 'Training', 'burbank-lms' ),
			esc_html( $title ),
			esc_html( $body ),
			$actions // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built with esc_url/esc_html above.
		);
	}
}
