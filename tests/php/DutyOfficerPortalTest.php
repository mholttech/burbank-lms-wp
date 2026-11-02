<?php
/**
 * Duty Officer portal cross-links.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Learner;
use Burbank_LMS\Integrations\Duty_Officer_Portal;
use Burbank_LMS\Learner\Shell;

/** Training links on the member portal, and a Duty Officer pill for linked roles. */
class DutyOfficerPortalTest extends WP_UnitTestCase {
	/** Drop the stand-in shortcode and role between cases. */
	public function tear_down(): void {
		remove_shortcode( 'duty_officer_member' );
		remove_role( 'duty_officer' );
		remove_role( 'duty_officer_admin' );
		parent::tear_down();
	}

	/** Guests on the portal get a catalog link and no private course list. */
	public function test_logged_out_companion_links_catalog(): void {
		wp_set_current_user( 0 );
		$html    = ( new Duty_Officer_Portal() )->companion( '', 'logged_out' );
		$catalog = get_post_type_archive_link( 'bfc_course' );
		$catalog = is_string( $catalog ) && '' !== $catalog ? $catalog : home_url( '/courses/' );
		$this->assertStringContainsString( 'bfc-duty-companion', $html );
		$this->assertStringContainsString( 'Course catalog', $html );
		$this->assertStringContainsString( $catalog, $html );
		$this->assertStringNotContainsString( 'My courses', $html );
	}

	/** A learner who is not an officer can still reach My Courses from the portal. */
	public function test_unlinked_learner_companion_links_my_courses(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		( new Learner() )->grant_access( $user_id );
		wp_set_current_user( $user_id );
		$html = ( new Duty_Officer_Portal() )->companion( '<p>existing</p>', 'unlinked' );
		$this->assertStringContainsString( '<p>existing</p>', $html );
		$this->assertStringContainsString( 'My courses', $html );
		$this->assertStringContainsString( home_url( '/my-courses/' ), $html );
	}

	/** An officer without training access does not get a training link inside the portal. */
	public function test_linked_officer_without_training_access_has_no_companion(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );
		$html = ( new Duty_Officer_Portal() )->companion( '', 'linked' );
		$this->assertSame( '', $html );
	}

	/** An officer who can train gets My Courses from the linked portal state. */
	public function test_linked_learner_companion_links_my_courses(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		( new Learner() )->grant_access( $user_id );
		wp_set_current_user( $user_id );
		$html = ( new Duty_Officer_Portal() )->companion( '', 'linked' );
		$this->assertStringContainsString( 'My courses', $html );
		$this->assertStringNotContainsString( 'Course catalog', $html );
	}

	/** Unknown companion contexts are ignored. */
	public function test_unknown_context_is_unchanged(): void {
		$html = ( new Duty_Officer_Portal() )->companion( 'keep', 'roster' );
		$this->assertSame( 'keep', $html );
	}

	/** The training shell stays catalog / my-courses / profile when Duty Officer is absent. */
	public function test_shell_omits_duty_officer_without_shortcode(): void {
		$ids = wp_list_pluck( ( new Shell() )->nav_items( 'catalog' ), 'id' );
		$this->assertSame( array( 'catalog', 'my-courses', 'profile' ), $ids );
	}

	/** A duty officer role plus the member shortcode adds one shell link. */
	public function test_shell_adds_duty_officer_for_officer_role(): void {
		add_shortcode( 'duty_officer_member', '__return_empty_string' );
		add_role( 'duty_officer', 'Duty Officer', array( 'read' => true ) );
		$user_id = self::factory()->user->create( array( 'role' => 'duty_officer' ) );
		( new Learner() )->grant_access( $user_id );
		wp_set_current_user( $user_id );
		Shell::reset_chrome_printed();
		$items = ( new Shell() )->nav_items( 'catalog' );
		$ids   = wp_list_pluck( $items, 'id' );
		$this->assertSame( array( 'catalog', 'my-courses', 'profile', 'duty-officer' ), $ids );
		$duty = $items[3];
		$this->assertSame( home_url( '/member-portal/' ), $duty['url'] );
		$this->assertFalse( $duty['current'] );
		$html = ( new Shell() )->render_chrome(
			array(
				'current' => 'catalog',
				'force'   => true,
			)
		);
		$this->assertStringContainsString( 'Duty Officer', $html );
		$this->assertStringContainsString( home_url( '/member-portal/' ), $html );
	}
}
