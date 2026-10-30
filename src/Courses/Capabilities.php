<?php
/**
 * Dedicated course editorial capabilities.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Courses;

/** Installs capabilities without replacing site roles. */
class Capabilities {
	/** Idempotently grant capabilities to the existing editorial roles. */
	public function install(): void {
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
			if ( 'administrator' === $name && ! $role->has_cap( 'approve_bfc_courses' ) ) {
				$role->add_cap( 'approve_bfc_courses' );
			}
		}
	}
}
