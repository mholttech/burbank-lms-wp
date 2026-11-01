<?php
/**
 * Soft gate audience and markup.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Invites;
use Burbank_LMS\Access\Registration;
use Burbank_LMS\Access\Tables as AccessTables;
use Burbank_LMS\Learner\Soft_Gate;

/** Exercises Soft_Gate audience detection and invite-only markup. */
class LearnerSoftGateTest extends WP_UnitTestCase {
	/** Install invite tables before each case. */
	public function set_up(): void {
		parent::set_up();
		global $wpdb;
		$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}bfc_invite_codes" ); // phpcs:ignore WordPress.DB
		delete_option( AccessTables::VERSION_OPTION );
		$this->assertTrue( ( new AccessTables() )->install() );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/** Restore PHPUnit transaction wrapping after real commits. */
	public function tear_down(): void {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_invite_codes" ); // phpcs:ignore WordPress.DB
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB
		parent::tear_down();
	}

	/** Register a learner with training access; return user id. */
	private function learner_with_access(): int {
		$created = ( new Invites() )->create(
			'site',
			array(
				'label' => 'Batch',
				'kind'  => 'multi',
			)
		);
		$this->assertIsArray( $created );
		$user_id = ( new Registration() )->register(
			array(
				'invite'       => $created['plaintext'],
				'email'        => 'softgate@example.com',
				'password'     => 'CorrectHorseBatteryStaple1!',
				'legal_name'   => 'Soft Gate',
				'display_name' => 'Soft',
			)
		);
		$this->assertIsInt( $user_id );
		( new \Burbank_LMS\Access\Learner() )->mark_email_verified( $user_id );
		return $user_id;
	}

	/** Guest, subscriber-without-access, and registered learner audiences. */
	public function test_audience_guest_no_access_and_learner(): void {
		$gate = new Soft_Gate();
		$this->assertSame( 'guest', $gate->audience( 0 ) );
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->assertSame( 'no_access', $gate->audience( $user_id ) );
		$learner = $this->learner_with_access();
		$this->assertSame( 'learner', $gate->audience( $learner ) );
		$this->assertFalse( $gate->should_block( $learner ) );
		$this->assertTrue( $gate->should_block( 0 ) );
	}

	/** Guest vs no-access cards differ and never expose catalog list markup. */
	public function test_render_guest_and_no_access_differ_and_omit_course_list(): void {
		$gate    = new Soft_Gate();
		$guest   = $gate->render( 'guest' );
		$blocked = $gate->render( 'no_access' );
		$this->assertStringContainsString( 'bfc-soft-gate', $guest );
		$this->assertStringContainsString( 'bfc-soft-gate__caption', $guest );
		$this->assertStringContainsString( '<h1 class="bfc-soft-gate__title">', $guest );
		$this->assertStringContainsString( 'register', strtolower( $guest ) );
		$this->assertStringNotContainsString( 'bfc-catalog__list', $guest );
		$this->assertStringContainsString( 'bfc-soft-gate', $blocked );
		$this->assertStringContainsString( 'bfc-soft-gate__caption', $blocked );
		$this->assertStringContainsString( '<h1 class="bfc-soft-gate__title">', $blocked );
		$this->assertStringContainsString( 'administrator', strtolower( $blocked ) );
		$this->assertNotSame( $guest, $blocked );
		$this->assertSame( '', $gate->render( 'learner' ) );
	}
}
