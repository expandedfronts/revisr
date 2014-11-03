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
$git 		= new Revisr_Git();
$db  		= new Revisr_DB();
$tables 	= $db->get_tables_not_in_db();
?>
<link href="<?php echo $styles_url; ?>" rel="stylesheet" type="text/css">
<div class="container" style="padding:10px">
	<form action="<?php echo get_admin_url(); ?>admin-post.php" method="post">
		<p><?php _e( 'The following new tables were added to the repository, but not automatically imported due to your tracking settings. Check any tables that you\'d like to import and click "Import" to continue.', 'revisr' ); ?></p>
		<?php
			foreach ( $tables as $table ) {
				echo "<input id='$table' type='checkbox' name='revisr_import_untracked[]' value='$table' /><label for='$table'>$table</label><br />";
			}
		?>
		<input type="hidden" name="action" value="process_import">
		<p id="import-tables-submit" style="margin:0;padding:0;text-align:center;">
			<button id="import-btn" class="button button-primary" style="background-color:#EB5A35;height:30px;width:45%;margin-top:15px;border-radius:4px;border:1px #972121 solid;color:#fff;"><?php _e( 'Import', 'revisr' ); ?></button>
		</p>
	</form>
</div>