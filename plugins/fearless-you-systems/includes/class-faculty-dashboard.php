<?php
/**
 * Fearless Faculty Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class FYS_Faculty_Dashboard {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('fys_faculty_dashboard', array($this, 'render_dashboard'));
    }

    public function render_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view the faculty dashboard.', 'fearless-you-systems') . '</p>';
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        if (!in_array('fearless_faculty', $user->roles) && !current_user_can('administrator')) {
            return '<p>' . __('This dashboard is only available to Fearless Faculty members.', 'fearless-you-systems') . '</p>';
        }

        // Load the enhanced template
        $template_file = FYS_PLUGIN_DIR . 'templates/faculty-dashboard.php';
        if (file_exists($template_file)) {
            ob_start();
            include $template_file;
            return ob_get_clean();
        }

        // Fallback to simple dashboard if template not found
        ob_start();
        ?>
        <div class="fys-faculty-dashboard">
            <div class="fys-dashboard-header">
                <h2><?php _e('Faculty Dashboard', 'fearless-you-systems'); ?></h2>
                <p class="fys-faculty-status"><?php echo esc_html($user->display_name); ?> - <?php _e('Fearless Faculty', 'fearless-you-systems'); ?></p>
            </div>

            <div class="fys-dashboard-content">
                <div class="fys-faculty-stats">
                    <h3><?php _e('Teaching Statistics', 'fearless-you-systems'); ?></h3>
                    <div class="fys-stats-grid">
                        <div class="fys-stat-card">
                            <span class="fys-stat-label"><?php _e('Active Courses', 'fearless-you-systems'); ?></span>
                            <span class="fys-stat-value"><?php echo $this->get_faculty_course_count($user_id); ?></span>
                        </div>
                        <div class="fys-stat-card">
                            <span class="fys-stat-label"><?php _e('Total Students', 'fearless-you-systems'); ?></span>
                            <span class="fys-stat-value"><?php echo $this->get_faculty_student_count($user_id); ?></span>
                        </div>
                    </div>
                </div>

                <div class="fys-faculty-tools">
                    <h3><?php _e('Faculty Tools', 'fearless-you-systems'); ?></h3>
                    <div class="fys-tools-grid">
                        <a href="/wp-admin/post-new.php?post_type=sfwd-courses" class="fys-tool-card">
                            <span class="dashicons dashicons-welcome-add-page"></span>
                            <span><?php _e('Create Course', 'fearless-you-systems'); ?></span>
                        </a>
                        <a href="/wp-admin/edit.php?post_type=sfwd-courses" class="fys-tool-card">
                            <span class="dashicons dashicons-edit"></span>
                            <span><?php _e('Manage Courses', 'fearless-you-systems'); ?></span>
                        </a>
                        <a href="/student-reports/" class="fys-tool-card">
                            <span class="dashicons dashicons-chart-area"></span>
                            <span><?php _e('Student Reports', 'fearless-you-systems'); ?></span>
                        </a>
                        <a href="/faculty-resources/" class="fys-tool-card">
                            <span class="dashicons dashicons-portfolio"></span>
                            <span><?php _e('Faculty Resources', 'fearless-you-systems'); ?></span>
                        </a>
                    </div>
                </div>

                <div class="fys-upcoming-sessions">
                    <h3><?php _e('Upcoming Teaching Sessions', 'fearless-you-systems'); ?></h3>
                    <?php $this->display_upcoming_sessions($user_id); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_faculty_course_count($user_id) {
        $courses = get_posts(array(
            'post_type' => 'sfwd-courses',
            'author' => $user_id,
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        return count($courses);
    }

    private function get_faculty_student_count($user_id) {
        // This would integrate with LearnDash to get actual student counts
        return 0;
    }

    private function display_upcoming_sessions($user_id) {
        // This would integrate with an events/calendar plugin
        echo '<p>' . __('No upcoming sessions scheduled.', 'fearless-you-systems') . '</p>';
    }
}