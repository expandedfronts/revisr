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
 * Version:           1.8.3
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
 * The main Revisr class. Initializes the plugin loads any
 * required hooks and dependencies.
 * 
 * @since 1.8.2
 */
class Revisr {

	/**
	 * Stores the current instance of Revisr.
	 * @var object
	 */
	private static $instance;

	/**
	 * The "Revisr_Git" object.
	 * @var object
	 */
	public $git;

	/**
	 * The "Revisr_DB" object.
	 * @var object
	 */
	public $db;

	/**
	 * The "Revisr_Admin" object.
	 * @var object
	 */
	public $admin;

	/**
	 * An array of user options and preferences.
	 * @var array
	 */
	public $options;

	/**
	 * The name of the plugin.
	 * @var string
	 */
	public $plugin_name;

	/**
	 * The name of the database table.
	 * @var string
	 */
	public $table_name;

	/**
	 * The "Revisr_Admin_Setup" object.
	 * @var object
	 */
	private $admin_setup;

	/**
	 * The "Revisr_Commits" object.
	 * @var object
	 */
	private $commits;

	/**
	 * The "Revisr_Process" object.
	 * @var object
	 */
	private $process;

	/**
	 * The "Revisr_Settings" object.
	 * @var object
	 */
	private $settings;

	/**
	 * The "Revisr_Cron" object.
	 * @var object
	 */
	private $cron;

	/**
	 * The "Revisr_Remote" object.
	 * @var object
	 */
	private $remote;


	/**
	 * Empty construct, use get_instance() instead.
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
			self::$instance 				= new self;
			self::$instance->plugin_name 	= 'revisr';
			self::$instance->table_name 	= self::$instance->get_table_name();
			self::$instance->options 		= self::$instance->get_options();
			
			self::$instance->define_constants();
			self::$instance->load_dependencies();
			self::$instance->set_locale();
			self::$instance->load_public_hooks();

			if ( is_admin() ) {
				self::$instance->load_admin_hooks();
			}
		}
		return self::$instance;
	}

	/**
	 * Defines the constants used by Revisr.
	 * @access public
	 */
	public function define_constants() {
		// Defines the plugin root file.
		if ( ! defined( 'REVISR_FILE' ) ) {
			define( 'REVISR_FILE', __FILE__ );
		}

		// Defines the plugin path.
		if ( ! defined( 'REVISR_PATH' ) ) {
			define( 'REVISR_PATH', plugin_dir_path( REVISR_FILE ) );
		}

		// Defines the plugin URL.
		if ( ! defined( 'REVISR_URL' ) ) {
			define( 'REVISR_URL', plugin_dir_url( REVISR_FILE ) );
		}

		// Defines the plugin version.
		if ( ! defined( 'REVISR_VERSION' ) ) {
			define( 'REVISR_VERSION', '1.8' );
		}
	}

