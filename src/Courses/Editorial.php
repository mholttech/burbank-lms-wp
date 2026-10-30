<?php
/**
 * Course approval, material revisions and retained configuration history.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Courses;

/** Approval snapshots describe configuration, never learner achievements. */
class Editorial {
	public const SNAPSHOT_META = '_bfc_approval_snapshot';

	/** Only approve() may append snapshot rows.
	 *
	 * @var bool
	 */
	private static bool $appending = false;

	/**
	 * Approve a saved, valid configuration as the current Training Administrator.
	 *
	 * @param int $course_id Course post ID.
	 */
	public function approve( int $course_id ): \WP_Error|bool {
		if ( ! current_user_can( 'approve_bfc_courses' ) || ! current_user_can( 'edit_post', $course_id ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered course capability.
			return new \WP_Error( 'burbank_course_approve_forbidden', __( 'A Training Administrator must approve this course.', 'burbank-lms' ), array( 'status' => 403 ) );
		}
		if ( ! $this->saved_course( $course_id ) ) {
			return new \WP_Error( 'burbank_course_save_draft', __( 'Save a course draft before requesting approval.', 'burbank-lms' ), array( 'status' => 400 ) );
		}
		$fields = ( new Validator() )->fields( $course_id );
		$error  = ( new Publication() )->validate_candidate( $course_id, $fields );
		if ( $error->has_errors() ) {
			return $error;
		}
		foreach ( $this->snapshots( $course_id ) as $snapshot ) {
			// A revision label names one assessed configuration, identity included, for this course.
			if ( (string) $snapshot['course_revision'] !== (string) $fields['course_revision'] ) {
				continue;
			}
			foreach ( Validator::PUBLISHED_GATES as $key ) {
				if ( (string) $snapshot[ $key ] !== (string) $fields[ $key ] ) {
					return new \WP_Error( 'burbank_course_revision_required', __( 'Start a new material revision before approving changed assessed configuration.', 'burbank-lms' ), array( 'status' => 400 ) );
				}
			}
		}
		if ( $this->matches_approval( $course_id, $fields ) && user_can( (int) get_post_meta( $course_id, 'approved_by', true ), 'approve_bfc_courses' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered course capability.
			return true;
		}

		$snapshot = array(
			'title'                   => get_post( $course_id )->post_title,
			'bfc_code'                => $fields['bfc_code'],
			'course_revision'         => $fields['course_revision'],
			'subject_category'        => $fields['subject_category'],
			'course_outcome'          => $fields['course_outcome'],
			'pressprimer_quiz_id'     => (int) $fields['pressprimer_quiz_id'],
			'certificate_template_id' => (int) $fields['certificate_template_id'],
			'approved_by'             => get_current_user_id(),
			'approved_at'             => gmdate( 'Y-m-d H:i:s' ),
		);
		return $this->persist_approval( $course_id, $snapshot ) ? true : $this->storage_error();
	}

	/**
	 * Commit snapshot and current evidence together, retaining earlier approval on failure.
	 *
	 * @param int                 $course_id Saved course ID.
	 * @param array<string,mixed> $snapshot Validated approval configuration.
	 */
	private function persist_approval( int $course_id, array $snapshot ): bool {
		global $wpdb;
		// phpcs:disable WordPress.DB -- Atomic postmeta approval; no partial historical approval on failure.
		if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
			return false;
		}
		$committed = false;
		try {
			self::$appending = true;
			try {
				$saved = add_post_meta( $course_id, self::SNAPSHOT_META, wp_slash( $snapshot ) );
			} finally {
				self::$appending = false;
			}
			if ( ! $saved ) {
				return false;
			}
			Validator::internal_write(
				static function () use ( $course_id, $snapshot ): void {
					update_post_meta( $course_id, 'approved_by', $snapshot['approved_by'] );
					update_post_meta( $course_id, 'approved_at', $snapshot['approved_at'] );
				}
			);
			wp_cache_delete( $course_id, 'post_meta' );
			if ( (int) get_post_meta( $course_id, 'approved_by', true ) !== $snapshot['approved_by'] || get_post_meta( $course_id, 'approved_at', true ) !== $snapshot['approved_at'] || ! $this->matches_approval( $course_id, ( new Validator() )->fields( $course_id ) ) || false === $wpdb->query( 'COMMIT' ) ) {
				return false;
			}
			$committed = true;
		} finally {
			if ( ! $committed ) {
				$wpdb->query( 'ROLLBACK' );
			}
			wp_cache_delete( $course_id, 'post_meta' );
		}
		// phpcs:enable WordPress.DB
		return true;
	}

	/**
	 * Start an explicitly material change; renewed approval precedes republication.
	 *
	 * @param int    $course_id Course post ID.
	 * @param string $revision New, distinct revision label.
	 */
	public function start_revision( int $course_id, string $revision ): \WP_Error|bool {
		if ( ! $this->saved_course( $course_id ) || ! current_user_can( 'edit_post', $course_id ) ) {
			return new \WP_Error( 'burbank_course_edit_forbidden', __( 'You must be able to edit a saved course to start a revision.', 'burbank-lms' ), array( 'status' => 403 ) );
		}
		$revision = trim( $revision );
		$used     = array_map( static fn( array $snapshot ): string => (string) $snapshot['course_revision'], $this->snapshots( $course_id ) );
		if ( '' === $revision || mb_strlen( $revision ) > Validator::LIMITS['course_revision'] || sanitize_text_field( $revision ) !== $revision || get_post_meta( $course_id, 'course_revision', true ) === $revision || in_array( $revision, $used, true ) ) {
			return new \WP_Error( 'burbank_course_revision', __( 'Enter a new, nonempty revision of at most 32 characters that this course has never been approved under.', 'burbank-lms' ), array( 'status' => 400 ) );
		}
		// Draft first: publication gates must not treat a material revision as an approved live edit.
		$this->invalidate( $course_id );
		if ( ! update_post_meta( $course_id, 'course_revision', wp_slash( $revision ) ) ) {
			return $this->storage_error();
		}
		return true;
	}

	/**
	 * Archive catalog visibility independently of publication and dependency health.
	 *
	 * @param int $course_id Course post ID.
	 */
	public function archive( int $course_id ): \WP_Error|bool {
		if ( ! $this->saved_course( $course_id ) || ! current_user_can( 'edit_post', $course_id ) ) {
			return new \WP_Error( 'burbank_course_edit_forbidden', __( 'You must be able to edit this saved course to archive it.', 'burbank-lms' ), array( 'status' => 403 ) );
		}
		update_post_meta( $course_id, 'catalog_state', 'archived' );
		return 'archived' === get_post_meta( $course_id, 'catalog_state', true ) ? true : $this->storage_error();
	}

	/**
	 * Read snapshots in append order. Returned arrays cannot mutate persisted rows.
	 *
	 * @param int $course_id Course post ID.
	 * @return array<int,array<string,mixed>> Immutable historical configurations.
	 */
	public function snapshots( int $course_id ): array {
		return get_post_meta( $course_id, self::SNAPSHOT_META, false );
	}

	/**
	 * Compare only assessed metadata, allowing ordinary title/content corrections.
	 *
	 * @param int                 $course_id Course post ID.
	 * @param array<string,mixed> $fields Candidate fields.
	 */
	public function matches_approval( int $course_id, array $fields ): bool {
		$history  = $this->snapshots( $course_id );
		$snapshot = end( $history );
		if ( ! $snapshot || (int) get_post_meta( $course_id, 'approved_by', true ) !== (int) $snapshot['approved_by'] || get_post_meta( $course_id, 'approved_at', true ) !== $snapshot['approved_at'] ) {
			return false;
		}
		foreach ( Validator::PUBLISHED_GATES as $key ) {
			if ( (string) ( $snapshot[ $key ] ?? '' ) !== (string) ( $fields[ $key ] ?? '' ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Invalidate only after an assessed write actually reaches storage (including deletion).
	 *
	 * @param mixed  $meta_id Metadata row ID or deleted IDs.
	 * @param int    $course_id Course post ID.
	 * @param string $key Changed field.
	 */
	public function metadata_changed( $meta_id, int $course_id, string $key ): void {
		if ( 'bfc_course' === get_post_type( $course_id ) && ! Validator::deleting( $course_id ) && in_array( $key, Validator::PUBLISHED_GATES, true ) && get_post_meta( $course_id, 'approved_by', true ) && ! $this->matches_approval( $course_id, ( new Validator() )->fields( $course_id ) ) ) {
			$this->invalidate( $course_id );
		}
	}

	/**
	 * Withdraw current evidence and live publication without touching prior snapshots.
	 *
	 * @param int $course_id Course post ID.
	 */
	private function invalidate( int $course_id ): void {
		Validator::internal_write(
			static function () use ( $course_id ): void {
				delete_post_meta( $course_id, 'approved_by' );
				delete_post_meta( $course_id, 'approved_at' );
			}
		);
		if ( in_array( get_post_status( $course_id ), array( 'publish', 'future' ), true ) ) {
			wp_update_post(
				array(
					'ID'          => $course_id,
					'post_status' => 'draft',
				)
			);
		}
	}

	/**
	 * Retire approved courses by archiving; permanent deletion would orphan their history.
	 *
	 * @param mixed    $check Existing short circuit.
	 * @param \WP_Post $post Post core is about to delete.
	 * @return mixed Unchanged result, or false to keep the course.
	 */
	public function guard_deletion( $check, \WP_Post $post ) {
		return 'bfc_course' === $post->post_type && $this->snapshots( $post->ID ) ? false : $check;
	}

	/**
	 * Protect snapshot storage, including bulk operations and metadata renames.
	 *
	 * @param mixed  $check Existing short circuit.
	 * @param int    $post_id Post ID, or zero for bulk deletion.
	 * @param string $key Metadata key.
	 * @return mixed Unchanged result or false.
	 */
	public function guard_snapshot( $check, int $post_id, string $key ) {
		if ( self::SNAPSHOT_META !== $key ) {
			return $check;
		}
		if ( 'add_post_metadata' === current_filter() && self::$appending ) {
			return $check;
		}
		// Snapshot history has no public update/delete path, including bulk deletion.
		return false;
	}

	/**
	 * Block updates or renames into/out of snapshot rows by metadata ID.
	 *
	 * @param mixed $check Existing short circuit.
	 * @param int   $meta_id Metadata row ID.
	 * @param mixed $value Candidate value.
	 * @param mixed $key New key or false.
	 * @return mixed Unchanged result or false.
	 */
	public function guard_snapshot_id( $check, int $meta_id, $value = null, $key = false ) {
		$meta = get_metadata_by_mid( 'post', $meta_id );
		return self::SNAPSHOT_META === $key || ( $meta && self::SNAPSHOT_META === $meta->meta_key ) ? false : $check;
	}

	/**
	 * Require an existing ordinary course, never auto drafts or trash.
	 *
	 * @param int $course_id Course post ID.
	 */
	private function saved_course( int $course_id ): bool {
		return 'bfc_course' === get_post_type( $course_id ) && ! in_array( get_post_status( $course_id ), array( 'auto-draft', 'trash' ), true );
	}

	/** Fail closed with actionable persistence guidance. */
	private function storage_error(): \WP_Error {
		return new \WP_Error( 'burbank_course_editorial_storage', __( 'The editorial change could not be saved. Reload the course, check its current approval, and retry.', 'burbank-lms' ), array( 'status' => 500 ) );
	}
}
