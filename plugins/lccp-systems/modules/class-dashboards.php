<?php
/**
 * LCCP Comprehensive Dashboards Module
 * 
 * Provides front-end dashboards for all LCCP roles:
 * - Program Coordinators (PC)
 * - Big Birds
 * - Mentors
 * - Students
 * 
 * All dashboards are front-end only since only admins access WP admin
 * 
 * @package LCCP_Systems
 * @subpackage Modules
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Dashboards {
    
    private static $instance = null;
    private $dashboard_pages = array();
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Initialize dashboard system
        add_action('init', array($this, 'init'), 5);
        
        // Register shortcodes for each dashboard type
        add_shortcode('lccp_dashboard', array($this, 'render_dashboard_shortcode'));
        add_shortcode('lccp_pc_dashboard', array($this, 'render_pc_dashboard'));
        add_shortcode('lccp_big_bird_dashboard', array($this, 'render_big_bird_dashboard'));
        add_shortcode('lccp_mentor_dashboard', array($this, 'render_mentor_dashboard'));
        add_shortcode('lccp_student_dashboard', array($this, 'render_student_dashboard'));
        
        // Auto-redirect users to their dashboard after login
        add_filter('login_redirect', array($this, 'dashboard_login_redirect'), 10, 3);

        // Add dashboard links to BuddyBoss profile menu - DISABLED: Community part is now optional
        // add_action('bp_setup_nav', array($this, 'add_dashboard_to_profile'), 100);

        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // AJAX handlers for dashboard widgets
        add_action('wp_ajax_lccp_refresh_widget', array($this, 'ajax_refresh_widget'));
        add_action('wp_ajax_lccp_save_widget_settings', array($this, 'ajax_save_widget_settings'));
        add_action('wp_ajax_lccp_get_dashboard_data', array($this, 'ajax_get_dashboard_data'));
        
        // Create dashboard pages if they don't exist
        add_action('init', array($this, 'create_dashboard_pages'), 20);
    }
    
    public function init() {
        // Register custom roles if they don't exist
        $this->register_lccp_roles();
        
        // Initialize widget system
        $this->register_dashboard_widgets();
    }
    
    /**
     * Register LCCP-specific roles (PC role removed)
     */
    private function register_lccp_roles() {
        // Program Coordinator role intentionally not registered anymore
        
        // Big Bird role
        if (!get_role('lccp_big_bird')) {
            add_role('lccp_big_bird', __('Big Bird', 'lccp-systems'), array(
                'read' => true,
                'edit_posts' => true,
                'upload_files' => true,
                'manage_lccp_students' => true,
                'view_big_bird_dashboard' => true
            ));
        }
        
        // Mentor role
        if (!get_role('lccp_mentor')) {
            add_role('lccp_mentor', __('LCCP Mentor', 'lccp-systems'), array(
                'read' => true,
                'edit_posts' => false,
                'upload_files' => true,
                'manage_assigned_students' => true,
                'view_mentor_dashboard' => true
            ));
        }
        
        // Add capabilities to existing subscriber role for students
        $subscriber = get_role('subscriber');
        if ($subscriber) {
            $subscriber->add_cap('view_student_dashboard', true);
        }
    }
    
    /**
     * Create dashboard pages
     */
    public function create_dashboard_pages() {
        // Check if pages already exist
        $existing_pages = get_option('lccp_dashboard_pages', array());
        
        $pages_to_create = array(
            'main' => array(
                'title' => 'LCCP Dashboard',
                'slug' => 'lccp-dashboard',
                'content' => '[lccp_dashboard]',
                'parent' => 0
            ),
            'pc' => array(
                'title' => 'Program Candidate Dashboard',
                'slug' => 'pc-dashboard',
                'content' => '[lccp_pc_dashboard]',
                'parent' => 'main'
            ),
            'bigbird' => array(
                'title' => 'Big Bird Dashboard',
                'slug' => 'big-bird-dashboard',
                'content' => '[lccp_big_bird_dashboard]',
                'parent' => 'main'
            ),
            'mentor' => array(
                'title' => 'Mentor Dashboard',
                'slug' => 'mentor-dashboard',
                'content' => '[lccp_mentor_dashboard]',
                'parent' => 'main'
            ),
            'student' => array(
                'title' => 'Student Dashboard',
                'slug' => 'student-dashboard',
                'content' => '[lccp_student_dashboard]',
                'parent' => 'main'
            )
        );
        
        foreach ($pages_to_create as $key => $page_data) {
            if (empty($existing_pages[$key])) {
                $parent_id = 0;
                if ($page_data['parent'] && $page_data['parent'] !== 0) {
                    $parent_id = isset($existing_pages[$page_data['parent']]) ? $existing_pages[$page_data['parent']] : 0;
                }
                
                $page_id = wp_insert_post(array(
                    'post_title' => $page_data['title'],
                    'post_name' => $page_data['slug'],
                    'post_content' => $page_data['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_parent' => $parent_id,
                    'meta_input' => array(
                        '_lccp_dashboard_type' => $key
                    )
                ));
                
                if ($page_id && !is_wp_error($page_id)) {
                    $existing_pages[$key] = $page_id;
                }
            }
        }
        
        update_option('lccp_dashboard_pages', $existing_pages);
        $this->dashboard_pages = $existing_pages;
    }
    
    /**
     * Main dashboard shortcode - auto-detects user role
     */
    public function render_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="lccp-dashboard-login">' . 
                   '<p>' . __('Please log in to view your dashboard.', 'lccp-systems') . '</p>' .
                   '<a href="' . wp_login_url(get_permalink()) . '" class="button">' . __('Log In', 'lccp-systems') . '</a>' .
                   '</div>';
        }
        
        $user = wp_get_current_user();
        
        // Determine which dashboard to show based on role
        if (in_array('lccp_pc', $user->roles) || in_array('administrator', $user->roles)) {
            return $this->render_pc_dashboard($atts);
        } elseif (in_array('lccp_big_bird', $user->roles)) {
            return $this->render_big_bird_dashboard($atts);
        } elseif (in_array('lccp_mentor', $user->roles)) {
            return $this->render_mentor_dashboard($atts);
        } else {
            return $this->render_student_dashboard($atts);
        }
    }
    
    /**
     * Program Candidate (PC) Dashboard
     */
    public function render_pc_dashboard($atts) {
        if (!current_user_can('read') || (!in_array('lccp_pc', wp_get_current_user()->roles) && !current_user_can('administrator'))) {
            return '<p>' . __('You do not have permission to view this dashboard.', 'lccp-systems') . '</p>';
        }
        ob_start();
        ?>
        <div class="lccp-dashboard lccp-pc-dashboard" data-role="program-candidate">
            <div class="lccp-dashboard-header">
                <h1><?php _e('Program Candidate Dashboard', 'lccp-systems'); ?></h1>
                <div class="lccp-dashboard-actions">
                    <button class="lccp-customize-dashboard"><?php _e('Customize', 'lccp-systems'); ?></button>
                    <button class="lccp-refresh-dashboard"><?php _e('Refresh', 'lccp-systems'); ?></button>
                </div>
            </div>

            <div class="lccp-dashboard-widgets">
                <div class="lccp-widget lccp-widget-full" data-widget="program-overview">
                    <div class="lccp-widget-header">
                        <h2><?php _e('Program Overview', 'lccp-systems'); ?></h2>
                    </div>
                    <div class="lccp-widget-content">
                        <?php echo $this->render_program_overview_widget(); ?>
                    </div>
                </div>

                <div class="lccp-widget lccp-widget-half" data-widget="student-stats">
                    <div class="lccp-widget-header">
                        <h2><?php _e('My Progress', 'lccp-systems'); ?></h2>
                    </div>
                    <div class="lccp-widget-content">
                        <?php echo $this->render_student_stats_widget(); ?>
                    </div>
                </div>

                <div class="lccp-widget lccp-widget-half" data-widget="resources">
                    <div class="lccp-widget-header">
                        <h2><?php _e('Resources', 'lccp-systems'); ?></h2>
                    </div>
                    <div class="lccp-widget-content">
                        <?php echo $this->render_resources_widget('student'); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Big Bird Dashboard
     */
    public function render_big_bird_dashboard($atts) {
        if (!current_user_can('manage_lccp_students') && !current_user_can('administrator')) {
            return '<p>' . __('You do not have permission to view this dashboard.', 'lccp-systems') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="lccp-dashboard lccp-big-bird-dashboard" data-role="bigbird">
            <div class="lccp-dashboard-header">
                <h1><?php _e('Big Bird Dashboard', 'lccp-systems'); ?></h1>
                <div class="lccp-dashboard-actions">
                    <button class="lccp-customize-dashboard"><?php _e('Customize', 'lccp-systems'); ?></button>
                    <button class="lccp-refresh-dashboard"><?php _e('Refresh', 'lccp-systems'); ?></button>
                </div>
            </div>
            
            <div class="lccp-dashboard-widgets">
                <!-- Assigned Students -->
                <div class="lccp-widget lccp-widget-full" data-widget="assigned-students">
                    <div class="lccp-widget-header">
                        <h2><?php _e('My Assigned Students', 'lccp-systems'); ?></h2>
                    </div>
                    <div class="lccp-widget-content">
                        <?php echo $this->render_assigned_students_widget(get_current_user_id(), 'bigbird'); ?>
                    </div>
                </div>
                
                <!-- Student Progress Tracking -->
                <div class="lccp-widget lccp-widget-half" data-widget="student-progress">
                    <div class="lccp-widget-header">
                        <h2><?php _e('Student Progress', 'lccp-systems'); ?></h2>
                    </div>
                    <div class="lccp-widget-content">
                        <?php echo $this->render_student_progress_widget('bigbird'); ?>
                    </div>
                </div>
                
                <!-- Upcoming Sessions -->
                <div class="lccp-widget lccp-widget-half" data-widget="upcoming-sessions">
                    <div class="lccp-widget-header">
                        <h2><?php _e('Upcoming Sessions', 'lccp-systems'); ?></h2>
                    </div>
                    <div class="lccp-widget-content">
                        <?php echo $this->render_upcoming_sessions_widget(); ?>
                    </div>
                </div>
                
                <!-- Communication Hub -->
                <div class="lccp-widget lccp-widget-half" data-widget="communication">
                    <div class="lccp-widget-header">
                        <h2><?php _e('Communication', 'lccp-systems'); ?></h2>
                    </div>
                    <div class="lccp-widget-content">
                        <?php echo $this->render_communication_widget('bigbird'); ?>
                    </div>
                </div>
                
                <!-- Resources -->
                <div class="lccp-widget lccp-widget-half" data-widget="resources">
                    <div class="lccp-widget-header">
                        <h2><?php _e('Big Bird Resources', 'lccp-systems'); ?></h2>
                    </div>
                    <div class="lccp-widget-content">
                        <?php echo $this->render_resources_widget('bigbird'); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Mentor Dashboard
     */
    public function render_mentor_dashboard($atts) {
        if (!current_user_can('manage_assigned_students') && !current_user_can('administrator')) {
            return '<p>' . __('You do not have permission to view this dashboard.', 'lccp-systems') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="lccp-dashboard lccp-mentor-dashboard" data-role="mentor">
            <div class="lccp-dashboard-header">
                <h1><?php _e('Mentor Dashboard', 'lccp-systems'); ?></h1>
                <div class="lccp-dashboard-actions">
                    <button class="lccp-customize-dashboard"><?php _e('Customize', 'lccp-systems'); ?></button>
                    <button class="lccp-refresh-dashboard"><?php _e('Refresh', 'lccp-systems'); ?></button>
                </div>
            </div>
            
            <div class="lccp-dashboard-widgets">
                <!-- Mentee Overview -->
                <div class="lccp-widget lccp-widget-full" data-widget="mentee-overview">
                    <div class="lccp-widget-header">
                        <h2><?php _e('My Mentees', 'lccp-systems'); ?></h2>
                    </div>
                    <div class="lccp-widget-content">
                        <?php echo $this->render_assigned_students_widget(get_current_user_id(), 'mentor'); ?>
                    </div>
                </div>
                
                <!-- Session Tracking -->
                <div class="lccp-widget lccp-widget-half" data-widget="session-tracking">
                    <div class="lccp-widget-header">
                        <h2><?php _e('Mentoring Sessions', 'lccp-systems'); ?></h2>
                    </div>
                    <div class="lccp-widget-content">
                        <?php echo $this->render_session_tracking_widget(); ?>
                    </div>
                </div>
                
                <!-- Hour Tracking -->
                <div class="lccp-widget lccp-widget-half" data-widget="hour-tracking">
                    <div class="lccp-widget-header">
                        <h2><?php _e('Hour Tracking', 'lccp-systems'); ?></h2>
                    </div>
                    <div class="lccp-widget-content">
                        <?php echo $this->render_hour_tracking_widget('mentor'); ?>
                    </div>
                </div>
                
                <!-- Feedback & Notes -->
                <div class="lccp-widget lccp-widget-third" data-widget="feedback-notes">
                    <div class="lccp-widget-header">
                        <h2><?php _e('Student Feedback', 'lccp-systems'); ?></h2>
                    </div>
                    <div class="lccp-widget-content">
                        <?php echo $this->render_feedback_widget(); ?>
                    </div>
                </div>
                
                <!-- Resources -->
                <div class="lccp-widget lccp-widget-third" data-widget="mentor-resources">
                    <div class="lccp-widget-header">
                        <h2><?php _e('Mentor Resources', 'lccp-systems'); ?></h2>
                    </div>
                    <div class="lccp-widget-content">
                        <?php echo $this->render_resources_widget('mentor'); ?>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="lccp-widget lccp-widget-third" data-widget="quick-actions">
                    <div class="lccp-widget-header">
                        <h2><?php _e('Quick Actions', 'lccp-systems'); ?></h2>
                    </div>
                    <div class="lccp-widget-content">
                        <?php echo $this->render_quick_actions_widget('mentor'); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Student Dashboard
     */
    public function render_student_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your dashboard.', 'lccp-systems') . '</p>';
        }
        
        $user_id = get_current_user_id();
        
        ob_start();
        ?>
        <div class="lccp-dashboard lccp-student-dashboard" data-role="student">
            <div class="lccp-dashboard-header">
                <h1><?php printf(__('Welcome, %s!', 'lccp-systems'), wp_get_current_user()->display_name); ?></h1>
                <div class="lccp-dashboard-actions">
                    <button class="lccp-customize-dashboard"><?php _e('Customize', 'lccp-systems'); ?></button>
                    <button class="lccp-refresh-dashboard"><?php _e('Refresh', 'lccp-systems'); ?></button>
                </div>
            </div>
            
            <div class="lccp-dashboard-widgets">
                <!-- Progress Overview -->
                <div class="lccp-widget lccp-widget-full" data-widget="progress-overview">
                    <div class="lccp-widget-header">
                        <h2><?php _e('My Progress', 'lccp-systems'); ?></h2>
                    </div>
                    <div class="lccp-widget-content">
                        <?php echo $this->render_student_progress_overview($user_id); ?>
                    </div>
                </div>
                
                <!-- Current Courses -->
                <div class="lccp-widget lccp-widget-half" data-widget="current-courses">
                    <div class="lccp-widget-header">
                        <h2><?php _e('My Courses', 'lccp-systems'); ?></h2>
                    </div>
                    <div class="lccp-widget-content">
                        <?php echo $this->render_student_courses_widget($user_id); ?>
                    </div>
                </div>
                
                <!-- Hour Tracker -->
                <div class="lccp-widget lccp-widget-half" data-widget="student-hours">
                    <div class="lccp-widget-header">
                        <h2><?php _e('Coaching Hours', 'lccp-systems'); ?></h2>
                    </div>
                    <div class="lccp-widget-content">
                        <?php echo $this->render_student_hours_widget($user_id); ?>
                    </div>
                </div>
                
                <!-- Upcoming Events -->
                <div class="lccp-widget lccp-widget-third" data-widget="upcoming-events">
                    <div class="lccp-widget-header">
                        <h2><?php _e('Upcoming Events', 'lccp-systems'); ?></h2>
                    </div>
                    <div class="lccp-widget-content">
                        <?php echo $this->render_upcoming_events_widget($user_id); ?>
                    </div>
                </div>
                
                <!-- Assignments -->
                <div class="lccp-widget lccp-widget-third" data-widget="assignments">
                    <div class="lccp-widget-header">
                        <h2><?php _e('Assignments', 'lccp-systems'); ?></h2>
                    </div>
                    <div class="lccp-widget-content">
                        <?php echo $this->render_assignments_widget($user_id); ?>
                    </div>
                </div>
                
                <!-- My Team -->
                <div class="lccp-widget lccp-widget-third" data-widget="my-team">
                    <div class="lccp-widget-header">
                        <h2><?php _e('My Support Team', 'lccp-systems'); ?></h2>
                    </div>
                    <div class="lccp-widget-content">
                        <?php echo $this->render_student_team_widget($user_id); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Widget Rendering Functions
     */
    
    private function render_program_overview_widget() {
        global $wpdb;
        
        // Get statistics
        $total_students = count(get_users(array('role__in' => array('subscriber', 'lccp_pc'))));
        $total_mentors = count(get_users(array('role' => 'lccp_mentor')));
        $total_bigbirds = count(get_users(array('role' => 'lccp_big_bird')));
        $active_courses = wp_count_posts('sfwd-courses')->publish;
        
        ob_start();
        ?>
        <div class="lccp-stats-grid">
            <div class="lccp-stat-box">
                <span class="lccp-stat-number"><?php echo $total_students; ?></span>
                <span class="lccp-stat-label"><?php _e('Total Students', 'lccp-systems'); ?></span>
            </div>
            <div class="lccp-stat-box">
                <span class="lccp-stat-number"><?php echo $total_mentors; ?></span>
                <span class="lccp-stat-label"><?php _e('Active Mentors', 'lccp-systems'); ?></span>
            </div>
            <div class="lccp-stat-box">
                <span class="lccp-stat-number"><?php echo $total_bigbirds; ?></span>
                <span class="lccp-stat-label"><?php _e('Big Birds', 'lccp-systems'); ?></span>
            </div>
            <div class="lccp-stat-box">
                <span class="lccp-stat-number"><?php echo $active_courses; ?></span>
                <span class="lccp-stat-label"><?php _e('Active Courses', 'lccp-systems'); ?></span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function render_student_stats_widget() {
        // Get student statistics
        ob_start();
        ?>
        <div class="lccp-student-stats">
            <canvas id="student-progress-chart"></canvas>
            <div class="lccp-stats-summary">
                <p><?php _e('Average Completion: 67%', 'lccp-systems'); ?></p>
                <p><?php _e('On Track: 85%', 'lccp-systems'); ?></p>
                <p><?php _e('Need Support: 15%', 'lccp-systems'); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function render_assigned_students_widget($user_id, $role) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'lccp_assignments';
        $column = ($role === 'mentor') ? 'mentor_id' : 'big_bird_id';
        
        $students = $wpdb->get_results($wpdb->prepare(
            "SELECT student_id FROM $table_name WHERE $column = %d AND status = 'active'",
            $user_id
        ));
        
        ob_start();
        ?>
        <div class="lccp-assigned-students">
            <?php if ($students): ?>
                <ul class="lccp-student-list">
                    <?php foreach ($students as $assignment): 
                        $student = get_userdata($assignment->student_id);
                        if ($student):
                    ?>
                        <li class="lccp-student-item">
                            <div class="lccp-student-avatar">
                                <?php echo get_avatar($student->ID, 40); ?>
                            </div>
                            <div class="lccp-student-info">
                                <h4><?php echo esc_html($student->display_name); ?></h4>
                                <p><?php echo $this->get_student_progress_summary($student->ID); ?></p>
                            </div>
                            <div class="lccp-student-actions">
                                <a href="#" class="button-small" data-student="<?php echo $student->ID; ?>">
                                    <?php _e('View Details', 'lccp-systems'); ?>
                                </a>
                            </div>
                        </li>
                    <?php 
                        endif;
                    endforeach; ?>
                </ul>
            <?php else: ?>
                <p><?php _e('No students currently assigned.', 'lccp-systems'); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function render_student_progress_overview($user_id) {
        // Get LearnDash progress if available
        $progress_data = array();
        
        if (function_exists('learndash_user_get_course_progress')) {
            $courses = learndash_user_get_enrolled_courses($user_id);
            foreach ($courses as $course_id) {
                $progress = learndash_user_get_course_progress($user_id, $course_id);
                $progress_data[] = array(
                    'course' => get_the_title($course_id),
                    'progress' => $progress
                );
            }
        }
        
        ob_start();
        ?>
        <div class="lccp-progress-overview">
            <div class="lccp-progress-summary">
                <div class="lccp-overall-progress">
                    <h3><?php _e('Overall Progress', 'lccp-systems'); ?></h3>
                    <div class="lccp-progress-bar">
                        <div class="lccp-progress-fill" style="width: 45%;"></div>
                    </div>
                    <span class="lccp-progress-text">45% <?php _e('Complete', 'lccp-systems'); ?></span>
                </div>
                
                <?php if ($progress_data): ?>
                    <div class="lccp-course-progress">
                        <?php foreach ($progress_data as $course_progress): ?>
                            <div class="lccp-course-item">
                                <h4><?php echo esc_html($course_progress['course']); ?></h4>
                                <div class="lccp-progress-bar small">
                                    <div class="lccp-progress-fill" style="width: <?php echo $course_progress['progress']['percentage']; ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="lccp-milestones">
                <h3><?php _e('Next Milestones', 'lccp-systems'); ?></h3>
                <ul>
                    <li>✓ <?php _e('Complete Module 3', 'lccp-systems'); ?></li>
                    <li>○ <?php _e('Submit First Practice Session', 'lccp-systems'); ?></li>
                    <li>○ <?php _e('Schedule Mentor Meeting', 'lccp-systems'); ?></li>
                </ul>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function render_quick_actions_widget($role) {
        $actions = array();
        
        switch ($role) {
            case 'pc':
                $actions = array(
                    array('label' => 'Add New Student', 'icon' => 'user-plus', 'link' => '#'),
                    array('label' => 'Assign Mentor', 'icon' => 'users', 'link' => '#'),
                    array('label' => 'Generate Report', 'icon' => 'chart', 'link' => '#'),
                    array('label' => 'Send Announcement', 'icon' => 'megaphone', 'link' => '#')
                );
                break;
            case 'mentor':
                $actions = array(
                    array('label' => 'Schedule Session', 'icon' => 'calendar', 'link' => '#'),
                    array('label' => 'Log Hours', 'icon' => 'clock', 'link' => '#'),
                    array('label' => 'Add Note', 'icon' => 'note', 'link' => '#'),
                    array('label' => 'View Resources', 'icon' => 'book', 'link' => '#')
                );
                break;
        }
        
        ob_start();
        ?>
        <div class="lccp-quick-actions">
            <?php foreach ($actions as $action): ?>
                <a href="<?php echo esc_url($action['link']); ?>" class="lccp-action-button">
                    <span class="lccp-icon lccp-icon-<?php echo $action['icon']; ?>"></span>
                    <span><?php echo esc_html($action['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Helper Functions
     */
    
    private function get_student_progress_summary($student_id) {
        // Get quick progress summary for a student
        if (function_exists('learndash_user_get_course_progress')) {
            $courses = learndash_user_get_enrolled_courses($student_id);
            if ($courses) {
                $total_progress = 0;
                foreach ($courses as $course_id) {
                    $progress = learndash_user_get_course_progress($student_id, $course_id);
                    $total_progress += $progress['percentage'];
                }
                $avg_progress = round($total_progress / count($courses));
                return sprintf(__('%d%% Complete', 'lccp-systems'), $avg_progress);
            }
        }
        return __('Not started', 'lccp-systems');
    }
    
    /**
     * Dashboard login redirect
     */
    public function dashboard_login_redirect($redirect_to, $request, $user) {
        if (isset($user->roles) && is_array($user->roles)) {
            $dashboard_pages = get_option('lccp_dashboard_pages', array());
            
            if (in_array('lccp_program_coordinator', $user->roles) && !empty($dashboard_pages['pc'])) {
                return get_permalink($dashboard_pages['pc']);
            } elseif (in_array('lccp_big_bird', $user->roles) && !empty($dashboard_pages['bigbird'])) {
                return get_permalink($dashboard_pages['bigbird']);
            } elseif (in_array('lccp_mentor', $user->roles) && !empty($dashboard_pages['mentor'])) {
                return get_permalink($dashboard_pages['mentor']);
            } elseif (!empty($dashboard_pages['student'])) {
                return get_permalink($dashboard_pages['student']);
            }
        }
        return $redirect_to;
    }
    
    /**
     * Add dashboard to BuddyBoss profile
     */
    public function add_dashboard_to_profile() {
        if (!function_exists('bp_core_new_nav_item')) {
            return;
        }
        
        bp_core_new_nav_item(array(
            'name' => __('LCCP Dashboard', 'lccp-systems'),
            'slug' => 'lccp-dashboard',
            'position' => 20,
            'screen_function' => array($this, 'bp_dashboard_screen'),
            'default_subnav_slug' => 'overview',
            'item_css_id' => 'lccp-dashboard'
        ));
    }
    
    public function bp_dashboard_screen() {
        add_action('bp_template_content', array($this, 'bp_dashboard_content'));
        bp_core_load_template('members/single/plugins');
    }
    
    public function bp_dashboard_content() {
        echo do_shortcode('[lccp_dashboard]');
    }
    
    /**
     * Enqueue dashboard assets
     */
    public function enqueue_assets() {
        if (is_page() && has_shortcode(get_post_field('post_content', get_the_ID()), 'lccp_dashboard')) {
            wp_enqueue_style('lccp-dashboards', 
                LCCP_SYSTEMS_PLUGIN_URL . 'assets/css/dashboards.css', 
                array(), 
                LCCP_SYSTEMS_VERSION
            );
            
            wp_enqueue_script('lccp-dashboards', 
                LCCP_SYSTEMS_PLUGIN_URL . 'assets/js/dashboards.js', 
                array('jquery'), 
                LCCP_SYSTEMS_VERSION, 
                true
            );
            
            wp_localize_script('lccp-dashboards', 'lccp_dashboard', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('lccp_dashboard_nonce'),
                'user_role' => $this->get_user_dashboard_role(),
                'strings' => array(
                    'loading' => __('Loading...', 'lccp-systems'),
                    'error' => __('An error occurred', 'lccp-systems'),
                    'save' => __('Save', 'lccp-systems'),
                    'cancel' => __('Cancel', 'lccp-systems')
                )
            ));
            
            // Include Chart.js for statistics
            wp_enqueue_script('chartjs', 
                'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', 
                array(), 
                '3.9.1'
            );
        }
    }
    
    private function get_user_dashboard_role() {
        $user = wp_get_current_user();
        if (in_array('lccp_program_coordinator', $user->roles)) {
            return 'program_coordinator';
        } elseif (in_array('lccp_big_bird', $user->roles)) {
            return 'bigbird';
        } elseif (in_array('lccp_mentor', $user->roles)) {
            return 'mentor';
        }
        return 'student';
    }
    
    /**
     * AJAX Handlers
     */
    
    public function ajax_refresh_widget() {
        check_ajax_referer('lccp_dashboard_nonce', 'nonce');
        
        $widget = sanitize_text_field($_POST['widget']);
        $role = sanitize_text_field($_POST['role']);
        
        // Render the specific widget based on type
        $output = '';
        
        switch ($widget) {
            case 'program-overview':
                $output = $this->render_program_overview_widget();
                break;
            case 'student-stats':
                $output = $this->render_student_stats_widget();
                break;
            // Add more cases as needed
        }
        
        wp_send_json_success(array('html' => $output));
    }
    
    public function ajax_save_widget_settings() {
        check_ajax_referer('lccp_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $settings = $_POST['settings'];
        
        update_user_meta($user_id, 'lccp_dashboard_settings', $settings);
        
        wp_send_json_success();
    }
    
    public function ajax_get_dashboard_data() {
        check_ajax_referer('lccp_dashboard_nonce', 'nonce');
        
        $type = sanitize_text_field($_POST['type']);
        $data = array();
        
        // Return specific data based on request type
        
        wp_send_json_success($data);
    }
    
    /**
     * Register dashboard widgets
     */
    private function register_dashboard_widgets() {
        // This would typically register all available widgets
        // that can be used in the dashboards
    }
    
    // Additional widget rendering methods would go here...
    private function render_mentor_assignments_widget() {
        return '<p>Mentor assignments widget content</p>';
    }
    
    private function render_recent_activities_widget($role) {
        return '<p>Recent activities for ' . $role . '</p>';
    }
    
    private function render_pending_approvals_widget() {
        return '<p>Pending approvals widget</p>';
    }
    
    private function render_student_progress_widget($role) {
        return '<p>Student progress widget for ' . $role . '</p>';
    }
    
    private function render_upcoming_sessions_widget() {
        return '<p>Upcoming sessions widget</p>';
    }
    
    private function render_communication_widget($role) {
        return '<p>Communication widget for ' . $role . '</p>';
    }
    
    private function render_resources_widget($role) {
        return '<p>Resources for ' . $role . '</p>';
    }
    
    private function render_session_tracking_widget() {
        return '<p>Session tracking widget</p>';
    }
    
    private function render_hour_tracking_widget($role) {
        return '<p>Hour tracking for ' . $role . '</p>';
    }
    
    private function render_feedback_widget() {
        return '<p>Feedback widget</p>';
    }
    
    private function render_student_courses_widget($user_id) {
        return '<p>Student courses widget</p>';
    }
    
    private function render_student_hours_widget($user_id) {
        return '<p>Student hours widget</p>';
    }
    
    private function render_upcoming_events_widget($user_id) {
        return '<p>Upcoming events widget</p>';
    }
    
    private function render_assignments_widget($user_id) {
        return '<p>Assignments widget</p>';
    }
    
    private function render_student_team_widget($user_id) {
        return '<p>Student team widget</p>';
    }
}

// Initialize the module
LCCP_Dashboards::get_instance();