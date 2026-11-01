<?php
/**
 * Course materials access boundary.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Learner;

use Burbank_LMS\Access\Learner;

/** Suppresses course materials before inner blocks or shortcodes execute. */
class Course_Content {
	/**
	 * Gate core/post-content for course posts, leaving the course block to present the gate.
	 *
	 * @param string|null          $pre_render Existing short-circuit value.
	 * @param array<string, mixed> $block Parsed block.
	 * @param \WP_Block|null       $parent_block Parent with post context.
	 * @return string|null
	 */
	public function guard( ?string $pre_render, array $block, ?\WP_Block $parent_block = null ): ?string {
		if ( is_admin() || 'core/post-content' !== ( $block['blockName'] ?? '' ) ) {
			return $pre_render;
		}
		$post_id = $parent_block ? (int) ( $parent_block->context['postId'] ?? get_the_ID() ) : (int) get_the_ID();
		if ( 'bfc_course' !== get_post_type( $post_id ) ) {
			return $pre_render;
		}
		$learner = new Learner();
		$user_id = get_current_user_id();
		return ! $learner->can_learn( $user_id ) || $learner->needs_email_verification( $user_id ) ? '' : $pre_render;
	}
}
