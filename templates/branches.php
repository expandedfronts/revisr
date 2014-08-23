<?php
/**
 * Displays the main dashboard page.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 */
?>

<div class="wrap">
	<h2><?php _e( 'Branches', 'revisr' ); ?></h2>
	<div id="col-container" class="revisr_col_container">
		<div id="col-right">
			<form id="revisr_branch_form">
				<table class="widefat" id="revisr_branch_table">
				<thead>
					<tr>
						<th><?php _e( 'Branch', 'revisr' ); ?></th>
						<th><?php _e( 'Actions', 'revisr' ); ?></th>
					</tr>
				</thead>
					<?php

						$output = Revisr_Git::run( 'branch' );
						if ( is_array( $output ) ) {
							foreach ($output as $key => $value){
								$branch = substr($value, 2);
								
								if (substr( $value, 0, 1 ) === "*"){
									echo "<tr>
									<td><strong>$branch (current branch)</strong></td>
									<td style='text-align:center;'>
										<a class='button disabled branch-btn' onclick='preventDefault()' href='#'>Checkout</a>
										<a class='button disabled branch-btn' onclick='preventDefault()' href='#'>Delete</a>
									</td></tr>";
								} else {
									$checkout_url = get_admin_url() . "admin-post.php?action=checkout&branch={$branch}";
									$delete_url = get_admin_url() . "admin-post.php?action=delete_branch_form&branch={$branch}&TB_iframe=true&width=350&height=150";
									?>
									<tr>
									<td><?php echo $branch; ?></td>
									<td style='text-align:center;'>
										<a class='button branch-btn' href='<?php echo $checkout_url; ?>'><?php _e( 'Checkout', 'revisr' ); ?></a>
										<a class='button branch-btn delete-branch-btn thickbox' href='<?php echo $delete_url; ?>' title='<?php _e( 'Delete Branch', 'revisr' ); ?>'><?php _e( 'Delete', 'revisr' ); ?></a>
									</td></tr>
									<?php
								}
							}										
						}

					?>
				</table>
			</form>
		</div><!-- /#col-right -->
		<div id="col-left">
			<div class="form-wrap">
				<h3><?php _e( 'Add New Branch', 'revisr' ); ?>
				<form id="add_branch" method="post" action="<?php echo get_admin_url() . 'admin-post.php'; ?>">
					<div class="form-field form-required">
						<label for="tag-name"><?php _e( 'Name', 'revisr' ); ?></label>
						<input name="branch_name" id="branch-name" type="text" value="" size="40" aria-required="true">
						<p><?php _e( 'The name of the new branch.', 'revisr' ); ?></p>
					</div>
					<div class="form-field">
						<input id="checkout-new-branch" type="checkbox" name="checkout_new_branch" style="width: 17px;">
						<label  id="checkout-label" for="checkout"><?php _e('Checkout new branch?'); ?></label>
						<input type="hidden" name="action" value="create_branch">
						<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Create Branch', 'revisr' ); ?>"></p>
					</div>
				</form>
			</div>

		</div><!-- /#col-left-->

	</div><!-- /#col-container -->

</div><!-- /.wrap -->