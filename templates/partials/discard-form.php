<?php
/**
 * discard-form.php
 *
 * Displays the form to discard all untracked changes.
 *
 * @package   	Revisr
 * @license   	GPLv3
 * @link      	https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Disallow direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

$styles_url = REVISR_URL . 'assets/css/thickbox.css?v=' . REVISR_VERSION;

?>

<link href="<?php echo $styles_url; ?>" rel="stylesheet" type="text/css">

<div class="revisr-tb-description">

	<p><?php _e( 'Are you sure you want to discard your uncommitted changes?', 'revisr' ); ?></p>
	<p><?php _e( 'This will delete any untracked files and reset the repository to the state of your last commit.', 'revisr' ); ?></p>

</div>

<div class="revisr-tb-submit">
	<button id="confirm-delete-branch-btn" class="revisr-tb-btn revisr-tb-danger" onclick="self.parent.revisr_discard();return false;"><?php _e( 'Discard Changes', 'revisr' ); ?></button><button class="revisr-tb-btn revisr-btn-cancel" onclick="self.parent.tb_remove();return false"><?php _e( 'Cancel', 'revisr' ); ?></button>
</div>

