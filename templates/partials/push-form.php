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

$styles_url = REVISR_URL . 'assets/css/thickbox.css?04162015';

// Grab any unpushed commits.
$unpushed = revisr()->git->run( 'log', array( revisr()->git->branch, '--not', '--remotes', '--oneline' ) );

?>

<link href="<?php echo $styles_url; ?>" rel="stylesheet" type="text/css">

<form action="<?php echo get_admin_url() . 'admin-post.php'; ?>" method="post">

	<div class="revisr-tb-description">

	<?php if ( is_array( $unpushed ) && 0 !== count( $unpushed ) ): ?>

		<p><?php _e( 'Are you sure you want to push the following commits?', 'revisr' ); ?></p>

		<ul class="revisr-unpushed" style="margin-left:-20px; margin-bottom:50px;">

			<?php foreach ( $unpushed as $commit ): ?>

				<?php

					$hash 	= substr( $commit, 0, 7 );
					$link 	= Revisr_Admin::get_the_id_by_hash( $hash, true );
					$title 	= substr( $commit, 7 );

					if ( $link ) {
						$commit_li = $link . $title;
					} else {
						$commit_li = $commit;
					}
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
