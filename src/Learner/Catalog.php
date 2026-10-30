<?php
/**
 * Discoverable course catalog queries for learner blocks.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Learner;

/**
 * Returns published, non-archived courses for catalog and related surfaces.
 */
class Catalog {
	/**
	 * Query discoverable courses (published WordPress status, not archived).
	 *
	 * @param array<string, mixed> $args Optional WP_Query overrides.
	 * @return list<\WP_Post>
	 */
	public function query( array $args = array() ): array {
		$query_args = array_merge(
			array(
				'post_type'              => 'bfc_course',
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Catalog visibility filter.
					'relation' => 'OR',
					array(
						'key'     => 'catalog_state',
						'value'   => 'archived',
						'compare' => '!=',
					),
					array(
						'key'     => 'catalog_state',
						'compare' => 'NOT EXISTS',
					),
				),
			),
			$args
		);

		// Caller overrides must not escape the discoverability contract.
		$query_args['post_type']   = 'bfc_course';
		$query_args['post_status'] = 'publish';

		$query = new \WP_Query( $query_args );
		$posts = array();
		foreach ( $query->posts as $post ) {
			if ( $post instanceof \WP_Post ) {
				$posts[] = $post;
			}
		}
		return $posts;
	}
}
