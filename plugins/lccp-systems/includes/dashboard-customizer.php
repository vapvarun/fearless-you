<?php
/**
 * Dasher Dashboard Customizer
 * Frontend card customization system
 * 
 * @package Dasher
 * @since 1.0.3
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Dasher_Dashboard_Customizer
 * Handles frontend dashboard customization
 */
class Dasher_Dashboard_Customizer {
    
    /**
     * Initialize the customizer system
     */
    public function __construct() {
        add_action('wp_ajax_dasher_save_card_settings', array($this, 'save_card_settings'));
        add_action('wp_ajax_dasher_get_card_settings', array($this, 'get_card_settings'));
        add_action('wp_ajax_dasher_toggle_card', array($this, 'toggle_card'));
        add_action('wp_ajax_dasher_update_card_size', array($this, 'update_card_size'));
        add_action('wp_ajax_dasher_reorder_cards', array($this, 'reorder_cards'));
        add_action('wp_ajax_dasher_reset_dashboard', array($this, 'reset_dashboard'));
        
        add_action('wp_enqueue_scripts', array($this, 'enqueue_customizer_assets'));
    }
    
    /**
     * Enqueue customizer assets
     */
    public function enqueue_customizer_assets() {
        global $post;
        
        // Only load on dashboard pages
        $has_dashboard = false;
        if (is_a($post, 'WP_Post')) {
            $has_dashboard = has_shortcode($post->post_content, 'dasher_mentor_dashboard') || 
                           has_shortcode($post->post_content, 'dasher_big_bird_dashboard') ||
                           has_shortcode($post->post_content, 'dasher_pc_dashboard');
        }
        
        if ($has_dashboard || is_page(array('mentor-dashboard', 'big-bird-dashboard', 'pc-dashboard'))) {
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('dasher-dashboard-customizer', 
                DASHER_PLUGIN_URL . 'assets/js/dashboard-customizer.js', 
                array('jquery', 'jquery-ui-sortable'), 
                DASHER_VERSION, 
                true
            );
            
            wp_localize_script('dasher-dashboard-customizer', 'dasher_customizer', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dasher_customizer_nonce'),
                'strings' => array(
                    'save_success' => __('Settings saved successfully!', 'dasher'),
                    'save_error' => __('Error saving settings. Please try again.', 'dasher'),
                    'confirm_reset' => __('Are you sure you want to reset all dashboard settings?', 'dasher'),
                    'reset_success' => __('Dashboard reset successfully!', 'dasher'),
                )
            ));
        }
    }
    
    /**
     * Get default card configuration for each dashboard type
     */
    public function get_default_card_config($dashboard_type) {
        $configs = array(
            'mentor' => array(
                'students_overview' => array(
                    'title' => __('Students Overview', 'dasher'),
                    'enabled' => true,
                    'size' => 'medium',
                    'order' => 1,
                    'type' => 'primary'
                ),
                'success_rate' => array(
                    'title' => __('Success Rate', 'dasher'),
                    'enabled' => true,
                    'size' => 'medium',
                    'order' => 2,
                    'type' => 'success'
                ),
                'engagement_score' => array(
                    'title' => __('Engagement Score', 'dasher'),
                    'enabled' => true,
                    'size' => 'medium',
                    'order' => 3,
                    'type' => 'info'
                ),
                'communication_status' => array(
                    'title' => __('Communication Status', 'dasher'),
                    'enabled' => true,
                    'size' => 'medium',
                    'order' => 4,
                    'type' => 'warning'
                ),
                'mentor_resources' => array(
                    'title' => __('Mentor Resources', 'dasher'),
                    'enabled' => true,
                    'size' => 'small',
                    'order' => 5,
                    'type' => 'info'
                ),
                'lccp_overview' => array(
                    'title' => __('LCCP Hours Overview', 'dasher'),
                    'enabled' => true,
                    'size' => 'small',
                    'order' => 6,
                    'type' => 'success'
                )
            ),
            'bigbird' => array(
                'students_overview' => array(
                    'title' => __('My Students', 'dasher'),
                    'enabled' => true,
                    'size' => 'large',
                    'order' => 1,
                    'type' => 'primary'
                ),
                'success_rate' => array(
                    'title' => __('Success Rate', 'dasher'),
                    'enabled' => true,
                    'size' => 'medium',
                    'order' => 2,
                    'type' => 'success'
                ),
                'engagement_score' => array(
                    'title' => __('Engagement', 'dasher'),
                    'enabled' => true,
                    'size' => 'medium',
                    'order' => 3,
                    'type' => 'info'
                ),
                'communication_status' => array(
                    'title' => __('Communications', 'dasher'),
                    'enabled' => true,
                    'size' => 'medium',
                    'order' => 4,
                    'type' => 'warning'
                ),
                'bigbird_resources' => array(
                    'title' => __('BigBird Resources', 'dasher'),
                    'enabled' => true,
                    'size' => 'small',
                    'order' => 5,
                    'type' => 'success'
                ),
                'lccp_progress' => array(
                    'title' => __('LCCP Progress', 'dasher'),
                    'enabled' => true,
                    'size' => 'small',
                    'order' => 6,
                    'type' => 'info'
                ),
                'training_support' => array(
                    'title' => __('Training Support', 'dasher'),
                    'enabled' => false,
                    'size' => 'small',
                    'order' => 7,
                    'type' => 'warning'
                )
            ),
            'pc' => array(
                'learning_progress' => array(
                    'title' => __('Learning Progress', 'dasher'),
                    'enabled' => true,
                    'size' => 'large',
                    'order' => 1,
                    'type' => 'primary'
                ),
                'lccp_hours' => array(
                    'title' => __('LCCP Hours', 'dasher'),
                    'enabled' => true,
                    'size' => 'medium',
                    'order' => 2,
                    'type' => 'success'
                ),
                'community_messages' => array(
                    'title' => __('Community Messages', 'dasher'),
                    'enabled' => true,
                    'size' => 'medium',
                    'order' => 3,
                    'type' => 'info'
                ),
                'achievements' => array(
                    'title' => __('Recent Achievements', 'dasher'),
                    'enabled' => true,
                    'size' => 'medium',
                    'order' => 4,
                    'type' => 'warning'
                )
            )
        );
        
        return isset($configs[$dashboard_type]) ? $configs[$dashboard_type] : array();
    }
    
    /**
     * Get user's card settings for a dashboard type
     */
    public function get_user_card_settings($user_id, $dashboard_type) {
        $saved_settings = get_user_meta($user_id, "dasher_dashboard_settings_{$dashboard_type}", true);
        $default_settings = $this->get_default_card_config($dashboard_type);
        
        if (empty($saved_settings) || !is_array($saved_settings)) {
            return $default_settings;
        }
        
        // Merge with defaults to ensure all cards are present
        return array_merge($default_settings, $saved_settings);
    }
    
    /**
     * Save card settings
     */
    public function save_card_settings() {
        if (!wp_verify_nonce($_POST['nonce'], 'dasher_customizer_nonce')) {
            wp_die('Security check failed');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('User not logged in', 'dasher')));
        }
        
        $dashboard_type = sanitize_text_field($_POST['dashboard_type']);
        $settings = json_decode(stripslashes($_POST['settings']), true);
        
        if (!$settings || !is_array($settings)) {
            wp_send_json_error(array('message' => __('Invalid settings data', 'dasher')));
        }
        
        // Sanitize settings
        $sanitized_settings = array();
        foreach ($settings as $card_id => $card_settings) {
            $sanitized_settings[sanitize_key($card_id)] = array(
                'title' => sanitize_text_field($card_settings['title']),
                'enabled' => (bool) $card_settings['enabled'],
                'size' => sanitize_text_field($card_settings['size']),
                'order' => intval($card_settings['order']),
                'type' => sanitize_text_field($card_settings['type'])
            );
        }
        
        $saved = update_user_meta($user_id, "dasher_dashboard_settings_{$dashboard_type}", $sanitized_settings);
        
        if ($saved !== false) {
            wp_send_json_success(array('message' => __('Settings saved successfully!', 'dasher')));
        } else {
            wp_send_json_error(array('message' => __('Failed to save settings', 'dasher')));
        }
    }
    
    /**
     * Get card settings
     */
    public function get_card_settings() {
        if (!wp_verify_nonce($_POST['nonce'], 'dasher_customizer_nonce')) {
            wp_die('Security check failed');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('User not logged in', 'dasher')));
        }
        
        $dashboard_type = sanitize_text_field($_POST['dashboard_type']);
        $settings = $this->get_user_card_settings($user_id, $dashboard_type);
        
        wp_send_json_success($settings);
    }
    
    /**
     * Toggle card enabled/disabled
     */
    public function toggle_card() {
        if (!wp_verify_nonce($_POST['nonce'], 'dasher_customizer_nonce')) {
            wp_die('Security check failed');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('User not logged in', 'dasher')));
        }
        
        $dashboard_type = sanitize_text_field($_POST['dashboard_type']);
        $card_id = sanitize_key($_POST['card_id']);
        $enabled = (bool) $_POST['enabled'];
        
        $settings = $this->get_user_card_settings($user_id, $dashboard_type);
        
        if (isset($settings[$card_id])) {
            $settings[$card_id]['enabled'] = $enabled;
            update_user_meta($user_id, "dasher_dashboard_settings_{$dashboard_type}", $settings);
            wp_send_json_success(array('message' => __('Card updated successfully!', 'dasher')));
        } else {
            wp_send_json_error(array('message' => __('Card not found', 'dasher')));
        }
    }
    
    /**
     * Update card size
     */
    public function update_card_size() {
        if (!wp_verify_nonce($_POST['nonce'], 'dasher_customizer_nonce')) {
            wp_die('Security check failed');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('User not logged in', 'dasher')));
        }
        
        $dashboard_type = sanitize_text_field($_POST['dashboard_type']);
        $card_id = sanitize_key($_POST['card_id']);
        $size = sanitize_text_field($_POST['size']);
        
        if (!in_array($size, array('small', 'medium', 'large'))) {
            wp_send_json_error(array('message' => __('Invalid size', 'dasher')));
        }
        
        $settings = $this->get_user_card_settings($user_id, $dashboard_type);
        
        if (isset($settings[$card_id])) {
            $settings[$card_id]['size'] = $size;
            update_user_meta($user_id, "dasher_dashboard_settings_{$dashboard_type}", $settings);
            wp_send_json_success(array('message' => __('Card size updated!', 'dasher')));
        } else {
            wp_send_json_error(array('message' => __('Card not found', 'dasher')));
        }
    }
    
    /**
     * Reorder cards
     */
    public function reorder_cards() {
        if (!wp_verify_nonce($_POST['nonce'], 'dasher_customizer_nonce')) {
            wp_die('Security check failed');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('User not logged in', 'dasher')));
        }
        
        $dashboard_type = sanitize_text_field($_POST['dashboard_type']);
        $card_order = array_map('sanitize_key', $_POST['card_order']);
        
        $settings = $this->get_user_card_settings($user_id, $dashboard_type);
        
        // Update order
        foreach ($card_order as $index => $card_id) {
            if (isset($settings[$card_id])) {
                $settings[$card_id]['order'] = $index + 1;
            }
        }
        
        update_user_meta($user_id, "dasher_dashboard_settings_{$dashboard_type}", $settings);
        wp_send_json_success(array('message' => __('Cards reordered successfully!', 'dasher')));
    }
    
    /**
     * Reset dashboard to defaults
     */
    public function reset_dashboard() {
        if (!wp_verify_nonce($_POST['nonce'], 'dasher_customizer_nonce')) {
            wp_die('Security check failed');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('User not logged in', 'dasher')));
        }
        
        $dashboard_type = sanitize_text_field($_POST['dashboard_type']);
        
        delete_user_meta($user_id, "dasher_dashboard_settings_{$dashboard_type}");
        wp_send_json_success(array('message' => __('Dashboard reset to defaults!', 'dasher')));
    }
    
    /**
     * Render customizer panel
     */
    public static function render_customizer_panel($dashboard_type) {
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            return '';
        }
        
        $customizer = new self();
        $settings = $customizer->get_user_card_settings($current_user_id, $dashboard_type);
        
        ob_start();
        ?>
        <div id="dasher-customizer-panel" class="dasher-customizer-panel" style="display: none;">
            <div class="dasher-customizer-overlay"></div>
            <div class="dasher-customizer-content">
                <div class="dasher-customizer-header">
                    <h3><?php esc_html_e('Customize Dashboard', 'dasher'); ?></h3>
                    <button type="button" class="dasher-customizer-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="dasher-customizer-body">
                    <div class="dasher-customizer-section">
                        <h4><?php esc_html_e('Dashboard Cards', 'dasher'); ?></h4>
                        <p class="description"><?php esc_html_e('Enable/disable cards, change their size, and drag to reorder.', 'dasher'); ?></p>
                        
                        <div id="dasher-card-list" class="dasher-card-list">
                            <?php foreach ($settings as $card_id => $card_settings) : ?>
                            <div class="dasher-card-setting" data-card-id="<?php echo esc_attr($card_id); ?>">
                                <div class="dasher-card-setting-header">
                                    <div class="dasher-drag-handle">
                                        <i class="fas fa-grip-vertical"></i>
                                    </div>
                                    <div class="dasher-card-info">
                                        <span class="dasher-card-title"><?php echo esc_html($card_settings['title']); ?></span>
                                        <span class="dasher-card-type dasher-type-<?php echo esc_attr($card_settings['type']); ?>">
                                            <?php echo esc_html(ucfirst($card_settings['type'])); ?>
                                        </span>
                                    </div>
                                    <div class="dasher-card-controls">
                                        <select class="dasher-card-size" data-card-id="<?php echo esc_attr($card_id); ?>">
                                            <option value="small" <?php selected($card_settings['size'], 'small'); ?>><?php esc_html_e('Small', 'dasher'); ?></option>
                                            <option value="medium" <?php selected($card_settings['size'], 'medium'); ?>><?php esc_html_e('Medium', 'dasher'); ?></option>
                                            <option value="large" <?php selected($card_settings['size'], 'large'); ?>><?php esc_html_e('Large', 'dasher'); ?></option>
                                        </select>
                                        <label class="dasher-toggle-switch">
                                            <input type="checkbox" class="dasher-card-toggle" 
                                                   data-card-id="<?php echo esc_attr($card_id); ?>" 
                                                   <?php checked($card_settings['enabled']); ?>>
                                            <span class="dasher-toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="dasher-customizer-footer">
                    <button type="button" class="dasher-btn dasher-btn-outline" id="dasher-reset-dashboard">
                        <i class="fas fa-undo"></i>
                        <?php esc_html_e('Reset to Defaults', 'dasher'); ?>
                    </button>
                    <button type="button" class="dasher-btn dasher-btn-primary" id="dasher-save-settings">
                        <i class="fas fa-save"></i>
                        <?php esc_html_e('Save Settings', 'dasher'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <script>
        window.dasher_dashboard_type = '<?php echo esc_js($dashboard_type); ?>';
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Add customize button to dashboard header
     */
    public static function render_customize_button() {
        if (!is_user_logged_in()) {
            return '';
        }
        
        return '<button type="button" class="dasher-btn dasher-btn-outline dasher-customize-btn" id="dasher-open-customizer">
            <i class="fas fa-cog"></i>
            ' . esc_html__('Customize', 'dasher') . '
        </button>';
    }
}

// Initialize the customizer
new Dasher_Dashboard_Customizer();