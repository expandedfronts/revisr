<?php
/**
 * Displays the main dashboard page.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts
 */

$dir = plugin_dir_path( __FILE__ );
$loader_url = plugins_url( '../../assets/img/loader.gif' , __FILE__ );

include_once $dir . '../includes/functions.php';

?>
<div class="wrap">
	
	<div id="icon-options-general" class="icon32"></div>
	<h2>Revisr Dashboard</h2>
	
	<?php 
		$pending = count_pending();
		if ( isset($_GET['checkout']) && $_GET['checkout'] == "success" ){
			$text = "<p>Successfully checked out branch <strong>{$_GET['branch']}</strong>.</p>";
		}
		else if ( isset($_GET['revert']) && $_GET['revert'] == "success"){
			$url = get_admin_url() . "post.php?post={$_GET['id']}&action=edit";
			$text = "<p>Successfully reverted to commit <a href='{$url}'><strong>#{$_GET['commit']}</strong></a>.</p>";
		}
		else if ( $pending != 0 ){
			if ( $pending == 1 ){
				$text = "<p>There is currently 1 pending file on branch <strong>" . current_branch() . "</strong>.</p>";
			}
			else {
				$text = "<p>There are currently {$pending} pending files on branch <strong>" . current_branch() . "</strong>.</p>";
			}
		}
		else {
			$text = "<p>There are currently no pending files.</p>";
		}
		echo "<div id='revisr_alert' class='updated'>{$text}</div>";
	?>

	<div id="poststuff">
	<div id="revisr_alert"></div>
		<div id="post-body" class="metabox-holder columns-2">
		
			<!-- main content -->
			<div id="post-body-content">
				
				<div class="meta-box-sortables ui-sortable">
					
					<div class="postbox">
					
						<h3><span>Recent Activity</span></h3>
						<div class="inside" id="revisr_activity">
							
							


						</div> <!-- .inside -->
					
					</div> <!-- .postbox -->
					
				</div> <!-- .meta-box-sortables .ui-sortable -->
				
			</div> <!-- post-body-content -->
			
			<!-- sidebar -->
			<div id="postbox-container-1" class="postbox-container">
				
				<div class="meta-box-sortables">
					
					<div class="postbox">
					
						<h3><span>Quick Actions</span> <div id='loader'><img src="<?php echo $loader_url; ?>"/></div></h3>
						<div class="inside">
							<button id="commit-btn" class="button button-primary quick-action-btn" onlick="confirmPull(); return false;">| Commit Changes</button>
							<button id="discard-btn" class="button button-primary quick-action-btn">| Discard Changes</button>
							<button id="push-btn" class="button button-primary quick-action-btn" onlick="confirmPush(); return false;">| Push Changes</button>
							<button id="pull-btn" class="button button-primary quick-action-btn" onlick="confirmPull(); return false;">| Pull Changes</button>
						</div> <!-- .inside -->
						
					</div> <!-- .postbox -->

					<div class="postbox">
					
						<h3><span>Branches <a id="new_branch" href="<?php echo get_admin_url(); ?>admin-post.php?action=create_branch&TB_iframe=true&width=250&height=150" title="Create Branch" class="thickbox">(Create Branch)</a> </span></h3>
						<div class="inside">
							<table class="widefat">
								<?php
									$dir = getcwd();
									chdir(ABSPATH);
									exec("git branch", $output);
									chdir($dir);

									foreach ($output as $key => $value){
										$branch = substr($value, 2);
										
										if (substr( $value, 0, 1 ) === "*"){
											echo "<tr><td><strong>$branch</strong></td><td width='70'><a class='button disabled branch-btn' onclick='preventDefault()' href='#'>Checked Out</a></td></tr>";
										}
										else {
											echo "<tr><td>$branch</td><td width='70'><a class='button branch-btn' href='" . get_admin_url() . "admin-post.php?action=checkout&branch={$branch}'>Checkout</a></td></tr>";
										}
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
							&copy; 2014 Expanded Fronts
						</div> <!-- .inside -->
						
					</div> <!-- .postbox -->


					
				</div> <!-- .meta-box-sortables -->
				
			</div> <!-- #postbox-container-1 .postbox-container -->
			
		</div> <!-- #post-body .metabox-holder .columns-2 -->
		
		<br class="clear">
	</div> <!-- #poststuff -->
	
</div> <!-- .wrap -->