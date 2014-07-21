<?php
/**
 * The official Revisr WordPress plugin.
 *
 * A plugin that allows developers to manage WordPress websites with Git repositories.
 * Integrates several key git functions into the WordPress admin.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts
 *
 * Plugin Name:       Revisr
 * Plugin URI:        http://revisr.io/
 * Description:       A plugin that allows developers to manage WordPress websites with Git repositories.
 * Version:           1.5.1
 * Text Domain:		  revisr-plugin
 * Author:            Expanded Fronts
 * Author URI: http://revisr.io/
 */

include_once 'admin/includes/class.init.php';
include_once 'admin/includes/class.revisr_db.php';
include_once 'admin/includes/functions.php';


class Revisr
{
  /**
   * Stores the database connection.
   * @var string
   */
	public $wpdb;

   /**
    * The current time.
    * @var string
    */
	public $time;

   /**
    * The name of the custom table.
    * @var string
    */
	public $table_name;

   /**
    * User options & preferences.
    * @var string
    */
	private $options;

   /**
    * The current working directory.
    * @var string
    */
	private $current_dir;

   /**
    * The current upload directory.
    * @var string
    */
	private $upload_dir;	

   /**
    * The current branch in git.
    * @var string
    */	
	private $branch;

   /**
    * The name of the remote.
    * @var string
    */	
	private $remote;


	/**
	* Initializes database connection and properties.
	* @access public
	*/
	public function __construct()
	{
		//Declarations
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->table_name = $this->wpdb->prefix . "revisr";
		$this->time = current_time( 'mysql' );
		$init = new RevisrInit();
		$this->options = get_option('revisr_settings');

		if (isset($this->options['remote_name']) && $this->options['remote_name'] != '') {
			$this->remote = $this->options['remote_name'];
		}
		else {
			$this->remote = "origin";
		}

		$this->current_dir = getcwd();
		$this->upload_dir = wp_upload_dir();
		$this->branch = current_branch();
		
		$plugin = plugin_basename(__FILE__);

		//Git functions
		add_action( 'publish_revisr_commits', array($this, 'commit') );
		add_action( 'admin_post_revert', array($this, 'revert') );
		add_action( 'admin_post_checkout', array($this, 'checkout'), 10, 1 );
		add_action( 'admin_post_create_branch', array($this, 'create_branch') );
		add_action( 'admin_post_view_diff', array($this, 'view_diff') );
		
		if (isset($this->options['auto_pull'])) {
			add_action( 'admin_post_nopriv_revisr_update', array($this, 'revisr_update') );
		}
		
		//AJAX functions
		add_action( 'wp_ajax_new_commit', array($this, 'new_commit') );
		add_action( 'wp_ajax_discard', array($this, 'discard') );
		add_action( 'wp_ajax_backup_db', array($this, 'backup_db') );
		add_action( 'wp_ajax_push', array($this, 'push') );
		add_action( 'wp_ajax_pull', array($this, 'pull') );
		add_action( 'wp_ajax_view_diff', array($this, 'view_diff') );

		//Database functions
		add_action( 'admin_post_revert_db', array($this, 'revert_db') );

		//Committed / pending files
		add_action( 'wp_ajax_pending_files', array($this, 'pending_files') );
		add_action( 'wp_ajax_committed_files', array($this, 'committed_files') );

		//Recent activity
		add_action( 'wp_ajax_recent_activity', array($this, 'recent_activity') );

		//Install
		register_activation_hook( __FILE__, array($this, 'revisr_install') );
		add_filter("plugin_action_links_$plugin", array($this, 'settings_link') );
	}

