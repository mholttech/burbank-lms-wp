<?php
/**
 * Server render for the standalone enroll block.
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
use Burbank_LMS\Learner\Catalog;

$course_id = isset( $attributes['courseId'] ) ? absint( $attributes['courseId'] ) : 0;
$user_id   = get_current_user_id();
$can_learn = $user_id > 0 && ( new Learner() )->can_learn( $user_id );
$courses   = ( new Catalog() )->query();
$wrapper   = get_block_wrapper_attributes(
	array(
		'class'                => 'bfc-enroll',
		'data-bfc-rest-config' => Block_Context::rest_config_json(),
	)
);

if ( ! $can_learn ) {
	printf(
		'<div %1$s><p>%2$s</p></div>',
		$wrapper, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes().
		is_user_logged_in()
			? esc_html__( 'Ask a training administrator to grant training access before enrolling.', 'burbank-lms' )
			: esc_html__( 'Sign in with training access to enroll.', 'burbank-lms' )
	);
	return;
}

$restricted = $course_id > 0 && ( new Invites() )->course_has_active_invites( $course_id );
$active     = $course_id > 0 ? ( new Enrollment_Service() )->get_active( $user_id, $course_id ) : null;
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?>>
	<?php if ( $active ) : ?>
		<p><?php esc_html_e( 'You are already enrolled in this course.', 'burbank-lms' ); ?></p>
	<?php else : ?>
		<form class="bfc-enroll__form" novalidate>
			<p class="bfc-enroll__field">
				<label for="bfc-enroll-course"><?php esc_html_e( 'Course', 'burbank-lms' ); ?></label>
				<select id="bfc-enroll-course" name="course_id" required data-bfc-course-select>
					<option value=""><?php esc_html_e( 'Select a course', 'burbank-lms' ); ?></option>
					<?php foreach ( $courses as $course ) : ?>
						<option
							value="<?php echo esc_attr( (string) $course->ID ); ?>"
							<?php selected( $course_id, (int) $course->ID ); ?>
							data-restricted="<?php echo ( new Invites() )->course_has_active_invites( (int) $course->ID ) ? '1' : '0'; ?>"
						>
							<?php echo esc_html( get_the_title( $course ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			<p class="bfc-enroll__field bfc-enroll__invite" <?php echo $restricted ? '' : 'hidden'; ?> data-bfc-invite-wrap>
				<label for="bfc-enroll-invite"><?php esc_html_e( 'Course invite code', 'burbank-lms' ); ?></label>
				<input id="bfc-enroll-invite" name="course_invite" type="text" autocomplete="off">
			</p>
			<p class="bfc-enroll__actions">
				<button type="submit"><?php esc_html_e( 'Enroll', 'burbank-lms' ); ?></button>
			</p>
			<p class="bfc-enroll__status" hidden data-bfc-status></p>
		</form>
	<?php endif; ?>
</div>
