<?php
/**
 * revert-form.php
 * 
 * Displays the form to revert to a specific commit.
 *
 * @package 	Revisr
 * @license 	GPLv3
 * @link 		https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Disallow direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

$commit 	= Revisr_Admin::get_commit_details( $_GET['commit_id'] );
$styles_url = REVISR_URL . 'assets/css/thickbox.css';

?>
	<form action="<?php echo get_admin_url() . 'admin-post.php'; ?>" method="post">

		<div class="revisr-tb-description">

		<p><?php _e( 'Are you sure you want to revert to this commit?', 'revisr' ); ?></p>

		<p>
			<select name="revert_type">
				<option><?php _e( 'Revert files', 'revisr' ); ?></option>
				<option><?php _e( 'Revert database', 'revisr' ); ?></option>
				<option><?php _e( 'Revert files and database', 'revisr' ); ?></option>
			</select>
		</p>

		</div>

		<div class="revisr-tb-submit">
			<button class="revisr-tb-btn revisr-tb-danger" type="submit"><?php _e( 'Revert', 'revisr' ); ?></button><button class="revisr-tb-btn revisr-btn-cancel"><?php _e( 'Cancel', 'revisr' ); ?></button>
		</div>

	</form>
</div>