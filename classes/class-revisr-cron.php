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

		revisr()->git 	= new Revisr_Git();
		revisr()->db 	= new Revisr_DB();
		$backup_type 	= revisr()->git->get_config( 'revisr', 'automatic-backups' ) ? revisr()->git->get_config( 'revisr', 'automatic-backups' ) : 'none';

		// Make sure backups have been enabled for this environment.
		if ( 'none' !== $backup_type ) {

			// Defaults.
			$date 			= date("F j, Y");
			$files 			= revisr()->git->status() ? revisr()->git->status() : array();
			$commit_msg 	= sprintf( __( '%s backup - %s', 'revisr' ), ucfirst( $backup_type ), $date );

			// Stage the files and commit.
			revisr()->git->stage_files( $files );
			revisr()->git->commit( $commit_msg );

			// Backup the DB.
			revisr()->db->backup();

			// Log result - TODO: improve error handling as necessary.
			$log_msg = sprintf( __( 'The %s backup was successful.', 'revisr' ), $backup_type );
			Revisr_Admin::log( $log_msg, 'backup' );

		}

	}

	/**
	 * Processes the "auto-pull" functionality.
	 * @access public
	 */
	public function run_autopull() {

		revisr()->git = new Revisr_Git();

		// If auto-pull isn't enabled, we definitely don't want to do this.
		if ( revisr()->git->get_config( 'revisr', 'auto-pull' ) !== 'true' ) {
			wp_die( __( 'Cheatin&#8217; uh?', 'revisr' ) );
		}

		// Verify the provided token matches the token stored locally.
		$remote = new Revisr_Remote();
		$remote->check_token();

		// If we're still running at this point, we've successfully authenticated.
		revisr()->git->reset();
		revisr()->git->fetch();

		// Grab the commits that need to be pulled.
		$commits_since = revisr()->git->run( 'log', array( revisr()->git->branch . '..' . revisr()->git->remote . '/' . revisr()->git->branch, '--pretty=oneline' ) );

		// Maybe backup the database.
		if ( revisr()->git->get_config( 'revisr', 'import-pulls' ) === 'true' ) {
			revisr()->db = new Revisr_DB();
			revisr()->db->backup();
			$undo_hash = revisr()->git->current_commit();
			revisr()->git->set_config( 'revisr', 'last-db-backup', $undo_hash );
		}

		// Pull the changes or return an error on failure.
		revisr()->git->pull( $commits_since );

	}
}
