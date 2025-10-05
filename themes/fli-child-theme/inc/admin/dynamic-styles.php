<?php
/**
 * Fearless Living Dynamic Styles
 *
 * @package FearlessLiving\Admin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Generate dynamic CSS based on theme options
 */
function fearless_living_generate_dynamic_css() {
	$css = '';
	
	// Get theme options
	$primary_color    = fearless_living_get_option( 'fl_primary_color', '#FF6B6B' );
	$secondary_color  = fearless_living_get_option( 'fl_secondary_color', '#4ECDC4' );
	$accent_color     = fearless_living_get_option( 'fl_accent_color', '#FFE66D' );
	$button_colors    = fearless_living_get_option( 'fl_button_colors', array(
		'regular' => '#FF6B6B',
		'hover'   => '#FF5252',
		'active'  => '#FF3838',
	) );
	$link_colors      = fearless_living_get_option( 'fl_link_colors', array(
		'regular' => '#4ECDC4',
		'hover'   => '#3DB5AC',
		'active'  => '#2C9E95',
	) );
	
	// Header colors
	$header_bg_color   = fearless_living_get_option( 'fl_header_bg_color', '#FFFFFF' );
	$header_text_color = fearless_living_get_option( 'fl_header_text_color', '#333333' );
	
	// Footer colors
	$footer_bg_color   = fearless_living_get_option( 'fl_footer_bg_color', '#222222' );
	$footer_text_color = fearless_living_get_option( 'fl_footer_text_color', '#FFFFFF' );
	$footer_link_color = fearless_living_get_option( 'fl_footer_link_color', array(
		'regular' => '#4ECDC4',
		'hover'   => '#6FD8D1',
		'active'  => '#3DB5AC',
	) );
	
	// Typography
	$body_font_size  = fearless_living_get_option( 'fl_body_font_size', 16 );
	$heading_color   = fearless_living_get_option( 'fl_heading_color', '#333333' );
	
	// CSS Variables (Modern approach)
	$css .= ':root {';
	$css .= '--fl-primary-color: ' . $primary_color . ';';
	$css .= '--fl-secondary-color: ' . $secondary_color . ';';
	$css .= '--fl-accent-color: ' . $accent_color . ';';
	$css .= '--fl-primary-color-dark: ' . fearless_living_darken_color( $primary_color, 10 ) . ';';
	$css .= '--fl-primary-color-light: ' . fearless_living_lighten_color( $primary_color, 10 ) . ';';
	$css .= '--fl-body-font-size: ' . $body_font_size . 'px;';
	$css .= '}';
	
	// Primary color overrides
	$css .= '
	/* Primary Color */
	.bb-primary-color,
	.primary-color,
	.site-header .site-title a:hover,
	.bb-header-buttons .button.header-button:hover,
	.bb-header-buttons .button.header-button:focus {
		color: ' . $primary_color . ' !important;
	}
	
	.bb-primary-bg,
	.primary-bg,
	.buddyboss-select-inner,
	.bb-header-buttons .button.header-button,
	.button.primary,
	input[type="submit"].primary,
	.pagination .current,
	.learndash-wrapper .ld-primary-background {
		background-color: ' . $primary_color . ' !important;
	}
	
	.bb-primary-border,
	.primary-border {
		border-color: ' . $primary_color . ' !important;
	}
	';
	
	// Secondary color
	$css .= '
	/* Secondary Color */
	.secondary-color {
		color: ' . $secondary_color . ';
	}
	
	.secondary-bg {
		background-color: ' . $secondary_color . ';
	}
	';
	
	// Accent color
	$css .= '
	/* Accent Color */
	.accent-color,
	.highlight {
		color: ' . $accent_color . ';
	}
	
	.accent-bg,
	.badge,
	.tag {
		background-color: ' . $accent_color . ';
		color: ' . fearless_living_get_contrast_color( $accent_color ) . ';
	}
	';
	
	// Links
	$css .= '
	/* Links */
	a {
		color: ' . $link_colors['regular'] . ';
	}
	
	a:hover {
		color: ' . $link_colors['hover'] . ';
	}
	
	a:active,
	a:focus {
		color: ' . $link_colors['active'] . ';
	}
	';
	
	// Buttons
	$css .= '
	/* Buttons */
	.button,
	button,
	input[type="submit"],
	input[type="button"],
	.btn,
	.learndash-wrapper .ld-button {
		background-color: ' . $button_colors['regular'] . ';
		border-color: ' . $button_colors['regular'] . ';
		color: ' . fearless_living_get_contrast_color( $button_colors['regular'] ) . ';
	}
	
	.button:hover,
	button:hover,
	input[type="submit"]:hover,
	input[type="button"]:hover,
	.btn:hover,
	.learndash-wrapper .ld-button:hover {
		background-color: ' . $button_colors['hover'] . ';
		border-color: ' . $button_colors['hover'] . ';
		color: ' . fearless_living_get_contrast_color( $button_colors['hover'] ) . ';
	}
	
	.button:active,
	button:active,
	input[type="submit"]:active,
	input[type="button"]:active,
	.btn:active,
	.learndash-wrapper .ld-button:active {
		background-color: ' . $button_colors['active'] . ';
		border-color: ' . $button_colors['active'] . ';
		color: ' . fearless_living_get_contrast_color( $button_colors['active'] ) . ';
	}
	';
	
	// Header
	$css .= '
	/* Header */
	.site-header,
	.bb-header,
	.header-wrapper {
		background-color: ' . $header_bg_color . ';
		color: ' . $header_text_color . ';
	}
	
	.site-header a,
	.bb-header a,
	.header-wrapper a {
		color: ' . $header_text_color . ';
	}
	';
	
	// Footer
	$css .= '
	/* Footer */
	.site-footer,
	.bb-footer,
	.footer-wrapper {
		background-color: ' . $footer_bg_color . ';
		color: ' . $footer_text_color . ';
	}
	
	.site-footer a,
	.bb-footer a,
	.footer-wrapper a {
		color: ' . $footer_link_color['regular'] . ';
	}
	
	.site-footer a:hover,
	.bb-footer a:hover,
	.footer-wrapper a:hover {
		color: ' . $footer_link_color['hover'] . ';
	}
	';
	
	// Typography
	$css .= '
	/* Typography */
	body {
		font-size: ' . $body_font_size . 'px;
	}
	
	h1, h2, h3, h4, h5, h6 {
		color: ' . $heading_color . ';
	}
	';
	
	// BuddyBoss specific overrides
	$css .= '
	/* BuddyBoss Overrides */
	.buddyboss-theme .header-search-link:before,
	.buddyboss-theme .header-messages-link:before,
	.buddyboss-theme .header-notifications-link:before {
		color: ' . $header_text_color . ';
	}
	
	.buddyboss-theme #buddypress .activity-list .activity-item .activity-content .activity-inner a,
	.buddyboss-theme #buddypress .groups-list li .item .item-desc a,
	.buddyboss-theme #buddypress .members-list li .item .item-desc a {
		color: ' . $link_colors['regular'] . ';
	}
	
	.buddyboss-theme #buddypress .activity-list .activity-item .activity-content .activity-inner a:hover,
	.buddyboss-theme #buddypress .groups-list li .item .item-desc a:hover,
	.buddyboss-theme #buddypress .members-list li .item .item-desc a:hover {
		color: ' . $link_colors['hover'] . ';
	}
	';
	
	// LearnDash specific overrides
	$css .= '
	/* LearnDash Overrides */
	.learndash-wrapper .ld-primary-color {
		color: ' . $primary_color . ' !important;
	}
	
	.learndash-wrapper .ld-primary-background,
	.learndash-wrapper .ld-progress-bar .ld-progress-bar-percentage {
		background-color: ' . $primary_color . ' !important;
	}
	
	.learndash-wrapper .ld-button.ld-button-transparent {
		color: ' . $primary_color . ';
		border-color: ' . $primary_color . ';
	}
	
	.learndash-wrapper .ld-button.ld-button-transparent:hover {
		background-color: ' . $primary_color . ';
		color: ' . fearless_living_get_contrast_color( $primary_color ) . ';
	}
	';
	
	// Custom CSS from options
	$custom_css = fearless_living_get_option( 'fl_custom_css', '' );
	if ( ! empty( $custom_css ) ) {
		$css .= "\n/* Custom CSS */\n" . $custom_css;
	}
	
	return $css;
}

