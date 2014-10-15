<?php
/**
 * class-revisr-db.php
 *
 * Performs database backup and restore operations.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 */

class Revisr_DB {

	/**
	 * The connection to use for the mysql/mysqldump commands.
	 */
	private $conn;

	/**
	 * The current working directory.
	 */
	private $dir;

	/**
	 * The main Git class.
	 */
	private $git;

	/**
	 * The WordPress database class.
	 */
	private $wpdb;

	/**
	 * Initialize the class.
	 * @access public
	 */
	public function __construct() {
		global $wpdb;
		$this->options 		= Revisr::get_options();
		$this->upload_dir 	= wp_upload_dir();
		$this->wpdb 		= $wpdb;
		$this->dir 			= getcwd();
		$this->git 			= new Revisr_Git();
		$this->setup_env();
		if ( isset( $this->options['mysql_path'] ) ) {
			$this->path = $this->options['mysql_path'];
		} else {
			$this->path = '';
		}
	}

	/**
	 * Any necessary cleanup.
	 * @access public
	 */
	public function __destruct() {
		chdir( $this->dir );
	}

	/**
	 * Backs up the entire database.
	 * @access public
	 */
	public function backup() {
		$tables = $this->get_tables();
		foreach ( $tables as $table ) {
			$backup_status[$table] = $this->backup_table( $table );
			$this->git->run( "add {$this->upload_dir['basedir']}/revisr-backups/revisr_$table.sql" );
		}
		if ( ! in_array( false, $backup_status) ) {
			$msg = __( 'Successfully backed up the database.', 'revisr' );
			$commit_msg = __( 'Backed up the database with Revisr.', 'revisr' );
			$this->git->commit( $commit_msg );

			//Insert the "revisr_commits" post if necessary.
			if ( isset( $_REQUEST['source'] ) && $_REQUEST['source'] == 'ajax_button' ) {
				$post = array(
					'post_title' 	=> $commit_msg,
					'post_content' 	=> '',
					'post_type' 	=> 'revisr_commits',
					'post_status' 	=> 'publish',
				);
				$post_id 		= wp_insert_post( $post );
				$commit_hash 	= $this->git->current_commit();
				add_post_meta( $post_id, 'commit_hash', $commit_hash );
				add_post_meta( $post_id, 'db_hash', $commit_hash );
				add_post_meta( $post_id, 'branch', $this->git->branch );
				add_post_meta( $post_id, 'files_changed', '0' );
				add_post_meta( $post_id, 'committed_files', array() );
			}
		} else {
			$msg = __( 'Error backing up the database.', 'revisr' );
		}
		Revisr_Admin::log( $msg, 'backup' );
	}

	/**
	 * Backs up a database table.
	 * @access public
	 * @param  string $table The table to backup.
	 * @return bool
	 */
	public function backup_table( $table ) {
		$conn = $this->build_conn( $table );
		exec( "{$this->path}mysqldump $conn > revisr_$table.sql" );
		return $this->verify_backup( $table );
	}

	/**
	 * Builds the database connection.
	 * @access 	private
	 * @param 	string $table Optional table name to add to the connection.
	 * @return 	string
	 */
	private function build_conn( $table = '' ) {
		if ( $this->check_port( DB_HOST ) != false ) {
			$port 		= $this->check_port( DB_HOST );
			$add_port 	= " --port=$port";
			$temp 		= strlen($port) * -1 - 1;
			$db_host 	= substr( DB_HOST, 0, $temp );
		} else {
			$add_port 	= '';
			$db_host 	= DB_HOST;
		}
		if ( $table != '' ) {
			$table = " $table";
		}
		if ( DB_PASSWORD != '' ) {
			$conn = "-u '" . DB_USER . "' -p'" . DB_PASSWORD . "' " . DB_NAME . $table . " --host " . $db_host . $add_port;
		} else {
			$conn = "-u '" . DB_USER . "' " . DB_NAME . $table . " --host " . $db_host . $add_port;
		}
		return $conn;
	}

