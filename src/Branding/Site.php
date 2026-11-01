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
	 * Logo URL for login / PressPrimer chrome.
	 *
	 * Prefer `burbank_lms_logo_url`; fall back to the PressPrimer-specific filter for BC.
	 * Default uses the custom logo or site-icon attachment at full size so landscape
	 * wordmarks are not forced through WordPress’s square icon crops.
	 */
	public function logo_url(): string {
		$default = $this->default_logo_url();
		$logo    = (string) apply_filters( 'burbank_lms_logo_url', $default );
		if ( '' === $logo ) {
			$logo = (string) apply_filters( 'burbank_lms_pressprimer_logo_url', $default );
		}
		return $logo;
	}

	/**
	 * Resolve the best available site logo URL at full resolution.
	 */
	private function default_logo_url(): string {
		$custom_logo_id = (int) get_theme_mod( 'custom_logo' );
		if ( $custom_logo_id > 0 ) {
			$url = wp_get_attachment_image_url( $custom_logo_id, 'full' );
			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}

		$icon_id = (int) get_option( 'site_icon' );
		if ( $icon_id > 0 ) {
			$url = wp_get_attachment_image_url( $icon_id, 'full' );
			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}

		return (string) get_site_icon_url( 512 );
	}
}
