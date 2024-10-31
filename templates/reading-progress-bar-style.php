<?php
/**
 * Template for creating the front-end of the progress bar.
 *
 * @package CodeCanel\Reading_Time_Progress_Bar
 */

defined( 'ABSPATH' ) || exit;

use COCA\Reading_Time_Progress_Bar\Plugin;

$coca_rtpb_settings = wp_parse_args( Plugin::get_settings(), Plugin::get_initial_settings_data() );

if ( ! empty( $coca_rtpb_settings['progress_bar_background_color'] ) ) {
	$coca_rpb_bg_color = $coca_rtpb_settings['progress_bar_background_color'];
} else {
	$coca_rpb_bg_color = array(
		'r' => '201',
		'g' => '210',
		'b' => '225',
		'a' => '1',
	);
}
if ( ! empty( $coca_rtpb_settings['progress_bar_foreground_color'] ) ) {
	$coca_rpb_fg_color = $coca_rtpb_settings['progress_bar_foreground_color'];
} else {
	$coca_rpb_fg_color = array(
		'r' => '201',
		'g' => '210',
		'b' => '225',
		'a' => '1',
	);
}

// generate color data value.
$coca_rpb_bg_color_data = '';
$coca_rpb_fg_color_data = '';
if ( count( array_values( $coca_rpb_bg_color ) ) ) {
	$coca_rpb_bg_color_data = sprintf( 'rgba(%s, %s, %s, %s)', $coca_rpb_bg_color['r'], $coca_rpb_bg_color['g'], $coca_rpb_bg_color['b'], $coca_rpb_bg_color['a'] );
}
if ( count( array_values( $coca_rpb_fg_color ) ) ) {
	$coca_rpb_fg_color_data = sprintf( 'rgba(%s, %s, %s, %s)', $coca_rpb_fg_color['r'], $coca_rpb_fg_color['g'], $coca_rpb_fg_color['b'], $coca_rpb_fg_color['a'] );
}

?>


<style id='coca_rtpb_plugin.reading-progress-bar-css'>
	.coca-rtpb-plugin.reading-progress-bar {
		width: calc(100% - <?php echo esc_attr( $coca_rtpb_settings['progress_bar_offset'] ); ?>px) !important;
		left: <?php echo esc_attr( $coca_rtpb_settings['progress_bar_offset'] ); ?>px !important;
	}

	.coca-rtpb-plugin.reading-progress-bar .progress-bar-container {
		height: <?php echo esc_attr( $coca_rtpb_settings['progress_bar_height'] ); ?>px;
		border-radius: <?php echo esc_attr( $coca_rtpb_settings['progress_bar_border_radius'] ); ?>px;
		overflow: <?php echo ! empty( $coca_rtpb_settings['progress_bar_border_radius'] ) ? 'hidden' : ''; ?>;
	}

	.coca-rtpb-plugin.reading-progress-bar .progress-bar-container::before {
		height: <?php echo esc_attr( $coca_rtpb_settings['progress_bar_height'] ); ?>px;
		background-color: <?php echo esc_attr( $coca_rpb_bg_color_data ); ?>;
		opacity: calc( <?php echo esc_attr( $coca_rtpb_settings['progress_bar_bg_offset'] ); ?> / 100);
	}

	.coca-rtpb-plugin.reading-progress-bar .progress-bar-container .progress-bar {
		position: relative;
		height: <?php echo esc_attr( $coca_rtpb_settings['progress_bar_height'] ); ?>px;
		background-color: <?php echo esc_attr( $coca_rpb_fg_color_data ); ?>;
		opacity: calc( <?php echo esc_attr( $coca_rtpb_settings['progress_bar_fg_offset'] ); ?> / 100);
	}

	.coca-rtpb-plugin.reading-progress-bar .progress-bar-container.gradient,
	.coca-rtpb-plugin.reading-progress-bar .progress-bar-container.gradient .progress-bar {
		background-color: transparent;
	}

	.coca-rtpb-plugin.reading-progress-bar .progress-bar-container.gradient .progress-bar {
		background-image: linear-gradient(to right, <?php echo esc_attr( $coca_rpb_bg_color_data ); ?> 0%, <?php echo esc_attr( $coca_rpb_fg_color_data ); ?> 100%);
	}

	[dir=rtl] .coca-rtpb-plugin.reading-progress-bar .progress-bar-container.gradient .progress-bar,
	.rtl .coca-rtpb-plugin.reading-progress-bar .progress-bar-container.gradient .progress-bar {
		background-image: linear-gradient(to left, <?php echo esc_attr( $coca_rpb_bg_color_data ); ?> 0%, <?php echo esc_attr( $coca_rpb_fg_color_data ); ?> 100%);
	}

	<?php if ( isset( $coca_rtpb_settings['use_progress_bar_custom_css'] ) && 'true' === $coca_rtpb_settings['use_progress_bar_custom_css'] ) : ?>
		<?php echo wp_kses_post( $coca_rtpb_settings['progress_bar_custom_css_data'] ); ?>
	<?php endif; ?>

	<?php echo wp_kses_post( apply_filters( 'coca_rtpb_plugin_progress_bar_style', '' ) ); ?>
</style>
