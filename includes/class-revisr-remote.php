<?php
/**
 * class-revisr-remote.php
 *
 * Processes remote updates for Revisr.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 */

// Disallow direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr_Remote {

	/**
	 * User options and preferences.
	 * @var array
	 */
	protected $options;

	/**
	 * The URL of the live instance.
	 * @var string
	 */
	protected $url;

	/**
	 * Initialize the class and get things going.
	 * @access public
	 */
	public function __construct() {
		$this->options 	= Revisr::get_options();
		$this->url 		= $this->get_live_url();
	}

	/**
	 * Gets the live URL, exits the script if one does not exist.
	 * @access private
	 * @return string
	 */
	private function get_live_url() {
		if ( isset( $this->options['live_url'] ) && $this->options['live_url'] != '' ) {
			return $this->options['live_url'];
		} else {
			wp_die( __( 'Live URL not set.', 'revisr' ) );
		}
	}

	/**
	 * Sends a new HTTP request to the live site.
	 * @access public
	 */
	public function send_request() {
		$db 	= new Revisr_DB();
		$body 	= array(
			'dev_url' 		=> site_url(),
			'action' 		=> 'revisr_update',
			'tables' 		=> $db->get_tracked_tables(),
			'import_db' 	=> true,
			'new_branch' 	=> false
		);
		$args 	= array(
			'method' 		=> 'POST',
			'timeout'		=> '45',
			'redirection'	=> '5',
			'httpversion'	=> '1.0',
			'blocking'		=> true,
			'headers'		=> array(),
			'body'			=> $body
		);
		$request = wp_remote_post( $this->url, $args );

		if ( is_wp_error( $request ) ) {
			Revisr_Admin::log( __( 'Push request to live failed.', 'revisr' ), 'error' );
		} else {
			Revisr_Admin::log( __( 'Push request to live successful.', 'revisr' ), 'push' );
		}
	}
}