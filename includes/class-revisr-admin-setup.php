<?php
/**
 * class-revisr-setup.php
 *
 * Installs and configures Revisr.
 * 
 * @package   	Revisr
 * @license   	GPLv3
 * @link      	https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Disallow direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr_Setup {
	
	/**
	 * The WordPress database object.
	 * @var object
	 */
	protected $wpdb;

	/**
	 * A reference back to the main Revisr instance.
	 * @var object
	 */
	protected $revisr;

	/**
	 * User options and preferences.
	 * @var array
	 */
	protected $options;

	/**
	 * Load items necessary for setup.
	 * @access public
	 */
	public function __construct( $options ) {
		global $wpdb;
		$this->wpdb 	= $wpdb;
		$this->options 	= $options;
		$this->revisr 	= Revisr::get_instance();
	}

	/**
	 * Registers and enqueues css and javascript files.
	 * @access public
	 * @param string $hook The page to enqueue the styles/scripts.
	 */
	public function revisr_scripts( $hook ) {
		
		// Register all CSS files used by Revisr.
		wp_register_style( 'revisr_dashboard_css', REVISR_URL . 'assets/css/dashboard.css', array(), '07052014' );
		wp_register_style( 'revisr_commits_css', REVISR_URL . 'assets/css/commits.css', array(), '08202014' );
		wp_register_style( 'revisr_octicons_css', REVISR_URL . 'assets/octicons/octicons.css', array(), '01152015' );
		
		// Register all JS files used by Revisr.
		wp_register_script( 'revisr_dashboard', REVISR_URL . 'assets/js/revisr-dashboard.js', 'jquery',  '09232014', true );
		wp_register_script( 'revisr_staging', REVISR_URL . 'assets/js/revisr-staging.js', 'jquery', '07052014', false );
		wp_register_script( 'revisr_committed', REVISR_URL . 'assets/js/revisr-committed.js', 'jquery', '07052014', false );
		wp_register_script( 'revisr_settings', REVISR_URL . 'assets/js/revisr-settings.js', 'jquery', '08272014', true );
		
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

			if ( 'post-new.php' === $hook ) {

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

			} elseif ( 'post.php' === $hook ) {

				// Enqueue scripts for the "View Commit" screen.
				wp_enqueue_script( 'revisr_committed' );
				wp_localize_script( 'revisr_committed', 'committed_vars', array(
					'post_id' 		=> $_GET['post'],
					'ajax_nonce' 	=> wp_create_nonce( 'committed_nonce' ),
					)
				);

			}

			wp_enqueue_style( 'revisr_commits_css' );
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_style( 'revisr_octicons_css' );
			wp_enqueue_script( 'thickbox' );
			wp_dequeue_script( 'autosave' );
		}

	}
	
	/**
	 * Registers the menus used by Revisr.
	 * @access public
	 */
	public function menus() {
		$menu = add_menu_page( __( 'Dashboard', 'revisr' ), 'Revisr', 'manage_options', 'revisr', array( $this, 'revisr_dashboard' ), REVISR_URL . 'assets/img/white_18x20.png' );
		add_submenu_page( 'revisr', __( 'Revisr - Dashboard', 'revisr' ), __( 'Dashboard', 'revisr' ), 'manage_options', 'revisr', array( $this, 'revisr_dashboard' ) );
		add_submenu_page( 'revisr', __( 'Revisr - Branches', 'revisr' ), __( 'Branches', 'revisr' ), 'manage_options', 'revisr_branches', array( $this, 'revisr_branches' ) );
		add_submenu_page( 'revisr', __( 'Revisr - Settings', 'revisr' ), __( 'Settings', 'revisr' ), 'manage_options', 'revisr_settings', array( $this, 'revisr_settings' ) );
		add_action( 'admin_print_scripts-' . $menu, array( $this, 'revisr_scripts' ) );
		remove_meta_box( 'authordiv', 'revisr_commits', 'normal' );
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
	 * Displays the number of files changed in the admin bar.
	 * @access public
	 */
	public function admin_bar( $wp_admin_bar ) {
		if ( $this->revisr->git->count_untracked() != 0 ) {
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

	/**
	 * Updates user settings to be compatible with 1.8.
	 * @access public
	 */
	public function do_upgrade() {

		// Check for the "auto_push" option and save it to the config.
		if ( isset( $this->options['auto_push'] ) ) {
			$this->revisr->git->set_config( 'revisr', 'auto-push', 'true' );
		}

		// Check for the "auto_pull" option and save it to the config.
		if ( isset( $this->options['auto_pull'] ) ) {
			$this->revisr->git->set_config( 'revisr', 'auto-pull', 'true' );
		}

		// Check for the "reset_db" option and save it to the config.
		if ( isset( $this->options['reset_db'] ) ) {
			$this->revisr->git->set_config( 'revisr', 'import-checkouts', 'true' );
		}

		// Check for the "mysql_path" option and save it to the config.
		if ( isset( $this->options['mysql_path'] ) ) {
			$this->revisr->git->set_config( 'revisr', 'mysql-path', $this->options['mysql_path'] );
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
}