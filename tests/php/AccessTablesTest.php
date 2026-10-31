<?php
/**
 * Invite and enrollment table installation coverage.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Tables as AccessTables;
use Burbank_LMS\Enrollment\Tables as EnrollmentTables;
use Burbank_LMS\Courses\Capabilities;

/** Exercises versioned custom-table installs and enrollment capability. */
class AccessTablesTest extends WP_UnitTestCase {
	/** Drop plugin tables and version flags so each case starts clean. */
	public function set_up(): void {
		parent::set_up();
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}bfc_invite_codes" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}bfc_enrollments" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		delete_option( AccessTables::VERSION_OPTION );
		delete_option( EnrollmentTables::VERSION_OPTION );
	}

	/** Repeated install calls create tables once and leave version options set. */
	public function test_invite_and_enrollment_tables_install_once(): void {
		$invites     = new AccessTables();
		$enrollments = new EnrollmentTables();
		$this->assertTrue( $invites->install() );
		$this->assertTrue( $enrollments->install() );
		$this->assertTrue( $invites->install() );
		$this->assertTrue( $enrollments->install() );
		global $wpdb;
		$this->assertNotEmpty( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'bfc_invite_codes' ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table existence check.
		$this->assertNotEmpty( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'bfc_enrollments' ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table existence check.
		$this->assertSame( 1, (int) get_option( AccessTables::VERSION_OPTION ) );
		$this->assertSame( 1, (int) get_option( EnrollmentTables::VERSION_OPTION ) );
	}

	/** Custom tables use InnoDB for transactional integrity. */
	public function test_tables_use_innodb_engine(): void {
		( new AccessTables() )->install();
		( new EnrollmentTables() )->install();
		global $wpdb;
		foreach ( array( 'bfc_invite_codes', 'bfc_enrollments' ) as $suffix ) {
			$table  = $wpdb->prefix . $suffix;
			$status = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS WHERE Name = %s', $table ), ARRAY_A ); // phpcs:ignore WordPress.DB -- Custom table verification.
			$this->assertIsArray( $status );
			$this->assertSame( 'InnoDB', $status['Engine'] ?? null, $table );
		}
	}

	/** One enrollment row per course and user pair. */
	public function test_enrollments_unique_course_user_index(): void {
		( new EnrollmentTables() )->install();
		global $wpdb;
		$table   = $wpdb->prefix . 'bfc_enrollments';
		$indexes = $wpdb->get_results( $wpdb->prepare( 'SHOW INDEX FROM %i WHERE Key_name = %s', $table, 'course_user' ), ARRAY_A ); // phpcs:ignore WordPress.DB -- Custom table verification.
		$this->assertCount( 2, $indexes );
		$columns = wp_list_pluck( $indexes, 'Column_name' );
		sort( $columns );
		$this->assertSame( array( 'course_id', 'user_id' ), $columns );
		$this->assertSame( '0', $indexes[0]['Non_unique'] );
	}

	/** Enroll and training-access caps are administrator-only at capability version 3. */
	public function test_enroll_capability_granted_to_administrator(): void {
		update_option( Capabilities::VERSION_OPTION, 1, false );
		( new Capabilities() )->install();
		$this->assertTrue( get_role( 'administrator' )->has_cap( 'enroll_bfc_courses' ) );
		$this->assertTrue( get_role( 'administrator' )->has_cap( 'burbank_lms_access' ) );
		$this->assertFalse( (bool) get_role( 'editor' )->has_cap( 'enroll_bfc_courses' ) );
		$this->assertFalse( (bool) get_role( 'editor' )->has_cap( 'burbank_lms_access' ) );
		$this->assertSame( 3, (int) get_option( Capabilities::VERSION_OPTION ) );
	}
}
