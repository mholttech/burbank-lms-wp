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
use Burbank_LMS\Learner\Block_Title;
use Burbank_LMS\Learner\Shell;
use Burbank_LMS\Learner\Soft_Gate;

$user_id = get_current_user_id();
$gate    = new Soft_Gate();
if ( $gate->should_block( $user_id ) ) {
	printf(
		'<div %1$s>%2$s</div>',
		get_block_wrapper_attributes( array( 'class' => 'bfc-my-courses' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core helper.
		$gate->render( $gate->audience( $user_id ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Soft_Gate escapes.
	);
	return;
}

$shell  = new Shell();
$chrome = $shell->render_chrome(
	array(
		'current' => 'my-courses',
	)
);
echo $chrome; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shell escapes.

$wrapper   = get_block_wrapper_attributes(
	array(
		'class'                => 'bfc-my-courses',
		'data-bfc-rest-config' => Block_Context::rest_config_json(),
	)
);
$archive   = get_post_type_archive_link( 'bfc_course' );
$has_certs = shortcode_exists( 'ppcert_my_certificates' );
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?>>
	<?php
	echo ( new Block_Title() )->render( __( 'My courses', 'burbank-lms' ), 'bfc-my-courses__heading' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block_Title escapes.
	?>
	<ul class="bfc-my-courses__stats<?php echo $has_certs ? '' : ' bfc-my-courses__stats--no-certs'; ?>" data-bfc-stats hidden>
		<li class="bfc-my-courses__stat">
			<span class="bfc-my-courses__stat-value" data-bfc-stat="enrolled">–</span>
			<span class="bfc-my-courses__stat-label"><?php esc_html_e( 'Enrolled', 'burbank-lms' ); ?></span>
		</li>
		<li class="bfc-my-courses__stat">
			<span class="bfc-my-courses__stat-value" data-bfc-stat="in-progress">–</span>
			<span class="bfc-my-courses__stat-label"><?php esc_html_e( 'In progress', 'burbank-lms' ); ?></span>
		</li>
		<li class="bfc-my-courses__stat">
			<span class="bfc-my-courses__stat-value" data-bfc-stat="completed">–</span>
			<span class="bfc-my-courses__stat-label"><?php esc_html_e( 'Completed', 'burbank-lms' ); ?></span>
		</li>
		<?php if ( $has_certs ) : ?>
			<li class="bfc-my-courses__stat">
				<span class="bfc-my-courses__stat-value" data-bfc-stat="certificates">–</span>
				<span class="bfc-my-courses__stat-label"><?php esc_html_e( 'Certificates', 'burbank-lms' ); ?></span>
			</li>
		<?php endif; ?>
	</ul>
	<div class="bfc-my-courses__section-head">
		<h2 class="bfc-my-courses__section-title"><?php esc_html_e( 'Enrolled courses', 'burbank-lms' ); ?></h2>
		<?php if ( is_string( $archive ) && $archive ) : ?>
			<a class="bfc-my-courses__catalog-link" href="<?php echo esc_url( $archive ); ?>"><?php esc_html_e( 'Browse course catalog', 'burbank-lms' ); ?> <span aria-hidden="true">→</span></a>
		<?php endif; ?>
	</div>
	<p class="bfc-my-courses__loading" data-bfc-loading><?php esc_html_e( 'Loading your courses…', 'burbank-lms' ); ?></p>
	<ul class="bfc-my-courses__list" data-bfc-list hidden></ul>
	<div class="bfc-my-courses__empty" data-bfc-empty hidden>
		<p><?php esc_html_e( 'You are not enrolled in any courses yet.', 'burbank-lms' ); ?></p>
		<?php if ( is_string( $archive ) && $archive ) : ?>
			<a class="bfc-my-courses__action" href="<?php echo esc_url( $archive ); ?>"><?php esc_html_e( 'Browse course catalog', 'burbank-lms' ); ?></a>
		<?php endif; ?>
	</div>
	<p class="bfc-my-courses__status" hidden data-bfc-status></p>
	<?php if ( $has_certs ) : ?>
		<div class="bfc-my-courses__certificates">
			<div class="bfc-my-courses__section-head">
				<h2 class="bfc-my-courses__section-title"><?php esc_html_e( 'Certificates', 'burbank-lms' ); ?></h2>
				<span class="bfc-my-courses__cert-valid" data-bfc-cert-valid hidden></span>
			</div>
			<?php echo do_shortcode( '[ppcert_my_certificates]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- PressPrimer shortcode markup. ?>
		</div>
	<?php endif; ?>
</div>
