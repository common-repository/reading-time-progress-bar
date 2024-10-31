<?php // phpcs:ignore WordPress.Files.FileName Squiz.Commenting.FileComment.Missing

namespace COCA\Reading_Time_Progress_Bar\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * JSON helper class.
 */
class Json {
	/**
	 * Reads and decodes a JSON file.
	 *
	 * @param string $filename Path to the JSON file.
	 * @param array  $options  Optional. Options to be used with `json_decode()`.
	 *
	 * @return mixed Returns the value encoded in JSON in appropriate PHP type.
	 */
	public static function file_decode( $filename, $options = array() ) {
		if ( function_exists( '\wp_json_file_decode' ) ) {
			return \wp_json_file_decode( $filename, $options );
		}

		$filename = wp_normalize_path( realpath( $filename ) );
		if ( ! $filename ) {
			trigger_error( // phpcs:ignore
				sprintf(
				/* translators: %s: Path to the JSON file. */
					__( "File %s doesn't exist!", 'reading-time-progress-bar' ), // phpcs:ignore
					$filename  // phpcs:ignore
				)
			);

			return null;
		}

		$options      = wp_parse_args( $options, array( 'associative' => false ) );
		$decoded_file = json_decode( file_get_contents( $filename ), $options['associative'] ); // phpcs:ignore
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			trigger_error(  // phpcs:ignore
				sprintf(
				/* translators: 1: Path to the JSON file, 2: Error message. */
					__( 'Error when decoding a JSON file at path %1$s: %2$s', 'reading-time-progress-bar' ),  // phpcs:ignore
					$filename,  // phpcs:ignore
					json_last_error_msg()  // phpcs:ignore
				)
			);

			return null;
		}

		return $decoded_file;
	}

	/**
	 * Verify that the current string is a jon data or not.
	 *
	 * @param mixed $string_content Json mixed raw data.
	 *
	 * @return bool
	 */
	public static function is_json( $string_content ) {
		try {
			json_decode( $string_content );

			return json_last_error() === JSON_ERROR_NONE;
		} catch ( \Exception $exception ) {
			return false;
		}
	}
}
