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
	 * The main database class.
	 * @var Revisr_DB()
	 */
	protected $db;

	/**
	 * The main Git class.
	 * @var Revisr_Git()
	 */
	protected $git;

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
		$this->db 		= new Revisr_DB();
		$this->git 		= new Revisr_Git();
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
			echo $this->git->count_unpulled();
		} else {
			echo $this->git->count_unpushed();
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
			$num_commits = $wpdb->get_results( "SELECT * FROM " . $wpdb->postmeta . " WHERE meta_key = 'branch'" );
		} else {
			$num_commits = $wpdb->get_results( "SELECT * FROM " . $wpdb->postmeta . " WHERE meta_key = 'branch' AND meta_value = '".$branch."'" );
		}
		return count( $num_commits );
	}

	/**
	 * Logs an event to the database.
	 * @access public
	 * @param  string $message The message to show in the Recent Activity. 
	 * @param  string $event   Will be used for filtering later. 
	 */
	public static function log( $message, $event ) {
		global $wpdb;
		$time  = current_time( 'mysql', 1 );
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
			if ( $this->git->count_untracked() == '0' ) {
				printf( __( '<div class="revisr-alert updated"><p>There are currently no untracked files on branch %s.', 'revisr' ), $this->git->branch );
			} else {
				$commit_link = get_admin_url() . 'post-new.php?post_type=revisr_commits';
				printf( __('<div class="revisr-alert updated"><p>There are currently %s untracked files on branch %s. <a href="%s">Commit</a> your changes to save them.</p></div>', 'revisr' ), $this->git->count_untracked(), $this->git->branch, $commit_link );
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
		<head><title><?php _e( 'View Diff', 'revisr' ); ?></title>
		</head>
		<body>
		<?php
			if ( isset( $_REQUEST['commit'] ) ) {
				$diff = $this->git->run("show {$_REQUEST['commit']} {$_REQUEST['file']}");
			} else {
				$diff = $this->git->run("diff {$_REQUEST['file']}");
			}
			if ( is_array( $diff ) ) {
				foreach ( $diff as $line ) {
					if (substr( $line, 0, 1 ) === "+") {
						echo "<span class='diff_added' style='background-color:#cfc;'>" . htmlspecialchars($line) . "</span><br>";
					} else if (substr( $line, 0, 1 ) === "-") {
						echo "<span class='diff_removed' style='background-color:#fdd;'>" . htmlspecialchars($line) . "</span><br>";
					} else {
						echo htmlspecialchars($line) . "<br>";
					}	
				}			
			} else {
				_e( 'Failed to render the diff.', 'revisr' );
			}
		?>
		</body>
		</html>
		<?php
		exit();
	}

	/**
	 * Shows a list of the pending files on the current branch. Clicking a modified file shows the diff.
	 * @access public
	 */
	public function pending_files() {
		check_ajax_referer('pending_nonce', 'security');
		$output 		= $this->git->status();
		$total_pending 	= count( $output );
		$text 			= sprintf( __( 'There are <strong>%s</strong> untracked files that can be added to this commit on branch <strong>%s</strong>.', 'revisr' ), $total_pending, $this->git->branch );
		echo "<br>" . $text . "<br><br>";
		_e( 'Use the boxes below to select the files to include in this commit. Only files in the "Staged Files" section will be included.<br>Double-click files marked as "Modified" to view the changes to the file.<br><br>', 'revisr' );
		echo "<input id='backup_db_cb' type='checkbox' name='backup_db'><label for='backup_db_cb'>" . __( 'Backup database?', 'revisr' ) . "</label><br><br>";

		if ( is_array( $output ) ) {
				?>
				<!-- Staging -->
				<div class="stage-container">
					<p><strong><?php _e( 'Staged Files', 'revisr' ); ?></strong></p>
					<select id='staged' multiple="multiple" name="staged_files[]" size="6">
					<?php
					// Clean up output from git status and echo the results.
					foreach ( $output as $result ) {
						$short_status = substr( $result, 0, 3 );
						$file = substr( $result, 3 );
						$status = Revisr_Git::get_status( $short_status );
						echo "<option class='pending' value='{$result}'>{$file} [{$status}]</option>";
					}
					?>
					</select>
					<div class="stage-nav">
						<input id="unstage-file" type="button" class="button button-primary stage-nav-button" value="<?php _e( 'Unstage Selected', 'revisr' ); ?>" onclick="unstage_file()" />
						<br>
						<input id="unstage-all" type="button" class="button stage-nav-button" value="<?php _e( 'Unstage All', 'revisr' ); ?>" onclick="unstage_all()" />
					</div>
				</div><!-- /Staging -->		
				<br>
				<!-- Unstaging -->
				<div class="stage-container">
					<p><strong><?php _e( 'Unstaged Files', 'revisr' ); ?></strong></p>
					<select id="unstaged" multiple="multiple" size="6">
					</select>
					<div class="stage-nav">
						<input id="stage-file" type="button" class="button button-primary stage-nav-button" value="<?php _e( 'Stage Selected', 'revisr' ); ?>" onclick="stage_file()" />
						<br>
						<input id="stage-all" type="button" class="button stage-nav-button" value="<?php _e( 'Stage All', 'revisr' ); ?>" onclick="stage_all()" />
					</div>
				</div><!-- /Unstaging -->
			<?php	
		}	
		exit();
	}

	/**
	 * Shows the files that were added in a given commit.
	 * @access public
	 */
	public function committed_files() {
		if ( get_post_type( $_POST['id'] ) != 'revisr_commits' ) {
			exit();
		}
		check_ajax_referer( 'committed_nonce', 'security' );
		$committed_files = get_post_custom_values( 'committed_files', $_POST['id'] );
		$commit_hash	 = get_post_custom_values( 'commit_hash', $_POST['id'] );
		if ( is_array( $committed_files ) ) {
			foreach ( $committed_files as $file ) {
				$output = maybe_unserialize( $file );
			}
		}
		if ( isset( $output ) ) {
			printf( __('<br><strong>%s</strong> files were included in this commit. Double-click files marked as "Modified" to view the changes in a diff.', 'revisr' ), count( $output ) );
			echo "<input id='commit_hash' name='commit_hash' value='{$commit_hash[0]}' type='hidden' />";
			echo '<br><br><select id="committed" multiple="multiple" size="6">';
				foreach ( $output as $result ) {
					$short_status = substr( $result, 0, 3 );
					$file = substr($result, 2);
					$status = Revisr_Git::get_status( $short_status );
					printf( '<option class="committed" value="%s">%s [%s]</option>', $result, $file, $status );	
				}
			echo '</select>';
		} else {
			_e( 'No files were included in this commit.', 'revisr' );
		}
		exit();
	}	
}
