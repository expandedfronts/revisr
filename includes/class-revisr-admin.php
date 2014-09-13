<?php

/**
 * class-revisr-admin.php
 *
 * Processes WordPress hooks and common actions.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 */

class Revisr_Admin
{
	/**
	 * User options and preferences.
	 */
	protected $options;

	/**
	 * The database table to use for logging.
	 */
	protected $table_name;

	/**
	 * The top-level Git directory.
	 */
	protected $dir;

	/**
	 * The main Git class.
	 */
	protected $git;

	/**
	 * Construct any necessary properties/values.
	 * @access public
	 * @param array  	$options 		An array of user options and preferences.
	 * @param string 	$table_name 	The name of the database table to use.
	 * @param string 	$git_dir 		The name of the top-level Git directory.
	 */
	public function __construct( $options, $table_name ) {
		$this->options 		= $options;
		$this->table_name 	= $table_name;
		$this->git 			= new Revisr_Git();
	}

	/**
	 * Stores an alert to be rendered on the dashboard.
	 * @access public
	 * @param string  $mesage 	The message to display.
	 * @param string  $is_error Whether the message is an error.
	 */
	public static function alert( $message, $is_error = false ) {
		if ( $is_error == true ) {
			$class = 'error';
		} else {
			$class = 'updated';
		}
		$alert = array( 
			'class' 	=> $class, 
			'message' 	=> $message
		);
		set_transient( 'revisr_alert', $alert, 5 );
	}

