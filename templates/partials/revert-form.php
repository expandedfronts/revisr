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
$styles_url = REVISR_URL . 'assets/css/thickbox.css?02162015';

?>

	<link href="<?php echo $styles_url; ?>" rel="stylesheet" type="text/css">

	<form action="<?php echo get_admin_url() . 'admin-post.php'; ?>" method="post">

		<div class="revisr-tb-description">

		<p><?php _e( 'Are you sure you want to revert to this commit?', 'revisr' ); ?></p>

		<?php if ( $commit['db_hash'] !== '' ): ?>
			<p>
				<select name="revert_type">
					<option value="files"><?php _e( 'Revert files', 'revisr' ); ?></option>
					<option value="db"><?php _e( 'Revert database', 'revisr' ); ?></option>
					<option value="files_and_db"><?php _e( 'Revert files and database', 'revisr' ); ?></option>
				</select>
			</p>
			<input type="hidden" name="db_hash" value="<?php echo esc_attr( $commit['db_hash'] ); ?>" />
			<input type="hidden" name="backup_method" value="<?php echo esc_attr( $commit['db_backup_method'] ); ?>" />

		<?php else: ?>
			<input type="hidden" name="revert_type" value="files" />
		<?php endif; ?>

		</div>

		<div class="revisr-tb-submit">
			<input type="hidden" name="echo_redirect" value="true" />
			<input type="hidden" name="post_id" value="<?php echo esc_attr( $_GET['commit_id'] ); ?>" />
			<input type="hidden" name="branch" value="<?php echo esc_attr( $commit['branch'] ); ?>" />
			<input type="hidden" name="commit_hash" value="<?php echo esc_attr( $commit['commit_hash'] ); ?>" />
			<input type="hidden" name="action" value="process_revert" />
			<?php wp_nonce_field( 'revisr_revert_nonce', 'revisr_revert_nonce' ); ?>
			<button class="revisr-tb-btn revisr-tb-danger" type="submit"><?php _e( 'Revert', 'revisr' ); ?></button><button class="revisr-tb-btn revisr-btn-cancel" onclick="self.parent.tb_remove();return false"><?php _e( 'Cancel', 'revisr' ); ?></button>
		</div>

	</form>
</div>
