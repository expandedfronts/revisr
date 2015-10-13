<?php
/**
 * view-commit.php
 *
 * Displays details on an existing commit.
 *
 * @package 	Revisr
 * @license 	GPLv3
 * @link		https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

// Get details about the commit.
$commit_hash    = isset( $_GET['commit'] ) ? esc_attr( $_GET['commit'] ) : '';
$commit         = Revisr_Git::get_commit_details( $commit_hash );
$subject        = esc_attr( $commit['subject'] );
?>

<div class="wrap">

    <h1><?php _e( 'View Commit', 'revisr' ); ?> <a href="<?php echo get_admin_url(); ?>admin.php?page=revisr_new_commit" class="page-title-action"><?php _e( 'Add New', 'revisr' ); ?></a></h1>

    <?php
        if ( isset( $_GET['success'] ) ) {
            $msg = sprintf( __( 'Committed files on branch <strong>%s</strong>.', 'revisr' ), revisr()->git->branch );
            echo '<div class="revisr-alert updated"><p>' . $msg . '</p></div>';
        }
    ?>

    <form id="revisr-commit-form" method="post" action="<?php echo get_admin_url(); ?>admin-post.php">

        <input type="hidden" name="action" value="process_commit">
        <?php wp_nonce_field( 'process_commit', 'revisr_commit_nonce' );

        // Save metabox order, state
        wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
        wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>

        <div id="poststuff">

            <div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">

                <div id="post-body-content">
					<div id="titlediv">
						<div id="titlewrap">
							<input type="text" name="post_title" size="30" id="title" spellcheck="true" autocomplete="off" value="<?php echo $subject; ?>">
						</div>
					</div><!-- /titlediv -->
                </div>

                <div id="postbox-container-1" class="postbox-container">
                    <?php do_meta_boxes( 'admin_page_revisr_view_commit', 'side', null ); ?>
                </div>

                <div id="postbox-container-2" class="postbox-container">
                    <?php do_meta_boxes( 'admin_page_revisr_view_commit', 'normal', null ); ?>
                </div>

            </div> <!-- #post-body -->

        </div> <!-- #poststuff -->

    </form><!-- #revisr-commit-form -->

</div><!-- .wrap -->
