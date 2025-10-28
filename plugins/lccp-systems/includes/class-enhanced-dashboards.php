<?php
/**
 * Enhanced Dashboards with Permission Hierarchy
 * 
 * Hierarchy: PC < Big Bird < Mentor < Rhonda (admin)
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
            '1.1.0'
        );

        // Enqueue dashboard widgets JavaScript
        wp_enqueue_script(
            'lccp-dashboard-widgets',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/dashboard-widgets.js',
            array('jquery'),
            '1.1.0',
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
        
        // Add role-specific widgets
        if ($this->user_role_level >= 100) {
            // Rhonda/Admin widgets
            wp_add_dashboard_widget(
                'lccp_admin_overview',
                'LCCP Program Overview',
                array($this, 'render_admin_overview_widget')
            );
            
            wp_add_dashboard_widget(
                'lccp_all_activity',
                'All Program Activity',
                array($this, 'render_all_activity_widget')
            );
            
            wp_add_dashboard_widget(
                'lccp_mentor_performance',
                'Mentor Performance Metrics',
                array($this, 'render_mentor_performance_widget')
            );
        }
        
        if ($this->user_role_level >= 75) {
            // Mentor widgets
            wp_add_dashboard_widget(
                'lccp_mentor_students',
                'My Mentorship Overview',
                array($this, 'render_mentor_students_widget')
            );
            
            wp_add_dashboard_widget(
                'lccp_big_bird_oversight',
                'Big Bird Team Performance',
                array($this, 'render_bigbird_oversight_widget')
            );
        }
        
        if ($this->user_role_level >= 50) {
            // Big Bird widgets
            wp_add_dashboard_widget(
                'lccp_big_bird_pcs',
                'My PC Team',
                array($this, 'render_bigbird_pcs_widget')
            );
            
            wp_add_dashboard_widget(
                'lccp_pc_performance',
                'PC Performance Tracking',
                array($this, 'render_pc_performance_widget')
            );
        }
        
        if ($this->user_role_level >= 25) {
            // PC widgets
            wp_add_dashboard_widget(
                'lccp_pc_students',
                'My Assigned Students',
                array($this, 'render_pc_students_widget')
            );
            
            wp_add_dashboard_widget(
                'lccp_student_hours',
                'Student Hour Tracking',
                array($this, 'render_student_hours_widget')
            );
        }
        
        // Common widgets for all LCCP roles
        if ($this->user_role_level >= 25) {
            wp_add_dashboard_widget(
                'lccp_course_progress',
                'Course Progress Overview',
                array($this, 'render_course_progress_widget')
            );
            
            wp_add_dashboard_widget(
                'lccp_upcoming_sessions',
                'Upcoming Sessions',
                array($this, 'render_upcoming_sessions_widget')
            );
        }
    }
    
    /**
     * Admin Overview Widget - Only for Rhonda/Admins (WordPress Standard)
     */
    public function render_admin_overview_widget() {
        global $wpdb;

        // Get comprehensive statistics - using count_users() for efficiency
        $user_counts = count_users();
        $total_students = isset($user_counts['avail_roles']['subscriber']) ? $user_counts['avail_roles']['subscriber'] : 0;
        $total_mentors = isset($user_counts['avail_roles']['lccp_mentor']) ? $user_counts['avail_roles']['lccp_mentor'] : 0;
        $total_bigbirds = isset($user_counts['avail_roles']['lccp_big_bird']) ? $user_counts['avail_roles']['lccp_big_bird'] : 0;
        $total_pcs = isset($user_counts['avail_roles']['lccp_pc']) ? $user_counts['avail_roles']['lccp_pc'] : 0;

        // Get hour statistics
        $total_hours = $wpdb->get_var("SELECT SUM(session_length) FROM {$wpdb->prefix}lccp_hour_tracker");
        $this_month_hours = $wpdb->get_var(
            "SELECT SUM(session_length) FROM {$wpdb->prefix}lccp_hour_tracker
            WHERE MONTH(session_date) = MONTH(CURRENT_DATE())"
        );

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
                <h4>Program Candidates</h4>
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
     * All Activity Widget - Shows all program activity for Rhonda (WordPress Standard)
     */
    public function render_all_activity_widget() {
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
     * Mentor Performance Widget (WordPress Standard)
     */
    public function render_mentor_performance_widget() {
        global $wpdb;

        // Get all mentor stats in a single efficient query with JOINs
        $mentor_stats = $wpdb->get_results("
            SELECT
                u.ID,
                u.display_name,
                COUNT(DISTINCT a.student_id) as student_count,
                COALESCE(SUM(h.session_length), 0) as month_hours,
                0 as completion_rate
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
                        <th>Mentor</th>
                        <th>Students</th>
                        <th>Hours (Month)</th>
                        <th>Completion Rate</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mentor_stats as $mentor): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($mentor->display_name); ?></strong>
                            </td>
                            <td><?php echo intval($mentor->student_count); ?></td>
                            <td><?php echo number_format($mentor->month_hours, 1); ?></td>
                            <td>
                                <span class="lccp-mini-progress">
                                    <span class="lccp-mini-progress-fill" style="width: <?php echo $mentor->completion_rate; ?>%"></span>
                                </span>
                                <?php echo $mentor->completion_rate; ?>%
                            </td>
                            <td>
                                <a href="#" class="view-details" data-mentor-id="<?php echo $mentor->ID; ?>">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="lccp-empty-state">
                <p class="lccp-empty-state-description">No mentor data available.</p>
            </div>
        <?php endif; ?>
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
        // LearnDash stores course progress in user meta as ld_course_progress_{course_id}
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
    
    private function get_mentor_student_count($mentor_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT student_id) 
            FROM {$wpdb->prefix}lccp_assignments 
            WHERE mentor_id = %d AND status = 'active'",
            $mentor_id
        ));
    }
    
    private function get_user_month_hours($user_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(session_length) 
            FROM {$wpdb->prefix}lccp_hour_tracker 
            WHERE user_id = %d 
            AND MONTH(session_date) = MONTH(CURRENT_DATE())",
            $user_id
        ));
    }
    
    private function get_mentor_completion_rate($mentor_id) {
        // Calculate average completion rate for mentor's students
        global $wpdb;
        
        $students = $wpdb->get_col($wpdb->prepare(
            "SELECT student_id FROM {$wpdb->prefix}lccp_assignments 
            WHERE mentor_id = %d AND status = 'active'",
            $mentor_id
        ));
        
        if (empty($students) || !function_exists('learndash_get_course_list')) {
            return 0;
        }
        
        $total_progress = 0;
        $count = 0;
        
        foreach ($students as $student_id) {
            $courses = learndash_get_course_list();
            foreach ($courses as $course_id) {
                $progress = learndash_course_progress(array(
                    'user_id' => $student_id,
                    'course_id' => $course_id,
                    'array' => true
                ));
                
                if (isset($progress['percentage'])) {
                    $total_progress += $progress['percentage'];
                    $count++;
                }
            }
        }
        
        return $count > 0 ? round($total_progress / $count) : 0;
    }
    
    /**
     * Big Bird and PC specific widget methods would continue here...
     */
    public function render_bigbird_pcs_widget() {
        // Implementation for Big Bird PC team widget
        global $wpdb;
        
        $pcs = $wpdb->get_results($wpdb->prepare(
            "SELECT u.*, a.assigned_date
            FROM {$wpdb->prefix}lccp_assignments a
            JOIN {$wpdb->users} u ON a.pc_id = u.ID
            WHERE a.bigbird_id = %d AND a.status = 'active'",
            $this->current_user->ID
        ));
        
        if ($pcs) {
            echo '<ul class="pc-list">';
            foreach ($pcs as $pc) {
                $hours = $this->get_user_month_hours($pc->ID);
                echo '<li>';
                echo '<strong>' . esc_html($pc->display_name) . '</strong>';
                echo ' - ' . number_format($hours, 1) . ' hours this month';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No PCs currently assigned.</p>';
        }
    }
    
    public function render_pc_students_widget() {
        // Implementation for PC students widget
        global $wpdb;
        
        $students = $wpdb->get_results($wpdb->prepare(
            "SELECT u.*, a.assigned_date 
            FROM {$wpdb->prefix}lccp_assignments a
            JOIN {$wpdb->users} u ON a.student_id = u.ID
            WHERE a.pc_id = %d AND a.status = 'active'",
            $this->current_user->ID
        ));
        
        if ($students) {
            echo '<ul class="student-list">';
            foreach ($students as $student) {
                echo '<li>';
                echo '<strong>' . esc_html($student->display_name) . '</strong>';
                echo ' - <a href="' . admin_url('admin.php?page=lccp-student-details&student_id=' . $student->ID) . '">View Progress</a>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No students currently assigned.</p>';
        }
    }
    
    /**
     * Render Mentor Students Widget
     */
    public function render_mentor_students_widget() {
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
            echo '<ul class="lccp-student-list">';
            foreach ($students as $student) {
                $hours_completed = $student->total_hours;
                echo '<li>';
                echo '<strong>' . esc_html($student->display_name) . '</strong><br>';
                echo 'Hours: ' . number_format($hours_completed, 1) . '/75<br>';
                echo '<div class="progress-bar">';
                echo '<div class="progress" style="width: ' . min(100, ($hours_completed/75)*100) . '%"></div>';
                echo '</div>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No students currently assigned to you.</p>';
        }
    }
    
    /**
     * Render Big Bird Oversight Widget
     */
    public function render_bigbird_oversight_widget() {
        global $wpdb;

        // Get all PCs with their student counts in a single efficient query
        $pc_stats = $wpdb->get_results("
            SELECT
                u.ID,
                u.display_name,
                COUNT(DISTINCT um_students.user_id) as student_count
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um_role ON u.ID = um_role.user_id
                AND um_role.meta_key = '{$wpdb->prefix}capabilities'
                AND um_role.meta_value LIKE '%lccp_pc%'
            LEFT JOIN {$wpdb->usermeta} um_students ON u.ID = um_students.meta_value
                AND um_students.meta_key = 'pc_id'
            GROUP BY u.ID, u.display_name
            ORDER BY u.display_name ASC
        ");

        if (!empty($pc_stats)) {
            echo '<table class="lccp-pc-overview">';
            echo '<thead><tr><th>PC Name</th><th>Students</th><th>Avg Progress</th></tr></thead>';
            echo '<tbody>';

            foreach ($pc_stats as $pc) {
                echo '<tr>';
                echo '<td>' . esc_html($pc->display_name) . '</td>';
                echo '<td>' . intval($pc->student_count) . '</td>';
                echo '<td>-</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        } else {
            echo '<p>No PC team members found.</p>';
        }
    }
    
    /**
     * Render PC Performance Widget
     */
    public function render_pc_performance_widget() {
        global $wpdb;

        // Get performance metrics for PCs using count_users() for efficiency
        $user_counts = count_users();
        $pc_count = isset($user_counts['avail_roles']['lccp_pc']) ? $user_counts['avail_roles']['lccp_pc'] : 0;
        $active_students = isset($user_counts['avail_roles']['subscriber']) ? $user_counts['avail_roles']['subscriber'] : 0;

        echo '<div class="lccp-pc-performance">';
        echo '<h4>Team Performance Metrics</h4>';
        echo '<ul>';
        echo '<li>Total PCs: ' . $pc_count . '</li>';
        echo '<li>Active Students: ' . $active_students . '</li>';
        echo '<li>Average Completion Rate: Calculating...</li>';
        echo '</ul>';
        echo '</div>';
    }
    
    /**
     * Render Student Hours Widget
     */
    public function render_student_hours_widget() {
        global $wpdb;
        $pc_id = get_current_user_id();

        // Get students assigned to this PC with their hours in a single query
        $students = $wpdb->get_results($wpdb->prepare("
            SELECT
                u.ID,
                u.display_name,
                COALESCE(SUM(h.session_length), 0) as total_hours
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um_pc ON u.ID = um_pc.user_id
                AND um_pc.meta_key = 'pc_id'
                AND um_pc.meta_value = %s
            INNER JOIN {$wpdb->usermeta} um_role ON u.ID = um_role.user_id
                AND um_role.meta_key = '{$wpdb->prefix}capabilities'
                AND um_role.meta_value LIKE '%%subscriber%%'
            LEFT JOIN {$wpdb->prefix}lccp_hour_tracker h ON u.ID = h.user_id
            GROUP BY u.ID, u.display_name
            ORDER BY u.display_name ASC
        ", $pc_id));

        if (!empty($students)) {
            echo '<table class="lccp-hours-tracking">';
            echo '<thead><tr><th>Student</th><th>Hours</th><th>Status</th></tr></thead>';
            echo '<tbody>';

            foreach ($students as $student) {
                $hours = $student->total_hours;
                $status = $hours >= 75 ? 'Complete' : 'In Progress';

                echo '<tr>';
                echo '<td>' . esc_html($student->display_name) . '</td>';
                echo '<td>' . number_format($hours, 1) . '/75</td>';
                echo '<td>' . $status . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        } else {
            echo '<p>No students currently assigned.</p>';
        }
    }
    
    /**
     * Render Course Progress Widget
     */
    public function render_course_progress_widget() {
        echo '<div class="lccp-course-progress">';
        echo '<p>Course progress tracking will be displayed here.</p>';
        echo '<ul>';
        echo '<li>Module 1: Foundation - In Progress</li>';
        echo '<li>Module 2: Advanced Techniques - Not Started</li>';
        echo '<li>Module 3: Practice Sessions - Not Started</li>';
        echo '</ul>';
        echo '</div>';
    }
    
    /**
     * Render Upcoming Sessions Widget
     */
    public function render_upcoming_sessions_widget() {
        echo '<div class="lccp-upcoming-sessions">';
        echo '<h4>Next Sessions</h4>';
        echo '<ul>';
        echo '<li>Group Coaching - Tomorrow 2:00 PM EST</li>';
        echo '<li>Q&A Session - Friday 3:00 PM EST</li>';
        echo '<li>Monthly Review - Next Monday 1:00 PM EST</li>';
        echo '</ul>';
        echo '</div>';
    }
    
    /**
     * Get user course progress data
     */
    public function get_user_course_progress($user_id) {
        $completed_courses = 0;
        $in_progress_courses = 0;
        $total_progress = 0;
        $course_count = 0;
        
        // Get user's enrolled courses
        $enrolled_courses = learndash_user_get_enrolled_courses($user_id);
        
        if (!empty($enrolled_courses)) {
            foreach ($enrolled_courses as $course_id) {
                $course_progress = learndash_course_progress(array(
                    'user_id' => $user_id,
                    'course_id' => $course_id,
                    'post_id' => $course_id
                ));
                
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
        
        $average_progress = $course_count > 0 ? round($total_progress / $course_count) : 0;
        
        return array(
            'average_progress' => $average_progress,
            'completed_courses' => $completed_courses,
            'in_progress_courses' => $in_progress_courses,
            'total_courses' => $course_count
        );
    }

    public function enqueue_dashboard_assets($hook) {
        if ('index.php' !== $hook) {
            return;
        }
        
        wp_enqueue_script(
            'lccp-dashboard',
            LCCP_SYSTEMS_PLUGIN_URL . 'assets/js/dashboard.js',
            array('jquery'),
            LCCP_SYSTEMS_VERSION,
            true
        );
        
        wp_localize_script('lccp-dashboard', 'lccp_dashboard', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lccp_dashboard_nonce'),
            'refresh_interval' => 30000 // Refresh every 30 seconds
        ));
    }
}

// Initialize enhanced dashboards
new LCCP_Enhanced_Dashboards();