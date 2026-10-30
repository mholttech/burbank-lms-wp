<?php
/**
 * Optional PressPrimer chrome branding overrides.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Branding;

/**
 * When `burbank_lms_brand_pressprimer` is truthy, replace PP plugin_name / logo
 * filters with Burbank-facing labels. Certificate upsell and mascot filters stay untouched.
 */
class PressPrimer {
	public const OPTION = 'burbank_lms_brand_pressprimer';

	/**
	 * Override PressPrimer Quiz / Certificate plugin display name.
	 *
	 * @param string $name Upstream plugin name.
	 */
	public function filter_plugin_name( string $name ): string {
		if ( ! $this->enabled() ) {
			return $name;
		}
		return (string) apply_filters(
			'burbank_lms_pressprimer_plugin_name',
			__( 'Burbank LMS', 'burbank-lms' )
		);
	}

	/**
	 * Override PressPrimer dashboard logo URL when a site or custom logo is available.
	 *
	 * @param string $url Upstream logo URL.
	 */
	public function filter_logo( string $url ): string {
		if ( ! $this->enabled() ) {
			return $url;
		}
		$custom = (string) apply_filters( 'burbank_lms_pressprimer_logo_url', $this->default_logo_url() );
		return '' !== $custom ? $custom : $url;
	}

	/**
	 * Optional learner shell branding (product name / logo) when PP exposes it.
	 *
	 * @param array<string, mixed> $branding Upstream branding bag.
	 * @return array<string, mixed>
	 */
	public function filter_shell_branding( array $branding ): array {
		if ( ! $this->enabled() ) {
			return $branding;
		}
		$branding['product_name'] = $this->filter_plugin_name(
			isset( $branding['product_name'] ) ? (string) $branding['product_name'] : ''
		);
		$logo                     = $this->filter_logo( isset( $branding['logo_url'] ) ? (string) $branding['logo_url'] : '' );
		if ( '' !== $logo ) {
			$branding['logo_url'] = $logo;
		}
		return $branding;
	}

	/** Whether Burbank branding overrides are enabled. */
	private function enabled(): bool {
		return (bool) get_option( self::OPTION );
	}

	/** Prefer the site icon when present. */
	private function default_logo_url(): string {
		return (string) get_site_icon_url( 128 );
	}
}
