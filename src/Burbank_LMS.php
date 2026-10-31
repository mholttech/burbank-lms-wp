<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/mholttech/burbank-lms-wp
 * @since      1.0.0
 *
 * @package    Burbank_LMS
 */

namespace Burbank_LMS;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Burbank_LMS
 * @author     Burbank LMS contributors
 */
class Burbank_LMS {


	const PLUGIN_NAME    = 'burbank-lms';
	const PLUGIN_VERSION = '1.1.0'; // x-release-please-version

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin
	 *
	 * @var Loader
	 */
	protected Loader $loader;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		$this->loader->add_action( 'init', $this, 'register_blocks' );
		$this->loader->add_action( 'init', new Learner\Templates(), 'register' );

		$course_picks = new Courses\Picks();
		$this->loader->add_action( 'pods_form_ui_field_pick_related_objects_custom', $course_picks, 'register' );
		$this->loader->add_action( 'init', $course_picks, 'register', 11 );
		$course_schema = new Courses\Schema();
		$this->loader->add_action( 'init', $course_schema, 'register', 12 );
		$this->loader->add_action( 'admin_notices', $course_schema, 'notice' );
		$this->loader->add_action( 'init', new Courses\Capabilities(), 'install', 12 );
		$course_registry = new Courses\Registry();
		$this->loader->add_action( 'init', $course_registry, 'install', 12 );
		$this->loader->add_action( 'admin_notices', $course_registry, 'notice' );
		$access_tables = new Access\Tables();
		$this->loader->add_action( 'init', $access_tables, 'install', 12 );
		$enrollment_tables = new Enrollment\Tables();
		$this->loader->add_action( 'init', $enrollment_tables, 'install', 12 );
		$registration = new Access\Registration();
		$this->loader->add_filter( 'registration_errors', $registration, 'block_default_registration' );
		$this->loader->add_action( 'profile_update', $registration, 'handle_email_change', 10, 2 );
		$this->loader->add_action( 'template_redirect', $registration, 'handle_verification_request' );
		$rest = new REST\Controller( $registration );
		$this->loader->add_action( 'rest_api_init', $rest, 'register_routes' );
		$admin_rest = new REST\Admin_Controller();
		$this->loader->add_action( 'rest_api_init', $admin_rest, 'register_routes' );
		$profile = new Access\Profile();
		$this->loader->add_filter( 'add_user_metadata', $profile, 'guard_legal_name_meta', 10, 4 );
		$this->loader->add_filter( 'update_user_metadata', $profile, 'guard_legal_name_meta', 10, 4 );
		$this->loader->add_filter( 'delete_user_metadata', $profile, 'guard_legal_name_delete', 10, 3 );
		$this->loader->add_filter( 'delete_user_metadata_by_mid', $profile, 'guard_legal_name_delete_id', 10, 2 );
		$learner = new Access\Learner();
		$this->loader->add_filter( 'user_has_cap', $learner, 'filter_quiz_cap', 10, 4 );
		$mailer = new Mail\Mailer();
		$this->loader->add_action( 'burbank_lms_access_granted', $mailer, 'on_access_granted', 10, 2 );
		$this->loader->add_action( 'burbank_lms_enrolled', $mailer, 'on_enrolled', 10, 4 );
		$this->loader->add_action( 'burbank_lms_course_completed', $mailer, 'on_course_completed', 10, 3 );
		$branding = new Branding\PressPrimer();
		$this->loader->add_filter( 'pressprimer_quiz_plugin_name', $branding, 'filter_plugin_name' );
		$this->loader->add_filter( 'pressprimer_quiz_dashboard_logo', $branding, 'filter_logo' );
		$this->loader->add_filter( 'pressprimer_quiz_shell_branding', $branding, 'filter_shell_branding' );
		$this->loader->add_filter( 'ppcert_plugin_name', $branding, 'filter_plugin_name' );
		$this->loader->add_filter( 'ppcert_dashboard_logo', $branding, 'filter_logo' );
		$progress                = new Progress\Service();
		$enrollment_service      = new Enrollment\Service();
		$pressprimer_quiz        = new Integrations\PressPrimer_Quiz( $progress, $enrollment_service, $learner );
		$pressprimer_certificate = new Integrations\PressPrimer_Certificate( $progress, $enrollment_service, $profile );
		$this->loader->add_filter( 'pressprimer_quiz_can_start', $pressprimer_quiz, 'can_start', 10, 2 );
		$this->loader->add_filter( 'pressprimer_quiz_before_submit', $pressprimer_quiz, 'before_submit', 10, 2 );
		$this->loader->add_action( 'pressprimer_quiz_quiz_passed', $pressprimer_quiz, 'quiz_passed', 10, 2 );
		$this->loader->add_filter( 'ppcert_register_merge_fields', $pressprimer_certificate, 'register_merge_fields', 10, 2 );
		$this->loader->add_filter( 'ppcert_merge_data', $pressprimer_certificate, 'merge_data', 10, 2 );
		$this->loader->add_filter( 'ppcert_issue_validation', $pressprimer_certificate, 'issue_validation', 10, 2 );
		$this->loader->add_action( 'ppcert_certificate_issued', $pressprimer_certificate, 'certificate_issued', 10, 2 );
		$course_validator = new Courses\Validator();
		$this->loader->add_filter( 'add_post_metadata', $course_validator, 'guard_meta', 10, 4 );
		$this->loader->add_filter( 'update_post_metadata', $course_validator, 'guard_meta', 10, 4 );
		$this->loader->add_filter( 'delete_post_metadata', $course_validator, 'guard_delete', 10, 3 );
		$this->loader->add_filter( 'update_post_metadata_by_mid', $course_validator, 'guard_meta_id', 10, 4 );
		$this->loader->add_filter( 'delete_post_metadata_by_mid', $course_validator, 'guard_delete_id', 10, 2 );
		$this->loader->add_filter( 'pods_api_post_save_pod_item_bfc_course', $course_validator, 'pods_finished', 10, 3 );
		$this->loader->add_filter( 'rest_pre_insert_bfc_course', $course_validator, 'rest_preflight', 10, 2 );
		$this->loader->add_filter( 'pods_api_field_validation', $course_validator, 'pods_preflight', 10, 7 );
		$this->loader->add_filter( 'wp_insert_post_empty_content', $course_validator, 'reject_invalid_batch', 10, 2 );
		$this->loader->add_filter( 'wp_insert_post_data', $course_validator, 'guard_post', 10, 2 );
		$this->loader->add_action( 'wp_after_insert_post', $course_validator, 'seal_published', 10, 2 );
		$this->loader->add_action( 'transition_post_status', $course_validator, 'guard_transition', PHP_INT_MIN, 3 );
		$this->loader->add_action( 'before_delete_post', $course_validator, 'begin_delete', PHP_INT_MIN );
		$this->loader->add_action( 'deleted_post', $course_validator, 'end_delete', PHP_INT_MAX );
		$this->loader->add_action( 'admin_notices', $course_validator, 'notice' );
		$this->loader->add_filter( 'redirect_post_location', $course_validator, 'redirect_notice', 10, 2 );
		$course_editorial = new Courses\Editorial();
		foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $hook ) {
			$this->loader->add_action( $hook, $course_editorial, 'metadata_changed', 10, 3 );
		}
		foreach ( array( 'add_post_metadata', 'update_post_metadata', 'delete_post_metadata' ) as $hook ) {
			$this->loader->add_filter( $hook, $course_editorial, 'guard_snapshot', 10, 3 );
		}
		$this->loader->add_filter( 'update_post_metadata_by_mid', $course_editorial, 'guard_snapshot_id', 10, 4 );
		$this->loader->add_filter( 'delete_post_metadata_by_mid', $course_editorial, 'guard_snapshot_id', 10, 2 );
		$this->loader->add_filter( 'pre_delete_post', $course_editorial, 'guard_deletion', PHP_INT_MAX, 2 );
		$course_admin = new Courses\Admin();
		$this->loader->add_filter( 'manage_bfc_course_posts_columns', $course_admin, 'columns' );
		$this->loader->add_action( 'manage_bfc_course_posts_custom_column', $course_admin, 'column', 10, 2 );
		$course_editor = new Courses\Editor();
		$this->loader->add_action( 'enqueue_block_editor_assets', $course_editor, 'enqueue' );
		$this->loader->add_action( 'add_meta_boxes_bfc_course', $course_editor, 'hide_pods_fields', 99 );
		$access_admin = new Access\Admin();
		$this->loader->add_action( 'admin_menu', $access_admin, 'register_menu' );
		$this->loader->add_action( 'admin_post_bfc_invite_create', $access_admin, 'handle_create_invite' );
		$this->loader->add_action( 'admin_post_bfc_invite_revoke', $access_admin, 'handle_revoke_invite' );
		$this->loader->add_action( 'admin_post_bfc_learner_profile', $access_admin, 'handle_profile' );
		$this->loader->add_action( 'show_user_profile', $access_admin, 'render_profile' );
		$this->loader->add_action( 'edit_user_profile', $access_admin, 'render_profile' );
		$this->loader->add_action( 'admin_notices', $access_admin, 'notice' );
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies(): void {

		$this->loader = new Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale(): void {
		load_plugin_textdomain(
			'burbank-lms',
			false,
			dirname( plugin_basename( __FILE__ ), 2 ) . '/languages/'
		);
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 */
	private function define_admin_hooks(): void {

		add_action(
			'admin_enqueue_scripts',
			function () {
				$this->enqueue_entrypoint( 'burbank-lms-admin' );
			},
			100
		);
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks(): void {

		add_action(
			'wp_enqueue_scripts',
			function () {
				$this->enqueue_entrypoint( 'burbank-lms-frontend' );
			},
			100
		);
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run(): void {
		$this->loader->run();
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return Loader Orchestrates the hooks of the plugin.
	 */
	public function get_loader(): Loader {
		return $this->loader;
	}

	/**
	 * Enqueue a webpack entrypoint
	 *
	 * @param string              $entry Name of the entrypoint defined in webpack.config.js.
	 * @param array<string,mixed> $localize_data Array of associated data. See https://developer.wordpress.org/reference/functions/wp_localize_script/ .
	 */
	private function enqueue_entrypoint( string $entry, array $localize_data = array() ): void {

		// Try to get WordPress filesystem. If not possible load it.
		global $wp_filesystem;
		if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php'; // @phpstan-ignore requireOnce.fileNotFound
			WP_Filesystem();
		}

		$filesystem = new \WP_Filesystem_Direct( false );

		$asset_file = BURBANK_LMS_PATH . "/build/{$entry}.asset.php";
		if ( ! $filesystem->exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;
		if ( ! isset( $asset['dependencies'], $asset['version'] ) ) {
			return;
		}

		if ( $filesystem->exists( BURBANK_LMS_PATH . "build/{$entry}.js" ) ) {
			wp_enqueue_script(
				self::PLUGIN_NAME . "/{$entry}",
				BURBANK_LMS_URL . "build/{$entry}.js",
				$asset['dependencies'],
				$asset['version'],
				true
			);

			// Potentially add localize data
			if ( ! empty( $localize_data ) ) {
				wp_localize_script(
					self::PLUGIN_NAME . "/{$entry}",
					str_replace( '-', '_', self::PLUGIN_NAME ),
					$localize_data
				);
			}

			// Load JSON translations
			wp_set_script_translations( self::PLUGIN_NAME . "/{$entry}", 'burbank-lms', BURBANK_LMS_PATH . 'languages/' );
		}

		if ( $filesystem->exists( BURBANK_LMS_PATH . "build/{$entry}.css" ) ) {
			wp_enqueue_style(
				self::PLUGIN_NAME . "/{$entry}",
				BURBANK_LMS_URL . "build/{$entry}.css",
				array(),
				$asset['version']
			);
		}
	}

	/**
	 * Register Gutenberg blocks.
	 *
	 * Registers all Gutenberg blocks from the Blocks directory.
	 * Block assets are loaded from the build/Blocks directory using a manifest file.
	 * Uses the metadata collection API (WP 6.8+).
	 *
	 * JSON Translations are loaded automatically. Use `npm run i18n:compile` to generate translation files from .po files.
	 *
	 * To localize scripts you need to use `wp_localize_script`.
	 * The handle can be generated with `generate_block_asset_handle('burbank-lms/block-name', 'editorScript')`.
	 *
	 * @link https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/
	 * @link https://developer.wordpress.org/reference/functions/generate_block_asset_handle/
	 *
	 * @return void
	 */
	public function register_blocks(): void {

		$manifest_file = BURBANK_LMS_PATH . 'build/blocks-manifest.php';
		$blocks_folder = BURBANK_LMS_PATH . 'build/Blocks';

		if ( ! is_readable( $manifest_file ) || ! is_dir( $blocks_folder ) ) {
			return;
		}

		wp_register_block_types_from_metadata_collection( $blocks_folder, $manifest_file );
	}
}
