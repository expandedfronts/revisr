<?php
/**
 * class-revisr-list-table.php
 *
 * Displays the custom WP_List_Table on the Revisr Dashboard.
 *
 * @package 	Revisr
 * @license 	GPLv3
 * @link 		https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

// Include WP_List_Table if it isn't already loaded.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . '/wp-admin/includes/class-wp-list-table.php' );
}



class Revisr_List_Table extends WP_List_Table {

	/**
	 * The main Revisr instance.
	 * @var Revisr
	 */
	protected $revisr;

	/**
	 * Initiate the class and add necessary action hooks.
	 * @access public
	 */
	public function __construct(){

		// Prevent PHP notices from breaking AJAX.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			error_reporting( ~E_NOTICE & ~E_STRICT );
		}

		// Grab the instance and load the parent class on the appropriate hook.
		$this->revisr = revisr();

		add_action( 'load-toplevel_page_revisr', array( $this, 'load' ) );
		add_action( 'wp_ajax_revisr_get_custom_list', array( $this, 'ajax_callback' ) );
		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );
	}

	/**
	 * Construct the parent class.
	 * @access public
	 */
	public function load() {
		global $status, $page;

		parent::__construct( array(
			'singular' 	=> 'activity',
			'plural'	=> 'activity',
			'ajax'		=> true
		) );

		add_screen_option(
			'per_page',
			array(
				'default' => 15,
				'label'   => __( 'Events per page', 'revisr' ),
				'option'  => 'edit_revisr_events_per_page',
			)
		);

		set_screen_options();
	}

	/**
	 * Sets the screen options for the Revisr dashboard.
	 * @access public
	 * @param  boolean 	$status This seems to be false
	 * @param  string 	$option The name of the option
	 * @param  int 		$value 	The number of events to display
	 * @return int|boolean
	 */
	public function set_screen_option( $status, $option, $value ) {
		if ( 'edit_revisr_events_per_page' === $option ) {
			return $value;
		}
		return $status;
	}

	/**
	 * Renders the default data for a column.
	 * @access 	public
     * @param 	array $item A singular item (one full row's worth of data)
     * @param 	array $column_name The name/slug of the column to be processed
     * @return 	string
     */
	public function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case 'message':
				return ucfirst( $item[$column_name] );
			case 'time':
				$current 	= strtotime( current_time( 'mysql' ) );
				$timestamp 	= strtotime( $item[$column_name] );
				return sprintf( __( '%s ago', 'revisr' ), human_time_diff( $timestamp, $current ) );
			default:
				return print_r( $item, true );
		}
	}

	/**
	 * Returns an array of the column names.
	 * @access public
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'message' 	=> __( 'Event', 'revisr' ),
			'time'		=> __( 'Time', 'revisr' )
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
			'message'	=> array( 'message', false ),
			'time'		=> array( 'time', false )
		);
		return $sortable_columns;
	}

	/**
	 * Prepares the data for display.
	 * @access public
	 */
	public function prepare_items() {
		global $wpdb;

		// Number of items per page.
		$per_page = $this->get_items_per_page( 'edit_revisr_events_per_page', 15 );

		// Set up the custom columns.
        $columns 	= $this->get_columns();
        $hidden 	= array();
        $sortable 	= $this->get_sortable_columns();

        // Builds the list of column headers.
        $this->_column_headers = array( $columns, $hidden, $sortable );

        // Get the data to populate into the table.
        $data = $wpdb->get_results( "SELECT message, time FROM {$wpdb->prefix}revisr", ARRAY_A );

        // Handle sorting of the data.
        function usort_reorder($a,$b){
            $orderby 	= ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'time'; //If no sort, default to time.
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
		wp_nonce_field( 'revisr-list-nonce', 'revisr_list_nonce' );

		echo '<input type="hidden" id="order" name="order" value="' . $this->_pagination_args['order'] . '" />';
		echo '<input type="hidden" id="orderby" name="orderby" value="' . $this->_pagination_args['orderby'] . '" />';

		parent::display();
	}

	/**
	 * Handles the AJAX response.
	 * @access public
	 */
	public function ajax_response() {
		check_ajax_referer( 'revisr-list-nonce', 'revisr_list_nonce' );
		$this->prepare_items();

		extract( $this->_args );
		extract( $this->_pagination_args, EXTR_SKIP );

		ob_start();
		if ( ! empty( $_REQUEST['no_placeholder'] ) ) {
			$this->display_rows();
		} else {
			$this->display_rows_or_placeholder();
		}
		$rows = ob_get_clean();

		ob_start();
		$this->print_column_headers();
		$headers = ob_get_clean();

		ob_start();
		$this->pagination('top');
		$pagination_top = ob_get_clean();

		ob_start();
		$this->pagination('bottom');
		$pagination_bottom = ob_get_clean();

		$response 							= array( 'rows' => $rows );
		$response['pagination']['top'] 		= $pagination_top;
		$response['pagination']['bottom'] 	= $pagination_bottom;
		$response['column_headers'] 		= $headers;

		if ( isset( $total_items ) ) {
			$response['total_items_i18n'] = sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) );
		}

		if ( isset( $total_pages ) ) {
			$response['total_pages'] = $total_pages;
			$response['total_pages_i18n'] = number_format_i18n( $total_pages );
		}

		die( json_encode( $response ) );
	}

	/**
	 * The callback for the AJAX response.
	 * @access public
	 */
	public function ajax_callback() {
		$this->load();
		$this->ajax_response();
	}

	/**
	 * Called when no activity is found.
	 * @access public
	 */
	public function no_items() {
		_e( 'Your recent activity will show up here.', 'revisr' );
	}
}
