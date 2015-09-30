<?php
/**
 * Admin class for HM TOC plugin.
 * Adds admin settings for HM TOC plugin to specify default TOC title and header elements list.
 */
namespace HM\Content_TOC;

// Abort - if this file is accessed directly and not via WP
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin {

	// Admin page slug for TOC plugin settings
	protected $page_slug = 'hm-toc-settings';

	// Option slug for TOC plugin settings
	protected $option_slug = 'hm_content_toc';

	// Admin page title
	protected $page_title;

	// Plugin basename, i.e. hm-content-toc/hm-content-toc.php
	protected $plugin_basename;

	// Link to github docs
	protected $github_doc_url = 'https://github.com/dashaluna/hm-content-toc#readme';

	/**
	 * Creates admin object and implements registered actions:
	 * 1) adds option submenu page to WP Settings page
	 * 2) sets up plugin settings and displays admin page content
	 *
	 * @param string $plugin_base_file The absolute full path and filename
	 *                                 of the main plugin file
	 */
	protected function __construct( $plugin_base_file ) {

		// Setup properties used throughout this class
		$this->plugin_basename = plugin_basename( $plugin_base_file );

		// Load plugin's textdomain (i.e. translations)
		load_plugin_textdomain( 'hm-content-toc', false, dirname( $this->plugin_basename ) . '/languages/' );

		// Setup strings used extensively throughout the class
		/* translators: TOC is table of contents */
		$this->page_title = __( 'HM Content TOC Settings', 'hm-content-toc' );

		// Add admin submenu page to Settings
		add_action( 'admin_menu', array( $this, 'add_plugin_option_menu_page' ) );

		// Setup plugin settings and display admin page content
		add_action( 'admin_init', array( $this, 'setup_plugin_option_settings' ) );

		// Add Settings link to plugin links on main Plugin page
		add_filter( 'plugin_action_links_' . $this->plugin_basename, array( $this, 'add_action_links' ) );
	}

	/**
	 * Make class a singleton, as we don't need more than
	 * one instance of it.
	 *
	 * NB: Parameter is optional here, but not in the __construct(),
	 * because the first call to the Admin::get_instance() must have
	 * a param to setup the static instance. Any other subsequent calls to
	 * Admin::get_instance() won't need a param and will return the
	 * previously setup static $instance.
	 *
	 * @param string $plugin_base_file Optional. The absolute full path and filename
	 *                                 of the main plugin file
	 *
	 * @return Admin True single instance of the class
	 */
	public static function get_instance( $plugin_base_file = '' ) {

		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new static( $plugin_base_file );
		}

		return $instance;
	}

	/**
	 * Adds option submenu page to WP Settings page
	 * Only users with capability `manage_options` will see it
	 */
	public function add_plugin_option_menu_page() {

		add_options_page(
			$this->page_title,
			$this->page_title,
			'manage_options',
			$this->page_slug,
			array( $this, 'display_settings_page' )
		);
	}

	/**
	 * Setup plugin admin:
	 * 1) Register plugin option
	 * 2) Register settings section and fields
	 */
	public function setup_plugin_option_settings() {

		// Register settings for plugin option
		register_setting(
			$this->option_slug,
			$this->option_slug,
			array( $this, 'option_sanitise' )
		);

		// Add settings section - anonymous, so just we can add fields to it
		add_settings_section(
			'hm-toc-section',
			'',
			'__return_empty_string',
			$this->page_slug
		);

		// Plugin input setting fields
		$input_fields = array(
			'title'   => array(
				'name' => __( 'Title', 'hm-content-toc' ),
				/* translators: TOC is table of contents */
				'desc' => __( 'The title is added before generated TOC links. Optional.', 'hm-content-toc' ),
			),
			'headers' => array(
				'name' => __( 'Header Elements', 'hm-content-toc' ),
				'desc' => sprintf(
					/* translators: TOC is table of contents. 1: The list of default header elements, i.e. h2, h3, h4, h5, h6 2: example how to correctly specify header element as string without <> brackets, i.e. h2 3: example how NOT to specify header element as string with <> brackets, i.e. <h2> */
					__( 'Comma separated list of HTML element names used to generate the TOC. For example, default elements are: %1$s. NOTE: use %2$s, not %3$s.', 'hm-content-toc' ),
					TOC::get_instance()->get_default_headers(),
					'<code>h2</code>',
					'<code>&lt;h2&gt;</code>'
				),
			),
		);

		// Display input fields
		foreach ( $input_fields as $field => $labels ) {

			add_settings_field(
				"hm-toc-{$field}",
				$labels['name'],
				array( $this, 'display_input_field' ),
				$this->page_slug,
				'hm-toc-section',
				array(
					'label_for' => "hm-toc-{$field}",
					'field'     => $field,
					'desc'      => $labels['desc'],
				)
			);
		}
	}

	/**
	 * Display the content of the plugin admin page
	 */
	public function display_settings_page() {
	?>
		<div class="wrap">

			<h2><?php echo esc_html( $this->page_title ); ?></h2>
			<p>
				<?php
				// Register text for translation, allow only HTML element `a` with `href, target` attribute
				/* translators: TOC is table of contents. 1: The link to plugin's documentation on github website. */
				echo wp_kses(
					sprintf(
						__( 'Specify default settings for HM Content TOC plugin. For more information and usage <a href="%1$s" target="_blank">see documentation on github website</a> (in English).', 'hm-content-toc' ),
						esc_url( $this->github_doc_url )
					),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
						),
					)
				);
				?>
			</p>

			<form method="post" action="options.php">

				<?php settings_fields( $this->option_slug ); ?>
				<?php do_settings_sections( $this->page_slug ); ?>
				<?php submit_button(); ?>

			</form>
		</div>

	<?php
	}

	/**
	 * Display input field HTML for a setting
	 *
	 * @param $args Array of extra info for a setting field
	 */
	public function display_input_field( $args ) {

		// Stop - if field slug hasn't been specified
		if ( ! isset( $args['field'] ) ) {
			return;
		}

		// Get plugin option and value per setting field
		$option      = TOC::get_instance()->get_toc_option();
		$field_value = isset( $option[ $args['field'] ] ) ? $option[ $args['field'] ] : '';

		// Display input field
		printf(
			'<input type="text" id="hm-toc-%1$s" name="%2$s[%1$s]" class="regular-text" value="%3$s" />',
			esc_attr( $args['field'] ),
			esc_attr( $this->option_slug ),
			esc_html( $field_value )
		);

		// Display description for the field
		if ( isset( $args['desc'] ) ) {
			printf(
				'<p class="description">%s</p>',
				wp_kses_post( $args['desc'] )
			);
		}
	}

	/**
	 * Sanitise settings values before saving them
	 *
	 * @param $option_arr Option value being saved, in this case
	 *                    an array of setting fields as we have
	 *                    a single option for plugin `hm_content_toc`
	 *
	 * @return array      Sanitised option value - array of sanitised values
	 *                    for each setting field
	 */
	public function option_sanitise( $option_arr ) {

		// Sanitise title field
		if ( isset( $option_arr['title'] ) ) {
			$option_arr['title'] = sanitize_text_field( $option_arr['title'] );
		}

		// Sanitise list of headers field
		if ( isset( $option_arr['headers'] ) ) {

			$sanitised_headers     = TOC::get_instance()->prepare_headers( $option_arr['headers'] );
			$option_arr['headers'] = join( ', ', $sanitised_headers );
		}

		return $option_arr;
	}

	/**
	 * Adds extra plugin action links that appear
	 * under the plugin summary on the main WP Plugins page
	 *
	 * @param array $links Plugin action links
	 *
	 * @return array       Array of plugin action links with added
	 *                     extra custom links
	 */
	public function add_action_links( $links ) {

		// Settings link
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=' . $this->page_slug ) ),
			/* translators: This is the quick link to plugin's settings. The link appears in the admin on the Plugin page that lists all the plugins. */
			esc_html__( 'Settings', 'hm-content-toc' )
		);

		// Documentation link
		$docs_link = sprintf(
			'<a href="%s" target="_blank">%s</a>',
			esc_url( $this->github_doc_url ),
			/* translators: This is the quick link to plugin's documentation. The link appears in the admin on the Plugin page that lists all the plugins. The documentation is localed on github website. */
			esc_html__( 'Documentation', 'hm-content-toc' )
		);

		return array_merge( $links, array( $settings_link, $docs_link ) );
	}

}
