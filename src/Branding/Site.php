<?php
/**
 * Shared site product name and logo for learner surfaces.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Branding;

/** Resolves Burbank-facing product name, kicker, and logo URL. */
class Site {
	/** Training caption used on shell, soft-gate, and login. */
	public function kicker(): string {
		return __( 'Training', 'burbank-lms' );
	}

	/** Product display name (filterable). */
	public function product_name(): string {
		return (string) apply_filters(
			'burbank_lms_product_name',
			__( 'Burbank LMS', 'burbank-lms' )
		);
	}

	/**
	 * Logo URL for login / PressPrimer chrome (site icon by default).
	 *
	 * Prefer `burbank_lms_logo_url`; fall back to the PressPrimer-specific filter for BC.
	 */
	public function logo_url(): string {
		$default = (string) get_site_icon_url( 128 );
		$logo    = (string) apply_filters( 'burbank_lms_logo_url', $default );
		if ( '' === $logo ) {
			$logo = (string) apply_filters( 'burbank_lms_pressprimer_logo_url', $default );
		}
		return $logo;
	}
}
