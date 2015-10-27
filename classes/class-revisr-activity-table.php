<?php
/**
 * class-revisr-activity-table.php
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

class Revisr_Activity_Table extends WP_List_Table {

	/**
	 * Initiate the class and add necessary action hooks.
	 * @access public
	 */
	public function __construct(){

		// Prevent PHP notices from breaking AJAX.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			error_reporting( ~E_NOTICE & ~E_STRICT );
		}

		// Load the parent class on the appropriate hook.
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
	 * Loads additional filters into the table.
	 * @access public
	 * @return string
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' === $which ) {

			$filter_event 	= isset( $_REQUEST['revisr_event'] ) ? $_REQUEST['revisr_event'] : 'all';
			$filter_user 	= isset( $_REQUEST['revisr_user'] ) ? $_REQUEST['revisr_user'] : 'all';
			$filter_time 	= isset( $_REQUEST['revisr_time'] ) ? $_REQUEST['revisr_time'] : 'all';

			?>

				<select id="revisr-events-select" name="revisr_event">
					<option value="all"><?php _e( 'All Events', 'revisr' ); ?></option>
					<option value="branch" <?php selected( 'branch', $filter_event ); ?>><?php _e( 'Branches', 'revisr' ); ?></option>
					<option value="commit" <?php selected( 'commit', $filter_event ); ?>><?php _e( 'Commits', 'revisr' ); ?></option>
					<option value="backup" <?php selected( 'backup', $filter_event ); ?>><?php _e( 'Database Backups', 'revisr' ); ?></option>
					<option value="imports" <?php selected( 'import', $filter_event ); ?>><?php _e( 'Database Imports', 'revisr' ); ?></option>
					<option value="discard" <?php selected( 'discard', $filter_event ); ?>><?php _e( 'File Discards', 'revisr' ); ?></option>
					<option value="push" <?php selected( 'push', $filter_event ); ?>><?php _e( 'Pushes', 'revisr' ); ?></option>
					<option value="pull" <?php selected( 'pull', $filter_event ); ?>><?php _e( 'Pulls', 'revisr' ); ?></option>
				</select>

				<select id="revisr-author-select" name="revisr_user">
					<option value="all"><?php _e( 'All Users', 'revisr' ); ?></option>
					<?php
						global $wpdb;
						$table = Revisr::get_table_name();
						$users = $wpdb->get_results( "SELECT DISTINCT user FROM $table ORDER BY user ASC", ARRAY_A );
						foreach ( $users as $user ) {
							if ( $user['user'] != null ) {
								printf( '<option value="%s" %s>%s</option>', esc_attr( $user['user'] ), selected( $user['user'], $filter_user, false ), esc_attr( $user['user'] ) );
							}
						}
					?>
				</select>

				<select id="revisr-time-select" name="revisr_time">
					<option value="all"><?php _e( 'All Time', 'revisr' ); ?></option>
					<option value="halfday" <?php selected( 'halfday', $filter_time ); ?>><?php _e( 'Last 12 Hours', 'revisr' ); ?></option>
					<option value="day" <?php selected( 'day', $filter_time ); ?>><?php _e( 'Last 24 Hours', 'revisr' ); ?></option>
					<option value="week" <?php selected( 'week', $filter_time ); ?>><?php _e( 'Last Week', 'revisr' ); ?></option>
					<option value="month" <?php selected( 'month', $filter_time ); ?>><?php _e( 'Last Month', 'revisr' ); ?></option>
				</select>

				<input type="submit" id="revisr-filter-submit" class="button" value="<?php _e( 'Filter', 'revisr' ); ?>" />
			<?php

			if ( $filter_event != 'all' || $filter_time != 'all' || $filter_user != 'all' ) {
				echo sprintf( '<a href="%s" id="reset"><span class="dashicons dashicons-dismiss" style="font-size:13px; text-decoration: none; margin-top: 7px;"></span><span class="record-query-reset-text">%s</span></a>', get_admin_url() . 'admin.php?page=revisr', __( 'Reset', 'revisr' ) );
			}

		}
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
				return wp_kses_post( ucfirst( $item[$column_name] ) );
			case 'time':
				$current 	= strtotime( current_time( 'mysql' ) );
				$timestamp 	= strtotime( $item[$column_name] );
				return sprintf( __( '%s ago', 'revisr' ), human_time_diff( $timestamp, $current ) );
			case 'user':
				$url = esc_url( add_query_arg( array( 'revisr_user' => urlencode( $item[$column_name] ) ), $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) );
				return sprintf( '<a href="%s">%s</a>', $url, esc_html( $item[$column_name] ) );
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
			'user'		=> __( 'User', 'revisr' ),
			'time'		=> __( 'Time', 'revisr' )
		);
		return $columns;
	}

	/**
	 * Returns an array of filters for the list table.
	 * @access public
	 * @return array
	 */
	public function get_filters() {
		$filters = array();
		$filters['revisr_event'] 	= 'event';
		$filters['revisr_user'] 	= 'user';
		$filters['revisr_time'] 	= 'time';

		return apply_filters( 'revisr_list_table_filters', $filters );
	}

	/**
	 * Returns an array of columns that are sortable.
	 * @access public
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'message'	=> array( 'message', false ),
			'user' 		=> array( 'user', false ),
			'time'		=> array( 'time', false )
		);
		return $sortable_columns;
	}

	/**
	 * Creates a where query based on our custom filters.
	 * @access public
	 */
	public function create_where() {

        $where = array();

        foreach ( $this->get_filters() as $filter => $col ) {

			if ( isset( $_REQUEST[$filter] ) && $_REQUEST[$filter] != 'all' ) {

	        	if ( 'revisr_time' === $filter ) {

	        		$value 	= $_REQUEST[$filter];
	        		$time 	= '';

	        		switch( $value ) {
	        			case 'halfday':
	        				$time = DAY_IN_SECONDS / 2;
	        				break;
	        			case 'day':
	        				$time = DAY_IN_SECONDS;
	        				break;
	    				case 'week':
	    					$time = DAY_IN_SECONDS * 7;
	    					break;
						case 'month':
							$time = DAY_IN_SECONDS * 28;
							break;

	        		}

	        		if ( $time !== '' ) {
	        			$time 		= esc_sql( date( 'Y-m-d H:i:s', time() - $time ) );
	        			$where[] 	= "$col > '$time'";
	        		}

	        	} else {
	        		$value 		= esc_sql( $_REQUEST[$filter] );
        			$where[] 	= "$col = '$value'";
	        	}

        	}
        }

        // Build out the WHERE queries.
        $where = empty( $where ) ? '' : 'WHERE ' . implode( ' AND ', $where );

        // Return the WHERE queries.
        return $where;
	}

	/**
	 * Prepares the data for display.
	 * @access public
	 */
	public function prepare_items() {
		global $wpdb;
		$table = Revisr::get_table_name();

		// Number of items per page.
		$per_page = $this->get_items_per_page( 'edit_revisr_events_per_page', 15 );

		// Set up the custom columns.
        $columns 	= $this->get_columns();
        $hidden 	= array();
        $sortable 	= $this->get_sortable_columns();

        // Builds the list of column headers.
        $this->_column_headers = array( $columns, $hidden, $sortable );

        // Run any custom filters.
        $where = $this->create_where();

        // Get the data to populate into the table.
        $data = $wpdb->get_results( "SELECT message, time, user FROM $table $where", ARRAY_A );

        // Handle sorting of the data.
        function usort_reorder($a,$b){
        	// Default to time for default sort.
        	$orderby 	= isset( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : 'time';

        	// Default to descending for default sort order.
        	$order 		= isset( $_REQUEST['order'] ) ? $_REQUEST['order'] : 'desc';

        	// Determine the sort order and send to usort.
        	$result 	= strcmp( $a[$orderby], $b[$orderby] );
        	return ( $order === 'asc' ) ? $result : -$result;
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
            'orderby'		=> isset( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : 'time',
            'order'			=> isset( $_REQUEST['order'] ) ? $_REQUEST['order'] : 'desc'
        ) );
	}

	/**
	 * Displays the table.
	 * @access public
	 */
	public function display() {
		wp_nonce_field( 'revisr-list-nonce', 'revisr_list_nonce', false );

		$filter_event 	= esc_attr( isset( $_REQUEST['revisr_event'] ) ? $_REQUEST['revisr_event'] : 'all' );
		$filter_user 	= esc_attr( isset( $_REQUEST['revisr_user'] ) ? $_REQUEST['revisr_user'] : 'all' );
		$filter_time 	= esc_attr( isset( $_REQUEST['revisr_time'] ) ? $_REQUEST['revisr_time'] : 'all' );

		echo '<input type="hidden" name="revisr_event" value="' . $filter_event . '" />';
		echo '<input type="hidden" name="revisr_user" value="' . $filter_user . '" />';
		echo '<input type="hidden" name="revisr_time" value="' . $filter_time . '" />';

		echo '<input type="hidden" id="order" name="order" value="' . $this->_pagination_args['order'] . '" />';
		echo '<input type="hidden" id="orderby" name="orderby" value="' . $this->_pagination_args['orderby'] . '" />';

		parent::display();
	}

	/**
	 * Renders the top or bottom tablenav.
	 * @access public
	 * @param  string $which Which tablenav to display (top or bottom)
	 */
	public function display_tablenav( $which ) {
	    ?>
	    <div class="tablenav <?php echo esc_attr( $which ); ?>">

	        <div class="alignleft actions">
	            <?php $this->bulk_actions(); ?>
	        </div>

	        <?php
	        	$this->extra_tablenav( $which );
	        	$this->pagination( $which );
	        ?>

	        <br class="clear" />

	    </div>
	    <?php
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
