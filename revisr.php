<?php
/**
 * The official Revisr WordPress plugin.
 *
 * A plugin that allows developers to manage WordPress websites with Git repositories.
 * Integrates several key git functions into the WordPress admin section.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 *
 * Plugin Name:       Revisr
 * Plugin URI:        http://revisr.io/
 * Description:       A plugin that allows developers to manage WordPress websites with Git repositories.
 * Version:           1.6
 * Text Domain:		  revisr
 * Domain Path:		  /languages/
 * Author:            Expanded Fronts
 * Author URI: 		  http://revisr.io/
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-revisr.php';

$revisr = new Revisr();

register_activation_hook( __FILE__, array( $revisr, 'revisr_install' ) );

$plugin = plugin_basename( __FILE__ );

add_filter("plugin_action_links_$plugin", array( $revisr, 'revisr_settings_link' ) );