	/**
	 * Loads the plugin dependencies.
	 * @access public
	 */
	public function load_dependencies() {
		require_once REVISR_PATH . 'includes/class-revisr-i18n.php';
		require_once REVISR_PATH . 'includes/class-revisr-git.php';
		require_once REVISR_PATH . 'includes/class-revisr-admin.php';
		require_once REVISR_PATH . 'includes/class-revisr-remote.php';
		require_once REVISR_PATH . 'includes/class-revisr-db.php';
		require_once REVISR_PATH . 'includes/class-revisr-git-callback.php';
		require_once REVISR_PATH . 'includes/class-revisr-cron.php';
		require_once REVISR_PATH . 'includes/class-revisr-process.php';

		if ( is_admin() ) {
			require_once REVISR_PATH . 'includes/class-revisr-commits.php';
			require_once REVISR_PATH . 'includes/class-revisr-settings.php';
			require_once REVISR_PATH . 'includes/class-revisr-settings-fields.php';	
			require_once REVISR_PATH . 'includes/class-revisr-admin-setup.php';
		}
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 * @access private
	 */
	private function set_locale() {
		$revisr_i18n = new Revisr_i18n();
		$revisr_i18n->set_domain( $this->get_plugin_name() );
		add_action( 'plugins_loaded', array( $revisr_i18n, 'load_plugin_textdomain' ) );
	}

	/**
	 * Loads hooks required regardless of user role.
	 * @access private
	 */
	private function load_public_hooks() {
		// Initialize the necessary classes.
		self::$instance->git 		= new Revisr_Git();
		self::$instance->admin 		= new Revisr_Admin();
		self::$instance->db 		= new Revisr_DB();
		self::$instance->cron 		= new Revisr_Cron();
		self::$instance->process 	= new Revisr_Process();

		// Allows the cron to run with no admin login.
		add_filter( 'cron_schedules', array( self::$instance->cron, 'revisr_schedules' ) );
		add_action( 'revisr_cron', array( self::$instance->cron, 'run_automatic_backup' ) );
		add_action( 'admin_post_nopriv_revisr_update', array( self::$instance->process, 'process_pull' ) );
	}

	/**
	 * Loads the hooks used in the plugin dashboard.
	 * @access private
	 */
	private function load_admin_hooks() {

		// Initialize the necessary classes.
		self::$instance->commits 		= new Revisr_Commits();
		self::$instance->settings 		= new Revisr_Settings();
		self::$instance->admin_setup 	= new Revisr_Setup( self::$instance->options );

		// Check for compatibility.
		self::$instance->check_compatibility();
		
		// Register the "revisr_commits" custom post type.
		add_action( 'init', array( self::$instance->commits, 'post_types' ) );
		add_action( 'pre_get_posts', array( self::$instance->commits, 'filters' ) );
		add_action( 'views_edit-revisr_commits', array( self::$instance->commits, 'custom_views' ) );
		add_action( 'load-edit.php', array( self::$instance->commits, 'default_views' ) );
		add_action( 'post_row_actions', array( self::$instance->commits, 'custom_actions' ) );
		add_action( 'manage_edit-revisr_commits_columns', array( self::$instance->commits, 'columns' ) );
		add_action( 'manage_revisr_commits_posts_custom_column', array( self::$instance->commits, 'custom_columns' ) );	
		add_action( 'admin_enqueue_scripts', array( self::$instance->commits, 'disable_autodraft' ) );
		add_filter( 'post_updated_messages', array( self::$instance->commits, 'custom_messages' ) );
		add_filter( 'bulk_post_updated_messages', array( self::$instance->commits, 'bulk_messages' ), 10, 2 );

		// Quick actions.
		add_action( 'wp_ajax_render_alert', array( self::$instance->admin, 'render_alert' ) );
		add_action( 'wp_ajax_ajax_button_count', array( self::$instance->admin, 'ajax_button_count' ) );
		add_action( 'wp_ajax_pending_files', array( self::$instance->admin, 'pending_files' ) );
		add_action( 'wp_ajax_committed_files', array( self::$instance->admin, 'committed_files' ) );
		add_action( 'wp_ajax_view_diff', array( self::$instance->admin, 'view_diff' ) );
		add_action( 'wp_ajax_verify_remote', array( self::$instance->git, 'verify_remote' ) );

		// Database backups.
		add_action( 'wp_ajax_backup_db', array( self::$instance->db, 'backup' ) );
		add_action( 'admin_post_revert_db', array( self::$instance->db, 'restore' ) );

		// General admin customizations.
		add_action( 'admin_notices', array( self::$instance->admin_setup, 'site5_notice' ) );
		add_action( 'load-post.php', array( self::$instance->admin_setup, 'meta' ) );
		add_action( 'load-post-new.php', array( self::$instance->admin_setup, 'meta' ) );
		add_action( 'admin_menu', array( self::$instance->admin_setup, 'menus' ), 2 );
		add_action( 'admin_post_delete_branch_form', array( self::$instance->admin_setup, 'delete_branch_form' ) );
		add_action( 'admin_post_merge_branch_form', array ( self::$instance->admin_setup, 'merge_branch_form' ) );
		add_action( 'admin_post_import_tables_form', array( self::$instance->admin_setup, 'import_tables_form' ) );
		add_action( 'admin_enqueue_scripts', array( self::$instance->admin_setup, 'revisr_scripts' ) );
		add_action( 'admin_bar_menu', array( self::$instance->admin_setup, 'admin_bar' ), 999 );
		add_filter( 'custom_menu_order', array( self::$instance->admin_setup, 'revisr_commits_submenu_order' ) );
		add_action( 'wp_ajax_recent_activity', array( self::$instance->admin_setup, 'recent_activity' ) );

		if ( get_option( 'revisr_db_version' ) === '1.0' ) {
			add_action( 'admin_init', array( self::$instance->admin_setup, 'do_upgrade' ) );
		}

		// Admin-specific actions.
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
	}

	/**
	 * Returns user options as a single array.
	 * @access public
	 * @return array $options An array of user-stored options.
	 */
	public static function get_options() {
		$old 		= get_option( 'revisr_settings' ) ? get_option( 'revisr_settings' ) : array();
		$general 	= get_option( 'revisr_general_settings' ) ? get_option( 'revisr_general_settings' ) : array();
		$remote 	= get_option( 'revisr_remote_settings' ) ? get_option( 'revisr_remote_settings' ) : array();
		$database 	= get_option( 'revisr_database_settings' ) ? get_option( 'revisr_database_settings' ) : array();
		$options 	= array_merge( $old, $general, $remote, $database );
		return $options;
	}

	/**
	 * Returns the name of the plugin.
	 * @access public
	 * @return 
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Returns the name of the database table for the plugin.
	 * @access public
	 * @return string The name of the database table.
	 */
	public static function get_table_name() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'revisr';
		return $table_name;
	}

