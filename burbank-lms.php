<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/mholttech/burbank-lms-wp
 * @since             1.0.0
 * @package           Burbank_LMS
 *
 * @wordpress-plugin
 * Plugin Name:       Burbank LMS
 * Description:       Burbank LMS WordPress plugin.
 * Version:           1.0.0
 * Requires PHP:      8.2
 * Requires at least: 7.1.3
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       burbank-lms
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
use Burbank_LMS\Activator;
use Burbank_LMS\Deactivator;
use Burbank_LMS\Burbank_LMS;
use Burbank_LMS\Uninstallor;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Plugin absolute path
 */
define( 'BURBANK_LMS_PATH', plugin_dir_path( __FILE__ ) );
define( 'BURBANK_LMS_URL', plugin_dir_url( __FILE__ ) );

if ( ! is_readable( BURBANK_LMS_PATH . 'vendor/autoload.php' ) || ! is_readable( BURBANK_LMS_PATH . 'vendor-prefixed/autoload.php' ) ) {
	wp_trigger_error(
		'',
		'Burbank LMS cannot load. Run composer install.',
		E_USER_WARNING
	);

	return;
}

require BURBANK_LMS_PATH . 'vendor/autoload.php';

/**
 * Public version constant for companion plugins to detect this plugin and
 * version-gate against it. Mirrors Burbank_LMS::PLUGIN_VERSION, the single
 * source of truth.
 */
if ( ! defined( 'BURBANK_LMS_VERSION' ) ) {
	define( 'BURBANK_LMS_VERSION', Burbank_LMS::PLUGIN_VERSION );
}

/**
 * Fires after this plugin has loaded and registered its hooks, so companion
 * plugins can attach to its extension points. Hooked to `plugins_loaded` to
 * stay independent of plugin load order.
 *
 * @param string $version The current plugin version.
 */
add_action(
	'plugins_loaded',
	static function (): void {
		do_action( 'burbank_lms_loaded', Burbank_LMS::PLUGIN_VERSION );
	},
	0
);

/**
 * The code that runs during plugin activation.
 */
function burbank_lms_activate(): void {
	Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function burbank_lms_deactivate(): void {
	Deactivator::deactivate();
}

/**
 * The code that runs during plugin uninstallation.
 */
function burbank_lms_uninstall(): void {
	Uninstallor::uninstall();
}

register_activation_hook( __FILE__, 'burbank_lms_activate' );
register_deactivation_hook( __FILE__, 'burbank_lms_deactivate' );
register_uninstall_hook( __FILE__, 'burbank_lms_uninstall' );
add_action( 'activated_plugin', array( Activator::class, 'network_activation' ), 10, 2 );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function burbank_lms_run(): void {
	$plugin = new Burbank_LMS();
	$plugin->run();
}
burbank_lms_run();
