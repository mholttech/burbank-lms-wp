<?php
/**
 * Course editor admin REST routes (editorial, invites, enrollments).
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\REST;

use Burbank_LMS\Access\Invites;
use Burbank_LMS\Courses\Editorial;
use Burbank_LMS\Courses\Validator;
use Burbank_LMS\Enrollment\Repository as Enrollment_Repository;

/**
 * Owns burbank-lms/v1 course editorial admin endpoints.
 *
 * Thin adapter over Courses\Editorial, Access\Invites, and Enrollment\Repository;
 * learner Controller stays independent (admin enroll remains there).
 */
class Admin_Controller {
	public const NAMESPACE = 'burbank-lms/v1';

	/**
	 * Optional collaborators for tests; production uses lazy defaults.
	 *
	 * @param Editorial|null             $editorial   Editorial service.
	 * @param Invites|null               $invites     Invite service.
	 * @param Enrollment_Repository|null $enrollments Enrollment repository.
	 */
	public function __construct(
		private ?Editorial $editorial = null,
		private ?Invites $invites = null,
		private ?Enrollment_Repository $enrollments = null,
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

		register_rest_route(
			self::NAMESPACE,
			'/courses/(?P<id>\d+)/invites',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list_invites' ),
					'permission_callback' => array( $this, 'permission_enroll_course' ),
					'args'                => $this->id_args(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_invite' ),
					'permission_callback' => array( $this, 'permission_enroll_course' ),
					'args'                => array_merge(
						$this->id_args(),
						array(
							'label'       => array(
								'type'              => 'string',
								'required'          => false,
								'sanitize_callback' => 'sanitize_text_field',
							),
							'kind'        => array(
								'type'              => 'string',
								'required'          => true,
								'sanitize_callback' => 'sanitize_text_field',
							),
							'max_uses'    => array(
								'type'              => 'integer',
								'required'          => false,
								'sanitize_callback' => 'absint',
							),
							'expires_at'  => array(
								'type'              => 'string',
								'required'          => false,
								'sanitize_callback' => 'sanitize_text_field',
							),
							'bound_email' => array(
								'type'              => 'string',
								'required'          => false,
								'sanitize_callback' => 'sanitize_email',
							),
						)
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/invites/(?P<invite_id>\d+)',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'revoke_invite' ),
				'permission_callback' => array( $this, 'permission_revoke_invite' ),
				'args'                => array(
					'invite_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/courses/(?P<id>\d+)/enrollments',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'list_enrollments' ),
				'permission_callback' => array( $this, 'permission_enroll_course' ),
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
	 * List course-scoped invites without code_hash or plaintext.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function list_invites( \WP_REST_Request $request ) {
		$course = $this->require_course( (int) $request['id'] );
		if ( is_wp_error( $course ) ) {
			return $course;
		}
		$rows    = $this->invites()->list( 'course', $course->ID );
		$invites = array();
		foreach ( $rows as $row ) {
			$invites[] = $this->serialize_invite( $row );
		}
		return new \WP_REST_Response( array( 'invites' => $invites ), 200 );
	}

	/**
	 * Create a course invite; plaintext is returned once.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_invite( \WP_REST_Request $request ) {
		$course = $this->require_course( (int) $request['id'] );
		if ( is_wp_error( $course ) ) {
			return $course;
		}
		$args     = array(
			'course_id' => $course->ID,
			'label'     => (string) ( $request->get_param( 'label' ) ?? '' ),
			'kind'      => (string) $request->get_param( 'kind' ),
		);
		$max_uses = $request->get_param( 'max_uses' );
		if ( null !== $max_uses && '' !== $max_uses ) {
			$args['max_uses'] = (int) $max_uses;
		}
		$expires_at = $request->get_param( 'expires_at' );
		if ( null !== $expires_at && '' !== $expires_at ) {
			$args['expires_at'] = (string) $expires_at;
		}
		$bound_email = $request->get_param( 'bound_email' );
		if ( null !== $bound_email && '' !== $bound_email ) {
			$args['bound_email'] = (string) $bound_email;
		}
		$result = $this->invites()->create( 'course', $args );
		if ( is_wp_error( $result ) ) {
			return $this->to_rest_error( $result );
		}
		return new \WP_REST_Response( $result, 201 );
	}

	/**
	 * Revoke an invite by ID.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function revoke_invite( \WP_REST_Request $request ) {
		$result = $this->invites()->revoke( (int) $request['invite_id'] );
		if ( is_wp_error( $result ) ) {
			return $this->to_rest_error( $result, 404 );
		}
		return new \WP_REST_Response( array( 'revoked' => true ), 200 );
	}

	/**
	 * List enrollments for a course with learner display names.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function list_enrollments( \WP_REST_Request $request ) {
		$course = $this->require_course( (int) $request['id'] );
		if ( is_wp_error( $course ) ) {
			return $course;
		}
		$rows        = $this->enrollments()->list_for_course( $course->ID );
		$enrollments = array();
		foreach ( $rows as $row ) {
			$user_id       = (int) $row->user_id;
			$user          = get_userdata( $user_id );
			$enrollments[] = array(
				'user_id'        => $user_id,
				'display_name'   => $user ? (string) $user->display_name : '',
				'status'         => (string) $row->status,
				'source'         => (string) $row->source,
				'enrolled_at'    => (string) $row->enrolled_at,
				'quiz_passed_at' => $row->quiz_passed_at ? (string) $row->quiz_passed_at : null,
				'completed_at'   => $row->completed_at ? (string) $row->completed_at : null,
			);
		}
		return new \WP_REST_Response( array( 'enrollments' => $enrollments ), 200 );
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
	 * Invite/enrollment list routes require enroll_bfc_courses and edit_post.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return true|\WP_Error
	 */
	public function permission_enroll_course( \WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) {
			return $this->unauthorized();
		}
		$course = $this->require_course( (int) $request['id'] );
		if ( is_wp_error( $course ) ) {
			return $course;
		}
		if ( ! current_user_can( 'enroll_bfc_courses' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- enroll_bfc_courses from Capabilities VERSION 2.
			return new \WP_Error(
				'burbank_enroll_forbidden',
				__( 'You are not allowed to manage course invitations or enrollments.', 'burbank-lms' ),
				array( 'status' => 403 )
			);
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
	 * Revoke requires enroll_bfc_courses and edit access to the invite's course.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return true|\WP_Error
	 */
	public function permission_revoke_invite( \WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) {
			return $this->unauthorized();
		}
		if ( ! current_user_can( 'enroll_bfc_courses' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- enroll_bfc_courses from Capabilities VERSION 2.
			return new \WP_Error(
				'burbank_invite_forbidden',
				__( 'You are not allowed to manage invites.', 'burbank-lms' ),
				array( 'status' => 403 )
			);
		}
		$invite = $this->invite_row( (int) $request['invite_id'] );
		if ( is_wp_error( $invite ) ) {
			return $invite;
		}
		if ( 'course' !== (string) $invite->scope || (int) $invite->course_id < 1 ) {
			return new \WP_Error(
				'burbank_invite_not_found',
				__( 'Invite not found.', 'burbank-lms' ),
				array( 'status' => 404 )
			);
		}
		$course = $this->require_course( (int) $invite->course_id );
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
	 * @return array{approved:bool,approved_by:int|null,approved_at:string|null,can_approve:bool,status:string,snapshots:array<int,array<string,mixed>>}
	 */
	private function editorial_payload( int $course_id ): array {
		$editorial   = $this->editorial();
		$fields      = ( new Validator() )->fields( $course_id );
		$approved_by = (int) get_post_meta( $course_id, 'approved_by', true );
		$approved_at = (string) get_post_meta( $course_id, 'approved_at', true );
		$approved    = $editorial->matches_approval( $course_id, $fields )
			&& $approved_by > 0
			&& user_can( $approved_by, 'approve_bfc_courses' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered course capability.
		$status      = get_post_status( $course_id );

		return array(
			'approved'    => $approved,
			'approved_by' => $approved_by > 0 ? $approved_by : null,
			'approved_at' => '' !== $approved_at ? $approved_at : null,
			'can_approve' => current_user_can( 'approve_bfc_courses' ), // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered course capability.
			'status'      => is_string( $status ) ? $status : 'draft',
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

	/**
	 * Serialize an invite row for REST without code_hash or plaintext.
	 *
	 * @param object $row Invite row.
	 * @return array<string, mixed>
	 */
	private function serialize_invite( object $row ): array {
		return array(
			'id'          => (int) $row->id,
			'scope'       => (string) $row->scope,
			'course_id'   => null !== $row->course_id && '' !== $row->course_id ? (int) $row->course_id : null,
			'label'       => (string) $row->label,
			'kind'        => (string) $row->kind,
			'max_uses'    => null !== $row->max_uses && '' !== $row->max_uses ? (int) $row->max_uses : null,
			'use_count'   => (int) $row->use_count,
			'expires_at'  => $row->expires_at ? (string) $row->expires_at : null,
			'revoked_at'  => $row->revoked_at ? (string) $row->revoked_at : null,
			'bound_email' => $row->bound_email ? (string) $row->bound_email : null,
			'created_by'  => (int) $row->created_by,
			'created_at'  => (string) $row->created_at,
		);
	}

	/**
	 * Load an invite row by ID for revoke permission checks.
	 *
	 * @param int $invite_id Invite row ID.
	 * @return \stdClass|\WP_Error
	 */
	private function invite_row( int $invite_id ): \stdClass|\WP_Error {
		if ( $invite_id < 1 ) {
			return new \WP_Error(
				'burbank_invite_not_found',
				__( 'Invite not found.', 'burbank-lms' ),
				array( 'status' => 404 )
			);
		}
		global $wpdb;
		$table = $wpdb->prefix . 'bfc_invite_codes';
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT id, scope, course_id FROM {$table} WHERE id = %d LIMIT 1", $invite_id ) ); // phpcs:ignore WordPress.DB -- Prepared custom-table read for permission scope.
		if ( ! $row instanceof \stdClass ) {
			return new \WP_Error(
				'burbank_invite_not_found',
				__( 'Invite not found.', 'burbank-lms' ),
				array( 'status' => 404 )
			);
		}
		return $row;
	}

	/** Lazy editorial service. */
	private function editorial(): Editorial {
		return $this->editorial ??= new Editorial();
	}

	/** Lazy invite service. */
	private function invites(): Invites {
		return $this->invites ??= new Invites();
	}

	/** Lazy enrollment repository. */
	private function enrollments(): Enrollment_Repository {
		return $this->enrollments ??= new Enrollment_Repository();
	}
}
