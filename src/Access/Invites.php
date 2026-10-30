<?php
/**
 * Site and course invite code service.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Access;

/** Hashed invite CRUD, lookup, and transactional redemption for site and course scopes. */
class Invites {
	private const SCOPES = array( 'site', 'course' );

	private const KINDS = array( 'multi', 'once' );

	/**
	 * Optional database connection for isolated integration tests.
	 *
	 * @param \wpdb|null $wpdb Database handle override.
	 */
	public function __construct( private ?\wpdb $wpdb = null ) {}

	/**
	 * Issue a new invite code; plaintext is returned once for admin display.
	 *
	 * @param string               $scope `site` or `course`.
	 * @param array<string, mixed> $args Creation options.
	 * @return array{id:int,plaintext:string}|\WP_Error
	 */
	public function create( string $scope, array $args ): array|\WP_Error {
		if ( ! in_array( $scope, self::SCOPES, true ) ) {
			return new \WP_Error( 'burbank_invite_bad_scope', __( 'Invite scope must be site or course.', 'burbank-lms' ) );
		}
		$kind = isset( $args['kind'] ) ? (string) $args['kind'] : 'multi';
		if ( ! in_array( $kind, self::KINDS, true ) ) {
			return new \WP_Error( 'burbank_invite_bad_kind', __( 'Invite kind must be multi or once.', 'burbank-lms' ) );
		}
		$course_id = isset( $args['course_id'] ) ? (int) $args['course_id'] : 0;
		if ( 'course' === $scope ) {
			if ( $course_id < 1 ) {
				return new \WP_Error( 'burbank_invite_missing_course_id', __( 'Course invites require a course ID.', 'burbank-lms' ) );
			}
		} else {
			$course_id = 0;
		}
		$label    = isset( $args['label'] ) ? sanitize_text_field( (string) $args['label'] ) : '';
		$max_uses = 'multi' === $kind && isset( $args['max_uses'] ) ? (int) $args['max_uses'] : null;
		if ( 'once' === $kind ) {
			$max_uses = null;
		} elseif ( null !== $max_uses && $max_uses < 1 ) {
			return new \WP_Error( 'burbank_invite_bad_max_uses', __( 'Multi-use invites require max uses to be at least one.', 'burbank-lms' ) );
		}
		$expires_at = isset( $args['expires_at'] ) ? $this->normalize_datetime( (string) $args['expires_at'] ) : null;
		if ( isset( $args['expires_at'] ) && null === $expires_at ) {
			return new \WP_Error( 'burbank_invite_bad_expires_at', __( 'Invite expiry must be a valid datetime.', 'burbank-lms' ) );
		}
		$bound_email = isset( $args['bound_email'] ) ? sanitize_email( (string) $args['bound_email'] ) : null;
		if ( isset( $args['bound_email'] ) && '' !== $args['bound_email'] && ! is_email( $bound_email ) ) {
			return new \WP_Error( 'burbank_invite_bad_bound_email', __( 'Bound email must be a valid address.', 'burbank-lms' ) );
		}
		if ( '' === $bound_email ) {
			$bound_email = null;
		}

		$plaintext = wp_generate_password( 32, false, false );
		$hash      = $this->hash_code( $plaintext );
		$db        = $this->db();
		$table     = $db->prefix . 'bfc_invite_codes';
		$now       = gmdate( 'Y-m-d H:i:s' );
		$inserted  = $db->insert( // phpcs:ignore WordPress.DB -- Prepared custom-table write.
			$table,
			array(
				'scope'       => $scope,
				'course_id'   => $course_id > 0 ? $course_id : null,
				'code_hash'   => $hash,
				'label'       => $label,
				'kind'        => $kind,
				'max_uses'    => $max_uses,
				'use_count'   => 0,
				'expires_at'  => $expires_at,
				'revoked_at'  => null,
				'bound_email' => $bound_email,
				'created_by'  => (int) get_current_user_id(),
				'created_at'  => $now,
			),
			array( '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%d', '%s' )
		);
		if ( false === $inserted ) {
			return new \WP_Error( 'burbank_invite_create_failed', __( 'The invite code could not be saved.', 'burbank-lms' ) );
		}
		return array(
			'id'        => (int) $db->insert_id,
			'plaintext' => $plaintext,
		);
	}

