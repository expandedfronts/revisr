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

/** If uninstall not called from WordPress, exit. */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/** Remove any set cronjobs. */
wp_clear_scheduled_hook( 'revisr_cron' );

/** Delete the Revisr database table. */
global $wpdb;
$table_name = $wpdb->prefix . 'revisr';
$sql 		= "DROP TABLE $table_name";
$wpdb->query( $sql );
