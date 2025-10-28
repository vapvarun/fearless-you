<?php
/**
 * Enhanced Dashboards with Permission Hierarchy - OPTIMIZED
 *
 * Minimal Dashboard: 5 Essential Widgets
 * - Admin (100): Program Overview, Activity Feed, Team Performance
 * - Mentor/Big Bird/PC (25-75): My Team, Course & Hour Progress
 *
 * Hierarchy: PC < Big Bird < Mentor < Rhonda (admin)
 *
 * @version 2.0.0 - Optimized for performance and UX
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Enhanced_Dashboards {

    private $current_user;
    private $user_role_level;

    public function __construct() {
        add_action('init', array($this, 'setup_user_permissions'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_dashboard_assets'));

        // AJAX handlers for real-time updates
        add_action('wp_ajax_lccp_get_student_progress', array($this, 'ajax_get_student_progress'));
        add_action('wp_ajax_lccp_get_hour_statistics', array($this, 'ajax_get_hour_statistics'));
        add_action('wp_ajax_lccp_get_activity_feed', array($this, 'ajax_get_activity_feed'));
    }

    /**
     * Enqueue dashboard widget assets (WordPress Standard)
     */
    public function enqueue_dashboard_assets($hook) {
        // Only load on dashboard
        if ('index.php' !== $hook) {
            return;
        }

        // Enqueue WordPress-standard dashboard widgets CSS
        wp_enqueue_style(
            'lccp-dashboard-widgets',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/dashboard-widgets.css',
            array(),
            '2.0.0'
        );

        // Enqueue dashboard widgets JavaScript
        wp_enqueue_script(
            'lccp-dashboard-widgets',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/dashboard-widgets.js',
            array('jquery'),
            '2.0.0',
            true
        );

        // Localize script for AJAX
        wp_localize_script('lccp-dashboard-widgets', 'lccpDashboard', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lccp_dashboard_nonce')
        ));
    }

    public function setup_user_permissions() {
        $this->current_user = wp_get_current_user();
        $this->user_role_level = $this->get_user_role_level();
    }

    private function get_user_role_level() {
        if (!$this->current_user) {
            return 0;
        }

        $user_email = $this->current_user->user_email;
        $user_roles = $this->current_user->roles;

        // Rhonda gets highest level
        if ($user_email === 'rhonda@fearlessliving.org' || in_array('administrator', $user_roles)) {
            return 100; // Full access to everything
        }

        // Role-based hierarchy
        if (in_array('lccp_mentor', $user_roles)) {
            return 75; // Access to Big Birds, PCs, and students
        }

        if (in_array('lccp_big_bird', $user_roles)) {
            return 50; // Access to PCs and students
        }

        if (in_array('lccp_pc', $user_roles)) {
            return 25; // Access to assigned students only
        }

        return 10; // Student level
    }

    public function add_dashboard_widgets() {
        global $wp_meta_boxes;

        // Remove default WordPress widgets for LCCP users
        if ($this->user_role_level > 0 && $this->user_role_level < 100) {
            unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity']);
            unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
            unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
        }

        // ADMIN WIDGETS (Level 100)
        if ($this->user_role_level >= 100) {
            // Widget 1: Program Overview - Comprehensive stats
            wp_add_dashboard_widget(
                'lccp_program_overview',
                'LCCP Program Overview',
                array($this, 'render_program_overview_widget')
            );

            // Widget 2: Activity Feed - Recent program activity
            wp_add_dashboard_widget(
                'lccp_activity_feed',
                'Program Activity Feed',
                array($this, 'render_activity_feed_widget')
            );

            // Widget 3: Team Performance - All team metrics
            wp_add_dashboard_widget(
                'lccp_team_performance',
                'Team Performance Dashboard',
                array($this, 'render_team_performance_widget')
            );
        }

        // ROLE-SPECIFIC WIDGETS (Level 25+)
        if ($this->user_role_level >= 25 && $this->user_role_level < 100) {
            // Widget 4: My Team - Role-specific team management
            wp_add_dashboard_widget(
                'lccp_my_team',
                $this->get_my_team_title(),
                array($this, 'render_my_team_widget')
            );

            // Widget 5: Course & Hour Progress - Combined tracking
            wp_add_dashboard_widget(
                'lccp_course_hour_progress',
                'Course & Hour Progress',
                array($this, 'render_course_hour_progress_widget')
            );
        }
    }

    /**
     * Get role-specific title for My Team widget
     */
    private function get_my_team_title() {
        if ($this->user_role_level >= 75) {
            return 'My Mentorship Team';
        } elseif ($this->user_role_level >= 50) {
            return 'My PC Team';
        } elseif ($this->user_role_level >= 25) {
            return 'My Assigned Students';
        }
        return 'My Team';
    }

    /**
     * WIDGET 1: Program Overview (Admin Only)
     * Consolidates: LCCP Program Overview + Systems Overview
     */
    public function render_program_overview_widget() {
        global $wpdb;

        // Get comprehensive statistics - using count_users() for efficiency
        $user_counts = count_users();
        $total_students = isset($user_counts['avail_roles']['subscriber']) ? $user_counts['avail_roles']['subscriber'] : 0;
        $total_mentors = isset($user_counts['avail_roles']['lccp_mentor']) ? $user_counts['avail_roles']['lccp_mentor'] : 0;
        $total_bigbirds = isset($user_counts['avail_roles']['lccp_big_bird']) ? $user_counts['avail_roles']['lccp_big_bird'] : 0;
        $total_pcs = isset($user_counts['avail_roles']['lccp_pc']) ? $user_counts['avail_roles']['lccp_pc'] : 0;

        // Get hour statistics (with null safety)
        $total_hours = $wpdb->get_var("SELECT SUM(session_length) FROM {$wpdb->prefix}lccp_hour_tracker");
        $total_hours = $total_hours ? $total_hours : 0;

        $this_month_hours = $wpdb->get_var(
            "SELECT SUM(session_length) FROM {$wpdb->prefix}lccp_hour_tracker
            WHERE MONTH(session_date) = MONTH(CURRENT_DATE())
            AND YEAR(session_date) = YEAR(CURRENT_DATE())"
        );
        $this_month_hours = $this_month_hours ? $this_month_hours : 0;

        // Get course completion rates
        $completion_rate = $this->calculate_overall_completion_rate();

        ?>
        <div class="lccp-widget-stats">
            <div class="lccp-stat-box">
                <h4>Total Students</h4>
                <div class="lccp-stat-number"><?php echo number_format($total_students); ?></div>
            </div>
            <div class="lccp-stat-box">
                <h4>Active Mentors</h4>
                <div class="lccp-stat-number"><?php echo number_format($total_mentors); ?></div>
            </div>
            <div class="lccp-stat-box">
                <h4>Big Birds</h4>
                <div class="lccp-stat-number"><?php echo number_format($total_bigbirds); ?></div>
            </div>
            <div class="lccp-stat-box">
                <h4>Practice Coaches</h4>
                <div class="lccp-stat-number"><?php echo number_format($total_pcs); ?></div>
            </div>
        </div>

        <div class="lccp-progress-container">
            <div class="lccp-progress-label">
                <span><strong>Hour Statistics</strong></span>
                <span>Total: <?php echo number_format($total_hours, 1); ?> hrs | This Month: <?php echo number_format($this_month_hours, 1); ?> hrs</span>
            </div>
        </div>

        <div class="lccp-progress-container">
            <div class="lccp-progress-label">
                <span><strong>Course Completion</strong></span>
                <span><?php echo round($completion_rate); ?>%</span>
            </div>
            <div class="lccp-progress-bar">
                <div class="lccp-progress-fill success" style="width: <?php echo $completion_rate; ?>%;">
                    <?php echo round($completion_rate); ?>%
                </div>
            </div>
        </div>

        <div class="lccp-widget-actions">
            <a href="<?php echo admin_url('admin.php?page=lccp-reports'); ?>" class="button button-primary">View Detailed Reports</a>
            <a href="<?php echo admin_url('admin.php?page=lccp-export'); ?>" class="button">Export Data</a>
        </div>
        <?php
    }

    /**
     * WIDGET 2: Activity Feed (Admin Only)
     * Shows all recent program activity
     */
    public function render_activity_feed_widget() {
        global $wpdb;

        // Get recent activities across all users
        $recent_activities = $wpdb->get_results(
            "SELECT
                h.*,
                u.display_name,
                u.user_email,
                um1.meta_value as role_type
            FROM {$wpdb->prefix}lccp_hour_tracker h
            LEFT JOIN {$wpdb->users} u ON h.user_id = u.ID
            LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'lccp_role_type'
            ORDER BY h.created_at DESC
            LIMIT 10"
        );

        ?>
        <div class="lccp-activity-block">
            <?php if ($recent_activities): ?>
                <?php foreach ($recent_activities as $activity): ?>
                    <div class="lccp-activity-item">
                        <div class="lccp-activity-header">
                            <span class="lccp-activity-user"><?php echo esc_html($activity->display_name); ?></span>
                            <?php if ($activity->role_type): ?>
                                <span class="lccp-activity-role"><?php echo esc_html($activity->role_type); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="lccp-activity-content">
                            Tracked <?php echo $activity->session_length; ?> hours with
                            <?php echo esc_html($activity->client_name); ?>
                        </div>
                        <div class="lccp-activity-time">
                            <?php echo human_time_diff(strtotime($activity->created_at), current_time('timestamp')); ?> ago
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="lccp-empty-state">
                    <p class="lccp-empty-state-description">No recent activity to display.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="lccp-widget-filters">
            <select id="activity-filter-role" class="activity-filter">
                <option value="">All Roles</option>
                <option value="mentor">Mentors</option>
                <option value="bigbird">Big Birds</option>
                <option value="pc">PCs</option>
                <option value="student">Students</option>
            </select>

            <select id="activity-filter-time" class="activity-filter">
                <option value="24">Last 24 Hours</option>
                <option value="168">Last Week</option>
                <option value="720">Last Month</option>
            </select>
        </div>
        <?php
    }

    /**
     * WIDGET 3: Team Performance (Admin Only)
     * Consolidates: Mentor Performance, PC Performance, Big Bird Performance
     */
    public function render_team_performance_widget() {
        global $wpdb;

        // Get all mentor stats in a single efficient query with JOINs
        $mentor_stats = $wpdb->get_results("
            SELECT
                u.ID,
                u.display_name,
                'Mentor' as role_label,
                COUNT(DISTINCT a.student_id) as student_count,
                COALESCE(SUM(h.session_length), 0) as month_hours
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
                AND um.meta_key = '{$wpdb->prefix}capabilities'
                AND um.meta_value LIKE '%lccp_mentor%'
            LEFT JOIN {$wpdb->prefix}lccp_assignments a
                ON u.ID = a.mentor_id AND a.status = 'active'
            LEFT JOIN {$wpdb->prefix}lccp_hour_tracker h
                ON u.ID = h.user_id
                AND MONTH(h.session_date) = MONTH(CURRENT_DATE())
                AND YEAR(h.session_date) = YEAR(CURRENT_DATE())
            GROUP BY u.ID, u.display_name
            ORDER BY u.display_name ASC
        ");

        ?>
        <?php if (!empty($mentor_stats)): ?>
            <table class="lccp-widget-table">
                <thead>
                    <tr>
                        <th>Team Member</th>
                        <th>Role</th>
                        <th>Team Size</th>
                        <th>Hours (Month)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mentor_stats as $member): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($member->display_name); ?></strong>
                            </td>
                            <td><?php echo esc_html($member->role_label); ?></td>
                            <td><?php echo intval($member->student_count); ?></td>
                            <td><?php echo number_format($member->month_hours, 1); ?></td>
                            <td>
                                <a href="#" class="view-details" data-member-id="<?php echo $member->ID; ?>">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="lccp-empty-state">
                <p class="lccp-empty-state-description">No team member data available.</p>
            </div>
        <?php endif; ?>
        <?php
    }

    /**
     * WIDGET 4: My Team (Role-Specific)
     * Consolidates: My Mentorship Overview, Big Bird Team, My PC Team, My Assigned Students
     */
    public function render_my_team_widget() {
        if ($this->user_role_level >= 75) {
            $this->render_mentor_team_view();
        } elseif ($this->user_role_level >= 50) {
            $this->render_bigbird_team_view();
        } elseif ($this->user_role_level >= 25) {
            $this->render_pc_team_view();
        }
    }

    /**
     * Mentor team view - Shows Big Birds, PCs, and Students
     */
    private function render_mentor_team_view() {
        global $wpdb;
        $mentor_id = get_current_user_id();

        // Get students assigned to this mentor with their hours in a single query
        $students = $wpdb->get_results($wpdb->prepare("
            SELECT
                u.ID,
                u.display_name,
                COALESCE(SUM(h.session_length), 0) as total_hours
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um_mentor ON u.ID = um_mentor.user_id
                AND um_mentor.meta_key = 'mentor_id'
                AND um_mentor.meta_value = %s
            INNER JOIN {$wpdb->usermeta} um_role ON u.ID = um_role.user_id
                AND um_role.meta_key = '{$wpdb->prefix}capabilities'
                AND um_role.meta_value LIKE '%%subscriber%%'
            LEFT JOIN {$wpdb->prefix}lccp_hour_tracker h ON u.ID = h.user_id
            GROUP BY u.ID, u.display_name
            ORDER BY u.display_name ASC
        ", $mentor_id));

        if (!empty($students)) {
            echo '<ul class="lccp-team-list">';
            foreach ($students as $student) {
                $hours_completed = $student->total_hours;
                $progress_percent = min(100, ($hours_completed/75)*100);

                echo '<li class="lccp-team-member">';
                echo '<div class="lccp-team-member-header">';
                echo '<strong>' . esc_html($student->display_name) . '</strong>';
                echo '<span class="lccp-hours-badge">' . number_format($hours_completed, 1) . '/75 hrs</span>';
                echo '</div>';
                echo '<div class="lccp-progress-bar">';
                echo '<div class="lccp-progress-fill" style="width: ' . $progress_percent . '%"></div>';
                echo '</div>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<div class="lccp-empty-state">';
            echo '<p class="lccp-empty-state-description">No students currently assigned to you.</p>';
            echo '</div>';
        }
    }

    /**
     * Big Bird team view - Shows PCs and their students
     */
    private function render_bigbird_team_view() {
        global $wpdb;

        $pcs = $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID, u.display_name, a.assigned_date
            FROM {$wpdb->prefix}lccp_assignments a
            JOIN {$wpdb->users} u ON a.pc_id = u.ID
            WHERE a.bigbird_id = %d AND a.status = 'active'",
            get_current_user_id()
        ));

        if ($pcs) {
            echo '<ul class="lccp-team-list">';
            foreach ($pcs as $pc) {
                $hours = $this->get_user_month_hours($pc->ID);
                echo '<li class="lccp-team-member">';
                echo '<div class="lccp-team-member-header">';
                echo '<strong>' . esc_html($pc->display_name) . '</strong>';
                echo '<span class="lccp-hours-badge">' . number_format($hours, 1) . ' hrs this month</span>';
                echo '</div>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<div class="lccp-empty-state">';
            echo '<p class="lccp-empty-state-description">No PCs currently assigned.</p>';
            echo '</div>';
        }
    }

    /**
     * PC team view - Shows assigned students
     */
    private function render_pc_team_view() {
        global $wpdb;

        $students = $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID, u.display_name, a.assigned_date
            FROM {$wpdb->prefix}lccp_assignments a
            JOIN {$wpdb->users} u ON a.student_id = u.ID
            WHERE a.pc_id = %d AND a.status = 'active'",
            get_current_user_id()
        ));

        if ($students) {
            echo '<ul class="lccp-team-list">';
            foreach ($students as $student) {
                echo '<li class="lccp-team-member">';
                echo '<div class="lccp-team-member-header">';
                echo '<strong>' . esc_html($student->display_name) . '</strong>';
                echo '<a href="' . admin_url('admin.php?page=lccp-student-details&student_id=' . $student->ID) . '" class="button button-small">View Progress</a>';
                echo '</div>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<div class="lccp-empty-state">';
            echo '<p class="lccp-empty-state-description">No students currently assigned.</p>';
            echo '</div>';
        }
    }

    /**
     * WIDGET 5: Course & Hour Progress (All Roles 25+)
     * Consolidates: Course Progress, Hour Tracking, Quiz Performance, Course Timeline
     */
    public function render_course_hour_progress_widget() {
        global $wpdb;
        $user_id = get_current_user_id();

        // Get hour progress
        $total_hours = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(session_length), 0) FROM {$wpdb->prefix}lccp_hour_tracker WHERE user_id = %d",
            $user_id
        ));
        $hours_required = 75;
        $hours_percent = min(100, ($total_hours / $hours_required) * 100);

        // Get course progress
        $course_progress = $this->get_user_course_progress($user_id);

        ?>
        <div class="lccp-progress-section">
            <h4>Hour Progress</h4>
            <div class="lccp-progress-container">
                <div class="lccp-progress-label">
                    <span>Hours Logged</span>
                    <span><?php echo number_format($total_hours, 1); ?> / <?php echo $hours_required; ?> hrs</span>
                </div>
                <div class="lccp-progress-bar">
                    <div class="lccp-progress-fill" style="width: <?php echo $hours_percent; ?>%;">
                        <?php echo round($hours_percent); ?>%
                    </div>
                </div>
            </div>
        </div>

        <div class="lccp-progress-section">
            <h4>Course Progress</h4>
            <div class="lccp-widget-stats">
                <div class="lccp-stat-box">
                    <h5>Overall Progress</h5>
                    <div class="lccp-stat-number"><?php echo $course_progress['average_progress']; ?>%</div>
                </div>
                <div class="lccp-stat-box">
                    <h5>Completed</h5>
                    <div class="lccp-stat-number"><?php echo $course_progress['completed_courses']; ?></div>
                </div>
                <div class="lccp-stat-box">
                    <h5>In Progress</h5>
                    <div class="lccp-stat-number"><?php echo $course_progress['in_progress_courses']; ?></div>
                </div>
            </div>
        </div>

        <div class="lccp-widget-actions">
            <a href="<?php echo admin_url('admin.php?page=lccp-my-courses'); ?>" class="button button-primary">View All Courses</a>
            <a href="<?php echo admin_url('admin.php?page=lccp-log-hours'); ?>" class="button">Log Hours</a>
        </div>
        <?php
    }

    /**
     * Helper Methods
     */
    private function calculate_overall_completion_rate() {
        if (!function_exists('learndash_get_course_list')) {
            return 0;
        }

        // Check cache first (15 minute cache)
        $cache_key = 'lccp_overall_completion_rate';
        $cached_rate = get_transient($cache_key);
        if (false !== $cached_rate) {
            return $cached_rate;
        }

        global $wpdb;

        // Get average completion using efficient database query
        $completion_rate = $wpdb->get_var("
            SELECT AVG(CAST(meta_value AS DECIMAL(5,2)))
            FROM {$wpdb->usermeta}
            WHERE meta_key LIKE 'course_%_progress'
            AND meta_value IS NOT NULL
            AND meta_value != ''
            AND CAST(meta_value AS DECIMAL(5,2)) BETWEEN 0 AND 100
        ");

        $rate = $completion_rate ? round($completion_rate) : 0;

        // Cache for 15 minutes
        set_transient($cache_key, $rate, 15 * MINUTE_IN_SECONDS);

        return $rate;
    }

    private function get_user_month_hours($user_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(session_length), 0)
            FROM {$wpdb->prefix}lccp_hour_tracker
            WHERE user_id = %d
            AND MONTH(session_date) = MONTH(CURRENT_DATE())
            AND YEAR(session_date) = YEAR(CURRENT_DATE())",
            $user_id
        ));
    }

    /**
     * Get user course progress data
     */
    private function get_user_course_progress($user_id) {
        $completed_courses = 0;
        $in_progress_courses = 0;
        $total_progress = 0;
        $course_count = 0;

        // Check if LearnDash is active
        if (!function_exists('learndash_user_get_enrolled_courses')) {
            return array(
                'average_progress' => 0,
                'completed_courses' => 0,
                'in_progress_courses' => 0,
                'total_courses' => 0
            );
        }

        // Get user's enrolled courses
        $enrolled_courses = learndash_user_get_enrolled_courses($user_id);

        if (!empty($enrolled_courses)) {
            foreach ($enrolled_courses as $course_id) {
                $course_progress = learndash_course_progress(array(
                    'user_id' => $user_id,
                    'course_id' => $course_id,
                    'array' => true
                ));

                if (isset($course_progress['percentage'])) {
                    $progress_percentage = $course_progress['percentage'];
                    $total_progress += $progress_percentage;
                    $course_count++;

                    if ($progress_percentage >= 100) {
                        $completed_courses++;
                    } elseif ($progress_percentage > 0) {
                        $in_progress_courses++;
                    }
                }
            }
        }

        $average_progress = $course_count > 0 ? round($total_progress / $course_count) : 0;

        return array(
            'average_progress' => $average_progress,
            'completed_courses' => $completed_courses,
            'in_progress_courses' => $in_progress_courses,
            'total_courses' => $course_count
        );
    }

    /**
     * AJAX Handlers
     */
    public function ajax_get_student_progress() {
        check_ajax_referer('lccp_dashboard_nonce', 'nonce');

        if (!current_user_can('edit_users')) {
            wp_send_json_error('Insufficient permissions');
        }

        $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;

        if (!$student_id) {
            wp_send_json_error('Invalid student ID');
        }

        $progress = $this->get_user_course_progress($student_id);
        wp_send_json_success($progress);
    }

    public function ajax_get_hour_statistics() {
        check_ajax_referer('lccp_dashboard_nonce', 'nonce');

        global $wpdb;
        $user_id = get_current_user_id();

        $stats = array(
            'total_hours' => $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(session_length), 0) FROM {$wpdb->prefix}lccp_hour_tracker WHERE user_id = %d",
                $user_id
            )),
            'month_hours' => $this->get_user_month_hours($user_id)
        );

        wp_send_json_success($stats);
    }

    public function ajax_get_activity_feed() {
        check_ajax_referer('lccp_dashboard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
        $time = isset($_POST['time']) ? intval($_POST['time']) : 24;

        // Implementation would filter activities based on role and time
        wp_send_json_success(array('html' => '<p>Filtered activities would appear here</p>'));
    }
}

// Initialize enhanced dashboards
new LCCP_Enhanced_Dashboards();
