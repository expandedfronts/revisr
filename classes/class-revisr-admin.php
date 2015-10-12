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
	 * An array of page hooks returned by add_menu_page and add_submenu_page.
	 * @var array
	 */
	public $page_hooks = array();

	/**
	 * Registers and enqueues css and javascript files.
	 * @access public
	 * @param string $hook The page to enqueue the styles/scripts.
	 */
	public function revisr_scripts( $hook ) {

		// Start registering/enqueuing scripts if the hook is in our allowed pages.
		if ( in_array( $hook, $this->page_hooks ) ) {

			// Registers all CSS files used by Revisr.
			wp_register_style( 'revisr_admin_css', REVISR_URL . 'assets/css/revisr-admin.css', array(), '05242015' );
			wp_register_style( 'revisr_octicons_css', REVISR_URL . 'assets/lib/octicons/octicons.css', array(), '04242015' );
			wp_register_style( 'revisr_select2_css', REVISR_URL . 'assets/lib/select2/css/select2.min.css', array(), '04242015' );

			// Registers all JS files used by Revisr.
			wp_register_script( 'revisr_dashboard', REVISR_URL . 'assets/js/revisr-dashboard.js', 'jquery',  '05242015', true );
			wp_register_script( 'revisr_staging', REVISR_URL . 'assets/js/revisr-staging.js', 'jquery', '04242015', false );
			wp_register_script( 'revisr_settings', REVISR_URL . 'assets/js/revisr-settings.js', 'jquery', '04242015', true );
			wp_register_script( 'revisr_setup', REVISR_URL . 'assets/js/revisr-setup.js', 'jquery', '0603015', true );
			wp_register_script( 'revisr_select2_js', REVISR_URL . 'assets/lib/select2/js/select2.min.js', 'jquery', '04242015', true );

			// Enqueues styles/scripts that should be loaded on all allowed pages.
			wp_enqueue_style( 'revisr_admin_css' );
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_style( 'revisr_select2_css' );
			wp_enqueue_style( 'revisr_octicons_css' );
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_script( 'revisr_select2_js' );

			// Switch through page-dependant styles/scripts.
			switch( $hook ) {

				// The main dashboard page.
				case 'toplevel_page_revisr':
				case 'revisr':
					wp_enqueue_script( 'revisr_dashboard' );
					wp_localize_script( 'revisr_dashboard', 'revisr_dashboard_vars', array(
						'ajax_nonce' 	=> wp_create_nonce( 'revisr_dashboard_nonce' ),
						'login_prompt' 	=> sprintf( __( 'Session Expired: Please <a href="%s">click here</a> to log in again.', 'revisr' ), wp_login_url( get_admin_url() . 'admin.php?page=revisr' ) )
						)
					);
					break;

				// The branches page.
				case 'revisr_page_revisr_branches':
					break;

				// The settings pages.
				case 'revisr_page_revisr_settings':
					wp_enqueue_script( 'revisr_settings' );
					break;

				// The setup page.
				case 'toplevel_page_revisr_setup':
					wp_enqueue_script( 'revisr_setup' );
					wp_localize_script( 'revisr_setup', 'revisr_setup_vars', array(
						'plugin_or_theme_placeholder' => __( 'Please select...', 'revisr' )
						)
					);
					break;

				// The "New Commit" screen and "View Commit" screen.
				case 'admin_page_revisr_new_commit':
				case 'admin_page_revisr_view_commit':
					wp_enqueue_script( 'revisr_staging' );
					wp_localize_script( 'revisr_staging', 'pending_vars', array(
						'ajax_nonce' 		=> wp_create_nonce( 'pending_nonce' ),
						'empty_title_msg' 	=> __( 'Please enter a message for your commit.', 'revisr' ),
						'empty_commit_msg' 	=> __( 'Nothing was added to the commit. Please use the section below to add files to use in the commit.', 'revisr' ),
						'error_commit_msg' 	=> __( 'There was an error committing the files. Make sure that your Git username and email is set, and that Revisr has write permissions to the ".git" directory.', 'revisr' ),
						'view_diff' 		=> __( 'View Diff', 'revisr' ),
						)
					);
					break;

			}

		}

	}

	/**
	 * Registers the menus used by Revisr.
	 * @access public
	 */
	public function menus() {
		$cap 		= Revisr::get_capability();
		$icon_svg 	= 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4NCjwhLS0gR2VuZXJhdG9yOiBBZG9iZSBJbGx1c3RyYXRvciAxOC4xLjAsIFNWRyBFeHBvcnQgUGx1Zy1JbiAuIFNWRyBWZXJzaW9uOiA2LjAwIEJ1aWxkIDApICAtLT4NCjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iTGF5ZXJfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiDQoJIHZpZXdCb3g9IjI0NS44IDM4MS4xIDgxLjkgODkuNSIgZW5hYmxlLWJhY2tncm91bmQ9Im5ldyAyNDUuOCAzODEuMSA4MS45IDg5LjUiIHhtbDpzcGFjZT0icHJlc2VydmUiPg0KPHBhdGggZmlsbD0iI2ZmZiIgZD0iTTI5NS4yLDM4Ny4yYy01LjEsNS4xLTUuMSwxMy4zLDAsMTguM2MzLjgsMy44LDkuMyw0LjcsMTMuOSwyLjlsNy4yLTcuMmMxLjgtNC43LDAuOS0xMC4yLTIuOS0xMy45DQoJQzMwOC41LDM4Mi4xLDMwMC4zLDM4Mi4xLDI5NS4yLDM4Ny4yeiBNMzA5LjcsNDAxLjZjLTIuOSwyLjktNy42LDIuOS0xMC42LDBjLTIuOS0yLjktMi45LTcuNiwwLTEwLjZjMi45LTIuOSw3LjYtMi45LDEwLjYsMA0KCUMzMTIuNiwzOTQsMzEyLjYsMzk4LjcsMzA5LjcsNDAxLjZ6Ii8+DQo8cGF0aCBmaWxsPSIjZmZmIiBkPSJNMjY4LjEsNDU0Yy0xMy4yLTEwLjEtMTYuMS0yOS02LjQtNDIuNmM0LTUuNiw5LjQtOS40LDE1LjQtMTEuNGwtMi0xMC4yYy04LjUsMi41LTE2LjIsNy43LTIxLjcsMTUuNQ0KCWMtMTIuOSwxOC4yLTguOSw0My41LDguOCw1N2wtNS42LDguM2wyNS45LTEuMmwtOC42LTIzLjZMMjY4LjEsNDU0eiIvPg0KPHBhdGggZmlsbD0iI2ZmZiIgZD0iTTMxOC4zLDQwMy4zYzEuMS0yLjEsMS43LTQuNSwxLjctN2MwLTguNC02LjgtMTUuMi0xNS4yLTE1LjJzLTE1LjIsNi44LTE1LjIsMTUuMnM2LjgsMTUuMiwxNS4yLDE1LjINCgljMi4xLDAsNC4xLTAuNCw1LjktMS4yYzguNCwxMC42LDkuMiwyNS44LDEsMzcuMmMtMy45LDUuNi05LjQsOS40LTE1LjQsMTEuNGwyLDEwLjJjOC41LTIuNSwxNi4yLTcuNywyMS43LTE1LjUNCglDMzMxLjIsNDM4LjEsMzI5LjksNDE3LjQsMzE4LjMsNDAzLjN6IE0zMDQuOCw0MDMuM2MtMy44LDAtNi45LTMuMS02LjktNi45czMuMS02LjksNi45LTYuOXM2LjksMy4xLDYuOSw2LjkNCglTMzA4LjcsNDAzLjMsMzA0LjgsNDAzLjN6Ii8+DQo8L3N2Zz4=';
		if ( ! Revisr_Admin::is_doing_setup() ) {
			$this->page_hooks['menu'] 			= add_menu_page( __( 'Dashboard', 'revisr' ), __( 'Revisr', 'revisr' ), $cap, 'revisr', array( $this, 'include_page' ), $icon_svg );
			$this->page_hooks['dashboard'] 		= add_submenu_page( 'revisr', __( 'Revisr - Dashboard', 'revisr' ), __( 'Dashboard', 'revisr' ), $cap, 'revisr', array( $this, 'include_page' ) );
			$this->page_hooks['commits'] 		= add_submenu_page( 'revisr', __( 'Revisr - Commits', 'revisr' ), __( 'Commits', 'revisr' ), $cap, 'revisr_commits', array( $this, 'include_page' ) );
			$this->page_hooks['new_commit'] 	= add_submenu_page( NULL, __( 'Revisr - New Commit', 'revisr' ), __( 'New Commit', 'revisr' ), $cap, 'revisr_new_commit', array( $this, 'include_page' ) );
			$this->page_hooks['view_commit'] 	= add_submenu_page( NULL, __( 'Revisr - View Commit', 'revisr' ), __( 'View Commit', 'revisr' ), $cap, 'revisr_view_commit', array( $this, 'include_page' ) );
			$this->page_hooks['branches'] 		= add_submenu_page( 'revisr', __( 'Revisr - Branches', 'revisr' ), __( 'Branches', 'revisr' ), $cap, 'revisr_branches', array( $this, 'include_page' ) );
			$this->page_hooks['settings'] 		= add_submenu_page( 'revisr', __( 'Revisr - Settings', 'revisr' ), __( 'Settings', 'revisr' ), $cap, 'revisr_settings', array( $this, 'include_page' ) );
			$this->page_hooks['setup'] 			= add_submenu_page( NULL, __( 'Revisr - Setup', 'revisr' ), 'Revisr', $cap, 'revisr_setup', array( $this, 'include_page' ) );
		} else {
			$this->page_hooks['setup'] 			= add_menu_page( __( 'Revisr Setup', 'revisr' ), __( 'Revisr', 'revisr' ), $cap, 'revisr_setup', array( $this, 'include_page' ), $icon_svg );
			$this->page_hooks['dashboard'] 		= add_submenu_page( null, __( 'Revisr - Dashboard', 'revisr' ), __( 'Dashboard', 'revisr' ), $cap, 'revisr', array( $this, 'include_page' ) );
			$this->page_hooks['branches'] 		= add_submenu_page( NULL, __( 'Revisr - Branches', 'revisr' ), __( 'Branches', 'revisr' ), $cap, 'revisr_branches', array( $this, 'include_page' ) );
			$this->page_hooks['settings'] 		= add_submenu_page( NULL, __( 'Revisr - Settings', 'revisr' ), __( 'Settings', 'revisr' ), $cap, 'revisr_settings', array( $this, 'include_page' ) );
		}

	}

	/**
	 * Filters the display order of the menu pages.
	 * @access public
	 */
	public function revisr_submenu_order( $menu_ord ) {
		global $submenu;
	    $arr = array();

		if ( isset( $submenu['revisr'] ) && ! Revisr_Admin::is_doing_setup()  ) {
		    $arr[] = $submenu['revisr'][0];
		    $arr[] = $submenu['revisr'][1];
		    $arr[] = $submenu['revisr'][2];
		    $arr[] = $submenu['revisr'][3];
		    $submenu['revisr'] = $arr;
		}
	    return $menu_ord;
	}

	/**
	 * Filters the parent_file for the new/view commit pages.
	 * @access public
	 * @param  string $file
	 * @return string
	 */
	public function revisr_parent_file( $file ) {
		global $plugin_page;

		if ( 'revisr_new_commit' === $plugin_page || 'revisr_view_commit' === $plugin_page ) {
			$plugin_page = 'revisr_commits';
		}

		return $file;
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
				$message 	.= sprintf( __( '<br>Click <a href="%s" class="thickbox" title="Error Details">here</a> for more details, or try again.', 'revisr' ), $error_url );
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

		if ( revisr()->git->is_repo ) {

			$untracked = revisr()->git->count_untracked();

			if ( $untracked != 0 ) {
				$text 		= sprintf( _n( '%d Untracked File', '%d Untracked Files', $untracked, 'revisr' ), $untracked );
				$args 		= array(
					'id'    => 'revisr',
					'title' => $text,
					'href'  => get_admin_url() . 'admin.php?page=revisr_new_commit',
					'meta'  => array( 'class' => 'revisr_commits' ),
				);
				$wp_admin_bar->add_node( $args );
			}

			$wp_admin_bar->add_menu( array(
				'id' 		=> 'revisr-new-commit',
				'title' 	=> __( 'Commit', 'revisr' ),
				'parent' 	=> 'new-content',
				'href' 		=> get_admin_url() . 'admin.php?page=revisr_new_commit',
				)
			);

		}

	}

	/**
	 * Returns the data for the AJAX buttons.
	 * @access public
	 */
	public function ajax_button_count() {
		if ( $_REQUEST['data'] == 'unpulled' ) {
			echo revisr()->git->count_unpulled();
		} else {
			echo revisr()->git->count_unpushed();
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
	 * Downloads the system info.
	 * @access public
	 */
	public function download_sysinfo() {
		if ( ! current_user_can( Revisr::get_capability() ) ) {
			return;
		}

		nocache_headers();

		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="revisr-system-info.txt"' );

		echo wp_strip_all_tags( $_POST['revisr-sysinfo'] );
		die();
	}

	/**
	 * Helper function for determining if we're in setup mode.
	 * @access public
	 * @return boolean
	 */
	public static function is_doing_setup() {

		if ( revisr()->git->is_repo ) {
			return false;
		} else {
			if ( defined( 'REVISR_SKIP_SETUP' ) || get_transient( 'revisr_skip_setup' ) ) {
				return false;
			}
			return true;
		}

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
	 * Returns an array of details on the provided commit.
	 * @access public
	 * @param  string $hash The SHA1 of the commit to check.
	 * @return array
	 */
	public static function get_commit_details( $hash ) {

		// Build an associative array of details.
		$commit_details = array(
			'hash' 				=> $hash,
			'branch' 			=> '',
			'author' 			=> '',
			'subject' 			=> __( 'Commit not found', 'revisr' ),
			'time' 				=> time(),
			'files_changed' 	=> 0,
			'committed_files' 	=> array(),
			'has_db_backup'		=> false,
			'tag' 				=> '',
			'status' 			=> false,
		);

		// Try to get some basic data about the commit.
		$commit = revisr()->git->run( 'show', array( '--pretty=format:%s|#|%an|#|%at', '--name-status', '--root', '-r', $hash ) );

		if ( is_array( $commit ) ) {

			$commit_meta = $commit[0];
			$commit_meta = explode( '|#|', $commit_meta );
			unset( $commit[0] );

			$commit_details['subject'] 	= $commit_meta[0];
			$commit_details['author'] 	= $commit_meta[1];
			$commit_details['time'] 	= $commit_meta[2];

			$commit = array_filter( $commit );
			$backed_up_tables = preg_grep( '/revisr.*sql/', $commit );

			if ( 0 !== count( $backed_up_tables ) ) {
				$commit_details['has_db_backup'] = true;
			}

			$commit_details['files_changed'] 	= count( $commit );
			$commit_details['committed_files'] 	= $commit;

			$branches = revisr()->git->run( 'branch', array( '--contains', $hash ) );
			$commit_details['branch'] = is_array( $branches ) ? implode( ', ', $branches ) : __( 'Unknown', 'revisr' );
			$commit_details['status'] = __( 'Committed', 'revisr' );

		}

		// Return the array.
		return $commit_details;
	}

	/**
	 * Logs an event to the database.
	 * @access public
	 * @param  string $message The message to show in the Recent Activity.
	 * @param  string $event   Will be used for filtering later.
	 */
	public static function log( $message, $event ) {

		global $wpdb;

		$time  	= current_time( 'mysql' );
		$user 	= wp_get_current_user();
		$table 	= Revisr::get_table_name();

		$wpdb->insert(
			"$table",
			array(
				'time' 		=> $time,
				'message'	=> $message,
				'event' 	=> $event,
				'user' 		=> $user->user_login,
			),
			array(
				'%s',
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
	 * @param  boolean $errors_only Whether or not to only display errors.
	 */
	public function render_alert( $errors_only = false ) {
		$alert = get_transient( 'revisr_alert' );
		$error = get_transient( 'revisr_error' );

		if ( $error ) {
			$alert = '<div class="revisr-alert error">' . wpautop( $error ) . '</div>';
		} else if ( $alert ) {
			$alert = '<div class="revisr-alert updated">' . wpautop( $alert ) . '</div>';
		} else {
			if ( revisr()->git->count_untracked() == '0' ) {
				$msg 	= sprintf( __( 'There are currently no untracked files on branch %s.', 'revisr' ), revisr()->git->branch );
				$alert 	= '<div class="revisr-alert updated"><p>' . $msg . '</p></div>';
			} else {
				$link 	= get_admin_url() . 'admin.php?page=revisr_new_commit';
				$msg 	= sprintf( __( 'There are currently %d untracked files on branch %s. <a href="%s">Commit</a> your changes to save them.', 'revisr' ), revisr()->git->count_untracked(), revisr()->git->branch, $link );
				$alert 	= '<div class="revisr-alert updated"><p>' . $msg . '</p></div>';
			}
		}

		if ( $errors_only && false !== $error ) {
			echo $alert;
		} else if ( ! $errors_only ) {
			echo $alert;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			exit();
		}

	}

	/**
	 * Processes a diff request.
	 * @access public
	 */
	public function view_diff() {

		if ( isset( $_REQUEST['commit'] ) ) {
			$diff = revisr()->git->run( 'show', array( $_REQUEST['commit'], $_REQUEST['file'] ) );
		} else {
			$diff = revisr()->git->run( 'diff', array( $_REQUEST['file'] ) );
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

		// We may need to exit early if doing_ajax.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			exit();
		}
	}

	/**
	 * Processes a view error request.
	 * @access public
	 */
	public function view_error() {
		if ( $revisr_error = get_transient( 'revisr_error_details' ) ) {
			echo implode( '<br>', $revisr_error );
		} else {
			_e( 'Detailed error information not available.', 'revisr' );
		}
	}

	/**
	 * Processes a view status request.
	 * @access public
	 */
	public function view_status() {
		if ( wp_verify_nonce( $_GET['revisr_status_nonce'], 'revisr_view_status' ) ) {
			$status = revisr()->git->run( 'status', array() );

			if ( is_array( $status ) ) {
				echo '<pre>';
				foreach ( $status as $line ) {
					echo $line . PHP_EOL;
				}
				echo '</pre>';
			} else {
				_e( 'Error retrieving the status of the repository.', 'revisr' );
			}
		}
	}

	/**
	 * Updates user settings to be compatible with 1.8.
	 * @access public
	 */
	public function do_upgrade() {
		global $wpdb;

		// For users upgrading from 1.7 and older.
		if ( get_option( 'revisr_db_version' ) === '1.0' ) {

			// Check for the "auto_push" option and save it to the config.
			if ( isset( revisr()->options['auto_push'] ) ) {
				revisr()->git->set_config( 'revisr', 'auto-push', 'true' );
			}

			// Check for the "auto_pull" option and save it to the config.
			if ( isset( revisr()->options['auto_pull'] ) ) {
				revisr()->git->set_config( 'revisr', 'auto-pull', 'true' );
			}

			// Check for the "reset_db" option and save it to the config.
			if ( isset( revisr()->options['reset_db'] ) ) {
				revisr()->git->set_config( 'revisr', 'import-checkouts', 'true' );
			}

			// Check for the "mysql_path" option and save it to the config.
			if ( isset( revisr()->options['mysql_path'] ) ) {
				revisr()->git->set_config( 'revisr', 'mysql-path', revisr()->options['mysql_path'] );
			}

			// Configure the database tracking to use all tables, as this was how it behaved in 1.7.
			revisr()->git->set_config( 'revisr', 'db_tracking', 'all_tables' );
		}

		// Upgrades from the "revisr_commits" custom post type to pure Git.
		$table 		= Revisr::get_table_name();
		$commits 	= $wpdb->get_results( "SELECT * FROM $table WHERE event = 'commit'", ARRAY_A );

		if ( is_array( $commits ) && ! empty( $commits ) ) {

			foreach ( $commits as $commit ) {
				// Get the commit short hash from the message.
				$msg_array 	= explode( '#', $commit['message'] );
				$commit_id 	= substr( $msg_array[1], 0, 7 );

				// Prepare the new message.
				$new_msg 	= sprintf(
					__( 'Committed <a href="%s">#%s</a> to the local repository.', 'revisr' ),
					get_admin_url() . 'admin.php?page=revisr_view_commit&commit=' . $commit_id,
					$commit_id
				);

				// Update the existing message.
				$query = $wpdb->prepare(
					"UPDATE $table SET message = %s WHERE id = '%d'",
					$new_msg,
					$commit['id']
				);

				$wpdb->query( $query );
			}

		}

		// Update the database schema using dbDelta.
		Revisr::revisr_install();

	}

	/**
	 * Displays the "Sponsored by Site5" logo.
	 * @access public
	 */
	public function site5_notice() {
		$allowed_pages = array(
			'revisr',
			'revisr_branches',
			'revisr_commits',
			'revisr_settings',
			'revisr_new_commit',
			'revisr_view_commit'
		);

		if ( isset( $_GET['page'] ) && in_array( $_GET['page'], $allowed_pages ) ) {
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
	 * Includes custom page templates used in the backend.
	 * @access public
	 */
	public function include_page() {

		$page = filter_input( INPUT_GET, 'page' );

		switch ( $page ) {

			case 'revisr_branches':
				$file = 'branches.php';
				break;

			case 'revisr_commits':
				$file = 'commits.php';
				break;

			case 'revisr_new_commit':
				$file = 'new-commit.php';
				break;

			case 'revisr_view_commit':
				$file = 'view-commit.php';
				break;

			case 'revisr_settings':
				$file = 'settings.php';
				break;

			case 'revisr_setup':
				$file = 'setup.php';
				break;

			case 'revisr':
			default:
				$file = 'dashboard.php';
				break;

		}

		require_once ( REVISR_PATH . "templates/pages/$file" );
	}

	/**
	 * Includes a form template.
	 * @access public
	 */
	public function include_form() {
		if ( isset( $_REQUEST['action'] ) && 'revisr_' === substr( $_REQUEST['action'], 0, 7 ) ) {
			$file = REVISR_PATH . 'templates/partials/' . str_replace( '_', '-', substr( $_REQUEST['action'], 7 ) ) . '.php';
			if ( file_exists( $file ) ) {
				include_once( $file );
			}
		}
	}
	/**
	 * Helper function for writing to the wp-config.php file,
	 * taken from WP Super Cache.
	 *
	 * @access public
	 * @return boolean
	 */
	public static function replace_config_line( $old, $new, $file = '' ) {

		if ( $file === '' ) {
			if ( file_exists( ABSPATH . 'wp-config.php') ) {
				$file = ABSPATH . 'wp-config.php';
			} else {
				$file = dirname(ABSPATH) . '/wp-config.php';
			}
		}

		if ( @is_file( $file ) == false ) {
			return false;
		}
		if (!is_writeable( $file ) ) {
			return false;
		}

		$found = false;
		$lines = file($file);
		foreach( (array)$lines as $line ) {
		 	if ( preg_match("/$old/", $line)) {
				$found = true;
				break;
			}
		}
		if ($found) {
			$fd = fopen($file, 'w');
			foreach( (array)$lines as $line ) {
				if ( !preg_match("/$old/", $line))
					fputs($fd, $line);
				else {
					fputs($fd, "$new // Added by Revisr\n");
				}
			}
			fclose($fd);
			return true;
		}
		$fd = fopen($file, 'w');
		$done = false;
		foreach( (array)$lines as $line ) {
			if ( $done || !preg_match('/^(if\ \(\ \!\ )?define|\$|\?>/', $line) ) {
				fputs($fd, $line);
			} else {
				fputs($fd, "$new // Added by Revisr\n");
				fputs($fd, $line);
				$done = true;
			}
		}
		fclose($fd);
		return true;
	}

}
