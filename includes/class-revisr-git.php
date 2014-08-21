<?php
/**
 * class-revisr-git.php
 *
 * Processes Git functions.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 */

class Revisr_Git
{
	/**
	 * The current branch to push/pull.
	 */
	public $branch;

	/**
	 * The current directory.
	 */
	public $dir;

	/**
	 * The name of the remote to push/pull.
	 */
	public $remote;

	/**
	 * User options.
	 */
	public $options;

	/**
	 * Declare properties.
	 * @access public
	 */
	public function __construct() {
		
		$this->branch 	= Revisr_Git::current_branch();
		$this->dir 		= getcwd();
		$this->options  = get_option( 'revisr_settings' );

		if ( isset( $this->options['remote_name']) && $this->options['remote_name'] != '' ) {
			$this->remote = $this->options['remote_name'];
		} else {
			$this->remote = 'origin';
		}
	}

	/**
	 * Executes a Git command.
	 * @access public
	 * @param string $args 		   The git command to execute.
	 * @param bool 	 $return_error Whether to return the exit code.
	 * @return Returns an array of output if successful, or false on failure.
	 */
	public static function run( $args, $return_error = false ) {
		$dir = getcwd();
		$cmd = "git $args";
		chdir( ABSPATH );
		exec( $cmd, $output, $return );
		chdir( $dir );
		if ( ! $return ) {
			return $output;
		}
		else if ( $return_error == true ){
			return $return;
		}
		else {
			return false;
		}
	}	

	/**
	 * Processes a new commit.
	 * @access public
	 */
	public function commit() {

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
			Revisr_Git::run( "commit -m $commit_msg" );
			
			//Variables to store in meta.
			$commit_hash = Revisr_Git::run( "log --pretty=format:'%h' -n 1" );
			$clean_hash = trim($commit_hash[0], "'");
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
				$db_hash = Revisr_Git::run( "log --pretty=format:'%h' -n 1" );
				add_post_meta( get_the_ID(), 'db_hash', $db_hash[0] );
			}

