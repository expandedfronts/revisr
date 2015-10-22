<?php
/**
 * class-revisr-commits-table.php
 *
 * Displays the custom WP_List_Table on the Revisr Commits page.
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

class Revisr_Commits_Table extends WP_List_Table {

	/**
	 * Constructs the class.
	 * @access public
	 */
	public function __construct() {
		// Load the parent class on the appropriate hook.
		add_action( 'load-revisr_page_revisr_commits', array( $this, 'load' ) );
		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );
	}

	/**
	 * Construct the parent class.
	 * @access public
	 */
	public function load() {
		global $status, $page;

		parent::__construct( array(
			'singular' 	=> 'commit',
			'plural'	=> 'commits'
		) );

		add_screen_option(
			'per_page',
			array(
				'default' => 20,
				'label'   => __( 'Commits per page', 'revisr' ),
				'option'  => 'edit_revisr_commits_per_page',
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
		if ( 'edit_revisr_commits_per_page' === $option ) {
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
			'hash' 		=> __( 'ID', 'revisr' ),
			'title' 	=> __( 'Commit', 'revisr' ),
			'author' 	=> __( 'Author', 'revisr' ),
			'date' 		=> __( 'Date', 'revisr' )
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
			'title'	=> array( 'title', false ),
			'date'	=> array( 'date', false )
		);
		return $sortable_columns;
	}

	/**
	 * Returns an array containing the branch information.
	 * @access public
	 * @return array
	 */
	public function get_data() {

		// Determine which branch to check, if any.
		$branch = isset( $_GET['branch'] ) ? $_GET['branch'] : revisr()->git->branch;
		if ( 'all' === $branch ) {
			$branch = '--all';
		}

		// Build the search string if necessary..
		if ( isset( $_GET['s'] ) ) {
			$search = '--grep=' . $_GET['s'];
		} else {
			$search = false;
		}

		// Get the date to start from.
		if ( isset( $_GET['revisr_time'] ) && $_GET['revisr_time'] != 'all' ) {
			$now = time();
			switch ( $_GET['revisr_time'] ) {
				case 'halfday':
					$offset = DAY_IN_SECONDS / 2;
					break;
				case 'day':
					$offset = DAY_IN_SECONDS;
					break;
				case 'week':
					$offset = DAY_IN_SECONDS * 7;
					break;
				case 'month':
				default:
					$offset = DAY_IN_SECONDS * 28;
					break;
			}
			$date = $now - $offset;
			$date = '--since=' . $date;
		} else {
			$date = false;
		}

		// Build the arguements to pass to Git.
		$args = array(
			'log',
			$branch,
			'--pretty=format:%h|#|%s|#|%an|#|%at',
		);

		// Add in search field if needed.
		if ( false !== $search ) {
			$args[] = '-i'; // case-insensitve
			$args[] = $search;
		}

		// Add in date field if needed.
		if ( false !== $date ) {
			$args[] = $date;
		}

		// Get the commits.
		$commits = revisr()->git->run( '--no-pager', $args );

		$commits_array 	= array();

		// Iterate through and pull out the data.
		if ( is_array( $commits ) && ! empty( $commits ) ) {

			foreach ( $commits as $key => $value ) {
				// Get data from Git log.
				$commit 						= explode( '|#|', $value );
				$commits_array[$key]['hash'] 	= $commit[0];
				$commits_array[$key]['title'] 	= $commit[1];
				$commits_array[$key]['author'] 	= $commit[2];
				$commits_array[$key]['date'] 	= $commit[3];
			}

		}

		// Return the array of commits.
		return $commits_array;
	}

	/**
	 * Renders the default data for a column.
	 * @access 	public
     * @param 	array 	$item 			A singular item (one full row's worth of data)
     * @param 	array 	$column_name 	The name/slug of the column to be processed
     * @return 	string
     */
	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {

			case 'date':
				$current 	= strtotime( current_time( 'mysql' ) );
				$timestamp 	= $item[$column_name];
				return sprintf( __( '%s ago', 'revisr' ), human_time_diff( $timestamp, $current ) );

			default:
				return $item[$column_name];

		}

	}

	/**
	 * Renders the title/commit subject column.
	 * @access public
	 * @return string
	 */
	public function column_title( $item ) {
		$view_url 			= get_admin_url() . 'admin.php?page=revisr_view_commit&commit=' . $item['hash'];
		$revert_url 		= get_admin_url() . 'admin-post.php?action=revisr_revert_form&commit=' . $item['hash'] . '&TB_iframe=true&width=400&height=225';
		$title 				= '<strong><a href="' . $view_url . '">' . $item['title'] . '</a></strong>';
		$actions['view'] 	= '<a href="' . $view_url . '">' . __( 'View', 'revisr' ) . '</a>';
		$actions['revert'] 	= '<a class="thickbox" title="' . __( 'Revert', 'revisr' ) . '" href="' . $revert_url . '">' . __( 'Revert', 'revisr' ) . '</a>';

		return $title . $this->row_actions( $actions );
	}

	/**
	 * Called when no commits are found.
	 * @access public
	 */
	public function no_items() {
		_e( 'No commits found.', 'revisr' );
	}

	/**
	 * Prepares the data for display.
	 * @access public
	 */
	public function prepare_items() {
		global $wpdb;

		// Number of items per page.
		$per_page = $this->get_items_per_page( 'edit_revisr_commits_per_page', 10 );

		// Set up the custom columns.
        $columns 	= $this->get_columns();
        $hidden 	= array();
        $sortable 	= $this->get_sortable_columns();

        // Builds the list of column headers.
        $this->_column_headers = array( $columns, $hidden, $sortable );

        // Get the data to populate into the table.
        $data = $this->get_data();

        // Handle sorting of the data.
        function usort_reorder( $a,$b ) {
        	// Sort by date, descending by default.
            $orderby 	= ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'date';
            $order 		= ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'desc';
            $result 	= strcmp($a[$orderby], $b[$orderby]);
            return ( $order==='asc' ) ? $result : -$result;
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
            'orderby'		=> ! empty( $_REQUEST['orderby'] ) && '' != $_REQUEST['orderby'] ? $_REQUEST['orderby'] : 'date',
            'order'			=> ! empty( $_REQUEST['order'] ) && '' != $_REQUEST['order'] ? $_REQUEST['order'] : 'desc'
        ) );
	}

	/**
	 * Displays the table.
	 * @access public
	 */
	public function display() {
		wp_nonce_field( 'revisr-commits-nonce', 'revisr_commits_nonce', false );

		echo '<input type="hidden" id="order" name="order" value="' . $this->_pagination_args['order'] . '" />';
		echo '<input type="hidden" id="orderby" name="orderby" value="' . $this->_pagination_args['orderby'] . '" />';

		parent::display();
	}

	/**
	 * Displays the "subsubsub" list.
	 * @access public
	 */
	public function render_views() {
		$branches 	= revisr()->git->get_branches();
		$views 		= array();
		$base_url 	= get_admin_url() . 'admin.php?page=revisr_commits';

		if ( is_array( $branches ) && ! empty( $branches ) ) {
			foreach ( $branches as $branch ) {

				if ( substr( $branch, 0, 2) === '* ' ) {

					$branch = substr( $branch, 2 );
					$count 	= revisr()->git->run( 'rev-list', array( '--count', $branch ) );
					$count 	= is_array( $count ) ? absint( $count[0] ) : 0;

					if ( ! isset( $_GET['branch'] ) || $_GET['branch'] === $branch ) {
						$views[$branch] = '<a class="current" href="#">' . $branch . ' <span class="count">(' . $count . ')</span></a>';
					} else {
						$views[$branch] = '<a href="' . $base_url . '&branch=' . $branch . '">' . $branch . ' <span class="count">(' . $count . ')</span></a>';
					}

				} else {

					$branch = trim( $branch );
					$count 	= revisr()->git->run( 'rev-list', array( '--count', $branch ) );
					$count 	= is_array( $count ) ? absint( $count[0] ) : 0;

					if ( isset( $_GET['branch'] ) && $_GET['branch'] === $branch ) {
						$views[$branch] = '<a class="current" href="#">' . $branch . ' <span class="count">(' . $count . ')</span></a>';
					} elseif ( isset( $_GET['branch'] ) && 'all' === $branch ) {
						$views[$branch] = '<a href="' . $base_url . '&branch=' . $branch . '">' . $branch . '</a>';
					} else {
						$views[$branch] = '<a href="' . $base_url . '&branch=' . $branch . '">' . $branch . ' <span class="count">(' . $count . ')</span></a>';
					}

				}

			}
		}

		// Start outputting the list.
		echo '<ul class="subsubsub">';
		foreach ( $views as $class => $view ) {
			$views[$class] = "\t<li class='$class'>$view";
		}

		// Render the "All" link.
		$all_class = isset( $_GET['branch'] ) && 'all' === $_GET['branch'] ? 'current' : '';
		$all_count = revisr()->git->run( 'rev-list', array( '--count', '--all' ) );
		$all_count = is_array( $all_count ) ? absint( $all_count[0] ) : 0;
		echo '<li class="all"><a class="' . $all_class . '" href="' . $base_url . '&branch=all">' . __( 'All', 'revisr' ) . ' <span class="count">(' . $all_count . ')</span></a> |</li>';

		// Render the branches.
		echo implode( " |</li>\n", $views ) . "</li>\n";
		echo '</ul>';
	}

	/**
	 * Displays the search box.
	 * @access public
	 */
	public function render_search() {
		$value = isset( $_GET['s'] ) ? esc_attr( $_GET['s'] ) : '';

		?>
		<p class="search-box">
			<label class="screen-reader-text" for="post-search-input"><?php _e( 'Search Commits:', 'revisr' ); ?></label>
			<input type="search" id="post-search-input" name="s" value="<?php echo $value; ?>">
			<input type="submit" id="search-submit" class="button" value="Search Commits">
		</p>
		<?php
	}

	/**
	 * Renders the top or bottom tablenav.
	 * @access public
	 * @param  string $which Which tablenav to display (top or bottom)
	 */
	public function display_tablenav( $which ) {
	    ?>
	    <div class="tablenav <?php echo esc_attr( $which ); ?>">

	        <?php
	        	$this->extra_tablenav( $which );
	        	$this->pagination( $which );
	        ?>

	        <br class="clear" />

	    </div>
	    <?php
	}

	/**
	 * Extra table navigation.
	 * @access public
	 * @param  string $which
	 * @return string
	 */
	public function extra_tablenav( $which ) {
		$filter_time 	= isset( $_REQUEST['revisr_time'] ) ? $_REQUEST['revisr_time'] : 'all';
		if ( 'top' === $which ) {
			?>
				<select id="revisr-time-select" name="revisr_time">
					<option value="all"><?php _e( 'All Time', 'revisr' ); ?></option>
					<option value="halfday" <?php selected( 'halfday', $filter_time ); ?>><?php _e( 'Last 12 Hours', 'revisr' ); ?></option>
					<option value="day" <?php selected( 'day', $filter_time ); ?>><?php _e( 'Last 24 Hours', 'revisr' ); ?></option>
					<option value="week" <?php selected( 'week', $filter_time ); ?>><?php _e( 'Last Week', 'revisr' ); ?></option>
					<option value="month" <?php selected( 'month', $filter_time ); ?>><?php _e( 'Last Month', 'revisr' ); ?></option>
				</select>
				<input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter">
			<?php
		}
	}

}
