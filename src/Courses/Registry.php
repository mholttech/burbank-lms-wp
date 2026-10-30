<?php
/**
 * Durable BFC number reservations.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Courses;

/** Database constraints arbitrate competing reservations; published rows are tombstones. */
class Registry {
	public const VERSION_OPTION = 'burbank_lms_course_registry_version';

	/** Last database or identity error.
	 *
	 * @var \WP_Error|null
	 */
	private ?\WP_Error $error = null;

	/** Install the InnoDB table without replacing existing reservations. */
	public function install(): bool {
		global $wpdb;
		if ( 1 === (int) get_option( self::VERSION_OPTION ) ) {
			return true;
		}
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table = $wpdb->prefix . 'bfc_course_registry';
		dbDelta(
			"CREATE TABLE {$table} (
			course_id bigint(20) unsigned NOT NULL,
			code varchar(7) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
			published tinyint(1) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (course_id),
			UNIQUE KEY code (code)
		) ENGINE=InnoDB {$wpdb->get_charset_collate()};"
		);
		$status = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS WHERE Name = %s', $table ), ARRAY_A ); // phpcs:ignore WordPress.DB -- Custom transactional registry.
		if ( ! $status || 'InnoDB' !== $status['Engine'] ) {
			return $this->fail();
		}
		// Backfill existing course identities before declaring the migration complete.
		// phpcs:disable WordPress.DB -- Core post/meta read for an idempotent identity migration.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT p.ID, p.post_status, m.meta_value FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id WHERE p.post_type = %s AND m.meta_key = %s AND m.meta_value <> '' ORDER BY p.ID", 'bfc_course', 'bfc_code' ) );
		if ( $wpdb->last_error ) {
			return $this->fail();
		}
		foreach ( $rows as $row ) {
			if ( ! $this->reserve( (int) $row->ID, $row->meta_value ) || ( 'publish' === $row->post_status && ! $this->publish( (int) $row->ID ) ) ) {
				return $this->fail();
			}
		}
		// phpcs:enable WordPress.DB
		update_option( self::VERSION_OPTION, 1, false );
		return true;
	}

	/**
	 * Atomically acquire a number or move an unpublished course's reservation.
	 *
	 * @param int           $course_id Course post ID.
	 * @param string        $code Permanent BFC identifier.
	 * @param callable|null $persist Optional trusted metadata write, performed under the same lock.
	 */
	public function reserve( int $course_id, string $code, ?callable $persist = null ): bool {
		global $wpdb;
		$this->error = null;
		if ( $course_id < 1 || ! preg_match( '/\ABFC-[1-9][0-9]{2}\z/', $code ) ) {
			return $this->fail();
		}
		$table = $wpdb->prefix . 'bfc_course_registry';
		// Unique constraints cover both concurrent first inserts and competing code moves.
		// phpcs:disable WordPress.DB -- Prepared queries against the plugin-owned registry; no object cache.
		if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
			return $this->fail();
		}
		$previous  = $wpdb->suppress_errors();
		$committed = false;
		try {
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT code, published FROM {$table} WHERE course_id = %d FOR UPDATE", $course_id ) );
			// wp_publish_post writes core status before firing transition hooks.
			$status = $wpdb->get_var( $wpdb->prepare( "SELECT post_status FROM {$wpdb->posts} WHERE ID = %d", $course_id ) );
			if ( $wpdb->last_error || ( $row && ( $row->published || 'publish' === $status ) && $row->code !== $code ) ) {
				return $this->fail();
			}
			$result = $row
				? $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET code = %s WHERE course_id = %d AND published = 0", $code, $course_id ) )
				: $wpdb->query( $wpdb->prepare( "INSERT INTO {$table} (course_id, code) VALUES (%d, %s)", $course_id, $code ) );
			if ( false === $result || ( $persist && ! $persist() ) || false === $wpdb->query( 'COMMIT' ) ) {
				return $this->fail();
			}
			$committed = true;
		} finally {
			if ( ! $committed ) {
				$wpdb->query( 'ROLLBACK' );
			}
			$wpdb->suppress_errors( $previous );
		}
		// phpcs:enable WordPress.DB
		return true;
	}

	/**
	 * Permanently seal an existing reservation, including after later post deletion.
	 *
	 * @param int $course_id Course post ID.
	 */
	public function publish( int $course_id ): bool {
		global $wpdb;
		$this->error = null;
		$table       = $wpdb->prefix . 'bfc_course_registry';
		// Conditional assignment is atomic and repeatable; there is no unpublish operation.
		$result = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET published = 1 WHERE course_id = %d", $course_id ) ); // phpcs:ignore WordPress.DB -- Prepared custom-table write.
		return false !== $result && null !== $this->get( $course_id ) ? true : $this->fail();
	}

	/**
	 * Read the authoritative identity, never deriving it from mutable postmeta.
	 *
	 * @param int $course_id Course post ID.
	 * @return array{code:string,published:string}|null Reservation.
	 */
	public function get( int $course_id ): ?array {
		global $wpdb;
		$table = $wpdb->prefix . 'bfc_course_registry';
		return $wpdb->get_row( $wpdb->prepare( "SELECT code, published FROM {$table} WHERE course_id = %d", $course_id ), ARRAY_A ); // phpcs:ignore WordPress.DB -- Prepared custom-table read.
	}

	/**
	 * Check availability for actionable preflight errors; reserve remains authoritative.
	 *
	 * @param int    $course_id Course post ID, or zero for a new course.
	 * @param string $code Requested number.
	 */
	public function available( int $course_id, string $code ): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'bfc_course_registry';
		$owner = $wpdb->get_var( $wpdb->prepare( "SELECT course_id FROM {$table} WHERE code = %s", $code ) ); // phpcs:ignore WordPress.DB -- Prepared custom-table read.
		$row   = $course_id ? $this->get( $course_id ) : null;
		return ! $wpdb->last_error && ( null === $owner || (int) $owner === $course_id ) && ( ! $row || ! $row['published'] || $row['code'] === $code );
	}

	/** Show migration failures without disclosing database internals. */
	public function notice(): void {
		if ( $this->error && current_user_can( 'manage_options' ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $this->error->get_error_message() ) . '</p></div>';
		}
	}

	/** Return an actionable failure without disclosing database details. */
	public function get_error(): ?\WP_Error {
		return $this->error;
	}

	/** Record a closed failure. */
	private function fail(): bool {
		$this->error = new \WP_Error( 'burbank_course_identity', __( 'The BFC code is unavailable or its permanent reservation could not be saved. Published numbers cannot be changed or reused. Retry or contact an administrator.', 'burbank-lms' ), array( 'status' => 400 ) );
		return false;
	}
}
