<?php
/**
 * Learner training shell: nav items and once-per-request chrome.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Learner;

use Burbank_LMS\Access\Learner;
use Burbank_LMS\Enrollment\Service as Enrollment_Service;

/** Builds hybrid training subnav and contextual course actions for can_learn users. */
class Shell {
	/**
	 * Whether chrome HTML has already been printed this request.
	 *
	 * @var bool
	 */
	private static bool $chrome_printed = false;

	/** Reset the once-per-request chrome flag (tests). */
	public static function reset_chrome_printed(): void {
		self::$chrome_printed = false;
	}

	/**
	 * Whether shell chrome should appear for this user.
	 *
	 * @param int $user_id WordPress user id (0 = guest).
	 */
	public function should_show( int $user_id ): bool {
		return $user_id > 0 && ( new Learner() )->can_learn( $user_id );
	}

	/**
	 * Detect the current shell context from the main query.
	 */
	public function detect_current(): string {
		if ( is_singular( 'bfc_course' ) ) {
			return 'course';
		}
		if ( is_post_type_archive( 'bfc_course' ) ) {
			return 'catalog';
		}
		if ( is_page( 'my-courses' ) ) {
			return 'my-courses';
		}
		if ( is_page( 'profile' ) ) {
			return 'profile';
		}
		return '';
	}

	/**
	 * Primary training nav items.
	 *
	 * @param string $current Context id (catalog|course|my-courses|profile|'').
	 * @return array<int, array{id:string,label:string,url:string,current:bool}>
	 */
	public function nav_items( string $current = '' ): array {
		$highlight = 'course' === $current ? 'catalog' : $current;
		$items     = array(
			array(
				'id'      => 'catalog',
				'label'   => __( 'Catalog', 'burbank-lms' ),
				'url'     => $this->catalog_url(),
				'current' => 'catalog' === $highlight,
			),
			array(
				'id'      => 'my-courses',
				'label'   => __( 'My Courses', 'burbank-lms' ),
				'url'     => $this->page_url( 'my-courses' ),
				'current' => 'my-courses' === $highlight,
			),
			array(
				'id'      => 'profile',
				'label'   => __( 'Profile', 'burbank-lms' ),
				'url'     => $this->page_url( 'profile' ),
				'current' => 'profile' === $highlight,
			),
		);
		return $items;
	}

	/**
	 * Contextual course actions for the shell (enroll / continue / withdraw).
	 *
	 * @param int $course_id Course post ID.
	 * @param int $user_id   Learner user ID.
	 * @return array<int, array{id:string,label:string,url:string,kind:string}>
	 */
	public function contextual_actions( int $course_id, int $user_id ): array {
		if ( $course_id < 1 || $user_id < 1 || ! ( new Learner() )->can_learn( $user_id ) ) {
			return array();
		}
		$post = get_post( $course_id );
		if ( ! $post || 'bfc_course' !== $post->post_type ) {
			return array();
		}

		$permalink = get_permalink( $course_id );
		if ( ! is_string( $permalink ) || '' === $permalink ) {
			return array();
		}

		$actions = array();
		$active  = ( new Enrollment_Service() )->get_active( $user_id, $course_id );
		if ( $active ) {
			$quiz_id = (int) get_post_meta( $course_id, 'pressprimer_quiz_id', true );
			if ( $quiz_id > 0 ) {
				$actions[] = array(
					'id'    => 'continue',
					'label' => __( 'Continue', 'burbank-lms' ),
					'url'   => $permalink . '#bfc-course-quiz',
					'kind'  => 'continue',
				);
			}
			$actions[] = array(
				'id'    => 'withdraw',
				'label' => __( 'Withdraw', 'burbank-lms' ),
				'url'   => add_query_arg( 'course', $course_id, $this->page_url( 'my-courses' ) ),
				'kind'  => 'withdraw',
			);
			return $actions;
		}

		$actions[] = array(
			'id'    => 'enroll',
			'label' => __( 'Enroll', 'burbank-lms' ),
			'url'   => $permalink . '#bfc-course-enroll',
			'kind'  => 'enroll',
		);
		return $actions;
	}

