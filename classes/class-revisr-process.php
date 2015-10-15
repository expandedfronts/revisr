<?php
/**
 * class-revisr-process.php
 *
 * Processes user actions and delegates work to the correct class.
 *
 * @package   	Revisr
 * @license   	GPLv3
 * @link      	https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr_Process {

	/**
	 * Checks if a the current WordPress site is a repository,
	 * and returns a link to create a new repository if not.
	 * @access public
	 * @return boolean
	 */
	public function is_repo() {

		if ( defined( 'REVISR_SKIP_SETUP' ) || get_transient( 'revisr_skip_setup' ) ) {
			if ( revisr()->git->is_repo ) {
				return true;
			} else {
				$init_url 	= wp_nonce_url( get_admin_url() . 'admin-post.php?action=init_repo', 'init_repo', 'revisr_init_nonce' );
				$alert 		= sprintf( __( 'Thanks for installing Revisr! No Git repository was detected, <a href="%s">click here</a> to create one.', 'revisr' ), $init_url );
				Revisr_Admin::alert( $alert );
			}
			return false;
		}

	}

	/**
	 * Processes the request to checkout an existing branch.
	 * @access public
	 */
	public function checkout( $args = '', $new_branch = false ) {

		Revisr_Admin::verify_nonce( $_REQUEST['revisr_checkout_nonce'], 'process_checkout' );

		if ( revisr()->git->get_config( 'revisr', 'import-checkouts' ) === 'true' ) {
			revisr()->db->backup();
		}

		$branch 	= isset( $_REQUEST['branch'] ) ? $_REQUEST['branch'] : $args;
		$new_branch = isset( $_REQUEST['new_branch'] ) ? $_REQUEST['new_branch'] : false;

		// Fires before the checkout.
		do_action( 'revisr_pre_checkout', $branch );

		revisr()->git->reset();
		revisr()->git->checkout( $branch, $new_branch );

		if ( revisr()->git->get_config( 'revisr', 'import-checkouts' ) === 'true' && $new_branch === false ) {
			revisr()->db->import();
		}

		Revisr_Admin::redirect();

	}

	/**
	 * Processes a new commit from the "New Commit" admin page.
	 * @access public
	 */
	public function commit() {

		Revisr_Admin::verify_nonce( $_REQUEST['revisr_commit_nonce'], 'process_commit' );

		$commit_msg 	= $_REQUEST['post_title'];

		// Backup the database if necessary.
		if ( isset( $_POST['backup_db'] ) ) {
			revisr()->db->backup( '', false );
		}

		// Add any staged files.
		if ( isset( $_POST['staged_files'] ) ) {
			$staged_files 	= $_POST['staged_files'];
			$quick_stage 	= isset( $_POST['unstaged_files'] ) ? false : true;
			revisr()->git->stage_files( $staged_files, $quick_stage );
		}

		// Make the commit.
		revisr()->git->commit( $commit_msg, 'commit' );

	}

	/**
	 * Processes the request to create a new branch.
	 * @access public
	 */
	public function create_branch() {

		Revisr_Admin::verify_nonce( $_REQUEST['revisr_create_branch_nonce'], 'process_create_branch' );

		// Branches can't have spaces, so we replace them with hyphens.
		$branch = str_replace( ' ', '-', $_REQUEST['branch_name'] );

		// Create the branch.
		$result = revisr()->git->create_branch( $branch );
		$url 	= get_admin_url() . 'admin.php?page=revisr_branches&branch=' . $branch;

		if ( $result !== false ) {
			$msg = sprintf( __( 'Created new branch: %s', 'revisr' ), $branch );
			Revisr_Admin::log( $msg, 'branch' );

			// Maybe checkout the new branch.
			if ( isset( $_REQUEST['checkout_new_branch'] ) ) {
				revisr()->git->checkout( $branch );
			}

			$url .= '&status=create_success';
		} else {
			$url .= '&status=create_error';
		}

		Revisr_Admin::redirect( $url );

	}

	/**
	 * Processes the request to delete an existing branch.
	 * @access public
	 */
	public function delete_branch() {

		Revisr_Admin::verify_nonce( $_REQUEST['revisr_delete_branch_nonce'], 'process_delete_branch' );

		$branch = $_REQUEST['branch'];

		// Allows deleting just the remote branch.
		if ( isset( $_REQUEST['delete_remote_only'] ) ){
			revisr()->git->delete_branch( $branch, true, true );
			exit();
		}

		// Delete local, and maybe remote branches.
		if ( isset( $_REQUEST['branch'] ) && $_REQUEST['branch'] != revisr()->git->branch ) {

			revisr()->git->delete_branch( $branch, true );

			if ( isset( $_REQUEST['delete_remote_branch'] ) ) {
				revisr()->git->delete_branch( $branch, false, true );
			}
		}

		exit();

	}

	/**
	 * Processes the request to discard all untracked changes.
	 * @access public
	 */
	public function discard() {

		Revisr_Admin::verify_nonce( $_REQUEST['revisr_dashboard_nonce'], 'revisr_dashboard_nonce' );

		// Fires prior to a discard.
		do_action( 'revisr_pre_discard' );

		if ( revisr()->git->reset( '--hard', 'HEAD', true ) ) {

			Revisr_Admin::log( __('Discarded all uncommitted changes.', 'revisr'), 'discard' );
			Revisr_Admin::alert( __('Successfully discarded any uncommitted changes.', 'revisr' ) );

			// Fires after a successful discard.
			do_action( 'revisr_post_discard' );

		}

		exit();

	}

	/**
	 * Processes a Git init.
	 * @access public
	 */
	public function init() {

		Revisr_Admin::verify_nonce( $_REQUEST['revisr_init_nonce'], 'init_repo' );

		// Fires before a repo is created.
		do_action( 'revisr_pre_init' );

		revisr()->git->init_repo();

	}

	/**
	 * Processes the import of additional (new) tables.
	 * @access public
	 */
	public function import() {

		Revisr_Admin::verify_nonce( $_REQUEST['revisr_import_nonce'], 'process_import' );

		if ( isset( $_REQUEST['revisr_import_untracked'] ) && is_array( $_REQUEST['revisr_import_untracked'] ) ) {
			revisr()->db->import( $_REQUEST['revisr_import_untracked'] );
			Revisr_Admin::redirect();
		}

	}

	/**
	 * Processes the request to merge a branch into the current branch.
	 * @access public
	 */
	public function merge() {

		Revisr_Admin::verify_nonce( $_REQUEST['revisr_merge_nonce'], 'process_merge' );

		// Fires immediately before a merge.
		do_action( 'revisr_pre_merge' );

		revisr()->git->merge( $_REQUEST['branch'] );

		if ( isset( $_REQUEST['import_db'] ) && $_REQUEST['import_db'] == 'on' ) {
			revisr()->db->import();
		}

	}

	/**
	 * Processes the request to pull changes into the current branch.
	 * @access public
	 */
	public function pull() {

		Revisr_Admin::verify_nonce( $_REQUEST['revisr_dashboard_nonce'], 'revisr_dashboard_nonce' );

		// Fetch the changes so we can compare them.
		revisr()->git->reset();
		revisr()->git->fetch();

		// Build an array of the commits we don't have locally.
		$commits_since = revisr()->git->run( 'log', array( revisr()->git->branch . '..' . revisr()->git->remote . '/' . revisr()->git->branch, '--pretty=oneline' ) );

		// Maybe backup database.
		if ( revisr()->git->get_config( 'revisr', 'import-pulls' ) === 'true' ) {
			revisr()->db->backup();
			$undo_hash = revisr()->git->current_commit();
			revisr()->git->set_config( 'revisr', 'last-db-backup', $undo_hash );
		}

		// Fires before the changes are pulled.
		do_action( 'revisr_pre_pull', $commits_since );

		// Pull the changes or return an error on failure.
		revisr()->git->pull( $commits_since );
	}

	/**
	 * Processes the request to push changes to a remote repository.
	 * @access public
	 */
	public function push() {

		Revisr_Admin::verify_nonce( $_REQUEST['revisr_dashboard_nonce'], 'revisr_dashboard_nonce' );

		// Fires before a push.
		do_action( 'revisr_pre_push' );

		revisr()->git->push();

	}

	/**
	 * Processes a request to revert, routing to the necessary functions.
	 * @access public
	 * @param  string $type What to revert
	 * @return null
	 */
	public function revert( $type = '' ) {

		Revisr_Admin::verify_nonce( $_REQUEST['revisr_revert_nonce'], 'revisr_revert_nonce' );

		// Determine how to handle the request.
		if ( isset( $_REQUEST['revert_type'] ) && $_REQUEST['revert_type'] !== '' ) {
			$revert_type = $_REQUEST['revert_type'];
		} else {
			$revert_type = $type;
		}

		// Fires before the revert.
		do_action( 'revisr_pre_revert', $type );

		// Run the action.
		switch ( $revert_type ) {
			case 'files':
				$this->revert_files( false );
				break;
			case 'db':
				revisr()->db->restore( false );
				break;
			case 'files_and_db':
				$this->revert_files( false );
				revisr()->db->restore( false );
				break;
			default:
		}

		Revisr_Admin::redirect();

	}

	/**
	 * Processes the request to revert to an earlier commit.
	 * @access public
	 */
	public function revert_files( $redirect = true ) {

		Revisr_Admin::verify_nonce( $_REQUEST['revisr_revert_nonce'], 'revisr_revert_nonce' );

		$commit 	= $_REQUEST['commit_hash'];
		$commit_msg = sprintf( __( 'Reverted to commit: #%s.', 'revisr' ), $commit );

		revisr()->git->reset( '--hard', 'HEAD', true );
		revisr()->git->reset( '--hard', $commit );
		revisr()->git->reset( '--soft', 'HEAD@{1}' );
		revisr()->git->run( 'add', array( '-A' ) );
		revisr()->git->commit( $commit_msg );
		revisr()->git->auto_push();

		$commit_url = get_admin_url() . 'admin.php?page=revisr_view_commit&commit=' . $commit;

		$msg = sprintf( __( 'Reverted to commit <a href="%s">#%s</a>.', 'revisr' ), $commit_url, $commit );
		$email_msg = sprintf( __( '%s was reverted to commit #%s', 'revisr' ), get_bloginfo(), $commit );
		Revisr_Admin::log( $msg, 'revert' );
		Revisr_Admin::notify( get_bloginfo() . __( ' - Commit Reverted', 'revisr' ), $email_msg );

		if ( true === $redirect ) {
			Revisr_Admin::redirect();
		}

	}

	/**
	 * Processes a diff request.
	 * @access public
	 */
	public function view_diff() {

		Revisr_Admin::verify_nonce( $_GET['security'], 'staging_nonce' );

		if ( isset( $_REQUEST['commit'] ) ) {
			$diff = revisr()->git->run( 'show', array( $_REQUEST['commit'], $_REQUEST['file'] ) );
		} else {
			$diff = revisr()->git->run( 'diff', array( $_REQUEST['file'] ) );
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

		// We may need to exit early if doing_ajax.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			exit();
		}

	}

	/**
	 * Processes a view error request.
	 * @access public
	 */
	public function view_error() {

		if ( $revisr_error = get_transient( 'revisr_error_details' ) ) {
			echo implode( '<br>', $revisr_error );
		} else {
			_e( 'Detailed error information not available.', 'revisr' );
		}

	}

	/**
	 * Processes a view status request.
	 * @access public
	 */
	public function view_status() {

		Revisr_Admin::verify_nonce( $_GET['revisr_status_nonce'], 'revisr_view_status' );

		$status = revisr()->git->run( 'status', array() );

		if ( is_array( $status ) ) {
			echo '<pre>';
			foreach ( $status as $line ) {
				echo $line . PHP_EOL;
			}
			echo '</pre>';
		} else {
			_e( 'Error retrieving the status of the repository.', 'revisr' );
		}

	}

	/**
	 * Downloads the system info.
	 * @access public
	 */
	public function download_sysinfo() {

		Revisr_Admin::verify_nonce( $_REQUEST['revisr_info_nonce'], 'process_download_sysinfo' );

		if ( ! current_user_can( Revisr::get_capability() ) ) {
			return;
		}

		nocache_headers();

		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="revisr-system-info.txt"' );

		echo wp_strip_all_tags( $_POST['revisr-sysinfo'] );
		die();

	}

}