	/**
	* Creates a new commit, automatically adds all files.
	* @access public
	*/
	public function commit()
	{
		if (isset($_REQUEST['_wpnonce']) && isset($_REQUEST['_wp_http_referer'])) {
			
			$id = get_the_ID();
			$title = $_REQUEST['post_title'];
			$nonce = $_REQUEST['_wpnonce'];
			$referrer = $_REQUEST['_wp_http_referer'];
			$post_new = get_admin_url() . "post-new.php?post_type=revisr_commits";

			if ($title == "Auto Draft" || $title == "") {
				$url = get_admin_url() . "post-new.php?post_type=revisr_commits&message=42";
				wp_redirect($url);
				exit();
			}

			if (isset($_POST['staged_files'])) {

				$staged_files = $_POST['staged_files'];

				foreach ($staged_files as $result) {
					$file = substr($result, 3);
					$status = get_status($result);

					if ($status == "Deleted") {
						git("rm {$file}");
					}
					else {
						git("add {$file}");
					}
				}
			}
			else {
				if (!isset($_REQUEST['backup_db'])) {
					$url = get_admin_url() . "post-new.php?post_type=revisr_commits&message=43";
					wp_redirect($url);
					exit();
				}
				$staged_files = array();
			}

			$commit_msg = escapeshellarg($title);
			git("commit -m {$commit_msg}");
			
			
			$commit_hash = git("log --pretty=format:'%h' -n 1");
			$clean_hash = trim($commit_hash[0], "'");

			$view_link = get_admin_url() . "post.php?post={$id}&action=edit";
			
			//Add post meta
			add_post_meta( get_the_ID(), 'commit_hash', $clean_hash );
			add_post_meta( get_the_ID(), 'branch', $this->branch );
			add_post_meta( get_the_ID(), 'committed_files', $staged_files );
			add_post_meta( get_the_ID(), 'files_changed', count($staged_files) );


			//Push if necessary
			if (isset($this->options['auto_push'])) {
				$errors = git_passthru("push {$this->remote} {$this->branch} --quiet");
				if ($errors == "") {
					$this->log("Committed <a href='{$view_link}'>#{$clean_hash}</a> and pushed to the remote repository.", "commit");
				}
				else {
					$this->log("Error pushing changes to the remote repository.", "error");
				}
			}
			else {
				$this->log("Committed <a href='{$view_link}'>#{$clean_hash}</a> to the local repository.", "commit");
			}

			//Backup the database if necessary
			if (isset($_REQUEST['backup_db']) && $_REQUEST['backup_db'] == "on") {
				$this->backup_db();
				$db_hash = git("log --pretty=format:'%h' -n 1");
				add_post_meta( get_the_ID(), "db_hash", $db_hash[0] );
			}

			$this->notify(get_bloginfo() . " - New Commit", "A new commit was made to the repository:<br> #{$clean_hash} - {$title}");
			return $clean_hash;		
		}

	}

	/**
	* Handles the "Add Commit" button on the dashboard.
	* @access public
	*/
	public function new_commit()
	{
		$url = get_admin_url() . "post-new.php?post_type=revisr_commits";
		wp_redirect($url);
	}

	/**
	* Pushes changes to the remote repository defined in git. The remote can be updated in the settings.
	* @access public
	*/
	public function push()
	{
		git("reset --hard HEAD");
		$num_commits = count_unpushed($this->remote);
		$errors = git_passthru("push {$this->remote} HEAD --quiet");
		
		if ($errors != "") {
			$this->log("Error pushing changes to the remote repository.", "error");
			echo "<p>There was an error while pushing to the remote repository. The remote may be ahead of this repository or you are not authenticated.</p>";
		}
		else {
			
			if ($num_commits == "1") {
				$this->log("Pushed 1 commit to {$this->remote}/{$this->branch}.", "push");
			}
			else {
				$this->log("Pushed {$num_commits} commits to {$this->remote}/{$this->branch}.", "push");
			}
			$this->notify(get_bloginfo() . " - Changes Pushed", "Changes were pushed to the remote repository for " . get_bloginfo());
			echo "<p>Successfully pushed to <strong>{$this->remote}/{$this->branch}.</p>";
		}

		exit;
	}

	/**
	* Pushes changes to the remote repository defined in git. The remote can be updated in the settings.
	* @access public
	*/
	public function pull()
	{
		check_ajax_referer("dashboard_nonce", "security");
		git("reset --hard HEAD");
		$num_commits = count_unpulled($this->remote);
		$branch = current_branch();
		$commits_since = git("log {$branch}..{$this->remote}/{$branch} --pretty=oneline");

		foreach ($commits_since as $commit) {
			$commit_hash = substr($commit, 0, 7);
			$commit_msg = substr($commit, 40);
			$show_files = git('show --pretty="format:" --name-status ' . $commit_hash);
			$files_changed = array_filter($show_files);
			
			$post = array(
				"post_title"	=> $commit_msg,
				"post_content"	=> "",
				"post_type"		=> "revisr_commits",
				"post_status"	=> "publish"
				);
			$post_id = wp_insert_post($post);

			add_post_meta( $post_id, "commit_hash", $commit_hash );
			add_post_meta( $post_id, "branch", $this->branch );
			add_post_meta( $post_id, "files_changed", count($files_changed) );
			add_post_meta( $post_id, "committed_files", $files_changed );

			$view_link = get_admin_url() . "post.php?post={$post_id}&action=edit";

			$this->log("Pulled <a href='" . $view_link . "'>#{$commit_hash}</a> from {$this->remote}/{$this->branch}.", "pull");
		}

		git("pull {$this->remote} {$this->branch}");

		$this->notify(get_bloginfo() . " - Changes Pulled", "Changes were pulled from the remote repository for " . get_bloginfo());
		echo "<p>Successfully pulled changes from <strong>{$this->remote}/{$this->branch}.</p></strong>";
		exit;
	}

