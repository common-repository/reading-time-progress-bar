<?php
/**
 * Template for creating the front-end of the reading time.
 *
 * @package CodeCanel\Reading_Time_Progress_Bar
 */

defined( 'ABSPATH' ) || exit;

use COCA\Reading_Time_Progress_Bar\Plugin;
use COCA\Reading_Time_Progress_Bar\Utils\Json;
use COCA\Reading_Time_Progress_Bar\Utils\Str;

$coca_rtpb_settings        = wp_parse_args( Plugin::get_settings(), Plugin::get_initial_settings_data() );
$coca_rt_selected_font     = ! empty( $coca_rtpb_settings['reading_time_text_font']['value'] ) ? $coca_rtpb_settings['reading_time_text_font']['value'] : '';
$coca_rt_font_weight_style = ! empty( $coca_rtpb_settings['reading_time_text_font_style']['value'] ) ? $coca_rtpb_settings['reading_time_text_font_style']['value'] : '';
$coca_rt_text_align        = ! empty( $coca_rtpb_settings['reading_time_text_font_text_align']['value'] ) ? $coca_rtpb_settings['reading_time_text_font_text_align']['value'] : '';
$coca_rt_text_size         = ! empty( $coca_rtpb_settings['reading_time_text_font_size'] ) ? $coca_rtpb_settings['reading_time_text_font_size'] : '';
$coca_rt_text_line_height  = ! empty( $coca_rtpb_settings['reading_time_text_font_line_height'] ) ? $coca_rtpb_settings['reading_time_text_font_line_height'] : '';

$coca_rt_text_font_style = 'normal';
$coca_rt_font_weight     = 400;
$coca_rt_font_query_url  = '';

if ( Str::ends_with( strtolower( $coca_rt_font_weight_style ), 'italic' ) ) {
	$coca_rt_text_font_style  = 'italic';
	$coca_rt_font_weight_data = explode( ' ', $coca_rt_font_weight_style );
	$coca_rt_font_weight      = array_shift( $coca_rt_font_weight_data );
} else {
	$coca_rt_font_weight = ! empty( $coca_rt_font_weight_style ) ? $coca_rt_font_weight_style : $coca_rt_font_weight;
}

// collect color values.
if ( ! empty( $coca_rtpb_settings['reading_time_text_color'] ) ) {
	$coca_rt_text_color = $coca_rtpb_settings['reading_time_text_color'];
} else {
	$coca_rt_text_color = array(
		'r' => '29',
		'g' => '35',
		'b' => '39',
		'a' => '1',
	);
}
if ( ! empty( $coca_rtpb_settings['reading_time_text_bg_color'] ) ) {
	$coca_rt_background_color = $coca_rtpb_settings['reading_time_text_bg_color'];
} else {
	$coca_rt_background_color = array(
		'r' => '238',
		'g' => '238',
		'b' => '238',
		'a' => '1',
	);
}

// generate color data value.
$coca_rt_text_color_data       = '';
$coca_rt_background_color_data = '';
if ( count( array_values( $coca_rt_text_color ) ) ) {
	$coca_rt_text_color_data = sprintf( 'rgba(%s, %s, %s, %s)', $coca_rt_text_color['r'], $coca_rt_text_color['g'], $coca_rt_text_color['b'], $coca_rt_text_color['a'] );
}
if ( count( array_values( $coca_rt_background_color ) ) ) {
	$coca_rt_background_color_data = sprintf( 'rgba(%s, %s, %s, %s)', $coca_rt_background_color['r'], $coca_rt_background_color['g'], $coca_rt_background_color['b'], $coca_rt_background_color['a'] );
}

?>

