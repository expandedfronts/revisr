<?php
/**
 * The official Revisr WordPress plugin.
 *
 * A plugin that allows users to manage WordPress websites with Git repositories.
 * Integrates several key git functions into the WordPress admin section.
 *
 * Plugin Name:       Revisr
 * Plugin URI:        http://revisr.io/
 * Description:       A plugin that allows users to manage WordPress websites with Git repositories.
 * Version:           1.9.5
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
	 * Stores the Revisr_Admin object.
	 * @var Revisr_Admin
	 */
	public $admin;

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
	 * Stores the Revisr_Process object.
	 * @var Revisr_Process
	 */
	public $process;

	/**
	 * Stores the Revisr_List_Table object.
	 * @var Revisr_List_Table
	 */
	public $list_table;

	/**
	 * Stores the Revisr_Settings object
	 * @var Revisr_Settings
	 */
	public $settings;

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
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'revisr'), '1.8' );
	}

	/**
	 * Prevent direct unserialization by making the method private.
	 * @access private
	 */
	private function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'revisr'), '1.8' );
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
			add_action( 'plugins_loaded', array( __CLASS__, 'load_instance' ) );

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
		if ( current_user_can( 'install_plugins' ) && is_admin() ) {
			self::$instance->load_admin_hooks();
		}

	}

	/**
	 * Callback for spl_autoload_register.
	 * @access private
	 * @param  string $class The class to load.
	 * @since  1.9
	 */
	private static function autoload( $class ) {
		$file = REVISR_PATH . 'includes/class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
		if ( is_readable( $file ) ) {
			require( $file );
		}
	}

	/**
	 * Loads dependencies if autoloading is not enabled.
	 * @access private
	 */
	private function load_dependencies() {
		require_once REVISR_PATH . 'includes/class-revisr-i18n.php';
		require_once REVISR_PATH . 'includes/class-revisr-git.php';
		require_once REVISR_PATH . 'includes/class-revisr-admin.php';
		require_once REVISR_PATH . 'includes/class-revisr-remote.php';
		require_once REVISR_PATH . 'includes/class-revisr-db.php';
		require_once REVISR_PATH . 'includes/class-revisr-db-backup.php';
		require_once REVISR_PATH . 'includes/class-revisr-db-import.php';
		require_once REVISR_PATH . 'includes/class-revisr-git-callback.php';
		require_once REVISR_PATH . 'includes/class-revisr-cron.php';

		// Classes that should only be loaded for admins.
		if ( current_user_can( 'install_plugins' ) && is_admin() ) {
			require_once REVISR_PATH . 'includes/class-revisr-compatibility.php';
			require_once REVISR_PATH . 'includes/class-revisr-process.php';
			require_once REVISR_PATH . 'includes/class-revisr-list-table.php';
			require_once REVISR_PATH . 'includes/class-revisr-commits.php';
			require_once REVISR_PATH . 'includes/class-revisr-settings.php';
			require_once REVISR_PATH . 'includes/class-revisr-settings-fields.php';
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
		define( 'REVISR_VERSION', '1.9.5' );
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
		self::$instance->git 			= new Revisr_Git();
		self::$instance->commits 		= new Revisr_Commits();
		self::$instance->admin 			= new Revisr_Admin();
		self::$instance->db 			= new Revisr_DB();
		self::$instance->process 		= new Revisr_Process();
		self::$instance->settings 		= new Revisr_Settings();
		self::$instance->list_table 	= new Revisr_List_Table();

		// Register the plugin settings link.
		add_filter( 'plugin_action_links_'  . plugin_basename( __FILE__ ), array( __CLASS__, 'settings_link' ) );

		// Create and configure the "revisr_commits" custom post type.
		add_action( 'init', array( self::$instance->commits, 'post_types' ) );
		add_action( 'init', array( self::$instance->commits, 'register_meta_keys' ) );
		add_action( 'pre_get_posts', array( self::$instance->commits, 'filters' ) );
		add_action( 'views_edit-revisr_commits', array( self::$instance->commits, 'custom_views' ) );
		add_action( 'load-edit.php', array( self::$instance->commits, 'default_views' ) );
		add_action( 'post_row_actions', array( self::$instance->commits, 'custom_actions' ) );
		add_action( 'manage_edit-revisr_commits_columns', array( self::$instance->commits, 'columns' ) );
		add_action( 'manage_revisr_commits_posts_custom_column', array( self::$instance->commits, 'custom_columns' ), 10, 2 );
		add_filter( 'post_updated_messages', array( self::$instance->commits, 'custom_messages' ) );
		add_filter( 'bulk_post_updated_messages', array( self::$instance->commits, 'bulk_messages' ), 10, 2 );
		add_action( 'wp_ajax_pending_files', array( self::$instance->commits, 'pending_files' ) );
		add_action( 'load-post.php', array( self::$instance->commits, 'post_meta' ) );
		add_action( 'load-post-new.php', array( self::$instance->commits, 'post_meta' ) );
		add_filter( 'enter_title_here', array( self::$instance->commits, 'custom_enter_title' ) );
		add_filter( 'posts_where', array( self::$instance->commits, 'posts_where' ) );

		// Enqueue styles and scripts.
		add_action( 'admin_enqueue_scripts', array( self::$instance->admin, 'revisr_scripts' ) );

		// Initiate the admin menus.
		add_action( 'admin_menu', array( self::$instance->admin, 'menus' ), 2 );
		add_action( 'admin_bar_menu', array( self::$instance->admin, 'admin_bar' ), 999 );
		add_filter( 'custom_menu_order', array( self::$instance->admin, 'revisr_submenu_order' ) );

		// Callbacks for AJAX UI
		add_action( 'wp_ajax_render_alert', array( self::$instance->admin, 'render_alert' ) );
		add_action( 'wp_ajax_ajax_button_count', array( self::$instance->admin, 'ajax_button_count' ) );
		add_action( 'wp_ajax_view_diff', array( self::$instance->admin, 'view_diff' ) );
		add_action( 'wp_ajax_verify_remote', array( self::$instance->git, 'verify_remote' ) );

		// Load the thickbox forms used by Revisr.
		add_action( 'admin_post_delete_branch_form', array( self::$instance->admin, 'delete_branch_form' ) );
		add_action( 'admin_post_merge_branch_form', array ( self::$instance->admin, 'merge_branch_form' ) );
		add_action( 'admin_post_import_tables_form', array( self::$instance->admin, 'import_tables_form' ) );
		add_action( 'admin_post_revert_form', array( self::$instance->admin, 'revert_form' ) );
		add_action( 'admin_post_revisr_view_status', array( self::$instance->admin, 'view_status' ) );
		add_action( 'admin_post_revisr_view_error', array( self::$instance->admin, 'view_error' ) );

		// Displays the "Sponsored by Site5" logo.
		add_action( 'admin_notices', array( self::$instance->admin, 'site5_notice' ) );

		// Update the database schema if necessary.
		if ( get_option( 'revisr_db_version' ) === '1.0' ) {
			add_action( 'admin_init', array( self::$instance->admin, 'do_upgrade' ) );
		}

		// Processes actions taken from within the WordPress dashboard.
		add_action( 'init', array( self::$instance->process, 'process_is_repo' ) );
		add_action( 'publish_revisr_commits', array( self::$instance->process, 'process_commit' ) );
		add_action( 'admin_post_process_checkout', array( self::$instance->process, 'process_checkout' ) );
		add_action( 'admin_post_process_create_branch', array( self::$instance->process, 'process_create_branch' ) );
		add_action( 'admin_post_process_delete_branch', array( self::$instance->process, 'process_delete_branch' ) );
		add_action( 'admin_post_process_merge', array( self::$instance->process, 'process_merge' ) );
		add_action( 'admin_post_process_import', array( self::$instance->process, 'process_import' ) );
		add_action( 'admin_post_init_repo', array( self::$instance->process, 'process_init' ) );
		add_action( 'admin_post_process_revert', array( self::$instance->process, 'process_revert' ) );
		add_action( 'admin_post_process_view_diff', array( self::$instance->process, 'process_view_diff' ) );
		add_action( 'wp_ajax_discard', array( self::$instance->process, 'process_discard' ) );
		add_action( 'wp_ajax_process_push', array( self::$instance->process, 'process_push' ) );
		add_action( 'wp_ajax_process_pull', array( self::$instance->process, 'process_pull' ) );
		add_action( 'wp_ajax_backup_db', array( self::$instance->db, 'backup' ) );

		// Load the settings page.
		add_action( 'admin_init', array( self::$instance->settings, 'init_settings' ) );
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
		$table_name = $wpdb->prefix . 'revisr';
		return $table_name;
	}

	/**
	 * Installs the database table.
	 * @access public
	 */
	public static function revisr_install() {
		$table_name = Revisr::get_table_name();
		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			message TEXT,
			event VARCHAR(42) NOT NULL,
			UNIQUE KEY id (id)
			);";

	  	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	   	dbDelta( $sql );

	   	if ( false === get_option( 'revisr_db_version' ) ) {
	   		add_option( 'revisr_db_version', '1.1' );
	   	}

	}

	/**
	 * Displays the link to the settings on the WordPress plugin page.
	 * @access public
	 * @param array $links The links assigned to Revisr.
	 */
	public static function settings_link( $links ) {
		$settings_link = '<a href="admin.php?page=revisr_settings">' . __( 'Settings', 'revisr' ) . '</a>';
  		array_unshift( $links, $settings_link );
  		return $links;
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
register_activation_hook( __FILE__, array( 'Revisr', 'revisr_install' ) );