	/**
	* Processes POST requests.
	* @access public
	*/
	public function revisr_update()
	{
		git("reset --hard HEAD");
		$num_commits = count_unpulled($this->remote);
		$branch = current_branch();
		$commits_since = git("log {$branch}..{$this->remote}/{$branch} --pretty=oneline");

		foreach ($commits_since as $commit) {
			$commit_hash = substr($commit, 0, 7);
			$commit_msg = substr($commit, 40);
			$show_files = git("show --pretty='format:' --name-status {$commit_hash}");
			$files_changed = array_filter($show_files);
			
			$post = array(
				"post_title"	=> $commit_msg,
				"post_content"	=> "",
				"post_type"		=> "revisr_commits",
				"post_status"	=> "publish"
				);
			$post_id = wp_insert_post($post);

			add_post_meta( $post_id, "commit_hash", $commit_hash );
			add_post_meta( $post_id, "branch", $this->branch );
			add_post_meta( $post_id, "files_changed", count($files_changed) );
			add_post_meta( $post_id, "committed_files", $files_changed );

			$view_link = get_admin_url() . "post.php?post={$post_id}&action=edit";

			$this->log("Pulled <a href='" . $view_link . "'>#{$commit_hash}</a> from {$this->remote}/{$this->branch}.", "pull");
		}

		git("pull {$this->remote} {$this->branch}");

		$this->notify(get_bloginfo() . " - Changes Pulled", "Revisr automatically pulled changes from the remote repository for " . get_bloginfo());
		exit();
	}

	/**
	* Checks out a new or existing branch.
	* @access public
	*/
	public function checkout($args)
	{
		if (isset($this->options['reset_db'])) {
			chdir($this->upload_dir['basedir']);
			$db = new RevisrDB();
			$db->backup();
			git("add revisr_db_backup.sql");
			git('commit -m "Autobackup by Revisr." ' . $this->upload_dir['basedir'] . '/revisr_db_backup.sql');

			if (isset($this->options['auto_push'])) {
				git("push {$this->remote} {$this->branch}");
			}
		}

		if ($args == "") {
			$branch = escapeshellarg($_REQUEST['branch']);
		}
		else {
			$branch = $args;
		}

		
		git("reset --hard HEAD");
		if (isset($_REQUEST['new_branch'])){
			if ($_REQUEST['new_branch'] == "true") {
				git("checkout -b {$branch}");
				$this->log("Checked out new branch: {$_REQUEST['branch']}.", "branch");
				$this->notify(get_bloginfo() . " - Branch Changed", get_bloginfo() . " was switched to the new branch {$branch}.");
				echo "<script>
						window.top.location.href = '" . get_admin_url() . "admin.php?page=revisr&checkout=success&branch={$_REQUEST['branch']}'
					</script>";
				exit;				
			}

		}
		else {
			git("checkout {$branch}");
			if (isset($this->options['reset_db'])) {
				$db->restore();
				chdir($this->current_dir);
			}
			$this->log("Checked out branch: {$_REQUEST['branch']}.", "branch");
			$this->notify(get_bloginfo() . " - Branch Changed", get_bloginfo() . " was switched to branch {$branch}.");
			$url = get_admin_url() . "admin.php?page=revisr&branch={$branch}&checkout=success";
			wp_redirect($url);
		}

	}

