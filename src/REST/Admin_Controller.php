<?php
/**
 * Course editor admin REST routes (editorial actions).
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\REST;

use Burbank_LMS\Courses\Editorial;
use Burbank_LMS\Courses\Validator;

/**
 * Owns burbank-lms/v1 course editorial admin endpoints.
 *
 * Thin adapter over Courses\Editorial; learner Controller stays independent.
 */
class Admin_Controller {
	public const NAMESPACE = 'burbank-lms/v1';

	/**
	 * Optional collaborator for tests; production uses a lazy default.
	 *
	 * @param Editorial|null $editorial Editorial service.
	 */
	public function __construct(
		private ?Editorial $editorial = null,
	) {}

	/** Register routes on rest_api_init. */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/courses/(?P<id>\d+)/editorial',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_editorial' ),
				'permission_callback' => array( $this, 'permission_edit_course' ),
				'args'                => $this->id_args(),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/courses/(?P<id>\d+)/approve',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'approve' ),
				'permission_callback' => array( $this, 'permission_approve_course' ),
				'args'                => $this->id_args(),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/courses/(?P<id>\d+)/revision',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'revision' ),
				'permission_callback' => array( $this, 'permission_edit_course' ),
				'args'                => array_merge(
					$this->id_args(),
					array(
						'revision' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					)
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/courses/(?P<id>\d+)/archive',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'archive' ),
				'permission_callback' => array( $this, 'permission_edit_course' ),
				'args'                => $this->id_args(),
			)
		);
	}

	/**
	 * Hydrate approval state and snapshot history for the course sidebar.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_editorial( \WP_REST_Request $request ) {
		$course = $this->require_course( (int) $request['id'] );
		if ( is_wp_error( $course ) ) {
			return $course;
		}
		return new \WP_REST_Response( $this->editorial_payload( $course->ID ), 200 );
	}

	/**
	 * Approve the saved course configuration.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function approve( \WP_REST_Request $request ) {
		$course = $this->require_course( (int) $request['id'] );
		if ( is_wp_error( $course ) ) {
			return $course;
		}
		$result = $this->editorial()->approve( $course->ID );
		if ( is_wp_error( $result ) ) {
			return $this->to_rest_error( $result );
		}
		return new \WP_REST_Response( $this->editorial_payload( $course->ID ), 200 );
	}

	/**
	 * Start a material revision with a new revision label.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function revision( \WP_REST_Request $request ) {
		$course = $this->require_course( (int) $request['id'] );
		if ( is_wp_error( $course ) ) {
			return $course;
		}
		$result = $this->editorial()->start_revision( $course->ID, (string) $request->get_param( 'revision' ) );
		if ( is_wp_error( $result ) ) {
			return $this->to_rest_error( $result );
		}
		return new \WP_REST_Response( $this->editorial_payload( $course->ID ), 200 );
	}

	/**
	 * Archive catalog visibility for the course.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function archive( \WP_REST_Request $request ) {
		$course = $this->require_course( (int) $request['id'] );
		if ( is_wp_error( $course ) ) {
			return $course;
		}
		$result = $this->editorial()->archive( $course->ID );
		if ( is_wp_error( $result ) ) {
			return $this->to_rest_error( $result );
		}
		return new \WP_REST_Response( $this->editorial_payload( $course->ID ), 200 );
	}

	/**
	 * Logged-in user who can edit the course.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return true|\WP_Error
	 */
	public function permission_edit_course( \WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) {
			return $this->unauthorized();
		}
		$course = $this->require_course( (int) $request['id'] );
		if ( is_wp_error( $course ) ) {
			return $course;
		}
		if ( ! current_user_can( 'edit_post', $course->ID ) ) {
			return new \WP_Error(
				'burbank_course_edit_forbidden',
				__( 'You are not allowed to edit this course.', 'burbank-lms' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Approve requires edit_post plus approve_bfc_courses.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return true|\WP_Error
	 */
	public function permission_approve_course( \WP_REST_Request $request ) {
		$edit = $this->permission_edit_course( $request );
		if ( true !== $edit ) {
			return $edit;
		}
		if ( ! current_user_can( 'approve_bfc_courses' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered course capability.
			return new \WP_Error(
				'burbank_course_approve_forbidden',
				__( 'A Training Administrator must approve this course.', 'burbank-lms' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Require an existing bfc_course post.
	 *
	 * @param int $id Post ID.
	 * @return \WP_Post|\WP_Error
	 */
	private function require_course( int $id ): \WP_Post|\WP_Error {
		$post = get_post( $id );
		if ( ! $post instanceof \WP_Post || 'bfc_course' !== $post->post_type ) {
			return new \WP_Error(
				'burbank_course_not_found',
				__( 'Course not found.', 'burbank-lms' ),
				array( 'status' => 404 )
			);
		}
		return $post;
	}

	/**
	 * GET-shaped editorial payload for hydrate and action responses.
	 *
	 * @param int $course_id Course post ID.
	 * @return array{approved:bool,approved_by:int|null,approved_at:string|null,can_approve:bool,snapshots:array<int,array<string,mixed>>}
	 */
	private function editorial_payload( int $course_id ): array {
		$editorial   = $this->editorial();
		$fields      = ( new Validator() )->fields( $course_id );
		$approved_by = (int) get_post_meta( $course_id, 'approved_by', true );
		$approved_at = (string) get_post_meta( $course_id, 'approved_at', true );
		$approved    = $editorial->matches_approval( $course_id, $fields )
			&& $approved_by > 0
			&& user_can( $approved_by, 'approve_bfc_courses' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered course capability.

		return array(
			'approved'    => $approved,
			'approved_by' => $approved_by > 0 ? $approved_by : null,
			'approved_at' => '' !== $approved_at ? $approved_at : null,
			'can_approve' => current_user_can( 'approve_bfc_courses' ), // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered course capability.
			'snapshots'   => $editorial->snapshots( $course_id ),
		);
	}

	/**
	 * Shared course id route args.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function id_args(): array {
		return array(
			'id' => array(
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
			),
		);
	}

	/**
	 * Attach an HTTP status to a service WP_Error for REST.
	 *
	 * @param \WP_Error $error          Service error.
	 * @param int       $default_status Default HTTP status.
	 */
	private function to_rest_error( \WP_Error $error, int $default_status = 400 ): \WP_Error {
		$data = $error->get_error_data();
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		if ( ! isset( $data['status'] ) ) {
			$code = $error->get_error_code();
			if ( is_string( $code ) && str_contains( $code, 'forbidden' ) ) {
				$default_status = 403;
			}
			$data['status'] = $default_status;
			$error->add_data( $data );
		}
		return $error;
	}

	/** Standard unauthenticated REST error. */
	private function unauthorized(): \WP_Error {
		return new \WP_Error(
			'burbank_rest_unauthorized',
			__( 'Authentication required.', 'burbank-lms' ),
			array( 'status' => 401 )
		);
	}

	/** Lazy editorial service. */
	private function editorial(): Editorial {
		return $this->editorial ??= new Editorial();
	}
}
