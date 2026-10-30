<?php
/**
 * Open and restricted course enrollment.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Enrollment;

use Burbank_LMS\Access\Invites;
use Burbank_LMS\Access\Learner;

/** Self/admin enroll, withdraw, and active-enrollment lookup. */
class Service {
	/**
	 * Optional collaborators for tests; production uses lazy defaults.
	 *
	 * @param Repository|null $repository Enrollment repository.
	 * @param Invites|null    $invites    Invite service.
	 * @param Learner|null    $learner    Learner access service.
	 */
	public function __construct(
		private ?Repository $repository = null,
		private ?Invites $invites = null,
		private ?Learner $learner = null,
	) {}

	/**
	 * Self-enroll a verified learner into a published, non-archived course.
	 *
	 * Restricted courses (active course invites) require a valid course invite.
	 *
	 * @param int         $user_id       Learner user ID.
	 * @param int         $course_id     Course post ID.
	 * @param string|null $course_invite Plaintext course invite when restricted.
	 */
	public function self_enroll( int $user_id, int $course_id, ?string $course_invite = null ): true|\WP_Error {
		if ( ! $this->learner()->can_learn( $user_id ) ) {
			return new \WP_Error( 'burbank_enrollment_cannot_learn', __( 'You must verify your email and have training access to enroll.', 'burbank-lms' ) );
		}
		$course_error = $this->assert_enrollable_course( $course_id );
		if ( is_wp_error( $course_error ) ) {
			return $course_error;
		}

		if ( ! $this->invites()->course_has_active_invites( $course_id ) ) {
			return $this->persist_enrollment( $user_id, $course_id, 'self', null );
		}

		$plaintext = null !== $course_invite ? (string) $course_invite : '';
		if ( '' === $plaintext ) {
			return new \WP_Error( 'burbank_enrollment_invite_required', __( 'A valid course invite is required to enroll.', 'burbank-lms' ) );
		}

		return $this->persist_restricted_enrollment( $user_id, $course_id, $plaintext );
	}

