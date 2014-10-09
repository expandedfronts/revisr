<?php
/**
 * class-revisr.php
 *
 * Processes the main functionality of the plugin.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 */

class Revisr {

	/**
	 * The WordPress database class.
	 */
	public $wpdb;

	/**
	 * The main Git class.
	 */
	public $git;

	/**
	 * User options and preferences.
	 */
	public $options;

	/**
	 * The unique identifier of this plugin.
	 */
	protected $plugin_name;

	/**
	 * The name of the database table to use for the plugin.
	 */
	public $table_name;

	/**
	 * The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the Dashboard and
	 * the public-facing side of the site.
	 *
	 * @since    1.7.0
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb 		= $wpdb;
		$this->options 		= $this->get_options();
		$this->plugin_name  = 'revisr';
		$this->table_name 	= $this->get_table_name();
		$this->version 		= '1.7.0';
		$this->load_dependencies();
		$this->set_locale();
		$this->admin_setup_hooks();		
		$this->admin_hooks();
		$this->db_hooks();
		$this->cron_hooks();
		$this->check_compatibility();
	}

	/**
	 * Load the required dependencies for this plugin.
	 * @access private
	 */
	private function load_dependencies() {
		require_once plugin_dir_path( __FILE__ ) . 'class-revisr-i18n.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-revisr-admin.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-revisr-admin-setup.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-revisr-db.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-revisr-git.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-revisr-git-callback.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-revisr-cron.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-revisr-settings.php';
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 * @access private
	 */
	private function set_locale() {
		$plugin_i18n = new Revisr_i18n();
		$plugin_i18n->set_domain( $this->get_plugin_name() );
		add_action( 'plugins_loaded', array( $plugin_i18n, 'load_plugin_textdomain' ) );
	}

	/**
	 * Registers common functions used on Revisr pages in the wp-admin.
	 * @access private
	 */
	private function admin_hooks() {
		$revisr_admin 	= new Revisr_Admin( $this->options, $this->get_table_name() );
		$revisr_git 	= new Revisr_Git();
		add_action( 'wp_ajax_render_alert', array( $revisr_admin, 'render_alert' ) );
		add_action( 'publish_revisr_commits', array( $revisr_admin, 'process_commit' ) );
		add_action( 'admin_post_process_checkout', array( $revisr_admin, 'process_checkout' ) );
		add_action( 'admin_post_process_create_branch', array( $revisr_admin, 'process_create_branch' ) );
		add_action( 'admin_post_process_delete_branch', array( $revisr_admin, 'process_delete_branch' ) );
		add_action( 'admin_post_process_merge', array( $revisr_admin, 'process_merge' ) );
		add_action( 'admin_post_init_repo', array( $revisr_git, 'init_repo' ) );
		add_action( 'admin_post_process_revert', array( $revisr_admin, 'process_revert' ) );
		add_action( 'admin_post_process_view_diff', array( $revisr_admin, 'process_view_diff' ) );
		if ( isset( $this->options['auto_pull'] ) ) {
			add_action( 'admin_post_nopriv_revisr_update', array( $revisr_admin, 'pull' ) );
		}
		add_action( 'wp_ajax_ajax_button_count', array( $revisr_admin, 'ajax_button_count' ) );
		add_action( 'wp_ajax_pending_files', array( $revisr_admin, 'pending_files' ) );
		add_action( 'wp_ajax_committed_files', array( $revisr_admin, 'committed_files' ) );
		add_action( 'wp_ajax_discard', array( $revisr_admin, 'process_discard' ) );
		add_action( 'wp_ajax_process_push', array( $revisr_admin, 'process_push' ) );
		add_action( 'wp_ajax_process_pull', array( $revisr_admin, 'process_pull' ) );
		add_action( 'wp_ajax_view_diff', array( $revisr_admin, 'view_diff' ) );
		add_action( 'wp_ajax_verify_remote', array( $revisr_git, 'verify_remote' ) );
	}

	/**
	 * Registers hooks for the plugin setup. 
	 * @access private
	 */
	private function admin_setup_hooks() {
		$revisr_setup = new Revisr_Setup( $this->options );
		$plugin = $this->plugin_name;
		add_action( 'init', array( $revisr_setup, 'revisr_post_types' ) );
		add_action( 'admin_notices', array( $revisr_setup, 'site5_notice' ) );
		add_action( 'load-edit.php', array( $revisr_setup, 'default_views' ) );
		add_action( 'load-post.php', array( $revisr_setup, 'meta' ) );
		add_action( 'load-post-new.php', array( $revisr_setup, 'meta' ) );
		add_action( 'pre_get_posts', array( $revisr_setup, 'filters' ) );
		add_action( 'views_edit-revisr_commits', array( $revisr_setup, 'custom_views' ) );
		add_action( 'post_row_actions', array( $revisr_setup, 'custom_actions' ) );
		add_action( 'admin_menu', array( $revisr_setup, 'menus' ), 2 );
		add_action( 'admin_post_delete_branch_form', array( $revisr_setup, 'delete_branch_form' ) );
		add_action( 'manage_edit-revisr_commits_columns', array( $revisr_setup, 'columns' ) );
		add_action( 'manage_revisr_commits_posts_custom_column', array( $revisr_setup, 'custom_columns' ) );
		add_action( 'admin_enqueue_scripts', array( $revisr_setup, 'revisr_scripts' ) );
		add_action( 'admin_bar_menu', array( $revisr_setup, 'admin_bar' ), 999 );
		add_action( 'admin_enqueue_scripts', array( $revisr_setup, 'disable_autodraft' ) );
		add_filter( 'post_updated_messages', array( $revisr_setup, 'revisr_commits_custom_messages' ) );
		add_filter( 'bulk_post_updated_messages', array( $revisr_setup, 'revisr_commits_bulk_messages' ), 10, 2 );
		add_filter( 'custom_menu_order', array( $revisr_setup, 'revisr_commits_submenu_order' ) );
		add_filter( "plugin_action_links_$plugin", array( $revisr_setup, 'settings_link' ) );
		add_action( 'wp_ajax_recent_activity', array( $revisr_setup, 'recent_activity' ) );
		$revisr_settings = new Revisr_Settings( $this->options );
	}

	/**
	 * Loads hooks for processing crons.
	 * @access private
	 */
	private function cron_hooks() {
		$revisr_cron = new Revisr_Cron();
		add_filter( 'cron_schedules', array( $revisr_cron, 'revisr_schedules' ) );
		add_action( 'revisr_cron', array( $revisr_cron, 'run_automatic_backup' ) );
	}

	/**
	 * Loads hooks for processing database actions.
	 * @access private
	 */
	private function db_hooks() {
		$db = new Revisr_DB();
		add_action( 'wp_ajax_backup_db', array( $db, 'backup' ) );
		add_action( 'admin_post_revert_db', array( $db, 'restore' ) );
	}

	/**
	 * Makes sure that Revisr is compatible in the current environment.
	 * @access public
	 */
	public function check_compatibility() {
		$git = new Revisr_Git;
		if ( ! function_exists( 'exec' ) ) {
			Revisr_Admin::alert( __( 'It appears that you don\'t have the PHP exec() function enabled on your server. This can be enabled in your php.ini.
				Check with your web host if you\'re not sure what this means.', 'revisr'), true );
			return false;
		}
		if ( ! is_writeable( $git->dir ) ) {
			Revisr_Admin::alert( __( 'Revisr requires write permissions to the repository. The recommended settings are 755 for directories, and 644 for files.', 'revisr' ), true );
			return false;
		}
		return true;
	}

	/**
	 * Returns user options as a single array.
	 * @access public
	 * @return array $options The array of user-stored options.
	 */
	public static function get_options() {
		$old	 	= get_option( 'revisr_settings' );
		if ( ! $old ) {
			$old = array();
		}
		$general 	= get_option( 'revisr_general_settings' );
		if ( ! $general ) {
			$general = array();
		}
		$remote 	= get_option( 'revisr_remote_settings' );
		if ( ! $remote ) {
			$remote = array();
		}
		$database 	= get_option( 'revisr_database_settings' );
		if ( ! $database ) {
			$database = array();
		}
		$options = array_merge( $old, $general, $remote, $database );
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
	 * Retrieve the version number of the plugin.
	 * @access public
	 * @return string The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Displays the link to the settings on the WordPress plugin page.
	 * @access public
	 * @param array $links The links assigned to Revisr.
	 */
	public function revisr_settings_link( $links ) {
		$settings_link = '<a href="admin.php?page=revisr_settings">' . __( 'Settings', 'revisr' ) . '</a>'; 
  		array_unshift( $links, $settings_link ); 
  		return $links; 
	}	

	/**
	 * Installs the database table.
	 * @access public
	 */
	public function revisr_install() {
		$sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			message TEXT,
			event VARCHAR(42) NOT NULL,
			UNIQUE KEY id (id)
			);";
		
	  	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	   	dbDelta( $sql );
	   	add_option( 'revisr_db_version', '1.0' );
	}	
}
