<?php // phpcs:ignore WordPress.Files.FileName Squiz.Commenting.FileComment.Missing

namespace COCA\Reading_Time_Progress_Bar\Frontend;

defined( 'ABSPATH' ) || exit;

use COCA\Reading_Time_Progress_Bar\Plugin;
use COCA\Reading_Time_Progress_Bar\Utils\WP;
use function add_action;
use function add_filter;
use function get_post_type;
use function get_the_ID;
use function in_the_loop;
use function is_archive;
use function is_home;
use function is_singular;
use function wp_get_theme;
use function wp_kses_post;
use function wp_parse_args;

/**
 * Reading Time Class.
 */
class Reading_Time {

	/**
	 * The constructor for Class.
	 */
	public function __construct() {
		// add all hooks.
		add_action( 'wp', array( $this, 'init_frontend' ) );
		add_filter( 'comments_template', array( $this, 'hook_remove_the_title_from_comments' ) );
	}

	/**
	 * Frontend settings.
	 */
	public function init_frontend() {
		$plugin_settings        = $this->get_settings();
		$current_theme          = $this->get_current_theme();
		$twenty_themes          = array(
			'Twenty Twelve',
			'Twenty Thirteen',
			'Twenty Fourteen',
			'Twenty Fifteen',
			'Twenty Sixteen',
			'Twenty Seventeen',
			'Twenty Nineteen',
		);
		$block_supported_themes = array(
			'Twenty Twenty-One',
			'Twenty Twenty-Two',
			'Twenty Twenty-Three',
			'Twenty Twenty-Four',
		);

		// For twenty fifteen Theme remove the extra markup in the next-post and prev post section.
		if ( in_array( $current_theme, array_merge( $twenty_themes, $block_supported_themes ), true ) ) {
			add_filter( 'next_post_link', array( $this, 'remove_markup_from_content' ) );
			add_filter( 'previous_post_link', array( $this, 'remove_markup_from_content' ) );
		}

		if ( count( $plugin_settings ) && isset( $plugin_settings['is_reading_time_disable'] ) && 'true' !== $plugin_settings['is_reading_time_disable'] ) {
			$allowed_est_positions = array_column( $plugin_settings['estimated_position'], 'value' );
			$time_text_position    = $plugin_settings['reading_time_text_position']['value'];

			// Add stylesheet in the header.
			add_action( 'wp_head', array( $this, 'get_reading_time_style_output' ), 9 );

			// Fot the block supported themes.
			if ( $this->is_a_block_theme() || in_array( $current_theme, $block_supported_themes, true ) ) {
				$blocked_theme_callback = array( $this, 'hook_remove_the_title_from_comments_title_block' );
				add_filter( 'pre_render_block', $blocked_theme_callback, 10, 3 );
			}

			// Adding in the singular page.
			if ( is_singular( get_post_type( get_the_ID() ) ) && $this->is_allowed_post_type() && in_array( 'single', $allowed_est_positions, true ) ) {
				if ( ( $this->is_a_block_theme() || in_array( $current_theme, $block_supported_themes, true ) ) ) {
					$title_single_hook = 'render_block_core/post-title';
				} else {
					$title_single_hook = 'the_title';
				}

				if ( 'before-title' === $time_text_position ) {
					$title_before_single_callback = array( $this, 'hook_add_reading_time_before_the_post_title' );
					add_filter( $title_single_hook, $title_before_single_callback );
				}

				if ( 'after-title' === $time_text_position ) {
					$title_after_single_callback = array( $this, 'hook_add_reading_time_after_the_post_title' );
					add_filter( $title_single_hook, $title_after_single_callback );
				}

				if ( 'above-content' === $time_text_position ) {
					add_filter( 'the_content', array( $this, 'hook_add_reading_time_above_the_content' ) );
				}

				if ( 'below-content' === $time_text_position ) {
					add_filter( 'the_content', array( $this, 'hook_add_reading_time_below_the_content' ) );
				}
			}

			// Adding in the home page.
			if ( is_home() && ! is_archive() && in_array( 'home_blog', $allowed_est_positions, true ) ) {
				if ( 'before-title' === $time_text_position ) {
					add_filter( 'the_title', array( $this, 'hook_add_reading_time_before_title_excerpt' ) );
				}

				if ( 'after-title' === $time_text_position ) {
					add_filter( 'the_title', array( $this, 'hook_add_reading_time_after_title_excerpt' ) );
				}

				if ( 'above-content' === $time_text_position ) {
					if ( in_array( $current_theme, $twenty_themes, true ) ) {
						$content_above_home_hook = 'the_content';
					} elseif ( $this->is_a_block_theme() || in_array( $current_theme, $block_supported_themes, true ) ) {
						$content_above_home_hook = 'render_block_core/post-excerpt';
					} else {
						$content_above_home_hook = 'get_the_excerpt';
					}

					$content_above_home_callback = array( $this, 'hook_add_reading_time_above_content_excerpt' );
					add_filter( $content_above_home_hook, $content_above_home_callback );
				}

				if ( 'below-content' === $time_text_position ) {
					if ( in_array( $current_theme, $twenty_themes, true ) ) {
						$content_below_home_hook = 'the_content';
					} elseif ( $this->is_a_block_theme() || in_array( $current_theme, $block_supported_themes, true ) ) {
						$content_below_home_hook = 'render_block_core/post-excerpt';
					} else {
						$content_below_home_hook = 'get_the_excerpt';
					}

					$content_below_home_callback = array( $this, 'hook_add_reading_time_below_content_excerpt' );
					add_filter( $content_below_home_hook, $content_below_home_callback );
				}

				// Fixed: excerpt_more hook issue.
				add_filter( 'excerpt_more', array( $this, 'remove_markup_from_content' ) );
			}

			// Adding in the archive page.
			if ( ! is_home() && is_archive() && $this->is_allowed_post_type() && in_array( 'archive', $allowed_est_positions, true ) ) {
				if ( 'before-title' === $time_text_position ) {
					add_filter( 'the_title', array( $this, 'hook_add_reading_time_before_title_archive' ) );
				}

				if ( 'after-title' === $time_text_position ) {
					add_filter( 'the_title', array( $this, 'hook_add_reading_time_after_title_archive' ) );
				}

				if ( 'above-content' === $time_text_position ) {
					if ( in_array( $current_theme, $twenty_themes, true ) ) {
						$content_above_archive_hook = 'the_content';
					} elseif ( $this->is_a_block_theme() || in_array( $current_theme, $block_supported_themes, true ) ) {
						$content_above_archive_hook = 'render_block_core/post-excerpt';
					} else {
						$content_above_archive_hook = 'get_the_excerpt';
					}

					$content_above_archive_callback = array( $this, 'hook_add_reading_time_above_content_archive' );
					add_filter( $content_above_archive_hook, $content_above_archive_callback );
				}

				if ( 'below-content' === $time_text_position ) {
					if ( in_array( $current_theme, $twenty_themes, true ) ) {
						$content_below_archive_hook = 'the_content';
					} elseif ( $this->is_a_block_theme() || in_array( $current_theme, $block_supported_themes, true ) ) {
						$content_below_archive_hook = 'render_block_core/post-excerpt';
					} else {
						$content_below_archive_hook = 'get_the_excerpt';
					}

					$content_below_archive_callback = array( $this, 'hook_add_reading_time_below_content_archive' );
					add_filter( $content_below_archive_hook, $content_below_archive_callback );
				}
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
	 * Verify the current post-type is allowed or not.
	 *
	 * @return bool
	 */
	public function is_allowed_post_type() {
		$plugin_settings    = $this->get_settings();
		$allowed_post_types = array_column( $plugin_settings['post_type'], 'value' );

		return in_array( get_post_type( get_the_ID() ), $allowed_post_types, true );
	}

	/**
	 * Get the current Theme Name.
	 *
	 * @return string theme name.
	 */
	public function get_current_theme() {
		$theme = wp_get_theme();

		return $theme->parent() ? $theme->parent()->get( 'Name' ) : $theme->get( 'Name' );
	}

	/**
	 * Returns whether this theme is a block-based theme or not.
	 *
	 * @return bool
	 */
	public function is_a_block_theme() {
		if ( WP::is_version_compatible( 5.9 ) && method_exists( wp_get_theme(), 'is_block_theme' ) ) {
			return wp_get_theme()->is_block_theme();
		}

		return false;
	}

	/**
	 * Remove markup for Twenty fifteen.
	 *
	 * @param string $output Markup of reading time div.
	 */
	public function remove_markup_from_content( $output ) {
		// Clean line-break from html markup.
		$html_markup = str_replace( "/\n/g", '', $output );
		$html_markup = str_replace( '/<p><\/p>/g', '', $html_markup );
		$pattern     = '/<span class="coca-rtpb-plugin reading-time after-title">(.*?)class="reading-time-text suffix-text"><\/span><\/span><\/span>/gs';

		return str_replace( $pattern, '', $html_markup );
	}

	/**
	 * Get the output for Reading time text.
	 *
	 * @return string The output for Reading time text.
	 */
	public function get_reading_time_text_output() {

		ob_start();

		require COCA_RTPB__PLUGIN_DIR . 'templates/reading-time.php';

		// Add reading time text to the title.
		return wp_kses_post( ob_get_clean() );
	}

	/**
	 * Get the stylesheet output for the Reading time text.
	 */
	public function get_reading_time_style_output() {

		ob_start();

		require COCA_RTPB__PLUGIN_DIR . 'templates/reading-time-style.php';

		print ob_get_clean(); // phpcs:ignore
	}

	/**
	 * Adds the reading time above the post-title.
	 *
	 * @param string $post_title The original post content.
	 *
	 * @return string The post-content with reading time prepended.
	 */
	public function hook_add_reading_time_before_the_post_title( $post_title ) {
		if ( in_the_loop() && is_singular() ) {
			$output_text = $this->get_reading_time_text_output();

			return $output_text . $post_title;
		}

		return $post_title;
	}

	/**
	 * Adds the reading time below the post-title.
	 *
	 * @param string $post_title The original post title.
	 *
	 * @return string The post-title with reading time prepended.
	 */
	public function hook_add_reading_time_after_the_post_title( $post_title ) {
		if ( in_the_loop() && is_singular() ) {
			$output_text = $this->get_reading_time_text_output();

			return $post_title . $output_text;
		}

		return $post_title;
	}

	/**
	 * Adds the reading time before the_excerpt title.
	 *
	 * If the options are selected to automatically add the reading time before
	 * the_excerpt, the reading time is calculated and added to the beginning of the_excerpt.
	 *
	 * @param string $post_title The original content of the_excerpt.
	 *
	 * @return string The excerpt content with reading time prepended.
	 */
	public function hook_add_reading_time_before_title_excerpt( $post_title ) {
		// The first post in the home is not under a loop query.
		if ( in_the_loop() && is_home() && ! is_archive() && $this->is_allowed_post_type() ) {
			$output_text = $this->get_reading_time_text_output();

			return $output_text . $post_title;
		} else {
			return $post_title;
		}
	}

	/**
	 * Adds the reading time after the_excerpt title.
	 *
	 * If the options are selected to automatically add the reading time before
	 * the_excerpt, the reading time is calculated and added to the beginning of the_excerpt.
	 *
	 * @param string $post_title The original content of the_excerpt.
	 *
	 * @return string The excerpt content with reading time prepended.
	 */
	public function hook_add_reading_time_after_title_excerpt( $post_title ) {
		// The first post in the home is not under a loop query.
		if ( in_the_loop() && is_home() && ! is_archive() && $this->is_allowed_post_type() ) {
			$output_text = $this->get_reading_time_text_output();

			return $post_title . $output_text;
		} else {
			return $post_title;
		}
	}

	/**
	 * Adds the reading time before the archive title.
	 *
	 * If the options are selected to automatically add the reading time before
	 * the_excerpt, the reading time is calculated and added to the beginning of the_excerpt.
	 *
	 * @param string $post_title The original content of the_excerpt.
	 *
	 * @return string The excerpt content with reading time prepended.
	 */
	public function hook_add_reading_time_before_title_archive( $post_title ) {
		// The first post in the archive is not under a loop query.
		if ( in_the_loop() && is_archive() ) {
			$output_text = $this->get_reading_time_text_output();

			return $output_text . $post_title;
		} else {
			return $post_title;
		}
	}

	/**
	 * Adds the reading time after the archive title.
	 *
	 * If the options are selected to automatically add the reading time before
	 * the_excerpt, the reading time is calculated and added to the beginning of the_excerpt.
	 *
	 * @param string $post_title The original content of the_excerpt.
	 *
	 * @return string The excerpt content with reading time prepended.
	 */
	public function hook_add_reading_time_after_title_archive( $post_title ) {
		// The first post in the archive is not under a loop query.
		if ( in_the_loop() && is_archive() ) {
			$output_text = $this->get_reading_time_text_output();

			return $post_title . $output_text;
		} else {
			return $post_title;
		}
	}

	/**
	 * Adds the reading time above the_content.
	 *
	 * If the options are selected to automatically add the reading time before
	 * the_content, the reading time is calculated and added to the beginning of the_content.
	 *
	 * @param string $content The original post content.
	 *
	 * @return string The post-content with reading time prepended.
	 */
	public function hook_add_reading_time_above_the_content( $content ) {
		if ( in_the_loop() && is_singular() ) {
			$output_text = $this->get_reading_time_text_output();

			return $output_text . $content;
		}

		return $content;
	}

	/**
	 * Adds the reading time below the_content.
	 *
	 * If the options are selected to automatically add the reading time before
	 * the_content, the reading time is calculated and added to the beginning of the_content.
	 *
	 * @param string $content The original post content.
	 *
	 * @return string The post-content with reading time prepended.
	 */
	public function hook_add_reading_time_below_the_content( $content ) {
		if ( in_the_loop() && is_singular() ) {
			$output_text = $this->get_reading_time_text_output();

			return $content . $output_text;
		}

		return $content;
	}

	/**
	 * Adds the reading time above the_excerpt content.
	 *
	 * If the options are selected to automatically add the reading time before
	 * the_excerpt, the reading time is calculated and added to the beginning of the_excerpt.
	 *
	 * @param string $excerpt The original content of the_excerpt.
	 *
	 * @return string The post-content with reading time prepended.
	 */
	public function hook_add_reading_time_above_content_excerpt( $excerpt ) {
		if ( in_the_loop() && is_home() && ! is_archive() && $this->is_allowed_post_type() ) {
			$output_text = $this->get_reading_time_text_output();

			return wp_kses_post( $output_text . $excerpt );
		}

		return wp_kses_post( $excerpt );
	}

	/**
	 * Adds the reading time below the_excerpt content.
	 *
	 * If the options are selected to automatically add the reading time below
	 * the_excerpt, the reading time is calculated and added to the below of the_excerpt.
	 *
	 * @param string $excerpt The original content of the_excerpt.
	 *
	 * @return string The post-content with reading time prepended.
	 */
	public function hook_add_reading_time_below_content_excerpt( $excerpt ) {
		if ( in_the_loop() && is_home() && ! is_archive() && $this->is_allowed_post_type() ) {
			$output_text = $this->get_reading_time_text_output();

			return wp_kses_post( $excerpt . $output_text );
		}

		return wp_kses_post( $excerpt );
	}

	/**
	 * Adds the reading time above the archive excerpt.
	 *
	 * If the options are selected to automatically add the reading time above
	 * the_excerpt, the reading time is calculated and added to the beginning of the_excerpt.
	 *
	 * @param string $excerpt The original content of the_excerpt.
	 *
	 * @return string The post-content with reading time prepended.
	 */
	public function hook_add_reading_time_above_content_archive( $excerpt ) {
		if ( in_the_loop() && is_archive() ) {
			$output_text = $this->get_reading_time_text_output();

			return wp_kses_post( $output_text . $excerpt );
		}

		return wp_kses_post( $excerpt );
	}

	/**
	 * Adds the reading time below the archive excerpt.
	 *
	 * If the options are selected to automatically add the reading time below
	 * the_excerpt, the reading time is calculated and added to the ending of the_excerpt.
	 *
	 * @param string $excerpt The original content of the_excerpt.
	 *
	 * @return string The post-content with reading time prepended.
	 */
	public function hook_add_reading_time_below_content_archive( $excerpt ) {
		if ( in_the_loop() && is_archive() ) {
			$output_text = $this->get_reading_time_text_output();

			return wp_kses_post( $excerpt . $output_text );
		}

		return wp_kses_post( $excerpt );
	}

	/**
	 * Removes our Reading time from the comment title.
	 */
	public function hook_remove_the_title_from_comments() {
		remove_filter( 'the_title', array( $this, 'hook_add_reading_time_before_the_post_title' ) );
		remove_filter( 'the_title', array( $this, 'hook_add_reading_time_after_the_post_title' ) );
	}

	/**
	 * Removes our Reading time from the comment title block.
	 *
	 * @param string|null $pre_render   The pre-rendered content. Default null.
	 * @param array       $parsed_block The block being rendered.
	 */
	public function hook_remove_the_title_from_comments_title_block( $pre_render, $parsed_block ) {
		$blocked_wp_core_blocks = array( 'core/comments-title' );
		if ( count( $parsed_block ) && ! empty( $parsed_block['blockName'] ) && in_array( $parsed_block['blockName'], $blocked_wp_core_blocks, true ) ) {
			$this->hook_remove_the_title_from_comments();
		}

		return $pre_render;
	}
}
