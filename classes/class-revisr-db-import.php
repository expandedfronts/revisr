<?php
/**
 * class-revisr-db-import.php
 *
 * Performs database import operations.
 *
 * @package 	Revisr
 * @license 	GPLv3
 * @link 		https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr_DB_Import extends Revisr_DB {

	/**
	 * Imports a single database table using MySQL directly.
	 * @access public
	 * @param  string $table The table to import
	 * @return boolean Whether it was successful.
	 */
	public function import_table_mysql( $table, $replace_url = '' ) {

		// Build the connection to use with MySQL.
		$conn = $this->build_conn();

		// Grab the path to use for MySQL.
		$path = $this->get_path();

		// Grab the site url.
		$live_url = site_url();

		// Import the table.
		$current_dir = getcwd();
		chdir( $this->backup_dir );
		exec( "{$path}mysql {$conn} < revisr_$table.sql", $output, $return_code );
		chdir( $current_dir );

		// Handle any errors.
		if ( 0 !== $return_code ) {
			Revisr_Admin::log( implode( '<br>', $output ), 'error' );
			return false;
		}

		// Run a search replace if necessary.
		if ( $replace_url !== '' && $replace_url !== false ) {
			$this->revisr_srdb( $table, $replace_url, $live_url );
		}

		// If we're still running at this point, everything worked.
		return true;
	}

	/**
	 * Imports a single database table using the WordPress database class.
	 * @access public
	 * @param  string $table The table to import
	 * @return array  An array of the results.
	 */
	public function import_table_wpdb( $table, $replace_url = '' ) {

		$live_url 	= site_url();
		$fh 		= fopen( "{$this->backup_dir}revisr_$table.sql", 'r' );
		$size		= filesize( "{$this->backup_dir}revisr_$table.sql" );
		$status		= array(
			'errors' 	=> 0,
			'updates' 	=> 0
		);

		while( !feof( $fh ) ) {
			$query = trim( stream_get_line( $fh, $size, ';' . PHP_EOL ) );
			if ( empty( $query ) ) {
				$status['dropped_queries'][] = $query;
				continue;
			}
			if ( $this->wpdb->query( $query ) === false ) {
				$status['errors']++;
				$status['bad_queries'][] = $query;
			} else {
				$status['updates']++;
				$status['good_queries'][] = $query;
			}
		}

		fclose( $fh );

		if ( '' !== $replace_url ) {
			$this->revisr_srdb( $table, $replace_url, $live_url );
		}

		if ( 0 !== $status['errors'] ) {
			return false;
		}
		return true;
	}

	/**
	 * Processes the results and alerts the user as necessary.
	 * @access public
	 * @param  array $args An array containing the results of the backup.
	 * @return boolean
	 */
	public function callback( $args ) {

		if ( in_array( false, $args ) ) {

			// There were one or more errors with the import.
			$msg = __( 'Error importing the database.', 'revisr' );
			Revisr_Admin::log( $msg, 'error' );
			Revisr_Admin::alert( $msg, true );

		} else {

			// Everything imported properly.
			$get_hash 		= revisr()->git->run( 'config', array( 'revisr.last-db-backup' ) );
			$revert_link 	= '';

			if ( is_array( $get_hash ) ) {
				$undo_hash 		= $get_hash[0];
				$undo_url 		= get_admin_url() . 'admin-post.php?action=process_revert&revert_type=db&db_hash=' . $undo_hash;
				$undo_nonced 	= wp_nonce_url( $undo_url, 'revisr_revert_nonce', 'revisr_revert_nonce' );
				$revert_link 	= '<a href="' . $undo_nonced . '">' . __( 'Undo', 'revisr') . '</a>';
				revisr()->git->run( 'config', array( '--unset', 'revisr.last-db-backup' ) );
			}

			// Alert the user.
			$msg = sprintf( __( 'Successfully imported the database. %s', 'revisr'), $revert_link );
			Revisr_Admin::log( $msg, 'import' );
			Revisr_Admin::alert( $msg );

			// Fires after a successful database import.
			do_action( 'revisr_post_db_import' );

			return true;
		}

		return false;
	}

	/**
	 * Gets the columns in a table.
	 * @access public
	 * @param  string $table The table to check.
	 * @return array
	 */
	public function get_columns( $table ) {
		$primary_key 	= null;
		$columns 		= array();
		$fields  		= $this->wpdb->get_results( 'DESCRIBE ' . $table );

		if ( is_array( $fields ) ) {
			foreach ( $fields as $column ) {
				$columns[] = $column->Field;
				if ( $column->Key == 'PRI' ) {
					$primary_key = $column->Field;
				}
			}
		}

		return array( $primary_key, $columns );
	}

	/**
	 * Adapated from interconnect/it's search/replace script.
	 * Modified to use WordPress wpdb functions instead of PHP's native mysql/pdo functions.
	 *
	 * @link https://interconnectit.com/products/search-and-replace-for-wordpress-databases/
	 *
	 * @access private
	 * @param  string $table 	The table to run the replacement on.
	 * @param  string $search 	The string to replace.
	 * @param  string $replace 	The string to replace with.
	 * @return boolean
	 */
	private function revisr_srdb( $table, $search = '', $replace = '' ) {

		$table = esc_sql( $table );

		// Get a list of columns in this table.
		list( $primary_key, $columns ) = $this->get_columns( $table );

		// Bail out early if there isn't a primary key.
		if ( null === $primary_key ) {
			return false;
		}

		// Count the number of rows we have in the table if large we'll split into blocks, This is a mod from Simon Wheatley
		$row_count = $this->wpdb->get_var( "SELECT COUNT(*) FROM $table" );
		if ( $row_count == 0 ) {
			return true;
		}

		$page_size 	= 50000;
		$pages 		= ceil( $row_count / $page_size );

		for( $page = 0; $page < $pages; $page++ ) {

			$current_row 	= 0;
			$start 			= $page * $page_size;
			$end 			= $page_size;

			// Grab the content of the table.
			$data = $this->wpdb->get_results( "SELECT * FROM $table LIMIT $start, $end", ARRAY_A );

			// Loop through the data.
			foreach ( $data as $row ) {
				$current_row++;
				$update_sql = array();
				$where_sql 	= array();
				$upd 		= false;

				foreach( $columns as $column ) {

					$data_to_fix = $row[ $column ];

					if ( $column == $primary_key ) {
						$where_sql[] = $column . ' = "' .  $this->mysql_escape_mimic( $data_to_fix ) . '"';
						continue;
					}

					// Run a search replace on the data that'll respect the serialisation.
					$edited_data = $this->recursive_unserialize_replace( $search, $replace, $data_to_fix );

					// Something was changed
					if ( $edited_data != $data_to_fix ) {
						$update_sql[] = $column . ' = "' . $this->mysql_escape_mimic( $edited_data ) . '"';
						$upd = true;
					}

				}

				if ( $upd && ! empty( $where_sql ) ) {
					$sql = 'UPDATE ' . $table . ' SET ' . implode( ', ', $update_sql ) . ' WHERE ' . implode( ' AND ', array_filter( $where_sql ) );
					$result = $this->wpdb->query( $sql );
					if ( ! $result ) {
						$error_msg = sprintf( __( 'Error updating the table: %s.', 'revisr' ), $table );
					}
				}

			}

		}

		$this->wpdb->flush();
		if ( isset( $error_msg ) ) {
			Revisr_Admin::log( $error_msg, 'error' );
			return false;
		}

	}

	/**
	 * Adapated from interconnect/it's search/replace script.
	 *
	 * @link https://interconnectit.com/products/search-and-replace-for-wordpress-databases/
	 *
	 * Take a serialised array and unserialise it replacing elements as needed and
	 * unserialising any subordinate arrays and performing the replace on those too.
	 *
	 * @access private
	 * @param  string $from       String we're looking to replace.
	 * @param  string $to         What we want it to be replaced with.
	 * @param  array  $data       Used to pass any subordinate arrays back to in.
	 * @param  bool   $serialised Does the array passed via $data need serialising.
	 *
	 * @return string|array	The original array with all elements replaced as needed.
	 */
	private function recursive_unserialize_replace( $from = '', $to = '', $data = '', $serialised = false ) {
		try {

			if ( is_string( $data ) && ( $unserialized = @unserialize( $data ) ) !== false ) {
				$data = $this->recursive_unserialize_replace( $from, $to, $unserialized, true );
			}

			elseif ( is_array( $data ) ) {
				$_tmp = array( );
				foreach ( $data as $key => $value ) {
					$_tmp[ $key ] = $this->recursive_unserialize_replace( $from, $to, $value, false );
				}

				$data = $_tmp;
				unset( $_tmp );
			}

			// Submitted by Tina Matter
			elseif ( is_object( $data ) ) {
				// $data_class = get_class( $data );
				$_tmp = $data; // new $data_class( );
				$props = get_object_vars( $data );
				foreach ( $props as $key => $value ) {
					$_tmp->$key = $this->recursive_unserialize_replace( $from, $to, $value, false );
				}

				$data = $_tmp;
				unset( $_tmp );
			}

			else {
				if ( is_string( $data ) )
					$data = str_replace( $from, $to, $data );
			}

			if ( $serialised )
				return serialize( $data );

		} catch( Exception $error ) {
			Revisr_Admin::log( $error, 'error' );
		}

		return $data;
	}

}
