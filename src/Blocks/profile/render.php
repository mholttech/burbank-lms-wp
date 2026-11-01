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

use Burbank_LMS\Access\Learner;
use Burbank_LMS\Access\Profile;
use Burbank_LMS\Enrollment\Service as Enrollment_Service;
use Burbank_LMS\Learner\Block_Context;
use Burbank_LMS\Learner\Block_Title;
use Burbank_LMS\Learner\Shell;
use Burbank_LMS\Learner\Soft_Gate;

$user_id = get_current_user_id();
$gate    = new Soft_Gate();
if ( $gate->should_block( $user_id ) ) {
	printf(
		'<div %1$s>%2$s</div>',
		get_block_wrapper_attributes( array( 'class' => 'bfc-profile' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core helper.
		$gate->render( $gate->audience( $user_id ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Soft_Gate escapes.
	);
	return;
}

$shell  = new Shell();
$chrome = $shell->render_chrome(
	array(
		'current' => 'profile',
	)
);
echo $chrome; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shell escapes.

wp_enqueue_script( 'password-strength-meter' );
$view_handle = 'burbank-lms-profile-view-script';
$wp_scripts  = wp_scripts();
if ( isset( $wp_scripts->registered[ $view_handle ] ) ) {
	$wp_scripts->registered[ $view_handle ]->deps[] = 'password-strength-meter';
}

$user       = wp_get_current_user();
$legal_name = ( new Profile() )->get_legal_name( (int) $user->ID );
$first_name = (string) $user->first_name;
$last_name  = (string) $user->last_name;
if ( '' === $first_name && '' === $last_name && '' !== $legal_name ) {
	$parts      = explode( ' ', $legal_name, 2 );
	$first_name = $parts[0];
	$last_name  = $parts[1] ?? '';
}

$learner     = new Learner();
$can_learn   = $learner->can_learn( (int) $user->ID );
$access_text = $can_learn
	? __( 'Active', 'burbank-lms' )
	: ( $learner->is_suspended( (int) $user->ID ) ? __( 'Suspended', 'burbank-lms' ) : __( 'No access', 'burbank-lms' ) );

$enrollments = ( new Enrollment_Service() )->list_for_user( (int) $user->ID );
$enrolled_ct = 0;
foreach ( $enrollments as $row ) {
	if ( 'withdrawn' !== (string) $row->status ) {
		++$enrolled_ct;
	}
}

$display_options = array();
foreach (
	array(
		$user->user_login,
		$first_name,
		$last_name,
		trim( $first_name . ' ' . $last_name ),
		trim( $last_name . ' ' . $first_name ),
		(string) $user->nickname,
	) as $candidate
) {
	$candidate = trim( (string) $candidate );
	if ( '' !== $candidate && ! in_array( $candidate, $display_options, true ) ) {
		$display_options[] = $candidate;
	}
}
if ( ! in_array( $user->display_name, $display_options, true ) ) {
	$display_options[] = $user->display_name;
}

$cert_name = trim( $first_name . ' ' . $last_name );
if ( '' === $cert_name ) {
	$cert_name = $legal_name;
}

$wrapper = get_block_wrapper_attributes(
	array(
		'class'                => 'bfc-profile',
		'data-bfc-rest-config' => Block_Context::rest_config_json(),
	)
);
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?>>
	<?php
	echo ( new Block_Title() )->render( __( 'Profile', 'burbank-lms' ), 'bfc-profile__title' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block_Title escapes.
	?>
	<div class="bfc-profile__summary">
		<span class="bfc-profile__avatar"><?php echo get_avatar( (int) $user->ID, 96 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core avatar HTML. ?></span>
		<div class="bfc-profile__identity">
			<span class="bfc-profile__identity-name"><?php echo esc_html( $user->display_name ); ?></span>
			<span class="bfc-profile__identity-sub"><?php echo esc_html( $user->user_login ); ?> · <?php echo esc_html( $user->user_email ); ?></span>
		</div>
		<dl class="bfc-profile__facts">
			<div class="bfc-profile__fact">
				<dt><?php esc_html_e( 'Training access', 'burbank-lms' ); ?></dt>
				<dd><span class="bfc-profile__badge"><?php echo esc_html( $access_text ); ?></span></dd>
			</div>
			<div class="bfc-profile__fact">
				<dt><?php esc_html_e( 'Courses enrolled', 'burbank-lms' ); ?></dt>
				<dd><?php echo esc_html( (string) $enrolled_ct ); ?></dd>
			</div>
		</dl>
	</div>

	<h2 class="bfc-profile__section-title"><?php esc_html_e( 'Personal information', 'burbank-lms' ); ?></h2>
	<form
		class="bfc-profile__form"
		data-bfc-legal-name="<?php echo esc_attr( $legal_name ); ?>"
		data-bfc-username="<?php echo esc_attr( $user->user_login ); ?>"
		novalidate
	>
		<div class="bfc-profile__fields bfc-profile__fields--name">
			<p class="bfc-profile__field">
				<label for="bfc-profile-first-name"><?php esc_html_e( 'First name', 'burbank-lms' ); ?></label>
				<input id="bfc-profile-first-name" name="first_name" type="text" value="<?php echo esc_attr( $first_name ); ?>" required autocomplete="given-name">
			</p>
			<p class="bfc-profile__field">
				<label for="bfc-profile-last-name"><?php esc_html_e( 'Last name', 'burbank-lms' ); ?></label>
				<input id="bfc-profile-last-name" name="last_name" type="text" value="<?php echo esc_attr( $last_name ); ?>" required autocomplete="family-name">
			</p>
		</div>
		<p class="bfc-profile__cert-preview" data-bfc-cert-preview>
			<?php
			printf(
				/* translators: %s: name that will be printed on certificates. */
				esc_html__( 'On your certificates: %s', 'burbank-lms' ),
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Strong wrapper; certificate name is escaped.
				'<strong data-bfc-cert-name>' . esc_html( $cert_name ) . '</strong>'
			);
			?>
			<span class="bfc-profile__hint"><?php echo esc_html__( "Certificates you've already earned keep the name printed on them. Name changes are recorded.", 'burbank-lms' ); ?></span>
		</p>
		<p class="bfc-profile__field">
			<label for="bfc-profile-nickname"><?php esc_html_e( 'Nickname', 'burbank-lms' ); ?></label>
			<input id="bfc-profile-nickname" name="nickname" type="text" value="<?php echo esc_attr( (string) $user->nickname ); ?>" autocomplete="nickname">
		</p>
		<p class="bfc-profile__field">
			<label for="bfc-profile-display-name"><?php esc_html_e( 'Display name publicly as', 'burbank-lms' ); ?></label>
			<select id="bfc-profile-display-name" name="display_name" required>
				<?php foreach ( $display_options as $option ) : ?>
					<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $user->display_name, $option ); ?>><?php echo esc_html( $option ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p class="bfc-profile__field">
			<label for="bfc-profile-email"><?php esc_html_e( 'Email', 'burbank-lms' ); ?></label>
			<input id="bfc-profile-email" name="email" type="email" value="<?php echo esc_attr( $user->user_email ); ?>" required autocomplete="email">
		</p>
		<p class="bfc-profile__field">
			<span class="bfc-profile__label"><?php esc_html_e( 'Username', 'burbank-lms' ); ?></span>
			<span class="bfc-profile__username"><?php echo esc_html( $user->user_login ); ?></span>
			<span class="bfc-profile__hint"><?php esc_html_e( 'Usernames cannot be changed.', 'burbank-lms' ); ?></span>
		</p>
		<p class="bfc-profile__actions">
			<button type="submit" class="wp-element-button"><?php esc_html_e( 'Save profile', 'burbank-lms' ); ?></button>
		</p>
		<p class="bfc-profile__status" hidden data-bfc-status></p>
	</form>

	<h2 class="bfc-profile__section-title"><?php esc_html_e( 'Security', 'burbank-lms' ); ?></h2>
	<form class="bfc-profile__security-form" novalidate>
		<p class="bfc-profile__field">
			<label for="bfc-profile-password"><?php esc_html_e( 'New password', 'burbank-lms' ); ?></label>
			<input id="bfc-profile-password" name="password" type="password" autocomplete="new-password">
		</p>
		<p class="bfc-profile__field">
			<label for="bfc-profile-password-confirm"><?php esc_html_e( 'Confirm new password', 'burbank-lms' ); ?></label>
			<input id="bfc-profile-password-confirm" name="password_confirm" type="password" autocomplete="new-password">
		</p>
		<div class="bfc-profile__meter" hidden data-bfc-meter>
			<span class="bfc-profile__meter-bar" data-bfc-meter-bar></span>
		</div>
		<span class="bfc-profile__meter-label" hidden data-bfc-meter-label></span>
		<p class="bfc-profile__actions">
			<button type="submit" class="wp-element-button"><?php esc_html_e( 'Change password', 'burbank-lms' ); ?></button>
			<button type="button" class="bfc-profile__signout-others" data-bfc-signout-others><?php esc_html_e( 'Sign out other sessions', 'burbank-lms' ); ?></button>
		</p>
		<p class="bfc-profile__status" hidden data-bfc-security-status></p>
	</form>
</div>
