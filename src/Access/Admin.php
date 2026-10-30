<?php
/**
 * Site/course invite and learner profile admin UI.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Access;

/** Server-rendered invite managers and training-access profile controls. */
class Admin {
	private const NOTICE_KEY = 'burbank_access_admin_notice_';

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

	/** Handle admin-post invite create. */
	public function handle_create_invite(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- dispatch_create_invite verifies the nonce.
		$scope     = isset( $_POST['bfc_invite_scope'] ) ? sanitize_key( wp_unslash( $_POST['bfc_invite_scope'] ) ) : 'site';
		$nonce     = isset( $_POST['bfc_invite_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bfc_invite_nonce'] ) ) : '';
		$course_id = isset( $_POST['bfc_course_id'] ) ? absint( $_POST['bfc_course_id'] ) : 0;
		$args      = array(
			'label'       => isset( $_POST['bfc_invite_label'] ) ? sanitize_text_field( wp_unslash( $_POST['bfc_invite_label'] ) ) : '',
			'kind'        => isset( $_POST['bfc_invite_kind'] ) ? sanitize_key( wp_unslash( $_POST['bfc_invite_kind'] ) ) : 'multi',
			'max_uses'    => isset( $_POST['bfc_invite_max_uses'] ) ? absint( $_POST['bfc_invite_max_uses'] ) : 0,
			'expires_at'  => isset( $_POST['bfc_invite_expires_at'] ) ? sanitize_text_field( wp_unslash( $_POST['bfc_invite_expires_at'] ) ) : '',
			'bound_email' => isset( $_POST['bfc_invite_bound_email'] ) ? sanitize_email( wp_unslash( $_POST['bfc_invite_bound_email'] ) ) : '',
			'course_id'   => $course_id,
		);
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( empty( $args['max_uses'] ) ) {
			unset( $args['max_uses'] );
		}
		if ( '' === $args['expires_at'] ) {
			unset( $args['expires_at'] );
		}
		if ( '' === $args['bound_email'] ) {
			unset( $args['bound_email'] );
		}
		$result = $this->dispatch_create_invite( $scope, $args, $nonce );
		if ( is_wp_error( $result ) && 403 === ( $result->get_error_data()['status'] ?? 0 ) ) {
			wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 403 ) );
		}
		$notice = array(
			'error'   => is_wp_error( $result ),
			'message' => is_wp_error( $result ) ? implode( ' ', $result->get_error_messages() ) : __( 'Invite created. Copy the code now; it will not be shown again.', 'burbank-lms' ),
		);
		if ( is_array( $result ) ) {
			$notice['plaintext'] = $result['plaintext'];
			$notice['message']   = sprintf(
				/* translators: %s: plaintext invite code shown once. */
				__( 'Invite created. Code (shown once): %s', 'burbank-lms' ),
				$result['plaintext']
			);
		}
		$this->store_notice( $notice );
		wp_safe_redirect( $this->redirect_after_invite( $scope, $course_id ) );
		exit;
	}

	/** Handle admin-post invite revoke. */
	public function handle_revoke_invite(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- dispatch_revoke_invite verifies the nonce.
		$id        = isset( $_POST['bfc_invite_id'] ) ? absint( $_POST['bfc_invite_id'] ) : 0;
		$nonce     = isset( $_POST['bfc_invite_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bfc_invite_nonce'] ) ) : '';
		$scope     = isset( $_POST['bfc_invite_scope'] ) ? sanitize_key( wp_unslash( $_POST['bfc_invite_scope'] ) ) : 'site';
		$course_id = isset( $_POST['bfc_course_id'] ) ? absint( $_POST['bfc_course_id'] ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$result = $this->dispatch_revoke_invite( $id, $nonce );
		if ( is_wp_error( $result ) && 403 === ( $result->get_error_data()['status'] ?? 0 ) ) {
			wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 403 ) );
		}
		$this->store_notice(
			array(
				'error'   => is_wp_error( $result ),
				'message' => is_wp_error( $result ) ? implode( ' ', $result->get_error_messages() ) : __( 'Invite revoked.', 'burbank-lms' ),
			)
		);
		wp_safe_redirect( $this->redirect_after_invite( $scope, $course_id ) );
		exit;
	}

	/** Handle admin-post learner profile actions. */
	public function handle_profile(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- dispatch_profile verifies the nonce.
		$user_id   = isset( $_POST['bfc_user_id'] ) ? absint( $_POST['bfc_user_id'] ) : 0;
		$operation = isset( $_POST['bfc_operation'] ) ? sanitize_key( wp_unslash( $_POST['bfc_operation'] ) ) : '';
		$nonce     = isset( $_POST['bfc_profile_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bfc_profile_nonce'] ) ) : '';
		$args      = array(
			'legal_name' => isset( $_POST['bfc_legal_name'] ) ? sanitize_text_field( wp_unslash( $_POST['bfc_legal_name'] ) ) : '',
		);
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$result = $this->dispatch_profile( $user_id, $operation, $nonce, $args );
		if ( is_wp_error( $result ) && 403 === ( $result->get_error_data()['status'] ?? 0 ) ) {
			wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 403 ) );
		}
		$this->store_notice(
			array(
				'error'   => is_wp_error( $result ),
				'message' => is_wp_error( $result ) ? implode( ' ', $result->get_error_messages() ) : __( 'Training profile updated.', 'burbank-lms' ),
			)
		);
		wp_safe_redirect( get_edit_user_link( $user_id ) );
		exit;
	}

	/** Register the global Site Invites admin page. */
	public function register_menu(): void {
		add_submenu_page(
			'edit.php?post_type=bfc_course',
			__( 'Site Invites', 'burbank-lms' ),
			__( 'Site Invites', 'burbank-lms' ),
			'enroll_bfc_courses', // phpcs:ignore WordPress.WP.Capabilities.Unknown -- enroll_bfc_courses from Capabilities VERSION 2.
			'burbank-lms-site-invites',
			array( $this, 'render_site_invites_page' )
		);
	}

	/** Render the Site Invites list and create form. */
	public function render_site_invites_page(): void {
		if ( ! current_user_can( 'enroll_bfc_courses' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- enroll_bfc_courses from Capabilities VERSION 2.
			wp_die( esc_html__( 'You are not allowed to manage invites.', 'burbank-lms' ), '', array( 'response' => 403 ) );
		}
		echo '<div class="wrap"><h1>' . esc_html__( 'Site Invites', 'burbank-lms' ) . '</h1>';
		$this->render_create_form( 'site', 0 );
		$this->render_invite_table( ( new Invites() )->list( 'site' ), 'site', 0 );
		echo '</div>';
	}

	/** Register the course invites meta box. */
	public function register_meta_box(): void {
		add_meta_box(
			'bfc-course-invites',
			__( 'Course Invites', 'burbank-lms' ),
			array( $this, 'render_course_invites_box' ),
			'bfc_course',
			'side',
			'default',
			array( '__block_editor_compatible_meta_box' => true )
		);
	}

	/**
	 * Render course invite create/list/revoke controls.
	 *
	 * @param \WP_Post $post Current course.
	 */
	public function render_course_invites_box( \WP_Post $post ): void {
		if ( ! current_user_can( 'enroll_bfc_courses' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- enroll_bfc_courses from Capabilities VERSION 2.
			echo '<p>' . esc_html__( 'You do not have permission to manage course invites.', 'burbank-lms' ) . '</p>';
			return;
		}
		if ( 'auto-draft' === $post->post_status ) {
			echo '<p>' . esc_html__( 'Save the course before issuing invites.', 'burbank-lms' ) . '</p>';
			return;
		}
		$this->render_create_form( 'course', (int) $post->ID );
		$this->render_invite_table( ( new Invites() )->list( 'course', (int) $post->ID ), 'course', (int) $post->ID );
	}

	/**
	 * Render training access controls on the user profile screen.
	 *
	 * @param \WP_User $user Profile user.
	 */
	public function render_profile( \WP_User $user ): void {
		if ( ! current_user_can( 'enroll_bfc_courses' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- enroll_bfc_courses from Capabilities VERSION 2.
			return;
		}
		$learner    = new Learner();
		$profile    = new Profile();
		$has_access = user_can( $user->ID, Learner::CAP_ACCESS ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Learner training access.
		$suspended  = $learner->is_suspended( (int) $user->ID );
		$invite_id  = (int) get_user_meta( $user->ID, Learner::META_SITE_INVITE_ID, true );
		$nonce      = wp_create_nonce( 'bfc_learner_profile_' . $user->ID );
		echo '<h2>' . esc_html__( 'Training Access', 'burbank-lms' ) . '</h2>';
		echo '<table class="form-table" role="presentation"><tbody>';
		echo '<tr><th>' . esc_html__( 'Training access', 'burbank-lms' ) . '</th><td>';
		echo esc_html( $has_access ? __( 'Granted', 'burbank-lms' ) : __( 'Not granted', 'burbank-lms' ) );
		echo '</td></tr>';
		echo '<tr><th>' . esc_html__( 'Suspension', 'burbank-lms' ) . '</th><td>';
		echo esc_html( $suspended ? __( 'Suspended', 'burbank-lms' ) : __( 'Active', 'burbank-lms' ) );
		echo '</td></tr>';
		echo '<tr><th>' . esc_html__( 'Site invite audit ID', 'burbank-lms' ) . '</th><td>';
		echo esc_html( $invite_id > 0 ? (string) $invite_id : '—' );
		echo '</td></tr>';
		echo '<tr><th>' . esc_html__( 'Legal / certificate name', 'burbank-lms' ) . '</th><td>';
		echo esc_html( $profile->get_legal_name( (int) $user->ID ) ?: '—' );
		echo '</td></tr>';
		echo '</tbody></table>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="bfc_learner_profile">';
		echo '<input type="hidden" name="bfc_user_id" value="' . esc_attr( (string) $user->ID ) . '">';
		echo '<input type="hidden" name="bfc_profile_nonce" value="' . esc_attr( $nonce ) . '">';
		echo '<p>';
		if ( $has_access ) {
			echo '<button class="button" name="bfc_operation" value="revoke">' . esc_html__( 'Revoke training access', 'burbank-lms' ) . '</button> ';
		} else {
			echo '<button class="button button-primary" name="bfc_operation" value="grant">' . esc_html__( 'Grant training access', 'burbank-lms' ) . '</button> ';
		}
		if ( $suspended ) {
			echo '<button class="button" name="bfc_operation" value="reactivate">' . esc_html__( 'Reactivate', 'burbank-lms' ) . '</button>';
		} else {
			echo '<button class="button" name="bfc_operation" value="suspend">' . esc_html__( 'Suspend', 'burbank-lms' ) . '</button>';
		}
		echo '</p>';
		echo '<p><label for="bfc-legal-name">' . esc_html__( 'Correct legal name', 'burbank-lms' ) . '</label> ';
		echo '<input type="text" id="bfc-legal-name" name="bfc_legal_name" class="regular-text" value="' . esc_attr( $profile->get_legal_name( (int) $user->ID ) ) . '"> ';
		echo '<button class="button" name="bfc_operation" value="legal_name">' . esc_html__( 'Save legal name correction', 'burbank-lms' ) . '</button></p>';
		echo '</form>';
	}

	/** Show one-time admin notices after invite/profile redirects. */
	public function notice(): void {
		$key    = self::NOTICE_KEY . get_current_user_id();
		$notice = get_transient( $key );
		delete_transient( $key );
		if ( ! is_array( $notice ) || ! current_user_can( 'enroll_bfc_courses' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- enroll_bfc_courses from Capabilities VERSION 2.
			return;
		}
		$class = ! empty( $notice['error'] ) ? 'notice-error' : 'notice-success';
		echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( (string) ( $notice['message'] ?? '' ) ) . '</p></div>';
	}

	/**
	 * Render a create-invite form for site or course scope.
	 *
	 * @param string $scope     Invite scope.
	 * @param int    $course_id Course ID when scope is course.
	 */
	private function render_create_form( string $scope, int $course_id ): void {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:1em 0;">';
		echo '<input type="hidden" name="action" value="bfc_invite_create">';
		echo '<input type="hidden" name="bfc_invite_scope" value="' . esc_attr( $scope ) . '">';
		echo '<input type="hidden" name="bfc_course_id" value="' . esc_attr( (string) $course_id ) . '">';
		echo '<input type="hidden" name="bfc_invite_nonce" value="' . esc_attr( wp_create_nonce( 'bfc_invite_create' ) ) . '">';
		echo '<p><label>' . esc_html__( 'Label', 'burbank-lms' ) . ' <input type="text" name="bfc_invite_label" class="regular-text"></label></p>';
		echo '<p><label>' . esc_html__( 'Kind', 'burbank-lms' ) . ' <select name="bfc_invite_kind"><option value="multi">' . esc_html__( 'Multi-use', 'burbank-lms' ) . '</option><option value="once">' . esc_html__( 'One-time', 'burbank-lms' ) . '</option></select></label></p>';
		echo '<p><label>' . esc_html__( 'Max uses (multi)', 'burbank-lms' ) . ' <input type="number" name="bfc_invite_max_uses" min="1" class="small-text"></label></p>';
		echo '<p><label>' . esc_html__( 'Expires at (UTC)', 'burbank-lms' ) . ' <input type="text" name="bfc_invite_expires_at" class="regular-text" placeholder="YYYY-MM-DD HH:MM:SS"></label></p>';
		echo '<p><label>' . esc_html__( 'Bound email', 'burbank-lms' ) . ' <input type="email" name="bfc_invite_bound_email" class="regular-text"></label></p>';
		echo '<p><button type="submit" class="button button-primary">' . esc_html__( 'Create invite', 'burbank-lms' ) . '</button></p>';
		echo '</form>';
	}

	/**
	 * Render an invite list with revoke actions.
	 *
	 * @param array<int, object> $rows      Invite rows.
	 * @param string             $scope     Invite scope.
	 * @param int                $course_id Course ID when scope is course.
	 */
	private function render_invite_table( array $rows, string $scope, int $course_id ): void {
		if ( array() === $rows ) {
			echo '<p>' . esc_html__( 'No invites yet.', 'burbank-lms' ) . '</p>';
			return;
		}
		$invites = new Invites();
		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'ID', 'burbank-lms' ) . '</th>';
		echo '<th>' . esc_html__( 'Label', 'burbank-lms' ) . '</th>';
		echo '<th>' . esc_html__( 'Kind', 'burbank-lms' ) . '</th>';
		echo '<th>' . esc_html__( 'Uses', 'burbank-lms' ) . '</th>';
		echo '<th>' . esc_html__( 'Bound email', 'burbank-lms' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'burbank-lms' ) . '</th>';
		echo '<th>' . esc_html__( 'Actions', 'burbank-lms' ) . '</th>';
		echo '</tr></thead><tbody>';
		foreach ( $rows as $row ) {
			$id      = (int) $row->id;
			$revoked = ! empty( $row->revoked_at );
			$max     = null === $row->max_uses || '' === $row->max_uses ? '∞' : (string) (int) $row->max_uses;
			$uses    = (int) $row->use_count . ' / ' . $max;
			$bound   = ! empty( $row->bound_email ) ? (string) $row->bound_email : '—';
			if ( $revoked ) {
				$status = __( 'Revoked', 'burbank-lms' );
			} elseif ( $invites->is_active( $row ) ) {
				$status = __( 'Active', 'burbank-lms' );
			} else {
				$status = __( 'Inactive', 'burbank-lms' );
			}
			echo '<tr>';
			echo '<td>' . esc_html( (string) $id ) . '</td>';
			echo '<td>' . esc_html( (string) $row->label ) . '</td>';
			echo '<td>' . esc_html( (string) $row->kind ) . '</td>';
			echo '<td>' . esc_html( $uses ) . '</td>';
			echo '<td>' . esc_html( $bound ) . '</td>';
			echo '<td>' . esc_html( $status ) . '</td><td>';
			if ( ! $revoked ) {
				echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline;">';
				echo '<input type="hidden" name="action" value="bfc_invite_revoke">';
				echo '<input type="hidden" name="bfc_invite_id" value="' . esc_attr( (string) $id ) . '">';
				echo '<input type="hidden" name="bfc_invite_scope" value="' . esc_attr( $scope ) . '">';
				echo '<input type="hidden" name="bfc_course_id" value="' . esc_attr( (string) $course_id ) . '">';
				echo '<input type="hidden" name="bfc_invite_nonce" value="' . esc_attr( wp_create_nonce( 'bfc_invite_revoke_' . $id ) ) . '">';
				echo '<button type="submit" class="button-link-delete">' . esc_html__( 'Revoke', 'burbank-lms' ) . '</button>';
				echo '</form>';
			} else {
				echo '—';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table>';
	}

	/**
	 * Audited legal-name correction via Profile.
	 *
	 * @param int    $user_id User ID.
	 * @param string $name    Corrected legal name.
	 */
	private function set_legal_name( int $user_id, string $name ): true|\WP_Error {
		$result = ( new Profile() )->set_legal_name( $user_id, $name, true );
		return is_wp_error( $result ) ? $result : true;
	}

	/**
	 * Persist a one-shot admin notice for the current user.
	 *
	 * @param array<string, mixed> $notice Notice payload.
	 */
	private function store_notice( array $notice ): void {
		set_transient( self::NOTICE_KEY . get_current_user_id(), $notice, 60 );
	}

	/**
	 * Choose redirect target after invite create/revoke.
	 *
	 * @param string $scope     Invite scope.
	 * @param int    $course_id Course ID when editing a course.
	 */
	private function redirect_after_invite( string $scope, int $course_id ): string {
		if ( 'course' === $scope && $course_id > 0 ) {
			return admin_url( 'post.php?post=' . $course_id . '&action=edit' );
		}
		return admin_url( 'edit.php?post_type=bfc_course&page=burbank-lms-site-invites' );
	}
}
