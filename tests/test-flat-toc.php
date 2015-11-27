<?php

/**
 * Class Test_Flat_TOC php unit test suit for testing HM Table of Contents plugin functionality
 * for flat (non-hierarchical) TOC generated for specified headers in the content.
 */

class Test_Flat_TOC extends WP_UnitTestCase {

	public $post_content_no_toc_shortcode = "
		<h2>Header 2</h2>
		Some text here. Some text here. Some text here.
		<h3>Header 3</h3>
		Some text here. Some text here. Some text here.
		<h4>Header 4</h4>
		Some text here. Some text here. Some text here.";

	/**
	 * TOC instance to use in this test class
	 * @var \HM\Content_TOC\TOC
	 */
	public $toc_instance;

	/**
	 * Sets up environment before each test functions is run
	 */
	public function setUp() {

		parent::setUp();

		$this->toc_instance = \HM\Content_TOC\TOC::get_instance();
	}

	/**
	 * Test that only one shortcode is implemented,
	 * i.e. if there are multiple [toc] shortcodes
	 * only the first one is implemented.
	 *
	 * We're counting the TOC elements <div class="hm-content-toc-wrapper">
	 * by looking at the class name
	 */
	public function test_toc_shortcode_first_one_only() {

		// Post content with 2 TOC shortcodes
		$post_content = '[toc title="The TOC 1" headers="h2, h3, h4"]' .
		                $this->post_content_no_toc_shortcode .
		                '[toc title="The TOC 2" headers="h3"]';

		// Get processed post content as if being displayed on a page
		$p_show = $this->get_processed_post_content( $post_content );

		// Check there is only 1 TOC HTML element
		$this->assertSame( 1, substr_count( $p_show, 'hm-content-toc-wrapper' ) );

		// Check only the first TOC shortcode title appears
		$this->assertSame( 1, substr_count( $p_show, 'The TOC 1' ) );

		// Check the second TOC shortcode title doesn't appear
		$this->assertSame( 0, substr_count( $p_show, 'The TOC 2' ) );
	}

	/**
	 * Test if shortcodes specified headers are sanitised correctly,
	 * only valid HTML element names are kept.
	 */
	public function test_toc_shortcode_headers_sanitized() {

		// Post content with TOC shortcode
		$headers = 'h2,  h222   , h3  , h3,   h4 class="class-1 class", , h5*&^%$, £@!, div, 67p, *%span';

		// Sanitise header elements, only unique
		// valid HTML element names are kept
		$headers = $this->toc_instance->prepare_headers( $headers );

		$this->assertEquals(
			array( 'h2', 'h3', 'h4', 'h5', 'div' ),
			$headers
		);
	}

	/**
	 * Tests if post with TOC shortcode is outputting a generated TOC HTML.
	 * A post without TOC shortcode doesn't have generated TOC HTML.
	 */
	public function test_toc_shortcode_processed_and_output() {

		// Create posts with TOC shortcode and without
		$p_with_toc = $this->get_processed_post_content(
			'[toc title="The TOC 1" headers="h2, h3, h4"]' .
			$this->post_content_no_toc_shortcode
		);

		$p_no_toc = $this->get_processed_post_content(
			$this->post_content_no_toc_shortcode
		);

		// Check if generated TOC is present for content with shortcode
		$this->assertSame( 1, substr_count( $p_with_toc, 'hm-content-toc-wrapper' ) );

		// Check if generated TOC is not present for content without shortcode
		$this->assertSame( 0, substr_count( $p_no_toc, 'hm-content-toc-wrapper' ) );
		$this->assertSame( 0, substr_count( $p_no_toc, $this->toc_instance->get_placeholder() ) );
	}

