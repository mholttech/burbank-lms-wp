<?php
/**
 * Invite code table installation.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Access;

/** Installs the invite codes InnoDB table without replacing existing rows. */
class Tables {
	public const VERSION_OPTION = 'burbank_lms_invite_tables_version';

	private const VERSION = 1;

	/** Install the InnoDB table without replacing existing invite codes. */
	public function install(): bool {
		global $wpdb;
		if ( self::VERSION === (int) get_option( self::VERSION_OPTION ) ) {
			return true;
		}
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table = $wpdb->prefix . 'bfc_invite_codes';
		dbDelta(
			"CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			scope varchar(20) NOT NULL,
			course_id bigint(20) unsigned DEFAULT NULL,
			code_hash varchar(255) NOT NULL,
			label varchar(191) NOT NULL DEFAULT '',
			kind varchar(20) NOT NULL,
			max_uses int(10) unsigned DEFAULT NULL,
			use_count int(10) unsigned NOT NULL DEFAULT 0,
			expires_at datetime DEFAULT NULL,
			revoked_at datetime DEFAULT NULL,
			bound_email varchar(191) DEFAULT NULL,
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY scope_course (scope, course_id),
			KEY code_hash (code_hash)
		) ENGINE=InnoDB {$wpdb->get_charset_collate()};"
		);
		$status = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS WHERE Name = %s', $table ), ARRAY_A ); // phpcs:ignore WordPress.DB -- Custom invite table verification.
		if ( ! $status || 'InnoDB' !== $status['Engine'] ) {
			return false;
		}
		update_option( self::VERSION_OPTION, self::VERSION, false );
		return true;
	}
}
