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

	// Comma separated list of header elements to generate TOC for
	protected $headers;

	// Array of HTML settings for markup
	protected $settings;

	// TOC shortcode counter (in case of multiple TOCs on the same page)
	protected $id_counter = 0;

	// Placeholder HTML that shortcode is substituted for
	protected $placeholder = '<div class="hm_content_toc_placeholder" style="display:none"></div>';

	/**
	 * Create Content_TOC:
	 * 1) Setup default header elements
	 * 2) Register shortcode
	 */
	protected function __construct() {

		// Register TOC shortcode
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
	 * Returns the placeholder HTML.
	 * The placeholder is used in an intermediate step - replacing shortcode,
	 * but before the TOC HTML is generated.
	 *
	 * So shortcode -> placeholder -> TOC HTML
	 *
	 * @return string Placeholder HTML
	 */
	public function get_placeholder() {
		return $this->placeholder;
	}

	/**
	 * Return a default header list - a comma separated string of header elements
	 *
	 * @return string Default header list
	 */
	public function get_default_headers() {
		return 'h2, h3, h4, h5, h6';
	}

	/**
	 * Returns plugin option as array
	 * If option does not exist, return default values
	 *
	 * @return array TOC plugin option as array of values
	 */
	public function get_toc_option() {

		// Get plugin option, specify defaults
		return get_option( 'hm_content_toc', array(
			'title'   => '',
			'headers' => $this->get_default_headers(),
		) );
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

		// Setup plugin defaults - headers, TOC HTML settings
		$this->setup_defaults();

		// Get shortcode supplied attributes or use defaults if not supplied
		$shortcode_atts = shortcode_atts( array(
			'headers' => $this->headers,
			'title'   => $this->settings['title'],
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
		 *
		 * NOTE: you can't add a filter within a function that was called by that filter because
		 * the remaining hooked functions from the first iteration of the filter will be discarded
		 * More info: https://core.trac.wordpress.org/ticket/17817
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
	 * Setup defaults: headers, TOC HTML markup settings
	 *
	 * Note: this is not run in __construct, as filters are unreachable from there
	 * in case some other plugin uses them, as we can't control the order of plugin
	 * loading
	 */
	protected function setup_defaults() {

		// Get plugin option
		$option = $this->get_toc_option();

		// Set up default header elements
		$this->headers = apply_filters( 'hm_content_toc_default_headers', $option['headers'] );

		// Set up default TOC HTML settings
		$this->settings = array(
			'wrapper_tag'     => 'div',
			'wrapper_class'   => 'hm-content-toc-wrapper',
			'list_tag'        => 'ul',
			'list_class'      => 'hm-content-toc-list',
			'list_item_tag'   => 'li',
			'list_item_class' => 'hm-content-toc-item',
			'title_tag'       => 'h3',
			'title_class'     => 'hm-content-toc-title',
			'title'           => $option['title'],
			'anchor_class'    => 'hm-content-toc-anchor',
		);

		// Allow TOC HTML settings to be changed
		$this->settings = apply_filters( 'hm_content_toc_settings', $this->settings );
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

			$list_html = sprintf(
				'<%1$s class="%2$s">%3$s</%1$s>',
				esc_attr( $this->settings['list_tag'] ),
				esc_attr( $this->settings['list_class'] ),
				wp_kses_post( $items_html )
			);
		}

		// TOC overall HTML
		$toc_html = $title_html . $list_html;
		if ( $this->settings['wrapper_tag'] ) {

			$toc_html = sprintf(
				'<%1$s class="%2$s">%3$s</%1$s>',
				esc_attr( $this->settings['wrapper_tag'] ),
				esc_attr( $this->settings['wrapper_class'] ),
				wp_kses_post( $title_html . $list_html )
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
	 * @param string $headers      Comma separated list of header elements
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
	 * Prepare/sanitise specified header elements string to be used in regex:
	 * 1) Split on commas
	 * 2) Trim each header to valid HTML element name,
	 *    i.e. starts with letter, followed by letter(s) and ends with optional digit
	 * 3) Remove empty elements
	 * 4) Keep unique values only
	 * 5) Escape regex special chars in headers with preg_quote
	 *
	 * @param string $headers Comma separated list of header elements to match for TOC generation
	 *
	 * @return array          Header elements to be matched in content to generate TOC
	 */
	public function prepare_headers( $headers ) {

		// 1) Split string by commas
		$headers_arr = explode( ',', $headers );

		// 2) Trim each element to valid HTML element name,
		// so everything after valid name is disregarded
		// i.e. starts with 1 or more letters, followed by 1 optional digit
		$headers_arr = array_map( function ( $header ) {

			// Trim from white spaces
			$header = trim( $header );

			// Match the valid element name as far as possible
			if ( 1 === preg_match( '#^[a-zA-Z]+\d{0,1}#', $header, $matches ) ) {
				$header = $matches[0];
			}
			// Match not found - element name is invalid
			else {
				$header = '';
			}

			return $header;
		}, $headers_arr );

		// 3) Remove empty elements
		$headers_arr = array_filter( $headers_arr, 'strlen' );

		// 4) Unique values
		$headers_arr = array_unique( $headers_arr );

		// 5) Escape regex special chars - just in case
		$headers_arr = array_map( 'preg_quote', $headers_arr );

		return array_values( $headers_arr );
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

		return sprintf(
			'<%1$s class="%2$s">%3$s</%1$s>',
			esc_attr( $this->settings['title_tag'] ),
			esc_attr( $this->settings['title_class'] ),
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

			// Strip tags from the TOC item text
			$item_text = strip_tags( $toc_item_match[1] );

			// Add filter to allow custom TOC item markup
			$items_html .= apply_filters(
				'hm_content_toc_single_item',
				sprintf(
					'<%1$s class="%2$s"><a href="#heading-%3$d">%4$s</a></%1$s>',
					esc_attr( $this->settings['list_item_tag'] ),
					esc_attr( $this->settings['list_item_class'] . '-' . $toc_item_match[2] ),
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
	 * Inserts anchors into the supplied post content, just before each of
	 * header that was matched and supplied as array of TOC matches
	 *
	 * @param string $post_content      HTML content of the current post
	 * @param array  $toc_items_matches Array of TOC matches, i.e. specified headers
	 *                                  that were matched in the content
	 *
	 * @return mixed                    Modified post content HTML with inserted anchors
	 *                                  before matched headers
	 */
	protected function insert_anchors( $post_content, $toc_items_matches ) {

		// Anchors to be inserted into the post content
		$anchors = array();

		// Insert anchors before the matched header elements in the post content
		foreach ( $toc_items_matches as $key => $match_set ) {

			// Counter of matched headers, starting at 1
			// This will be the name of an anchor, so should be human readable
			$key_current = $key + 1;

			// Store all anchors so we can ensure we don't insert multiple anchors for duplicate headers
			$anchors[] = sprintf(
				'<a name="heading-%s" class="%s"></a>',
				esc_attr( $key_current ),
				esc_attr( $this->settings['anchor_class'] )
			);

			// Regex escape stored anchors
			$anchors_regex_ready = array();
			foreach ( $anchors as $anchor ) {
				$anchors_regex_ready[] = preg_quote( $anchor, '/' );
			}

			// Add anchor just before the matched header element
			// Use negative lookbehind to ensure we don't insert multiple anchors to a single header
			$post_content = preg_replace(
				'/(?<!' . implode( '|', $anchors_regex_ready ) . ')' . preg_quote( $match_set[0], '/' ) . '/',
				end( $anchors ) . $match_set[0], // Insert latest/currently considered anchor before the matched header in the post content
				$post_content,
				1 // Maximum replacements (replace the first match only)
			);
		}

		return $post_content;
	}

	/**
	 * Add shortcake UI integration for 'hm_content_toc' shortcode
	 */
	public function register_shortcake_ui() {

		shortcode_ui_register_for_shortcode(
			'hm_content_toc',
			array(
				/* translators: TOC is table of contents */
				'label'         => __( 'HM Content TOC', 'hm-content-toc' ),
				'listItemImage' => 'dashicons-menu',
				// Available shortcode attributes and default values
				'attrs'         => array(

					// Title field
					array(
						'label'       => __( 'Title', 'hm-content-toc' ),
						'attr'        => 'title',
						'type'        => 'text',
						'description' => __( 'The title is added before generated TOC links. Optional.', 'hm-content-toc' ),
					),

					// Headers field
					array(
						'label'       => __( 'Header Elements', 'hm-content-toc' ),
						'attr'        => 'headers',
						'type'        => 'text',
						'placeholder' => $this->headers,
						'description' => sprintf(
							__( 'Comma separated list of HTML element names used to generate the TOC. For example, default elements are: %1$s. NOTE: use %2$s, not %3$s.', 'hm-content-toc' ),
							$this->get_default_headers(),
							'h2',
							'<h2>'
						),
					),
				),
			)
		);
	}

}
