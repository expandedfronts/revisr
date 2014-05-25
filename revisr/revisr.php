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

include_once 'admin/includes/init.php';
include_once 'admin/includes/functions.php';

class Revisr
{

	public $wpdb;
	public $time;
	public $table_name;
	private $current_dir;
	private $current_branch;

	public function __construct()
	{
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->table_name = $wpdb->prefix . "revisr";
		$this->time = current_time( 'mysql' );
		$init = new revisr_init;
		$this->current_dir = getcwd();
		$this->current_branch = exec("git rev-parse --abbrev-ref HEAD");
		add_action( 'publish_revisr_commits', array($this, 'commit') );
		add_action( 'wp_ajax_committed_files', array($this, 'committed_files') );
		add_action( 'admin_footer', array($this, 'committed_files_js') );
		add_action( 'wp_ajax_pending_files', array($this, 'pending_files') );
		add_action( 'admin_footer', array($this, 'pending_files_js') );
		add_action( 'admin_post_revert', array($this, 'revert') );
		add_action( 'admin_post_branch', array($this, 'branch') );
		add_action( 'admin_post_push', array($this, 'push') );
		add_action( 'admin_post_pull', array($this, 'pull') );

	}

	public function commit()
	{
		$this->git("add -A");
		$this->git("commit -am '" . get_the_title() . "'");
		$commit_hash = $this->git("log --pretty=format:'%h' -n 1");
		$this->git("push origin {$this->current_branch}");
		add_post_meta( get_the_ID(), 'commit_hash', $commit_hash );
		$author = the_author();
		$view_link = get_admin_url() . "post.php?post=" . get_the_ID() . "&action=edit";
		$this->log("Committed <a href='{$view_link}'>#{$commit_hash[0]}</a> to the repository.", "commit");
		return $commit_hash;
	}

	//Reverts to a specified commit.
	public function revert()
	{
		$commit = $_GET['commit_hash'];
		$this->git("reset --hard {$commit}");
		$this->git("reset --soft HEAD@{1}");
		$this->git("add -A");
		$commit_hash = $this->git("push origin {$this->current_branch}");
		$this->git("commit -am 'Reverted to commit: #" . $commit_hash . "'");
		$this->log("Reverted to commit #{$commit_hash}.", "revert");
		wp_redirect(get_admin_url() . "edit.php?post_type=revisr_commits");
		exit;
	}

	public function branch()
	{
		$branch = $_REQUEST['branch'];
		$this->git("reset --hard HEAD");
		$this->git("checkout {$branch}");
		wp_redirect(get_admin_url() . "admin.php?page=revisr&branch=success");

	}

	public function push()
	{
		$this->git("reset --hard HEAD");
		$this->git("push origin HEAD");
		$this->log("Pushed changes to the remote repository.", "push");
		wp_redirect(get_admin_url() . "admin.php?page=revisr&push=success");
	}

	public function pull()
	{
		$this->git("reset --hard HEAD");
		$this->git("pull origin");
		$this->log("Pulled changes from the remote repository", "pull");
		wp_redirect(get_admin_url() . "admin.php?page=revisr&pull=success");
	}

	public function git($args)
	{
		$cmd = "git $args";
		chdir(ABSPATH);
		exec($cmd, $output);
		chdir($this->current_dir);
		return $output;
	}

	public function committed_files_js()
	{
		?>
			<script type="text/javascript" >
			var page = 1;

			function next1()
			{
				var next_page = ++page;

				var data = {
					action: 'committed_files',
					pagenum: next_page,
					id: <?php echo $_GET['post']; ?>
				};
				jQuery.post(ajaxurl, data, function(response) {
					document.getElementById('committed_files_result').innerHTML = response;
				});		
			}

			function prev1()
			{
				var prev_page = --page;
				var data = {
					action: 'committed_files',
					pagenum: prev_page,
					id: <?php echo $_GET['post']; ?>
				};
				jQuery.post(ajaxurl, data, function(response) {
					document.getElementById('committed_files_result').innerHTML = response;
				});				
			}

			jQuery(document).ready(function($) {

				var data = {
					action: 'committed_files',
					page: 1,
					id: <?php echo $_GET['post']; ?>
				};

				// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
				$.post(ajaxurl, data, function(response) {
					document.getElementById('committed_files_result').innerHTML = response;
				});
			});
			</script>
		<?php
	}

	public static function committed_files()
	{
		$files = get_post_custom_values( 'committed_files', $_POST['id'] );
		foreach ( $files as $file ) {
		    $output = unserialize($file);
		}

		echo "<br><strong>" . count($output) . "</strong> files were included in this commit. (<a href='" . get_admin_url() . "admin.php?page=revisr'>view all</a>).<br><br>";

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

				if (strpos($short_status, "M") !== false){
						$status = "Modified";
				}
				elseif (strpos($short_status, "D") !== false){
					$status = "Deleted";
				}
				elseif (strpos($short_status, "A") !== false){
					$status = "Added";
				}
				elseif (strpos($short_status, "R") !== false){
					$status = "Renamed";
				}
				elseif (strpos($short_status, "U") !== false){
					$status = "Updated";
				}
				elseif (strpos($short_status, "C") !== false){
					$status = "Copied";
				}
				elseif (strpos($short_status, "??") !== false){
					$status = "Untracked";
				}
				else {
					$status = $short_status;
				}

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

	public function pending_files_js()
	{
		?>
			<script type="text/javascript" >

			var page = 1;

			function next()
			{
				var next_page = ++page;

				var data = {
					action: 'pending_files',
					pagenum: next_page
				};
				jQuery.post(ajaxurl, data, function(response) {
					document.getElementById('pending_files_result').innerHTML = response;
				});		
			}

			function prev()
			{
				var prev_page = --page;
				var data = {
					action: 'pending_files',
					pagenum: prev_page
				};
				jQuery.post(ajaxurl, data, function(response) {
					document.getElementById('pending_files_result').innerHTML = response;
				});				
			}

			jQuery(document).ready(function($) {

				var data = {
					action: 'pending_files',
					pagenum: page
				};

				// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
				$.post(ajaxurl, data, function(response) {
					document.getElementById('pending_files_result').innerHTML = response;
				});
			});



			</script>
		<?php
	}	

	public function pending_files()
	{
		$current_dir = getcwd();
		chdir(ABSPATH);
		exec("git status --short", $output);
		chdir($current_dir);

		echo "<br>There are <strong>" . count($output) . "</strong> pending files that will be added to this commit. (<a href='" . get_admin_url() . "admin.php?page=revisr'>view all</a>).<br><br>";

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

					if (strpos($short_status, "M") !== false){
						$status = "Modified";
					}
					elseif (strpos($short_status, "D") !== false){
						$status = "Deleted";
					}
					elseif (strpos($short_status, "A") !== false){
						$status = "Added";
					}
					elseif (strpos($short_status, "R") !== false){
						$status = "Renamed";
					}
					elseif (strpos($short_status, "U") !== false){
						$status = "Updated";
					}
					elseif (strpos($short_status, "C") !== false){
						$status = "Copied";
					}
					elseif (strpos($short_status, "??") !== false){
						$status = "Untracked";
					}
					else {
						$status = $short_status;
					}

					echo "<tr><td>{$file}</td><td>{$status}</td></td>";
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
}

$revisr = new Revisr;