<?php // phpcs:ignore WordPress.Files.FileName Squiz.Commenting.FileComment.Missing

namespace COCA\Reading_Time_Progress_Bar\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * WP Theme helper class.
 */
class WP {
	/**
	 * Checks compatibility with the current WordPress version.
	 *
	 * @param string $required   Minimum required WordPress version.
	 *
	 * @return bool True if a required version is compatible or empty, false if not.
	 *
	 * @global string $wp_version The WordPress version string.
	 */
	public static function is_version_compatible( $required ) {
		if ( function_exists( '\is_wp_version_compatible' ) ) {
			return \is_wp_version_compatible( $required );
		}

		global $wp_version;

		// Strip off any -alpha, -RC, -beta, -src suffixes.
		list( $version ) = explode( '-', $wp_version );

		return empty( $required ) || version_compare( $version, $required, '>=' );
	}
}
