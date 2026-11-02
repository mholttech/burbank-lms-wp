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

use Burbank_LMS\Access\Invites;
use Burbank_LMS\Learner\Block_Context;
use Burbank_LMS\Learner\Block_Title;

$courses_url = home_url( '/my-courses/' );
$courses     = get_page_by_path( 'my-courses' );
if ( $courses instanceof WP_Post ) {
	$permalink = get_permalink( $courses );
	if ( is_string( $permalink ) && '' !== $permalink ) {
		$courses_url = $permalink;
	}
}
$login_url = wp_login_url( $courses_url );

if ( is_user_logged_in() ) {
	printf(
		'<div %1$s><div class="bfc-register__panel"><p>%2$s</p><p class="bfc-register__actions"><a class="wp-element-button" href="%3$s">%4$s</a> <a class="wp-element-button is-secondary" href="%5$s">%6$s</a></p></div></div>',
		get_block_wrapper_attributes( array( 'class' => 'bfc-register' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core helper escapes class/attrs.
		esc_html__( 'You are already signed in.', 'burbank-lms' ),
		esc_url( $courses_url ),
		esc_html__( 'My courses', 'burbank-lms' ),
		esc_url( wp_logout_url( home_url( '/register/' ) ) ),
		esc_html__( 'Log out', 'burbank-lms' )
	);
	return;
}

$invite_code = Invites::requested_code();
$wrapper     = get_block_wrapper_attributes(
	array(
		'class'                => 'bfc-register' . ( '' !== $invite_code ? ' has-invite' : '' ),
		'data-bfc-rest-config' => Block_Context::rest_config_json(),
	)
);
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?>>
	<?php
	echo ( new Block_Title() )->render( __( 'Create training account', 'burbank-lms' ), 'bfc-register__title' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block_Title escapes.
	?>
	<form class="bfc-register__form" novalidate>
		<fieldset class="bfc-register__section">
			<legend><?php esc_html_e( 'Invite', 'burbank-lms' ); ?></legend>
			<?php if ( '' !== $invite_code ) : ?>
				<p class="bfc-register__hint"><?php esc_html_e( 'Your invite link is filled in. You can paste a different code if this one is not yours.', 'burbank-lms' ); ?></p>
			<?php else : ?>
				<p class="bfc-register__hint"><?php esc_html_e( 'Paste the code from your training administrator, or open the invite link they sent.', 'burbank-lms' ); ?></p>
			<?php endif; ?>
			<p class="bfc-register__field">
				<label for="bfc-register-invite"><?php esc_html_e( 'Invite code', 'burbank-lms' ); ?></label>
				<input id="bfc-register-invite" name="invite" type="text" required autocomplete="off" spellcheck="false" value="<?php echo esc_attr( $invite_code ); ?>">
			</p>
		</fieldset>

		<fieldset class="bfc-register__section">
			<legend><?php esc_html_e( 'Name on certificates', 'burbank-lms' ); ?></legend>
			<div class="bfc-register__name">
				<p class="bfc-register__field">
					<label for="bfc-register-first-name"><?php esc_html_e( 'First name', 'burbank-lms' ); ?></label>
					<input id="bfc-register-first-name" name="first_name" type="text" required autocomplete="given-name">
				</p>
				<p class="bfc-register__field">
					<label for="bfc-register-last-name"><?php esc_html_e( 'Last name', 'burbank-lms' ); ?></label>
					<input id="bfc-register-last-name" name="last_name" type="text" required autocomplete="family-name">
				</p>
			</div>
			<p class="bfc-register__preview"><?php esc_html_e( 'On your certificates:', 'burbank-lms' ); ?> <strong data-bfc-cert-name></strong></p>
			<p class="bfc-register__field">
				<label for="bfc-register-display-name"><?php esc_html_e( 'Display name', 'burbank-lms' ); ?></label>
				<input id="bfc-register-display-name" name="display_name" type="text" required autocomplete="nickname">
				<span class="bfc-register__hint"><?php esc_html_e( 'How your name appears in training. Filled from your first and last name until you change it.', 'burbank-lms' ); ?></span>
			</p>
		</fieldset>

		<fieldset class="bfc-register__section">
			<legend><?php esc_html_e( 'Sign-in', 'burbank-lms' ); ?></legend>
			<p class="bfc-register__field">
				<label for="bfc-register-email"><?php esc_html_e( 'Email', 'burbank-lms' ); ?></label>
				<input id="bfc-register-email" name="email" type="email" required autocomplete="email">
			</p>
			<p class="bfc-register__field">
				<label for="bfc-register-password"><?php esc_html_e( 'Password', 'burbank-lms' ); ?></label>
				<span class="bfc-register__password">
					<input id="bfc-register-password" name="password" type="password" required autocomplete="new-password">
					<button type="button" class="bfc-register__reveal" aria-controls="bfc-register-password" aria-pressed="false"><?php esc_html_e( 'Show', 'burbank-lms' ); ?></button>
				</span>
			</p>
		</fieldset>

		<p class="bfc-register__actions">
			<button type="submit" class="wp-element-button" data-bfc-submit><?php esc_html_e( 'Create training account', 'burbank-lms' ); ?></button>
		</p>
		<p class="bfc-register__status" hidden data-bfc-status></p>
		<p class="bfc-register__switch"><?php esc_html_e( 'Already have an account?', 'burbank-lms' ); ?> <a href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Sign in', 'burbank-lms' ); ?></a></p>
	</form>
	<div class="bfc-register__done" hidden data-bfc-done>
		<h2><?php esc_html_e( 'Account created', 'burbank-lms' ); ?></h2>
		<p><?php esc_html_e( 'Check your email to verify your address, then sign in to open your courses.', 'burbank-lms' ); ?></p>
		<p class="bfc-register__actions"><a class="wp-element-button" href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Sign in', 'burbank-lms' ); ?></a></p>
	</div>
</div>