	/**
	 * Tests if plugin option settings are used as defaults in case where shortcode is
	 * specified without attributes.
	 *
	 * Tests that shortcode attributes take precedence over option values when the attributes
	 * are specified.
	 */
	public function test_toc_shortcode_with_and_without_attributes_so_plugin_options_are_used_as_defaults() {

		// Create TOC plugin option
		update_option( 'toc', array(
			'title'   => 'TOC title via plugin settings',
			'headers' => 'h2, h3, h4',
		) );

		// Generic post content without shortcode
		$post_content_no_toc_shortcode = '
			<h2>Header 2</h2>
			Some text here. Some text here. Some text here.
			<h3>Header 3</h3>
			Some text here. Some text here. Some text here.
			<h4>Header 4</h4>
			Some text here. Some text here. Some text here.
			<h4>Header 4</h4>
			Some text here. Some text here. Some text here.
			<h5>Header 5</h5>
			Some text here. Some text here. Some text here.';

		$p_shortcode_no_attrs = $this->get_processed_post_content(
			'[toc]' . $post_content_no_toc_shortcode
		);

		$p_shortcode_with_attrs = $this->get_processed_post_content(
			'[toc title="" headers="h2"]' . $post_content_no_toc_shortcode
		);

		/* Shortcode without attributes tests */
		// Check if generated TOC is present
		$this->assertSame( 1, substr_count( $p_shortcode_no_attrs, 'hm-content-toc-wrapper' ) );

		// Check the TOC has got title specified in option
		$this->assertSame( 1, substr_count( $p_shortcode_no_attrs, 'TOC title via plugin settings' ) );

		// Check the TOC links are generated for specified headers in option - overall
		$this->assertSame( 4, substr_count( $p_shortcode_no_attrs, 'hm-content-toc-item-' ) );

		// Check a link appears in TOC for each matched header
		$this->assertSame( 1, substr_count( $p_shortcode_no_attrs, 'hm-content-toc-item-h2' ) );
		$this->assertSame( 1, substr_count( $p_shortcode_no_attrs, 'hm-content-toc-item-h3' ) );
		$this->assertSame( 2, substr_count( $p_shortcode_no_attrs, 'hm-content-toc-item-h4' ) );
		$this->assertSame( 0, substr_count( $p_shortcode_no_attrs, 'hm-content-toc-item-h5' ) );

		/* Shortcode with attributes tests */
		// Check if generated TOC is present
		$this->assertSame( 1, substr_count( $p_shortcode_with_attrs, 'hm-content-toc-wrapper' ) );

		// Check the TOC title is not present (used from shortcode)
		$this->assertSame( 0, substr_count( $p_shortcode_with_attrs, 'hm-content-toc-title' ) );

		// Check the TOC links are generated for specified headers in shortcode attribute - overall
		$this->assertSame( 1, substr_count( $p_shortcode_with_attrs, 'hm-content-toc-item-' ) );

		// Check a link appears in TOC for each matched header
		$this->assertSame( 1, substr_count( $p_shortcode_with_attrs, 'hm-content-toc-item-h2' ) );
		$this->assertSame( 0, substr_count( $p_shortcode_with_attrs, 'hm-content-toc-item-h3' ) );
		$this->assertSame( 0, substr_count( $p_shortcode_with_attrs, 'hm-content-toc-item-h4' ) );
		$this->assertSame( 0, substr_count( $p_shortcode_with_attrs, 'hm-content-toc-item-h5' ) );
	}

	/**
	 * Test the TOC links/items are generated correctly:
	 * 1) for each specified header in the shortcode
	 * 2) only contains simple text, no HTML tags
	 */
	public function test_generated_toc_links_are_simple_text_no_tags_for_specified_headers() {

		$post_content = '
			[toc title="The TOC 1" headers="h2, h3, h4"]
			<h2>Header 2</h2>
			Some text here. Some text here. Some text here.
			<h3>Header 3</h3>
			Some text here. Some text here. Some text here.
			<h4>Header 4</h4>
			Some text here. Some text here. Some text here.
			<h4>Header 4</h4>
			Some text here. Some text here. Some text here.
			<h5>Header 5</h5>
			Some text here. Some text here. Some text here.
			<h2>Header 2</h2>
			Some text here. Some text here. Some text here.
			<h2>Header 2 <b>with bold text</b></h2>
			Some text here. Some text here. Some text here.
			<h3>Header 3</h3>
			Some text here. Some text here. Some text here.
			<h6>Header 6</h6>
			Some text here. Some text here. Some text here.';

		$p = $this->get_processed_post_content( $post_content );

		// Check if generated TOC HTML contains correct number of links/items
		$this->assertSame( 3, substr_count( $p, 'hm-content-toc-item-h2' ) );
		$this->assertSame( 2, substr_count( $p, 'hm-content-toc-item-h3' ) );
		$this->assertSame( 2, substr_count( $p, 'hm-content-toc-item-h4' ) );

		// Count the overall number of TOC links/items
		$this->assertSame( 7, substr_count( $p, 'hm-content-toc-item-' ) );

		// Check each TOC link/item is generated correctly, any HTML tags are stripped
		$this->assertSame( 1, substr_count( $p, '<a href="#heading-1">Header 2</a>' ) );
		$this->assertSame( 1, substr_count( $p, '<a href="#heading-2">Header 3</a>' ) );
		$this->assertSame( 1, substr_count( $p, '<a href="#heading-3">Header 4</a>' ) );
		$this->assertSame( 1, substr_count( $p, '<a href="#heading-4">Header 4</a>' ) );
		$this->assertSame( 1, substr_count( $p, '<a href="#heading-5">Header 2</a>' ) );
		$this->assertSame( 1, substr_count( $p, '<a href="#heading-6">Header 2 with bold text</a>' ) );
		$this->assertSame( 1, substr_count( $p, '<a href="#heading-7">Header 3</a>' ) );
	}

