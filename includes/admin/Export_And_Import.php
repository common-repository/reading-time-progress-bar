<?php // phpcs:ignore WordPress.Files.FileName Squiz.Commenting.FileComment.Missing

namespace COCA\Reading_Time_Progress_Bar\Admin;

use Exception;
use function COCA\Reading_Time_Progress_Bar\coca_sanitize_state_data as sanitize_array_data;

defined( 'ABSPATH' ) || exit;

/**
 * WP Settings Export & Import register class.
 */
class Export_And_Import {

	/**
	 * The constructor for WP Ajax Class.
	 */
	public function __construct() {
		add_action( 'wp_ajax_coca_rtpb_plugin_import_settings', array( $this, 'hook_import_settings' ) );
		add_action( 'wp_ajax_coca_rtpb_plugin_export_settings', array( $this, 'hook_export_settings' ) );
	}

	/**
	 * Handles get estimated position for AJAX request.
	 *
	 * @return void
	 * @throws Exception Throw exception when json error found.
	 */
	public function hook_import_settings() {
		// Verify the nonce to prevent unauthorized request in the current endpoint.
		if ( ! check_ajax_referer( 'coca-rtpb-plugin-ajax-nonce' ) ) {
			wp_die( esc_html__( 'Unauthorized request.', 'reading-time-progress-bar' ) );
		}

		$settings_array = array();
		if ( isset( $_POST['requestState'] ) & ! empty( $_POST['requestState'] ) ) {
			$imported_settings = sanitize_array_data( wp_unslash( $_POST['requestState'] ) );
			if ( is_string( $imported_settings ) && $this->is_json_string( $imported_settings ) ) {
				$settings_array = json_decode( $imported_settings, true );
			} elseif ( is_array( $imported_settings ) ) {
				$settings_array = $imported_settings;
			} else {
				// Send an error message to the user.
				wp_send_json(
					array(
						'success' => false,
						'code'    => 400,
						'message' => 'invalid_data',
					)
				);
			}
		}

		if ( count( $settings_array ) !== 0 ) {
			// Verify the imported settings data before receive it.
			$is_valid_key_found = false;
			$valid_keys         = $this->get_allowed_data_keys();
			foreach ( $valid_keys as $valid_key ) {
				$is_valid_key_found = isset( $settings_array[ $valid_key ] );
				if ( ! $is_valid_key_found ) {
					break;
				}
			}

			if ( $is_valid_key_found ) {
				if ( update_option( 'coca_rtpb_plugin_settings_data', $settings_array ) ) {
					wp_send_json(
						array(
							'success' => true,
							'code'    => 201,
							'message' => 'imported',
						)
					);
				} else {
					wp_send_json(
						array(
							'success' => false,
							'code'    => 400,
							'message' => 'already_imported',
						)
					);
				}
			} else {
				wp_send_json(
					array(
						'success' => false,
						'code'    => 400,
						'message' => 'invalid_data',
					)
				);
			}
		}

		// All ajax handlers die when finished.
		wp_die();
	}

	/**
	 * Export settings data in to json file
	 *
	 * @return void
	 */
	public function hook_export_settings() {
		// Verify the nonce to prevent unauthorized request in the current endpoint.
		if ( ! check_ajax_referer( 'coca-rtpb-plugin-ajax-nonce' ) ) {
			wp_die( esc_html__( 'Unauthorized request.', 'reading-time-progress-bar' ) );
		}

		// Collect settings data from database.
		$coca_meta_data = get_option( 'coca_rtpb_plugin_settings_data', array() );
		$coca_meta_data = apply_filters( 'coca_rtpb_plugin_settings_data_export', $coca_meta_data );

		if ( count( $coca_meta_data ) ) {
			// Send Headers.
			header( 'Content-Type: application/json;charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="rtpb-plugin-settings.json"' );
			echo wp_json_encode( $coca_meta_data, true );

			// Send Headers: Prevent Caching of File.
			header( 'Cache-Control: private' );
			header( 'Pragma: private' );
		}

		// exit current session.
		exit( 0 );
	}

	/**
	 * Verify the content, is it a json content.
	 *
	 * @param string $json_str Encoded json string.
	 *
	 * @return bool
	 */
	private function is_json_string( $json_str ) {
		try {
			json_decode( $json_str );

			return true;
		} catch ( Exception $exception ) {
			return false;
		}
	}

	/**
	 * The allowed data keys for the plugin settings.
	 *
	 * @return array
	 */
	private function get_allowed_data_keys() {
		return array(
			'post_type',
			'words_per_minute',
			'include_comments',
			'include_images',
			'images_per_minute',
			'estimated_position',
			'reading_time_text_font',
			'reading_time_text_position',
			'reading_time_text_prefix',
			'reading_time_text_suffix',
			'reading_time_text_suffix_singular',
		);
	}
}
