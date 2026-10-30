<?php
/**
 * Server render for the learner profile block.
 *
 * @package Burbank_LMS
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

use Burbank_LMS\Access\Profile;
use Burbank_LMS\Learner\Block_Context;

if ( ! is_user_logged_in() ) {
	printf(
		'<div %1$s><p>%2$s</p></div>',
		get_block_wrapper_attributes( array( 'class' => 'bfc-profile' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core helper.
		esc_html__( 'Sign in to manage your profile.', 'burbank-lms' )
	);
	return;
}

$user       = wp_get_current_user();
$legal_name = ( new Profile() )->get_legal_name( (int) $user->ID );
$wrapper    = get_block_wrapper_attributes(
	array(
		'class'                => 'bfc-profile',
		'data-bfc-rest-config' => Block_Context::rest_config_json(),
	)
);
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?>>
	<form class="bfc-profile__form" novalidate>
		<p class="bfc-profile__field">
			<label for="bfc-profile-legal-name"><?php esc_html_e( 'Legal / certificate name', 'burbank-lms' ); ?></label>
			<input id="bfc-profile-legal-name" type="text" value="<?php echo esc_attr( $legal_name ); ?>" readonly disabled>
			<span class="bfc-profile__hint"><?php esc_html_e( 'Contact a training administrator to correct your legal name.', 'burbank-lms' ); ?></span>
		</p>
		<p class="bfc-profile__field">
			<label for="bfc-profile-display-name"><?php esc_html_e( 'Display name', 'burbank-lms' ); ?></label>
			<input id="bfc-profile-display-name" name="display_name" type="text" value="<?php echo esc_attr( $user->display_name ); ?>" required autocomplete="nickname">
		</p>
		<p class="bfc-profile__field">
			<label for="bfc-profile-email"><?php esc_html_e( 'Email', 'burbank-lms' ); ?></label>
			<input id="bfc-profile-email" name="email" type="email" value="<?php echo esc_attr( $user->user_email ); ?>" required autocomplete="email">
		</p>
		<p class="bfc-profile__field">
			<label for="bfc-profile-password"><?php esc_html_e( 'New password', 'burbank-lms' ); ?></label>
			<input id="bfc-profile-password" name="password" type="password" autocomplete="new-password" placeholder="<?php esc_attr_e( 'Leave blank to keep current password', 'burbank-lms' ); ?>">
		</p>
		<p class="bfc-profile__actions">
			<button type="submit"><?php esc_html_e( 'Save profile', 'burbank-lms' ); ?></button>
		</p>
		<p class="bfc-profile__status" hidden data-bfc-status></p>
	</form>
</div>
