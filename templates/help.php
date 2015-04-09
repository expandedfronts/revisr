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

// Load the Revisr instance.
$revisr = revisr();

?>

<h3><?php _e( 'Help and Troubleshooting', 'revisr' ); ?></h3>

<p><?php _e( 'Support is available on the <a href="https://wordpress.org/support/plugin/revisr" target="_blank">plugin support forums</a>.', 'revisr' ); ?></p>

<p><?php _e( 'Found a bug or have a feature request? Please open an issue on <a href="https://github.com/ExpandedFronts/revisr" target="_blank">GitHub</a>!', 'revisr' ); ?></p>

<table id="revisr-debug-table" class="wp-list-table widefat fixed striped">

	<tbody>

		<tr>
			<td><label><strong><?php _e( 'Operating System', 'revisr' ); ?></strong></label></td>
			<td>
			<?php
				$os = Revisr_Compatibility::get_os();
				echo $os['name'];
			?>
			</td>
		</tr>

		<tr>
			<td><label><strong><?php _e( 'Exec() Enabled', 'revisr' ); ?></strong></label></td>
			<td><?php echo Revisr_Compatibility::server_has_exec(); ?></td>
		</tr>

		<tr>
			<td><label><strong><?php _e( 'Git Install Path', 'revisr' ); ?></label></strong></td>
			<td><?php echo Revisr_Compatibility::guess_path( 'git' ); ?></td>
		</tr>

		<tr>
			<td><label><strong><?php _e( 'Git Version', 'revisr' ); ?></strong></label></td>
			<td><?php echo $revisr->git->version(); ?></td>
		</tr>

		<tr>
			<td><label><strong><?php _e( 'MySQL Install Path', 'revisr' ); ?></strong></label></td>
			<td><?php echo Revisr_Compatibility::guess_path( 'mysql' ); ?></td>
		</tr>

		<?php if ( $revisr->git->is_repo ): ?>

		<tr>
			<td><label><strong><?php _e( 'File Permissions', 'revisr' ); ?></strong></label></td>
			<td><?php echo Revisr_Compatibility::server_has_permissions( $revisr->git->get_git_dir() ); ?></td>
		</tr>

		<tr>
			<td><label><strong><?php _e( 'Repository Path', 'revisr' ); ?></strong></label></td>
			<td><?php echo $revisr->git->get_git_dir(); ?></td>
		</tr>

		<tr>
			<td><label><strong><?php _e( 'Repository Status', 'revisr' ); ?></strong></label></td>
			<td><?php printf( __( '<a href="%s" class="thickbox" title="View Status">Click here</a> to view.', 'revisr' ), wp_nonce_url( admin_url( 'admin-post.php?action=revisr_view_status&TB_iframe=true' ), 'revisr_view_status', 'revisr_status_nonce' ) ); ?></td>
		</tr>

		<?php else: ?>

		<tr>
			<td><label><strong><?php _e( 'Repository Status', 'revisr' ); ?></strong></label></td>
			<td><?php _e( 'No repository detected.', 'revisr' ); ?></td>
		</tr>

		<?php endif; ?>

	</tbody>

</table>
