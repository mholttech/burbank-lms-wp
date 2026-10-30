<?php
/**
 * Server render shell for the My Courses block (list loaded via REST).
 *
 * @package Burbank_LMS
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

use Burbank_LMS\Learner\Block_Context;

$wrapper = get_block_wrapper_attributes(
	array(
		'class'                => 'bfc-my-courses',
		'data-bfc-rest-config' => Block_Context::rest_config_json(),
	)
);

if ( ! is_user_logged_in() ) {
	printf(
		'<div %1$s><p>%2$s</p></div>',
		$wrapper, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes().
		esc_html__( 'Sign in to view your courses.', 'burbank-lms' )
	);
	return;
}
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?>>
	<p class="bfc-my-courses__loading" data-bfc-loading><?php esc_html_e( 'Loading your courses…', 'burbank-lms' ); ?></p>
	<ul class="bfc-my-courses__list" data-bfc-list hidden></ul>
	<p class="bfc-my-courses__empty" data-bfc-empty hidden><?php esc_html_e( 'You are not enrolled in any courses yet.', 'burbank-lms' ); ?></p>
	<p class="bfc-my-courses__status" hidden data-bfc-status></p>
</div>