	/**
	 * Revoke an invite so it can no longer be redeemed.
	 *
	 * @param int $id Invite row ID.
	 */
	public function revoke( int $id ): bool|\WP_Error {
		if ( $id < 1 ) {
			return new \WP_Error( 'burbank_invite_not_found', __( 'Invite not found.', 'burbank-lms' ) );
		}
		$db     = $this->db();
		$table  = $db->prefix . 'bfc_invite_codes';
		$exists = $db->get_var( $db->prepare( "SELECT id FROM {$table} WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB -- Prepared custom-table read.
		if ( ! $exists ) {
			return new \WP_Error( 'burbank_invite_not_found', __( 'Invite not found.', 'burbank-lms' ) );
		}
		$updated = $db->update( // phpcs:ignore WordPress.DB -- Prepared custom-table write.
			$table,
			array(
				'revoked_at' => gmdate( 'Y-m-d H:i:s' ),
			),
			array(
				'id'         => $id,
				'revoked_at' => null,
			),
			array( '%s' ),
			array( '%d', '%s' )
		);
		if ( false === $updated ) {
			return new \WP_Error( 'burbank_invite_revoke_failed', __( 'The invite could not be revoked.', 'burbank-lms' ) );
		}
		return true;
	}

	/**
	 * Resolve an active invite row for the supplied plaintext and scope.
	 *
	 * @param string   $plaintext Raw invite code.
	 * @param string   $scope     Expected scope.
	 * @param int|null $course_id Required when scope is course.
	 * @return object|null
	 */
	public function find_active( string $plaintext, string $scope, ?int $course_id = null ): ?object {
		if ( '' === $plaintext || ! in_array( $scope, self::SCOPES, true ) ) {
			return null;
		}
		if ( 'course' === $scope && ( null === $course_id || $course_id < 1 ) ) {
			return null;
		}
		$db    = $this->db();
		$table = $db->prefix . 'bfc_invite_codes';
		$hash  = $this->hash_code( $plaintext );
		$sql   = "SELECT * FROM {$table} WHERE code_hash = %s AND scope = %s AND revoked_at IS NULL AND (expires_at IS NULL OR expires_at > UTC_TIMESTAMP()) AND ((kind = 'once' AND use_count = 0) OR (kind = 'multi' AND (max_uses IS NULL OR use_count < max_uses)))";
		if ( 'course' === $scope ) {
			// phpcs:ignore WordPress.DB -- Prepared indexed custom-table read.
			$row = $db->get_row( $db->prepare( $sql . ' AND course_id = %d LIMIT 1', $hash, $scope, $course_id ) );
		} else {
			// phpcs:ignore WordPress.DB -- Prepared indexed custom-table read.
			$row = $db->get_row( $db->prepare( $sql . ' AND course_id IS NULL LIMIT 1', $hash, $scope ) );
		}
		return $row instanceof \stdClass ? $row : null;
	}

	/**
	 * Atomically increment use_count when the invite is still active.
	 *
	 * When `$manage_transaction` is false the caller must already hold an open
	 * transaction; this method only performs FOR UPDATE + conditional UPDATE.
	 *
	 * @param int  $id                  Invite row ID.
	 * @param bool $manage_transaction Whether to START/COMMIT/ROLLBACK here.
	 */
	public function redeem( int $id, bool $manage_transaction = true ): bool {
		if ( $id < 1 ) {
			return false;
		}
		$db    = $this->db();
		$table = $db->prefix . 'bfc_invite_codes';
		// phpcs:disable WordPress.DB -- Transactional custom-table redemption.
		if ( $manage_transaction && false === $db->query( 'START TRANSACTION' ) ) {
			return false;
		}
		$previous  = $db->suppress_errors();
		$succeeded = false;
		try {
			$row = $db->get_row( $db->prepare( "SELECT * FROM {$table} WHERE id = %d FOR UPDATE", $id ) );
			if ( ! $row || ! $this->row_is_active( $row ) ) {
				return false;
			}
			if ( 'once' === $row->kind ) {
				$result = $db->query(
					$db->prepare(
						"UPDATE {$table} SET use_count = use_count + 1 WHERE id = %d AND kind = 'once' AND use_count = 0 AND revoked_at IS NULL AND (expires_at IS NULL OR expires_at > UTC_TIMESTAMP())",
						$id
					)
				);
			} else {
				$result = $db->query(
					$db->prepare(
						"UPDATE {$table} SET use_count = use_count + 1 WHERE id = %d AND kind = 'multi' AND revoked_at IS NULL AND (expires_at IS NULL OR expires_at > UTC_TIMESTAMP()) AND (max_uses IS NULL OR use_count < max_uses)",
						$id
					)
				);
			}
			if ( false === $result || 1 !== (int) $db->rows_affected ) {
				return false;
			}
			if ( $manage_transaction && false === $db->query( 'COMMIT' ) ) {
				return false;
			}
			$succeeded = true;
		} finally {
			if ( $manage_transaction && ! $succeeded ) {
				$db->query( 'ROLLBACK' );
			}
			$db->suppress_errors( $previous );
		}
		// phpcs:enable WordPress.DB
		return true;
	}

	/**
	 * List invite rows for a scope, newest first.
	 *
	 * @param string   $scope     `site` or `course`.
	 * @param int|null $course_id Required when scope is course.
	 * @return list<object>
	 */
	public function list( string $scope, ?int $course_id = null ): array {
		if ( ! in_array( $scope, self::SCOPES, true ) ) {
			return array();
		}
		if ( 'course' === $scope && ( null === $course_id || $course_id < 1 ) ) {
			return array();
		}
		$db    = $this->db();
		$table = $db->prefix . 'bfc_invite_codes';
		if ( 'course' === $scope ) {
			// phpcs:ignore WordPress.DB -- Prepared custom-table read.
			$rows = $db->get_results(
				$db->prepare(
					"SELECT id, scope, course_id, label, kind, max_uses, use_count, expires_at, revoked_at, bound_email, created_by, created_at FROM {$table} WHERE scope = %s AND course_id = %d ORDER BY id DESC",
					$scope,
					$course_id
				)
			);
		} else {
			// phpcs:ignore WordPress.DB -- Prepared custom-table read.
			$rows = $db->get_results(
				$db->prepare(
					"SELECT id, scope, course_id, label, kind, max_uses, use_count, expires_at, revoked_at, bound_email, created_by, created_at FROM {$table} WHERE scope = %s AND course_id IS NULL ORDER BY id DESC",
					$scope
				)
			);
		}
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Whether a course currently has at least one active course-scoped invite.
	 *
	 * @param int $course_id Course post ID.
	 */
	public function course_has_active_invites( int $course_id ): bool {
		if ( $course_id < 1 ) {
			return false;
		}
		$db    = $this->db();
		$table = $db->prefix . 'bfc_invite_codes';
		// phpcs:ignore WordPress.DB -- Prepared custom-table read.
		$rows = $db->get_results( $db->prepare( "SELECT kind, max_uses, use_count, expires_at, revoked_at FROM {$table} WHERE scope = 'course' AND course_id = %d", $course_id ) );
		if ( ! is_array( $rows ) ) {
			return false;
		}
		foreach ( $rows as $row ) {
			if ( $this->row_is_active( $row ) ) {
				return true;
			}
		}
		return false;
	}

	/** Return the database handle used for invite persistence. */
	private function db(): \wpdb {
		return $this->wpdb ?? $GLOBALS['wpdb'];
	}

	/**
	 * Whether a stored invite row is still redeemable.
	 *
	 * @param object $row Invite row object.
	 */
	private function row_is_active( object $row ): bool {
		if ( ! empty( $row->revoked_at ) ) {
			return false;
		}
		if ( ! empty( $row->expires_at ) && strtotime( (string) $row->expires_at . ' UTC' ) <= time() ) {
			return false;
		}
		$use_count = (int) $row->use_count;
		if ( 'once' === $row->kind ) {
			return 0 === $use_count;
		}
		if ( 'multi' !== $row->kind ) {
			return false;
		}
		if ( null === $row->max_uses || '' === $row->max_uses ) {
			return true;
		}
		return $use_count < (int) $row->max_uses;
	}

	/**
	 * Derive the indexed lookup digest for a plaintext invite code.
	 *
	 * @param string $plaintext Raw invite code.
	 */
	private function hash_code( string $plaintext ): string {
		return hash_hmac( 'sha256', $plaintext, wp_salt( 'auth' ) );
	}

	/**
	 * Normalize a MySQL datetime string in UTC.
	 *
	 * @param string $value Raw datetime input.
	 */
	private function normalize_datetime( string $value ): ?string {
		$value = trim( $value );
		if ( '' === $value ) {
			return null;
		}
		$timestamp = strtotime( $value . ' UTC' );
		if ( false === $timestamp ) {
			return null;
		}
		return gmdate( 'Y-m-d H:i:s', $timestamp );
	}
}
