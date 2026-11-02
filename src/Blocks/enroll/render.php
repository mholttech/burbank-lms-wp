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
if ( ( new Learner() )->needs_email_verification( $user_id ) ) {
	echo ( new \Burbank_LMS\Learner\Soft_Gate() )->render_verify( $user_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Soft_Gate escapes.
	return;
}
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

$enroll_svc = new Enrollment_Service();
$restricted = $course_id > 0 && ( new Invites() )->course_has_active_invites( $course_id );
$row        = $course_id > 0 ? $enroll_svc->get( $user_id, $course_id ) : null;
$locked     = $course_id > 0 && $enroll_svc->credential_locks_actions( $row, $course_id );
$retake     = $course_id > 0 && $enroll_svc->retake_available( $row, $course_id );
$active     = $course_id > 0 ? $enroll_svc->get_active( $user_id, $course_id ) : null;
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?>>
	<div class="bfc-enroll__foot">
	<?php if ( $locked ) : ?>
		<span class="bfc-enroll__badge"><?php esc_html_e( 'Completed', 'burbank-lms' ); ?></span>
		<a class="bfc-enroll__action wp-element-button is-secondary" href="<?php echo esc_url( get_permalink( $course_id ) ); ?>"><?php esc_html_e( 'View course', 'burbank-lms' ); ?></a>
	<?php elseif ( $active ) : ?>
		<span class="bfc-enroll__badge bfc-enroll__badge--enrolled"><?php esc_html_e( 'Enrolled', 'burbank-lms' ); ?></span>
		<a class="bfc-enroll__action wp-element-button" href="<?php echo esc_url( get_permalink( $course_id ) ); ?>"><?php esc_html_e( 'Continue course', 'burbank-lms' ); ?></a>
	<?php elseif ( $retake && $restricted ) : ?>
		<span class="bfc-enroll__badge"><?php esc_html_e( 'New version', 'burbank-lms' ); ?></span>
		<a class="bfc-enroll__action wp-element-button is-secondary" href="<?php echo esc_url( get_permalink( $course_id ) ); ?>"><?php esc_html_e( 'View course', 'burbank-lms' ); ?></a>
	<?php elseif ( $restricted ) : ?>
		<span class="bfc-enroll__badge bfc-enroll__badge--invite"><?php esc_html_e( 'Invite required', 'burbank-lms' ); ?></span>
		<a class="bfc-enroll__action wp-element-button is-secondary" href="<?php echo esc_url( get_permalink( $course_id ) ); ?>"><?php esc_html_e( 'View course', 'burbank-lms' ); ?></a>
	<?php else : ?>
		<span class="bfc-enroll__badge bfc-enroll__badge--open"><?php echo esc_html( $retake ? __( 'New version', 'burbank-lms' ) : __( 'Open enrollment', 'burbank-lms' ) ); ?></span>
		<form class="bfc-enroll__form" novalidate>
			<p class="bfc-enroll__field">
				<label for="bfc-enroll-course"><?php esc_html_e( 'Course', 'burbank-lms' ); ?></label>
				<select id="bfc-enroll-course" name="course_id" required data-bfc-course-select>
					<option value=""><?php esc_html_e( 'Select a course', 'burbank-lms' ); ?></option>
					<?php foreach ( $courses as $course ) : ?>
						<option
							value="<?php echo esc_attr( (string) $course->ID ); ?>"
							<?php selected( $course_id, (int) $course->ID ); ?>
							data-course-url="<?php echo esc_url( get_permalink( $course ) ); ?>" data-restricted="<?php echo ( new Invites() )->course_has_active_invites( (int) $course->ID ) ? '1' : '0'; ?>"
						>
							<?php echo esc_html( get_the_title( $course ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			<p class="bfc-enroll__field bfc-enroll__invite" hidden data-bfc-invite-wrap>
				<label for="bfc-enroll-invite"><?php esc_html_e( 'Course invite code', 'burbank-lms' ); ?></label>
				<input id="bfc-enroll-invite" name="course_invite" type="text" autocomplete="off" value="<?php echo esc_attr( Invites::requested_code() ); ?>">
			</p>
			<p class="bfc-enroll__actions">
				<button type="submit" class="wp-element-button"><?php echo esc_html( $retake ? __( 'Enroll in new version', 'burbank-lms' ) : __( 'Enroll', 'burbank-lms' ) ); ?></button>
			</p>
		</form>
	<?php endif; ?>
	</div>
	<p class="bfc-enroll__status" role="status" aria-live="polite" hidden data-bfc-status></p>
</div>
