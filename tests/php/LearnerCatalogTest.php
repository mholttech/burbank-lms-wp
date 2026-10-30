<?php
/**
 * Learner catalog query coverage.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Learner\Catalog;

/** Ensures Catalog::query returns published non-archived courses only. */
class LearnerCatalogTest extends WP_UnitTestCase {
	/**
	 * Create a bfc_course fixture (SQL publish bypasses publication guards).
	 *
	 * @param string $status        Desired post status.
	 * @param string $catalog_state Catalog state meta.
	 * @param string $title         Post title.
	 */
	private function course( string $status, string $catalog_state, string $title ): int {
		$id = (int) self::factory()->post->create(
			array(
				'post_type'   => 'bfc_course',
				'post_status' => 'draft',
				'post_title'  => $title,
				'meta_input'  => array(
					'catalog_state' => $catalog_state,
				),
			)
		);
		if ( 'publish' === $status ) {
			global $wpdb;
			$wpdb->update( // phpcs:ignore WordPress.DB -- Bypass publication guards for catalog fixtures.
				$wpdb->posts,
				array( 'post_status' => 'publish' ),
				array( 'ID' => $id ),
				array( '%s' ),
				array( '%d' )
			);
			clean_post_cache( $id );
		}
		return $id;
	}

	/** Discoverable courses exclude drafts and archived catalog state. */
	public function test_query_returns_published_non_archived_only(): void {
		$visible  = $this->course( 'publish', 'published', 'Alpha Course' );
		$archived = $this->course( 'publish', 'archived', 'Archived Course' );
		$draft    = $this->course( 'draft', 'draft', 'Draft Course' );

		$ids = wp_list_pluck( ( new Catalog() )->query(), 'ID' );

		$this->assertContains( $visible, $ids );
		$this->assertNotContains( $archived, $ids );
		$this->assertNotContains( $draft, $ids );
	}
}
