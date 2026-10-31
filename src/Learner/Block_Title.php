<?php
/**
 * Optional H2 titles for learner blocks (page templates own the H1).
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Learner;

/**
 * Renders demoted block headings when the surrounding page does not already supply one.
 */
class Block_Title {
	/** Page slugs whose theme/pattern markup already prints the primary H1. */
	private const PAGE_SLUGS = array(
		'profile',
		'my-courses',
		'register',
	);

	/**
	 * Whether a visible block title should print for the given learner page slug.
	 *
	 * @param string $page_slug Empty string when not on a learner slug page.
	 */
	public function should_render( string $page_slug = '' ): bool {
		if ( '' === $page_slug ) {
			if ( is_page( self::PAGE_SLUGS ) ) {
				return false;
			}
			return true;
		}

		return ! in_array( $page_slug, self::PAGE_SLUGS, true );
	}

	/**
	 * Print an H2 block title when appropriate.
	 *
	 * @param string $text  Visible title.
	 * @param string $class_name BEM class on the heading element.
	 */
	public function render( string $text, string $class_name ): string {
		if ( ! $this->should_render() ) {
			return '';
		}

		return sprintf(
			'<h2 class="%1$s">%2$s</h2>',
			esc_attr( $class_name ),
			esc_html( $text )
		);
	}
}