	/**
	 * Displays the link to the settings on the WordPress plugin page.
	 * @access public
	 * @param array $links The links assigned to Revisr.
	 */
	public static function revisr_settings_link( $links ) {
		$settings_link = '<a href="admin.php?page=revisr_settings">' . __( 'Settings', 'revisr' ) . '</a>'; 
  		array_unshift( $links, $settings_link ); 
  		return $links; 
	}

	/**
	 * Makes sure that Revisr is compatible in the current environment.
	 * @access public
	 */
	public function check_compatibility() {
		if ( ! function_exists( 'exec' ) ) {
			Revisr_Admin::alert( __( 'It appears that you don\'t have the PHP exec() function enabled on your server. This can be enabled in your php.ini.
				Check with your web host if you\'re not sure what this means.', 'revisr'), true );
			return false;
		}
		$git = self::$instance->git;
		if ( is_dir( $git->dir . '/.git/' ) && !is_writeable( $git->dir . '/.git/' ) ) {
			Revisr_Admin::alert( __( 'Revisr requires write permissions to the repository. The recommended settings are 755 for directories, and 644 for files.', 'revisr' ), true );
			return false;
		}
		return true;
	}

	/**
	 * Installs the database table.
	 * @access public
	 */
	public static function revisr_install() {
		$table_name = self::$instance->table_name;
		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			message TEXT,
			event VARCHAR(42) NOT NULL,
			UNIQUE KEY id (id)
			);";
		
	  	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	   	dbDelta( $sql );
	   	if ( get_option( 'revisr_db_version' ) === false ) {
	   		add_option( 'revisr_db_version', '1.1' );
	   	}
	}	

}

/**
 * Returns a single instance of the Revisr plugin.
 * 
 * @since  1.8.2
 * @return object
 */
function revisr() {
	return Revisr::get_instance();
}

// Runs the plugin.
$revisr = revisr();

// Registers the activation hook.
register_activation_hook( REVISR_FILE, array( 'Revisr', 'revisr_install' ) );

// Adds the settings link to the plugins page.
add_filter( 'plugin_action_links_'  . plugin_basename( REVISR_FILE ), array( 'Revisr', 'revisr_settings_link' ) );
