<?php
/**
 * Server render for the course catalog block.
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

$courses   = ( new Catalog() )->query();
$user_id   = get_current_user_id();
$can_learn = $user_id > 0 && ( new Learner() )->can_learn( $user_id );
$invites   = new Invites();
$enroll    = new Enrollment_Service();
$wrapper   = get_block_wrapper_attributes(
	array(
		'class'                => 'bfc-catalog',
		'data-bfc-rest-config' => Block_Context::rest_config_json(),
	)
);
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?>>
	<?php if ( empty( $courses ) ) : ?>
		<p><?php esc_html_e( 'No courses are available yet.', 'burbank-lms' ); ?></p>
	<?php else : ?>
		<ul class="bfc-catalog__list">
			<?php foreach ( $courses as $course ) : ?>
				<?php
				$course_id  = (int) $course->ID;
				$permalink  = (string) get_permalink( $course );
				$restricted = $invites->course_has_active_invites( $course_id );
				$active     = $can_learn ? $enroll->get_active( $user_id, $course_id ) : null;
				?>
				<li class="bfc-catalog__item" data-course-id="<?php echo esc_attr( (string) $course_id ); ?>">
					<a class="bfc-catalog__title" href="<?php echo esc_url( $permalink ); ?>">
						<?php echo esc_html( get_the_title( $course ) ); ?>
					</a>
					<?php if ( $course->post_excerpt ) : ?>
						<p class="bfc-catalog__excerpt"><?php echo esc_html( wp_strip_all_tags( $course->post_excerpt ) ); ?></p>
					<?php endif; ?>
					<?php if ( $active ) : ?>
						<p class="bfc-catalog__enrolled"><?php esc_html_e( 'Enrolled', 'burbank-lms' ); ?></p>
					<?php elseif ( $can_learn && ! $restricted ) : ?>
						<button type="button" class="bfc-catalog__enroll" data-bfc-enroll="<?php echo esc_attr( (string) $course_id ); ?>">
							<?php esc_html_e( 'Enroll', 'burbank-lms' ); ?>
						</button>
					<?php elseif ( $can_learn && $restricted ) : ?>
						<a class="bfc-catalog__cta" href="<?php echo esc_url( $permalink ); ?>">
							<?php esc_html_e( 'View course (invite required)', 'burbank-lms' ); ?>
						</a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="bfc-catalog__status" hidden data-bfc-status></p>
	<?php endif; ?>
</div>
