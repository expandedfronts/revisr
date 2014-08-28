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

class Revisr
{

	/**
	 * The WordPress database object.
	 */
	public $wpdb;
	
	/**
	 * Name of the plugin.
	 */
	public $plugin;

	/**
	 * User options.
	 */
	private $options;

	/**
	 * The name of the database table.
	 */
	private $table_name;

	/**
	 * Initializes the plugin.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->plugin 		= plugin_basename( __FILE__ );
		$this->table_name 	= $this->wpdb->prefix . 'revisr';

		//Load dependancies and WordPress hooks.
		$this->load_dependancies();
		$this->git_hooks();
		$this->db_hooks();

		if ( is_admin() ) {
			add_action( 'plugins_loaded', array( $this, 'admin_hooks' ) );
		}
	}

	/**
	 * Loads required classes.
	 * @access private
	 */
	private function load_dependancies() {
		require_once plugin_dir_path( __FILE__ ) . 'class-revisr-admin.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-revisr-db.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-revisr-git.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-revisr-settings.php';
	}

	/**
	 * Loads hooks for rendering the WordPress admin.
	 * @access public
	 */
	public function admin_hooks() {
		$admin = new Revisr_Admin();
		if ( is_super_admin() ) {
			
			$plugin 		= $this->plugin;
			$this->options 	= Revisr_Admin::options();
			add_action( 'init', array( $admin, 'revisr_post_types' ) );
			add_action( 'admin_notices', array( $admin, 'site5_notice' ) );
			add_action( 'load-edit.php', array( $admin, 'default_views' ) );
			add_action( 'load-post.php', array( $admin, 'meta' ) );
			add_action( 'load-post-new.php', array( $admin, 'meta' ) );
			add_action( 'pre_get_posts', array( $admin, 'filters' ) );
			add_action( 'views_edit-revisr_commits', array( $admin, 'custom_views' ) );
			add_action( 'post_row_actions', array( $admin, 'custom_actions' ) );
			add_action( 'admin_menu', array( $admin, 'menus' ), 2 );
			add_action( 'admin_post_delete_branch_form', array( $admin, 'delete_branch_form' ) );
			add_action( 'manage_edit-revisr_commits_columns', array( $admin, 'columns' ) );
			add_action( 'manage_revisr_commits_posts_custom_column', array( $admin, 'custom_columns' ) );
			add_action( 'admin_enqueue_scripts', array( $admin, 'revisr_scripts' ) );
			add_action( 'admin_bar_menu', array( $admin, 'admin_bar' ), 999 );
			add_action( 'admin_enqueue_scripts', array( $admin, 'disable_autodraft' ) );
			add_filter( 'post_updated_messages', array( $admin, 'revisr_commits_custom_messages' ) );
			add_filter( 'bulk_post_updated_messages', array( $admin, 'revisr_commits_bulk_messages' ), 10, 2 );
			add_filter( 'custom_menu_order', array( $admin, 'revisr_commits_submenu_order' ) );
			add_filter( "plugin_action_links_$plugin", array( $admin, 'settings_link' ) );
			add_action( 'wp_ajax_recent_activity', array( $admin, 'recent_activity' ) );
			$revisr_settings = new Revisr_Settings();
		}
	}

	/**
	 * Loads hooks for processing git/quick actions.
	 * @access private
	 */
	private function git_hooks() {
		$git = new Revisr_Git();
		add_action( 'publish_revisr_commits', array( $git, 'commit' ) );
		add_action( 'admin_post_checkout', array( $git, 'checkout' ) );
		add_action( 'admin_post_create_branch', array( $git, 'create_branch' ) );
		add_action( 'admin_post_delete_branch', array( $git, 'delete_branch' ) );
		add_action( 'admin_post_revert', array( $git, 'revert' ) );
		add_action( 'admin_post_view_diff', array( $git, 'view_diff' ) );

		if ( isset( $this->options['auto_pull'] ) ) {
			add_action( 'admin_post_nopriv_revisr_update', array( $git, 'pull' ) );
		}

		add_action( 'wp_ajax_count_unpulled', array( $git, 'count_unpulled' ) );
		add_action( 'wp_ajax_count_unpushed', array( $git, 'count_unpushed' ) );
		add_action( 'wp_ajax_new_commit', array( $git, 'new_commit' ) );
		add_action( 'wp_ajax_discard', array( $git, 'discard' ) );
		add_action( 'wp_ajax_push', array( $git, 'push' ) );
		add_action( 'wp_ajax_pull', array( $git, 'pull' ) );
		add_action( 'wp_ajax_view_diff', array( $git, 'view_diff' ) );
		add_action( 'wp_ajax_pending_files', array( $git, 'pending_files' ) );
		add_action( 'wp_ajax_committed_files', array( $git, 'committed_files' ) );
		add_action( 'wp_ajax_verify_remote', array( $git, 'verify_remote' ) );
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
	   	add_option( "revisr_db_version", "1.0" );
	}

	/**
	 * Displays the link to the settings on the WordPress plugin page.
	 * @access public
	 * @param array $links The links assigned to Revisr.
	 */
	public function revisr_settings_link( $links ) {
		$settings_link = '<a href="admin.php?page=revisr_settings">' . __( 'Settings', 'revisr') . '</a>'; 
  		array_unshift($links, $settings_link); 
  		return $links; 
	}

	/**
	 * Checks to make sure that exec is enabled and Git is installed correctly on the server.
	 * @access public
	 */
	public static function check_compatibility() {
		$error = '';
		if ( ! function_exists( 'exec' ) ) {
			$error .= __( '<p><strong>WARNING:</strong> Your server does not appear to support php exec() and/or passthru(). <br> 
			These functions are necessary for Revisr to work correctly. Contact your web host if you\'re not sure how to activate these functions.</p>', 'revisr' );
			return $error;
		}

		if ( Revisr_Git::run( 'version' ) === false || Revisr_Git::run( 'status' ) === false ) {
			$error .= __( '<p><strong>WARNING:</strong> No Git repository detected. Revisr requires that Git be installed on the server and the parent WordPress installation be in the root directory of a Git repository.</p>', 'revisr' );
			return $error;
		}

		$top_level = Revisr_Git::run( 'rev-parse --show-toplevel' );
		$git_dir 	= $top_level[0] . '/.git/';
		if ( ! is_writable( $git_dir ) ) {
			$error .= __( '<p><strong>WARNING:</strong> Revisr cannot write to the ".git/" directory.<br>Please make sure that write permissions are set for this directory. The recommended settings are 755 for directories, and 644 for files.');
			return $error;
		}		
	}
}