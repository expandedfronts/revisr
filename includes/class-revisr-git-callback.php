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

// Disallow direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr_Git_Callback {

	/**
	 * The instance of the Git class.
	 * @var object
	 */
	private $revisr;

	/**
	 * Initiates the class.
	 * @access public
	 */
	public function __construct() {
		$this->revisr = Revisr::get_instance();
	}

	/**
	 * The default success callback. Fired if no callback is provided.
	 * @access public
	 */
	public function success_( $output = '', $args = '' ) {
		return $output;
	}

	/**
	 * The default failure callback, fired if no callback is provided.
	 * @access public
	 */
	public function null_( $output = '', $args = '' ) {
		return false;
	}
	
	/**
	 * Callback for a successful checkout.
	 * @access public
	 */
	public function success_checkout( $output = '', $args = '' ) {
		$branch 	= $this->revisr->git->current_branch();
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
	public function null_checkout( $output = '', $args = '' ) {
		$msg = __( 'There was an error checking out the branch. Check your configuration and try again.', 'revisr' );
		Revisr_Admin::alert( $msg, true );
		Revisr_Admin::log( $msg, 'error' );
	}

	/**
	 * Callback for a successful commit.
	 * @access public
	 */
	public function success_commit( $output = '', $args = '' ) {
		$id 			= get_the_ID();
		$view_link 		= get_admin_url() . "post.php?post={$id}&action=edit";
		$commit_hash 	= $this->revisr->git->current_commit();
		$commit_msg 	= $_REQUEST['post_title'];
		add_post_meta( $id, 'commit_hash', $commit_hash );
		add_post_meta( $id, 'branch', $this->revisr->git->branch );
		// Backup the database if necessary
		if ( isset( $_REQUEST['backup_db'] ) && $_REQUEST['backup_db'] == 'on' ) {
			$this->revisr->db->backup();
			add_post_meta( $id, 'db_hash', $this->revisr->git->current_commit() );
			add_post_meta( $id, 'backup_method', 'tables' );
		}
		// Log the event.
		$msg = sprintf( __( 'Committed <a href="%s">#%s</a> to the local repository.', 'revisr' ), $view_link, $commit_hash );
		Revisr_Admin::log( $msg, 'commit' );
		// Notify the admin.
		$email_msg = sprintf( __( 'A new commit was made to the repository: <br> #%s - %s', 'revisr' ), $commit_hash, $commit_msg );
		Revisr_Admin::notify( get_bloginfo() . __( ' - New Commit', 'revisr' ), $email_msg );
		// Add a tag if necessary.
		if ( isset( $_REQUEST['tag_name'] ) ) {
			$this->revisr->git->tag( $_POST['tag_name'] );
			add_post_meta( $id, 'git_tag', $_POST['tag_name'] );
		}
		// Push if necessary.
		$this->revisr->git->auto_push();
		return $commit_hash;
	}

	/**
	 * Callback for a failed commit.
	 * @access public
	 */
	public function null_commit( $output = '', $args = '' ) {
		$msg = __( 'Error committing the changes to the local repository.', 'revisr' );
		Revisr_Admin::log( $msg, 'error' );
		$url = get_admin_url() . 'post-new.php?post_type=revisr_commits&message=44';
		wp_redirect( $url );
	}

	/**
	 * Callback for successful branch deletion.
	 * @access public
	 */
	public function success_delete_branch( $output = '', $args = '' ) {
		$branch 	= $args;
		$msg 		= sprintf( __( 'Deleted branch %s.', 'revisr'), $branch );
		$email_msg 	= sprintf( __( 'The branch "%s" on the repository for %s was deleted.', 'revisr' ), $branch, get_bloginfo() );
		Revisr_Admin::log( $msg, 'branch' );
		Revisr_Admin::notify( get_bloginfo() . __( ' - Branch Deleted', 'revisr' ), $email_msg );
		_e( 'Branch deleted successfully. Redirecting...', 'revisr' );
		echo "<script>
				window.top.location.href = '" . get_admin_url() . "admin.php?page=revisr_branches&status=delete_success&branch={$branch}'
		</script>";
	}

	/**
	 * Callback for a failed branch deletion.
	 * @access public
	 */
	public function null_delete_branch( $output = '', $args = '' ) {
		echo "<script>
				window.top.location.href = '" . get_admin_url() . "admin.php?page=revisr_branches&status=delete_fail'
		</script>";
	}

	/**
	 * Renders the number of unpushed/unpulled commits for the AJAX buttons.
	 * @access public
	 */
	public function success_count_ajax_btn( $output = '', $args = '' ) {
		if ( count( $output ) != 0 ) {
			echo '(' . count( $output ) . ')';
		}
		exit();
	}

	/**
	 * Returns nothing if there are no commits to push/pull.
	 * @access public
	 */
	public function null_count_ajax_btn( $output = '', $args = '' ) {
		exit();
	}

	/**
	 * Called if the repo initialization was successful.
	 * Sets up the '.git/config' file for the first time.
	 * @access public
	 */
	public function success_init_repo() {
		Revisr_Admin::clear_transients();
		$user = wp_get_current_user();

		if ( isset( $this->revisr->git->options['username'] ) && $this->revisr->git->options['username'] != '' ) {
			$this->revisr->git->set_config( 'user', 'name', $this->revisr->git->options['username'] );
		} else {
			$this->revisr->git->set_config( 'user', 'name', $user->user_login );
		}
		if ( isset( $this->revisr->git->options['email'] ) && $this->revisr->git->options['email'] != '' ) {
			$this->revisr->git->set_config( 'user', 'email', $this->revisr->git->options['email'] );
		} else {
			$this->revisr->git->set_config( 'user', 'email', $user->user_email );
		}
		if ( isset( $this->revisr->git->options['remote_name'] ) && $this->revisr->git->options['remote_name'] != '' ) {
			$remote_name = $this->revisr->git->options['remote_name'];
		} else {
			$remote_name = 'origin';
		}
		if ( isset( $this->revisr->git->options['remote_url'] ) && $this->revisr->git->options['remote_url'] != '' ) {
			$this->revisr->git->run( 'remote', array( 'add', $remote_name, $this->revisr->git->options['remote_url'] ) );
		}

		Revisr_Admin::log( __( 'Successfully created a new repository.', 'revisr' ), 'init' );
		wp_redirect( get_admin_url() . 'admin.php?page=revisr_settings&init=success' );
		exit();
	}

	/**
	 * Returns if an initialization failed.
	 * @access public
	 */
	public function null_init_repo() {
		Revisr_Admin::log( __( 'Failed to initialize a new repository. Please make sure that Git is installed on the server and that Revisr has write permissons to the WordPress install.', 'revisr' ), 'error' );
		wp_redirect( get_admin_url() . 'admin.php?page=revisr' );
		exit();
	}

	/**
	 * Returns if a merge was successful.
	 * @access public
	 */
	public function success_merge( $output = '', $args = '' ) {
		$alert_msg 	= sprintf( __( 'Successfully merged changes from branch %s into branch %s.', 'revisr' ), $_REQUEST['branch'], $this->revisr->git->branch );
		$log_msg 	= sprintf( __( 'Merged branch %s into branch %s.', 'revisr' ), $_REQUEST['branch'], $this->revisr->git->branch );
		Revisr_Admin::alert( $alert_msg );
		Revisr_Admin::log( $log_msg, 'merge' );
		_e( 'Merge completed successfully. Redirecting...', 'revisr' );
		echo "<script>
				window.top.location.href = '" . get_admin_url() . "admin.php?page=revisr';
		</script>";
	}

	/**
	 * Returns if a merge failed.
	 * @access public
	 */
	public function null_merge( $output = '', $args = '' ) {
		$log_msg 	= sprintf( __( 'Error merging branch %s into %s.', 'revisr'), $_REQUEST['branch'], $this->revisr->git->branch );
		$alert_msg 	= sprintf( __( 'There was an error merging branch %s into your current branch. The merge was aborted to avoid conflicts.', 'revisr' ), $_REQUEST['branch'] );
		Revisr_Admin::alert( $alert_msg, true );
		Revisr_Admin::log( $log_msg, 'error' );
		echo "<script>
				window.top.location.href = '" . get_admin_url() . "admin.php?page=revisr';
		</script>";
	}

	/**
	 * Returns if a pull was successful.
	 * @access public
	 */
	public function success_pull( $output = '', $args = '' ) {
		if ( $args == '0' ) {
			$msg = __( 'The local repository is already up-to-date with the remote repository.', 'revisr' );
			Revisr_Admin::alert( $msg );
		} else {
			$msg = sprintf( _n( 'Successfully pulled %s commit from %s/%s.', 'Successfully pulled %s commits from %s/%s.', $args, 'revisr' ), $args, $this->revisr->git->remote, $this->revisr->git->branch );
			Revisr_Admin::alert( $msg );

			if ( $this->revisr->git->get_config( 'revisr', 'import-pulls' ) === 'true' ) {
				$this->revisr->db->import();
			}
		}
	}

	/**
	 * Returns if a pull failed.
	 * @access public
	 * @return boolean
	 */
	public function null_pull( $output = '', $args = '' ) {
		$msg = __( 'There was an error pulling from the remote repository. The local repository could be ahead, or there may be an authentication issue.', 'revisr' );
		Revisr_Admin::alert( $msg, true );
		Revisr_Admin::log( __( 'Error pulling changes from the remote repository.', 'revisr' ), 'error' );
		return false;
	}

	/**
	 * Returns if a push was successful.
	 * @access public
	 */
	public function success_push( $output = '', $args = '' ) {
		$msg = sprintf( _n( 'Successfully pushed %s commit to %s/%s.', 'Successfully pushed %s commits to %s/%s.', $args, 'revisr' ), $args, $this->revisr->git->remote, $this->revisr->git->branch );
		Revisr_Admin::alert( $msg );
		Revisr_Admin::log( $msg, 'push' );
		if ( $this->revisr->git->get_config( 'revisr', 'webhook-url' ) !== false ) {
			$remote = new Revisr_Remote();
			$remote->send_request();
		}
	}

	/**
	 * Returns if a push failed.
	 * @access public
	 */
	public function null_push( $output = '', $args = '' ) {
		$msg = __( 'Error pushing to the remote repository. The remote repository could be ahead, or there may be an authentication issue.', 'revisr' );
		Revisr_Admin::alert( $msg, true );
		Revisr_Admin::log( __( 'Error pushing changes to the remote repository.', 'revisr' ), 'error' );
		return;
	}

	/**
	 * Returns "Success!" if the connection to remote was successful.
	 * @access public
	 */
	public function success_verify_remote(  $output = '', $args = '' ) {
		_e( 'Success!', 'revisr' );
		exit();
	}

	/**
	 * Returns if the connection to the remote was unsuccessful.
	 * @access public
	 */
	public function null_verify_remote( $output = '', $args = '' ) {
		_e( 'Remote not found...', 'revisr' );
		exit();
	} 

	/**
	 * Returns the Git version.
	 * @access public
	 */
	public function success_version( $output = '', $args = '' ) {
		return $output['0'];
	}

	/**
	 * Returns if Revisr could not detect the Git version.
	 * @access public
	 */
	public function null_version( $output = '', $args = '' ) {
		return __( 'Unknown', 'revisr' );
	}
}