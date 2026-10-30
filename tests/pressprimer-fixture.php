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
 * Approve a valid fixture using the real authorization and snapshot service.
 *
 * @param int $id Saved course ID.
 */
function bfc_test_approve( int $id ): void {
	( new Burbank_LMS\Courses\Editorial() )->approve( $id );
}

/** Remove committed course fixtures, including courses whose approval history blocks deletion. */
function bfc_test_purge_courses(): void {
	global $wpdb;
	$ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'bfc_course'" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
	foreach ( $ids as $id ) {
		if ( ! wp_delete_post( (int) $id, true ) ) {
			$wpdb->delete( $wpdb->postmeta, array( 'post_id' => (int) $id ) ); // phpcs:ignore WordPress.DB -- Retained by policy; tests purge the committed rows.
			$wpdb->delete( $wpdb->posts, array( 'ID' => (int) $id ) ); // phpcs:ignore WordPress.DB -- Retained by policy; tests purge the committed rows.
			clean_post_cache( (int) $id );
		}
	}
}
