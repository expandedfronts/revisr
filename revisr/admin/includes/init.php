<?php
/**
 * init.php
 *
 * WordPress hooks and functions for the 'wp-admin'.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts
 */

include "functions.php";

class revisr_init
{
	

	private $dir;
	private $options;
	private $table_name;

	public function __construct()
	{
		global $wpdb;

		$this->wpdb = $wpdb;
		$this->options = get_option('revisr_settings');

		$this->table_name = $wpdb->prefix . "revisr";
		$this->dir = plugin_dir_path( __FILE__ );

		if ( is_admin() ) {
			add_action( 'init', array($this, 'post_types') );
			add_action( 'admin_init', array($this, 'settings_init'));
			add_action( 'load-post.php', array($this, 'meta') );
			add_action( 'load-post-new.php', array($this, 'meta') );
			add_action( 'pre_get_posts', array($this, 'filters') );
			add_action( 'views_edit-revisr_commits', array($this, 'custom_views') );
			add_action( 'post_row_actions', array($this, 'custom_actions') );
			add_action( 'admin_menu', array($this, 'menus'), 2 );
			add_action( 'manage_edit-revisr_commits_columns', array($this, 'columns') );
			add_action( 'manage_revisr_commits_posts_custom_column', array($this, 'custom_columns') );
			add_action( 'admin_enqueue_scripts', array($this, 'styles') );
			add_action( 'admin_enqueue_scripts', array($this, 'scripts') );
			add_filter( 'post_updated_messages', array($this, 'revisr_commits_custom_messages') );
			add_filter( 'bulk_post_updated_messages', array($this, 'revisr_commits_bulk_messages'), 10, 2 );
			add_filter( 'custom_menu_order', array($this, 'revisr_commits_submenu_order') );
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
		if (isset($_GET['action'])) {
			if ($_GET['action'] == 'edit') {
				add_meta_box( 'revisr_committed_files', 'Committed Files', array($this, 'committed_files_meta'), 'revisr_commits', 'normal', 'high' );
			}			
		}
		
		else {
			add_meta_box( 'revisr_pending_files', 'Pending Files', array($this, 'pending_files_meta'), 'revisr_commits', 'normal', 'high' );
		}
	}

	public function menus()
	{
		$menu = add_menu_page( 'Dashboard', 'Revisr', 'manage_options', 'revisr', array($this, 'revisr_dashboard'), plugins_url( 'revisr/assets/img/white_18x20.png' ) );
		add_submenu_page( 'revisr', 'Revisr - Dashboard', 'Dashboard', 'manage_options', 'revisr', array($this, 'revisr_dashboard') );
		$settings_hook = add_submenu_page( 'revisr', 'Revisr - Settings', 'Settings', 'manage_options', 'revisr_settings', array($this, 'revisr_settings') );
		add_submenu_page( NULL,'View Diff','View Diff','manage_options','view_diff', array($this, 'view_diff') );
		add_action( 'load-'.$settings_hook, array($this, 'update_settings') );
		add_action( 'admin_print_styles-' . $menu, array($this, 'styles') );
		add_action( 'admin_print_scripts-' . $menu, array($this, 'scripts') );
	}

	public function revisr_commits_submenu_order($menu_ord)
	{
		global $submenu;

	    $arr = array();
	    $arr[] = $submenu['revisr'][0];
	    $arr[] = $submenu['revisr'][2];
	    $arr[] = $submenu['revisr'][1];
	    $submenu['revisr'] = $arr;

	    return $menu_ord;
	}

	public function revisr_dashboard()
	{
		include_once $this->dir . "../templates/dashboard.php";
	}

	public function view_diff()
	{
		include_once $this->dir . "../templates/view_diff.php";
	}

	public function settings_init()
	{
		register_setting(
			'revisr_option_group',
			'revisr_settings',
			array($this, 'sanitize')
		);

        add_settings_section(
            'revisr_general_config', // ID
            '', // Title
            array( $this, 'general_config_callback' ), // Callback
            'revisr_settings' // Page
        );  

        add_settings_field(
            'username', // ID
            'Username', // Title 
            array( $this, 'username_callback' ), // Callback
            'revisr_settings', // Page
            'revisr_general_config' // Section           
        );      

        add_settings_field(
            'email', 
            'Email', 
            array( $this, 'email_callback' ), 
            'revisr_settings', 
            'revisr_general_config'
        );

        add_settings_field(
            'remote_url', 
            'Remote URL', 
            array( $this, 'remote_url_callback' ), 
            'revisr_settings', 
            'revisr_general_config'
        );

        add_settings_field(
        	'gitignore',
        	'Files / Directories to add to .gitignore',
        	array( $this, 'gitignore_callback'),
        	'revisr_settings',
        	'revisr_general_config'
    	);

    	add_settings_field(
    		'notifications',
    		'Enable email notifications?',
    		array($this, 'notifications_callback'),
    		'revisr_settings',
    		'revisr_general_config'
		);

	}

	public function general_config_callback()
	{
		//print "Enter your settings below:";
	}

	public function username_callback()
	{
		printf(
            '<input type="text" id="username" name="revisr_settings[username]" value="%s" class="regular-text" />
            <br><span class="description">Username to commit with in git.</span>',
            isset( $this->options['username'] ) ? esc_attr( $this->options['username']) : ''
        );
	}

	public function email_callback()
	{
		printf(
            '<input type="text" id="email" name="revisr_settings[email]" value="%s" class="regular-text" />
            <br><span class="description">Used for notifications and git.</span>',
            isset( $this->options['email'] ) ? esc_attr( $this->options['email']) : ''
        );
	}

	public function remote_url_callback()
	{
		printf(
			'<input type="text" id="remote_url" name="revisr_settings[remote_url]" value="%s" class="regular-text" placeholder="https://user:pass@host.com/user/example.git" />
			<br><span class="description">Optional. Useful if you need to authenticate over "https://" instead of SSH, or if the remote has not been set.</span>',
			isset( $this->options['remote_url'] ) ? esc_attr( $this->options['remote_url']) : ''
			);
	}

	public function gitignore_callback()
	{
		printf(
            '<textarea id="gitignore" name="revisr_settings[gitignore]" rows="6" />%s</textarea>
            <br><span class="description">Add files or directories to be ignored here, one per line.</span>',
            isset( $this->options['gitignore'] ) ? esc_attr( $this->options['gitignore']) : ''
		);
	}

	public function notifications_callback()
	{
		printf(
			'<input type="checkbox" id="notifications" name="revisr_settings[notifications]" %s />',
			isset( $this->options['notifications'] ) ? "checked" : ''
		);
	}

	public function sanitize($input)
	{
		return $input;
	}

	public function revisr_settings()
	{
		include_once $this->dir . "../templates/settings.php";
	}

	public function update_settings()
	{
		if(isset($_GET['settings-updated']) && $_GET['settings-updated'])
	   {

	   	  chdir(ABSPATH);
	      file_put_contents(".gitignore", $this->options['gitignore']);
	      $options = get_option('revisr_settings');
	      if ($options['username'] != "") {
	      	git('config user.name "' . $options['username'] . '"');
	      }
	      if ($options['email'] != "") {
	      	git('config user.email "' . $options['email'] . '"');
	      }
	      if ($options['remote_url'] != "") {
	      	git('config remote.origin.url ' . $options['remote_url']);
	      }
	      chdir($this->dir);
	   }
	}

	public function custom_actions($actions)
	{
		if (get_post_type() == 'revisr_commits')
			{
				if (isset($actions)) {
					unset( $actions['edit'] );
			        unset( $actions['view'] );
			        unset( $actions['trash'] );
			        unset( $actions['inline hide-if-no-js'] );

			        $url = get_admin_url() . "post.php?post=" . get_the_ID() . "&action=edit";

			        $actions['view'] = "<a href='{$url}'>View</a>";
			        $commit_meta = get_post_custom_values('commit_hash', get_the_ID());
			        $commit_hash = unserialize($commit_meta[0]);
			        $actions['revert'] = "<a href='" . get_admin_url() . "admin-post.php?action=revert&commit_hash={$commit_hash[0]}&post_id=" . get_the_ID() ."'>Revert</a>";
			    	return $actions;
				}
			}
	}

	public function filters($commits)
	{
		if ( isset($_GET['branch']) ) {
			$commits->set( 'meta_key', 'branch' );
			$commits->set( 'meta_value', $_GET['branch'] );
		}
		$commits->set('post_type', 'revisr_commits');

		return $commits;
	}

	public function count_commits($branch)
	{
		if ($branch == "all") {
			$num_commits = $this->wpdb->get_results("SELECT * FROM " . $this->wpdb->prefix . "postmeta WHERE meta_key = 'branch'");
		}
		else {
			$num_commits = $this->wpdb->get_results("SELECT * FROM " . $this->wpdb->prefix . "postmeta WHERE meta_key = 'branch' AND meta_value = '".$branch."'");
		}
		return count($num_commits);
	}

	public function custom_views($views)
	{

		$output = git("branch");

		global $wp_query;

		foreach ($output as $key => $value) {
			$branch = substr($value, 2);
    	    $class = ($wp_query->query_vars['meta_value'] == $branch) ? ' class="current"' : '';
	    	$views["$branch"] = sprintf(__('<a href="%s"'. $class .'>' . ucwords($branch) . ' <span class="count">(%d)</span></a>'),
	        admin_url('edit.php?post_type=revisr_commits&branch='.$branch),
	        $this->count_commits($branch));
		}
		if (!isset($_GET['branch']) && !isset($_GET['post_status'])) {
			$class = 'class="current"';
		}
		else {
			$class = '';
		}
		$views['all'] = sprintf(__('<a href="%s"' . $class . '>All <span class="count">(%d)</span></a>' ),
			admin_url('edit.php?post_type=revisr_commits'),
			$this->count_commits("all"));
		unset($views['publish']);
		//unset($views['trash']);
		if (isset($views)) {
			return $views;
		}
	}

	public function styles()
	{
		wp_enqueue_style( 'revisr_css', plugins_url() . '/revisr/assets/css/revisr.css' );
		wp_enqueue_style('thickbox');
	}

	public function scripts($hook)
	{
		
		wp_enqueue_script('alerts', plugins_url() . '/revisr/assets/js/dashboard.js');
		wp_enqueue_script('thickbox');

		if ($hook == 'post-new.php') {
			wp_enqueue_script('pending_files', plugins_url() . '/revisr/assets/js/pending_files.js');
		}
		if ($hook == 'post.php') {
			wp_enqueue_script('committed_files', plugins_url() . '/revisr/assets/js/committed_files.js');
			if (isset($_GET['post'])) {
				wp_localize_script('committed_files', 'committed_vars', array(
					'post_id' => $_GET['post'])
				);			
			}
		}
	}

	public function committed_files_meta()
	{
		echo "<div id='committed_files_result'></div>";
	}

	public function pending_files_meta()
	{
		$output = git("status --short");
		add_post_meta( get_the_ID(), 'committed_files', $output );
		add_post_meta( get_the_ID(), 'files_changed', count($output) );
		echo "<div id='pending_files_result'></div>";
	}

	public function columns()
	{
		$columns = array (
			'cb' => '<input type="checkbox" />',
			'hash' => __('ID'),
			'title' => __('Commit'),
			'branch' => __('Branch'),			
			'files_changed' => __('Files Changed'),
			'date' => __('Date'));
		return $columns;
	}

	public function custom_columns($column)
	{
		global $post;

		$post_id = get_the_ID();
		switch ($column) {
			case "hash": 
				$commit_meta = get_post_meta( $post_id, "commit_hash" );
				
				if (isset($commit_meta[0])) {
					$commit_hash = $commit_meta[0];
				}

				if (empty($commit_hash)) {
					echo __("Unknown");
				}
				else {
					echo $commit_hash[0];
				}
			break;
			case "branch":
				$branch_meta = get_post_meta( $post_id, "branch" );
				if ( isset($branch_meta[0]) ) {
					echo $branch_meta[0];
				}
			break;			
			case "files_changed":
				$files_meta = get_post_meta( $post_id, "files_changed" );
				if ( isset($files_meta[0]) ) {
					echo $files_meta[0];
				}
			break;
		}

	}

	public function revisr_commits_custom_messages($messages)
	{
		$post             = get_post();
		$post_type        = get_post_type( $post );
		$post_type_object = get_post_type_object( $post_type );

		$messages['revisr_commits'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Commit updated.', 'revisr_commits' ),
			2  => __( 'Custom field updated.', 'revisr_commits' ),
			3  => __( 'Custom field deleted.', 'revisr_commits' ),
			4  => __( 'Commit updated.', 'revisr_commits' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Commit restored to revision from %s', 'revisr_commits' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Commit published.', 'revisr_commits' ),
			7  => __( 'Commit saved.', 'revisr_commits' ),
			8  => __( 'Commit submitted.', 'revisr_commits' ),
			9  => sprintf(
				__( 'Commit scheduled for: <strong>%1$s</strong>.', 'revisr_commits' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i', 'revisr_commits' ), strtotime( $post->post_date ) )
			),
			10 => __( 'Commit draft updated.', 'revisr_commits' ),
		);

		if ( $post_type_object->publicly_queryable ) {
			$permalink = get_permalink( $post->ID );

			$view_link = sprintf( ' <a href="%s">%s</a>', esc_url( $permalink ), __( 'View Commit', 'revisr_commits' ) );
			$messages[ $post_type ][1] .= $view_link;
			$messages[ $post_type ][6] .= $view_link;
			$messages[ $post_type ][9] .= $view_link;

			$preview_permalink = add_query_arg( 'preview', 'true', $permalink );
			$preview_link = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), __( 'Preview Commit', 'revisr_commits' ) );
			$messages[ $post_type ][8]  .= $preview_link;
			$messages[ $post_type ][10] .= $preview_link;
		}

		return $messages;
	}

	public function revisr_commits_bulk_messages($bulk_messages, $bulk_counts)
	{
		$bulk_messages['revisr_commits'] = array(
			'updated' => _n( '%s commit updated.', '%s commits updated.', $bulk_counts['updated'] ),
			'locked'    => _n( '%s commit not updated, somebody is editing it.', '%s commits not updated, somebody is editing them.', $bulk_counts['locked'] ),
			'deleted'   => _n( '%s commit permanently deleted.', '%s commits permanently deleted.', $bulk_counts['deleted'] ),
			'trashed'   => _n( '%s commit moved to the Trash.', '%s commits moved to the Trash.', $bulk_counts['trashed'] ),
        	'untrashed' => _n( '%s commit restored from the Trash.', '%s commits restored from the Trash.', $bulk_counts['untrashed'] )
        	);
		return $bulk_messages;
	}


}