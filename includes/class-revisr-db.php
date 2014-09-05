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
	private $dir;

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

		$this->branch		= Revisr_Git::current_branch();
		$this->dir 			= getcwd();
		$this->git 			= new Revisr_Git;
		$this->sql_file 	= 'revisr_db_backup.sql';
		$this->options 		= Revisr_Admin::options();
		$this->upload_dir 	= wp_upload_dir();
		$this->check_exec();

		if ( isset( $this->options['mysql_path'] ) ) {
			$this->path = $this->options['mysql_path'];
		} else {
			$this->path = '';
		}

		if ( DB_PASSWORD != '' ) {
			$this->conn = "-u '" . DB_USER . "' -p'" . DB_PASSWORD . "' " . DB_NAME . " --host " . DB_HOST;
		} else {
			$this->conn = "-u '" . DB_USER . "' " . DB_NAME . " --host " . DB_HOST;
		}
		chdir( $this->upload_dir['basedir'] );
	}

	/**
	 * Any post-operation cleanup.
	 * @access public
	 */
	public function __destruct() {
		chdir( $this->dir );
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
			$this->maybe_return( $msg );
		} else {
			$msg = __( 'Failed to backup the database.', 'revisr' );
			Revisr_Admin::log( $msg, 'error');
			$this->maybe_return( $msg );
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
			
			if ( $branch != $this->branch ) {
				$this->git->checkout($branch);
			}

			if ( $this->verify_backup() === false ) {
				wp_die( __( 'The backup file does not exist or has been corrupted.', 'revisr' ) );
			}
			clearstatcache();

			$this->backup();

			$commit 		= escapeshellarg( $_GET['db_hash'] );
			$current_temp	= Revisr_Git::run( "log --pretty=format:'%h' -n 1" );

			$checkout = Revisr_Git::run( "checkout {$commit} {$this->upload_dir['basedir']}/{$this->sql_file}", true );

			if ( $checkout !== 1 ) {
				
				exec( "{$this->path}mysql {$this->conn} < {$this->sql_file}" );
				Revisr_Git::run( "checkout {$this->branch} {$this->upload_dir['basedir']}/{$this->sql_file}" );
				
				if (is_array($current_temp)) {
					$current_commit = str_replace("'", "", $current_temp);
					$undo_nonce 	= wp_nonce_url( admin_url("admin-post.php?action=revert_db&db_hash={$current_commit[0]}&branch={$_GET['branch']}"), 'revert_db', 'revert_db_nonce' );
					$msg = sprintf( __( 'Reverted the database to a previous commit. <a href="%s">Undo</a>', 'revisr'), $undo_nonce );
					Revisr_Admin::log( $msg, 'revert' );
					$redirect = get_admin_url() . "admin.php?page=revisr&revert_db=success&prev_commit={$current_commit[0]}";
					wp_redirect($redirect);			
				} else {
					wp_die( __( 'Something went wrong. Check your settings and try again.', 'revisr' ) );
				}
			} else {
				wp_die( __( 'Failed to revert the database to an earlier commit.', 'revisr' ) );
			}	
		} else if ( $restore_branch === true ){
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
		$commit_msg = escapeshellarg( __( 'Backed up the database with Revisr.', 'revisr' ) );
		$file 	= $this->upload_dir['basedir'] . '/' . $this->sql_file;
		$add 	= Revisr_Git::run( "add {$file}" );
		$commit = Revisr_Git::run( "commit -m $commit_msg" );

		if ( $add === false || $commit === false ) {
			$error = __( 'There was an error committing the database.', 'revisr' );
			$this->maybe_return( $error );
		}

		//Insert the corresponding post if necessary.
		if ( $insert_post === true ) {
			$post = array(
				'post_title' 	=> $commit_msg,
				'post_content' 	=> '',
				'post_type' 	=> 'revisr_commits',
				'post_status' 	=> 'publish',
			);
			$post_id = wp_insert_post( $post );
			$commit_hash = Revisr_Git::run( 'rev-parse --short HEAD' );
			add_post_meta( $post_id, 'commit_hash', $commit_hash[0] );
			add_post_meta( $post_id, 'db_hash', $commit_hash[0] );
			add_post_meta( $post_id, 'branch', $this->branch );
			add_post_meta( $post_id, 'files_changed', '0' );
			add_post_meta( $post_id, 'committed_files', array() );
		}

		//Push changes if necessary.
		$this->git->auto_push();
	}

	/**
	 * Echoes the result if necessary.
	 * @access private
	 * @param string $status 		The string to output.
	 * @param string $insert_post 	Whether to insert a post.
	 */
	private function maybe_return( $status ) {
		if ( isset( $_REQUEST['source'] ) && $_REQUEST['source'] == 'ajax_button' ) {
			echo '<p>' . $status . '</p>';
			exit();
		}
	}

	/**
	 * Verifies that a backup file is valid.
	 * @access private
	 */
	private function verify_backup() {
		if ( ! file_exists( $this->sql_file ) || filesize( $this->sql_file ) < 1000 ) {
			return false;
		} else {
			return true;
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