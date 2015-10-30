<?php
/**
 * The official Revisr WordPress plugin.
 *
 * A plugin that allows users to manage WordPress websites with Git repositories.
 * Integrates several key git functions into the WordPress admin section.
 *
 * Plugin Name:       Revisr
 * Plugin URI:        https://revisr.io/
 * Description:       A plugin that allows users to manage WordPress websites with Git repositories.
 * Version:           2.0.1
 * Author:            Expanded Fronts, LLC
 * Author URI:        http://expandedfronts.com/
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       revisr
 * Domain Path:       /languages
 * Network: 		  true
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The main Revisr class. Initializes the plugin and loads any
 * required hooks and dependencies.
 *
 * @since 1.8.2
 */
final class Revisr {

	/**
	 * Stores the current instance of Revisr.
	 * @var object
	 */
	private static $instance;

	/**
	 * Stores the Revisr_DB object.
	 * @var Revisr_DB
	 */
	public $db;

	/**
	 * Stores the Revisr_Git object.
	 * @var Revisr_Git
	 */
	public $git;

	/**
	 * Stores the Revisr_Activity_Table object.
	 * @var Revisr_Activity_Table
	 */
	public $activity_table;

	/**
	 * Stores the Revisr_Branch_Table object.
	 * @var Revisr_Branch_Table
	 */
	public $branch_table;

	/**
	 * Stores the Revisr_Commits_Table object.
	 * @var Revisr_Commits_Table
	 */
	public $commits_table;

	/**
	 * The name of the plugin.
	 * @var string
	 */
	public $plugin_name = 'revisr';

	/**
	 * An array of user options and preferences.
	 * @var array
	 */
	public $options;

	/**
	 * Empty construct, use revisr() instead.
	 * @access private
	 */
	private function __construct() {
		// Do nothing here.
	}

