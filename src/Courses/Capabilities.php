<?php
/**
 * Dedicated course editorial capabilities.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Courses;

/** Installs capabilities without replacing site roles. */
class Capabilities {
	public const VERSION_OPTION = 'burbank_lms_course_capabilities_version';
	private const VERSION       = 3;

	/** Grant capabilities to the existing editorial roles once per capability version, so later role edits persist. */
	public function install(): void {
		if ( self::VERSION <= (int) get_option( self::VERSION_OPTION ) ) {
			return;
		}
		$caps = array(
			'edit_bfc_courses',
			'edit_others_bfc_courses',
			'edit_private_bfc_courses',
			'edit_published_bfc_courses',
			'publish_bfc_courses',
			'read_private_bfc_courses',
			'delete_bfc_courses',
			'delete_others_bfc_courses',
			'delete_private_bfc_courses',
			'delete_published_bfc_courses',
		);
		foreach ( array( 'administrator', 'editor' ) as $name ) {
			$role = get_role( $name );
			if ( ! $role ) {
				continue;
			}
			foreach ( $caps as $cap ) {
				if ( ! $role->has_cap( $cap ) ) {
					$role->add_cap( $cap );
				}
			}
			if ( 'administrator' === $name ) {
				if ( ! $role->has_cap( 'approve_bfc_courses' ) ) {
					$role->add_cap( 'approve_bfc_courses' );
				}
				if ( ! $role->has_cap( 'enroll_bfc_courses' ) ) {
					$role->add_cap( 'enroll_bfc_courses' );
				}
				// Administrators may enroll and take courses without a separate training-access grant.
				if ( ! $role->has_cap( 'burbank_lms_access' ) ) {
					$role->add_cap( 'burbank_lms_access' );
				}
			}
		}
		update_option( self::VERSION_OPTION, self::VERSION, false );
	}
}
