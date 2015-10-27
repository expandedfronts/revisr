<?php
/**
 * class-revisr-admin.php
 *
 * Common functions used throughout the Revisr admin.
 *
 * @package   	Revisr
 * @license   	GPLv3
 * @link      	https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr_Admin {

	/**
	 * Stores an alert to be rendered on the dashboard.
	 * @access public
	 * @param  string  	$message 	The message to display.
	 * @param  bool    	$is_error 	Whether the message is an error.
	 * @param  array  	$output 	An array of output to store for viewing error details.
	 */
	public static function alert( $message, $is_error = false, $output = array() ) {
		if ( true === $is_error ) {

			if ( is_array( $output ) && ! empty( $output ) ) {
				// Store info about the error for later.
				set_transient( 'revisr_error_details', $output );

				// Provide a link to view the error.
				$error_url 	= wp_nonce_url( admin_url( 'admin-post.php?action=revisr_view_error&TB_iframe=true&width=350&height=300' ), 'revisr_view_error', 'revisr_error_nonce' );
				$message 	.= sprintf( __( '<br>Click <a href="%s" class="thickbox" title="Error Details">here</a> for more details, or try again.', 'revisr' ), $error_url );
			}

			set_transient( 'revisr_error', $message, 10 );

		} else {
			set_transient( 'revisr_alert', $message, 3 );
		}
	}

	/**
	 * Returns the data for the AJAX buttons.
	 * @access public
	 */
	public function ajax_button_count() {
		if ( $_REQUEST['data'] == 'unpulled' ) {
			echo revisr()->git->count_unpulled();
		} else {
			echo revisr()->git->count_unpushed();
		}
		exit();
	}

	/**
	 * Deletes existing transients.
	 * @access public
	 */
	public static function clear_transients( $errors = true ) {
		if ( true === $errors ) {
			delete_transient( 'revisr_error' );
			delete_transient( 'revisr_error_details' );
		} else {
			delete_transient( 'revisr_alert' );
		}
	}

	/**
	 * Helper function for determining if we're in setup mode.
	 * @access public
	 * @return boolean
	 */
	public static function is_doing_setup() {

		if ( revisr()->git->is_repo ) {
			return false;
		} else {
			if ( defined( 'REVISR_SKIP_SETUP' ) || get_transient( 'revisr_skip_setup' ) ) {
				return false;
			}
			return true;
		}

	}

	/**
	 * Escapes a shell arguement.
	 * @access public
	 * @param  string $string The string to escape.
	 * @return string $string The escaped string.
	 */
	public static function escapeshellarg( $string ) {
		$os = Revisr_Compatibility::get_os();
		if ( 'WIN' !== $os['code'] ) {
			return escapeshellarg( $string );
		} else {
			// Windows-friendly workaround.
			return '"' . str_replace( "'", "'\\''", $string ) . '"';
		}
	}

	/**
	 * Logs an event to the database.
	 * @access public
	 * @param  string $message The message to show in the Recent Activity.
	 * @param  string $event   Will be used for filtering later.
	 * @param  string $user    An optional user to associate the record with.
	 */
	public static function log( $message, $event, $user = '' ) {

		global $wpdb;

		$time  	= current_time( 'mysql' );
		$table 	= Revisr::get_table_name();

		if ( '' === $user ) {
			$user = wp_get_current_user();
			$username = $user->user_login;
		} else {
			$username = $user;
		}

		if ( ! $username || '' === $username ) {
			$username = __( 'Revisr Bot', 'revisr' );
		}

		$wpdb->insert(
			"$table",
			array(
				'time' 		=> $time,
				'message'	=> $message,
				'event' 	=> $event,
				'user' 		=> $username,
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);
	}

	/**
	 * Notifies the admin if notifications are enabled.
	 * @access private
	 * @param  string $subject The subject line of the email.
	 * @param  string $message The message for the email.
	 */
	public static function notify( $subject, $message ) {
		$options 	= Revisr::get_options();
		$url 		= get_admin_url() . 'admin.php?page=revisr';

		if ( isset( $options['notifications'] ) ) {
			$email 		= $options['email'];
			$message	.= '<br><br>';
			$message	.= sprintf( __( '<a href="%s">Click here</a> for more details.', 'revisr' ), $url );
			$headers 	= "Content-Type: text/html; charset=ISO-8859-1\r\n";
			wp_mail( $email, $subject, $message, $headers );
		}
	}

	/**
	 * Renders an alert and removes the old data.
	 * @access public
	 * @param  boolean $errors_only Whether or not to only display errors.
	 */
	public static function render_alert( $errors_only = false ) {
		$alert = get_transient( 'revisr_alert' );
		$error = get_transient( 'revisr_error' );

		if ( $error ) {
			$alert = '<div class="revisr-alert error">' . wpautop( $error ) . '</div>';
		} else if ( $alert ) {
			$alert = '<div class="revisr-alert updated">' . wpautop( $alert ) . '</div>';
		} else {
			if ( revisr()->git->count_untracked() == '0' ) {
				$msg 	= sprintf( __( 'There are currently no untracked files on branch %s.', 'revisr' ), revisr()->git->branch );
				$alert 	= '<div class="revisr-alert updated"><p>' . $msg . '</p></div>';
			} else {
				$link 	= get_admin_url() . 'admin.php?page=revisr_new_commit';
				$msg 	= sprintf( __( 'There are currently %d untracked files on branch %s. <a href="%s">Commit</a> your changes to save them.', 'revisr' ), revisr()->git->count_untracked(), revisr()->git->branch, $link );
				$alert 	= '<div class="revisr-alert updated"><p>' . $msg . '</p></div>';
			}
		}

		if ( $errors_only && false !== $error ) {
			echo $alert;
		} else if ( ! $errors_only ) {
			echo $alert;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			exit();
		}

	}

	/**
	 * Updates user settings to be compatible with 1.8.
	 * @access public
	 */
	public function do_upgrade() {
		global $wpdb;

		// For users upgrading from 1.7 and older.
		if ( get_option( 'revisr_db_version' ) === '1.0' ) {

			// Check for the "auto_push" option and save it to the config.
			if ( isset( revisr()->options['auto_push'] ) ) {
				revisr()->git->set_config( 'revisr', 'auto-push', 'true' );
			}

			// Check for the "auto_pull" option and save it to the config.
			if ( isset( revisr()->options['auto_pull'] ) ) {
				revisr()->git->set_config( 'revisr', 'auto-pull', 'true' );
			}

			// Check for the "reset_db" option and save it to the config.
			if ( isset( revisr()->options['reset_db'] ) ) {
				revisr()->git->set_config( 'revisr', 'import-checkouts', 'true' );
			}

			// Check for the "mysql_path" option and save it to the config.
			if ( isset( revisr()->options['mysql_path'] ) ) {
				revisr()->git->set_config( 'revisr', 'mysql-path', revisr()->options['mysql_path'] );
			}

			// Configure the database tracking to use all tables, as this was how it behaved in 1.7.
			revisr()->git->set_config( 'revisr', 'db_tracking', 'all_tables' );
		}

		// Upgrades from the "revisr_commits" custom post type to pure Git.
		$table 		= Revisr::get_table_name();
		$commits 	= $wpdb->get_results( "SELECT * FROM $table WHERE event = 'commit'", ARRAY_A );

		if ( is_array( $commits ) && ! empty( $commits ) ) {

			foreach ( $commits as $commit ) {
				// Get the commit short hash from the message.
				$msg_array 	= explode( '#', $commit['message'] );
				$commit_id 	= substr( $msg_array[1], 0, 7 );

				// Prepare the new message.
				$new_msg 	= sprintf(
					__( 'Committed <a href="%s">#%s</a> to the local repository.', 'revisr' ),
					get_admin_url() . 'admin.php?page=revisr_view_commit&commit=' . $commit_id,
					$commit_id
				);

				// Update the existing message.
				$query = $wpdb->prepare(
					"UPDATE $table SET message = %s WHERE id = '%d'",
					$new_msg,
					$commit['id']
				);

				$wpdb->query( $query );
			}

		}

		// Update the database schema using dbDelta.
		Revisr::install();

	}

	/**
	 * Helper function for writing to the wp-config.php file,
	 * taken from WP Super Cache.
	 *
	 * @access public
	 * @return boolean
	 */
	public static function replace_config_line( $old, $new, $file = '' ) {

		if ( $file === '' ) {
			if ( file_exists( ABSPATH . 'wp-config.php') ) {
				$file = ABSPATH . 'wp-config.php';
			} else {
				$file = dirname(ABSPATH) . '/wp-config.php';
			}
		}

		if ( @is_file( $file ) == false ) {
			return false;
		}
		if (!is_writeable( $file ) ) {
			return false;
		}

		$found = false;
		$lines = file($file);
		foreach( (array)$lines as $line ) {
		 	if ( preg_match("/$old/", $line)) {
				$found = true;
				break;
			}
		}
		if ($found) {
			$fd = fopen($file, 'w');
			foreach( (array)$lines as $line ) {
				if ( !preg_match("/$old/", $line))
					fputs($fd, $line);
				else {
					fputs($fd, "$new // Added by Revisr\n");
				}
			}
			fclose($fd);
			return true;
		}
		$fd = fopen($file, 'w');
		$done = false;
		foreach( (array)$lines as $line ) {
			if ( $done || !preg_match('/^(if\ \(\ \!\ )?define|\$|\?>/', $line) ) {
				fputs($fd, $line);
			} else {
				fputs($fd, "$new // Added by Revisr\n");
				fputs($fd, $line);
				$done = true;
			}
		}
		fclose($fd);
		return true;

	}

	/**
	 * Wrapper for wp_verify_nonce.
	 *
	 * @access public
	 */
	public static function verify_nonce( $nonce, $action ) {

		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			wp_die( __( 'Cheatin&#8217; uh?', 'revisr' ) );
		}

	}

	/**
	 * Helper function for processing redirects.
	 *
	 * @access public
	 * @param  string 	$url 	The URL to redirect to.
	 * @param  bool 	$echo 	Set to true if the redirect should be done in Javascript.
	 */
	public static function redirect( $url = '', $echo = false ) {

		if ( '' === $url ) {
			$url = get_admin_url() . 'admin.php?page=revisr';
		}

		if ( $echo || isset( $_REQUEST['echo_redirect'] ) ) {
			_e( 'Processing...', 'revisr' );
			?>
			<script>
				window.top.location.href = "<?php echo $url; ?>";
			</script>
			<?php
		}  else {
			wp_safe_redirect( $url );
			exit();
		}

	}

}
