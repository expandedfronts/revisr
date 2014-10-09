<?php
/**
 * delete-branch-form.php
 * 
 * Displays the form to delete a branch.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 */
$styles_url = get_admin_url() . "load-styles.php?c=0&dir=ltr&load=dashicons,admin-bar,wp-admin,buttons,wp-auth-check";
?>
<link href="<?php echo $styles_url; ?>" rel="stylesheet" type="text/css">
<div class="container" style="padding:10px">
	<form action="<?php echo get_admin_url(); ?>admin-post.php" method="post">
		<p><?php _e( 'Are you sure you want to delete this branch? This will delete all local work on this branch.', 'revisr' ); ?></p>
		<input type="checkbox" id="delete_remote_branch" name="delete_remote_branch">
		<label for="delete_remote_branch"><?php _e( 'Also delete this branch from the remote repository.', 'revisr' ); ?></label>
		<input type="hidden" name="action" value="process_delete_branch">
		<input type="hidden" name="branch" value="<?php echo $_GET['branch']; ?>">
		<p id="delete-branch-submit" style="margin:0;padding:0;text-align:center;">
			<button id="confirm-delete-branch-btn" class="button button-primary" style="background-color:#EB5A35;height:30px;width:45%;margin-top:15px;border-radius:4px;border:1px #972121 solid;color:#fff;"><?php _e( 'Delete Branch', 'revisr' ); ?></button>
		</p>
	</form>
</div>