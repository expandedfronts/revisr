<?php
/**
 * class-revisr-settings-fields.php
 *
 * Displays (and updates) the settings fields.
 *
 * @package   	Revisr
 * @license   	GPLv3
 * @link      	https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr_Settings_Fields {

	/**
	 * A reference back to the main Revisr instance.
	 * @var object
	 */
	protected $revisr;

	/**
	 * Initialize the class.
	 * @access public
	 */
	public function __construct() {
		$this->revisr 	= revisr();
	}

	/**
	 * Checks if a setting has been saved and is not empty.
	 * Used to determine if we should update the .git/config.
	 * @access private
	 * @param  string $option The option to check.
	 * @return boolean
	 */
	private function is_updated( $option ) {
		if ( isset( $_GET['settings-updated'] ) ) {
			if ( isset( $this->revisr->options[$option] ) && $this->revisr->options[$option] != '' ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Displays the description for the "General Settings" tab.
	 * @access public
	 */
	public function revisr_general_settings_callback() {
		_e( 'These settings configure the local repository, and may be required for Revisr to work correctly.', 'revisr' );
	}

	/**
	 * Displays the description for the "Remote Settings" tab.
	 * @access public
	 */
	public function revisr_remote_settings_callback() {
		_e( 'These settings are optional, and only need to be configured if you plan to push your website to a remote repository like Bitbucket or Github.', 'revisr' );
	}

	/**
	 * Displays the description for the "Database Settings" tab.
	 * @access public
	 */
	public function revisr_database_settings_callback() {
		_e( 'These settings configure how Revisr interacts with your database, if at all.', 'revisr' );
	}
	/**
	 * Displays/updates the "Username" settings field.
	 * @access public
	 */
	public function username_callback() {

		// Update the .git/config if necessary.
        if ( $this->is_updated( 'username' ) ) {
        	$this->revisr->git->set_config(  'user', 'name', $this->revisr->options['username'] );
        }

		$check_username = $this->revisr->git->get_config( 'user', 'name' );
		if ( $check_username ) {
			$username = $check_username;
		} elseif ( isset( $this->revisr->options['username'] ) ) {
			$username = $this->revisr->options['username'];
		} else {
			$username = '';
		}

		printf(
            '<input type="text" id="username" name="revisr_general_settings[username]" value="%s" class="regular-text revisr-text" />
            <p class="description revisr-description">%s</p>',
           esc_attr( $username ),
            __( 'The username to commit with in Git.', 'revisr' )
        );
	}

	/**
	 * Displays/updates the "Email" settings field.
	 * @access public
	 */
	public function email_callback() {

		// Update the .git/config if necessary.
        if ( $this->is_updated( 'email' ) ) {
        	$this->revisr->git->set_config( 'user', 'email',  $this->revisr->options['email'] );
        }

		$check_email = $this->revisr->git->get_config( 'user', 'email' );
		if ( $check_email ) {
			$email = $check_email;
		} elseif ( isset( $this->revisr->options['email'] ) ) {
			$email = $this->revisr->options['email'];
		} else {
			$email = '';
		}

		printf(
            '<input type="text" id="email" name="revisr_general_settings[email]" value="%s" class="regular-text revisr-text" />
            <p class="description revisr-description">%s</p>',
           	esc_attr( $email ),
            __( 'The email address associated to your Git username. Also used for notifications (if enabled).', 'revisr' )
        );
	}

	/**
	 * Displays/updates the ".gitignore" settings field.
	 * @access public
	 */
	public function gitignore_callback() {

		// Update the .gitignore if necessary.
		if ( $this->is_updated( 'gitignore' ) ) {
			$this->revisr->git->update_gitignore();
		}

		// Grab the contents from the .gitignore.
		if ( file_exists( $this->revisr->git->git_dir . '/.gitignore' ) ) {
			$gitignore = file_get_contents( $this->revisr->git->git_dir . '/.gitignore' );
		} else {
			$gitignore = $this->revisr->options['gitignore'] ? $this->revisr->options['gitignore'] : '';
		}

		// Display the settings field.
		printf(
            '<textarea id="gitignore" name="revisr_general_settings[gitignore]" rows="6" />%s</textarea>
            <p class="description revisr-description">%s</p>',
            esc_textarea( $gitignore ),
            __( 'Add files or directories that you don\'t want to show up in Git here, one per line.<br>This will update the ".gitignore" file for this repository.', 'revisr' )
		);
	}

	/**
	 * Displays/updates the "Automatic Backups" settings field.
	 * @access public
	 */
	public function automatic_backups_callback() {
		if ( isset( $this->revisr->options['automatic_backups'] ) ) {
			$schedule = $this->revisr->options['automatic_backups'];
		} else {
			$schedule = 'none';
		}
		?>
			<select id="automatic_backups" name="revisr_general_settings[automatic_backups]">
				<option value="none" <?php selected( $schedule, 'none' ); ?>><?php _e( 'None', 'revisr' ); ?></option>
				<option value="daily" <?php selected( $schedule, 'daily' ); ?>><?php _e( 'Daily', 'revisr' ); ?></option>
				<option value="weekly" <?php selected( $schedule, 'weekly' ); ?>><?php _e( 'Weekly', 'revisr' ); ?></option>
			</select>
			<span class="description"><?php _e( 'Automatic backups will backup both the files and database at the interval of your choosing.', 'revisr' ); ?></span>
		<?php

		// Update the cron settings/clear if necessary on save.
		if ( $this->is_updated( 'automatic_backups' ) ) {

			// Clear the existing cron.
			wp_clear_scheduled_hook( 'revisr_cron' );


			if ( isset( $this->revisr->options['automatic_backups'] ) && $this->revisr->options['automatic_backups'] != 'none' ) {

				// Schedule the next one!
				$next_time = time() + 300;
				wp_schedule_event( $next_time, $this->revisr->options['automatic_backups'], 'revisr_cron' );

			}
		}

	}

	/**
	 * Displays/updates the "Notifications" settings field.
	 * @access public
	 */
	public function notifications_callback() {
		printf(
			'<input type="checkbox" id="notifications" name="revisr_general_settings[notifications]" %s />
			<label for="notifications"><span class="description">%s</span></label>',
			isset( $this->revisr->options['notifications'] ) ? "checked" : '',
			__( 'Enabling notifications will send updates about new commits, pulls, and pushes to the email address above.', 'revisr' )
		);
	}

	/**
	 * Displays/updates the "Remote Name" settings field.
	 * @access public
	 */
	public function remote_name_callback() {
		printf(
			'<input type="text" id="remote_name" name="revisr_remote_settings[remote_name]" value="%s" class="regular-text revisr-text" placeholder="origin" />
			<p class="description revisr-description">%s</p>',
			isset( $this->revisr->options['remote_name'] ) ? esc_attr( $this->revisr->options['remote_name']) : '',
			__( 'Git sets this to "origin" by default when you clone a repository, and this should be sufficient in most cases. If you\'ve changed the remote name or have more than one remote, you can specify that here.', 'revisr' )
		);

		if ( $this->is_updated( 'remote_name' ) ) {
			$remote_name = $this->revisr->options['remote_name'];
		} else {
			$remote_name = 'origin';
		}

		// Sets the remote name and/or URL if necessary.
		if ( isset( $this->revisr->options['remote_url'] ) ) {
			$add = $this->revisr->git->run( 'remote',  array( 'add', $remote_name, $this->revisr->options['remote_url'] ) );
			if ( $add == false ) {
				$this->revisr->git->run( 'remote', array( 'set-url', $remote_name, $this->revisr->options['remote_url'] ) );
			}
		}
	}

	/**
	 * Displays/updates the "Remote URL" settings field.
	 * @access public
	 */
	public function remote_url_callback() {

		$check_remote = $this->revisr->git->get_config( 'remote', 'origin.url' );

		if ( false !== $check_remote ) {
			$remote = $check_remote;
		} elseif ( isset( $this->revisr->options['remote_url'] ) ) {
			$remote = $this->revisr->options['remote_url'];
		} else {
			$remote = '';
		}

		printf(
			'<input type="text" id="remote_url" name="revisr_remote_settings[remote_url]" value="%s" class="regular-text revisr-text" placeholder="https://user:pass@host.com/user/example.git" /><span id="verify-remote"></span>
			<p class="description revisr-description">%s</p>',
			$remote,
			__( 'Useful if you need to authenticate over "https://" instead of SSH, or if the remote has not already been set through Git.', 'revisr' )
		);
	}

	/**
	 * Displays/updates the "Revisr Webhook URL" settings field.
	 * @access public
	 */
	public function webhook_url_callback() {
		// Allow the user to unset the Webhook URL.
		if ( isset( $_GET['settings-updated'] ) ) {
			if ( $this->is_updated( 'webhook_url' ) ) {
				$this->revisr->git->set_config( 'revisr', 'webhook-url', $this->revisr->options['webhook_url'] );
			} else {
				$this->revisr->git->run( 'config', array( '--unset', 'revisr.webhook-url' ) );
			}
		}

		// Grab the URL from the .git/config as it MAY be replaced in the database.
		$get_url = $this->revisr->git->get_config( 'revisr', 'webhook-url' );
		if ( $get_url ) {
			$webhook_url = urldecode($get_url);
		} else {
			$webhook_url = '';
		}
		printf(
			'<input type="text" name="revisr_remote_settings[webhook_url]" value="%s" class="regular-text revisr-text" /><p class="description revisr-description">%s</p>',
			$webhook_url,
			__( 'If you have Revisr installed on another server using the same repository,<br> you can add the Revisr Webhook from that server here to trigger an update when pushing.', 'revisr' )
		);
	}

	/**
	 * Displays/updates the "Auto Push" settings field.
	 * @access public
	 */
	public function auto_push_callback() {
		if ( isset( $_GET['settings-updated'] ) ) {
			if ( isset( $this->revisr->options['auto_push'] ) ) {
				$this->revisr->git->set_config( 'revisr', 'auto-push', 'true' );
			} else {
				$this->revisr->git->run( 'config', array( '--unset', 'revisr.auto-push' ) );
			}
		}

		printf(
			'<input type="checkbox" id="auto_push" name="revisr_remote_settings[auto_push]" %s />
			<label for="auto_push">%s</label>',
			checked( $this->revisr->git->get_config( 'revisr', 'auto-push' ), 'true', false ),
			__( 'Check to automatically push new commits to the remote repository.', 'revisr' )
		);
	}

	/**
	 * Displays/updates the "Auto Pull" settings field.
	 * @access public
	 */
	public function auto_pull_callback() {
		if ( isset( $_GET['settings-updated'] ) ) {
			if ( isset( $this->revisr->options['auto_pull'] ) ) {
				$this->revisr->git->set_config( 'revisr', 'auto-pull', 'true' );
			} else {
				$this->revisr->git->run( 'config', array( '--unset', 'revisr.auto-pull' ) );
			}
		}

		printf(
			'<input type="checkbox" id="auto_pull" name="revisr_remote_settings[auto_pull]" %s />
			<label for="auto_pull">%s</label>',
			checked( $this->revisr->git->get_config( 'revisr', 'auto-pull' ), 'true', false ),
			__( 'Check to generate the Revisr Webhook and allow Revisr to automatically pull commits from a remote repository.', 'revisr' )
		);
		$remote 	= new Revisr_Remote();
		$token 		= $remote->get_token();

		if ( $token ) {
			$post_hook 	= get_admin_url() . 'admin-post.php?action=revisr_update&token=' . $remote->get_token();

			?>
			<div id="post-hook">
				<p class="description revisr-description"><?php _e( 'Revisr Webhook:', 'revisr' ); ?></p>
				<input id="post-hook-input" type="text" value="<?php echo $post_hook; ?>" disabled />
				<p class="description revisr-description"><?php _e( 'You can add the above webhook to Bitbucket, GitHub, or another instance of Revisr to automatically update this repository.', 'revisr' ); ?></p>
			</div>
			<?php
		}
		else {
			echo '<p id="post-hook" class="description">' . __( 'There was an error generating the webhook. Please make sure that Revisr has write access to the ".git/config" and try again.', 'revisr' ) . '</p>';
		}

	}

	/**
	 * Displays/updates the "DB Tracking" settings field.
	 * @access public
	 */
	public function tracked_tables_callback() {
		if ( $this->is_updated( 'db_tracking' ) ) {
			$this->revisr->git->set_config( 'revisr', 'db-tracking', $this->revisr->options['db_tracking'] );
		}

		if ( $db_tracking = $this->revisr->git->get_config( 'revisr', 'db-tracking' ) ) {
			if ( $db_tracking == 'custom' && $this->is_updated( 'tracked_tables' ) ) {
				$this->revisr->git->run( 'config', array( '--unset-all', 'revisr.tracked-tables' ) );
				$tables = $this->revisr->options['tracked_tables'];
				foreach ( $tables as $table ) {
					$this->revisr->git->run( 'config', array( '--add', 'revisr.tracked-tables', $table ) );
				}
			} elseif ( $db_tracking != 'custom' ) {
				$this->revisr->git->run( 'config', array( '--unset-all', 'revisr.tracked-tables' ) );
			}
		} else {
			$db_tracking = '';
		}

		?>
		<select id="db-tracking-select" name="revisr_database_settings[db_tracking]">
			<option value="all_tables" <?php selected( $db_tracking, 'all_tables' ); ?>><?php _e( 'All Tables', 'revisr' ); ?></option>
			<option value="custom" <?php selected( $db_tracking, 'custom' ); ?>><?php _e( 'Let me decide...', 'revisr' ); ?></option>
			<option value="none" <?php selected( $db_tracking, 'none' ); ?>><?php _e( 'None', 'revisr' ); ?></option>
		</select>

		<?php
		// Allows the user to select the tables they want to track.
		$db 	= new Revisr_DB();
		$tables = $db->get_tables();
		$sizes  = $db->get_sizes();
		echo '<div id="advanced-db-tracking" style="display:none;"><br><select name="revisr_database_settings[tracked_tables][]" multiple="multiple" style="width:35em;height:250px;">';
		if ( is_array( $tables ) ) {
			foreach ( $tables as $table ) {
				$table_selected = '';
				if ( in_array( $table, $db->get_tracked_tables() ) ) {
					$table_selected = ' selected';
				}
				echo "<option value='$table'$table_selected>$table $sizes[$table]</option>";
			}
		}
		echo '</select></div>';
	}

	/**
	 * Displays/updates the "Development URL" settings field.
	 * NOTE: DO NOT USE THE OPTION AS STORED IN THE DATABASE!
	 * @access public
	 */
	public function development_url_callback() {
		// Allow the user to unset the dev URL.
		if ( isset( $_GET['settings-updated'] ) ) {
			if ( $this->is_updated( 'development_url' ) ) {
				$this->revisr->git->set_config( 'revisr', 'dev-url', esc_url_raw( $this->revisr->options['development_url'] ) );
			} else {
				$this->revisr->git->run( 'config', array( '--unset', 'revisr.dev-url' ) );
			}
		}

		// Grab the URL from the .git/config as it will be replaced in the database.
		$get_url = $this->revisr->git->get_config( 'revisr', 'dev-url' );
		if ( $get_url !== false ) {
			$dev_url = $get_url;
		} else {
			$dev_url = '';
		}

		printf(
			'<input type="text" id="development_url" name="revisr_database_settings[development_url]" class="regular-text revisr-text" value="%s" />
			<p class="description revisr-description">%s</p>',
			$dev_url,
			__( 'If you\'re importing the database from a seperate environment, enter the WordPress Site URL for that environment here to replace all occurrences of that URL with the current Site URL during import. This MUST match the WordPress Site URL of the database being imported.', 'revisr' )
		);
	}

	/**
	 * Displays/updates the "DB Driver" settings field.
	 * @access public
	 */
	public function db_driver_callback() {
		if ( $this->is_updated( 'db_driver' ) ) {
			$this->revisr->git->set_config( 'revisr', 'db-driver', $this->revisr->options['db_driver'] );
		}

		$current = $this->revisr->git->get_config( 'revisr', 'db-driver' );

		?>
		<select id="db-driver-select" name="revisr_database_settings[db_driver]">
			<option value="mysql" <?php selected( 'mysql', $current ); ?>><?php _e( 'MySQL', 'revisr' ); ?></option>
			<option value="wpdb" <?php selected( 'wpdb', $current ); ?>><?php _e( 'WordPress', 'revisr' ); ?></option>
		</select>
		<p class="description"><?php _e( 'MySQL can be faster, but may not be available on some servers.', 'revisr' ); ?></p>

		<?php

	}

	/**
	 * Displays/updates the "Path to MySQL" settings field.
	 * @access public
	 */
	public function mysql_path_callback() {
		if ( isset( $_GET['settings-updated'] ) ) {
			if ( $this->is_updated( 'mysql_path' ) ) {

				// Properly escape trailing backslashes on Windows.
				if ( substr( $this->revisr->options['mysql_path'], -1 ) === '\\' ) {
					$this->revisr->options['mysql_path'] .= '\\';
				}

				$this->revisr->git->set_config( 'revisr', 'mysql-path', $this->revisr->options['mysql_path'] );

			} else {
				$this->revisr->git->run( 'config', array( '--unset', 'revisr.mysql-path' ) );
			}
		}

		if ( $get_path = $this->revisr->git->get_config( 'revisr', 'mysql-path' ) ) {
			$mysql_path = $get_path;
		} else {
			$mysql_path = '';
		}

		printf(
			'<input type="text" id="mysql_path" name="revisr_database_settings[mysql_path]" value="%s" class="regular-text revisr-text" placeholder="" />
			<p class="description revisr-description">%s</p>',
			esc_attr( $mysql_path ),
			__( 'Leave blank if the full path to MySQL has already been set on the server. Some possible settings include:
			<br><br>For MAMP: /Applications/MAMP/Library/bin/<br>
			For WAMP: C:\wamp\bin\mysql\mysql5.6.12\bin\ ', 'revisr' )
		);
	}

	/**
	 * Displays/updates the "Reset DB" settings field.
	 * @access public
	 */
	public function reset_db_callback() {
		if ( isset( $_GET['settings-updated'] ) ) {

			if ( isset( $this->revisr->options['reset_db'] ) ) {
				$this->revisr->git->set_config( 'revisr', 'import-checkouts', 'true' );
			} else {
				$this->revisr->git->run( 'config', array( '--unset-all', 'revisr.import-checkouts' ) );
			}

			if ( isset( $this->revisr->options['import_db'] ) ) {
				$this->revisr->git->set_config( 'revisr', 'import-pulls', 'true' );
			} else {
				$this->revisr->git->run( 'config',  array( '--unset-all', 'revisr.import-pulls' ) );
			}
		}

		printf(
			'<input type="checkbox" id="reset_db" name="revisr_database_settings[reset_db]" %s /><label for="reset_db">%s</label><br><br>
			<input type="checkbox" id="import_db" name="revisr_database_settings[import_db]" %s /><label for="import_db">%s</label><br><br>
			<p class="description revisr-description">%s</p>',
			checked( $this->revisr->git->get_config( 'revisr', 'import-checkouts' ), 'true', false ),
			__( 'Import database when changing branches?', 'revisr' ),
			checked( $this->revisr->git->get_config( 'revisr', 'import-pulls' ), 'true', false ),
			__( 'Import database when pulling commits?', 'revisr' ),
			__( 'If checked, Revisr will automatically import the above tracked tables while pulling from or checking out a branch. The tracked tables will be backed up beforehand to provide a restore point immediately prior to the import. Use this feature with caution and only after verifying that you have a full backup of your website.', 'revisr' )
		);
	}
}
