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

		$invite_id = null;
		$source    = 'self';
		if ( $this->invites()->course_has_active_invites( $course_id ) ) {
			$plaintext = null !== $course_invite ? (string) $course_invite : '';
			$invite    = '' !== $plaintext ? $this->invites()->find_active( $plaintext, 'course', $course_id ) : null;
			if ( ! $invite ) {
				return new \WP_Error( 'burbank_enrollment_invite_required', __( 'A valid course invite is required to enroll.', 'burbank-lms' ) );
			}
			$invite_id = (int) $invite->id;
			$source    = 'invite';
		}

		return $this->persist_enrollment( $user_id, $course_id, $source, $invite_id );
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
	 * Insert or reactivate an enrollment, redeeming a course invite when required.
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

		$revision    = (string) get_post_meta( $course_id, 'course_revision', true );
		$enrolled_at = gmdate( 'Y-m-d H:i:s' );
		$inserted_id = null;
		$reactivated = false;

		if ( $existing ) {
			$result = $this->repository()->reactivate(
				(int) $existing->id,
				$source,
				$invite_id,
				$enrolled_at,
				$revision
			);
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$reactivated = true;
		} else {
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
			if ( is_wp_error( $inserted ) ) {
				return $inserted;
			}
			$inserted_id = $inserted;
		}

		if ( null !== $invite_id && $invite_id > 0 && ! $this->invites()->redeem( $invite_id ) ) {
			if ( null !== $inserted_id ) {
				$this->repository()->delete( $inserted_id );
			} elseif ( $reactivated ) {
				$this->repository()->withdraw( $user_id, $course_id );
			}
			return new \WP_Error( 'burbank_enrollment_invite_redeem_failed', __( 'The course invite could not be redeemed.', 'burbank-lms' ) );
		}

		return true;
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
