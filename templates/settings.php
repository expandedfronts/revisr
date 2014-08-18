<?php
/**
 * Displays the settings page.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 */

if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] == "true" ) {
	
	$options = get_option('revisr_settings');
	$dir = getcwd();
	
	chdir(ABSPATH);
	file_put_contents(".gitignore", $options['gitignore']);
	
	if ( $options['username'] != "" ) {
		Revisr_Git::run('config user.name "' . $options['username'] . '"');
	}
	if ( $options['email'] != "" ) {
		Revisr_Git::run('config user.email "' . $options['email'] . '"');
	}
	if ( $options['remote_url'] != "" ) {
		Revisr_Git::run('config remote.origin.url ' . $options['remote_url']);
	}
	if ( isset( $options['auto_push'] ) ) {
		$auto_push = Revisr_Git::run("push origin {$this->branch} --quiet");
		if ( $auto_push === false ) {
			wp_redirect( get_admin_url() . "admin.php?page=revisr_settings&error=push" );
		}
	}

	Revisr_Git::run("add .gitignore");
	Revisr_Git::run("commit -m 'Updated .gitignore'");

	chdir( $dir );
}

?>

<div class="wrap">
	<div id="revisr_settings">
		<h2><?php _e( 'Revisr Settings', 'revisr' ); ?></h2>
		<?php
			if ( isset( $_GET['error'] ) && $_GET['error'] == "push" && $_GET['settings-updated'] != "true") {
				_e( '<div id="revisr_alert" class="error"><p>There was an error updating the .gitignore on the remote repository.<br>
				The remote may be ahead, or the connection settings below may be incorrect.</p></div>', 'revisr' );
			}
			if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] == "true" ) {
				_e( '<div id="revisr_alert" class="updated"><p>Settings updated successfully.</p></div>', 'revisr' );
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