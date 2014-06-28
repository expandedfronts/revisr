<?php
/**
 * Displays the settings page.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts
 */

if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == "true") {
	
	$options = get_option('revisr_settings');
	$dir = getcwd();
	
	chdir(ABSPATH);
	file_put_contents(".gitignore", $options['gitignore']);
	
	if ($options['username'] != "") {
		git('config user.name "' . $options['username'] . '"');
	}
	if ($options['email'] != "") {
		git('config user.email "' . $options['email'] . '"');
	}
	if ($options['remote_url'] != "") {
		git('config remote.origin.url ' . $options['remote_url']);
	}
	if (isset($options['auto_push'])) {
		$errors = git_passthru("push origin {$this->branch} --quiet");
		if ($errors != "") {
			wp_redirect(get_admin_url() . "admin.php?page=revisr_settings&error=push");
		}
	}

	git("add .gitignore");
	git("commit -m 'Updated .gitignore'");

	chdir($dir);
}

?>

<div class="wrap">
	<div id="revisr_settings">
		<h2>Revisr Settings</h2>
		<?php
			if (isset($_GET['error']) && $_GET['error'] == "push" && $_GET['settings-updated'] != "true")
			{
				echo "<div id='revisr_alert' class='error'><p>There was an error updating the .gitignore on the remote repository.<br>
				The remote may be ahead, or the connection settings below may be incorrect.</p></div>";
			}
			if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == "true") {
				echo "<div id='revisr_alert' class='updated'><p>Settings updated successfully.</p></div>";
			}
		?>
		<form method="post" action="options.php">
		<?php
	                //Print the settings fields.
	                settings_fields( 'revisr_option_group' );   
	                do_settings_sections( 'revisr_settings' );
	                submit_button(); 
	    ?>
		</form>
	</div>
</div>