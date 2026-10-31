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
use Burbank_LMS\Learner\Block_Context;
use Burbank_LMS\Learner\Presentation;

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

$user_id      = get_current_user_id();
$can_learn    = $user_id > 0 && ( new Learner() )->can_learn( $user_id );
$restricted   = ( new Invites() )->course_has_active_invites( $course_id );
$enrollment   = new Enrollment_Service();
$row          = $user_id > 0 ? $enrollment->get( $user_id, $course_id ) : null;
$presentation = new Presentation();
$status_key   = $presentation->enrollment_status( $row );
$status_text  = $presentation->status_label( $status_key );
$active       = $user_id > 0 ? $enrollment->get_active( $user_id, $course_id ) : null;
$quiz_id      = (int) get_post_meta( $course_id, 'pressprimer_quiz_id', true );
$code         = (string) get_post_meta( $course_id, 'bfc_code', true );
$subject      = $presentation->label( (string) get_post_meta( $course_id, 'subject_category', true ) );
$outcome      = $presentation->label( (string) get_post_meta( $course_id, 'course_outcome', true ) );
$revision     = (string) get_post_meta( $course_id, 'course_revision', true );
$quiz_cta     = 'passed' === $status_key
	? __( 'Continue to quiz', 'burbank-lms' )
	: __( 'Start quiz', 'burbank-lms' );
$wrapper      = get_block_wrapper_attributes(
	array(
		'class'                => 'bfc-course',
		'data-bfc-rest-config' => Block_Context::rest_config_json(),
		'data-course-id'       => (string) $course_id,
	)
);
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?>>
	<div class="bfc-course__meta">
		<?php if ( $code ) : ?>
			<p class="bfc-course__code"><strong><?php esc_html_e( 'Code:', 'burbank-lms' ); ?></strong> <?php echo esc_html( $code ); ?></p>
		<?php endif; ?>
		<?php if ( $subject ) : ?>
			<p class="bfc-course__subject"><?php echo esc_html( $subject ); ?></p>
		<?php endif; ?>
		<?php if ( $outcome ) : ?>
			<p class="bfc-course__outcome"><?php echo esc_html( $outcome ); ?></p>
		<?php endif; ?>
		<?php if ( $revision ) : ?>
			<p class="bfc-course__revision"><strong><?php esc_html_e( 'Revision:', 'burbank-lms' ); ?></strong> <?php echo esc_html( $revision ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( 'none' !== $status_key && '' !== $status_text ) : ?>
		<p class="bfc-course__status"><?php echo esc_html( $status_text ); ?></p>
	<?php endif; ?>

	<?php if ( $active && $quiz_id > 0 ) : ?>
		<p class="bfc-course__actions">
			<a class="bfc-course__quiz-cta wp-element-button" href="#bfc-course-quiz"><?php echo esc_html( $quiz_cta ); ?></a>
		</p>
		<div id="bfc-course-quiz" class="bfc-course__quiz-embed">
			<?php if ( shortcode_exists( 'pressprimer_quiz' ) ) : ?>
				<?php echo do_shortcode( '[pressprimer_quiz id="' . absint( $quiz_id ) . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- PressPrimer shortcode HTML. ?>
			<?php endif; ?>
		</div>
	<?php elseif ( ! $active && $can_learn && $restricted ) : ?>
		<form class="bfc-course__invite-form" novalidate>
			<p class="bfc-course__field">
				<label for="bfc-course-invite-<?php echo esc_attr( (string) $course_id ); ?>"><?php esc_html_e( 'Course invite code', 'burbank-lms' ); ?></label>
				<input id="bfc-course-invite-<?php echo esc_attr( (string) $course_id ); ?>" name="course_invite" type="text" required autocomplete="off">
			</p>
			<p class="bfc-course__actions">
				<button type="submit" class="wp-element-button"><?php esc_html_e( 'Enroll with invite', 'burbank-lms' ); ?></button>
			</p>
		</form>
	<?php elseif ( ! $active && $can_learn ) : ?>
		<p class="bfc-course__actions">
			<button type="button" class="bfc-course__enroll wp-element-button" data-bfc-enroll="<?php echo esc_attr( (string) $course_id ); ?>">
				<?php esc_html_e( 'Enroll', 'burbank-lms' ); ?>
			</button>
		</p>
	<?php elseif ( ! $active && ! is_user_logged_in() ) : ?>
		<p><?php esc_html_e( 'Sign in with training access to enroll.', 'burbank-lms' ); ?></p>
	<?php elseif ( ! $active ) : ?>
		<p><?php esc_html_e( 'Ask a training administrator to grant training access before enrolling.', 'burbank-lms' ); ?></p>
	<?php endif; ?>
	<p class="bfc-course__notice" hidden data-bfc-status></p>
</div>
