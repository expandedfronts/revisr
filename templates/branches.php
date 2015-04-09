<?php
/**
 * Displays the main dashboard page.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

$revisr = revisr();

?>

<div class="wrap">
	<h2><?php _e( 'Revisr - Branches', 'revisr' ); ?></h2>
	<?php
		if ( isset( $_GET['status'] ) && isset( $_GET['branch'] ) ) {
			switch ( $_GET['status'] ) {
				case "create_success":
					$msg = sprintf( esc_html__( 'Successfully created branch: %s.', 'revisr' ), $_GET['branch'] );
					echo '<div id="revisr-alert" class="updated" style="margin-top:20px;"><p>' . $msg . '</p></div>';
					break;
				case "create_error":
					$msg = __( 'Failed to create the new branch.', 'revisr' );
					if ( $revisr->git->is_branch( $_GET['branch'] ) ) {
						$msg = sprintf( esc_html__( 'Failed to create branch: %s (branch already exists).', 'revisr' ), $_GET['branch'] );
					}
					echo '<div id="revisr-alert" class="error" style="margin-top:20px;"><p>' . $msg . '</p></div>';
					break;
				case "delete_success":
					$msg = sprintf( esc_html__( 'Successfully deleted branch: %s.', 'revisr' ), $_GET['branch'] );
					echo '<div id="revisr-alert" class="updated" style="margin-top:20px;"><p>' . $msg . '</p></div>';
					break;
				default:
					// Do nothing.
			}
		}
	?>
	<div id="col-container" class="revisr_col_container">
		<div id="col-right">
			<form id="revisr_branch_form">
				<table class="widefat" id="revisr_branch_table">
				<thead>
					<tr>
						<th><?php _e( 'Branch', 'revisr' ); ?></th>
						<th class="center-td"><?php _e( 'Commits', 'revisr' ); ?></th>
						<th class="center-td"><?php _e( 'Actions', 'revisr' ); ?></th>
					</tr>
				</thead>
					<?php
						$output 	= $revisr->git->get_branches();
						$admin_url 	= get_admin_url();

						if ( is_array( $output ) ) {

							foreach ( $output as $key => $value ) {

								$branch 		= substr( $value, 2 );
								$num_commits 	= Revisr_Admin::count_commits( $branch );

								if ( substr( $value, 0, 1 ) === "*" ){

									?>
									<tr>
										<td><strong><?php printf( __( '%s (current branch)', 'revisr' ), $branch ); ?></strong></td>
										<td class='center-td'><?php echo $num_commits; ?></td>
										<td class="center-td">
											<a class="button disabled branch-btn" onclick="preventDefault()" href="#"><?php _e( 'Checkout', 'revisr' ); ?></a>
											<a class="button disabled branch-btn" onclick="preventDefault()" href="#"><?php _e( 'Merge', 'revisr' ); ?></a>
											<a class="button disabled branch-btn" onclick="preventDefault()" href="#"><?php _e( 'Delete', 'revisr' ); ?></a>
										</td>
									</tr>
									<?php

								} else {

									$checkout_url 		= wp_nonce_url( $admin_url . "admin-post.php?action=process_checkout&branch=" . $branch, 'process_checkout', 'revisr_checkout_nonce' );
									$merge_url 			= $admin_url . "admin-post.php?action=merge_branch_form&branch=" . $branch . "&TB_iframe=true&width=350&height=200";
									$delete_url 		= $admin_url . "admin-post.php?action=delete_branch_form&branch=" . $branch . "&TB_iframe=true&width=350&height=200";
									$pull_remote_url 	= $admin_url . "admin-post.php?action=pull_remote_form&remote_branch=" . $branch . "&TB_iframe=true&width=350&height=200";
									?>
									<tr>
										<td><?php echo $branch; ?></td>
										<td style='text-align:center;'><?php echo $num_commits; ?></td>
										<td class="center-td">
											<a class='button branch-btn' href='<?php echo $checkout_url; ?>'><?php _e( 'Checkout', 'revisr' ); ?></a>
											<a class='button branch-btn merge-btn thickbox' href="<?php echo $merge_url; ?>" title="<?php _e( 'Merge Branch', 'revisr' ); ?>"><?php _e( 'Merge', 'revisr' ); ?></a>
											<a class='button branch-btn delete-branch-btn thickbox' href='<?php echo $delete_url; ?>' title='<?php _e( 'Delete Branch', 'revisr' ); ?>'><?php _e( 'Delete', 'revisr' ); ?></a>
										</td>
									</tr>
									<?php

								}
							}
						}

					?>
					<tfoot>
						<tr>
							<th><?php _e( 'Branch', 'revisr' ); ?></th>
							<th class="center-td"><?php _e( 'Commits', 'revisr' ); ?></th>
							<th class="center-td"><?php _e( 'Actions', 'revisr' ); ?></th>
						</tr>
					</tfoot>
				</table>
			</form>
		</div><!-- /#col-right -->
		<div id="col-left">
			<div id="add-branch-box" class="postbox">
				<h3 id="add-branch-title"><?php _e( 'Add New Branch', 'revisr' ); ?></h3>
				<div class="inside">
					<form id="add_branch" method="post" action="<?php echo get_admin_url() . 'admin-post.php'; ?>">
						<div class="form-field form-required">
							<label for="tag-name"><strong><?php _e( 'Name', 'revisr' ); ?></strong></label>
							<input name="branch_name" id="branch-name" type="text" value="" size="40" aria-required="true">
							<p class="description"><?php _e( 'The name of the new branch.', 'revisr' ); ?></p><br>
						</div>
						<div class="form-field">
							<input id="checkout-new-branch" type="checkbox" name="checkout_new_branch" style="width: 17px;">
							<label  id="checkout-label" for="checkout-new-branch"><?php _e('Checkout new branch?'); ?></label>
							<input type="hidden" name="action" value="process_create_branch">
							<?php wp_nonce_field( 'process_create_branch', 'revisr_create_branch_nonce' ); ?>
							<p id="add-branch-submit" class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Create Branch', 'revisr' ); ?>" style="width:150px;"></p>
						</div>
					</form>
				</div>
			</div>

		</div><!-- /#col-left-->

	</div><!-- /#col-container -->

</div><!-- /.wrap -->
