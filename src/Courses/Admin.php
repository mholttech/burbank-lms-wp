<?php
/**
 * Course editorial controls and administrator feedback.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Courses;

/** Server-rendered controls require no new editor assets. */
class Admin {
	/**
	 * Validate CSRF before dispatching to capability-checked services.
	 *
	 * @param int    $course_id Course post ID.
	 * @param string $operation Explicit editorial action.
	 * @param string $nonce Action/course-specific nonce.
	 * @param string $revision New revision label.
	 */
	public function dispatch( int $course_id, string $operation, string $nonce, string $revision = '' ): \WP_Error|bool {
		if ( ! in_array( $operation, array( 'approve', 'revision', 'archive' ), true ) || ! wp_verify_nonce( $nonce, 'bfc_course_' . $operation . '_' . $course_id ) ) {
			return new \WP_Error( 'burbank_course_nonce', __( 'The course action expired or could not be verified. Reload the editor and try again.', 'burbank-lms' ), array( 'status' => 403 ) );
		}
		$editorial = new Editorial();
		return match ( $operation ) {
			'approve' => $editorial->approve( $course_id ),
			'revision' => $editorial->start_revision( $course_id, $revision ),
			'archive' => $editorial->archive( $course_id ),
		};
	}

	/** Handle the authenticated POST endpoint; services recheck all permissions. */
	public function handle(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- dispatch verifies operation/course-specific nonces.
		$id        = isset( $_POST['bfc_course_id'] ) ? absint( $_POST['bfc_course_id'] ) : 0;
		$operation = isset( $_POST['bfc_operation'] ) ? sanitize_key( wp_unslash( $_POST['bfc_operation'] ) ) : '';
		$nonce     = isset( $_POST[ 'bfc_nonce_' . $operation ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'bfc_nonce_' . $operation ] ) ) : '';
		$revision  = isset( $_POST['bfc_revision'] ) ? wp_unslash( $_POST['bfc_revision'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Service rejects invalid labels rather than silently changing revision identity.
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$result = $this->dispatch( $id, $operation, $nonce, $revision );
		if ( is_wp_error( $result ) && 403 === ( $result->get_error_data()['status'] ?? 0 ) ) {
			wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 403 ) );
		}
		set_transient(
			'burbank_course_editorial_notice_' . get_current_user_id(),
			array(
				'error'   => is_wp_error( $result ),
				'message' => is_wp_error( $result ) ? implode( ' ', $result->get_error_messages() ) : __( 'Course editorial action saved.', 'burbank-lms' ),
			),
			60
		);
		wp_safe_redirect( admin_url( 'post.php?post=' . $id . '&action=edit' ) );
		exit;
	}

	/** Add a Gutenberg-compatible editorial meta box. */
	public function meta_box(): void {
		add_meta_box( 'bfc-course-editorial', __( 'Course approval and history', 'burbank-lms' ), array( $this, 'controls' ), 'bfc_course', 'side', 'default', array( '__block_editor_compatible_meta_box' => true ) );
	}

	/**
	 * Render actions for the last saved configuration and retained history.
	 *
	 * @param \WP_Post $post Current course.
	 */
	public function controls( \WP_Post $post ): void {
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return;
		}
		echo '<p>' . esc_html__( 'Save your edits first. These actions apply to the saved course. Use a material revision for assessed content changes; editorial corrections may retain approval under administrator policy.', 'burbank-lms' ) . '</p>';
		if ( 'auto-draft' === $post->post_status ) {
			echo '<p>' . esc_html__( 'Save a draft before requesting approval.', 'burbank-lms' ) . '</p>';
			return;
		}
		// Enter in the revision field submits its own form, whose only button starts a revision.
		foreach ( array(
			'approve'  => 'bfc-course-actions',
			'revision' => 'bfc-course-revision',
			'archive'  => 'bfc-course-actions',
		) as $operation => $form ) {
			echo '<input form="' . esc_attr( $form ) . '" type="hidden" name="bfc_nonce_' . esc_attr( $operation ) . '" value="' . esc_attr( wp_create_nonce( 'bfc_course_' . $operation . '_' . $post->ID ) ) . '">';
		}
		echo '<p>';
		$this->column( 'bfc_approval', $post->ID );
		echo '</p>';
		if ( current_user_can( 'approve_bfc_courses' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered course capability.
			echo '<p><button form="bfc-course-actions" class="button button-primary" name="bfc_operation" value="approve">' . esc_html__( 'Approve saved course', 'burbank-lms' ) . '</button></p>';
		}
		echo '<p><label for="bfc-revision">' . esc_html__( 'New revision', 'burbank-lms' ) . '</label><input form="bfc-course-revision" id="bfc-revision" name="bfc_revision" type="text" maxlength="32"></p>';
		echo '<p><button form="bfc-course-revision" class="button" name="bfc_operation" value="revision">' . esc_html__( 'Start material revision', 'burbank-lms' ) . '</button></p>';
		echo '<p><button form="bfc-course-actions" class="button" name="bfc_operation" value="archive">' . esc_html__( 'Archive', 'burbank-lms' ) . '</button></p>';
		echo '<details><summary>' . esc_html__( 'Approval history', 'burbank-lms' ) . '</summary><ul>';
		foreach ( ( new Editorial() )->snapshots( $post->ID ) as $snapshot ) {
			echo '<li>' . esc_html( sprintf( /* translators: 1: code, 2: revision, 3: historical title, 4: approval date, 5: approver ID. */ __( '%1$s revision %2$s — %3$s; %4$s UTC; approver #%5$d', 'burbank-lms' ), $snapshot['bfc_code'], $snapshot['course_revision'], $snapshot['title'], $snapshot['approved_at'], $snapshot['approved_by'] ) ) . '</li>';
		}
		echo '</ul></details>';
	}

	/** Emit separate forms to avoid nesting inside WordPress' post editing form. */
	public function action_form(): void {
		global $post;
		if ( ! $post instanceof \WP_Post || 'bfc_course' !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) {
			return;
		}
		foreach ( array( 'bfc-course-actions', 'bfc-course-revision' ) as $form ) {
			echo '<form id="' . esc_attr( $form ) . '" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="bfc_course_editorial"><input type="hidden" name="bfc_course_id" value="' . esc_attr( (string) $post->ID ) . '"></form>';
		}
	}

	/**
	 * Add the editorial status columns to the course list.
	 *
	 * @param array<string,string> $columns Core columns.
	 * @return array<string,string> Course columns.
	 */
	public function columns( array $columns ): array {
		return array_merge(
			$columns,
			array(
				'bfc_code'        => __( 'BFC code', 'burbank-lms' ),
				'course_revision' => __( 'Revision', 'burbank-lms' ),
				'catalog_state'   => __( 'Catalog state', 'burbank-lms' ),
				'bfc_approval'    => __( 'Approval', 'burbank-lms' ),
			)
		);
	}

	/**
	 * Render escaped list values and meaningful approval state.
	 *
	 * @param string $column Column key.
	 * @param int    $course_id Course post ID.
	 */
	public function column( string $column, int $course_id ): void {
		if ( 'bfc_approval' === $column ) {
			$approved = ( new Editorial() )->matches_approval( $course_id, ( new Validator() )->fields( $course_id ) ) && user_can( (int) get_post_meta( $course_id, 'approved_by', true ), 'approve_bfc_courses' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered course capability.
			echo esc_html( $approved ? __( 'Approved', 'burbank-lms' ) : __( 'Approval required', 'burbank-lms' ) );
		} elseif ( in_array( $column, array( 'bfc_code', 'course_revision', 'catalog_state' ), true ) ) {
			$value = get_post_meta( $course_id, $column, true );
			echo esc_html( $value ?: '—' );
		}
	}

	/** Show one-time feedback after the editorial action redirect. */
	public function notice(): void {
		$key    = 'burbank_course_editorial_notice_' . get_current_user_id();
		$notice = get_transient( $key );
		delete_transient( $key );
		if ( is_array( $notice ) && current_user_can( 'edit_bfc_courses' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered course capability.
			echo '<div class="notice ' . ( $notice['error'] ? 'notice-error' : 'notice-success' ) . '"><p>' . esc_html( $notice['message'] ) . '</p></div>';
		}
	}
}
