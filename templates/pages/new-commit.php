<?php
/**
 * new-commit.php
 *
 * Displays the form to create new commit.
 *
 * @package 	Revisr
 * @license 	GPLv3
 * @link		https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

?>

<div class="wrap">

    <h1><?php _e( 'New Commit', 'revisr' ); ?></h1>

    <?php if ( isset( $_GET['error'] ) ) Revisr_Admin::render_alert( true ); ?>

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
							<input type="text" name="post_title" size="30" value="" id="title" spellcheck="true" autocomplete="off" placeholder="<?php _e( 'Enter a message for your commit', 'revisr' ); ?>">
						</div>
					</div><!-- /titlediv -->
                </div>

                <div id="postbox-container-1" class="postbox-container">
                    <?php do_meta_boxes( 'admin_page_revisr_new_commit', 'side', null ); ?>
                </div>

                <div id="postbox-container-2" class="postbox-container">
                    <?php do_meta_boxes( 'admin_page_revisr_new_commit', 'normal', null ); ?>
                </div>

            </div> <!-- #post-body -->

        </div> <!-- #poststuff -->

    </form><!-- #revisr-commit-form -->

</div><!-- .wrap -->
