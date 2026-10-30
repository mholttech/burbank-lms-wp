<?php
/**
 * PressPrimer Quiz access and progress adapters.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Integrations;

use Burbank_LMS\Access\Learner;
use Burbank_LMS\Enrollment\Service as Enrollment_Service;
use Burbank_LMS\Progress\Service as Progress_Service;

/**
 * Gates mapped quizzes and records first pass onto the active enrollment.
 *
 * Unmapped quizzes (no published `bfc_course` with matching `pressprimer_quiz_id`)
 * are left unchanged so PressPrimer can still run standalone quizzes.
 */
class PressPrimer_Quiz {
	/**
	 * Optional collaborators for tests; production uses lazy defaults.
	 *
	 * @param Progress_Service|null   $progress   Progress service.
	 * @param Enrollment_Service|null $enrollment Enrollment service.
	 * @param Learner|null            $learner    Learner access service.
	 */
	public function __construct(
		private ?Progress_Service $progress = null,
		private ?Enrollment_Service $enrollment = null,
		private ?Learner $learner = null,
	) {}

	/**
	 * Filter whether the current user may start a quiz.
	 *
	 * @param mixed $can_start True if allowed, WP_Error to block.
	 * @param mixed $quiz      PressPrimer quiz object.
	 * @return true|\WP_Error
	 */
	public function can_start( $can_start, $quiz ) {
		if ( is_wp_error( $can_start ) ) {
			return $can_start;
		}
		$quiz_id = $this->quiz_id( $quiz );
		if ( $quiz_id < 1 ) {
			return $can_start;
		}
		$course_id = $this->published_course_id_for_quiz( $quiz_id );
		if ( null === $course_id ) {
			return $can_start;
		}
		$user_id = get_current_user_id();
		$gate    = $this->assert_may_take( $user_id, $course_id );
		return is_wp_error( $gate ) ? $gate : true;
	}

	/**
	 * Re-check enrollment/access before attempt submission.
	 *
	 * @param mixed $attempt         Attempt object or prior rejection.
	 * @param mixed $deadline_submit Whether submission was deadline-triggered.
	 * @return mixed
	 */
	public function before_submit( $attempt, $deadline_submit = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- PressPrimer filter signature.
		if ( is_wp_error( $attempt ) || ! is_object( $attempt ) ) {
			return $attempt;
		}
		$quiz_id = isset( $attempt->quiz_id ) ? (int) $attempt->quiz_id : 0;
		if ( $quiz_id < 1 ) {
			return $attempt;
		}
		$course_id = $this->published_course_id_for_quiz( $quiz_id );
		if ( null === $course_id ) {
			return $attempt;
		}
		$user_id = isset( $attempt->user_id ) ? (int) $attempt->user_id : get_current_user_id();
		$gate    = $this->assert_may_take( $user_id, $course_id );
		return is_wp_error( $gate ) ? $gate : $attempt;
	}

	/**
	 * Record the first quiz pass for a mapped course enrollment.
	 *
	 * @param mixed $attempt Attempt object.
	 * @param mixed $quiz    Quiz object.
	 */
	public function quiz_passed( $attempt, $quiz ): void {
		if ( ! is_object( $attempt ) ) {
			return;
		}
		$user_id = isset( $attempt->user_id ) ? (int) $attempt->user_id : 0;
		$quiz_id = $this->quiz_id( $quiz );
		if ( $quiz_id < 1 && isset( $attempt->quiz_id ) ) {
			$quiz_id = (int) $attempt->quiz_id;
		}
		if ( $user_id < 1 || $quiz_id < 1 ) {
			return;
		}
		$course_id = $this->published_course_id_for_quiz( $quiz_id );
		if ( null === $course_id ) {
			return;
		}
		$this->progress()->record_quiz_passed( $user_id, $course_id );
	}

	/**
	 * Shared gate: can_learn + active enrollment on the mapped course.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 */
	private function assert_may_take( int $user_id, int $course_id ): true|\WP_Error {
		if ( $user_id < 1 || ! $this->learner()->can_learn( $user_id ) ) {
			return new \WP_Error(
				'burbank_quiz_access',
				__( 'You must verify your email and have training access to take this quiz.', 'burbank-lms' )
			);
		}
		if ( ! $this->enrollment()->get_active( $user_id, $course_id ) ) {
			return new \WP_Error(
				'burbank_quiz_enrollment',
				__( 'You must be enrolled in this course to take the quiz.', 'burbank-lms' )
			);
		}
		return true;
	}

	/**
	 * Published bfc_course ID mapped to a quiz, or null when unmapped.
	 *
	 * @param int $quiz_id PressPrimer quiz row ID.
	 */
	private function published_course_id_for_quiz( int $quiz_id ): ?int {
		if ( $quiz_id < 1 ) {
			return null;
		}
		$ids = get_posts(
			array(
				'post_type'              => 'bfc_course',
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Single quiz→course lookup.
					array(
						'key'   => 'pressprimer_quiz_id',
						'value' => (string) $quiz_id,
					),
				),
			)
		);
		return $ids ? (int) $ids[0] : null;
	}

	/**
	 * Extract quiz row ID from a PressPrimer quiz object.
	 *
	 * @param mixed $quiz Quiz object.
	 */
	private function quiz_id( $quiz ): int {
		return is_object( $quiz ) && isset( $quiz->id ) ? (int) $quiz->id : 0;
	}

	/** Lazy progress service. */
	private function progress(): Progress_Service {
		return $this->progress ??= new Progress_Service();
	}

	/** Lazy enrollment service. */
	private function enrollment(): Enrollment_Service {
		return $this->enrollment ??= new Enrollment_Service();
	}

	/** Lazy learner access service. */
	private function learner(): Learner {
		return $this->learner ??= new Learner();
	}
}
