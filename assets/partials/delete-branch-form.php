<?php
/**
 * delete-branch-form.php
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

$styles_url 	= REVISR_URL . 'assets/css/thickbox.css?v=02162015';
$confirmation 	= sprintf( __( 'Are you sure you want to delete this branch?<br>This will delete all local work on branch <strong>%s</strong>.', 'revisr' ), esc_html( $_GET['branch'] ) );

?>

<link href="<?php echo $styles_url; ?>" rel="stylesheet" type="text/css">

<form action="<?php echo get_admin_url(); ?>admin-post.php" method="post">

	<div class="revisr-tb-description">
		<p><?php echo $confirmation; ?></p>
		<input type="checkbox" id="delete_remote_branch" name="delete_remote_branch">
		<label for="delete_remote_branch"><?php _e( 'Also delete this branch from the remote repository.', 'revisr' ); ?></label>
	</div>

	<div class="revisr-tb-submit">
		<input type="hidden" name="action" value="process_delete_branch">
		<input type="hidden" name="branch" value="<?php echo esc_html( $_GET['branch'] ); ?>">
		<?php wp_nonce_field( 'process_delete_branch', 'revisr_delete_branch_nonce' ); ?>
		<button id="confirm-delete-branch-btn" class="revisr-tb-btn revisr-tb-danger"><?php _e( 'Delete Branch', 'revisr' ); ?></button><button class="revisr-tb-btn revisr-btn-cancel" onclick="self.parent.tb_remove();return false"><?php _e( 'Cancel', 'revisr' ); ?></button>
	</div>

</form>
