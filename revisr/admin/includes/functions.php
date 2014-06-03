<?php
/**
 * functions.php
 *
 * Common functions used in the plugin.
 *
 * @package   Revisr
 * @author    Matt Shaw <matt@expandedfronts.com>
 * @license   GPL-2.0+
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 */

//Returns the number of pending files.
function count_pending()
{
	$current_dir = getcwd();
	chdir(ABSPATH);
	exec("git status --short", $output);
	chdir($current_dir);
	return count($output);
}

//Returns the status of a file. 
function get_status($status)
{
	if (strpos($status, "M") !== false){
		$status = "Modified";
	}
	elseif (strpos($status, "D") !== false){
		$status = "Deleted";
	}
	elseif (strpos($status, "A") !== false){
		$status = "Added";
	}
	elseif (strpos($status, "R") !== false){
		$status = "Renamed";
	}
	elseif (strpos($status, "U") !== false){
		$status = "Updated";
	}
	elseif (strpos($status, "C") !== false){
		$status = "Copied";
	}
	elseif (strpos($status, "??") !== false){
		$status = "Untracked";
	}
	else {
		$status = $status;
	}

	return $status;
}

function current_branch()
{
	$branch = "git rev-parse --abbrev-ref HEAD";
	$dir = getcwd();
	chdir(ABSPATH);
	exec($branch, $output);
	chdir($dir);
	return $output[0];
}

?>