<?php
/**
 * Learner registration and enrollment REST routes.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\REST;

use Burbank_LMS\Access\Learner;
use Burbank_LMS\Access\Registration;
use Burbank_LMS\Enrollment\Service as Enrollment_Service;

/**
 * Owns burbank-lms/v1 register / enroll / withdraw endpoints.
 *
 * Does not proxy PressPrimer attempt APIs; quiz gates stay on PressPrimer hooks.
 */
class Controller {
	public const NAMESPACE = 'burbank-lms/v1';

	private const REGISTER_RATE_LIMIT = 10;

	private const REGISTER_RATE_WINDOW = 600;

	/**
	 * Optional collaborators for tests; production uses lazy defaults.
	 *
	 * @param Registration|null       $registration Registration service.
	 * @param Enrollment_Service|null $enrollment   Enrollment service.
	 * @param Learner|null            $learner      Learner access service.
	 */
	public function __construct(
		private ?Registration $registration = null,
		private ?Enrollment_Service $enrollment = null,
		private ?Learner $learner = null,
	) {}

	/** Register routes on rest_api_init. */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/register',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'register' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'invite'       => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'email'        => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_email',
					),
					'password'     => array(
						'type'     => 'string',
						'required' => false,
					),
					'legal_name'   => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'display_name' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/enrollments',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list_enrollments' ),
					'permission_callback' => array( $this, 'permission_list_enrollments' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'enroll' ),
					'permission_callback' => array( $this, 'permission_enroll' ),
					'args'                => array(
						'course_id'     => array(
							'type'              => 'integer',
							'required'          => false,
							'sanitize_callback' => 'absint',
						),
						'course_invite' => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/enrollments/(?P<course_id>\d+)',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'withdraw' ),
				'permission_callback' => array( $this, 'permission_withdraw' ),
				'args'                => array(
					'course_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'user_id'   => array(
						'type'              => 'integer',
						'required'          => false,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/enrollments/admin',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'admin_enroll' ),
				'permission_callback' => array( $this, 'permission_admin_enroll' ),
				'args'                => array(
					'user_id'   => array(
						'type'              => 'integer',
						'required'          => false,
						'sanitize_callback' => 'absint',
					),
					'course_id' => array(
						'type'              => 'integer',
						'required'          => false,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Invite-gated public registration.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function register( \WP_REST_Request $request ) {
		$limited = $this->check_register_rate_limit();
		if ( is_wp_error( $limited ) ) {
			return $limited;
		}

		$result = $this->registration()->register(
			array(
				'invite'       => (string) $request->get_param( 'invite' ),
				'email'        => (string) $request->get_param( 'email' ),
				'password'     => (string) $request->get_param( 'password' ),
				'legal_name'   => (string) $request->get_param( 'legal_name' ),
				'display_name' => (string) $request->get_param( 'display_name' ),
			)
		);
		if ( is_wp_error( $result ) ) {
			return $this->to_rest_error( $result );
		}

		return new \WP_REST_Response(
			array(
				'user_id' => (int) $result,
			),
			201
		);
	}

	/**
	 * List the current learner's enrollments for My Courses.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function list_enrollments() {
		$user_id = get_current_user_id();
		$rows    = $this->enrollment()->list_for_user( $user_id );
		$items   = array();
		foreach ( $rows as $row ) {
			$course_id = (int) $row->course_id;
			$post      = get_post( $course_id );
			$status    = (string) $row->status;
			if ( 'active' === $status && ! empty( $row->quiz_passed_at ) ) {
				$status = 'passed';
			}
			$items[] = array(
				'course_id'    => $course_id,
				'title'        => $post instanceof \WP_Post ? get_the_title( $post ) : '',
				'permalink'    => $post instanceof \WP_Post ? get_permalink( $post ) : '',
				'status'       => $status,
				'enrolled_at'  => (string) $row->enrolled_at,
				'completed_at' => $row->completed_at ? (string) $row->completed_at : null,
			);
		}
		return new \WP_REST_Response( array( 'enrollments' => $items ), 200 );
	}

	/**
	 * Self-enroll the current learner.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function enroll( \WP_REST_Request $request ) {
		$user_id       = get_current_user_id();
		$course_id     = (int) $request->get_param( 'course_id' );
		$course_invite = $request->get_param( 'course_invite' );
		$invite        = null !== $course_invite && '' !== (string) $course_invite
			? (string) $course_invite
			: null;

		$result = $this->enrollment()->self_enroll( $user_id, $course_id, $invite );
		if ( is_wp_error( $result ) ) {
			return $this->to_rest_error( $result );
		}

		return new \WP_REST_Response(
			array(
				'enrolled'  => true,
				'user_id'   => $user_id,
				'course_id' => $course_id,
			),
			201
		);
	}

	/**
	 * Withdraw an enrollment (self or enroll_bfc_courses).
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function withdraw( \WP_REST_Request $request ) {
		$actor_id  = get_current_user_id();
		$course_id = (int) $request['course_id'];
		$user_id   = (int) ( $request->get_param( 'user_id' ) ?: $actor_id );

		$result = $this->enrollment()->withdraw( $actor_id, $user_id, $course_id );
		if ( is_wp_error( $result ) ) {
			$status = 'burbank_enrollment_forbidden' === $result->get_error_code() ? 403 : 400;
			return $this->to_rest_error( $result, $status );
		}

		return new \WP_REST_Response(
			array(
				'withdrawn' => true,
				'user_id'   => $user_id,
				'course_id' => $course_id,
			),
			200
		);
	}

	/**
	 * Admin-assign enrollment.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function admin_enroll( \WP_REST_Request $request ) {
		$actor_id  = get_current_user_id();
		$user_id   = (int) $request->get_param( 'user_id' );
		$course_id = (int) $request->get_param( 'course_id' );

		$result = $this->enrollment()->admin_enroll( $actor_id, $user_id, $course_id );
		if ( is_wp_error( $result ) ) {
			$status = 'burbank_enrollment_forbidden' === $result->get_error_code() ? 403 : 400;
			return $this->to_rest_error( $result, $status );
		}

		return new \WP_REST_Response(
			array(
				'enrolled'  => true,
				'user_id'   => $user_id,
				'course_id' => $course_id,
			),
			201
		);
	}

	/**
	 * Listing enrollments requires a logged-in user.
	 *
	 * @return true|\WP_Error
	 */
	public function permission_list_enrollments() {
		if ( ! is_user_logged_in() ) {
			return $this->unauthorized();
		}
		return true;
	}

	/**
	 * Self-enroll requires a logged-in learner who can_learn.
	 *
	 * @return true|\WP_Error
	 */
	public function permission_enroll() {
		if ( ! is_user_logged_in() ) {
			return $this->unauthorized();
		}
		if ( ! $this->learner()->can_learn( get_current_user_id() ) ) {
			return new \WP_Error(
				'burbank_enrollment_cannot_learn',
				__( 'You must verify your email and have training access to enroll.', 'burbank-lms' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Withdraw is allowed for self or enroll_bfc_courses.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return true|\WP_Error
	 */
	public function permission_withdraw( \WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) {
			return $this->unauthorized();
		}
		$actor_id = get_current_user_id();
		$user_id  = (int) ( $request->get_param( 'user_id' ) ?: $actor_id );
		if ( $actor_id === $user_id ) {
			return true;
		}
		if ( current_user_can( 'enroll_bfc_courses' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- enroll_bfc_courses from Capabilities VERSION 2.
			return true;
		}
		return new \WP_Error(
			'burbank_enrollment_forbidden',
			__( 'You are not allowed to withdraw this enrollment.', 'burbank-lms' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Admin enroll requires enroll_bfc_courses.
	 *
	 * @return true|\WP_Error
	 */
	public function permission_admin_enroll() {
		if ( ! is_user_logged_in() ) {
			return $this->unauthorized();
		}
		if ( ! current_user_can( 'enroll_bfc_courses' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- enroll_bfc_courses from Capabilities VERSION 2.
			return new \WP_Error(
				'burbank_enrollment_forbidden',
				__( 'You are not allowed to enroll learners.', 'burbank-lms' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Light per-IP rate limit for public registration.
	 *
	 * @return true|\WP_Error
	 */
	private function check_register_rate_limit() {
		$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '0.0.0.0'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Hashed into transient key only.
		$key  = 'burbank_lms_reg_' . md5( $ip );
		$hits = (int) get_transient( $key );
		if ( $hits >= self::REGISTER_RATE_LIMIT ) {
			return new \WP_Error(
				'burbank_register_rate_limited',
				__( 'Too many registration attempts. Please try again later.', 'burbank-lms' ),
				array( 'status' => 429 )
			);
		}
		set_transient( $key, $hits + 1, self::REGISTER_RATE_WINDOW );
		return true;
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

	/** Lazy registration service. */
	private function registration(): Registration {
		return $this->registration ??= new Registration();
	}

	/** Lazy enrollment service. */
	private function enrollment(): Enrollment_Service {
		return $this->enrollment ??= new Enrollment_Service();
	}

	/** Lazy learner access service. */
	private function learner(): Learner {
		return $this->learner ??= new Learner();
	}
}
