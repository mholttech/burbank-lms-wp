<?php
/**
 * Heading ownership when portal chrome or soft-gate owns the page title.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Learner;

/**
 * Suppresses redundant theme/page H1s on learner pages so portal / soft-gate
 * remain the single document heading (Duty Officer hides its shortcode H2
 * after enhancement; LMS does the equivalent for page H1s in PHP).
 */
class Portal_Headings {
	/** Learner page slugs whose theme patterns often print a competing H1. */
	private const PAGE_SLUGS = array(
		'profile',
		'my-courses',
	);

	/**
	 * Drop core/post-title and core/heading level-1 output on profile and my-courses.
	 *
	 * @param string               $block_content Block HTML.
	 * @param array<string, mixed> $block         Parsed block.
	 */
	public function suppress_page_h1( string $block_content, array $block ): string {
		if ( '' === $block_content || is_admin() ) {
			return $block_content;
		}
		if ( ! is_page( self::PAGE_SLUGS ) ) {
			return $block_content;
		}

		$name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
		if ( 'core/post-title' === $name ) {
			return '';
		}
		if ( 'core/heading' === $name || '' === $name ) {
			$level = isset( $block['attrs']['level'] ) ? (int) $block['attrs']['level'] : 2;
			if ( 1 === $level ) {
				return '';
			}
		}

		return $block_content;
	}

	/**
	 * Drop core/query-title output on the course archive (portal owns the H1).
	 *
	 * @param string               $block_content Block HTML.
	 * @param array<string, mixed> $block         Parsed block.
	 */
	public function suppress_archive_title( string $block_content, array $block ): string {
		if ( '' === $block_content || is_admin() ) {
			return $block_content;
		}
		if ( ! is_post_type_archive( 'bfc_course' ) ) {
			return $block_content;
		}
		$name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
		return 'core/query-title' === $name ? '' : $block_content;
	}
}
