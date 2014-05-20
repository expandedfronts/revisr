<?php
/**
 * The official Revisr WordPress plugin.
 *
 * A plugin that allows developers to manage WordPress websites with Git repositories.
 * Integrates several key git functions into the WordPress admin.
 *
 * @package   Revisr
 * @author    Matt Shaw <matt@expandedfronts.com>
 * @license   GPL-2.0+
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 *
 * Plugin Name:       Revisr
 * Plugin URI:        https://revisr.io/
 * Description:       A plugin that allows developers to manage WordPress websites with Git repositories.
 * Version:           1.0.0
 * Author:            Expanded Fronts, LLC
 * Author URI:        http://expandedfronts.com/
 */

class Revisr
{
	private $current_dir;
	private $current_branch;
	private $table_name;
	public $wpdb;
	public $time;


	/////////////////////////////////////////////////
	// PLUGIN INIT
	/////////////////////////////////////////////////

	public function __construct()
	{
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->time = current_time( 'mysql' );
		$this->current_dir = getcwd();
		$this->current_branch = exec("git rev-parse --abbrev-ref HEAD");
		$this->table_name = $wpdb->prefix . "revisr";
		register_activation_hook( __FILE__, 'revisr_install' );
		add_action( 'init', array($this, 'revisr_commits') );

		if ( is_admin() ) {
			add_action( 'load-post.php', array($this, 'revisr_meta') );
			add_action( 'load-post-new.php', array($this, 'revisr_meta') );
			add_action( 'admin_menu', array($this, 'revisr_menus') );
			add_action( 'views_edit-revisr_commits', array($this, 'revisr_remove_views') );
			add_action( 'post_row_actions', array($this, 'revisr_remove_actions') );
			add_action( 'admin_post_commit', array($this, 'process_commit') );
			add_action( 'admin_post_pull', array($this, 'process_pull') );
			add_action( 'admin_post_pull', array($this, 'process_create_branch') );
			add_action( 'publish_revisr_commits', array($this, 'revisr_process_commit') );

		}

	}

