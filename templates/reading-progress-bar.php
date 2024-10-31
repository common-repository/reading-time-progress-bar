<?php
/**
 * Template for creating the front-end of the progress bar.
 *
 * @package CodeCanel\Reading_Time_Progress_Bar
 */

defined( 'ABSPATH' ) || exit;

use COCA\Reading_Time_Progress_Bar\Plugin;

// Collect plugin settings data.
$coca_rtpb_settings = wp_parse_args( Plugin::get_settings(), Plugin::get_initial_settings_data() );
$coca_rpb_position  = ! empty( $coca_rtpb_settings['progress_bar_position']['value'] ) ? $coca_rtpb_settings['progress_bar_position']['value'] : 'top';
$coca_rpb_style     = ! empty( $coca_rtpb_settings['progress_bar_style']['value'] ) ? $coca_rtpb_settings['progress_bar_style']['value'] : '';


// Create a public settings to integrate the library.
$coca_rpb_public_settings = array(
	'include_comments'            => ! empty( $coca_rtpb_settings['include_comments'] ) ? $coca_rtpb_settings['include_comments'] : 'false',
	'progress_bar_content_offset' => ! empty( $coca_rtpb_settings['progress_bar_content_offset'] ) ? $coca_rtpb_settings['progress_bar_content_offset'] : 0,
);

printf(
	'<div class="coca-rtpb-plugin reading-progress-bar %1$s" data-settings=\'%3$s\'><div class=\'progress-bar-container %2$s\'><div class="progress-bar"></div></div></div>',
	esc_attr( $coca_rpb_position ),
	esc_attr( $coca_rpb_style ),
	esc_attr( wp_json_encode( $coca_rpb_public_settings ) )
);
