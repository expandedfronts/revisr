<?php
/**
 * Displays the main dashboard page.
 *
 * @package   Revisr
 * @author    Matt Shaw <matt@expandedfronts.com>
 * @license   GPL-2.0+
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 */

?>
<div class="wrap">
	<h2>Revisr - Dashboard</h2>
	<hr>
	<h3>Select Branch</h3>
	<select id="branch">
		<option>dev</option>
		<option>staging</option>
		<option>master</option>
		<option>theme</option>
	</select>
	<button class="button button-default">Select Branch</button>
	<hr>
	<h3>Commit Changes</h3>
	<form action="<?php echo get_admin_url();?>admin-post.php" method="post">
		<table class="form-table">
			<tr>
				<th scope="row"><label for="commit_message">Message</label></th>
				<td><textarea id="commit_message" name="message"></textarea></td>
			</tr>
		</table>
		<input type="hidden" name="action" value="commit">
		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Commit Changes"></p>
	</form>
	<hr>
	<h3>Pending Files</h3>
	<?php
		//TODO(?): Add WordPress-style filtering:
		//All(123) | Modified(80) | Untracked(2) | Renamed(1)
		//TODO: Evaluate for performance with extremely large arrays.
		$current_dir = getcwd();
		chdir(ABSPATH);
		exec("git status --short", $output);
		chdir($current_dir);

		echo "There are " . count($output) . " pending files (<a href='" . get_admin_url() . "admin.php?page=revisr'>view all</a>).<br><br>";

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
		if ($_GET['pagenum'] != "1"){
			echo "<a href='" . get_admin_url() . "admin.php?page=revisr&pagenum=" . ($current_page - 1) . "'><- Previous</a>";
		}
		echo " Page {$current_page} of {$last_page} "; 
		if ($current_page != $last_page){
			echo "<a href='" . get_admin_url() . "admin.php?page=revisr&pagenum=" . ($current_page + 1) . "'>Next -></a>";
		}
	?>

</div>