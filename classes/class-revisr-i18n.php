<?php
/**
 * class-revisr-i18n.php
 *
 * Defines the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that its ready for translation.
 *
 * @package   	Revisr
 * @license   	GPLv3
 * @link      	https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr_i18n {

	/**
	 * The domain specified for this plugin.
	 * @access 	private
	 * @var 	string $domain The domain identifier for this plugin.
	 */
	private $domain;

	/**
	 * Load the plugin text domain for translation.
	 * @access public
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			$this->domain,
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . 'languages/'
		);
	}

	/**
	 * Set the domain equal to that of the specified domain.
	 * @access public
	 * @param  string $domain The domain that represents the locale of this plugin.
	 */
	public function set_domain( $domain ) {
		$this->domain = $domain;
	}

}
