<?php
/**
 * Main class of the plugin with logic to generate content TOC links
 * and anchors in the content just before the matched headers.
 *
 * Features:
 * 1) 'hm_content_toc' shortcode
 * 2) Integration with Shortcake UI plugin: https://github.com/fusioneng/Shortcake
 */
namespace HM\Content_TOC;

// Abort - if this file is accessed directly and not via WP
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TOC {

	// TOC shortcode counter (in case of multiple TOCs on the same page)
	protected $id_counter;

	// Comma separated list of header elements to generate TOC for
	protected $headers;

	// Array of HTML settings for markup
	protected $settings;

	// Placeholder HTML that shortcode is substituted for
	protected $placeholder = '<div class="hm_content_toc_placeholder" style="display:none"></div>';

	/**
	 * Create Content_TOC:
	 * 1) Setup default header elements
	 * 2) Register shortcode
	 */
	protected function __construct() {

		// TOC shortcode counter
		$this->id_counter = 0;

		// Set up default header elements
		$this->headers = apply_filters( 'hm_content_toc_default_headers', 'h2, h3, h4, h5, h6' );

		// Set up default HTML settings
		$this->settings = array(
			'wrapper_tag'     => 'div',
			'wrapper_class'   => 'hm-content-toc-wrapper',
			'list_tag'        => 'ul',
			'list_class'      => 'hm-content-toc-list',
			'list_item_tag'   => 'li',
			'list_item_class' => 'hm-content-toc-item',
			'title_tag'       => 'h3',
			'title_class'     => 'hm-content-toc-title',
			'title'           => '',
			'anchor_class'    => 'hm-content-toc-anchor'
		);

		$this->settings = apply_filters( 'hm_content_toc_settings', $this->settings );

		// Register shortcode
		add_shortcode( 'hm_content_toc', array( $this, 'shortcode' ) );

		// Shortcake UI plugin integration (Source: https://github.com/fusioneng/Shortcake)
		if ( function_exists( 'shortcode_ui_register_for_shortcode' ) ) {
			add_action( 'init', array( $this, 'register_shortcake_ui' ) );
		}
	}

	/**
	 * Make class a singleton, as we don't need more than
	 * one instance of it
	 *
	 * @return TOC True single instance of the class
	 */
	public static function get_instance() {

		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new static;
		}

		return $instance;
	}

	/**
	 * Return a default header list - a comma separated string of header elements
	 *
	 * @return string Default header list
	 */
	public function get_default_headers() {
		return $this->headers;
	}

	/**
	 * Register shortcode to generate TOC for specified header elements.
	 *
	 * Shortcode attributes:
	 * 1) headers - comma separated list of header elements
	 * 2) title   - title before the TOC list
	 *
	 * @param $shortcode_atts Shortcode attributes
	 * @param $post_content   Shortcode content
	 *
	 * @return string         HTML markup of the TOC
	 */
	public function shortcode( $shortcode_atts, $shortcode_content = null ) {

		$shortcode_atts = shortcode_atts( array(
			'headers' => $this->headers,
			'title'   => $this->settings['title']
		), $shortcode_atts, 'hm_content_toc' );

		// Stop - if subsequent TOC is being processed (not 1st one). Only process the first TOC shortcode
		if (  ++$this->id_counter > 1 ) {
			return '';
		}

		/**
		 * Add `the_content` filter at 12, because `do_shortcode` is run at 11
		 * and it means that we don't have any access to the posts content within
		 * the shortcode callback, i.e. this function
		 *
		 * So:
		 * 1) Shortcode is replaced with placeholder HTML
		 * 2) `the_content` filter is added at priority 12, so that we can access post content
		 *    after the shortcode has been processed/replaced
		 * 3) The added filter is self removed, so it only runs on content that contains the shortcode
		 */
		add_filter( 'the_content', $func = function( $post_content ) use ( $shortcode_atts, &$func ) {

			// Self remove just added filter, so it only runs whenever there is specified shortcode
			remove_filter( 'the_content', $func, 12 );

			// Process post content - insert TOC and anchors before headers
			return TOC::get_instance()->filter_content( $post_content, $shortcode_atts );

		}, 12 );

		/**
		 * Shortcode is substituted for HTML placeholder.
		 * This is done, so that we can call `the_content` filter within
		 * this shortcode function and self remove that filter.
		 *
		 * Currently this approach is buggy in WP core ref: https://core.trac.wordpress.org/ticket/17817
		 * hence it requires a workaround.
		 */
		return $this->placeholder;
	}

	/**
	 * Create string ' class=""' with specified class and suffix,
	 * escaped and ready to use in HTML
	 *
	 * @param string $class  The class string
	 * @param string $suffix Suffix, additional class string
	 *
	 * @return string        String ' class="{$class}-{$suffix}"' escaped and ready to use in HTML
	 *                       If $class is empty/not specified, return original value
	 */
	protected function tag_class( $class, $suffix = '' ) {

		if ( $class ) {

			// Append suffix
			if ( $suffix ) {
				$class .= '-' . $suffix;
			}

			$class = sprintf( ' class="%s"', esc_attr( $class ) );
		}

		return $class;
	}

	/**
	 * Callback for applying filter to post content
	 *
	 * Replaces content TOC placeholder with content TOC HTML and inserts anchor tags at headings
	 *
	 * @param string $post_content   The content HTML string, comming from `the_content` filter
	 * @param array  $shortcode_atts The shortcode attributes, coming from `hm_content_toc` shortcode
	 *
	 * @return string
	 */
	public function filter_content( $post_content, $shortcode_atts ) {

		/**
		 * Reset the counter to support archive pages (multiple posts display on the same page).
		 * By this point all the shortcodes for the current post have already been parsed
		 * by `the_content` filter on priority 11
		 */
		$this->id_counter = 0;

		// Generate TOC from the post content
		$toc_items_matches = $this->get_content_toc_headers( $shortcode_atts['headers'], $post_content );
		$toc_html          = '';

		// No matches for specified headers in the post content
		// Remove the shortcode HTML placeholder from the post content
		if ( ! $toc_items_matches ) {
			return str_replace( $this->placeholder, '', $post_content );
		}

		// TOC title HTML
		$title_html = $this->get_toc_title_html( $shortcode_atts );

		// TOC items HTML
		$items_html = $this->get_toc_items_html( $toc_items_matches );

		// TOC list HTML
		$list_html = $items_html;
		if ( $this->settings['list_tag'] ) {

			$list_html = sprintf( '<%1$s%2$s>%3$s</%1$s>',
				esc_attr( $this->settings['list_tag'] ),
				$this->tag_class( $this->settings['list_class'] ),
				$items_html
			);
		}

		// TOC overall HTML
		$toc_html = $title_html . $list_html;
		if ( $this->settings['wrapper_tag'] ) {

			$toc_html = sprintf( '<%1$s%2$s>%3$s</%1$s>',
				esc_attr( $this->settings['wrapper_tag'] ),
				$this->tag_class( $this->settings['wrapper_class'] ),
				$title_html . $list_html
			);
		}

		// Replace shortcode HTML placeholder with generated TOC HTML
		$post_content = str_replace( $this->placeholder, $toc_html, $post_content );

		// Insert anchors before the corresponding headers in the post content
		$post_content = $this->insert_anchors( $post_content, $toc_items_matches );

		return $post_content;
	}

	/**
	 * Find and return an array of HTML headers for a given set of accepted header elements
	 * and a given string of HTML content
	 *
	 * @param array  $headers      Comma separated list of header elements
	 * @param string $post_content A HTML content string
	 *
	 * @return array               Regex matches of specified header elements
	 *                             from the current content
	 */
	public function get_content_toc_headers( $headers, $post_content ) {

		// Stop - if content is empty
		if ( empty( $post_content ) ) {
			return array();
		}

		// Prepare headers for regex & get them in array
		$headers = $this->prepare_headers( $headers );

		// Stop - if no header elements are specified to be matched
		if ( empty( $headers ) ) {
			return array();
		}

		// Construct regex to find specified headers
		$header_elements = implode( "|", $headers );
		$regex = "/<(?:$header_elements).*?>(.*?)<\/($header_elements)>/i";

		// Find/match header elements in the supplied post content
		preg_match_all( $regex, $post_content, $matches, PREG_SET_ORDER );

		// Stop if headers haven't been found/matched in content
		if ( ! $matches ) {
			return array();
		}

		return $matches;
	}

	/**
	 * Prepare specified header elements string to be used in regex:
	 * 1) Split on commas
	 * 2) Trim each element to first space, so everything after first space is disregarded
	 * 3) Remove empty elements
	 * 4) Keep unique values only
	 * 5) Escape regex special chars in headers with preg_quote
	 *
	 * @param $headers Comma separated list of header elements to match for TOC generation
	 *
	 * @return array   Header elements to be matched in content to generate TOC
	 */
	public function prepare_headers( $headers ) {

		// 1) Split string by commas
		$headers_arr = explode( ',', $headers );

		// 2) Trim each element to first space, so everything after first space is disregarded
		$headers_arr = array_map( function ( $header ) {
			$arr_by_space = explode( ' ', trim( $header ) );
			return array_shift( $arr_by_space );
		}, $headers_arr );

		// 3) Remove empty elements
		$headers_arr = array_filter( $headers_arr, 'strlen' );

		// 4) Unique values
		$headers_arr = array_unique( $headers_arr );

		// 5) Escape regex special chars
		$headers_arr = array_map( 'preg_quote', $headers_arr );

		return $headers_arr;
	}

	/**
	 * Gets the HTML for the content TOC title
	 *
	 * @param $shortcode_atts Array of shortcode attributes
	 *
	 * @return string         Output HTML for shortcode title
	 */
	protected function get_toc_title_html( $shortcode_atts ) {

		if ( ! $shortcode_atts['title'] || ! $this->settings['title_tag'] ) {
			return '';
		}

		return sprintf( '<%1$s%2$s>%3$s</%1$s>',
			esc_attr( $this->settings['title_tag'] ),
			$this->tag_class( $this->settings['title_class'] ),
			esc_html( $shortcode_atts['title'] )
		);
	}

	/**
	 * Gets the HTML for the content TOC items
	 *
	 * @param array $toc_items_matches Array of specified headers that were matched in the content
	 *
	 * @return string                  Output HTML for content TOC items
	 */
	protected function get_toc_items_html( $toc_items_matches ) {

		$items_html = '';

		foreach ( $toc_items_matches as $key => $toc_item_match ) {

			// Counter of items, starting at 1
			$key_current = $key + 1;

			// Stripped item text
			$item_text = strip_tags( $toc_item_match[1] );

			// Add filter to allow custom TOC item markup
			$items_html .= apply_filters(
				'hm_content_toc_single_item',
				sprintf(
					'<%1$s%2$s><a href="#heading-%3$d">%4$s</a></%1$s>',
					esc_attr( $this->settings['list_item_tag'] ),
					$this->tag_class( $this->settings['list_item_class'], $toc_item_match[2] ),
					esc_attr( $key_current ),
					esc_html( $item_text )
				),
				$key_current,
				$item_text,
				$toc_item_match
			);
		}

		return $items_html;
	}

	/**
	 * Inserts supplied array of anchors into the supplied HTML content string
	 *
	 * @param string $post_content      HTML content of the current post
	 * @param array  $toc_items_matches Array of TOC matches, i.e. specified headers
	 *                                  that were matched in the content
	 *
	 * @return mixed                    Modified post content HTML with inserted anchors
	 *                                  before matched headers
	 */
	protected function insert_anchors( $post_content, $toc_items_matches ) {

		// Add anchors before the matched header elements in the content
		foreach ( $toc_items_matches as $key => $match_set ) {

			// Counter of matched headers, starting at 1
			$key_current = $key + 1;

			// Add anchor just before the matched header element
			$post_content = preg_replace(
				'/' . preg_quote( $match_set[0], '/' ) . '/',
				sprintf(
					'<a name="heading-%s"%s></a>%s',
					esc_attr( $key_current ),
					$this->tag_class( $this->settings['anchor_class'] ),
					$match_set[0]
				),
				$post_content
			);
		}

		return $post_content;
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
						'placeholder' => $this->headers,
						'description' => sprintf(
							__( 'Comma separated list of HTML elements that are considered for building Content TOC. For example, default elements are: %1$s NOTE: DO NOT use %2$s to wrap an element, i.e. for example it should be simply %3$s and not %4$s. If no elements are specified, the default ones will be used instead: %1$s', 'hm-content-toc' ),
							$this->headers,
							'<>',
							'h2',
							'<h2>'
						)
					),
				)
			)
		);
	}

}
