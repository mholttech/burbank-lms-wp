<?php
/**
 * Quiz pass and certificate completion against enrollments.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Progress;

use Burbank_LMS\Enrollment\Repository;

/** Records first quiz pass and completes enrollment when a credential issues. */
class Service {
	/**
	 * Optional repository for tests; production uses a lazy default.
	 *
	 * @param Repository|null $repository Enrollment repository.
	 */
	public function __construct( private ?Repository $repository = null ) {}

	/**
	 * Stamp quiz_passed_at on the active enrollment once.
	 *
	 * @param int $user_id   Learner user ID.
	 * @param int $course_id Course post ID.
	 */
	public function record_quiz_passed( int $user_id, int $course_id ): void {
		if ( $user_id < 1 || $course_id < 1 ) {
			return;
		}
		$row = $this->repository()->get_active( $user_id, $course_id );
		if ( ! $row || ! empty( $row->quiz_passed_at ) ) {
			return;
		}
		$this->repository()->mark_quiz_passed( (int) $row->id, gmdate( 'Y-m-d H:i:s' ) );
	}

	/**
	 * Mark the active enrollment completed and fire burbank_lms_course_completed.
	 *
	 * @param int $user_id       Learner user ID.
	 * @param int $course_id     Course post ID.
	 * @param int $credential_id PressPrimer certificate row ID.
	 */
	public function complete_with_credential( int $user_id, int $course_id, int $credential_id ): void {
		if ( $user_id < 1 || $course_id < 1 || $credential_id < 1 ) {
			return;
		}
		$row = $this->repository()->get_active( $user_id, $course_id );
		if ( ! $row ) {
			return;
		}
		$completed_at = gmdate( 'Y-m-d H:i:s' );
		if ( ! $this->repository()->complete( (int) $row->id, $credential_id, $completed_at ) ) {
			return;
		}
		$enrollment = $this->repository()->get( $user_id, $course_id );
		if ( ! $enrollment ) {
			return;
		}
		/**
		 * Fires after a course enrollment is completed by certificate issuance.
		 *
		 * @param int    $user_id    Learner user ID.
		 * @param int    $course_id  Course post ID.
		 * @param object $enrollment Completed enrollment row.
		 */
		do_action( 'burbank_lms_course_completed', $user_id, $course_id, $enrollment );
	}

	/** Lazy enrollment repository. */
	private function repository(): Repository {
		return $this->repository ??= new Repository();
	}
}