	/**
	* Displays the form for a new branch.
	* @access public
	*/
	public function create_branch()
	{
		$styles_url = get_admin_url() . "load-styles.php?c=0&dir=ltr&load=dashicons,admin-bar,wp-admin,buttons,wp-auth-check&ver=3.9.1";
		?>
		<link href="<?php echo $styles_url; ?>" rel="stylesheet" type="text/css">
		<div class="container" style="padding:10px">
			
			<form action="<?php echo get_admin_url(); ?>admin-post.php?action=checkout" method="post">
			<label for="branch_name"><strong>Branch Name:</strong></label>
			<input id="branch_name" type="text" name="branch" style="width:100%" autofocus />
			<input type="hidden" name="new_branch" value="true" class="regular-text"/>
			<button class="button button-primary" style="
				background-color: #5cb85c;
				height: 30px;
				width: 100%;
				margin-top:5px;
				border-radius: 4px;
				border: 1px #4cae4c solid;
				color: #fff;">Create Branch</button>
			</form>
			<p style="font-style:italic;color:#BBB;text-align:center;">New branch will be checked out.</p>
		</div>
		<?php
	}

	/**
	* Discards all changes to the working directory.
	* @access public
	*/
	public function discard()
	{
		check_ajax_referer( 'dashboard_nonce', 'security' );
		git("reset --hard HEAD");
		git("clean -f -d");
		$this->log("Discarded all changes to the working directory.", "discard");
		$this->notify(get_bloginfo() . " - Changes Discarded", "All uncommitted changes were discarded on " . get_bloginfo() . "." );
		echo "<p>Successfully discarded uncommitted changes.</p>";
		exit;
	}

	/**
	* Reverts to a specified commit.
	* @access public
	*/
	public function revert()
	{
	   if (isset($_GET['revert_nonce']) && wp_verify_nonce($_GET['revert_nonce'], 'revert')) {
			$branch = $_GET['branch'];
			if ($branch != $this->branch) {
				$this->checkout($branch);
			}
			$commit = $_GET['commit_hash'];
			$esc_commit = escapeshellarg($commit);
			$commit_msg = escapeshellarg("Reverted to commit: #{$commit}");
			git("reset --hard {$esc_commit}");
			git("reset --soft HEAD@{1}");
			git("add -A");
			git("commit -am {$commit_msg}");
			
			if (isset($this->options['auto_push'])) {
				git("push {$this->remote} {$this->branch}");
			}
			
			$post_url = get_admin_url() . "post.php?post=" . $_GET['post_id'] . "&action=edit";
			$this->log("Reverted to commit <a href='{$post_url}'>#{$commit}</a>.", "revert");
			$this->notify(get_bloginfo() . " - Commit Reverted", get_bloginfo() . " was reverted to commit #{$commit}.");
			$redirect = get_admin_url() . "admin.php?page=revisr&revert=success&commit={$commit}&id=" . $_GET['post_id'];
			wp_redirect($redirect);
		}
		else {
			wp_die("You are not authorized to access this page.");
		}
	}

	/**
	* Backs up the database, and pushes it to the remote.
	* @access public
	*/
	public function backup_db()
	{

		$db = new RevisrDB();
		chdir($this->upload_dir['basedir']);
		$backup = $db->backup();
		$file = $this->upload_dir['basedir'] . "/revisr_db_backup.sql";
		
		//Verify that the backup was successful. 
		if (file_exists($file) && filesize($file) > 1000) {
			
			git("add revisr_db_backup.sql");
			git('commit -m "Backed up the database with Revisr." ' . $file);
			
			if (isset($this->options['auto_push'])) {
				git("push {$this->remote} {$this->branch}");
			}
			
			chdir($this->current_dir);
			$this->log("Backed up the database.", "backup");
			$this->notify(get_bloginfo() . " - Database Backup", "The database for " . get_bloginfo() . " was successfully backed up.");
			
			if (isset($_REQUEST['source']) && $_REQUEST['source'] == 'ajax_button') {
				echo "<p>Successfully backed up the database.</p>";
				exit;
			}

		}
		else {
			$this->log("Error backing up the database.", "error");
			
			if (isset($_REQUEST['source']) && $_REQUEST['source'] == 'ajax_button') {
				echo "<p>There may have been an error backing up the database. Check that the permissions on your '/uploads' directory are correct and try again.</p>";
				exit;
			}
		}
		clearstatcache();
	}

