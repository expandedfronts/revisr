<?php
/**
 * merge-form.php
 * 
 * Displays the form to merge a branch.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 */
$styles_url = REVISR_URL . 'assets/css/thickbox.css';
$merge_text = sprintf( __( 'This will merge changes from branch <strong>%s</strong> into the current branch. In the event of conflicts, Revisr will keep the version from the branch being merged in.', 'revisr'), $_GET['branch'] );
?>
<link href="<?php echo $styles_url; ?>" rel="stylesheet" type="text/css">
<div class="container" style="padding:10px">
	<form action="<?php echo get_admin_url(); ?>admin-post.php" method="post">
		<p><?php echo $merge_text; ?></p>
		<input id="import_db" type="checkbox" name="import_db" />
		<label for="import_db"><?php _e( 'Import tracked database tables', 'revisr' ); ?></label>
		<input type="hidden" name="action" value="process_merge">
		<input type="hidden" name="branch" value="<?php echo $_GET['branch']; ?>">
		<p id="merge-branch-submit" style="margin:0;padding:0;text-align:center;">
			<button id="merge-btn" class="button button-primary" style="background-color:#EB5A35;height:30px;width:45%;margin-top:15px;border-radius:4px;border:1px #972121 solid;color:#fff;"><?php _e( 'Merge Branch', 'revisr' ); ?></button>
		</p>
	</form>
</div>