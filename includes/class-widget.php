<?php
/**
 * HM Content TOC Widget
 */

namespace HM\Content_TOC;
use WP_Widget;

// Abort - if this file is accessed directly and not via WP
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Widget extends WP_Widget {

	// Main TOC singleton object
	protected $toc;

	// Default values for the widget
	protected $widget_defaults;
	
	/**
	 * Sets up the widget with custom description
	 * Instantiates the widget
	 */
	public function __construct() {

		// Construct the widget
		parent::__construct(
			'hm_content_toc',
			__( 'HM Content TOC', 'hm-content-toc' ),
			array(
				'description' => __( 'Displays TOC for specified headers of the current page or post.', 'hm-content-toc' )
			)
		);

		// Dependency - get main TOC singleton object
		$this->toc = get_toc();

		// Setup default widget values
		$this->set_widget_defaults();
	}

	/**
	 * Setup widget defaults
	 */
	protected function set_widget_defaults() {

		$this->widget_defaults = array(
			'title'   => '',
			'headers' => $this->toc->get_default_headers()
		);
	}

	/**
	 * Display widget on the non-admin site
	 *
	 * @param array $args     The array of form elements
	 * @param array $instance Current instance of the widget
	 */
	public function widget( $args, $instance ) {

		echo $this->toc->shortcode( $instance );
	}

	/**
	 * Update widgets options specified in Admin Widgets page
	 *
	 * @param array $new_instance New widget instance with values to be saved
	 * @param array $old_instance Previous widget instance with values before the update
	 *
	 * @return array|void         A widget instance with new saved values
	 */
	public function update( $new_instance, $old_instance ) {

		// Overwrite old values with new only if they pass validation
		$instance = $old_instance;

		// Title - trim and remove multiple spaces
		$instance['title'] = trim( preg_replace( '/\s+/', ' ', $new_instance['title'] ) );

		// Headers - prepare headers string
		// TODO: Should I strip regex special chars here?
		$instance['headers'] = implode( ", ", $this->toc->prepare_headers( $new_instance['headers'] ) );

		return $instance;
	}

	/**
	 * Displays widget form in the admin site
	 *
	 * @param array $instance Current widget instance (array of widget's keys and values)
	 *
	 * @return void           Outputs form HTML
	 */
	public function form( $instance ) {

		// Use widget default values if nothing is specified
		$instance = wp_parse_args( (array) $instance, $this->widget_defaults );

		// Display widget's admin form
		ob_start(); ?>

		<!-- Title text -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title:', 'hm-content-toc' ); ?>
			</label>
			<input type="text"
			       class="widefat"
			       id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
			       value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>

		<!-- Headers list -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'headers' ) ); ?>">
				<?php esc_html_e( 'Headers (comma separated list of header elements):', 'hm-content-toc' ); ?>
			</label>
			<input type="text"
			       class="widefat"
			       id="<?php echo esc_attr( $this->get_field_id( 'headers' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'headers' ) ); ?>"
			       value="<?php echo esc_attr( $instance['headers'] ); ?>" />
			<span>
				<?php esc_html_e( 'NOTE: specified elements will be sanitized and only allowed ones will be considered', 'hm-content-toc' ); ?>
			</span>
		</p>

		<?php
		ob_end_flush();
	}

}
