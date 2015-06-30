<?php
/**
 * Displays the help page.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

?>

<h3><?php _e( 'Help and Troubleshooting', 'revisr' ); ?></h3>

<p><?php _e( 'Support is available on the <a href="https://wordpress.org/support/plugin/revisr" target="_blank">plugin support forums</a>.', 'revisr' ); ?></p>

<p><?php _e( 'Found a bug or have a feature request? Please open an issue on <a href="https://github.com/ExpandedFronts/revisr" target="_blank">GitHub</a>!', 'revisr' ); ?></p>
</form>
<form class="revisr-settings-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
	<textarea readonly="readonly" onclick="this.focus(); this.select()" style="width:750px;height:500px;font-family:Menlo,Monaco,monospace;" name='revisr-sysinfo'><?php echo Revisr_Compatibility::get_sysinfo(); ?></textarea>
	<p class="submit">
		<input type="hidden" name="action" value="revisr_download_sysinfo" />
		<?php submit_button( 'Download System Info', 'primary', 'revisr-download-sysinfo', false ); ?>
	</p>
</form>
