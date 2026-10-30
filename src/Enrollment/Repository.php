<?php
/**
 * Enrollment persistence against the custom enrollments table.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Enrollment;

/** SQL access for `{prefix}bfc_enrollments`. */
class Repository {
	/**
	 * Optional database connection for isolated integration tests.
	 *
	 * @param \wpdb|null $wpdb Database handle override.
	 */
	public function __construct( private ?\wpdb $wpdb = null ) {}

	/**
	 * Fetch the active enrollment row for a course/user pair.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 */
	public function get_active( int $user_id, int $course_id ): ?object {
		if ( $user_id < 1 || $course_id < 1 ) {
			return null;
		}
		$db    = $this->db();
		$table = $this->table();
		// phpcs:ignore WordPress.DB -- Prepared custom-table read.
		$row = $db->get_row(
			$db->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d AND course_id = %d AND status = %s LIMIT 1",
				$user_id,
				$course_id,
				'active'
			)
		);
		return $row instanceof \stdClass ? $row : null;
	}

	/**
	 * Fetch any enrollment row for a course/user pair regardless of status.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 */
	public function get( int $user_id, int $course_id ): ?object {
		if ( $user_id < 1 || $course_id < 1 ) {
			return null;
		}
		$db    = $this->db();
		$table = $this->table();
		// phpcs:ignore WordPress.DB -- Prepared custom-table read.
		$row = $db->get_row(
			$db->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d AND course_id = %d LIMIT 1",
				$user_id,
				$course_id
			)
		);
		return $row instanceof \stdClass ? $row : null;
	}

	/**
	 * Insert a new enrollment row.
	 *
	 * @param array<string, mixed> $data Enrollment columns.
	 */
	public function insert( array $data ): int|\WP_Error {
		$db     = $this->db();
		$table  = $this->table();
		$result = $db->insert( // phpcs:ignore WordPress.DB -- Prepared custom-table write.
			$table,
			array(
				'course_id'                => (int) $data['course_id'],
				'user_id'                  => (int) $data['user_id'],
				'status'                   => (string) $data['status'],
				'source'                   => (string) $data['source'],
				'invite_code_id'           => $data['invite_code_id'],
				'enrolled_at'              => (string) $data['enrolled_at'],
				'course_revision_snapshot' => (string) $data['course_revision_snapshot'],
			),
			array( '%d', '%d', '%s', '%s', '%d', '%s', '%s' )
		);
		if ( false === $result ) {
			return new \WP_Error( 'burbank_enrollment_insert_failed', __( 'Enrollment could not be saved.', 'burbank-lms' ) );
		}
		return (int) $db->insert_id;
	}

	/**
	 * Reactivate a withdrawn (or otherwise inactive) enrollment row.
	 *
	 * @param int      $id                       Enrollment row ID.
	 * @param string   $source                   Enrollment source.
	 * @param int|null $invite_code_id           Redeemed course invite ID.
	 * @param string   $enrolled_at              New enrollment timestamp (UTC).
	 * @param string   $course_revision_snapshot Revision string at enroll.
	 */
	public function reactivate( int $id, string $source, ?int $invite_code_id, string $enrolled_at, string $course_revision_snapshot ): true|\WP_Error {
		if ( $id < 1 ) {
			return new \WP_Error( 'burbank_enrollment_not_found', __( 'Enrollment not found.', 'burbank-lms' ) );
		}
		$db     = $this->db();
		$table  = $this->table();
		$result = $db->update( // phpcs:ignore WordPress.DB -- Prepared custom-table write.
			$table,
			array(
				'status'                   => 'active',
				'source'                   => $source,
				'invite_code_id'           => $invite_code_id,
				'enrolled_at'              => $enrolled_at,
				'completed_at'             => null,
				'quiz_passed_at'           => null,
				'credential_id'            => null,
				'course_revision_snapshot' => $course_revision_snapshot,
			),
			array(
				'id' => $id,
			),
			array( '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s' ),
			array( '%d' )
		);
		if ( false === $result ) {
			return new \WP_Error( 'burbank_enrollment_reactivate_failed', __( 'Enrollment could not be reactivated.', 'burbank-lms' ) );
		}
		return true;
	}

	/**
	 * Mark an enrollment as withdrawn.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 */
	public function withdraw( int $user_id, int $course_id ): true|\WP_Error {
		if ( $user_id < 1 || $course_id < 1 ) {
			return new \WP_Error( 'burbank_enrollment_not_found', __( 'Enrollment not found.', 'burbank-lms' ) );
		}
		$db     = $this->db();
		$table  = $this->table();
		$result = $db->update( // phpcs:ignore WordPress.DB -- Prepared custom-table write.
			$table,
			array(
				'status' => 'withdrawn',
			),
			array(
				'user_id'   => $user_id,
				'course_id' => $course_id,
				'status'    => 'active',
			),
			array( '%s' ),
			array( '%d', '%d', '%s' )
		);
		if ( false === $result || 0 === (int) $result ) {
			return new \WP_Error( 'burbank_enrollment_not_found', __( 'Active enrollment not found.', 'burbank-lms' ) );
		}
		return true;
	}

	/**
	 * Stamp quiz_passed_at when still null on an active enrollment.
	 *
	 * @param int    $id             Enrollment row ID.
	 * @param string $quiz_passed_at UTC MySQL datetime.
	 */
	public function mark_quiz_passed( int $id, string $quiz_passed_at ): bool {
		if ( $id < 1 ) {
			return false;
		}
		$db     = $this->db();
		$table  = $this->table();
		$result = $db->query( // phpcs:ignore WordPress.DB -- Prepared custom-table write.
			$db->prepare(
				"UPDATE {$table} SET quiz_passed_at = %s WHERE id = %d AND status = %s AND quiz_passed_at IS NULL",
				$quiz_passed_at,
				$id,
				'active'
			)
		);
		return false !== $result && (int) $result > 0;
	}

	/**
	 * Mark an active enrollment completed with a certificate credential.
	 *
	 * @param int    $id             Enrollment row ID.
	 * @param int    $credential_id  PressPrimer certificate row ID.
	 * @param string $completed_at   UTC MySQL datetime.
	 */
	public function complete( int $id, int $credential_id, string $completed_at ): bool {
		if ( $id < 1 || $credential_id < 1 ) {
			return false;
		}
		$db     = $this->db();
		$table  = $this->table();
		$result = $db->update( // phpcs:ignore WordPress.DB -- Prepared custom-table write.
			$table,
			array(
				'status'        => 'completed',
				'credential_id' => $credential_id,
				'completed_at'  => $completed_at,
			),
			array(
				'id'     => $id,
				'status' => 'active',
			),
			array( '%s', '%d', '%s' ),
			array( '%d', '%s' )
		);
		return false !== $result && (int) $result > 0;
	}

	/**
	 * Delete an enrollment row (compensation after a failed invite redeem).
	 *
	 * @param int $id Enrollment row ID.
	 */
	public function delete( int $id ): bool {
		if ( $id < 1 ) {
			return false;
		}
		$db = $this->db();
		return false !== $db->delete( // phpcs:ignore WordPress.DB -- Prepared custom-table write.
			$this->table(),
			array( 'id' => $id ),
			array( '%d' )
		);
	}

	/** Fully-qualified enrollments table name. */
	private function table(): string {
		return $this->db()->prefix . 'bfc_enrollments';
	}

	/** Return the database handle used for enrollment persistence. */
	private function db(): \wpdb {
		return $this->wpdb ?? $GLOBALS['wpdb'];
	}
}
