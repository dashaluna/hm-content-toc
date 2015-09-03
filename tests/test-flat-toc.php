<?php

/**
 * Class Test_Flat_TOC php unit test suit for testing HM content TOC plugin functionality
 * for flat (non-hierarchical) TOC generated for specified headers in the content.
 */

class Test_Flat_TOC extends WP_UnitTestCase {

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
		$post_content = "
		[hm_content_toc title='The TOC 1' headers='h2, h3']
		<h2>Header's 2</h2>
		Some text here. Some text here. Some text here.
		<h3>Header= 3</h3>
		Some text here. Some text here. Some text here.
		<h3>Header/\'& 3</h3>
		Some text here. Some text here. Some text here.
		[hm_content_toc title='The TOC 2' headers='h3']";

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
	 * Setup a test post with specified content.
	 * Return that posts's content after all processing and filters
	 * as if it was displayed on a browser page.
	 *
	 * @param $post_content Post content to add to the post
	 *
	 * @return string       Processed post content (after all the filters)
	 *                      as if being displayed on a browser page
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
