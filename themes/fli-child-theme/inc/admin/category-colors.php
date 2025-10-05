<?php
/**
 * Category Colors Dynamic CSS Generation
 *
 * @package FearlessLiving\Admin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Category Colors Manager
 */
class FearlessLiving_Category_Colors {

	/**
	 * The single instance of the class.
	 *
	 * @var FearlessLiving_Category_Colors
	 */
	protected static $_instance = null;

	/**
	 * Main Instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_head', array( $this, 'output_category_colors_css' ), 1 );
		add_action( 'admin_head', array( $this, 'output_category_colors_css' ), 1 );
		add_action( 'redux/options/fearless_living_options/saved', array( $this, 'clear_category_colors_cache' ) );
		add_action( 'redux/options/fearless_living_options/reset', array( $this, 'clear_category_colors_cache' ) );
	}

	/**
	 * Get category settings mapping (merged color, visibility, and assignments)
	 */
	public function get_category_settings_mapping() {
		// Check cache first
		$cache_key = 'fl_category_settings_mapping';
		$cached_mapping = get_transient( $cache_key );
		
		if ( $cached_mapping !== false ) {
			return $cached_mapping;
		}

		// Get Redux options
		$options = get_option( 'fearless_living_options', array() );
		
		// Get category settings from repeater field
		$category_settings = isset( $options['fl_category_settings'] ) ? $options['fl_category_settings'] : array();
		
		// Build settings mapping array
		$settings_mapping = array();
		foreach ( $category_settings as $setting ) {
			if ( isset( $setting['category_slug'] ) ) {
				$slug = $setting['category_slug'];
				$settings_mapping[ $slug ] = array(
					'color' => isset( $setting['category_color'] ) ? $setting['category_color'] : $this->get_default_category_color( $slug ),
					'visible' => isset( $setting['category_visible'] ) ? $setting['category_visible'] : true,
					'show_separator' => isset( $setting['category_show_separator'] ) ? $setting['category_show_separator'] : true,
					'show_badge' => isset( $setting['category_show_badge'] ) ? $setting['category_show_badge'] : true,
					'show_border' => isset( $setting['category_show_border'] ) ? $setting['category_show_border'] : true,
				);
			}
		}

		// Add default settings for any missing categories
		$default_categories = array( 'curriculum', 'love-notes', 'monthly-reflection', 'qa-calls', 'topics', 'uncategorized' );
		foreach ( $default_categories as $slug ) {
			if ( ! isset( $settings_mapping[ $slug ] ) ) {
				$settings_mapping[ $slug ] = array(
					'color' => $this->get_default_category_color( $slug ),
					'visible' => true,
					'show_separator' => true,
					'show_badge' => true,
					'show_border' => true,
				);
			}
		}

		// Cache for 1 hour
		set_transient( $cache_key, $settings_mapping, HOUR_IN_SECONDS );
		
		return $settings_mapping;
	}

	/**
	 * Get category color mapping (backward compatibility)
	 */
	public function get_category_color_mapping() {
		$settings = $this->get_category_settings_mapping();
		$color_mapping = array();
		
		foreach ( $settings as $slug => $setting ) {
			$color_mapping[ $slug ] = $setting['color'];
		}
		
		return $color_mapping;
	}

	/**
	 * Get default category color
	 */
	private function get_default_category_color( $slug ) {
		$defaults = array(
			'curriculum' => '#59898d',
			'love-notes' => '#ff69b4',
			'monthly-reflection' => '#bfc046',
			'qa-calls' => '#ff6b00',
			'topics' => '#009bde',
			'uncategorized' => '#7f868f',
		);

		return isset( $defaults[ $slug ] ) ? $defaults[ $slug ] : '#7f868f';
	}

	/**
	 * Get category color by slug
	 */
	public function get_category_color( $category_slug ) {
		$mapping = $this->get_category_color_mapping();
		return isset( $mapping[ $category_slug ] ) ? $mapping[ $category_slug ] : '#7f868f';
	}

	/**
	 * Get category settings by slug
	 */
	public function get_category_settings( $category_slug ) {
		$mapping = $this->get_category_settings_mapping();
		return isset( $mapping[ $category_slug ] ) ? $mapping[ $category_slug ] : array(
			'color' => '#7f868f',
			'visible' => true,
			'show_separator' => true,
			'show_badge' => true,
			'show_border' => true,
		);
	}

	/**
	 * Check if category is visible
	 */
	public function is_category_visible( $category_slug ) {
		$settings = $this->get_category_settings( $category_slug );
		return $settings['visible'];
	}

	/**
	 * Check if category should show separator
	 */
	public function should_show_separator( $category_slug ) {
		$settings = $this->get_category_settings( $category_slug );
		return $settings['show_separator'];
	}

	/**
	 * Check if category should show badge
	 */
	public function should_show_badge( $category_slug ) {
		$settings = $this->get_category_settings( $category_slug );
		return $settings['show_badge'];
	}

	/**
	 * Check if category should show border
	 */
	public function should_show_border( $category_slug ) {
		$settings = $this->get_category_settings( $category_slug );
		return $settings['show_border'];
	}

