<?php
/**
 * merge-form.php
 *
 * Displays the form to merge a branch.
 *
 * @package   	Revisr
 * @license   	GPLv3
 * @link      	https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Disallow direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

$styles_url = REVISR_URL . 'assets/css/thickbox.css?02162015';
$merge_text = sprintf( __( 'This will merge changes from branch <strong>%s</strong> into the current branch. In the event of conflicts, Revisr will keep the version from the branch being merged in.', 'revisr' ), esc_html( $_GET['branch'] ) );

?>

<link href="<?php echo $styles_url; ?>" rel="stylesheet" type="text/css">

<form action="<?php echo get_admin_url(); ?>admin-post.php" method="post">

	<div class="revisr-tb-description">
		<p><?php echo $merge_text; ?></p>
		<input id="import_db" type="checkbox" name="import_db" />
		<label for="import_db"><?php _e( 'Import tracked database tables', 'revisr' ); ?></label>
	</div>

	<div class="revisr-tb-submit">
		<input type="hidden" name="action" value="process_merge">
		<input type="hidden" name="branch" value="<?php echo esc_html( $_GET['branch'] ); ?>">
		<?php wp_nonce_field( 'process_merge', 'revisr_merge_nonce' ); ?>
		<button id="merge-btn" class="revisr-tb-btn revisr-tb-danger"><?php _e( 'Merge Branch', 'revisr' ); ?></button><button class="revisr-tb-btn revisr-btn-cancel" onclick="self.parent.tb_remove();return false"><?php _e( 'Cancel', 'revisr' ); ?></button>
	</div>

</form>
