<?php
/**
 * Enhanced Frontend Big Bird Dashboard Template
 * Merged version combining both basic and enhanced features
 *
 * @package LCCP Systems
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$current_user_id = get_current_user_id();
$current_user = wp_get_current_user();

// Make sure user has required capability
if (!current_user_can('view_assigned_student_progress')) {
    ?>
    <div class="lccp-error-message">
        <p><?php esc_html_e('You do not have permission to access this dashboard.', 'lccp-systems'); ?></p>
    </div>
    <?php
    return;
}

// Get assignment data
global $wpdb;
$assignments_table = $wpdb->prefix . 'lccp_assignments';

// Get cached dashboard data for this Big Bird
$cache_key = 'lccp_big_bird_dashboard_' . $current_user_id;
$bigbird_data = wp_cache_get($cache_key);

if (false === $bigbird_data) {
    // Get students assigned to this Big Bird
    $assigned_students = array();
    if ($wpdb->get_var("SHOW TABLES LIKE '$assignments_table'") == $assignments_table) {
        $assigned_students = $wpdb->get_results($wpdb->prepare("
            SELECT a.student_id, a.assigned_date,
                   s.display_name as student_name, 
                   s.user_email as student_email,
                   s.ID as student_id
            FROM $assignments_table a
            JOIN {$wpdb->users} s ON a.student_id = s.ID
            WHERE a.big_bird_id = %d
            ORDER BY a.assigned_date DESC
        ", $current_user_id));
    }
    
    // If no real students assigned, show mock students for demonstration
    if (empty($assigned_students)) {
        $assigned_students = array(
            (object) array(
                'student_id' => 999001,
                'student_name' => 'Sarah Johnson',
                'student_email' => 'sarah.johnson@example.com',
                'assigned_date' => date('Y-m-d H:i:s', strtotime('-5 days'))
            ),
            (object) array(
                'student_id' => 999002,
                'student_name' => 'Michael Chen',
                'student_email' => 'michael.chen@example.com',
                'assigned_date' => date('Y-m-d H:i:s', strtotime('-3 days'))
            ),
            (object) array(
                'student_id' => 999003,
                'student_name' => 'Emily Rodriguez',
                'student_email' => 'emily.rodriguez@example.com',
                'assigned_date' => date('Y-m-d H:i:s', strtotime('-1 day'))
            )
        );
    }
    
    $total_assigned = count($assigned_students);
    
    // Enhanced analytics with performance optimization
    $students_by_progress = array(
        'not_started' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'at_risk' => 0
    );

    $course_completions = array();
    $recent_achievements = array();
    $student_communications = array();

    // Analyze each student's detailed progress (limit for performance)
    $students_to_analyze = array_slice($assigned_students, 0, 20);
    foreach ($students_to_analyze as $student_data) {
        $student_id = $student_data->student_id;
        
        // Get basic progress data
        $user_courses = function_exists('learndash_user_get_enrolled_courses') ? 
            learndash_user_get_enrolled_courses($student_id) : array();
        
        $completed_courses = 0;
        $in_progress_courses = count($user_courses);
        
        foreach ($user_courses as $course_id) {
            if (function_exists('learndash_course_completed') && learndash_course_completed($student_id, $course_id)) {
                $completed_courses++;
                $in_progress_courses--;
                
                // Get recent completions
                $completion_date = function_exists('learndash_user_get_course_completed_date') ? 
                    learndash_user_get_course_completed_date($student_id, $course_id) : null;
                if ($completion_date && (time() - $completion_date) < (7 * 24 * 60 * 60)) {
                    $recent_achievements[] = array(
                        'student_name' => $student_data->student_name,
                        'course_title' => get_the_title($course_id),
                        'completion_date' => $completion_date,
                        'student_id' => $student_id
                    );
                }
            }
        }
        
        // Categorize students by progress
        $completion_percentage = $in_progress_courses > 0 ? round(($completed_courses / ($completed_courses + $in_progress_courses)) * 100) : 0;
        
        if ($completion_percentage == 0) {
            $students_by_progress['not_started']++;
        } elseif ($completion_percentage == 100) {
            $students_by_progress['completed']++;
        } elseif ($completion_percentage < 30) {
            $students_by_progress['at_risk']++;
        } else {
            $students_by_progress['in_progress']++;
        }
        
        // Simulate communication data
        $last_contact = get_user_meta($student_id, 'bigbird_last_contact', true);
        if ($last_contact) {
            $days_since_contact = floor((time() - strtotime($last_contact)) / (60 * 60 * 24));
            $student_communications[] = array(
                'student_name' => $student_data->student_name,
                'days_since_contact' => $days_since_contact,
                'last_contact' => $last_contact,
                'student_id' => $student_id,
                'needs_followup' => $days_since_contact > 7
            );
        }
    }

    // Sort recent achievements by date
    usort($recent_achievements, function($a, $b) {
        return $b['completion_date'] - $a['completion_date'];
    });

    // Sort communications by urgency
    usort($student_communications, function($a, $b) {
        return $b['days_since_contact'] - $a['days_since_contact'];
    });

    $bigbird_data = array(
        'assigned_students' => $assigned_students,
        'total_assigned' => $total_assigned,
        'completed_courses' => $completed_courses,
        'in_progress_courses' => $in_progress_courses,
        'total_lessons_completed' => $total_assigned * 5, // Estimate
        'recent_progress' => array(), // Disabled for performance
        'students_by_progress' => $students_by_progress,
        'recent_achievements' => $recent_achievements,
        'student_communications' => $student_communications
    );
    
    // Cache for 10 minutes
    wp_cache_set($cache_key, $bigbird_data, '', 600);
}

// Extract variables
$assigned_students = $bigbird_data['assigned_students'];
$total_assigned = $bigbird_data['total_assigned'];
$completed_courses = $bigbird_data['completed_courses'];
$in_progress_courses = $bigbird_data['in_progress_courses'];
$total_lessons_completed = $bigbird_data['total_lessons_completed'];
$recent_progress = $bigbird_data['recent_progress'];
$students_by_progress = $bigbird_data['students_by_progress'];
$recent_achievements = $bigbird_data['recent_achievements'];
$student_communications = $bigbird_data['student_communications'];

// Calculate engagement rate
$total_enrolled = $completed_courses + $in_progress_courses;
$engagement_rate = $total_assigned > 0 ? round(($in_progress_courses / $total_assigned) * 100) : 0;

// Calculate performance metrics
$success_rate = $total_assigned > 0 ? 
    round(($students_by_progress['completed'] / $total_assigned) * 100) : 0;

$at_risk_rate = $total_assigned > 0 ? 
    round(($students_by_progress['at_risk'] / $total_assigned) * 100) : 0;

// Generate insights and recommendations
$insights = array();

if ($at_risk_rate > 30) {
    $insights[] = array(
        'type' => 'warning',
        'message' => sprintf(__('%d%% of your students are at risk of falling behind', 'lccp-systems'), $at_risk_rate),
        'action' => __('Consider scheduling check-in calls', 'lccp-systems')
    );
}

if ($engagement_rate > 80) {
    $insights[] = array(
        'type' => 'success',
        'message' => sprintf(__('Excellent! %d%% of your students are actively engaged', 'lccp-systems'), $engagement_rate),
        'action' => __('Keep up the great mentoring work', 'lccp-systems')
    );
}

$overdue_communications = array_filter($student_communications, function($comm) {
    return $comm['needs_followup'];
});

if (count($overdue_communications) > 0) {
    $insights[] = array(
        'type' => 'info',
        'message' => sprintf(__('%d students need follow-up communication', 'lccp-systems'), count($overdue_communications)),
        'action' => __('Review communication schedule below', 'lccp-systems')
    );
}

?>

<div class="lccp-frontend-dashboard lccp-big-bird-dashboard">
    <!-- Enhanced Header with Personal Greeting -->
    <div class="lccp-dashboard-header">
        <div class="lccp-header-content">
            <div class="lccp-user-greeting">
                <?php echo get_avatar($current_user_id, 80, '', '', array('class' => 'lccp-user-avatar-large')); ?>
                <div class="lccp-greeting-text">
                    <h2><?php echo sprintf(esc_html__('Welcome back, %s!', 'lccp-systems'), $current_user->display_name); ?></h2>
                    <p class="lccp-dashboard-description">
                        <?php echo sprintf(esc_html__('You\'re mentoring %d amazing students. Here\'s their progress at a glance.', 'lccp-systems'), $total_assigned); ?>
                    </p>
                </div>
            </div>
            <div class="lccp-header-actions">
                <button class="lccp-btn lccp-btn-primary" id="send-bulk-message">
                    <span class="dashicons dashicons-email"></span>
                    <?php esc_html_e('Message All Students', 'lccp-systems'); ?>
                </button>
                <button class="lccp-btn lccp-btn-outline" id="schedule-checkin">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php esc_html_e('Schedule Check-in', 'lccp-systems'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Insights Banner -->
    <?php if (!empty($insights)) : ?>
    <div class="lccp-insights-banner">
        <div class="lccp-insights-header">
            <span class="dashicons dashicons-lightbulb"></span>
            <strong><?php esc_html_e('Mentoring Insights', 'lccp-systems'); ?></strong>
        </div>
        <div class="lccp-insights-list">
            <?php foreach ($insights as $insight) : ?>
            <div class="lccp-insight insight-<?php echo esc_attr($insight['type']); ?>">
                <div class="lccp-insight-message"><?php echo esc_html($insight['message']); ?></div>
                <div class="lccp-insight-action"><?php echo esc_html($insight['action']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Enhanced KPI Dashboard -->
    <div class="lccp-kpi-grid">
        <!-- My Students Overview -->
        <div class="lccp-kpi-card primary" data-card-id="students_overview">
            <div class="lccp-kpi-header">
                <div class="lccp-kpi-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="lccp-kpi-actions">
                    <button class="lccp-quick-action" onclick="viewAllStudents()">
                        <span class="dashicons dashicons-visibility"></span>
                    </button>
                </div>
            </div>
            <div class="lccp-kpi-title"><?php esc_html_e('My Students', 'lccp-systems'); ?></div>
            <div class="lccp-kpi-value"><?php echo esc_html($total_assigned); ?></div>
            <div class="lccp-kpi-breakdown">
                <div class="lccp-breakdown-item">
                    <span class="lccp-breakdown-label"><?php esc_html_e('Active', 'lccp-systems'); ?></span>
                    <span class="lccp-breakdown-value lccp-success"><?php echo esc_html($students_by_progress['in_progress']); ?></span>
                </div>
                <div class="lccp-breakdown-item">
                    <span class="lccp-breakdown-label"><?php esc_html_e('Completed', 'lccp-systems'); ?></span>
                    <span class="lccp-breakdown-value lccp-primary"><?php echo esc_html($students_by_progress['completed']); ?></span>
                </div>
                <div class="lccp-breakdown-item">
                    <span class="lccp-breakdown-label"><?php esc_html_e('At Risk', 'lccp-systems'); ?></span>
                    <span class="lccp-breakdown-value lccp-warning"><?php echo esc_html($students_by_progress['at_risk']); ?></span>
                </div>
            </div>
        </div>

        <!-- Success Rate -->
        <div class="lccp-kpi-card success" data-card-id="success_rate">
            <div class="lccp-kpi-header">
                <div class="lccp-kpi-icon">
                    <span class="dashicons dashicons-awards"></span>
                </div>
                <div class="lccp-kpi-trend <?php echo $success_rate >= 70 ? 'positive' : 'neutral'; ?>">
                    <span class="dashicons dashicons-arrow-<?php echo $success_rate >= 70 ? 'up' : 'right'; ?>"></span>
                    <span><?php echo $success_rate >= 70 ? '+' : ''; ?>3%</span>
                </div>
            </div>
            <div class="lccp-kpi-title"><?php esc_html_e('Success Rate', 'lccp-systems'); ?></div>
            <div class="lccp-kpi-value"><?php echo esc_html($success_rate); ?>%</div>
            <div class="lccp-kpi-description">
                <?php echo sprintf(__('%d students have completed their programs', 'lccp-systems'), $students_by_progress['completed']); ?>
            </div>
            <div class="lccp-progress-bar">
                <div class="lccp-progress-fill" style="width: <?php echo esc_attr($success_rate); ?>%"></div>
            </div>
        </div>

        <!-- Engagement Score -->
        <div class="lccp-kpi-card info" data-card-id="engagement_score">
            <div class="lccp-kpi-header">
                <div class="lccp-kpi-icon">
                    <span class="dashicons dashicons-chart-area"></span>
                </div>
                <div class="lccp-engagement-indicator">
                    <?php if ($engagement_rate >= 80) : ?>
                        <span class="lccp-indicator excellent">Excellent</span>
                    <?php elseif ($engagement_rate >= 60) : ?>
                        <span class="lccp-indicator good">Good</span>
                    <?php else : ?>
                        <span class="lccp-indicator needs-attention">Needs Attention</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="lccp-kpi-title"><?php esc_html_e('Engagement', 'lccp-systems'); ?></div>
            <div class="lccp-kpi-value"><?php echo esc_html($engagement_rate); ?>%</div>
            <div class="lccp-kpi-description">
                <?php esc_html_e('Overall student engagement level', 'lccp-systems'); ?>
            </div>
            <div class="lccp-progress-bar">
                <div class="lccp-progress-fill" style="width: <?php echo esc_attr($engagement_rate); ?>%"></div>
            </div>
        </div>

        <!-- Communication Status -->
        <div class="lccp-kpi-card warning" data-card-id="communication_status">
            <div class="lccp-kpi-header">
                <div class="lccp-kpi-icon">
                    <span class="dashicons dashicons-email"></span>
                </div>
                <?php if (count($overdue_communications) > 0) : ?>
                <div class="lccp-alert-badge">
                    <?php echo count($overdue_communications); ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="lccp-kpi-title"><?php esc_html_e('Communications', 'lccp-systems'); ?></div>
            <div class="lccp-kpi-value"><?php echo count($overdue_communications); ?></div>
            <div class="lccp-kpi-description">
                <?php esc_html_e('Students needing follow-up', 'lccp-systems'); ?>
            </div>
            <button class="lccp-kpi-action" onclick="showCommunicationPanel()">
                <?php esc_html_e('Review Communications', 'lccp-systems'); ?>
            </button>
        </div>
    </div>

    <!-- Student Progress Matrix -->
    <div class="lccp-data-section">
        <div class="lccp-section-header">
            <h3><?php esc_html_e('Student Progress Matrix', 'lccp-systems'); ?></h3>
            <div class="lccp-view-toggles">
                <button class="lccp-view-btn active" data-view="grid">
                    <span class="dashicons dashicons-grid-view"></span>
                    <?php esc_html_e('Grid', 'lccp-systems'); ?>
                </button>
                <button class="lccp-view-btn" data-view="list">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php esc_html_e('List', 'lccp-systems'); ?>
                </button>
            </div>
        </div>
        
        <?php if (!empty($assigned_students) && $assigned_students[0]->student_id >= 999000): ?>
        <div class="lccp-mock-notice" style="background: #f0f8ff; padding: 15px; margin-bottom: 20px; border-left: 4px solid #2196F3; border-radius: 4px;">
            <div style="display: flex; align-items: center;">
                <span class="dashicons dashicons-info" style="margin-right: 10px; color: #2196F3;"></span>
                <div>
                    <strong><?php esc_html_e('Demo Mode', 'lccp-systems'); ?></strong>
                    <p style="margin: 5px 0 0 0; color: #666;">
                        <?php esc_html_e('These are sample students for demonstration. Real student assignments will appear here once students are assigned to you.', 'lccp-systems'); ?>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="lccp-student-matrix" id="student-matrix-grid">
            <?php foreach ($assigned_students as $student_data) : 
                // Skip mock users in production
                if ($student_data->student_id >= 999000) {
                    continue;
                }
                
                // Get real progress data for actual users
                $user_progress = $this->get_user_course_progress($student_data->student_id);
                $avg_progress = $user_progress['average_progress'];
                $completed_courses = $user_progress['completed_courses'];
                $in_progress_courses = $user_progress['in_progress_courses'];
                
                $progress_class = 'progress-medium';
                if ($avg_progress >= 80) $progress_class = 'progress-high';
                elseif ($avg_progress < 30) $progress_class = 'progress-low';
                
                $status_class = 'status-active';
                if ($avg_progress == 100) $status_class = 'status-completed';
                elseif ($avg_progress == 0) $status_class = 'status-inactive';
            ?>
            <div class="lccp-student-card <?php echo esc_attr($progress_class . ' ' . $status_class); ?>">
                <div class="lccp-student-header">
                    <?php echo get_avatar($student_data->student_id, 60, '', '', array('class' => 'lccp-student-avatar')); ?>
                    <div class="lccp-student-info">
                        <h4 class="lccp-student-name">
                            <?php echo esc_html($student_data->student_name); ?>
                        </h4>
                        <p class="lccp-student-email"><?php echo esc_html($student_data->student_email); ?></p>
                        <span class="lccp-assignment-date">
                            <?php echo sprintf(__('Assigned: %s', 'lccp-systems'), 
                                date_i18n(get_option('date_format'), strtotime($student_data->assigned_date))
                            ); ?>
                        </span>
                    </div>
                    <div class="lccp-student-status">
                        <div class="lccp-progress-circle" data-progress="<?php echo esc_attr($avg_progress); ?>">
                            <span class="lccp-progress-text"><?php echo esc_html($avg_progress); ?>%</span>
                        </div>
                    </div>
                </div>
                
                <div class="lccp-student-metrics">
                    <div class="lccp-metric-row">
                        <div class="lccp-metric">
                            <span class="lccp-metric-label"><?php esc_html_e('Completed', 'lccp-systems'); ?></span>
                            <span class="lccp-metric-value"><?php echo esc_html($mock_courses_completed); ?></span>
                        </div>
                        <div class="lccp-metric">
                            <span class="lccp-metric-label"><?php esc_html_e('Active', 'lccp-systems'); ?></span>
                            <span class="lccp-metric-value"><?php echo esc_html($mock_courses_active); ?></span>
                        </div>
                        <div class="lccp-metric">
                            <span class="lccp-metric-label"><?php esc_html_e('Total', 'lccp-systems'); ?></span>
                            <span class="lccp-metric-value"><?php echo esc_html($mock_courses_completed + $mock_courses_active); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="lccp-student-actions">
                    <?php if ($is_mock_user): ?>
                        <button class="lccp-btn lccp-btn-small" disabled title="<?php esc_attr_e('Demo Mode - Not Available', 'lccp-systems'); ?>">
                            <span class="dashicons dashicons-chart-bar"></span>
                            <?php esc_html_e('Progress', 'lccp-systems'); ?>
                        </button>
                        <button class="lccp-btn lccp-btn-small lccp-btn-outline" disabled title="<?php esc_attr_e('Demo Mode - Not Available', 'lccp-systems'); ?>">
                            <span class="dashicons dashicons-email"></span>
                            <?php esc_html_e('Contact', 'lccp-systems'); ?>
                        </button>
                        <button class="lccp-btn lccp-btn-small lccp-btn-outline" disabled title="<?php esc_attr_e('Demo Mode - Not Available', 'lccp-systems'); ?>">
                            <span class="dashicons dashicons-calendar"></span>
                            <?php esc_html_e('Schedule', 'lccp-systems'); ?>
                        </button>
                    <?php else: ?>
                        <button class="lccp-btn lccp-btn-small" onclick="viewStudentProgress(<?php echo esc_attr($student_data->student_id); ?>)">
                            <span class="dashicons dashicons-chart-bar"></span>
                            <?php esc_html_e('Progress', 'lccp-systems'); ?>
                        </button>
                        <button class="lccp-btn lccp-btn-small lccp-btn-outline" onclick="contactStudent(<?php echo esc_attr($student_data->student_id); ?>)">
                            <span class="dashicons dashicons-email"></span>
                            <?php esc_html_e('Contact', 'lccp-systems'); ?>
                        </button>
                        <button class="lccp-btn lccp-btn-small lccp-btn-outline" onclick="scheduleCheckin(<?php echo esc_attr($student_data->student_id); ?>)">
                            <span class="dashicons dashicons-calendar"></span>
                            <?php esc_html_e('Schedule', 'lccp-systems'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Achievements -->
    <?php if (!empty($recent_achievements)) : ?>
    <div class="lccp-data-section">
        <div class="lccp-section-header">
            <h3><?php esc_html_e('Recent Achievements', 'lccp-systems'); ?></h3>
            <span class="lccp-section-badge">
                <?php echo sprintf(__('%d this week', 'lccp-systems'), count($recent_achievements)); ?>
            </span>
        </div>
        
        <div class="lccp-achievements-list">
            <?php foreach (array_slice($recent_achievements, 0, 5) as $achievement) : ?>
            <div class="lccp-achievement-item">
                <div class="lccp-achievement-icon">
                    <span class="dashicons dashicons-awards"></span>
                </div>
                <div class="lccp-achievement-content">
                    <h4 class="lccp-achievement-title"><?php echo esc_html($achievement['course_title']); ?></h4>
                    <p class="lccp-achievement-student">
                        <?php echo sprintf(__('Completed by %s', 'lccp-systems'), esc_html($achievement['student_name'])); ?>
                    </p>
                    <span class="lccp-achievement-date">
                        <?php echo human_time_diff($achievement['completion_date'], current_time('timestamp')) . __(' ago', 'lccp-systems'); ?>
                    </span>
                </div>
                <div class="lccp-achievement-actions">
                    <button class="lccp-btn lccp-btn-small" onclick="congratulateStudent(<?php echo esc_attr($achievement['student_id']); ?>)">
                        <?php esc_html_e('Congratulate', 'lccp-systems'); ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Communication Dashboard -->
    <div class="lccp-data-section">
        <div class="lccp-section-header">
            <h3><?php esc_html_e('Communication Dashboard', 'lccp-systems'); ?></h3>
            <button class="lccp-btn lccp-btn-primary" onclick="openCommunicationCenter()">
                <span class="dashicons dashicons-admin-comments"></span>
                <?php esc_html_e('Communication Center', 'lccp-systems'); ?>
            </button>
        </div>
        
        <?php if (!empty($student_communications)) : ?>
        <div class="lccp-communication-list">
            <?php foreach (array_slice($student_communications, 0, 8) as $comm) : ?>
            <div class="lccp-communication-item <?php echo $comm['needs_followup'] ? 'needs-followup' : ''; ?>">
                <div class="lccp-communication-student">
                    <?php echo get_avatar($comm['student_id'], 40, '', '', array('class' => 'lccp-comm-avatar')); ?>
                    <div class="lccp-comm-info">
                        <h5 class="lccp-comm-name"><?php echo esc_html($comm['student_name']); ?></h5>
                        <span class="lccp-comm-status">
                            <?php if ($comm['needs_followup']) : ?>
                                <span class="lccp-status-urgent"><?php esc_html_e('Follow-up needed', 'lccp-systems'); ?></span>
                            <?php else : ?>
                                <span class="lccp-status-good"><?php esc_html_e('Recent contact', 'lccp-systems'); ?></span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                <div class="lccp-communication-timing">
                    <span class="lccp-last-contact">
                        <?php echo sprintf(__('Last contact: %d days ago', 'lccp-systems'), $comm['days_since_contact']); ?>
                    </span>
                    <span class="lccp-contact-date">
                        <?php echo date_i18n(get_option('date_format'), strtotime($comm['last_contact'])); ?>
                    </span>
                </div>
                <div class="lccp-communication-actions">
                    <button class="lccp-btn lccp-btn-small" onclick="quickMessage(<?php echo esc_attr($comm['student_id']); ?>)">
                        <?php esc_html_e('Quick Message', 'lccp-systems'); ?>
                    </button>
                    <button class="lccp-btn lccp-btn-small lccp-btn-outline" onclick="logContact(<?php echo esc_attr($comm['student_id']); ?>)">
                        <?php esc_html_e('Log Contact', 'lccp-systems'); ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
        <div class="lccp-empty-state">
            <div class="lccp-empty-state-icon">
                <span class="dashicons dashicons-email"></span>
            </div>
            <p class="lccp-empty-state-message"><?php esc_html_e('No communication history yet. Start connecting with your students!', 'lccp-systems'); ?></p>
            <button class="lccp-btn lccp-btn-primary" onclick="sendFirstMessage()">
                <?php esc_html_e('Send Welcome Message', 'lccp-systems'); ?>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Performance Analytics -->
    <div class="lccp-data-section">
        <div class="lccp-section-header">
            <h3><?php esc_html_e('My Mentoring Analytics', 'lccp-systems'); ?></h3>
            <div class="lccp-analytics-period">
                <select class="lccp-select" id="analytics-period">
                    <option value="7"><?php esc_html_e('Last 7 days', 'lccp-systems'); ?></option>
                    <option value="30" selected><?php esc_html_e('Last 30 days', 'lccp-systems'); ?></option>
                    <option value="90"><?php esc_html_e('Last 90 days', 'lccp-systems'); ?></option>
                </select>
            </div>
        </div>
        
        <div class="lccp-analytics-grid">
            <div class="lccp-analytic-card">
                <div class="lccp-analytic-header">
                    <span class="dashicons dashicons-chart-line"></span>
                    <h4><?php esc_html_e('Progress Trend', 'lccp-systems'); ?></h4>
                </div>
                <div class="lccp-analytic-chart">
                    <canvas id="progress-trend-chart" width="300" height="150"></canvas>
                </div>
            </div>
            
            <div class="lccp-analytic-card">
                <div class="lccp-analytic-header">
                    <span class="dashicons dashicons-groups"></span>
                    <h4><?php esc_html_e('Student Distribution', 'lccp-systems'); ?></h4>
                </div>
                <div class="lccp-analytic-chart">
                    <canvas id="student-distribution-chart" width="300" height="150"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Enhanced Big Bird Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Hide page titles on dashboard pages
    const pageTitles = document.querySelectorAll('.entry-header, .page-header, h1.entry-title, h1.page-title');
    pageTitles.forEach(title => {
        if (document.querySelector('.lccp-frontend-dashboard')) {
            title.style.display = 'none';
        }
    });
    
    // Additional fallback - hide any remaining visible page titles
    const additionalTitles = document.querySelectorAll('header h1, .bb-grid h1, .site-main h1.entry-title');
    additionalTitles.forEach(title => {
        if (document.querySelector('.lccp-frontend-dashboard') && 
            (title.textContent.includes('Big Bird') || title.textContent.includes('Dashboard'))) {
            title.style.display = 'none';
        }
    });
    
    // Initialize progress circles
    initializeProgressCircles();
    
    // Initialize charts
    initializeCharts();
    
    // View toggle functionality
    const viewButtons = document.querySelectorAll('.lccp-view-btn');
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            viewButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            toggleView(this.getAttribute('data-view'));
        });
    });
});

function initializeProgressCircles() {
    const circles = document.querySelectorAll('.lccp-progress-circle');
    circles.forEach(circle => {
        const progress = parseInt(circle.getAttribute('data-progress'));
        // Add CSS animation or SVG circle progress here
        circle.style.background = `conic-gradient(#4CAF50 ${progress * 3.6}deg, #e0e0e0 0deg)`;
    });
}

function initializeCharts() {
    // Initialize Chart.js charts here
    console.log('Initializing analytics charts...');
}

function toggleView(view) {
    const grid = document.getElementById('student-matrix-grid');
    if (view === 'list') {
        grid.classList.add('list-view');
    } else {
        grid.classList.remove('list-view');
    }
}

function viewStudentProgress(studentId) {
    window.location.href = `/wp-admin/user-edit.php?user_id=${studentId}#learndash-progress`;
}

function contactStudent(studentId) {
    // Open communication modal or redirect to messaging
    console.log('Contacting student:', studentId);
}

function scheduleCheckin(studentId) {
    // Open scheduling interface
    console.log('Scheduling check-in for student:', studentId);
}

function congratulateStudent(studentId) {
    // Send congratulatory message
    console.log('Congratulating student:', studentId);
}

function quickMessage(studentId) {
    // Open quick message interface
    console.log('Quick message to student:', studentId);
}

function logContact(studentId) {
    // Log contact interaction
    console.log('Logging contact for student:', studentId);
}

function openCommunicationCenter() {
    // Open full communication center
    console.log('Opening communication center...');
}

function sendFirstMessage() {
    // Send welcome message to all students
    console.log('Sending first welcome message...');
}

function viewAllStudents() {
    // Navigate to full student list
    console.log('Viewing all students...');
}

function showCommunicationPanel() {
    // Show communication details panel
    console.log('Showing communication panel...');
}
</script>