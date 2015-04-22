<?php
/**
 * Integration with shortcake plugin - a WordPress plugin that adds UI for shortcodes
 * Resource: https://github.com/fusioneng/Shortcake
 */

namespace HM\Content_TOC;

// Abort - if this file is accessed directly and not via WP
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if shortcake  plugin is active before using it
 */
if ( function_exists( 'shortcode_ui_register_for_shortcode' ) ) {

	add_action( 'init', __NAMESPACE__ . '\\register_shortcake_ui' );
}

/**
 * Add shortcake UI integration for 'hm_content_toc' shortcode
 */
function register_shortcake_ui() {

	shortcode_ui_register_for_shortcode(
		'hm_content_toc',
		array(
			'label'         => __( 'HM Content TOC', 'hm-content-toc' ),
			'listItemImage' => 'dashicons-menu',
			// Available shortcode attributes and default values
			'attrs'         => array(

				// Title field
				array(
					'label'       => __( 'Title', 'hm-content-toc' ),
					'attr'        => 'title',
					'type'        => 'text',
					'description' => __( 'Title that appears before the Content TOC. Optional.', 'hm-content-toc' )
				),

				// Headers field
				array(
					'label'       => __( 'Header Elements', 'hm-content-toc' ),
					'attr'        => 'headers',
					'type'        => 'text',
					'placeholder' => get_toc()->get_default_headers(),
					'description' => sprintf(
						__( 'Comma separated list of HTML elements that are considered for building Content TOC. For example, default elements are: %1$s NOTE: DO NOT use %2$s to wrap an element, i.e. for example it should be simply %3$s and not %4$s. If no elements are specified, the default ones will be used instead: %1$s', 'hm-content-toc' ),
						get_toc()->get_default_headers(),
						'<>',
						'h2',
						'<h2>'
					)
				),
			)
		)
	);
}
