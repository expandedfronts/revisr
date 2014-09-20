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

include_once 'class-revisr-admin.php';

class Revisr_Git
{
	/**
	 * The current branch of the local repository.
	 */
	public $branch;

	/**
	 * The top-level Git directory ( '.git/' ).
	 */
	public $dir;

	/**
	 * The short SHA1 hash of the current state of the repository.
	 */
	public $hash;

	/**
	 * User options and preferences.
	 */
	private $options;

	/**
	 * The name of the active remote.
	 */
	public $remote;

	/**
	 * Initiate the class properties.
	 * @access public
	 */
	public function __construct() {
		$this->dir 		= $this->current_dir();
		$this->options  = Revisr::get_options();
		$this->branch 	= $this->current_branch();
		$this->remote 	= $this->current_remote();
		$this->hash 	= $this->current_commit();
	}

	/**
	 * Pushes changes if "Automatically push new commits" is enabled.
	 * @access public
	 */
	public function auto_push() {
		if ( isset( $this->options['auto_push'] ) ) {
			$this->push();
		}
	}

	/**
	 * Lists available branches on the local repository.
	 * @access public
	 * @param string $args 		Any arguements to pass to the function.
	 * @param string $branch 	The branch to act on.
	 */
	public function branches() {
		$branches = $this->run( 'branch' );
		return $branches;
	}

	/**
	 * Checks out an existing branch.
	 * @access public
	 * @param string $branch The branch to checkout.
	 */
	public function checkout( $branch ) {
		$this->run( "checkout $branch", __FUNCTION__ );
	}

	/**
	 * Creates a new branch.
	 * @access public
	 * @param string $branch The name of the branch to create.
	 */
	public function create_branch( $branch ) {
		$new_branch = $this->run( "branch $branch" );
		return $new_branch;
	}

	/**
	 * Commits any staged files to the local repository.
	 * @access public
	 * @param string $message The message to use with the commit.
	 */
	public function commit( $message ) {
		$commit_message = escapeshellarg($message);
		$commit = $this->run( "commit -m$commit_message" );
		return $commit;
	}

	/**
	 * Gets or sets the user's email address stored in Git.
	 * @access public
	 * @param string $user_email If provided, will update the user's email.
	 */
	public function config_user_email( $user_email = '' ) {
		$email = $this->run( "config user.email $user_email" );
		return $email;
	}

	/**
	 * Gets or sets the username stored in Git.
	 * @access public
	 * @param string $username If provided, will update the username.
	 */
	public function config_user_name( $username = '' ) {
		$username = $this->run( "config user.name $username" );
		return $username;
	}	

	/**
	 * Returns the current branch.
	 * @access public
	 */
	public function current_branch() {
		$current_branch = $this->run( 'rev-parse --abbrev-ref HEAD' );
		return $current_branch[0];
	}

	/**
	 * Returns the hash of the current commit.
	 * @access public
	 */
	public function current_commit() {
		$commit_hash = $this->run( 'rev-parse --short HEAD' );
		return $commit_hash[0];
	}

	/**
	 * Returns the path to the top-level git directory.
	 * @access public
	 * @return string The path to the top-level Git directory.
	 */
	public function current_dir() {
		$dir = exec( 'git rev-parse --show-toplevel' );
		if ( $dir ) {
			return $dir;
		} else {
			return ABSPATH;
		}
	}

	/**
	 * Returns the name of the current remote.
	 * @access public
	 */
	public function current_remote() {
		if ( isset( $this->options['remote_name'] ) ) {
			return $this->options['remote_name'];
		} else {
			return 'origin';
		}
	}

	/**
	 * Deletes a branch.
	 * @access public
	 * @param string $branch The branch to delete.
	 */
	public function delete_branch( $branch, $delete_remote = false ) {
		$deletion = $this->run( "branch -D $branch", __FUNCTION__ );
		return $deletion;
	}

	/**
	 * Runs a Diff.
	 * @access public
	 * @param string $args What to diff.
	 */
	public function diff() {

	}

