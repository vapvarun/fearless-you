<?php
/**
 * LCCP Dasher Dashboard Module
 * 
 * Provides enhanced dashboard functionality for LCCP students
 * 
 * @package LCCP_Systems
 * @subpackage Modules
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Dasher_Dashboard {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Initialize dashboard hooks
        add_action('init', array($this, 'init'));
        
        // Add dashboard shortcode
        add_shortcode('lccp_dasher', array($this, 'render_dashboard_shortcode'));
        
        // Add menu item for students
        add_action('admin_menu', array($this, 'add_dashboard_menu'), 20);
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_lccp_dasher_get_progress', array($this, 'ajax_get_progress'));
        add_action('wp_ajax_lccp_dasher_update_milestone', array($this, 'ajax_update_milestone'));
    }
    
    public function init() {
        // Register custom post type for milestones if needed
        $this->register_milestone_post_type();
    }
    
    private function register_milestone_post_type() {
        $labels = array(
            'name' => __('LCCP Milestones', 'lccp-systems'),
            'singular_name' => __('Milestone', 'lccp-systems'),
            'menu_name' => __('Milestones', 'lccp-systems'),
            'add_new' => __('Add New Milestone', 'lccp-systems'),
            'add_new_item' => __('Add New Milestone', 'lccp-systems'),
            'edit_item' => __('Edit Milestone', 'lccp-systems'),
            'new_item' => __('New Milestone', 'lccp-systems'),
            'view_item' => __('View Milestone', 'lccp-systems'),
            'search_items' => __('Search Milestones', 'lccp-systems'),
            'not_found' => __('No milestones found', 'lccp-systems'),
            'not_found_in_trash' => __('No milestones found in trash', 'lccp-systems')
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'custom-fields'),
            'has_archive' => false,
            'rewrite' => false
        );
        
        register_post_type('lccp_milestone', $args);
    }
    
    public function add_dashboard_menu() {
        // Add submenu under LCCP Systems
        add_submenu_page(
            'lccp-systems',
            __('Student Dashboard', 'lccp-systems'),
            __('My Dashboard', 'lccp-systems'),
            'read',
            'lccp-dasher',
            array($this, 'render_admin_dashboard')
        );
    }
    
    public function render_admin_dashboard() {
        $current_user = wp_get_current_user();
        ?>
        <div class="wrap lccp-dasher-dashboard">
            <h1><?php printf(__('Welcome, %s!', 'lccp-systems'), esc_html($current_user->display_name)); ?></h1>
            
            <div class="lccp-dasher-container">
                <?php $this->render_dashboard_content($current_user->ID); ?>
            </div>
        </div>
        <?php
    }
    
    public function render_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your dashboard.', 'lccp-systems') . '</p>';
        }
        
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'show_progress' => 'yes',
            'show_milestones' => 'yes',
            'show_hours' => 'yes'
        ), $atts);
        
        ob_start();
        ?>
        <div class="lccp-dasher-frontend">
            <?php $this->render_dashboard_content($atts['user_id'], $atts); ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function render_dashboard_content($user_id, $options = array()) {
        $defaults = array(
            'show_progress' => 'yes',
            'show_milestones' => 'yes',
            'show_hours' => 'yes'
        );
        $options = wp_parse_args($options, $defaults);
        
        ?>
        <div class="lccp-dasher-grid">
            <?php if ($options['show_progress'] === 'yes'): ?>
                <div class="lccp-dasher-card">
                    <h2><?php _e('Your Progress', 'lccp-systems'); ?></h2>
                    <?php $this->render_progress_section($user_id); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($options['show_milestones'] === 'yes'): ?>
                <div class="lccp-dasher-card">
                    <h2><?php _e('Certification Milestones', 'lccp-systems'); ?></h2>
                    <?php $this->render_milestones_section($user_id); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($options['show_hours'] === 'yes'): ?>
                <div class="lccp-dasher-card">
                    <h2><?php _e('Coaching Hours', 'lccp-systems'); ?></h2>
                    <?php $this->render_hours_section($user_id); ?>
                </div>
            <?php endif; ?>
            
            <div class="lccp-dasher-card">
                <h2><?php _e('Quick Actions', 'lccp-systems'); ?></h2>
                <?php $this->render_quick_actions($user_id); ?>
            </div>
        </div>
        <?php
    }
    
    private function render_progress_section($user_id) {
        // Get LearnDash progress if available
        $progress_data = $this->get_user_progress($user_id);
        
        ?>
        <div class="lccp-progress-section">
            <div class="lccp-progress-bar">
                <div class="lccp-progress-fill" style="width: <?php echo esc_attr($progress_data['percentage']); ?>%;">
                    <span><?php echo esc_html($progress_data['percentage']); ?>%</span>
                </div>
            </div>
            
            <div class="lccp-progress-stats">
                <div class="lccp-stat">
                    <span class="lccp-stat-label"><?php _e('Courses Completed:', 'lccp-systems'); ?></span>
                    <span class="lccp-stat-value"><?php echo esc_html($progress_data['courses_completed']); ?> / <?php echo esc_html($progress_data['total_courses']); ?></span>
                </div>
                <div class="lccp-stat">
                    <span class="lccp-stat-label"><?php _e('Current Phase:', 'lccp-systems'); ?></span>
                    <span class="lccp-stat-value"><?php echo esc_html($progress_data['current_phase']); ?></span>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_milestones_section($user_id) {
        $milestones = $this->get_user_milestones($user_id);
        
        ?>
        <div class="lccp-milestones-section">
            <ul class="lccp-milestone-list">
                <?php foreach ($milestones as $milestone): ?>
                    <li class="lccp-milestone-item <?php echo $milestone['completed'] ? 'completed' : ''; ?>">
                        <span class="lccp-milestone-icon">
                            <?php if ($milestone['completed']): ?>
                                <span class="dashicons dashicons-yes-alt"></span>
                            <?php else: ?>
                                <span class="dashicons dashicons-marker"></span>
                            <?php endif; ?>
                        </span>
                        <span class="lccp-milestone-title"><?php echo esc_html($milestone['title']); ?></span>
                        <?php if ($milestone['date']): ?>
                            <span class="lccp-milestone-date"><?php echo esc_html($milestone['date']); ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }
    
    private function render_hours_section($user_id) {
        global $wpdb;
        
        // Get hour tracking data
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        $total_hours = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(session_length) FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        $required_hours = 75; // LCCP requirement
        $percentage = min(100, ($total_hours / $required_hours) * 100);
        
        ?>
        <div class="lccp-hours-section">
            <div class="lccp-hours-circle">
                <svg width="120" height="120">
                    <circle cx="60" cy="60" r="54" fill="none" stroke="#e0e0e0" stroke-width="8"/>
                    <circle cx="60" cy="60" r="54" fill="none" stroke="#667eea" stroke-width="8"
                            stroke-dasharray="<?php echo 339.292 * ($percentage / 100); ?> 339.292"
                            transform="rotate(-90 60 60)"/>
                </svg>
                <div class="lccp-hours-text">
                    <span class="lccp-hours-number"><?php echo number_format($total_hours, 1); ?></span>
                    <span class="lccp-hours-label"><?php _e('hours', 'lccp-systems'); ?></span>
                </div>
            </div>
            
            <div class="lccp-hours-info">
                <p><?php printf(__('You have completed %s of %d required coaching hours.', 'lccp-systems'), 
                    '<strong>' . number_format($total_hours, 1) . '</strong>', 
                    $required_hours); ?></p>
                
                <?php if ($total_hours >= $required_hours): ?>
                    <p class="lccp-success-message">
                        <span class="dashicons dashicons-awards"></span>
                        <?php _e('Congratulations! You have met the hour requirement!', 'lccp-systems'); ?>
                    </p>
                <?php else: ?>
                    <p><?php printf(__('You need %s more hours to complete the requirement.', 'lccp-systems'), 
                        '<strong>' . number_format($required_hours - $total_hours, 1) . '</strong>'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    private function render_quick_actions($user_id) {
        ?>
        <div class="lccp-quick-actions">
            <a href="<?php echo admin_url('admin.php?page=lccp-hour-tracker'); ?>" class="button button-primary">
                <span class="dashicons dashicons-clock"></span>
                <?php _e('Log Coaching Hours', 'lccp-systems'); ?>
            </a>
            
            <?php if (class_exists('SFWD_LMS')): ?>
                <a href="<?php echo esc_url(learndash_get_course_list_url()); ?>" class="button">
                    <span class="dashicons dashicons-welcome-learn-more"></span>
                    <?php _e('Continue Learning', 'lccp-systems'); ?>
                </a>
            <?php endif; ?>
            
            <a href="<?php echo get_permalink(get_option('lccp_resources_page_id')); ?>" class="button">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Resources', 'lccp-systems'); ?>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=lccp-messages'); ?>" class="button">
                <span class="dashicons dashicons-email"></span>
                <?php _e('Messages', 'lccp-systems'); ?>
            </a>
        </div>
        <?php
    }
    
    private function get_user_progress($user_id) {
        $progress = array(
            'percentage' => 0,
            'courses_completed' => 0,
            'total_courses' => 0,
            'current_phase' => __('Getting Started', 'lccp-systems')
        );
        
        // If LearnDash is active, get real progress
        if (function_exists('learndash_user_get_course_progress')) {
            $courses = learndash_user_get_enrolled_courses($user_id);
            $completed = 0;
            
            foreach ($courses as $course_id) {
                $course_progress = learndash_user_get_course_progress($user_id, $course_id);
                if ($course_progress['percentage'] == 100) {
                    $completed++;
                }
            }
            
            $progress['total_courses'] = count($courses);
            $progress['courses_completed'] = $completed;
            
            if ($progress['total_courses'] > 0) {
                $progress['percentage'] = round(($completed / $progress['total_courses']) * 100);
            }
            
            // Determine phase based on progress
            if ($progress['percentage'] < 25) {
                $progress['current_phase'] = __('Foundation', 'lccp-systems');
            } elseif ($progress['percentage'] < 50) {
                $progress['current_phase'] = __('Skills Development', 'lccp-systems');
            } elseif ($progress['percentage'] < 75) {
                $progress['current_phase'] = __('Practice Integration', 'lccp-systems');
            } else {
                $progress['current_phase'] = __('Certification Ready', 'lccp-systems');
            }
        }
        
        return $progress;
    }
    
    private function get_user_milestones($user_id) {
        // Define LCCP milestones
        $milestones = array(
            array(
                'id' => 'enrollment',
                'title' => __('Program Enrollment', 'lccp-systems'),
                'completed' => true,
                'date' => get_user_meta($user_id, 'lccp_enrollment_date', true)
            ),
            array(
                'id' => 'foundation',
                'title' => __('Complete Foundation Training', 'lccp-systems'),
                'completed' => $this->check_milestone_completion($user_id, 'foundation'),
                'date' => get_user_meta($user_id, 'lccp_foundation_complete_date', true)
            ),
            array(
                'id' => 'first_client',
                'title' => __('First Practice Client', 'lccp-systems'),
                'completed' => $this->check_milestone_completion($user_id, 'first_client'),
                'date' => get_user_meta($user_id, 'lccp_first_client_date', true)
            ),
            array(
                'id' => 'hours_25',
                'title' => __('25 Coaching Hours', 'lccp-systems'),
                'completed' => $this->check_hours_milestone($user_id, 25),
                'date' => null
            ),
            array(
                'id' => 'hours_50',
                'title' => __('50 Coaching Hours', 'lccp-systems'),
                'completed' => $this->check_hours_milestone($user_id, 50),
                'date' => null
            ),
            array(
                'id' => 'hours_75',
                'title' => __('75 Coaching Hours Complete', 'lccp-systems'),
                'completed' => $this->check_hours_milestone($user_id, 75),
                'date' => null
            ),
            array(
                'id' => 'mentor_sessions',
                'title' => __('Complete Mentor Sessions', 'lccp-systems'),
                'completed' => $this->check_milestone_completion($user_id, 'mentor_sessions'),
                'date' => null
            ),
            array(
                'id' => 'final_exam',
                'title' => __('Pass Final Exam', 'lccp-systems'),
                'completed' => $this->check_milestone_completion($user_id, 'final_exam'),
                'date' => get_user_meta($user_id, 'lccp_exam_pass_date', true)
            ),
            array(
                'id' => 'certification',
                'title' => __('Receive Certification', 'lccp-systems'),
                'completed' => $this->check_milestone_completion($user_id, 'certification'),
                'date' => get_user_meta($user_id, 'lccp_certification_date', true)
            )
        );
        
        return $milestones;
    }
    
    private function check_milestone_completion($user_id, $milestone_id) {
        return get_user_meta($user_id, 'lccp_milestone_' . $milestone_id, true) === 'completed';
    }
    
    private function check_hours_milestone($user_id, $hours_required) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        $total_hours = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(session_length) FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        return $total_hours >= $hours_required;
    }
    
    public function ajax_get_progress() {
        check_ajax_referer('lccp_dasher_nonce', 'nonce');
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : get_current_user_id();
        
        if (!current_user_can('read') && $user_id !== get_current_user_id()) {
            wp_send_json_error('Unauthorized');
        }
        
        $progress = $this->get_user_progress($user_id);
        wp_send_json_success($progress);
    }
    
    public function ajax_update_milestone() {
        check_ajax_referer('lccp_dasher_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $milestone_id = isset($_POST['milestone_id']) ? sanitize_text_field($_POST['milestone_id']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'pending';
        
        if ($user_id && $milestone_id) {
            update_user_meta($user_id, 'lccp_milestone_' . $milestone_id, $status);
            
            if ($status === 'completed') {
                update_user_meta($user_id, 'lccp_' . $milestone_id . '_date', current_time('mysql'));
            }
            
            wp_send_json_success('Milestone updated');
        }
        
        wp_send_json_error('Invalid parameters');
    }
    
    public function enqueue_frontend_assets() {
        if (!is_admin()) {
            wp_enqueue_style(
                'lccp-dasher-frontend',
                LCCP_SYSTEMS_PLUGIN_URL . 'assets/css/dasher-frontend.css',
                array(),
                LCCP_SYSTEMS_VERSION
            );
            
            wp_enqueue_script(
                'lccp-dasher-frontend',
                LCCP_SYSTEMS_PLUGIN_URL . 'assets/js/dasher-frontend.js',
                array('jquery'),
                LCCP_SYSTEMS_VERSION,
                true
            );
            
            wp_localize_script('lccp-dasher-frontend', 'lccp_dasher', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('lccp_dasher_nonce')
            ));
        }
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'lccp-dasher') !== false) {
            wp_enqueue_style(
                'lccp-dasher-admin',
                LCCP_SYSTEMS_PLUGIN_URL . 'assets/css/dasher-admin.css',
                array(),
                LCCP_SYSTEMS_VERSION
            );
            
            wp_enqueue_script(
                'lccp-dasher-admin',
                LCCP_SYSTEMS_PLUGIN_URL . 'assets/js/dasher-admin.js',
                array('jquery'),
                LCCP_SYSTEMS_VERSION,
                true
            );
            
            // Add inline styles for quick styling
            $custom_css = '
                .lccp-dasher-dashboard {
                    max-width: 1200px;
                }
                .lccp-dasher-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: 20px;
                    margin-top: 20px;
                }
                .lccp-dasher-card {
                    background: white;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    padding: 20px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                }
                .lccp-dasher-card h2 {
                    margin-top: 0;
                    color: #333;
                    font-size: 18px;
                    border-bottom: 2px solid #667eea;
                    padding-bottom: 10px;
                }
                .lccp-progress-bar {
                    background: #e0e0e0;
                    border-radius: 10px;
                    height: 30px;
                    overflow: hidden;
                    margin: 20px 0;
                }
                .lccp-progress-fill {
                    background: linear-gradient(90deg, #667eea, #764ba2);
                    height: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-weight: bold;
                    transition: width 0.3s ease;
                }
                .lccp-milestone-list {
                    list-style: none;
                    padding: 0;
                    margin: 0;
                }
                .lccp-milestone-item {
                    display: flex;
                    align-items: center;
                    padding: 10px 0;
                    border-bottom: 1px solid #f0f0f0;
                }
                .lccp-milestone-item.completed {
                    color: #4caf50;
                }
                .lccp-milestone-icon {
                    margin-right: 10px;
                }
                .lccp-hours-circle {
                    position: relative;
                    display: inline-block;
                    margin: 20px auto;
                }
                .lccp-hours-text {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    text-align: center;
                }
                .lccp-hours-number {
                    display: block;
                    font-size: 24px;
                    font-weight: bold;
                    color: #667eea;
                }
                .lccp-hours-label {
                    display: block;
                    font-size: 12px;
                    color: #999;
                }
                .lccp-quick-actions {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 10px;
                }
                .lccp-quick-actions .button {
                    flex: 1;
                    min-width: 150px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 5px;
                }
                .lccp-stat {
                    display: flex;
                    justify-content: space-between;
                    padding: 8px 0;
                    border-bottom: 1px solid #f0f0f0;
                }
                .lccp-stat-label {
                    color: #666;
                }
                .lccp-stat-value {
                    font-weight: bold;
                    color: #333;
                }
                .lccp-success-message {
                    background: #d4edda;
                    color: #155724;
                    padding: 10px;
                    border-radius: 4px;
                    margin-top: 10px;
                    display: flex;
                    align-items: center;
                    gap: 5px;
                }
            ';
            wp_add_inline_style('lccp-dasher-admin', $custom_css);
        }
    }
}

// Initialize the module
LCCP_Dasher_Dashboard::get_instance();