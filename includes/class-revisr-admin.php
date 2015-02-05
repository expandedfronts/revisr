<?php
/**
 * class-revisr-admin.php
 *
 * Handles admin-specific functionality.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 */

// Disallow direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr_Admin {

	/**
	 * A reference back to the main Revisr instance.
	 * @var object
	 */
	protected $revisr;

	/**
	 * User options and preferences.
	 * @var array
	 */
	protected $options;

	/**
	 * Initialize the class.
	 * @access public
	 */
	public function __construct() {
		$this->revisr 	= Revisr::get_instance();
		$this->options 	= Revisr::get_options();
	}

	/**
	 * Stores an alert to be rendered on the dashboard.
	 * @access public
	 * @param  string  $message 	The message to display.
	 * @param  bool    $is_error Whether the message is an error.
	 */
	public static function alert( $message, $is_error = false ) {
		if ( $is_error == true ) {
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
			echo $this->revisr->git->count_unpulled();
		} else {
			echo $this->revisr->git->count_unpushed();
		}
		exit();
	}

	/**
	 * Deletes existing transients.
	 * @access public
	 */
	public static function clear_transients( $errors = true ) {
		if ( $errors == true ) {
			delete_transient( 'revisr_error' );
		} else {
			delete_transient( 'revisr_alert' );
		}
	}

	/**
	 * Counts the number of commits in the database on a given branch.
	 * @access public
	 * @param  string $branch The name of the branch to count commits for.
	 */
	public static function count_commits( $branch ) {
		global $wpdb;
		if ( $branch == 'all' ) {

			$num_commits = $wpdb->get_results( "SELECT meta_id FROM $wpdb->postmeta WHERE meta_key = 'branch'" );
		} else {
			$num_commits = $wpdb->get_results( $wpdb->prepare( "SELECT meta_id FROM $wpdb->postmeta WHERE meta_key = 'branch' AND meta_value = %s", $branch ) );
		}
		return count( $num_commits );
	}

	/**
	 * Gets an array of details on a saved commit.
	 * @access public
	 * @param  string $id The WordPress Post ID associated with the commit.
	 * @return array
	 */
	public static function get_commit_details( $id ) {

		// Grab the values from the post meta.
		$branch 			= get_post_meta( $id, 'branch', true );
		$hash 				= get_post_meta( $id, 'commit_hash', true );
		$db_hash 			= get_post_meta( $id, 'db_hash', true );
		$db_backup_method	= get_post_meta( $id, 'backup_method', true );
		$files_changed 		= get_post_meta( $id, 'files_changed', true );
		$committed_files 	= get_post_meta( $id, 'committed_files' );
		$git_tag 			= get_post_meta( $id, 'git_tag', true );

		// Store the values in an array.
		$commit_details = array(
			'branch' 			=> $branch ? $branch : __( 'Unknown', 'revisr' ),
			'commit_hash' 		=> $hash ? $hash : __( 'Unknown', 'revisr' ),
			'db_hash' 			=> $db_hash ? $db_hash : '',
			'db_backup_method'	=> $db_backup_method ? $db_backup_method : '',
			'files_changed' 	=> $files_changed ? $files_changed : 0,
			'committed_files' 	=> $committed_files ? $committed_files : array(),
			'tag'				=> $git_tag ? $git_tag : ''
		);

		// Return the array.
		return $commit_details;
	}

	/**
	 * Logs an event to the database.
	 * @access public
	 * @param  string $message The message to show in the Recent Activity. 
	 * @param  string $event   Will be used for filtering later. 
	 */
	public static function log( $message, $event ) {
		global $wpdb;
		$time  = current_time( 'mysql' );
		$table = $wpdb->prefix . 'revisr';
		$wpdb->insert(
			"$table",
			array( 
				'time' 		=> $time,
				'message'	=> $message,
				'event' 	=> $event,
			),
			array(
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
	 */
	public function render_alert() {
		$alert = get_transient( 'revisr_alert' );
		$error = get_transient( 'revisr_error' );
		if ( $error ) {
			echo "<div class='revisr-alert error'>" . wpautop( $error ) . "</div>";
		} else if ( $alert ) {
			echo "<div class='revisr-alert updated'>" . wpautop( $alert ) . "</div>";
		} else {
			if ( $this->revisr->git->count_untracked() == '0' ) {
				printf( __( '<div class="revisr-alert updated"><p>There are currently no untracked files on branch %s.', 'revisr' ), $this->revisr->git->branch );
			} else {
				$commit_link = get_admin_url() . 'post-new.php?post_type=revisr_commits';
				printf( __('<div class="revisr-alert updated"><p>There are currently %s untracked files on branch %s. <a href="%s">Commit</a> your changes to save them.</p></div>', 'revisr' ), $this->revisr->git->count_untracked(), $this->revisr->git->branch, $commit_link );
			}
		}
		exit();
	}

	/**
	 * Processes a diff request.
	 * @access public
	 */
	public function view_diff() {
		?>
		<html>
		<head>
			<title><?php _e( 'View Diff', 'revisr' ); ?></title>
		</head>
		<body>
		<?php

			if ( isset( $_REQUEST['commit'] ) ) {
				$diff = $this->revisr->git->run( 'show', array( $_REQUEST['commit'], $_REQUEST['file'] ) );
			} else {
				$diff = $this->revisr->git->run( 'diff', array( $_REQUEST['file'] ) );
			}

			if ( is_array( $diff ) ) {

				// Loop through the diff and echo the output.
				foreach ( $diff as $line ) {
					if ( substr( $line, 0, 1 ) === '+' ) {
						echo '<span class="diff_added" style="background-color:#cfc;">' . htmlspecialchars( $line ) . '</span><br>';
					} else if ( substr( $line, 0, 1 ) === '-' ) {
						echo '<span class="diff_removed" style="background-color:#fdd;">' . htmlspecialchars( $line ) . '</span><br>';
					} else {
						echo htmlspecialchars( $line ) . '<br>';
					}	
				}

			} else {
				_e( 'Oops! Revisr ran into an error rendering the diff.', 'revisr' );
			}
		?>
		</body>
		</html>
		<?php
		exit();
	}
}
