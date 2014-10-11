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

class Revisr_Admin {
	
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
	 * The database class.
	 */
	protected $db;

	/**
	 * Construct any necessary properties/values.
	 * @access public
	 * @param array  	$options 		An array of user options and preferences.
	 * @param string 	$table_name 	The name of the database table to use.
	 */
	public function __construct( $options, $table_name ) {
		$this->options 		= $options;
		$this->table_name 	= $table_name;
		$this->git 			= new Revisr_Git();
		$this->db 			= new Revisr_DB();
	}

	/**
	 * Stores an alert to be rendered on the dashboard.
	 * @access public
	 * @param string  $mesage 	The message to display.
	 * @param bool    $is_error Whether the message is an error.
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
	 * Counts the number of commits in the database. on a given branch.
	 * @access public
	 * @param string $branch The name of the branch to count commits for.
	 */
	public static function count_commits( $branch ) {
		global $wpdb;
		if ($branch == "all") {
			$num_commits = $wpdb->get_results( "SELECT * FROM " . $wpdb->postmeta . " WHERE meta_key = 'branch'" );
		} else {
			$num_commits = $wpdb->get_results( "SELECT * FROM " . $wpdb->postmeta . " WHERE meta_key = 'branch' AND meta_value = '".$branch."'" );
		}
		return count( $num_commits );
	}	

