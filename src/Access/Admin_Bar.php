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
	 * Roles that use the public site, not wp-admin.
	 *
	 * @var string[]
	 */
	private const HIDDEN_ROLES = array(
		'subscriber',
		Learner::ROLE,
		'duty_officer',
		'duty_officer_admin',
	);

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
			if ( ! in_array( $role, self::HIDDEN_ROLES, true ) ) {
				return false;
			}
		}
		return true;
	}
}
