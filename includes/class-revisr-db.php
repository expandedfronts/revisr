<?php
/**
 * class-revisr-db.php
 *
 * The base database class, acting as a factory via run() for
 * backups and imports. Also contains some methods shared
 * across all database operations.
 *
 * @package   	Revisr
 * @license   	GPLv3
 * @link      	https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr_DB {

	/**
	 * A reference to the main Revisr instance.
	 * @var Revisr
	 */
	protected $revisr;

	/**
	 * An array of user options and preferences.
	 * @var array
	 */
	protected $options;

	/**
	 * The backup directory.
	 * @var string
	 */
	protected $backup_dir;

	/**
	 * The WordPress database class.
	 * @var WPDB
	 */
	protected $wpdb;

	/**
	 * Constructs the class.
	 * @access public
	 */
	public function __construct() {

		// Make WPDB available to the class.
		global $wpdb;
		$this->wpdb = $wpdb;

		// Grab the instance of Revisr.
		$this->revisr 	= Revisr::get_instance();
		$this->options 	= Revisr::get_options();

		$upload_dir = wp_upload_dir();

		$this->backup_dir = $upload_dir['basedir'] . '/revisr-backups/';

		// Set up the "revisr_backups" directory if necessary.
		$this->setup_env();

	}

	/**
	 * Runs an action on all tracked database tables.
	 *
	 * @access private
	 * @param  string 			$action 	The action to perform.
	 * @param  array 			$tables 	The tables to run on (optional).
	 * @param  string|array 	$args 		Any arguements to pass through (optional).
	 * @return boolean
	 */
	private function run( $action, $tables = array(), $args = '' ) {

		// An empty status array to update later.
		$status 	= array();

		// Whether we're using MySQL, WPDB, or maybe even something else.
		$driver 	= $this->get_driver();

		// Builds the name of the class to construct.
		$class 		= 'Revisr_DB_' . $action;

		// Builds the name of the method to call.
		$method 	= $action . '_table_' . $driver;

		// The tables we want to run the action on.
		$tables 	= $tables ? $tables : $this->get_tracked_tables();

		if ( is_callable( array( $class, $method ) ) ) {

			// Construct the class.
			$class = new $class;

			/**
			 * Loop through the tracked tables,
			 * storing the results in the status array.
			 */
			foreach ( $tables as $table ) {
				$status[$table] = $class->$method( $table, $args );
			}

			// Fire off the callback.
			$class->callback( $status );

		}

	}

	/**
	 * Returns the database method being used.
	 * @access private
	 * @return string
	 */
	private function get_driver() {
		return $this->revisr->git->get_config( 'revisr', 'db-driver' ) ? $this->revisr->git->get_config( 'revisr', 'db-driver' ) : 'mysql';
	}

	/**
	 * Returns the path to MySQL.
	 * @access protected
	 * @return string
	 */
	protected function get_path() {
		return $this->revisr->git->get_config( 'revisr', 'mysql-path' ) ? $this->revisr->git->get_config( 'revisr', 'mysql-path' ) : '';
	}

	/**
	 * Returns an array of tables in the database.
	 * @access public
	 * @return array
	 */
	public function get_tables() {
		$tables = $this->wpdb->get_col( 'SHOW TABLES' );
		return $tables;
	}

	/**
	 * Returns a list of tables that are in the "revisr-backups" directory,
	 * but not in the database. Necessary for importing tables that could
	 * not be added to the tracked tables due to them not existing.
	 * @access public
	 * @return array
	 */
	public function get_tables_not_in_db() {
		$backup_tables	= array();
		$db_tables 		= $this->get_tables();

		foreach ( scandir( $this->backup_dir ) as $file ) {

			if ( 'revisr_' !== substr( $file, 0, 7 ) ) {
				continue;
			}

			// Remove the prefix and extension.
	        $backup_tables[] 	= substr( substr( $file, 7 ), 0, -4 );
    	}

    	$new_tables = array_diff( $backup_tables, $db_tables );
    	return $new_tables;
	}

	/**
	 * Returns the array of tables that are to be tracked.
	 * @access public
	 * @return array
	 */
	public function get_tracked_tables() {
		$stored_tables = $this->revisr->git->run( 'config', array( '--get-all', 'revisr.tracked-tables' ) );
		if ( isset( $this->options['db_tracking'] ) && $this->options['db_tracking'] == 'all_tables' ) {
			$tracked_tables = $this->get_tables();
		} elseif ( is_array( $stored_tables ) ) {
			$tracked_tables = array_intersect( $stored_tables, $this->get_tables() );
		} else {
			$tracked_tables = array();
		}
		return $tracked_tables;
	}

	/**
	 * Adds a table to version control.
	 * @access private
	 * @param  string $table The table to add.
	 */
	protected function add_table( $table ) {
		$this->revisr->git->run( 'add', array( $this->backup_dir . 'revisr_' . $table. '.sql' ) );
	}

	/**
	 * Builds the connection string to use with MySQL.
	 * @access protected
	 * @param  string $table Optionally pass the table to use.
	 * @return string
	 */
	protected function build_conn( $table = '' ) {

		// Allow using the port from the DB_HOST constant.
		if ( false !== $this->check_port( DB_HOST ) ) {
			$port 		= $this->check_port( DB_HOST );
			$add_port 	= " --port=$port";
			$temp 		= strlen( $port ) * -1 - 1;
			$db_host 	= substr( DB_HOST, 0, $temp );
		} else {
			$add_port 	= '';
			$db_host 	= DB_HOST;
		}

		// Maybe connect to a specific table.
		if ( '' !== $table ) {
			$table = ' ' . Revisr_Admin::escapeshellarg( $table );
		}

		// Workaround for compatibility between UNIX and Windows.
		if ( '' !== DB_PASSWORD ) {
			$conn = "-u " . Revisr_Admin::escapeshellarg( DB_USER ) . " -p" . Revisr_Admin::escapeshellarg( DB_PASSWORD ) . " " . DB_NAME . $table . " --host " . $db_host . $add_port;
		} else {
			$conn = "-u " . Revisr_Admin::escapeshellarg( DB_USER ) . " " . DB_NAME . $table . " --host " . $db_host . $add_port;
		}

		// Return the connection string.
		return $conn;
	}

	/**
	 * Creates the backup folder and adds the .htaccess if necessary.
	 * @access private
	 */
	private function setup_env() {
		// Check if the backups directory has already been created.
		if ( is_dir( $this->backup_dir ) ) {
			return true;
		} else {
			// Make the backups directory.
			mkdir( $this->backup_dir );

			// Add .htaccess to prevent direct access.
			$htaccess_content = '<FilesMatch "\.sql">' .
			PHP_EOL . 'Order allow,deny' .
			PHP_EOL . 'Deny from all' .
			PHP_EOL . 'Satisfy All' .
			PHP_EOL . '</FilesMatch>';
			file_put_contents( $this->backup_dir . '/.htaccess', $htaccess_content );

			// Add index.php to prevent directory listing.
			$index_content = '<?php // Silence is golden' . PHP_EOL;
			file_put_contents( $this->backup_dir . '/index.php', $index_content );
		}
	}

	/**
	 * Checks if a given host is using a port, if so, return the port.
	 * @access public
	 * @param  string $url The URL to check.
	 * @return string|boolean
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
	 * Mimics the mysql_real_escape_string function. Adapted from a post by 'feedr' on php.net.
	 * @link   http://php.net/manual/en/function.mysql-real-escape-string.php#101248
	 * @access public
	 * @param  string $input The string to escape.
	 */
	public function mysql_escape_mimic( $input ) {

	    if ( is_array( $input ) ) {
	        return array_map( __METHOD__, $input );
	    }

	    if( ! empty( $input ) && is_string( $input ) ) {
	        return str_replace( array( '\\', "\0", "\n", "\r", "'", '"', "\x1a" ), array( '\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z' ), $input );
	    }

	    return $input;
	}

	/**
	 * Callback for database backups (AJAX button and via New Commit)
	 * @access public
	 */
	public function backup() {

		// Get the tables to backup.
		$tables = $this->get_tracked_tables();
		if ( empty( $tables ) ) {
			$tables = $this->get_tables();
		}

		// Run the backup.
		$this->run( 'backup', $tables );

		// Commit any changed database files and insert a post if necessary.
		if ( isset( $_REQUEST['source'] ) && $_REQUEST['source'] == 'ajax_button' ) {
			$this->commit_db( true );
		} else {
			$this->commit_db( false );
		}

	}

	/**
	 * Reverts all tracked tables to an earlier commit.
	 * @access public
	 * @param  boolean $redirect Whether or not to redirect via PHP.
	 */
	public function restore( $redirect = true ) {

		// Make sure we really want to do this.
		if ( ! wp_verify_nonce( $_REQUEST['revisr_revert_nonce'], 'revisr_revert_nonce' ) ) {
			wp_die( __( 'Cheatin&#8217; uh?', 'revisr' ) );
		}

		$commit = $_REQUEST['db_hash'];
		$branch = $_REQUEST['branch'];

		// Checkout the appropriate branch if necessary.
		if ( $branch != $this->revisr->git->branch ) {
			$this->revisr->git->checkout( $branch );
		}

		/**
		 * Backup the database and store the resulting commit hash
		 * in memory so we can use it to create the revert link later.
		 */
		$this->backup();
		$current_temp = $this->revisr->git->current_commit();

		if ( $current_temp ) {

			/**
			 * If the backup was successful and we have the resulting commit hash,
			 * run a revert on the sql files for the tracked tables and import them.
			 */
			$tables 	= $this->get_tracked_tables();
			$checkout 	= array();

			foreach ( $tables as $table ) {
				$checkout[$table] = $this->revisr->git->run( 'checkout', array( $commit, "{$this->backup_dir}revisr_$table.sql" ) );
			}

			if ( ! in_array( 1, $checkout ) ) {
				// If all tables reverted successfully, import them.
				$this->import();
			} else {
				$msg = __( 'Error reverting one or more database tables.', 'revisr' );
				Revisr_Admin::log( $msg, 'error' );
				Revisr_Admin::alert( $msg, true );
			}


			// Create a link to undo the revert if necessary.
			$undo_nonce = wp_nonce_url( admin_url( "admin-post.php?action=process_revert&revert_type=db&db_hash=" . $current_temp . "&branch=" . $_REQUEST['branch'] . "&backup_method=tables" ), 'revisr_revert_nonce', 'revisr_revert_nonce' );
			$undo_msg 	= sprintf( __( 'Successfully reverted the database to a previous commit. <a href="%s">Undo</a>', 'revisr' ), $undo_nonce );

			// Store the undo link and alert the user.
			Revisr_Admin::log( $undo_msg, 'revert' );
			Revisr_Admin::alert( $undo_msg );

			// Redirect if necessary.
			if ( $redirect !== false ) {
				wp_redirect( get_admin_url() . 'admin.php?page=revisr' );
				exit();
			}

		} else {
			wp_die( __( 'Something went wrong. Check your settings and try again.', 'revisr' ) );
		}

	}

	/**
	 * Runs an import of all tracked tables, importing any new tables
	 * if tracking all_tables, or providing a link to import new tables
	 * if necessary.
	 * @access public
	 * @param  array $tables The tables to import.
	 */
	public function import( $tables = array() ) {
		// The tables currently being tracked.
		$tracked_tables = $this->get_tracked_tables();

		// Tables that have import files but aren't in the database.
		$new_tables 	= $this->get_tables_not_in_db();

		// All tables.
		$all_tables		= array_unique( array_merge( $new_tables, $tracked_tables ) );

		// The URL to replace during import.
		$replace_url 	= $this->revisr->git->get_config( 'revisr', 'dev-url' ) ? $this->revisr->git->get_config( 'revisr', 'dev-url' ) : '';

		if ( empty( $tables ) ) {

			if ( ! empty( $new_tables ) ) {
				// If there are new tables that were imported.
				if ( isset( $this->options['db_tracking'] ) && $this->options['db_tracking'] == 'all_tables' ) {
					// If the user is tracking all tables, import all tables.
					$this->run( 'import', $all_tables, $replace_url );
				} else {
					// Import only tracked tables, but provide a warning and import link.
					$this->run( 'import', $tracked_tables, $replace_url );
					$url = wp_nonce_url( get_admin_url() . 'admin-post.php?action=import_tables_form&TB_iframe=true&width=350&height=200', 'import_table_form', 'import_nonce' );
					$msg = sprintf( __( 'New database tables detected. <a class="thickbox" title="Import Tables" href="%s">Click here</a> to view and import.', 'revisr' ), $url );
					Revisr_Admin::log( $msg, 'db' );
				}
			} else {
				// If there are no new tables, go ahead and import the tracked tables.
				$this->run( 'import', $tracked_tables, $replace_url );
			}

		} else {
			// Import the provided tables.
			$this->run( 'import', $tables, $replace_url );
		}
	}

	/**
	 * Commits the database to the repository and pushes if needed.
	 * @access public
	 * @param  boolean $insert_post Whether to insert a new commit custom_post_type.
	 */
	public function commit_db( $insert_post = false ) {

		$commit_msg  = __( 'Backed up the database with Revisr.', 'revisr' );

		// Allow for overriding default commit message through the "New Commit" screen.
		if ( isset( $_REQUEST['post_title'] ) && $_REQUEST['post_title'] !== '' ) {
			$commit_msg = $_REQUEST['post_title'];
		}

		// Make the commit.
		$this->revisr->git->commit( $commit_msg );

		// Insert the corresponding post if necessary.
		if ( true === $insert_post ) {
			$post = array(
				'post_title' 	=> $commit_msg,
				'post_content' 	=> '',
				'post_type' 	=> 'revisr_commits',
				'post_status' 	=> 'publish',
			);
			$post_id 		= wp_insert_post( $post );
			$commit_hash 	= $this->revisr->git->current_commit();
			add_post_meta( $post_id, 'commit_hash', $commit_hash );
			add_post_meta( $post_id, 'db_hash', $commit_hash );
			add_post_meta( $post_id, 'backup_method', 'tables' );
			add_post_meta( $post_id, 'branch', $this->revisr->git->branch );
			add_post_meta( $post_id, 'files_changed', '0' );
			add_post_meta( $post_id, 'committed_files', array() );
		}

		// Push changes if necessary.
		$this->revisr->git->auto_push();
	}

	/**
	 * Verifies a backup for a table.
	 * @access public
	 * @param  string $table The table to check.
	 * @return boolean
	 */
	public function verify_backup( $table ) {
		if ( ! file_exists( "{$this->backup_dir}revisr_$table.sql" ) || filesize( "{$this->backup_dir}revisr_$table.sql" ) < 100 ) {
			return false;
		}
		return true;
	}

}
