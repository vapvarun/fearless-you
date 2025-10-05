<?php
/**
 * Fearless Living Theme Functions
 *
 * @package FearlessLiving\Admin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get theme option
 *
 * @param string $option Option name.
 * @param mixed  $default Default value.
 * @return mixed Option value.
 */
function fearless_living_get_option( $option, $default = false ) {
	$options = get_option( 'fearless_living_options' );
	
	if ( isset( $options[ $option ] ) ) {
		return $options[ $option ];
	}
	
	return $default;
}

/**
 * Echo theme option
 *
 * @param string $option Option name.
 * @param mixed  $default Default value.
 */
function fearless_living_option( $option, $default = false ) {
	echo fearless_living_get_option( $option, $default );
}

/**
 * Get color with opacity
 *
 * @param string $hex Hex color.
 * @param float  $alpha Opacity value.
 * @return string RGBA color.
 */
function fearless_living_hex_to_rgba( $hex, $alpha = 1 ) {
	$hex = str_replace( '#', '', $hex );
	
	if ( strlen( $hex ) == 3 ) {
		$r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
		$g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
		$b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
	} else {
		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );
	}
	
	return "rgba({$r}, {$g}, {$b}, {$alpha})";
}

/**
 * Darken color
 *
 * @param string $hex Hex color.
 * @param int    $percent Percentage to darken.
 * @return string Hex color.
 */
function fearless_living_darken_color( $hex, $percent ) {
	$hex = str_replace( '#', '', $hex );
	$rgb = array_map( 'hexdec', str_split( $hex, 2 ) );
	
	foreach ( $rgb as &$color ) {
		$color = round( $color * ( 100 - $percent ) / 100 );
		$color = dechex( $color );
		$color = str_pad( $color, 2, '0', STR_PAD_LEFT );
	}
	
	return '#' . implode( '', $rgb );
}

/**
 * Lighten color
 *
 * @param string $hex Hex color.
 * @param int    $percent Percentage to lighten.
 * @return string Hex color.
 */
function fearless_living_lighten_color( $hex, $percent ) {
	$hex = str_replace( '#', '', $hex );
	$rgb = array_map( 'hexdec', str_split( $hex, 2 ) );
	
	foreach ( $rgb as &$color ) {
		$color = round( $color + ( 255 - $color ) * $percent / 100 );
		$color = dechex( $color );
		$color = str_pad( $color, 2, '0', STR_PAD_LEFT );
	}
	
	return '#' . implode( '', $rgb );
}

/**
 * Check if color is light
 *
 * @param string $hex Hex color.
 * @return bool True if light, false if dark.
 */
function fearless_living_is_light_color( $hex ) {
	$hex = str_replace( '#', '', $hex );
	
	// Convert to RGB
	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );
	
	// Calculate luminance
	$luminance = ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) / 255;
	
	return $luminance > 0.5;
}

/**
 * Get contrasting color (black or white)
 *
 * @param string $hex Hex color.
 * @return string #000000 or #FFFFFF.
 */
function fearless_living_get_contrast_color( $hex ) {
	return fearless_living_is_light_color( $hex ) ? '#000000' : '#FFFFFF';
}