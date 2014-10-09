<?php
/**
 * class-revisr-db.php
 *
 * Performs database backup and restore operations.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts
 */

class Revisr_DB
{
	/**
	 * The connection to use for the sqldump/import.
	 */
	private $conn;

	/**
	 * The current directory.
	 */
	private $current_dir;

	/**
	 * The current branch.
	 */
	private $branch;
	
	/**
	 * The Git class.
	 */
	private $git;

	/**
	 * The upload directory to store the SQL file.
	 */
	private $upload_dir;

	/**
	 * The file to store the sqldump.
	 */
	private $sql_file;

	/**
	 * Load the user's settings.
	 */
	private $options;

	/**
	 * The path to MySQL.
	 */
	private $path;

	/**
	 * Define the connection to use with mysqldump.
	 * @access public
	 */
	public function __construct() {
		$this->git 			= new Revisr_Git;
		$this->branch		= $this->git->current_branch();
		$this->current_dir  = getcwd();
		$this->sql_file 	= 'revisr_db_backup.sql';
		$this->options 		= Revisr::get_options();
		$this->upload_dir 	= wp_upload_dir();
		$this->check_exec();

		if ( isset( $this->options['mysql_path'] ) ) {
			$this->path = $this->options['mysql_path'];
		} else {
			$this->path = '';
		}

		if ( $this->check_port( DB_HOST ) != false ) {
			$port 		= $this->check_port( DB_HOST );
			$add_port 	= " --port=$port";
			$temp 		= strlen($port) * -1 - 1;
			$db_host 	= substr( DB_HOST, 0, $temp );
		} else {
			$add_port 	= '';
			$db_host 	= DB_HOST;
		}

		if ( DB_PASSWORD != '' ) {
			$this->conn = "-u '" . DB_USER . "' -p'" . DB_PASSWORD . "' " . DB_NAME . " --host " . $db_host . $add_port;
		} else {
			$this->conn = "-u '" . DB_USER . "' " . DB_NAME . " --host " . $db_host . $add_port;
		}
		chdir( $this->upload_dir['basedir'] );
	}

	/**
	 * Any post-operation cleanup.
	 * @access public
	 */
	public function __destruct() {
		chdir( $this->current_dir );
	}

	/**
	 * Backs up the database.
	 * @access public
	 */
	public function backup() {
		exec( "{$this->path}mysqldump {$this->conn} > {$this->sql_file}" );

		if ( $this->verify_backup() != false ) {

			if ( isset( $_REQUEST['source'] ) && $_REQUEST['source'] == 'ajax_button' ) {
				$this->commit_db( true );
			} else {
				$this->commit_db();
			}

			$msg = __( 'Successfully backed up the database.', 'revisr' );
			Revisr_Admin::log( $msg, 'backup' );
			Revisr_Admin::alert( $msg );
		} else {
			$msg = __( 'Failed to backup the database.', 'revisr' );
			Revisr_Admin::log( $msg, 'error');
			Revisr_Admin::alert( $msg, true );
		}
	}

	/**
	 * Restores the database to an earlier version if it exists.
	 * @access public
	 * @param boolean $restore_branch True if restoring the database from another branch.
	 */
	public function restore( $restore_branch = false ) {
		if ( isset($_GET['revert_db_nonce']) && wp_verify_nonce( $_GET['revert_db_nonce'], 'revert_db' ) ) {

			$branch = $_GET['branch'];
			
			if ( $branch != $this->git->branch ) {
				$this->git->checkout( $branch );
			}

			if ( $this->verify_backup() === false ) {
				wp_die( __( 'The backup file does not exist or has been corrupted.', 'revisr' ) );
			}
			clearstatcache();

			$this->backup();

			$commit 		= escapeshellarg( $_GET['db_hash'] );
			$current_temp	= $this->git->run( "log --pretty=format:'%h' -n 1" );

			$checkout = $this->git->run( "checkout {$commit} {$this->upload_dir['basedir']}/{$this->sql_file}" );

			if ( $checkout !== 1 ) {
				
				exec( "{$this->path}mysql {$this->conn} < {$this->sql_file}" );
				$this->git->run( "checkout {$this->branch} {$this->upload_dir['basedir']}/{$this->sql_file}" );
				
				if (is_array($current_temp)) {
					$current_commit = str_replace("'", "", $current_temp);
					$undo_nonce 	= wp_nonce_url( admin_url("admin-post.php?action=revert_db&db_hash={$current_commit[0]}&branch={$_GET['branch']}"), 'revert_db', 'revert_db_nonce' );
					$msg = sprintf( __( 'Successfully reverted the database to a previous commit. <a href="%s">Undo</a>', 'revisr'), $undo_nonce );
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
		} else if ( $restore_branch == true ){
			exec( "{$this->path}mysql {$this->conn} < {$this->sql_file}" );
		} else {
			wp_die( __( 'You are not authorized to perform this action.', 'revisr') );
		}
	}

	/**
	 * Commits the database to the repository and pushes if needed.
	 * @access public
	 * @param boolean $insert_post Whether to insert a new commit custom_post_type.
	 */
	public function commit_db( $insert_post = false ) {
		$commit_msg = __( 'Backed up the database with Revisr.', 'revisr' );
		$file 		= $this->upload_dir['basedir'] . '/' . $this->sql_file;
		$this->git->run( "add {$file}" );
		$this->git->commit( $commit_msg );
		//Insert the corresponding post if necessary.
		if ( $insert_post === true ) {
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
		//Push changes if necessary.
		$this->git->auto_push();
	}

	/**
	 * Verifies that a backup file is valid.
	 * @access public
	 */
	public function verify_backup() {
		if ( ! file_exists( $this->sql_file ) || filesize( $this->sql_file ) < 1000 ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Checks if a given URL is using a port, if so, return the port number.
	 * @access public
	 * @param string $url The URL to parse.
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
	 * Makes sure exec is enabled, as it is necessary.
	 * @access private
	 */
	private function check_exec() {
		if ( ! function_exists( 'exec' ) ) {
			wp_die( __('It appears you don\'t have the PHP exec() function enabled. This is required to revert the database. Check with your hosting provider or enable this in your PHP configuration.', 'revisr' ) );
		}
	}
}