<?php
/**
 * Template for creating the front-end of the reading time.
 *
 * @package CodeCanel\Reading_Time_Progress_Bar
 */

defined( 'ABSPATH' ) || exit;

use COCA\Reading_Time_Progress_Bar\Plugin;
use function COCA\Reading_Time_Progress_Bar\coca_calculate_reading_time as calculate_reading_time;

$coca_rtpb_settings           = wp_parse_args( Plugin::get_settings(), Plugin::get_initial_settings_data() );
$coca_rt_time_text_position   = isset( $coca_rtpb_settings['reading_time_text_position']['value'] ) ? $coca_rtpb_settings['reading_time_text_position']['value'] : '';
$coca_rt_time_prefix_text     = isset( $coca_rtpb_settings['reading_time_text_prefix'] ) ? $coca_rtpb_settings['reading_time_text_prefix'] : '';
$coca_rt_suffix_text_plural   = isset( $coca_rtpb_settings['reading_time_text_suffix'] ) ? $coca_rtpb_settings['reading_time_text_suffix'] : '';
$coca_rt_suffix_text_singular = isset( $coca_rtpb_settings['reading_time_text_suffix_singular'] ) ? $coca_rtpb_settings['reading_time_text_suffix_singular'] : '';

$coca_rt_reading_time = calculate_reading_time( get_the_ID(), $coca_rtpb_settings );
$coca_rt_suffix_text  = ( '< 1' === $coca_rt_reading_time || 2 > $coca_rt_reading_time ) ? $coca_rt_suffix_text_singular : $coca_rt_suffix_text_plural;

?>

<span class="coca-rtpb-plugin reading-time <?php echo esc_attr( $coca_rt_time_text_position ); ?>">
	<span class='reading-time-container'>
		<span data-text="<?php echo esc_attr( $coca_rt_time_prefix_text ); ?>" class="reading-time-text prefix-text"></span>
		<span data-text="<?php echo esc_attr( $coca_rt_reading_time ); ?>" class="reading-time-text time-text"></span>
		<span data-text="<?php echo esc_attr( $coca_rt_suffix_text ); ?>" class="reading-time-text suffix-text"></span>
	</span>
</span>
