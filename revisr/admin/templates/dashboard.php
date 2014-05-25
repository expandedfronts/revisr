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

$dir = plugin_dir_path( __FILE__ );
include_once $dir . '../includes/functions.php';

?>

<div class="wrap">
	<h2>Revisr Dashboard</h2>
	<hr>
	<?php 
		$pending = count_pending();
		if ( $pending != 0 ){
			if ( $pending == 1 ){
				$text = "There is currently 1 file pending.";
			}
			else {
				$text = "There are currently {$pending} files pending.";
			}
			echo "<div class='revisr-alert'>{$text}</div>";
		}
	?>
	<h3>Quick Options</h3>
	<button class="button button-primary">Pull Changes</button>
	<button class="button button-primary">Push Changes</button>

</div>