	/**
	 * Get category color by term ID
	 */
	public function get_category_color_by_id( $term_id ) {
		$term = get_term( $term_id );
		if ( is_wp_error( $term ) || ! $term ) {
			return '#7f868f';
		}
		return $this->get_category_color( $term->slug );
	}

	/**
	 * Output category colors CSS
	 */
	public function output_category_colors_css() {
		$settings_mapping = $this->get_category_settings_mapping();
		
		if ( empty( $settings_mapping ) ) {
			return;
		}

		echo '<style id="fl-category-colors">' . "\n";
		echo '/* Fearless Living Category Colors & Settings */' . "\n";
		
		// Generate CSS variables for each category
		echo ':root {' . "\n";
		foreach ( $settings_mapping as $slug => $settings ) {
			$color = $settings['color'];
			echo "  --fl-category-{$slug}-color: {$color};\n";
			echo "  --fl-category-{$slug}-color-rgb: " . $this->hex_to_rgb( $color ) . ";\n";
			echo "  --fl-category-{$slug}-visible: " . ( $settings['visible'] ? '1' : '0' ) . ";\n";
			echo "  --fl-category-{$slug}-show-separator: " . ( $settings['show_separator'] ? '1' : '0' ) . ";\n";
			echo "  --fl-category-{$slug}-show-badge: " . ( $settings['show_badge'] ? '1' : '0' ) . ";\n";
			echo "  --fl-category-{$slug}-show-border: " . ( $settings['show_border'] ? '1' : '0' ) . ";\n";
		}
		echo '}' . "\n\n";

		// Generate category-specific styles
		foreach ( $settings_mapping as $slug => $settings ) {
			$this->output_category_specific_css( $slug, $settings );
		}

		// Generate general category styles
		$this->output_general_category_css();
		
		// Generate global settings CSS
		$this->output_global_settings_css();
		
		echo '</style>' . "\n";
	}

	/**
	 * Output category-specific CSS
	 */
	private function output_category_specific_css( $slug, $settings ) {
		$color = $settings['color'];
		$rgb = $this->hex_to_rgb( $color );
		
		echo "/* {$slug} category styles */\n";
		
		// Hide category if not visible
		if ( ! $settings['visible'] ) {
			echo ".category-{$slug},\n";
			echo ".category-{$slug} * {\n";
			echo "  display: none !important;\n";
			echo "}\n\n";
			return;
		}

		// Category separators and dividers (only if enabled)
		if ( $settings['show_separator'] ) {
			echo ".category-{$slug} .category-separator,\n";
			echo ".category-{$slug} .category-divider,\n";
			echo ".category-{$slug} hr,\n";
			echo ".category-{$slug} .separator {\n";
			echo "  border-color: {$color} !important;\n";
			echo "  background-color: {$color} !important;\n";
			echo "}\n\n";
		} else {
			echo ".category-{$slug} .category-separator,\n";
			echo ".category-{$slug} .category-divider,\n";
			echo ".category-{$slug} hr,\n";
			echo ".category-{$slug} .separator {\n";
			echo "  display: none !important;\n";
			echo "}\n\n";
		}

		// Category badges and labels (only if enabled)
		if ( $settings['show_badge'] ) {
			echo ".category-{$slug} .category-badge,\n";
			echo ".category-{$slug} .category-label,\n";
			echo ".category-{$slug} .post-category,\n";
			echo ".category-{$slug} .entry-category {\n";
			echo "  background-color: {$color} !important;\n";
			echo "  color: " . $this->get_contrast_color( $color ) . " !important;\n";
			echo "}\n\n";
		} else {
			echo ".category-{$slug} .category-badge,\n";
			echo ".category-{$slug} .category-label,\n";
			echo ".category-{$slug} .post-category,\n";
			echo ".category-{$slug} .entry-category {\n";
			echo "  display: none !important;\n";
			echo "}\n\n";
		}

		// Category-specific borders and accents (only if enabled)
		if ( $settings['show_border'] ) {
			echo ".category-{$slug} .category-accent,\n";
			echo ".category-{$slug} .category-border,\n";
			echo ".category-{$slug} .entry-header,\n";
			echo ".category-{$slug} .post-header {\n";
			echo "  border-left-color: {$color} !important;\n";
			echo "  border-top-color: {$color} !important;\n";
			echo "}\n\n";
		} else {
			echo ".category-{$slug} .category-accent,\n";
			echo ".category-{$slug} .category-border {\n";
			echo "  border-left-color: transparent !important;\n";
			echo "  border-top-color: transparent !important;\n";
			echo "}\n\n";
		}

		// Category-specific buttons and links
		echo ".category-{$slug} .category-button,\n";
		echo ".category-{$slug} .category-link,\n";
		echo ".category-{$slug} a.category-link {\n";
		echo "  color: {$color} !important;\n";
		echo "}\n\n";

		echo ".category-{$slug} .category-button:hover,\n";
		echo ".category-{$slug} .category-link:hover,\n";
		echo ".category-{$slug} a.category-link:hover {\n";
		echo "  background-color: {$color} !important;\n";
		echo "  color: " . $this->get_contrast_color( $color ) . " !important;\n";
		echo "}\n\n";

		// Category archive page elements
		echo ".category-{$slug} .archive-header,\n";
		echo ".category-{$slug} .page-header,\n";
		echo ".category-{$slug} .archive-title {\n";
		echo "  color: {$color} !important;\n";
		echo "}\n\n";

		// Category-specific hover effects
		echo ".category-{$slug} .post-item:hover,\n";
		echo ".category-{$slug} .entry:hover {\n";
		echo "  border-color: {$color} !important;\n";
		echo "  box-shadow: 0 0 0 1px {$color} !important;\n";
		echo "}\n\n";
	}

