<?php
/**
 * The official Revisr WordPress plugin.
 *
 * A plugin that allows users to manage WordPress websites with Git repositories.
 * Integrates several key git functions into the WordPress admin section.
 *
 * Plugin Name:       Revisr
 * Plugin URI:        http://revisr.io/
 * Description:       A plugin that allows users to manage WordPress websites with Git repositories.
 * Version:           1.7.1
 * Author:            Expanded Fronts, LLC
 * Author URI:        http://expandedfronts.com/
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       revisr
 * Domain Path:       /languages
 * Network: 		  true
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/** Abort if this file was called directly. */
if ( ! defined( 'WPINC' ) ) {
	die;
}

/** Loads the main plugin class. */
require plugin_dir_path( __FILE__ ) . 'includes/class-revisr.php';

/** Begins execution of the plugin. */
$revisr = new Revisr();

/** Registers the activation hook. */
register_activation_hook( __FILE__, array( $revisr, 'revisr_install' ) );

/** Adds the settings link to the WordPress "Plugins" page. */
add_filter( 'plugin_action_links_'  . plugin_basename(__FILE__), array( $revisr, 'revisr_settings_link' ) );
