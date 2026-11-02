<?php
/**
 * Account menu shared by training and plugins that extend it.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Learner;

use Burbank_LMS\Access\Learner;

/**
 * Builds the portal menu and lets other plugins append items.
 */
class Menu {
	/**
	 * Remember which training page is current.
	 *
	 * @param string $current Training context used to mark Catalog, My Courses, or Profile.
	 */
	public function __construct( private string $current = '' ) {}

	/**
	 * Menu items after the burbank_lms_portal_menu filter.
	 *
	 * @return array<int, array{id:string,label:string,url:string,current:bool,priority:int}>
	 */
	public function items(): array {
		$items    = $this->training_items();
		$filtered = apply_filters( 'burbank_lms_portal_menu', $items );
		$items    = is_array( $filtered ) ? $filtered : $items;
		$clean    = array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) || empty( $item['id'] ) || empty( $item['label'] ) || empty( $item['url'] ) ) {
				continue;
			}
			$clean[] = array(
				'id'       => (string) $item['id'],
				'label'    => (string) $item['label'],
				'url'      => (string) $item['url'],
				'current'  => ! empty( $item['current'] ),
				'priority' => isset( $item['priority'] ) ? (int) $item['priority'] : 50,
			);
		}
		if ( is_user_logged_in() && array() !== $clean && ! in_array( 'logout', wp_list_pluck( $clean, 'id' ), true ) ) {
			$clean[] = array(
				'id'       => 'logout',
				'label'    => __( 'Log out', 'burbank-lms' ),
				'url'      => wp_logout_url( home_url( '/' ) ),
				'current'  => false,
				'priority' => 90,
			);
		}
		usort(
			$clean,
			static function ( array $a, array $b ): int {
				return $a['priority'] <=> $b['priority'];
			}
		);
		return $clean;
	}

	/**
	 * One pill row. Empty when this account has no training items and no plugin added any.
	 */
	public function render(): string {
		$items = $this->items();
		if ( array() === $items ) {
			return '';
		}
		$lis = '';
		foreach ( $items as $item ) {
			$lis .= sprintf(
				'<li class="bfc-shell__item%1$s%2$s" data-menu-id="%3$s"><a class="bfc-shell__link" href="%4$s"%5$s>%6$s</a></li>',
				$item['current'] ? ' is-current' : '',
				'logout' === $item['id'] ? ' bfc-shell__item--logout' : '',
				esc_attr( $item['id'] ),
				esc_url( $item['url'] ),
				$item['current'] ? ' aria-current="page"' : '',
				esc_html( $item['label'] )
			);
		}
		$duty = false;
		foreach ( $items as $item ) {
			if ( str_starts_with( $item['id'], 'duty-' ) ) {
				$duty = true;
				break;
			}
		}
		$label = $duty ? __( 'Training and Duty Officer', 'burbank-lms' ) : __( 'Training', 'burbank-lms' );
		return sprintf(
			'<nav class="bfc-portal bfc-shell" data-bfc-menu aria-label="%1$s"><ul class="bfc-shell__list">%2$s</ul></nav>',
			esc_attr( $label ),
			$lis
		);
	}

	/**
	 * Training destinations for someone who can learn.
	 *
	 * @return array<int, array{id:string,label:string,url:string,current:bool,priority:int}>
	 */
	private function training_items(): array {
		if ( ! ( new Learner() )->can_learn( get_current_user_id() ) ) {
			return array();
		}
		$highlight = 'course' === $this->current ? 'catalog' : $this->current;
		return array(
			array(
				'id'       => 'catalog',
				'label'    => __( 'Catalog', 'burbank-lms' ),
				'url'      => $this->catalog_url(),
				'current'  => 'catalog' === $highlight,
				'priority' => 10,
			),
			array(
				'id'       => 'my-courses',
				'label'    => __( 'My Courses', 'burbank-lms' ),
				'url'      => $this->page_url( 'my-courses' ),
				'current'  => 'my-courses' === $highlight,
				'priority' => 20,
			),
			array(
				'id'       => 'profile',
				'label'    => __( 'Profile', 'burbank-lms' ),
				'url'      => $this->page_url( 'profile' ),
				'current'  => 'profile' === $highlight,
				'priority' => 30,
			),
		);
	}

	/**
	 * Course catalog archive URL.
	 */
	private function catalog_url(): string {
		$link = get_post_type_archive_link( 'bfc_course' );
		return is_string( $link ) && '' !== $link ? $link : home_url( '/courses/' );
	}

	/**
	 * Learner page permalink, with a path fallback.
	 *
	 * @param string $path Page path.
	 */
	private function page_url( string $path ): string {
		$page = get_page_by_path( $path );
		if ( $page instanceof \WP_Post ) {
			$permalink = get_permalink( $page );
			if ( '' !== $permalink ) {
				return $permalink;
			}
		}
		return home_url( '/' . trim( $path, '/' ) . '/' );
	}
}
