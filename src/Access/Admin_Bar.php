<?php
/**
 * Front-end toolbar visibility for training accounts.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Access;

/**
 * Hides the WordPress admin bar for learners and other non-editorial roles.
 */
class Admin_Bar {
	/**
	 * Public-site roles. Editors and administrators are excluded by capability.
	 *
	 * @return string[]
	 */
	public static function hidden_roles(): array {
		$roles = apply_filters(
			'burbankcert_toolbar_hidden_roles',
			array(
				'subscriber',
				Learner::ROLE,
				'duty_officer',
				'duty_officer_admin',
			)
		);
		return array_values( array_unique( array_map( 'strval', is_array( $roles ) ? $roles : array() ) ) );
	}

	/**
	 * Hide the toolbar when every role this account has is a volunteer role.
	 *
	 * Editors and administrators keep the bar.
	 *
	 * @param bool $show Whether the admin bar would show.
	 */
	public function filter_show( bool $show ): bool {
		if ( ! is_user_logged_in() || is_admin() ) {
			return $show;
		}
		return $this->hides_for_current_user() ? false : $show;
	}

	/**
	 * Whether the current account is only a subscriber, learner, or duty officer.
	 */
	public function hides_for_current_user(): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		if ( current_user_can( 'edit_posts' ) || current_user_can( 'manage_options' ) ) {
			return false;
		}
		$roles = (array) wp_get_current_user()->roles;
		if ( array() === $roles ) {
			return false;
		}
		foreach ( $roles as $role ) {
			if ( ! in_array( $role, self::hidden_roles(), true ) ) {
				return false;
			}
		}
		return true;
	}
}
