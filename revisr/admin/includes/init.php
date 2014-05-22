<?php
/**
 * init.php
 *
 * WordPress hooks and functions for the 'wp-admin'.
 *
 * @package   Revisr
 * @author    Matt Shaw <matt@expandedfronts.com>
 * @license   GPL-2.0+
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 */

class revisr_init
{
	public function __construct()
	{
		if ( is_admin() ) {
			add_action( 'init', array($this, 'post_types') );
			add_action( 'load-post.php', array($this, 'meta') );
			add_action( 'load-post-new.php', array($this, 'meta') );
			add_action( 'admin_menu', array($this, 'menus') );
		}
	}

	public function post_types()
	{
		$labels = array(
			'name'                => 'Commits',
			'singular_name'       => 'Commit',
			'menu_name'           => 'Commits',
			'parent_item_colon'   => '',
			'all_items'           => 'Commits',
			'view_item'           => 'View Commit',
			'add_new_item'        => 'New Commit',
			'add_new'             => 'New Commit',
			'edit_item'           => 'Edit Commit',
			'update_item'         => 'Update Commit',
			'search_items'        => 'Search Commits',
			'not_found'           => 'No commits found yet, why not create a new one?',
			'not_found_in_trash'  => 'No commits in trash.',
		);
		$capabilities = array(
			'edit_post'           => 'activate_plugins',
			'read_post'           => 'activate_plugins',
			'delete_post'         => 'activate_plugins',
			'edit_posts'          => 'activate_plugins',
			'edit_others_posts'   => 'activate_plugins',
			'publish_posts'       => 'activate_plugins',
			'read_private_posts'  => 'activate_plugins',
		);
		$args = array(
			'label'               => 'revisr_commits',
			'description'         => 'Commits made through Revisr',
			'labels'              => $labels,
			'supports'            => array( 'title', 'author'),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'revisr',
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 5,
			'menu_icon'           => '',
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capabilities'        => $capabilities,
		);
		register_post_type( 'revisr_commits', $args );
	}

	public function meta()
	{
		if ($_GET['action'] == 'edit') {
			add_meta_box( 'revisr_committed_files', 'Committed Files', array('Revisr', 'committed_files'), 'revisr_commits' );
		}
		else {
			add_meta_box( 'revisr_pending_files', 'Pending Files', array('Revisr', 'pending_files'), 'revisr_commits' );
		}
	}

	public function menus()
	{
		$menu = add_menu_page( 'Revisr', 'Revisr', 'manage_options', 'revisr', array($this, 'revisr_dash'), plugins_url( 'revisr/img/revisrlogo_small-white.png' ) );
		$settings_menu = add_submenu_page( 'revisr', 'Revisr - Settings', 'Settings', 'manage_options', 'revisr_settings', array($this, 'revisr_settings') );
		add_action( 'admin_print_styles-' . $menu, array($this, 'styles') );
		add_action( 'admin_print_scripts-' . $menu, array($this, 'scripts') );
	}

	public function styles()
	{
		wp_enqueue_style( 'revisr_css', plugin_dir_url( __FILE__ ) . 'assets/css/revisr.css' );
	}

	public function scripts()
	{

	}


}