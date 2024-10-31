<?php // phpcs:ignore WordPress.Files.FileName Squiz.Commenting.FileComment.Missing

/**
 * Reading Time & Progress Bar
 *
 * @package           CodeCanel\Reading_Time_Progress_Bar
 * @author            Code Canel
 * @copyright         2023 Code Canel
 * @license           GPLv2 or later
 */

namespace COCA\Reading_Time_Progress_Bar;

/**
 * Free Plugin Launcher Class.
 */
final class Plugin {

	/**
	 * The instance of the current class.
	 *
	 * @var ?self
	 */
	private static $instance = null;  // phpcs:ignore Squiz.Commenting.VariableComment.Missing

	/**
	 * The version number.
	 *
	 * @var string
	 */
	private $version = '1.0.0';

	/**
	 * The output directory url.
	 *
	 * @var string
	 */
	private $output_path = '';

	/**
	 * The output directory path.
	 *
	 * @var string
	 */
	private $output_dir = '';

	/**
	 * Get the instance of the plugin.
	 *
	 * @return ?self
	 */
	public static function get_instance() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self();

			if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
				self::$instance->version = time();
			}

			self::$instance->output_path = plugins_url( 'build', __FILE__ );
			self::$instance->output_dir  = plugin_dir_path( __FILE__ ) . 'build';

			// Define constants.
			self::$instance->define_all_constants();

			// Load all features.
			self::$instance->register_all_hooks();
			self::$instance->load_admin_features();
		}

		return self::$instance;
	}

	/**
	 * Define require constants.
	 *
	 * @return void
	 */
	private function define_all_constants() {
		define( 'COCA_RTPB__PLUGIN_DIR', __DIR__ . DIRECTORY_SEPARATOR );
		define( 'COCA_RTPB__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
	}

	/**
	 * The hook registerer.
	 *
	 * @return void
	 */
	private function register_all_hooks() {
		// Register hooks for plugin activation.
		register_activation_hook( COCA_RTPB__PLUGIN_FILE, array( $this, 'activate' ) );

		// Register plugin links by its hooks.
		add_filter( 'plugin_action_links', array( $this, 'register_action_links' ), 10, 2 );

		// Register an admin page with its scripts and styles.
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 10, 2 );

		// Additional hooks.
		add_action( 'admin_init', array( $this, 'redirect_to_settings_page' ) );
		add_filter( 'coca_rtpb_plugin_settings_data', array( $this, 'hook_settings_data' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_dependencies' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_dependencies' ) );
		add_filter( 'the_content', array( $this, 'hook_the_content_data' ), 1000000000 );
		add_filter( 'comments_template', array( $this, 'hook_comments_template' ), 1000000000 );
	}

	/**
	 * Activate the plugin.
	 */
	public function activate() {
		// Add the plugin installation time to a database.
		if ( ! get_option( 'coca_rtpb_plugin_installed' ) ) {
			update_option( 'coca_rtpb_plugin_installed', time() );
		}

		// Add general data to the database.
		update_option( 'coca_rtpb_plugin_do-activation-redirect', true );
		update_option( 'coca_rtpb_plugin_version', $this->version );
		update_option( 'coca_rtpb_plugin-settings-data', self::get_initial_settings_data() );
	}

	/**
	 * Redirect to the plugin page.
	 *
	 * @return void
	 */
	public function redirect_to_settings_page() {
		if ( get_option( 'coca_rtpb_plugin_do-activation-redirect', false ) ) {
			delete_option( 'coca_rtpb_plugin_do-activation-redirect' );

			if ( ! isset( $_GET['activate-multi'] ) ) { // phpcs:ignore
				wp_redirect( 'admin.php?page=reading-time-progress-bar' ); // phpcs:ignore
			}
		}
	}

	/**
	 * Adds a top-level menu page.
	 *
	 * @return void
	 */
	public function register_admin_menu() {
		add_menu_page(
			esc_html__( 'Reading Time & Progress Bar', 'reading-time-progress-bar' ),
			esc_html__( 'Reading Time & Progress Bar', 'reading-time-progress-bar' ),
			'manage_options',
			'reading-time-progress-bar',
			array( $this, 'set_html__admin_panel_root' ),
			'dashicons-clock',
			80
		);
	}

	/**
	 * Register scripts and styles for the admin panel.
	 *
	 * @param string $hook_suffix The current admin page.
	 *
	 * @return void
	 */
	public function register_admin_dependencies( $hook_suffix ) {
		if ( 'toplevel_page_reading-time-progress-bar' === $hook_suffix ) {
			// The common script and styles for widgets.
			$admin_dir     = "$this->output_path/admin";
			$static_dir    = "$this->output_path/static";
			$version       = $this->version;
			$in_footer     = array( 'in_footer' => false );
			$handle_prefix = 'coca-rtpb-plugin';
			$panel_asset   = include "$this->output_dir/admin/js/panel.asset.php";
			$panel_deps    = ! empty( $panel_asset['dependencies'] ) ? $panel_asset['dependencies'] : array();

			// load required dependencies in the WP Admin area.
			if ( version_compare( get_bloginfo( 'version' ), '6.2', '<' ) ) {
				wp_dequeue_script( 'wp-i18n' );
				wp_dequeue_script( 'wp-element' );
				wp_dequeue_script( 'react-dom' );
				wp_dequeue_script( 'react' );

				wp_deregister_script( 'wp-i18n' );
				wp_deregister_script( 'wp-element' );
				wp_deregister_script( 'react' );
				wp_deregister_script( 'react-dom' );

				// Add new deps for WP Admin area.
				wp_register_script( 'react', "$static_dir/js/react.min.js", array(), $version, $in_footer );
				wp_register_script( 'react-dom', "$static_dir/js/react-dom.min.js", array(), $version, $in_footer );
				wp_register_script( 'wp-i18n', "$static_dir/js/wp-i18n.min.js", array(), $version, $in_footer );
				wp_register_script( 'wp-element', "$static_dir/js/wp-element.min.js", array( 'react-dom' ), $version, $in_footer );
			}

			// Load assets admin ui.
			wp_enqueue_script( "{$handle_prefix}_admin-panel", "$admin_dir/js/panel.js", $panel_deps, $version, $in_footer );
			wp_enqueue_style( "{$handle_prefix}_admin-components", "$admin_dir/css/components.css", array(), $version );
			wp_enqueue_style( "{$handle_prefix}_admin-panel", "$admin_dir/css/panel.css", array(), $version );

			// Localize translations.
			$lang_path = plugin_dir_path( COCA_RTPB__PLUGIN_FILE ) . 'languages';
			wp_set_script_translations( "{$handle_prefix}_admin-panel", 'reading-time-progress-bar', $lang_path );

			// Localize data.
			$plugin_settings_data = self::get_settings();
			wp_localize_script( "{$handle_prefix}_admin-panel", 'COCA_WP_READING_METER_DATA', $plugin_settings_data );
			wp_localize_script( "{$handle_prefix}_admin-panel", 'COCA_WP_READING_METER_DEFAULTS', self::get_settings_data_defaults() );
			wp_localize_script(
				"{$handle_prefix}_admin-panel",
				'COCA_WP_READING_METER_VARIABLES',
				array(
					'is_configured' => 0 !== count( $plugin_settings_data ),
					'build_url'     => $this->output_path,
					'libraries_url' => "$this->output_path/lib",
					'ajax_url'      => admin_url( 'admin-ajax.php' ),
					'ajax_nonce'    => wp_create_nonce( 'coca-rtpb-plugin-ajax-nonce' ),
				)
			);
		}
	}

	/**
	 * Register scripts and styles for the frontend.
	 *
	 * @return void
	 */
	public function register_frontend_dependencies() {
		wp_register_script( 'coca-rtpb-plugin-reading-progress-bar', "$this->output_path/frontend/js/reading-progress-bar.js", array(), $this->version, array( 'in_footer' => true ) );
		wp_register_script( 'coca-rtpb-plugin-reading-time', "$this->output_path/frontend/js/reading-time.js", array(), $this->version, array( 'in_footer' => true ) );
		wp_register_style( 'coca-rtpb-plugin-reading-progress-bar', "$this->output_path/frontend/css/reading-progress-bar.css", array(), $this->version );
		wp_register_style( 'coca-rtpb-plugin-reading-time', "$this->output_path/frontend/css/reading-time.css", array(), $this->version );
	}

	/**
	 * Set the settings data defaults.
	 *
	 * @return array Plugins settings data defaults.
	 */
	public static function get_settings_data_defaults() {
		// Add the plugin settings to the database.
		$tab_list_defaults = array(
			array(
				'title'     => esc_html__( 'General Settings', 'reading-time-progress-bar' ),
				'name'      => 'general-settings',
				'className' => 'tab-one-general-settings',
			),
			array(
				'title'     => esc_html__( 'Reading Time', 'reading-time-progress-bar' ),
				'name'      => 'reading-time',
				'className' => 'tab-two-reading-time',
			),
			array(
				'title'     => esc_html__( 'Progress Bar', 'reading-time-progress-bar' ),
				'name'      => 'reading-progress-bar',
				'className' => 'tab-three-progress-bar',
			),
			array(
				'title'     => esc_html__( 'Export, Import and Reset', 'reading-time-progress-bar' ),
				'name'      => 'export-import',
				'className' => 'tab-four-export-import',
			),
		);

		$settings_data_defaults = array(
			'is_mounted' => false,
			'is_saved'   => false,
			'tab_lists'  => $tab_list_defaults,
		);

		return apply_filters( 'coca_rtpb_plugin_settings_data_defaults', $settings_data_defaults );
	}

	/**
	 * Set initial the settings data.
	 *
	 * @return array Plugins settings data.
	 */
	public static function get_initial_settings_data() {
		// Add the plugin settings to the database.
		$post_types_defaults          = array(
			array(
				'label' => esc_html__( 'Posts', 'reading-time-progress-bar' ),
				'value' => 'post',
			),
		);
		$estimated_positions_defaults = array(
			array(
				'label' => esc_html__( 'Single Post', 'reading-time-progress-bar' ),
				'value' => 'single',
			),
			array(
				'label' => esc_html__( 'Archive Page', 'reading-time-progress-bar' ),
				'value' => 'archive',
			),
		);
		$selection_defaults           = array(
			'label' => esc_html__( 'Default', 'reading-time-progress-bar' ),
			'value' => '',
		);

		$initial_settings_data = array(
			'is_general_control_save_request_sending' => false,
			'customToggles'                           => array(),
			'is_reading_time_disable'                 => false,
			'is_reading_progress_bar_disable'         => false,
			'is_general_control_changes'              => false,
			'post_types'                              => $post_types_defaults,
			'post_type'                               => $post_types_defaults,
			'words_per_minute'                        => 200,
			'include_comments'                        => false,
			'include_images'                          => false,
			'images_per_minute'                       => 4,
			'estimated_positions'                     => $estimated_positions_defaults,
			'estimated_position'                      => $estimated_positions_defaults,
			'show_on_specific_pages'                  => false,
			'use_reading_time_custom_css'             => false,
			'specific_pages_list'                     => array(),
			'specific_pages_list_data'                => array(),
			'reading_time_text_position'              => array(
				'label' => esc_html__( 'Above The Title', 'reading-time-progress-bar' ),
				'value' => 'after-title',
			),
			'reading_time_text_positions'             => array(
				'label' => esc_html__( 'Above The Title', 'reading-time-progress-bar' ),
				'value' => 'after-title',
			),
			'reading_time_text_prefix'                => esc_html__( 'Reading Time', 'reading-time-progress-bar' ),
			'reading_time_text_suffix'                => esc_html__( ' mins', 'reading-time-progress-bar' ),
			'reading_time_text_suffix_singular'       => esc_html__( ' min', 'reading-time-progress-bar' ),
			'reading_time_text_font'                  => $selection_defaults,
			'reading_time_text_font_style'            => $selection_defaults,
			'reading_time_text_font_subset'           => $selection_defaults,
			'reading_time_text_font_text_align'       => $selection_defaults,
			'reading_time_text_font_size'             => '14',
			'reading_time_text_color'                 => array(
				'r' => '29',
				'g' => '35',
				'b' => '39',
				'a' => '1',
			),
			'reading_time_text_bg_color'              => array(
				'r' => '238',
				'g' => '238',
				'b' => '238',
				'a' => '1',
			),
			'reading_time_text_font_line_height'      => '1.5',
			'reading_time_custom_css_data'            => '',
			'is_progress_bar_control_changes'         => false,
			'is_progress_bar_styles_changes'          => false,
			'progress_bar_style'                      => $selection_defaults,
			'progress_bar_position'                   => array(
				'label' => 'Top',
				'value' => 'top',
			),
			'progress_bar_height'                     => 10,
			'progress_bar_offset'                     => 0,
			'progress_bar_content_offset'             => 0,
			'progress_bar_rtl_support'                => false,
			'progress_bar_sticky'                     => false,
			'progress_bar_fg_offset'                  => 100,
			'progress_bar_bg_offset'                  => 100,
			'progress_bar_foreground_color'           => array(
				'r' => '29',
				'g' => '35',
				'b' => '39',
				'a' => '1',
			),
			'progress_bar_background_color'           => array(
				'r' => '201',
				'g' => '210',
				'b' => '225',
				'a' => '1',
			),
			'progress_bar_border_radius'              => 0,
			'use_progress_bar_custom_css'             => false,
			'progress_bar_custom_css_data'            => '',
			'google_fonts'                            => array(),
			'standard_fonts'                          => array(),
			'is_settings_imported'                    => false,
			'is_settings_exported'                    => false,
			'upload_from_clipboard'                   => false,
			'import_clipboard_settings'               => '',
			'import_settings_file'                    => '',
			'export_clipboard_settings'               => '',
		);
		$initial_settings_data = array_merge( self::get_settings_data_defaults(), $initial_settings_data );

		return apply_filters( 'coca_rtpb_plugin_initial_settings_data', $initial_settings_data );
	}

	/**
	 * Get the settings data.
	 *
	 * @return array Plugins settings data.
	 */
	public static function get_settings() {
		// Collect plugin settings from a database.
		$plugin_settings = get_option( 'coca_rtpb_plugin_settings_data', array() );

		return apply_filters( 'coca_rtpb_plugin_settings_data', $plugin_settings );
	}

	/**
	 * Filters the settings data before send the frontend.
	 *
	 * @param array $settings Plugins settings data.
	 */
	public function hook_settings_data( $settings ) {
		if ( is_array( $settings ) && ! empty( $settings['post_type'] ) ) {
			foreach ( $settings['post_type'] as $index => $post_type ) {
				if ( ! post_type_exists( $post_type['value'] ) ) {
					unset( $settings['post_type'][ $index ] );
				}
			}
		}

		return $settings;
	}

	/**
	 * Filter the content with the plugin data
	 *
	 * @param string $content The post-content.
	 */
	public function hook_the_content_data( $content ) {
		if ( is_singular( get_post_type( get_the_ID() ) ) ) {
			$plugin_settings    = self::get_settings();
			$allowed_post_types = isset( $plugin_settings['post_type'] ) ? array_column( $plugin_settings['post_type'], 'value' ) : array();

			// verify the current post type from the allowed post types.
			if ( in_array( get_post_type( get_the_ID() ), $allowed_post_types, true ) ) {
				return "<div id='coca_rtpb_plugin_content'>$content</div>";
			}
		}

		return $content;
	}

	/**
	 * Filter the template file with the plugin data
	 *
	 * @param string $template The template file.
	 */
	public function hook_comments_template( $template ) {
		if ( is_singular( get_post_type( get_the_ID() ) ) ) {
			$plugin_settings     = self::get_settings();
			$allowed_post_types  = isset( $plugin_settings['post_type'] ) ? array_column( $plugin_settings['post_type'], 'value' ) : array();
			$is_include_comments = isset( $plugin_settings['include_comments'] ) && 'true' === $plugin_settings['include_comments'];

			// verify the current post type from the allowed post types.
			if ( $is_include_comments && in_array( get_post_type( get_the_ID() ), $allowed_post_types, true ) ) {
				echo '<div id="coca_rtpb_plugin_comments"></div>';
			}
		}

		return $template;
	}

	/**
	 * Filters the action links displayed for each plugin in the Plugins list table.
	 *
	 * @param string[] $action_links An array of plugin action links.
	 * @param string   $plugin_file  Path to the plugin file relative to the plugins' directory.
	 */
	public function register_action_links( $action_links, $plugin_file ) {
		if ( COCA_RTPB__PLUGIN_BASE === $plugin_file ) {
			$page_url          = admin_url( 'admin.php?page=reading-time-progress-bar' );
			$wcu_settings_link = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $page_url ),
				esc_html__( 'Get started now!', 'reading-time-progress-bar' )
			);
			array_unshift( $action_links, $wcu_settings_link );
		}

		return $action_links;
	}

	/**
	 * Load admin features.
	 *
	 * @return void
	 */
	private function load_admin_features() {
		new Admin\WP_Ajax();
		new Admin\Export_And_Import();
		new Frontend\Reading_Time();
		new Frontend\Reading_Progress_Bar();
	}

	/**
	 * Set root html for edit panel.
	 *
	 * @return void
	 */
	public function set_html__admin_panel_root() {
		printf(
			'<div id="reading-time-progress-bar__edit_panel_root"></div> <!-- end admin panel --><!-- Al Amin Ahamed (alaminahamed.com) -->'
		);
	}
}
