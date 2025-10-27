<?php
/**
 * LearnDash Widgets for LCCP Systems
 * 
 * Provides comprehensive learning widgets with role-based views
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_LearnDash_Widgets {
    
    private $user_role_level = 0;
    private $current_user_id;
    private $user_roles = array();
    
    public function __construct() {
        add_action('init', array($this, 'setup_user_context'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'), 999);
        add_action('widgets_init', array($this, 'register_widgets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_widget_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_lccp_refresh_widget', array($this, 'ajax_refresh_widget'));
        add_action('wp_ajax_lccp_get_quiz_details', array($this, 'ajax_get_quiz_details'));
        add_action('wp_ajax_lccp_get_assignment_details', array($this, 'ajax_get_assignment_details'));
    }
    
    /**
     * Setup user context for role-based views
     */
    public function setup_user_context() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $this->current_user_id = get_current_user_id();
        $user = wp_get_current_user();
        $this->user_roles = (array) $user->roles;
        
        // Determine user role level for hierarchical access
        if (in_array('administrator', $this->user_roles) || user_can($user, 'manage_options')) {
            $this->user_role_level = 100; // Admin/Rhonda
        } elseif (in_array('lccp_mentor', $this->user_roles)) {
            $this->user_role_level = 75; // Mentor
        } elseif (in_array('lccp_big_bird', $this->user_roles)) {
            $this->user_role_level = 50; // Big Bird
        } elseif (in_array('lccp_pc', $this->user_roles)) {
            $this->user_role_level = 25; // PC
        } else {
            $this->user_role_level = 10; // Student/Subscriber
        }
    }
    
    /**
     * Register all dashboard widgets
     */
    public function add_dashboard_widgets() {
        // Quiz Performance Widget - All roles
        wp_add_dashboard_widget(
            'lccp_quiz_performance',
            'Quiz Performance ' . $this->get_role_context_label(),
            array($this, 'render_quiz_performance_widget')
        );
        
        // Assignment Tracker - All roles
        wp_add_dashboard_widget(
            'lccp_assignment_tracker',
            'Assignment Tracker ' . $this->get_role_context_label(),
            array($this, 'render_assignment_tracker_widget')
        );
        
        // Course Timeline - All roles
        wp_add_dashboard_widget(
            'lccp_course_timeline',
            'Course Completion Timeline',
            array($this, 'render_course_timeline_widget')
        );
        
        // Peer Learning - Students and PCs
        if ($this->user_role_level <= 25) {
            wp_add_dashboard_widget(
                'lccp_peer_learning',
                'Peer Learning Activity',
                array($this, 'render_peer_learning_widget')
            );
        }
        
        // Certificates & Badges - All roles
        wp_add_dashboard_widget(
            'lccp_certificates_badges',
            'Certificates & Achievements',
            array($this, 'render_certificates_widget')
        );
        
        // Learning Streak - Primarily for students
        if ($this->user_role_level <= 50) {
            wp_add_dashboard_widget(
                'lccp_learning_streak',
                'Learning Streak Tracker',
                array($this, 'render_learning_streak_widget')
            );
        }
        
        // Topic Focus Time - All roles
        wp_add_dashboard_widget(
            'lccp_topic_focus',
            'Topic Focus Analytics',
            array($this, 'render_topic_focus_widget')
        );
        
        // Mentor Feedback - Students and Mentors
        if ($this->user_role_level <= 25 || $this->user_role_level >= 75) {
            wp_add_dashboard_widget(
                'lccp_mentor_feedback',
                'Mentor Feedback & Notes',
                array($this, 'render_mentor_feedback_widget')
            );
        }
        
        // Resource Library - All roles
        wp_add_dashboard_widget(
            'lccp_resource_library',
            'Quick Resource Access',
            array($this, 'render_resource_library_widget')
        );
        
        // Live Sessions - All roles
        wp_add_dashboard_widget(
            'lccp_live_sessions',
            'Live Sessions & Recordings',
            array($this, 'render_live_sessions_widget')
        );
    }
    
    /**
     * Get role context label for widget titles
     */
    private function get_role_context_label() {
        if ($this->user_role_level >= 100) {
            return '(Admin Overview)';
        } elseif ($this->user_role_level >= 75) {
            return '(Mentor View)';
        } elseif ($this->user_role_level >= 50) {
            return '(Big Bird View)';
        } elseif ($this->user_role_level >= 25) {
            return '(PC View)';
        }
        return '';
    }
    
    /**
     * 1. Quiz Performance Widget
     */
    public function render_quiz_performance_widget() {
        global $wpdb;
        
        echo '<div class="lccp-widget-content quiz-performance">';
        
        if ($this->user_role_level >= 75) {
            // Admin/Mentor view - Show all students' quiz performance
            $this->render_quiz_performance_admin_view();
        } elseif ($this->user_role_level >= 25) {
            // PC/BigBird view - Show assigned students
            $this->render_quiz_performance_team_view();
        } else {
            // Student view - Personal quiz performance
            $this->render_quiz_performance_student_view();
        }
        
        echo '</div>';
    }
    
    private function render_quiz_performance_student_view() {
        $user_id = $this->current_user_id;
        
        // Get user's quiz attempts
        $quiz_attempts = learndash_get_user_quiz_attempts($user_id);
        
        if (empty($quiz_attempts)) {
            echo '<p>No quizzes attempted yet.</p>';
            return;
        }
        
        echo '<div class="quiz-stats">';
        
        // Calculate statistics
        $total_quizzes = count($quiz_attempts);
        $passed_quizzes = 0;
        $total_score = 0;
        
        foreach ($quiz_attempts as $attempt) {
            if ($attempt['pass']) {
                $passed_quizzes++;
            }
            $total_score += floatval($attempt['percentage']);
        }
        
        $avg_score = $total_quizzes > 0 ? round($total_score / $total_quizzes, 1) : 0;
        
        echo '<div class="stat-cards">';
        echo '<div class="stat-card">';
        echo '<span class="stat-value">' . $total_quizzes . '</span>';
        echo '<span class="stat-label">Quizzes Taken</span>';
        echo '</div>';
        
        echo '<div class="stat-card">';
        echo '<span class="stat-value">' . $passed_quizzes . '</span>';
        echo '<span class="stat-label">Passed</span>';
        echo '</div>';
        
        echo '<div class="stat-card">';
        echo '<span class="stat-value">' . $avg_score . '%</span>';
        echo '<span class="stat-label">Avg Score</span>';
        echo '</div>';
        echo '</div>';
        
        // Recent quiz attempts
        echo '<h4>Recent Attempts</h4>';
        echo '<ul class="quiz-list">';
        
        $recent_attempts = array_slice($quiz_attempts, -5);
        foreach (array_reverse($recent_attempts) as $attempt) {
            $quiz_title = get_the_title($attempt['quiz']);
            $date = date('M j', $attempt['time']);
            $score = round($attempt['percentage'], 1);
            $status = $attempt['pass'] ? 'passed' : 'failed';
            
            echo '<li class="quiz-item ' . $status . '">';
            echo '<span class="quiz-name">' . esc_html($quiz_title) . '</span>';
            echo '<span class="quiz-score">' . $score . '%</span>';
            echo '<span class="quiz-date">' . $date . '</span>';
            echo '</li>';
        }
        
        echo '</ul>';
        echo '</div>';
    }
    
    private function render_quiz_performance_team_view() {
        // Get assigned students based on role
        $students = $this->get_assigned_students();
        
        if (empty($students)) {
            echo '<p>No students assigned.</p>';
            return;
        }
        
        echo '<div class="team-quiz-overview">';
        echo '<table class="quiz-performance-table">';
        echo '<thead><tr>';
        echo '<th>Student</th>';
        echo '<th>Quizzes</th>';
        echo '<th>Avg Score</th>';
        echo '<th>Pass Rate</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($students as $student) {
            $quiz_attempts = learndash_get_user_quiz_attempts($student->ID);
            $total = count($quiz_attempts);
            
            if ($total == 0) {
                continue;
            }
            
            $passed = 0;
            $total_score = 0;
            
            foreach ($quiz_attempts as $attempt) {
                if ($attempt['pass']) {
                    $passed++;
                }
                $total_score += floatval($attempt['percentage']);
            }
            
            $avg_score = round($total_score / $total, 1);
            $pass_rate = round(($passed / $total) * 100, 1);
            
            echo '<tr>';
            echo '<td>' . esc_html($student->display_name) . '</td>';
            echo '<td>' . $total . '</td>';
            echo '<td>' . $avg_score . '%</td>';
            echo '<td>' . $pass_rate . '%</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
    }
    
    private function render_quiz_performance_admin_view() {
        global $wpdb;
        
        // Get overall statistics
        $total_users = count(get_users(array('role' => 'subscriber')));
        
        echo '<div class="admin-quiz-stats">';
        echo '<h4>System-Wide Quiz Performance</h4>';
        
        // Get quiz statistics from database
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(*) as total_attempts,
                AVG(CAST(activity_meta_value as DECIMAL(10,2))) as avg_score
            FROM {$wpdb->prefix}learndash_user_activity 
            WHERE activity_type = 'quiz'
            AND activity_status = 1
        ");
        
        echo '<div class="stat-cards">';
        echo '<div class="stat-card">';
        echo '<span class="stat-value">' . ($stats->unique_users ?: 0) . '</span>';
        echo '<span class="stat-label">Active Quiz Takers</span>';
        echo '</div>';
        
        echo '<div class="stat-card">';
        echo '<span class="stat-value">' . ($stats->total_attempts ?: 0) . '</span>';
        echo '<span class="stat-label">Total Attempts</span>';
        echo '</div>';
        
        echo '<div class="stat-card">';
        echo '<span class="stat-value">' . round($stats->avg_score ?: 0, 1) . '%</span>';
        echo '<span class="stat-label">Platform Avg</span>';
        echo '</div>';
        echo '</div>';
        
        // Top performing students
        echo '<h4>Top Performers</h4>';
        $top_performers = $wpdb->get_results("
            SELECT 
                u.display_name,
                COUNT(*) as quiz_count,
                AVG(CAST(a.activity_meta_value as DECIMAL(10,2))) as avg_score
            FROM {$wpdb->prefix}learndash_user_activity a
            JOIN {$wpdb->users} u ON a.user_id = u.ID
            WHERE a.activity_type = 'quiz'
            AND a.activity_status = 1
            GROUP BY a.user_id
            ORDER BY avg_score DESC
            LIMIT 5
        ");
        
        if (!empty($top_performers)) {
            echo '<ol class="top-performers">';
            foreach ($top_performers as $performer) {
                echo '<li>';
                echo esc_html($performer->display_name);
                echo ' - ' . round($performer->avg_score, 1) . '% avg';
                echo '</li>';
            }
            echo '</ol>';
        }
        
        echo '</div>';
    }
    
    /**
     * 2. Assignment Submission Tracker Widget
     */
    public function render_assignment_tracker_widget() {
        echo '<div class="lccp-widget-content assignment-tracker">';
        
        if ($this->user_role_level >= 75) {
            $this->render_assignments_admin_view();
        } elseif ($this->user_role_level >= 25) {
            $this->render_assignments_team_view();
        } else {
            $this->render_assignments_student_view();
        }
        
        echo '</div>';
    }
    
    private function render_assignments_student_view() {
        $user_id = $this->current_user_id;
        
        // Get user's assignments
        $assignments = get_posts(array(
            'post_type' => 'sfwd-assignment',
            'author' => $user_id,
            'posts_per_page' => -1,
            'post_status' => array('publish', 'pending', 'draft')
        ));
        
        $pending = array();
        $graded = array();
        $submitted = array();
        
        foreach ($assignments as $assignment) {
            $approval_status = get_post_meta($assignment->ID, 'approval_status', true);
            
            if ($approval_status == 'approved') {
                $graded[] = $assignment;
            } elseif ($assignment->post_status == 'publish') {
                $submitted[] = $assignment;
            } else {
                $pending[] = $assignment;
            }
        }
        
        echo '<div class="assignment-stats">';
        echo '<div class="stat-cards">';
        
        echo '<div class="stat-card">';
        echo '<span class="stat-value">' . count($pending) . '</span>';
        echo '<span class="stat-label">Pending</span>';
        echo '</div>';
        
        echo '<div class="stat-card">';
        echo '<span class="stat-value">' . count($submitted) . '</span>';
        echo '<span class="stat-label">Submitted</span>';
        echo '</div>';
        
        echo '<div class="stat-card">';
        echo '<span class="stat-value">' . count($graded) . '</span>';
        echo '<span class="stat-label">Graded</span>';
        echo '</div>';
        
        echo '</div>';
        
        // List recent assignments
        if (!empty($submitted)) {
            echo '<h4>Awaiting Review</h4>';
            echo '<ul class="assignment-list">';
            foreach (array_slice($submitted, 0, 3) as $assignment) {
                $lesson_id = get_post_meta($assignment->ID, 'lesson_id', true);
                $lesson_title = get_the_title($lesson_id);
                
                echo '<li>';
                echo '<strong>' . esc_html($assignment->post_title) . '</strong><br>';
                echo '<small>Lesson: ' . esc_html($lesson_title) . '</small><br>';
                echo '<small>Submitted: ' . get_the_date('M j, Y', $assignment) . '</small>';
                echo '</li>';
            }
            echo '</ul>';
        }
        
        echo '</div>';
    }
    
    private function render_assignments_team_view() {
        $students = $this->get_assigned_students();
        
        if (empty($students)) {
            echo '<p>No students assigned.</p>';
            return;
        }
        
        echo '<div class="team-assignments">';
        echo '<h4>Student Assignments</h4>';
        echo '<table class="assignment-table">';
        echo '<thead><tr>';
        echo '<th>Student</th>';
        echo '<th>Pending</th>';
        echo '<th>Submitted</th>';
        echo '<th>Graded</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($students as $student) {
            $assignments = get_posts(array(
                'post_type' => 'sfwd-assignment',
                'author' => $student->ID,
                'posts_per_page' => -1,
                'post_status' => array('publish', 'pending', 'draft')
            ));
            
            $pending = 0;
            $submitted = 0;
            $graded = 0;
            
            foreach ($assignments as $assignment) {
                $approval_status = get_post_meta($assignment->ID, 'approval_status', true);
                
                if ($approval_status == 'approved') {
                    $graded++;
                } elseif ($assignment->post_status == 'publish') {
                    $submitted++;
                } else {
                    $pending++;
                }
            }
            
            echo '<tr>';
            echo '<td>' . esc_html($student->display_name) . '</td>';
            echo '<td>' . $pending . '</td>';
            echo '<td>' . $submitted . '</td>';
            echo '<td>' . $graded . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
    }
    
    private function render_assignments_admin_view() {
        global $wpdb;
        
        // Get all assignments needing review
        $assignments_needing_review = get_posts(array(
            'post_type' => 'sfwd-assignment',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'approval_status',
                    'value' => 'approved',
                    'compare' => '!='
                )
            )
        ));
        
        echo '<div class="admin-assignment-overview">';
        echo '<h4>System Assignment Overview</h4>';
        
        echo '<div class="stat-cards">';
        echo '<div class="stat-card urgent">';
        echo '<span class="stat-value">' . count($assignments_needing_review) . '</span>';
        echo '<span class="stat-label">Need Review</span>';
        echo '</div>';
        echo '</div>';
        
        // List assignments needing urgent review
        if (!empty($assignments_needing_review)) {
            echo '<h4>Urgent Reviews Needed</h4>';
            echo '<ul class="urgent-assignments">';
            
            foreach (array_slice($assignments_needing_review, 0, 5) as $assignment) {
                $author = get_user_by('id', $assignment->post_author);
                $days_waiting = floor((time() - strtotime($assignment->post_date)) / 86400);
                
                echo '<li>';
                echo '<strong>' . esc_html($author->display_name) . '</strong> - ';
                echo esc_html($assignment->post_title);
                echo ' <span class="days-waiting">(' . $days_waiting . ' days waiting)</span>';
                echo '</li>';
            }
            
            echo '</ul>';
        }
        
        echo '</div>';
    }
    
    /**
     * 3. Course Completion Timeline Widget
     */
    public function render_course_timeline_widget() {
        echo '<div class="lccp-widget-content course-timeline">';
        
        if ($this->user_role_level >= 75) {
            $this->render_timeline_admin_view();
        } elseif ($this->user_role_level >= 25) {
            $this->render_timeline_team_view();
        } else {
            $this->render_timeline_student_view();
        }
        
        echo '</div>';
    }
    
    private function render_timeline_student_view() {
        $user_id = $this->current_user_id;
        $courses = learndash_user_get_enrolled_courses($user_id);
        
        if (empty($courses)) {
            echo '<p>No courses enrolled.</p>';
            return;
        }
        
        echo '<div class="course-timeline">';
        
        foreach ($courses as $course_id) {
            $course_title = get_the_title($course_id);
            $progress = learndash_course_progress(array(
                'user_id' => $user_id,
                'course_id' => $course_id,
                'array' => true
            ));
            
            $percentage = isset($progress['percentage']) ? $progress['percentage'] : 0;
            $completed = isset($progress['completed']) ? $progress['completed'] : 0;
            $total = isset($progress['total']) ? $progress['total'] : 0;
            
            echo '<div class="course-progress-item">';
            echo '<h4>' . esc_html($course_title) . '</h4>';
            echo '<div class="progress-bar-container">';
            echo '<div class="progress-bar">';
            echo '<div class="progress-fill" style="width: ' . $percentage . '%;"></div>';
            echo '</div>';
            echo '<span class="progress-text">' . $completed . '/' . $total . ' lessons (' . $percentage . '%)</span>';
            echo '</div>';
            
            // Show completion date if completed
            if ($percentage == 100) {
                $completion_date = learndash_user_get_course_completed_date($user_id, $course_id);
                if ($completion_date) {
                    echo '<small>Completed: ' . date('M j, Y', $completion_date) . '</small>';
                }
            } else {
                // Estimate completion date
                if ($completed > 0) {
                    $days_since_start = 30; // Placeholder
                    $rate = $completed / $days_since_start;
                    $days_remaining = ($total - $completed) / $rate;
                    $estimated_date = date('M j, Y', strtotime('+' . ceil($days_remaining) . ' days'));
                    echo '<small>Est. completion: ' . $estimated_date . '</small>';
                }
            }
            
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    private function render_timeline_team_view() {
        $students = $this->get_assigned_students();
        
        if (empty($students)) {
            echo '<p>No students assigned.</p>';
            return;
        }
        
        echo '<div class="team-timeline">';
        echo '<h4>Student Progress Overview</h4>';
        
        foreach ($students as $student) {
            $courses = learndash_user_get_enrolled_courses($student->ID);
            
            if (empty($courses)) {
                continue;
            }
            
            echo '<div class="student-timeline">';
            echo '<h5>' . esc_html($student->display_name) . '</h5>';
            
            foreach ($courses as $course_id) {
                $progress = learndash_course_progress(array(
                    'user_id' => $student->ID,
                    'course_id' => $course_id,
                    'array' => true
                ));
                
                $percentage = isset($progress['percentage']) ? $progress['percentage'] : 0;
                
                echo '<div class="mini-progress">';
                echo '<span class="course-name">' . get_the_title($course_id) . '</span>';
                echo '<div class="mini-progress-bar">';
                echo '<div class="progress-fill" style="width: ' . $percentage . '%;"></div>';
                echo '</div>';
                echo '<span class="percentage">' . $percentage . '%</span>';
                echo '</div>';
            }
            
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    private function render_timeline_admin_view() {
        global $wpdb;
        
        // Get course completion statistics
        $courses = get_posts(array(
            'post_type' => 'sfwd-courses',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        echo '<div class="admin-timeline">';
        echo '<h4>Course Completion Rates</h4>';
        echo '<table class="completion-table">';
        echo '<thead><tr>';
        echo '<th>Course</th>';
        echo '<th>Enrolled</th>';
        echo '<th>In Progress</th>';
        echo '<th>Completed</th>';
        echo '<th>Avg Time</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($courses as $course) {
            $enrolled = count(learndash_get_users_for_course($course->ID));
            
            // Get completion stats
            $completed = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(DISTINCT user_id) 
                FROM {$wpdb->prefix}learndash_user_activity 
                WHERE course_id = %d 
                AND activity_type = 'course' 
                AND activity_status = 1
            ", $course->ID));
            
            $in_progress = $enrolled - $completed;
            
            echo '<tr>';
            echo '<td>' . esc_html($course->post_title) . '</td>';
            echo '<td>' . $enrolled . '</td>';
            echo '<td>' . $in_progress . '</td>';
            echo '<td>' . $completed . '</td>';
            echo '<td>-</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
    }
    
    /**
     * 4. Peer Learning Activity Widget
     */
    public function render_peer_learning_widget() {
        echo '<div class="lccp-widget-content peer-learning">';
        
        // Get recent activity from other students
        global $wpdb;
        
        $recent_activities = $wpdb->get_results("
            SELECT 
                u.display_name,
                a.activity_type,
                a.post_id,
                a.activity_completed,
                a.activity_updated
            FROM {$wpdb->prefix}learndash_user_activity a
            JOIN {$wpdb->users} u ON a.user_id = u.ID
            WHERE a.user_id != {$this->current_user_id}
            AND a.activity_updated > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY a.activity_updated DESC
            LIMIT 10
        ");
        
        if (empty($recent_activities)) {
            echo '<p>No recent peer activity.</p>';
            return;
        }
        
        echo '<h4>Recent Peer Activity</h4>';
        echo '<ul class="peer-activity-list">';
        
        foreach ($recent_activities as $activity) {
            $post_title = get_the_title($activity->post_id);
            $time_ago = human_time_diff(strtotime($activity->activity_updated), current_time('timestamp')) . ' ago';
            
            echo '<li>';
            echo '<strong>' . esc_html($activity->display_name) . '</strong> ';
            
            if ($activity->activity_completed) {
                echo 'completed ';
            } else {
                echo 'started ';
            }
            
            echo esc_html($post_title);
            echo ' <small>(' . $time_ago . ')</small>';
            echo '</li>';
        }
        
        echo '</ul>';
        echo '</div>';
    }
    
    /**
     * 5. Certificate & Badge Display Widget
     */
    public function render_certificates_widget() {
        echo '<div class="lccp-widget-content certificates-badges">';
        
        if ($this->user_role_level >= 75) {
            $this->render_certificates_admin_view();
        } else {
            $this->render_certificates_student_view();
        }
        
        echo '</div>';
    }
    
    private function render_certificates_student_view() {
        $user_id = $this->current_user_id;
        
        // Get user certificates
        $certificates = learndash_get_user_certificates($user_id);
        
        echo '<div class="certificates-section">';
        
        if (!empty($certificates)) {
            echo '<h4>Your Certificates</h4>';
            echo '<ul class="certificate-list">';
            
            foreach ($certificates as $certificate) {
                $cert_link = $certificate['certificate_link'];
                $course_title = get_the_title($certificate['course_id']);
                
                echo '<li>';
                echo '<a href="' . esc_url($cert_link) . '" target="_blank">';
                echo '📜 ' . esc_html($course_title);
                echo '</a>';
                echo '</li>';
            }
            
            echo '</ul>';
        } else {
            echo '<p>No certificates earned yet. Complete courses to earn certificates!</p>';
        }
        
        // Show achievement badges if using BadgeOS or similar
        echo '<h4>Achievements</h4>';
        echo '<div class="achievement-badges">';
        
        // Placeholder for badges
        $achievements = array(
            'First Quiz Passed' => '🎯',
            '10 Hours Logged' => '⏱️',
            'Week Streak' => '🔥'
        );
        
        foreach ($achievements as $name => $icon) {
            echo '<div class="badge">';
            echo '<span class="badge-icon">' . $icon . '</span>';
            echo '<span class="badge-name">' . $name . '</span>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    private function render_certificates_admin_view() {
        global $wpdb;
        
        // Get certificate statistics
        $total_certificates = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->usermeta} 
            WHERE meta_key LIKE 'learndash_certificate_%'
        ");
        
        echo '<div class="admin-certificates">';
        echo '<h4>Certificate Statistics</h4>';
        
        echo '<div class="stat-cards">';
        echo '<div class="stat-card">';
        echo '<span class="stat-value">' . $total_certificates . '</span>';
        echo '<span class="stat-label">Total Certificates Issued</span>';
        echo '</div>';
        echo '</div>';
        
        // Recent certificates
        $recent_certs = $wpdb->get_results("
            SELECT 
                u.display_name,
                m.meta_key,
                m.meta_value,
                m.umeta_id
            FROM {$wpdb->usermeta} m
            JOIN {$wpdb->users} u ON m.user_id = u.ID
            WHERE m.meta_key LIKE 'learndash_certificate_%'
            ORDER BY m.umeta_id DESC
            LIMIT 5
        ");
        
        if (!empty($recent_certs)) {
            echo '<h4>Recently Issued</h4>';
            echo '<ul class="recent-certificates">';
            
            foreach ($recent_certs as $cert) {
                echo '<li>';
                echo esc_html($cert->display_name) . ' earned a certificate';
                echo '</li>';
            }
            
            echo '</ul>';
        }
        
        echo '</div>';
    }
    
    /**
     * 6. Learning Streak Widget
     */
    public function render_learning_streak_widget() {
        echo '<div class="lccp-widget-content learning-streak">';
        
        $user_id = $this->current_user_id;
        
        // Calculate learning streak
        global $wpdb;
        
        $activity_dates = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT DATE(activity_updated) as activity_date
            FROM {$wpdb->prefix}learndash_user_activity
            WHERE user_id = %d
            ORDER BY activity_date DESC
            LIMIT 30
        ", $user_id));
        
        $current_streak = 0;
        $longest_streak = 0;
        $temp_streak = 0;
        
        if (!empty($activity_dates)) {
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $today = date('Y-m-d');
            
            // Check if user was active today or yesterday
            if ($activity_dates[0] == $today || $activity_dates[0] == $yesterday) {
                $current_streak = 1;
                
                for ($i = 1; $i < count($activity_dates); $i++) {
                    $prev_date = $activity_dates[$i - 1];
                    $curr_date = $activity_dates[$i];
                    
                    $diff = (strtotime($prev_date) - strtotime($curr_date)) / 86400;
                    
                    if ($diff == 1) {
                        $current_streak++;
                    } else {
                        break;
                    }
                }
            }
        }
        
        echo '<div class="streak-display">';
        echo '<div class="current-streak">';
        echo '<span class="streak-number">' . $current_streak . '</span>';
        echo '<span class="streak-label">Day Streak</span>';
        
        if ($current_streak > 0) {
            echo '<span class="fire-emoji">🔥</span>';
        }
        
        echo '</div>';
        
        // Motivational message
        $messages = array(
            0 => "Start your learning journey today!",
            1 => "Great start! Keep it going!",
            3 => "3 days strong! You're building a habit!",
            7 => "One week streak! Amazing consistency!",
            14 => "Two weeks! You're unstoppable!",
            30 => "30 days! You're a learning champion!"
        );
        
        $message = "Keep up the great work!";
        foreach ($messages as $days => $msg) {
            if ($current_streak >= $days) {
                $message = $msg;
            }
        }
        
        echo '<p class="motivational-message">' . $message . '</p>';
        
        // Show calendar view
        echo '<div class="streak-calendar">';
        echo '<h4>Activity This Month</h4>';
        
        $current_month = date('Y-m');
        $days_in_month = date('t');
        
        echo '<div class="calendar-grid">';
        
        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = $current_month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
            $is_active = in_array($date, $activity_dates);
            $is_today = ($date == date('Y-m-d'));
            
            $class = '';
            if ($is_active) $class .= ' active';
            if ($is_today) $class .= ' today';
            
            echo '<div class="calendar-day' . $class . '">' . $day . '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * 7. Topic Focus Time Widget
     */
    public function render_topic_focus_widget() {
        echo '<div class="lccp-widget-content topic-focus">';
        
        if ($this->user_role_level >= 75) {
            $this->render_topic_focus_admin_view();
        } else {
            $this->render_topic_focus_student_view();
        }
        
        echo '</div>';
    }
    
    private function render_topic_focus_student_view() {
        global $wpdb;
        $user_id = $this->current_user_id;
        
        // Get time spent on different topics/lessons
        $topic_times = $wpdb->get_results($wpdb->prepare("
            SELECT 
                post_id,
                SUM(TIMESTAMPDIFF(MINUTE, activity_started, activity_updated)) as minutes_spent
            FROM {$wpdb->prefix}learndash_user_activity
            WHERE user_id = %d
            AND activity_type IN ('lesson', 'topic')
            AND activity_started IS NOT NULL
            GROUP BY post_id
            ORDER BY minutes_spent DESC
            LIMIT 10
        ", $user_id));
        
        if (empty($topic_times)) {
            echo '<p>No learning time tracked yet.</p>';
            return;
        }
        
        echo '<h4>Your Focus Areas</h4>';
        echo '<div class="focus-time-chart">';
        
        $total_minutes = array_sum(array_column($topic_times, 'minutes_spent'));
        
        foreach ($topic_times as $topic) {
            $title = get_the_title($topic->post_id);
            $hours = floor($topic->minutes_spent / 60);
            $minutes = $topic->minutes_spent % 60;
            $percentage = ($topic->minutes_spent / $total_minutes) * 100;
            
            echo '<div class="focus-item">';
            echo '<div class="focus-title">' . esc_html($title) . '</div>';
            echo '<div class="focus-bar">';
            echo '<div class="focus-fill" style="width: ' . $percentage . '%;"></div>';
            echo '</div>';
            echo '<div class="focus-time">';
            
            if ($hours > 0) {
                echo $hours . 'h ' . $minutes . 'm';
            } else {
                echo $minutes . ' minutes';
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // Total study time
        $total_hours = floor($total_minutes / 60);
        $total_mins = $total_minutes % 60;
        
        echo '<div class="total-study-time">';
        echo '<strong>Total Study Time:</strong> ';
        echo $total_hours . ' hours ' . $total_mins . ' minutes';
        echo '</div>';
        
        echo '</div>';
    }
    
    private function render_topic_focus_admin_view() {
        global $wpdb;
        
        // Get most studied topics across all users
        $popular_topics = $wpdb->get_results("
            SELECT 
                post_id,
                COUNT(DISTINCT user_id) as unique_users,
                AVG(TIMESTAMPDIFF(MINUTE, activity_started, activity_updated)) as avg_minutes
            FROM {$wpdb->prefix}learndash_user_activity
            WHERE activity_type IN ('lesson', 'topic')
            AND activity_started IS NOT NULL
            GROUP BY post_id
            ORDER BY unique_users DESC
            LIMIT 10
        ");
        
        echo '<h4>Most Engaged Content</h4>';
        echo '<table class="engagement-table">';
        echo '<thead><tr>';
        echo '<th>Content</th>';
        echo '<th>Students</th>';
        echo '<th>Avg Time</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($popular_topics as $topic) {
            $title = get_the_title($topic->post_id);
            $avg_time = round($topic->avg_minutes);
            
            echo '<tr>';
            echo '<td>' . esc_html($title) . '</td>';
            echo '<td>' . $topic->unique_users . '</td>';
            echo '<td>' . $avg_time . ' min</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
    }
    
    /**
     * 8. Mentor Feedback Widget
     */
    public function render_mentor_feedback_widget() {
        echo '<div class="lccp-widget-content mentor-feedback">';
        
        if ($this->user_role_level >= 75) {
            $this->render_feedback_mentor_view();
        } else {
            $this->render_feedback_student_view();
        }
        
        echo '</div>';
    }
    
    private function render_feedback_student_view() {
        $user_id = $this->current_user_id;
        
        // Get feedback comments on assignments
        $assignments = get_posts(array(
            'post_type' => 'sfwd-assignment',
            'author' => $user_id,
            'posts_per_page' => 5,
            'post_status' => 'publish'
        ));
        
        echo '<h4>Recent Feedback</h4>';
        
        if (empty($assignments)) {
            echo '<p>No feedback yet. Submit assignments to receive mentor feedback.</p>';
            return;
        }
        
        echo '<div class="feedback-list">';
        
        foreach ($assignments as $assignment) {
            $comments = get_comments(array(
                'post_id' => $assignment->ID,
                'status' => 'approve'
            ));
            
            if (!empty($comments)) {
                echo '<div class="feedback-item">';
                echo '<h5>' . esc_html($assignment->post_title) . '</h5>';
                
                foreach ($comments as $comment) {
                    echo '<div class="feedback-comment">';
                    echo '<strong>' . esc_html($comment->comment_author) . ':</strong> ';
                    echo wp_kses_post($comment->comment_content);
                    echo '<br><small>' . human_time_diff(strtotime($comment->comment_date)) . ' ago</small>';
                    echo '</div>';
                }
                
                echo '</div>';
            }
        }
        
        echo '</div>';
        
        // Action items from mentor
        $action_items = get_user_meta($user_id, 'lccp_mentor_action_items', true);
        
        if (!empty($action_items)) {
            echo '<h4>Action Items from Mentor</h4>';
            echo '<ul class="action-items">';
            
            foreach ($action_items as $item) {
                $completed = isset($item['completed']) ? $item['completed'] : false;
                $class = $completed ? 'completed' : '';
                
                echo '<li class="' . $class . '">';
                echo esc_html($item['task']);
                
                if ($completed) {
                    echo ' ✓';
                }
                
                echo '</li>';
            }
            
            echo '</ul>';
        }
        
        echo '</div>';
    }
    
    private function render_feedback_mentor_view() {
        // Get students needing feedback
        $pending_assignments = get_posts(array(
            'post_type' => 'sfwd-assignment',
            'posts_per_page' => 10,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'approval_status',
                    'value' => 'approved',
                    'compare' => '!='
                )
            )
        ));
        
        echo '<h4>Assignments Needing Feedback</h4>';
        
        if (empty($pending_assignments)) {
            echo '<p>No assignments pending review.</p>';
            return;
        }
        
        echo '<ul class="pending-feedback">';
        
        foreach ($pending_assignments as $assignment) {
            $author = get_user_by('id', $assignment->post_author);
            
            echo '<li>';
            echo '<strong>' . esc_html($author->display_name) . '</strong>: ';
            echo esc_html($assignment->post_title);
            echo ' <a href="' . get_edit_post_link($assignment->ID) . '">Review</a>';
            echo '</li>';
        }
        
        echo '</ul>';
        echo '</div>';
    }
    
    /**
     * 9. Resource Library Widget
     */
    public function render_resource_library_widget() {
        echo '<div class="lccp-widget-content resource-library">';
        
        // Get course materials
        $courses = learndash_user_get_enrolled_courses($this->current_user_id);
        
        echo '<h4>Quick Access Resources</h4>';
        
        // Recent materials
        $recent_materials = get_user_meta($this->current_user_id, 'lccp_recent_materials', true);
        
        if (!empty($recent_materials)) {
            echo '<div class="recent-materials">';
            echo '<h5>Recently Viewed</h5>';
            echo '<ul>';
            
            foreach (array_slice($recent_materials, 0, 5) as $material) {
                echo '<li>';
                echo '<a href="' . esc_url($material['url']) . '">';
                echo esc_html($material['title']);
                echo '</a>';
                echo '</li>';
            }
            
            echo '</ul>';
            echo '</div>';
        }
        
        // Bookmarked resources
        $bookmarks = get_user_meta($this->current_user_id, 'lccp_bookmarks', true);
        
        if (!empty($bookmarks)) {
            echo '<div class="bookmarked-resources">';
            echo '<h5>Bookmarked</h5>';
            echo '<ul>';
            
            foreach ($bookmarks as $bookmark) {
                echo '<li>';
                echo '<a href="' . get_permalink($bookmark) . '">';
                echo get_the_title($bookmark);
                echo '</a>';
                echo '</li>';
            }
            
            echo '</ul>';
            echo '</div>';
        }
        
        // Course materials by category
        echo '<div class="resource-categories">';
        echo '<h5>Resources by Type</h5>';
        
        $resource_types = array(
            'worksheets' => '📝 Worksheets',
            'videos' => '🎥 Videos',
            'audios' => '🎧 Audio Lessons',
            'guides' => '📚 Study Guides'
        );
        
        foreach ($resource_types as $type => $label) {
            echo '<a href="#" class="resource-type-link" data-type="' . $type . '">';
            echo $label;
            echo '</a> ';
        }
        
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * 10. Live Session Reminder Widget
     */
    public function render_live_sessions_widget() {
        echo '<div class="lccp-widget-content live-sessions">';
        
        // Get upcoming sessions
        $sessions = $this->get_upcoming_sessions();
        
        echo '<h4>Upcoming Live Sessions</h4>';
        
        if (empty($sessions)) {
            echo '<p>No upcoming sessions scheduled.</p>';
        } else {
            echo '<ul class="session-list">';
            
            foreach ($sessions as $session) {
                $time_until = human_time_diff(current_time('timestamp'), strtotime($session['date']));
                
                echo '<li class="session-item">';
                echo '<div class="session-title">' . esc_html($session['title']) . '</div>';
                echo '<div class="session-time">' . date('M j, g:i A', strtotime($session['date'])) . '</div>';
                echo '<div class="session-countdown">In ' . $time_until . '</div>';
                
                if (!empty($session['zoom_link'])) {
                    echo '<a href="' . esc_url($session['zoom_link']) . '" class="join-button" target="_blank">Join Session</a>';
                }
                
                echo '</li>';
            }
            
            echo '</ul>';
        }
        
        // Recent recordings
        echo '<h4>Recent Recordings</h4>';
        
        $recordings = $this->get_recent_recordings();
        
        if (empty($recordings)) {
            echo '<p>No recordings available.</p>';
        } else {
            echo '<ul class="recording-list">';
            
            foreach ($recordings as $recording) {
                echo '<li>';
                echo '<a href="' . esc_url($recording['url']) . '">';
                echo esc_html($recording['title']);
                echo '</a>';
                echo ' <small>(' . date('M j', strtotime($recording['date'])) . ')</small>';
                echo '</li>';
            }
            
            echo '</ul>';
        }
        
        echo '</div>';
    }
    
    /**
     * Helper function to get assigned students based on role
     */
    private function get_assigned_students() {
        $students = array();
        
        if ($this->user_role_level >= 75) {
            // Mentor - get all students
            $students = get_users(array(
                'role' => 'subscriber'
            ));
        } elseif ($this->user_role_level >= 50) {
            // Big Bird - get PCs' students
            $pcs = get_users(array(
                'meta_key' => 'big_bird_id',
                'meta_value' => $this->current_user_id,
                'role' => 'lccp_pc'
            ));
            
            foreach ($pcs as $pc) {
                $pc_students = get_users(array(
                    'meta_key' => 'pc_id',
                    'meta_value' => $pc->ID,
                    'role' => 'subscriber'
                ));
                $students = array_merge($students, $pc_students);
            }
        } elseif ($this->user_role_level >= 25) {
            // PC - get directly assigned students
            $students = get_users(array(
                'meta_key' => 'pc_id',
                'meta_value' => $this->current_user_id,
                'role' => 'subscriber'
            ));
        }
        
        return $students;
    }
    
    /**
     * Get upcoming sessions (integrated with Events Calendar)
     */
    private function get_upcoming_sessions() {
        $sessions = array();
        
        // Check if Events Calendar is active
        if (class_exists('Tribe__Events__Main')) {
            // Get upcoming events from Events Calendar
            $events = tribe_get_events(array(
                'posts_per_page' => 5,
                'start_date' => 'now',
                'orderby' => 'event_date',
                'order' => 'ASC',
                'meta_query' => array(
                    array(
                        'key' => '_EventStartDate',
                        'value' => current_time('Y-m-d H:i:s'),
                        'compare' => '>='
                    )
                )
            ));
            
            foreach ($events as $event) {
                $start_date = tribe_get_start_date($event->ID, false, 'Y-m-d H:i:s');
                $zoom_link = get_post_meta($event->ID, '_EventZoomLink', true);
                
                $sessions[] = array(
                    'title' => $event->post_title,
                    'date' => $start_date,
                    'zoom_link' => $zoom_link ?: '#',
                    'event_id' => $event->ID
                );
            }
        } else {
            // Fallback: Get events from custom post type if Events Calendar not available
            $custom_events = get_posts(array(
                'post_type' => 'lccp_event',
                'posts_per_page' => 5,
                'meta_query' => array(
                    array(
                        'key' => 'event_date',
                        'value' => current_time('Y-m-d'),
                        'compare' => '>='
                    )
                ),
                'orderby' => 'meta_value',
                'order' => 'ASC'
            ));
            
            foreach ($custom_events as $event) {
                $event_date = get_post_meta($event->ID, 'event_date', true);
                $event_time = get_post_meta($event->ID, 'event_time', true);
                $zoom_link = get_post_meta($event->ID, 'zoom_link', true);
                
                $sessions[] = array(
                    'title' => $event->post_title,
                    'date' => $event_date . ' ' . $event_time,
                    'zoom_link' => $zoom_link ?: '#',
                    'event_id' => $event->ID
                );
            }
        }
        
        return $sessions;
    }
    
    /**
     * Get recent session recordings (integrated with media library)
     */
    private function get_recent_recordings() {
        $recordings = array();
        
        // Get recent recordings from media library
        $recent_media = get_posts(array(
            'post_type' => 'attachment',
            'post_mime_type' => 'video/mp4,video/webm,video/ogg,audio/mpeg,audio/wav,audio/ogg',
            'posts_per_page' => 5,
            'post_status' => 'inherit',
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => 'lccp_recording_type',
                    'value' => 'session_recording',
                    'compare' => '='
                )
            )
        ));
        
        foreach ($recent_media as $media) {
            $recording_date = get_post_meta($media->ID, 'lccp_recording_date', true);
            $session_title = get_post_meta($media->ID, 'lccp_session_title', true);
            
            $recordings[] = array(
                'title' => $session_title ?: $media->post_title,
                'date' => $recording_date ?: $media->post_date,
                'url' => wp_get_attachment_url($media->ID),
                'media_id' => $media->ID
            );
        }
        
        // If no recordings found, check for LearnDash course materials
        if (empty($recordings)) {
            $course_materials = get_posts(array(
                'post_type' => 'sfwd-courses',
                'posts_per_page' => 3,
                'meta_query' => array(
                    array(
                        'key' => 'course_materials',
                        'value' => '',
                        'compare' => '!='
                    )
                )
            ));
            
            foreach ($course_materials as $course) {
                $materials = get_post_meta($course->ID, 'course_materials', true);
                if (!empty($materials)) {
                    $recordings[] = array(
                        'title' => $course->post_title . ' - Course Materials',
                        'date' => $course->post_date,
                        'url' => get_permalink($course->ID),
                        'course_id' => $course->ID
                    );
                }
            }
        }
        
        return $recordings;
    }
    
    /**
     * Enqueue widget assets
     */
    public function enqueue_widget_assets() {
        wp_enqueue_style(
            'lccp-advanced-widgets',
            LCCP_SYSTEMS_PLUGIN_URL . 'assets/css/advanced-widgets.css',
            array(),
            LCCP_SYSTEMS_VERSION
        );
        
        wp_enqueue_script(
            'lccp-advanced-widgets',
            LCCP_SYSTEMS_PLUGIN_URL . 'assets/js/advanced-widgets.js',
            array('jquery'),
            LCCP_SYSTEMS_VERSION,
            true
        );
        
        wp_localize_script('lccp-advanced-widgets', 'lccp_widgets', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lccp_widgets_nonce')
        ));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ('index.php' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'lccp-admin-widgets',
            LCCP_SYSTEMS_PLUGIN_URL . 'assets/css/admin-widgets.css',
            array(),
            LCCP_SYSTEMS_VERSION
        );
    }
    
    /**
     * Register sidebar widgets
     */
    public function register_widgets() {
        // Include widget class files
        require_once LCCP_SYSTEMS_PLUGIN_DIR . 'includes/widgets/class-course-progress-widget.php';
        require_once LCCP_SYSTEMS_PLUGIN_DIR . 'includes/widgets/class-learning-streak-widget.php';
        require_once LCCP_SYSTEMS_PLUGIN_DIR . 'includes/widgets/class-upcoming-sessions-widget.php';
        require_once LCCP_SYSTEMS_PLUGIN_DIR . 'includes/widgets/class-resource-library-widget.php';
        
        // Register Course Progress Widget
        register_widget('LCCP_Course_Progress_Widget');
        
        // Register Learning Streak Widget
        register_widget('LCCP_Learning_Streak_Widget');
        
        // Register Upcoming Sessions Widget
        register_widget('LCCP_Upcoming_Sessions_Widget');
        
        // Register Resource Library Widget
        register_widget('LCCP_Resource_Library_Widget');
    }
    
    /**
     * AJAX handler to refresh widget content
     */
    public function ajax_refresh_widget() {
        check_ajax_referer('lccp_widgets_nonce', 'nonce');
        
        $widget_type = isset($_POST['widget_type']) ? sanitize_text_field($_POST['widget_type']) : '';
        
        ob_start();
        
        switch($widget_type) {
            case 'quiz_performance':
                $this->render_quiz_performance_widget();
                break;
            case 'assignment_tracker':
                $this->render_assignment_tracker_widget();
                break;
            case 'course_timeline':
                $this->render_course_timeline_widget();
                break;
            case 'learning_streak':
                $this->render_learning_streak_widget();
                break;
            case 'live_sessions':
                $this->render_live_sessions_widget();
                break;
            default:
                echo 'Invalid widget type';
        }
        
        $content = ob_get_clean();
        
        wp_send_json_success(array('content' => $content));
    }
    
    /**
     * AJAX handler to get quiz details
     */
    public function ajax_get_quiz_details() {
        check_ajax_referer('lccp_widgets_nonce', 'nonce');
        
        $quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : get_current_user_id();
        
        if (!$quiz_id) {
            wp_send_json_error('Invalid quiz ID');
        }
        
        // Get quiz statistics
        $quiz_stats = learndash_get_user_quiz_statistics($user_id, $quiz_id);
        $quiz_title = get_the_title($quiz_id);
        
        $details = array(
            'quiz_title' => $quiz_title,
            'attempts' => count($quiz_stats),
            'best_score' => 0,
            'average_score' => 0,
            'last_attempt' => 'Never'
        );
        
        if (!empty($quiz_stats)) {
            $scores = array();
            foreach ($quiz_stats as $stat) {
                $score = isset($stat['percentage']) ? $stat['percentage'] : 0;
                $scores[] = $score;
            }
            
            $details['best_score'] = max($scores);
            $details['average_score'] = round(array_sum($scores) / count($scores), 1);
            
            $last_stat = end($quiz_stats);
            $details['last_attempt'] = date('M j, Y', $last_stat['time']);
        }
        
        wp_send_json_success($details);
    }
    
    /**
     * AJAX handler to get assignment details
     */
    public function ajax_get_assignment_details() {
        check_ajax_referer('lccp_widgets_nonce', 'nonce');
        
        $assignment_id = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : get_current_user_id();
        
        if (!$assignment_id) {
            wp_send_json_error('Invalid assignment ID');
        }
        
        $assignment = get_post($assignment_id);
        
        if (!$assignment || $assignment->post_type !== 'sfwd-assignment') {
            wp_send_json_error('Assignment not found');
        }
        
        $details = array(
            'title' => $assignment->post_title,
            'content' => wp_trim_words($assignment->post_content, 50),
            'status' => get_post_meta($assignment_id, 'approval_status', true),
            'submitted_date' => get_the_date('M j, Y', $assignment_id),
            'points' => get_post_meta($assignment_id, 'points', true),
            'feedback' => get_post_meta($assignment_id, 'instructor_feedback', true)
        );
        
        // Get associated lesson/topic
        $lesson_id = get_post_meta($assignment_id, 'lesson_id', true);
        if ($lesson_id) {
            $details['lesson'] = get_the_title($lesson_id);
        }
        
        wp_send_json_success($details);
    }
}

// Initialize the advanced widgets
new LCCP_Advanced_LearnDash_Widgets();