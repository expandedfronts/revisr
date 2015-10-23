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
						get_admin_url( null, 'admin.php?page=revisr' ),
						__( 'Continue to dashboard.', 'revisr' )
					);
				?>

			<?php elseif ( ! $step || '1' === $step ): ?>

				<?php $thanks = __( 'Thanks for installing Revisr!', 'revisr' ); ?>

				<?php

					if ( isset( $_GET['revisr_git_path'] ) ) {
						Revisr_Admin::verify_nonce( $_GET['revisr_setup_nonce'], 'revisr_setup_nonce' );

						// Define in the wp-config.
						$path = filter_input( INPUT_GET, 'revisr_git_path', FILTER_SANITIZE_STRING );
						$line = "define('REVISR_GIT_PATH', '$path');";
						Revisr_Admin::replace_config_line( 'define *\( *\'REVISR_GIT_PATH\'', $line );

						// Update the instance.
						revisr()->git->git_path = $path;
					}

				?>

				<?php if ( revisr()->git->run( 'version', array() ) ): ?>

					<p><?php echo $thanks . ' ' . __( 'No Repository was detected.', 'revisr' ); ?></p>

					<p><?php _e( 'Would you like to create a new one?', 'revisr' ); ?></p>

					<select name="action" class="revisr-setup-input">
						<option value="create"><?php _e( 'Yes, create a new repository.', 'revisr' ); ?></option>
						<option value="find"><?php _e( 'No, find an existing repository.', 'revisr' ); ?></option>
						<option value="skip"><?php _e( 'Skip setup...', 'revisr' ); ?></option>
					</select>

					<br /><br />

					<input type="hidden" name="step" value="2" />
					<button class="button button-primary" type="submit" style="float:right;"><?php _e( 'Next step...', 'revisr' ); ?></button>

				<?php else: ?>

					<?php if ( isset( $_GET['revisr_git_path'] ) ): ?>
						<p><strong><?php _e( 'The install path to Git was not recognized.', 'revisr' ); ?></strong></p>
					<?php else: ?>
						<p><?php echo $thanks . ' ' . __( 'Git was not detected on the server.', 'revisr' ); ?></p>
					<?php endif; ?>

					<p><?php _e( 'Please enter the install path to Git below and click "Continue".', 'revisr' ); ?></p>

					<input name="revisr_git_path" class="regular-text revisr-setup-input" />

					<br><br>
					<button class="button button-primary" type="submit" name="step" value="1"><?php _e( 'Continue', 'revisr' ); ?></button>

				<?php endif; // git version check ?>

				<br /><br />

			<?php elseif ( '2' === $step ): ?>

				<?php Revisr_Admin::verify_nonce( $_GET['revisr_setup_nonce'], 'revisr_setup_nonce' ); ?>

				<?php if ( 'create' === $action ): ?>

					<p><?php _e( 'What would you like to track?', 'revisr' ); ?></p>

					<select id="revisr-tracking-options" name="tracking_options">
						<option value="<?php echo ABSPATH; ?>"><?php _e( 'Everything', 'revisr' ); ?></option>
						<option value="<?php echo WP_CONTENT_DIR; ?>"><?php _e( 'Plugins, Themes, and Uploads', 'revisr' ); ?></option>
						<option value="single"><?php _e( 'A plugin or theme...', 'revisr' ); ?></option>
						<?php
							if ( defined( 'REVISR_WORK_TREE' ) && constant( 'REVISR_WORK_TREE' ) !== false ) {
								echo '<option value="' . REVISR_WORK_TREE . '">' . __( 'Custom (REVISR_WORK_TREE)', 'revisr' ) . '</option>';
							}
						?>
					</select>

					<br /><br />

					<div id="revisr-plugin-or-theme-wrap" style="display:none;">

						<p><?php _e( 'Please select a plugin or theme...', 'revisr' ); ?></p>

						<select id="revisr-plugin-or-theme" name="plugin_or_theme_select" style="display:none;">
							<option></option>
							<optgroup label="<?php _e( 'Plugins', 'revisr' ); ?>">
								<?php
									foreach( get_plugins() as $k => $v ) {

										// Get the path to the plugin.
										$plugin_folder 	= explode( '/', $k );
										$plugin_path 	= WP_PLUGIN_DIR . '/' .$plugin_folder[0];

										// Only allow plugins that are in a subdirectory.
										if ( is_dir( $plugin_path ) ) {
											printf( '<option value="%s">%s</option>', $plugin_path, $v['Name'] );
										}
									}
								?>
							</optgroup>

							<optgroup label="<?php _e( 'Themes', 'revisr' ); ?>">
								<?php
									foreach ( wp_get_themes() as $theme ) {
										echo '<option value="' . get_theme_root() . DIRECTORY_SEPARATOR . $theme->Template . '">' . $theme->Name . '</option>';
									}
								?>
							</optgroup>

						</select>
						<br /><br />

					</div>

					<p><?php _e( 'Revisr will try to create a repository in the following directory:', 'revisr' ); ?></p>
					<input id="revisr-create-path" class="regular-text revisr-setup-input disabled" name="revisr_git_dir" type="text" disabled="disabled" value="<?php echo ABSPATH; ?>" />

					<?php printf( '<p><strong>%s</strong>%s</p>', __( 'Note: ', 'revisr' ), __( 'You can always ignore specific files/directories within the repository later by going to the Revisr Settings page.', 'revisr' ) ); ?>

					<br /><br />

					<input type="hidden" name="step" value="3" />
					<button class="button button-primary revisr-setup-input" type="submit"><?php _e( 'Initialize Revisr', 'revisr' ); ?></button>

				<?php elseif ( 'find' === $action ): ?>

					<p><?php _e( 'Revisr wasn\'t able to automatically detect your repository.', 'revisr' ); ?></p>
					<p><?php _e( 'Enter the path to the repository below to try to detect it manually.', 'revisr' ); ?></p>

					<input id="revisr-manual-repo-path" class="regular-text revisr-setup-input" name="revisr_manual_git_dir" value="<?php echo ABSPATH; ?>" />
					<input type="hidden" name="action" value="check" />

					<div class="revisr-setup-nav">
						<button class="button" type="submit" name="step" value="1"><?php _e( 'Go Back', 'revisr' ); ?></button>
						<button class="button button-primary" type="submit" style="float:right;" name="step" value="2"><?php _e( 'Check path...', 'revisr' ); ?></button>
					</div>
					<div style="clear:both;"></div>

				<?php elseif ( 'skip' === $action ): ?>

					<?php set_transient( 'revisr_skip_setup', true, 12 * HOUR_IN_SECONDS ); ?>

					<p><?php _e( 'Setup has been skipped for this session.', 'revisr' ); ?></p>
					<p><?php _e( 'If you want to permanently skip the setup, you can do so by adding the following to your "wp-config.php" file:', 'revisr' ); ?></p>

					<pre>define( 'REVISR_SKIP_SETUP', true );</pre>

					<?php printf( '<p><a href="%s">%s</a></p>', get_admin_url( null, 'admin.php?page=revisr' ), __( 'Continue to dashboard', 'revisr' ) ); ?>

				<?php elseif( 'check' === $action ): ?>

					<?php

						$dir = filter_input( INPUT_GET, 'revisr_manual_git_dir', FILTER_SANITIZE_STRING );

						revisr()->git->work_tree = $dir;

						if ( ! defined( 'REVISR_GIT_DIR' ) ) {
							revisr()->git->git_dir = $dir . '/.git';
						}

						if ( revisr()->git->check_work_tree( $dir ) ) {

							// Write it to the wp-config file if necessary.
							$line = "define('REVISR_WORK_TREE', '$dir');";
							Revisr_Admin::replace_config_line( 'define *\( *\'REVISR_WORK_TREE\'', $line );

							Revisr_Admin::clear_transients();

							printf( '<p>%s</p><br><a href="%s">%s</a>',
								__( 'Repository detected succesfully.', 'revisr' ),
								get_admin_url() . 'admin.php?page=revisr',
								__( 'Continue to dashboard.', 'revisr' )
							);

						} else {
						?>
							<div class="alert"><p class="alert error"><?php _e( 'Repository not found. Double-check the path and try again.', 'revisr' ); ?></p></div>

							<input id="revisr-manual-repo-path" class="regular-text revisr-setup-input" name="revisr_manual_git_dir" value="<?php echo ABSPATH; ?>" />
							<input type="hidden" name="action" value="check" />

							<div class="revisr-setup-nav">
								<button class="button" type="submit" name="step" value="1"><?php _e( 'Go Back', 'revisr' ); ?></button>
								<button class="button button-primary" type="submit" style="float:right;" name="step" value="2"><?php _e( 'Check path...', 'revisr' ); ?></button>
							</div>
							<div style="clear:both;"></div>
						<?php
						}

					?>

				<?php else: ?>

				<?php endif; ?>

			<?php elseif( '3' === $step ): ?>

				<?php Revisr_Admin::verify_nonce( $_GET['revisr_setup_nonce'], 'revisr_setup_nonce' ); ?>

				<?php

					$tracking 			= filter_input( INPUT_GET, 'tracking_options', FILTER_SANITIZE_STRING );
					$plugin_or_theme 	= filter_input( INPUT_GET, 'plugin_or_theme_select', FILTER_SANITIZE_STRING );
					$gitignore 			= array();
					$dir 				= $tracking;

					// Customize .gitignore & git dir as necessary.
					switch ( $tracking ) {

						case ABSPATH:
							$gitignore = array(
								'.htaccess',
								'wp-config.php',
							);
							break;

						case WP_CONTENT_DIR:
							break;

						case 'single':
							$dir = $plugin_or_theme;
							break;

					}

					// Write it to the wp-config file if necessary.
					$line = "define('REVISR_WORK_TREE', '$dir');";
					Revisr_Admin::replace_config_line( 'define *\( *\'REVISR_WORK_TREE\'', $line );

					// Refresh the 'Revisr_Git' instance.
					revisr()->git = new Revisr_Git;

					// Create the .gitignore file BEFORE repo creation.
					$gitignore_file = revisr()->git->get_work_tree() . DIRECTORY_SEPARATOR . '.gitignore';
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
							get_admin_url( null, 'admin.php?page=revisr' ),
							__( 'Continue to dashboard.') );
					} else {
						printf( '<p>%s</p>', __( 'Error creating repository.', 'revisr' ) );
					}

				?>

			<?php endif; ?>

			<?php wp_nonce_field( 'revisr_setup_nonce', 'revisr_setup_nonce', false ); ?>

		</form><!-- /#revisr-setup-form -->

	</div><!-- /#revisr-setup-container -->

</div><!-- /.wrap -->
