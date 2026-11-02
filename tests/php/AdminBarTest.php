<?php
/**
 * Front-end toolbar visibility.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Admin_Bar;
use Burbank_LMS\Access\Learner;

/** Learners and subscribers lose the front-end admin bar. Editors keep it. */
class AdminBarTest extends WP_UnitTestCase {
	/** Register the learner role before each case. */
	public function set_up(): void {
		parent::set_up();
		delete_option( Learner::ROLE_VERSION_OPTION );
		( new Learner() )->install_role();
	}

	/** A learner role hides the toolbar outside wp-admin. */
	public function test_learner_hides_front_end_admin_bar(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => Learner::ROLE ) ) );
		$this->assertTrue( ( new Admin_Bar() )->hides_for_current_user() );
	}

	/** An editor keeps the toolbar. */
	public function test_editor_keeps_admin_bar(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertFalse( ( new Admin_Bar() )->hides_for_current_user() );
	}
}
