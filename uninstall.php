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
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

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

// Drop the Revisr database table.
global $wpdb;
$table_name = $wpdb->prefix . 'revisr';
$sql 		= "DROP TABLE $table_name";
$wpdb->query( $sql );
