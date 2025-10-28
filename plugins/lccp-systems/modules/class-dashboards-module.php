<?php
/**
 * Dashboards Module for LCCP Systems
 * Modular version with feature toggle support
 *
 * @package LCCP Systems
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Dashboards_Module extends LCCP_Module {
    
    protected $module_id = 'dashboards';
    protected $module_name = 'Role-Based Dashboards';
    protected $module_description = 'Provides role-based dashboards for Mentors, Big Birds, PCs, and Students with progress tracking and management tools.';
    protected $module_version = '1.0.0';
    protected $module_dependencies = array();
    protected $module_settings = array(
        'enable_mentor_dashboard' => true,
        'enable_big_bird_dashboard' => true,
        'enable_pc_dashboard' => true,
        'enable_student_dashboard' => true,
        'enable_shortcodes' => true,
        'enable_ajax' => true,
        'enable_widgets' => true,
        'cache_duration' => 600, // 10 minutes
        'show_mock_data' => true
    );
    
    protected function init() {
        // Only initialize if module is enabled
        if (!$this->is_enabled()) {
            return;
        }
        
        // Register shortcodes
        if ($this->get_setting('enable_shortcodes')) {
            $this->register_shortcodes();
        }
        
        // AJAX handlers
        if ($this->get_setting('enable_ajax')) {
            $this->register_ajax_handlers();
        }
        
        // Dashboard widgets for admin
        if ($this->get_setting('enable_widgets')) {
            add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
        }
        
        // Enqueue dashboard assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_dashboard_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_dashboard_assets'));
    }
    
    /**
     * Get a specific setting value
     */
    private function get_setting($key) {
        $settings = $this->get_settings();
        return isset($settings[$key]) ? $settings[$key] : null;
    }
    
    /**
     * Register shortcodes
     */
    private function register_shortcodes() {
        if ($this->get_setting('enable_mentor_dashboard')) {
            add_shortcode('lccp_mentor_dashboard', array($this, 'render_mentor_dashboard'));
            add_shortcode('dasher_mentor_dashboard', array($this, 'render_mentor_dashboard')); // Backward compatibility
        }
        
        if ($this->get_setting('enable_big_bird_dashboard')) {
            add_shortcode('lccp_big_bird_dashboard', array($this, 'render_big_bird_dashboard'));
            add_shortcode('dasher_big_bird_dashboard', array($this, 'render_big_bird_dashboard')); // Backward compatibility
        }
        
        if ($this->get_setting('enable_pc_dashboard')) {
            add_shortcode('lccp_pc_dashboard', array($this, 'render_pc_dashboard'));
            add_shortcode('dasher_pc_dashboard', array($this, 'render_pc_dashboard')); // Backward compatibility
        }
        
        if ($this->get_setting('enable_student_dashboard')) {
            add_shortcode('lccp_student_dashboard', array($this, 'render_student_dashboard'));
        }
    }
    
    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_lccp_get_student_details', array($this, 'ajax_get_student_details'));
        add_action('wp_ajax_lccp_assign_student', array($this, 'ajax_assign_student'));
        add_action('wp_ajax_lccp_unassign_student', array($this, 'ajax_unassign_student'));
        add_action('wp_ajax_lccp_get_student_progress', array($this, 'ajax_get_student_progress'));
        add_action('wp_ajax_lccp_send_message', array($this, 'ajax_send_message'));
    }
    
    /**
     * Render Mentor Dashboard
     */
    public function render_mentor_dashboard() {
        if (!is_user_logged_in()) {
            return '<p>Please log in to view your dashboard.</p>';
        }
        
        $current_user = wp_get_current_user();
        if (!in_array('lccp_mentor', $current_user->roles) && !in_array('administrator', $current_user->roles)) {
            return '<p>You do not have permission to access this dashboard.</p>';
        }
        
        // Check cache first
        $cache_key = 'lccp_mentor_dashboard_' . get_current_user_id();
        $cached_content = get_transient($cache_key);
        
        if ($cached_content && !$this->get_setting('show_mock_data')) {
            return $cached_content;
        }
        
        // Load template
        $template_path = LCCP_SYSTEMS_PLUGIN_DIR . 'templates/mentor-dashboard.php';
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            $content = ob_get_clean();
            
            // Cache the content
            if (!$this->get_setting('show_mock_data')) {
                set_transient($cache_key, $content, $this->get_setting('cache_duration'));
            }
            
            return $content;
        }
        
        return '<p>Dashboard template not found.</p>';
    }
    
    /**
     * Render Big Bird Dashboard
     */
    public function render_big_bird_dashboard() {
        if (!is_user_logged_in()) {
            return '<p>Please log in to view your dashboard.</p>';
        }
        
        $current_user = wp_get_current_user();
        if (!in_array('lccp_big_bird', $current_user->roles) && !in_array('administrator', $current_user->roles)) {
            return '<p>You do not have permission to access this dashboard.</p>';
        }
        
        // Check cache first
        $cache_key = 'lccp_big_bird_dashboard_' . get_current_user_id();
        $cached_content = get_transient($cache_key);
        
        if ($cached_content && !$this->get_setting('show_mock_data')) {
            return $cached_content;
        }
        
        // Load template
        $template_path = LCCP_SYSTEMS_PLUGIN_DIR . 'templates/big-bird-dashboard.php';
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            $content = ob_get_clean();
            
            // Cache the content
            if (!$this->get_setting('show_mock_data')) {
                set_transient($cache_key, $content, $this->get_setting('cache_duration'));
            }
            
            return $content;
        }
        
        return '<p>Dashboard template not found.</p>';
    }
    
    /**
     * Render PC Dashboard
     */
    public function render_pc_dashboard() {
        if (!is_user_logged_in()) {
            return '<p>Please log in to view your dashboard.</p>';
        }
        
        $current_user = wp_get_current_user();
        if (!in_array('lccp_pc', $current_user->roles) && !in_array('administrator', $current_user->roles)) {
            return '<p>You do not have permission to access this dashboard.</p>';
        }
        
        // Check cache first
        $cache_key = 'lccp_pc_dashboard_' . get_current_user_id();
        $cached_content = get_transient($cache_key);
        
        if ($cached_content && !$this->get_setting('show_mock_data')) {
            return $cached_content;
        }
        
        // Load template
        $template_path = LCCP_SYSTEMS_PLUGIN_DIR . 'templates/pc-dashboard-enhanced.php';
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            $content = ob_get_clean();
            
            // Cache the content
            if (!$this->get_setting('show_mock_data')) {
                set_transient($cache_key, $content, $this->get_setting('cache_duration'));
            }
            
            return $content;
        }
        
        return '<p>Dashboard template not found.</p>';
    }
    
    /**
     * Render Student Dashboard
     */
    public function render_student_dashboard() {
        if (!is_user_logged_in()) {
            return '<p>Please log in to view your dashboard.</p>';
        }
        
        $current_user = wp_get_current_user();
        if (!in_array('subscriber', $current_user->roles) && !in_array('administrator', $current_user->roles)) {
            return '<p>You do not have permission to access this dashboard.</p>';
        }
        
        // Check cache first
        $cache_key = 'lccp_student_dashboard_' . get_current_user_id();
        $cached_content = get_transient($cache_key);
        
        if ($cached_content && !$this->get_setting('show_mock_data')) {
            return $cached_content;
        }
        
        // Generate student dashboard content
        ob_start();
        ?>
        <div class="lccp-student-dashboard">
            <div class="lccp-dashboard-header">
                <h2><?php echo sprintf(__('Welcome, %s!', 'lccp-systems'), $current_user->display_name); ?></h2>
                <p><?php esc_html_e('Track your learning progress and connect with your mentors.', 'lccp-systems'); ?></p>
            </div>
            
            <div class="lccp-student-content">
                <div class="lccp-student-progress">
                    <h3><?php esc_html_e('Your Progress', 'lccp-systems'); ?></h3>
                    <div class="lccp-progress-summary">
                        <div class="lccp-progress-item">
                            <span class="lccp-progress-label"><?php esc_html_e('Courses Completed', 'lccp-systems'); ?></span>
                            <span class="lccp-progress-value">0</span>
                        </div>
                        <div class="lccp-progress-item">
                            <span class="lccp-progress-label"><?php esc_html_e('Lessons Completed', 'lccp-systems'); ?></span>
                            <span class="lccp-progress-value">0</span>
                        </div>
                        <div class="lccp-progress-item">
                            <span class="lccp-progress-label"><?php esc_html_e('Certificates Earned', 'lccp-systems'); ?></span>
                            <span class="lccp-progress-value">0</span>
                        </div>
                    </div>
                </div>
                
                <div class="lccp-student-actions">
                    <h3><?php esc_html_e('Quick Actions', 'lccp-systems'); ?></h3>
                    <div class="lccp-action-buttons">
                        <a href="/courses" class="lccp-btn lccp-btn-primary">
                            <?php esc_html_e('View Courses', 'lccp-systems'); ?>
                        </a>
                        <a href="/profile" class="lccp-btn lccp-btn-secondary">
                            <?php esc_html_e('Update Profile', 'lccp-systems'); ?>
                        </a>
                        <a href="/contact" class="lccp-btn lccp-btn-secondary">
                            <?php esc_html_e('Contact Support', 'lccp-systems'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .lccp-student-dashboard {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .lccp-dashboard-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .lccp-dashboard-header h2 {
            margin: 0 0 10px 0;
            color: #23282d;
        }
        
        .lccp-student-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .lccp-student-progress,
        .lccp-student-actions {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .lccp-progress-summary {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .lccp-progress-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 4px;
        }
        
        .lccp-progress-label {
            font-weight: 500;
        }
        
        .lccp-progress-value {
            font-size: 1.2em;
            font-weight: bold;
            color: #007cba;
        }
        
        .lccp-action-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .lccp-btn {
            display: inline-block;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 4px;
            text-align: center;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .lccp-btn-primary {
            background-color: #007cba;
            color: white;
        }
        
        .lccp-btn-primary:hover {
            background-color: #005a87;
        }
        
        .lccp-btn-secondary {
            background-color: #f7f7f7;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .lccp-btn-secondary:hover {
            background-color: #e7e7e7;
        }
        
        @media (max-width: 768px) {
            .lccp-student-content {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
        
        $content = ob_get_clean();
        
        // Cache the content
        if (!$this->get_setting('show_mock_data')) {
            set_transient($cache_key, $content, $this->get_setting('cache_duration'));
        }
        
        return $content;
    }
    
    /**
     * Add dashboard widgets
     * DISABLED: Dashboard widget removed for optimization (v2.0.0)
     * Functionality consolidated into Enhanced Dashboards - Program Overview widget
     */
    public function add_dashboard_widgets() {
        // Widget disabled - duplicate of Enhanced Dashboards "Program Overview"
        // Dashboard optimization: 22 widgets reduced to 5 essential widgets

        /* REMOVED FOR OPTIMIZATION
        wp_add_dashboard_widget(
            'lccp_dashboard_overview',
            __('LCCP Systems Overview', 'lccp-systems'),
            array($this, 'render_dashboard_widget')
        );
        */
    }
    
    // render_dashboard_widget() - DELETED (v2.0.0 optimization)
    // get_system_stats() - DELETED (v2.0.0 optimization)
    // Functionality moved to Enhanced Dashboards - Program Overview widget
    
    /**
     * Enqueue dashboard assets
     */
    public function enqueue_dashboard_assets() {
        wp_enqueue_style(
            'lccp-dashboards',
            LCCP_SYSTEMS_PLUGIN_URL . 'assets/css/dashboards.css',
            array(),
            LCCP_SYSTEMS_VERSION
        );
        
        wp_enqueue_script(
            'lccp-dashboards',
            LCCP_SYSTEMS_PLUGIN_URL . 'assets/js/dashboards.js',
            array('jquery'),
            LCCP_SYSTEMS_VERSION,
            true
        );
        
        wp_localize_script('lccp-dashboards', 'lccp_dashboards', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lccp_dashboards_nonce')
        ));
    }
    
    /**
     * AJAX handler for getting student details
     */
    public function ajax_get_student_details() {
        check_ajax_referer('lccp_dashboards_nonce', 'nonce');
        
        if (!current_user_can('read')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $student_id = intval($_POST['student_id']);
        $student = get_user_by('id', $student_id);
        
        if (!$student) {
            wp_send_json_error('Student not found');
        }
        
        wp_send_json_success(array(
            'id' => $student->ID,
            'name' => $student->display_name,
            'email' => $student->user_email,
            'registered' => $student->user_registered
        ));
    }
    
    /**
     * AJAX handler for assigning students
     */
    public function ajax_assign_student() {
        check_ajax_referer('lccp_dashboards_nonce', 'nonce');
        
        if (!current_user_can('edit_users')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $student_id = intval($_POST['student_id']);
        $mentor_id = intval($_POST['mentor_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_assignments';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'student_id' => $student_id,
                'mentor_id' => $mentor_id,
                'assigned_date' => current_time('mysql')
            ),
            array('%d', '%d', '%s')
        );
        
        if ($result) {
            wp_send_json_success('Student assigned successfully');
        } else {
            wp_send_json_error('Failed to assign student');
        }
    }
    
    /**
     * AJAX handler for unassigning students
     */
    public function ajax_unassign_student() {
        check_ajax_referer('lccp_dashboards_nonce', 'nonce');
        
        if (!current_user_can('edit_users')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $student_id = intval($_POST['student_id']);
        $mentor_id = intval($_POST['mentor_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_assignments';
        
        $result = $wpdb->delete(
            $table_name,
            array(
                'student_id' => $student_id,
                'mentor_id' => $mentor_id
            ),
            array('%d', '%d')
        );
        
        if ($result) {
            wp_send_json_success('Student unassigned successfully');
        } else {
            wp_send_json_error('Failed to unassign student');
        }
    }
    
    /**
     * AJAX handler for getting student progress
     */
    public function ajax_get_student_progress() {
        check_ajax_referer('lccp_dashboards_nonce', 'nonce');
        
        if (!current_user_can('read')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $student_id = intval($_POST['student_id']);
        
        // Get student progress data
        $progress = array(
            'courses_completed' => 0,
            'lessons_completed' => 0,
            'total_hours' => 0,
            'last_activity' => 'Never'
        );
        
        wp_send_json_success($progress);
    }
    
    /**
     * AJAX handler for sending messages
     */
    public function ajax_send_message() {
        check_ajax_referer('lccp_dashboards_nonce', 'nonce');
        
        if (!current_user_can('read')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $recipient_id = intval($_POST['recipient_id']);
        $message = sanitize_textarea_field($_POST['message']);
        $subject = sanitize_text_field($_POST['subject']);
        
        // Send message logic here
        wp_send_json_success('Message sent successfully');
    }
    
    /**
     * Called when module is activated
     */
    protected function on_activate() {
        // Create database tables if needed
        $this->create_database_tables();
    }
    
    /**
     * Called when module is deactivated
     */
    protected function on_deactivate() {
        // Clear cached dashboard content
        $this->clear_dashboard_cache();
    }
    
    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Student assignments table
        $table_name = $wpdb->prefix . 'lccp_assignments';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            mentor_id bigint(20) DEFAULT NULL,
            big_bird_id bigint(20) DEFAULT NULL,
            pc_id bigint(20) DEFAULT NULL,
            assigned_date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY (id),
            KEY student_id (student_id),
            KEY mentor_id (mentor_id),
            KEY big_bird_id (big_bird_id),
            KEY pc_id (pc_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Clear dashboard cache
     */
    private function clear_dashboard_cache() {
        global $wpdb;
        
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_lccp_%_dashboard_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_lccp_%_dashboard_%'");
    }
}
