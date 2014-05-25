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

?>