	/**
	 * Logs an event to the database.
	 * @access private
	 * @param string $message The message to show in the Recent Activity. 
	 * @param string $event   Will be used for filtering later. 
	 */
	private function log( $message, $event ) {
		global $wpdb;
		$time = current_time( 'mysql', 1 );
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
	 * @param string $subject The subject line of the email.
	 * @param string $message The message for the email.
	 */
	private function notify( $subject, $message ) {
		$options = get_option( 'revisr_settings' );
		$url = get_admin_url() . 'admin.php?page=revisr';

		if ( isset( $options['notifications'] ) ) {
			$email = $options['email'];
			$message .= '<br><br>';
			$message .= sprintf( __( '<a href="%s">Click here</a> for more details.', 'revisr' ), $url );
			$headers = "Content-Type: text/html; charset=ISO-8859-1\r\n";
			wp_mail( $email, $subject, $message, $headers );
		}
	}

	/**
	 * Checks out an existing branch.
	 * @access public
	 * @param string $branch The name of the branch to checkout.
	 */
	public function process_checkout() {
		
	}

	/**
	 * Processes a new commit from the "New Commit" screen in the admin.
	 * @access public
	 */
	public function process_commit() {
		if ( isset( $_REQUEST['_wpnonce'] ) && isset( $_REQUEST['_wp_http_referer'] ) ) {
			
			$id 	  = get_the_ID();
			$title 	  = $_REQUEST['post_title'];
			$post_new = get_admin_url() . 'post-new.php?post_type=revisr_commits';

			//Require a message to be entered for the commit.
			if ( $title == 'Auto Draft' || $title == '' ) {
				$url = $post_new . '&message=42';
				wp_redirect( $url );
				exit();
			}

			//Stage any necessary files.
			if ( isset( $_POST['staged_files'] ) ) {
				$this->stage_files( $_POST['staged_files'] );
				$staged_files = $_POST['staged_files'];
			} else {
				if ( ! isset( $_REQUEST['backup_db'] ) ) {
					$url = $post_new . '&message=43';
					wp_redirect( $url );
					exit();
				}
				$staged_files = array();
			}

			$commit_msg = escapeshellarg( $title );
			$this->git->commit( $commit_msg );
			
			//Variables to store in meta.
			$commit_hash = $this->git->run( "log --pretty=format:'%h' -n 1" );
			$clean_hash = trim( $commit_hash[0], "'" );
			$view_link = get_admin_url() . "post.php?post={$id}&action=edit";
			
			//Add post meta
			add_post_meta( get_the_ID(), 'commit_hash', $clean_hash );
			add_post_meta( get_the_ID(), 'branch', $this->branch );
			add_post_meta( get_the_ID(), 'committed_files', $staged_files );
			add_post_meta( get_the_ID(), 'files_changed', count( $staged_files ) );

			//Log the commit
			$msg = sprintf( __( 'Committed <a href="%s">#%s</a> to the local repository.', 'revisr' ), $view_link, $clean_hash );
			Revisr_Admin::log( $msg, 'commit' );

			$this->auto_push();

			//Backup the database if necessary
			if ( isset( $_REQUEST['backup_db'] ) && $_REQUEST['backup_db'] == 'on' ) {
				$db = new Revisr_DB;
				$db->backup();
				$db_hash = $this->git->run( "log --pretty=format:'%h' -n 1" );
				add_post_meta( get_the_ID(), 'db_hash', $db_hash[0] );
			}

			//Notify the admin.
			$email_msg = sprintf( __( 'A new commit was made to the repository: <br> #%s - %s', 'revisr' ), $clean_hash, $title );
			Revisr_Admin::notify( get_bloginfo() . __( ' - New Commit', 'revisr' ), $email_msg );
			return $clean_hash;		
		}
	}

	/**
	 * Processes the creation of a new branch.
	 * @access public
	 * @param string 	$branch 	The name of the new branch.
	 * @param boolean 	$checkout 	Whether to checkout the new branch immediately.
	 */
	public function process_create_branch( $branch, $checkout = false ) {

	}

	/**
	 * Processes the deletion of an existing branch.
	 * @access public
	 * @param string 	$branch 		The name of the branch to delete.
	 * @param boolean 	$delete_remote 	Whether to delete the remote branch as well.
	 */
	public function process_delete_branch( $branch, $delete_remote = false ) {

	}

	/**
	 * Processes the creation of a new repository.
	 * @access public
	 */
	public function process_init() {

	}

	/**
	 * Displays the recent activity box on the dashboard.
	 * @access public
	 */
	public function recent_activity() {
		global $wpdb;
		$revisr_events = $wpdb->get_results( "SELECT id, time, message FROM $this->table_name ORDER BY id DESC LIMIT 15", ARRAY_A );

		if ( $revisr_events ) {
			?>
			<table class="widefat">
				<tbody id="activity_content">
				<?php
					foreach ($revisr_events as $revisr_event) {
						$timestamp = strtotime($revisr_event['time']);
						$time  	   = sprintf( __( '%s ago', 'revisr' ), human_time_diff( $timestamp ) );
						echo "<tr><td>{$revisr_event['message']}</td><td>{$time}</td></tr>";
					}
				?>
				</tbody>
			</table>
			<?php		
		} else {
			_e( '<p id="revisr_activity_no_results">Your recent activity will show up here.</p>', 'revisr' );
		}
		exit();
	}

	/**
	 * Renders an alert and removes the old data. 
	 * @access public
	 */
	public function render_alert() {
		$alert = get_transient('revisr_alert');
		if ( $alert ) {
			echo "<div id='revisr_alert' class='" . $alert['class'] . "'>" . wpautop($alert['message']) . "</div>";
			delete_transient('revisr_alert');
		} else {
			$untracked = $this->git->count_untracked();
			$branch = $this->git->branch;
			echo "<div id='revisr_alert' class='updated'><p>There are currently $untracked untracked files on branch $branch.</p></div>";
		}
		exit();
	}

	/**
	 * Shows a list of the pending files on the current branch. Clicking a modified file shows the diff.
	 * @access public
	 */
	public function pending_files() {
		check_ajax_referer('pending_nonce', 'security');
		$output = $this->git->status();
		$total_pending = count( $output );
		echo "<br>There are <strong>{$total_pending}</strong> untracked files that can be added to this commit on branch <strong>" . $this->branch . "</strong>.<br>
		Use the boxes below to add/remove files. Double-click modified files to view diffs.<br><br>";
		echo "<input id='backup_db_cb' type='checkbox' name='backup_db'><label for='backup_db_cb'>" . __( 'Backup database?', 'revisr' ) . "</label><br><br>";

		if ( is_array( $output ) ) {
				?>
				
				<!-- Staging -->
				<div class="stage-container">
					
					<p><strong><?php _e( 'Staged Files', 'revisr' ); ?></strong></p>
					
					<select id='staged' multiple="multiple" name="staged_files[]" size="6">
					<?php
					//Clean up output from git status and echo the results.
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
		$commit_hash = get_post_custom_values( 'commit_hash', $_POST['id'] );
		if ( is_array( $committed_files ) ) {
			foreach ( $committed_files as $file ) {
				$output = unserialize( $file );
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