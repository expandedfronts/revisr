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

// Disallow direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr_Process {

	/**
	 * A reference back to the main Revisr instance.
	 * @var object
	 */
	protected $revisr;

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
		$this->revisr 	= Revisr::get_instance();
		$this->options 	= Revisr::get_options();
	}

	/**
	 * Checks if a the current WordPress site is a repository,
	 * and returns a link to create a new repository if not.
	 * @access public
	 * @return boolean
	 */
	public function process_is_repo() {
		if ( $this->revisr->git->is_repo ) {
			return true;
		} else {
			$init_url 	= wp_nonce_url( get_admin_url() . 'admin-post.php?action=init_repo', 'init_repo', 'revisr_init_nonce' );
			$alert 		= sprintf( __( 'Thanks for installing Revisr! No Git repository was detected, <a href="%s">click here</a> to create one.', 'revisr' ), $init_url );
			Revisr_Admin::alert( $alert );
		}
		return false;
	}

	/**
	 * Processes the request to checkout an existing branch.
	 * @access public
	 */
	public function process_checkout( $args = '', $new_branch = false ) {

		if ( $this->revisr->git->get_config( 'revisr', 'import-checkouts' ) === 'true' ) {
			$this->revisr->db->backup();
		}

		if ( $args == '' ) {
			$branch = $_REQUEST['branch'];
		} else {
			$branch = $args;
		}

		$this->revisr->git->reset();
		$this->revisr->git->checkout( $branch );

		if ( $this->revisr->git->get_config( 'revisr', 'import-checkouts' ) === 'true' && $new_branch === false ) {
			$this->revisr->db->import();
		}
		$url = get_admin_url() . 'admin.php?page=revisr';
		wp_redirect( $url );
	}

	/**
	 * Processes a new commit from the "New Commit" admin page.
	 * @access public
	 */
	public function process_commit() {
		if ( isset( $_REQUEST['_wpnonce'] ) && isset( $_REQUEST['_wp_http_referer'] ) ) {

			$id 			= get_the_ID();
			$commit_msg 	= $_REQUEST['post_title'];
			$post_new 		= get_admin_url() . 'post-new.php?post_type=revisr_commits';

			// Require a message to be entered for the commit.
			if ( $commit_msg == 'Auto Draft' || $commit_msg == '' ) {
				wp_redirect( $post_new . '&message=42' );
				exit();
			}

			// Stage any necessary files, or cancel if none are found.
			if ( isset( $_POST['staged_files'] ) ) {
				$this->revisr->git->stage_files( $_POST['staged_files'] );
				$staged_files = $_POST['staged_files'];
			} else {
				wp_redirect( $post_new . '&message=43' );
				exit();
			}

			// Add the necessary post meta and make the commit in Git.
			add_post_meta( $id, 'committed_files', $staged_files );
			add_post_meta( $id, 'files_changed', count( $staged_files ) );
			$this->revisr->git->commit( $commit_msg, 'commit' );
		}
	}

	/**
	 * Processes the request to create a new branch.
	 * @access public
	 */
	public function process_create_branch() {
		$branch = $_REQUEST['branch_name'];
		$result = $this->revisr->git->create_branch( $branch );

		if ( $result !== false ) {
			$msg = sprintf( __( 'Created new branch: %s', 'revisr' ), $branch );
			Revisr_Admin::log( $msg, 'branch' );

			if ( isset( $_REQUEST['checkout_new_branch'] ) ) {
				$this->revisr->git->checkout( $branch );
			}

			wp_redirect( get_admin_url() . 'admin.php?page=revisr_branches&status=create_success&branch=' . $branch );
		} else {
			wp_redirect( get_admin_url() . 'admin.php?page=revisr_branches&status=create_error&branch=' . $branch );
		}

		exit();
	}

	/**
	 * Processes the request to delete an existing branch.
	 * @access public
	 */
	public function process_delete_branch() {
		if ( isset( $_POST['branch'] ) && $_POST['branch'] != $this->revisr->git->branch ) {
			$branch = $_POST['branch'];
			$this->revisr->git->delete_branch( $branch );
			if ( isset( $_POST['delete_remote_branch'] ) ) {
				$this->revisr->git->run( "push {$this->revisr->git->remote} --delete {$branch}" );
			}
		}
		exit();
	}

	/**
	 * Processes the request to discard all untracked changes.
	 * @access public
	 */
	public function process_discard() {
		if ( wp_verify_nonce( $_REQUEST['revisr_dashboard_nonce'], 'revisr_dashboard_nonce' ) ) {
			$this->revisr->git->reset( '--hard', 'HEAD', true );
			Revisr_Admin::log( __('Discarded all uncommitted changes.', 'revisr'), 'discard' );
			Revisr_Admin::alert( __('Successfully discarded any uncommitted changes.', 'revisr') );
			exit();
		}
	}

	/**
	 * Processes a Git init.
	 * @access public
	 */
	public function process_init() {
		if ( ! wp_verify_nonce( $_REQUEST['revisr_init_nonce'], 'init_repo' ) ) {
			wp_die( 'Cheatin&#8217; uh?', 'revisr' );
		}
		$this->revisr->git->init_repo();
	}

	/**
	 * Processes the import of additional (new) tables.
	 * @access public
	 */
	public function process_import() {
		if ( isset( $_REQUEST['revisr_import_untracked'] ) && is_array( $_REQUEST['revisr_import_untracked'] ) ) {
			$this->revisr->db->import( $_REQUEST['revisr_import_untracked'] );
			_e( 'Importing...', 'revisr' );
			echo "<script>
					window.top.location.href = '" . get_admin_url() . "admin.php?page=revisr';
			</script>";
		}
	}

	/**
	 * Processes the request to merge a branch into the current branch.
	 * @access public
	 */
	public function process_merge() {
		$this->revisr->git->merge( $_REQUEST['branch'] );
		if ( isset( $_REQUEST['import_db'] ) && $_REQUEST['import_db'] == 'on' ) {
			$this->revisr->db->import();
		}
	}

	/**
	 * Processes the request to pull changes into the current branch.
	 * @access public
	 */
	public function process_pull() {
		if ( ! wp_verify_nonce( $_REQUEST['revisr_dashboard_nonce'], 'revisr_dashboard_nonce' ) ) {
			wp_die( __( 'Cheatin&#8217; uh?', 'revisr' ) );
		}

		$this->revisr->git->reset();
		$this->revisr->git->fetch();

		$commits_since = $this->revisr->git->run( 'log', array( $this->revisr->git->branch . '..' . $this->revisr->git->remote . '/' . $this->revisr->git->branch, '--pretty=oneline' ) );

		if ( is_array( $commits_since ) ) {
			// Iterate through the commits to pull and add them to the database.
			foreach ( $commits_since as $commit ) {
				$commit_hash = substr( $commit, 0, 7 );
				$commit_msg = substr( $commit, 40 );
				$show_files = $this->revisr->git->run( 'show', array( '--pretty=format:', '--name-status', $commit_hash ) );

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
					add_post_meta( $post_id, 'branch', $this->revisr->git->branch );
					add_post_meta( $post_id, 'files_changed', count( $files_changed ) );
					add_post_meta( $post_id, 'committed_files', $files_changed );

					$view_link = get_admin_url() . "post.php?post=$post_id&action=edit";
					$msg = sprintf( __( 'Pulled <a href="%s">#%s</a> from %s/%s.', 'revisr' ), $view_link, $commit_hash, $this->revisr->git->remote, $this->revisr->git->branch );
					Revisr_Admin::log( $msg, 'pull' );
				}
			}
		}

		if ( $this->revisr->git->get_config( 'revisr', 'import-pulls' ) === 'true' ) {
			$this->revisr->db->backup();
			$undo_hash = $this->revisr->git->current_commit();
			$this->revisr->git->set_config( 'revisr', 'last-db-backup', $undo_hash );
		}
		// Pull the changes or return an error on failure.
		$this->revisr->git->pull();
	}

	/**
	 * Processes the request to push changes to a remote repository.
	 * @access public
	 */
	public function process_push() {
		if ( wp_verify_nonce( $_REQUEST['revisr_dashboard_nonce'], 'revisr_dashboard_nonce' ) ) {
			$this->revisr->git->push();
		}
	}

	/**
	 * Processes a request to revert, routing to the necessary functions.
	 * @access public
	 * @param  string $type What to revert
	 * @return null
	 */
	public function process_revert( $type = '' ) {
		if ( ! wp_verify_nonce( $_REQUEST['revisr_revert_nonce'], 'revisr_revert_nonce' ) ) {
			wp_die( __( 'Cheatin&#8217; uh?', 'revisr' ) );
		}

		// Determine how to handle the request.
		if ( isset( $_REQUEST['revert_type'] ) && $_REQUEST['revert_type'] !== '' ) {
			$revert_type = $_REQUEST['revert_type'];
		} else {
			$revert_type = $type;
		}

		// Run the action.
		switch ( $revert_type ) {
			case 'files':
				$this->process_revert_files( false );
				break;
			case 'db':
				$this->revisr->db->restore( false );
				break;
			case 'files_and_db':
				$this->process_revert_files( false );
				$this->revisr->db->restore( false );
				break;
			default:
		}

		if ( isset( $_REQUEST['echo_redirect'] ) ) {
			_e( 'Revert completed. Redirecting...', 'revisr' );
			echo "<script>window.top.location.href = '" . get_admin_url() . "admin.php?page=revisr';</script>";
		} else {
			wp_redirect( get_admin_url() . 'admin.php?page=revisr' );
		}
	}

	/**
	 * Processes the request to revert to an earlier commit.
	 * @access public
	 */
	public function process_revert_files( $redirect = true ) {
		if ( ! wp_verify_nonce( $_REQUEST['revisr_revert_nonce'], 'revisr_revert_nonce' ) ) {
			wp_die( __( 'Cheatin&#8217; uh?', 'revisr' ) );
		}

		$branch 	= $_REQUEST['branch'];
		$commit 	= $_REQUEST['commit_hash'];
		$commit_msg = sprintf( __( 'Reverted to commit: #%s.', 'revisr' ), $commit );

		if ( $branch != $this->revisr->git->branch ) {
			$this->revisr->git->checkout( $branch );
		}

		$this->revisr->git->reset( '--hard', 'HEAD', true );
		$this->revisr->git->reset( '--hard', $commit );
		$this->revisr->git->reset( '--soft', 'HEAD@{1}' );
		$this->revisr->git->run( 'add', array( '-A' ) );
		$this->revisr->git->commit( $commit_msg );
		$this->revisr->git->auto_push();

		$post_url = get_admin_url() . "post.php?post=" . $_REQUEST['post_id'] . "&action=edit";

		$msg = sprintf( __( 'Reverted to commit <a href="%s">#%s</a>.', 'revisr' ), $post_url, $commit );
		$email_msg = sprintf( __( '%s was reverted to commit #%s', 'revisr' ), get_bloginfo(), $commit );
		Revisr_Admin::log( $msg, 'revert' );
		Revisr_Admin::notify( get_bloginfo() . __( ' - Commit Reverted', 'revisr' ), $email_msg );

		if ( true === $redirect ) {
			$redirect = get_admin_url() . "admin.php?page=revisr";
			wp_redirect( $redirect );
		}
	}
}
