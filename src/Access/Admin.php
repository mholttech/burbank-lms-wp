<?php
/**
 * React training administration mount, assets and compatibility dispatchers.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Access;

/** Loads the React training screens; preserves nonce-checked compatibility dispatchers. */
class Admin {

	/**
	 * Create a site or course invite after CSRF and capability checks.
	 *
	 * @param string               $scope `site` or `course`.
	 * @param array<string, mixed> $args  Invite creation options.
	 * @param string               $nonce Create-invite nonce.
	 * @return array{id:int,plaintext:string}|\WP_Error
	 */
	public function dispatch_create_invite( string $scope, array $args, string $nonce ): array|\WP_Error {
		if ( ! wp_verify_nonce( $nonce, 'bfc_invite_create' ) ) {
			return new \WP_Error( 'burbank_invite_nonce', __( 'The invite action expired or could not be verified. Reload and try again.', 'burbank-lms' ), array( 'status' => 403 ) );
		}
		if ( ! current_user_can( 'enroll_bfc_courses' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- enroll_bfc_courses from Capabilities VERSION 2.
			return new \WP_Error( 'burbank_invite_forbidden', __( 'You are not allowed to manage invites.', 'burbank-lms' ), array( 'status' => 403 ) );
		}
		return ( new Invites() )->create( $scope, $args );
	}

	/**
	 * Revoke an invite after CSRF and capability checks.
	 *
	 * @param int    $id    Invite row ID.
	 * @param string $nonce Revoke nonce for this invite.
	 */
	public function dispatch_revoke_invite( int $id, string $nonce ): true|\WP_Error {
		if ( ! wp_verify_nonce( $nonce, 'bfc_invite_revoke_' . $id ) ) {
			return new \WP_Error( 'burbank_invite_nonce', __( 'The invite action expired or could not be verified. Reload and try again.', 'burbank-lms' ), array( 'status' => 403 ) );
		}
		if ( ! current_user_can( 'enroll_bfc_courses' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- enroll_bfc_courses from Capabilities VERSION 2.
			return new \WP_Error( 'burbank_invite_forbidden', __( 'You are not allowed to manage invites.', 'burbank-lms' ), array( 'status' => 403 ) );
		}
		$result = ( new Invites() )->revoke( $id );
		return is_wp_error( $result ) ? $result : true;
	}

	/**
	 * Apply a training-profile action after CSRF and capability checks.
	 *
	 * @param int                  $user_id   Target user ID.
	 * @param string               $operation grant|revoke|suspend|reactivate|legal_name.
	 * @param string               $nonce     Profile nonce for this user.
	 * @param array<string, mixed> $args      Extra fields (legal_name).
	 */
	public function dispatch_profile( int $user_id, string $operation, string $nonce, array $args = array() ): true|\WP_Error {
		if ( ! in_array( $operation, array( 'grant', 'revoke', 'suspend', 'reactivate', 'legal_name' ), true ) || ! wp_verify_nonce( $nonce, 'bfc_learner_profile_' . $user_id ) ) {
			return new \WP_Error( 'burbank_profile_nonce', __( 'The profile action expired or could not be verified. Reload and try again.', 'burbank-lms' ), array( 'status' => 403 ) );
		}
		if ( ! current_user_can( 'enroll_bfc_courses' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- enroll_bfc_courses from Capabilities VERSION 2.
			return new \WP_Error( 'burbank_profile_forbidden', __( 'You are not allowed to manage training access.', 'burbank-lms' ), array( 'status' => 403 ) );
		}
		if ( $user_id < 1 || ! get_userdata( $user_id ) ) {
			return new \WP_Error( 'burbank_profile_invalid_user', __( 'User not found.', 'burbank-lms' ), array( 'status' => 404 ) );
		}

		$learner = new Learner();
		if ( 'legal_name' === $operation ) {
			return $this->set_legal_name( $user_id, isset( $args['legal_name'] ) ? (string) $args['legal_name'] : '' );
		}
		if ( 'grant' === $operation ) {
			$learner->grant_access( $user_id );
		} elseif ( 'revoke' === $operation ) {
			$learner->revoke_access( $user_id );
		} elseif ( 'suspend' === $operation ) {
			$learner->suspend( $user_id );
		} else {
			$learner->reactivate( $user_id );
		}
		return true;
	}

	/** Register React training screens. */
	public function register_menu(): void {
		foreach ( array(
			'site-invites' => __( 'Site Invites', 'burbank-lms' ),
			'learners'     => __( 'Training Access', 'burbank-lms' ),
		) as $slug => $label ) {
			add_submenu_page( 'edit.php?post_type=bfc_course', $label, $label, 'enroll_bfc_courses', 'burbank-lms-' . $slug, array( $this, 'render_page' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Plugin capability.
		}
	}

	/** Render an application mount point. */
	public function render_page(): void {
		if ( ! current_user_can( 'enroll_bfc_courses' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Plugin capability.
			wp_die( esc_html__( 'You cannot manage training.', 'burbank-lms' ), '', array( 'response' => 403 ) );
		}
		echo '<div class="wrap"><div id="burbank-lms-training"></div></div>';
	}

	/** Load the app only on its own screens. */
	public function enqueue(): void {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, array( 'bfc_course_page_burbank-lms-site-invites', 'bfc_course_page_burbank-lms-learners' ), true ) ) {
			return;
		}
		$this->enqueue_entrypoint( 'burbank-lms-training-admin' );
	}

	/** Load an entrypoint manifest.
	 *
	 * @param string $entry Webpack entry name.
	 */
	private function enqueue_entrypoint( string $entry ): void {
		$file = BURBANK_LMS_PATH . '/build/' . $entry . '.asset.php';
		if ( ! file_exists( $file ) ) {
			return;
		}
		$asset = require $file;
		wp_enqueue_script( $entry, BURBANK_LMS_URL . 'build/' . $entry . '.js', $asset['dependencies'], $asset['version'], true );
		wp_enqueue_style( $entry, BURBANK_LMS_URL . 'build/' . $entry . '.css', array( 'wp-components' ), $asset['version'] );
		wp_localize_script(
			$entry,
			'burbankLmsTraining',
			array(
				'restUrl' => rest_url( 'burbank-lms/v1/training' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	/** Audited legal name correction.
	 *
	 * @param int    $user_id User ID.
	 * @param string $name Corrected name.
	 */
	private function set_legal_name( int $user_id, string $name ): true|\WP_Error {
		$result = ( new Profile() )->set_legal_name( $user_id, $name, true );
		return is_wp_error( $result ) ? $result : true;
	}
}
