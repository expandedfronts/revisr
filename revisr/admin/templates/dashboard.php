<?php
/**
 * Displays the main dashboard page.
 *
 * @package   Revisr
 * @author    Matt Shaw <matt@expandedfronts.com>
 * @license   GPL-2.0+
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 */

$dir = plugin_dir_path( __FILE__ );
include_once $dir . '../includes/functions.php';

?>
<div class="wrap">
	
	<div id="icon-options-general" class="icon32"></div>
	<h2>Revisr Dashboard</h2>
	
	<div id="poststuff">
	
		<div id="post-body" class="metabox-holder columns-2">
		
			<!-- main content -->
			<div id="post-body-content">
				
				<div class="meta-box-sortables ui-sortable">
					
					<div class="postbox">
					
						<h3><span>Recent Activity</span></h3>
						<div class="inside">
							<?php 
								$pending = count_pending();
								if ( $pending != 0 ){
									if ( $pending == 1 ){
										$text = "There is currently 1 file pending.";
									}
									else {
										$text = "There are currently {$pending} files pending.";
									}
									echo "<div class='updated'><p>{$text}</p></div>";
								}
							?>
							<table id="revisr-activity" class="widefat">
								<thead>
								    <tr>
								        <th>Event</th>
								        <th>Time</th>
								    </tr>
								</thead>
								<tbody>
								<?php
									global $wpdb;
									$revisr_events = $wpdb->get_results('SELECT * FROM ef_revisr ORDER BY id DESC LIMIT 10', ARRAY_A);

									foreach ($revisr_events as $revisr_event) {
										echo "<tr><td>{$revisr_event['message']}</td><td>{$revisr_event['time']}</td></tr>";
									}

								?>
								</tbody>
							</table>
						</div> <!-- .inside -->
					
					</div> <!-- .postbox -->
					
				</div> <!-- .meta-box-sortables .ui-sortable -->
				
			</div> <!-- post-body-content -->
			
			<!-- sidebar -->
			<div id="postbox-container-1" class="postbox-container">
				
				<div class="meta-box-sortables">
					
					<div class="postbox">
					
						<h3><span>Quick Actions</span></h3>
						<div class="inside">
							<a href="<?php echo get_admin_url(); ?>admin-post.php?action=pull" class="button button-primary">Pull Changes</a>
							<a href="<?php echo get_admin_url(); ?>admin-post.php?action=push" class="button button-primary">Push Changes</a>
						</div> <!-- .inside -->
						
					</div> <!-- .postbox -->

					<div class="postbox">
					
						<h3><span>Branches</span></h3>
						<div class="inside">
							<table class="widefat">
								<?php
									$dir = getcwd();
									chdir(ABSPATH);
									exec("git branch", $output);
									chdir($dir);

									foreach ($output as $key => $value){
										$branch = substr($value, 2);
										$disabled = "";
										if ($branch == $_SESSION['branch']){
											$branch = "<strong>" . substr($value, 2) . " (current branch)</strong>";
											$disabled = " disabled";
										}
										echo "<tr><td>$branch</td><td width='70'>Checkout</td></tr>";
									}
								?>
							</table>
						</div> <!-- .inside -->
						
					</div> <!-- .postbox -->

					<div class="postbox">
					
						<h3><span>About this plugin</span></h3>
						<div class="inside">
							Please read more about this plugin at <a href="https://revisr.io/">revisr.io</a>.
							<br><br>
							&copy; 2014 Expanded Fronts, LLC.
						</div> <!-- .inside -->
						
					</div> <!-- .postbox -->


					
				</div> <!-- .meta-box-sortables -->
				
			</div> <!-- #postbox-container-1 .postbox-container -->
			
		</div> <!-- #post-body .metabox-holder .columns-2 -->
		
		<br class="clear">
	</div> <!-- #poststuff -->
	
</div> <!-- .wrap -->