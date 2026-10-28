<?php
/**
 * Tests for the companion-plugin handshake exposed by the bootstrap.
 *
 * Covers the `burbank_lms_loaded` action and the `BURBANK_LMS_VERSION` constant
 * defined in burbank-lms.php.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Burbank_LMS;

/**
 * Verifies the load handshake companion plugins rely on.
 */
class CompanionHandshakeTest extends WP_UnitTestCase {

	/**
	 * The global version constant must mirror the single source of truth so
	 * companions can version-gate without autoloading the plugin's classes.
	 */
	public function test_version_constant_mirrors_class_constant(): void {
		$this->assertTrue( defined( 'BURBANK_LMS_VERSION' ), 'BURBANK_LMS_VERSION should be defined by the bootstrap.' );
		$this->assertSame( Burbank_LMS::PLUGIN_VERSION, BURBANK_LMS_VERSION );
	}

	/**
	 * The handshake must actually fire during a real WordPress load, after
	 * plugins are included (it is hooked to `plugins_loaded`).
	 */
	public function test_loaded_action_fires_on_bootstrap(): void {
		$this->assertGreaterThan( 0, did_action( 'burbank_lms_loaded' ) );
	}

	/**
	 * The action must pass the current plugin version to its listeners.
	 *
	 * Re-dispatching `plugins_loaded` runs the bootstrap's priority-0 callback
	 * again so the argument it forwards can be observed.
	 */
	public function test_loaded_action_passes_plugin_version(): void {
		$received = array();
		add_action(
			'burbank_lms_loaded',
			static function ( $version ) use ( &$received ): void {
				$received[] = $version;
			}
		);

		do_action( 'plugins_loaded' );

		$this->assertNotEmpty( $received, 'burbank_lms_loaded should fire when plugins_loaded runs.' );
		$this->assertSame( Burbank_LMS::PLUGIN_VERSION, end( $received ) );
	}
}
