<?php
/**
 * class-revisr-admin-pages.php
 *
 * Creates and loads admin pages and scripts.
 *
 * @package   	Revisr
 * @license   	GPLv3
 * @link      	https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr_Admin_Pages {

	/**
	 * An array of page hooks returned by add_menu_page and add_submenu_page.
	 * @var array
	 */
	public $page_hooks = array();

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
	 * Registers and enqueues css and javascript files.
	 * @access public
	 * @param string $hook The page to enqueue the styles/scripts.
	 */
	public function scripts( $hook ) {

		// Start registering/enqueuing scripts if the hook is in our allowed pages.
		if ( in_array( $hook, $this->page_hooks ) ) {

			// Registers all CSS and JS files used by Revisr.
			wp_register_style( 'revisr_admin_css', REVISR_URL . 'assets/css/revisr-admin.css', array(), REVISR_VERSION );
			wp_register_style( 'revisr_octicons_css', REVISR_URL . 'assets/lib/octicons/octicons.css', array(), REVISR_VERSION );
			wp_register_style( 'revisr_select2_css', REVISR_URL . 'assets/lib/select2/css/select2.min.css', array(), REVISR_VERSION );
			wp_register_script( 'revisr_dashboard', REVISR_URL . 'assets/js/revisr-dashboard.js', 'jquery', REVISR_VERSION, true );
			wp_register_script( 'revisr_staging', REVISR_URL . 'assets/js/revisr-staging.js', 'jquery', REVISR_VERSION, false );
			wp_register_script( 'revisr_settings', REVISR_URL . 'assets/js/revisr-settings.js', 'jquery', REVISR_VERSION, true );
			wp_register_script( 'revisr_setup', REVISR_URL . 'assets/js/revisr-setup.js', 'jquery', REVISR_VERSION, true );
			wp_register_script( 'revisr_select2_js', REVISR_URL . 'assets/lib/select2/js/select2.min.js', 'jquery', REVISR_VERSION, true );

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
					wp_localize_script( 'revisr_staging', 'staging_vars', array(
						'ajax_nonce' 		=> wp_create_nonce( 'staging_nonce' ),
						'view_diff' 		=> __( 'View Diff', 'revisr' ),
						)
					);
					break;

			}

		}

	}

	/**
	 * Filters the display order of the menu pages.
	 * @access public
	 */
	public function submenu_order( $menu_ord ) {
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
	public function parent_file( $file ) {
		global $plugin_page;

		if ( 'revisr_new_commit' === $plugin_page || 'revisr_view_commit' === $plugin_page ) {
			$plugin_page = 'revisr_commits';
		}

		return $file;
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
	 * Displays the link to the settings on the WordPress plugin page.
	 * @access public
	 * @param array $links The links assigned to Revisr.
	 */
	public function settings_link( $links ) {
		$settings_link = '<a href="' . get_admin_url() . 'admin.php?page=revisr_settings">' . __( 'Settings', 'revisr' ) . '</a>';
  		array_unshift( $links, $settings_link );
  		return $links;
	}

}
