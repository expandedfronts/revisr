<?php
/**
 * Displays the setup page.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

// Grab the step and current action.
$step 	= filter_input( INPUT_GET, 'step', FILTER_SANITIZE_NUMBER_INT );
$action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );

delete_transient( 'revisr_skip_setup' );

?>

<div class="wrap">

	<div id="revisr-setup-container">

		<form id="revisr-setup-form" method="get">

			<input type="hidden" name="page" value="revisr_setup" />

			<h2><div class="revisr-arrow-logo"></div><?php _e( 'Revisr - Installation', 'revisr' ); ?></h2>

			<?php if ( revisr()->git->is_repo ): ?>

				<?php
					printf( '<p>%s<br><a href="%s">%s</a></p>',
						__( 'Revisr has succesfully detected a Git repository.', 'revisr' ),
						get_admin_url() . 'admin.php?page=revisr',
						__( 'Continue to dashboard.', 'revisr' )
					);
				?>

			<?php elseif ( ! $step || '1' === $step ): ?>

				<p><?php _e( 'Thanks for installing Revisr! No repository was detected.', 'revisr' ); ?></p>

				<p><?php _e( 'Would you like to create a new one?', 'revisr' ); ?></p>

				<select name="action" class="revisr-setup-input">
					<option value="create"><?php _e( 'Yes, create a new repository.', 'revisr' ); ?></option>
					<option value="find"><?php _e( 'No, find an existing repository.', 'revisr' ); ?></option>
					<option value="skip"><?php _e( 'Skip setup...', 'revisr' ); ?></option>
				</select>

				<br /><br />

				<input type="hidden" name="step" value="2" />
				<button class="button button-primary" type="submit" style="float:right;"><?php _e( 'Next step...', 'revisr' ); ?></button>
				<br /><br />

			<?php elseif ( '2' === $step ): ?>

				<?php if ( 'create' === $action ): ?>

					<p><?php _e( 'Revisr will try to create a repository in the following directory:', 'revisr' ); ?></p>
					<input id="revisr-create-path" class="regular-text revisr-setup-input disabled" type="text" disabled="disabled" value="<?php echo revisr()->git->git_dir; ?>" />

					<p><?php _e( 'What would you like to track?', 'revisr' ); ?></p>

					<select id="revisr-tracking-options" name="tracking_options">
						<option value="everything"><?php _e( 'Everything', 'revisr' ); ?></option>
						<option value="wp_content"><?php _e( 'Plugins, Themes, and Uploads', 'revisr' ); ?></option>
						<option value="single"><?php _e( 'A single plugin or theme...', 'revisr' ); ?></option>
					</select>

					<br /><br />

					<div id="revisr-plugin-or-theme-wrap" style="display:none;">

					<p><?php _e( 'Please select a plugin or theme...', 'revisr' ); ?></p>

						<select id="revisr-plugin-or-theme" name="plugin_or_theme_select" style="display:none;" />
							<option></option>
							<optgroup label="<?php _e( 'Plugins', 'revisr' ); ?>">
								<?php
									foreach( get_plugins() as $k => $v ) {
										printf( '<option value="%s">%s</option>', $k, $v['Name'] );
									}
								?>
							</optgroup>

							<optgroup label="<?php _e( 'Themes', 'revisr' ); ?>">
								<?php
									foreach ( wp_get_themes() as $theme ) {
										echo '<option value="' . $theme->Template . '">' . $theme->Name . '</option>';
									}
								?>
							</optgroup>

						</select>

					</div>

					<br /><br />

					<input type="hidden" name="step" value="3" />
					<button class="button button-primary revisr-setup-input" type="submit"><?php _e( 'Initialize Revisr', 'revisr' ); ?></button>

				<?php elseif ( 'find' === $action ): ?>

					<p><?php _e( 'Revisr wasn\'t able to automatically detect your repository.', 'revisr' ); ?></p>

				<?php elseif ( 'skip' === $action ): ?>

					<?php set_transient( 'revisr_skip_setup', true, 12 * HOUR_IN_SECONDS ); ?>

					<p><?php _e( 'Setup has been skipped for this session.', 'revisr' ); ?></p>
					<p><?php _e( 'If you want to permanently skip the setup, you can do so by adding the following to your "wp-config.php" file:', 'revisr' ); ?></p>

					<pre>define( 'REVISR_SKIP_SETUP', true );</pre>

					<?php printf( '<p><a href="%s">%s</a></p>', get_admin_url() . 'admin.php?page=revisr', __( 'Continue to dashboard', 'revisr' ) ); ?>

				<?php else: ?>

				<?php endif; ?>


			<?php elseif ( '3' === $step ): ?>

				<?php

					$tracking 	= filter_input( INPUT_GET, 'tracking_options', FILTER_SANITIZE_STRING );
					$gitignore 	= array();

					switch ( $tracking ) {

						case 'everything':
							$gitignore = array(
								'.htaccess',
								'wp-config.php',
							);
							break;

						case 'wp_content':
							$gitignore = array(
								'/*',
								'/*/',
								'!/wp-content/'
								);
							break;

						case 'single':
						default:
							break;

					}

					// Create .gitiginore BEFORE repo creation.
					$gitignore_file = revisr()->git->get_git_dir() . '/.gitignore';
					if ( ! file_exists( $gitignore_file ) && ! empty( $gitignore ) ) {
						file_put_contents( $gitignore_file, implode( PHP_EOL, $gitignore ) );
					}

				?>

				<p><?php _e( 'Attempting to create repository...', 'revisr' ); ?></p>

				<?php

					define( 'REVISR_SETUP_INIT', true );

					if ( revisr()->git->init_repo() ) {
						printf( '<p>%s</p><br><a href="%s">%s</a>',
							__( 'Repository created successfully.', 'revisr' ),
							get_admin_url() . 'admin.php?page=revisr',
							__( 'Continue to dashboard.') );
					} else {
						printf( '<p>%s</p>', __( 'Error creating repository.', 'revisr' ) );
					}

				?>

			<?php endif; ?>

		</form><!-- /#revisr-setup-form -->

	</div><!-- /#revisr-setup-container -->

</div><!-- /.wrap -->
