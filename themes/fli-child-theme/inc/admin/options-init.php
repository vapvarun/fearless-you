<?php
/**
 * Fearless Living Child Theme Redux Framework Config
 *
 * @package FearlessLiving\Admin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'FearlessLiving_Redux_Framework_Config' ) ) {

	class FearlessLiving_Redux_Framework_Config {

		public $args = array();
		public $sections = array();
		public $theme;
		public $Redux;

		public function __construct() {
			if ( ! class_exists( 'Redux' ) ) {
				return;
			}

			// This is needed to run on admin_init to ensure menus are loaded
			add_action( 'admin_init', array( $this, 'initSettings' ), 10 );
		}

		public function initSettings() {
			// Set the default arguments
			$this->setArguments();

			// Set sections
			$this->setSections();

			if ( ! isset( $this->args['opt_name'] ) ) {
				return;
			}

			// Create the sections and fields
			$this->Redux = new Redux( $this->args, $this->sections );
		}

		/**
		 * Set Redux arguments
		 */
		public function setArguments() {
			$theme = wp_get_theme( 'buddyboss-theme-child' );

			$this->args = array(
				// Redux settings
				'opt_name'             => 'fearless_living_options',
				'display_name'         => $theme->get( 'Name' ),
				'display_version'      => $theme->get( 'Version' ),
				'menu_type'            => 'submenu',
				'allow_sub_menu'       => true,
				'menu_title'           => __( 'Fearless Living', 'buddyboss-theme-child' ),
				'page_title'           => __( 'Fearless Living Theme Options', 'buddyboss-theme-child' ),
				'google_api_key'       => '',
				'google_update_weekly' => false,
				'async_typography'     => false,
				'admin_bar'            => false,
				'admin_bar_icon'       => 'dashicons-admin-generic',
				'admin_bar_priority'   => 50,
				'global_variable'      => 'fearless_living_options',
				'dev_mode'             => false,
				'update_notice'        => false,
				'customizer'           => false,
				'page_parent'          => $this->buddyboss_menu_exists() ? 'buddyboss-settings' : 'themes.php',
				'page_slug'            => 'fearless-living-options',
				'save_defaults'        => true,
				'default_show'         => false,
				'default_mark'         => '',
				'show_import_export'   => true,
				'transient_time'       => 60 * MINUTE_IN_SECONDS,
				'output'               => true,
				'output_tag'           => true,
				'database'             => '',
				'use_cdn'              => true,
				'compiler'             => true,

				// Panel styling
				'intro_text'           => '<p>' . __( 'Customize your Fearless Living theme with these options.', 'buddyboss-theme-child' ) . '</p>',
				'footer_text'          => '<p>' . __( 'Fearless Living Theme Options', 'buddyboss-theme-child' ) . '</p>',

				// Icons
				'menu_icon'            => '',
				'class'                => '',
				'admin_theme'          => 'wp',
				'ajax_save'            => true,
				'show_options_object'  => false,
			);
		}

		/**
		 * Set Redux sections and fields
		 */
		public function setSections() {
			// Colors Section
			$this->sections[] = array(
				'title'  => __( 'Colors', 'buddyboss-theme-child' ),
				'id'     => 'fearless_colors',
				'desc'   => __( 'Customize the color scheme for your site.', 'buddyboss-theme-child' ),
				'icon'   => 'dashicons dashicons-admin-appearance',
				'fields' => array(
					array(
						'id'       => 'fl_primary_color',
						'type'     => 'color',
						'title'    => __( 'Primary Brand Color', 'buddyboss-theme-child' ),
						'subtitle' => __( 'This will override the parent theme primary color.', 'buddyboss-theme-child' ),
						'default'  => '#FF6B6B',
						'validate' => 'color',
						'compiler' => true,
					),
					array(
						'id'       => 'fl_secondary_color',
						'type'     => 'color',
						'title'    => __( 'Secondary Color', 'buddyboss-theme-child' ),
						'subtitle' => __( 'Used for accents and secondary elements.', 'buddyboss-theme-child' ),
						'default'  => '#4ECDC4',
						'validate' => 'color',
						'compiler' => true,
					),
					array(
						'id'       => 'fl_accent_color',
						'type'     => 'color',
						'title'    => __( 'Accent Color', 'buddyboss-theme-child' ),
						'subtitle' => __( 'Used for highlights and special elements.', 'buddyboss-theme-child' ),
						'default'  => '#FFE66D',
						'validate' => 'color',
						'compiler' => true,
					),
					array(
						'id'       => 'fl_button_colors',
						'type'     => 'link_color',
						'title'    => __( 'Primary Button Colors', 'buddyboss-theme-child' ),
						'subtitle' => __( 'Set colors for buttons in different states.', 'buddyboss-theme-child' ),
						'default'  => array(
							'regular' => '#FF6B6B',
							'hover'   => '#FF5252',
							'active'  => '#FF3838',
						),
						'compiler' => true,
					),
					array(
						'id'       => 'fl_link_colors',
						'type'     => 'link_color',
						'title'    => __( 'Link Colors', 'buddyboss-theme-child' ),
						'subtitle' => __( 'Set colors for links in different states.', 'buddyboss-theme-child' ),
						'default'  => array(
							'regular' => '#4ECDC4',
							'hover'   => '#3DB5AC',
							'active'  => '#2C9E95',
						),
						'compiler' => true,
					),
				),
			);

			// Header Section
			$this->sections[] = array(
				'title'  => __( 'Header', 'buddyboss-theme-child' ),
				'id'     => 'fearless_header',
				'desc'   => __( 'Customize header appearance.', 'buddyboss-theme-child' ),
				'icon'   => 'dashicons dashicons-schedule',
				'fields' => array(
					array(
						'id'       => 'fl_header_bg_color',
						'type'     => 'color',
						'title'    => __( 'Header Background Color', 'buddyboss-theme-child' ),
						'subtitle' => __( 'Set the background color for the header.', 'buddyboss-theme-child' ),
						'default'  => '#FFFFFF',
						'validate' => 'color',
						'compiler' => true,
					),
					array(
						'id'       => 'fl_header_text_color',
						'type'     => 'color',
						'title'    => __( 'Header Text Color', 'buddyboss-theme-child' ),
						'subtitle' => __( 'Set the text color for the header.', 'buddyboss-theme-child' ),
						'default'  => '#333333',
						'validate' => 'color',
						'compiler' => true,
					),
					array(
						'id'       => 'fl_sticky_header',
						'type'     => 'switch',
						'title'    => __( 'Sticky Header', 'buddyboss-theme-child' ),
						'subtitle' => __( 'Enable/disable sticky header on scroll.', 'buddyboss-theme-child' ),
						'default'  => true,
					),
				),
			);

			// Footer Section
			$this->sections[] = array(
				'title'  => __( 'Footer', 'buddyboss-theme-child' ),
				'id'     => 'fearless_footer',
				'desc'   => __( 'Customize footer appearance.', 'buddyboss-theme-child' ),
				'icon'   => 'dashicons dashicons-editor-insertmore',
				'fields' => array(
					array(
						'id'       => 'fl_footer_bg_color',
						'type'     => 'color',
						'title'    => __( 'Footer Background Color', 'buddyboss-theme-child' ),
						'subtitle' => __( 'Set the background color for the footer.', 'buddyboss-theme-child' ),
						'default'  => '#222222',
						'validate' => 'color',
						'compiler' => true,
					),
					array(
						'id'       => 'fl_footer_text_color',
						'type'     => 'color',
						'title'    => __( 'Footer Text Color', 'buddyboss-theme-child' ),
						'subtitle' => __( 'Set the text color for the footer.', 'buddyboss-theme-child' ),
						'default'  => '#FFFFFF',
						'validate' => 'color',
						'compiler' => true,
					),
					array(
						'id'       => 'fl_footer_link_color',
						'type'     => 'link_color',
						'title'    => __( 'Footer Link Colors', 'buddyboss-theme-child' ),
						'subtitle' => __( 'Set colors for footer links.', 'buddyboss-theme-child' ),
						'default'  => array(
							'regular' => '#4ECDC4',
							'hover'   => '#6FD8D1',
							'active'  => '#3DB5AC',
						),
						'compiler' => true,
					),
				),
			);

			// Typography Section
			$this->sections[] = array(
				'title'  => __( 'Typography', 'buddyboss-theme-child' ),
				'id'     => 'fearless_typography',
				'desc'   => __( 'Customize typography settings.', 'buddyboss-theme-child' ),
				'icon'   => 'dashicons dashicons-editor-textcolor',
				'fields' => array(
					array(
						'id'       => 'fl_body_font_size',
						'type'     => 'slider',
						'title'    => __( 'Base Font Size', 'buddyboss-theme-child' ),
						'subtitle' => __( 'Set the base font size in pixels.', 'buddyboss-theme-child' ),
						'default'  => 16,
						'min'      => 12,
						'max'      => 24,
						'step'     => 1,
						'display_value' => 'text',
					),
					array(
						'id'       => 'fl_heading_color',
						'type'     => 'color',
						'title'    => __( 'Heading Color', 'buddyboss-theme-child' ),
						'subtitle' => __( 'Set the color for all headings.', 'buddyboss-theme-child' ),
						'default'  => '#333333',
						'validate' => 'color',
						'compiler' => true,
					),
				),
			);

			// Category Management Section
			$this->sections[] = array(
				'title'  => __( 'Category Management', 'buddyboss-theme-child' ),
				'id'     => 'fearless_category_management',
				'desc'   => __( 'Manage category colors, visibility, and assignments for separation elements throughout the site.', 'buddyboss-theme-child' ),
				'icon'   => 'dashicons dashicons-tag',
				'fields' => array(
					array(
						'id'       => 'fl_category_settings',
						'type'     => 'repeater',
						'title'    => __( 'Category Settings', 'buddyboss-theme-child' ),
						'subtitle' => __( 'Configure color, visibility, and assignment for each category.', 'buddyboss-theme-child' ),
						'fields'   => array(
							array(
								'id'       => 'category_slug',
								'type'     => 'select',
								'title'    => __( 'Category', 'buddyboss-theme-child' ),
								'options'  => array(
									'curriculum' => 'Curriculum',
									'love-notes' => 'Love Notes from Rhonda',
									'monthly-reflection' => 'Monthly Reflection',
									'qa-calls' => 'QA Calls',
									'topics' => 'Topics',
									'uncategorized' => 'Uncategorized',
								),
							),
							array(
								'id'       => 'category_color',
								'type'     => 'color',
								'title'    => __( 'Color', 'buddyboss-theme-child' ),
								'subtitle' => __( 'Color for separations and accents.', 'buddyboss-theme-child' ),
								'validate' => 'color',
								'compiler' => true,
							),
							array(
								'id'       => 'category_visible',
								'type'     => 'switch',
								'title'    => __( 'Visible', 'buddyboss-theme-child' ),
								'subtitle' => __( 'Show this category in navigation and listings.', 'buddyboss-theme-child' ),
								'default'  => true,
							),
							array(
								'id'       => 'category_show_separator',
								'type'     => 'switch',
								'title'    => __( 'Show Separator', 'buddyboss-theme-child' ),
								'subtitle' => __( 'Display visual separator for this category.', 'buddyboss-theme-child' ),
								'default'  => true,
							),
							array(
								'id'       => 'category_show_badge',
								'type'     => 'switch',
								'title'    => __( 'Show Badge', 'buddyboss-theme-child' ),
								'subtitle' => __( 'Display category badge/label.', 'buddyboss-theme-child' ),
								'default'  => true,
							),
							array(
								'id'       => 'category_show_border',
								'type'     => 'switch',
								'title'    => __( 'Show Border Accent', 'buddyboss-theme-child' ),
								'subtitle' => __( 'Display colored border accent.', 'buddyboss-theme-child' ),
								'default'  => true,
							),
						),
						'default' => array(
							array(
								'category_slug' => 'curriculum',
								'category_color' => '#59898d',
								'category_visible' => true,
								'category_show_separator' => true,
								'category_show_badge' => true,
								'category_show_border' => true,
							),
							array(
								'category_slug' => 'love-notes',
								'category_color' => '#ff69b4',
								'category_visible' => true,
								'category_show_separator' => true,
								'category_show_badge' => true,
								'category_show_border' => true,
							),
							array(
								'category_slug' => 'monthly-reflection',
								'category_color' => '#bfc046',
								'category_visible' => true,
								'category_show_separator' => true,
								'category_show_badge' => true,
								'category_show_border' => true,
							),
							array(
								'category_slug' => 'qa-calls',
								'category_color' => '#ff6b00',
								'category_visible' => true,
								'category_show_separator' => true,
								'category_show_badge' => true,
								'category_show_border' => true,
							),
							array(
								'category_slug' => 'topics',
								'category_color' => '#009bde',
								'category_visible' => true,
								'category_show_separator' => true,
								'category_show_badge' => true,
								'category_show_border' => true,
							),
							array(
								'category_slug' => 'uncategorized',
								'category_color' => '#7f868f',
								'category_visible' => false,
								'category_show_separator' => false,
								'category_show_badge' => false,
								'category_show_border' => false,
							),
						),
					),
					array(
						'id'       => 'fl_category_global_settings',
						'type'     => 'section',
						'title'    => __( 'Global Category Settings', 'buddyboss-theme-child' ),
						'indent'   => true,
					),
					array(
						'id'       => 'fl_category_separator_height',
						'type'     => 'slider',
						'title'    => __( 'Separator Height', 'buddyboss-theme-child' ),
						'subtitle' => __( 'Height of category separators in pixels.', 'buddyboss-theme-child' ),
						'default'  => 3,
						'min'      => 1,
						'max'      => 10,
						'step'     => 1,
						'display_value' => 'text',
					),
					array(
						'id'       => 'fl_category_badge_style',
						'type'     => 'select',
						'title'    => __( 'Badge Style', 'buddyboss-theme-child' ),
						'subtitle' => __( 'Visual style for category badges.', 'buddyboss-theme-child' ),
						'options'  => array(
							'rounded' => 'Rounded',
							'square' => 'Square',
							'pill' => 'Pill',
						),
						'default'  => 'pill',
					),
					array(
						'id'       => 'fl_category_border_width',
						'type'     => 'slider',
						'title'    => __( 'Border Width', 'buddyboss-theme-child' ),
						'subtitle' => __( 'Width of category border accents in pixels.', 'buddyboss-theme-child' ),
						'default'  => 4,
						'min'      => 1,
						'max'      => 10,
						'step'     => 1,
						'display_value' => 'text',
					),
					array(
						'id'       => 'fl_category_color_usage',
						'type'     => 'info',
						'title'    => __( 'Category Color Usage', 'buddyboss-theme-child' ),
						'desc'     => __( 'These settings control:<br>• Category separators and dividers<br>• Category badges and labels<br>• Category-specific borders and accents<br>• Category archive page elements<br>• Category visibility in navigation<br>• Category-specific buttons and links', 'buddyboss-theme-child' ),
						'style'    => 'info',
					),
				),
			);

			// Advanced Section
			$this->sections[] = array(
				'title'  => __( 'Advanced', 'buddyboss-theme-child' ),
				'id'     => 'fearless_advanced',
				'desc'   => __( 'Advanced theme settings.', 'buddyboss-theme-child' ),
				'icon'   => 'dashicons dashicons-admin-tools',
				'fields' => array(
					array(
						'id'       => 'fl_custom_css',
						'type'     => 'textarea',
						'title'    => __( 'Custom CSS', 'buddyboss-theme-child' ),
						'subtitle' => __( 'Add your custom CSS code here.', 'buddyboss-theme-child' ),
						'default'  => '',
						'rows'     => 20,
					),
					array(
						'id'       => 'fl_custom_js',
						'type'     => 'textarea',
						'title'    => __( 'Custom JavaScript', 'buddyboss-theme-child' ),
						'subtitle' => __( 'Add your custom JavaScript code here.', 'buddyboss-theme-child' ),
						'default'  => '',
						'rows'     => 20,
					),
				),
			);
		}

		/**
		 * Check if BuddyBoss menu exists
		 */
		private function buddyboss_menu_exists() {
			// First check if BuddyBoss Platform plugin is active
			if ( function_exists( 'buddypress' ) && isset( buddypress()->buddyboss ) ) {
				return true;
			}
			
			// Then check the actual menu
			global $menu;
			if ( ! is_array( $menu ) ) {
				return false;
			}
			
			foreach ( $menu as $item ) {
				if ( isset( $item[2] ) && $item[2] === 'buddyboss-settings' ) {
					return true;
				}
			}
			
			return false;
		}
	}

	// Initialize Redux config
	new FearlessLiving_Redux_Framework_Config();
}