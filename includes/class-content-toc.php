<?php
/**
 * Main class of the plugin with logic to generate content TOC links
 * and anchors in the content just before the matched headers.
 */
namespace HM\Content_TOC;

class TOC {

	// Comma separated list of header elements to generate TOC for
	protected $headers;

	/**
	 * Create Content_TOC:
	 * 1) Setup default header elements
	 * 2) Register shortcode
	 */
	public function __construct() {

		// Set up default header elements
		$this->headers = apply_filters( 'hm_content_toc_default_headers', 'h2, h3, h4, h5, h6' );

		// Register shortcode
		add_shortcode( 'hm_content_toc', array( $this, 'shortcode' ) );
	}

	/**
	 * Register shortcode to generate TOC for specified header elements.
	 *
	 * Shortcode attributes:
	 * 1) headers - comma separated list of header elements
	 * 2) title   - title before the TOC list
	 *
	 * @param $atts         Shortcode attributes
	 * @param null $content Shortcode content
	 *
	 * @return string       HTML markup of the TOC
	 */
	public function shortcode( $atts, $content = null ) {

		$atts = shortcode_atts( array(
			'headers' => $this->headers,
			'title'   => ''
			), $atts, 'hm_content_toc' );

		// Generate TOC from the content
		$items    = $this->generate_content_toc( $atts['headers'] );
		$toc_html = '';

		if ( $items ) {

			// Title HTML
			if ( $atts['title'] ) {

				$toc_html .= apply_filters(
					'hm_content_toc_title',
					'<h3 id="content-toc-title">' . esc_html( $atts['title'] ) . '</h3>',
					$atts['title']
				);
			}

			// TOC list items HTML
			$toc_list = '';
			foreach ( $items as $key => $item ) {

				// Counter of items, starting at 1
				$key_current = $key + 1;

				// Add filter to allow custom TOC item markup
				$toc_list .= apply_filters(
					'hm_content_toc_single_item',
					sprintf(
						'<li><a href="#heading-%d">%s</a></li>',
						esc_attr( $key_current ),
						esc_html( $item[1] )
					),
					$key_current,
					$item[1],
					$item
				);
			}

			$toc_html .= apply_filters(
				'hm_content_toc_list',
				'<ul id="content-toc">' . $toc_list . '</ul>',
				$toc_list
			);
		}

		return $toc_html;
	}

	/**
	 * Generate TOC and add the_content filter to add anchors before
	 * the matched headers
	 *
	 * The filter is self removing to make sure it only runs once,
	 * when there is a TOC shortcode in the content
	 *
	 * @param $headers Comma separated list of header elements
	 *
	 * @return array   Regex matches of specified header elements
	 *                 from the current content
	 */
	public function generate_content_toc( $headers ) {

		// Sanitize header list & get array
		$headers = $this->sanitize_headers( $headers );

		// Get current post's content
		$content = get_the_content();

		if ( empty( $content ) || empty( $headers ) ) {
			return array();
		}

		// Construct regex to find specified headers
		$header_elements = implode( "|", $headers );
		$regex = "/<(?:$header_elements).*?>(.*?)<\/($header_elements)>/i";

		// Find/match header elements in the content
		preg_match_all( $regex, $content, $matches, PREG_SET_ORDER );

		// Stop if headers haven't been found/matched in content
		if ( ! $matches ) {
			return array();
		}

		// Add anchors before matched headers via filter
		// Run this filter once only when executing the TOC shortcode
		add_filter( 'the_content', $func = function ( $content ) use ( &$func, $matches ) {

			// Self remove the filter to make sure it only runs once
			// on current content if the shortcode is present
			remove_filter( 'the_content', $func, 100 );

			// Add anchors before the matched header elements in the content
			foreach ( $matches as $key => $match_set ) {

				// Counter of matched headers, starting at 1
				$key_current = $key + 1;

				// Add anchor just before the matched header element
				// Add filter to allow for custom anchor markup
				$content = preg_replace(
					'/' . preg_quote( $match_set[0], '/' ) . '/',
					apply_filters(
						'hm_content_toc_anchor',
						'<a name="heading-' . esc_attr( $key_current ) . '" class="toc-anchor"></a>',
						$key_current,
						$match_set
					) . $match_set[0],
					$content
				);
			}

			return $content;
		}, 100 );

		return $matches;
	}

	/**
	 * Sanitize specified header elements string:
	 * 1) Split on commas
	 * 2) Trim each element to first space, so everything after first space is disregarded
	 * 3) Remove empty elements
	 * 4) Keep unique values only
	 *
	 * @param $headers Comma separated list of header elements to match for TOC generation
	 *
	 * @return array   Header elements to be matched in content to generate TOC
	 */
	private function sanitize_headers( $headers ) {

		// 1) Split string by commas
		$headers_arr = explode( ',', $headers );

		// 2) Trim each element to first space, so everything after first space is disregarded
		$headers_arr = array_map( function ( $header ) {
			$arr_by_space = explode( ' ', trim( $header ) );
			return array_shift( $arr_by_space );
		}, $headers_arr );

		// 3) Remove empty elements
		$headers_arr = array_filter( $headers_arr, 'strlen' );

		// 3) Unique values
		$headers_arr = array_unique( $headers_arr );

		return $headers_arr;
	}

}
