<?php
/**
 * checkout-remote-form.php
 *
 * Displays the form to checkout a remote branch.
 *
 * @package 	Revisr
 * @license 	GPLv3
 * @link 		https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

$styles_url = REVISR_URL . 'assets/css/thickbox.css?v=' . REVISR_VERSION;

$remote = esc_html( $_GET['branch'] );
$local 	= substr( $remote, strlen( revisr()->git->current_remote() ) + 1 );

if ( revisr()->git->is_branch( $local ) ) {
	$text = sprintf( __( 'Revisr detected that local branch <strong>%s</strong> may already be tracking remote branch <strong>%s</strong>.<br><br>Do you want to checkout local branch <strong>%s</strong>?', 'revisr' ), $local, $remote, $local );
} else {
	$text = sprintf( __( 'This will checkout remote branch <strong>%s</strong> into a new local branch <strong>%s</strong>.', 'revisr' ), $remote, $local );
}

?>

<link href="<?php echo $styles_url; ?>" rel="stylesheet" type="text/css">

<form action="<?php echo get_admin_url(); ?>admin-post.php" method="post">

	<div class="revisr-tb-description">
		<p><?php echo $text; ?></p>
		<input id="import_db" type="checkbox" name="import_db" <?php checked( revisr()->git->get_config( 'revisr', 'import-checkouts' ), 'true' ); ?> />
		<label for="import_db"><?php _e( 'Import tracked database tables', 'revisr' ); ?></label>
	</div>

	<div class="revisr-tb-submit">
		<input type="hidden" name="action" value="process_checkout" />

		<?php if ( ! revisr()->git->is_branch( $local ) ): ?>
			<input type="hidden" name="new_branch" value="true" />
		<?php endif; ?>

		<input type="hidden" name="echo_redirect" value="true" />
		<input type="hidden" name="branch" value="<?php echo esc_attr( $local ); ?>" />
		<?php wp_nonce_field( 'process_checkout', 'revisr_checkout_nonce' ); ?>
		<button id="merge-btn" class="revisr-tb-btn revisr-tb-danger"><?php _e( 'Checkout Branch', 'revisr' ); ?></button><button class="revisr-tb-btn revisr-btn-cancel" onclick="self.parent.tb_remove();return false"><?php _e( 'Cancel', 'revisr' ); ?></button>
	</div>

</form>
