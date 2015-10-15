<?php
/**
 * class-revisr-git-callback.php
 *
 * Processes Git responses and errors.
 *
 * @package   	Revisr
 * @license   	GPLv3
 * @link      	https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr_Git_Callback {

	/**
	 * The default success callback. Fired if no callback is provided.
	 * @access public
	 * @return array
	 */
	public function success_( $output = array(), $args = '' ) {
		return $output;
	}

	/**
	 * The default failure callback, fired if no callback is provided.
	 * @access public
	 * @return boolean
	 */
	public function null_( $output = array(), $args = '' ) {
		return false;
	}

	/**
	 * Callback for a successful checkout.
	 * @access public
	 */
	public function success_checkout( $output = array(), $args = '' ) {
		$branch 	= revisr()->git->current_branch();
		$msg 		= sprintf( __( 'Checked out branch: %s.', 'revisr' ), $branch );
		$email_msg 	= sprintf( __( '%s was switched to branch %s.', 'revisr' ), get_bloginfo(), $branch );
		Revisr_Admin::alert( $msg );
		Revisr_Admin::log( $msg, 'branch' );
		Revisr_Admin::notify(get_bloginfo() . __( ' - Branch Changed', 'revisr'), $email_msg );
	}

	/**
	 * Callback for a failed checkout.
	 * @access public
	 */
	public function null_checkout( $output = array(), $args = '' ) {
		$msg = __( 'There was an error checking out the branch. Check your configuration and try again.', 'revisr' );
		Revisr_Admin::alert( $msg, true, $output );
		Revisr_Admin::log( $msg, 'error' );
	}

	/**
	 * Callback for a successful commit.
	 * @access public
	 */
	public function success_commit( $output = array(), $args = '' ) {

		$commit_hash 	= revisr()->git->current_commit();
		$commit_msg 	= $_REQUEST['post_title'];
		$view_link 		= get_admin_url() . 'admin.php?page=revisr_view_commit&commit=' . $commit_hash . '&success=true';

		// Log the event.
		$msg = sprintf( __( 'Committed <a href="%s">#%s</a> to the local repository.', 'revisr' ), $view_link, $commit_hash );
		Revisr_Admin::log( $msg, 'commit' );

		// Notify the admin.
		$email_msg = sprintf( __( 'A new commit was made to the repository: <br> #%s - %s', 'revisr' ), $commit_hash, $commit_msg );
		Revisr_Admin::notify( get_bloginfo() . __( ' - New Commit', 'revisr' ), $email_msg );

		// Add a tag if necessary.
		if ( isset( $_REQUEST['tag_name'] ) ) {
			revisr()->git->tag( $_POST['tag_name'] );
		}

		// Fires after the commit has been made.
		do_action( 'revisr_post_commit', $output );

		// Push if necessary.
		revisr()->git->auto_push();

		// View the commit.
		Revisr_Admin::redirect( $view_link );
	}

	/**
	 * Callback for a failed commit.
	 * @access public
	 */
	public function null_commit( $output = array(), $args = '' ) {

		$msg 	= __( 'There was an error committing the changes to the local repository.', 'revisr' );
		$url 	= get_admin_url() . 'admin.php?page=revisr_new_commit&error=true';

		Revisr_Admin::alert( $msg, true, $output );
		Revisr_Admin::log( $msg, 'error' );
		Revisr_Admin::redirect( $url );
	}

	/**
	 * Callback for successful branch deletion.
	 * @access public
	 */
	public function success_delete_branch( $output = array(), $args = '' ) {
		$branch 	= $args;
		$msg 		= sprintf( __( 'Deleted branch %s.', 'revisr' ), $branch );
		$url 		= get_admin_url() . 'admin.php?page=revisr_branches&status=delete_success&branch=' . $branch;
		$email_msg 	= sprintf( __( 'The branch "%s" on the repository for %s was deleted.', 'revisr' ), $branch, get_bloginfo() );
		Revisr_Admin::log( $msg, 'branch' );
		Revisr_Admin::notify( get_bloginfo() . __( ' - Branch Deleted', 'revisr' ), $email_msg );
		Revisr_Admin::redirect( $url, true );
	}

	/**
	 * Callback for a failed branch deletion.
	 * @access public
	 */
	public function null_delete_branch( $output = array(), $args = '' ) {
		$branch = $args;
		$url 	= get_admin_url() . 'admin.php?page=revisr_branches&status=delete_fail&branch=' . $branch;
		Revisr_Admin::redirect( $url, true );
	}

	/**
	 * Renders the number of unpushed/unpulled commits for the AJAX buttons.
	 * @access public
	 */
	public function success_count_ajax_btn( $output = array(), $args = '' ) {

		$count = count( $output );

		if ( 0 != $count && revisr()->git->has_remote() ) {
			echo "($count)";
		}

		exit();
	}

	/**
	 * Returns nothing if there are no commits to push/pull.
	 * @access public
	 */
	public function null_count_ajax_btn( $output = array(), $args = '' ) {
		exit();
	}

	/**
	 * Called if the repo initialization was successful.
	 * Sets up the '.git/config' file for the first time.
	 * @access public
	 */
	public function success_init_repo( $output, $args ) {

		// Updates the repository properties.
		revisr()->git->is_repo 		= true;
		revisr()->git->git_dir 		= revisr()->git->get_git_dir();
		revisr()->git->work_tree 	= revisr()->git->get_work_tree();

		// Clear out any errors.
		Revisr_Admin::clear_transients();

		// Grab the current user.
		$user = wp_get_current_user();

		// Set the default username to use in Git.
		if ( isset( revisr()->git->options['username'] ) && revisr()->git->options['username'] != '' ) {
			revisr()->git->set_config( 'user', 'name', revisr()->git->options['username'] );
		} else {
			revisr()->git->set_config( 'user', 'name', $user->user_login );
		}

		// Set the default email to use in Git.
		if ( isset( revisr()->git->options['email'] ) && revisr()->git->options['email'] != '' ) {
			revisr()->git->set_config( 'user', 'email', revisr()->git->options['email'] );
		} else {
			revisr()->git->set_config( 'user', 'email', $user->user_email );
		}

		// Set the default name of the remote.
		if ( isset( revisr()->git->options['remote_name'] ) && revisr()->git->options['remote_name'] != '' ) {
			$remote_name = revisr()->git->options['remote_name'];
		} else {
			$remote_name = 'origin';
		}

		// Add the remote URL in Git if already set in the database.
		if ( isset( revisr()->git->options['remote_url'] ) && revisr()->git->options['remote_url'] != '' ) {
			revisr()->git->run( 'remote', array( 'add', $remote_name, revisr()->git->options['remote_url'] ) );
		}

		// Adds an .htaccess file to the "/.git" directory to prevent public access.
		if ( is_writable( revisr()->git->git_dir ) ) {
			file_put_contents( revisr()->git->git_dir . DIRECTORY_SEPARATOR . '.htaccess', 'Deny from all' . PHP_EOL );
		}

		// Fires after the repo has been created.
		do_action( 'revisr_post_init', $output );

		// Alerts the user.
		Revisr_Admin::log( __( 'Successfully created a new repository.', 'revisr' ), 'init' );

		// Redirect if necessary (through skipped/legacy setup).
		if ( ! defined( 'REVISR_SETUP_INIT' ) ) {
			Revisr_Admin::redirect( get_admin_url() . 'admin.php?page=revisr_settings&init=success' );
		}

		// Return true if we haven't exited already.
		return true;
	}

	/**
	 * Returns if an initialization failed.
	 * @access public
	 */
	public function null_init_repo() {

		// Redirect if necessary (through skipped/legacy setup).
		if ( ! defined( 'REVISR_SETUP_INIT' ) ) {
			Revisr_Admin::log( __( 'Failed to initialize a new repository. Please make sure that Git is installed on the server and that Revisr has write permissons to the WordPress install.', 'revisr' ), 'error' );
			Revisr_Admin::redirect();
		}

		// Return false if we haven't exited already.
		return false;
	}

	/**
	 * Returns if a merge was successful.
	 * @access public
	 */
	public function success_merge( $output = array(), $args = '' ) {
		$alert_msg 	= sprintf( __( 'Successfully merged changes from branch %s into branch %s.', 'revisr' ), $_REQUEST['branch'], revisr()->git->branch );
		$log_msg 	= sprintf( __( 'Merged branch %s into branch %s.', 'revisr' ), $_REQUEST['branch'], revisr()->git->branch );
		Revisr_Admin::alert( $alert_msg );
		Revisr_Admin::log( $log_msg, 'merge' );

		// Fires after a successful merge.
		do_action( 'revisr_post_merge', $output );

		Revisr_Admin::redirect();
	}

	/**
	 * Returns if a merge failed.
	 * @access public
	 */
	public function null_merge( $output = array(), $args = '' ) {
		$log_msg 	= sprintf( __( 'Error merging branch %s into %s.', 'revisr'), $_REQUEST['branch'], revisr()->git->branch );
		$alert_msg 	= sprintf( __( 'There was an error merging branch %s into your current branch. The merge was aborted to avoid conflicts.', 'revisr' ), $_REQUEST['branch'] );
		Revisr_Admin::alert( $alert_msg, true, $output );
		Revisr_Admin::log( $log_msg, 'error' );
		Revisr_Admin::redirect();
	}

	/**
	 * Returns if a pull was successful.
	 * @access public
	 */
	public function success_pull( $output = array(), $args = array() ) {
		$commits_since 	= $args;
		$num_commits 	= count( $commits_since );

		if ( 0 === $num_commits ) {
			$msg = __( 'The local repository is already up-to-date with the remote repository.', 'revisr' );
			Revisr_Admin::alert( $msg );
		} else {

			foreach ( $commits_since as $commit ) {

				$commit_hash 	= substr( $commit, 0, 7 );
				$view_link 		= get_admin_url() . 'admin.php?page=revisr_view_commit&commit=' . $commit_hash;
				$msg 			= sprintf( __( 'Pulled <a href="%s">#%s</a> from %s/%s.', 'revisr' ), $view_link, $commit_hash, revisr()->git->remote, revisr()->git->branch );
				Revisr_Admin::log( $msg, 'pull' );

			}

			$msg = sprintf( _n( 'Successfully pulled %s commit from %s/%s.', 'Successfully pulled %s commits from %s/%s.', $num_commits, 'revisr' ), $num_commits, revisr()->git->remote, revisr()->git->branch );
			Revisr_Admin::alert( $msg );

			// Fires just after a successful pull.
			do_action( 'revisr_post_pull', $output );

			if ( revisr()->git->get_config( 'revisr', 'import-pulls' ) === 'true' ) {
				revisr()->db->import();
			}
		}
	}

	/**
	 * Returns if a pull failed.
	 * @access public
	 * @return boolean
	 */
	public function null_pull( $output = array(), $args = '' ) {
		$msg = __( 'There was an error pulling from the remote repository. The local repository could be ahead, or there may be an authentication issue.', 'revisr' );
		Revisr_Admin::alert( $msg, true, $output );
		Revisr_Admin::log( __( 'Error pulling changes from the remote repository.', 'revisr' ), 'error' );
		return false;
	}

	/**
	 * Returns if a push was successful.
	 * @access public
	 */
	public function success_push( $output = array(), $args = '' ) {
		$msg = sprintf( _n( 'Successfully pushed %s commit to %s/%s.', 'Successfully pushed %s commits to %s/%s.', $args, 'revisr' ), $args, revisr()->git->remote, revisr()->git->branch );
		Revisr_Admin::alert( $msg );
		Revisr_Admin::log( $msg, 'push' );

		// Fires after a successful push.
		do_action( 'revisr_post_push', $output );

		if ( revisr()->git->get_config( 'revisr', 'webhook-url' ) !== false ) {
			$remote = new Revisr_Remote();
			$remote->send_request();
		}
	}

	/**
	 * Returns if a push failed.
	 * @access public
	 */
	public function null_push( $output = array(), $args = '' ) {
		$msg = __( 'Error pushing to the remote repository. The remote repository could be ahead, or there may be an authentication issue.', 'revisr' );
		Revisr_Admin::alert( $msg, true, $output );
		Revisr_Admin::log( __( 'Error pushing changes to the remote repository.', 'revisr' ), 'error' );
		return;
	}

	/**
	 * Returns "Success!" if the connection to remote was successful.
	 * @access public
	 */
	public function success_verify_remote(  $output = array(), $args = '' ) {
		_e( 'Success!', 'revisr' );
		exit();
	}

	/**
	 * Returns if the connection to the remote was unsuccessful.
	 * @access public
	 */
	public function null_verify_remote( $output = array(), $args = '' ) {
		_e( 'Remote not found...', 'revisr' );
		exit();
	}

	/**
	 * Returns the Git version.
	 * @access public
	 */
	public function success_version( $output = array(), $args = '' ) {
		return $output['0'];
	}

	/**
	 * Returns if Revisr could not detect the Git version.
	 * @access public
	 */
	public function null_version( $output = array(), $args = '' ) {
		return __( 'Unknown', 'revisr' );
	}
}
