<?php
/**
 * Learner profile fields and legal-name audit.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Access;

/** Owns legal/certificate name storage and admin correction audit history. */
class Profile {
	public const META_LEGAL_NAME = 'burbank_lms_legal_name';

	public const META_LEGAL_NAME_AUDIT = 'burbank_lms_legal_name_audit';

	/**
	 * Bypass flag for intentional Profile writes through meta APIs (shared across instances).
	 *
	 * @var bool
	 */
	private static bool $allow_legal_name_write = false;

	/**
	 * Set the legal/certificate name.
	 *
	 * Initial set (empty existing value) is allowed during registration.
	 * Subsequent changes require admin_correction=true and append an audit entry.
	 *
	 * @param int    $user_id          User ID.
	 * @param string $name             Legal name.
	 * @param bool   $admin_correction Whether this is an audited admin correction.
	 */
	public function set_legal_name( int $user_id, string $name, bool $admin_correction = false ): bool|\WP_Error {
		if ( $user_id < 1 ) {
			return new \WP_Error( 'burbank_profile_invalid_user', __( 'Invalid user.', 'burbank-lms' ) );
		}
		$name = sanitize_text_field( $name );
		if ( '' === $name ) {
			return new \WP_Error( 'burbank_profile_legal_name_required', __( 'Legal name is required.', 'burbank-lms' ) );
		}
		$current = $this->get_legal_name( $user_id );
		if ( '' !== $current && ! $admin_correction ) {
			return new \WP_Error(
				'burbank_profile_legal_name_locked',
				__( 'Legal name can only be corrected by a training administrator.', 'burbank-lms' )
			);
		}
		if ( $admin_correction ) {
			if ( ! current_user_can( 'enroll_bfc_courses' ) && ! current_user_can( 'edit_users' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- enroll_bfc_courses from Capabilities VERSION 2.
				return new \WP_Error(
					'burbank_profile_legal_name_forbidden',
					__( 'You are not allowed to correct legal names.', 'burbank-lms' )
				);
			}
		}

		self::$allow_legal_name_write = true;
		try {
			update_user_meta( $user_id, self::META_LEGAL_NAME, $name );
			if ( $admin_correction && '' !== $current && $current !== $name ) {
				$this->append_audit( $user_id, $current, $name );
			}
		} finally {
			self::$allow_legal_name_write = false;
		}
		return true;
	}

	/**
	 * Return the stored legal/certificate name.
	 *
	 * @param int $user_id User ID.
	 */
	public function get_legal_name( int $user_id ): string {
		return (string) get_user_meta( $user_id, self::META_LEGAL_NAME, true );
	}

	/**
	 * Block direct usermeta writes to legal name unless Profile allows them.
	 *
	 * @param null|bool $check    Short-circuit value.
	 * @param int       $user_id  User ID.
	 * @param string    $meta_key Meta key.
	 * @param mixed     $meta_value Meta value.
	 * @return null|bool
	 */
	public function guard_legal_name_meta( $check, int $user_id, string $meta_key, $meta_value ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- WordPress filter signature.
		if ( self::META_LEGAL_NAME !== $meta_key ) {
			return $check;
		}
		if ( self::$allow_legal_name_write ) {
			return $check;
		}
		return false;
	}

	/**
	 * Append a legal-name correction entry to the JSON audit log.
	 *
	 * @param int    $user_id User ID.
	 * @param string $from    Previous name.
	 * @param string $to      New name.
	 */
	private function append_audit( int $user_id, string $from, string $to ): void {
		$raw     = get_user_meta( $user_id, self::META_LEGAL_NAME_AUDIT, true );
		$entries = array();
		if ( is_string( $raw ) && '' !== $raw ) {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				$entries = $decoded;
			}
		}
		$entries[] = array(
			'from' => $from,
			'to'   => $to,
			'by'   => (int) get_current_user_id(),
			'at'   => gmdate( 'c' ),
		);
		update_user_meta( $user_id, self::META_LEGAL_NAME_AUDIT, wp_json_encode( $entries ) );
	}
}
