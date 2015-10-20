<?php
/**
 * pull-form.php
 *
 * Displays the form for pulling commits from a remote.
 *
 * @package 	Revisr
 * @license 	GPLv3
 * @link 		https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

 // Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

$styles_url = REVISR_URL . 'assets/css/thickbox.css?v=' . REVISR_VERSION;

// Grab any unpulled commits.
$unpulled 	= revisr()->git->run( 'log', array( revisr()->git->branch . '..' . revisr()->git->remote . '/' . revisr()->git->branch, '--format=%h - %s' ) );

// If none are detected, do an extra fetch, just to be sure.
if ( is_array( $unpulled ) && 0 === count( $unpulled ) ) {
	revisr()->git->fetch();
	$unpulled = revisr()->git->run( 'log', array( revisr()->git->branch . '..' . revisr()->git->remote . '/' . revisr()->git->branch, '--format=%h - %s' ) );
}

?>

<link href="<?php echo $styles_url; ?>" rel="stylesheet" type="text/css">

<form action="<?php echo get_admin_url() . 'admin-post.php'; ?>" method="post">

	<div class="revisr-tb-description">

	<?php if ( is_array( $unpulled ) && 0 !== count( $unpulled ) ): ?>

		<p><?php _e( 'Are you sure you want to pull the following commits?', 'revisr' ); ?></p>

		<ul class="revisr-unpulled" style="margin-left:-20px; margin-bottom:50px;">

			<?php foreach ( $unpulled as $commit ): ?>

				<li><?php echo $commit; ?></li>

			<?php endforeach; ?>

		</ul>

	<?php else: ?>

		<p><?php _e( 'The local repository is already up-to-date with the remote repository.', 'revisr' ); ?></p>

	<?php endif; ?>

	</div>

	<div class="revisr-tb-submit">
		<button id="tb-pull-btn" class="revisr-tb-btn revisr-tb-danger" type="submit" onclick="self.parent.revisr_pull();return false;"><?php _e( 'Pull Changes', 'revisr' ); ?></button><button class="revisr-tb-btn revisr-btn-cancel" onclick="self.parent.tb_remove();return false"><?php _e( 'Cancel', 'revisr' ); ?></button>
	</div>

</form>
