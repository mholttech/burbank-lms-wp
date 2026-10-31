<?php
/**
 * Course block-editor asset enqueue and Pods field UI teardown.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Courses;

/**
 * Loads the dedicated course-editor entry on bfc_course screens only and hides Pods field boxes.
 */
class Editor {
	/** Webpack entry name (must match webpack.config.js). */
	private const ENTRY = 'burbank-lms-course-editor';

	/**
	 * Script/style handle registered for the course-editor entry.
	 *
	 * Format mirrors Burbank_LMS::enqueue_entrypoint: `{plugin}/{entry}`.
	 */
	public const SCRIPT_HANDLE = 'burbank-lms/' . self::ENTRY;

	/**
	 * JS global produced by wp_localize_script for Task 4+ React panels.
	 *
	 * Read in the browser as `window.burbankLmsCourseEditor`.
	 */
	public const LOCALIZE_OBJECT = 'burbankLmsCourseEditor';

	/**
	 * Pods meta box ids removed from the bfc_course editor.
	 *
	 * Pods 3.3.x registers `add_meta_box( 'pods-meta-' . sanitize_title( $group['label'] ), … )`.
	 * Schema provisions fields into the default “More Fields” group → `pods-meta-more-fields`.
	 * `pods-meta-bfc_course` is removed as a defensive alias if a group label ever matches the pod name.
	 */
	public const PODS_META_BOX_IDS = array(
		'pods-meta-more-fields',
		'pods-meta-bfc_course',
	);

	/**
	 * Human labels for Validator::SUBJECTS slugs (select options).
	 *
	 * @return array<string,string>
	 */
	private static function subject_labels(): array {
		return array(
			'program_administration_membership'     => __( 'Program Administration & Membership', 'burbank-lms' ),
			'safety_field_awareness'                => __( 'Safety & Field Awareness', 'burbank-lms' ),
			'communications'                        => __( 'Communications', 'burbank-lms' ),
			'field_operations_documentation'        => __( 'Field Operations & Documentation', 'burbank-lms' ),
			'rehabilitation_logistics'              => __( 'Rehabilitation & Logistics', 'burbank-lms' ),
			'incident_management_coordination'      => __( 'Incident Management & Coordination', 'burbank-lms' ),
			'leadership_program_operations'         => __( 'Leadership & Program Operations', 'burbank-lms' ),
			'community_engagement_public_education' => __( 'Community Engagement & Public Education', 'burbank-lms' ),
			'reserved'                              => __( 'Reserved', 'burbank-lms' ),
		);
	}

	/**
	 * Enqueue course-editor assets when editing a bfc_course in the block editor.
	 *
	 * Hooked to `enqueue_block_editor_assets` (no hook arg). Optional `$hook` kept for
	 * compatibility if rewired to `admin_enqueue_scripts`.
	 *
	 * @param string $hook Admin page hook when used with admin_enqueue_scripts; unused otherwise.
	 */
	public function enqueue( string $hook = '' ): void {
		unset( $hook );
		if ( ! $this->is_course_block_editor() ) {
			return;
		}

		$this->enqueue_entrypoint( self::ENTRY, $this->localize_data() );
	}

	/**
	 * Remove Pods course field meta boxes without deleting field definitions.
	 *
	 * Hooked to `add_meta_boxes_bfc_course` at priority 99 (after Pods registers boxes).
	 */
	public function hide_pods_fields(): void {
		foreach ( self::PODS_META_BOX_IDS as $id ) {
			remove_meta_box( $id, 'bfc_course', 'normal' );
			remove_meta_box( $id, 'bfc_course', 'advanced' );
			remove_meta_box( $id, 'bfc_course', 'side' );
		}
	}

	/**
	 * Whether the current screen is the bfc_course block editor.
	 */
	private function is_course_block_editor(): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen instanceof \WP_Screen ) {
			return 'bfc_course' === $screen->post_type;
		}

		return 'bfc_course' === get_post_type();
	}

	/**
	 * Payload for window.burbankLmsCourseEditor.
	 *
	 * @return array<string,mixed>
	 */
	private function localize_data(): array {
		$labels   = self::subject_labels();
		$subjects = array();
		foreach ( Validator::SUBJECTS as $slug ) {
			$subjects[ $slug ] = $labels[ $slug ] ?? $slug;
		}

		$requirements = Picks::requirement_data();

		return array(
			'subjects'        => $subjects,
			'outcomes'        => array(
				'independent_study' => __( 'Independent study', 'burbank-lms' ),
			),
			'catalogStates'   => array(
				'draft'     => __( 'Draft', 'burbank-lms' ),
				'published' => __( 'Published', 'burbank-lms' ),
				'archived'  => __( 'Archived', 'burbank-lms' ),
			),
			'quizzes'         => Picks::quiz_data(),
			'templates'       => Picks::template_data(),
			'requirements'    => $requirements,
			'hasRequirements' => post_type_exists( 'bc_requirement' ),
			'caps'            => array(
				'approve' => current_user_can( 'approve_bfc_courses' ), // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered by Courses\Capabilities.
				'enroll'  => current_user_can( 'enroll_bfc_courses' ), // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered by Courses\Capabilities.
			),
			'restNamespace'   => 'burbank-lms/v1',
		);
	}

	/**
	 * Enqueue a webpack entrypoint for the course editor only.
	 *
	 * Adapted from Burbank_LMS::enqueue_entrypoint so React editor deps are not
	 * loaded globally via burbank-lms-admin, and so the localize object name can
	 * be `burbankLmsCourseEditor` instead of `burbank_lms`.
	 *
	 * @param string               $entry Entry name defined in webpack.config.js.
	 * @param array<string, mixed> $localize_data Data for LOCALIZE_OBJECT.
	 */
	private function enqueue_entrypoint( string $entry, array $localize_data ): void {
		global $wp_filesystem;
		if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php'; // @phpstan-ignore requireOnce.fileNotFound
			WP_Filesystem();
		}

		$filesystem = new \WP_Filesystem_Direct( false );
		$asset_file = BURBANK_LMS_PATH . "/build/{$entry}.asset.php";
		if ( ! $filesystem->exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;
		if ( ! isset( $asset['dependencies'], $asset['version'] ) ) {
			return;
		}

		$handle = 'burbank-lms/' . $entry;

		if ( $filesystem->exists( BURBANK_LMS_PATH . "build/{$entry}.js" ) ) {
			wp_enqueue_script(
				$handle,
				BURBANK_LMS_URL . "build/{$entry}.js",
				$asset['dependencies'],
				$asset['version'],
				true
			);
			wp_localize_script( $handle, self::LOCALIZE_OBJECT, $localize_data );
			wp_set_script_translations( $handle, 'burbank-lms', BURBANK_LMS_PATH . 'languages/' );
		}

		if ( $filesystem->exists( BURBANK_LMS_PATH . "build/{$entry}.css" ) ) {
			wp_enqueue_style(
				$handle,
				BURBANK_LMS_URL . "build/{$entry}.css",
				array(),
				$asset['version']
			);
		}
	}
}
