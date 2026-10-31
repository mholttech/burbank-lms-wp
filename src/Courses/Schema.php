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
	/** Set when data schema provisioning changes rewrite-affecting CPT options; cleared after one flush. */
	public const REWRITE_FLUSH_OPTION = 'burbank_lms_course_schema_rewrite_flush';
	private const VERSION             = 2;

	/** UI-only Pods field presentation (pick sources, limits, help text). */
	public const EDITORIAL_UI_OPTION   = 'burbank_lms_course_editorial_ui_version';
	private const EDITORIAL_UI_VERSION = 2;

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

		$provisioned = false;
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
				'has_archive'            => true,
				'has_archive_slug'       => 'courses',
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
			update_option( self::REWRITE_FLUSH_OPTION, 1, false );
			$provisioned = true;
		}

		// UI-only migration: no new storage fields, so the data schema version remains unchanged.
		if ( $provisioned || self::EDITORIAL_UI_VERSION > (int) get_option( self::EDITORIAL_UI_OPTION ) ) {
			foreach ( self::FIELDS as $name => $field_type ) {
				$field   = $api->load_field(
					array(
						'name' => $name,
						'pod'  => 'bfc_course',
					)
				);
				$options = array(
					'id'   => $field['id'],
					'pod'  => 'bfc_course',
					'name' => $name,
				);
				if ( isset( Validator::LIMITS[ $name ] ) ) {
					$options[ $field_type . '_max_length' ] = Validator::LIMITS[ $name ];
				}
				if ( 'number' === $field_type ) {
					$options['number_format']   = '9999.99';
					$options['number_decimals'] = 0;
					$options['number_html5']    = true;
					$options['number_min']      = 1;
				}
				$descriptions = array(
					'bfc_code'                => __( 'Use BFC-100 through BFC-999. The first published code is permanent.', 'burbank-lms' ),
					'course_revision'         => __( 'Use a new revision for material assessed changes. Approval must be renewed.', 'burbank-lms' ),
					'subject_category'        => __( 'Choose the subject matching the first digit of the BFC code:', 'burbank-lms' ) . ' ' . implode( ', ', Validator::SUBJECTS ),
					'course_outcome'          => __( 'Use independent_study for a knowledge course.', 'burbank-lms' ),
					'catalog_state'           => __( 'Use draft, published, or archived. Archive retains identity and approval history; WordPress post status controls publication.', 'burbank-lms' ),
					'pressprimer_quiz_id'     => __( 'Select a published PressPrimer quiz.', 'burbank-lms' ),
					'certificate_template_id' => __( 'Select a published certificate template with an active quiz trigger for this course.', 'burbank-lms' ),
					'matrix_requirement_slug' => __( 'Optional published matrix requirement slug when bc_requirement exists.', 'burbank-lms' ),
				);
				if ( isset( $descriptions[ $name ] ) ) {
					$options['description'] = $descriptions[ $name ];
				}

				if ( in_array( $name, Validator::AUDIT_FIELDS, true ) ) {
					$options['hidden'] = true;
				}
				if ( ! $this->saved( $api->save_field( $options ) ) ) {
					return;
				}
			}
			$picks = new Picks();
			$picks->register();
			if ( ! $picks->configure_fields( $api ) ) {
				$this->error = new \WP_Error( 'burbank_course_schema_failed', __( 'Burbank LMS could not configure its Pods course schema. Review Pods configuration and retry.', 'burbank-lms' ) );
				return;
			}
			update_option( self::EDITORIAL_UI_OPTION, self::EDITORIAL_UI_VERSION, false );
		} elseif ( post_type_exists( 'bc_requirement' ) ) {
			// Theme may register bc_requirement after the UI version is current; keep the slug pick in sync.
			$picks = new Picks();
			$picks->register();
			$picks->configure_fields( $api );
		}

		foreach ( self::FIELDS as $name => $field_type ) {
			$is_number = 'number' === $field_type;
			register_post_meta(
				'bfc_course',
				$name,
				array(
					'type'          => $is_number ? 'integer' : 'string',
					'single'        => true,
					// Integer+null lets Gutenberg REST delete quiz/template (and other number) meta on clear.
					'show_in_rest'  => $is_number
						? array(
							'schema' => array(
								'type' => array( 'integer', 'null' ),
							),
						)
						: true,
					'auth_callback' => static function ( bool $allowed, string $key, int $post_id ): bool {
						if ( in_array( $key, Validator::AUDIT_FIELDS, true ) ) {
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

	/** Flush rewrite rules once after schema provisioning that affects the course archive. */
	public function maybe_flush_rewrite_rules(): void {
		if ( ! get_option( self::REWRITE_FLUSH_OPTION ) ) {
			return;
		}

		delete_option( self::REWRITE_FLUSH_OPTION );
		flush_rewrite_rules( false );
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
