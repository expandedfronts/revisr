<?php
/**
 * uninstall.php
 *
 * Fired when the plugin is deleted.
 *
 * @package   	Revisr
 * @license   	GPLv3
 * @link      	https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Exit if uninstall not called from WP.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

// Load main plugin file.
include_once( 'revisr.php' );

if ( revisr()->options['uninstall_on_delete'] ) {

	// Remove any set crons.
	wp_clear_scheduled_hook( 'revisr_cron' );

	// Remove any set options.
	delete_option( 'revisr_settings' );
	delete_option( 'revisr_general_settings' );
	delete_option( 'revisr_remote_settings' );
	delete_option( 'revisr_database_settings' );

	// Remove any set transients.
	delete_transient( 'revisr_error_details' );
	delete_transient( 'revisr_error' );
	delete_transient( 'revisr_alert' );
	delete_transient( 'revisr_skip_setup' );

	// Delete any commits.
	$commits = get_posts( array( 'post_type' => 'revisr_commits', 'post_status' => 'any', 'number_posts' => -1, 'fields' => 'ids' ) );
	foreach ( $commits as $commit ) {
		wp_delete_post( $commit, true );
	}

	// Drop the Revisr database table.
	global $wpdb;
	$table_name = $wpdb->prefix . 'revisr';
	$sql 		= "DROP TABLE $table_name";
	$wpdb->query( $sql );

}
