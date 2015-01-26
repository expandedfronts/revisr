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

$commit 	= Revisr_Admin::get_commit_details( $_GET['commit_id'] );
$styles_url = REVISR_URL . 'assets/css/thickbox.css';

$revert_nonce 		= wp_nonce_url( admin_url( 'admin-post.php?action=process_revert&commit_hash=' . $commit . '&branch=' . $commit_branch . '&post_id=' . $id ), 'revert', 'revert_nonce' );
$actions['revert'] 	= "<a href='" . $revert_nonce . "'>" . __( 'Revert Files', 'revisr' ) . "</a>";

// If there is a database backup available to revert to, display the revert link.
if ( isset( $_GET['db_hash'] ) && $_GET['db_hash'] != '' ) {
	$revert_db_nonce = wp_nonce_url( admin_url( 'admin-post.php?action=revert_db&db_hash=' . $commit['db_hash'] . '&branch=' . $commit['branch'] . '&backup_method=' . $commit['db_backup_method'] . '&post_id=' . $id ), 'revert_db', 'revert_db_nonce' );
	$actions['revert_db'] = '<a href="' . $revert_db_nonce . '">' . __( 'Revert Database', 'revisr' ) . '</a>';
}

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