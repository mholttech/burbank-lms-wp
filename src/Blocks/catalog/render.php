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
use Burbank_LMS\Learner\Presentation;
use Burbank_LMS\Learner\Shell;
use Burbank_LMS\Learner\Soft_Gate;

$user_id = get_current_user_id();
$gate    = new Soft_Gate();
if ( $gate->should_block( $user_id ) ) {
	printf(
		'<div %1$s>%2$s</div>',
		get_block_wrapper_attributes( array( 'class' => 'bfc-catalog' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core helper.
		$gate->render( $gate->audience( $user_id ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Soft_Gate escapes.
	);
	return;
}

$shell  = new Shell();
$chrome = $shell->render_chrome(
	array(
		'current' => 'catalog',
	)
);
echo $chrome; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shell escapes.
if ( ( new \Burbank_LMS\Access\Learner() )->needs_email_verification( $user_id ) ) {
	echo $gate->render_verify( $user_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Soft_Gate escapes.
	return;
}


$courses      = ( new Catalog() )->query();
$can_learn    = $user_id > 0 && ( new Learner() )->can_learn( $user_id );
$invites      = new Invites();
$enroll       = new Enrollment_Service();
$presentation = new Presentation();
$wrapper      = get_block_wrapper_attributes(
	array(
		'class'                => 'bfc-catalog',
		'data-bfc-rest-config' => Block_Context::rest_config_json(),
	)
);

$subjects = array();
foreach ( $courses as $course_for_subject ) {
	$slug = (string) get_post_meta( (int) $course_for_subject->ID, 'subject_category', true );
	if ( '' !== $slug ) {
		$subjects[ $slug ] = $presentation->label( $slug );
	}
}
ksort( $subjects, SORT_NATURAL | SORT_FLAG_CASE );
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?>>
	<?php if ( empty( $courses ) ) : ?>
		<p><?php esc_html_e( 'No courses are available yet.', 'burbank-lms' ); ?></p>
	<?php else : ?>
		<?php
		$search_id  = wp_unique_id( 'bfc-catalog-search-' );
		$subject_id = wp_unique_id( 'bfc-catalog-subject-' );
		?>
		<div class="bfc-catalog__head">
			<h2 class="bfc-catalog__heading"><?php esc_html_e( 'Course catalog', 'burbank-lms' ); ?></h2>
			<span class="bfc-catalog__count">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: number of courses in the catalog. */
						_n( '%d course available', '%d courses available', count( $courses ), 'burbank-lms' ),
						count( $courses )
					)
				);
				?>
			</span>
		</div>
		<div class="bfc-catalog__filters">
			<p class="bfc-catalog__field">
				<label for="<?php echo esc_attr( $search_id ); ?>"><?php esc_html_e( 'Search courses', 'burbank-lms' ); ?></label>
				<input
					id="<?php echo esc_attr( $search_id ); ?>"
					type="search"
					class="bfc-catalog__search"
					data-bfc-catalog-search
					placeholder="<?php esc_attr_e( 'Search by title or code', 'burbank-lms' ); ?>"
					autocomplete="off"
				>
			</p>
			<p class="bfc-catalog__field">
				<label for="<?php echo esc_attr( $subject_id ); ?>"><?php esc_html_e( 'Subject', 'burbank-lms' ); ?></label>
				<select id="<?php echo esc_attr( $subject_id ); ?>" class="bfc-catalog__subject" data-bfc-catalog-subject>
					<option value=""><?php esc_html_e( 'All subjects', 'burbank-lms' ); ?></option>
					<?php foreach ( $subjects as $slug => $label ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
		</div>
		<ul class="bfc-catalog__list">
			<?php foreach ( $courses as $course ) : ?>
				<?php
				$course_id    = (int) $course->ID;
				$permalink    = (string) get_permalink( $course );
				$course_title = get_the_title( $course );
				$code         = (string) get_post_meta( $course_id, 'bfc_code', true );
				$subject      = (string) get_post_meta( $course_id, 'subject_category', true );
				$outcome      = (string) get_post_meta( $course_id, 'course_outcome', true );
				$restricted   = $invites->course_has_active_invites( $course_id );
				$row          = $can_learn ? $enroll->get( $user_id, $course_id ) : null;
				$status_key   = $presentation->enrollment_status( $row );
				$locked       = $enroll->credential_locks_actions( $row, $course_id );
				$retake       = $enroll->retake_available( $row, $course_id );
				?>
				<li
					class="bfc-catalog__item"
					data-course-id="<?php echo esc_attr( (string) $course_id ); ?>"
					data-bfc-title="<?php echo esc_attr( $course_title ); ?>"
					data-bfc-code="<?php echo esc_attr( $code ); ?>"
					data-bfc-subject="<?php echo esc_attr( $subject ); ?>"
				>
					<?php if ( $subject ) : ?>
						<span class="bfc-catalog__badge"><?php echo esc_html( $presentation->label( $subject ) ); ?></span>
					<?php endif; ?>
					<a class="bfc-catalog__title" href="<?php echo esc_url( $permalink ); ?>">
						<?php echo esc_html( $course_title ); ?>
					</a>
					<div class="bfc-catalog__meta">
						<?php if ( $code ) : ?>
							<span class="bfc-catalog__code"><?php echo esc_html( $code ); ?></span>
						<?php endif; ?>
						<?php if ( $outcome ) : ?>
							<span class="bfc-catalog__outcome"><?php echo esc_html( $presentation->label( $outcome ) ); ?></span>
						<?php endif; ?>
					</div>
					<?php if ( $course->post_excerpt ) : ?>
						<p class="bfc-catalog__excerpt"><?php echo esc_html( wp_strip_all_tags( $course->post_excerpt ) ); ?></p>
					<?php endif; ?>
					<div class="bfc-catalog__foot">
						<?php if ( in_array( $status_key, array( 'enrolled', 'passed' ), true ) ) : ?>
							<span class="bfc-catalog__chip bfc-catalog__chip--enrolled"><?php esc_html_e( 'Enrolled', 'burbank-lms' ); ?></span>
							<a class="bfc-catalog__cta bfc-catalog__cta--continue" href="<?php echo esc_url( $permalink ); ?>">
								<?php esc_html_e( 'Continue course', 'burbank-lms' ); ?>
							</a>
						<?php elseif ( $locked ) : ?>
							<span class="bfc-catalog__chip bfc-catalog__chip--completed"><?php esc_html_e( 'Completed', 'burbank-lms' ); ?></span>
							<a class="bfc-catalog__cta bfc-catalog__cta--continue" href="<?php echo esc_url( $permalink ); ?>">
								<?php esc_html_e( 'View course', 'burbank-lms' ); ?>
							</a>
						<?php elseif ( $retake && $can_learn && $restricted ) : ?>
							<span class="bfc-catalog__chip bfc-catalog__chip--completed"><?php esc_html_e( 'New version', 'burbank-lms' ); ?></span>
							<a class="bfc-catalog__cta wp-element-button is-secondary" href="<?php echo esc_url( $permalink ); ?>">
								<?php esc_html_e( 'View course', 'burbank-lms' ); ?>
							</a>
						<?php elseif ( $retake && $can_learn ) : ?>
							<span class="bfc-catalog__chip bfc-catalog__chip--completed"><?php esc_html_e( 'New version', 'burbank-lms' ); ?></span>
							<button type="button" class="bfc-catalog__enroll wp-element-button" data-bfc-enroll="<?php echo esc_attr( (string) $course_id ); ?>">
								<?php esc_html_e( 'Enroll in new version', 'burbank-lms' ); ?>
							</button>
						<?php elseif ( $can_learn && $restricted ) : ?>
							<span class="bfc-catalog__chip bfc-catalog__chip--invite"><?php esc_html_e( 'Invite required', 'burbank-lms' ); ?></span>
							<a class="bfc-catalog__cta wp-element-button is-secondary" href="<?php echo esc_url( $permalink ); ?>">
								<?php esc_html_e( 'View course', 'burbank-lms' ); ?>
							</a>
						<?php elseif ( $can_learn ) : ?>
							<span class="bfc-catalog__open"><?php esc_html_e( 'Open enrollment', 'burbank-lms' ); ?></span>
							<button type="button" class="bfc-catalog__enroll wp-element-button" data-bfc-enroll="<?php echo esc_attr( (string) $course_id ); ?>">
								<?php esc_html_e( 'Enroll', 'burbank-lms' ); ?>
							</button>
						<?php endif; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="bfc-catalog__empty-filter" hidden data-bfc-catalog-empty-filter>
			<?php esc_html_e( 'No courses match your filters.', 'burbank-lms' ); ?>
		</p>
		<p class="bfc-catalog__status" hidden data-bfc-status></p>
	<?php endif; ?>
</div>