	/**
	* Backs up the database, then restores it to an earlier commit.
	* @access public
	*/
	public function revert_db()
	{
		if (isset($_GET['revert_db_nonce']) && wp_verify_nonce($_GET['revert_db_nonce'], 'revert_db')) {

			//Verify we are on the correct branch, if not, checkout the correct branch.
			$branch = $_GET['branch'];
			if ($branch != $this->branch) {
				$this->checkout($branch);
			}
			
			$db = new RevisrDB();
			$file = $this->upload_dir['basedir'] . "/revisr_db_backup.sql";

			//Backup the database before restoring the older version.
			chdir($this->upload_dir['basedir']);
			$db->backup();
			git("add revisr_db_backup.sql");
			git('commit -m "Autobackup by Revisr." ' . $this->upload_dir['basedir'] . '/revisr_db_backup.sql');
			if (isset($this->options['auto_push'])) {
				git("push {$this->remote} {$this->branch}");
			}

			$commit = escapeshellarg($_GET['db_hash']);
			$current_temp = git("log --pretty=format:'%h' -n 1");

			//Checkout the older version of the database.
			git("checkout {$commit} " . $this->upload_dir['basedir'] . "/revisr_db_backup.sql");

			//Verify and restore the database.
			$db->restore();

			//Allow the user to undo the restore.
			git("checkout {$branch} " . $this->upload_dir['basedir'] . "/revisr_db_backup.sql");
			chdir($this->current_dir);
			
			if (is_array($current_temp)) {
				$current_commit = str_replace("'", "", $current_temp);
				$undo_nonce = wp_nonce_url( admin_url("admin-post.php?action=revert_db&db_hash={$current_commit[0]}&branch={$_GET['branch']}"), 'revert_db', 'revert_db_nonce' );
				$this->log("Reverted the database to a previous commit. <a href='{$undo_nonce}'>Undo</a>", "revert");
				$redirect = get_admin_url() . "admin.php?page=revisr&revert_db=success&prev_commit={$current_commit[0]}";
				wp_redirect($redirect);			
			}
			else {
				wp_die("Something went wrong. Check your settings and try again.");
			}
		}
		else {
			wp_die("You are not authorized to access this page.");
		}
	}
	
	/**
	* Shows a list of the pending files on the current branch. Clicking a modified file shows the diff.
	* @access public
	*/
	public function pending_files()
	{
		check_ajax_referer('pending_nonce', 'security');
		$output = git("status --short");
		$total_pending = count($output);
		echo "<br>There are <strong>{$total_pending}</strong> untracked files that can be added to this commit on branch <strong>" . current_branch() . "</strong>.<br>
		Use the boxes below to add/remove files. Double-click modified files to view diffs.<br><br>";
		echo "<input id='backup_db_cb' type='checkbox' name='backup_db'><label for='backup_db_cb'>Backup database?</label><br><br>";
		
		$num_files = count($output);

		if ($num_files != 0) {
				?>
				
				<!-- Staging -->
				<div class="stage-container">
					
					<p><strong>Staged Files</strong></p>
					
					<select id='staged' multiple="multiple" name="staged_files[]" size="6">
					<?php
					//Clean up output from git status and echo the results.
					foreach ($output as $result) {
						$short_status = substr($result, 0, 3);
						$file = substr($result, 3);
						$status = get_status($short_status);
						echo "<option class='pending' value='{$result}'>{$file} [{$status}]</option>";
					}
					?>
					</select>

					<div class="stage-nav">
						<input id="unstage-file" type="button" class="button button-primary stage-nav-button" value="Unstage Selected" onclick="unstage_file()" />
						<br>
						<input id="unstage-all" type="button" class="button stage-nav-button" value="Unstage All" onclick="unstage_all()" />
					</div>

				</div><!-- /Staging -->
				
				<br>

				<!-- Unstaging -->
				<div class="stage-container">
					
					<p><strong>Unstaged Files</strong></p>

					<select id="unstaged" multiple="multiple" size="6">
					</select>

					<div class="stage-nav">
						<input id="stage-file" type="button" class="button button-primary stage-nav-button" value="Stage Selected" onclick="stage_file()" />
						<br>
						<input id="stage-all" type="button" class="button stage-nav-button" value="Stage All" onclick="stage_all()" />
					</div>

				</div><!-- /Unstaging -->

			<?php	
		}
			
		exit();
	}

