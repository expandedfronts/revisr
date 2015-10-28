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

$loader_url 	= REVISR_URL . 'assets/img/loader.gif';
$discard_url 	= get_admin_url() . 'admin-post.php?action=revisr_discard_form&TB_iframe=true&width=400&height=225';
$push_url 		= get_admin_url() . 'admin-post.php?action=revisr_push_form&TB_iframe=true&width=400&height=225';
$pull_url 		= get_admin_url() . 'admin-post.php?action=revisr_pull_form&TB_iframe=true&width=400&height=225';

?>

<?php if ( Revisr_Admin::is_doing_setup() ): ?>

<script>
	window.location.href = "<?php echo get_admin_url(); ?>admin.php?page=revisr_setup";
</script>

<?php else: ?>

<?php
	// Prepare the wp_list_table.
	revisr()->activity_table->prepare_items();
?>

<div class="wrap">
	<div id="icon-options-general" class="icon32"></div>
	<h2><?php _e( 'Revisr - Dashboard', 'revisr' ); ?></h2>
	<div id="revisr-alert-container">
		<div id="revisr-loading-alert" class="revisr-alert updated"><p><?php _e( 'Loading...', 'revisr' ); ?></p></div>
		<div id="revisr-processing-request" class="revisr-alert updated" style="display:none;"><p><?php _e( 'Processing request...', 'revisr' ); ?></p></div>
	</div>

	<div id="poststuff" class="revisr-poststuff">
		<div id="post-body" class="metabox-holder columns-2">

			<!-- main content -->
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">

					<form id="revisr-activity-form">
						<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
						<?php revisr()->activity_table->display(); ?>
					</form>

				</div><!-- .meta-box-sortables .ui-sortable -->
			</div><!-- post-body-content -->

			<!-- sidebar -->
			<div id="postbox-container-1" class="postbox-container">
				<div class="meta-box-sortables">

					<!-- BEGIN QUICK ACTIONS -->
					<div class="postbox">
						<h3><span><?php _e('Quick Actions', 'revisr'); ?></span> <div id="revisr-loader"><img src="<?php echo $loader_url; ?>"/></div></h3>
						<div class="inside">
							<button id="revisr-commit-btn" class="button revisr-quick-action-btn"><span class="revisr-quick-action-btn-txt">| <?php _e( 'Save Changes', 'revisr' ); ?></span></button>
							<a id="revisr-discard-btn" class="button revisr-quick-action-btn thickbox" title="<?php _e( 'Discard Changes', 'revisr' ); ?>" href="<?php echo $discard_url; ?>"><span class="revisr-quick-action-btn-txt">| <?php _e( 'Discard Changes', 'revisr' ); ?></span></a>
							<button id="revisr-backup-btn" class="button revisr-quick-action-btn"><span class="revisr-quick-action-btn-txt">| <?php _e( 'Backup Database', 'revisr' ); ?></span></button>
							<a id="revisr-push-btn" class="button revisr-quick-action-btn thickbox" title="<?php _e( 'Push Changes', 'revisr' ); ?>" href="<?php echo $push_url; ?>"><span id="push-text" class="revisr-quick-action-btn-txt">| <?php _e( 'Push Changes ', 'revisr' ); ?> <span id="revisr-unpushed"></span></span></a>
							<a id="revisr-pull-btn" class="button revisr-quick-action-btn thickbox" title="<?php _e( 'Pull Changes' , 'revisr' ); ?>" href="<?php echo $pull_url; ?>"><span id="pull-text" class="revisr-quick-action-btn-txt">| <?php _e( 'Pull Changes', 'revisr' ); ?>  <span id="revisr-unpulled"></span></span></a>
						</div> <!-- .inside -->
					</div> <!-- .postbox -->
					<!-- END QUICK ACTIONS -->

					<!-- BEGIN BRANCHES/TAGS WIDGET -->
					<div id="revisr-branches-tags-widget" class="postbox ">
						<h3 class="hndle"><span><?php _e( 'Branches/Tags', 'revisr' ); ?></span></h3>
						<div class="inside">
							<div id="taxonomy-category" class="categorydiv">
								<ul id="branches-tags-tabs" class="category-tabs">
									<li id="branches-tab" class="tabs"><a id="branches-link" href="#branches" onclick="return false;"><?php _e( 'Branches', 'revisr' ); ?></a></li>
									<li id="tags-tab" class="hide-if-no-js"><a id="tags-link" href="#tags" onclick="return false;"><?php _e( 'Tags', 'revisr' ); ?></a></li>
								</ul>
								<div id="branches" class="tabs-panel" style="display: block;">

									<table id="branches-table" class="widefat">
										<?php

											$admin_url 	= get_admin_url();
											$branches 	= revisr()->git->get_branches();

											if ( is_array( $branches ) && ! empty( $branches ) ) {

												foreach ( $branches as $key => $value ){

													$branch = substr( $value, 2 );

													if ( '*' === substr( $value, 0, 1 ) ) {
														printf( '<tr><td><strong>%s</strong></td><td width="70"><a class="button disabled branch-btn" onlick="preventDefault()" href="#">%s</a></td></tr>',
															$branch,
															__( 'Checked Out', 'revisr' )
														);
													} else {
														$branch_url = wp_nonce_url( $admin_url . "admin-post.php?action=process_checkout&branch={$branch}", 'process_checkout', 'revisr_checkout_nonce' );
														printf( '<tr><td>%s</td><td width="70"><a class="button branch-btn" href="%s">%s</a></td></tr>',
															$branch,
															$branch_url,
															__( 'Checkout', 'revisr' )
														);
													}

												}

											} else {
												printf( '<tr><td>%s</td></tr>', __( 'No branches found.', 'revisr' ) );
											}
										?>
									</table>
								</div>

								<div id="tags" class="tabs-panel" style="display: none;">
									<?php

										$refs = revisr()->git->run( 'show-ref', array( '--tags', '-d', '--abbrev=7' ) );
										$tags = array();

										if ( is_array( $refs ) ) {

											foreach ( $refs as $ref ) {
												$tmp = explode( ' ', $ref );
												$tag = explode( '/', $tmp[1] );
												$tag = end( $tag );
												$tags[$tag] = $tmp[0];
											}

										}

										if ( is_array( $tags ) && ! empty( $tags ) ) {

											echo '<ul id="tags-list">';

											foreach ( $tags as $tag => $commit_hash ) {
												$tag = esc_attr( $tag );
												$url = esc_url( get_admin_url() . 'admin.php?page=revisr_view_commit&commit=' . $commit_hash );
												echo '<li class="revisr-tag"><a href="' . $url .'">' . $tag . '</a></li>';
											}

											echo '</ul>';

										} else {
											printf( '<p>%s</p>', __( 'No tags found.', 'revisr' ) );
										}

									?>
								</div>

								<div id="manage-branches" class="wp-hidden-children">
									<h4><a id="revisr-manage-branches-link" href="<?php echo get_admin_url() . 'admin.php?page=revisr_branches'; ?>" class="hide-if-no-js"><?php _e( 'Manage Branches', 'revisr' ); ?></a></h4>
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

<?php endif; // endif is_doing_setup() ?>
