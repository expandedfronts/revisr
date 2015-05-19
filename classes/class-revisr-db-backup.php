<?php
/**
 * class-revisr-db-backup.php
 *
 * Performs database backup operations.
 *
 * @package 	Revisr
 * @license 	GPLv3
 * @link 		https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr_DB_Backup extends Revisr_DB {

	/**
	 * Backs up a single database table using mysqldump.
	 * @access public
	 * @param  string $table The database table to backup.
	 * @return array  An array of the results.
	 */
	public function backup_table_mysql( $table ) {

		// Build the connection to use with mysqldump.
		$conn = $this->build_conn( $table );

		// Grab the path to use for mysqldump.
		$path = $this->get_path();

		// Backup the table.
		$current_dir = getcwd();
		chdir( $this->backup_dir );
		exec( "{$path}mysqldump $conn > revisr_$table.sql --skip-comments", $output, $return_code );
		chdir( $current_dir );

		// Handle any errors
		if ( 0 !== $return_code ) {
			return false;
		}

		// Add the table into version control.
		$this->add_table( $table );

		// Makes sure that the backup file exists and is not empty.
		return $this->verify_backup( $table );

	}

	/**
	 * Backs up a single database table using the WordPress database class.
	 * @access public
	 * @param  string $table The database table to backup.
	 * @return boolean
	 */
	public function backup_table_wpdb( $table ) {

		$table 			= esc_sql( $table );

		// Initialize the results, ultimately stored in the backup file.
		$results 		= '';

		// An empty array to store the queries for the table in later.
		$queries 		= array();

		// Grab the SQL needed to create the table.
		$show_create 	= $this->wpdb->get_row( "SHOW CREATE TABLE `$table`" );
		$want 			= 'Create Table';

		if ( $show_create ) {

			// Store the table schema in the backup file.
			$results .= "DROP TABLE IF EXISTS `$table`;" . PHP_EOL;
			$results .= $show_create->$want . ';' . PHP_EOL;

			// Grab the content of the database table.
			foreach ( $this->wpdb->get_results( "SELECT * FROM `$table`" ) as $row ) {

				$vals = array();

				foreach ( get_object_vars( $row ) as $i => $v ) {
					$vals[] = sprintf( "'%s'", esc_sql( $v ) );
				}

				$queries[] = sprintf( "(%s)", implode( ',', $vals ) );
			}

			if ( ! empty( $queries ) ) {
				// Implode the queries and generate the rest of the SQL file.
				$results .= "LOCK TABLES `$table` WRITE;" . PHP_EOL;
				$results .= "INSERT INTO `$table` VALUES " . implode( ', ', $queries ) . ';' . PHP_EOL;
				$results .= 'UNLOCK TABLES;' . PHP_EOL;
			}

		}

		// Store the contents of the SQL file.
		file_put_contents( $this->backup_dir . "revisr_$table.sql", $results );
		$this->add_table( $table );

		// Verify the backup was successful and return a boolean.
		return $this->verify_backup( $table );
	}

	/**
	 * Processes the results and alerts the user as necessary.
	 * @access public
	 * @param  array $args An array containing the results of the backup.
	 * @return boolean
	 */
	public function callback( $args ) {

		if ( in_array( false, $args ) ) {
			$msg = __( 'Error backing up the database.', 'revisr' );
			Revisr_Admin::alert( $msg, true );
			Revisr_Admin::log( $msg, 'error' );
		} else {
			$msg = __( 'Successfully backed up the database.', 'revisr' );
			Revisr_Admin::alert( $msg );
			Revisr_Admin::log( $msg, 'backup' );

			// Fires after a successful database backup.
			do_action( 'revisr_post_db_backup' );

			return true;
		}

		return false;
	}

}

