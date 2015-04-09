<?php
/**
 * class-revisr-admin.php
 *
 * Handles admin-specific functionality.
 *
 * @package   	Revisr
 * @license   	GPLv3
 * @link      	https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr_Admin {

	/**
	 * A reference back to the main Revisr instance.
	 * @var object
	 */
	protected $revisr;

	/**
	 * An array of page hooks returned by add_menu_page and add_submenu_page.
	 * @var array
	 */
	public $page_hooks = array();

	/**
	 * Initialize the class.
	 * @access public
	 */
	public function __construct() {
		$this->revisr = revisr();
	}

	/**
	 * Registers and enqueues css and javascript files.
	 * @access public
	 * @param string $hook The page to enqueue the styles/scripts.
	 */
	public function revisr_scripts( $hook ) {

		// Register all CSS files used by Revisr.
		wp_register_style( 'revisr_dashboard_css', REVISR_URL . 'assets/css/dashboard.css', array(), '03302015' );
		wp_register_style( 'revisr_commits_css', REVISR_URL . 'assets/css/commits.css', array(), '03302015' );
		wp_register_style( 'revisr_octicons_css', REVISR_URL . 'assets/octicons/octicons.css', array(), '03302015' );

		// Register all JS files used by Revisr.
		wp_register_script( 'revisr_dashboard', REVISR_URL . 'assets/js/revisr-dashboard.js', 'jquery',  '03302015', true );
		wp_register_script( 'revisr_staging', REVISR_URL . 'assets/js/revisr-staging.js', 'jquery', '03302015', false );
		wp_register_script( 'revisr_committed', REVISR_URL . 'assets/js/revisr-committed.js', 'jquery', '03302015', false );
		wp_register_script( 'revisr_settings', REVISR_URL . 'assets/js/revisr-settings.js', 'jquery', '03302015', true );

		// An array of pages that most scripts can be allowed on.
		$allowed_pages = array( 'revisr', 'revisr_settings', 'revisr_branches' );

		// Enqueue common scripts and styles.
		if ( isset( $_GET['page'] ) && in_array( $_GET['page'], $allowed_pages ) ) {

			wp_enqueue_style( 'revisr_dashboard_css' );
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_script( 'revisr_settings' );
			wp_enqueue_style( 'revisr_octicons_css' );

		}

		// Enqueue scripts and styles for the 'revisr_commits' custom post type.
		if ( 'revisr_commits' === get_post_type() ) {

			if ( 'post-new.php' === $hook  || 'post.php' === $hook ) {

				// Enqueue scripts for the "New Commit" screen.
				wp_enqueue_script( 'revisr_staging' );
				wp_localize_script( 'revisr_staging', 'pending_vars', array(
					'ajax_nonce' 		=> wp_create_nonce( 'pending_nonce' ),
					'empty_title_msg' 	=> __( 'Please enter a message for your commit.', 'revisr' ),
					'empty_commit_msg' 	=> __( 'Nothing was added to the commit. Please use the section below to add files to use in the commit.', 'revisr' ),
					'error_commit_msg' 	=> __( 'There was an error committing the files. Make sure that your Git username and email is set, and that Revisr has write permissions to the ".git" directory.', 'revisr' ),
					'view_diff' 		=> __( 'View Diff', 'revisr' ),
					)
				);

			}

			wp_enqueue_style( 'revisr_commits_css' );
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_style( 'revisr_octicons_css' );
			wp_enqueue_script( 'thickbox' );
			wp_dequeue_script( 'autosave' );

		} elseif ( isset( $_GET['post_type'] ) && 'revisr_commits' === $_GET['post_type'] ) {
			// Necessary for when there are no commits found in a branch.
			wp_enqueue_style( 'revisr_commits_css' );
		}

	}

	/**
	 * Registers the menus used by Revisr.
	 * @access public
	 */
	public function menus() {
		$icon_svg = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4NCjwhLS0gR2VuZXJhdG9yOiBBZG9iZSBJbGx1c3RyYXRvciAxOC4xLjAsIFNWRyBFeHBvcnQgUGx1Zy1JbiAuIFNWRyBWZXJzaW9uOiA2LjAwIEJ1aWxkIDApICAtLT4NCjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iTGF5ZXJfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiDQoJIHZpZXdCb3g9IjI0NS44IDM4MS4xIDgxLjkgODkuNSIgZW5hYmxlLWJhY2tncm91bmQ9Im5ldyAyNDUuOCAzODEuMSA4MS45IDg5LjUiIHhtbDpzcGFjZT0icHJlc2VydmUiPg0KPHBhdGggZmlsbD0iI2ZmZiIgZD0iTTI5NS4yLDM4Ny4yYy01LjEsNS4xLTUuMSwxMy4zLDAsMTguM2MzLjgsMy44LDkuMyw0LjcsMTMuOSwyLjlsNy4yLTcuMmMxLjgtNC43LDAuOS0xMC4yLTIuOS0xMy45DQoJQzMwOC41LDM4Mi4xLDMwMC4zLDM4Mi4xLDI5NS4yLDM4Ny4yeiBNMzA5LjcsNDAxLjZjLTIuOSwyLjktNy42LDIuOS0xMC42LDBjLTIuOS0yLjktMi45LTcuNiwwLTEwLjZjMi45LTIuOSw3LjYtMi45LDEwLjYsMA0KCUMzMTIuNiwzOTQsMzEyLjYsMzk4LjcsMzA5LjcsNDAxLjZ6Ii8+DQo8cGF0aCBmaWxsPSIjZmZmIiBkPSJNMjY4LjEsNDU0Yy0xMy4yLTEwLjEtMTYuMS0yOS02LjQtNDIuNmM0LTUuNiw5LjQtOS40LDE1LjQtMTEuNGwtMi0xMC4yYy04LjUsMi41LTE2LjIsNy43LTIxLjcsMTUuNQ0KCWMtMTIuOSwxOC4yLTguOSw0My41LDguOCw1N2wtNS42LDguM2wyNS45LTEuMmwtOC42LTIzLjZMMjY4LjEsNDU0eiIvPg0KPHBhdGggZmlsbD0iI2ZmZiIgZD0iTTMxOC4zLDQwMy4zYzEuMS0yLjEsMS43LTQuNSwxLjctN2MwLTguNC02LjgtMTUuMi0xNS4yLTE1LjJzLTE1LjIsNi44LTE1LjIsMTUuMnM2LjgsMTUuMiwxNS4yLDE1LjINCgljMi4xLDAsNC4xLTAuNCw1LjktMS4yYzguNCwxMC42LDkuMiwyNS44LDEsMzcuMmMtMy45LDUuNi05LjQsOS40LTE1LjQsMTEuNGwyLDEwLjJjOC41LTIuNSwxNi4yLTcuNywyMS43LTE1LjUNCglDMzMxLjIsNDM4LjEsMzI5LjksNDE3LjQsMzE4LjMsNDAzLjN6IE0zMDQuOCw0MDMuM2MtMy44LDAtNi45LTMuMS02LjktNi45czMuMS02LjksNi45LTYuOXM2LjksMy4xLDYuOSw2LjkNCglTMzA4LjcsNDAzLjMsMzA0LjgsNDAzLjN6Ii8+DQo8L3N2Zz4=';
		$this->page_hooks['menu'] 		= add_menu_page( __( 'Dashboard', 'revisr' ), 'Revisr', 'manage_options', 'revisr', array( $this, 'revisr_dashboard' ), $icon_svg );
		$this->page_hooks['dashboard'] 	= add_submenu_page( 'revisr', __( 'Revisr - Dashboard', 'revisr' ), __( 'Dashboard', 'revisr' ), 'manage_options', 'revisr', array( $this, 'revisr_dashboard' ) );
		$this->page_hooks['branches'] 	= add_submenu_page( 'revisr', __( 'Revisr - Branches', 'revisr' ), __( 'Branches', 'revisr' ), 'manage_options', 'revisr_branches', array( $this, 'revisr_branches' ) );
		$this->page_hooks['settings'] 	= add_submenu_page( 'revisr', __( 'Revisr - Settings', 'revisr' ), __( 'Settings', 'revisr' ), 'manage_options', 'revisr_settings', array( $this, 'revisr_settings' ) );
	}

	/**
	 * Filters the display order of the menu pages.
	 * @access public
	 */
	public function revisr_submenu_order( $menu_ord ) {
		global $submenu;
	    $arr = array();

		if ( isset( $submenu['revisr'] ) ) {
		    $arr[] = $submenu['revisr'][0];
		    $arr[] = $submenu['revisr'][3];
		    $arr[] = $submenu['revisr'][1];
		    $arr[] = $submenu['revisr'][2];
		    $submenu['revisr'] = $arr;
		}
	    return $menu_ord;
	}

	/**
	 * Stores an alert to be rendered on the dashboard.
	 * @access public
	 * @param  string  	$message 	The message to display.
	 * @param  bool    	$is_error 	Whether the message is an error.
	 * @param  array  	$output 	An array of output to store for viewing error details.
	 */
	public static function alert( $message, $is_error = false, $output = array() ) {
		if ( true === $is_error ) {

			if ( is_array( $output ) && ! empty( $output ) ) {
				// Store info about the error for later.
				set_transient( 'revisr_error_details', $output );

				// Provide a link to view the error.
				$error_url 	= wp_nonce_url( admin_url( 'admin-post.php?action=revisr_view_error&TB_iframe=true&width=350&height=300' ), 'revisr_view_error', 'revisr_error_nonce' );
				$message 	.= sprintf( __( '<br>Click <a href="%s" class="thickbox" title="Error Details">here</a> for more details.', 'revisr' ), $error_url );
			}

			set_transient( 'revisr_error', $message, 10 );

		} else {
			set_transient( 'revisr_alert', $message, 3 );
		}
	}

	/**
	 * Displays the number of files changed in the admin bar.
	 * @access public
	 */
	public function admin_bar( $wp_admin_bar ) {
		if ( $this->revisr->git->is_repo && $this->revisr->git->count_untracked() != 0 ) {
			$untracked 	= $this->revisr->git->count_untracked();
			$text 		= sprintf( _n( '%s Untracked File', '%s Untracked Files', $untracked, 'revisr' ), $untracked );
			$args 		= array(
				'id'    => 'revisr',
				'title' => $text,
				'href'  => get_admin_url() . 'post-new.php?post_type=revisr_commits',
				'meta'  => array( 'class' => 'revisr_commits' ),
			);
			$wp_admin_bar->add_node( $args );
		}
	}

	/**
	 * Returns the data for the AJAX buttons.
	 * @access public
	 */
	public function ajax_button_count() {
		if ( $_REQUEST['data'] == 'unpulled' ) {
			echo $this->revisr->git->count_unpulled();
		} else {
			echo $this->revisr->git->count_unpushed();
		}
		exit();
	}

	/**
	 * Deletes existing transients.
	 * @access public
	 */
	public static function clear_transients( $errors = true ) {
		if ( true === $errors ) {
			delete_transient( 'revisr_error' );
			delete_transient( 'revisr_error_details' );
		} else {
			delete_transient( 'revisr_alert' );
		}
	}

	/**
	 * Counts the number of commits in the database on a given branch.
	 * @access public
	 * @param  string $branch The name of the branch to count commits for.
	 */
	public static function count_commits( $branch ) {
		global $wpdb;
		if ( $branch == 'all' ) {

			$num_commits = $wpdb->get_results( "SELECT meta_id FROM $wpdb->postmeta WHERE meta_key = 'branch'" );
		} else {
			$num_commits = $wpdb->get_results( $wpdb->prepare( "SELECT meta_id FROM $wpdb->postmeta WHERE meta_key = 'branch' AND meta_value = %s", $branch ) );
		}
		return count( $num_commits );
	}

	/**
	 * Escapes a shell arguement.
	 * @access public
	 * @param  string $string The string to escape.
	 * @return string $string The escaped string.
	 */
	public static function escapeshellarg( $string ) {
		$os = Revisr_Compatibility::get_os();

		if ( 'WIN' !== $os['code'] ) {
			return escapeshellarg( $string );
		} else {
			// Windows-friendly workaround.
			return '"' . str_replace( "'", "'\\''", $string ) . '"';
		}
	}

	/**
	 * Gets an array of details on a saved commit.
	 * @access public
	 * @param  string $id The WordPress Post ID associated with the commit.
	 * @return array
	 */
	public static function get_commit_details( $id ) {

		// Grab the values from the post meta.
		$branch 			= get_post_meta( $id, 'branch', true );
		$hash 				= get_post_meta( $id, 'commit_hash', true );
		$db_hash 			= get_post_meta( $id, 'db_hash', true );
		$db_backup_method	= get_post_meta( $id, 'backup_method', true );
		$files_changed 		= get_post_meta( $id, 'files_changed', true );
		$committed_files 	= get_post_meta( $id, 'committed_files' );
		$git_tag 			= get_post_meta( $id, 'git_tag', true );
		$status 			= get_post_meta( $id, 'commit_status', true );
		$error 				= get_post_meta( $id, 'error_details' );

		// Store the values in an array.
		$commit_details = array(
			'branch' 			=> $branch ? $branch : __( 'Unknown', 'revisr' ),
			'commit_hash' 		=> $hash ? $hash : __( 'Unknown', 'revisr' ),
			'db_hash' 			=> $db_hash ? $db_hash : '',
			'db_backup_method'	=> $db_backup_method ? $db_backup_method : '',
			'files_changed' 	=> $files_changed ? $files_changed : 0,
			'committed_files' 	=> $committed_files ? $committed_files : array(),
			'tag'				=> $git_tag ? $git_tag : '',
			'status'			=> $status ? $status : '',
			'error_details' 	=> $error ? $error : false
		);

		// Return the array.
		return $commit_details;
	}

	/**
	 * Returns the ID of a commit with a provided commit hash.
	 * @access public
	 * @param  string 	$commit_hash The commit hash to check.
	 * @param  boolean 	$return_link If set to true, will return as a link.
	 * @return mixed
	 */
	public static function get_the_id_by_hash( $commit_hash, $return_link = false ) {
		global $wpdb;
		$query 	= $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'commit_hash' AND meta_value = %s", $commit_hash );
		$result = $wpdb->get_results( $query, ARRAY_A );

		if ( $result ) {

			if ( true === $return_link ) {
				$url 	= wp_nonce_url( get_admin_url() . 'post.php?post=' . $result[0]['post_id'] . '&action=edit', 'edit', 'revisr_edit_nonce' );
				$link 	= sprintf( '<a href="%s" target="_blank">%s</a>', $url, $commit_hash );
				return $link;
			}

			return $result[0]['post_id'];
		}

		return false;
	}

	/**
	 * Logs an event to the database.
	 * @access public
	 * @param  string $message The message to show in the Recent Activity.
	 * @param  string $event   Will be used for filtering later.
	 */
	public static function log( $message, $event ) {
		global $wpdb;
		$time  = current_time( 'mysql' );
		$table = $wpdb->prefix . 'revisr';
		$wpdb->insert(
			"$table",
			array(
				'time' 		=> $time,
				'message'	=> $message,
				'event' 	=> $event,
			),
			array(
				'%s',
				'%s',
				'%s',
			)
		);
	}

	/**
	 * Notifies the admin if notifications are enabled.
	 * @access private
	 * @param  string $subject The subject line of the email.
	 * @param  string $message The message for the email.
	 */
	public static function notify( $subject, $message ) {
		$options 	= Revisr::get_options();
		$url 		= get_admin_url() . 'admin.php?page=revisr';

		if ( isset( $options['notifications'] ) ) {
			$email 		= $options['email'];
			$message	.= '<br><br>';
			$message	.= sprintf( __( '<a href="%s">Click here</a> for more details.', 'revisr' ), $url );
			$headers 	= "Content-Type: text/html; charset=ISO-8859-1\r\n";
			wp_mail( $email, $subject, $message, $headers );
		}
	}

	/**
	 * Renders an alert and removes the old data.
	 * @access public
	 */
	public function render_alert() {
		$alert = get_transient( 'revisr_alert' );
		$error = get_transient( 'revisr_error' );
		if ( $error ) {
			echo "<div class='revisr-alert error'>" . wpautop( $error ) . "</div>";
		} else if ( $alert ) {
			echo "<div class='revisr-alert updated'>" . wpautop( $alert ) . "</div>";
		} else {
			if ( $this->revisr->git->count_untracked() == '0' ) {
				printf( __( '<div class="revisr-alert updated"><p>There are currently no untracked files on branch %s.', 'revisr' ), $this->revisr->git->branch );
			} else {
				$commit_link = get_admin_url() . 'post-new.php?post_type=revisr_commits';
				printf( __('<div class="revisr-alert updated"><p>There are currently %s untracked files on branch %s. <a href="%s">Commit</a> your changes to save them.</p></div>', 'revisr' ), $this->revisr->git->count_untracked(), $this->revisr->git->branch, $commit_link );
			}
		}
		exit();
	}

	/**
	 * Processes a diff request.
	 * @access public
	 */
	public function view_diff() {
		?>
		<html>
		<head>
			<title><?php _e( 'View Diff', 'revisr' ); ?></title>
		</head>
		<body>
		<?php

			if ( isset( $_REQUEST['commit'] ) ) {
				$diff = $this->revisr->git->run( 'show', array( $_REQUEST['commit'], $_REQUEST['file'] ) );
			} else {
				$diff = $this->revisr->git->run( 'diff', array( $_REQUEST['file'] ) );
			}

			if ( is_array( $diff ) ) {

				// Loop through the diff and echo the output.
				foreach ( $diff as $line ) {
					if ( substr( $line, 0, 1 ) === '+' ) {
						echo '<span class="diff_added" style="background-color:#cfc;">' . htmlspecialchars( $line ) . '</span><br>';
					} else if ( substr( $line, 0, 1 ) === '-' ) {
						echo '<span class="diff_removed" style="background-color:#fdd;">' . htmlspecialchars( $line ) . '</span><br>';
					} else {
						echo htmlspecialchars( $line ) . '<br>';
					}
				}

			} else {
				_e( 'Oops! Revisr ran into an error rendering the diff.', 'revisr' );
			}
		?>
		</body>
		</html>
		<?php
		exit();
	}

	/**
	 * Processes a view error request.
	 * @access public
	 */
	public function view_error() {
		?>
		<html>
		<head>
		<title><?php _e( 'Error Details', 'revisr' ); ?></title>
		</head>
		<body>
			<?php
				if ( isset( $_REQUEST['post_id'] ) && get_post_meta( $_REQUEST['post_id'], 'error_details', true ) ) {
					echo implode( '<br>', get_post_meta( $_REQUEST['post_id'], 'error_details', true ) );
				} elseif ( $revisr_error = get_transient( 'revisr_error_details' ) ) {
					echo implode( '<br>', $revisr_error );
				} else {
					_e( 'Detailed error information not available.', 'revisr' );
				}
			?>
		</body>
		</html>
		<?php
	}

	/**
	 * Processes a view status request.
	 * @access public
	 */
	public function view_status() {
		?>
		<html>
		<head>
		<title><?php _e( 'View Status', 'revisr' ); ?></title>
		</head>
		<body>
		<?php
			$status = $this->revisr->git->run( 'status', array() );

			if ( is_array( $status ) ) {
				echo '<pre>';
				foreach ( $status as $line ) {
					echo $line . PHP_EOL;
				}
				echo '</pre>';
			} else {
				_e( 'Error retrieving the status of the repository.', 'revisr' );
			}
		?>
		</body>
		</html>
		<?php
		exit();
	}

	/**
	 * Updates user settings to be compatible with 1.8.
	 * @access public
	 */
	public function do_upgrade() {

		// Check for the "auto_push" option and save it to the config.
		if ( isset( $this->revisr->options['auto_push'] ) ) {
			$this->revisr->git->set_config( 'revisr', 'auto-push', 'true' );
		}

		// Check for the "auto_pull" option and save it to the config.
		if ( isset( $this->revisr->options['auto_pull'] ) ) {
			$this->revisr->git->set_config( 'revisr', 'auto-pull', 'true' );
		}

		// Check for the "reset_db" option and save it to the config.
		if ( isset( $this->revisr->options['reset_db'] ) ) {
			$this->revisr->git->set_config( 'revisr', 'import-checkouts', 'true' );
		}

		// Check for the "mysql_path" option and save it to the config.
		if ( isset( $this->revisr->options['mysql_path'] ) ) {
			$this->revisr->git->set_config( 'revisr', 'mysql-path', $this->revisr->options['mysql_path'] );
		}

		// Configure the database tracking to use all tables, as this was how it behaved in 1.7.
		$this->revisr->git->set_config( 'revisr', 'db_tracking', 'all_tables' );

		// We're done here.
		update_option( 'revisr_db_version', '1.1' );
	}

	/**
	 * Displays the "Sponsored by Site5" logo.
	 * @access public
	 */
	public function site5_notice() {
		$allowed_on = array( 'revisr', 'revisr_settings', 'revisr_commits', 'revisr_settings', 'revisr_branches' );
		if ( isset( $_GET['page'] ) && in_array( $_GET['page'], $allowed_on ) ) {
			$output = true;
		} else if ( isset( $_GET['post_type'] ) && in_array( $_GET['post_type'], $allowed_on ) || get_post_type() == 'revisr_commits' ) {
			$output = true;
		} else {
			$output = false;
		}
		if ( $output === true ) {
			?>
			<div id="site5_wrapper">
				<?php _e( 'Sponsored by', 'revisr' ); ?>
				<a href="http://www.site5.com/" target="_blank"><img id="site5_logo" src="<?php echo REVISR_URL . 'assets/img/site5.png'; ?>" width="80" /></a>
			</div>
			<?php
		}
	}

	/**
	 * Returns an escaped array suitable for attributes.
	 * @access public
	 * @param  array $input An array of input to filter.
	 * @return array
	 */
	public static function esc_attr_array( $input ) {
		return array_map( 'esc_attr', $input );
	}

	/**
	 * Includes the template for the main dashboard.
	 * @access public
	 */
	public function revisr_dashboard() {
		include_once REVISR_PATH . 'templates/dashboard.php';
	}

	/**
	 * Includes the template for the branches page.
	 * @access public
	 */
	public function revisr_branches() {
		include_once REVISR_PATH . 'templates/branches.php';
	}

	/**
	 * Includes the template for the settings page.
	 * @access public
	 */
	public function revisr_settings() {
		include_once REVISR_PATH . 'templates/settings.php';
	}

	/**
	 * Displays the form to delete a branch.
	 * @access public
	 */
	public function delete_branch_form() {
		include_once REVISR_PATH . 'assets/partials/delete-branch-form.php';
	}

	/**
	 * Displays the form to merge a branch.
	 * @access public
	 */
	public function merge_branch_form() {
		include_once REVISR_PATH . 'assets/partials/merge-form.php';
	}

	/**
	 * Displays the form to pull a remote branch.
	 * @access public
	 */
	public function import_tables_form() {
		include_once REVISR_PATH . 'assets/partials/import-tables-form.php';
	}

	/**
	 * Displays the form to revert a commit.
	 * @access public
	 */
	public function revert_form() {
		include_once REVISR_PATH . 'assets/partials/revert-form.php';
	}
}
