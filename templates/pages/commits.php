<?php
/**
 * commits.php
 *
 * Displays the commits list table page.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

// Prepares the Revisr custom list table.
revisr()->commits_table->prepare_items();

// Gets current branches for filtering.
$branches = revisr()->git->get_branches();

?>

<div class="wrap">

	<h1><?php _e( 'Commits', 'revisr' ); ?> <a href="<?php echo get_admin_url(); ?>admin.php?page=revisr_new_commit" class="page-title-action"><?php _e( 'Add New', 'revisr' ); ?></a></h1>

	<?php revisr()->commits_table->render_views(); ?>

	<form id="revisr-commits-filter" action="<?php echo get_admin_url();?>admin.php">
		<input type="hidden" name="page" value="revisr_commits" />
		<?php revisr()->commits_table->render_search(); ?>
		<?php revisr()->commits_table->display(); ?>
	</form>

</div><!-- /.wrap -->
