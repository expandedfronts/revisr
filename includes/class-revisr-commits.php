<?php
/**
 * class-revisr-commits.php
 *
 * Configures the 'revisr_commits' custom post type.
 *
 * @package   	Revisr
 * @license   	GPLv3
 * @link      	https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr_Commits {

	/**
	 * A reference back to the main Revisr instance.
	 * @var object
	 */
	protected $revisr;

	/**
	 * Initialize the class.
	 * @access public
	 */
	public function __construct() {
		$this->revisr = revisr();
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
	 * Adds/removes meta boxes for the "revisr_commits" post type.
	 * @access public
	 */
	public function post_meta() {
		if ( isset( $_GET['action'] ) ) {
			if ( 'edit' == $_GET['action'] ) {
				add_meta_box( 'revisr_committed_files', __( 'Committed Files', 'revisr' ), array( $this, 'committed_files_meta' ), 'revisr_commits', 'normal', 'high' );
				add_meta_box( 'revisr_view_commit', __( 'Commit Details', 'revisr' ), array( $this, 'view_commit_meta' ), 'revisr_commits', 'side', 'core' );
				remove_meta_box( 'submitdiv', 'revisr_commits', 'side' );
			}
		} else {
			add_meta_box( 'revisr_pending_files', __( 'Stage Changes', 'revisr' ), array( $this, 'pending_files_meta' ), 'revisr_commits', 'normal', 'high' );
			add_meta_box( 'revisr_add_tag', __( 'Add Tag', 'revisr' ), array( $this, 'add_tag_meta' ), 'revisr_commits', 'side', 'default' );
			add_meta_box( 'revisr_save_commit', __( 'Save Commit', 'revisr' ), array( $this, 'save_commit_meta' ), 'revisr_commits', 'side', 'core' );
			remove_meta_box( 'submitdiv', 'revisr_commits', 'side' );
		}
		remove_meta_box( 'authordiv', 'revisr_commits', 'normal' );
	}

	/**
	 * Registers all postmeta keys used and assigns the default
	 * method used for escaping when using add_post_meta or edit_post_meta
	 * @access public
	 */
	public function register_meta_keys() {
		register_meta( 'post', 'files_changed', 'absint' );
		register_meta( 'post', 'branch', 'wp_kses' );
		register_meta( 'post', 'commit_hash', 'wp_kses' );
		register_meta( 'post', 'db_hash', 'wp_kses' );
		register_meta( 'post', 'committed_files', array( 'Revisr_Admin', 'esc_attr_array' ) );
		register_meta( 'post', 'git_tag', 'esc_attr' );
		register_meta( 'post', 'backup_method', 'esc_attr' );
		register_meta( 'post', 'commit_status', 'esc_attr' );
		register_meta( 'post', 'error_details', array( 'Revisr_Admin', 'esc_attr_array' ) );
	}

	/**
	 * Custom title message for the revisr_commits custom post type.
	 * @access public
	 * @return string
	 */
	public function custom_enter_title( $input ) {
	    global $post_type;

	    if ( is_admin() && 'revisr_commits' == $post_type ) {
	        return __( 'Enter a message for your commit', 'revisr' );
	    }

	    return $input;
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
			6  => sprintf( __( 'Committed files on branch <strong>%s</strong>.', 'revisr' ), $this->revisr->git->branch ),
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

		if ( 'revisr_commits' === get_post_type() && isset( $actions ) ) {

			// Unset the default WordPress actions
			unset( $actions['edit'] );
	        unset( $actions['view'] );
	        unset( $actions['trash'] );
	        unset( $actions['inline hide-if-no-js'] );

	        // Display the View and Revert links
	        $id 				= get_the_ID();
	        $commit 			= Revisr_Admin::get_commit_details( $id );
	        $url 				= get_admin_url() . 'post.php?post=' . $id . '&action=edit';
	        $actions['view'] 	= "<a href='{$url}'>" . __( 'View', 'revisr' ) . "</a>";
	        $revert_nonce 		= wp_nonce_url( admin_url( 'admin-post.php?action=process_revert&revert_type=files&commit_hash=' . $commit['commit_hash'] . '&branch=' . $commit['branch'] . '&post_id=' . $id ), 'revisr_revert_nonce', 'revisr_revert_nonce' );
	        $actions['revert'] 	= "<a href='" . $revert_nonce . "'>" . __( 'Revert Files', 'revisr' ) . "</a>";

	        // If there is a database backup available to revert to, display the revert link.
	        if ( $commit['db_hash'] !== '' ) {
	        	$revert_db_nonce = wp_nonce_url( admin_url( 'admin-post.php?action=process_revert&revert_type=db&db_hash=' . $commit['db_hash'] . '&branch=' . $commit['branch'] . '&backup_method=' . $commit['db_backup_method'] . '&post_id=' . $id ), 'revisr_revert_nonce', 'revisr_revert_nonce' );
	        	$actions['revert_db'] = '<a href="' . $revert_db_nonce . '">' . __( 'Revert Database', 'revisr' ) . '</a>';
	        }

		}

		// Return the actions for display.
		return $actions;
	}

	/**
	 * Filters for edit.php.
	 * @access public
	 * @param  object $commits The commits query.
	 */
	public function filters( $commits ) {
		if ( isset( $commits->query_vars['post_type'] ) && 'revisr_commits' === $commits->query_vars['post_type'] ) {

			// Filter by tag.
			if ( isset( $_GET['git_tag'] ) && '' !== $_GET['git_tag'] ) {
				$commits->set( 'meta_key', 'git_tag' );
				$commits->set( 'meta_value', esc_sql( $_GET['git_tag'] ) );

				// Bail out early so the filter isn't potentially overwritten.
				return $commits;
			}

			// Filter by branch.
			if ( isset( $_GET['branch'] ) && $_GET['branch'] != 'all' ) {
				$commits->set( 'meta_key', 'branch' );
				$commits->set( 'meta_value', esc_sql( $_GET['branch'] ) );
			}
		}
		return $commits;
	}

	/**
	 * Allows for searching by the 7 digit commit hash on edit.php.
	 * @access public
	 * @param  string $where The WordPress "WHERE" queries being ran.
	 * @return string
	 */
	public function posts_where( $where ) {
		global $pagenow, $wpdb;
		if ( 'edit.php' === $pagenow && isset( $_GET['post_type'] ) && 'revisr_commits' === $_GET['post_type'] ) {
			if ( isset( $_GET['s'] ) && 7 === strlen( trim( $_GET['s'] ) ) ) {
				$hash 	= esc_sql( $_GET['s'] );
				$where .= " OR $wpdb->postmeta.meta_key = 'commit_hash'  AND $wpdb->postmeta.meta_value = '$hash'";
			}
		}
		return $where;
	}

	/**
	 * Unsets unused views, replaced with branches.
	 * @access public
	 * @param  array $views The global views for the post type.
	 */
	public function custom_views( $views ) {

		$output = $this->revisr->git->get_branches();

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
		if( !isset( $_GET['branch'] ) && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'revisr_commits' ) {
			$_GET['branch'] = $this->revisr->git->branch;
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
			'author'		=> __( 'Author', 'revisr' ),
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
	 * @param  string 	$column_name 	The name of the column to display.
	 * @param  int 		$post_id 		The ID of the current post.
	 */
	public function custom_columns( $column_name, $post_id ) {

		$commit = Revisr_Admin::get_commit_details( $post_id );

		switch ( $column_name ) {
			case 'hash':
				echo $commit['commit_hash'];
				break;
			case 'branch':
				echo $commit['branch'];
				break;
			case 'tag':
				echo $commit['tag'];
				break;
			case 'files_changed':
				echo $commit['files_changed'];
				break;
		}
	}

	/**
	 * Shows a list of the pending files on the current branch. Clicking a modified file shows the diff.
	 * @access public
	 */
	public function pending_files() {
		check_ajax_referer( 'pending_nonce', 'security' );
		$output 		= $this->revisr->git->status();
		$total_pending 	= count( $output );
		$text 			= sprintf( __( 'There are <strong>%s</strong> untracked files that can be added to this commit.', 'revisr' ), $total_pending, $this->revisr->git->branch );
		echo "<br>" . $text . "<br><br>";
		_e( 'Use the boxes below to select the files to include in this commit. Only files in the "Staged Files" section will be included.<br>Double-click files marked as "Modified" to view the changes to the file.<br><br>', 'revisr' );
		if ( is_array( $output ) ) {
				?>
				<!-- Staging -->
				<div class="stage-container">
					<p><strong><?php _e( 'Staged Files', 'revisr' ); ?></strong></p>
					<select id='staged' multiple="multiple" name="staged_files[]" size="6">
					<?php
					// Clean up output from git status and echo the results.
					foreach ( $output as $result ) {
						$result 		= str_replace( '"', '', $result );
						$short_status 	= substr( $result, 0, 3 );
						$file 			= substr( $result, 3 );
						$status 		= Revisr_Git::get_status( $short_status );
						echo "<option class='pending' value='{$result}'>{$file} [{$status}]</option>";
					}
					?>
					</select>
					<div class="stage-nav">
						<input id="unstage-file" type="button" class="button button-primary stage-nav-button" value="<?php _e( 'Unstage Selected', 'revisr' ); ?>" onclick="unstage_file()" />
						<br>
						<input id="unstage-all" type="button" class="button stage-nav-button" value="<?php _e( 'Unstage All', 'revisr' ); ?>" onclick="unstage_all()" />
					</div>
				</div><!-- /Staging -->
				<br>
				<!-- Unstaging -->
				<div class="stage-container">
					<p><strong><?php _e( 'Unstaged Files', 'revisr' ); ?></strong></p>
					<select id="unstaged" multiple="multiple" size="6">
					</select>
					<div class="stage-nav">
						<input id="stage-file" type="button" class="button button-primary stage-nav-button" value="<?php _e( 'Stage Selected', 'revisr' ); ?>" onclick="stage_file()" />
						<br>
						<input id="stage-all" type="button" class="button stage-nav-button" value="<?php _e( 'Stage All', 'revisr' ); ?>" onclick="stage_all()" />
					</div>
				</div><!-- /Unstaging -->
			<?php
		}
		exit();
	}

	/**
	 * Shows the files that were added in a given commit.
	 * @access public
	 */
	public function committed_files_meta() {

		$commit = Revisr_Admin::get_commit_details( get_the_ID() );

		if ( count( $commit['committed_files'] ) !== 0 ) {
			foreach ( $commit['committed_files']  as $file ) {
				$output = maybe_unserialize( $file );
			}
		}

		echo '<div id="message"></div><div id="committed_files_result">';

		if ( isset( $output ) ) {
			printf( __('<br><strong>%s</strong> files were included in this commit. Double-click files marked as "Modified" to view the changes in a diff.', 'revisr' ), $commit['files_changed'] );
			echo '<input id="commit_hash" name="commit_hash" value="' . $commit['commit_hash'] . '" type="hidden" />';
			echo '<br><br><select id="committed" multiple="multiple" size="6">';

				// Display the files that were included in the commit.
				foreach ( $output as $result ) {
					$result 		= str_replace( '"', '', $result );
					$short_status 	= substr( $result, 0, 3 );
					$file 			= substr( $result, 2 );
					$status 		= Revisr_Git::get_status( $short_status );
					printf( '<option class="committed" value="%s">%s [%s]</option>', $result, $file, $status );
				}

			echo '</select>';
		} else {
			_e( 'No files were included in this commit.', 'revisr' );
		}

		echo '</div>';
	}

	/**
	 * Displays the "Add Tag" meta box on the sidebar.
	 * @access public
	 */
	public function add_tag_meta() {
		printf(
			'<label for="tag_name"><p>%s</p></label>
			<input id="tag_name" type="text" name="tag_name" />',
			__( 'Tag Name:', 'revisr' )
		);
	}

	/**
	 * Displays the "Save Commit" meta box in the sidebar.
	 * @access public
	 */
	public function save_commit_meta() {
		?>

		<div id="minor-publishing">
			<div id="misc-publishing-actions">

				<div class="misc-pub-section revisr-pub-status">
					<label for="post_status"><?php _e( 'Status:', 'revisr' ); ?></label>
					<span><strong><?php _e( 'Pending', 'revisr' ); ?></strong></span>
				</div>

				<div class="misc-pub-section revisr-pub-branch">
					<label for="revisr-branch"><?php _e( 'Branch:', 'revisr' ); ?></label>
					<span><strong><?php echo $this->revisr->git->branch; ?></strong></span>
				</div>

				<div class="misc-pub-section revisr-backup-cb">
					<span><input id="revisr-backup-cb" type="checkbox" name="backup_db" /></span>
					<label for="revisr-backup-cb"><?php _e( 'Backup database?', 'revisr' ); ?></label>
				</div>

				<div class="misc-pub-section revisr-push-cb">
					<?php if ( $this->revisr->git->get_config( 'revisr', 'auto-push' ) == 'true' ): ?>
						<input type="hidden" name="autopush_enabled" value="true" />
						<span><input id="revisr-push-cb" type="checkbox" name="auto_push" checked /></span>
					<?php else: ?>
						<span><input id="revisr-push-cb" type="checkbox" name="auto_push" /></span>
					<?php endif; ?>
					<label for="revisr-push-cb"><?php _e( 'Push changes?', 'revisr' ); ?></label>
				</div>

			</div><!-- /#misc-publishing-actions -->
		</div>

		<div id="major-publishing-actions">
			<div id="delete-action"></div>
			<div id="publishing-action">
				<span class="spinner"></span>
				<?php wp_nonce_field( 'process_commit', 'revisr_commit_nonce' ); ?>
				<input type="submit" name="publish" id="commit" class="button button-primary button-large" value="<?php _e( 'Commit Changes', 'revisr' ); ?>" onclick="commit_files();" accesskey="p">
			</div>
			<div class="clear"></div>
		</div>

		<?php
	}

	/**
	 * Displays the "Commit Details" meta box on a previous commit.
	 * @access public
	 */
	public function view_commit_meta() {

		$post_id 			= get_the_ID();
		$commit 			= Revisr_Admin::get_commit_details( $post_id );
		$revert_url 		= get_admin_url() . "admin-post.php?action=revert_form&commit_id=" . $post_id . "&TB_iframe=true&width=350&height=200";

		$time_format 	 	= __( 'M j, Y @ G:i' );
		$timestamp 		 	= sprintf( __( 'Committed on: <strong>%s</strong>', 'revisr' ), date_i18n( $time_format, get_the_time( 'U' ) ) );

		if ( false !== $commit['error_details'] ) {
			$details = ' <a class="thickbox" title="' . __( 'Error Details', 'revisr' ) . '" href="' . wp_nonce_url( admin_url( 'admin-post.php?action=revisr_view_error&post_id=' . $post_id . '&TB_iframe=true&width=350&height=300' ), 'revisr_view_error', 'revisr_error_nonce' ) . '">View Details</a>';
			$revert_btn = '<a class="button button-primary disabled" href="#">' . __( 'Revert to this Commit', 'revisr' ) . '</a>';
		} else {
			$revert_btn = '<a class="button button-primary thickbox" href="' . $revert_url . '" title="' . __( 'Revert', 'revisr' ) . '">' . __( 'Revert to this Commit', 'revisr' ) . '</a>';
			$details = '';
		}

		?>
		<div id="minor-publishing">
			<div id="misc-publishing-actions">

				<div class="misc-pub-section revisr-pub-status">
					<label for="post_status"><?php _e( 'Status:', 'revisr' ); ?></label>
					<span><strong><?php echo $commit['status'] . $details; ?></strong></span>
				</div>

				<div class="misc-pub-section revisr-pub-branch">
					<label for="revisr-branch"><?php _e( 'Branch:', 'revisr' ); ?></label>
					<span><strong><?php echo $commit['branch']; ?></strong></span>
				</div>

				<div class="misc-pub-section curtime misc-pub-curtime">
					<span id="timestamp" class="revisr-timestamp"><?php echo $timestamp; ?></span>
				</div>

				<?php if ( $commit['tag'] !== '' ): ?>
				<div class="misc-pub-section revisr-git-tag">
					<label for="revisr-tag"><?php _e( 'Tagged:', 'revisr' ); ?></label>
					<span><strong><?php echo $commit['tag']; ?></strong></span>
				</div>
				<?php endif; ?>

			</div><!-- /#misc-publishing-actions -->
		</div>

		<div id="major-publishing-actions">
			<div id="delete-action"></div>
			<div id="publishing-action">
				<span class="spinner"></span>
				<?php echo $revert_btn; ?>
			</div>
			<div class="clear"></div>
		</div>
		<?php
	}

	/**
	 * The container for the staging area.
	 * @access public
	 */
	public function pending_files_meta() {
		echo "<div id='message'></div>
		<div id='pending_files_result'></div>";
	}

}
