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
	<h2><?php _e('Revisr Dashboard', 'revisr-plugin'); ?></h2>
	
	<?php 
		$pending = count_pending();
		if (isset($_GET['revert_db']) && $_GET['revert_db'] == "success"){
			$text = "<p>" . __('Successfully reverted the database.', 'revisr-plugin') . "</p>";
		}
		else if ( isset($_GET['checkout']) && $_GET['checkout'] == "success" ){
			$text = sprintf(__('<p>Successfully checked out branch <strong>%s</strong>.</p>', 'revisr-plugin'), $_GET['branch']);
		}
		else if ( isset($_GET['revert']) && $_GET['revert'] == "success"){
			$url = get_admin_url() . "post.php?post={$_GET['id']}&action=edit";
			$text = "<p>Successfully reverted to commit <a href='{$url}'><strong>#{$_GET['commit']}</strong></a>.</p>";
		}
		else if ( $pending != 0 ){
			if ( $pending == 1 ){
				$text = sprintf(__('<p>There is currently <strong>1</strong> pending file on branch <strong>%s</strong>.</p>', 'revisr-plugin'), current_branch());
			}
			else {
				$text = sprintf(__('<p>There are currently <strong>%d</strong> pending files on branch <strong>%s</strong>.</p>', 'revisr-plugin'), $pending, current_branch());
			}
		}
		else {
			$text = sprintf(__('<p>There are currently no pending files on branch <strong>%s</strong>.</p>', 'revisr-plugin'), current_branch());
		}
		echo "<div id='revisr_alert' class='updated'>{$text}</div>";

		$error = check_compatibility();
		if ($error != "") {
			echo "<div id='revisr_error' class='error'>{$error}</div>";
		}
	?>

	<div id="poststuff">
	<div id="revisr_alert"></div>
		<div id="post-body" class="metabox-holder columns-2">
		
			<!-- main content -->
			<div id="post-body-content">
				
				<div class="meta-box-sortables ui-sortable">
					
					<div class="postbox">
					
						<h3><span><?php _e('Recent Activity', 'revisr-plugin'); ?></span></h3>
						<div class="inside" id="revisr_activity">
							
							


						</div> <!-- .inside -->
					
					</div> <!-- .postbox -->
					
				</div> <!-- .meta-box-sortables .ui-sortable -->
				
			</div> <!-- post-body-content -->
			
			<!-- sidebar -->
			<div id="postbox-container-1" class="postbox-container">
				
				<div class="meta-box-sortables">
					
					<div class="postbox">
					
						<h3><span><?php _e('Quick Actions', 'revisr-plugin'); ?></span> <div id='loader'><img src="<?php echo $loader_url; ?>"/></div></h3>
						<div class="inside">
							<button id="commit-btn" class="button button-primary quick-action-btn" onlick="confirmPull(); return false;">| Commit Changes</button>
							<button id="discard-btn" class="button button-primary quick-action-btn">| Discard Changes</button>
							<button id="push-btn" class="button button-primary quick-action-btn" onlick="confirmPush(); return false;">| Push Changes</button>
							<button id="pull-btn" class="button button-primary quick-action-btn" onlick="confirmPull(); return false;">| Pull Changes</button>
						</div> <!-- .inside -->
						
					</div> <!-- .postbox -->

					<div class="postbox">
					
						<h3><span><?php _e('Branches', 'revisr-plugin'); ?> <a id="new_branch" href="<?php echo get_admin_url(); ?>admin-post.php?action=create_branch&TB_iframe=true&width=250&height=150" title="Create Branch" class="thickbox"><?php _e('(Create Branch)', 'revisr-plugin'); ?></a> </span></h3>
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
					
						<h3><span><?php _e('About this plugin', 'revisr-plugin'); ?></span></h3>
						<div class="inside">
							<?php _e('Please read more about this plugin at <a href="http://revisr.io/">revisr.io</a>.', 'revisr-plugin'); ?>
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