	/**
	 * Render once-per-request shell chrome HTML for can_learn users.
	 *
	 * @param array{current?:string,course_id?:int,force?:bool} $args Render options.
	 */
	public function render_chrome( array $args = array() ): string {
		$user_id = get_current_user_id();
		if ( ! $this->should_show( $user_id ) ) {
			return '';
		}
		if ( self::$chrome_printed && empty( $args['force'] ) ) {
			return '';
		}
		self::$chrome_printed = true;

		$current = isset( $args['current'] ) ? (string) $args['current'] : $this->detect_current();
		$items   = $this->nav_items( 'course' === $current ? 'catalog' : $current );

		$actions = array();
		if ( ! empty( $args['course_id'] ) ) {
			$actions = $this->contextual_actions( (int) $args['course_id'], $user_id );
		} elseif ( 'course' === $current ) {
			$actions = $this->contextual_actions( (int) get_the_ID(), $user_id );
		}

		$nav_list     = $this->render_nav_list( $items, 'bfc-shell__list' );
		$actions_html = '';
		if ( array() !== $actions ) {
			$actions_html = $this->render_actions_list( $actions );
		}

		$user      = get_userdata( $user_id );
		$display   = ( $user instanceof \WP_User && '' !== $user->display_name ) ? $user->display_name : __( 'Learner', 'burbank-lms' );
		$lede      = $this->lede_for_current( $current );
		$title_tag = ( 'course' === $current ) ? 'p' : 'h1';
		$header    = sprintf(
			'<header class="bfc-portal__header"><p class="bfc-portal__kicker">%1$s</p><%2$s class="bfc-portal__title">%3$s</%2$s><p class="bfc-portal__lede">%4$s</p></header>',
			esc_html__( 'Training', 'burbank-lms' ),
			$title_tag,
			esc_html( $display ),
			esc_html( $lede )
		);
		$aria      = esc_attr__( 'Training', 'burbank-lms' );
		$nav       = sprintf(
			'<nav class="bfc-shell" aria-label="%1$s">%2$s%3$s</nav>',
			$aria,
			$nav_list,
			$actions_html
		);

		return sprintf(
			'<section class="bfc-portal">%1$s%2$s</section>',
			$header,
			$nav
		);
	}

	/**
	 * Short lede under the portal title for the active shell context.
	 *
	 * @param string $current Context id.
	 */
	private function lede_for_current( string $current ): string {
		switch ( $current ) {
			case 'my-courses':
				return __( 'Your enrollments, certificates, and progress.', 'burbank-lms' );
			case 'profile':
				return __( 'Your training profile and account details.', 'burbank-lms' );
			case 'course':
				return __( 'Course materials, enrollment, and quiz access.', 'burbank-lms' );
			case 'catalog':
			default:
				return __( 'Browse the catalog, enroll, and continue your courses.', 'burbank-lms' );
		}
	}

	/**
	 * Catalog archive URL with home fallback.
	 */
	private function catalog_url(): string {
		$link = get_post_type_archive_link( 'bfc_course' );
		if ( is_string( $link ) && '' !== $link ) {
			return $link;
		}
		return home_url( '/courses/' );
	}

	/**
	 * Resolve a learner page permalink by path, with home_url fallback.
	 *
	 * @param string $path Page path (my-courses|profile).
	 */
	private function page_url( string $path ): string {
		$page = get_page_by_path( $path );
		if ( $page instanceof \WP_Post ) {
			$permalink = get_permalink( $page );
			if ( '' !== $permalink ) {
				return $permalink;
			}
		}
		return home_url( '/' . $path . '/' );
	}

	/**
	 * Escaped unordered list of nav links.
	 *
	 * @param array<int, array{id:string,label:string,url:string,current:bool}> $items      Nav items.
	 * @param string                                                            $list_class List class name.
	 */
	private function render_nav_list( array $items, string $list_class ): string {
		$lis = '';
		foreach ( $items as $item ) {
			$lis .= sprintf(
				'<li class="bfc-shell__item%1$s"><a class="bfc-shell__link" href="%2$s"%3$s>%4$s</a></li>',
				$item['current'] ? ' is-current' : '',
				esc_url( $item['url'] ),
				$item['current'] ? ' aria-current="page"' : '',
				esc_html( $item['label'] )
			);
		}
		return sprintf( '<ul class="%1$s">%2$s</ul>', esc_attr( $list_class ), $lis );
	}

	/**
	 * Escaped unordered list of contextual action links.
	 *
	 * @param array<int, array{id:string,label:string,url:string,kind:string}> $actions Actions.
	 */
	private function render_actions_list( array $actions ): string {
		$lis = '';
		foreach ( $actions as $action ) {
			$lis .= sprintf(
				'<li class="bfc-shell__action bfc-shell__action--%1$s"><a class="bfc-shell__action-link" href="%2$s">%3$s</a></li>',
				esc_attr( $action['kind'] ),
				esc_url( $action['url'] ),
				esc_html( $action['label'] )
			);
		}
		return sprintf( '<ul class="bfc-shell__actions">%s</ul>', $lis );
	}
}