	/**
	* Shows the files that were added in the given commit.
	* @access public
	*/
	public function committed_files()
	{
		check_ajax_referer('committed_nonce', 'security');
		if (get_post_type($_POST['id']) != "revisr_commits") {
			exit();
		}
		$commit = get_hash($_POST['id']);
		$files = get_post_custom_values( 'committed_files', $_POST['id'] );
		foreach ( $files as $file ) {
		    $output = unserialize($file);
		}

		echo "<br><strong>" . count($output) . "</strong> files were included in this commit.<br><br>";
		

		if (isset($_POST['pagenum'])) {
			$current_page = $_POST['pagenum'];
		}
		else {
			$current_page = 1;
		}
		
		$num_rows = count($output);
		$rows_per_page = 20;
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
					$file = substr($result, 2);
					$status = get_status($short_status);
					if ($status != "Untracked" && $status != "Deleted") {
						echo "<tr><td><a href='" . get_admin_url() . "admin-post.php?action=view_diff&file={$file}&commit={$commit}&TB_iframe=true&width=600&height=550' title='View Diff' class='thickbox'>{$file}</a></td><td>{$status}</td></td>";
					}
					else {
						echo "<tr><td>{$file}</td><td>{$status}</td></td>";
					}					
				}
			?>
			</tbody>
		</table>
		<?php
			if ($current_page != "1"){
				echo "<a href='#' onclick='prev();return false;'><- Previous</a>";
			}
			echo " Page {$current_page} of {$last_page} "; 
			if ($current_page != $last_page){
				echo "<a href='#' onclick='next();return false;'>Next -></a>";
			}
			exit();
	}
	
	/**
	* Displays the differences between a pending and current file.
	* @access public
	*/
	public function view_diff()
	{
		?>
		<html>
		<head><title>View Diff</title>
		</head>
		<body>
		<?php
		$file = $_REQUEST['file'];

		if (isset($_REQUEST['commit']) && $_REQUEST['commit'] != "") {
			$commit = $_REQUEST['commit'];
			$diff = git("show {$commit} {$file}");
		}
		else {
			$diff = git("diff {$file}");
		}

		foreach ($diff as $line) {
			if (substr( $line, 0, 1 ) === "+") {
				echo "<span class='diff_added' style='background-color:#cfc;'>" . htmlspecialchars($line) . "</span><br>";
			}
			else if (substr( $line, 0, 1 ) === "-") {
				echo "<span class='diff_removed' style='background-color:#fdd;'>" . htmlspecialchars($line) . "</span><br>";
			}
			else {
				echo htmlspecialchars($line) . "<br>";
			}	
		}
		?>
		</body>
		</html>
		<?php
		exit();
	}

	/**
	* Displays on plugin dashboard.
	* @access public
	*/
	public function recent_activity()
	{
		global $wpdb;
		$revisr_events = $wpdb->get_results("SELECT id, time, message FROM $this->table_name ORDER BY id DESC LIMIT 10", ARRAY_A);
		if ($revisr_events) {
			echo '<table class="widefat">
					<thead>
					    <tr>
					        <th>Event</th>
					        <th>Time</th>
					    </tr>
					</thead>
					<tbody id="activity_content">';
					foreach ($revisr_events as $revisr_event) {
						echo "<tr><td>{$revisr_event['message']}</td><td>{$revisr_event['time']}</td></tr>";
					}
			echo '</tbody>
				</table>';
						
		}
		else {
			echo "<p>Your recent activity will show up here.</p>";
		}
		exit;

	}

	/**
	* Logs an action to the database.
	* @access private
	*/
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

	/**
	* Notifies the user of an action only if notifications are enabled.
	* @access private
	*/
	private function notify($subject, $message)
	{
		$options = get_option('revisr_settings');
		$url = get_admin_url() . "admin.php?page=revisr";
		

		if (isset($options['notifications'])) {
			$email = $options['email'];
			$message .= "<br><br><a href='{$url}'>Click here</a> for more details.";
			$headers = "Content-Type: text/html; charset=ISO-8859-1\r\n";
			wp_mail($email, $subject, $message, $headers);
		}
	}

	/**
	* Creates the table necessary for logging.
	* @access public
	*/
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
	   	add_option( "revisr_db_version", "1.0" );
	}	

	public function settings_link($links)
	{
		$settings_link = '<a href="admin.php?page=revisr_settings">Settings</a>'; 
  		array_unshift($links, $settings_link); 
  		return $links; 
	}	
}

$revisr = new Revisr;