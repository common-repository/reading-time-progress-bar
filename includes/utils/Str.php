<?php // phpcs:ignore WordPress.Files.FileName Squiz.Commenting.FileComment.Missing

namespace COCA\Reading_Time_Progress_Bar\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * String helper class.
 */
class Str {
	/**
	 * Polyfill for `str_starts_with()` function added in PHP 8.0.
	 *
	 * Performs a case-sensitive check indicating if the haystack begins with needle.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle   The substring to search for in the `$haystack`.
	 *
	 * @return bool True if `$haystack` starts with `$needle`, otherwise false.
	 */
	public static function starts_with( $haystack, $needle ) {
		if ( function_exists( '\str_starts_with' ) ) {
			return \str_starts_with( $haystack, $needle );
		}

		if ( '' === $needle ) {
			return true;
		}

		return 0 === strpos( $haystack, $needle );
	}

	/**
	 * Polyfill for `str_ends_with()` function added in PHP 8.0.
	 *
	 * Performs a case-sensitive check indicating if the haystack ends with needle.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle   The substring to search for in the `$haystack`.
	 *
	 * @return bool True if `$haystack` ends with `$needle`, otherwise false.
	 */
	public static function ends_with( $haystack, $needle ) {
		if ( function_exists( '\str_ends_with' ) ) {
			return \str_ends_with( $haystack, $needle );
		}

		if ( '' === $haystack ) {
			return '' === $needle;
		}

		$len = strlen( $needle );

		return substr( $haystack, - $len, $len ) === $needle;
	}
}