	/**
	 * Output general category CSS
	 */
	private function output_general_category_css() {
		echo "/* General category styles */\n";
		
		// Category separators
		echo ".category-separator,\n";
		echo ".category-divider {\n";
		echo "  height: 2px;\n";
		echo "  background: linear-gradient(90deg, transparent, var(--fl-category-color, #7f868f), transparent);\n";
		echo "  border: none;\n";
		echo "  margin: 20px 0;\n";
		echo "}\n\n";

		// Category badges
		echo ".category-badge,\n";
		echo ".category-label {\n";
		echo "  display: inline-block;\n";
		echo "  padding: 4px 8px;\n";
		echo "  border-radius: 4px;\n";
		echo "  font-size: 12px;\n";
		echo "  font-weight: 600;\n";
		echo "  text-transform: uppercase;\n";
		echo "  letter-spacing: 0.5px;\n";
		echo "}\n\n";

		// Category accents
		echo ".category-accent {\n";
		echo "  border-left: 4px solid var(--fl-category-color, #7f868f);\n";
		echo "  padding-left: 15px;\n";
		echo "}\n\n";

		// Category buttons
		echo ".category-button {\n";
		echo "  display: inline-block;\n";
		echo "  padding: 8px 16px;\n";
		echo "  border: 2px solid var(--fl-category-color, #7f868f);\n";
		echo "  border-radius: 6px;\n";
		echo "  text-decoration: none;\n";
		echo "  transition: all 0.3s ease;\n";
		echo "}\n\n";

		echo ".category-button:hover {\n";
		echo "  background-color: var(--fl-category-color, #7f868f);\n";
		echo "  color: " . $this->get_contrast_color( '#7f868f' ) . ";\n";
		echo "}\n\n";
	}

	/**
	 * Convert hex color to RGB
	 */
	private function hex_to_rgb( $hex ) {
		$hex = str_replace( '#', '', $hex );
		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );
		return "{$r}, {$g}, {$b}";
	}

	/**
	 * Get contrast color (black or white) for given background
	 */
	private function get_contrast_color( $hex ) {
		$hex = str_replace( '#', '', $hex );
		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );
		
		// Calculate luminance
		$luminance = ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) / 255;
		
		return $luminance > 0.5 ? '#000000' : '#ffffff';
	}

	/**
	 * Output global settings CSS
	 */
	private function output_global_settings_css() {
		$options = get_option( 'fearless_living_options', array() );
		
		echo "/* Global category settings */\n";
		
		// Separator height
		$separator_height = isset( $options['fl_category_separator_height'] ) ? $options['fl_category_separator_height'] : 3;
		echo ".category-separator {\n";
		echo "  height: {$separator_height}px !important;\n";
		echo "}\n\n";
		
		// Badge style
		$badge_style = isset( $options['fl_category_badge_style'] ) ? $options['fl_category_badge_style'] : 'pill';
		$border_radius = 'pill' === $badge_style ? '20px' : ( 'rounded' === $badge_style ? '6px' : '0px' );
		echo ".category-badge,\n";
		echo ".category-label {\n";
		echo "  border-radius: {$border_radius} !important;\n";
		echo "}\n\n";
		
		// Border width
		$border_width = isset( $options['fl_category_border_width'] ) ? $options['fl_category_border_width'] : 4;
		echo ".category-accent,\n";
		echo ".category-border {\n";
		echo "  border-left-width: {$border_width}px !important;\n";
		echo "  border-top-width: {$border_width}px !important;\n";
		echo "}\n\n";
	}

	/**
	 * Clear category colors cache
	 */
	public function clear_category_colors_cache() {
		delete_transient( 'fl_category_colors_mapping' );
		delete_transient( 'fl_category_settings_mapping' );
	}

	/**
	 * Get category color for current post
	 */
	public function get_current_post_category_color() {
		if ( ! is_single() && ! is_page() ) {
			return '#7f868f';
		}

		$categories = get_the_category();
		if ( empty( $categories ) ) {
			return '#7f868f';
		}

		return $this->get_category_color( $categories[0]->slug );
	}

	/**
	 * Get category color for archive page
	 */
	public function get_archive_category_color() {
		if ( ! is_category() ) {
			return '#7f868f';
		}

		$category = get_queried_object();
		return $this->get_category_color( $category->slug );
	}
}

// Initialize
FearlessLiving_Category_Colors::instance();