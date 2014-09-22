<?php
/**
 * Displays the main dashboard page.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 */

$dir = plugin_dir_path( __FILE__ );
$loader_url = plugins_url( '../assets/img/loader.gif' , __FILE__ );

wp_enqueue_script( 'revisr_dashboard' );
wp_localize_script( 'revisr_dashboard', 'dashboard_vars', array(
	'ajax_nonce' => wp_create_nonce( 'dashboard_nonce' ),
	)
);

?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"></div>
	<h2><?php _e( 'Revisr - Dashboard', 'revisr' ); ?></h2>
	<div id="revisr-alert-container" style="height:38px;width:100%;margin-top:20px;"></div>
	<div id="poststuff">
	<div id="revisr_alert"></div>
		<div id="post-body" class="metabox-holder columns-2">
			<!-- main content -->
			<div id="post-body-content">
				
				<div class="meta-box-sortables ui-sortable">
					
					<div class="postbox">
					
						<h3><span><?php _e('Recent Activity', 'revisr'); ?></span></h3>
						<div class="inside" id="revisr_activity">

						</div> <!-- .inside -->
					
					</div> <!-- .postbox -->
					
				</div> <!-- .meta-box-sortables .ui-sortable -->
				
			</div> <!-- post-body-content -->
			
			<!-- sidebar -->
			<div id="postbox-container-1" class="postbox-container">
				
				<div class="meta-box-sortables">
					
					<div class="postbox">
					
						<h3><span><?php _e('Quick Actions', 'revisr'); ?></span> <div id='loader'><img src="<?php echo $loader_url; ?>"/></div></h3>
						<div class="inside">
							<button id="commit-btn" class="button button-primary quick-action-btn" onlick="confirmPull(); return false;"><span class="qb-text">| <?php _e( 'Commit Changes', 'revisr' ); ?></span></button>
							<button id="discard-btn" class="button button-primary quick-action-btn"><span class="qb-text">| <?php _e( 'Discard Changes', 'revisr' ); ?></span></button>
							<button id="backup-btn" class="button button-primary quick-action-btn"><span class="qb-text">| <?php _e( 'Backup Database', 'revisr' ); ?></span></button>
							<button id="push-btn" class="button button-primary quick-action-btn" onlick="confirmPush(); return false;"><span id="push-text" class="qb-text">| <?php _e( 'Push Changes ', 'revisr' ); ?> <span id="unpushed"></span></span></button>
							<button id="pull-btn" class="button button-primary quick-action-btn" onlick="confirmPull(); return false;"><span id="pull-text" class="qb-text">| <?php _e( 'Pull Changes', 'revisr' ); ?>  <span id="unpulled"></span></span></button>
						</div> <!-- .inside -->
						
					</div> <!-- .postbox -->

					<div class="postbox">
						<h3><?php _e('Branches', 'revisr'); ?><span id="manage_branches"> (<a href="<?php echo get_admin_url(); ?>admin.php?page=revisr_branches" title="Manage Branches"><?php _e( 'Manage', 'revisr' ); ?></a>)</span></h3>
						<div id="branches_box" class="inside">
							<table id="branches_table" class="widefat">
								<?php
									$git = new Revisr_Git;
									$output = $git->branches();
									if ( is_array( $output ) ) {
										foreach ($output as $key => $value){
											$branch = substr($value, 2);
											
											if (substr( $value, 0, 1 ) === "*"){
												echo "<tr><td><strong>$branch</strong></td><td width='70'><a class='button disabled branch-btn' onclick='preventDefault()' href='#'>Checked Out</a></td></tr>";
											}
											else {
												echo "<tr><td>$branch</td><td width='70'><a class='button branch-btn' href='" . get_admin_url() . "admin-post.php?action=checkout&branch={$branch}'>Checkout</a></td></tr>";
											}
										}										
									}

								?>
							</table>
						</div> <!-- .inside -->
					</div> <!-- .postbox -->

					<div class="postbox">
						<h3><span><?php _e( 'About this plugin', 'revisr' ); ?></span></h3>
						<div class="inside">
							<?php printf( __( 'Please read more about this plugin at %s.', 'revisr' ),  ' <a href="http://revisr.io/">revisr.io</a>' ); ?>
							<br><br>
							&copy; 2014 Expanded Fronts, LLC
						</div> <!-- .inside -->
					</div> <!-- .postbox -->
					
				</div> <!-- .meta-box-sortables -->
				
			</div> <!-- #postbox-container-1 .postbox-container -->
			
		</div> <!-- #post-body .metabox-holder .columns-2 -->
		
		<br class="clear">
	</div> <!-- #poststuff -->
	
</div> <!-- .wrap -->