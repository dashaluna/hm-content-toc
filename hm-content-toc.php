<?php
/**
 * Plugin Name: HM Content TOC
 * Plugin URI:
 * Description: Creates content TOC for specified header elements from the current post content. Adds anchor elements in the content just before the matched headers.
 * Version:
 * Author:      Dasha Luna at Human Made
 * Author URI:  https://github.com/dashaluna
 * Text Domain: hm-content-toc
 * Domain Path:
 * License:     GPL2
 */
namespace HM\Content_TOC;

// Abort - if this file is accessed directly and not via WP
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include plugin classes
require_once( __DIR__ . '/includes/class-content-toc.php' );

/**
 * Instantiate TOC class, i.e. TOC logic is hooked into WP
 *
 * @return TOC True single instance of the class
 */
function get_toc() {
	return TOC::get_instance();
}

// Activate the plugin
add_action( 'plugins_loaded', __NAMESPACE__ . '\\get_toc' );
