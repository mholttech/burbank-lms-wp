<?php
/**
 * Shared course metadata validation and write guards.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Courses;

/** Validates metadata before Pods, REST, classic and core metadata writes. */
class Validator {
	/** Stable subject values correspond to the first digit of the BFC code. */
	public const SUBJECTS = array(
		1 => 'program_administration_membership',
		2 => 'safety_field_awareness',
		3 => 'communications',
		4 => 'field_operations_documentation',
		5 => 'rehabilitation_logistics',
		6 => 'incident_management_coordination',
		7 => 'leadership_program_operations',
		8 => 'community_engagement_public_education',
		9 => 'reserved',
	);

	/** Maximum character lengths for free-text metadata. */
	public const LIMITS = array(
		'bfc_code'                => 7,
		'course_revision'         => 32,
		'subject_category'        => 64,
		'matrix_requirement_slug' => 200,
		'course_outcome'          => 32,
		'catalog_state'           => 16,
		'approved_at'             => 32,
		'source_revision'         => 191,
		'approval_notes'          => 2000,
	);

	/** Fields only the approval service may write through trusted server-side code. */
	public const AUDIT_FIELDS = array( 'approved_by', 'approved_at', 'approval_notes' );

	/** Metadata whose change can invalidate an already published course. Other fields (for example archiving) stay editable when a dependency fails. */
	public const PUBLISHED_GATES = array( 'bfc_code', 'course_revision', 'subject_category', 'course_outcome', 'pressprimer_quiz_id', 'certificate_template_id' );

	/** Statuses that make, or schedule, publication of a course. */
	private const PUBLICATION_STATUSES = array( 'publish', 'future' );

	/** Scoped internal approval write depth.
	 *
	 * @var int
	 */
	private static int $internal_depth = 0;

	/** Identity metadata writes recurse once inside the registry transaction.
	 *
	 * @var bool
	 */
	private static bool $writing_code = false;

	/** Courses core is permanently deleting; their metadata may be removed, never edited.
	 *
	 * @var array<int,bool>
	 */
	private static array $deleting = array();

	/** Validated batch candidates permit coherent subject/code changes.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private array $batches = array();

	/** Active Pods batch IDs.
	 *
	 * @var array<int,bool>
	 */
	private array $pods_batches = array();

	/** Existing-course publication requests wait until identity can be sealed atomically.
	 *
	 * @var array<int,bool>
	 */
	private array $publishing = array();

	/** Last rejected core write, available to editor/publication services.
	 *
	 * @var \WP_Error|null
	 */
	private ?\WP_Error $error = null;

	/** Publication failure retained through later metadata writes until the save redirect.
	 *
	 * @var \WP_Error|null
	 */
	private ?\WP_Error $publication_error = null;

	/**
	 * Validate a complete course contract without mutating storage.
	 *
	 * @param array<string,mixed> $fields Candidate metadata.
	 * @param int                 $course_id Course ID, or zero before creation.
	 */
	public function validate( array $fields, int $course_id ): \WP_Error {
		return $this->check( $fields, $course_id, true );
	}

