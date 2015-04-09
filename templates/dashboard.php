<?php
/**
 * Displays the main dashboard page.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

// Grab the instance
$revisr 	= revisr();
$loader_url = REVISR_URL . 'assets/img/loader.gif';

// Enqueue any necessary scripts (Already registered in "Revisr_Admin_Setup").
wp_enqueue_script( 'revisr_dashboard' );
wp_localize_script( 'revisr_dashboard', 'revisr_dashboard_vars', array(
	'ajax_nonce' 	=> wp_create_nonce( 'revisr_dashboard_nonce' ),
	'discard_msg' 	=> __( 'Are you sure you want to discard your uncommitted changes?', 'revisr' ),
	'push_msg' 		=> __( 'Are you sure you want to push all committed changes to the remote?', 'revisr' ),
	'pull_msg' 		=> __( 'Are you sure you want to discard your uncommitted changes and pull from the remote?', 'revisr' ),
	)
);

// Prepares the Revisr custom list table.
$revisr->list_table->prepare_items();

?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"></div>
	<h2><?php _e( 'Revisr - Dashboard', 'revisr' ); ?></h2>
	<div id="revisr-alert-container">
		<div id="revisr-loading-alert" class="revisr-alert updated"><p><?php _e( 'Loading...', 'revisr' ); ?></p></div>
		<div id="revisr-processing-request" class="revisr-alert updated" style="display:none;"><p><?php _e( 'Processing request...', 'revisr' ); ?></p></div>
	</div>
	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<!-- main content -->
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<form id="revisr-list-table">
						<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
						<?php $revisr->list_table->display(); ?>
					</form>
				</div><!-- .meta-box-sortables .ui-sortable -->
			</div><!-- post-body-content -->
			<!-- sidebar -->
			<div id="postbox-container-1" class="postbox-container">
				<div class="meta-box-sortables">
					<!-- BEGIN QUICK ACTIONS -->
					<div class="postbox">
						<h3><span><?php _e('Quick Actions', 'revisr'); ?></span> <div id='loader'><img src="<?php echo $loader_url; ?>"/></div></h3>
						<div class="inside">
							<button id="commit-btn" class="button button-primary quick-action-btn"><span class="qb-text">| <?php _e( 'Save Changes', 'revisr' ); ?></span></button>
							<button id="discard-btn" class="button button-primary quick-action-btn"><span class="qb-text">| <?php _e( 'Discard Changes', 'revisr' ); ?></span></button>
							<button id="backup-btn" class="button button-primary quick-action-btn"><span class="qb-text">| <?php _e( 'Backup Database', 'revisr' ); ?></span></button>
							<button id="push-btn" class="button button-primary quick-action-btn"><span id="push-text" class="qb-text">| <?php _e( 'Push Changes ', 'revisr' ); ?> <span id="unpushed"></span></span></button>
							<button id="pull-btn" class="button button-primary quick-action-btn"><span id="pull-text" class="qb-text">| <?php _e( 'Pull Changes', 'revisr' ); ?>  <span id="unpulled"></span></span></button>
						</div> <!-- .inside -->
					</div> <!-- .postbox -->
					<!-- END QUICK ACTIONS -->
					<!-- BEGIN BRANCHES/TAGS WIDGET -->
					<div id="branches_tags_widget" class="postbox ">
						<h3 class="hndle"><span><?php _e( 'Branches/Tags', 'revisr' ); ?></span></h3>
						<div class="inside">
							<div id="taxonomy-category" class="categorydiv">
								<ul id="branches-tags-tabs" class="category-tabs">
									<li id="branches-tab" class="tabs"><a id="branches-link" href="#branches" onclick="return false;"><?php _e( 'Branches', 'revisr' ); ?></a></li>
									<li id="tags-tab" class="hide-if-no-js"><a id="tags-link" href="#tags" onclick="return false;"><?php _e( 'Tags', 'revisr' ); ?></a></li>
								</ul>
								<div id="branches" class="tabs-panel" style="display: block;">
									<table id="branches_table" class="widefat">
										<?php

											$admin_url 	= get_admin_url();
											$output 	= $revisr->git->get_branches();

											if ( is_array( $output ) ) {
												foreach ($output as $key => $value){
													$branch = substr($value, 2);
													if (substr( $value, 0, 1 ) === "*"){
														echo "<tr><td><strong>$branch</strong></td><td width='70'><a class='button disabled branch-btn' onclick='preventDefault()' href='#'>Checked Out</a></td></tr>";
													}
													else {
														$branch_url = wp_nonce_url( $admin_url . "admin-post.php?action=process_checkout&branch={$branch}", 'process_checkout', 'revisr_checkout_nonce' );
														echo "<tr><td>$branch</td><td width='70'><a class='button branch-btn' href='" . $branch_url . "'>Checkout</a></td></tr>";
													}
												}
											}
										?>
									</table>
								</div>
								<div id="tags" class="tabs-panel" style="display: none;">
								<?php
									$tags = $revisr->git->run( 'tag', array() );

									if ( is_array( $tags ) ) {

										echo '<ul id="tags-list">';
										foreach ( $tags as $tag ) {
											$tag = esc_attr( $tag );
											echo '<li class="revisr-tag"><a href="' . get_admin_url() . 'edit.php?post_type=revisr_commits&git_tag=' . $tag . '">' . $tag . '</a></li>';
										}
										echo '</ul>';

									}
								?>
								</div>
								<div id="manage_branches" class="wp-hidden-children">
									<h4><a id="manage-branches-link" href="<?php echo get_admin_url() . 'admin.php?page=revisr_branches'; ?>" class="hide-if-no-js"><?php _e( 'Manage Branches', 'revisr' ); ?></a></h4>
								</div>
							</div>
						</div>
					</div>
					<!-- END BRANCHES/TAGS WIDGET -->
					<div class="postbox">
						<h3><span><?php _e( 'Documentation', 'revisr' ); ?></span></h3>
						<div class="inside">
							<?php printf( __( 'Need help? Check out the improved documentation at %s.', 'revisr' ),  ' <a href="http://docs.revisr.io/" target="_blank">http://docs.revisr.io</a>' ); ?>
							<br><br>
							<?php printf( __( '&copy; %d Expanded Fronts, LLC', 'revisr' ), date( 'Y' ) ); ?>
						</div> <!-- .inside -->
					</div> <!-- .postbox -->
				</div> <!-- .meta-box-sortables -->
			</div> <!-- #postbox-container-1 .postbox-container -->
		</div> <!-- #post-body .metabox-holder .columns-2 -->
		<br class="clear">
	</div> <!-- #poststuff -->
</div> <!-- .wrap -->
