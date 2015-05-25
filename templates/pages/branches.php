<?php
/**
 * branches.php
 *
 * Displays the branch management page.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

// Prepares the Revisr custom list table.
revisr()->branch_table->prepare_items();

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
					if ( revisr()->git->is_branch( $_GET['branch'] ) ) {
						$msg = sprintf( esc_html__( 'Failed to create branch: %s (branch already exists).', 'revisr' ), $_GET['branch'] );
					}
					echo '<div id="revisr-alert" class="error" style="margin-top:20px;"><p>' . $msg . '</p></div>';
					break;
				case "delete_success":
					$msg = sprintf( esc_html__( 'Successfully deleted branch: %s.', 'revisr' ), $_GET['branch'] );
					echo '<div id="revisr-alert" class="updated" style="margin-top:20px;"><p>' . $msg . '</p></div>';
					break;
				case "delete_fail":
					$msg = sprintf( esc_html__( 'Failed to delete branch: %s.', 'revisr' ), $_GET['branch'] );
					echo '<div id="revisr-alert" class="error" style="margin-top:20px;"><p>' . $msg . '</p></div>';
				default:
					// Do nothing.
			}
		}
	?>

	<div id="col-container" class="revisr-col-container">

		<div id="col-right">
			<form id="revisr-branch-form">
				<?php revisr()->branch_table->display(); ?>
			</form>
		</div><!-- /#col-right -->

		<div id="col-left">
			
			<div id="revisr-add-branch-box" class="postbox">
				<h3><?php _e( 'Add New Branch', 'revisr' ); ?></h3>
				<div class="inside">
					<form id="revisr-add-branch-form" method="post" action="<?php echo get_admin_url() . 'admin-post.php'; ?>">
						<div class="form-field form-required">
							<label for="revisr-branch-name"><strong><?php _e( 'Name', 'revisr' ); ?></strong></label>
							<input id="revisr-branch-name" name="branch_name" type="text" value="" size="40" aria-required="true" />
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
