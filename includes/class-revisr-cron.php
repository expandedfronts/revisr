<?php
/**
 * class-revisr-cron.php
 *
 * Processes scheduled events for Revisr.
 *
 * @package   	Revisr
 * @license   	GPLv3
 * @link      	https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr_Cron {

	/**
	 * A reference back to the main Revisr instance.
	 * @var object
	 */
	protected $revisr;

	/**
	 * Sets up the class.
	 * @access public
	 */
	public function __construct() {
		$this->revisr = revisr();
	}

	/**
	 * Creates new schedules.
	 * @access public
	 * @param  array $schedules An array of available schedules.
	 */
	public function revisr_schedules( $schedules ) {
		// Adds weekly backups
		$schedules['weekly'] = array(
			'interval' => 604800,
			'display'  => __( 'Weekly', 'revisr' )
		);
		return $schedules;
	}

	/**
	 * The main "automatic backup" event.
	 * @access public
	 */
	public function run_automatic_backup() {
		$this->revisr->git 	= new Revisr_Git();
		$this->revisr->db 	= new Revisr_DB();

		$date 				= date("F j, Y");
		$files 				= $this->revisr->git->status();
		$backup_type 		= ucfirst( $this->revisr->options['automatic_backups'] );
		$commit_msg 		= sprintf( __( '%s backup - %s', 'revisr' ), $backup_type, $date );

		// In case there are no files to commit.
		if ( $files == false ) {
			$files = array();
		}

		$this->revisr->git->stage_files( $files );
		$this->revisr->git->commit( $commit_msg );
		$post = array(
			'post_title'	=> $commit_msg,
			'post_content'	=> '',
			'post_type'		=> 'revisr_commits',
			'post_status'	=> 'publish',
		);
		$post_id = wp_insert_post( $post );
		add_post_meta( $post_id, 'branch', $this->revisr->git->branch );
		add_post_meta( $post_id, 'commit_hash', $this->revisr->git->current_commit() );
		add_post_meta( $post_id, 'files_changed', count( $files ) );
		add_post_meta( $post_id, 'committed_files', $files );
		$this->revisr->db->backup();
		add_post_meta( $post_id, 'db_hash', $this->revisr->git->current_commit() );
		$log_msg = sprintf( __( 'The %s backup was successful.', 'revisr' ), $this->revisr->options['automatic_backups'] );
		Revisr_Admin::log( $log_msg, 'backup' );
	}

	/**
	 * Processes the "auto-pull" functionality.
	 * @access public
	 */
	public function run_autopull() {
		$this->revisr->git = new Revisr_Git();

		// If auto-pull isn't enabled, we definitely don't want to do this.
		if ( $this->revisr->git->get_config( 'revisr', 'auto-pull' ) !== 'true' ) {
			wp_die( __( 'Cheatin&#8217; uh?', 'revisr' ) );
		}

		// Verify the provided token matches the token stored locally.
		$remote = new Revisr_Remote();
		$remote->check_token();

		// If we're still running at this point, we've successfully authenticated.
		$this->revisr->git->reset();
		$this->revisr->git->fetch();

		// Grab the commits that need to be pulled.
		$commits_since = $this->revisr->git->run( 'log', array( $this->revisr->git->branch . '..' . $this->revisr->git->remote . '/' . $this->revisr->git->branch, '--pretty=oneline' ) );

		// Maybe backup the database.
		if ( $this->revisr->git->get_config( 'revisr', 'import-pulls' ) === 'true' ) {
			$this->revisr->db = new Revisr_DB();
			$this->revisr->db->backup();
			$undo_hash = $this->revisr->git->current_commit();
			$this->revisr->git->set_config( 'revisr', 'last-db-backup', $undo_hash );
		}

		// Pull the changes or return an error on failure.
		$this->revisr->git->pull( $commits_since );

	}
}
