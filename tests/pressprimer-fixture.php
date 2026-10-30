<?php
/**
 * Real released-plugin fixtures.
 *
 * @package Burbank_LMS
 */

/** Create released mapping rows, or none when the optional plugins are intentionally absent. */
function bfc_test_mapping_or_empty(): array {
	return class_exists( 'PressPrimer_Quiz_Quiz' ) && class_exists( 'PressPrimer_Certificate_Template' ) ? bfc_test_mapping() : array();
}

/** Create released model rows and an exact quiz trigger. */
function bfc_test_mapping(): array {
	PressPrimer_Quiz_Activator::activate();
	PressPrimer_Certificate_Activator::activate();
	$quiz     = PressPrimer_Quiz_Quiz::create(
		array(
			'title'  => 'Scene safety',
			'status' => 'published',
		)
	);
	$template = PressPrimer_Certificate_Template::create(
		array(
			'title'  => 'Scene safety',
			'status' => 'published',
			'layout' => array( 'page' => array( 'size' => 'a4' ) ),
		)
	);
	PressPrimer_Certificate_Trigger::replace_for_template(
		$template,
		array(
			array(
				'trigger_type' => 'ppq_quiz',
				'source_ref'   => (string) $quiz,
				'is_active'    => 1,
			),
		)
	);
	return array(
		'pressprimer_quiz_id'     => $quiz,
		'certificate_template_id' => $template,
	);
}
/**
 * Simulate approval service persistence.
 *
 * @param int $id Saved course ID.
 */
function bfc_test_approve( int $id ): void {
	Burbank_LMS\Courses\Validator::internal_write(
		static function () use ( $id ): void {
			update_post_meta( $id, 'approved_by', get_current_user_id() );
			update_post_meta( $id, 'approved_at', gmdate( 'Y-m-d H:i:s' ) );
		}
	);
}
