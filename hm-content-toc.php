<?php
/**
 * Plugin Name: HM Content TOC
 * Plugin URI:
 * Description: Creates content TOC (table of contents) for specified header elements from the current post content. Adds anchor elements in the content just before the matched headers. Integrates with: <a href="https://wordpress.org/plugins/shortcode-ui/" target="_blank">Shortcake UI plugin</a>
 * Version:     1.0.1
 * Author:      Dasha Luna at Human Made
 * Author URI:  https://github.com/dashaluna
 * Text Domain: hm-content-toc
 * Domain Path: /languages
 * License:     GPL2
 */
namespace HM\Content_TOC;

// Abort - if this file is accessed directly and not via WP
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include plugin classes
require_once( __DIR__ . '/includes/class-content-toc.php' );
require_once( __DIR__ . '/includes/admin/class-admin.php' );

/**
 * Instantiate TOC class, i.e. TOC logic is hooked into WP
 *
 * @return TOC True single instance of the class
 */
function get_toc() {
	return TOC::get_instance();
}

/**
 * Instantiate Admin class, i.e. Admin logic is hooked into WP
 *
 * @return Admin True single instance of the class
 */
function get_admin() {
	return Admin::get_instance( __FILE__ );
}

// Activate the plugin
add_action( 'plugins_loaded', __NAMESPACE__ . '\\get_toc' );

// Add plugin admin page
add_action( 'plugins_loaded', __NAMESPACE__ . '\\get_admin' );
