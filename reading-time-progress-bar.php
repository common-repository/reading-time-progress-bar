<?php
/**
 * Reading Time & Progress Bar
 *
 * @package           CodeCanel\Reading_Time_Progress_Bar
 * @author            Code Canel
 * @copyright         2023 Code Canel
 * @license           GPLv2 or later
 *
 * @wordpress-plugin
 * Plugin Name:         Reading Time & Progress Bar
 * Plugin URI:          https://codecanel.com/reading-time-progress-bar
 * Description:         Track and display the average reading time of your posts. It provides valuable insights into how long it takes for users to read your content, helping you optimize your content length and engagement.
 * Version:             1.0.0
 * Requires at least:   4.9
 * Requires PHP:        5.6
 * Author:              Code Canel
 * Author URI:          https://codecanel.com/
 * License:             GPLv2 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:         reading-time-progress-bar
 * Domain Path:         /languages
 */

namespace COCA\Reading_Time_Progress_Bar;

defined( 'ABSPATH' ) || exit;

/**
 * Autoload function.
 *
 * @param string $class_name Class name.
 *
 * @return void
 */
spl_autoload_register(
	static function ( $class_name ) {
		// Bail out if the class name doesn't start with our prefix.
		if ( strpos( $class_name, 'COCA\\Reading_Time_Progress_Bar\\' ) !== 0 ) {
			return;
		}
		// Generate paths by namespace.
		$regex = array(
			'COCA\\Reading_Time_Progress_Bar\\Admin\\'    => '/includes/admin/',
			'COCA\\Reading_Time_Progress_Bar\\Frontend\\' => '/includes/frontend/',
			'COCA\\Reading_Time_Progress_Bar\\Utils\\'    => '/includes/utils/',
		);

		// Replace the namespace separator with the path prefix.
		$class_name = str_replace( array_keys( $regex ), array_values( $regex ), $class_name );

		// Replace the namespace separator with the directory separator.
		$class_name = str_replace( array( '\\', '//' ), DIRECTORY_SEPARATOR, $class_name );

		// Add the .php extension.
		$file_path = __DIR__ . $class_name . '.php';

		if ( file_exists( $file_path ) ) {
			require_once $file_path;
		}
	}
);

define( 'COCA_RTPB__PLUGIN_FILE', __FILE__ );
define( 'COCA_RTPB__PLUGIN_BASE', plugin_basename( __FILE__ ) );

// Load the plugin class.
require_once __DIR__ . '/Core.php';

/**
 * Return the instance.
 *
 * @return Plugin
 */
function coca_rtpb_plugin() {
	return Plugin::get_instance();
}

/**
 * Adds additional reading time for images.
 * Calculate additional reading time added by images in posts based on calculations by Medium. Https://blog.medium.com/read-time-and-you-bc2048ab620c
 *
 * @param int   $total_images     number of images in post.
 * @param array $words_per_minute words per minute.
 *
 * @return int Additional time added to the reading time by images.
 */
function coca_calculate_images( $total_images, $words_per_minute ) {
	$additional_time = 0;

	// For the first image we need to add 12 seconds, second image adds 11, ..., for image 10+ add 3 seconds.
	for ( $i = 1; $i <= $total_images; $i ++ ) {
		if ( $i >= 10 ) {
			$additional_time += 3 * (int) $words_per_minute / 60;
		} else {
			$additional_time += ( 12 - ( $i - 1 ) ) * (int) $words_per_minute / 60;
		}
	}

	return $additional_time;
}

/**
 * Calculate the reading time of a post.
 *
 * Gets the post-content, counts the images, strips shortcodes, and strips tags.
 * Then count the words. Converts images into a word coun and outputs the total reading time.
 *
 * @param int   $post     The Post ID.
 * @param array $settings The options selected for the plugin.
 *
 * @return false|float|string The total reading time for the article or string if it's 0.
 */
function coca_calculate_reading_time( $post, $settings ) {
	$current_post_type  = get_post_type( get_the_ID() );
	$comment_word_count = 0;

	if ( 'post' === $current_post_type ) {
		if ( in_the_loop() && is_singular() ) {
			$comments       = get_comments( array( 'post_id' => $post ) );
			$comment_string = '';

			foreach ( $comments as $comment ) {
				$comment_string = $comment_string . ' ' . $comment->comment_content;
			}

			$comment_word_count = ( count( preg_split( '/\s+/', $comment_string ) ) );
		}
	}

	$content          = get_post_field( 'post_content', $post );
	$number_of_images = substr_count( strtolower( $content ), '<img ' );

	$content    = wp_strip_all_tags( $content );
	$word_count = count( preg_split( '/\s+/', $content ) );

	if ( isset( $settings['include_comments'] ) && 'true' === $settings['include_comments'] ) {
		$word_count += $comment_word_count;
	}

	// Calculate additional time added to post by images.
	$additional_words_for_images = coca_calculate_images( $number_of_images, $settings['words_per_minute'] );

	if ( isset( $settings['include_images'] ) && 'true' === $settings['include_images'] ) {
		$word_count += $additional_words_for_images;
	}

	if ( $word_count < $settings['words_per_minute'] ) {
		$reading_time = $word_count / $settings['words_per_minute'];
	} else {
		$reading_time = ceil( $word_count / $settings['words_per_minute'] );
	}

	// If the reading time is 0 then return it as < 1 instead of 0.
	if ( 1 > $reading_time ) {
		$reading_time = '< 1';
	}

	return $reading_time;
}

/**
 * Sanitize array data for App State
 *
 * @param array $states   The App State data.
 * @param array $defaults Default data for sanitize.
 *
 * @return array Sanitize array.
 */
function coca_sanitize_state_data( $states, $defaults = array() ) {
	$data = array();

	foreach ( $states as $state_key => $state_value ) {
		if ( 'string' === gettype( $state_value ) ) {
			$data[ $state_key ] = sanitize_text_field( $state_value );
		}
		if ( 'integer' === gettype( $state_value ) ) {
			$data[ $state_key ] = (int) $state_value;
		}
		if ( 'double' === gettype( $state_value ) ) {
			$data[ $state_key ] = (float) $state_value;
		}
		if ( 'boolean' === gettype( $state_value ) ) {
			$data[ $state_key ] = (bool) $state_value;
		}
		if ( 'array' === gettype( $state_value ) ) {
			$current_key        = ! empty( $data[ $state_key ] ) && is_array( $data[ $state_key ] ) ? $data[ $state_key ] : array();
			$data[ $state_key ] = coca_sanitize_state_data( $state_value, array_merge( $current_key, $defaults ) );
		}
	}

	return $data;
}

// take off.
coca_rtpb_plugin();
