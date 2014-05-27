<?php
/**
 * Displays the settings page.
 *
 * @package   Revisr
 * @author    Matt Shaw <matt@expandedfronts.com>
 * @license   GPL-2.0+
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 */

$file = $_GET['file'];

?>

<div class="wrap">
	<h2>Diff: <?php echo $file; ?></h2>
	<hr>
	<div id="diff_container">
		<pre>
			<?php
				echo "<br>";
				$file = $_GET['file'];
				$dir = getcwd();
				chdir(ABSPATH);
				exec("git diff {$file}", $diff);
				chdir($dir);
				foreach ($diff as $line) {

					if (substr( $line, 0, 1 ) === "+") {
						echo "<span class='diff_added'>" . htmlspecialchars($line) . "</span><br>";
					}
					else if (substr( $line, 0, 1 ) === "-") {
						echo "<span class='diff_removed'>" . htmlspecialchars($line) . "</span><br>";
					}
					else {
						echo htmlspecialchars($line) . "<br>";
					}
					
				}
			?>
		</pre>
	</div>
</div>