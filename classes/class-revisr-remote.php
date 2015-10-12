<?php
/**
 * class-revisr-remote.php
 *
 * Processes remote updates for Revisr.
 *
 * @package   	Revisr
 * @license   	GPLv3
 * @link      	https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr_Remote {

	/**
	 * Returns the current token, creating one if it does not exist.
	 * @access public
	 * @return string|boolean The token, or false on complete failure.
	 */
	public function get_token() {
		$check = revisr()->git->get_config( 'revisr', 'token' );

		if ( $check === false ) {

			// If there is no token, generate a new one and save it.
			$token 	= wp_generate_password( 16, false, false );
			revisr()->git->set_config( 'revisr', 'token', $token );

			// Make sure that the token saved correctly.
			$new_token = revisr()->git->get_config( 'revisr', 'token' );
			if ( is_string( $new_token ) && hash_equals( $new_token, $token ) ) {
				if ( $new_token !== false ) {
					return $new_token;
				}
			} else {
				return false;
			}

		} else {
			// Return the already saved token.
			return $check;
		}
	}

	/**
	 * Verifies a token is valid.
	 * @access public
	 * @return boolean
	 */
	public function check_token( $token = '' ) {

		// Allow testing of this function.
		if ( $token !== '' ) {
			$token_to_check = $token;
		}

		// This is set in the Webhook URL.
		if ( isset( $_REQUEST['token'] ) && $_REQUEST['token'] !== '' ) {
			$token_to_check = $_REQUEST['token'];
		}

		// Compare the tokens and return true if a complete match.
		if ( isset( $token_to_check ) ) {
			$safe_token = revisr()->git->get_config( 'revisr', 'token' );
			if ( hash_equals( $safe_token, $token_to_check ) ) {
				return true;
			}
		}

		// Die if not.
		wp_die( __( 'Cheatin&#8217; uh?', 'revisr' ) );
	}

	/**
	 * Sends a new HTTP request to the live site.
	 * @access public
	 */
	public function send_request() {
		$body 	= array(
			'action' 		=> 'revisr_update'
		);
		$args 	= array(
			'method' 		=> 'POST',
			'timeout'		=> '30',
			'redirection'	=> '5',
			'httpversion'	=> '1.0',
			'blocking'		=> true,
			'headers'		=> array(),
			'body'			=> $body
		);

		// Get the URL and send the request.
		$get_url = revisr()->git->get_config( 'revisr', 'webhook-url' );

		if ( $get_url !== false ) {
			$webhook = urldecode( $get_url );
			$request = wp_remote_post( $webhook, $args );
			if ( is_wp_error( $request ) ) {
				Revisr_Admin::log( __( 'Error contacting webhook URL.', 'revisr' ), 'error' );
			} else {
				Revisr_Admin::log( __( 'Sent update request to the webhook.', 'revisr' ), 'push' );
			}
		}
	}

}
