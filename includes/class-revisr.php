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

// Disallow direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr {

	/**
	 * User options and preferences.
	 * @var array
	 */
	public $options;

	/**
	 * The unique identifier of this plugin.
	 * @var string
	 */
	protected $plugin_name;

	/**
	 * The name of the database table to use for the plugin.
	 * @var string
	 */
	public $table_name;

	/**
	 * Loads the core functionality of the plugin.
	 * @access public
	 */
	public function __construct() {
		$this->options 		= $this->get_options();
		$this->plugin_name  = 'revisr';
		$this->table_name 	= $this->get_table_name();
		$this->load_dependencies();
		$this->set_locale();
		$this->revisr_commits_hooks();
		$this->revisr_process_hooks();
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
		require_once REVISR_PATH . 'includes/class-revisr-i18n.php';
		require_once REVISR_PATH . 'includes/class-revisr-git.php';
		require_once REVISR_PATH . 'includes/class-revisr-admin.php';
		require_once REVISR_PATH . 'includes/class-revisr-process.php';
		require_once REVISR_PATH . 'includes/class-revisr-commits.php';
		require_once REVISR_PATH . 'includes/class-revisr-admin-setup.php';
		require_once REVISR_PATH . 'includes/class-revisr-remote.php';
		require_once REVISR_PATH . 'includes/class-revisr-db.php';
		require_once REVISR_PATH . 'includes/class-revisr-git-callback.php';
		require_once REVISR_PATH . 'includes/class-revisr-cron.php';
		require_once REVISR_PATH . 'includes/class-revisr-settings.php';
		require_once REVISR_PATH . 'includes/class-revisr-settings-fields.php';
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
	 * Registers common functions used on Revisr pages in the wp-admin.
	 * @access private
	 */
	private function admin_hooks() {
		$revisr_admin 	= new Revisr_Admin();
		$revisr_git 	= new Revisr_Git();
		add_action( 'wp_ajax_render_alert', array( $revisr_admin, 'render_alert' ) );
		add_action( 'wp_ajax_ajax_button_count', array( $revisr_admin, 'ajax_button_count' ) );
		add_action( 'wp_ajax_pending_files', array( $revisr_admin, 'pending_files' ) );
		add_action( 'wp_ajax_committed_files', array( $revisr_admin, 'committed_files' ) );
		add_action( 'wp_ajax_view_diff', array( $revisr_admin, 'view_diff' ) );
		add_action( 'wp_ajax_verify_remote', array( $revisr_git, 'verify_remote' ) );
	}

	/**
	 * Registers hooks for the 'revisr_commits' custom post type.
	 * @access private
	 */
	private function revisr_commits_hooks() {
		$revisr_commits = new Revisr_Commits();
		add_action( 'init', array( $revisr_commits, 'post_types' ) );
		add_action( 'pre_get_posts', array( $revisr_commits, 'filters' ) );
		add_action( 'views_edit-revisr_commits', array( $revisr_commits, 'custom_views' ) );
		add_action( 'load-edit.php', array( $revisr_commits, 'default_views' ) );
		add_action( 'post_row_actions', array( $revisr_commits, 'custom_actions' ) );
		add_action( 'manage_edit-revisr_commits_columns', array( $revisr_commits, 'columns' ) );
		add_action( 'manage_revisr_commits_posts_custom_column', array( $revisr_commits, 'custom_columns' ) );	
		add_action( 'admin_enqueue_scripts', array( $revisr_commits, 'disable_autodraft' ) );
		add_filter( 'post_updated_messages', array( $revisr_commits, 'custom_messages' ) );
		add_filter( 'bulk_post_updated_messages', array( $revisr_commits, 'bulk_messages' ), 10, 2 );
	}

	/**
	 * Registers hooks for actions taken within the WordPress dashboard.
	 * @access private
	 */
	private function revisr_process_hooks() {
		$revisr_process = new Revisr_Process();
		add_action( 'init', array( $revisr_process, 'process_is_repo' ) );
		add_action( 'publish_revisr_commits', array( $revisr_process, 'process_commit' ) );
		add_action( 'admin_post_process_checkout', array( $revisr_process, 'process_checkout' ) );
		add_action( 'admin_post_process_create_branch', array( $revisr_process, 'process_create_branch' ) );
		add_action( 'admin_post_process_delete_branch', array( $revisr_process, 'process_delete_branch' ) );
		add_action( 'admin_post_process_merge', array( $revisr_process, 'process_merge' ) );
		add_action( 'admin_post_process_import', array( $revisr_process, 'process_import' ) );
		add_action( 'admin_post_init_repo', array( $revisr_process, 'process_init' ) );
		add_action( 'admin_post_process_revert', array( $revisr_process, 'process_revert' ) );
		add_action( 'admin_post_process_view_diff', array( $revisr_process, 'process_view_diff' ) );
		add_action( 'wp_ajax_discard', array( $revisr_process, 'process_discard' ) );
		add_action( 'wp_ajax_process_push', array( $revisr_process, 'process_push' ) );
		add_action( 'wp_ajax_process_pull', array( $revisr_process, 'process_pull' ) );
		add_action( 'admin_post_nopriv_revisr_update', array( $revisr_process, 'process_pull' ) );
	}

	/**
	 * Registers hooks for the plugin setup. 
	 * @access private
	 */
	private function admin_setup_hooks() {
		$revisr_setup = new Revisr_Setup( $this->options );
		add_action( 'admin_notices', array( $revisr_setup, 'site5_notice' ) );
		add_action( 'load-post.php', array( $revisr_setup, 'meta' ) );
		add_action( 'load-post-new.php', array( $revisr_setup, 'meta' ) );
		add_action( 'admin_menu', array( $revisr_setup, 'menus' ), 2 );
		add_action( 'admin_post_delete_branch_form', array( $revisr_setup, 'delete_branch_form' ) );
		add_action( 'admin_post_merge_branch_form', array ( $revisr_setup, 'merge_branch_form' ) );
		add_action( 'admin_post_import_tables_form', array( $revisr_setup, 'import_tables_form' ) );
		add_action( 'admin_enqueue_scripts', array( $revisr_setup, 'revisr_scripts' ) );
		add_action( 'admin_bar_menu', array( $revisr_setup, 'admin_bar' ), 999 );
		add_filter( 'custom_menu_order', array( $revisr_setup, 'revisr_commits_submenu_order' ) );
		add_action( 'wp_ajax_recent_activity', array( $revisr_setup, 'recent_activity' ) );
		$revisr_settings = new Revisr_Settings( $this->options );

		if ( get_option( 'revisr_db_version' ) === '1.0' ) {
			add_action( 'admin_init', array( $revisr_setup, 'do_upgrade' ) );
		}
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
		if ( ! function_exists( 'exec' ) ) {
			Revisr_Admin::alert( __( 'It appears that you don\'t have the PHP exec() function enabled on your server. This can be enabled in your php.ini.
				Check with your web host if you\'re not sure what this means.', 'revisr'), true );
			return false;
		}
		$git = new Revisr_Git;
		if ( is_dir( $git->dir . '/.git/' ) && !is_writeable( $git->dir . '/.git/' ) ) {
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
	   	if ( get_option( 'revisr_db_version' ) === false ) {
	   		add_option( 'revisr_db_version', '1.1' );
	   	}
	}
}
