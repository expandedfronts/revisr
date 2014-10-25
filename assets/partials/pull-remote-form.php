<?php
/**
 * pull-remote-form.php
 * 
 * Displays the form to delete a branch.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 */
$styles_url = REVISR_URL . "assets/css/thickbox.css";
$git = new Revisr_Git();
?>
<link href="<?php echo $styles_url; ?>" rel="stylesheet" type="text/css">
<div class="container" style="padding:10px">
	<form action="<?php echo get_admin_url(); ?>admin-post.php" method="post">
		<p><?php _e( 'Pull this branch into: ', 'revisr' ); ?></p>
		<input id="current_branch" type="radio" name="destination" value="<?php echo $git->branch; ?>" />
		<label for="current_branch"><?php _e( 'Current Branch', 'revisr' ); ?></label>
		<br>
		<input id="new_branch" type="radio" name="destination" value="new_branch" />
		<label for="new_branch"><?php _e( 'New Branch: ', 'revisr' ); ?><input type="text" name="new_branch_name" />
		<input type="hidden" name="action" value="process_pull_remote">
		<input type="hidden" name="branch" value="<?php echo $_GET['remote_branch']; ?>">
		<p id="pull-remote-submit" style="margin:0;padding:0;text-align:center;">
			<button id="confirm-delete-branch-btn" class="button button-primary" style="background-color:#EB5A35;height:30px;width:45%;margin-top:15px;border-radius:4px;border:1px #972121 solid;color:#fff;font-size:13px;">
			<?php _e( 'Pull Branch', 'revisr' ); ?></button>
		</p>
	</form>
</div>