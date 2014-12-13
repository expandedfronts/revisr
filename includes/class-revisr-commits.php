<?php
/**
 * class-revisr-commits.php
 * 
 * Creates and configures the "revisr_commits" custom post type.
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 */

// Disallow direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr_Commits {

	/**
	 * The main Git class.
	 * @var Revisr_Git()
	 */
	protected $git;

	/**
	 * Initialize the class.
	 * @access public
	 */
	public function __construct() {
		$revisr 	= Revisr::get_instance();
		$this->git 	= $revisr->git;
	}

	/**
	 * Registers the "revisr_commits" post type.
	 * @access public
	 */
	public function post_types() {
		$labels = array(
			'name'                => __( 'Commits', 'revisr' ),
			'singular_name'       => __( 'Commit', 'revisr' ),
			'menu_name'           => __( 'Commits', 'revisr' ),
			'parent_item_colon'   => '',
			'all_items'           => __( 'Commits', 'revisr' ),
			'view_item'           => __( 'View Commit', 'revisr' ),
			'add_new_item'        => __( 'New Commit', 'revisr' ),
			'add_new'             => __( 'New Commit', 'revisr' ),
			'edit_item'           => __( 'Edit Commit', 'revisr' ),
			'update_item'         => __( 'Update Commit', 'revisr' ),
			'search_items'        => __( 'Search Commits', 'revisr' ),
			'not_found'           => __( 'No commits found yet, why not create a new one?', 'revisr' ),
			'not_found_in_trash'  => __( 'No commits in trash.', 'revisr' ),
		);
		$capabilities = array(
			'edit_post'           => 'activate_plugins',
			'read_post'           => 'activate_plugins',
			'delete_posts'        => 'activate_plugins',
			'edit_posts'          => 'activate_plugins',
			'edit_others_posts'   => 'activate_plugins',
			'publish_posts'       => 'activate_plugins',
			'read_private_posts'  => 'activate_plugins',
		);
		$args = array(
			'label'               => 'revisr_commits',
			'description'         => __( 'Commits made through Revisr', 'revisr' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'author' ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'revisr',
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 5,
			'menu_icon'           => '',
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capabilities'        => $capabilities,
		);
		register_post_type( 'revisr_commits', $args );
	}

	/**
	 * Custom messages for commits.
	 * @access public
	 * @param  array $messages The messages to pass back to the commits.
	 */
	public function custom_messages( $messages ) {
		$post = get_post();
		$messages['revisr_commits'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Commit updated.', 'revisr' ),
			2  => __( 'Custom field updated.', 'revisr' ),
			3  => __( 'Custom field deleted.', 'revisr' ),
			4  => __( 'Commit updated.', 'revisr' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Commit restored to revision from %s', 'revisr' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => sprintf( __( 'Committed files on branch <strong>%s</strong>.', 'revisr' ), $this->git->branch ),
			7  => __( 'Commit saved.', 'revisr' ),
			8  => __( 'Commit submitted.', 'revisr' ),
			9  => sprintf(
				__( 'Commit scheduled for: <strong>%1$s</strong>.', 'revisr' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i', 'revisr' ), strtotime( $post->post_date ) )
			),
			10 => __( 'Commit draft updated.', 'revisr' ),
		);
		return $messages;
	}

	/**
	 * Custom bulk messages for Revisr.
	 * @access public
	 * @param  array $bulk_messages The messages to display. 
	 * @param  array $bulk_counts   The number of those messages.
	 */
	public function bulk_messages( $bulk_messages, $bulk_counts ) {
		$bulk_messages['revisr_commits'] = array(
			'updated' 	=> _n( '%s commit updated.', '%s commits updated.', $bulk_counts['updated'] ),
			'locked'    => _n( '%s commit not updated, somebody is editing it.', '%s commits not updated, somebody is editing them.', $bulk_counts['locked'] ),
			'deleted'   => _n( '%s commit permanently deleted.', '%s commits permanently deleted.', $bulk_counts['deleted'] ),
			'trashed'   => _n( '%s commit moved to the Trash.', '%s commits moved to the Trash.', $bulk_counts['trashed'] ),
	    	'untrashed' => _n( '%s commit restored from the Trash.', '%s commits restored from the Trash.', $bulk_counts['untrashed'] )
	    	);
		return $bulk_messages;
	}

	/**
	 * Adds the custom actions to the Commits list.
	 * @access public
	 * @param  array $actions The default array of actions.
	 */
	public function custom_actions( $actions ) {
		if ( get_post_type() == 'revisr_commits' ) {
			if ( isset( $actions ) ) {
				unset( $actions['edit'] );
		        unset( $actions['view'] );
		        unset( $actions['trash'] );
		        unset( $actions['inline hide-if-no-js'] );

		        $id 				= get_the_ID();
		        $url 				= get_admin_url() . 'post.php?post=' . get_the_ID() . '&action=edit';
		        $actions['view'] 	= "<a href='{$url}'>" . __( 'View', 'revisr' ) . "</a>";
		        $branch_meta 		= get_post_custom_values( 'branch', get_the_ID() );
		        $db_hash_meta 		= get_post_custom_values( 'db_hash', get_the_ID() );
		        $backup_method 		= get_post_custom_values( 'backup_method', get_the_ID() );
		        $commit_hash 		= Revisr_Git::get_hash( $id );
		        $revert_nonce 		= wp_nonce_url( admin_url("admin-post.php?action=process_revert&commit_hash={$commit_hash}&branch={$branch_meta[0]}&post_id=" . get_the_ID()), 'revert', 'revert_nonce' );
		        $actions['revert'] 	= "<a href='" . $revert_nonce . "'>" . __( 'Revert Files', 'revisr' ) . "</a>";
		        
		        if ( is_array( $db_hash_meta ) ) {
		        	$db_hash 			= str_replace( "'", "", $db_hash_meta );
		        	if ( isset( $backup_method ) && $backup_method[0] == 'tables' ) {
			        	$revert_db_nonce 	= wp_nonce_url( admin_url("admin-post.php?action=revert_db&db_hash={$db_hash[0]}&branch={$branch_meta[0]}&backup_method=tables&post_id=" . get_the_ID()), 'revert_db', 'revert_db_nonce' );
		        	} else {
			        	$revert_db_nonce 	= wp_nonce_url( admin_url("admin-post.php?action=revert_db&db_hash={$db_hash[0]}&branch={$branch_meta[0]}&post_id=" . get_the_ID()), 'revert_db', 'revert_db_nonce' );
		        	}

			        if ( $db_hash[0] != '' ) {
		          		$actions['revert_db'] = "<a href='" . $revert_db_nonce ."'>" . __( 'Revert Database', 'revisr' ) . "</a>";
			        }	
		        }        
			}
		}
		return $actions;
	}

	/**
	 * Filters commits by branch.
	 * @access public
	 * @param  object $commits The commits query.
	 */
	public function filters( $commits ) {
		if ( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'revisr_commits' ) {
			if ( isset( $_GET['branch'] ) && $_GET['branch'] != 'all' ) {
				$commits->set( 'meta_key', 'branch' );
				$commits->set( 'meta_value', $_GET['branch'] );
				$commits->set( 'post_type', 'revisr_commits' );
			}
		}
		return $commits;
	}

	/**
	 * Unsets unused views, replaced with branches.
	 * @access public
	 * @param  array $views The global views for the post type.
	 */
	public function custom_views( $views ) {

		$output = $this->git->run( 'branch' );

		global $wp_query;

		if ( is_array( $output ) ) {
			foreach ( $output as $key => $value ) {
				$branch = substr( $value, 2 );
	    	    $class = ( $wp_query->query_vars['meta_value'] == $branch ) ? ' class="current"' : '';
		    	$views["$branch"] = sprintf( __( '<a href="%s"'. $class .'>' . ucwords( $branch ) . ' <span class="count">(%d)</span></a>' ),
		        admin_url( 'edit.php?post_type=revisr_commits&branch='.$branch ),
		        Revisr_Admin::count_commits( $branch ) );
			}
			$class = '';
			if ( $_GET['branch'] == 'all' ) {
				$class = ' class="current"';
			}
			$views['all'] = sprintf( 
				__( '<a href="%s"%s>All Branches <span class="count">(%d)</span></a>', 'revisr' ),
				admin_url( 'edit.php?post_type=revisr_commits&branch=all' ),
				$class,
				Revisr_Admin::count_commits( 'all' )
			);
			unset( $views['publish'] );
			unset( $views['draft'] );
			unset( $views['trash'] );
			if ( isset( $views ) ) {
				return $views;
			}
		}
	}

	/**
	 * Sets the default view to the current branch on the commit listing.
	 * @access public
	 */
	public function default_views() {
		if( !isset($_GET['branch'] ) && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'revisr_commits' ) {
			$_GET['branch'] = $this->git->branch;
		}
	}

	/**
	 * Disables autodraft when on the new commit page.
	 * @access public
	 */
	public function disable_autodraft() {
		if ( 'revisr_commits' == get_post_type() ) {
			wp_dequeue_script( 'autosave' );
		}
	}

	/**
	 * Displays custom columns for the commits post type.
	 * @access public
	 */
	public function columns() {
		$columns = array (
			'cb' 			=> '<input type="checkbox" />',
			'hash' 			=> __( 'ID', 'revisr' ),
			'title' 		=> __( 'Commit', 'revisr' ),
			'branch' 		=> __( 'Branch', 'revisr' ),
			'tag' 			=> __( 'Tag', 'revisr' ),
			'files_changed' => __( 'Files Changed', 'revisr' ),
			'date' 			=> __( 'Date', 'revisr' ),
		);
		return $columns;
	}

	/**
	 * Displays the number of committed files and the commit hash for commits.
	 * @access public
	 * @param  string $column The column to add.
	 */
	public function custom_columns( $column ) {
		global $post;
		$post_id = get_the_ID();
		switch ( $column ) {
			case "hash": 
				echo Revisr_Git::get_hash( $post_id );
				break;
			case "branch":
				$branch_meta = get_post_meta( $post_id, "branch" );
				if ( isset( $branch_meta[0] ) ) {
					echo $branch_meta[0];
				}
				break;
			case "tag":
				$tag_meta = get_post_meta( $post_id, "git_tag" );
				if ( isset( $tag_meta[0] ) ) {
					echo $tag_meta[0];
				}
				break;
			case "files_changed":
				$files_meta = get_post_meta( $post_id, "files_changed" );
				if ( isset( $files_meta[0] ) ) {
					echo $files_meta[0];
				}
				break;
		}
	}			
}
