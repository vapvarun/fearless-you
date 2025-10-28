<?php
/**
 * Enhanced Frontend Mentor Dashboard Template
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

// Make sure user has required capability
if (!current_user_can('view_all_student_progress')) {
    ?>
    <div class="lccp-error-message">
        <p><?php esc_html_e('You do not have permission to access this dashboard.', 'lccp-systems'); ?></p>
    </div>
    <?php
    return;
}

// Use transient caching for performance
$cache_key = 'lccp_mentor_dashboard_data_' . $current_user_id;
$cached_data = get_transient($cache_key);

if ($cached_data === false) {
    // Get all Program Candidates (PCs) - but limit for performance
    $pc_args = array(
        'role' => 'lccp_pc',
        'number' => 100, // Limit to first 100 for performance
        'orderby' => 'registered',
        'order' => 'DESC'
    );
    $program_candidates = get_users($pc_args);

    // Get all Big Birds
    $bb_args = array(
        'role' => 'lccp_big_bird',
        'number' => 50, // Reasonable limit
    );
    $big_birds = get_users($bb_args);
    
    // Cache the basic data
    set_transient($cache_key, array(
        'program_candidates' => $program_candidates,
        'big_birds' => $big_birds
    ), 300); // Cache for 5 minutes
} else {
    $program_candidates = $cached_data['program_candidates'];
    $big_birds = $cached_data['big_birds'];
}

// Get assignment data
global $wpdb;
$assignments_table = $wpdb->prefix . 'lccp_assignments';

// Enhanced metrics calculation
$assigned_students = array();
$bigbird_performance = array();
$student_engagement = array();

if ($wpdb->get_var("SHOW TABLES LIKE '$assignments_table'") == $assignments_table) {
    $assigned_students = $wpdb->get_col("SELECT DISTINCT student_id FROM $assignments_table");
    
    // Get Big Bird performance metrics
    $bigbird_performance = $wpdb->get_results("
        SELECT 
            bb.ID as big_bird_id,
            bb.display_name as bigbird_name,
            bb.user_email as bigbird_email,
            COUNT(a.student_id) as student_count,
            DATE(a.assigned_date) as assignment_date,
            AVG(DATEDIFF(NOW(), a.assigned_date)) as avg_assignment_duration
        FROM {$wpdb->users} bb
        LEFT JOIN $assignments_table a ON bb.ID = a.big_bird_id
        WHERE bb.ID IN (SELECT ID FROM {$wpdb->users} u INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id WHERE um.meta_key = '{$wpdb->prefix}capabilities' AND um.meta_value LIKE '%lccp_big_bird%')
        GROUP BY bb.ID
        ORDER BY student_count DESC
    ");
}

// Calculate enhanced statistics
$total_students = count($program_candidates);
$total_bigbirds = count($big_birds);
$assigned_count = count($assigned_students);
$unassigned_count = $total_students - $assigned_count;
$assignment_rate = $total_students > 0 ? round(($assigned_count / $total_students) * 100) : 0;

// Calculate student engagement metrics with caching
$engagement_cache_key = 'lccp_engagement_metrics_' . $current_user_id;
$engagement_metrics = get_transient($engagement_cache_key);

if ($engagement_metrics === false) {
    $engagement_metrics = array();
    // Limit to first 20 students for performance on initial load
    $students_to_process = array_slice($program_candidates, 0, 20);
    
    foreach ($students_to_process as $student) {
        // Check individual student cache first
        $student_cache_key = 'lccp_student_progress_' . $student->ID;
        $progress_data = get_transient($student_cache_key);
        
        if ($progress_data === false) {
            // Simplified progress calculation for performance
            $user_courses = function_exists('learndash_user_get_enrolled_courses') ? 
                learndash_user_get_enrolled_courses($student->ID) : array();
            
            $completed_courses = 0;
            $in_progress_courses = count($user_courses);
            
            foreach ($user_courses as $course_id) {
                if (function_exists('learndash_course_completed') && learndash_course_completed($student->ID, $course_id)) {
                    $completed_courses++;
                    $in_progress_courses--;
                }
            }
            
            $completion_percentage = ($completed_courses + $in_progress_courses) > 0 ? 
                round(($completed_courses / ($completed_courses + $in_progress_courses)) * 100) : 0;
            
            $progress_data = array(
                'completion_percentage' => $completion_percentage,
                'completed_courses' => $completed_courses,
                'in_progress_courses' => $in_progress_courses,
                'total_courses' => $completed_courses + $in_progress_courses
            );
            
            set_transient($student_cache_key, $progress_data, 600); // Cache for 10 minutes
        }
        
        $engagement_metrics[] = array(
            'student_id' => $student->ID,
            'student_name' => $student->display_name,
            'completion_rate' => $progress_data['completion_percentage'],
            'courses_in_progress' => $progress_data['in_progress_courses'],
            'courses_completed' => $progress_data['completed_courses'],
            'last_activity' => get_user_meta($student->ID, 'last_activity', true) ?: 'Never'
        );
    }
    
    set_transient($engagement_cache_key, $engagement_metrics, 300); // Cache for 5 minutes
}

// Sort by engagement (completion rate)
usort($engagement_metrics, function($a, $b) {
    return $b['completion_rate'] - $a['completion_rate'];
});

// Calculate performance insights
$high_performers = array_filter($engagement_metrics, function($student) {
    return $student['completion_rate'] >= 80;
});

$at_risk_students = array_filter($engagement_metrics, function($student) {
    return $student['completion_rate'] < 30 && $student['courses_in_progress'] == 0;
});

$avg_completion_rate = count($engagement_metrics) > 0 ? 
    round(array_sum(array_column($engagement_metrics, 'completion_rate')) / count($engagement_metrics)) : 0;

// Get recent activities and alerts
$recent_completions = array();
$alerts = array();

// Check for students who haven't been active
foreach ($engagement_metrics as $student) {
    if ($student['last_activity'] !== 'Never') {
        $last_activity_date = strtotime($student['last_activity']);
        $days_inactive = floor((time() - $last_activity_date) / (60 * 60 * 24));
        
        if ($days_inactive > 7) {
            $alerts[] = array(
                'type' => 'inactive',
                'message' => sprintf('%s has been inactive for %d days', $student['student_name'], $days_inactive),
                'severity' => $days_inactive > 14 ? 'high' : 'medium',
                'student_id' => $student['student_id']
            );
        }
    }
}

// Check for overloaded Big Birds
foreach ($bigbird_performance as $bb) {
    if ($bb->student_count > 10) {
        $alerts[] = array(
            'type' => 'overload',
            'message' => sprintf('%s is managing %d students (recommended max: 10)', $bb->bigbird_name, $bb->student_count),
            'severity' => 'medium',
            'big_bird_id' => $bb->big_bird_id
        );
    }
}

// Sort alerts by severity
usort($alerts, function($a, $b) {
    $severity_order = array('high' => 3, 'medium' => 2, 'low' => 1);
    return $severity_order[$b['severity']] - $severity_order[$a['severity']];
});

// Calculate statistics
$avg_workload = $total_bigbirds > 0 ? round($assigned_count / $total_bigbirds, 1) : 0;
?>

<div class="lccp-frontend-dashboard lccp-mentor-dashboard">
    <!-- Enhanced Header with Time Period Selector -->
    <div class="lccp-dashboard-header">
        <div class="lccp-header-left">
            <h2><?php esc_html_e('Mentor Command Center', 'lccp-systems'); ?></h2>
            <p class="lccp-dashboard-description">
                <?php esc_html_e('Advanced analytics and insights for program management', 'lccp-systems'); ?>
            </p>
        </div>
        <div class="lccp-header-right">
            <div class="lccp-time-selector">
                <select id="lccp-time-period" class="lccp-select">
                    <option value="7"><?php esc_html_e('Last 7 days', 'lccp-systems'); ?></option>
                    <option value="30" selected><?php esc_html_e('Last 30 days', 'lccp-systems'); ?></option>
                    <option value="90"><?php esc_html_e('Last 90 days', 'lccp-systems'); ?></option>
                    <option value="365"><?php esc_html_e('Last year', 'lccp-systems'); ?></option>
                </select>
            </div>
            <button class="lccp-btn lccp-btn-primary" id="export-report">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Export Report', 'lccp-systems'); ?>
            </button>
        </div>
    </div>

    <!-- Alert Banner -->
    <?php if (!empty($alerts)) : ?>
    <div class="lccp-alerts-banner">
        <div class="lccp-alert-header">
            <span class="dashicons dashicons-warning"></span>
            <strong><?php echo sprintf(__('%d Active Alerts', 'lccp-systems'), count($alerts)); ?></strong>
            <button class="lccp-toggle-alerts" data-expanded="false">
                <?php esc_html_e('View All', 'lccp-systems'); ?>
                <span class="dashicons dashicons-arrow-down"></span>
            </button>
        </div>
        <div class="lccp-alerts-list" style="display: none;">
            <?php foreach (array_slice($alerts, 0, 5) as $alert) : ?>
            <div class="lccp-alert severity-<?php echo esc_attr($alert['severity']); ?>">
                <span class="lccp-alert-icon">
                    <?php if ($alert['type'] === 'inactive') : ?>
                        <span class="dashicons dashicons-clock"></span>
                    <?php elseif ($alert['type'] === 'overload') : ?>
                        <span class="dashicons dashicons-chart-line"></span>
                    <?php endif; ?>
                </span>
                <span class="lccp-alert-message"><?php echo esc_html($alert['message']); ?></span>
                <span class="lccp-alert-severity"><?php echo esc_html(ucfirst($alert['severity'])); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Enhanced KPI Cards -->
    <div class="lccp-kpi-grid">
        <!-- Total Students with Trend -->
        <div class="lccp-kpi-card primary" data-card-id="students_overview">
            <div class="lccp-kpi-header">
                <div class="lccp-kpi-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="lccp-kpi-trend positive">
                    <span class="dashicons dashicons-arrow-up"></span>
                    <span>+12%</span>
                </div>
            </div>
            <div class="lccp-kpi-title"><?php esc_html_e('Total Students', 'lccp-systems'); ?></div>
            <div class="lccp-kpi-value"><?php echo esc_html($total_students); ?></div>
            <div class="lccp-kpi-description">
                <span class="lccp-success"><?php echo esc_html($assigned_count); ?> assigned</span> • 
                <span class="lccp-warning"><?php echo esc_html($unassigned_count); ?> unassigned</span>
            </div>
            <div class="lccp-progress-bar">
                <div class="lccp-progress-fill" style="width: <?php echo esc_attr($assignment_rate); ?>%"></div>
            </div>
            <div class="lccp-kpi-footer">
                <small><?php echo sprintf(__('%d%% assignment rate', 'lccp-systems'), $assignment_rate); ?></small>
            </div>
        </div>

        <!-- Average Completion Rate -->
        <div class="lccp-kpi-card success" data-card-id="success_rate">
            <div class="lccp-kpi-header">
                <div class="lccp-kpi-icon">
                    <span class="dashicons dashicons-chart-pie"></span>
                </div>
                <div class="lccp-kpi-trend <?php echo $avg_completion_rate >= 70 ? 'positive' : 'negative'; ?>">
                    <span class="dashicons dashicons-arrow-<?php echo $avg_completion_rate >= 70 ? 'up' : 'down'; ?>"></span>
                    <span><?php echo $avg_completion_rate >= 70 ? '+' : '-'; ?>5%</span>
                </div>
            </div>
            <div class="lccp-kpi-title"><?php esc_html_e('Avg Completion Rate', 'lccp-systems'); ?></div>
            <div class="lccp-kpi-value"><?php echo esc_html($avg_completion_rate); ?>%</div>
            <div class="lccp-kpi-description">
                <span class="lccp-success"><?php echo count($high_performers); ?> high performers</span>
            </div>
            <div class="lccp-progress-bar">
                <div class="lccp-progress-fill" style="width: <?php echo esc_attr($avg_completion_rate); ?>%"></div>
            </div>
            <div class="lccp-kpi-footer">
                <small><?php esc_html_e('Program-wide average', 'lccp-systems'); ?></small>
            </div>
        </div>

        <!-- BigBird Efficiency -->
        <div class="lccp-kpi-card info" data-card-id="engagement_score">
            <div class="lccp-kpi-header">
                <div class="lccp-kpi-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="lccp-kpi-trend neutral">
                    <span class="dashicons dashicons-minus"></span>
                    <span>0%</span>
                </div>
            </div>
            <div class="lccp-kpi-title"><?php esc_html_e('Big Bird Network', 'lccp-systems'); ?></div>
            <div class="lccp-kpi-value"><?php echo esc_html($total_bigbirds); ?></div>
            <div class="lccp-kpi-description">
                <?php 
                $active_bigbirds = count(array_filter($bigbird_performance, function($bb) { return $bb->student_count > 0; }));
                echo sprintf(__('%d active mentors', 'lccp-systems'), $active_bigbirds);
                ?>
            </div>
            <div class="lccp-progress-bar">
                <div class="lccp-progress-fill" style="width: <?php echo $total_bigbirds > 0 ? esc_attr(($active_bigbirds / $total_bigbirds) * 100) : 0; ?>%"></div>
            </div>
            <div class="lccp-kpi-footer">
                <small><?php echo sprintf(__('%.1f avg students per Big Bird', 'lccp-systems'), $total_bigbirds > 0 ? $assigned_count / $total_bigbirds : 0); ?></small>
            </div>
        </div>

        <!-- At-Risk Students -->
        <div class="lccp-kpi-card warning" data-card-id="communication_status">
            <div class="lccp-kpi-header">
                <div class="lccp-kpi-icon">
                    <span class="dashicons dashicons-warning"></span>
                </div>
                <div class="lccp-kpi-trend negative">
                    <span class="dashicons dashicons-arrow-up"></span>
                    <span>+3</span>
                </div>
            </div>
            <div class="lccp-kpi-title"><?php esc_html_e('At-Risk Students', 'lccp-systems'); ?></div>
            <div class="lccp-kpi-value"><?php echo count($at_risk_students); ?></div>
            <div class="lccp-kpi-description">
                <?php esc_html_e('Need immediate attention', 'lccp-systems'); ?>
            </div>
            <div class="lccp-kpi-footer">
                <small><?php echo sprintf(__('%d%% of total students', 'lccp-systems'), $total_students > 0 ? round((count($at_risk_students) / $total_students) * 100) : 0); ?></small>
            </div>
        </div>

        <!-- Document Resources Card -->
        <div class="lccp-kpi-card info" data-card-id="mentor_resources">
            <div class="lccp-kpi-header">
                <div class="lccp-kpi-icon">
                    <span class="dashicons dashicons-media-document"></span>
                </div>
            </div>
            <div class="lccp-kpi-title"><?php esc_html_e('Mentor Resources', 'lccp-systems'); ?></div>
            <div class="lccp-kpi-value">
                <?php 
                $mentor_docs = get_posts(array(
                    'post_type' => 'lccp_document',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        array(
                            'key' => '_lccp_access_roles',
                            'value' => 'lccp_mentor',
                            'compare' => 'LIKE'
                        )
                    )
                ));
                echo count($mentor_docs);
                ?>
            </div>
            <div class="lccp-kpi-description">
                <?php esc_html_e('Available documents and resources', 'lccp-systems'); ?>
            </div>
            <div class="lccp-kpi-footer">
                <small>
                    <a href="#" onclick="window.open('<?php echo home_url('/mentor-library/'); ?>', '_blank')" style="color: inherit; text-decoration: none;">
                        <?php esc_html_e('View All Documents →', 'lccp-systems'); ?>
                    </a>
                </small>
            </div>
        </div>

        <!-- LCCP Hours Overview Card -->
        <div class="lccp-kpi-card warning" data-card-id="lccp_overview">
            <div class="lccp-kpi-header">
                <div class="lccp-kpi-icon">
                    <span class="dashicons dashicons-clock"></span>
                </div>
            </div>
            <div class="lccp-kpi-title"><?php esc_html_e('LCCP Hours Overview', 'lccp-systems'); ?></div>
            <div class="lccp-kpi-value">
                <?php 
                // Get total LCCP hours across all PCs
                global $wpdb;
                $total_hours = $wpdb->get_var("
                    SELECT SUM(meta_value) 
                    FROM {$wpdb->usermeta} 
                    WHERE meta_key = 'lccp_hours_tracked'
                ");
                echo number_format($total_hours ?: 0, 1);
                ?>
            </div>
            <div class="lccp-kpi-description">
                <?php esc_html_e('Total hours logged by Program Candidates', 'lccp-systems'); ?>
            </div>
            <div class="lccp-kpi-footer">
                <small>
                    <a href="<?php echo admin_url('admin.php?page=dasher-pc-dashboard'); ?>" style="color: inherit; text-decoration: none;">
                        <?php esc_html_e('View LCCP Dashboard →', 'lccp-systems'); ?>
                    </a>
                </small>
            </div>
        </div>
    </div>

    <!-- Enhanced Charts Section -->
    <div class="lccp-charts-section">
        <div class="lccp-chart-container">
            <div class="lccp-chart-header">
                <h3><?php esc_html_e('Student Progress Overview', 'lccp-systems'); ?></h3>
                <div class="lccp-chart-controls">
                    <button class="lccp-chart-btn active" data-chart="completion">
                        <?php esc_html_e('Completion Rate', 'lccp-systems'); ?>
                    </button>
                    <button class="lccp-chart-btn" data-chart="engagement">
                        <?php esc_html_e('Engagement', 'lccp-systems'); ?>
                    </button>
                    <button class="lccp-chart-btn" data-chart="bigbird">
                        <?php esc_html_e('Big Bird Performance', 'lccp-systems'); ?>
                    </button>
                </div>
            </div>
            <div class="lccp-chart-canvas">
                <canvas id="lccp-main-chart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Student Performance Leaderboard -->
    <div class="lccp-data-section">
        <div class="lccp-section-header">
            <h3><?php esc_html_e('Student Performance Leaderboard', 'lccp-systems'); ?></h3>
            <div class="lccp-section-controls">
                <input type="text" placeholder="<?php esc_attr_e('Search students...', 'lccp-systems'); ?>" class="lccp-search-input" id="student-search">
                <select class="lccp-filter-select" id="performance-filter">
                    <option value="all"><?php esc_html_e('All Students', 'lccp-systems'); ?></option>
                    <option value="high"><?php esc_html_e('High Performers (80%+)', 'lccp-systems'); ?></option>
                    <option value="medium"><?php esc_html_e('Medium Performers (50-79%)', 'lccp-systems'); ?></option>
                    <option value="low"><?php esc_html_e('At Risk (<50%)', 'lccp-systems'); ?></option>
                </select>
            </div>
        </div>
        
        <div class="lccp-leaderboard">
            <?php foreach (array_slice($engagement_metrics, 0, 10) as $index => $student) : 
                $rank_class = '';
                if ($index === 0) $rank_class = 'gold';
                elseif ($index === 1) $rank_class = 'silver';
                elseif ($index === 2) $rank_class = 'bronze';
                
                $performance_class = 'performance-medium';
                if ($student['completion_rate'] >= 80) $performance_class = 'performance-high';
                elseif ($student['completion_rate'] < 50) $performance_class = 'performance-low';
            ?>
            <div class="lccp-leaderboard-item <?php echo esc_attr($rank_class . ' ' . $performance_class); ?>">
                <div class="lccp-rank">
                    <span class="lccp-rank-number"><?php echo $index + 1; ?></span>
                    <?php if ($rank_class) : ?>
                        <span class="lccp-rank-medal dashicons dashicons-awards"></span>
                    <?php endif; ?>
                </div>
                
                <div class="lccp-student-info">
                    <?php echo get_avatar($student['student_id'], 50, '', '', array('class' => 'lccp-student-avatar')); ?>
                    <div class="lccp-student-details">
                        <h4 class="lccp-student-name"><?php echo esc_html($student['student_name']); ?></h4>
                        <div class="lccp-student-stats">
                            <span class="lccp-stat">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php echo sprintf(__('%d completed', 'lccp-systems'), $student['courses_completed']); ?>
                            </span>
                            <span class="lccp-stat">
                                <span class="dashicons dashicons-clock"></span>
                                <?php echo sprintf(__('%d in progress', 'lccp-systems'), $student['courses_in_progress']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="lccp-progress-section">
                    <div class="lccp-progress-circle" data-progress="<?php echo esc_attr($student['completion_rate']); ?>">
                        <span class="lccp-progress-text"><?php echo esc_html($student['completion_rate']); ?>%</span>
                    </div>
                </div>
                
                <div class="lccp-action-section">
                    <button class="lccp-btn lccp-btn-small" onclick="viewStudentDetail(<?php echo esc_attr($student['student_id']); ?>)">
                        <?php esc_html_e('View Details', 'lccp-systems'); ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- BigBird Management Dashboard -->
    <div class="lccp-data-section">
        <div class="lccp-section-header">
            <h3><?php esc_html_e('Big Bird Management Dashboard', 'lccp-systems'); ?></h3>
            <button class="lccp-btn lccp-btn-primary" id="auto-assign-students">
                <span class="dashicons dashicons-randomize"></span>
                <?php esc_html_e('Auto-Assign Students', 'lccp-systems'); ?>
            </button>
        </div>
        
        <div class="lccp-big-bird-grid">
            <?php foreach ($bigbird_performance as $bb) : 
                $workload_status = 'normal';
                if ($bb->student_count > 10) $workload_status = 'overloaded';
                elseif ($bb->student_count < 3) $workload_status = 'underutilized';
            ?>
            <div class="lccp-big-bird-card <?php echo esc_attr($workload_status); ?>">
                <div class="lccp-big-bird-header">
                    <?php echo get_avatar($bb->big_bird_id, 60, '', '', array('class' => 'lccp-big-bird-avatar')); ?>
                    <div class="lccp-big-bird-info">
                        <h4 class="lccp-big-bird-name"><?php echo esc_html($bb->bigbird_name); ?></h4>
                        <p class="lccp-big-bird-email"><?php echo esc_html($bb->bigbird_email); ?></p>
                        <span class="lccp-workload-badge <?php echo esc_attr($workload_status); ?>">
                            <?php echo ucfirst($workload_status); ?>
                        </span>
                    </div>
                </div>
                
                <div class="lccp-big-bird-metrics">
                    <div class="lccp-metric">
                        <span class="lccp-metric-label"><?php esc_html_e('Students', 'lccp-systems'); ?></span>
                        <span class="lccp-metric-value"><?php echo esc_html($bb->student_count); ?></span>
                    </div>
                    <div class="lccp-metric">
                        <span class="lccp-metric-label"><?php esc_html_e('Avg Duration', 'lccp-systems'); ?></span>
                        <span class="lccp-metric-value"><?php echo esc_html(round($bb->avg_assignment_duration ?: 0)); ?>d</span>
                    </div>
                    <div class="lccp-metric">
                        <span class="lccp-metric-label"><?php esc_html_e('Capacity', 'lccp-systems'); ?></span>
                        <span class="lccp-metric-value"><?php echo esc_html(round(($bb->student_count / 10) * 100)); ?>%</span>
                    </div>
                </div>
                
                <div class="lccp-big-bird-actions">
                    <button class="lccp-btn lccp-btn-small" onclick="manageBigBird(<?php echo esc_attr($bb->big_bird_id); ?>)">
                        <?php esc_html_e('Manage', 'lccp-systems'); ?>
                    </button>
                    <button class="lccp-btn lccp-btn-small lccp-btn-outline" onclick="viewBigBirdStats(<?php echo esc_attr($bb->big_bird_id); ?>)">
                        <?php esc_html_e('Stats', 'lccp-systems'); ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
// Enhanced JavaScript functionality  
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
            (title.textContent.includes('Mentor') || title.textContent.includes('Dashboard'))) {
            title.style.display = 'none';
        }
    });
    
    // Force header background color for mentor dashboard
    const mentorHeader = document.querySelector('.lccp-mentor-dashboard .lccp-dashboard-header');
    if (mentorHeader) {
        mentorHeader.style.background = 'linear-gradient(135deg, rgb(54, 87, 112) 0%, rgb(44, 71, 92) 100%)';
        mentorHeader.style.color = 'white';
    }
    
    // Toggle alerts visibility
    const toggleAlertsBtn = document.querySelector('.lccp-toggle-alerts');
    if (toggleAlertsBtn) {
        toggleAlertsBtn.addEventListener('click', function() {
            const alertsList = document.querySelector('.lccp-alerts-list');
            const isExpanded = this.getAttribute('data-expanded') === 'true';
            
            if (isExpanded) {
                alertsList.style.display = 'none';
                this.setAttribute('data-expanded', 'false');
                this.innerHTML = '<?php esc_html_e('View All', 'lccp-systems'); ?> <span class="dashicons dashicons-arrow-down"></span>';
            } else {
                alertsList.style.display = 'block';
                this.setAttribute('data-expanded', 'true');
                this.innerHTML = '<?php esc_html_e('Hide', 'lccp-systems'); ?> <span class="dashicons dashicons-arrow-up"></span>';
            }
        });
    }
    
    // Chart functionality
    const chartButtons = document.querySelectorAll('.lccp-chart-btn');
    chartButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            chartButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            updateChart(this.getAttribute('data-chart'));
        });
    });
    
    // Search functionality
    const searchInput = document.getElementById('student-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterStudents(this.value);
        });
    }
    
    // Performance filter
    const performanceFilter = document.getElementById('performance-filter');
    if (performanceFilter) {
        performanceFilter.addEventListener('change', function() {
            filterByPerformance(this.value);
        });
    }
});

function viewStudentDetail(studentId) {
    // Implement student detail view
    window.location.href = '/wp-admin/user-edit.php?user_id=' + studentId;
}

function manageBigBird(bigbirdId) {
    // Implement Big Bird management
    window.location.href = '/wp-admin/admin.php?page=lccp-big-bird-assignment&bigbird=' + bigbirdId;
}

function viewBigBirdStats(bigbirdId) {
    // Implement Big Bird stats view
    console.log('Viewing stats for Big Bird:', bigbirdId);
}

function updateChart(type) {
    // Implement chart updates
    console.log('Updating chart to:', type);
}

function filterStudents(query) {
    // Implement student filtering
    console.log('Filtering students with query:', query);
}

function filterByPerformance(level) {
    // Implement performance filtering
    console.log('Filtering by performance level:', level);
}
</script>