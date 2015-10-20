<?php
/**
 * pull-remote-form.php
 *
 * Displays the form to delete a branch.
 *
 * @package   	Revisr
 * @license   	GPLv3
 * @link      	https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Disallow direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

$styles_url 	= REVISR_URL . "assets/css/thickbox.css?v=" . REVISR_VERSION;
$tables 		= revisr()->db->get_tables_not_in_db();

?>

<link href="<?php echo $styles_url; ?>" rel="stylesheet" type="text/css">

<form action="<?php echo get_admin_url(); ?>admin-post.php" method="post">

	<div class="revisr-tb-description">
		<p><?php _e( 'The following new tables were added to the repository, but not automatically imported due to your tracking settings. Check any tables that you\'d like to import and click "Import" to continue.', 'revisr' ); ?></p>
		<?php
			foreach ( $tables as $table ) {
				echo "<input id='$table' type='checkbox' name='revisr_import_untracked[]' value='$table' /><label for='$table'>$table</label><br />";
			}
		?>
	</div>

	<div class="revisr-tb-submit">
		<input type="hidden" name="action" value="process_import">
		<?php wp_nonce_field( 'process_import', 'revisr_import_nonce' ); ?>
		<button id="import-btn" class="revisr-tb-btn revisr-tb-danger"><?php _e( 'Import', 'revisr' ); ?></button><button class="revisr-tb-btn revisr-btn-cancel" onclick="self.parent.tb_remove();return false"><?php _e( 'Cancel', 'revisr' ); ?></button>
	</div>

</form>
