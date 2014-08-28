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

	$git = new Revisr_Git;
	$options = Revisr_Admin::options();
	
	if ( isset( $options['gitignore'] ) ) {
		chdir( ABSPATH );
		file_put_contents( ".gitignore", $options['gitignore'] );
	}
	if ( isset( $options['username'] ) && $options['username'] != "" ) {
		Revisr_Git::run('config user.name "' . $options['username'] . '"');
	}
	if ( isset( $options['email'] ) && $options['email'] != "" ) {
		Revisr_Git::run('config user.email "' . $options['email'] . '"');
	}
	if ( isset( $options['remote_url'] ) && $options['remote_url'] != "" ) {
		Revisr_Git::run('config remote.origin.url ' . $options['remote_url']);
	}

	Revisr_Git::run("add .gitignore");
	$commit_msg = __( 'Updated .gitignore.', 'revisr' );
	Revisr_Git::run("commit -m \"$commit_msg\"");

	$git->auto_push();

	chdir( $git->dir );
}

?>

<div class="wrap">
	<div id="revisr_settings">
		<h2><?php _e( 'Revisr - Settings', 'revisr' ); ?></h2>
		<?php
			$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general_settings';
			if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] == "true" ) {
				_e( '<div id="revisr_alert" class="updated"><p>Settings updated successfully.</p></div>', 'revisr' );
			}
		?>
		<h2 class="nav-tab-wrapper">
		    <a href="?page=revisr_settings&tab=general_settings" class="nav-tab <?php echo $active_tab == 'general_settings' ? 'nav-tab-active' : ''; ?>">General</a>
		    <a href="?page=revisr_settings&tab=remote_settings" class="nav-tab <?php echo $active_tab == 'remote_settings' ? 'nav-tab-active' : ''; ?>">Remote Repository</a>
		    <a href="?page=revisr_settings&tab=database_settings" class="nav-tab <?php echo $active_tab == 'database_settings' ? 'nav-tab-active' : ''; ?>">Database</a>
		</h2>
		<form class="settings-form" method="post" action="options.php">
			<?php

				//Decides which settings to display.
				$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general_settings';
	            if ( $active_tab == 'general_settings' ) {
	            	settings_fields( 'revisr_general_settings' );   
	            	do_settings_sections( 'revisr_general_settings' );
	            } else if ( $active_tab == 'remote_settings' ) {
		            settings_fields( 'revisr_remote_settings' );   
	            	do_settings_sections( 'revisr_remote_settings' );
	            } else {
		            settings_fields( 'revisr_database_settings' );   
	            	do_settings_sections( 'revisr_database_settings' );
	            }

	            submit_button(); 
		    ?>
		</form>
	</div>
</div>