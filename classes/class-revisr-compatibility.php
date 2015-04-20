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
		if ( ! is_writable( $path ) || ! is_writeable( $path . '/.git/config' ) ) {
			return 'false';
		}
		return 'true';
	}
}