<style id='coca_rtpb_plugin.reading-time-css'>
	<?php

	if ( ! empty( $coca_rt_selected_font ) ) {
			$coca_rt_selected_font = str_replace( '\"', '"', $coca_rt_selected_font );
			$coca_rt_selected_font = str_replace( '&gt; ', '', $coca_rt_selected_font );

		if ( Json::is_json( $coca_rt_selected_font ) ) {
			$coca_rt_selected_fonts_data = json_decode( $coca_rt_selected_font, true, JSON_UNESCAPED_SLASHES );

			if ( count( $coca_rt_selected_fonts_data ) ) {
				$coca_rt_font_name_encoded = preg_replace( '/\s+/', '+', $coca_rt_selected_fonts_data['family'] );
				$coca_rt_font_variants     = array();
				$coca_rt_regular_variants  = array();
				$coca_rt_italic_variants   = array();
				$coca_rt_query_variants    = '';

				if ( ! empty( $coca_rt_selected_fonts_data['variants'] ) ) {
					foreach ( $coca_rt_selected_fonts_data['variants'] as $coca_rt_variant ) {
						if ( 'regular' === $coca_rt_variant ) {
							$coca_rt_font_variants[] = '400';
						} elseif ( 'italic' === $coca_rt_variant ) {
							$coca_rt_font_variants[] = '400 Italic';
						} elseif ( Str::ends_with( $coca_rt_variant, 'italic' ) ) {
							$coca_rt_font_variants[] = str_replace( 'italic', ' Italic', $coca_rt_variant );
						} else {
							$coca_rt_font_variants[] = $coca_rt_variant;
						}
					}

					foreach ( $coca_rt_font_variants as $coca_rt_font_variant ) {
						if ( Str::ends_with( strtolower( $coca_rt_font_variant ), 'italic' ) ) {
							$coca_rt_italic_variants[] = $coca_rt_font_variant;
						} else {
							$coca_rt_regular_variants[] = $coca_rt_font_variant;
						}
					}

					$coca_rt_join_variants = implode( ';', $coca_rt_regular_variants );

					if ( count( $coca_rt_regular_variants ) && count( $coca_rt_italic_variants ) ) {
						$coca_rt_join_regular_variants = implode( ';0,', $coca_rt_regular_variants );
						$coca_rt_join_italic_variants  = implode( ';1,', $coca_rt_regular_variants );
						$coca_rt_join_variants         = "0,$coca_rt_join_regular_variants;1,$coca_rt_join_italic_variants";
					}

					$coca_rt_query_suffix   = count( $coca_rt_italic_variants ) ? 'ital,' : '';
					$coca_rt_query_variants = ":{$coca_rt_query_suffix}wght@$coca_rt_join_variants";
				}

				$coca_rt_text_subset = $coca_rtpb_settings['reading_time_text_font_subset']['value'];
				$coca_rt_font_subset = ! empty( $coca_rt_text_subset ) ? "&subset=$coca_rt_text_subset" : '';

				printf( '/* %1$s */', esc_html__( 'Load google font when needed', 'reading-time-progress-bar' ) );
				printf(
					'@import url(\'https://fonts.googleapis.com/css2?family=%1$s%2$s&display=swap%3$s\');',
					esc_attr( $coca_rt_font_name_encoded ),
					esc_attr( $coca_rt_query_variants ),
					esc_attr( $coca_rt_font_subset )
				);

				$coca_rt_selected_font = "'{$coca_rt_selected_fonts_data['family']}', {$coca_rt_selected_fonts_data['category']}";
			}
		} else {
			$coca_rt_selected_font = str_replace( "\'", "'", $coca_rt_selected_font );
		}

		printf(
			'.coca-rtpb-plugin.reading-time .reading-time-container {font-family:%1$s;}',
			wp_kses_post( $coca_rt_selected_font )
		);
	}

	?>

	.coca-rtpb-plugin.reading-time {
		margin: 1px;
		padding: 0;
		line-height: 0;
		display: flex;
		justify-content: <?php echo ! empty( $coca_rt_text_align ) ? esc_attr( $coca_rt_text_align ) : 'left'; ?>;
		cursor: auto;
	}

	body.rtl .coca-rtpb-plugin.reading-time,
	[dir=rlt] .coca-rtpb-plugin.reading-time {
		text-align: <?php echo ! empty( $coca_rt_text_align ) ? esc_attr( $coca_rt_text_align ) : 'right'; ?>;
	}

	.coca-rtpb-plugin.reading-time .reading-time-container {
		background-color: <?php echo esc_attr( $coca_rt_background_color_data ); ?>;
		color: <?php echo esc_attr( $coca_rt_text_color_data ); ?>;
		font-size: <?php echo ! empty( $coca_rt_text_size ) ? esc_attr( "{$coca_rt_text_size}px" ) : '15px'; ?>;
		font-weight: <?php echo esc_attr( $coca_rt_font_weight ); ?>;
		font-style: <?php echo esc_attr( $coca_rt_text_font_style ); ?>;
		line-height: <?php echo ! empty( $coca_rt_text_line_height ) ? esc_attr( $coca_rt_text_line_height ) : '1.5'; ?>;
		padding: 0.5em 0.7em;
		width: max-content;
		display: block;
		text-decoration: none;
		text-decoration-color: transparent;
		text-decoration-style: unset;
	}

	.coca-rtpb-plugin.reading-time .reading-time-container br,
	.coca-rtpb-plugin.reading-time .reading-time-container p {
		display: none;
	}

	.coca-rtpb-plugin.reading-time .reading-time-container .reading-time-text:after {
		content: attr(data-text)
	}

	<?php if ( isset( $coca_rtpb_settings['use_reading_time_custom_css'] ) && 'true' === $coca_rtpb_settings['use_reading_time_custom_css'] ) : ?>
		<?php echo wp_kses_post( $coca_rtpb_settings['reading_time_custom_css_data'] ); ?>
	<?php endif; ?>

	<?php echo wp_kses_post( apply_filters( 'coca_rtpb_plugin_reading_time_style', '' ) ); ?>
</style>
