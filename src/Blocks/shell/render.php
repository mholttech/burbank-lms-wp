<?php
/**
 * Server render for the optional learner shell block.
 *
 * Prints once-per-request shell chrome, then InnerBlocks content.
 * Learner blocks may also call Shell::render_chrome; the once-flag
 * prevents duplicate nav when both paths run.
 *
 * @package Burbank_LMS
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string   $content    InnerBlocks serialized content.
 * @var WP_Block $block      Block instance.
 */

use Burbank_LMS\Learner\Shell;

$shell  = new Shell();
$chrome = $shell->render_chrome();
echo $chrome; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shell escapes.

$wrapper = get_block_wrapper_attributes( array( 'class' => 'bfc-shell-block' ) );
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?>>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- InnerBlocks HTML. ?>
</div>
