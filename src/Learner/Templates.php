<?php
/**
 * Plugin-registered FSE templates for the course catalog CPT.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Learner;

/**
 * Registers archive and single block templates when the WP 6.7+ API is available.
 */
class Templates {
	/** Register plugin block templates for bfc_course archive and single. */
	public function register(): void {
		if ( ! function_exists( 'register_block_template' ) ) {
			return;
		}

		register_block_template(
			'burbank-lms//archive-bfc_course',
			array(
				'title'       => __( 'Course Catalog', 'burbank-lms' ),
				'description' => __( 'Default archive template for published Burbank courses.', 'burbank-lms' ),
				'content'     => $this->archive_content(),
			)
		);

		register_block_template(
			'burbank-lms//single-bfc_course',
			array(
				'title'       => __( 'Single Course', 'burbank-lms' ),
				'description' => __( 'Default single template for a Burbank course.', 'burbank-lms' ),
				'content'     => $this->single_content(),
			)
		);
	}

	/** Block markup for the course archive. */
	private function archive_content(): string {
		return '<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
	<!-- wp:heading {"level":1} -->
	<h1 class="wp-block-heading">' . esc_html__( 'Course catalog', 'burbank-lms' ) . '</h1>
	<!-- /wp:heading -->
	<!-- wp:burbank-lms/catalog /-->
</div>
<!-- /wp:group -->';
	}

	/** Block markup for a single course. */
	private function single_content(): string {
		return '<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
	<!-- wp:post-title {"level":1} /-->
	<!-- wp:post-excerpt /-->
	<!-- wp:burbank-lms/course /-->
</div>
<!-- /wp:group -->';
	}
}
