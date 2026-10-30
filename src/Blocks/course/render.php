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

$user_id    = get_current_user_id();
$can_learn  = $user_id > 0 && ( new Learner() )->can_learn( $user_id );
$restricted = ( new Invites() )->course_has_active_invites( $course_id );
$active     = $can_learn ? ( new Enrollment_Service() )->get_active( $user_id, $course_id ) : null;
$quiz_id    = (int) get_post_meta( $course_id, 'pressprimer_quiz_id', true );
$quiz_url   = Block_Context::quiz_url( $course_id );
$outcome    = (string) get_post_meta( $course_id, 'course_outcome', true );
$code       = (string) get_post_meta( $course_id, 'bfc_code', true );
$wrapper    = get_block_wrapper_attributes(
	array(
		'class'                => 'bfc-course',
		'data-bfc-rest-config' => Block_Context::rest_config_json(),
		'data-course-id'       => (string) $course_id,
	)
);
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?>>
	<?php if ( $code ) : ?>
		<p class="bfc-course__code"><strong><?php esc_html_e( 'Code:', 'burbank-lms' ); ?></strong> <?php echo esc_html( $code ); ?></p>
	<?php endif; ?>
	<?php if ( $outcome ) : ?>
		<p class="bfc-course__outcome"><?php echo esc_html( $outcome ); ?></p>
	<?php endif; ?>

	<?php if ( $active ) : ?>
		<p class="bfc-course__status"><?php esc_html_e( 'You are enrolled in this course.', 'burbank-lms' ); ?></p>
		<?php if ( $quiz_id > 0 && $quiz_url ) : ?>
			<p class="bfc-course__quiz">
				<a href="<?php echo esc_url( $quiz_url ); ?>"><?php esc_html_e( 'Take quiz', 'burbank-lms' ); ?></a>
			</p>
			<?php if ( shortcode_exists( 'pressprimer_quiz' ) ) : ?>
				<div id="bfc-course-quiz" class="bfc-course__quiz-embed">
					<?php echo do_shortcode( '[pressprimer_quiz id="' . absint( $quiz_id ) . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- PressPrimer shortcode HTML. ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	<?php elseif ( $can_learn && $restricted ) : ?>
		<form class="bfc-course__invite-form" novalidate>
			<p class="bfc-course__field">
				<label for="bfc-course-invite-<?php echo esc_attr( (string) $course_id ); ?>"><?php esc_html_e( 'Course invite code', 'burbank-lms' ); ?></label>
				<input id="bfc-course-invite-<?php echo esc_attr( (string) $course_id ); ?>" name="course_invite" type="text" required autocomplete="off">
			</p>
			<p class="bfc-course__actions">
				<button type="submit"><?php esc_html_e( 'Enroll with invite', 'burbank-lms' ); ?></button>
			</p>
		</form>
	<?php elseif ( $can_learn ) : ?>
		<p class="bfc-course__actions">
			<button type="button" class="bfc-course__enroll" data-bfc-enroll="<?php echo esc_attr( (string) $course_id ); ?>">
				<?php esc_html_e( 'Enroll', 'burbank-lms' ); ?>
			</button>
		</p>
	<?php elseif ( ! is_user_logged_in() ) : ?>
		<p><?php esc_html_e( 'Sign in with training access to enroll.', 'burbank-lms' ); ?></p>
	<?php else : ?>
		<p><?php esc_html_e( 'Verify your email and training access before enrolling.', 'burbank-lms' ); ?></p>
	<?php endif; ?>
	<p class="bfc-course__notice" hidden data-bfc-status></p>
</div>
