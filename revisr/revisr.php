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
 * Plugin URI:        https://revisr.io/
 * Description:       A plugin that allows developers to manage WordPress websites with Git repositories.
 * Version:           1.0.2
 * Author:            Expanded Fronts
 */

include_once 'admin/includes/init.php';
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
    * The current working directory.
    * @var string
    */
	private $current_dir;

   /**
    * The current branch in git.
    * @var string
    */	
	private $current_branch;


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
		$init = new revisr_init;
		$this->current_dir = getcwd();
		$this->current_branch = exec("git rev-parse --abbrev-ref HEAD");

		//Git functions
		add_action( 'publish_revisr_commits', array($this, 'commit') );
		add_action( 'admin_post_revert', array($this, 'revert') );
		add_action( 'admin_post_checkout', array($this, 'checkout') );
		add_action( 'admin_post_create_branch', array($this, 'create_branch') );
		add_action( 'admin_post_view_diff', array($this, 'view_diff') );
		add_action( 'wp_ajax_new_commit', array($this, 'new_commit') );
		add_action( 'wp_ajax_discard', array($this, 'discard') );
		add_action( 'wp_ajax_push', array($this, 'push') );
		add_action( 'wp_ajax_pull', array($this, 'pull') );

		//Committed / pending files
		add_action( 'wp_ajax_pending_files', array($this, 'pending_files') );
		add_action( 'wp_ajax_committed_files', array($this, 'committed_files') );

		//Recent activity
		add_action( 'wp_ajax_recent_activity', array($this, 'recent_activity') );

		//Install
		register_activation_hook( __FILE__, array($this, 'revisr_install'));
	}

	/**
	* Creates a new commit, automatically adds all files.
	* @access public
	*/
	public function commit()
	{
		$title = $_REQUEST['post_title'];
		git("add -A");
		git("commit -am '" . $title . "'");
		$commit_hash = git("log --pretty=format:'%h' -n 1");
		git("push origin {$this->current_branch}");
		add_post_meta( get_the_ID(), 'commit_hash', $commit_hash );
		$branch = git("rev-parse --abbrev-ref HEAD");
		add_post_meta( get_the_ID(), 'branch', $branch[0] );
		$author = the_author();
		$view_link = get_admin_url() . "post.php?post=" . get_the_ID() . "&action=edit";
		$this->log("Committed <a href='{$view_link}'>#{$commit_hash[0]}</a> to the repository.", "commit");
		$this->notify(get_bloginfo() . " - New Commit", "A new commit was made to the repository:<br> #{$commit_hash[0]} - {$title}");
		return $commit_hash;
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
	* Reverts to a specified commit.
	* @access public
	*/
	public function revert()
	{
		$commit = $_GET['commit_hash'];
		git("reset --hard {$commit}");
		git("reset --soft HEAD@{1}");
		git("add -A");
		$commit_hash = git("push origin {$this->current_branch}");
		git("commit -am 'Reverted to commit: #" . $commit . "'");
		$post_url = get_admin_url() . "post.php?post=" . $_GET['post_id'] . "&action=edit";
		$this->log("Reverted to commit <a href='{$post_url}'>#{$commit}</a>.", "revert");
		$this->notify(get_bloginfo() . " - Commit Reverted", get_bloginfo() . " was reverted to commit #{$commit}.");
		$redirect = get_admin_url() . "admin.php?page=revisr&revert=success&commit={$commit}&id=" . $_GET['post_id'];
		wp_redirect($redirect);
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
		$file = $_GET['file'];
		$diff = git("diff {$file}");

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
		<?
	}

	/**
	* Discards all changes to the working directory.
	* @access public
	*/
	public function discard()
	{
		git("reset --hard HEAD");
		$this->log("Discarded all changes to the working directory.", "discard");
		$this->notify(get_bloginfo() . " - Changes Discarded", "All changes were discarded on " . get_bloginfo() . "." );
		echo "<p>Successfully discarded uncommitted changes.</p>";
		exit;
	}

	/**
	* Checks out a new or existing branch.
	* @access public
	*/
	public function checkout()
	{
		$branch = $_REQUEST['branch'];
		git("reset --hard HEAD");
		if (isset($_REQUEST['new_branch'])){
			if ($_REQUEST['new_branch'] == "true") {
				git("checkout -b {$branch}");
				$this->log("Checked out new branch: {$branch}.", "branch");
				$this->notify(get_bloginfo() . " - Branch Changed", get_bloginfo() . " was switched to the new branch {$branch}.");
				echo "<script>
						window.top.location.href = '" . get_admin_url() . "admin.php?page=revisr&checkout=success&branch={$branch}'
					</script>";
				exit;				
			}

		}
		else {
			git("checkout {$branch}");
			$this->log("Checked out branch: {$branch}.", "branch");
			$this->notify(get_bloginfo() . " - Branch Changed", get_bloginfo() . " was switched to the branch {$branch}.");
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
			<input id="branch_name" type="text" name="branch" style="width:100%" />
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
		<?
	}

	/**
	* Pushes changes to the remote repository defined in git. The remote can be updated in the settings.
	* @access public
	*/
	public function push()
	{
		git("reset --hard HEAD");
		git("push origin HEAD");
		$this->log("Pushed changes to the remote repository.", "push");
		$this->notify(get_bloginfo() . " - Changes Pushed", "Changes were pushed to the remote repository for " . get_bloginfo());
		echo "<p>Successfully pushed to the remote.</p>";
		exit;
	}

	/**
	* Pushes changes to the remote repository defined in git. The remote can be updated in the settings.
	* @access public
	*/
	public function pull()
	{
		git("reset --hard HEAD");
		git("pull origin");
		$this->log("Pulled changes from the remote repository", "pull");
		$this->notify(get_bloginfo() . " - Changes Pulled", "Changes were pulled from the remote repository for " . get_bloginfo());
		echo "<p>Successfully pulled from the remote.</p>";
		exit;
	}

	/**
	* Shows the files that were added in the given commit.
	* @access public
	*/
	public static function committed_files()
	{
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
					$file = substr($result, 3);
					$status = get_status($short_status);
					echo "<tr><td>{$file}</td><td>{$status}</td></td>";
				}
			?>
			</tbody>
		</table>
		<?php
			if ($current_page != "1"){
				echo "<a href='#' onclick='prev1();return false;'><- Previous</a>";
			}
			echo " Page {$current_page} of {$last_page} "; 
			if ($current_page != $last_page){
				echo "<a href='#' onclick='next1();return false;'>Next -></a>";
			}
			exit();
	}
	
	/**
	* Shows a list of the pending files on the current branch. Clicking a modified file shows the diff.
	* @access public
	*/
	public function pending_files()
	{
		$output = git("status --short");

		echo "<br>There are <strong>" . count($output) . "</strong> pending files that will be added to this commit on branch <strong>" . current_branch() . "</strong>.<br><br>";

		$current_page = $_POST['pagenum'];
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


		if ($num_rows != 0) {
			echo '		
			<table class="widefat">
				<thead>
				    <tr>
				        <th>File</th>
				        <th>Status</th>
				    </tr>
				</thead>
				<tbody>';
				//Clean up output from git status and echo the results.
				foreach ($results as $result) {
					$short_status = substr($result, 0, 3);
					$file = substr($result, 3);
					$status = get_status($short_status);
					if ($status != "Untracked" && $status != "Deleted") {
						echo "<tr><td><a href='" . get_admin_url() . "admin-post.php?action=view_diff&file={$file}&TB_iframe=true&width=600&height=550' title='View Diff' class='thickbox'>{$file}</a></td><td>{$status}</td></td>";
					}
					else {
						echo "<tr><td>{$file}</td><td>{$status}</td></td>";
					}
				}

			echo '</tbody>
			</table>
			<div id="revisr-pagination">';

			if ($current_page != "1"){
				echo "<a href='#' onclick='prev();return false;'><- Previous</a>";
			}
			echo " Page {$current_page} of {$last_page} "; 
			if ($current_page != $last_page){
				echo "<a href='#' onclick='next();return false;'>Next -></a>";
			}
			echo "</div>";	
		}

		else {
			echo "<p>There are no files to add to this commit.</p>";
		}			
		exit();
	}

	/**
	* Displays on plugin dashboard.
	* @access public
	*/
	public function recent_activity()
	{
		global $wpdb;
		$revisr_events = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY id DESC LIMIT 10", ARRAY_A);
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
}

$revisr = new Revisr;