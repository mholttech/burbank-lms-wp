<?php
/** Training management endpoints.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\REST;

use Burbank_LMS\Access\Invites;
use Burbank_LMS\Access\Learner;
use Burbank_LMS\Access\Profile;
use Burbank_LMS\Access\Email_Verification;

/** Capability-protected adapters for the React training screens. */
class Training_Controller {
	/** Register routes through Loader's rest_api_init. */
	public function register_routes(): void {
		foreach ( array(
			'/training/invites'              => array( 'GET', 'POST' ),
			'/training/invites/(?P<id>\d+)'  => array( 'DELETE' ),
			'/training/learners'             => array( 'GET' ),
			'/training/learners/(?P<id>\d+)' => array( 'POST' ),
		) as $path => $methods ) {
			register_rest_route(
				'burbank-lms/v1',
				$path,
				array(
					'methods'             => implode( ',', $methods ),
					'callback'            => array( $this, 'dispatch' ),
					'permission_callback' => array( $this, 'permission' ),
				)
			);
		}
	}

	/** Require the existing training management capability. */
	public function permission(): bool {
		return current_user_can( 'enroll_bfc_courses' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Plugin capability.
	}

	/** Dispatch validated training actions.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function dispatch( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$invites = new Invites();
		$id      = absint( $request['id'] ?? 0 );
		if ( str_contains( $request->get_route(), '/invites' ) ) {
			if ( 'POST' === $request->get_method() ) {
				$args = array();
				foreach ( array( 'label', 'kind', 'max_uses', 'expires_at', 'bound_email' ) as $key ) {
					$value = $request->get_param( $key );
					if ( null !== $value && '' !== $value ) {
						if ( ! is_scalar( $value ) ) {
							return $this->error( 'invalid', __( 'Invalid invite option.', 'burbank-lms' ) );
						}
						$args[ $key ] = $value;
					}
				}
				return $this->response( $invites->create( 'site', $args ) );
			}
			$rows = $invites->list( 'site' );
			if ( 'DELETE' === $request->get_method() ) {
				if ( ! in_array( $id, array_map( static fn( $row ) => (int) $row->id, $rows ), true ) ) {
					return $this->error( 'not_found', __( 'Site invite not found.', 'burbank-lms' ), 404 );
				}
				return $this->response( $invites->revoke( $id ) );
			}
			return rest_ensure_response(
				array_map(
					static function ( $row ) use ( $invites ) {
						return array(
							'id'          => (int) $row->id,
							'label'       => $row->label,
							'kind'        => $row->kind,
							'uses'        => (int) $row->use_count,
							'max_uses'    => $row->max_uses,
							'expires_at'  => $row->expires_at,
							'bound_email' => $row->bound_email,
							'active'      => $invites->is_active( $row ),
							'revoked'     => ! empty( $row->revoked_at ),
						);
					},
					$rows
				)
			);
		}
		if ( 'GET' === $request->get_method() ) {
			$page   = max( 1, absint( $request['page'] ?? 1 ) );
			$search = $request['search'] ?? '';
			if ( ! is_string( $search ) ) {
				return $this->error( 'invalid', __( 'Invalid search.', 'burbank-lms' ) );
			}
			$query = new \WP_User_Query(
				array(
					'number'         => 20,
					'paged'          => $page,
					'search'         => '' === $search ? '' : '*' . sanitize_text_field( $search ) . '*',
					'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
					'orderby'        => 'ID',
					'order'          => 'ASC',
				)
			);
			return rest_ensure_response(
				array(
					'items' => array_map( array( $this, 'learner_data' ), $query->get_results() ),
					'total' => $query->get_total(),
					'page'  => $page,
				)
			);
		}
		$user = get_userdata( $id );
		if ( ! $user ) {
			return $this->error( 'not_found', __( 'User not found.', 'burbank-lms' ), 404 );
		}
		$operation = $request['operation'];
		$learner   = new Learner();
		if ( 'legal_name' === $operation ) {
			$name = $request['legal_name'];
			if ( ! is_string( $name ) ) {
				return $this->error( 'invalid', __( 'Enter a legal name.', 'burbank-lms' ) );
			}
			$result = ( new Profile() )->set_legal_name( $id, $name, true );
		} elseif ( 'resend' === $operation ) {
			if ( ! $learner->needs_email_verification( $id ) ) {
				return $this->error( 'verified', __( 'This learner does not need verification.', 'burbank-lms' ) );
			}
			$result = ( new Email_Verification() )->send( $id );
			if ( false === $result ) {
				return $this->error( 'mail_failed', __( 'Verification email could not be sent.', 'burbank-lms' ), 502 );
			}
		} elseif ( in_array( $operation, array( 'grant', 'revoke', 'suspend', 'reactivate' ), true ) ) {
			$method = array(
				'grant'      => 'grant_access',
				'revoke'     => 'revoke_access',
				'suspend'    => 'suspend',
				'reactivate' => 'reactivate',
			)[ $operation ];
			$learner->$method( $id );
			$result = true;
		} else {
			return $this->error( 'invalid', __( 'Unknown training action.', 'burbank-lms' ) );
		}
		return is_wp_error( $result ) ? $this->response( $result ) : rest_ensure_response( $this->learner_data( new \WP_User( $id ) ) );
	}

	/** Safe user data for management.
	 *
	 * @param \WP_User $user User.
	 * @return array<string,mixed>
	 */
	public function learner_data( \WP_User $user ): array {
		$learner = new Learner();
		return array(
			'id'         => $user->ID,
			'name'       => $user->display_name,
			'email'      => $user->user_email,
			'legal_name' => ( new Profile() )->get_legal_name( $user->ID ),
			'access'     => user_can( $user, Learner::CAP_ACCESS ),
			'suspended'  => $learner->is_suspended( $user->ID ),
			'verified'   => $learner->is_email_verified( $user->ID ),
		); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Plugin capability.
	}

	/** Normalize service errors.
	 *
	 * @param mixed $result Service result.
	 */
	private function response( $result ): \WP_REST_Response|\WP_Error {
		if ( is_wp_error( $result ) ) {
			if ( ! $result->get_error_data() ) {
				$result->add_data( array( 'status' => 400 ) );
			}
			return $result;
		}
		return rest_ensure_response( $result );
	}

	/** Build a REST error.
	 *
	 * @param string $code Error code.
	 * @param string $message Message.
	 * @param int    $status HTTP status.
	 */
	private function error( string $code, string $message, int $status = 400 ): \WP_Error {
		return new \WP_Error( 'burbank_training_' . $code, $message, array( 'status' => $status ) );
	}
}
