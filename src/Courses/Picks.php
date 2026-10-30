<?php
/**
 * Pods pick sources for course quiz, certificate, and matrix fields.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Courses;

/**
 * Registers simple related objects and applies pick presentation without changing Phase 1 storage shape.
 *
 * Quiz and template IDs remain positive integers in post meta; matrix values remain slugs.
 */
class Picks {
	/** Related-object name for published PressPrimer quizzes. */
	public const QUIZ_OBJECT = 'burbank_pressprimer_quiz';

	/** Related-object name for published certificate templates. */
	public const TEMPLATE_OBJECT = 'burbank_certificate_template';

	/** Related-object name for published bc_requirement posts, keyed by slug. */
	public const REQUIREMENT_OBJECT = 'burbank_bc_requirement';

	/** Register custom simple related objects for Pods pick fields. */
	public function register(): void {
		if ( ! function_exists( 'pods_register_related_object' ) ) {
			return;
		}

		pods_register_related_object(
			self::QUIZ_OBJECT,
			__( 'Published PressPrimer quizzes', 'burbank-lms' ),
			array(
				'group'         => __( 'Burbank LMS', 'burbank-lms' ),
				'simple'        => true,
				'data_callback' => array( self::class, 'quiz_data' ),
			)
		);
		pods_register_related_object(
			self::TEMPLATE_OBJECT,
			__( 'Published certificate templates', 'burbank-lms' ),
			array(
				'group'         => __( 'Burbank LMS', 'burbank-lms' ),
				'simple'        => true,
				'data_callback' => array( self::class, 'template_data' ),
			)
		);
		if ( post_type_exists( 'bc_requirement' ) ) {
			pods_register_related_object(
				self::REQUIREMENT_OBJECT,
				__( 'Published matrix requirements', 'burbank-lms' ),
				array(
					'group'         => __( 'Burbank LMS', 'burbank-lms' ),
					'simple'        => true,
					'data_callback' => array( self::class, 'requirement_data' ),
				)
			);
		}
	}

	/**
	 * Apply pick presentation on top of Phase 1 number/text storage fields.
	 *
	 * @param object $api Pods API instance already configured for this request.
	 * @return bool Whether every field save succeeded.
	 */
	public function configure_fields( object $api ): bool {
		$targets = array(
			'pressprimer_quiz_id'     => self::QUIZ_OBJECT,
			'certificate_template_id' => self::TEMPLATE_OBJECT,
		);
		foreach ( $targets as $name => $related ) {
			if ( ! $this->save_pick_field( $api, $name, $related ) ) {
				return false;
			}
		}

		if ( post_type_exists( 'bc_requirement' ) ) {
			return $this->save_pick_field( $api, 'matrix_requirement_slug', self::REQUIREMENT_OBJECT );
		}

		return $this->save_text_field( $api, 'matrix_requirement_slug' );
	}

	/**
	 * Published quiz id => title pairs for the pick UI.
	 *
	 * @param mixed ...$args Unused Pods data_callback arguments.
	 * @return array<string,string>
	 */
	public static function quiz_data( ...$args ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Pods data_callback signature.
		unset( $args );
		if ( ! class_exists( '\PressPrimer_Quiz_Quiz' ) ) {
			return array();
		}
		$data = array();
		foreach (
			\PressPrimer_Quiz_Quiz::find(
				array(
					'where'    => array( 'status' => 'published' ),
					'order_by' => 'title',
					'order'    => 'ASC',
				)
			) as $quiz
		) {
			$id = (string) ( $quiz->id ?? '' );
			if ( '' === $id ) {
				continue;
			}
			$data[ $id ] = (string) ( $quiz->title ?? $id );
		}
		return $data;
	}

	/**
	 * Published certificate template id => title pairs for the pick UI.
	 *
	 * @param mixed ...$args Unused Pods data_callback arguments.
	 * @return array<string,string>
	 */
	public static function template_data( ...$args ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Pods data_callback signature.
		unset( $args );
		if ( ! class_exists( '\PressPrimer_Certificate_Template' ) ) {
			return array();
		}
		$result = \PressPrimer_Certificate_Template::query(
			array(
				'status'   => 'published',
				'per_page' => 100,
				'page'     => 1,
			)
		);
		$data   = array();
		foreach ( (array) $result['items'] as $template ) {
			$id = (string) ( $template->id ?? '' );
			if ( '' === $id ) {
				continue;
			}
			$data[ $id ] = (string) ( $template->title ?? $id );
		}
		return $data;
	}

	/**
	 * Published bc_requirement post_name => title pairs (slug storage, never IDs).
	 *
	 * @param mixed ...$args Unused Pods data_callback arguments.
	 * @return array<string,string>
	 */
	public static function requirement_data( ...$args ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Pods data_callback signature.
		unset( $args );
		if ( ! post_type_exists( 'bc_requirement' ) ) {
			return array();
		}
		$data  = array();
		$posts = get_posts(
			array(
				'post_type'              => 'bc_requirement',
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		foreach ( $posts as $post ) {
			$slug = (string) $post->post_name;
			if ( '' === $slug ) {
				continue;
			}
			$data[ $slug ] = (string) $post->post_title;
		}
		return $data;
	}

	/**
	 * Persist a single-select pick field pointing at a registered related object.
	 *
	 * @param object $api Pods API.
	 * @param string $name Field name.
	 * @param string $related Related-object name.
	 */
	private function save_pick_field( object $api, string $name, string $related ): bool {
		$field = $api->load_field(
			array(
				'name' => $name,
				'pod'  => 'bfc_course',
			)
		);
		if ( ! $field ) {
			return false;
		}
		$result = $api->save_field(
			array(
				'id'                 => $field['id'],
				'pod'                => 'bfc_course',
				'name'               => $name,
				'type'               => 'pick',
				'pick_object'        => $related,
				'pick_format_type'   => 'single',
				'pick_format_single' => 'dropdown',
			)
		);
		return ! is_wp_error( $result ) && (bool) $result;
	}

	/**
	 * Restore the Phase 1 text field when bc_requirement is absent.
	 *
	 * @param object $api Pods API.
	 * @param string $name Field name.
	 */
	private function save_text_field( object $api, string $name ): bool {
		$field = $api->load_field(
			array(
				'name' => $name,
				'pod'  => 'bfc_course',
			)
		);
		if ( ! $field ) {
			return false;
		}
		if ( 'text' === ( $field['type'] ?? '' ) ) {
			return true;
		}
		$result = $api->save_field(
			array(
				'id'   => $field['id'],
				'pod'  => 'bfc_course',
				'name' => $name,
				'type' => 'text',
			)
		);
		return ! is_wp_error( $result ) && (bool) $result;
	}
}