	/**
	 * Prevent direct __clones by making the method private.
	 * @access private
	 */
	private function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'revisr' ), '1.8' );
	}

	/**
	 * Prevent direct unserialization by making the method private.
	 * @access private
	 */
	private function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'revisr' ), '1.8' );
	}

	/**
	 * Retrieves the current instance of the Revisr plugin,
	 * or create a new one if it doesn't already exist.
	 * @access public
	 * @since  1.8.2
	 * @return object
	 */
	public static function get_instance() {

		if ( null == self::$instance ) {

			// Create the instance.
			self::$instance 			= new self;
			self::$instance->options 	= self::$instance->get_options();

			// Define constants used by the plugin.
			self::$instance->define_constants();

			// Load the rest of the plugin.
			add_action( 'after_setup_theme', array( __CLASS__, 'load_instance' ) );

		}

		return self::$instance;
	}

	/**
	 * Loads dependencies and initiates action hooks.
	 * @access public
	 */
	public static function load_instance() {

		// Load the classes via autoloader if available.
		if ( function_exists( 'spl_autoload_register' ) ) {
			spl_autoload_register( array( __CLASS__, 'autoload' ) );
		} else {
			self::$instance->load_dependencies();
		}

		// Set the locale.
		self::$instance->set_locale();

		// Load any public-facing hooks.
		self::$instance->load_public_hooks();

		// Load any admin-side hooks.
		if ( current_user_can( self::get_capability() ) && is_admin() ) {
			self::$instance->load_admin_hooks();
		}

		// Fires after the plugin has loaded.
		do_action( 'revisr_loaded' );

	}

	/**
	 * Callback for spl_autoload_register.
	 * @access private
	 * @param  string $class The class to load.
	 * @since  1.9
	 */
	private static function autoload( $class ) {
		$file = REVISR_PATH . 'classes/class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
		if ( is_readable( $file ) ) {
			require( $file );
		}
	}

	/**
	 * Loads dependencies if autoloading is not enabled.
	 * @access private
	 */
	private function load_dependencies() {

		require_once REVISR_PATH . 'classes/class-revisr-i18n.php';
		require_once REVISR_PATH . 'classes/class-revisr-git.php';
		require_once REVISR_PATH . 'classes/class-revisr-admin.php';
		require_once REVISR_PATH . 'classes/class-revisr-remote.php';
		require_once REVISR_PATH . 'classes/class-revisr-db.php';
		require_once REVISR_PATH . 'classes/class-revisr-db-backup.php';
		require_once REVISR_PATH . 'classes/class-revisr-db-import.php';
		require_once REVISR_PATH . 'classes/class-revisr-git-callback.php';
		require_once REVISR_PATH . 'classes/class-revisr-cron.php';

		// Classes that should only be loaded for admins.
		if ( current_user_can( self::get_capability() ) && is_admin() ) {
			require_once REVISR_PATH . 'classes/class-revisr-admin-pages.php';
			require_once REVISR_PATH . 'classes/class-revisr-compatibility.php';
			require_once REVISR_PATH . 'classes/class-revisr-process.php';
			require_once REVISR_PATH . 'classes/class-revisr-activity-table.php';
			require_once REVISR_PATH . 'classes/class-revisr-branch-table.php';
			require_once REVISR_PATH . 'classes/class-revisr-commits-table.php';
			require_once REVISR_PATH . 'classes/class-revisr-meta-boxes.php';
			require_once REVISR_PATH . 'classes/class-revisr-settings.php';
			require_once REVISR_PATH . 'classes/class-revisr-settings-fields.php';
		}

	}

	/**
	 * Defines the constants used by Revisr.
	 * @access private
	 */
	private function define_constants() {
		// The base plugin file.
		define( 'REVISR_FILE', __FILE__ );

		// The full path used for includes.
		define( 'REVISR_PATH', plugin_dir_path( REVISR_FILE ) );

		// The URL of the plugin base directory.
		define( 'REVISR_URL', plugin_dir_url( REVISR_FILE ) );

		// The current version of the plugin.
		define( 'REVISR_VERSION', '2.0.1' );
	}

	/**
	 * Sets the locale and loads any translation files.
	 * @access private
	 */
	private function set_locale() {
		$revisr_i18n = new Revisr_i18n();
		$revisr_i18n->set_domain( $this->plugin_name );
		add_action( 'plugins_loaded', array( $revisr_i18n, 'load_plugin_textdomain' ) );
	}

	/**
	 * Loads any public-facing hooks.
	 * @access private
	 */
	private function load_public_hooks() {
		$cron = new Revisr_Cron();
		add_filter( 'cron_schedules', array( $cron, 'revisr_schedules' ) );
		add_action( 'revisr_cron', array( $cron, 'run_automatic_backup' ) );
		add_action( 'admin_post_nopriv_revisr_update', array( $cron, 'run_autopull' ) );
	}

	/**
	 * Loads hooks used in the admin.
	 * @access private
	 */
	private function load_admin_hooks() {

		// Load necessary classes into the instance.
		self::$instance->git 				= new Revisr_Git();
		self::$instance->db 				= new Revisr_DB();
		self::$instance->activity_table 	= new Revisr_Activity_Table();
		self::$instance->branch_table 		= new Revisr_Branch_Table();
		self::$instance->commits_table 		= new Revisr_Commits_Table();

		// Create/configure custom admin pages and menus.
		$admin_pages = new Revisr_Admin_Pages();
		add_action( 'admin_menu', array( $admin_pages, 'menus' ), 2 );
		add_action( 'admin_enqueue_scripts', array( $admin_pages, 'scripts' ) );
		add_filter( 'custom_menu_order', array( $admin_pages, 'submenu_order' ) );
		add_filter( 'parent_file', array( $admin_pages, 'parent_file' ) );
		add_action( 'admin_bar_menu', array( $admin_pages, 'admin_bar' ), 999 );
		add_action( 'admin_notices', array( $admin_pages, 'site5_notice' ) );
		add_filter( 'plugin_action_links_'  . plugin_basename( __FILE__ ), array( $admin_pages, 'settings_link' ) );

		// Load the thickbox forms used by Revisr.
		add_action( 'admin_post_revisr_delete_branch_form', array( $admin_pages, 'include_form' ) );
		add_action( 'admin_post_revisr_merge_branch_form', array ( $admin_pages, 'include_form' ) );
		add_action( 'admin_post_revisr_import_tables_form', array( $admin_pages, 'include_form' ) );
		add_action( 'admin_post_revisr_revert_form', array( $admin_pages, 'include_form' ) );
		add_action( 'admin_post_revisr_discard_form', array( $admin_pages, 'include_form' ) );
		add_action( 'admin_post_revisr_push_form', array( $admin_pages, 'include_form' ) );
		add_action( 'admin_post_revisr_pull_form', array( $admin_pages, 'include_form' ) );
		add_action( 'admin_post_revisr_checkout_remote_form', array( $admin_pages, 'include_form' ) );

		// Add custom meta boxes.
		$meta_boxes = new Revisr_Meta_Boxes();
		add_action( 'load-admin_page_revisr_new_commit', array( $meta_boxes, 'add_meta_box_actions' ) );
		add_action( 'load-admin_page_revisr_view_commit', array( $meta_boxes, 'add_meta_box_actions' ) );
		add_action( 'admin_footer-admin_page_revisr_new_commit', array( $meta_boxes, 'init_meta_boxes' ) );
		add_action( 'add_meta_boxes_admin_page_revisr_new_commit', array( $meta_boxes, 'post_meta' ) );
		add_action( 'wp_ajax_pending_files', array( $meta_boxes, 'pending_files' ) );

		// Callbacks for AJAX UI
		$admin = new Revisr_Admin();
		add_action( 'wp_ajax_render_alert', array( 'Revisr_Admin', 'render_alert' ) );
		add_action( 'wp_ajax_ajax_button_count', array( $admin, 'ajax_button_count' ) );
		add_action( 'wp_ajax_verify_remote', array( self::$instance->git, 'verify_remote' ) );

		// Update the database schema if necessary.
		if ( get_option( 'revisr_db_version' ) !== '2.0' ) {
			add_action( 'admin_init', array( $admin, 'do_upgrade' ) );
		}

		// Processes actions taken from within the WordPress dashboard.
		$process = new Revisr_Process();
		add_action( 'init', array( $process, 'is_repo' ) );
		add_action( 'admin_post_process_commit', array( $process, 'commit' ) );
		add_action( 'admin_post_process_checkout', array( $process, 'checkout' ) );
		add_action( 'admin_post_process_create_branch', array( $process, 'create_branch' ) );
		add_action( 'admin_post_process_delete_branch', array( $process, 'delete_branch' ) );
		add_action( 'admin_post_process_merge', array( $process, 'merge' ) );
		add_action( 'admin_post_process_import', array( $process, 'import' ) );
		add_action( 'admin_post_init_repo', array( $process, 'init' ) );
		add_action( 'admin_post_process_revert', array( $process, 'revert' ) );
		add_action( 'admin_post_revisr_view_error', array( $process, 'view_error' ) );
		add_action( 'admin_post_process_view_status', array( $process, 'view_status' ) );
		add_action( 'admin_post_process_view_diff', array( $process, 'view_diff' ) );
		add_action( 'wp_ajax_process_view_diff', array( $process, 'view_diff' ) );
		add_action( 'wp_ajax_process_discard', array( $process, 'discard' ) );
		add_action( 'wp_ajax_process_push', array( $process, 'push' ) );
		add_action( 'wp_ajax_process_pull', array( $process, 'pull' ) );
		add_action( 'wp_ajax_backup_db', array( self::$instance->db, 'backup' ) );
		add_action( 'admin_post_revisr_download_sysinfo', array( $process, 'download_sysinfo' ) );

		// Load the settings page.
		$settings = new Revisr_Settings();
		add_action( 'admin_init', array( $settings, 'init_settings' ) );
	}

	/**
	 * Returns the name of the capability required to use Revisr.
	 * @access public
	 * @return string
	 */
	public static function get_capability() {
		return apply_filters( 'manage_revisr', 'install_plugins' );
	}

	/**
	 * Returns an array of user options and preferences.
	 * @access public
	 * @return array
	 */
	public static function get_options() {
		$deprecated = get_option( 'revisr_settings' ) ? get_option( 'revisr_settings' ) : array();
		$general 	= get_option( 'revisr_general_settings' ) ? get_option( 'revisr_general_settings' ) : array();
		$remote 	= get_option( 'revisr_remote_settings' ) ? get_option( 'revisr_remote_settings' ) : array();
		$database 	= get_option( 'revisr_database_settings' ) ? get_option( 'revisr_database_settings' ) : array();
		$options 	= array_merge( $deprecated, $general, $remote, $database );
		return $options;
	}

	/**
	 * Returns the name of the custom table used by Revisr.
	 * @access public
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;
		return esc_sql( $wpdb->prefix . 'revisr' );
	}

	/**
	 * Installs the database table.
	 * @access public
	 */
	public static function install() {
		$table_name = Revisr::get_table_name();
		$sql = "CREATE TABLE {$table_name} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			message TEXT,
			event VARCHAR(42) NOT NULL,
			user VARCHAR(60),
			UNIQUE KEY id (id)
			);";

	  	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	   	dbDelta( $sql );

		update_option( 'revisr_db_version', '2.0' );
	}

}

/**
 * Returns a single instance of the Revisr plugin.
 *
 * @since 	1.8.2
 * @return 	object
 */
function revisr() {
	return Revisr::get_instance();
}

// Let's go!
revisr();

// Register the activation hook.
register_activation_hook( __FILE__, array( 'Revisr', 'install' ) );
