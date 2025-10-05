<?php
/**
 * Fearless Living Child Theme Admin Initialize
 *
 * @package FearlessLiving\Admin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Initialize admin functionality
 */
class FearlessLiving_Admin_Init {

	/**
	 * The single instance of the class.
	 *
	 * @var FearlessLiving_Admin_Init
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
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		
		// Load Redux config on init if Redux is available
		add_action( 'init', array( $this, 'maybe_load_redux_config' ), 20 );
		
		// Load category colors system
		add_action( 'init', array( $this, 'load_category_colors' ), 25 );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		// Don't add menu if Redux will handle it
		if ( class_exists( 'Redux' ) ) {
			// Ensure Redux config is loaded
			$this->maybe_load_redux_config();
			
			// Check if Redux instance exists
			$opt_name = 'fearless_living_options';
			if ( Redux::instance( $opt_name ) ) {
				// Redux will handle the menu, don't add our own
				return;
			}
		}
		
		// Add fallback menu only if Redux isn't handling it
		$parent_slug = 'buddyboss-settings';
		
		// Check if BuddyBoss menu exists
		global $submenu;
		if ( ! isset( $submenu[ $parent_slug ] ) ) {
			$parent_slug = 'themes.php'; // Fallback to Appearance menu
		}

		add_submenu_page(
			$parent_slug,
			__( 'Fearless Living Options', 'buddyboss-theme-child' ),
			__( 'Fearless Living', 'buddyboss-theme-child' ),
			'manage_options',
			'fearless-living-options',
			array( $this, 'options_page_callback' )
		);
	}

	/**
	 * Options page callback (fallback if Redux not loaded)
	 */
	public function options_page_callback() {
		// Check if Redux is loaded
		if ( ! class_exists( 'Redux' ) ) {
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Fearless Living Theme Options', 'buddyboss-theme-child' ); ?></h1>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Redux Framework is not loaded. Please ensure the parent theme is active.', 'buddyboss-theme-child' ); ?></p>
				</div>
			</div>
			<?php
			return;
		}

		// Load the Redux config if not already loaded
		if ( ! class_exists( 'FearlessLiving_Redux_Framework_Config' ) ) {
			require_once get_stylesheet_directory() . '/inc/admin/options-init.php';
		}

		// Check if instance exists
		$opt_name = 'fearless_living_options';
		$redux_instance = Redux::instance( $opt_name );
		
		if ( ! $redux_instance ) {
			// Try to create the instance
			new FearlessLiving_Redux_Framework_Config();
			$redux_instance = Redux::instance( $opt_name );
		}

		if ( $redux_instance ) {
			// Render the Redux options page
			$redux_instance->_default_panel();
		} else {
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Fearless Living Theme Options', 'buddyboss-theme-child' ); ?></h1>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Unable to initialize theme options. Please check your configuration.', 'buddyboss-theme-child' ); ?></p>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Admin scripts
	 */
	public function admin_scripts( $hook ) {
		if ( strpos( $hook, 'fearless-living-options' ) !== false ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
		}
	}

	/**
	 * Maybe load Redux config
	 */
	public function maybe_load_redux_config() {
		if ( ! class_exists( 'Redux' ) ) {
			return;
		}

		if ( ! class_exists( 'FearlessLiving_Redux_Framework_Config' ) ) {
			require_once get_stylesheet_directory() . '/inc/admin/options-init.php';
		}
	}

	/**
	 * Load category colors system
	 */
	public function load_category_colors() {
		require_once get_stylesheet_directory() . '/inc/admin/category-colors.php';
	}

	/**
	 * Load Redux config (legacy method for compatibility)
	 */
	public function load_redux_config() {
		$this->maybe_load_redux_config();
	}
}

// Initialize
FearlessLiving_Admin_Init::instance();