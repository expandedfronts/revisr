<?php
/**
 * class-revisr-compatibility.php
 *
 * Checks if Revisr is compatible with the current server.
 *
 * @package 	Revisr
 * @license 	GPLv3
 * @link 		https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr_Compatibility {

	/**
	 * Returns the system info.
	 * @access public
	 * @return string
	 */
	public static function get_sysinfo() {

		global $wpdb;

		$return = '### Begin System Info ###' . "\n\n";

		// Basic site info
		$return .= '-- WordPress Configuration' . "\n\n";
		$return .= 'Site URL:                 ' . site_url() . "\n";
		$return .= 'Home URL:                 ' . home_url() . "\n";
		$return .= 'Multisite:                ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";
		$return .= 'Version:                  ' . get_bloginfo( 'version' ) . "\n";
		$return .= 'Language:                 ' . ( defined( 'WPLANG' ) && WPLANG ? WPLANG : 'en_US' ) . "\n";
		$return .= 'Table Prefix:             ' . 'Length: ' . strlen( $wpdb->prefix ) . "\n";
		$return .= 'WP_DEBUG:                 ' . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
		$return .= 'Memory Limit:             ' . WP_MEMORY_LIMIT . "\n";

		// Revisr Configuration
		$return .= "\n" . '-- Revisr Configuration' . "\n\n";
		$return .= 'Plugin Version:           ' . REVISR_VERSION . "\n";

		if ( isset( revisr()->options['automatic_backups'] ) && 'none' !== revisr()->options['automatic_backups'] ) {
			$backups = 'Enabled';
		} else {
			$backups = 'Disabled';
		}
		$return .= 'Automatic Backups:        ' . $backups  . "\n";

		if ( revisr()->git->get_config( 'revisr', 'auto-push' ) === 'true' ) {
			$auto_push = 'Enabled';
		} else {
			$auto_push = 'Disabled';
		}
		$return .= 'Auto-Push:                ' . $auto_push . "\n";

		if ( revisr()->git->get_config( 'revisr', 'auto-pull' ) === 'true' ) {
			$auto_pull = 'Enabled';
		} else {
			$auto_pull = 'Disabled';
		}
		$return .= 'Auto-Pull:                ' . $auto_pull . "\n";

		if ( revisr()->git->get_config( 'revisr', 'import-checkouts' ) === 'true' ) {
			$import_checkouts = 'Enabled';
		} else {
			$import_checkouts = 'Disabled';
		}
		$return .= 'Import checkouts:         ' . $import_checkouts . "\n";

		if ( revisr()->git->get_config( 'revisr', 'import-pulls' ) === 'true' ) {
			$import_pulls = 'Enabled';
		} else {
			$import_pulls = 'Disabled';
		}
		$return .= 'Import pulls:             ' . $import_pulls . "\n";
		$return .= 'Work Tree:                ' . revisr()->git->get_work_tree() . "\n";
		$return .= 'Git Dir:                  ' . revisr()->git->get_git_dir() . "\n";

		if ( revisr()->git->is_repo ) {
			$detected = 'true';
		} else {
			$detected = 'false';
		}
		$return .= 'Repository Detected:      ' . $detected . "\n";
		if ( 'true' === $detected ) {
			$return .= 'Repository Writable:      ' . Revisr_Compatibility::server_has_permissions( revisr()->git->get_git_dir() ) . "\n";
		}


		// Server Configuration
		$return .= "\n" . '-- Server Configuration' . "\n\n";
		$os = Revisr_Compatibility::get_os();
		$return .= 'Operating System:         ' . $os['name'] . "\n";
		$return .= 'PHP Version:              ' . PHP_VERSION . "\n";
		$return .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";
		$return .= 'Git Version:              ' . revisr()->git->version() . "\n";

		$return .= 'Git Install Path:         ' . Revisr_Compatibility::guess_path( 'git' ) . "\n";
		$return .= 'MySQL Install Path:       ' . Revisr_Compatibility::guess_path( 'mysql' ) . "\n";

		$return .= 'Server Software:          ' . $_SERVER['SERVER_SOFTWARE'] . "\n";
		$return .= 'Server User:              ' . Revisr_Compatibility::get_user() . "\n";

		// PHP configs... now we're getting to the important stuff
		$return .= "\n" . '-- PHP Configuration' . "\n\n";
		$return .= 'Safe Mode:                ' . ( ini_get( 'safe_mode' ) ? 'Enabled' : 'Disabled' . "\n" );

		if ( function_exists( 'exec' ) ) {
			$exec = 'Enabled';
		} else {
			$exec = 'Disabled';
		}

		$return .= 'Exec Enabled:             ' . $exec . "\n";
		$return .= 'Memory Limit:             ' . ini_get( 'memory_limit' ) . "\n";
		$return .= 'Upload Max Size:          ' . ini_get( 'upload_max_filesize' ) . "\n";
		$return .= 'Post Max Size:            ' . ini_get( 'post_max_size' ) . "\n";
		$return .= 'Upload Max Filesize:      ' . ini_get( 'upload_max_filesize' ) . "\n";
		$return .= 'Time Limit:               ' . ini_get( 'max_execution_time' ) . "\n";
		$return .= 'Max Input Vars:           ' . ini_get( 'max_input_vars' ) . "\n";
		$return .= 'Display Errors:           ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n";

		// WordPress active plugins
		$return .= "\n" . '-- WordPress Active Plugins' . "\n\n";
		$plugins = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );
		foreach( $plugins as $plugin_path => $plugin ) {
			if( !in_array( $plugin_path, $active_plugins ) )
				continue;
			$return .= $plugin['Name'] . ': ' . $plugin['Version'] . "\n";
		}

		// WordPress inactive plugins
		$return .= "\n" . '-- WordPress Inactive Plugins' . "\n\n";
		foreach( $plugins as $plugin_path => $plugin ) {
			if( in_array( $plugin_path, $active_plugins ) )
				continue;
			$return .= $plugin['Name'] . ': ' . $plugin['Version'] . "\n";
		}

		if( is_multisite() ) {
			// WordPress Multisite active plugins
			$return .= "\n" . '-- Network Active Plugins' . "\n\n";
			$plugins = wp_get_active_network_plugins();
			$active_plugins = get_site_option( 'active_sitewide_plugins', array() );
			foreach( $plugins as $plugin_path ) {
				$plugin_base = plugin_basename( $plugin_path );
				if( !array_key_exists( $plugin_base, $active_plugins ) )
					continue;
				$plugin  = get_plugin_data( $plugin_path );
				$return .= $plugin['Name'] . ': ' . $plugin['Version'] . "\n";
			}
		}

		$return .= "\n" . '### End System Info ###';
		return $return;
	}

	/**
	 * Determines the current operating system.
	 * @access public
	 * @return array
	 */
	public static function get_os() {
		$os 		= array();
		$uname 		= php_uname( 's' );
		$os['code'] = strtoupper( substr( $uname, 0, 3 ) );
		$os['name'] = $uname;
		return $os;
	}

	/**
	 * Gets the user running this PHP process.
	 * @access public
	 * @return string
	 */
	public static function get_user() {
		if ( function_exists( 'exec' ) ) {
			return exec( 'whoami' );
		}
		return __( 'Unknown', 'revisr' );
	}

	/**
	 * Tries to guess the install path to the provided program.
	 * @access public
	 * @param  string $program The program to check for.
	 * @return string
	 */
	public static function guess_path( $program ) {
		$os 		= Revisr_Compatibility::get_os();
		$program 	= Revisr_Admin::escapeshellarg( $program );

		if ( $os['code'] !== 'WIN' ) {
			$path = exec( "which $program" );
		} else {
			$path = exec( "where $program" );
		}

		if ( $path ) {
			return $path;
		} else {
			return __( 'Not Found', 'revisr' );
		}
	}

	/**
	 * Checks if the exec() function is enabled.
	 * @access public
	 * @return string
	 */
	public static function server_has_exec() {
		if ( function_exists( 'exec' ) ) {
			return 'true';
		}
		return 'false';
	}

	/**
	 * Checks if Revisr has write permissions to the repository.
	 * @access public
	 * @param  string $path The path to the repository.
	 * @return string
	 */
	public static function server_has_permissions( $path ) {
		if ( ! is_writable( $path ) || ! is_writeable( $path . DIRECTORY_SEPARATOR . 'config' ) ) {
			return 'false';
		}
		return 'true';
	}
}
