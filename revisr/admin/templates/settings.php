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

?>

<div class="wrap">
	<h2>Revisr Settings</h2>
	<hr>
	<form method="post" action="options.php">
	<?php
                // This prints out all hidden setting fields
                settings_fields( 'revisr_option_group' );   
                do_settings_sections( 'revisr_settings' );
                submit_button(); 
    ?>
	</form>
</div>