	public function revisr_install()
	{
		
		$sql = "CREATE TABLE IF NOT EXISTS $this->table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			message TEXT,
			event VARCHAR(42) NOT NULL,
			UNIQUE KEY id (id)
			);";
		
	  	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	   	dbDelta( $sql );
	   	add_option( "revisr_db_version", $revisr_db_version );
	}
	
	public function revisr_menus()
	{
		$menu = add_menu_page( 'Revisr', 'Revisr', 'manage_options', 'revisr', array($this, 'revisr_dash'), plugins_url( 'revisr/img/revisrlogo_small-white.png' ) );
		$settings_menu = add_submenu_page( 'revisr', 'Revisr - Settings', 'Settings', 'manage_options', 'revisr_settings', array($this, 'revisr_settings') );
		add_action( 'admin_print_styles-' . $menu, array($this, 'revisr_styles') );
		add_action( 'admin_print_scripts-' . $menu, array($this, 'revisr_scripts') );
	}

	public function revisr_commits() {

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

	public function revisr_process_commit()
	{
		$commit_hash = $this->commit(get_the_title());
		$this->push($this->current_branch);
		$this->log("Committed {$commit_hash} to the repository.", "commit");
	}

	public function revisr_meta()
	{
		add_meta_box( 'revisr_options',	'Options', array($this, 'revisr_options'), 'revisr_commits' );

		if ($_GET['action'] == 'edit') {
			add_meta_box( 'revisr_committed_files', 'Committed Files', array($this, 'revisr_committed_files'), 'revisr_commits' );
		}
		else {
			add_meta_box( 'revisr_pending_files', 'Pending Files', array($this, 'revisr_pending_files'), 'revisr_commits' );
		}
	}

	public function revisr_options()
	{
		?>
		<p>Add Tag?</p>
		<?php
	}

	public function revisr_pending_files()
	{
		//TODO(?): Add WordPress-style filtering:
		//All(123) | Modified(80) | Untracked(2) | Renamed(1)
		//TODO: Evaluate for performance with extremely large arrays.
		$current_dir = getcwd();
		chdir(ABSPATH);
		exec("git status --short", $output);
		chdir($current_dir);

		echo "<br>There are <strong>" . count($output) . "</strong> pending files that will be added to this commit. (<a href='" . get_admin_url() . "admin.php?page=revisr'>view all</a>).<br><br>";

		$current_page = $_GET['pagenum'];
		$num_rows = count($output);
		$rows_per_page = 10;
		$last_page = ceil($num_rows/$rows_per_page);

		if ($current_page < 1){
		    $current_page = 1;
		}
		if ($current_page > $last_page){
		    $current_page = $last_page;
		}
		
		$offset = $rows_per_page * ($current_page - 1);

		$results = array_slice($output, $offset, $rows_per_page);
		add_post_meta( get_the_ID(), 'committed_files', $output );
	?>
	<table class="widefat">
		<thead>
		    <tr>
		        <th>File</th>
		        <th>Status</th>
		    </tr>
		</thead>
		<tbody>
		<?php
			//Clean up output from git status and echo the results.
			foreach ($results as $result) {

				$short_status = substr($result, 0, 3);
				$file = substr($result, 3);

				if (strpos($short_status, "M")){
					$status = "Modified";
				}
				elseif (strpos($short_status, "D")){
					$status = "Deleted";
				}
				elseif (strpos($short_status, "??") !== false){
					$status = "Untracked";
				}
				else {
					$status = "Unknown";
				}

				echo "<tr><td>{$file}</td><td>{$status}</td></td>";
			}
		?>
		</tbody>
	</table>
	<?php
		if ($current_page != "1"){
			echo "<a href='" . get_admin_url() . "post-new.php?post_type=revisr_commits&pagenum=" . ($current_page - 1) . "'><- Previous</a>";
		}
		echo " Page {$current_page} of {$last_page} "; 
		if ($current_page != $last_page){
			echo "<a href='" . get_admin_url() . "post-new.php?post_type=revisr_commits&pagenum=" . ($current_page + 1) . "'>Next -></a>";
		}
	}

	public function revisr_committed_files()
	{
		$files = get_post_custom_values( 'committed_files', get_the_ID() );
		foreach ( $files as $file ) {
		    $output = unserialize($file);
		}

		echo "<br><strong>" . count($output) . "</strong> files were included in this commit. (<a href='" . get_admin_url() . "admin.php?page=revisr'>view all</a>).<br><br>";

		$current_page = $_GET['pagenum'];
		$num_rows = count($output);
		$rows_per_page = 10;
		$last_page = ceil($num_rows/$rows_per_page);

		if ($current_page < 1){
		    $current_page = 1;
		}
		if ($current_page > $last_page){
		    $current_page = $last_page;
		}
		
		$offset = $rows_per_page * ($current_page - 1);

		$results = array_slice($output, $offset, $rows_per_page);
	?>
	<table class="widefat">
		<thead>
		    <tr>
		        <th>File</th>
		        <th>Status</th>
		    </tr>
		</thead>
		<tbody>
		<?php
			//Clean up output from git status and echo the results.
			foreach ($results as $result) {

				$short_status = substr($result, 0, 3);
				$file = substr($result, 3);

				if (strpos($short_status, "M")){
					$status = "Modified";
				}
				elseif (strpos($short_status, "D")){
					$status = "Deleted";
				}
				elseif (strpos($short_status, "??") !== false){
					$status = "Untracked";
				}
				else {
					$status = "Unknown";
				}

				echo "<tr><td>{$file}</td><td>{$status}</td></td>";
			}
		?>
		</tbody>
	</table>
	<?php
		if ($current_page != "1"){
			echo "<a href='" . get_admin_url() . "admin.php?page=revisr&pagenum=" . ($current_page - 1) . "'><- Previous</a>";
		}
		echo " Page {$current_page} of {$last_page} "; 
		if ($current_page != $last_page){
			echo "<a href='" . get_admin_url() . "admin.php?page=revisr&pagenum=" . ($current_page + 1) . "'>Next -></a>";
		}

	}

	public function revisr_remove_views($views)
	{
		unset($views);
		return $views;
	}

	public function revisr_remove_actions($actions)
	{
		if (get_post_type() == 'revisr_commits')
		{
			unset( $actions['edit'] );
	        unset( $actions['view'] );
	        unset( $actions['trash'] );
	        unset( $actions['inline hide-if-no-js'] );
	        $actions['view'] = "<a href='#'>View</a>";
	        $actions['revert'] = "<a href='#'>Revert</a>";
	    	return $actions;
		}

	}

	public function revisr_styles()
	{
		wp_enqueue_style( 'revisr_css', plugin_dir_url( __FILE__ ) . 'css/revisr.css' );
	}

	public function revisr_scripts()
	{
		
	}

	public function revisr_dash()
	{
		include "inc/dashboard.php";
	}

	public function revisr_settings()
	{
		include "inc/settings.php";
	}

	/////////////////////////////////////////////////
	// PROCESS ACTIONS
	/////////////////////////////////////////////////

	//Commits all modified/untracked files.
	public function process_commit()
	{
		$commit_hash = $this->commit($_POST['message']);
		$this->push($this->current_branch);
		$this->log("Committed {$commit_hash} to the repository.", "commit");
		$this->redirect("revisr", $this->current_branch);
	}

	//Pulls changes from a remote repository.
	public function process_pull()
	{
		$this->reset("HEAD");
		$this->pull($_POST['branch']);
		$this->log("Pulled {$_POST['branch']} from the remote repository.", "pull");
		$this->redirect("revisr", "success");
	}

	//Creates a new branch.
	public function process_create_branch()
	{
		$this->checkout("-b", $_POST['branch']);
		$this->log("Created new branch '{$_POST['branch']}'.", "branch");
		$this->redirect("revisr", "success");
	}

	//Checks out existing branch.
	public function process_checkout()
	{
		$this->checkout("", $_POST['branch']);
		$this->log("Checked out branch '{$_POST['branch']}'.", "branch");
		$this->redirect("revisr", "success");
	}

	/////////////////////////////////////////////////
	// GIT METHODS
	/////////////////////////////////////////////////

	public function push($branch)
	{
		$push = "git push origin $branch";
		exec($push);
	}

	public function pull($branch)
	{
		$pull = "git pull origin $branch";
		exec($pull);
	}

	public function commit($message)
	{
		$add_files = "git add -A";
		$commit = 'git commit -am "' . $message . '"';
		
		chdir(ABSPATH);
		exec($add_files);
		exec($commit);
		chdir($this->current_dir);

		$commit_hash = exec("git log --pretty=format:'%h' -n 1");
		return $commit_hash;
	}

	public function branch()
	{

	}

	public function checkout($args, $branch)
	{
		$checkout = "git checkout {$args} {$branch}";
		exec($checkout);
	}

	public function tag($tag)
	{
		$tag = "git tag $tag";
		exec($tag);
		exec("git push --tags");
	}

	public function reset($args)
	{
		$reset = "git reset --hard $args";
		exec($reset);
	}

	public function revert()
	{

	}

	/////////////////////////////////////////////////
	// MISC
	/////////////////////////////////////////////////

	//Logs actions taken.
	private function log($message, $event)
	{
		$this->wpdb->insert(
			"$this->table_name",
			array(
				"time" => $this->time,
				"message" => $message,
				"event" => $event
			),
			array(
				'%s',
				'%s',
				'%s'
			)
		);
	}

	//Cleanly exits the script when a redirect is needed.
	private function redirect($location, $result)
	{
		$url = get_admin_url() . "admin.php?page={$location}&result={$result}";
		chdir($this->current_dir);
		header("Location: {$url}");
		exit;
	}

	//Makes sure we're in the right directory.
	public function __destruct()
	{
		chdir($this->current_dir);
	}
}

$revisr = new Revisr;