	/**
	 * Test to check that anchors are inserted into the content correctly
	 * before corresponding headers, that contain special chars (ampersand,
	 * quotes, prime, another HTML elements and so on"
	 */
	public function test_toc_special_chars_in_content_headers() {
		$post_content = '
			[toc title="The TOC 1" headers="h2, h3, h4"]
			<h2>Header\'s 2 ...</h2>
			Some text here. Some text here. Some text here.
			<h3>Header & other text 3</h3>
			Some text here. Some text here. Some text here.
			<h4>Header -- en dash 4</h4>
			Some text here. Some text here. Some text here.
			<h4>Header with prime 9\'</h4>
			Some text here. Some text here. Some text here.
			<h2>Header in quotes "hey there"</h2>
			Some text here. Some text here. Some text here.
			<h3>Header with <b>bold tag</b></h3>
			Some text here. Some text here. Some text here.';

		$p = $this->get_processed_post_content( $post_content );

		// Check if generated TOC HTML contains correct number of elements
		$this->assertSame( 2, substr_count( $p, 'hm-content-toc-item-h2' ) );
		$this->assertSame( 2, substr_count( $p, 'hm-content-toc-item-h3' ) );
		$this->assertSame( 2, substr_count( $p, 'hm-content-toc-item-h4' ) );

		// Check the number of anchors inserted into content
		// 1 before each header, so 6 in total
		$this->assertSame( 6, substr_count( $p, 'hm-content-toc-anchor' ) );

		// Check if anchors have been inserted before each headers correctly in the post content
		$this->assertSame( 1, substr_count(
			$p,
			'<a name="heading-1" class="hm-content-toc-anchor"></a><h2>Header&#8217;s 2 &#8230;</h2>'
		) );

		$this->assertSame( 1, substr_count(
			$p,
			'<a name="heading-2" class="hm-content-toc-anchor"></a><h3>Header &amp; other text 3</h3>'
		) );

		$this->assertSame( 1, substr_count(
			$p,
			'<a name="heading-3" class="hm-content-toc-anchor"></a><h4>Header &#8212; en dash 4</h4>'
		) );

		$this->assertSame( 1, substr_count(
			$p,
			'<a name="heading-4" class="hm-content-toc-anchor"></a><h4>Header with prime 9&#8242;</h4>'
		) );

		$this->assertSame( 1, substr_count(
			$p,
			'<a name="heading-5" class="hm-content-toc-anchor"></a><h2>Header in quotes &#8220;hey there&#8221;</h2>'
		) );

		$this->assertSame( 1, substr_count(
			$p,
			'<a name="heading-6" class="hm-content-toc-anchor"></a><h3>Header with <b>bold tag</b></h3>'
		) );
	}

	/**
	 * Test an anchor is inserted only once before a header
	 * in case of multiple identical headers
	 */
	public function test_anchors_inserted_once_in_content_before_header_for_multiple_identical_headers() {

		$post_content = '
			[toc title="The TOC 1" headers="h2, h3, h4"]
			<h2>Header 2</h2>
			Some text here. Some text here. Some text here.
			<h3>Header 3</h3>
			Some text here. Some text here. Some text here.
			<h4>Header 4</h4>
			Some text here. Some text here. Some text here.
			<h4>Header 4</h4>
			Some text here. Some text here. Some text here.
			<h5>Header 5</h5>
			Some text here. Some text here. Some text here.
			<h2>Header 2</h2>
			Some text here. Some text here. Some text here.
			<h6>Header 6</h6>
			Some text here. Some text here. Some text here.';

		$p = $this->get_processed_post_content( $post_content );

		// Check correct number of anchors have been inserted into post content
		$this->assertSame( 5, substr_count( $p, 'hm-content-toc-anchor' ) );


		// Check if anchors have been inserted before each headers correctly in the post content
		$this->assertSame( 1, substr_count(
			$p,
			'<a name="heading-1" class="hm-content-toc-anchor"></a><h2>Header 2</h2>'
		) );
		// All together there should be 2 refs to the same anchor - in TOC and in content
		$this->assertSame( 2, substr_count(
			$p,
			'heading-1'
		) );

		$this->assertSame( 1, substr_count(
			$p,
			'<a name="heading-2" class="hm-content-toc-anchor"></a><h3>Header 3</h3>'
		) );
		$this->assertSame( 2, substr_count(
			$p,
			'heading-2'
		) );

		$this->assertSame( 1, substr_count(
			$p,
			'<a name="heading-3" class="hm-content-toc-anchor"></a><h4>Header 4</h4>'
		) );
		$this->assertSame( 2, substr_count(
			$p,
			'heading-3'
		) );

		$this->assertSame( 1, substr_count(
			$p,
			'<a name="heading-4" class="hm-content-toc-anchor"></a><h4>Header 4</h4>'
		) );
		$this->assertSame( 2, substr_count(
			$p,
			'heading-4'
		) );

		$this->assertSame( 1, substr_count(
			$p,
			'<a name="heading-5" class="hm-content-toc-anchor"></a><h2>Header 2</h2>'
		) );
		$this->assertSame( 2, substr_count(
			$p,
			'heading-5'
		) );
	}

	/**
	 * Setup a test post with specified content.
	 * Return that posts's content after all processing and filters
	 * as if it was displayed on a browser page.
	 *
	 * @param string $post_content Post content to add to the post
	 *
	 * @return string              Processed post content (after all the filters)
	 *                             as if being displayed on a browser page
	 */
	protected function get_processed_post_content( $post_content ) {

		global $post;
		$post = $this->factory->post->create_and_get( array(
			'post_content' => $post_content,
		) );

		// Return post content as if it was displayed on a page
		setup_postdata( $post );
		ob_start();
		the_content();

		return ob_get_clean();
	}

}