	/**
	 * Checks if a given host is using a port, if so, return the port.
	 * @access public
	 * @param  string $url The URL to check.
	 * @return string
	 */
	public function check_port( $url ) {
		$parsed_url = parse_url( $url );
		if ( isset( $parsed_url['port'] ) && $parsed_url['port'] != '' ) {
			return $parsed_url['port'];
		} else {
			return false;
		}
	}

	/**
	 * Returns a list of tables in the database.
	 * @access public
	 * @return array
	 */
	public function get_tables() {
		$tables = $this->wpdb->get_col( 'SHOW TABLES' );
		if ( is_array( $tables ) ) {
			return $tables;
		}
	}

	/**
	 * Restores all database tables.
	 * @access public
	 * @return bool
	 */
	public function restore() {
		$tables = $this->get_tables();
		foreach ( $tables as $table ) {
			$this->restore_table( $table );
		}
		Revisr_Admin::log( __( 'Successfully imported the database.', 'revisr' ), 'restore' );
	}

	/**
	 * Restores/imports a single database table.
	 * @access public
	 * @param string $table The table to import
	 * @return bool
	 */
	public function restore_table( $table ) {
		if ( isset($_GET['revert_db_nonce']) && wp_verify_nonce( $_GET['revert_db_nonce'], 'revert_db' ) ) {
			$branch = $_GET['branch'];
			if ( $branch != $this->git->branch ) {
				$this->git->checkout( $branch );
			}
			$this->backup();
			$commit 		= escapeshellarg( $_GET['db_hash'] );
			$current_temp	= $this->git->run( "log --pretty=format:'%h' -n 1" );
			$checkout 		= $this->git->run( "checkout {$commit} {$this->upload_dir['basedir']}/revisr-backups/revisr_$table.sql" );

			if ( $checkout !== 1 ) {
				
				exec( "{$this->path}mysql {$this->conn} < {$this->sql_file}" );
				//$this->revisr_srdb_replacer( $table, $options['dev_url'], $options['live_url'] );
				$this->git->run( "checkout {$this->branch} {$this->upload_dir['basedir']}/revisr-backups/revisr_$table.sql" );
				
				if ( is_array( $current_temp ) ) {

					$current_commit = str_replace( "'", "", $current_temp );
					$undo_nonce 	= wp_nonce_url( admin_url( "admin-post.php?action=revert_db&db_hash={$current_commit[0]}&branch={$_GET['branch']}" ), 'revert_db', 'revert_db_nonce' );
					$msg 			= sprintf( __( 'Successfully reverted the database to a previous commit. <a href="%s">Undo</a>', 'revisr' ), $undo_nonce );
					Revisr_Admin::log( $msg, 'revert' );
					Revisr_Admin::alert( $msg );

					$redirect = get_admin_url() . "admin.php?page=revisr";
					wp_redirect( $redirect );			
				} else {
					wp_die( __( 'Something went wrong. Check your settings and try again.', 'revisr' ) );
				}
			} else {
				wp_die( __( 'Failed to revert the database to an earlier commit.', 'revisr' ) );
			}	
		}
	}

