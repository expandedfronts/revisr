<?php
/**
 * class-revisr-process.php
 *
 * Processes user actions and delegates work to the correct class.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 */

// Disallow direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr_Process {

	/**
	 * The Revisr database class.
	 * @var Revisr_DB()
	 */
	protected $db;

	/**
	 * The Revisr Git class.
	 * @var Revisr_Git()
	 */
	protected $git;

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
		$this->db 		= new Revisr_DB();
		$this->git 		= new Revisr_Git();
		$this->options 	= Revisr::get_options();
	}

	/**
	 * Checks if a the current WordPress site is a repository,
	 * and returns a link to create a new repository if not.
	 * @access public
	 * @return boolean
	 */
	public function process_is_repo() {
		if ( $this->git->is_repo() ) {
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
			$this->db->import();
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
			$commit_msg 	= $_REQUEST['post_title'];
			$post_new 		= get_admin_url() . 'post-new.php?post_type=revisr_commits';
			
			// Require a message to be entered for the commit.
			if ( $commit_msg == 'Auto Draft' || $commit_msg == '' ) {
				$url = $post_new . '&message=42';
				wp_redirect( $url );
				exit();
			}

			// Stage any necessary files, or cancel if none are found.
			if ( isset( $_POST['staged_files'] ) ) {
				$this->git->stage_files( $_POST['staged_files'] );
				$staged_files = $_POST['staged_files'];
			} else {
				$url = $post_new . '&message=43';
				wp_redirect( $url );
				exit();
			}

			// Add the necessary post meta and make the commit in Git.
			add_post_meta( get_the_ID(), 'committed_files', $staged_files );
			add_post_meta( get_the_ID(), 'files_changed', count( $staged_files ) );
			$this->git->commit( $commit_msg, 'commit' );	
		}
	}
	
	/**
	 * Processes the request to create a new branch.
	 * @access public
	 */
	public function process_create_branch() {
		$branch = $_REQUEST['branch_name'];
		$result = $this->git->create_branch( $branch );
		if ( isset( $_REQUEST['checkout_new_branch'] ) ) {
			$this->git->checkout( $branch );
		}
		if ( $result !== false ) {
			$msg = sprintf( __( 'Created new branch: %s', 'revisr' ), $branch );
			Revisr_Admin::log( $msg, 'branch' );
			wp_redirect( get_admin_url() . 'admin.php?page=revisr_branches&status=create_success&branch=' . $branch );
		} else {
			wp_redirect( get_admin_url() . 'admin.php?page=revisr_branches&status=create_error&branch=' . $branch );
		}
	}
	
	/**
	 * Processes the request to delete an existing branch.
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
	 * Processes the request to discard all untracked changes.
	 * @access public
	 */
	public function process_discard() {
		$this->git->reset( '--hard', 'HEAD', true );
		Revisr_Admin::log( __('Discarded all uncommitted changes.', 'revisr'), 'discard' );
		Revisr_Admin::alert( __('Successfully discarded any uncommitted changes.', 'revisr') );
		exit();
	}

	/**
	 * Processes a Git init.
	 * @access public
	 */
	public function process_init() {
		if ( ! wp_verify_nonce( $_REQUEST['revisr_init_nonce'], 'init_repo' ) ) {
			wp_die( 'Cheatin&#8217; uh?', 'revisr' );
		}
		$this->git->init_repo();
	}

	/**
	 * Processes the request to merge a branch into the current branch.
	 * @access public
	 */
	public function process_merge() {
		$this->git->merge( $_REQUEST['branch'] );
		if ( isset( $_REQUEST['import_db'] ) && $_REQUEST['import_db'] == 'on' ) {
			$this->db->import();
		}
	}
	
	/**
	 * Processes the request to pull changes into the current branch.
	 * @access public
	 */
	public function process_pull() {
		// Determine whether this is a request from the dashboard or a POST request.
		$from_dash = check_ajax_referer( 'dashboard_nonce', 'security', false );
		if ( $from_dash == false ) {
			if ( ! isset( $this->options['auto_pull'] ) ) {
				wp_die( __( 'Cheatin&#8217; uh?', 'revisr' ) );
			}
			$remote = new Revisr_Remote();
			$remote->check_token();
		}

		$this->git->reset();
		$this->git->fetch();

		$commits_since  = $this->git->run( "log {$this->git->branch}..{$this->git->remote}/{$this->git->branch} --pretty=oneline" );

		if ( is_array( $commits_since ) ) {
			// Iterate through the commits to pull and add them to the database.
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
		if ( isset( $this->options['import_db'] ) ) {
			$this->db->backup();
			$undo_hash = $this->git->current_commit();
			$this->git->run( "config --add revisr.last-db-backup $undo_hash" );
		}
		// Pull the changes or return an error on failure.
		$this->git->pull();
	}
	
	/**
	 * Processes the request to push changes to a remote repository.
	 * @access public
	 */
	public function process_push() {
		$this->git->reset();
		$this->git->push();
	}
	
	/**
	 * Processes the request to revert to an earlier commit.
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
}