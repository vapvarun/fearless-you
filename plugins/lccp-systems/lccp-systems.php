<?php
/**
 * Plugin Name: LCCP Systems
 * Plugin URI: https://fearlessliving.org
 * Description: Life Coach Certification Program systems for Fearless Living Institute - Provides hour tracking, dashboards, and management tools for the certification program.
 * Version: 1.0.0
 * Author: Fearless Living Institute
 * Author URI: https://fearlessliving.org
 * Text Domain: lccp-systems
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'LCCP_SYSTEMS_VERSION', '1.0.0' );
define( 'LCCP_SYSTEMS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LCCP_SYSTEMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LCCP_SYSTEMS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Include the module manager class
require_once LCCP_SYSTEMS_PLUGIN_DIR . 'includes/class-module-manager.php';

class LCCP_Systems {

	private static $instance = null;
	private $module_manager  = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Initialize module manager
		$this->module_manager = LCCP_Module_Manager::get_instance();

		// Core initialization
		add_action( 'init', array( $this, 'init' ) );

		// Admin initialization
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_link' ) );
			add_action( 'admin_notices', array( $this, 'check_dependencies' ) );
		}

		// Activation/Deactivation hooks
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// Load text domain
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// AJAX handlers
		add_action( 'wp_ajax_lccp_toggle_module', array( $this, 'handle_toggle_module' ) );
	}

	public function init() {
		// Initialize core functionality that doesn't depend on modules
	}

	public function check_dependencies() {
		$missing_deps = array();

		// Check for LearnDash (optional but recommended)
		if ( ! class_exists( 'SFWD_LMS' ) ) {
			$missing_deps[] = 'LearnDash LMS';
		}

		// Check for BuddyBoss (optional)
		if ( ! defined( 'BP_PLATFORM_VERSION' ) ) {
			$missing_deps[] = 'BuddyBoss Platform';
		}

		if ( ! empty( $missing_deps ) ) {
			$class   = 'notice notice-warning is-dismissible';
			$message = sprintf(
				__( 'LCCP Systems works best with the following plugins: %s. Some features may be limited without them.', 'lccp-systems' ),
				implode( ', ', $missing_deps )
			);
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}
	}

	public function add_admin_menu() {
		// Use a custom icon or dashicon
		$icon_url = LCCP_SYSTEMS_PLUGIN_URL . 'assets/images/lccp-icon.png';
		if ( ! file_exists( LCCP_SYSTEMS_PLUGIN_DIR . 'assets/images/lccp-icon.png' ) ) {
			$icon_url = 'dashicons-welcome-learn-more';
		}

		add_menu_page(
			__( 'LCCP Systems', 'lccp-systems' ),
			__( 'LCCP Systems', 'lccp-systems' ),
			'manage_options',
			'lccp-systems',
			array( $this, 'render_settings_page' ),
			$icon_url,
			30
		);
	}

	public function register_settings() {
		// Register ALL module enable/disable settings
		register_setting( 'lccp_systems_settings', 'lccp_module_hour_tracker' );
		register_setting( 'lccp_systems_settings', 'lccp_module_hour_tracker_advanced' );
		register_setting( 'lccp_systems_settings', 'lccp_module_dashboards' );
		register_setting( 'lccp_systems_settings', 'lccp_module_roles' );
		register_setting( 'lccp_systems_settings', 'lccp_module_performance' );
		register_setting( 'lccp_systems_settings', 'lccp_module_performance_advanced' );
		register_setting( 'lccp_systems_settings', 'lccp_module_advanced_checklist' );
		register_setting( 'lccp_systems_settings', 'lccp_module_learndash_advanced' );
		register_setting( 'lccp_systems_settings', 'lccp_module_events_integration' );
		register_setting( 'lccp_systems_settings', 'lccp_module_accessibility' );
		register_setting( 'lccp_systems_settings', 'lccp_module_autologin' );

		// Register Hour Tracker settings
		register_setting( 'lccp_systems_settings', 'lccp_hour_tracker_required' );
		register_setting( 'lccp_systems_settings', 'lccp_hour_tracker_self_report' );
		register_setting( 'lccp_systems_settings', 'lccp_hour_tracker_notifications' );
		register_setting( 'lccp_systems_settings', 'lccp_hour_tracker_session_types' );
		register_setting( 'lccp_systems_settings', 'lccp_hour_tracker_tier_levels' );
		register_setting( 'lccp_systems_settings', 'lccp_hour_tracker_tier_names' );
		register_setting( 'lccp_systems_settings', 'lccp_hour_tracker_email_template' );
		register_setting( 'lccp_systems_settings', 'lccp_hour_tracker_export_formats' );
		register_setting( 'lccp_systems_settings', 'lccp_hour_tracker_min_duration' );
		register_setting( 'lccp_systems_settings', 'lccp_hour_tracker_max_duration' );
		register_setting( 'lccp_systems_settings', 'lccp_hour_tracker_approval_required' );
		register_setting( 'lccp_systems_settings', 'lccp_hour_tracker_reminder_days' );

		// Register Dashboard settings
		register_setting( 'lccp_systems_settings', 'lccp_dash_progress' );
		register_setting( 'lccp_systems_settings', 'lccp_dash_hours' );
		register_setting( 'lccp_systems_settings', 'lccp_dash_assignments' );
		register_setting( 'lccp_systems_settings', 'lccp_dash_messages' );
		register_setting( 'lccp_systems_settings', 'lccp_mentor_submissions' );
		register_setting( 'lccp_systems_settings', 'lccp_mentor_stats' );
		register_setting( 'lccp_systems_settings', 'lccp_mentor_schedule' );
		register_setting( 'lccp_systems_settings', 'lccp_dash_widget_layout' );
		register_setting( 'lccp_systems_settings', 'lccp_dash_color_scheme' );
		register_setting( 'lccp_systems_settings', 'lccp_dash_refresh_interval' );

		// Register Role/Tag mapping settings
		register_setting( 'lccp_systems_settings', 'lccp_role_tag_fearless_you' );
		register_setting( 'lccp_systems_settings', 'lccp_role_tag_lccp_student' );
		register_setting( 'lccp_systems_settings', 'lccp_role_tag_mentor' );
		register_setting( 'lccp_systems_settings', 'lccp_role_tag_bigbird' );
		register_setting( 'lccp_systems_settings', 'lccp_role_tag_pc' );

		// Register Performance settings
		register_setting( 'lccp_systems_settings', 'lccp_cache_duration' );
		register_setting( 'lccp_systems_settings', 'lccp_optimize_db' );
		register_setting( 'lccp_systems_settings', 'lccp_minify_assets' );
		register_setting( 'lccp_systems_settings', 'lccp_lazy_load' );

		// Register Checklist settings
		register_setting( 'lccp_systems_settings', 'lccp_checklist_autosave' );
		register_setting( 'lccp_systems_settings', 'lccp_checklist_certificate' );

		// Register LearnDash settings
		register_setting( 'lccp_systems_settings', 'lccp_ld_auto_enroll_mentor' );
		register_setting( 'lccp_systems_settings', 'lccp_ld_auto_enroll_bigbird' );
		register_setting( 'lccp_systems_settings', 'lccp_ld_category_slug' );
		register_setting( 'lccp_systems_settings', 'lccp_ld_bypass_prerequisites' );
		register_setting( 'lccp_systems_settings', 'lccp_ld_compatibility_fixes' );

		// Register Events settings
		register_setting( 'lccp_systems_settings', 'lccp_events_virtual' );
		register_setting( 'lccp_systems_settings', 'lccp_events_blocks' );

		// Register Accessibility settings
		register_setting( 'lccp_systems_settings', 'lccp_access_font_controls' );
		register_setting( 'lccp_systems_settings', 'lccp_access_high_contrast' );
		register_setting( 'lccp_systems_settings', 'lccp_access_screen_reader' );

		// Register Auto Login settings
		register_setting( 'lccp_systems_settings', 'lccp_autologin_duration' );
		register_setting( 'lccp_systems_settings', 'lccp_autologin_ip_check' );

		// Register General settings
		register_setting( 'lccp_systems_settings', 'lccp_system_email' );
		register_setting( 'lccp_systems_settings', 'lccp_debug_mode' );
		register_setting( 'lccp_systems_settings', 'lccp_data_retention' );
	}

	public function render_settings_page() {
		$modules          = $this->get_available_modules();
		$wp_fusion_active = class_exists( 'WP_Fusion' );
		?>
		<div class="lccp-admin-page">
			<div class="lccp-admin-header">
				<h1><?php _e( 'LCCP Systems - Fearless Living Institute', 'lccp-systems' ); ?></h1>
				<p class="description"><?php _e( 'Manage your Life Coach Certification Program modules and settings.', 'lccp-systems' ); ?></p>
			</div>
			
			<div class="lccp-wrap">
				<!-- System Overview Section -->
				<div class="lccp-system-overview">
					<h2><?php _e( 'System Overview', 'lccp-systems' ); ?></h2>
					<div class="lccp-stats-grid">
						<?php
						global $wpdb;
						$hour_table     = $wpdb->prefix . 'lccp_hour_tracker';
						$total_hours    = $wpdb->get_var( "SELECT SUM(session_length) FROM $hour_table" );
						$total_students = $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM $hour_table" );
						$active_modules = 0;
						foreach ( $modules as $module ) {
							if ( get_option( $module['id'], 'off' ) === 'on' ) {
								++$active_modules;
							}
						}
						?>
						<div class="stat-card">
							<div class="stat-value"><?php echo $active_modules; ?>/<?php echo count( $modules ); ?></div>
							<div class="stat-label"><?php _e( 'Active Modules', 'lccp-systems' ); ?></div>
						</div>
						<div class="stat-card">
							<div class="stat-value"><?php echo number_format( $total_hours ?: 0, 1 ); ?></div>
							<div class="stat-label"><?php _e( 'Total Hours Tracked', 'lccp-systems' ); ?></div>
						</div>
						<div class="stat-card">
							<div class="stat-value"><?php echo intval( $total_students ?: 0 ); ?></div>
							<div class="stat-label"><?php _e( 'Active Students', 'lccp-systems' ); ?></div>
						</div>
						<div class="stat-card">
							<div class="stat-value"><?php echo count( get_users( array( 'role' => 'lccp_mentor' ) ) ); ?></div>
							<div class="stat-label"><?php _e( 'Mentors', 'lccp-systems' ); ?></div>
						</div>
					</div>
				</div>
				
				<!-- Quick Actions Bar -->
				<div class="lccp-quick-actions">
					<h3><?php _e( 'Quick Actions', 'lccp-systems' ); ?></h3>
					<div class="button-group">
						<a href="<?php echo admin_url( 'users.php?page=lccp-hour-tracker' ); ?>" class="button">
							<span class="dashicons dashicons-clock"></span> <?php _e( 'View Hour Submissions', 'lccp-systems' ); ?>
						</a>
						<a href="<?php echo admin_url( 'admin.php?page=lccp-systems&action=export_data' ); ?>" class="button">
							<span class="dashicons dashicons-download"></span> <?php _e( 'Export Data', 'lccp-systems' ); ?>
						</a>
						<button type="button" class="button" onclick="if(confirm('Clear all caches?')) location.href='<?php echo wp_nonce_url( admin_url( 'admin.php?page=lccp-systems&action=clear_cache' ), 'lccp_clear_cache' ); ?>'">
							<span class="dashicons dashicons-trash"></span> <?php _e( 'Clear Cache', 'lccp-systems' ); ?>
						</button>
						<a href="<?php echo admin_url( 'admin.php?page=lccp-systems&action=run_diagnostics' ); ?>" class="button">
							<span class="dashicons dashicons-admin-tools"></span> <?php _e( 'Run Diagnostics', 'lccp-systems' ); ?>
						</a>
					</div>
				</div>
				
				<form method="post" action="options.php">
					<?php settings_fields( 'lccp_systems_settings' ); ?>
					
					<div class="lccp-accordion">
						
						<!-- Module Management Section -->
						<div class="lccp-accordion-item">
							<div class="lccp-accordion-header">
								<label class="lccp-module-toggle">
									<input type="checkbox" checked disabled />
									<span class="lccp-toggle-label"><?php _e( 'Module Management', 'lccp-systems' ); ?></span>
								</label>
								<span class="lccp-accordion-icon dashicons dashicons-arrow-down"></span>
							</div>
							<div class="lccp-accordion-content">
								<p class="description"><?php _e( 'Enable or disable individual modules. Some modules may have dependencies that need to be enabled first.', 'lccp-systems' ); ?></p>
								
								<div class="lccp-modules-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin-top: 20px;">
									<?php foreach ( $modules as $module ) : ?>
									<div class="module-toggle-card" style="border: 1px solid #ddd; padding: 15px; border-radius: 4px; background: white;">
										<label style="display: flex; align-items: start; cursor: pointer;">
											<input type="checkbox" name="<?php echo esc_attr( $module['id'] ); ?>" value="on" 
													<?php checked( get_option( $module['id'], 'off' ), 'on' ); ?> 
													style="margin-right: 10px; margin-top: 2px;" />
											<div>
												<strong style="display: block; margin-bottom: 5px;"><?php echo esc_html( $module['name'] ); ?></strong>
												<span style="color: #666; font-size: 13px;"><?php echo esc_html( $module['description'] ); ?></span>
												<?php if ( ! empty( $module['requires'] ) ) : ?>
												<div style="margin-top: 5px;">
													<small style="color: #999;">
														<?php _e( 'Requires:', 'lccp-systems' ); ?> 
														<?php echo implode( ', ', $module['requires'] ); ?>
													</small>
												</div>
												<?php endif; ?>
											</div>
										</label>
									</div>
									<?php endforeach; ?>
								</div>
								
								<div style="margin-top: 20px; padding: 15px; background: #f0f0f0; border-radius: 4px;">
									<h4 style="margin-top: 0;"><?php _e( 'Bulk Actions', 'lccp-systems' ); ?></h4>
									<button type="button" class="button" onclick="toggleAllModules(true)">
										<?php _e( 'Enable All Modules', 'lccp-systems' ); ?>
									</button>
									<button type="button" class="button" onclick="toggleAllModules(false)">
										<?php _e( 'Disable All Modules', 'lccp-systems' ); ?>
									</button>
								</div>
							</div>
						</div>
						
						<!-- Hour Tracker Module -->
						<div class="lccp-accordion-item">
							<div class="lccp-accordion-header">
								<label class="lccp-module-toggle">
									<input type="checkbox" name="lccp_module_hour_tracker" value="on" 
											<?php checked( get_option( 'lccp_module_hour_tracker', 'off' ), 'on' ); ?> />
									<span class="lccp-toggle-label"><?php _e( 'Hour Tracker', 'lccp-systems' ); ?></span>
								</label>
								<span class="lccp-accordion-icon dashicons dashicons-arrow-down"></span>
							</div>
							<div class="lccp-accordion-content">
								<p class="description"><?php _e( 'Track student coaching hours and progress toward certification levels.', 'lccp-systems' ); ?></p>
								
								<h4><?php _e( 'Basic Settings', 'lccp-systems' ); ?></h4>
								<table class="form-table">
									<tr>
										<th scope="row"><?php _e( 'Required Hours for Certification', 'lccp-systems' ); ?></th>
										<td>
											<input type="number" name="lccp_hour_tracker_required" value="<?php echo get_option( 'lccp_hour_tracker_required', 75 ); ?>" min="1" />
											<p class="description"><?php _e( 'Minimum hours required for basic certification', 'lccp-systems' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php _e( 'Session Duration Limits', 'lccp-systems' ); ?></th>
										<td>
											<label><?php _e( 'Minimum:', 'lccp-systems' ); ?> 
												<input type="number" name="lccp_hour_tracker_min_duration" value="<?php echo get_option( 'lccp_hour_tracker_min_duration', 0.25 ); ?>" min="0" step="0.25" style="width: 80px;" /> hours
											</label><br>
											<label><?php _e( 'Maximum:', 'lccp-systems' ); ?> 
												<input type="number" name="lccp_hour_tracker_max_duration" value="<?php echo get_option( 'lccp_hour_tracker_max_duration', 10 ); ?>" min="1" max="24" style="width: 80px;" /> hours
											</label>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php _e( 'Approval Settings', 'lccp-systems' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="lccp_hour_tracker_self_report" value="1" 
														<?php checked( get_option( 'lccp_hour_tracker_self_report', 1 ), 1 ); ?> />
												<?php _e( 'Allow self-reporting without mentor approval', 'lccp-systems' ); ?>
											</label><br>
											<label>
												<input type="checkbox" name="lccp_hour_tracker_approval_required" value="1" 
														<?php checked( get_option( 'lccp_hour_tracker_approval_required', 0 ), 1 ); ?> />
												<?php _e( 'Require mentor approval for all submissions', 'lccp-systems' ); ?>
											</label>
										</td>
									</tr>
								</table>
								
								<h4><?php _e( 'Certification Tiers', 'lccp-systems' ); ?></h4>
								<table class="form-table">
									<tr>
										<th scope="row"><?php _e( 'Tier Levels Configuration', 'lccp-systems' ); ?></th>
										<td>
											<?php
											$default_tiers = array(
												array(
													'hours' => 75,
													'name' => 'Certified Fearless Living Coach',
													'abbr' => 'CFLC',
												),
												array(
													'hours' => 150,
													'name' => 'Advanced Certified Fearless Living Coach',
													'abbr' => 'ACFLC',
												),
												array(
													'hours' => 250,
													'name' => 'Certified Fearless Trainer',
													'abbr' => 'CFT',
												),
												array(
													'hours' => 500,
													'name' => 'Master Certified Fearless Living Coach',
													'abbr' => 'MCFLC',
												),
											);
											$tiers         = get_option( 'lccp_hour_tracker_tier_levels', $default_tiers );
											?>
											<div id="tier-levels-config">
												<?php foreach ( $tiers as $index => $tier ) : ?>
												<div class="tier-level-row" style="margin-bottom: 15px; padding: 10px; background: #f0f0f0; border-radius: 4px;">
													<label><?php printf( __( 'Tier %d:', 'lccp-systems' ), $index + 1 ); ?></label><br>
													<input type="number" name="lccp_hour_tracker_tier_levels[<?php echo $index; ?>][hours]" 
															value="<?php echo esc_attr( $tier['hours'] ); ?>" min="1" style="width: 80px;" /> hours = 
													<input type="text" name="lccp_hour_tracker_tier_levels[<?php echo $index; ?>][name]" 
															value="<?php echo esc_attr( $tier['name'] ); ?>" style="width: 300px;" placeholder="Full Name" />
													(<input type="text" name="lccp_hour_tracker_tier_levels[<?php echo $index; ?>][abbr]" 
															value="<?php echo esc_attr( $tier['abbr'] ); ?>" style="width: 80px;" placeholder="Abbreviation" />)
												</div>
												<?php endforeach; ?>
											</div>
											<button type="button" class="button" onclick="addTierLevel()"><?php _e( 'Add Tier Level', 'lccp-systems' ); ?></button>
										</td>
									</tr>
								</table>
								
								<h4><?php _e( 'Session Types', 'lccp-systems' ); ?></h4>
								<table class="form-table">
									<tr>
										<th scope="row"><?php _e( 'Available Session Types', 'lccp-systems' ); ?></th>
										<td>
											<?php
											$default_types = array( 'Individual Client', 'Group Session', 'Practice Session', 'Mentor Coaching' );
											$session_types = get_option( 'lccp_hour_tracker_session_types', $default_types );
											?>
											<div id="session-types-config">
												<?php foreach ( $session_types as $index => $type ) : ?>
												<div class="session-type-row" style="margin-bottom: 5px;">
													<input type="text" name="lccp_hour_tracker_session_types[]" 
															value="<?php echo esc_attr( $type ); ?>" style="width: 250px;" />
													<button type="button" class="button-link" onclick="this.parentElement.remove()"><?php _e( 'Remove', 'lccp-systems' ); ?></button>
												</div>
												<?php endforeach; ?>
											</div>
											<button type="button" class="button" onclick="addSessionType()"><?php _e( 'Add Session Type', 'lccp-systems' ); ?></button>
										</td>
									</tr>
								</table>
								
								<h4><?php _e( 'Notifications', 'lccp-systems' ); ?></h4>
								<table class="form-table">
									<tr>
										<th scope="row"><?php _e( 'Email Notifications', 'lccp-systems' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="lccp_hour_tracker_notifications" value="1" 
														<?php checked( get_option( 'lccp_hour_tracker_notifications', 1 ), 1 ); ?> />
												<?php _e( 'Send email when hours are submitted', 'lccp-systems' ); ?>
											</label>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php _e( 'Reminder Settings', 'lccp-systems' ); ?></th>
										<td>
											<label><?php _e( 'Send reminder after', 'lccp-systems' ); ?> 
												<input type="number" name="lccp_hour_tracker_reminder_days" 
														value="<?php echo get_option( 'lccp_hour_tracker_reminder_days', 7 ); ?>" min="0" style="width: 60px;" />
												<?php _e( 'days of inactivity', 'lccp-systems' ); ?>
											</label>
											<p class="description"><?php _e( 'Set to 0 to disable reminders', 'lccp-systems' ); ?></p>
										</td>
									</tr>
								</table>
							</div>
						</div>
						
						<!-- Dashboards Module -->
						<div class="lccp-accordion-item">
							<div class="lccp-accordion-header">
								<label class="lccp-module-toggle">
									<input type="checkbox" name="lccp_module_dashboards" value="on" 
											<?php checked( get_option( 'lccp_module_dashboards', 'off' ), 'on' ); ?> />
									<span class="lccp-toggle-label"><?php _e( 'Custom Dashboards', 'lccp-systems' ); ?></span>
								</label>
								<span class="lccp-accordion-icon dashicons dashicons-arrow-down"></span>
							</div>
							<div class="lccp-accordion-content">
								<p class="description"><?php _e( 'Role-based dashboards with custom widgets for different user types.', 'lccp-systems' ); ?></p>
								
								<h4><?php _e( 'Student Dashboard Widgets', 'lccp-systems' ); ?></h4>
								<table class="form-table">
									<tr>
										<th scope="row"><?php _e( 'Widgets', 'lccp-systems' ); ?></th>
										<td>
											<label><input type="checkbox" name="lccp_dash_progress" value="1" 
													<?php checked( get_option( 'lccp_dash_progress', 1 ), 1 ); ?> /> 
													<?php _e( 'Course Progress', 'lccp-systems' ); ?></label><br>
											<label><input type="checkbox" name="lccp_dash_hours" value="1" 
													<?php checked( get_option( 'lccp_dash_hours', 1 ), 1 ); ?> /> 
													<?php _e( 'Hours Tracking', 'lccp-systems' ); ?></label><br>
											<label><input type="checkbox" name="lccp_dash_assignments" value="1" 
													<?php checked( get_option( 'lccp_dash_assignments', 1 ), 1 ); ?> /> 
													<?php _e( 'Assignments', 'lccp-systems' ); ?></label><br>
											<label><input type="checkbox" name="lccp_dash_messages" value="1" 
													<?php checked( get_option( 'lccp_dash_messages', 1 ), 1 ); ?> /> 
													<?php _e( 'Messages', 'lccp-systems' ); ?></label>
										</td>
									</tr>
								</table>
								
								<h4><?php _e( 'Mentor Dashboard Widgets', 'lccp-systems' ); ?></h4>
								<table class="form-table">
									<tr>
										<th scope="row"><?php _e( 'Widgets', 'lccp-systems' ); ?></th>
										<td>
											<label><input type="checkbox" name="lccp_mentor_submissions" value="1" 
													<?php checked( get_option( 'lccp_mentor_submissions', 1 ), 1 ); ?> /> 
													<?php _e( 'Student Submissions', 'lccp-systems' ); ?></label><br>
											<label><input type="checkbox" name="lccp_mentor_stats" value="1" 
													<?php checked( get_option( 'lccp_mentor_stats', 1 ), 1 ); ?> /> 
													<?php _e( 'Student Statistics', 'lccp-systems' ); ?></label><br>
											<label><input type="checkbox" name="lccp_mentor_schedule" value="1" 
													<?php checked( get_option( 'lccp_mentor_schedule', 1 ), 1 ); ?> /> 
													<?php _e( 'Coaching Schedule', 'lccp-systems' ); ?></label>
										</td>
									</tr>
								</table>
							</div>
						</div>
						
						<!-- Roles & Permissions Module -->
						<div class="lccp-accordion-item">
							<div class="lccp-accordion-header">
								<label class="lccp-module-toggle">
									<input type="checkbox" name="lccp_module_roles" value="on" 
											<?php checked( get_option( 'lccp_module_roles', 'off' ), 'on' ); ?> />
									<span class="lccp-toggle-label"><?php _e( 'Roles & Permissions', 'lccp-systems' ); ?></span>
								</label>
								<span class="lccp-accordion-icon dashicons dashicons-arrow-down"></span>
							</div>
							<div class="lccp-accordion-content">
								<p class="description"><?php _e( 'Manage user roles and map them to WP Fusion tags.', 'lccp-systems' ); ?></p>
								
								<?php if ( $wp_fusion_active ) : ?>
									<?php
									$available_tags = wp_fusion()->settings->get_available_tags_flat();
									?>
									
									<h4><?php _e( 'WP Fusion Tag Mapping', 'lccp-systems' ); ?></h4>
									<p class="description"><?php _e( 'Map WP Fusion tags to user roles. Users with these tags will automatically be assigned the corresponding role.', 'lccp-systems' ); ?></p>
									
									<table class="form-table">
										<tr>
											<th scope="row"><?php _e( 'Fearless You Member', 'lccp-systems' ); ?></th>
											<td>
												<select name="lccp_role_tag_fearless_you[]" multiple class="lccp-tag-select">
													<?php
													$selected = get_option( 'lccp_role_tag_fearless_you', array() );
													foreach ( $available_tags as $tag_id => $tag_name ) :
														?>
														<option value="<?php echo esc_attr( $tag_id ); ?>" 
																<?php selected( in_array( $tag_id, (array) $selected ) ); ?>>
															<?php echo esc_html( $tag_name ); ?>
														</option>
													<?php endforeach; ?>
												</select>
												<p class="description"><?php _e( 'Premium membership with full access', 'lccp-systems' ); ?></p>
											</td>
										</tr>
										<tr>
											<th scope="row"><?php _e( 'Program Candidate', 'lccp-systems' ); ?></th>
											<td>
												<select name="lccp_role_tag_lccp_student[]" multiple class="lccp-tag-select">
													<?php
													$selected = get_option( 'lccp_role_tag_lccp_student', array() );
													foreach ( $available_tags as $tag_id => $tag_name ) :
														?>
														<option value="<?php echo esc_attr( $tag_id ); ?>" 
																<?php selected( in_array( $tag_id, (array) $selected ) ); ?>>
															<?php echo esc_html( $tag_name ); ?>
														</option>
													<?php endforeach; ?>
												</select>
												<p class="description"><?php _e( 'Enrolled in certification program', 'lccp-systems' ); ?></p>
											</td>
										</tr>
										<tr>
											<th scope="row"><?php _e( 'LCCP Mentor', 'lccp-systems' ); ?></th>
											<td>
												<select name="lccp_role_tag_mentor[]" multiple class="lccp-tag-select">
													<?php
													$selected = get_option( 'lccp_role_tag_mentor', array() );
													foreach ( $available_tags as $tag_id => $tag_name ) :
														?>
														<option value="<?php echo esc_attr( $tag_id ); ?>" 
																<?php selected( in_array( $tag_id, (array) $selected ) ); ?>>
															<?php echo esc_html( $tag_name ); ?>
														</option>
													<?php endforeach; ?>
												</select>
												<p class="description"><?php _e( 'Can review student submissions', 'lccp-systems' ); ?></p>
											</td>
										</tr>
										<tr>
											<th scope="row"><?php _e( 'Big Bird', 'lccp-systems' ); ?></th>
											<td>
												<select name="lccp_role_tag_bigbird[]" multiple class="lccp-tag-select">
													<?php
													$selected = get_option( 'lccp_role_tag_bigbird', array() );
													foreach ( $available_tags as $tag_id => $tag_name ) :
														?>
														<option value="<?php echo esc_attr( $tag_id ); ?>" 
																<?php selected( in_array( $tag_id, (array) $selected ) ); ?>>
															<?php echo esc_html( $tag_name ); ?>
														</option>
													<?php endforeach; ?>
												</select>
												<p class="description"><?php _e( 'Manages Program Candidates', 'lccp-systems' ); ?></p>
											</td>
										</tr>
										<tr>
											<th scope="row"><?php _e( 'Program Candidate (PC)', 'lccp-systems' ); ?></th>
											<td>
												<select name="lccp_role_tag_pc[]" multiple class="lccp-tag-select">
													<?php
													$selected = get_option( 'lccp_role_tag_pc', array() );
													foreach ( $available_tags as $tag_id => $tag_name ) :
														?>
														<option value="<?php echo esc_attr( $tag_id ); ?>" 
																<?php selected( in_array( $tag_id, (array) $selected ) ); ?>>
															<?php echo esc_html( $tag_name ); ?>
														</option>
													<?php endforeach; ?>
												</select>
												<p class="description"><?php _e( 'Works with students', 'lccp-systems' ); ?></p>
											</td>
										</tr>
									</table>
								<?php else : ?>
									<div class="notice notice-warning inline">
										<p><?php _e( 'WP Fusion is not active. Install and activate WP Fusion to enable tag-based role mapping.', 'lccp-systems' ); ?></p>
									</div>
								<?php endif; ?>
							</div>
						</div>
						
						<!-- Performance Module -->
						<div class="lccp-accordion-item">
							<div class="lccp-accordion-header">
								<label class="lccp-module-toggle">
									<input type="checkbox" name="lccp_module_performance" value="on" 
											<?php checked( get_option( 'lccp_module_performance', 'off' ), 'on' ); ?> />
									<span class="lccp-toggle-label"><?php _e( 'Performance Optimization', 'lccp-systems' ); ?></span>
								</label>
								<span class="lccp-accordion-icon dashicons dashicons-arrow-down"></span>
							</div>
							<div class="lccp-accordion-content">
								<p class="description"><?php _e( 'Optimize site performance with caching and database improvements.', 'lccp-systems' ); ?></p>
								<table class="form-table">
									<tr>
										<th scope="row"><?php _e( 'Cache Duration', 'lccp-systems' ); ?></th>
										<td>
											<input type="number" name="lccp_cache_duration" value="<?php echo get_option( 'lccp_cache_duration', 3600 ); ?>" />
											<span><?php _e( 'seconds', 'lccp-systems' ); ?></span>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php _e( 'Optimizations', 'lccp-systems' ); ?></th>
										<td>
											<label><input type="checkbox" name="lccp_optimize_db" value="1" 
													<?php checked( get_option( 'lccp_optimize_db', 1 ), 1 ); ?> /> 
													<?php _e( 'Database optimization', 'lccp-systems' ); ?></label><br>
											<label><input type="checkbox" name="lccp_minify_assets" value="1" 
													<?php checked( get_option( 'lccp_minify_assets', 0 ), 1 ); ?> /> 
													<?php _e( 'Minify CSS and JavaScript', 'lccp-systems' ); ?></label><br>
											<label><input type="checkbox" name="lccp_lazy_load" value="1" 
													<?php checked( get_option( 'lccp_lazy_load', 1 ), 1 ); ?> /> 
													<?php _e( 'Lazy load images', 'lccp-systems' ); ?></label>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php _e( 'Quick Actions', 'lccp-systems' ); ?></th>
										<td>
											<button type="button" class="button" onclick="if(confirm('Clear all caches?')) location.href='<?php echo wp_nonce_url( admin_url( 'admin.php?page=lccp-systems&action=clear_cache' ), 'lccp_clear_cache' ); ?>'">
												<?php _e( 'Clear All Caches', 'lccp-systems' ); ?>
											</button>
											<button type="button" class="button" onclick="if(confirm('Optimize database?')) location.href='<?php echo wp_nonce_url( admin_url( 'admin.php?page=lccp-systems&action=optimize_db' ), 'lccp_optimize_db' ); ?>'">
												<?php _e( 'Optimize Database', 'lccp-systems' ); ?>
											</button>
										</td>
									</tr>
								</table>
							</div>
						</div>
						
						<!-- Checklists Module -->
						<div class="lccp-accordion-item">
							<div class="lccp-accordion-header">
								<label class="lccp-module-toggle">
									<input type="checkbox" name="lccp_module_advanced_checklist" value="on" 
											<?php checked( get_option( 'lccp_module_advanced_checklist', 'off' ), 'on' ); ?> />
									<span class="lccp-toggle-label"><?php _e( 'Checklists', 'lccp-systems' ); ?></span>
								</label>
								<span class="lccp-accordion-icon dashicons dashicons-arrow-down"></span>
							</div>
							<div class="lccp-accordion-content">
								<p class="description"><?php _e( 'Interactive checklists with progress tracking, categories, and completion certificates.', 'lccp-systems' ); ?></p>
								<table class="form-table">
									<tr>
										<th scope="row"><?php _e( 'Auto-Save Progress', 'lccp-systems' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="lccp_checklist_autosave" value="1" 
														<?php checked( get_option( 'lccp_checklist_autosave', 1 ), 1 ); ?> />
												<?php _e( 'Automatically save checklist progress', 'lccp-systems' ); ?>
											</label>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php _e( 'Show Completion Certificate', 'lccp-systems' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="lccp_checklist_certificate" value="1" 
														<?php checked( get_option( 'lccp_checklist_certificate', 1 ), 1 ); ?> />
												<?php _e( 'Generate certificate on completion', 'lccp-systems' ); ?>
											</label>
										</td>
									</tr>
								</table>
							</div>
						</div>
						
						<!-- LearnDash Integration Module -->
						<div class="lccp-accordion-item">
							<div class="lccp-accordion-header">
								<label class="lccp-module-toggle">
									<input type="checkbox" name="lccp_module_learndash_advanced" value="on" 
											<?php checked( get_option( 'lccp_module_learndash_advanced', 'off' ), 'on' ); ?> />
									<span class="lccp-toggle-label"><?php _e( 'LearnDash Features', 'lccp-systems' ); ?></span>
								</label>
								<span class="lccp-accordion-icon dashicons dashicons-arrow-down"></span>
							</div>
							<div class="lccp-accordion-content">
								<p class="description"><?php _e( 'LCCP-specific LearnDash enhancements for course access, auto-enrollment, and compatibility.', 'lccp-systems' ); ?></p>
								<table class="form-table">
									<tr>
										<th scope="row"><?php _e( 'Auto-Enroll Roles', 'lccp-systems' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="lccp_ld_auto_enroll_mentor" value="1" 
														<?php checked( get_option( 'lccp_ld_auto_enroll_mentor', 1 ), 1 ); ?> />
												<?php _e( 'Auto-enroll Mentors in LCCP courses', 'lccp-systems' ); ?>
											</label><br>
											<label>
												<input type="checkbox" name="lccp_ld_auto_enroll_bigbird" value="1" 
														<?php checked( get_option( 'lccp_ld_auto_enroll_bigbird', 1 ), 1 ); ?> />
												<?php _e( 'Auto-enroll Big Birds in LCCP courses', 'lccp-systems' ); ?>
											</label>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php _e( 'LCCP Category Slug', 'lccp-systems' ); ?></th>
										<td>
											<input type="text" name="lccp_ld_category_slug" 
													value="<?php echo get_option( 'lccp_ld_category_slug', 'lccp' ); ?>" />
											<p class="description"><?php _e( 'Course category slug for LCCP courses', 'lccp-systems' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php _e( 'Access Control', 'lccp-systems' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="lccp_ld_bypass_prerequisites" value="1" 
														<?php checked( get_option( 'lccp_ld_bypass_prerequisites', 0 ), 1 ); ?> />
												<?php _e( 'Allow Mentors to bypass course prerequisites', 'lccp-systems' ); ?>
											</label><br>
											<label>
												<input type="checkbox" name="lccp_ld_compatibility_fixes" value="1" 
														<?php checked( get_option( 'lccp_ld_compatibility_fixes', 1 ), 1 ); ?> />
												<?php _e( 'Enable BuddyBoss/LearnDash compatibility fixes', 'lccp-systems' ); ?>
											</label>
										</td>
									</tr>
								</table>
							</div>
						</div>
						
						<!-- Events Integration Module -->
						<div class="lccp-accordion-item">
							<div class="lccp-accordion-header">
								<label class="lccp-module-toggle">
									<input type="checkbox" name="lccp_module_events_integration" value="on" 
											<?php checked( get_option( 'lccp_module_events_integration', 'off' ), 'on' ); ?> />
									<span class="lccp-toggle-label"><?php _e( 'Events Integration', 'lccp-systems' ); ?></span>
								</label>
								<span class="lccp-accordion-icon dashicons dashicons-arrow-down"></span>
							</div>
							<div class="lccp-accordion-content">
								<p class="description"><?php _e( 'Consolidated events functionality including virtual events, blocks, and shortcodes.', 'lccp-systems' ); ?></p>
								<table class="form-table">
									<tr>
										<th scope="row"><?php _e( 'Virtual Events', 'lccp-systems' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="lccp_events_virtual" value="1" 
														<?php checked( get_option( 'lccp_events_virtual', 1 ), 1 ); ?> />
												<?php _e( 'Enable virtual event support', 'lccp-systems' ); ?>
											</label>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php _e( 'Event Blocks', 'lccp-systems' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="lccp_events_blocks" value="1" 
														<?php checked( get_option( 'lccp_events_blocks', 1 ), 1 ); ?> />
												<?php _e( 'Enable Gutenberg blocks for events', 'lccp-systems' ); ?>
											</label>
										</td>
									</tr>
								</table>
							</div>
						</div>
						
						<!-- Accessibility Module -->
						<div class="lccp-accordion-item">
							<div class="lccp-accordion-header">
								<label class="lccp-module-toggle">
									<input type="checkbox" name="lccp_module_accessibility" value="on" 
											<?php checked( get_option( 'lccp_module_accessibility', 'off' ), 'on' ); ?> />
									<span class="lccp-toggle-label"><?php _e( 'Accessibility Tools', 'lccp-systems' ); ?></span>
								</label>
								<span class="lccp-accordion-icon dashicons dashicons-arrow-down"></span>
							</div>
							<div class="lccp-accordion-content">
								<p class="description"><?php _e( 'Enhanced accessibility features including font sizing, high contrast mode, and screen reader optimizations.', 'lccp-systems' ); ?></p>
								<table class="form-table">
									<tr>
										<th scope="row"><?php _e( 'Font Size Controls', 'lccp-systems' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="lccp_access_font_controls" value="1" 
														<?php checked( get_option( 'lccp_access_font_controls', 1 ), 1 ); ?> />
												<?php _e( 'Show font size adjustment buttons', 'lccp-systems' ); ?>
											</label>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php _e( 'High Contrast Mode', 'lccp-systems' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="lccp_access_high_contrast" value="1" 
														<?php checked( get_option( 'lccp_access_high_contrast', 1 ), 1 ); ?> />
												<?php _e( 'Enable high contrast toggle', 'lccp-systems' ); ?>
											</label>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php _e( 'Screen Reader Optimization', 'lccp-systems' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="lccp_access_screen_reader" value="1" 
														<?php checked( get_option( 'lccp_access_screen_reader', 1 ), 1 ); ?> />
												<?php _e( 'Optimize for screen readers', 'lccp-systems' ); ?>
											</label>
										</td>
									</tr>
								</table>
							</div>
						</div>
						
						<!-- Auto Login Module -->
						<div class="lccp-accordion-item">
							<div class="lccp-accordion-header">
								<label class="lccp-module-toggle">
									<input type="checkbox" name="lccp_module_autologin" value="on" 
											<?php checked( get_option( 'lccp_module_autologin', 'off' ), 'on' ); ?> />
									<span class="lccp-toggle-label"><?php _e( 'Auto Login System', 'lccp-systems' ); ?></span>
								</label>
								<span class="lccp-accordion-icon dashicons dashicons-arrow-down"></span>
							</div>
							<div class="lccp-accordion-content">
								<p class="description"><?php _e( 'Secure auto-login functionality for members with customizable expiration and security options.', 'lccp-systems' ); ?></p>
								<table class="form-table">
									<tr>
										<th scope="row"><?php _e( 'Auto-Login Duration', 'lccp-systems' ); ?></th>
										<td>
											<input type="number" name="lccp_autologin_duration" value="<?php echo get_option( 'lccp_autologin_duration', 30 ); ?>" />
											<span><?php _e( 'days', 'lccp-systems' ); ?></span>
											<p class="description"><?php _e( 'How long auto-login links remain valid', 'lccp-systems' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php _e( 'IP Restriction', 'lccp-systems' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="lccp_autologin_ip_check" value="1" 
														<?php checked( get_option( 'lccp_autologin_ip_check', 0 ), 1 ); ?> />
												<?php _e( 'Restrict auto-login to same IP address', 'lccp-systems' ); ?>
											</label>
										</td>
									</tr>
								</table>
							</div>
						</div>
						
						<!-- General Settings -->
						<div class="lccp-accordion-item">
							<div class="lccp-accordion-header">
								<label class="lccp-module-toggle">
									<input type="checkbox" checked disabled />
									<span class="lccp-toggle-label"><?php _e( 'General Settings', 'lccp-systems' ); ?></span>
								</label>
								<span class="lccp-accordion-icon dashicons dashicons-arrow-down"></span>
							</div>
							<div class="lccp-accordion-content">
								<p class="description"><?php _e( 'Core system settings that apply globally.', 'lccp-systems' ); ?></p>
								<table class="form-table">
									<tr>
										<th scope="row"><?php _e( 'System Email', 'lccp-systems' ); ?></th>
										<td>
											<input type="email" name="lccp_system_email" 
													value="<?php echo get_option( 'lccp_system_email', get_option( 'admin_email' ) ); ?>" 
													class="regular-text" />
										</td>
									</tr>
									<tr>
										<th scope="row"><?php _e( 'Debug Mode', 'lccp-systems' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="lccp_debug_mode" value="1" 
														<?php checked( get_option( 'lccp_debug_mode', 0 ), 1 ); ?> />
												<?php _e( 'Enable debug logging', 'lccp-systems' ); ?>
											</label>
										</td>
									</tr>
								</table>
							</div>
						</div>
						
						<!-- Import/Export Settings -->
						<div class="lccp-accordion-item">
							<div class="lccp-accordion-header">
								<label class="lccp-module-toggle">
									<input type="checkbox" checked disabled />
									<span class="lccp-toggle-label"><?php _e( 'Import/Export Settings', 'lccp-systems' ); ?></span>
								</label>
								<span class="lccp-accordion-icon dashicons dashicons-arrow-down"></span>
							</div>
							<div class="lccp-accordion-content">
								<p class="description"><?php _e( 'Backup or transfer your LCCP Systems configuration.', 'lccp-systems' ); ?></p>
								
								<h4><?php _e( 'Export Settings', 'lccp-systems' ); ?></h4>
								<p><?php _e( 'Export all LCCP Systems settings to a JSON file for backup or migration.', 'lccp-systems' ); ?></p>
								<button type="button" class="button button-primary" onclick="exportLCCPSettings()">
									<span class="dashicons dashicons-download"></span> <?php _e( 'Export Settings', 'lccp-systems' ); ?>
								</button>
								
								<h4 style="margin-top: 30px;"><?php _e( 'Import Settings', 'lccp-systems' ); ?></h4>
								<p><?php _e( 'Import previously exported LCCP Systems settings from a JSON file.', 'lccp-systems' ); ?></p>
								<input type="file" id="lccp-import-file" accept=".json" style="margin-bottom: 10px;" />
								<br>
								<button type="button" class="button" onclick="importLCCPSettings()">
									<span class="dashicons dashicons-upload"></span> <?php _e( 'Import Settings', 'lccp-systems' ); ?>
								</button>
								
								<h4 style="margin-top: 30px;"><?php _e( 'Export Data', 'lccp-systems' ); ?></h4>
								<p><?php _e( 'Export hour tracking data and student information.', 'lccp-systems' ); ?></p>
								<select id="export-format" style="margin-right: 10px;">
									<option value="csv"><?php _e( 'CSV Format', 'lccp-systems' ); ?></option>
									<option value="excel"><?php _e( 'Excel Format', 'lccp-systems' ); ?></option>
									<option value="json"><?php _e( 'JSON Format', 'lccp-systems' ); ?></option>
								</select>
								<button type="button" class="button" onclick="exportLCCPData()">
									<span class="dashicons dashicons-database-export"></span> <?php _e( 'Export Data', 'lccp-systems' ); ?>
								</button>
							</div>
						</div>
						
						<!-- System Information -->
						<div class="lccp-accordion-item">
							<div class="lccp-accordion-header">
								<label class="lccp-module-toggle">
									<input type="checkbox" checked disabled />
									<span class="lccp-toggle-label"><?php _e( 'System Information', 'lccp-systems' ); ?></span>
								</label>
								<span class="lccp-accordion-icon dashicons dashicons-arrow-down"></span>
							</div>
							<div class="lccp-accordion-content">
								<p class="description"><?php _e( 'Technical information about your LCCP Systems installation.', 'lccp-systems' ); ?></p>
								
								<div style="background: #f5f5f5; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 12px;">
									<?php
									global $wpdb, $wp_version;
									$theme = wp_get_theme();
									?>
									<strong>LCCP Systems Version:</strong> <?php echo LCCP_SYSTEMS_VERSION; ?><br>
									<strong>WordPress Version:</strong> <?php echo $wp_version; ?><br>
									<strong>PHP Version:</strong> <?php echo phpversion(); ?><br>
									<strong>MySQL Version:</strong> <?php echo $wpdb->db_version(); ?><br>
									<strong>Active Theme:</strong> <?php echo $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ); ?><br>
									<strong>Site URL:</strong> <?php echo get_site_url(); ?><br>
									<strong>WordPress URL:</strong> <?php echo get_home_url(); ?><br>
									<strong>Memory Limit:</strong> <?php echo WP_MEMORY_LIMIT; ?><br>
									<strong>Max Upload Size:</strong> <?php echo size_format( wp_max_upload_size() ); ?><br>
									<strong>Active Plugins:</strong> <?php echo count( get_option( 'active_plugins' ) ); ?><br>
									<strong>Database Prefix:</strong> <?php echo $wpdb->prefix; ?><br>
									
									<h4 style="margin-top: 15px;"><?php _e( 'LCCP Tables Status', 'lccp-systems' ); ?></h4>
									<?php
									$tables = array(
										'lccp_hour_tracker' => 'Hour Tracker',
										'lccp_assignments' => 'Mentor Assignments',
									);
									foreach ( $tables as $table => $name ) {
										$table_name = $wpdb->prefix . $table;
										$exists     = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;
										$status     = $exists ? '✓ Exists' : '✗ Missing';
										$count      = $exists ? $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" ) : 0;
										echo "<strong>$name:</strong> $status";
										if ( $exists ) {
											echo " ($count records)";
										}
										echo '<br>';
									}
									?>
									
									<h4 style="margin-top: 15px;"><?php _e( 'Required Plugins Status', 'lccp-systems' ); ?></h4>
									<strong>LearnDash:</strong> <?php echo class_exists( 'SFWD_LMS' ) ? '✓ Active' : '✗ Not Active'; ?><br>
									<strong>BuddyBoss:</strong> <?php echo defined( 'BP_PLATFORM_VERSION' ) ? '✓ Active' : '✗ Not Active'; ?><br>
									<strong>WP Fusion:</strong> <?php echo class_exists( 'WP_Fusion' ) ? '✓ Active' : '✗ Not Active'; ?><br>
								</div>
								
								<div style="margin-top: 20px;">
									<button type="button" class="button" onclick="copySystemInfo()">
										<span class="dashicons dashicons-clipboard"></span> <?php _e( 'Copy System Info', 'lccp-systems' ); ?>
									</button>
									<button type="button" class="button" onclick="downloadSystemInfo()">
										<span class="dashicons dashicons-download"></span> <?php _e( 'Download System Info', 'lccp-systems' ); ?>
									</button>
								</div>
							</div>
						</div>
						
					</div>
					
					<div class="lccp-submit-section">
						<?php submit_button( __( 'Save All Settings', 'lccp-systems' ), 'primary large' ); ?>
					</div>
				</form>
				
				<div class="lccp-footer">
					<p>&copy; <?php echo date( 'Y' ); ?> Fearless Living Institute. All rights reserved.</p>
					<p><a href="https://fearlessliving.org" target="_blank">Visit Fearless Living</a> | 
						<a href="mailto:support@fearlessliving.org">Get Support</a></p>
				</div>
			</div>
		</div>
		
		<style>
			.lccp-admin-page {
				max-width: 1200px;
				margin: 20px 0;
			}
			.lccp-admin-header {
				background: linear-gradient(135deg, #59898D 0%, #042247 100%);
				color: white;
				padding: 30px;
				border-radius: 8px;
				margin-bottom: 30px;
			}
			.lccp-admin-header h1 {
				color: white;
				margin: 0 0 10px 0;
				font-size: 28px;
			}
			.lccp-admin-header .description {
				color: rgba(255,255,255,0.9);
				font-size: 16px;
				margin: 0;
			}
			
			/* System Overview Styles */
			.lccp-system-overview {
				background: white;
				padding: 20px;
				border-radius: 8px;
				margin-bottom: 20px;
				box-shadow: 0 1px 3px rgba(0,0,0,0.1);
			}
			.lccp-system-overview h2 {
				margin-top: 0;
				color: #042247;
				border-bottom: 2px solid #59898D;
				padding-bottom: 10px;
			}
			.lccp-stats-grid {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
				gap: 20px;
				margin-top: 20px;
			}
			.stat-card {
				background: linear-gradient(135deg, #f7f7f7 0%, #fff 100%);
				padding: 20px;
				border-radius: 8px;
				text-align: center;
				border: 1px solid #e0e0e0;
				transition: transform 0.2s, box-shadow 0.2s;
			}
			.stat-card:hover {
				transform: translateY(-2px);
				box-shadow: 0 4px 8px rgba(0,0,0,0.1);
			}
			.stat-value {
				font-size: 32px;
				font-weight: bold;
				color: #59898D;
				margin-bottom: 5px;
			}
			.stat-label {
				font-size: 14px;
				color: #666;
				text-transform: uppercase;
				letter-spacing: 0.5px;
			}
			
			/* Quick Actions Styles */
			.lccp-quick-actions {
				background: white;
				padding: 20px;
				border-radius: 8px;
				margin-bottom: 20px;
				box-shadow: 0 1px 3px rgba(0,0,0,0.1);
			}
			.lccp-quick-actions h3 {
				margin-top: 0;
				color: #042247;
			}
			.lccp-quick-actions .button-group {
				display: flex;
				flex-wrap: wrap;
				gap: 10px;
			}
			.lccp-quick-actions .button {
				display: inline-flex;
				align-items: center;
				gap: 5px;
			}
			.lccp-quick-actions .button .dashicons {
				font-size: 16px;
				width: 16px;
				height: 16px;
			}
			
			/* Accordion Styles */
			.lccp-accordion {
				margin: 20px 0;
			}
			.lccp-accordion-item {
				background: white;
				border: 1px solid #ddd;
				margin-bottom: 10px;
				border-radius: 4px;
				overflow: hidden;
			}
			.lccp-accordion-header {
				padding: 15px 20px;
				background: #f7f7f7;
				cursor: pointer;
				display: flex;
				justify-content: space-between;
				align-items: center;
				transition: background 0.3s;
			}
			.lccp-accordion-header:hover {
				background: #e8e8e8;
			}
			.lccp-accordion-header.active {
				background: #59898D;
				color: white;
			}
			.lccp-accordion-header.active .lccp-toggle-label {
				color: white;
			}
			.lccp-module-toggle {
				display: flex;
				align-items: center;
				font-size: 16px;
				font-weight: 600;
				cursor: pointer;
			}
			.lccp-module-toggle input[type="checkbox"] {
				margin-right: 10px;
			}
			.lccp-accordion-icon {
				transition: transform 0.3s;
			}
			.lccp-accordion-header.active .lccp-accordion-icon {
				transform: rotate(180deg);
				color: white;
			}
			.lccp-accordion-content {
				padding: 20px;
				display: none;
				background: #fafafa;
			}
			.lccp-accordion-content.active {
				display: block;
			}
			.lccp-accordion-content .description {
				margin-bottom: 20px;
				padding: 10px;
				background: white;
				border-left: 3px solid #59898D;
			}
			.lccp-accordion-content h4 {
				margin-top: 20px;
				margin-bottom: 10px;
				color: #333;
				border-bottom: 1px solid #ddd;
				padding-bottom: 5px;
			}
			.lccp-accordion-content .form-table {
				background: white;
				padding: 10px;
				border-radius: 4px;
			}
			.lccp-accordion-content .form-table th {
				width: 200px;
				padding: 15px 10px;
			}
			.lccp-accordion-content .form-table td {
				padding: 15px 10px;
			}
			
			/* Tag Select Styles */
			.lccp-tag-select {
				width: 100%;
				max-width: 400px;
				min-height: 100px;
			}
			
			/* Submit Section */
			.lccp-submit-section {
				margin: 30px 0;
				padding: 20px;
				background: white;
				border: 2px solid #59898D;
				border-radius: 8px;
				text-align: center;
			}
			
			.lccp-footer {
				text-align: center;
				padding: 20px;
				color: #999;
				font-size: 14px;
			}
			.lccp-footer a {
				color: #042247;
				text-decoration: none;
			}
			.lccp-footer a:hover {
				text-decoration: underline;
			}
			
			.notice.inline {
				margin: 10px 0;
			}
		</style>
		
		<script>
		jQuery(document).ready(function($) {
			// Accordion functionality
			$('.lccp-accordion-header').on('click', function() {
				var $header = $(this);
				var $content = $header.next('.lccp-accordion-content');
				var $icon = $header.find('.lccp-accordion-icon');
				
				// Toggle current section
				$header.toggleClass('active');
				$content.toggleClass('active');
				
				// Close other sections (optional - remove if you want multiple open)
				// $('.lccp-accordion-header').not($header).removeClass('active');
				// $('.lccp-accordion-content').not($content).removeClass('active');
			});
			
			// Initialize multi-select for tags
			if ($.fn.select2) {
				$('.lccp-tag-select').select2({
					placeholder: 'Select tags...',
					allowClear: true
				});
			}
			
			// Open first accordion by default
			$('.lccp-accordion-item:first .lccp-accordion-header').addClass('active');
			$('.lccp-accordion-item:first .lccp-accordion-content').addClass('active');
		});
		
		// Toggle all modules
		function toggleAllModules(enable) {
			var checkboxes = document.querySelectorAll('.lccp-modules-grid input[type="checkbox"], .lccp-accordion input[type="checkbox"][name^="lccp_module_"]');
			checkboxes.forEach(function(checkbox) {
				if (!checkbox.disabled) {
					checkbox.checked = enable;
				}
			});
		}
		
		// Dynamic tier level management
		function addTierLevel() {
			var container = document.getElementById('tier-levels-config');
			var index = container.children.length;
			var newRow = document.createElement('div');
			newRow.className = 'tier-level-row';
			newRow.style = 'margin-bottom: 15px; padding: 10px; background: #f0f0f0; border-radius: 4px;';
			newRow.innerHTML = `
				<label>Tier ${index + 1}:</label><br>
				<input type="number" name="lccp_hour_tracker_tier_levels[${index}][hours]" 
						value="" min="1" style="width: 80px;" placeholder="Hours" /> hours = 
				<input type="text" name="lccp_hour_tracker_tier_levels[${index}][name]" 
						value="" style="width: 300px;" placeholder="Full Name" />
				(<input type="text" name="lccp_hour_tracker_tier_levels[${index}][abbr]" 
						value="" style="width: 80px;" placeholder="Abbreviation" />)
				<button type="button" class="button-link" onclick="this.parentElement.remove()" style="color: red; margin-left: 10px;">Remove</button>
			`;
			container.appendChild(newRow);
		}
		
		// Dynamic session type management
		function addSessionType() {
			var container = document.getElementById('session-types-config');
			var newRow = document.createElement('div');
			newRow.className = 'session-type-row';
			newRow.style = 'margin-bottom: 5px;';
			newRow.innerHTML = `
				<input type="text" name="lccp_hour_tracker_session_types[]" 
						value="" style="width: 250px;" placeholder="New Session Type" />
				<button type="button" class="button-link" onclick="this.parentElement.remove()">Remove</button>
			`;
			container.appendChild(newRow);
		}
		</script>
		<?php
	}

	private function render_overview_tab() {
		$modules = $this->get_available_modules();
		?>
		<div class="lccp-overview-tab">
			<h2><?php _e( 'System Overview', 'lccp-systems' ); ?></h2>
			<p><?php _e( 'Welcome to LCCP Systems. All modules are active and configured below.', 'lccp-systems' ); ?></p>
			
			<div class="lccp-modules-grid">
				<?php foreach ( $modules as $module ) : ?>
					<div class="lccp-module-card">
						<h3><?php echo esc_html( $module['name'] ); ?></h3>
						<p><?php echo esc_html( $module['description'] ); ?></p>
						<p class="lccp-module-status" style="font-weight:600;color:#46b450;">
							<?php _e( 'Active', 'lccp-systems' ); ?>
						</p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	private function render_hour_tracker_tab() {
		?>
		<div class="lccp-hour-tracker-tab">
			<h2><?php _e( 'Hour Tracker', 'lccp-systems' ); ?></h2>
			
			<form method="post" action="options.php">
				<?php settings_fields( 'lccp_systems_settings' ); ?>
				
				<table class="form-table">
					<tr>
						<th scope="row"><?php _e( 'Required Hours', 'lccp-systems' ); ?></th>
						<td>
							<input type="number" name="lccp_hour_tracker_required" value="<?php echo get_option( 'lccp_hour_tracker_required', 75 ); ?>" />
							<p class="description"><?php _e( 'Number of hours required for certification', 'lccp-systems' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'Allow Self-Reporting', 'lccp-systems' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="lccp_hour_tracker_self_report" value="1" <?php checked( get_option( 'lccp_hour_tracker_self_report', 1 ), 1 ); ?> />
								<?php _e( 'Students can submit hours without mentor approval', 'lccp-systems' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'Email Notifications', 'lccp-systems' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="lccp_hour_tracker_notifications" value="1" <?php checked( get_option( 'lccp_hour_tracker_notifications', 1 ), 1 ); ?> />
								<?php _e( 'Send email when hours are submitted', 'lccp-systems' ); ?>
							</label>
						</td>
					</tr>
				</table>
				
				<?php submit_button(); ?>
			</form>
			
			<h3><?php _e( 'Recent Hour Submissions', 'lccp-systems' ); ?></h3>
			<p><?php _e( 'View and manage student hour submissions here.', 'lccp-systems' ); ?></p>
		</div>
		<?php
	}

	private function render_dashboards_tab() {
		?>
		<div class="lccp-dashboards-tab">
			<h2><?php _e( 'Dashboard Configuration', 'lccp-systems' ); ?></h2>
			
			<form method="post" action="options.php">
				<?php settings_fields( 'lccp_systems_settings' ); ?>
				
				<h3><?php _e( 'Student Dashboard Widgets', 'lccp-systems' ); ?></h3>
				<table class="form-table">
					<tr>
						<th scope="row"><?php _e( 'Available Widgets', 'lccp-systems' ); ?></th>
						<td>
							<label><input type="checkbox" name="lccp_dash_progress" value="1" <?php checked( get_option( 'lccp_dash_progress', 1 ), 1 ); ?> /> <?php _e( 'Course Progress', 'lccp-systems' ); ?></label><br>
							<label><input type="checkbox" name="lccp_dash_hours" value="1" <?php checked( get_option( 'lccp_dash_hours', 1 ), 1 ); ?> /> <?php _e( 'Hours Tracking', 'lccp-systems' ); ?></label><br>
							<label><input type="checkbox" name="lccp_dash_assignments" value="1" <?php checked( get_option( 'lccp_dash_assignments', 1 ), 1 ); ?> /> <?php _e( 'Assignments', 'lccp-systems' ); ?></label><br>
							<label><input type="checkbox" name="lccp_dash_messages" value="1" <?php checked( get_option( 'lccp_dash_messages', 1 ), 1 ); ?> /> <?php _e( 'Messages', 'lccp-systems' ); ?></label>
						</td>
					</tr>
				</table>
				
				<h3><?php _e( 'Mentor Dashboard Widgets', 'lccp-systems' ); ?></h3>
				<table class="form-table">
					<tr>
						<th scope="row"><?php _e( 'Available Widgets', 'lccp-systems' ); ?></th>
						<td>
							<label><input type="checkbox" name="lccp_mentor_submissions" value="1" <?php checked( get_option( 'lccp_mentor_submissions', 1 ), 1 ); ?> /> <?php _e( 'Student Submissions', 'lccp-systems' ); ?></label><br>
							<label><input type="checkbox" name="lccp_mentor_stats" value="1" <?php checked( get_option( 'lccp_mentor_stats', 1 ), 1 ); ?> /> <?php _e( 'Student Statistics', 'lccp-systems' ); ?></label><br>
							<label><input type="checkbox" name="lccp_mentor_schedule" value="1" <?php checked( get_option( 'lccp_mentor_schedule', 1 ), 1 ); ?> /> <?php _e( 'Coaching Schedule', 'lccp-systems' ); ?></label>
						</td>
					</tr>
				</table>
				
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	private function render_performance_tab() {
		?>
		<div class="lccp-performance-tab">
			<h2><?php _e( 'Performance Settings', 'lccp-systems' ); ?></h2>
			
			<form method="post" action="options.php">
				<?php settings_fields( 'lccp_systems_settings' ); ?>
				
				<table class="form-table">
					<tr>
						<th scope="row"><?php _e( 'Cache Duration', 'lccp-systems' ); ?></th>
						<td>
							<input type="number" name="lccp_cache_duration" value="<?php echo get_option( 'lccp_cache_duration', 3600 ); ?>" />
							<p class="description"><?php _e( 'Cache lifetime in seconds', 'lccp-systems' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'Optimization Features', 'lccp-systems' ); ?></th>
						<td>
							<label><input type="checkbox" name="lccp_optimize_db" value="1" <?php checked( get_option( 'lccp_optimize_db', 1 ), 1 ); ?> /> <?php _e( 'Database optimization', 'lccp-systems' ); ?></label><br>
							<label><input type="checkbox" name="lccp_minify_assets" value="1" <?php checked( get_option( 'lccp_minify_assets', 0 ), 1 ); ?> /> <?php _e( 'Minify CSS and JavaScript', 'lccp-systems' ); ?></label><br>
							<label><input type="checkbox" name="lccp_lazy_load" value="1" <?php checked( get_option( 'lccp_lazy_load', 1 ), 1 ); ?> /> <?php _e( 'Lazy load images', 'lccp-systems' ); ?></label>
						</td>
					</tr>
				</table>
				
				<h3><?php _e( 'Quick Actions', 'lccp-systems' ); ?></h3>
				<p>
					<button type="button" class="button" onclick="if(confirm('Clear all caches?')) location.href='<?php echo wp_nonce_url( admin_url( 'admin.php?page=lccp-systems&tab=performance&action=clear_cache' ), 'lccp_clear_cache' ); ?>'"><?php _e( 'Clear All Caches', 'lccp-systems' ); ?></button>
					<button type="button" class="button" onclick="if(confirm('Optimize database?')) location.href='<?php echo wp_nonce_url( admin_url( 'admin.php?page=lccp-systems&tab=performance&action=optimize_db' ), 'lccp_optimize_db' ); ?>'"><?php _e( 'Optimize Database', 'lccp-systems' ); ?></button>
				</p>
				
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	private function render_settings_tab() {
		?>
		<div class="lccp-settings-tab">
			<h2><?php _e( 'General Settings', 'lccp-systems' ); ?></h2>
			
			<form method="post" action="options.php">
				<?php settings_fields( 'lccp_systems_settings' ); ?>
				
				<table class="form-table">
					<tr>
						<th scope="row"><?php _e( 'System Email', 'lccp-systems' ); ?></th>
						<td>
							<input type="email" name="lccp_system_email" value="<?php echo get_option( 'lccp_system_email', get_option( 'admin_email' ) ); ?>" class="regular-text" />
							<p class="description"><?php _e( 'Email address for system notifications', 'lccp-systems' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'Debug Mode', 'lccp-systems' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="lccp_debug_mode" value="1" <?php checked( get_option( 'lccp_debug_mode', 0 ), 1 ); ?> />
								<?php _e( 'Enable debug mode for troubleshooting', 'lccp-systems' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'Data Retention', 'lccp-systems' ); ?></th>
						<td>
							<input type="number" name="lccp_data_retention" value="<?php echo get_option( 'lccp_data_retention', 90 ); ?>" />
							<p class="description"><?php _e( 'Days to retain old data', 'lccp-systems' ); ?></p>
						</td>
					</tr>
				</table>
				
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public function get_available_modules() {
		$modules = array(
			array(
				'id'          => 'lccp_module_dashboards',
				'name'        => __( 'Dashboards', 'lccp-systems' ),
				'description' => __( 'Comprehensive front-end dashboards for all roles: Program Coordinators, Big Birds, Mentors, and Students with role-specific widgets.', 'lccp-systems' ),
				'file'        => 'modules/class-dashboards.php',
				'requires'    => array(),
			),
			array(
				'id'          => 'lccp_module_roles',
				'name'        => __( 'Roles Manager', 'lccp-systems' ),
				'description' => __( 'Manage custom roles for mentors, big birds, and program coordinators.', 'lccp-systems' ),
				'file'        => 'modules/class-roles-manager.php',
				'requires'    => array(),
			),
			array(
				'id'          => 'lccp_module_events_integration',
				'name'        => __( 'Events Integration', 'lccp-systems' ),
				'description' => __( 'Consolidated events functionality including virtual events, blocks, and shortcodes. Replaces multiple event plugins.', 'lccp-systems' ),
				'file'        => 'modules/class-events-integration.php',
				'requires'    => array( 'The Events Calendar' ),
			),
			array(
				'id'          => 'lccp_module_accessibility',
				'name'        => __( 'Accessibility Tools', 'lccp-systems' ),
				'description' => __( 'Enhanced accessibility features including font sizing, high contrast mode, and screen reader optimizations.', 'lccp-systems' ),
				'file'        => 'modules/class-accessibility-module.php',
				'requires'    => array(),
			),
			array(
				'id'          => 'lccp_module_autologin',
				'name'        => __( 'Auto Login System', 'lccp-systems' ),
				'description' => __( 'Secure auto-login functionality for members with customizable expiration and security options.', 'lccp-systems' ),
				'file'        => 'modules/class-autologin-module.php',
				'requires'    => array(),
			),
			array(
				'id'          => 'lccp_module_dashboards',
				'name'        => __( 'Custom Dashboards', 'lccp-systems' ),
				'description' => __( 'Role-based custom dashboards for students, mentors, and administrators with personalized widgets.', 'lccp-systems' ),
				'file'        => 'modules/class-dashboards-module.php',
				'requires'    => array(),
			),
			array(
				'id'          => 'lccp_module_advanced_checklist',
				'name'        => __( 'Checklists', 'lccp-systems' ),
				'description' => __( 'Interactive checklists with progress tracking, categories, and completion certificates.', 'lccp-systems' ),
				'file'        => 'modules/class-checklist-module.php',
				'requires'    => array(),
			),
			array(
				'id'          => 'lccp_module_hour_tracker_advanced',
				'name'        => __( 'Hour Tracker', 'lccp-systems' ),
				'description' => __( 'Comprehensive hour tracking with reporting, exports, and automated reminders.', 'lccp-systems' ),
				'file'        => 'modules/class-hour-tracker-module.php',
				'requires'    => array(),
			),
			array(
				'id'          => 'lccp_module_learndash_advanced',
				'name'        => __( 'LearnDash Features', 'lccp-systems' ),
				'description' => __( 'Extended LearnDash functionality with custom progress tracking, certificates, and gamification.', 'lccp-systems' ),
				'file'        => 'modules/class-learndash-integration-module.php',
				'requires'    => array( 'LearnDash' ),
			),
			array(
				'id'          => 'lccp_module_performance_advanced',
				'name'        => __( 'Performance Tools', 'lccp-systems' ),
				'description' => __( 'Caching, database optimization, and performance monitoring tools.', 'lccp-systems' ),
				'file'        => 'modules/class-performance-module.php',
				'requires'    => array(),
			),
		);

		// Set default values for new modules
		foreach ( $modules as $module ) {
			if ( get_option( $module['id'] ) === false ) {
				update_option( $module['id'], 'off' );
			}
		}

		return $modules;
	}

	public function handle_toggle_module() {
		if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'lccp_toggle_module' ) ) {
			wp_send_json_error( 'Security check failed' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$module = sanitize_text_field( $_POST['module'] ?? '' );
		$status = sanitize_text_field( $_POST['status'] ?? '' );

		if ( empty( $module ) ) {
			wp_send_json_error( 'Missing module parameter' );
		}

		// Test load the module if turning on
		if ( $status === 'on' ) {
			$test_result = $this->test_load_module( $module );
			if ( ! $test_result['success'] ) {
				wp_send_json_error( 'Module failed to load: ' . $test_result['error'] );
			}
		}

		update_option( $module, $status );
		wp_send_json_success( array( 'message' => 'Module ' . ( $status === 'on' ? 'enabled' : 'disabled' ) ) );
	}

	private function test_load_module( $module_id ) {
		$modules = $this->get_available_modules();

		foreach ( $modules as $module ) {
			if ( $module['id'] === $module_id ) {
				$file_path = LCCP_SYSTEMS_PLUGIN_DIR . $module['file'];

				if ( ! file_exists( $file_path ) ) {
					return array(
						'success' => false,
						'error'   => 'Module file not found',
					);
				}

				// Check dependencies
				if ( ! empty( $module['requires'] ) ) {
					foreach ( $module['requires'] as $requirement ) {
						if ( $requirement === 'LearnDash' && ! class_exists( 'SFWD_LMS' ) ) {
							return array(
								'success' => false,
								'error'   => 'LearnDash is required for this module',
							);
						}
						if ( $requirement === 'BuddyBoss' && ! defined( 'BP_PLATFORM_VERSION' ) ) {
							return array(
								'success' => false,
								'error'   => 'BuddyBoss is required for this module',
							);
						}
						if ( $requirement === 'The Events Calendar' && ! class_exists( 'Tribe__Events__Main' ) ) {
							return array(
								'success' => false,
								'error'   => 'The Events Calendar is required for this module',
							);
						}
					}
				}

				// Test load the file
				ob_start();
				$error_handler = set_error_handler(
					function ( $severity, $message, $file, $line ) {
						throw new ErrorException( $message, 0, $severity, $file, $line );
					}
				);

				try {
					require_once $file_path;
					ob_end_clean();
					if ( $error_handler ) {
						restore_error_handler();
					}
					return array( 'success' => true );
				} catch ( Throwable $e ) {
					ob_end_clean();
					if ( $error_handler ) {
						restore_error_handler();
					}
					return array(
						'success' => false,
						'error'   => $e->getMessage(),
					);
				}
			}
		}

		return array(
			'success' => false,
			'error'   => 'Module not found',
		);
	}

	public function enqueue_admin_assets( $hook ) {
		if ( strpos( $hook, 'lccp-systems' ) === false ) {
			return;
		}

		// Enqueue Select2 for tag selection
		wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0' );
		wp_enqueue_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0', true );

		wp_enqueue_style(
			'lccp-systems-admin',
			LCCP_SYSTEMS_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			LCCP_SYSTEMS_VERSION
		);

		wp_enqueue_script(
			'lccp-systems-admin',
			LCCP_SYSTEMS_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'select2' ),
			LCCP_SYSTEMS_VERSION,
			true
		);
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'lccp-systems', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	public function activate() {
		// Create database tables
		$this->create_database_tables();

		// Set up default options
		$modules = $this->get_available_modules();
		foreach ( $modules as $module ) {
			if ( get_option( $module['id'] ) === false ) {
				update_option( $module['id'], 'off' );
			}
		}

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	public function deactivate() {
		// Clean up scheduled tasks if any
		flush_rewrite_rules();
	}

	private function create_database_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Hour tracker table
		$table_name = $wpdb->prefix . 'lccp_hour_tracker';
		$sql        = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            client_name varchar(100) NOT NULL,
            session_date date NOT NULL,
            session_length float NOT NULL,
            session_number int NOT NULL,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY session_date (session_date)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Mentor assignments table
		$table_name = $wpdb->prefix . 'lccp_assignments';
		$sql        = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            mentor_id bigint(20) DEFAULT NULL,
            bigbird_id bigint(20) DEFAULT NULL,
            pc_id bigint(20) DEFAULT NULL,
            assigned_date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY (id),
            KEY student_id (student_id),
            KEY mentor_id (mentor_id)
        ) $charset_collate;";

		dbDelta( $sql );

		update_option( 'lccp_systems_db_version', LCCP_SYSTEMS_VERSION );
	}

	public function add_settings_link( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=lccp-systems' ) ) . '">' . __( 'Settings', 'lccp-systems' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Render module settings page
	 */
	public function render_module_settings_page() {
		// Include the module settings page class
		require_once LCCP_SYSTEMS_PLUGIN_DIR . 'admin/module-settings.php';

		// Create instance and render page
		$settings_page = new LCCP_Module_Settings_Page( $this->module_manager );
		$settings_page->render_page();
	}

	/**
	 * Get module manager instance
	 */
	public function get_module_manager() {
		return $this->module_manager;
	}
}

// Initialize the plugin
LCCP_Systems::get_instance();

// Load enabled modules
add_action( 'plugins_loaded', 'lccp_load_enabled_modules', 20 );
function lccp_load_enabled_modules() {
	$instance = LCCP_Systems::get_instance();
	$modules  = $instance->get_available_modules();

	foreach ( $modules as $module ) {
		if ( get_option( $module['id'], 'off' ) === 'on' ) {
			$file_path = LCCP_SYSTEMS_PLUGIN_DIR . $module['file'];

			if ( file_exists( $file_path ) ) {
				// Check dependencies before loading
				$can_load = true;
				if ( ! empty( $module['requires'] ) ) {
					foreach ( $module['requires'] as $requirement ) {
						if ( $requirement === 'LearnDash' && ! class_exists( 'SFWD_LMS' ) ) {
							$can_load = false;
							break;
						}
						if ( $requirement === 'BuddyBoss' && ! defined( 'BP_PLATFORM_VERSION' ) ) {
							$can_load = false;
							break;
						}
						if ( $requirement === 'The Events Calendar' && ! class_exists( 'Tribe__Events__Main' ) ) {
							$can_load = false;
							break;
						}
					}
				}

				if ( $can_load ) {
					try {
						require_once $file_path;
					} catch ( Throwable $e ) {
						// Auto-disable on error
						update_option( $module['id'], 'off' );
						error_log( 'LCCP Systems: Disabled module ' . $module['name'] . ' due to error: ' . $e->getMessage() );
					}
				}
			}
		}
	}
}