/**
 * Output dynamic CSS
 */
function fearless_living_output_dynamic_css() {
	$css = fearless_living_generate_dynamic_css();
	
	if ( ! empty( $css ) ) {
		echo '<style type="text/css" id="fearless-living-dynamic-css">' . fearless_living_minify_css( $css ) . '</style>';
	}
}
add_action( 'wp_head', 'fearless_living_output_dynamic_css', 999 );

/**
 * Output custom JavaScript
 */
function fearless_living_output_custom_js() {
	$custom_js = fearless_living_get_option( 'fl_custom_js', '' );
	
	if ( ! empty( $custom_js ) ) {
		echo '<script type="text/javascript" id="fearless-living-custom-js">' . $custom_js . '</script>';
	}
}
add_action( 'wp_footer', 'fearless_living_output_custom_js', 999 );

/**
 * Minify CSS
 *
 * @param string $css CSS to minify.
 * @return string Minified CSS.
 */
function fearless_living_minify_css( $css ) {
	// Remove comments
	$css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );
	
	// Remove unnecessary whitespace
	$css = str_replace( array( "\r\n", "\r", "\n", "\t" ), '', $css );
	$css = preg_replace( '/\s+/', ' ', $css );
	$css = str_replace( array( ' {', '{ ' ), '{', $css );
	$css = str_replace( array( ' }', '} ' ), '}', $css );
	$css = str_replace( array( ' :', ': ' ), ':', $css );
	$css = str_replace( array( ' ;', '; ' ), ';', $css );
	$css = str_replace( array( ' ,', ', ' ), ',', $css );
	
	return trim( $css );
}

/**
 * Handle Redux compiler hook
 */
function fearless_living_redux_compiler_action( $options, $css, $changed_values ) {
	// Clear any caches if needed
	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}
}
add_action( 'redux/options/fearless_living_options/compiler', 'fearless_living_redux_compiler_action', 10, 3 );