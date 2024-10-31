<?php // phpcs:ignore WordPress.Files.FileName Squiz.Commenting.FileComment.Missing

namespace COCA\Reading_Time_Progress_Bar\Frontend;

defined( 'ABSPATH' ) || exit;

use COCA\Reading_Time_Progress_Bar\Plugin;
use function add_action;
use function get_post_type;
use function get_the_ID;
use function is_singular;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_kses_post;
use function wp_parse_args;

/**
 * Reading Progress Bar Class.
 */
class Reading_Progress_Bar {

	/**
	 * The constructor for Class
	 */
	public function __construct() {
		add_action( 'wp', array( $this, 'init_frontend' ), 0 );
	}

	/**
	 * Frontend settings.
	 */
	public function init_frontend() {
		$plugin_settings   = $this->get_settings();
		$current_post_type = get_post_type( get_the_ID() );

		if ( count( $plugin_settings ) && ! empty( $plugin_settings['is_reading_progress_bar_disable'] ) && 'true' !== $plugin_settings['is_reading_progress_bar_disable'] ) {
			$allowed_post_types = array_column( $plugin_settings['post_type'], 'value' );

			// Load assets for frontend.
			wp_enqueue_script( 'coca-rtpb-plugin-reading-progress-bar' );
			wp_enqueue_style( 'coca-rtpb-plugin-reading-progress-bar' );

			// Add stylesheet in the header.
			add_action( 'wp_head', array( $this, 'get_reading_progress_bar_style_output' ), 9 );

			// Verification:
			// 1. Check the current page.
			// 2. Check the current post-type with the allowed post-type.
			if ( is_singular( get_post_type( get_the_ID() ) ) && in_array( $current_post_type, $allowed_post_types, true ) ) {
				add_action( 'wp_body_open', array( $this, 'show_the_reading_progress_bar' ), 0 );
			}
		}
	}

	/**
	 * Get the plugin settings.
	 *
	 * @return array settings.
	 */
	public function get_settings() {
		$saved_settings   = Plugin::get_settings();
		$initial_settings = Plugin::get_initial_settings_data();

		return count( $saved_settings ) !== 0 ? wp_parse_args( $saved_settings, $initial_settings ) : array();
	}

	/**
	 * Get the html output of Reading progress bar.
	 *
	 * @return void
	 */
	public function show_the_reading_progress_bar() {
		ob_start();

		require COCA_RTPB__PLUGIN_DIR . 'templates/reading-progress-bar.php';

		print wp_kses_post( ob_get_clean() );
	}

	/**
	 * Get the stylesheet output for Reading progress bar.
	 *
	 * @return void
	 */
	public function get_reading_progress_bar_style_output() {

		ob_start();

		require COCA_RTPB__PLUGIN_DIR . 'templates/reading-progress-bar-style.php';

		print ob_get_clean(); // phpcs:ignore
	}
}