	/**
	 * Adapated from interconnect/it's search/replace script.
	 * Modified to use WordPress wpdb functions instead of PHP's native mysql() functions.
	 * 
	 * @link https://interconnectit.com/products/search-and-replace-for-wordpress-databases/
	 * 
	 * @access public
	 * @param string $table 	The table to run the replacement on.
	 * @param string $search 	The string to replace.
	 * @param string $replace 	The string to replace with.
	 * @return array   			Collection of information gathered during the run.
	 */
	public function revisr_srdb_replacer( $table, $search = '', $replace = '' ) {

		//Get a list of columns in this table.
		$columns = array();
		$fields  = $this->wpdb->get_results( 'DESCRIBE ' . $table );
		foreach ( $fields as $column ) {
			$columns[$column->Field] = $column->Key == 'PRI' ? true : false;
		}
		$this->wpdb->flush();

		//Count the number of rows we have in the table if large we'll split into blocks, This is a mod from Simon Wheatley
		$this->wpdb->get_results( 'SELECT COUNT(*) FROM ' . $table );
		$row_count = $this->wpdb->num_rows;
		if ( $row_count == 0 )
			continue;

		$page_size = 50000;
		$pages = ceil( $row_count / $page_size );

		for( $page = 0; $page < $pages; $page++ ) {

			$current_row 	= 0;
			$start 			= $page * $page_size;
			$end 			= $start + $page_size;
			
			// Grab the content of the table.
			$data = $this->wpdb->get_results( "SELECT * FROM $table LIMIT $start, $end", ARRAY_A );
			
			//Loop through the data.
			foreach ( $data as $row ) {
				$current_row++;
				$update_sql = array();
				$where_sql 	= array();
				$upd 		= false;

				foreach( $columns as $column => $primary_key ) {
					$edited_data = $data_to_fix = $row[ $column ];

					// Run a search replace on the data that'll respect the serialisation.
					$edited_data = $this->recursive_unserialize_replace( $search, $replace, $data_to_fix );

					// Something was changed
					if ( $edited_data != $data_to_fix ) {
						$update_sql[] = $column . ' = "' . $this->mysql_escape_mimic( $edited_data ) . '"';
						$upd = true;
					}

					if ( $primary_key )
						$where_sql[] = $column . ' = "' .  $this->mysql_escape_mimic( $data_to_fix ) . '"';
				}

				if ( $upd && ! empty( $where_sql ) ) {
					$sql = 'UPDATE ' . $table . ' SET ' . implode( ', ', $update_sql ) . ' WHERE ' . implode( ' AND ', array_filter( $where_sql ) );
					$result = $this->wpdb->query( $sql );
					if ( ! $result ) {
						$error_msg = sprintf( __( 'Error updating the table: %s.', 'revisr' ), $table );
					}
				} elseif ( $upd ) {
					$error_msg = sprintf( __( 'The table "%s" has no primary key. Manual change needed on row %s.', 'revisr' ), $table, $current_row );
				}
			}
		}
		$this->wpdb->flush();
		if ( isset( $error_msg ) ) {
			Revisr_Admin::log( $error_msg, $error );
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
	 * @param string $from       String we're looking to replace.
	 * @param string $to         What we want it to be replaced with
	 * @param array  $data       Used to pass any subordinate arrays back to in.
	 * @param bool   $serialised Does the array passed via $data need serialising.
	 *
	 * @return array	The original array with all elements replaced as needed.
	 */
	public function recursive_unserialize_replace( $from = '', $to = '', $data = '', $serialised = false ) {
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

			else {
				if ( is_string( $data ) )
					$data = str_replace( $from, $to, $data );
			}

			if ( $serialised )
				return serialize( $data );

		} catch( Exception $error ) {

		}

		return $data;
	}

	/**
	 * Mimics the mysql_real_escape_string function. From feedr on php.net.
	 * @link http://php.net/manual/en/function.mysql-real-escape-string.php#101248
	 * 
	 * @access public
	 */
	public function mysql_escape_mimic($inp) { 
	    if(is_array($inp)) 
	        return array_map(__METHOD__, $inp); 

	    if(!empty($inp) && is_string($inp)) { 
	        return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp); 
	    } 

	    return $inp; 
	} 

	/**
	 * Sets up the backup environment.
	 * @access public
	 */
	public function setup_env() {
		$upload_dir = wp_upload_dir();
		$backup_dir = $upload_dir['basedir'] . '/revisr-backups/';
		if ( is_dir( $backup_dir ) ) {
			chdir( $backup_dir );
		} else {
			mkdir( $backup_dir );
			chdir( $backup_dir );
		}
		if ( ! file_exists( '.htaccess' ) ) {
			$htaccess_content = '<FilesMatch "\.sql">' .
			PHP_EOL . 'Order allow,deny' .
			PHP_EOL . 'Deny from all' .
			PHP_EOL . 'Satisfy All' . 
			PHP_EOL . '</FilesMatch>';
			file_put_contents( '.htaccess', $htaccess_content );
		}		
	}

	/**
	 * Verifies a backup file.
	 * @access public
	 * @return bool
	 */
	public function verify_backup( $table ) {
		if ( ! file_exists( "revisr_$table.sql" ) || filesize( "revisr_$table.sql" ) < 1000 ) {
			return false;
		}
		return true;
	}
}