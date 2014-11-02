<?php
/**
 * class-revisr-cron.php
 *
 * Processes scheduled events for Revisr.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 */

// Disallow direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr_Cron {

	/**
	 * The Revisr database class.
	 */
	protected $db;

	/**
	 * The main Git class.
	 */
	protected $git;

	/**
	 * User options and preferences.
	 */
	protected $options;

	/**
	 * Sets up the class.
	 * @access public
	 */
	public function __construct() {
		$this->db 		= new Revisr_DB();
		$this->git 		= new Revisr_Git();
		$this->options 	= Revisr::get_options();
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
		$date 			= date("F j, Y");
		$files 			= $this->git->status();
		$backup_type 	= ucfirst( $this->options['automatic_backups'] );
		$commit_msg 	= sprintf( __( '%s backup - %s', 'revisr' ), $backup_type, $date );
		// In case there are no files to commit.
		if ( $files == false ) {
			$files = array();
		}
		$this->git->stage_files( $files );
		$this->git->commit( $commit_msg );
		$post = array(
			'post_title'	=> $commit_msg,
			'post_content'	=> '',
			'post_type'		=> 'revisr_commits',
			'post_status'	=> 'publish',
		);
		$post_id = wp_insert_post( $post );
		add_post_meta( $post_id, 'branch', $this->git->branch );
		add_post_meta( $post_id, 'commit_hash', $this->git->current_commit() );
		add_post_meta( $post_id, 'files_changed', count( $files ) );
		add_post_meta( $post_id, 'committed_files', $files );
		$this->db->backup();
		add_post_meta( $post_id, 'db_hash', $this->git->current_commit() );
		$log_msg = sprintf( __( 'The %s backup was successful.', 'revisr' ), $this->options['automatic_backups'] );
		Revisr_Admin::log( $log_msg, 'backup' );
	}
}