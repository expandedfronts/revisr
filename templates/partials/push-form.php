<?php
/**
 * push-form.php
 *
 * Displays the form for pushing commits to a remote.
 *
 * @package 	Revisr
 * @license 	GPLv3
 * @link 		https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

 // Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

$styles_url = REVISR_URL . 'assets/css/thickbox.css?v=' . REVISR_VERSION;

// Grab any unpushed commits.
$unpushed = revisr()->git->run( 'log', array( revisr()->git->branch, '--not', '--remotes', '--oneline' ) );

?>

<link href="<?php echo $styles_url; ?>" rel="stylesheet" type="text/css">

<form action="<?php echo get_admin_url() . 'admin-post.php'; ?>" method="post">

	<div class="revisr-tb-description">

	<?php if ( ! revisr()->git->has_remote() ): ?>

		<p><?php _e( 'Are you sure you sure you want to push all committed changes to the remote?','revisr' ); ?></p>

	<?php elseif ( is_array( $unpushed ) && 0 !== count( $unpushed ) ): ?>

		<p><?php _e( 'Are you sure you want to push the following commits?', 'revisr' ); ?></p>

		<ul class="revisr-unpushed" style="margin-left:-20px; margin-bottom:50px;">

			<?php foreach ( $unpushed as $commit ): ?>

				<?php

					$hash 	= substr( $commit, 0, 7 );
					$link 	= '<a href="' . get_admin_url() . 'admin.php?page=revisr_view_commit&commit=' . $hash . '" target="_blank">' . $hash . '</a>';
					$title 	= substr( $commit, 7 );

					$commit_li = $link . $title;
				?>

				<li><?php echo $commit_li; ?></li>

			<?php endforeach; ?>

		</ul>

	<?php else: ?>

		<p><?php _e( 'There are no commits to push to the remote. Would you like to make a new one?', 'revisr' ); ?></p>

	<?php endif; ?>

	</div>

	<div class="revisr-tb-submit">
		<button id="tb-push-btn" class="revisr-tb-btn revisr-tb-danger" type="submit" onclick="self.parent.revisr_push();return false;"><?php _e( 'Push Changes', 'revisr' ); ?></button><button class="revisr-tb-btn revisr-btn-cancel" onclick="self.parent.tb_remove();return false"><?php _e( 'Cancel', 'revisr' ); ?></button>
	</div>

</form>