	/**
	 * Logs an event to the database.
	 * @access public
	 * @param string $message The message to show in the Recent Activity. 
	 * @param string $event   Will be used for filtering later. 
	 */
	public static function log( $message, $event ) {
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
	public static function notify( $subject, $message ) {
		$options 	= get_option( 'revisr_settings' );
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
	 * Checks out an existing branch.
	 * @access public
	 * @param string $branch The name of the branch to checkout.
	 */
	public function process_checkout( $args = '', $new_branch = false ) {
		if ( isset( $this->options['reset_db'] ) ) {
			$this->db->backup();
		}

		if ( $args == '' ) {
			$branch = escapeshellarg( $_REQUEST['branch'] );
		} else {
			$branch = $args;
		}
		
		$this->git->reset();
		$this->git->checkout( $branch );
		if ( isset( $this->options['reset_db'] ) && $new_branch === false ) {
			$this->db->restore( true );
		}
		$url = get_admin_url() . 'admin.php?page=revisr';
		wp_redirect( $url );
	}

	/**
	 * Processes a new commit from the "New Commit" screen in the admin.
	 * @access public
	 */
	public function process_commit() {
		if ( isset( $_REQUEST['_wpnonce'] ) && isset( $_REQUEST['_wp_http_referer'] ) ) {
			$commit_msg 	= $_REQUEST['post_title'];
			$post_new 		= get_admin_url() . 'post-new.php?post_type=revisr_commits';
			//Require a message to be entered for the commit.
			if ( $commit_msg == 'Auto Draft' || $commit_msg == '' ) {
				$url = $post_new . '&message=42';
				wp_redirect( $url );
				exit();
			}
			//Stage any necessary files.
			if ( isset( $_POST['staged_files'] ) ) {
				$this->git->stage_files( $_POST['staged_files'] );
				$staged_files = $_POST['staged_files'];
			} else {
				$url = $post_new . '&message=43';
				wp_redirect( $url );
				exit();
			}

			add_post_meta( get_the_ID(), 'committed_files', $staged_files );
			add_post_meta( get_the_ID(), 'files_changed', count( $staged_files ) );
			$this->git->commit( $commit_msg, 'commit' );	
		}
	}

	/**
	 * Processes the creation of a new branch.
	 * @access public
	 */
	public function process_create_branch() {
		$branch = $_REQUEST['branch_name'];
		$result = $this->git->create_branch( $branch );
		if ( isset( $_REQUEST['checkout_new_branch'] ) ) {
			$this->git->checkout( $branch );
		}
		if ( $result !== false ) {
			wp_redirect( get_admin_url() . 'admin.php?page=revisr_branches&status=create_success&branch=' . $branch );
		} else {
			wp_redirect( get_admin_url() . 'admin.php?page=revisr_branches&status=create_error&branch=' . $branch );
		}
	}

	/**
	 * Processes the deletion of an existing branch.
	 * @access public
	 */
	public function process_delete_branch() {
		if ( isset( $_POST['branch'] ) && $_POST['branch'] != $this->git->branch ) {
			$branch = $_POST['branch'];
			$this->git->delete_branch( $branch );
			if ( isset( $_POST['delete_remote_branch'] ) ) {
				$this->git->run( "push {$this->git->remote} --delete {$branch}" );
			}
		}
		exit();
	}

	/**
	 * Resets all uncommitted changes to the working directory.
	 * @access public
	 */
	public function process_discard() {
		$this->git->reset( '--hard', 'HEAD', true );
		Revisr_Admin::log( __('Discarded all uncommitted changes.', 'revisr'), 'discard' );
		Revisr_Admin::alert( __('Successfully discarded any uncommitted changes.', 'revisr') );
		exit();
	}

	/**
	 * Processes a merge.
	 * @access public
	 */
	public function process_merge() {
		$this->git->merge( $_REQUEST['branch'] );
	}

	/**
	 * Processes a pull.
	 * @access public
	 */
	public function process_pull() {
		//Determine whether this is a request from the dashboard or a POST request.
		$from_dash = check_ajax_referer( 'dashboard_nonce', 'security', false );
		if ( $from_dash == false ) {
			if ( ! isset( $this->options['auto_pull'] ) ) {
				wp_die( __( 'You are not authorized to perform this action.', 'revisr' ) );
			}
		}

		$this->git->reset();
		$this->git->fetch();

		$commits_since  = $this->git->run( "log {$this->git->branch}..{$this->git->remote}/{$this->git->branch} --pretty=oneline" );

		if ( is_array( $commits_since ) ) {
			//Iterate through the commits to pull and add them to the database.
			foreach ( $commits_since as $commit ) {
				$commit_hash = substr( $commit, 0, 7 );
				$commit_msg = substr( $commit, 40 );
				$show_files = $this->git->run( 'show --pretty="format:" --name-status ' . $commit_hash );
				
				if ( is_array( $show_files ) ) {
					$files_changed = array_filter( $show_files );			
					$post = array(
						'post_title'	=> $commit_msg,
						'post_content'	=> '',
						'post_type'		=> 'revisr_commits',
						'post_status'	=> 'publish',
						);
					$post_id = wp_insert_post( $post );

					add_post_meta( $post_id, 'commit_hash', $commit_hash );
					add_post_meta( $post_id, 'branch', $this->git->branch );
					add_post_meta( $post_id, 'files_changed', count( $files_changed ) );
					add_post_meta( $post_id, 'committed_files', $files_changed );

					$view_link = get_admin_url() . "post.php?post=$post_id&action=edit";
					$msg = sprintf( __( 'Pulled <a href="%s">#%s</a> from %s/%s.', 'revisr' ), $view_link, $commit_hash, $this->git->remote, $this->git->branch );
					Revisr_Admin::log( $msg, 'pull' );
				}
			}
		}
		//Pull the changes or return an error on failure.
		$this->git->pull();
	}

	/**
	 * Processes a push.
	 * @access public
	 */
	public function process_push(){
		$this->git->reset();
		$this->git->push();
	}

	/**
	 * Processes a revert to an earlier commit.
	 * @access public
	 */
	public function process_revert() {
	    if ( isset( $_GET['revert_nonce'] ) && wp_verify_nonce( $_GET['revert_nonce'], 'revert' ) ) {
			
			$branch 	= $_GET['branch'];
			$commit 	= $_GET['commit_hash'];			
			$commit_msg = sprintf( __( 'Reverted to commit: #%s.', 'revisr' ), $commit );

			if ( $branch != $this->git->branch ) {
				$this->git->checkout( $branch );
			}

			$this->git->reset( '--hard', 'HEAD', true );
			$this->git->reset( '--hard', $commit );
			$this->git->reset( '--soft', 'HEAD@{1}' );
			$this->git->run( 'add -A' );
			$this->git->commit( $commit_msg );
			$this->git->auto_push();
			
			$post_url = get_admin_url() . "post.php?post=" . $_GET['post_id'] . "&action=edit";

			$msg = sprintf( __( 'Reverted to commit <a href="%s">#%s</a>.', 'revisr' ), $post_url, $commit );
			$email_msg = sprintf( __( '%s was reverted to commit #%s', 'revisr' ), get_bloginfo(), $commit );
			Revisr_Admin::log( $msg, 'revert' );
			Revisr_Admin::notify( get_bloginfo() . __( ' - Commit Reverted', 'revisr' ), $email_msg );
			$redirect = get_admin_url() . "admin.php?page=revisr";
			wp_redirect( $redirect );
		}
		else {
			wp_die( __( 'You are not authorized to access this page.', 'revisr' ) );
		}
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
		$commit_hash	 = get_post_custom_values( 'commit_hash', $_POST['id'] );
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