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
	 * Stores the path to the .git directory.
	 * @var string
	 */
	public $git_dir;

	/**
	 * Stores the path to the working directory.
	 * @var string
	 */
	public $work_tree;

	/**
	 * Stores the path to Git.
	 * @var string
	 */
	public $git_path;

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
		$this->is_repo 		= true;
		$this->current_dir 	= getcwd();
		$this->git_path 	= $this->get_git_path();
		$this->git_dir 		= $this->get_git_dir();
		$this->work_tree 	= $this->get_work_tree();

		// Make sure the provided Git directory is valid.
		$this->check_work_tree();

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

		// Allow for customizing the git work-tree and git-dir paths.
		$git_dir 	= Revisr_Admin::escapeshellarg( "--git-dir=$this->git_dir" );
		$work_tree 	= Revisr_Admin::escapeshellarg( "--work-tree=$this->work_tree" );

		// Run the command.
		chdir( $this->work_tree );
		exec( "$safe_path $git_dir $work_tree $safe_cmd $safe_args 2>&1", $output, $return_code );
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
	 * Checks if the provided path is a Git repository.
	 * @access public
	 * @param  string $dir The directory to check (optional).
	 * @return boolean
	 */
	public function check_work_tree( $dir = '' ) {

		// If no dir provided, use constant.
		if ( '' === $dir ) {
			$dir = $this->get_work_tree();
		}

		// Definitely bail if not a directory.
		if ( ! is_dir( $dir ) ) {
			return false;
		}

		$this->work_tree = $dir;

		// Check with Git binary if repo is detected.
		$git_toplevel = $this->run( 'rev-parse', array( '--show-toplevel' ) );

		// If not, set the is_repo flag to false.
		if ( ! $git_toplevel ) {
			$this->is_repo = false;
			return false;
		}

		return true;
	}

	/**
	 * Returns the path to the top-level Git directory.
	 * @access public
	 * @return string The path to the top-level Git directory.
	 */
	public function get_git_dir() {

		if ( defined( 'REVISR_GIT_DIR' ) && is_dir( REVISR_GIT_DIR ) ) {

			// Force removal of trailing slash upfront.
			$git_dir = rtrim( REVISR_GIT_DIR, DIRECTORY_SEPARATOR );

			if ( is_dir( REVISR_GIT_DIR . DIRECTORY_SEPARATOR . '.git' ) ) {

				// Workaround for backwards compatibility.
				if ( ! defined( 'REVISR_WORK_TREE' ) ) {

					// Define the REVISR_WORK_TREE constant to match the old REVISR_GIT_DIR constant.
					define( 'REVISR_WORK_TREE', REVISR_GIT_DIR );
					$line = "define('REVISR_WORK_TREE', '" . REVISR_GIT_DIR . "');";
					Revisr_Admin::replace_config_line( 'define *\( *\'REVISR_WORK_TREE\'', $line );

					// Update the old REVISR_GIT_DIR constant.
					$git_dir 	= REVISR_GIT_DIR . DIRECTORY_SEPARATOR . '.git';
					$line 		= "define('REVISR_GIT_DIR', '" . REVISR_GIT_DIR . DIRECTORY_SEPARATOR . ".git');";
					Revisr_Admin::replace_config_line( 'define *\( *\'REVISR_GIT_DIR\'', $line );

				}

			} else {
				$git_dir = REVISR_GIT_DIR;
			}

		} else {

			// Best guess.
			$git_dir = $this->get_work_tree() . DIRECTORY_SEPARATOR . '.git';

		}

		return $git_dir;
	}

	/**
	 * Returns the current path to Git.
	 * @access public
	 * @return string The path to the installation of Git.
	 */
	public function get_git_path() {
		if ( defined( 'REVISR_GIT_PATH' ) && file_exists( REVISR_GIT_PATH ) ) {
			return REVISR_GIT_PATH;
		} else {
			// This is surprisingly still the best option
			// given the huge amount of possible install paths,
			// and ~90% of the time this will work anyway.
			return 'git';
		}
	}

	/**
	 * Returns the path to the Git work-tree in exec() friendly format.
	 * @access public
	 * @return string
	 */
	public function get_work_tree() {

		if ( defined( 'REVISR_WORK_TREE' ) && is_dir( REVISR_WORK_TREE ) ) {
			$work_tree = REVISR_WORK_TREE;
		} else {
			$work_tree = ABSPATH;
		}

		// Remove trailing slash.
		$work_tree = rtrim( $work_tree, DIRECTORY_SEPARATOR );
		return $work_tree;
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
	public function checkout( $branch, $new_branch = false ) {
		if ( $new_branch ) {
			$args = array( '-b', $branch, '-q' );
		} else {
			$args = array( $branch, '-q' );
		}

		$this->run( 'checkout', $args, __FUNCTION__ );
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
		if ( ! $this->run( 'config', array( "$section.$key", $value ) ) ) {
			return false;
		}
		return true;
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

		if ( true === $ajax_btn ) {
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

		if ( true === $ajax_btn ) {
			$this->run( 'log', array( $this->branch, '--not', '--remotes', '--oneline' ), 'count_ajax_btn' );
		} else {
			$unpushed = $this->run( 'log', array( $this->branch, '--not', '--remotes', '--oneline' ) );
			return count( $unpushed );
		}

	}

	/**
	 * Returns the number of untracked/modified files.
	 * @access public
	 */
	public function count_untracked() {
		$untracked = $this->run( 'status', array( '--short', '--untracked-files=all' ) );
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
	 * @return string
	 */
	public function current_remote() {
		$remote = $this->get_config( 'revisr', 'current-remote' );
		if ( ! $remote ) {
			$remote = 'origin';
		}
		return $remote;
	}

	/**
	 * Deletes a local or remote branch.
	 * @access public
	 * @param  string 	$branch 	The branch to delete.
	 * @param  boolean 	$redirect 	Whether or not to redirect on completion.
	 * @param  boolean 	$remote 	Whether $branch is a local or remote branch.
	 * @return mixed
	 */
	public function delete_branch( $branch, $redirect = true, $remote = false ) {

		$callback = '';

		if ( $redirect ) {
			$callback = __FUNCTION__;
		}

		if ( $remote ) {
			$deletion = $this->run( 'push', array( $this->remote, ":$branch" ), $callback, $branch );
		} else {
			$deletion = $this->run( 'branch', array( '-D', $branch ), $callback, $branch );
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

		$params = array();

		if ( true === $remote ) {
			$params[] = '-r';
		}

		return $this->run( 'branch', $params );
	}

	/**
	 * Returns the date of last update for a branch/ref.
	 * @access public
	 * @return string
	 */
	public function get_branch_last_updated( $branch, $format = '' ) {
		$last_updated = $this->run( 'log', array( $branch, '-1', '--format=%cd', '--date=relative' ) );

		if ( $last_updated ) {
			return $last_updated[0];
		} else {
			return __( 'Unknown', 'revisr' );
		}

	}

	/**
	 * Returns the author of a provided commit.
	 * @access public
	 * @param  string $commit_hash The hash of the commit to get the author of.
	 * @return string|boolean
	 */
	public function get_commit_author_by_hash( $commit_hash ) {

		$author = $this->run( 'log', array( '-1', '--format=%an', $commit_hash ) );

		if ( is_array( $author ) ) {
			return $author[0];
		}

		return false;

	}

	/**
	 * Returns an array of details on the provided commit.
	 * @access public
	 * @param  string $hash The SHA1 of the commit to check.
	 * @return array
	 */
	public static function get_commit_details( $hash ) {

		// Build an associative array of details.
		$commit_details = array(
			'hash' 				=> $hash,
			'branch' 			=> '',
			'author' 			=> '',
			'subject' 			=> __( 'Commit not found', 'revisr' ),
			'time' 				=> time(),
			'files_changed' 	=> 0,
			'committed_files' 	=> array(),
			'has_db_backup'		=> false,
			'tag' 				=> '',
			'status' 			=> false,
		);

		// Try to get some basic data about the commit.
		$commit = revisr()->git->run( 'show', array( '--pretty=format:%s|#|%an|#|%at', '--name-status', '--root', '-r', $hash ) );

		if ( is_array( $commit ) ) {

			$commit_meta = $commit[0];
			$commit_meta = explode( '|#|', $commit_meta );
			unset( $commit[0] );

			$commit_details['subject'] 	= $commit_meta[0];
			$commit_details['author'] 	= $commit_meta[1];
			$commit_details['time'] 	= $commit_meta[2];

			$commit = array_filter( $commit );
			$backed_up_tables = preg_grep( '/revisr.*sql/', $commit );

			if ( 0 !== count( $backed_up_tables ) ) {
				$commit_details['has_db_backup'] = true;
			}

			$commit_details['files_changed'] 	= count( $commit );
			$commit_details['committed_files'] 	= $commit;

			$branches = revisr()->git->run( 'branch', array( '--contains', $hash ) );
			$commit_details['branch'] = is_array( $branches ) ? implode( ', ', $branches ) : __( 'Unknown', 'revisr' );
			$commit_details['status'] = __( 'Committed', 'revisr' );

		}

		// Return the array.
		return $commit_details;

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
	 * Checks if the repo has a remote.
	 * @access public
	 * @return boolean
	 */
	public function has_remote() {
		if ( $this->get_config( 'remote', 'origin.url' ) ) {
			return true;
		}
		return false;
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
	 * @param  boolean 	$clean 	Whether to remove any untracked files.
	 * @return boolean
	 */
	public function reset( $mode = '--hard', $path = 'HEAD', $clean = false ) {

		if ( $this->run( 'reset', array( $mode, $path ) ) ) {

			if ( true === $clean ) {

				if ( ! $this->run( 'clean', array( '-f', '-d' ) ) ) {
					return false;
				}

			}

			return true;
		}

		return false;
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
	 * @param  array 	$staged_files 	An array of files to add/remove.
	 * @param  boolean 	$stage_all 		If set to true, skip the loop and add -A.
	 * @return boolean
	 */
	public function stage_files( $staged_files, $stage_all = false ) {

		// An empty array for errors.
		$errors = array();

		if ( true === $stage_all ) {

			if ( $this->run( 'add', array( '-A' ) ) === false ) {
				$errors[] = $staged_files;
			}

		} else {

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

		}

		if ( ! empty( $errors ) ) {
			$msg = __( 'There was an error staging the files. Please check the settings and try again.', 'revisr' );
			Revisr_Admin::alert( $msg, true );
			Revisr_Admin::log( __( 'Error staging files.', 'revisr' ), 'error' );
			return false;
		}

		return true;
	}

	/**
	 * Returns the current status.
	 * @access public
	 * @param  array $args Defaults to "--short".
	 */
	public function status( $args = array( '--short', '--untracked-files=all' ) ) {
		$status = $this->run( 'status', $args );
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
		file_put_contents( $this->work_tree . DIRECTORY_SEPARATOR . '.gitignore', revisr()->options['gitignore'] );

		// Add the .gitignore.
		$this->run( 'add', array( '.gitignore' ) );

		// Convert the .gitignore into an array we can work with.
		$files = explode( PHP_EOL, revisr()->options['gitignore'] );

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
		$this->run( 'commit', array( '-m', $commit_msg ) );
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
