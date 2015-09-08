<?php

/**
 * Class Test_Flat_TOC php unit test suit for testing HM content TOC plugin functionality
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
	 * i.e. if there are multiple [hm_content_toc] shortcodes
	 * only the first one is implemented.
	 *
	 * We're counting the TOC elements <div class="hm-content-toc-wrapper">
	 * by looking at the class name
	 */
	public function test_toc_shortcode_first_one_only() {

		// Post content with 2 TOC shortcodes
		$post_content = '[hm_content_toc title="The TOC 1" headers="h2, h3, h4"]' .
		                $this->post_content_no_toc_shortcode .
		                '[hm_content_toc title="The TOC 2" headers="h3"]';

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
			'[hm_content_toc title="The TOC 1" headers="h2, h3, h4"]' .
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
		update_option( 'hm_content_toc', array(
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
			'[hm_content_toc]' . $post_content_no_toc_shortcode
		);

		$p_shortcode_with_attrs = $this->get_processed_post_content(
			'[hm_content_toc title="" headers="h2"]' . $post_content_no_toc_shortcode
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
