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

class TOC {

	// Comma separated list of header elements to generate TOC for
	protected $headers;

	/**
	 * Create Content_TOC:
	 * 1) Setup default header elements
	 * 2) Register shortcode
	 */
	protected function __construct() {

		// Set up default header elements
		$this->headers = apply_filters( 'hm_content_toc_default_headers', 'h2, h3, h4, h5, h6' );

		// Register shortcode
		add_shortcode( 'hm_content_toc', array( $this, 'shortcode' ) );

		// Shortcake UI plugin integration (Source: https://github.com/fusioneng/Shortcake)
		if ( function_exists( 'shortcode_ui_register_for_shortcode' ) ) {
			add_action( 'init', array( $this, 'register_shortcake_ui' ) );
		}
		// Display admin notice about Shortcake UI plugin
		else {
			add_action( 'admin_notices', array( $this, 'promote_shortcake_ui' ) );
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

		// Prepare headers for regex & get them in array
		$headers = $this->prepare_headers( $headers );

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
	protected function prepare_headers( $headers ) {

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

	/**
	 * Add admin notice to promote Shortcake UI plugin if it isn't active
	 */
	public function promote_shortcake_ui() {

		// Allow to disable admin notice
		$is_display = apply_filters( 'hm_content_toc_display_shortcake_admin_notice', true );
		if ( ! $is_display ) {
			return;
		}

		?>
		<div class="notice">
			<p>
				<?php
				$shortcake_url = 'https://wordpress.org/plugins/shortcode-ui/';
				printf(
					esc_html__( 'HM Content TOC plugin supports integration with Shortcake UI plugin. Read about Shortcake UI plugin from WordPress plugin directory: %s', 'hm-content-toc' ),
					'<a href="' . $shortcake_url . '" target="_blank">' . $shortcake_url . '</a>'
				);
				?>
			</p>
		</div>
		<?php
	}

}