	/**
	 * Fetches changes without merging them.
	 * @access public
	 */
	public function fetch() {
		$fetch = $this->run( 'fetch' );
		return $fetch;
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

	/**
	 * Initializes a new repository.
	 * @access public
	 */
	public function init() {
		$init = $this->run( 'init .' );
		return $init;
	}

	/**
	 * Checks if a given branch name exists in the local repository.
	 * @access public
	 * @param string $branch The branch to check.
	 */
	public function is_branch( $branch ) {
		$branches = $this->branches();
		if ( in_array( $branch, $branches ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Merges a branch into the current branch.
	 * @access public
	 * @param string $branch The branch to merge in.
	 */
	public function merge( $branch ) {
		$this->reset();
		$merge = $this->run( "merge $branch --ff-only" );
		return $merge;
	}

	/**
	 * Pulls changes from the remote repository.
	 * @access public
	 */
	public function pull() {
		$this->reset();
		$pull = $this->run( "pull {$this->remote} {$this->branch}" );
		return $pull;
	}

	/**
	 * Pushes changes to the remote repository.
	 * @access public
	 */
	public function push() {
		$this->reset();
		$push = $this->run( "push {$this->remote} HEAD --quiet" );
		return $push;
	}

	/**
	 * Resets the working directory.
	 * @access public
	 * @param string 	$mode	The mode to use for the reset (hard, soft, etc.).
	 * @param string 	$path 	The path to apply the reset to.
	 * @param bool 		$clean 	Whether to remove any untracked files.
	 */
	public function reset( $mode = '--hard', $path = 'HEAD', $clean = false ) {
		$reset = $this->run( "reset $mode $path" );
		if ( $clean === true ) {
			$this->run( 'clean -f -d' );
		}
	}

	/**
	 * Reverts the working directory to a specified commit.
	 * @access public
	 * @param string $commit The hash of the commit to revert to.
	 */
	public function revert( $commit ) {
		$this->reset( '--hard', 'HEAD', true );
		$this->reset( '--hard', $commit );
		$this->reset( '--soft', 'HEAD@{1}' );
	}

	/**
	 * Executes a Git command.
	 * @access public
	 * @param string 	$args 		    The git command to execute.
	 * @param string 	$callback 	    The function to callback on response.
	 * @param bool 	 	$return_error 	Whether to return the error callback.
	 */
	public function run( $args, $callback = '', $return_error = true ) {
		$cmd = "git $args";
		$dir = getcwd();
		chdir( $this->dir );
		exec( $cmd, $output, $error );
		chdir( $dir );
		if ( $callback != '' ) {
			$response = new Revisr_Git_Callback;
			$success_callback = 'success_' . $callback;
			$failure_callback = 'null_' . $callback;
			if ( $error && $return_error == true ) {
				return $response->$failure_callback( $error );
			} else {
				return $response->$success_callback( $output );
			}
		}
		if ( ! $error ) {
			return $output;
		} else {
			return false;
		}
	}

	/**
	 * Stages the array of files passed through the New Commit screen.
	 * @access public
	 * @param array $staged_files The files to add/remove
	 */
	public function stage_files( $staged_files ) {
		foreach ( $staged_files as $result ) {
			$file = substr( $result, 3 );
			$status = Revisr_Git::get_status( substr( $result, 0, 2 ) );

			if ( $status == __( 'Deleted', 'revisr' ) ) {
				if ( $this->run( "rm {$file}" ) === false ) {
					$error = sprintf( __( 'There was an error removing "%s" from the repository.', 'revisr' ), $file );
					Revisr_Admin::log( $error, 'error' );
				}
			} else {
				if ( $this->run( "add {$file}" ) === false ) {
					$error = sprintf( __( 'There was an error adding "%s" to the repository.', 'revisr' ), $file );
					Revisr_Admin::log( $error, 'error' );
				}
			}
		}
	}	

	/**
	 * Returns the current status.
	 * @access public
	 * @param string $args Defaults to "--short".
	 */
	public function status( $args = '--short' ) {
		$status = $this->run( "status $args" );
		return $status;
	}

	/**
	 * Adds a tag to the repository.
	 * @access public
	 * @param string $tag 		The tag to add.
	 */
	public function tag( $tag = '' ) {
		$tag = $this->run( "tag $tag" );
		return $tag;
	}

	/**
	 * Returns the number of unpulled commits.
	 * @access public
	 */
	public function count_unpulled() {
		$this->fetch();
		$unpulled = $this->run( "log {$this->branch}..{$this->remote}/{$this->branch} --pretty=oneline", 'count_ajax_btn' );
		return count( $unpulled );
	}

	/**
	 * Returns the number of unpushed commits.
	 * @access public
	 */
	public function count_unpushed() {
		$unpushed = $this->run("log {$this->remote}/{$this->branch}..{$this->branch} --pretty=oneline", 'count_ajax_btn' );
		return count( $unpushed );
	}

	/**
	 * Returns the number of untracked/modified files.
	 * @access public
	 */
	public function count_untracked() {
		$untracked = $this->run( 'status --short' );
		return count( $untracked );
	}

	/**
	 * Pings a remote repository to verify that it exists and is reachable.
	 * @access public
	 * @param string $remote The remote to ping.
	 */
	public function verify_remote() {
		$ping = $this->run( "ls-remote " . $_REQUEST['remote'] . " HEAD", __FUNCTION__ );
		return $ping;
	}

	/**
	 * Returns the current version of Git.
	 * @access public
	 */
	public function version() {
		$version = $this->run( 'version' );
		return $version;
	}
}

/**
 * Processes Git responses and errors.
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 */
class Revisr_Git_Callback extends Revisr_Git
{

	/**
	 * Callback for a successful checkout.
	 * @access public
	 * @param array $output An array of output from the checkout.
	 */
	public function success_checkout( $output ) {
		$branch = $this->branch;
		$msg = sprintf( __( 'Checked out branch: %s.', 'revisr' ), $branch );
		$email_msg = sprintf( __( '%s was switched to branch %s.', 'revisr' ), get_bloginfo(), $branch );
		Revisr_Admin::alert( $msg );
		Revisr_Admin::log( $msg, "branch" );
		Revisr_Admin::notify(get_bloginfo() . __( ' - Branch Changed', 'revisr'), $email_msg );
	}

	/**
	 * Callback for a failed checkout.
	 * @access public
	 * @param int $error The error code that was thrown.
	 */
	public function null_checkout( $error ) {
		$msg = __( 'There was an error checking out the branch. Check your configuration and try again.', 'revisr' );
		Revisr_Admin::alert( $msg, true );
		Revisr_Admin::log( $msg, 'error' );
	}

	/**
	 * Callback for a successful commit.
	 * @access public
	 * @param array $output An array of output from the commit.
	 */
	public function success_commit( $output ) {
		$commit_hash = $this->current_commit();
		$msg = sprintf( __( 'Successfully commmitted %s to the local repository.', 'revisr' ) );
		Revisr_Admin::log( $msg, 'commit' );
	}

	/**
	 * Callback for a failed commit.
	 * @access public
	 * @param int $error The error that was thrown.
	 */
	public function null_commit( $error ) {
		$msg = __( 'There was an error committing the changes to the local repository.', 'revisr' );
		Revisr_Admin::log( $msg, 'error' );
	}

	/**
	 * Callback for successful branch deletion.
	 * @access public
	 * @param array $output An array of output from the deletion.
	 */
	public function success_delete_branch( $output ) {
		$branch = $_POST['branch'];
		$msg = sprintf( __( 'Deleted branch %s.', 'revisr'), $branch );
		Revisr_Admin::log( $msg, 'branch' );
		Revisr_Admin::notify( get_bloginfo() . __( 'Branch Deleted', 'revisr' ), $msg );
		echo "<script>
				window.top.location.href = '" . get_admin_url() . "admin.php?page=revisr_branches&status=delete_success&branch={$branch}'
		</script>";
	}

	/**
	 * Callback for a failed branch deletion.
	 * @access public
	 * @param int $error The error code thrown during deletion.
	 */
	public function null_delete_branch( $error ) {
		echo "<script>
				window.top.location.href = '" . get_admin_url() . "admin.php?page=revisr_branches&status=delete_fail'
		</script>";
	}

	/**
	 * Renders the number of unpushed/unpulled commits for the AJAX buttons.
	 * @access public
	 * @param int $output The number of unpushed/unpulled commits.
	 */
	public function success_count_ajax_btn( $output ) {
		if ( count( $output ) != 0 ) {
			echo '(' . count( $output ) . ')';
		}
		exit();
	}

	/**
	 * Returns nothing if there are no commits to push/pull.
	 * @access public
	 * @param int $error The error code received (if any).
	 */
	public function null_count_ajax_button( $error ) {
		return;
	}

	/**
	 * Returns "Success!" if the connection to remote was successful.
	 * @acccess public
	 * @param array $output An array of output from the Git command.
	 */
	public function success_verify_remote( $output ) {
		echo 'Success!';
		exit();
	}

	/**
	 * Returns if the connection to the remote was unsuccessful.
	 * @access public
	 * @param  int $errror The error code that was thrown.
	 */
	public function null_verify_remote( $error ) {
		echo "Remote not found...";
		exit();
	} 
}

