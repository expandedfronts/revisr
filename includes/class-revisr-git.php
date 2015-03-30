<?php
/**
 * class-revisr-git.php
 *
 * Processes interactions with Git.
 *
 * @package 	Revisr
 * @license 	GPLv3
 * @link 		https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

// The main Git class.
class Revisr_Git {

	/**
	 * Stores the current branch in Git.
	 * @var string
	 */
	public $branch;

	/**
	 * Stores the current remote used by Git.
	 * @var string
	 */
	public $remote;

	/**
	 * Stores the ID of the current commit.
	 * @var string
	 */
	public $current_commit;

	/**
	 * Stores the current directory during runtime.
	 * @var string
	 */
	public $current_dir;

	/**
	 * Stores the top-level git directory.
	 * @var string
	 */
	public $git_dir;

	/**
	 * Stores the path to Git.
	 * @var string
	 */
	public $git_path;

	/**
	 * Stores an array of user options/preferences.
	 * @var array
	 */
	public $options;

	/**
	 * Stores the state of the repository
	 * @var boolean
	 */
	public $is_repo;

	/**
	 * Initiates the class and it's properties.
	 * @access public
	 */
	public function __construct() {

		// Necessary for execution of Revisr.
		$this->current_dir 	= getcwd();
		$this->is_repo 		= true;
		$this->options 		= Revisr::get_options();
		$this->git_path 	= $this->get_git_path();
		$this->git_dir 		= $this->get_git_dir();

		// Load up information about the current repository.
		if ( $this->is_repo ) {
			$this->branch 			= $this->current_branch();
			$this->remote 			= $this->current_remote();
			$this->current_commit 	= $this->current_commit();
		}
	}

	/**
	 * Runs a Git command and fires the given callback.
	 * @access 	public
	 * @param 	string 			$command 	The command to use.
	 * @param 	array 			$args 		Arguements provided by user.
	 * @param 	string 			$callback 	The callback to use.
	 * @param 	string|array 	$info 		Additional info to pass to the callback
	 */
	public function run( $command, $args, $callback = '', $info = '' ) {

		// Setup the command for safe usage.
		$safe_path 		= Revisr_Admin::escapeshellarg( $this->git_path );
		$safe_cmd 		= Revisr_Admin::escapeshellarg( $command );
		$safe_args 		= join( ' ', array_map( array( 'Revisr_Admin', 'escapeshellarg' ), $args ) );

		// Run the command.
		chdir( $this->git_dir );
		exec( "$safe_path $safe_cmd $safe_args 2>&1", $output, $return_code );
		chdir( $this->current_dir );

		// Process the response.
		$response 			= new Revisr_Git_Callback();
		$success_callback 	= 'success_' . $callback;
		$failure_callback 	= 'null_' . $callback;

		// Return the callback.
		if ( 0 !== $return_code ) {
			return $response->$failure_callback( $output, $info );
		} else {
			return $response->$success_callback( $output, $info );
		}
	}

	/**
	 * Returns the path to the top-level Git directory.
	 * @access public
	 * @return string The path to the top-level Git directory.
	 */
	public function get_git_dir() {

		// Allow users to set a custom path for the .git directory.
		if ( defined( 'REVISR_GIT_DIR' ) ) {
			chdir( REVISR_GIT_DIR );
		} else {
			chdir( ABSPATH );
		}

		$git_toplevel = exec( "$this->git_path rev-parse --show-toplevel" );

		if ( !$git_toplevel ) {
			$git_dir 		= getcwd();
			$this->is_repo 	= false;
		} else {
			$git_dir = $git_toplevel;
		}

		chdir( $this->current_dir );
		return $git_dir;
	}

	/**
	 * Returns the current path to Git.
	 * @access public
	 * @return string The path to the installation of Git.
	 */
	public function get_git_path() {
		if ( defined( 'REVISR_GIT_PATH' ) ) {
			return REVISR_GIT_PATH;
		} else {
			// This is surprisingly still the best option
			// given the huge amount of possible install paths,
			// and ~90% of the time this will work anyway.
			return 'git';
		}
	}

	/**
	 * Pushes changes if "Automatically push new commits" is enabled.
	 * @access public
	 */
	public function auto_push() {

		// Allow for preventing auto-push on a per-commit basis.
		if ( isset( $_REQUEST['autopush_enabled'] ) && ! isset( $_REQUEST['auto_push'] ) ) {
			return;
		}

		// Push the changes if needed.
		if ( $this->get_config( 'revisr', 'auto-push' ) === 'true' || isset( $_REQUEST['auto_push'] ) ) {
			$this->push();
		}
	}

	/**
	 * Checks out an existing branch.
	 * @access public
	 * @param string $branch The branch to checkout.
	 */
	public function checkout( $branch ) {
		$this->run( 'checkout', array( $branch, '-q' ), __FUNCTION__ );
	}

	/**
	 * Commits any staged files to the local repository.
	 * @access public
	 * @param  string $message 		The message to use with the commit.
	 * @param  string $callback 	The callback to run.
	 */
	public function commit( $message, $callback = '' ) {
		if ( is_user_logged_in() ) {
			$current_user 	= wp_get_current_user();
			$author 	 	= "$current_user->user_login <$current_user->user_email>";
			$commit 		= $this->run( 'commit', array( '-m', $message, '--author', $author ), $callback );
		} else {
			$commit = $this->run( 'commit', array( '-m', $message ), $callback );
		}

		return $commit;
	}

	/**
	 * Sets a value in the .git/config.
	 * @access public
	 * @param  string 	$section 	The section to store/update the value for.
	 * @param  string 	$key 		The key for the value which is being updated.
	 * @param  string 	$value 		The value to set.
	 * @return boolean
	 */
	public function set_config( $section, $key, $value ) {
		$update = $this->run( 'config', array( "$section.$key", $value ) );
		if ( $update !== false ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Gets a single value from the config.
	 * @access public
	 * @param  string $section 	The section to check from.
	 * @param  string $key 		The key who's value to grab.
	 * @return string|boolean
	 */
	public function get_config( $section, $key ) {
		$result = $this->run( 'config', array( '--get', "$section.$key" ) );
		if ( is_array( $result ) ) {
			return $result[0];
		} elseif ( is_string( $result ) ) {
			return $result;
		} else {
			return false;
		}
	}

	/**
	 * Returns the number of unpulled commits.
	 * @access public
	 */
	public function count_unpulled( $ajax_btn = true ) {
		$this->fetch();
		if ( $ajax_btn == true ) {
			$this->run( 'log', array( $this->branch . '..' . $this->remote . '/' . $this->branch, '--pretty=oneline' ), 'count_ajax_btn' );
		} else {
			$unpulled = $this->run( 'log', array( $this->branch . '..' . $this->remote . '/' . $this->branch, '--pretty=oneline' ) );
			return count( $unpulled );
		}
	}

	/**
	 * Returns the number of unpushed commits.
	 * @access public
	 */
	public function count_unpushed( $ajax_btn = true ) {
		if ( $ajax_btn == true ) {
			$this->run( 'log', array( $this->remote . '/' . $this->branch . '..' . $this->branch, '--pretty=oneline' ), 'count_ajax_btn' );
		} else {
			$unpushed = $this->run( 'log', array( $this->remote . '/' . $this->branch . '..' . $this->branch, '--pretty=oneline' ) );
			return count( $unpushed );
		}
	}

	/**
	 * Returns the number of untracked/modified files.
	 * @access public
	 */
	public function count_untracked() {
		$untracked = $this->run( 'status', array( '--short' ) );
		return count( $untracked );
	}

	/**
	 * Creates a new branch.
	 * @access public
	 * @param  string $branch The name of the branch to create.
	 */
	public function create_branch( $branch ) {
		$new_branch = $this->run( 'branch', array( $branch ) );
		return $new_branch;
	}

	/**
	 * Returns the current branch.
	 * @access public
	 */
	public function current_branch() {
		$current_branch = $this->run( 'rev-parse', array( '--abbrev-ref', 'HEAD' ) );
		if ( $current_branch != false && is_array( $current_branch ) ) {
			return $current_branch[0];
		}
	}

	/**
	 * Returns the hash of the current commit.
	 * @access public
	 */
	public function current_commit() {
		$commit_hash = $this->run( 'rev-parse', array( '--short',  'HEAD' ) );
		if ( is_array( $commit_hash ) ) {
			return $commit_hash[0];
		}
	}

	/**
	 * Returns the name of the current remote.
	 * @access public
	 */
	public function current_remote() {
		if ( isset( $this->options['remote_name'] ) && $this->options['remote_name'] != '' ) {
			return $this->options['remote_name'];
		} else {
			return 'origin';
		}
	}

	/**
	 * Deletes a branch.
	 * @access public
	 * @param  string 	$branch 	The branch to delete.
	 * @param  boolean 	$redirect 	Whether or not to redirect on completion.
	 */
	public function delete_branch( $branch, $redirect = true ) {
		if ( $redirect === false ) {
			$deletion = $this->run( 'branch', array( '-D', $branch ) );
		} else {
			$deletion = $this->run( 'branch', array( '-D', $branch ), __FUNCTION__, $branch );
		}
		return $deletion;
	}

	/**
	 * Fetches changes without merging them.
	 * @access public
	 */
	public function fetch() {
		$fetch = $this->run( 'fetch', array() );
		return $fetch;
	}

	/**
	 * Returns available branches on the local or remote repository.
	 * @access public
	 * @param  boolean $remote If set to true, will retrieve the remote branches.
	 */
	public function get_branches( $remote = false ) {
		if ( $remote == true ) {
			$branches = $this->run( 'branch', array( '-r' ) );
		} else {
			$branches = $this->run( 'branch', array() );
		}
		return $branches;
	}

	/**
	 * Returns the status of a file.
	 * @access public
	 * @param  string $status The status code returned via 'git status --short'
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
	public function init_repo() {
		$init = $this->run( 'init', array( '.' ), __FUNCTION__ );
		return $init;
	}

	/**
	 * Checks if the provided branch is an existing branch.
	 * @access public
	 * @param  string $branch The name of the branch to check.
	 * @return boolean
	 */
	public function is_branch( $branch ) {
		$branches = $this->get_branches();
		if ( in_array( $branch, $branches ) || in_array( "* $branch", $branches ) || in_array( "  $branch", $branches ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Merges a branch into the current branch.
	 * @access public
	 * @param  string $branch The branch to merge into the current branch.
	 */
	public function merge( $branch ) {
		$this->reset();
		$merge = $this->run( 'merge', array( $branch, '--strategy-option', 'theirs' ), __FUNCTION__ );
		return $merge;
	}

	/**
	 * Pulls changes from the remote repository.
	 * @access public
	 * @param  array $commits The commits we're pulling (used in callback).
	 */
	public function pull( $commits = array() ) {
		$this->reset();
		$pull = $this->run( 'pull', array( '-Xtheirs', '--quiet', $this->remote, $this->branch ), __FUNCTION__, $commits );
		return $pull;
	}

	/**
	 * Pushes changes to the remote repository.
	 * @access public
	 */
	public function push() {
		$push = $this->run( 'push', array( $this->remote, 'HEAD', '--quiet' ), __FUNCTION__, $this->count_unpushed( false ) );
		return $push;
	}

	/**
	 * Resets the working directory.
	 * @access public
	 * @param  string 	$mode	The mode to use for the reset (hard, soft, etc.).
	 * @param  string 	$path 	The path to apply the reset to.
	 * @param  bool 	$clean 	Whether to remove any untracked files.
	 */
	public function reset( $mode = '--hard', $path = 'HEAD', $clean = false ) {
		$this->run( 'reset', array( $mode, $path ) );
		if ( $clean === true ) {
			$this->run( 'clean', array( '-f', '-d' ) );
		}
	}

	/**
	 * Reverts the working directory to a specified commit.
	 * @access public
	 * @param  string $commit The hash of the commit to revert to.
	 */
	public function revert( $commit ) {
		$this->reset( '--hard', 'HEAD', true );
		$this->reset( '--hard', $commit );
		$this->reset( '--soft', 'HEAD@{1}' );
	}

	/**
	 * Stages the array of files passed through the New Commit screen.
	 * @access public
	 * @param  array $staged_files The files to add/remove
	 */
	public function stage_files( $staged_files ) {
		$errors = array();

		foreach ( $staged_files as $result ) {
			$file 	= substr( $result, 3 );
			$status = self::get_status( substr( $result, 0, 2 ) );

			if ( $status == __( 'Deleted', 'revisr' ) ) {
				if ( $this->run( 'rm', array( $file ) ) === false ) {
					$errors[] = $file;
				}
			} else {
				if ( $this->run( 'add', array( $file ) ) === false ) {
					$errors[] = $file;
				}
			}
		}

		if ( ! empty( $errors ) ) {
			$msg = __( 'There was an error staging the files. Please check the settings and try again.', 'revisr' );
			Revisr_Admin::alert( $msg, true );
			Revisr_Admin::log( __( 'Error staging files.', 'revisr' ), 'error' );
		}
	}

	/**
	 * Returns the current status.
	 * @access public
	 * @param  string $args Defaults to "--short".
	 */
	public function status( $args = '--short' ) {
		$status = $this->run( 'status', array( $args ) );
		return $status;
	}

	/**
	 * Adds a tag to the repository, or returns a list of tags if no parameters are passed.
	 * @access public
	 * @param  string $tag 		The tag to add.
	 */
	public function tag( $tag = '' ) {
		$tag = $this->run( 'tag', array( $tag ) );
		return $tag;
	}

	/**
	 * Updates the .gitignore file, and removes files from version control,
	 * making sure to keep the physical copies of the files on the server.
	 * @access public
	 */
	public function update_gitignore() {
		// Store the content in the .gitignore.
		file_put_contents( $this->git_dir . '/.gitignore', $this->options['gitignore'] );

		// Add the .gitignore.
		$this->run( 'add', array( '.gitignore' ) );

		// Convert the .gitignore into an array we can work with.
		$files = explode( PHP_EOL, $this->options['gitignore'] );

		foreach ( $files as $file ) {
			if ( '' == $file || '!' === $file[0] ) {
				// Don't do anything.
				continue;
			} else {
				/**
				 * Remove the cached version of the file,
				 * leaving it intact in the working directory.
				 */
				$this->run( 'rm', array( '--cached', $file ) );
			}
		}

		// Commit the updates.
		$commit_msg = __( 'Updated .gitignore.', 'revisr' );
		$this->run('commit', array( '-m', $commit_msg ) );
		$this->auto_push();
	}

	/**
	 * Pings a remote repository to verify that it exists and is reachable.
	 * @access public
	 * @param  string $remote The remote to ping.
	 */
	public function verify_remote( $remote = '' ) {
		if ( $remote != '' ) {
			$ping = $this->run( 'ls-remote', array( $remote, 'HEAD' ), __FUNCTION__ );
		} else {
			$ping = $this->run( 'ls-remote', array( $_REQUEST['remote'], 'HEAD' ), __FUNCTION__ );
		}
		return $ping;
	}

	/**
	 * Returns the current version of Git.
	 * @access public
	 */
	public function version() {
		$version = $this->run( 'version', array(), __FUNCTION__ );
		return $version;
	}
}
