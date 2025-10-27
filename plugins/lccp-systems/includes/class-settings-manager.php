<?php
/**
 * LCCP Systems Settings Manager
 * Centralized settings page with WP Fusion tag integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Settings_Manager {
    
    private $options;
    private $wp_fusion_active = false;
    
    public function __construct() {
        $this->options = get_option('lccp_systems_settings', $this->get_defaults());
        $this->wp_fusion_active = class_exists('WP_Fusion');
        
        add_action('admin_menu', array($this, 'add_settings_page'), 99);
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_lccp_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_lccp_sync_wp_fusion_tags', array($this, 'ajax_sync_wp_fusion_tags'));
        add_action('wp_ajax_lccp_test_tag_mapping', array($this, 'ajax_test_tag_mapping'));
        
        // Apply tag-based role mapping
        if ($this->wp_fusion_active) {
            add_action('wpf_tags_modified', array($this, 'update_user_role_from_tags'), 10, 2);
            add_action('wpf_user_created', array($this, 'assign_role_from_tags'), 10, 3);
        }
    }
    
    private function get_defaults() {
        return array(
            'general' => array(
                'enable_hour_tracking' => true,
                'enable_dashboards' => true,
                'enable_auto_login' => false,
                'enable_performance' => true,
            ),
            'role_mapping' => array(
                'fearless_you_member_tags' => array(),
                'public_user_tags' => array(),
                'free_user_tags' => array(),
                'lccp_mentor_tags' => array(),
                'lccp_big_bird_tags' => array(),
                'lccp_pc_tags' => array(),
            ),
            'course_access' => array(
                'admin_full_access' => true,
                'mentor_lccp_access' => true,
                'bigbird_lccp_access' => true,
                'lccp_category_slug' => 'lccp',
            ),
            'notifications' => array(
                'enable_email_notifications' => true,
                'notification_email' => get_option('admin_email'),
                'notify_on_enrollment' => true,
                'notify_on_completion' => true,
            ),
            'advanced' => array(
                'cache_lifetime' => 3600,
                'enable_debug_mode' => false,
                'cleanup_old_data' => true,
                'data_retention_days' => 90,
            )
        );
    }
    
    public function add_settings_page() {
        // Replace the existing settings page with comprehensive one
        remove_submenu_page('lccp-systems', 'lccp-settings');
        
        add_submenu_page(
            'lccp-systems',
            __('LCCP Settings', 'lccp-systems'),
            __('Settings', 'lccp-systems'),
            'manage_options',
            'lccp-settings',
            array($this, 'render_settings_page')
        );
    }
    
    public function render_settings_page() {
        ?>
        <div class="wrap lccp-settings-wrap">
            <h1><?php _e('LCCP Systems Settings', 'lccp-systems'); ?></h1>
            
            <?php if (isset($_GET['settings-updated'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Settings saved successfully!', 'lccp-systems'); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="lccp-settings-container">
                <div class="lccp-settings-tabs">
                    <ul class="nav-tab-wrapper">
                        <li><a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'lccp-systems'); ?></a></li>
                        <li><a href="#role-mapping" class="nav-tab"><?php _e('Role Mapping', 'lccp-systems'); ?></a></li>
                        <li><a href="#course-access" class="nav-tab"><?php _e('Course Access', 'lccp-systems'); ?></a></li>
                        <li><a href="#notifications" class="nav-tab"><?php _e('Notifications', 'lccp-systems'); ?></a></li>
                        <li><a href="#advanced" class="nav-tab"><?php _e('Advanced', 'lccp-systems'); ?></a></li>
                    </ul>
                </div>
                
                <form method="post" action="options.php" id="lccp-settings-form">
                    <?php settings_fields('lccp_systems_settings_group'); ?>
                    
                    <!-- General Settings Tab -->
                    <div id="general" class="tab-content active">
                        <h2><?php _e('General Settings', 'lccp-systems'); ?></h2>
                        <div class="lccp-module-settings">
                            
                            <!-- Hour Tracking Module -->
                            <div class="module-section">
                                <div class="module-header">
                                    <label class="module-toggle">
                                        <input type="checkbox" name="lccp_systems_settings[general][enable_hour_tracking]" 
                                               class="module-checkbox" data-module="hour-tracking"
                                               value="1" <?php checked($this->options['general']['enable_hour_tracking'], true); ?> />
                                        <span class="module-title"><?php _e('Hour Tracking', 'lccp-systems'); ?></span>
                                        <span class="module-description"><?php _e('Allow students to track coaching hours', 'lccp-systems'); ?></span>
                                    </label>
                                </div>
                                <div class="module-settings-content" id="hour-tracking-settings" style="display: <?php echo $this->options['general']['enable_hour_tracking'] ? 'block' : 'none'; ?>">
                                    <table class="form-table">
                                        <tr>
                                            <th><?php _e('Required Hours', 'lccp-systems'); ?></th>
                                            <td>
                                                <input type="number" name="lccp_systems_settings[hour_tracking][required_hours]" 
                                                       value="<?php echo isset($this->options['hour_tracking']['required_hours']) ? esc_attr($this->options['hour_tracking']['required_hours']) : '75'; ?>" />
                                                <p class="description"><?php _e('Number of hours required for certification', 'lccp-systems'); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php _e('Allow Self-Reporting', 'lccp-systems'); ?></th>
                                            <td>
                                                <label>
                                                    <input type="checkbox" name="lccp_systems_settings[hour_tracking][allow_self_reporting]" 
                                                           value="1" <?php checked(isset($this->options['hour_tracking']['allow_self_reporting']) ? $this->options['hour_tracking']['allow_self_reporting'] : true, true); ?> />
                                                    <?php _e('Students can submit hours without mentor approval', 'lccp-systems'); ?>
                                                </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php _e('Email Notifications', 'lccp-systems'); ?></th>
                                            <td>
                                                <label>
                                                    <input type="checkbox" name="lccp_systems_settings[hour_tracking][email_notifications]" 
                                                           value="1" <?php checked(isset($this->options['hour_tracking']['email_notifications']) ? $this->options['hour_tracking']['email_notifications'] : true, true); ?> />
                                                    <?php _e('Send email when hours are submitted', 'lccp-systems'); ?>
                                                </label>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Dashboards Module -->
                            <div class="module-section">
                                <div class="module-header">
                                    <label class="module-toggle">
                                        <input type="checkbox" name="lccp_systems_settings[general][enable_dashboards]" 
                                               class="module-checkbox" data-module="dashboards"
                                               value="1" <?php checked($this->options['general']['enable_dashboards'], true); ?> />
                                        <span class="module-title"><?php _e('Custom Dashboards', 'lccp-systems'); ?></span>
                                        <span class="module-description"><?php _e('Show role-based dashboards', 'lccp-systems'); ?></span>
                                    </label>
                                </div>
                                <div class="module-settings-content" id="dashboards-settings" style="display: <?php echo $this->options['general']['enable_dashboards'] ? 'block' : 'none'; ?>">
                                    <table class="form-table">
                                        <tr>
                                            <th><?php _e('Student Dashboard', 'lccp-systems'); ?></th>
                                            <td>
                                                <label>
                                                    <input type="checkbox" name="lccp_systems_settings[dashboards][show_progress]" 
                                                           value="1" <?php checked(isset($this->options['dashboards']['show_progress']) ? $this->options['dashboards']['show_progress'] : true, true); ?> />
                                                    <?php _e('Show course progress widget', 'lccp-systems'); ?>
                                                </label><br>
                                                <label>
                                                    <input type="checkbox" name="lccp_systems_settings[dashboards][show_hours]" 
                                                           value="1" <?php checked(isset($this->options['dashboards']['show_hours']) ? $this->options['dashboards']['show_hours'] : true, true); ?> />
                                                    <?php _e('Show hours tracking widget', 'lccp-systems'); ?>
                                                </label><br>
                                                <label>
                                                    <input type="checkbox" name="lccp_systems_settings[dashboards][show_assignments]" 
                                                           value="1" <?php checked(isset($this->options['dashboards']['show_assignments']) ? $this->options['dashboards']['show_assignments'] : true, true); ?> />
                                                    <?php _e('Show assignments widget', 'lccp-systems'); ?>
                                                </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php _e('Mentor Dashboard', 'lccp-systems'); ?></th>
                                            <td>
                                                <label>
                                                    <input type="checkbox" name="lccp_systems_settings[dashboards][mentor_reviews]" 
                                                           value="1" <?php checked(isset($this->options['dashboards']['mentor_reviews']) ? $this->options['dashboards']['mentor_reviews'] : true, true); ?> />
                                                    <?php _e('Show student submissions for review', 'lccp-systems'); ?>
                                                </label><br>
                                                <label>
                                                    <input type="checkbox" name="lccp_systems_settings[dashboards][mentor_stats]" 
                                                           value="1" <?php checked(isset($this->options['dashboards']['mentor_stats']) ? $this->options['dashboards']['mentor_stats'] : true, true); ?> />
                                                    <?php _e('Show student statistics', 'lccp-systems'); ?>
                                                </label>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Auto Login Module -->
                            <div class="module-section">
                                <div class="module-header">
                                    <label class="module-toggle">
                                        <input type="checkbox" name="lccp_systems_settings[general][enable_auto_login]" 
                                               class="module-checkbox" data-module="auto-login"
                                               value="1" <?php checked($this->options['general']['enable_auto_login'], true); ?> />
                                        <span class="module-title"><?php _e('Auto Login', 'lccp-systems'); ?></span>
                                        <span class="module-description"><?php _e('Enable IP-based automatic login', 'lccp-systems'); ?></span>
                                    </label>
                                </div>
                                <div class="module-settings-content" id="auto-login-settings" style="display: <?php echo $this->options['general']['enable_auto_login'] ? 'block' : 'none'; ?>">
                                    <table class="form-table">
                                        <tr>
                                            <th><?php _e('Allowed IP Addresses', 'lccp-systems'); ?></th>
                                            <td>
                                                <textarea name="lccp_systems_settings[auto_login][allowed_ips]" rows="5" cols="50"><?php 
                                                    echo isset($this->options['auto_login']['allowed_ips']) ? esc_textarea($this->options['auto_login']['allowed_ips']) : ''; 
                                                ?></textarea>
                                                <p class="description"><?php _e('Enter one IP address per line', 'lccp-systems'); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php _e('Default User', 'lccp-systems'); ?></th>
                                            <td>
                                                <?php wp_dropdown_users(array(
                                                    'name' => 'lccp_systems_settings[auto_login][default_user]',
                                                    'selected' => isset($this->options['auto_login']['default_user']) ? $this->options['auto_login']['default_user'] : 0,
                                                    'show_option_none' => __('Select User', 'lccp-systems')
                                                )); ?>
                                                <p class="description"><?php _e('User to auto-login from allowed IPs', 'lccp-systems'); ?></p>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Performance Module -->
                            <div class="module-section">
                                <div class="module-header">
                                    <label class="module-toggle">
                                        <input type="checkbox" name="lccp_systems_settings[general][enable_performance]" 
                                               class="module-checkbox" data-module="performance"
                                               value="1" <?php checked($this->options['general']['enable_performance'], true); ?> />
                                        <span class="module-title"><?php _e('Performance Optimization', 'lccp-systems'); ?></span>
                                        <span class="module-description"><?php _e('Enable performance optimizations', 'lccp-systems'); ?></span>
                                    </label>
                                </div>
                                <div class="module-settings-content" id="performance-settings" style="display: <?php echo $this->options['general']['enable_performance'] ? 'block' : 'none'; ?>">
                                    <table class="form-table">
                                        <tr>
                                            <th><?php _e('Cache Duration', 'lccp-systems'); ?></th>
                                            <td>
                                                <input type="number" name="lccp_systems_settings[performance][cache_duration]" 
                                                       value="<?php echo isset($this->options['performance']['cache_duration']) ? esc_attr($this->options['performance']['cache_duration']) : '3600'; ?>" />
                                                <p class="description"><?php _e('Cache lifetime in seconds', 'lccp-systems'); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php _e('Optimization Features', 'lccp-systems'); ?></th>
                                            <td>
                                                <label>
                                                    <input type="checkbox" name="lccp_systems_settings[performance][optimize_database]" 
                                                           value="1" <?php checked(isset($this->options['performance']['optimize_database']) ? $this->options['performance']['optimize_database'] : true, true); ?> />
                                                    <?php _e('Database optimization', 'lccp-systems'); ?>
                                                </label><br>
                                                <label>
                                                    <input type="checkbox" name="lccp_systems_settings[performance][minify_assets]" 
                                                           value="1" <?php checked(isset($this->options['performance']['minify_assets']) ? $this->options['performance']['minify_assets'] : false, true); ?> />
                                                    <?php _e('Minify CSS and JavaScript', 'lccp-systems'); ?>
                                                </label><br>
                                                <label>
                                                    <input type="checkbox" name="lccp_systems_settings[performance][lazy_load]" 
                                                           value="1" <?php checked(isset($this->options['performance']['lazy_load']) ? $this->options['performance']['lazy_load'] : true, true); ?> />
                                                    <?php _e('Lazy load images', 'lccp-systems'); ?>
                                                </label>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    
                    <!-- Modules Tab - Removed since settings are now inline -->
                    
                    <!-- Role Mapping Tab -->
                    <div id="role-mapping" class="tab-content">
                        <h2><?php _e('Role Mapping Configuration', 'lccp-systems'); ?></h2>
                        
                        <?php if ($this->wp_fusion_active): ?>
                            <div class="notice notice-info">
                                <p><?php _e('WP Fusion is active. You can map tags to roles below.', 'lccp-systems'); ?></p>
                                <button type="button" class="button" id="sync-wp-fusion-tags">
                                    <?php _e('Sync WP Fusion Tags', 'lccp-systems'); ?>
                                </button>
                            </div>
                            
                            <?php $available_tags = $this->get_wp_fusion_tags(); ?>
                            
                            <table class="form-table role-mapping-table">
                                <!-- Fearless You Member -->
                                <tr>
                                    <th scope="row">
                                        <?php _e('Fearless You Member', 'lccp-systems'); ?>
                                        <p class="description"><?php _e('Premium membership with full access', 'lccp-systems'); ?></p>
                                    </th>
                                    <td>
                                        <?php $this->render_tag_selector('fearless_you_member_tags', $available_tags); ?>
                                    </td>
                                </tr>
                                
                                <!-- Public User -->
                                <tr>
                                    <th scope="row">
                                        <?php _e('Public User', 'lccp-systems'); ?>
                                        <p class="description"><?php _e('Users with any purchases', 'lccp-systems'); ?></p>
                                    </th>
                                    <td>
                                        <?php $this->render_tag_selector('public_user_tags', $available_tags); ?>
                                    </td>
                                </tr>
                                
                                <!-- Free User -->
                                <tr>
                                    <th scope="row">
                                        <?php _e('Free User', 'lccp-systems'); ?>
                                        <p class="description"><?php _e('Default role for new users', 'lccp-systems'); ?></p>
                                    </th>
                                    <td>
                                        <?php $this->render_tag_selector('free_user_tags', $available_tags); ?>
                                    </td>
                                </tr>
                                
                                <!-- LCCP Mentor -->
                                <tr>
                                    <th scope="row">
                                        <?php _e('LCCP Mentor', 'lccp-systems'); ?>
                                        <p class="description"><?php _e('Mentor role with oversight capabilities', 'lccp-systems'); ?></p>
                                    </th>
                                    <td>
                                        <?php $this->render_tag_selector('lccp_mentor_tags', $available_tags); ?>
                                    </td>
                                </tr>
                                
                                <!-- LCCP BigBird -->
                                <tr>
                                    <th scope="row">
                                        <?php _e('LCCP Big Bird', 'lccp-systems'); ?>
                                        <p class="description"><?php _e('Big Bird role managing PCs', 'lccp-systems'); ?></p>
                                    </th>
                                    <td>
                                        <?php $this->render_tag_selector('lccp_big_bird_tags', $available_tags); ?>
                                    </td>
                                </tr>
                                
                                <!-- LCCP PC -->
                                <tr>
                                    <th scope="row">
                                        <?php _e('LCCP Program Candidate', 'lccp-systems'); ?>
                                        <p class="description"><?php _e('PC role working with students', 'lccp-systems'); ?></p>
                                    </th>
                                    <td>
                                        <?php $this->render_tag_selector('lccp_pc_tags', $available_tags); ?>
                                    </td>
                                </tr>
                            </table>
                            
                            <div class="tag-mapping-test">
                                <h3><?php _e('Test Tag Mapping', 'lccp-systems'); ?></h3>
                                <input type="text" id="test-user-email" placeholder="Enter user email to test" />
                                <button type="button" class="button" id="test-tag-mapping">
                                    <?php _e('Test Mapping', 'lccp-systems'); ?>
                                </button>
                                <div id="test-results"></div>
                            </div>
                            
                        <?php else: ?>
                            <div class="notice notice-warning">
                                <p><?php _e('WP Fusion is not active. Install and activate WP Fusion to enable tag-based role mapping.', 'lccp-systems'); ?></p>
                            </div>
                            
                            <h3><?php _e('Manual Role Assignment', 'lccp-systems'); ?></h3>
                            <p><?php _e('Without WP Fusion, roles are assigned based on:', 'lccp-systems'); ?></p>
                            <ul>
                                <li><?php _e('User meta tags', 'lccp-systems'); ?></li>
                                <li><?php _e('Purchase history', 'lccp-systems'); ?></li>
                                <li><?php _e('Course enrollments', 'lccp-systems'); ?></li>
                            </ul>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Course Access Tab -->
                    <div id="course-access" class="tab-content">
                        <h2><?php _e('Course Access Settings', 'lccp-systems'); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Admin Full Access', 'lccp-systems'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="lccp_systems_settings[course_access][admin_full_access]" 
                                               value="1" <?php checked($this->options['course_access']['admin_full_access'], true); ?> />
                                        <?php _e('Administrators have access to all courses', 'lccp-systems'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Mentor LCCP Access', 'lccp-systems'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="lccp_systems_settings[course_access][mentor_lccp_access]" 
                                               value="1" <?php checked($this->options['course_access']['mentor_lccp_access'], true); ?> />
                                        <?php _e('Mentors have access to all LCCP category courses', 'lccp-systems'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Big Bird LCCP Access', 'lccp-systems'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="lccp_systems_settings[course_access][bigbird_lccp_access]" 
                                               value="1" <?php checked($this->options['course_access']['bigbird_lccp_access'], true); ?> />
                                        <?php _e('Big Birds have access to all LCCP category courses', 'lccp-systems'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('LCCP Category Slug', 'lccp-systems'); ?></th>
                                <td>
                                    <input type="text" name="lccp_systems_settings[course_access][lccp_category_slug]" 
                                           value="<?php echo esc_attr($this->options['course_access']['lccp_category_slug']); ?>" />
                                    <p class="description"><?php _e('Course category slug for LCCP courses', 'lccp-systems'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Notifications Tab -->
                    <div id="notifications" class="tab-content">
                        <h2><?php _e('Notification Settings', 'lccp-systems'); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Enable Email Notifications', 'lccp-systems'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="lccp_systems_settings[notifications][enable_email_notifications]" 
                                               value="1" <?php checked($this->options['notifications']['enable_email_notifications'], true); ?> />
                                        <?php _e('Send email notifications for important events', 'lccp-systems'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Notification Email', 'lccp-systems'); ?></th>
                                <td>
                                    <input type="email" name="lccp_systems_settings[notifications][notification_email]" 
                                           value="<?php echo esc_attr($this->options['notifications']['notification_email']); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Notify on Enrollment', 'lccp-systems'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="lccp_systems_settings[notifications][notify_on_enrollment]" 
                                               value="1" <?php checked($this->options['notifications']['notify_on_enrollment'], true); ?> />
                                        <?php _e('Send notification when user enrolls in course', 'lccp-systems'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Notify on Completion', 'lccp-systems'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="lccp_systems_settings[notifications][notify_on_completion]" 
                                               value="1" <?php checked($this->options['notifications']['notify_on_completion'], true); ?> />
                                        <?php _e('Send notification when user completes course', 'lccp-systems'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Advanced Tab -->
                    <div id="advanced" class="tab-content">
                        <h2><?php _e('Advanced Settings', 'lccp-systems'); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Cache Lifetime', 'lccp-systems'); ?></th>
                                <td>
                                    <input type="number" name="lccp_systems_settings[advanced][cache_lifetime]" 
                                           value="<?php echo esc_attr($this->options['advanced']['cache_lifetime']); ?>" 
                                           min="0" /> seconds
                                    <p class="description"><?php _e('How long to cache data (0 to disable)', 'lccp-systems'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Debug Mode', 'lccp-systems'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="lccp_systems_settings[advanced][enable_debug_mode]" 
                                               value="1" <?php checked($this->options['advanced']['enable_debug_mode'], true); ?> />
                                        <?php _e('Enable debug logging', 'lccp-systems'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Cleanup Old Data', 'lccp-systems'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="lccp_systems_settings[advanced][cleanup_old_data]" 
                                               value="1" <?php checked($this->options['advanced']['cleanup_old_data'], true); ?> />
                                        <?php _e('Automatically cleanup old data', 'lccp-systems'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Data Retention', 'lccp-systems'); ?></th>
                                <td>
                                    <input type="number" name="lccp_systems_settings[advanced][data_retention_days]" 
                                           value="<?php echo esc_attr($this->options['advanced']['data_retention_days']); ?>" 
                                           min="30" /> days
                                    <p class="description"><?php _e('Keep data for this many days', 'lccp-systems'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
        </div>
        
        <style>
        .lccp-settings-wrap {
            max-width: 1200px;
        }
        .lccp-settings-container {
            background: white;
            padding: 20px;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .nav-tab-wrapper {
            display: flex;
            margin: 0;
            padding: 0;
            list-style: none;
            border-bottom: 1px solid #ccd0d4;
        }
        .nav-tab {
            display: block;
            padding: 10px 20px;
            margin-bottom: -1px;
            background: #f1f1f1;
            border: 1px solid #ccd0d4;
            border-bottom: none;
            text-decoration: none;
            color: #555;
        }
        .nav-tab-active {
            background: white;
            color: #000;
        }
        .tab-content {
            display: none;
            padding: 20px 0;
        }
        .tab-content.active {
            display: block;
        }
        .tag-selector {
            width: 100%;
            min-height: 100px;
        }
        .tag-mapping-test {
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }
        #test-results {
            margin-top: 20px;
            padding: 10px;
            background: white;
            border: 1px solid #ddd;
            display: none;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab switching
            $('.nav-tab').click(function(e) {
                e.preventDefault();
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                $('.tab-content').removeClass('active');
                $($(this).attr('href')).addClass('active');
            });
            
            // Sync WP Fusion tags
            $('#sync-wp-fusion-tags').click(function() {
                var $button = $(this);
                $button.prop('disabled', true).text('Syncing...');
                
                $.post(ajaxurl, {
                    action: 'lccp_sync_wp_fusion_tags',
                    nonce: '<?php echo wp_create_nonce('lccp_settings'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error syncing tags: ' + response.data);
                    }
                    $button.prop('disabled', false).text('Sync WP Fusion Tags');
                });
            });
            
            // Test tag mapping
            $('#test-tag-mapping').click(function() {
                var email = $('#test-user-email').val();
                if (!email) {
                    alert('Please enter a user email');
                    return;
                }
                
                $.post(ajaxurl, {
                    action: 'lccp_test_tag_mapping',
                    email: email,
                    nonce: '<?php echo wp_create_nonce('lccp_settings'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#test-results').html(response.data).show();
                    } else {
                        $('#test-results').html('<p style="color:red;">Error: ' + response.data + '</p>').show();
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    private function render_tag_selector($field_name, $available_tags) {
        $selected_tags = isset($this->options['role_mapping'][$field_name]) ? 
                        $this->options['role_mapping'][$field_name] : array();
        ?>
        <select name="lccp_systems_settings[role_mapping][<?php echo $field_name; ?>][]" 
                class="tag-selector" multiple>
            <?php foreach ($available_tags as $tag_key => $tag_label): ?>
                <option value="<?php echo esc_attr($tag_key); ?>" 
                        <?php echo in_array($tag_key, $selected_tags) ? 'selected' : ''; ?>>
                    <?php echo esc_html($tag_label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php _e('Hold Ctrl/Cmd to select multiple tags', 'lccp-systems'); ?>
        </p>
        <?php
    }
    
    private function get_wp_fusion_tags() {
        if (!$this->wp_fusion_active) {
            return array();
        }
        
        $tags = wp_fusion()->settings->get_available_tags_flat();
        
        if (empty($tags)) {
            // Try to get tags from the CRM
            $tags = wp_fusion()->crm->get_available_tags();
        }
        
        return is_array($tags) ? $tags : array();
    }
    
    public function register_settings() {
        register_setting(
            'lccp_systems_settings_group',
            'lccp_systems_settings',
            array($this, 'sanitize_settings')
        );
    }
    
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Sanitize each section
        foreach ($input as $section => $values) {
            foreach ($values as $key => $value) {
                if (is_array($value)) {
                    $sanitized[$section][$key] = array_map('sanitize_text_field', $value);
                } elseif (is_bool($value) || $value === '1' || $value === '0') {
                    $sanitized[$section][$key] = (bool) $value;
                } elseif (is_numeric($value)) {
                    $sanitized[$section][$key] = intval($value);
                } elseif ($key === 'notification_email') {
                    $sanitized[$section][$key] = sanitize_email($value);
                } else {
                    $sanitized[$section][$key] = sanitize_text_field($value);
                }
            }
        }
        
        return $sanitized;
    }
    
    public function update_user_role_from_tags($user_id, $user_tags) {
        $role_mappings = $this->options['role_mapping'];
        $new_role = null;
        
        // Check tags against role mappings (highest priority first)
        if ($this->user_has_tags($user_tags, $role_mappings['lccp_mentor_tags'])) {
            $new_role = 'lccp_mentor';
        } elseif ($this->user_has_tags($user_tags, $role_mappings['lccp_big_bird_tags'])) {
            $new_role = 'lccp_big_bird';
        } elseif ($this->user_has_tags($user_tags, $role_mappings['lccp_pc_tags'])) {
            $new_role = 'lccp_pc';
        } elseif ($this->user_has_tags($user_tags, $role_mappings['fearless_you_member_tags'])) {
            $new_role = 'fearless_you_member';
        } elseif ($this->user_has_tags($user_tags, $role_mappings['public_user_tags'])) {
            $new_role = 'public_user';
        } elseif ($this->user_has_tags($user_tags, $role_mappings['free_user_tags'])) {
            $new_role = 'free_user';
        }
        
        if ($new_role) {
            $user = new WP_User($user_id);
            
            // Don't change admin roles
            if (!in_array('administrator', $user->roles)) {
                $user->set_role($new_role);
            }
        }
    }
    
    private function user_has_tags($user_tags, $required_tags) {
        if (empty($required_tags)) {
            return false;
        }
        
        foreach ($required_tags as $tag) {
            if (in_array($tag, $user_tags)) {
                return true;
            }
        }
        
        return false;
    }
    
    public function enqueue_admin_scripts($hook) {
        if ('lccp-systems_page_lccp-settings' !== $hook) {
            return;
        }
        
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0');
        
        // Add CSS for tab and module functionality
        wp_add_inline_style('select2', '
            .lccp-settings-wrap .tab-content {
                display: none;
            }
            .lccp-settings-wrap .tab-content.active {
                display: block;
            }
            .lccp-settings-wrap .nav-tab-wrapper {
                border-bottom: 1px solid #ccc;
                margin-bottom: 20px;
                padding: 0;
            }
            .lccp-settings-wrap .nav-tab {
                cursor: pointer;
            }
            
            /* Module sections styling */
            .lccp-module-settings {
                margin-top: 20px;
            }
            .module-section {
                background: #fff;
                border: 1px solid #e0e0e0;
                border-radius: 4px;
                margin-bottom: 20px;
                overflow: hidden;
            }
            .module-header {
                padding: 15px 20px;
                background: #f7f7f7;
                border-bottom: 1px solid #e0e0e0;
            }
            .module-toggle {
                display: block;
                cursor: pointer;
                font-size: 14px;
            }
            .module-toggle input[type="checkbox"] {
                margin-right: 10px;
            }
            .module-title {
                font-weight: 600;
                font-size: 16px;
                color: #333;
            }
            .module-description {
                color: #666;
                font-size: 13px;
                margin-left: 5px;
            }
            .module-settings-content {
                padding: 20px;
                background: #fafafa;
            }
            .module-settings-content table {
                width: 100%;
            }
            .module-settings-content th {
                width: 200px;
                text-align: left;
                padding: 10px 10px 10px 0;
                vertical-align: top;
                font-weight: 600;
            }
            .module-settings-content td {
                padding: 10px;
            }
            .module-settings-content .description {
                font-size: 12px;
                color: #666;
                margin-top: 5px;
            }
        ');
        
        wp_add_inline_script('select2', '
            jQuery(document).ready(function($) {
                $(".tag-selector").select2({
                    placeholder: "Select tags...",
                    allowClear: true
                });
                
                // Tab switching functionality
                $(".nav-tab").on("click", function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all tabs
                    $(".nav-tab").removeClass("nav-tab-active");
                    // Add active class to clicked tab
                    $(this).addClass("nav-tab-active");
                    
                    // Hide all tab content
                    $(".tab-content").removeClass("active").hide();
                    // Show selected tab content
                    var target = $(this).attr("href");
                    $(target).addClass("active").show();
                });
                
                // Module checkbox toggle functionality
                $(".module-checkbox").on("change", function() {
                    var module = $(this).data("module");
                    var settingsContent = $("#" + module + "-settings");
                    
                    if ($(this).is(":checked")) {
                        settingsContent.slideDown(300);
                    } else {
                        settingsContent.slideUp(300);
                    }
                });
                
                // Initialize - show settings for already checked modules
                $(".module-checkbox:checked").each(function() {
                    var module = $(this).data("module");
                    $("#" + module + "-settings").show();
                });
                
                // Initialize - show only active tab
                $(".tab-content").hide();
                $(".tab-content.active").show();
            });
        ');
    }
    
    public function ajax_sync_wp_fusion_tags() {
        check_ajax_referer('lccp_settings', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if ($this->wp_fusion_active) {
            wp_fusion()->crm->sync_tags();
            wp_send_json_success('Tags synced successfully');
        } else {
            wp_send_json_error('WP Fusion is not active');
        }
    }
    
    public function ajax_test_tag_mapping() {
        check_ajax_referer('lccp_settings', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $email = sanitize_email($_POST['email']);
        $user = get_user_by('email', $email);
        
        if (!$user) {
            wp_send_json_error('User not found');
        }
        
        $output = '<h4>User: ' . esc_html($user->display_name) . '</h4>';
        $output .= '<p>Current Role: ' . implode(', ', $user->roles) . '</p>';
        
        if ($this->wp_fusion_active) {
            $user_tags = wp_fusion()->user->get_tags($user->ID);
            $output .= '<p>WP Fusion Tags: ' . (empty($user_tags) ? 'None' : implode(', ', $user_tags)) . '</p>';
            
            // Determine what role they would get
            $this->update_user_role_from_tags($user->ID, $user_tags);
            $output .= '<p>Suggested Role: ' . implode(', ', $user->roles) . '</p>';
        }
        
        wp_send_json_success($output);
    }
}

// Initialize settings manager
new LCCP_Settings_Manager();