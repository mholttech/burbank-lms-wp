<?php
/**
 * Server render for the single-course learner block.
 *
 * @package Burbank_LMS
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

use Burbank_LMS\Access\Invites;
use Burbank_LMS\Access\Learner;
use Burbank_LMS\Enrollment\Service as Enrollment_Service;
use Burbank_LMS\Integrations\PressPrimer_Quiz;
use Burbank_LMS\Learner\Block_Context;
use Burbank_LMS\Learner\Presentation;
use Burbank_LMS\Learner\Shell;
use Burbank_LMS\Learner\Soft_Gate;

$course_id = isset( $attributes['courseId'] ) ? absint( $attributes['courseId'] ) : 0;
if ( $course_id < 1 ) {
	$course_id = (int) get_the_ID();
}
$course_post = get_post( $course_id );
if ( ! $course_post instanceof \WP_Post || 'bfc_course' !== $course_post->post_type ) {
	printf(
		'<div %1$s><p>%2$s</p></div>',
		get_block_wrapper_attributes( array( 'class' => 'bfc-course' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core helper.
		esc_html__( 'Course not found.', 'burbank-lms' )
	);
	return;
}

$user_id = get_current_user_id();
$gate    = new Soft_Gate();
if ( $gate->should_block( $user_id ) ) {
	printf(
		'<div %1$s>%2$s</div>',
		get_block_wrapper_attributes( array( 'class' => 'bfc-course' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core helper.
		$gate->render( $gate->audience( $user_id ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Soft_Gate escapes.
	);
	return;
}

$shell  = new Shell();
$chrome = $shell->render_chrome(
	array(
		'current'   => 'course',
		'course_id' => $course_id,
	)
);
echo $chrome; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shell escapes.
if ( ( new \Burbank_LMS\Access\Learner() )->needs_email_verification( $user_id ) ) {
	echo $gate->render_verify( $user_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Soft_Gate escapes.
	return;
}


$can_learn     = $user_id > 0 && ( new Learner() )->can_learn( $user_id );
$restricted    = ( new Invites() )->course_has_active_invites( $course_id );
$enrollment    = new Enrollment_Service();
$row           = $user_id > 0 ? $enrollment->get( $user_id, $course_id ) : null;
$presentation  = new Presentation();
$status_key    = $presentation->enrollment_status( $row );
$status_text   = $presentation->status_label( $status_key );
$locked        = $enrollment->credential_locks_actions( $row, $course_id );
$retake        = $enrollment->retake_available( $row, $course_id );
$active        = $user_id > 0 ? $enrollment->get_active( $user_id, $course_id ) : null;
$quiz_id       = (int) get_post_meta( $course_id, 'pressprimer_quiz_id', true );
$code          = (string) get_post_meta( $course_id, 'bfc_code', true );
$subject       = $presentation->label( (string) get_post_meta( $course_id, 'subject_category', true ) );
$outcome       = $presentation->label( (string) get_post_meta( $course_id, 'course_outcome', true ) );
$quiz_embedded = (bool) $active && $quiz_id > 0;
$wrapper       = get_block_wrapper_attributes(
	array(
		'class'                => 'bfc-course',
		'data-bfc-rest-config' => Block_Context::rest_config_json(),
		'data-course-id'       => (string) $course_id,
	)
);
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?>>
	<span class="bfc-course__badge"><?php echo esc_html( 'none' === $status_key ? ( $restricted ? __( 'Invite required', 'burbank-lms' ) : __( 'Open enrollment', 'burbank-lms' ) ) : $presentation->label( $status_key ) ); ?></span>
	<h1 class="bfc-course__title"><?php echo esc_html( get_the_title( $course_id ) ); ?></h1>
	<div class="bfc-course__meta">
		<?php if ( $code ) : ?>
			<span class="bfc-course__code"><?php echo esc_html( $code ); ?></span>
		<?php endif; ?>
		<?php if ( $subject ) : ?>
			<span class="bfc-course__subject"><?php echo esc_html( $subject ); ?></span>
		<?php endif; ?>
		<?php if ( $outcome ) : ?>
			<span class="bfc-course__outcome"><?php echo esc_html( $outcome ); ?></span>
		<?php endif; ?>
	</div>

	<?php if ( has_excerpt( $course_id ) ) : ?>
		<p class="bfc-course__lede"><?php echo esc_html( get_the_excerpt( $course_id ) ); ?></p>
	<?php endif; ?>
	<?php if ( $quiz_embedded ) : ?>
		<div id="bfc-course-quiz" class="bfc-course__quiz-embed">
			<?php if ( shortcode_exists( 'pressprimer_quiz' ) ) : ?>
				<?php
				PressPrimer_Quiz::begin_course_embed( $course_id );
				$quiz_html = do_shortcode( '[pressprimer_quiz id="' . absint( $quiz_id ) . '"]' );
				PressPrimer_Quiz::end_course_embed();
				echo ( new PressPrimer_Quiz() )->demote_quiz_title( $quiz_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- PressPrimer shortcode HTML + demotion.
				?>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<div class="bfc-course__foot">
	<?php if ( $locked ) : ?>
		<p class="bfc-course__status"><?php echo esc_html( $status_text ); ?></p>
		<a class="bfc-course__action wp-element-button is-secondary" href="<?php echo esc_url( home_url( '/my-courses/' ) ); ?>"><?php esc_html_e( 'View certificates', 'burbank-lms' ); ?></a>
	<?php elseif ( $retake && ! $active && $can_learn && $restricted ) : ?>
		<p class="bfc-course__status"><?php esc_html_e( 'A new course version is available.', 'burbank-lms' ); ?></p>
		<form id="bfc-course-enroll" class="bfc-course__invite-form" novalidate>
			<p class="bfc-course__field">
				<label for="bfc-course-invite-<?php echo esc_attr( (string) $course_id ); ?>"><?php esc_html_e( 'Course invite code', 'burbank-lms' ); ?></label>
				<input id="bfc-course-invite-<?php echo esc_attr( (string) $course_id ); ?>" name="course_invite" type="text" required autocomplete="off" value="<?php echo esc_attr( \Burbank_LMS\Access\Invites::requested_code() ); ?>">
			</p>
			<p class="bfc-course__actions">
				<button type="submit" class="wp-element-button"><?php esc_html_e( 'Enroll in new version', 'burbank-lms' ); ?></button>
			</p>
		</form>
	<?php elseif ( $retake && ! $active && $can_learn ) : ?>
		<p class="bfc-course__status"><?php esc_html_e( 'A new course version is available.', 'burbank-lms' ); ?></p>
		<p id="bfc-course-enroll" class="bfc-course__actions">
			<button type="button" class="bfc-course__enroll wp-element-button" data-bfc-enroll="<?php echo esc_attr( (string) $course_id ); ?>">
				<?php esc_html_e( 'Enroll in new version', 'burbank-lms' ); ?>
			</button>
		</p>
	<?php elseif ( $quiz_embedded ) : ?>
		<button type="button" class="bfc-course__action wp-element-button is-secondary" data-bfc-withdraw><?php esc_html_e( 'Withdraw', 'burbank-lms' ); ?></button>
	<?php elseif ( ! $active && $can_learn && $restricted ) : ?>
		<?php if ( 'none' !== $status_key && '' !== $status_text ) : ?>
			<p class="bfc-course__status"><?php echo esc_html( $status_text ); ?></p>
		<?php endif; ?>
		<form id="bfc-course-enroll" class="bfc-course__invite-form" novalidate>
			<p class="bfc-course__field">
				<label for="bfc-course-invite-<?php echo esc_attr( (string) $course_id ); ?>"><?php esc_html_e( 'Course invite code', 'burbank-lms' ); ?></label>
				<input id="bfc-course-invite-<?php echo esc_attr( (string) $course_id ); ?>" name="course_invite" type="text" required autocomplete="off" value="<?php echo esc_attr( \Burbank_LMS\Access\Invites::requested_code() ); ?>">
			</p>
			<p class="bfc-course__actions">
				<button type="submit" class="wp-element-button"><?php esc_html_e( 'Enroll with invite', 'burbank-lms' ); ?></button>
			</p>
		</form>
	<?php elseif ( ! $active && $can_learn ) : ?>
		<?php if ( 'none' !== $status_key && '' !== $status_text ) : ?>
			<p class="bfc-course__status"><?php echo esc_html( $status_text ); ?></p>
		<?php endif; ?>
		<p id="bfc-course-enroll" class="bfc-course__actions">
			<button type="button" class="bfc-course__enroll wp-element-button" data-bfc-enroll="<?php echo esc_attr( (string) $course_id ); ?>">
				<?php esc_html_e( 'Enroll', 'burbank-lms' ); ?>
			</button>
		</p>
	<?php elseif ( ! $active && ! is_user_logged_in() ) : ?>
		<p><?php esc_html_e( 'Sign in with training access to enroll.', 'burbank-lms' ); ?></p>
	<?php elseif ( ! $active ) : ?>
		<p><?php esc_html_e( 'Ask a training administrator to grant training access before enrolling.', 'burbank-lms' ); ?></p>
	<?php elseif ( $active ) : ?>
		<?php if ( 'none' !== $status_key && '' !== $status_text ) : ?>
			<p class="bfc-course__status"><?php echo esc_html( $status_text ); ?></p>
		<?php endif; ?>
		<button type="button" class="bfc-course__action wp-element-button is-secondary" data-bfc-withdraw><?php esc_html_e( 'Withdraw', 'burbank-lms' ); ?></button>
	<?php endif; ?>
	</div>
	<p class="bfc-course__notice" hidden data-bfc-status></p>
</div>
