<?php
/**
 * Course enrollment admin UI.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Enrollment;

/** Server-rendered enrollment list and admin-enroll form on course screens. */
class Admin {
	private const NOTICE_KEY = 'burbank_enrollment_admin_notice_';

	/**
	 * Admin-enroll a user after CSRF and capability checks.
	 *
	 * @param int    $course_id Course post ID.
	 * @param int    $user_id   Target learner user ID.
	 * @param string $nonce     Course-specific enroll nonce.
	 */
	public function dispatch_enroll( int $course_id, int $user_id, string $nonce ): true|\WP_Error {
		if ( ! wp_verify_nonce( $nonce, 'bfc_enrollment_admin_enroll_' . $course_id ) ) {
			return new \WP_Error( 'burbank_enrollment_nonce', __( 'The enrollment action expired or could not be verified. Reload and try again.', 'burbank-lms' ), array( 'status' => 403 ) );
		}
		if ( ! current_user_can( 'enroll_bfc_courses' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- enroll_bfc_courses from Capabilities VERSION 2.
			return new \WP_Error( 'burbank_enrollment_forbidden', __( 'You are not allowed to enroll learners.', 'burbank-lms' ), array( 'status' => 403 ) );
		}
		return ( new Service() )->admin_enroll( (int) get_current_user_id(), $user_id, $course_id );
	}

	/** Handle admin-post enrollment. */
	public function handle_enroll(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- dispatch_enroll verifies the nonce.
		$course_id = isset( $_POST['bfc_course_id'] ) ? absint( $_POST['bfc_course_id'] ) : 0;
		$user_id   = isset( $_POST['bfc_user_id'] ) ? absint( $_POST['bfc_user_id'] ) : 0;
		$nonce     = isset( $_POST['bfc_enrollment_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bfc_enrollment_nonce'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$result = $this->dispatch_enroll( $course_id, $user_id, $nonce );
		if ( is_wp_error( $result ) && 403 === ( $result->get_error_data()['status'] ?? 0 ) ) {
			wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 403 ) );
		}
		set_transient(
			self::NOTICE_KEY . get_current_user_id(),
			array(
				'error'   => is_wp_error( $result ),
				'message' => is_wp_error( $result ) ? implode( ' ', $result->get_error_messages() ) : __( 'Learner enrolled.', 'burbank-lms' ),
			),
			60
		);
		wp_safe_redirect( admin_url( 'post.php?post=' . $course_id . '&action=edit' ) );
		exit;
	}

	/** Register the enrollments meta box on course screens. */
	public function register_meta_box(): void {
		add_meta_box(
			'bfc-course-enrollments',
			__( 'Enrollments', 'burbank-lms' ),
			array( $this, 'render_enrollments_box' ),
			'bfc_course',
			'normal',
			'default',
			array( '__block_editor_compatible_meta_box' => true )
		);
	}

	/**
	 * Render enrollments list and admin enroll form.
	 *
	 * @param \WP_Post $post Current course.
	 */
	public function render_enrollments_box( \WP_Post $post ): void {
		if ( ! current_user_can( 'enroll_bfc_courses' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- enroll_bfc_courses from Capabilities VERSION 2.
			echo '<p>' . esc_html__( 'You do not have permission to manage enrollments.', 'burbank-lms' ) . '</p>';
			return;
		}
		if ( 'auto-draft' === $post->post_status ) {
			echo '<p>' . esc_html__( 'Save the course before managing enrollments.', 'burbank-lms' ) . '</p>';
			return;
		}

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-bottom:1em;">';
		echo '<input type="hidden" name="action" value="bfc_enrollment_admin_enroll">';
		echo '<input type="hidden" name="bfc_course_id" value="' . esc_attr( (string) $post->ID ) . '">';
		echo '<input type="hidden" name="bfc_enrollment_nonce" value="' . esc_attr( wp_create_nonce( 'bfc_enrollment_admin_enroll_' . $post->ID ) ) . '">';
		echo '<p><label for="bfc-enroll-user-id">' . esc_html__( 'User ID', 'burbank-lms' ) . '</label> ';
		echo '<input type="number" id="bfc-enroll-user-id" name="bfc_user_id" min="1" class="small-text" required> ';
		echo '<button type="submit" class="button button-primary">' . esc_html__( 'Enroll user', 'burbank-lms' ) . '</button></p>';
		echo '</form>';

		$rows = ( new Repository() )->list_for_course( (int) $post->ID );
		if ( array() === $rows ) {
			echo '<p>' . esc_html__( 'No enrollments yet.', 'burbank-lms' ) . '</p>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'User', 'burbank-lms' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'burbank-lms' ) . '</th>';
		echo '<th>' . esc_html__( 'Source', 'burbank-lms' ) . '</th>';
		echo '<th>' . esc_html__( 'Enrolled', 'burbank-lms' ) . '</th>';
		echo '<th>' . esc_html__( 'Passed', 'burbank-lms' ) . '</th>';
		echo '<th>' . esc_html__( 'Completed', 'burbank-lms' ) . '</th>';
		echo '</tr></thead><tbody>';
		foreach ( $rows as $row ) {
			$user = get_userdata( (int) $row->user_id );
			$label = $user ? sprintf( '%s (#%d)', $user->display_name, $user->ID ) : '#' . (int) $row->user_id;
			echo '<tr>';
			echo '<td>' . esc_html( $label ) . '</td>';
			echo '<td>' . esc_html( (string) $row->status ) . '</td>';
			echo '<td>' . esc_html( (string) $row->source ) . '</td>';
			echo '<td>' . esc_html( (string) $row->enrolled_at ) . '</td>';
			echo '<td>' . esc_html( $row->quiz_passed_at ? (string) $row->quiz_passed_at : '—' ) . '</td>';
			echo '<td>' . esc_html( $row->completed_at ? (string) $row->completed_at : '—' ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	}

	/** Show one-time feedback after the enroll redirect. */
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
}
