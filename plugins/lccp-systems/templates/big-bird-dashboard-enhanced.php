<?php
/**
 * Enhanced Frontend BigBird Dashboard Template
 *
 * @package Dasher
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
    <div class="dasher-error-message">
        <p><?php esc_html_e('You do not have permission to access this dashboard.', 'dasher'); ?></p>
    </div>
    <?php
    return;
}

// Get dasher plugin instance
global $dasher_plugin;
if (!$dasher_plugin) {
    $dasher_plugin = new Dasher_Plugin();
}

// Get assigned students data
$assigned_students_data = $dasher_plugin->get_assigned_students($current_user_id);

// Enhanced analytics
$total_assigned = count($assigned_students_data);
$students_by_progress = array(
    'not_started' => 0,
    'in_progress' => 0,
    'completed' => 0,
    'at_risk' => 0
);

$course_completions = array();
$recent_achievements = array();
$student_communications = array();
$weekly_activity = array();

// Analyze each student's detailed progress
foreach ($assigned_students_data as $student_data) {
    $student_progress = $student_data['progress'];
    $student_id = $student_data['ID'];
    
    // Categorize students by progress
    if ($student_progress['completion_percentage'] == 0) {
        $students_by_progress['not_started']++;
    } elseif ($student_progress['completion_percentage'] == 100) {
        $students_by_progress['completed']++;
    } elseif ($student_progress['completion_percentage'] < 30) {
        $students_by_progress['at_risk']++;
    } else {
        $students_by_progress['in_progress']++;
    }
    
    // Get recent course completions
    $user_courses = learndash_user_get_enrolled_courses($student_id);
    foreach ($user_courses as $course_id) {
        $course_completed = learndash_course_completed($student_id, $course_id);
        if ($course_completed) {
            $completion_date = learndash_user_get_course_completed_date($student_id, $course_id);
            if ($completion_date && (time() - $completion_date) < (7 * 24 * 60 * 60)) { // Last 7 days
                $recent_achievements[] = array(
                    'student_name' => $student_data['display_name'],
                    'course_title' => get_the_title($course_id),
                    'completion_date' => $completion_date,
                    'student_id' => $student_id
                );
            }
        }
    }
    
    // Simulate communication data (in real implementation, this would come from a communications table)
    $last_contact = get_user_meta($student_id, 'bigbird_last_contact', true);
    if ($last_contact) {
        $days_since_contact = floor((time() - strtotime($last_contact)) / (60 * 60 * 24));
        $student_communications[] = array(
            'student_name' => $student_data['display_name'],
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

// Calculate performance metrics
$engagement_rate = $total_assigned > 0 ? 
    round((($students_by_progress['in_progress'] + $students_by_progress['completed']) / $total_assigned) * 100) : 0;

$success_rate = $total_assigned > 0 ? 
    round(($students_by_progress['completed'] / $total_assigned) * 100) : 0;

$at_risk_rate = $total_assigned > 0 ? 
    round(($students_by_progress['at_risk'] / $total_assigned) * 100) : 0;

// Generate insights and recommendations
$insights = array();
$recommendations = array();

if ($at_risk_rate > 30) {
    $insights[] = array(
        'type' => 'warning',
        'message' => sprintf(__('%d%% of your students are at risk of falling behind', 'dasher'), $at_risk_rate),
        'action' => __('Consider scheduling check-in calls', 'dasher')
    );
}

if ($engagement_rate > 80) {
    $insights[] = array(
        'type' => 'success',
        'message' => sprintf(__('Excellent! %d%% of your students are actively engaged', 'dasher'), $engagement_rate),
        'action' => __('Keep up the great mentoring work', 'dasher')
    );
}

$overdue_communications = array_filter($student_communications, function($comm) {
    return $comm['needs_followup'];
});

if (count($overdue_communications) > 0) {
    $insights[] = array(
        'type' => 'info',
        'message' => sprintf(__('%d students need follow-up communication', 'dasher'), count($overdue_communications)),
        'action' => __('Review communication schedule below', 'dasher')
    );
}
?>

<div class="dasher-frontend-dashboard dasher-big-bird-dashboard-enhanced">
    <!-- Enhanced Header with Personal Greeting -->
    <div class="dasher-dashboard-header">
        <div class="dasher-header-content">
            <div class="dasher-user-greeting">
                <?php echo get_avatar($current_user_id, 80, '', '', array('class' => 'dasher-user-avatar-large')); ?>
                <div class="dasher-greeting-text">
                    <h2><?php echo sprintf(esc_html__('Welcome back, %s!', 'dasher'), $current_user->display_name); ?></h2>
                    <p class="dasher-dashboard-description">
                        <?php echo sprintf(esc_html__('You\'re mentoring %d amazing students. Here\'s their progress at a glance.', 'dasher'), $total_assigned); ?>
                    </p>
                </div>
            </div>
            <div class="dasher-header-actions">
                <?php echo Dasher_Dashboard_Customizer::render_customize_button(); ?>
                <button class="dasher-btn dasher-btn-primary" id="send-bulk-message">
                    <span class="fas fa-envelope"></span>
                    <?php esc_html_e('Message All Students', 'dasher'); ?>
                </button>
                <button class="dasher-btn dasher-btn-outline" id="schedule-checkin">
                    <span class="fas fa-calendar-alt"></span>
                    <?php esc_html_e('Schedule Check-in', 'dasher'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Insights Banner -->
    <?php if (!empty($insights)) : ?>
    <div class="dasher-insights-banner">
        <div class="dasher-insights-header">
            <span class="fas fa-lightbulb"></span>
            <strong><?php esc_html_e('Mentoring Insights', 'dasher'); ?></strong>
        </div>
        <div class="dasher-insights-list">
            <?php foreach ($insights as $insight) : ?>
            <div class="dasher-insight insight-<?php echo esc_attr($insight['type']); ?>">
                <div class="dasher-insight-message"><?php echo esc_html($insight['message']); ?></div>
                <div class="dasher-insight-action"><?php echo esc_html($insight['action']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Enhanced KPI Dashboard -->
    <div class="dasher-kpi-grid">
        <!-- My Students Overview -->
        <div class="dasher-kpi-card primary" data-card-id="students_overview">
            <div class="dasher-kpi-header">
                <div class="dasher-kpi-icon">
                    <span class="fas fa-users"></span>
                </div>
                <div class="dasher-kpi-actions">
                    <button class="dasher-quick-action" onclick="viewAllStudents()">
                        <span class="fas fa-eye"></span>
                    </button>
                </div>
            </div>
            <div class="dasher-kpi-title"><?php esc_html_e('My Students', 'dasher'); ?></div>
            <div class="dasher-kpi-value"><?php echo esc_html($total_assigned); ?></div>
            <div class="dasher-kpi-breakdown">
                <div class="dasher-breakdown-item">
                    <span class="dasher-breakdown-label"><?php esc_html_e('Active', 'dasher'); ?></span>
                    <span class="dasher-breakdown-value dasher-success"><?php echo esc_html($students_by_progress['in_progress']); ?></span>
                </div>
                <div class="dasher-breakdown-item">
                    <span class="dasher-breakdown-label"><?php esc_html_e('Completed', 'dasher'); ?></span>
                    <span class="dasher-breakdown-value dasher-primary"><?php echo esc_html($students_by_progress['completed']); ?></span>
                </div>
                <div class="dasher-breakdown-item">
                    <span class="dasher-breakdown-label"><?php esc_html_e('At Risk', 'dasher'); ?></span>
                    <span class="dasher-breakdown-value dasher-warning"><?php echo esc_html($students_by_progress['at_risk']); ?></span>
                </div>
            </div>
        </div>

        <!-- Success Rate -->
        <div class="dasher-kpi-card success" data-card-id="success_rate">
            <div class="dasher-kpi-header">
                <div class="dasher-kpi-icon">
                    <span class="fas fa-award"></span>
                </div>
                <div class="dasher-kpi-trend <?php echo $success_rate >= 70 ? 'positive' : 'neutral'; ?>">
                    <span class="fas fa-arrow-<?php echo $success_rate >= 70 ? 'up' : 'right'; ?>"></span>
                    <span><?php echo $success_rate >= 70 ? '+' : ''; ?>3%</span>
                </div>
            </div>
            <div class="dasher-kpi-title"><?php esc_html_e('Success Rate', 'dasher'); ?></div>
            <div class="dasher-kpi-value"><?php echo esc_html($success_rate); ?>%</div>
            <div class="dasher-kpi-description">
                <?php echo sprintf(__('%d students have completed their programs', 'dasher'), $students_by_progress['completed']); ?>
            </div>
            <div class="dasher-progress-bar">
                <div class="dasher-progress-fill" style="width: <?php echo esc_attr($success_rate); ?>%"></div>
            </div>
        </div>

        <!-- Engagement Score -->
        <div class="dasher-kpi-card info" data-card-id="engagement_score">
            <div class="dasher-kpi-header">
                <div class="dasher-kpi-icon">
                    <span class="fas fa-chart-area"></span>
                </div>
                <div class="dasher-engagement-indicator">
                    <?php if ($engagement_rate >= 80) : ?>
                        <span class="dasher-indicator excellent">Excellent</span>
                    <?php elseif ($engagement_rate >= 60) : ?>
                        <span class="dasher-indicator good">Good</span>
                    <?php else : ?>
                        <span class="dasher-indicator needs-attention">Needs Attention</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="dasher-kpi-title"><?php esc_html_e('Engagement', 'dasher'); ?></div>
            <div class="dasher-kpi-value"><?php echo esc_html($engagement_rate); ?>%</div>
            <div class="dasher-kpi-description">
                <?php esc_html_e('Overall student engagement level', 'dasher'); ?>
            </div>
            <div class="dasher-progress-bar">
                <div class="dasher-progress-fill" style="width: <?php echo esc_attr($engagement_rate); ?>%"></div>
            </div>
        </div>

        <!-- Communication Status -->
        <div class="dasher-kpi-card warning" data-card-id="communication_status">
            <div class="dasher-kpi-header">
                <div class="dasher-kpi-icon">
                    <span class="fas fa-envelope"></span>
                </div>
                <?php if (count($overdue_communications) > 0) : ?>
                <div class="dasher-alert-badge">
                    <?php echo count($overdue_communications); ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="dasher-kpi-title"><?php esc_html_e('Communications', 'dasher'); ?></div>
            <div class="dasher-kpi-value"><?php echo count($overdue_communications); ?></div>
            <div class="dasher-kpi-description">
                <?php esc_html_e('Students needing follow-up', 'dasher'); ?>
            </div>
            <button class="dasher-kpi-action" onclick="showCommunicationPanel()">
                <?php esc_html_e('Review Communications', 'dasher'); ?>
            </button>
        </div>

        <!-- BigBird Resources Card (5th card to demonstrate grid layout) -->
        <div class="dasher-kpi-card success" data-card-id="bigbird_resources">
            <div class="dasher-kpi-header">
                <div class="dasher-kpi-icon">
                    <span class="fas fa-file-alt"></span>
                </div>
            </div>
            <div class="dasher-kpi-title"><?php esc_html_e('BigBird Resources', 'dasher'); ?></div>
            <div class="dasher-kpi-value">
                <?php 
                $bigbird_docs = get_posts(array(
                    'post_type' => 'dasher_document',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        array(
                            'key' => '_dasher_access_roles',
                            'value' => 'dasher_bigbird',
                            'compare' => 'LIKE'
                        )
                    )
                ));
                echo count($bigbird_docs);
                ?>
            </div>
            <div class="dasher-kpi-description">
                <?php esc_html_e('Guidelines and training materials', 'dasher'); ?>
            </div>
            <div class="dasher-kpi-footer">
                <small>
                    <a href="#" onclick="window.open('<?php echo home_url('/bigbird-library/'); ?>', '_blank')" style="color: inherit; text-decoration: none;">
                        <?php esc_html_e('View All Resources →', 'dasher'); ?>
                    </a>
                </small>
            </div>
        </div>

        <!-- Student LCCP Progress Card (6th card) -->
        <div class="dasher-kpi-card info" data-card-id="lccp_progress">
            <div class="dasher-kpi-header">
                <div class="dasher-kpi-icon">
                    <span class="fas fa-clock"></span>
                </div>
            </div>
            <div class="dasher-kpi-title"><?php esc_html_e('LCCP Progress', 'dasher'); ?></div>
            <div class="dasher-kpi-value">
                <?php 
                // Count students with LCCP hours
                $students_with_hours = 0;
                foreach ($assigned_students_data as $student_data) {
                    $hours = get_user_meta($student_data['user']->ID, 'lccp_hours_tracked', true);
                    if ($hours && $hours > 0) {
                        $students_with_hours++;
                    }
                }
                echo $students_with_hours;
                ?>
            </div>
            <div class="dasher-kpi-description">
                <?php esc_html_e('Students actively logging hours', 'dasher'); ?>
            </div>
            <div class="dasher-kpi-footer">
                <small>
                    <a href="<?php echo admin_url('admin.php?page=dasher-pc-dashboard'); ?>" style="color: inherit; text-decoration: none;">
                        <?php esc_html_e('View LCCP Dashboard →', 'dasher'); ?>
                    </a>
                </small>
            </div>
        </div>

        <!-- Training Support Card (7th card) -->
        <div class="dasher-kpi-card warning">
            <div class="dasher-kpi-header">
                <div class="dasher-kpi-icon">
                    <span class="fas fa-bullhorn"></span>
                </div>
            </div>
            <div class="dasher-kpi-title"><?php esc_html_e('Training Support', 'dasher'); ?></div>
            <div class="dasher-kpi-value">
                <?php 
                // Count students who may need training support (low completion rates)
                $students_needing_support = 0;
                foreach ($assigned_students_data as $student_data) {
                    if (isset($student_data['progress']['average_completion']) && $student_data['progress']['average_completion'] < 50) {
                        $students_needing_support++;
                    }
                }
                echo $students_needing_support;
                ?>
            </div>
            <div class="dasher-kpi-description">
                <?php esc_html_e('Students who may need additional support', 'dasher'); ?>
            </div>
            <div class="dasher-kpi-footer">
                <small><?php esc_html_e('Based on completion rates', 'dasher'); ?></small>
            </div>
        </div>
    </div>

    <!-- Student Progress Matrix -->
    <div class="dasher-data-section">
        <div class="dasher-section-header">
            <h3><?php esc_html_e('Student Progress Matrix', 'dasher'); ?></h3>
            <div class="dasher-view-toggles">
                <button class="dasher-view-btn active" data-view="grid">
                    <span class="fas fa-th"></span>
                    <?php esc_html_e('Grid', 'dasher'); ?>
                </button>
                <button class="dasher-view-btn" data-view="list">
                    <span class="fas fa-list"></span>
                    <?php esc_html_e('List', 'dasher'); ?>
                </button>
            </div>
        </div>
        
        <div class="dasher-student-matrix" id="student-matrix-grid">
            <?php foreach ($assigned_students_data as $student_data) : 
                $progress = $student_data['progress'];
                $progress_class = 'progress-medium';
                if ($progress['completion_percentage'] >= 80) $progress_class = 'progress-high';
                elseif ($progress['completion_percentage'] < 30) $progress_class = 'progress-low';
                
                $status_class = 'status-active';
                if ($progress['completion_percentage'] == 100) $status_class = 'status-completed';
                elseif ($progress['completion_percentage'] == 0) $status_class = 'status-inactive';
            ?>
            <div class="dasher-student-card <?php echo esc_attr($progress_class . ' ' . $status_class); ?>">
                <div class="dasher-student-header">
                    <?php echo get_avatar($student_data['ID'], 60, '', '', array('class' => 'dasher-student-avatar')); ?>
                    <div class="dasher-student-info">
                        <h4 class="dasher-student-name"><?php echo esc_html($student_data['display_name']); ?></h4>
                        <p class="dasher-student-email"><?php echo esc_html($student_data['user_email']); ?></p>
                        <span class="dasher-assignment-date">
                            <?php echo sprintf(__('Assigned: %s', 'dasher'), 
                                is_string($student_data['assigned_date']) && $student_data['assigned_date'] !== 'Auto-assigned' 
                                    ? date_i18n(get_option('date_format'), strtotime($student_data['assigned_date']))
                                    : $student_data['assigned_date']
                            ); ?>
                        </span>
                    </div>
                    <div class="dasher-student-status">
                        <div class="dasher-progress-circle" data-progress="<?php echo esc_attr($progress['completion_percentage']); ?>">
                            <span class="dasher-progress-text"><?php echo esc_html($progress['completion_percentage']); ?>%</span>
                        </div>
                    </div>
                </div>
                
                <div class="dasher-student-metrics">
                    <div class="dasher-metric-row">
                        <div class="dasher-metric">
                            <span class="dasher-metric-label"><?php esc_html_e('Completed', 'dasher'); ?></span>
                            <span class="dasher-metric-value"><?php echo esc_html($progress['completed_courses']); ?></span>
                        </div>
                        <div class="dasher-metric">
                            <span class="dasher-metric-label"><?php esc_html_e('Active', 'dasher'); ?></span>
                            <span class="dasher-metric-value"><?php echo esc_html($progress['in_progress_courses']); ?></span>
                        </div>
                        <div class="dasher-metric">
                            <span class="dasher-metric-label"><?php esc_html_e('Total', 'dasher'); ?></span>
                            <span class="dasher-metric-value"><?php echo esc_html($progress['total_courses']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="dasher-student-actions">
                    <button class="dasher-btn dasher-btn-small" onclick="viewStudentProgress(<?php echo esc_attr($student_data['ID']); ?>)">
                        <span class="fas fa-chart-bar"></span>
                        <?php esc_html_e('Progress', 'dasher'); ?>
                    </button>
                    <button class="dasher-btn dasher-btn-small dasher-btn-outline" onclick="contactStudent(<?php echo esc_attr($student_data['ID']); ?>)">
                        <span class="fas fa-envelope"></span>
                        <?php esc_html_e('Contact', 'dasher'); ?>
                    </button>
                    <button class="dasher-btn dasher-btn-small dasher-btn-outline" onclick="scheduleCheckin(<?php echo esc_attr($student_data['ID']); ?>)">
                        <span class="fas fa-calendar"></span>
                        <?php esc_html_e('Schedule', 'dasher'); ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Achievements -->
    <?php if (!empty($recent_achievements)) : ?>
    <div class="dasher-data-section">
        <div class="dasher-section-header">
            <h3><?php esc_html_e('Recent Achievements', 'dasher'); ?></h3>
            <span class="dasher-section-badge">
                <?php echo sprintf(__('%d this week', 'dasher'), count($recent_achievements)); ?>
            </span>
        </div>
        
        <div class="dasher-achievements-list">
            <?php foreach (array_slice($recent_achievements, 0, 5) as $achievement) : ?>
            <div class="dasher-achievement-item">
                <div class="dasher-achievement-icon">
                    <span class="fas fa-award"></span>
                </div>
                <div class="dasher-achievement-content">
                    <h4 class="dasher-achievement-title"><?php echo esc_html($achievement['course_title']); ?></h4>
                    <p class="dasher-achievement-student">
                        <?php echo sprintf(__('Completed by %s', 'dasher'), esc_html($achievement['student_name'])); ?>
                    </p>
                    <span class="dasher-achievement-date">
                        <?php echo human_time_diff($achievement['completion_date'], current_time('timestamp')) . __(' ago', 'dasher'); ?>
                    </span>
                </div>
                <div class="dasher-achievement-actions">
                    <button class="dasher-btn dasher-btn-small" onclick="congratulateStudent(<?php echo esc_attr($achievement['student_id']); ?>)">
                        <?php esc_html_e('Congratulate', 'dasher'); ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Communication Dashboard -->
    <div class="dasher-data-section">
        <div class="dasher-section-header">
            <h3><?php esc_html_e('Communication Dashboard', 'dasher'); ?></h3>
            <button class="dasher-btn dasher-btn-primary" onclick="openCommunicationCenter()">
                <span class="fas fa-comments"></span>
                <?php esc_html_e('Communication Center', 'dasher'); ?>
            </button>
        </div>
        
        <?php if (!empty($student_communications)) : ?>
        <div class="dasher-communication-list">
            <?php foreach (array_slice($student_communications, 0, 8) as $comm) : ?>
            <div class="dasher-communication-item <?php echo $comm['needs_followup'] ? 'needs-followup' : ''; ?>">
                <div class="dasher-communication-student">
                    <?php echo get_avatar($comm['student_id'], 40, '', '', array('class' => 'dasher-comm-avatar')); ?>
                    <div class="dasher-comm-info">
                        <h5 class="dasher-comm-name"><?php echo esc_html($comm['student_name']); ?></h5>
                        <span class="dasher-comm-status">
                            <?php if ($comm['needs_followup']) : ?>
                                <span class="dasher-status-urgent"><?php esc_html_e('Follow-up needed', 'dasher'); ?></span>
                            <?php else : ?>
                                <span class="dasher-status-good"><?php esc_html_e('Recent contact', 'dasher'); ?></span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                <div class="dasher-communication-timing">
                    <span class="dasher-last-contact">
                        <?php echo sprintf(__('Last contact: %d days ago', 'dasher'), $comm['days_since_contact']); ?>
                    </span>
                    <span class="dasher-contact-date">
                        <?php echo date_i18n(get_option('date_format'), strtotime($comm['last_contact'])); ?>
                    </span>
                </div>
                <div class="dasher-communication-actions">
                    <button class="dasher-btn dasher-btn-small" onclick="quickMessage(<?php echo esc_attr($comm['student_id']); ?>)">
                        <?php esc_html_e('Quick Message', 'dasher'); ?>
                    </button>
                    <button class="dasher-btn dasher-btn-small dasher-btn-outline" onclick="logContact(<?php echo esc_attr($comm['student_id']); ?>)">
                        <?php esc_html_e('Log Contact', 'dasher'); ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
        <div class="dasher-empty-state">
            <div class="dasher-empty-state-icon">
                <span class="fas fa-envelope"></span>
            </div>
            <p class="dasher-empty-state-message"><?php esc_html_e('No communication history yet. Start connecting with your students!', 'dasher'); ?></p>
            <button class="dasher-btn dasher-btn-primary" onclick="sendFirstMessage()">
                <?php esc_html_e('Send Welcome Message', 'dasher'); ?>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Performance Analytics -->
    <div class="dasher-data-section">
        <div class="dasher-section-header">
            <h3><?php esc_html_e('My Mentoring Analytics', 'dasher'); ?></h3>
            <div class="dasher-analytics-period">
                <select class="dasher-select" id="analytics-period">
                    <option value="7"><?php esc_html_e('Last 7 days', 'dasher'); ?></option>
                    <option value="30" selected><?php esc_html_e('Last 30 days', 'dasher'); ?></option>
                    <option value="90"><?php esc_html_e('Last 90 days', 'dasher'); ?></option>
                </select>
            </div>
        </div>
        
        <div class="dasher-analytics-grid">
            <div class="dasher-analytic-card">
                <div class="dasher-analytic-header">
                    <span class="fas fa-chart-line"></span>
                    <h4><?php esc_html_e('Progress Trend', 'dasher'); ?></h4>
                </div>
                <div class="dasher-analytic-chart">
                    <canvas id="progress-trend-chart" width="300" height="150"></canvas>
                </div>
            </div>
            
            <div class="dasher-analytic-card">
                <div class="dasher-analytic-header">
                    <span class="fas fa-users-cog"></span>
                    <h4><?php esc_html_e('Student Distribution', 'dasher'); ?></h4>
                </div>
                <div class="dasher-analytic-chart">
                    <canvas id="student-distribution-chart" width="300" height="150"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Enhanced BigBird Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Hide page titles on dashboard pages
    const pageTitles = document.querySelectorAll('.entry-header, .page-header, h1.entry-title, h1.page-title');
    pageTitles.forEach(title => {
        if (document.querySelector('.dasher-frontend-dashboard')) {
            title.style.display = 'none';
        }
    });
    
    // Additional fallback - hide any remaining visible page titles
    const additionalTitles = document.querySelectorAll('header h1, .bb-grid h1, .site-main h1.entry-title');
    additionalTitles.forEach(title => {
        if (document.querySelector('.dasher-frontend-dashboard') && 
            (title.textContent.includes('BigBird') || title.textContent.includes('Dashboard'))) {
            title.style.display = 'none';
        }
    });
    // Initialize progress circles
    initializeProgressCircles();
    
    // Initialize charts
    initializeCharts();
    
    // View toggle functionality
    const viewButtons = document.querySelectorAll('.dasher-view-btn');
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            viewButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            toggleView(this.getAttribute('data-view'));
        });
    });
});

function initializeProgressCircles() {
    const circles = document.querySelectorAll('.dasher-progress-circle');
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

<?php echo Dasher_Dashboard_Customizer::render_customizer_panel('bigbird'); ?>