	/**
	 * Validate incomplete drafts as well as complete publication candidates.
	 *
	 * @param array<string,mixed> $fields Candidate metadata.
	 * @param int                 $course_id Course post ID.
	 * @param bool                $complete Require all mandatory fields.
	 */
	private function check( array $fields, int $course_id, bool $complete = false ): \WP_Error {
		$error = new \WP_Error();
		foreach ( self::LIMITS as $key => $max ) {
			if ( isset( $fields[ $key ] ) && ( ! is_string( $fields[ $key ] ) || mb_strlen( $fields[ $key ] ) > $max ) ) {
				$error->add( 'burbank_course_' . $key, sprintf( /* translators: 1: field name, 2: maximum length. */ __( '%1$s must be text of at most %2$d characters.', 'burbank-lms' ), $key, $max ), array( 'status' => 400 ) );
			}
		}
		if ( $complete ) {
			foreach ( array( 'bfc_code', 'course_revision', 'subject_category', 'course_outcome', 'pressprimer_quiz_id', 'certificate_template_id' ) as $key ) {
				if ( ! isset( $fields[ $key ] ) || '' === $fields[ $key ] ) {
					$error->add( 'burbank_course_required', sprintf( /* translators: %s: required field name. */ __( 'Complete the required course field: %s.', 'burbank-lms' ), $key ), array( 'status' => 400 ) );
				}
			}
		}
		$code = $fields['bfc_code'] ?? '';
		if ( '' !== $code ) {
			if ( ! is_string( $code ) || ! preg_match( '/\ABFC-[1-9][0-9]{2}\z/', $code ) ) {
				$error->add( 'burbank_course_code', __( 'Use a BFC code from BFC-100 through BFC-999 with no revision suffix.', 'burbank-lms' ), array( 'status' => 400 ) );
			} else {
				$subject = $fields['subject_category'] ?? '';
				if ( '' !== $subject && self::SUBJECTS[ (int) $code[4] ] !== $subject ) {
					$error->add( 'burbank_course_subject', __( 'Choose the subject category matching the BFC number range.', 'burbank-lms' ), array( 'status' => 400 ) );
				}
				if ( ! ( new Registry() )->available( $course_id, $code ) ) {
					$error->add( 'burbank_course_identity', __( 'This BFC code is already reserved, or this course has a different permanent published code.', 'burbank-lms' ), array( 'status' => 400 ) );
				}
			}
		}
		if ( isset( $fields['subject_category'] ) && '' !== $fields['subject_category'] && ! in_array( $fields['subject_category'], self::SUBJECTS, true ) ) {
			$error->add( 'burbank_course_subject', __( 'Choose a supported course subject category.', 'burbank-lms' ), array( 'status' => 400 ) );
		}
		foreach ( array( 'pressprimer_quiz_id', 'certificate_template_id' ) as $key ) {
			if ( isset( $fields[ $key ] ) && '' !== $fields[ $key ] && ( is_bool( $fields[ $key ] ) || ! is_scalar( $fields[ $key ] ) || ! preg_match( '/\A[1-9][0-9]*\z/', (string) $fields[ $key ] ) || false === filter_var( $fields[ $key ], FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) ) ) ) {
				$error->add( 'burbank_course_reference', __( 'Quiz and certificate template references must be positive integer IDs.', 'burbank-lms' ), array( 'status' => 400 ) );
			}
		}
		if ( isset( $fields['course_outcome'] ) && '' !== $fields['course_outcome'] && 'independent_study' !== $fields['course_outcome'] ) {
			$error->add( 'burbank_course_outcome', __( 'Only the independent_study knowledge-course outcome is supported.', 'burbank-lms' ), array( 'status' => 400 ) );
		}
		if ( isset( $fields['catalog_state'] ) && '' !== $fields['catalog_state'] && ! in_array( $fields['catalog_state'], array( 'draft', 'published', 'archived' ), true ) ) {
			$error->add( 'burbank_course_state', __( 'Choose draft, published or archived for catalog state.', 'burbank-lms' ), array( 'status' => 400 ) );
		}
		return $error;
	}

	/**
	 * Load current metadata for shared preflight consumers.
	 *
	 * @param int $course_id Course post ID.
	 * @return array<string,mixed> Existing fields.
	 */
	public function fields( int $course_id ): array {
		$fields = array();
		foreach ( Schema::FIELDS as $key => $type ) {
			if ( metadata_exists( 'post', $course_id, $key ) ) {
				$fields[ $key ] = get_post_meta( $course_id, $key, true );
			}
		}
		return $fields;
	}

	/**
	 * Scoped API for trusted approval services, never for request-provided callbacks.
	 *
	 * Validation and permanent identity remain enforced inside this scope.
	 *
	 * @param callable $write Trusted approval persistence operation.
	 * @return mixed Callback result.
	 */
	public static function internal_write( callable $write ) {
		++self::$internal_depth;
		try {
			return $write();
		} finally {
			--self::$internal_depth;
		}
	}

	/**
	 * Track core deletion from before its metadata loop, which runs outside any deletion action.
	 *
	 * @param int $post_id Post being permanently deleted.
	 */
	public function begin_delete( int $post_id ): void {
		if ( 'bfc_course' === get_post_type( $post_id ) ) {
			self::$deleting[ $post_id ] = true;
		}
	}

	/**
	 * Stop treating the post as dying once core removed it.
	 *
	 * @param int $post_id Deleted post ID.
	 */
	public function end_delete( int $post_id ): void {
		unset( self::$deleting[ $post_id ] );
	}

	/**
	 * Whether core is permanently deleting this course.
	 *
	 * @param int $post_id Course post ID.
	 */
	public static function deleting( int $post_id ): bool {
		return isset( self::$deleting[ $post_id ] );
	}

	/**
	 * Block invalid or unauthorized core metadata additions and updates.
	 *
	 * @param mixed  $check Existing short-circuit result.
	 * @param int    $post_id Target post ID.
	 * @param string $key Metadata key.
	 * @param mixed  $value Candidate value.
	 * @return mixed Existing result or false to reject.
	 */
	public function guard_meta( $check, int $post_id, string $key, $value ) {
		if ( self::$writing_code || 'bfc_course' !== get_post_type( $post_id ) || ! array_key_exists( $key, Schema::FIELDS ) ) {
			return $check;
		}
		if ( ( in_array( $key, self::AUDIT_FIELDS, true ) && ! self::$internal_depth ) || ( get_current_user_id() && ! current_user_can( 'edit_post', $post_id ) && ! self::$internal_depth ) ) {
			return false;
		}
		// Owned metadata is single-valued even through add_post_meta(..., false).
		$current = get_post_meta( $post_id, $key, false );
		if ( 1 < count( $current ) || ( 'add_post_metadata' === current_filter() && $current ) ) {
			return false;
		}
		$fields      = array_replace( $this->batches[ $post_id ] ?? $this->fields( $post_id ), array( $key => $value ) );
		$this->error = $this->check( $fields, $post_id );
		if ( in_array( $key, self::PUBLISHED_GATES, true ) && 'publish' === get_post_status( $post_id ) && ! self::$internal_depth ) {
			$this->error = ( new Publication() )->validate_candidate( $post_id, $fields );
		}
		if ( $this->error->has_errors() ) {
			return false;
		}
		if ( 'bfc_code' === $key ) {
			if ( ! is_string( $value ) || '' === $value ) {
				return false;
			}
			$result = false;
			$saved  = ( new Registry() )->reserve(
				$post_id,
				$value,
				static function () use ( $post_id, $value, &$result ): bool {
					global $wpdb;
					// Another request may have changed metadata while this request waited for the registry lock.
					wp_cache_delete( $post_id, 'post_meta' );
					self::$writing_code = true;
					try {
						$result = update_post_meta( $post_id, 'bfc_code', $value );
						// Verify storage directly; a stale or externally populated object cache is not evidence of persistence.
						$stored = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", $post_id, 'bfc_code' ) ); // phpcs:ignore WordPress.DB -- Authoritative verification inside the registry lock.
						return ! $wpdb->last_error && array( $value ) === $stored;
					} finally {
						self::$writing_code = false;
					}
				}
			);
			wp_cache_delete( $post_id, 'post_meta' );
			return $saved ? ( $result ?: true ) : false;
		}
		return $check;
	}

	/**
	 * Do not remove identity/audit metadata or delete protected fields in bulk.
	 *
	 * @param mixed  $check Existing result.
	 * @param int    $post_id Post ID, or zero for bulk deletion.
	 * @param string $key Metadata key.
	 * @return mixed Existing result or false to reject.
	 */
	public function guard_delete( $check, int $post_id, string $key ) {
		if ( ! array_key_exists( $key, Schema::FIELDS ) || ( $post_id && 'bfc_course' !== get_post_type( $post_id ) ) ) {
			return $check;
		}
		// Core post deletion may remove this course's meta but never the registry.
		if ( $post_id && self::deleting( $post_id ) ) {
			return $check;
		}
		if ( ! $post_id || 'bfc_code' === $key || ( in_array( $key, self::AUDIT_FIELDS, true ) && ! self::$internal_depth ) || ( get_current_user_id() && ! current_user_can( 'edit_post', $post_id ) && ! self::$internal_depth ) ) {
			return false;
		}
		if ( in_array( $key, self::PUBLISHED_GATES, true ) && 'publish' === get_post_status( $post_id ) && ! self::$internal_depth ) {
			$fields = $this->fields( $post_id );
			unset( $fields[ $key ] );
			$this->error = ( new Publication() )->validate( $post_id, $fields );
			if ( $this->error->has_errors() ) {
				return false;
			}
		}
		return $check;
	}

	/**
	 * Reject invalid REST batches before core applies any post or metadata updates.
	 *
	 * @param \stdClass|\WP_Error $prepared Prepared post.
	 * @param \WP_REST_Request    $request REST request.
	 * @return \stdClass|\WP_Error Valid post or actionable error.
	 */
	public function rest_preflight( $prepared, \WP_REST_Request $request ) {
		if ( is_wp_error( $prepared ) ) {
			return $prepared;
		}
		$id    = (int) $request->get_param( 'id' );
		$meta  = (array) $request->get_param( 'meta' );
		$audit = array_flip( self::AUDIT_FIELDS );
		// Audit metadata is read-only over REST, like core's readonly properties; clients may echo it back.
		if ( array_intersect_key( $meta, $audit ) ) {
			$meta = array_diff_key( $meta, $audit );
			// Core's meta handler applies the same parameter after this filter.
			$request->set_param( 'meta', $meta );
		}
		$meta   = array_intersect_key( $meta, Schema::FIELDS );
		$fields = array_replace( $this->fields( $id ), $meta );
		// A status-less update of a published course still leaves it published, so it needs the same gate.
		$status = isset( $prepared->post_status ) ? $prepared->post_status : ( $id ? get_post_status( $id ) : 'draft' );
		$error  = ( in_array( $status, self::PUBLICATION_STATUSES, true ) ? ( new Publication() )->validate( $id, $fields ) : $this->check( $fields, $id ) );
		if ( ! $error->has_errors() ) {
			$prepared->meta_input = $meta;
		}
		return $error->has_errors() ? $error : $prepared;
	}

	/**
	 * Pods 3.3.9.2 field validation runs for all active fields before any storage.
	 * Returning an error message aborts save_pod_item through Pods' own error mode.
	 *
	 * @param mixed $valid Existing validation result.
	 * @param mixed $value Candidate field value.
	 * @param mixed $field Field name.
	 * @param mixed $object_fields Native object fields.
	 * @param mixed $fields Pods field definitions and submitted values.
	 * @param mixed $pod Pod definition.
	 * @param mixed $params Save parameters.
	 * @return mixed Existing result or error messages.
	 */
	public function pods_preflight( $valid, $value, $field, $object_fields, $fields, $pod, $params ) {
		if ( 'bfc_course' !== $pod['name'] ) {
			return $valid;
		}
		$id = (int) $params->id;
		if ( ! current_user_can( $id ? 'edit_post' : 'edit_bfc_courses', $id ) ) {
			return array( __( 'You cannot edit this course.', 'burbank-lms' ) );
		}
		$data = array();
		foreach ( array_merge( (array) $fields, (array) $object_fields ) as $name => $definition ) {
			if ( isset( $definition['value'] ) ) {
				$data[ $name ] = $definition['value'];
			}
		}
		foreach ( self::AUDIT_FIELDS as $key ) {
			if ( array_key_exists( $key, $data ) && ! self::$internal_depth ) {
				return array( __( 'Approval audit fields are managed by the course approval workflow.', 'burbank-lms' ) );
			}
		}
		$candidate = array_replace( $this->fields( $id ), $data );
		$error     = $this->check( $candidate, $id );
		if ( ! $error->has_errors() && $id ) {
			$this->batches[ $id ]      = $candidate;
			$this->pods_batches[ $id ] = true;
		}
		return $error->has_errors() ? $error->get_error_messages() : $valid;
	}

	/**
	 * Reject invalid meta_input batches before core stores either post or identity.
	 *
	 * @param bool                $is_empty Existing empty-content decision.
	 * @param array<string,mixed> $postarr Incoming post fields.
	 */
	public function reject_invalid_batch( bool $is_empty, array $postarr ): bool {
		if ( 'bfc_course' !== ( $postarr['post_type'] ?? '' ) || empty( $postarr['meta_input'] ) ) {
			return $is_empty;
		}
		$id    = (int) ( $postarr['ID'] ?? 0 );
		$input = (array) $postarr['meta_input'];
		if ( ! self::$internal_depth && array_intersect( array_keys( $input ), self::AUDIT_FIELDS ) ) {
			return true;
		}
		$this->error = $this->check( array_replace( $this->fields( $id ), $input ), $id );
		return $is_empty || $this->error->has_errors();
	}

	/**
	 * Hold invalid classic/programmatic publication before core saves the post.
	 *
	 * @param array<string,mixed> $data Sanitized post data.
	 * @param array<string,mixed> $postarr Input post data.
	 * @return array<string,mixed> Guarded post data.
	 */
	public function guard_post( array $data, array $postarr ): array {
		if ( 'bfc_course' !== $data['post_type'] ) {
			return $data;
		}
		$id      = (int) ( $postarr['ID'] ?? 0 );
		$status  = $data['post_status'];
		$publish = in_array( $status, self::PUBLICATION_STATUSES, true );
		if ( ! $id && $publish ) {
			// Approval and publication require a previously saved, stable course ID.
			$data['post_status']     = 'draft';
			$this->error             = new \WP_Error( 'burbank_course_save_draft', __( 'Save this course as a draft before requesting approval and publication.', 'burbank-lms' ), array( 'status' => 400 ) );
			$this->publication_error = $this->error;
			return $data;
		}
		$fields      = array_replace( $this->batches[ $id ] ?? $this->fields( $id ), (array) ( $postarr['meta_input'] ?? array() ) );
		$this->error = ( $publish ? ( new Publication() )->validate( $id, $fields ) : $this->check( $fields, $id ) );
		if ( ! $this->error->has_errors() && $id ) {
			$this->batches[ $id ] = $fields;
		}
		if ( $this->error->has_errors() && $publish ) {
			$this->publication_error = $this->error;
			$data['post_status']     = 'draft';
		}
		// A scheduled course is sealed by its later publish transition, never at scheduling time.
		if ( ! $this->error->has_errors() && 'publish' === $status ) {
			$row = ( new Registry() )->get( $id );
			if ( ! $row || ! $row['published'] ) {
				// Core must never expose publication before the permanent reservation is sealed.
				$this->publishing[ $id ] = true;
				$data['post_status']     = 'draft';
			}
		}
		return $data;
	}

	/**
	 * Seal first publication after core has saved its metadata.
	 *
	 * @param int      $post_id Saved post ID.
	 * @param \WP_Post $post Saved post.
	 */
	public function seal_published( int $post_id, \WP_Post $post ): void {
		if ( empty( $this->pods_batches[ $post_id ] ) ) {
			unset( $this->batches[ $post_id ] );
		}
		if ( 'bfc_course' !== $post->post_type || empty( $this->publishing[ $post_id ] ) ) {
			return;
		}
		unset( $this->publishing[ $post_id ] );
		if ( $this->seal( $post_id, true ) ) {
			$post->post_status = 'publish';
			wp_transition_post_status( 'publish', 'draft', $post );
		}
	}

	/**
	 * Revalidate and permanently bind identity under the registry lock.
	 *
	 * @param int  $post_id Course post ID.
	 * @param bool $promote Move a saved draft to publish in the same transaction; false when core already stored publish.
	 * @return bool Whether publication is valid and sealed.
	 */
	private function seal( int $post_id, bool $promote ): bool {
		global $wpdb;
		$registry = new Registry();
		$code     = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", $post_id, 'bfc_code' ) ); // phpcs:ignore WordPress.DB -- Authoritative publication candidate.
		$saved    = $registry->reserve(
			$post_id,
			(string) $code,
			function () use ( $post_id, $code, $registry, $wpdb, $promote ): bool {
				wp_cache_delete( $post_id, 'post_meta' );
				$fields      = $this->fields( $post_id );
				$this->error = ( new Publication() )->validate( $post_id, $fields );
				if ( $this->error->has_errors() || ( $fields['bfc_code'] ?? '' ) !== $code || ! $registry->publish( $post_id ) ) {
					return false;
				}
				if ( ! $promote ) {
					return true;
				}
				// The post's first published status and immutable identity become visible in one commit.
				return 1 === $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_status = %s WHERE ID = %d AND post_status = %s", 'publish', $post_id, 'draft' ) ); // phpcs:ignore WordPress.DB -- Atomic first publication under the registry lock.
			}
		);
		clean_post_cache( $post_id );
		if ( ! $saved && ( ! $this->error || ! $this->error->has_errors() ) ) {
			$this->error = new \WP_Error( 'burbank_course_seal', __( 'The permanent course identity could not be reserved. Save the course again to retry publication.', 'burbank-lms' ), array( 'status' => 409 ) );
		}
		if ( ! $saved ) {
			$this->publication_error = $this->error;
		}
		return $saved;
	}

	/**
	 * Protect metadata-by-ID operations, including renaming another key into an owned key.
	 *
	 * @param mixed $check Existing short circuit.
	 * @param int   $meta_id Metadata row ID.
	 * @param mixed $value Candidate value.
	 * @param mixed $key New metadata key, or false to retain it.
	 * @return mixed Existing result or false.
	 */
	public function guard_meta_id( $check, int $meta_id, $value, $key ) {
		$meta = get_metadata_by_mid( 'post', $meta_id );
		if ( ! $meta || 'bfc_course' !== get_post_type( (int) $meta->post_id ) ) {
			return $check;
		}
		if ( array_key_exists( $meta->meta_key, Schema::FIELDS ) || ( $key && array_key_exists( $key, Schema::FIELDS ) ) ) {
			// Require the ordinary single-field API so identity and metadata share one transaction.
			return false;
		}
		return $check;
	}

	/**
	 * Prevent metadata-by-ID deletion bypassing permanent identity and audit guards.
	 *
	 * @param mixed $check Existing result.
	 * @param int   $meta_id Metadata row ID.
	 * @return mixed Existing result or false.
	 */
	public function guard_delete_id( $check, int $meta_id ) {
		$meta = get_metadata_by_mid( 'post', $meta_id );
		return $meta ? $this->guard_delete( $check, (int) $meta->post_id, $meta->meta_key ) : $check;
	}

	/** Clear validated Pods batch context after its own persistence has finished.
	 *
	 * @param mixed $pieces Pods save pieces.
	 * @param bool  $is_new Whether the item was created.
	 * @param int   $id Saved course ID.
	 * @return mixed Unchanged save pieces.
	 */
	public function pods_finished( $pieces, bool $is_new, int $id ) {
		unset( $this->batches[ $id ], $this->pods_batches[ $id ] );
		return $pieces;
	}

	/**
	 * Protect core's direct and scheduled publication path (wp_publish_post, cron, untrash).
	 *
	 * WordPress offers no pre-write filter on this path: it stores `publish` and then fires the
	 * transition. An invalid course is therefore reverted to draft immediately, but this boundary
	 * cannot hide the transient `publish` row from a concurrent reader, nor stop transition listeners
	 * registered after this one from receiving the rejected `publish` argument.
	 *
	 * @param string   $new_status Requested status.
	 * @param string   $old_status Previous status.
	 * @param \WP_Post $post Saved post.
	 */
	public function guard_transition( string $new_status, string $old_status, \WP_Post $post ): void {
		if ( 'bfc_course' !== $post->post_type || 'publish' !== $new_status || $new_status === $old_status ) {
			return;
		}
		// Core already stored publish before this hook; force the matrix soft-check for the transition.
		$this->error = ( new Publication() )->validate( $post->ID, $this->fields( $post->ID ), true );
		if ( ! $this->error->has_errors() ) {
			$row = ( new Registry() )->get( $post->ID );
			if ( ( $row && $row['published'] ) || $this->seal( $post->ID, false ) ) {
				return;
			}
		}
		$this->publication_error = $this->error;
		global $wpdb;
		$wpdb->update( $wpdb->posts, array( 'post_status' => 'draft' ), array( 'ID' => $post->ID ) ); // phpcs:ignore WordPress.DB -- Core wp_publish_post has no pre-write filter.
		$post->post_status = 'draft';
		clean_post_cache( $post->ID );
	}

	/**
	 * Carry classic-editor errors across WordPress' normal save redirect.
	 *
	 * Core reports "Post published" for any Publish click; replace it with "Draft saved" when held.
	 *
	 * @param string $location Core redirect URL.
	 * @param int    $post_id Saved post ID.
	 */
	public function redirect_notice( string $location, int $post_id = 0 ): string {
		$error = new \WP_Error();
		if ( $this->publication_error ) {
			$error->merge_from( $this->publication_error );
		}
		if ( $this->error && $this->error !== $this->publication_error ) {
			$error->merge_from( $this->error );
		}
		if ( $error->has_errors() && get_current_user_id() && ( ! $post_id || 'bfc_course' === get_post_type( $post_id ) ) ) {
			set_transient( 'burbank_course_notice_' . get_current_user_id(), array_unique( $error->get_error_messages() ), 60 );
			$this->publication_error = null;
			if ( $post_id && 'publish' !== get_post_status( $post_id ) ) {
				$location = add_query_arg( 'message', 10, $location );
			}
		}
		return $location;
	}

	/** Display actionable errors after recoverable classic-editor draft fallback. */
	public function notice(): void {
		$messages = get_transient( 'burbank_course_notice_' . get_current_user_id() );
		delete_transient( 'burbank_course_notice_' . get_current_user_id() );
		$messages = $messages ?: ( $this->error ? $this->error->get_error_messages() : array() );
		if ( $messages && current_user_can( 'edit_bfc_courses' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered by Courses\Capabilities.
			echo '<div class="notice notice-error"><p>' . esc_html( implode( ' ', $messages ) ) . '</p></div>';
		}
	}

	/** Return the most recent rejected core save for editor notices. */
	public function get_error(): ?\WP_Error {
		return $this->error;
	}
}