			//Notify the admin.
			$email_msg = sprintf( __( 'A new commit was made to the repository: <br> #%s - %s', 'revisr' ), $clean_hash, $title );
			Revisr_Admin::notify( get_bloginfo() . __( ' - New Commit', 'revisr' ), $email_msg );
			return $clean_hash;		
		}	
	}

	/**
	 * Stages the array of files passed through the New Commit screen.
	 * @access private
	 * @param array $staged_files The files to add/remove
	 */
	private function stage_files( $staged_files ) {
		foreach ( $staged_files as $result ) {
			$file = substr( $result, 3 );
			$status = Revisr_Git::get_status( substr( $result, 0, 2 ) );

			if ( $status == __( 'Deleted', 'revisr' ) ) {
				if ( Revisr_Git::run( "rm {$file}" ) === false ) {
					$error = sprintf( __( 'There was an error removing "%s" from the repository.', 'revisr' ), $file );
					Revisr_Admin::log( $error, 'error' );
				}
			} else {
				if ( Revisr_Git::run( "add {$file}" ) === false ) {
					$error = sprintf( __( 'There was an error adding "%s" to the repository.', 'revisr' ), $file );
					Revisr_Admin::log( $error, 'error' );
				}
			}
		}
	}

	/**
	* Checks out a new or existing branch.
	* @access public
	*/
	public function checkout( $args ) {
		if ( isset( $this->options['reset_db'] ) ) {
			$db = new Revisr_DB();
			$db->backup();
		}

		if ( $args == '' ) {
			$branch = escapeshellarg( $_REQUEST['branch'] );
		}
		else {
			$branch = $args;
		}
		
		//Checkout the new or existing branch.
		Revisr_Git::run( 'reset --hard HEAD' );
		if ( isset( $_REQUEST['new_branch'] ) && $_REQUEST['new_branch'] == 'true' ) {
			Revisr_Git::run("checkout -b {$_REQUEST['branch']}");
			Revisr_Admin::log("Checked out new branch: {$_REQUEST['branch']}.", "branch");
			Revisr_Admin::notify(get_bloginfo() . " - Branch Changed", get_bloginfo() . " was switched to the new branch {$branch}.");
			echo "<script>
					window.top.location.href = '" . get_admin_url() . "admin.php?page=revisr&checkout=success&branch={$_REQUEST['branch']}'
				</script>";
			exit();
		} else {
			Revisr_Git::run("checkout {$branch}");
			if ( isset( $this->options['reset_db'] ) ) {
				$db->restore( true );
			}
			Revisr_Admin::log( "Checked out branch: {$_REQUEST['branch']}.", "branch" );
			Revisr_Admin::notify(get_bloginfo() . " - Branch Changed", get_bloginfo() . " was switched to branch {$branch}.");
			$url = get_admin_url() . "admin.php?page=revisr&branch={$branch}&checkout=success";
			wp_redirect( $url );
		}
	}	

	/**
	 * Discards the changes to the current working directory.
	 * @access public
	 */
	public function discard() {
		check_ajax_referer( 'dashboard_nonce', 'security' );
		Revisr_Git::run( 'reset --hard HEAD' );
		Revisr_Git::run( 'clean -f -d' );
		Revisr_Admin::log( __( 'Discarded all changes to the working directory.', 'revisr' ), 'discard' );
		Revisr_Admin::notify( get_bloginfo() . __(' - Changes Discarded', 'revisr' ), __( 'All uncommitted changes were discarded on ', 'revisr' ) . get_bloginfo() . '.' );
		echo '<p>' . __( 'Successfully discarded any uncommitted changes.', 'revisr' ) . '</p>';
		exit();
	}

	/**
	 * Push changes to a remote repository.
	 * @access public
	 * @param boolean $is_auto_push True if coming from an autopush.
	 */
	public function push( $is_auto_push = false ) {
		Revisr_Git::run( 'reset --hard HEAD' );
		$num_commits = $this->count_unpushed();
		$push = Revisr_Git::run( "push {$this->remote} HEAD --quiet" );
		
		if  ( $push === false ) {
			Revisr_Admin::log( __( 'Error pushing changes to the remote repository.', 'revisr' ), "error" );
			$result = "<p>" . __( 'There was an error while pushing to the remote repository. The remote may be ahead of this repository or you are not authenticated.', 'revisr' ) . "</p>";
		} else {
			$msg = sprintf( _n( 'Pushed %s commit to %s/%s.', 'Pushed %s commits to %s/%s.', $num_commits, 'revisr' ), $num_commits, $this->remote, $this->branch );
			Revisr_Admin::log( $msg, 'push' );
			$email_msg = sprintf( __( 'Changes were pushed to the remote repository for %s', 'revisr' ), get_bloginfo() ); 
			Revisr_Admin::notify( get_bloginfo() . __( ' - Changes Pushed', 'revisr' ), $email_msg );
			$result = sprintf( __( '<p>Successfully pushed to <strong>%s/%s.</p>', 'revisr' ), $this->remote, $this->branch );
		}

		if ( $is_auto_push != true ) {
				echo $result;
				exit();	
		}	
	}

	/**
	 * Pull changes from a remote repository.
	 * @access public
	 */
	public function pull() {

		//Determine whether this is a request from the dashboard or a POST request.
		$from_dash = check_ajax_referer( 'dashboard_nonce', 'security', false );
		if ( $from_dash == false ) {
			if ( ! isset( $this->options['auto_pull'] ) ) {
				wp_die( __( 'You are not authorized to perform this action.', 'revisr' ) );
			}
		}

		Revisr_Git::run( 'reset --hard HEAD' );

		//Calculate the commits to pull.
		Revisr_Git::run( 'fetch' );
		$commits_since  = Revisr_Git::run( "log {$this->branch}..{$this->remote}/{$this->branch} --pretty=oneline" );

		if ( is_array( $commits_since ) ) {
			//Iterate through the commits to pull and add them to the database.
			foreach ( $commits_since as $commit ) {
				$commit_hash = substr( $commit, 0, 7 );
				$commit_msg = substr( $commit, 40 );
				$show_files = Revisr_Git::run( 'show --pretty="format:" --name-status ' . $commit_hash );
				
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
					add_post_meta( $post_id, 'branch', $this->branch );
					add_post_meta( $post_id, 'files_changed', count( $files_changed ) );
					add_post_meta( $post_id, 'committed_files', $files_changed );

					$view_link = get_admin_url() . "post.php?post=$post_id}&action=edit";
					$msg = sprintf( __( 'Pulled <a href="%s">#%s</a> from %s/%s.', 'revisr' ), $view_link, $commit_hash, $this->remote, $this->branch );
					Revisr_Admin::log( $msg, 'pull' );
				}
			}
		}
		
		//Pull the changes or return an error on failure.
		if ( Revisr_Git::run( "pull {$this->remote} {$this->branch}" ) === false ) {
			$error_msg = __( 'Error pulling changes from the remote repository.', 'revisr' );
			Revisr_Admin::log( $error_msg, 'error' );
			$msg = __( 'There was an error pulling from the remote repository. The local repository could be ahead of the remote, or the remote settings may be incorrect.', 'revisr' );
		} else {
			Revisr_Admin::notify(get_bloginfo() . __( ' - Changes Pulled', 'revisr' ), __( 'Changes were pulled from the remote repository for ', 'revisr' ) . get_bloginfo());
			$msg = sprintf( __( 'Successfully pulled any changes from <strong>%s/%s</strong>', 'revisr' ), $this->remote, $this->branch );
		}

		if ( $from_dash == true ) {
			echo '<p>' . $msg . '</p>';
			exit();
		}
	}

	/**
	 * Shows a list of the pending files on the current branch. Clicking a modified file shows the diff.
	 * @access public
	 */
	public function pending_files() {
		check_ajax_referer('pending_nonce', 'security');
		$output = Revisr_Git::run("status --short");
		$total_pending = count($output);
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
	 * Shows the files that were added in the given commit.
	 * @access public
	 */
	public function committed_files() {
		check_ajax_referer('committed_nonce', 'security');
		if (get_post_type($_POST['id']) != "revisr_commits") {
			exit();
		}
		$commit = Revisr_Git::get_hash($_POST['id']);
		$files = get_post_custom_values( 'committed_files', $_POST['id'] );

		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
			    $output = unserialize( $file );
			}
		} else {
			$output = 0;
		}

		printf( __('<br><strong>%s</strong> files were included in this commit.<br><br>', 'revisr' ), count( $output ) );
		

		if (isset($_POST['pagenum'])) {
			$current_page = $_POST['pagenum'];
		}
		else {
			$current_page = 1;
		}
		
		$num_rows = count( $output );
		$rows_per_page = 20;
		$last_page = ceil( $num_rows/$rows_per_page );

		if ( $current_page < 1){
		    $current_page = 1;
		}
		if ( $current_page > $last_page){
		    $current_page = $last_page;
		}
		
		$offset = $rows_per_page * ($current_page - 1);

		$results = array_slice($output, $offset, $rows_per_page);
		?>
		<table class="widefat">
			<thead>
			    <tr>
			        <th><?php _e( 'File', 'revisr'); ?></th>
			        <th><?php _e( 'Status', 'revisr'); ?></th>
			    </tr>
			</thead>
			<tbody>
			<?php
				//Clean up output from git status and echo the results.
				foreach ($results as $result) {
					$short_status = substr($result, 0, 3);
					$file = substr($result, 2);
					$status = Revisr_Git::get_status($short_status);
					if ($status != "Untracked" && $status != "Deleted") {
						echo "<tr><td><a href='" . get_admin_url() . "admin-post.php?action=view_diff&file={$file}&commit={$commit}&TB_iframe=true&width=600&height=550' title='View Diff' class='thickbox'>{$file}</a></td><td>{$status}</td></td>";
					}
					else {
						echo "<tr><td>$file</td><td>$status</td></td>";
					}					
				}
			?>
			</tbody>
		</table>
		<?php
			if ( $current_page != "1" ){
				echo '<a href="#" onclick="prev();return false;">' . __( '<- Previous', 'revisr' ) .  '</a>';
			}
			printf( __( 'Page %s of %s', 'revisr' ), $current_page, $last_page ); 
			if ( $current_page != $last_page ){
				echo '<a href="#" onclick="next();return false;">' . __( 'Next ->', 'revisr' ) . '</a>';
			}
			exit();
	}

	/**
	 * Displays the diff for a modified file.
	 * @access public
	 */
	public function view_diff() {
		?>
		<html>
		<head><title><?php _e( 'View Diff', 'revisr' ); ?></title>
		</head>
		<body>
		<?php
			if ( isset( $_REQUEST['commit'] ) && $_REQUEST['commit'] != "" ) {
				$diff = Revisr_Git::run("show {$_REQUEST['commit']} {$_REQUEST['file']}");
			}
			else {
				$diff = Revisr_Git::run("diff {$_REQUEST['file']}");
			}

			if ( is_array( $diff ) ) {
				foreach ( $diff as $line ) {
					if (substr( $line, 0, 1 ) === "+") {
						echo "<span class='diff_added' style='background-color:#cfc;'>" . htmlspecialchars($line) . "</span><br>";
					}
					else if (substr( $line, 0, 1 ) === "-") {
						echo "<span class='diff_removed' style='background-color:#fdd;'>" . htmlspecialchars($line) . "</span><br>";
					}
					else {
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
	 * Pushes to the remote if auto push is enabled.
	 * @access public
	 */
	public function auto_push() {
		if ( isset( $this->options['auto_push'] ) ) {
			$this->push( true );
		}
	}

	/**
	 * Returns the current branch of the Git repository.
	 * @access public
	 */
	public static function current_branch() {
		$output = Revisr_Git::run( 'rev-parse --abbrev-ref HEAD' );
		if ( $output != false ) {
			return $output[0];
		} else {
			return 'Unset';
		}
	}

	/**
	 * Returns the hash/id of the current commit.
	 * @access public
	 */
	public static function current_commit() {
		$branch = Revisr_Git::current_branch();
		$hash = Revisr_Git::run("rev-parse {$branch} --pretty=oneline");
		return substr( $hash[0], 0, 7 );
	}

	/**
	 * Returns the number of untracked/pending files.
	 * @access public
	 */
	public static function count_pending() {
		$pending = Revisr_Git::run("status --short");
		return count($pending);
	}

	/**
	 * Returns the number of commits that haven't been pushed.
	 * @access public
	 */
	public function count_unpushed() {
		$unpushed = Revisr_Git::run("log {$this->remote}/{$this->branch}..{$this->branch} --pretty=oneline");
		
		if ( $unpushed !== false ) {
			$num_unpushed = count( $unpushed );
			if ( $num_unpushed !== 0 ) {
				if ( isset( $_REQUEST['should_exit'] ) && $_REQUEST['should_exit'] == 'true' ) {
					echo '(' . $num_unpushed . ')';
				}
				else {
					return $num_unpushed;
				}
			}
		}

		//Exit cleanly if being returned via AJAX.
		if ( isset( $_REQUEST['should_exit'] ) && $_REQUEST['should_exit'] == 'true' ) {
			exit();
		}
	}

	/**
	 * Returns the number of unpulled commits.
	 * @access public
	 */
	public function count_unpulled() {
		Revisr_Git::run( 'fetch' );
		$unpulled = Revisr_Git::run( "log {$this->branch}..{$this->remote}/{$this->branch} --pretty=oneline" );
		
		if ( $unpulled !== false ) {
			$num_unpulled = count( $unpulled );
			if ( $num_unpulled !== 0 ) {
				if ( isset( $_REQUEST['should_exit'] ) && $_REQUEST['should_exit'] == 'true' ) {
					echo '(' . $num_unpulled . ')';
				} else {
					return $num_unpulled;
				}
			}
		}

		//Exit cleanly if being returned via AJAX.
		if ( isset( $_REQUEST['should_exit'] ) && $_REQUEST['should_exit'] == 'true' ) {
			exit();
		}
	}

	/**
	 * Returns the commit hash for a specific commit.
	 * @access public
	 * @param int $post_id The ID of the associated post.
	 */
	public static function get_hash( $post_id ) {
		$commit_meta = maybe_unserialize( get_post_meta( $post_id, "commit_hash" ) );
					
		if ( isset( $commit_meta[0] ) ) {
			if ( ! is_array( $commit_meta[0] ) && strlen( $commit_meta[0] ) == "1" ) {
				$commit_hash = $commit_meta;
			}
			else {
				$commit_hash = $commit_meta[0];
			}
		}

		if ( empty( $commit_hash ) ) {
			return __( 'Unknown', 'revisr' );
		} else {
			if ( is_array( $commit_hash ) ) {
				return $commit_hash[0];
			} else {
				return $commit_hash;
			}
		}
	}

	/**
	 * Returns the status of a file.
	 * @access public
	 * @param string $status The status code returned via 'git status --short'
	 */
	public static function get_status( $status ) {
		if ( strpos( $status, 'M' ) !== false ){
			$status = __( 'Modified', 'revisr' );
		} elseif ( strpos( $status, 'D' ) !== false ){
			$status = __( 'Deleted', 'revisr' );
		} elseif ( strpos( $status, 'A' ) !== false ){
			$status = __( 'Added', 'revisr' );
		} elseif ( strpos( $status, 'R' ) !== false ){
			$status = __( 'Renamed', 'revisr' );
		} elseif ( strpos( $status, 'U' ) !== false ){
			$status = __( 'Updated', 'revisr' );
		} elseif ( strpos( $status, 'C' ) !== false ){
			$status = __( 'Copied', 'revisr' );
		} elseif ( strpos( $status, '??' ) !== false ){
			$status = __( 'Untracked', 'revisr' );
		} else {
			$status = false;
		}
		return $status;
	}
}