<?php
/**
 * Server render for the learner registration block.
 *
 * @package Burbank_LMS
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

use Burbank_LMS\Learner\Block_Context;

if ( is_user_logged_in() ) {
	printf(
		'<div %1$s><p>%2$s</p></div>',
		get_block_wrapper_attributes( array( 'class' => 'bfc-register' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core helper escapes class/attrs.
		esc_html__( 'You are already signed in.', 'burbank-lms' )
	);
	return;
}

$wrapper = get_block_wrapper_attributes(
	array(
		'class'                => 'bfc-register',
		'data-bfc-rest-config' => Block_Context::rest_config_json(),
	)
);
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?>>
	<form class="bfc-register__form" novalidate>
		<p class="bfc-register__field">
			<label for="bfc-register-invite"><?php esc_html_e( 'Site invite code', 'burbank-lms' ); ?></label>
			<input id="bfc-register-invite" name="invite" type="text" required autocomplete="off">
		</p>
		<p class="bfc-register__field">
			<label for="bfc-register-legal-name"><?php esc_html_e( 'Legal / certificate name', 'burbank-lms' ); ?></label>
			<input id="bfc-register-legal-name" name="legal_name" type="text" required autocomplete="name">
		</p>
		<p class="bfc-register__field">
			<label for="bfc-register-display-name"><?php esc_html_e( 'Display name', 'burbank-lms' ); ?></label>
			<input id="bfc-register-display-name" name="display_name" type="text" required autocomplete="nickname">
		</p>
		<p class="bfc-register__field">
			<label for="bfc-register-email"><?php esc_html_e( 'Email', 'burbank-lms' ); ?></label>
			<input id="bfc-register-email" name="email" type="email" required autocomplete="email">
		</p>
		<p class="bfc-register__field">
			<label for="bfc-register-password"><?php esc_html_e( 'Password', 'burbank-lms' ); ?></label>
			<input id="bfc-register-password" name="password" type="password" required autocomplete="new-password">
		</p>
		<p class="bfc-register__actions">
			<button type="submit" class="wp-element-button"><?php esc_html_e( 'Create account', 'burbank-lms' ); ?></button>
		</p>
		<p class="bfc-register__status" hidden data-bfc-status></p>
	</form>
</div>
