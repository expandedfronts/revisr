<?php
/**
 * Displays the settings page.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 */

// Disallow direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

?>
<div class="wrap">
	<div id="revisr_settings">
		<h2><?php _e( 'Revisr - Settings', 'revisr' ); ?></h2>

		<?php
			$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general_settings';
			if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] == "true" ) {
				_e( '<div id="revisr_alert" class="updated" style="margin-top:20px;"><p>Settings updated successfully.</p></div>', 'revisr' );
			}
			if ( isset( $_GET['init'] ) && $_GET['init'] == 'success' ) {
				printf( '<div id="revisr_alert" class="updated" style="margin-top:20px;"><p>%s</p></div>',
					__( 'Successfully initialized a new repository. Please confirm the settings below before creating your first commit.', 'revisr' )
				);
			}
		?>

		<h2 class="nav-tab-wrapper">
		    <a href="?page=revisr_settings&tab=general_settings" class="nav-tab <?php echo $active_tab == 'general_settings' ? 'nav-tab-active' : ''; ?>"><?php _e( 'General', 'revisr' ); ?></a>
		    <a href="?page=revisr_settings&tab=remote_settings" class="nav-tab <?php echo $active_tab == 'remote_settings' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Remote', 'revisr' ); ?></a>
		    <a href="?page=revisr_settings&tab=database_settings" class="nav-tab <?php echo $active_tab == 'database_settings' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Database', 'revisr' ); ?></a>
		    <a href="?page=revisr_settings&tab=help" class="nav-tab <?php echo $active_tab == 'help' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Help', 'revisr' ); ?></a>
		</h2>

		<form class="settings-form" method="post" action="options.php">
			<?php
				// Decides which settings to display.
				$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general_settings';
	            if ( $active_tab == 'general_settings' ) {
	            	settings_fields( 'revisr_general_settings' );
	            	do_settings_sections( 'revisr_general_settings' );
	            } elseif ( $active_tab == 'remote_settings' ) {
		            settings_fields( 'revisr_remote_settings' );
	            	do_settings_sections( 'revisr_remote_settings' );
	            } elseif ( $active_tab == 'help' ) {
	            	include REVISR_PATH . 'templates/help.php';
	            } else {
		            settings_fields( 'revisr_database_settings' );
	            	do_settings_sections( 'revisr_database_settings' );
	            }

	            if ( $active_tab !== 'help' ) {
	            	submit_button();
	            }
		    ?>
		</form>
	</div>
</div>
