<?php
/**
 * LearnDash Integration Module for LCCP Systems
 * Modular version with feature toggle support
 *
 * @package LCCP Systems
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_LearnDash_Integration_Module extends LCCP_Module {
    
    protected $module_id = 'learndash_integration';
    protected $module_name = 'LearnDash Integration';
    protected $module_description = 'Enhanced LearnDash functionality with course access management, progress tracking, and compatibility fixes.';
    protected $module_version = '1.0.0';
    protected $module_dependencies = array('learndash');
    protected $module_settings = array(
        'enable_course_access' => true,
        'enable_progress_tracking' => true,
        'enable_compatibility_fixes' => true,
        'enable_widgets' => true,
        'auto_enroll_roles' => array('lccp_mentor', 'lccp_big_bird'),
        'lccp_category_slug' => 'lccp',
        'bypass_prerequisites' => false,
        'custom_completion_logic' => false
    );
    
    protected function init() {
        // Only initialize if module is enabled and LearnDash is active
        if (!$this->is_enabled() || !$this->check_dependencies()) {
            return;
        }
        
        // Course access management
        if ($this->get_setting('enable_course_access')) {
            $this->init_course_access();
        }
        
        // Progress tracking
        if ($this->get_setting('enable_progress_tracking')) {
            $this->init_progress_tracking();
        }
        
        // Compatibility fixes
        if ($this->get_setting('enable_compatibility_fixes')) {
            $this->init_compatibility_fixes();
        }
        
        // Widgets
        if ($this->get_setting('enable_widgets')) {
            $this->init_widgets();
        }
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_page'));
    }
    
    /**
     * Get a specific setting value
     */
    private function get_setting($key) {
        $settings = $this->get_settings();
        return isset($settings[$key]) ? $settings[$key] : null;
    }
    
    /**
     * Initialize course access management
     */
    private function init_course_access() {
        // Course access filters
        add_filter('learndash_course_access', array($this, 'filter_course_access'), 5, 2);
        add_filter('learndash_lesson_access', array($this, 'filter_lesson_access'), 5, 2);
        add_filter('learndash_topic_access', array($this, 'filter_topic_access'), 5, 2);
        add_filter('learndash_quiz_access', array($this, 'filter_quiz_access'), 5, 2);
        
        // Enrollment filters
        add_filter('learndash_is_course_enrolled', array($this, 'filter_course_enrollment'), 5, 3);
        add_filter('learndash_is_user_in_course', array($this, 'filter_user_in_course'), 5, 3);
        
        // Auto-enrollment
        add_action('user_register', array($this, 'auto_enroll_user'));
        add_action('set_user_role', array($this, 'auto_enroll_on_role_change'), 10, 2);
    }
    
    /**
     * Initialize progress tracking
     */
    private function init_progress_tracking() {
        // Progress hooks
        add_action('learndash_course_completed', array($this, 'on_course_completed'), 10, 2);
        add_action('learndash_lesson_completed', array($this, 'on_lesson_completed'), 10, 2);
        add_action('learndash_topic_completed', array($this, 'on_topic_completed'), 10, 2);
        add_action('learndash_quiz_completed', array($this, 'on_quiz_completed'), 10, 2);
        
        // Custom completion logic
        if ($this->get_setting('custom_completion_logic')) {
            add_filter('learndash_course_completed', array($this, 'custom_course_completion'), 10, 2);
        }
    }
    
    /**
     * Initialize compatibility fixes
     */
    private function init_compatibility_fixes() {
        // Fix LearnDash AJAX handlers
        add_action('wp_ajax_learndash_mark_complete', array($this, 'fix_mark_complete_handler'), 5);
        add_action('wp_ajax_nopriv_learndash_mark_complete', array($this, 'fix_mark_complete_handler'), 5);
        
        // Fix completion process
        add_filter('learndash_mark_complete_process', array($this, 'fix_completion_process'), 5, 2);
        
        // Fix theme conflicts
        add_action('wp_enqueue_scripts', array($this, 'fix_theme_conflicts'), 20);
    }
    
    /**
     * Initialize widgets
     */
    private function init_widgets() {
        add_action('widgets_init', array($this, 'register_widgets'));
    }
    
    /**
     * Filter course access
     */
    public function filter_course_access($has_access, $course_id) {
        if (!$has_access) {
            return $this->check_lccp_course_access($course_id);
        }
        return $has_access;
    }
    
    /**
     * Filter lesson access
     */
    public function filter_lesson_access($has_access, $lesson_id) {
        if (!$has_access) {
            $course_id = learndash_get_course_id($lesson_id);
            return $this->check_lccp_course_access($course_id);
        }
        return $has_access;
    }
    
    /**
     * Filter topic access
     */
    public function filter_topic_access($has_access, $topic_id) {
        if (!$has_access) {
            $course_id = learndash_get_course_id($topic_id);
            return $this->check_lccp_course_access($course_id);
        }
        return $has_access;
    }
    
    /**
     * Filter quiz access
     */
    public function filter_quiz_access($has_access, $quiz_id) {
        if (!$has_access) {
            $course_id = learndash_get_course_id($quiz_id);
            return $this->check_lccp_course_access($course_id);
        }
        return $has_access;
    }
    
    /**
     * Filter course enrollment
     */
    public function filter_course_enrollment($is_enrolled, $user_id, $course_id) {
        if (!$is_enrolled) {
            return $this->check_lccp_course_access($course_id, $user_id);
        }
        return $is_enrolled;
    }
    
    /**
     * Filter user in course
     */
    public function filter_user_in_course($is_in_course, $user_id, $course_id) {
        if (!$is_in_course) {
            return $this->check_lccp_course_access($course_id, $user_id);
        }
        return $is_in_course;
    }
    
    /**
     * Check LCCP course access
     */
    private function check_lccp_course_access($course_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        // Admins have access to everything
        if (in_array('administrator', $user->roles)) {
            return true;
        }
        
        // Check if course is in LCCP category
        if (!$this->is_lccp_course($course_id)) {
            return false;
        }
        
        // Check if user has LCCP role
        $auto_enroll_roles = $this->get_setting('auto_enroll_roles');
        foreach ($auto_enroll_roles as $role) {
            if (in_array($role, $user->roles)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if course is in LCCP category
     */
    private function is_lccp_course($course_id) {
        $lccp_category = $this->get_setting('lccp_category_slug');
        $course_categories = wp_get_post_terms($course_id, 'ld_course_category', array('fields' => 'slugs'));
        
        return in_array($lccp_category, $course_categories);
    }
    
    /**
     * Auto-enroll user in LCCP courses
     */
    public function auto_enroll_user($user_id) {
        $this->enroll_user_in_lccp_courses($user_id);
    }
    
    /**
     * Auto-enroll on role change
     */
    public function auto_enroll_on_role_change($user_id, $role) {
        $auto_enroll_roles = $this->get_setting('auto_enroll_roles');
        if (in_array($role, $auto_enroll_roles)) {
            $this->enroll_user_in_lccp_courses($user_id);
        }
    }
    
    /**
     * Enroll user in LCCP courses
     */
    private function enroll_user_in_lccp_courses($user_id) {
        $lccp_category = $this->get_setting('lccp_category_slug');
        
        $courses = get_posts(array(
            'post_type' => 'sfwd-courses',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'ld_course_category',
                    'field' => 'slug',
                    'terms' => $lccp_category
                )
            )
        ));
        
        foreach ($courses as $course) {
            ld_update_course_access($user_id, $course->ID);
        }
    }
    
    /**
     * Handle course completion
     */
    public function on_course_completed($course_id, $user_id) {
        // Log completion
        $this->log_completion('course', $course_id, $user_id);
        
        // Trigger custom actions
        do_action('lccp_course_completed', $course_id, $user_id);
    }
    
    /**
     * Handle lesson completion
     */
    public function on_lesson_completed($lesson_id, $user_id) {
        // Log completion
        $this->log_completion('lesson', $lesson_id, $user_id);
        
        // Trigger custom actions
        do_action('lccp_lesson_completed', $lesson_id, $user_id);
    }
    
    /**
     * Handle topic completion
     */
    public function on_topic_completed($topic_id, $user_id) {
        // Log completion
        $this->log_completion('topic', $topic_id, $user_id);
        
        // Trigger custom actions
        do_action('lccp_topic_completed', $topic_id, $user_id);
    }
    
    /**
     * Handle quiz completion
     */
    public function on_quiz_completed($quiz_id, $user_id) {
        // Log completion
        $this->log_completion('quiz', $quiz_id, $user_id);
        
        // Trigger custom actions
        do_action('lccp_quiz_completed', $quiz_id, $user_id);
    }
    
    /**
     * Log completion
     */
    private function log_completion($type, $item_id, $user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'lccp_completions';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'item_id' => $item_id,
                'item_type' => $type,
                'completed_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s')
        );
    }
    
    /**
     * Fix mark complete handler
     */
    public function fix_mark_complete_handler() {
        // Ensure proper nonce verification
        if (!wp_verify_nonce($_POST['nonce'], 'learndash_mark_complete_' . $_POST['post'] . '_' . get_current_user_id())) {
            wp_die('Security check failed');
        }
        
        // Continue with original handler
        return true;
    }
    
    /**
     * Fix completion process
     */
    public function fix_completion_process($return, $post_id) {
        // Add custom completion logic here
        return $return;
    }
    
    /**
     * Fix theme conflicts
     */
    public function fix_theme_conflicts() {
        // Enqueue compatibility styles
        wp_enqueue_style(
            'lccp-learndash-compatibility',
            LCCP_SYSTEMS_PLUGIN_URL . 'assets/css/learndash-compatibility.css',
            array(),
            LCCP_SYSTEMS_VERSION
        );
    }
    
    /**
     * Register widgets
     */
    public function register_widgets() {
        register_widget('LCCP_LearnDash_Progress_Widget');
        register_widget('LCCP_LearnDash_Courses_Widget');
    }
    
    /**
     * Add admin page
     */
    public function add_admin_page() {
        add_submenu_page(
            'lccp-systems',
            __('LearnDash Integration', 'lccp-systems'),
            __('LearnDash Integration', 'lccp-systems'),
            'manage_options',
            'lccp-learndash-integration',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $settings = $this->get_settings();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('LearnDash Integration Settings', 'lccp-systems'); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('lccp_learndash_settings'); ?>
                <?php do_settings_sections('lccp_learndash_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Course Access Management', 'lccp-systems'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="lccp_learndash_settings[enable_course_access]" 
                                       value="1" <?php checked($settings['enable_course_access'], true); ?> />
                                <?php esc_html_e('Enable automatic course access for LCCP roles', 'lccp-systems'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Progress Tracking', 'lccp-systems'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="lccp_learndash_settings[enable_progress_tracking]" 
                                       value="1" <?php checked($settings['enable_progress_tracking'], true); ?> />
                                <?php esc_html_e('Enable enhanced progress tracking', 'lccp-systems'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Compatibility Fixes', 'lccp-systems'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="lccp_learndash_settings[enable_compatibility_fixes]" 
                                       value="1" <?php checked($settings['enable_compatibility_fixes'], true); ?> />
                                <?php esc_html_e('Enable LearnDash compatibility fixes', 'lccp-systems'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('LCCP Category Slug', 'lccp-systems'); ?></th>
                        <td>
                            <input type="text" name="lccp_learndash_settings[lccp_category_slug]" 
                                   value="<?php echo esc_attr($settings['lccp_category_slug']); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php esc_html_e('The slug for the LCCP course category', 'lccp-systems'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Auto-Enroll Roles', 'lccp-systems'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="lccp_learndash_settings[auto_enroll_roles][]" 
                                           value="lccp_mentor" <?php checked(in_array('lccp_mentor', $settings['auto_enroll_roles'])); ?> />
                                    <?php esc_html_e('Mentors', 'lccp-systems'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="lccp_learndash_settings[auto_enroll_roles][]" 
                                           value="lccp_big_bird" <?php checked(in_array('lccp_big_bird', $settings['auto_enroll_roles'])); ?> />
                                    <?php esc_html_e('Big Birds', 'lccp-systems'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="lccp_learndash_settings[auto_enroll_roles][]" 
                                           value="lccp_pc" <?php checked(in_array('lccp_pc', $settings['auto_enroll_roles'])); ?> />
                                    <?php esc_html_e('PCs', 'lccp-systems'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Called when module is activated
     */
    protected function on_activate() {
        $this->create_database_tables();
        $this->register_settings();
    }
    
    /**
     * Called when module is deactivated
     */
    protected function on_deactivate() {
        // Clean up any module-specific data if needed
    }
    
    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Completions table
        $table_name = $wpdb->prefix . 'lccp_completions';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            item_id bigint(20) NOT NULL,
            item_type varchar(20) NOT NULL,
            completed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY item_id (item_id),
            KEY item_type (item_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Register settings
     */
    private function register_settings() {
        register_setting('lccp_learndash_settings', 'lccp_module_learndash_integration_settings');
    }
}

/**
 * LearnDash Progress Widget
 */
class LCCP_LearnDash_Progress_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'lccp_learndash_progress_widget',
            __('LCCP LearnDash Progress', 'lccp-systems'),
            array('description' => __('Display user\'s LearnDash course progress', 'lccp-systems'))
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . __('Please log in to view your progress.', 'lccp-systems') . '</p>';
            echo $args['after_widget'];
            return;
        }
        
        $courses = learndash_user_get_enrolled_courses($user_id);
        $completed_courses = 0;
        
        foreach ($courses as $course_id) {
            if (learndash_course_completed($user_id, $course_id)) {
                $completed_courses++;
            }
        }
        
        $progress_percentage = count($courses) > 0 ? round(($completed_courses / count($courses)) * 100) : 0;
        
        ?>
        <div class="lccp-progress-widget">
            <div class="lccp-progress-circle" data-progress="<?php echo esc_attr($progress_percentage); ?>">
                <span class="lccp-progress-text"><?php echo esc_html($progress_percentage); ?>%</span>
            </div>
            <div class="lccp-progress-info">
                <div class="lccp-progress-courses">
                    <?php echo esc_html($completed_courses); ?> / <?php echo esc_html(count($courses)); ?>
                    <?php esc_html_e('Courses Completed', 'lccp-systems'); ?>
                </div>
            </div>
        </div>
        
        <style>
        .lccp-progress-widget {
            text-align: center;
            padding: 20px;
        }
        
        .lccp-progress-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: conic-gradient(#007cba 0deg, #e0e0e0 0deg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            position: relative;
        }
        
        .lccp-progress-text {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
        }
        
        .lccp-progress-info {
            font-size: 0.9em;
            color: #666;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('.lccp-progress-circle').each(function() {
                var progress = $(this).data('progress');
                var degrees = (progress / 100) * 360;
                $(this).css('background', 'conic-gradient(#007cba ' + degrees + 'deg, #e0e0e0 0deg)');
            });
        });
        </script>
        <?php
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('My Progress', 'lccp-systems');
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php esc_html_e('Title:', 'lccp-systems'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }
}

/**
 * LearnDash Courses Widget
 */
class LCCP_LearnDash_Courses_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'lccp_learndash_courses_widget',
            __('LCCP LearnDash Courses', 'lccp-systems'),
            array('description' => __('Display user\'s enrolled LearnDash courses', 'lccp-systems'))
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . __('Please log in to view your courses.', 'lccp-systems') . '</p>';
            echo $args['after_widget'];
            return;
        }
        
        $courses = learndash_user_get_enrolled_courses($user_id);
        $limit = !empty($instance['limit']) ? intval($instance['limit']) : 5;
        $courses = array_slice($courses, 0, $limit);
        
        if (empty($courses)) {
            echo '<p>' . __('No courses enrolled.', 'lccp-systems') . '</p>';
            echo $args['after_widget'];
            return;
        }
        
        ?>
        <div class="lccp-courses-widget">
            <ul class="lccp-courses-list">
                <?php foreach ($courses as $course_id): ?>
                    <?php
                    $course = get_post($course_id);
                    $progress = learndash_course_progress($user_id, $course_id);
                    $is_completed = learndash_course_completed($user_id, $course_id);
                    ?>
                    <li class="lccp-course-item <?php echo $is_completed ? 'completed' : ''; ?>">
                        <a href="<?php echo get_permalink($course_id); ?>" class="lccp-course-link">
                            <div class="lccp-course-title"><?php echo esc_html($course->post_title); ?></div>
                            <div class="lccp-course-progress">
                                <div class="lccp-progress-bar">
                                    <div class="lccp-progress-fill" style="width: <?php echo esc_attr($progress['percentage']); ?>%"></div>
                                </div>
                                <span class="lccp-progress-text"><?php echo esc_html($progress['percentage']); ?>%</span>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <style>
        .lccp-courses-widget {
            padding: 10px 0;
        }
        
        .lccp-courses-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .lccp-course-item {
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .lccp-course-item.completed {
            border-color: #46b450;
            background-color: #f0f8f0;
        }
        
        .lccp-course-link {
            display: block;
            padding: 15px;
            text-decoration: none;
            color: #333;
        }
        
        .lccp-course-link:hover {
            background-color: #f9f9f9;
        }
        
        .lccp-course-title {
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .lccp-course-progress {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .lccp-progress-bar {
            flex: 1;
            height: 8px;
            background-color: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .lccp-progress-fill {
            height: 100%;
            background-color: #007cba;
            transition: width 0.3s ease;
        }
        
        .lccp-course-item.completed .lccp-progress-fill {
            background-color: #46b450;
        }
        
        .lccp-progress-text {
            font-size: 0.9em;
            font-weight: 500;
            color: #666;
        }
        </style>
        <?php
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('My Courses', 'lccp-systems');
        $limit = !empty($instance['limit']) ? $instance['limit'] : 5;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php esc_html_e('Title:', 'lccp-systems'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('limit'); ?>"><?php esc_html_e('Number of courses to show:', 'lccp-systems'); ?></label>
            <input class="tiny-text" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="number" value="<?php echo esc_attr($limit); ?>" min="1" max="20">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['limit'] = (!empty($new_instance['limit'])) ? intval($new_instance['limit']) : 5;
        return $instance;
    }
}
