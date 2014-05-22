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
	private $current_dir;
	private $current_branch;

	public function __construct()
	{
		$init = new revisr_init;
		$this->current_dir = getcwd();
		$this->current_branch = exec("git rev-parse --abbrev-ref HEAD");
		add_action( 'publish_revisr_commits', array($this, 'commit') );
	}

	public function commit()
	{
		$this->git("add -A");
		$commit_hash = $this->git("commit -am" . get_the_title());
		$this->git("push origin {$this->current_branch}");
		return $commit_hash;
	}

	public function git($args)
	{
		$cmd = "git $args";
		chdir(ABSPATH);
		exec($cmd, $output);
		chdir($this->current_dir);
		return $output;
	}

	public function committed_files()
	{
		echo "Just for tests.";
	}

	public function pending_files()
	{
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
}

$revisr = new Revisr;