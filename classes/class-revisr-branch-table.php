<?php
/**
 * class-revisr-branch-table.php
 *
 * Displays the custom WP_List_Table on the Revisr Branches page.
 *
 * @package 	Revisr
 * @license 	GPLv3
 * @link 		https://revisr.io
 * @copyright 	Expanded Fronts
 */

 // Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

// Include WP_List_Table if it isn't already loaded.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . '/wp-admin/includes/class-wp-list-table.php' );
}

class Revisr_Branch_Table extends WP_List_Table {

	/**
	 * Constructs the class.
	 * @access public
	 */
	public function __construct() {
		// Load the parent class on the appropriate hook.
		add_action( 'load-revisr_page_revisr_branches', array( $this, 'load' ) );
		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );
	}

	/**
	 * Construct the parent class.
	 * @access public
	 */
	public function load() {
		global $status, $page;

		parent::__construct( array(
			'singular' 	=> 'branch',
			'plural'	=> 'branches'
		) );

		add_screen_option(
			'per_page',
			array(
				'default' => 10,
				'label'   => __( 'Branches per page', 'revisr' ),
				'option'  => 'edit_revisr_branches_per_page',
			)
		);

		set_screen_options();
	}

	/**
	 * Sets the screen options for the Revisr branches page.
	 * @access public
	 * @param  boolean 	$status This seems to be false
	 * @param  string 	$option The name of the option
	 * @param  int 		$value 	The number of events to display
	 * @return int|boolean
	 */
	public function set_screen_option( $status, $option, $value ) {
		if ( 'edit_revisr_branches_per_page' === $option ) {
			return $value;
		}
		return $status;
	}

	/**
	 * Returns an array of the column names.
	 * @access public
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'branch' 	=> __( 'Branch', 'revisr' ),
			'updated'	=> __( 'Last Updated', 'revisr' ),
			'actions' 	=> __( 'Actions', 'revisr' ),
		);
		return $columns;
	}

	/**
	 * Returns an array of columns that are sortable.
	 * @access public
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'branch'	=> array( 'branch', false ),
			'updated'	=> array( 'updated', false )
		);
		return $sortable_columns;
	}

	/**
	 * Returns an array containing the branch information.
	 * @access public
	 * @return array
	 */
	public function get_data() {

		// Basic filter for viewing remote branches.
		if ( isset( $_GET['branches'] ) && $_GET['branches'] === 'remote' ) {
			// We may need to clear out the cache for the remote branches.
			revisr()->git->run( 'remote', array( 'update', '--prune' ) );
			$remote = true;
		} else {
			$remote = false;
		}

		// Get the branches.
		$branches = revisr()->git->get_branches( $remote );
		$branches_array = array();

		if ( is_array( $branches ) && ! empty( $branches ) ) {

			$count = 0;

			foreach ( $branches as $key => $value ) {

				$branch 	= substr( $value, 2 );
				$updated 	= revisr()->git->get_branch_last_updated( $branch );

				if ( substr( $value, 0, 1 ) === '*' ) {
					$current 		= true;
					$branch_title 	= sprintf( '<strong>%s %s</strong>', $branch, __( '(current branch)', 'revisr' ) );
				} else {
					$current 		= false;
					$branch_title 	= $branch;
				}

				$branches_array[$count]['branch'] 	= $branch_title;
				$branches_array[$count]['updated'] 	= $updated;
				$branches_array[$count]['actions']	= $this->get_actions( $branch, $remote, $current );

				$count++;

			}

		}

		return $branches_array;
	}

	/**
	 * Returns possible actions for a given branch.
	 * @access public
	 * @return string
	 */
	public function get_actions( $branch, $remote, $current ) {

		$admin_url 		= get_admin_url();
		$checkout_text 	= __( 'Checkout', 'revisr' );
		$merge_text 	= __( 'Merge', 'revisr' );
		$delete_text 	= __( 'Delete', 'revisr' );
		$checkout_title = __( 'Checkout Branch', 'revisr' );
		$merge_title 	= __( 'Merge Branch', 'revisr' );
		$delete_title 	= __( 'Delete Branch', 'revisr' );
		$checkout_url 	= wp_nonce_url( $admin_url . "admin-post.php?action=process_checkout&branch=" . $branch, 'process_checkout', 'revisr_checkout_nonce' );
		$merge_url 		= $admin_url . "admin-post.php?action=revisr_merge_branch_form&branch=" . $branch . "&TB_iframe=true&width=400&height=225";
		$delete_url 	= $admin_url . "admin-post.php?action=revisr_delete_branch_form&branch=" . $branch . "&TB_iframe=true&width=400&height=225";

		if ( $current ) {
			return sprintf(
				'<a class="button disabled branch-btn" onclick="preventDefault()" href="#">%s</a>
				<a class="button disabled branch-btn" onclick="preventDefault()" href="#">%s</a>
				<a class="button disabled branch-btn" onclick="preventDefault()" href="#">%s</a>',
				$checkout_text,
				$merge_text,
				$delete_text
			);
		}

		if ( ! $remote ) {

			return sprintf(
				'<a class="button branch-btn" href="%s">%s</a>
				<a class="button branch-btn thickbox" href="%s" title="%s">%s</a>
				<a class="button branch-btn thickbox" href="%s" title="%s">%s</a>',
				$checkout_url,
				$checkout_text,
				$merge_url,
				$merge_title,
				$merge_text,
				$delete_url,
				$delete_title,
				$delete_text
			);

		} else {
			$checkout_url 	= esc_url( $admin_url . 'admin-post.php?action=revisr_checkout_remote_form&branch=' . $branch . '&TB_iframe=true&width=400&height=225' );
			$merge_url 		= esc_url( $admin_url . 'admin-post.php?action=revisr_merge_branch_form&branch=' . $branch . '&TB_iframe=true&width=400&height=225' );
			$delete_url 	= esc_url( $admin_url . 'admin-post.php?action=revisr_delete_branch_form&branch=' . $branch . '&remote=true&TB_iframe=true&width=400&height=225' );

			return sprintf(
				'<a class="button branch-btn thickbox" href="%s" title="%s">%s</a>
				<a class="button branch-btn thickbox" href="%s" title="%s">%s</a>
				<a class="button branch-btn thickbox" href="%s" title="%s">%s</a>',
				$checkout_url,
				$checkout_title,
				$checkout_text,
				$merge_url,
				$merge_title,
				$merge_text,
				$delete_url,
				$delete_title,
				$delete_text
			);
		}

	}

	/**
	 * Renders the default data for a column.
	 * @access 	public
     * @param 	array 	$item 			A singular item (one full row's worth of data)
     * @param 	array 	$column_name 	The name/slug of the column to be processed
     * @return 	string
     */
	public function column_default( $item, $column_name ) {
		return $item[$column_name];
	}

	/**
	 * Called when no branches are found.
	 * @access public
	 */
	public function no_items() {
		_e( 'No branches found.', 'revisr' );
	}

	/**
	 * Prepares the data for display.
	 * @access public
	 */
	public function prepare_items() {
		global $wpdb;

		// Number of items per page.
		$per_page = $this->get_items_per_page( 'edit_revisr_branches_per_page', 10 );

		// Set up the custom columns.
        $columns 	= $this->get_columns();
        $hidden 	= array();
        $sortable 	= $this->get_sortable_columns();

        // Builds the list of column headers.
        $this->_column_headers = array( $columns, $hidden, $sortable );

        // Get the data to populate into the table.
        $data = $this->get_data();

        // Handle sorting of the data.
        function usort_reorder($a,$b){
            $orderby 	= ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'branch'; //If no sort, default to time.
            $order 		= ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'desc'; //If no order, default to desc
            $result 	= strcmp($a[$orderby], $b[$orderby]); //Determine sort order
            return ( $order==='asc' ) ? $result : -$result; //Send final sort direction to usort
        }
        usort( $data, 'usort_reorder' );

        // Pagination.
        $current_page 	= $this->get_pagenum();
        $total_items 	= count($data);
       	$data 			= array_slice($data,(($current_page-1)*$per_page),$per_page);

        $this->items = $data;
        $this->set_pagination_args( array(
            'total_items' 	=> $total_items,
            'per_page'    	=> $per_page,
            'total_pages' 	=> ceil($total_items/$per_page),
            'orderby'		=> ! empty( $_REQUEST['orderby'] ) && '' != $_REQUEST['orderby'] ? $_REQUEST['orderby'] : 'time',
            'order'			=> ! empty( $_REQUEST['order'] ) && '' != $_REQUEST['order'] ? $_REQUEST['order'] : 'desc'
        ) );
	}

	/**
	 * Displays the table.
	 * @access public
	 */
	public function display() {
		wp_nonce_field( 'revisr-branches-nonce', 'revisr_branches_nonce' );

		echo '<input type="hidden" id="order" name="order" value="' . $this->_pagination_args['order'] . '" />';
		echo '<input type="hidden" id="orderby" name="orderby" value="' . $this->_pagination_args['orderby'] . '" />';

		parent::display();
	}

	/**
	 * Extra table navigation.
	 * @access public
	 * @param  string $which
	 * @return string
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' != $which && revisr()->git->has_remote() ) {

			if ( isset( $_GET['branches'] ) && $_GET['branches'] == 'remote' ) {
				$url = esc_url( add_query_arg( array( 'branches' => 'local' ) ) );
				printf( '<a href="%s">%s</a>', $url, __( 'Local Branches', 'revisr' ) );

			} else {
				$url = esc_url( add_query_arg( array( 'branches' => 'remote' ) ) );
				printf( '<a href="%s">%s</a>', $url, __( 'Remote Branches', 'revisr' ) );
			}

		}
	}

}
