<?php
/**
 * Enrollment table installation.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Enrollment;

/** Installs the enrollments InnoDB table without replacing existing rows. */
class Tables {
	public const VERSION_OPTION = 'burbank_lms_enrollment_tables_version';

	private const VERSION = 1;

	/** Install the InnoDB table without replacing existing enrollments. */
	public function install(): bool {
		global $wpdb;
		if ( self::VERSION === (int) get_option( self::VERSION_OPTION ) ) {
			return true;
		}
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table = $wpdb->prefix . 'bfc_enrollments';
		dbDelta(
			"CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			course_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			status varchar(20) NOT NULL,
			source varchar(20) NOT NULL,
			invite_code_id bigint(20) unsigned DEFAULT NULL,
			enrolled_at datetime NOT NULL,
			completed_at datetime DEFAULT NULL,
			quiz_passed_at datetime DEFAULT NULL,
			credential_id bigint(20) unsigned DEFAULT NULL,
			course_revision_snapshot varchar(191) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			UNIQUE KEY course_user (course_id, user_id)
		) ENGINE=InnoDB {$wpdb->get_charset_collate()};"
		);
		$status = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS WHERE Name = %s', $table ), ARRAY_A ); // phpcs:ignore WordPress.DB -- Custom enrollment table verification.
		if ( ! $status || 'InnoDB' !== $status['Engine'] ) {
			return false;
		}
		update_option( self::VERSION_OPTION, self::VERSION, false );
		return true;
	}
}
