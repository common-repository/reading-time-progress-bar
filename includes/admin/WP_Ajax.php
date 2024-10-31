<?php // phpcs:ignore WordPress.Files.FileName Squiz.Commenting.FileComment.Missing

namespace COCA\Reading_Time_Progress_Bar\Admin;

use function COCA\Reading_Time_Progress_Bar\coca_sanitize_state_data as sanitize_array_data;

defined( 'ABSPATH' ) || exit;

/**
 * WP Ajax route register class.
 *
 * This class registers all ajax routes as per requirements.
 */
class WP_Ajax {

	/**
	 * The constructor for WP Ajax Class.
	 */
	public function __construct() {
		// Collect all callbacks for hooks.
		$panel_data   = array( $this, 'hook_get_all_data' );
		$all_settings = array( $this, 'hook_save_all_settings' );

		add_action( 'wp_ajax_coca_rtpb_plugin_get_panel_data', $panel_data );
		add_action( 'wp_ajax_coca_rtpb_plugin_save_settings', $all_settings );
	}

	/**
	 * Get all block post types.
	 *
	 * @return array
	 */
	public function get_blocked_post_types() {
		$wp_default_post_types   = array( 'attachment', 'revision' );
		$wp_gutenberg_post_types = array(
			'nav_menu_item',
			'wp_block',
			'wp_template',
			'wp_template_part',
			'wp_global_styles',
			'wp_navigation',
		);
		$wp_newly_post_types     = array( 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request' );
		$blocked_post_types      = array_merge( $wp_default_post_types, $wp_gutenberg_post_types, $wp_newly_post_types );

		return apply_filters( 'coca_rtpb_plugin_blocked_post_types', $blocked_post_types );
	}

	/**
	 * Handles get all registered post types AJAX request.
	 *
	 * @return void
	 */
	public function hook_get_all_data() {
		// Verify the nonce to prevent unauthorized request in the current endpoint.
		if ( ! check_ajax_referer( 'coca-rtpb-plugin-ajax-nonce' ) ) {
			wp_die( esc_html__( 'Unauthorized request.', 'reading-time-progress-bar' ) );
		}

		// Retrieves all data for admin panel.
		$panel_data = array(
			'all_post_types'      => $this->get_all_post_types(),
			'estimated_positions' => $this->get_estimated_positions(),
			'time_text_positions' => $this->get_reading_time_text_positions(),
		);

		// Send to the frontend user interface.
		wp_send_json( $panel_data );

		// All ajax handlers die when finished.
		wp_die();
	}

	/**
	 * Get all registered post-types.
	 *
	 * @return array
	 */
	public function get_all_post_types() {
		// Retrieves all public post types.
		$registered          = get_post_types( array( 'public' => true ), 'objects' );
		$post_types          = array();
		$filtered_post_types = apply_filters( 'coca_rtpb_plugin_general_settings_blocked_post_types', $this->get_blocked_post_types() );

		foreach ( $registered as $post_type ) {
			if ( ! in_array( $post_type->name, $filtered_post_types, true ) ) {
				$post_types[] = array(
					'label' => $post_type->label,
					'value' => $post_type->name,
				);
			}
		}

		return $post_types;
	}

	/**
	 * Get estimated position.
	 *
	 * @return array
	 */
	public function get_estimated_positions() {
		// Retrieves all estimated positions.
		return array(
			array(
				'label' => esc_html__( 'Single Post', 'reading-time-progress-bar' ),
				'value' => 'single',
			),
			array(
				'label' => esc_html__( 'Home/Blog Page', 'reading-time-progress-bar' ),
				'value' => 'home_blog',
			),
			array(
				'label' => esc_html__( 'Archive Page', 'reading-time-progress-bar' ),
				'value' => 'archive',
			),
		);
	}

	/**
	 * Get estimated position.
	 *
	 * @return array
	 */
	public function get_reading_time_text_positions() {
		// Retrieves all public post types.
		$defaults_positions = array(
			array(
				'label' => esc_html__( 'Above The Title', 'reading-time-progress-bar' ),
				'value' => 'before-title',
			),
			array(
				'label' => esc_html__( 'Below The Title', 'reading-time-progress-bar' ),
				'value' => 'after-title',
			),
			array(
				'label' => esc_html__( 'Above The Content', 'reading-time-progress-bar' ),
				'value' => 'above-content',
			),
			array(
				'label' => esc_html__( 'Below The Content', 'reading-time-progress-bar' ),
				'value' => 'below-content',
			),
		);

		return apply_filters( 'coca_rtpb_plugin_reading_time_text_positions', $defaults_positions );
	}

	/**
	 * Handles get estimated position for AJAX request.
	 *
	 * @return void
	 */
	public function hook_save_all_settings() {
		// Verify the nonce to prevent unauthorized request in the current endpoint.
		if ( ! check_ajax_referer( 'coca-rtpb-plugin-ajax-nonce' ) ) {
			wp_die( esc_html__( 'Unauthorized request.', 'reading-time-progress-bar' ) );
		}

		// Retrieves all data from http request.
		$overrides     = array();
		$settings_data = ! empty( $_POST['requestState'] ) ? sanitize_array_data( wp_unslash( $_POST['requestState'] ) ) : array();
		$settings      = apply_filters( 'coca_rtpb_plugin_settings_data', array_merge( $settings_data, $overrides ) );
		$options       = get_option( 'coca_rtpb_plugin_settings_data', array() );

		// Set default response.
		$response = array();

		// Collect old data for new data keys.
		$data_array_keys = array_keys( $settings );
		$data_old_values = wp_array_slice_assoc( $options, $data_array_keys );

		// Verify duplication settings and send an error message to the user.
		if ( $data_old_values === $settings ) {
			$response['success'] = false;
			$response['code']    = 400;
			$response['message'] = 'already_updated';
		} else {
			// Add to the database.
			$update_data = wp_parse_args( $settings, $options );
			if ( update_option( 'coca_rtpb_plugin_settings_data', $update_data ) ) {
				$response['success'] = true;
				$response['code']    = 201;
				$response['message'] = 'updated';
			} else {
				$response['success'] = false;
				$response['code']    = 403;
				$response['message'] = 'error';
			}
		}

		// Send to the frontend user interface.
		wp_send_json( $response );

		// All ajax handlers die when finished.
		wp_die();
	}
}
