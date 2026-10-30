<?php
/**
 * Versioned, plugin-owned Pods course schema.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Courses;

/** Provisions the course definition through the required Pods API. */
class Schema {
	public const VERSION_OPTION = 'burbank_lms_course_schema_version';
	private const VERSION       = 1;

	/** Course fields and their Pods storage types. */
	public const FIELDS = array(
		'bfc_code'                => 'text',
		'course_revision'         => 'text',
		'subject_category'        => 'text',
		'pressprimer_quiz_id'     => 'number',
		'certificate_template_id' => 'number',
		'matrix_requirement_slug' => 'text',
		'course_outcome'          => 'text',
		'catalog_state'           => 'text',
		'approved_by'             => 'number',
		'approved_at'             => 'text',
		'source_revision'         => 'text',
		'approval_notes'          => 'paragraph',
	);

	/** Dependency probe, injectable for failure coverage.
	 *
	 * @var \Closure(): bool
	 */
	private \Closure $available;

	/** Last provisioning failure.
	 *
	 * @var \WP_Error|null
	 */
	private ?\WP_Error $error = null;

	/**
	 * Set the dependency probe without registering hooks.
	 *
	 * @param \Closure|null $available Optional dependency probe.
	 */
	public function __construct( ?\Closure $available = null ) {
		$this->available = $available ?? static fn (): bool => function_exists( 'pods_api' ) && function_exists( 'pods_init' );
	}

	/** Provision owned schema options and fields, retaining all course data. */
	public function register(): void {
		$this->error = null;
		if ( ! ( $this->available )() || ! function_exists( 'pods_api' ) || ! function_exists( 'pods_init' ) ) {
			$this->error = new \WP_Error( 'burbank_pods_required', __( 'Burbank LMS requires Pods. Install and activate Pods to initialize courses. Existing course data is retained.', 'burbank-lms' ) );
			return;
		}

		$api                 = clone pods_api();
		$api->display_errors = 'wp_error';
		$pod                 = $api->load_pod( array( 'name' => 'bfc_course' ) );
		$type                = get_post_type_object( 'bfc_course' );
		if ( ( $pod && ! get_option( 'burbank_lms_course_schema_owned' ) ) || ( $type && 'edit_bfc_courses' !== $type->cap->edit_posts ) || ( $pod && ( 'post_type' !== $pod['type'] || 'meta' !== $pod['storage'] ) ) ) {
			$this->error = new \WP_Error( 'burbank_course_type_conflict', __( 'Burbank LMS found an incompatible bfc_course definition. Resolve the conflicting type before initializing courses.', 'burbank-lms' ) );
			return;
		}

		if ( self::VERSION > (int) get_option( self::VERSION_OPTION ) || ! $pod ) {
			$options = array(
				'name'                   => 'bfc_course',
				'label'                  => __( 'Courses', 'burbank-lms' ),
				'label_singular'         => __( 'Course', 'burbank-lms' ),
				'type'                   => 'post_type',
				'storage'                => 'meta',
				'public'                 => true,
				'show_ui'                => true,
				'rest_enable'            => true,
				'capability_type'        => 'custom',
				'capability_type_custom' => 'bfc_course',
				'capability_type_extra'  => true,
				'supports_title'         => true,
				'supports_editor'        => true,
				'supports_excerpt'       => true,
				'supports_thumbnail'     => true,
				'supports_revisions'     => true,
				'supports_custom_fields' => true,
			);
			if ( $pod ) {
				$options['id'] = $pod['id'];
			}
			$result = $api->save_pod( $options );
			if ( ! $this->saved( $result ) ) {
				return;
			}
			update_option( 'burbank_lms_course_schema_owned', true, false );
			foreach ( self::FIELDS as $name => $field_type ) {
				// Save individually: save_pod with a partial fields list removes omitted fields.
				$field  = $api->load_field(
					array(
						'name' => $name,
						'pod'  => 'bfc_course',
					)
				);
				$result = $api->save_field(
					array(
						'id'    => $field ? $field['id'] : 0,
						'pod'   => 'bfc_course',
						'name'  => $name,
						'label' => ucwords( str_replace( '_', ' ', $name ) ),
						'type'  => $field_type,
					)
				);
				if ( ! $this->saved( $result ) ) {
					return;
				}
			}
			update_option( self::VERSION_OPTION, self::VERSION, false );
			pods_init()->setup_content_types( true );
		}

		foreach ( self::FIELDS as $name => $field_type ) {
			register_post_meta(
				'bfc_course',
				$name,
				array(
					'type'          => 'number' === $field_type ? 'integer' : 'string',
					'single'        => true,
					'show_in_rest'  => true,
					'auth_callback' => static function ( bool $allowed, string $key, int $post_id ): bool {
						if ( in_array( $key, array( 'approved_by', 'approved_at', 'approval_notes' ), true ) ) {
							return false;
						}
						return current_user_can( 'edit_post', $post_id );
					},
				)
			);
		}
	}

	/**
	 * Check a Pods persistence result before advancing the schema version.
	 *
	 * @param mixed $result Pods API result.
	 * @return bool Whether persistence succeeded.
	 */
	private function saved( $result ): bool {
		if ( is_wp_error( $result ) || ! $result ) {
			$this->error = new \WP_Error( 'burbank_course_schema_failed', __( 'Burbank LMS could not configure its Pods course schema. Review Pods configuration and retry.', 'burbank-lms' ) );
			return false;
		}
		return true;
	}

	/** Return the latest actionable initialization error. */
	public function get_error(): ?\WP_Error {
		return $this->error;
	}

	/** Show dependency or conflict guidance to site administrators. */
	public function notice(): void {
		if ( $this->error && current_user_can( 'manage_options' ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $this->error->get_error_message() ) . '</p></div>';
		}
	}
}