	/**
	 * Admin-assign a user to a published, non-archived course.
	 *
	 * Bypasses course invite codes and grants training access when missing.
	 *
	 * @param int $actor_id  Acting administrator user ID.
	 * @param int $user_id   Target learner user ID.
	 * @param int $course_id Course post ID.
	 */
	public function admin_enroll( int $actor_id, int $user_id, int $course_id ): true|\WP_Error {
		if ( $actor_id < 1 || ! user_can( $actor_id, 'enroll_bfc_courses' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- enroll_bfc_courses from Capabilities VERSION 2.
			return new \WP_Error( 'burbank_enrollment_forbidden', __( 'You are not allowed to enroll learners.', 'burbank-lms' ) );
		}
		if ( $user_id < 1 || ! get_userdata( $user_id ) ) {
			return new \WP_Error( 'burbank_enrollment_user_invalid', __( 'Learner not found.', 'burbank-lms' ) );
		}
		$course_error = $this->assert_enrollable_course( $course_id );
		if ( is_wp_error( $course_error ) ) {
			return $course_error;
		}
		if ( ! user_can( $user_id, Learner::CAP_ACCESS ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Learner training access.
			$this->learner()->grant_access( $user_id );
		}
		return $this->persist_enrollment( $user_id, $course_id, 'admin', null );
	}

	/**
	 * Withdraw an active enrollment (learner self or enroll_bfc_courses).
	 *
	 * @param int $actor_id  Acting user ID.
	 * @param int $user_id   Enrolled user ID.
	 * @param int $course_id Course post ID.
	 */
	public function withdraw( int $actor_id, int $user_id, int $course_id ): true|\WP_Error {
		if ( $actor_id < 1 || $user_id < 1 || $course_id < 1 ) {
			return new \WP_Error( 'burbank_enrollment_forbidden', __( 'You are not allowed to withdraw this enrollment.', 'burbank-lms' ) );
		}
		$is_self  = $actor_id === $user_id;
		$is_admin = user_can( $actor_id, 'enroll_bfc_courses' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- enroll_bfc_courses from Capabilities VERSION 2.
		if ( ! $is_self && ! $is_admin ) {
			return new \WP_Error( 'burbank_enrollment_forbidden', __( 'You are not allowed to withdraw this enrollment.', 'burbank-lms' ) );
		}
		return $this->repository()->withdraw( $user_id, $course_id );
	}

	/**
	 * Return the active enrollment row for a course/user pair.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 */
	public function get_active( int $user_id, int $course_id ): ?object {
		return $this->repository()->get_active( $user_id, $course_id );
	}

	/**
	 * List all enrollment rows for a learner.
	 *
	 * @param int $user_id User ID.
	 * @return list<object>
	 */
	public function list_for_user( int $user_id ): array {
		return $this->repository()->list_for_user( $user_id );
	}

	/**
	 * Ensure the course is a published, non-archived bfc_course.
	 *
	 * @param int $course_id Course post ID.
	 */
	private function assert_enrollable_course( int $course_id ): true|\WP_Error {
		if ( $course_id < 1 ) {
			return new \WP_Error( 'burbank_enrollment_course_invalid', __( 'Course not found.', 'burbank-lms' ) );
		}
		$post = get_post( $course_id );
		if ( ! $post || 'bfc_course' !== $post->post_type ) {
			return new \WP_Error( 'burbank_enrollment_course_invalid', __( 'Course not found.', 'burbank-lms' ) );
		}
		if ( 'publish' !== $post->post_status ) {
			return new \WP_Error( 'burbank_enrollment_course_unpublished', __( 'This course is not published.', 'burbank-lms' ) );
		}
		if ( 'archived' === (string) get_post_meta( $course_id, 'catalog_state', true ) ) {
			return new \WP_Error( 'burbank_enrollment_course_archived', __( 'This course is archived and cannot accept new enrollments.', 'burbank-lms' ) );
		}
		return true;
	}

	/**
	 * Restricted self-enroll: find invite, insert/reactivate, and redeem in one transaction.
	 *
	 * @param int    $user_id       User ID.
	 * @param int    $course_id     Course post ID.
	 * @param string $course_invite Plaintext course invite.
	 */
	private function persist_restricted_enrollment( int $user_id, int $course_id, string $course_invite ): true|\WP_Error {
		$existing = $this->repository()->get( $user_id, $course_id );
		if ( $existing && 'active' === $existing->status ) {
			return new \WP_Error( 'burbank_enrollment_already_active', __( 'Already enrolled in this course.', 'burbank-lms' ) );
		}

		global $wpdb;
		// phpcs:disable WordPress.DB -- Shared enrollment + invite redemption transaction.
		if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
			return new \WP_Error( 'burbank_enrollment_transaction_failed', __( 'Enrollment could not be started.', 'burbank-lms' ) );
		}
		$previous  = $wpdb->suppress_errors();
		$committed = false;
		$invite_id = null;
		try {
			$invite = $this->invites()->find_active( $course_invite, 'course', $course_id );
			if ( ! $invite ) {
				return new \WP_Error( 'burbank_enrollment_invite_required', __( 'A valid course invite is required to enroll.', 'burbank-lms' ) );
			}
			if ( ! empty( $invite->bound_email ) ) {
				$user  = get_userdata( $user_id );
				$email = $user ? (string) $user->user_email : '';
				if ( '' === $email || 0 !== strcasecmp( (string) $invite->bound_email, $email ) ) {
					return new \WP_Error( 'burbank_enrollment_bound_email', __( 'This course invite is bound to a different email address.', 'burbank-lms' ) );
				}
			}
			$invite_id = (int) $invite->id;
			$written   = $this->write_enrollment_row( $user_id, $course_id, 'invite', $invite_id, $existing );
			if ( is_wp_error( $written ) ) {
				return $written;
			}
			if ( ! $this->invites()->redeem( $invite_id, false ) ) {
				return new \WP_Error( 'burbank_enrollment_invite_redeem_failed', __( 'The course invite could not be redeemed.', 'burbank-lms' ) );
			}
			if ( false === $wpdb->query( 'COMMIT' ) ) {
				return new \WP_Error( 'burbank_enrollment_transaction_failed', __( 'Enrollment could not be saved.', 'burbank-lms' ) );
			}
			$committed = true;
		} finally {
			if ( ! $committed ) {
				$wpdb->query( 'ROLLBACK' );
			}
			$wpdb->suppress_errors( $previous );
		}
		// phpcs:enable WordPress.DB
		$this->fire_enrolled( $user_id, $course_id, 'invite', $invite_id );
		return true;
	}

	/**
	 * Insert or reactivate an enrollment without an invite redeem step.
	 *
	 * @param int      $user_id   User ID.
	 * @param int      $course_id Course post ID.
	 * @param string   $source    Enrollment source.
	 * @param int|null $invite_id Course invite row ID when restricted.
	 */
	private function persist_enrollment( int $user_id, int $course_id, string $source, ?int $invite_id ): true|\WP_Error {
		$existing = $this->repository()->get( $user_id, $course_id );
		if ( $existing && 'active' === $existing->status ) {
			return new \WP_Error( 'burbank_enrollment_already_active', __( 'Already enrolled in this course.', 'burbank-lms' ) );
		}
		$written = $this->write_enrollment_row( $user_id, $course_id, $source, $invite_id, $existing );
		if ( true === $written ) {
			$this->fire_enrolled( $user_id, $course_id, $source, $invite_id );
		}
		return $written;
	}

	/**
	 * Notify listeners after a successful enrollment write.
	 *
	 * @param int      $user_id   User ID.
	 * @param int      $course_id Course post ID.
	 * @param string   $source    Enrollment source.
	 * @param int|null $invite_id Course invite row ID when restricted.
	 */
	private function fire_enrolled( int $user_id, int $course_id, string $source, ?int $invite_id ): void {
		/**
		 * Fires after a learner is enrolled (self, admin, or course-invite redemption).
		 *
		 * @param int      $user_id   User ID.
		 * @param int      $course_id Course post ID.
		 * @param string   $source    Enrollment source (`self`, `admin`, or `invite`).
		 * @param int|null $invite_id Course invite row ID when restricted.
		 */
		do_action( 'burbank_lms_enrolled', $user_id, $course_id, $source, $invite_id );
	}

	/**
	 * Insert a new active row or reactivate a withdrawn/completed pair.
	 *
	 * @param int         $user_id   User ID.
	 * @param int         $course_id Course post ID.
	 * @param string      $source    Enrollment source.
	 * @param int|null    $invite_id Course invite row ID when restricted.
	 * @param object|null $existing  Existing enrollment row if any.
	 */
	private function write_enrollment_row( int $user_id, int $course_id, string $source, ?int $invite_id, ?object $existing ): true|\WP_Error {
		$revision    = (string) get_post_meta( $course_id, 'course_revision', true );
		$enrolled_at = gmdate( 'Y-m-d H:i:s' );

		if ( $existing ) {
			return $this->repository()->reactivate(
				(int) $existing->id,
				$source,
				$invite_id,
				$enrolled_at,
				$revision
			);
		}

		$inserted = $this->repository()->insert(
			array(
				'course_id'                => $course_id,
				'user_id'                  => $user_id,
				'status'                   => 'active',
				'source'                   => $source,
				'invite_code_id'           => $invite_id,
				'enrolled_at'              => $enrolled_at,
				'course_revision_snapshot' => $revision,
			)
		);
		return is_wp_error( $inserted ) ? $inserted : true;
	}

	/** Lazy enrollment repository. */
	private function repository(): Repository {
		return $this->repository ??= new Repository();
	}

	/** Lazy invite service. */
	private function invites(): Invites {
		return $this->invites ??= new Invites();
	}

	/** Lazy learner access service. */
	private function learner(): Learner {
		return $this->learner ??= new Learner();
	}
}
