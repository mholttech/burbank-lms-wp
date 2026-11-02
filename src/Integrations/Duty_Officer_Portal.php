<?php
/**
 * Cross-links between training and the Duty Officer member portal.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Integrations;

use Burbank_LMS\Access\Learner;

/**
 * Appends training links on the Duty Officer portal and a Duty Officer pill
 * on the training shell. Neither plugin reads the other's tables.
 */
class Duty_Officer_Portal {
	/**
	 * Append a training link for the Duty Officer portal companion filter.
	 *
	 * Linked officers use the LMS portal menu, so this state stays empty.
	 *
	 * @param string $html    Existing companion HTML.
	 * @param string $context `logged_out`, `unlinked`, or `linked`.
	 */
	public function companion( string $html, string $context ): string {
		$extra = $this->companion_markup( $context );
		if ( '' === $extra ) {
			return $html;
		}
		return $html . $extra;
	}

	/**
	 * Send a training-only account from the member portal to My Courses.
	 *
	 * Linked duty officers stay on the portal. Guests are already sent to login
	 * by Duty Officer. Accounts with neither training access nor a roster link
	 * stay and see the portal's own message.
	 */
	public function redirect_training_only(): void {
		if ( ! is_user_logged_in() || ! is_singular() ) {
			return;
		}
		$post = get_queried_object();
		if ( ! $post instanceof \WP_Post || ! has_shortcode( (string) $post->post_content, 'duty_officer_member' ) ) {
			return;
		}
		$destination = $this->training_only_destination();
		if ( '' === $destination ) {
			return;
		}
		wp_safe_redirect( $destination );
		exit;
	}

	/**
	 * My Courses URL for a signed-in learner who is not a duty officer.
	 */
	public function training_only_destination(): string {
		if ( ! is_user_logged_in() || $this->current_user_is_officer() ) {
			return '';
		}
		$user_id = get_current_user_id();
		if ( $user_id < 1 || ! ( new Learner() )->can_learn( $user_id ) ) {
			return '';
		}
		return $this->page_url( 'my-courses' );
	}

	/**
	 * Training-shell item for an account that also has a Duty Officer role.
	 *
	 * @param string $current Shell context id.
	 * @return array{id:string,label:string,url:string,current:bool}|null
	 */
	public function nav_item( string $current ): ?array {
		if ( ! shortcode_exists( 'duty_officer_member' ) || ! $this->current_user_is_officer() ) {
			return null;
		}
		return array(
			'id'      => 'duty-officer',
			'label'   => __( 'Duty Officer', 'burbank-lms' ),
			'url'     => $this->portal_url(),
			'current' => 'duty-officer' === $current,
		);
	}

	/**
	 * Companion markup for one portal audience, or an empty string.
	 *
	 * @param string $context Portal audience.
	 */
	private function companion_markup( string $context ): string {
		if ( ! in_array( $context, array( 'logged_out', 'unlinked', 'linked' ), true ) ) {
			return '';
		}
		$user_id = get_current_user_id();
		$links   = array();
		if ( 'linked' === $context ) {
			// Training links join the Duty Officer tab row instead of a second bar.
			return '';
		} else {
			$links[] = $this->anchor( $this->catalog_url(), __( 'Course catalog', 'burbank-lms' ) );
			if ( 'unlinked' === $context && $user_id > 0 && ( new Learner() )->can_learn( $user_id ) ) {
				$links[] = $this->anchor( $this->page_url( 'my-courses' ), __( 'My courses', 'burbank-lms' ) );
			}
		}
		return '<p class="bfc-duty-companion">' . esc_html__( 'Training courses use this WordPress sign-in and stay separate from Duty Officer scheduling.', 'burbank-lms' ) . ' ' . implode( ' ', $links ) . '</p>';
	}

	/**
	 * Whether the current user has a Duty Officer plugin role.
	 */
	private function current_user_is_officer(): bool {
		$user = wp_get_current_user();
		if ( ! $user->exists() ) {
			return false;
		}
		$roles = (array) $user->roles;
		if ( in_array( 'duty_officer', $roles, true ) || in_array( 'duty_officer_admin', $roles, true ) ) {
			return true;
		}
		// WordPress administrators keep their role when linked. Duty Officer stores the access level in meta.
		$access = (string) get_user_meta( $user->ID, 'duty_officer_access', true );
		return in_array( $access, array( 'member', 'scheduler' ), true );
	}

	/**
	 * Member portal URL, including a custom Duty Officer base when that plugin is active.
	 */
	private function portal_url(): string {
		$fallback = home_url( '/member-portal/' );
		$class    = 'Duty_Officer\\Integrations\\Frontend\\Member_Portal_Urls';
		if ( ! class_exists( $class ) || ! is_callable( array( $class, 'url' ) ) ) {
			return $fallback;
		}
		$url = call_user_func( array( $class, 'url' ) );
		return is_string( $url ) && '' !== $url ? $url : $fallback;
	}

	/**
	 * Course catalog archive URL.
	 */
	private function catalog_url(): string {
		$link = get_post_type_archive_link( 'bfc_course' );
		if ( is_string( $link ) && '' !== $link ) {
			return $link;
		}
		return home_url( '/courses/' );
	}

	/**
	 * Learner page permalink, with a path fallback.
	 *
	 * @param string $path Page path.
	 */
	private function page_url( string $path ): string {
		$page = get_page_by_path( $path );
		if ( $page instanceof \WP_Post ) {
			$permalink = get_permalink( $page );
			if ( '' !== $permalink ) {
				return $permalink;
			}
		}
		return home_url( '/' . trim( $path, '/' ) . '/' );
	}

	/**
	 * Escaped anchor.
	 *
	 * @param string $url   Destination.
	 * @param string $label Link text.
	 */
	private function anchor( string $url, string $label ): string {
		return '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
	}
}
