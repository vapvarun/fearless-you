<?php
/**
 * Enhanced Frontend Mentor Dashboard Template
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

// Make sure user has required capability
if (!current_user_can('view_all_student_progress')) {
    ?>
    <div class="dasher-error-message">
        <p><?php esc_html_e('You do not have permission to access this dashboard.', 'dasher'); ?></p>
    </div>
    <?php
    return;
}

// Get dasher plugin instance for enhanced data
global $dasher_plugin;
if (!$dasher_plugin) {
    $dasher_plugin = new Dasher_Plugin();
}

// Use transient caching for performance
$cache_key = 'dasher_mentor_dashboard_data_' . $current_user_id;
$cached_data = get_transient($cache_key);

if ($cached_data === false) {
    // Get all Program Candidates (PCs) - but limit for performance
    $pc_args = array(
        'role' => 'dasher_pc',
        'number' => 100, // Limit to first 100 for performance
        'orderby' => 'registered',
        'order' => 'DESC'
    );
    $program_candidates = get_users($pc_args);

    // Get all BigBirds
    $bb_args = array(
        'role' => 'dasher_bigbird',
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
$assignments_table = $wpdb->prefix . 'dasher_student_assignments';

// Enhanced metrics calculation
$assigned_students = array();
$bigbird_performance = array();
$student_engagement = array();

if ($wpdb->get_var("SHOW TABLES LIKE '$assignments_table'") == $assignments_table) {
    $assigned_students = $wpdb->get_col("SELECT DISTINCT student_id FROM $assignments_table");
    
    // Get BigBird performance metrics
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
        WHERE bb.ID IN (SELECT ID FROM {$wpdb->users} u INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id WHERE um.meta_key = '{$wpdb->prefix}capabilities' AND um.meta_value LIKE '%dasher_bigbird%')
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
$engagement_cache_key = 'dasher_engagement_metrics_' . $current_user_id;
$engagement_metrics = get_transient($engagement_cache_key);

if ($engagement_metrics === false) {
    $engagement_metrics = array();
    // Limit to first 20 students for performance on initial load
    $students_to_process = array_slice($program_candidates, 0, 20);
    
    foreach ($students_to_process as $student) {
        // Check individual student cache first
        $student_cache_key = 'dasher_student_progress_' . $student->ID;
        $progress_data = get_transient($student_cache_key);
        
        if ($progress_data === false) {
            $progress_data = $dasher_plugin->get_student_progress_data($student->ID);
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

// Check for overloaded BigBirds
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
?>

<div class="dasher-frontend-dashboard dasher-mentor-dashboard-enhanced">
    <!-- Enhanced Header with Time Period Selector -->
    <div class="dasher-dashboard-header">
        <div class="dasher-header-left">
            <h2><?php esc_html_e('Mentor Command Center', 'dasher'); ?></h2>
            <p class="dasher-dashboard-description">
                <?php esc_html_e('Advanced analytics and insights for program management', 'dasher'); ?>
            </p>
        </div>
        <div class="dasher-header-right">
            <div class="dasher-time-selector">
                <select id="dasher-time-period" class="dasher-select">
                    <option value="7"><?php esc_html_e('Last 7 days', 'dasher'); ?></option>
                    <option value="30" selected><?php esc_html_e('Last 30 days', 'dasher'); ?></option>
                    <option value="90"><?php esc_html_e('Last 90 days', 'dasher'); ?></option>
                    <option value="365"><?php esc_html_e('Last year', 'dasher'); ?></option>
                </select>
            </div>
            <?php echo Dasher_Dashboard_Customizer::render_customize_button(); ?>
            <button class="dasher-btn dasher-btn-primary" id="export-report">
                <span class="fas fa-download"></span>
                <?php esc_html_e('Export Report', 'dasher'); ?>
            </button>
        </div>
    </div>

    <!-- Alert Banner -->
    <?php if (!empty($alerts)) : ?>
    <div class="dasher-alerts-banner">
        <div class="dasher-alert-header">
            <span class="fas fa-exclamation-triangle"></span>
            <strong><?php echo sprintf(__('%d Active Alerts', 'dasher'), count($alerts)); ?></strong>
            <button class="dasher-toggle-alerts" data-expanded="false">
                <?php esc_html_e('View All', 'dasher'); ?>
                <span class="fas fa-arrow-down"></span>
            </button>
        </div>
        <div class="dasher-alerts-list" style="display: none;">
            <?php foreach (array_slice($alerts, 0, 5) as $alert) : ?>
            <div class="dasher-alert severity-<?php echo esc_attr($alert['severity']); ?>">
                <span class="dasher-alert-icon">
                    <?php if ($alert['type'] === 'inactive') : ?>
                        <span class="fas fa-clock"></span>
                    <?php elseif ($alert['type'] === 'overload') : ?>
                        <span class="fas fa-chart-line"></span>
                    <?php endif; ?>
                </span>
                <span class="dasher-alert-message"><?php echo esc_html($alert['message']); ?></span>
                <span class="dasher-alert-severity"><?php echo esc_html(ucfirst($alert['severity'])); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Enhanced KPI Cards -->
    <div class="dasher-kpi-grid">
        <!-- Total Students with Trend -->
        <div class="dasher-kpi-card primary" data-card-id="students_overview">
            <div class="dasher-kpi-header">
                <div class="dasher-kpi-icon">
                    <span class="fas fa-users"></span>
                </div>
                <div class="dasher-kpi-trend positive">
                    <span class="fas fa-arrow-up"></span>
                    <span>+12%</span>
                </div>
            </div>
            <div class="dasher-kpi-title"><?php esc_html_e('Total Students', 'dasher'); ?></div>
            <div class="dasher-kpi-value"><?php echo esc_html($total_students); ?></div>
            <div class="dasher-kpi-description">
                <span class="dasher-success"><?php echo esc_html($assigned_count); ?> assigned</span> • 
                <span class="dasher-warning"><?php echo esc_html($unassigned_count); ?> unassigned</span>
            </div>
            <div class="dasher-progress-bar">
                <div class="dasher-progress-fill" style="width: <?php echo esc_attr($assignment_rate); ?>%"></div>
            </div>
            <div class="dasher-kpi-footer">
                <small><?php echo sprintf(__('%d%% assignment rate', 'dasher'), $assignment_rate); ?></small>
            </div>
        </div>

        <!-- Average Completion Rate -->
        <div class="dasher-kpi-card success" data-card-id="success_rate">
            <div class="dasher-kpi-header">
                <div class="dasher-kpi-icon">
                    <span class="fas fa-chart-pie"></span>
                </div>
                <div class="dasher-kpi-trend <?php echo $avg_completion_rate >= 70 ? 'positive' : 'negative'; ?>">
                    <span class="fas fa-arrow-<?php echo $avg_completion_rate >= 70 ? 'up' : 'down'; ?>"></span>
                    <span><?php echo $avg_completion_rate >= 70 ? '+' : '-'; ?>5%</span>
                </div>
            </div>
            <div class="dasher-kpi-title"><?php esc_html_e('Avg Completion Rate', 'dasher'); ?></div>
            <div class="dasher-kpi-value"><?php echo esc_html($avg_completion_rate); ?>%</div>
            <div class="dasher-kpi-description">
                <span class="dasher-success"><?php echo count($high_performers); ?> high performers</span>
            </div>
            <div class="dasher-progress-bar">
                <div class="dasher-progress-fill" style="width: <?php echo esc_attr($avg_completion_rate); ?>%"></div>
            </div>
            <div class="dasher-kpi-footer">
                <small><?php esc_html_e('Program-wide average', 'dasher'); ?></small>
            </div>
        </div>

        <!-- BigBird Efficiency -->
        <div class="dasher-kpi-card info" data-card-id="engagement_score">
            <div class="dasher-kpi-header">
                <div class="dasher-kpi-icon">
                    <span class="fas fa-users-cog"></span>
                </div>
                <div class="dasher-kpi-trend neutral">
                    <span class="fas fa-minus"></span>
                    <span>0%</span>
                </div>
            </div>
            <div class="dasher-kpi-title"><?php esc_html_e('BigBird Network', 'dasher'); ?></div>
            <div class="dasher-kpi-value"><?php echo esc_html($total_bigbirds); ?></div>
            <div class="dasher-kpi-description">
                <?php 
                $active_bigbirds = count(array_filter($bigbird_performance, function($bb) { return $bb->student_count > 0; }));
                echo sprintf(__('%d active mentors', 'dasher'), $active_bigbirds);
                ?>
            </div>
            <div class="dasher-progress-bar">
                <div class="dasher-progress-fill" style="width: <?php echo $total_bigbirds > 0 ? esc_attr(($active_bigbirds / $total_bigbirds) * 100) : 0; ?>%"></div>
            </div>
            <div class="dasher-kpi-footer">
                <small><?php echo sprintf(__('%.1f avg students per BigBird', 'dasher'), $total_bigbirds > 0 ? $assigned_count / $total_bigbirds : 0); ?></small>
            </div>
        </div>

        <!-- At-Risk Students -->
        <div class="dasher-kpi-card warning" data-card-id="communication_status">
            <div class="dasher-kpi-header">
                <div class="dasher-kpi-icon">
                    <span class="fas fa-exclamation-triangle"></span>
                </div>
                <div class="dasher-kpi-trend negative">
                    <span class="fas fa-arrow-up"></span>
                    <span>+3</span>
                </div>
            </div>
            <div class="dasher-kpi-title"><?php esc_html_e('At-Risk Students', 'dasher'); ?></div>
            <div class="dasher-kpi-value"><?php echo count($at_risk_students); ?></div>
            <div class="dasher-kpi-description">
                <?php esc_html_e('Need immediate attention', 'dasher'); ?>
            </div>
            <div class="dasher-kpi-footer">
                <small><?php echo sprintf(__('%d%% of total students', 'dasher'), $total_students > 0 ? round((count($at_risk_students) / $total_students) * 100) : 0); ?></small>
            </div>
        </div>

        <!-- Document Resources Card (5th card to demonstrate grid layout) -->
        <div class="dasher-kpi-card info" data-card-id="mentor_resources">
            <div class="dasher-kpi-header">
                <div class="dasher-kpi-icon">
                    <span class="fas fa-file-alt"></span>
                </div>
            </div>
            <div class="dasher-kpi-title"><?php esc_html_e('Mentor Resources', 'dasher'); ?></div>
            <div class="dasher-kpi-value">
                <?php 
                $mentor_docs = get_posts(array(
                    'post_type' => 'dasher_document',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        array(
                            'key' => '_dasher_access_roles',
                            'value' => 'dasher_mentor',
                            'compare' => 'LIKE'
                        )
                    )
                ));
                echo count($mentor_docs);
                ?>
            </div>
            <div class="dasher-kpi-description">
                <?php esc_html_e('Available documents and resources', 'dasher'); ?>
            </div>
            <div class="dasher-kpi-footer">
                <small>
                    <a href="#" onclick="window.open('<?php echo home_url('/mentor-library/'); ?>', '_blank')" style="color: inherit; text-decoration: none;">
                        <?php esc_html_e('View All Documents →', 'dasher'); ?>
                    </a>
                </small>
            </div>
        </div>

        <!-- LCCP Hours Overview Card (6th card) -->
        <div class="dasher-kpi-card warning" data-card-id="lccp_overview">
            <div class="dasher-kpi-header">
                <div class="dasher-kpi-icon">
                    <span class="fas fa-clock"></span>
                </div>
            </div>
            <div class="dasher-kpi-title"><?php esc_html_e('LCCP Hours Overview', 'dasher'); ?></div>
            <div class="dasher-kpi-value">
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
            <div class="dasher-kpi-description">
                <?php esc_html_e('Total hours logged by Program Candidates', 'dasher'); ?>
            </div>
            <div class="dasher-kpi-footer">
                <small>
                    <a href="<?php echo admin_url('admin.php?page=dasher-pc-dashboard'); ?>" style="color: inherit; text-decoration: none;">
                        <?php esc_html_e('View LCCP Dashboard →', 'dasher'); ?>
                    </a>
                </small>
            </div>
        </div>
    </div>

    <!-- Enhanced Charts Section -->
    <div class="dasher-charts-section">
        <div class="dasher-chart-container">
            <div class="dasher-chart-header">
                <h3><?php esc_html_e('Student Progress Overview', 'dasher'); ?></h3>
                <div class="dasher-chart-controls">
                    <button class="dasher-chart-btn active" data-chart="completion">
                        <?php esc_html_e('Completion Rate', 'dasher'); ?>
                    </button>
                    <button class="dasher-chart-btn" data-chart="engagement">
                        <?php esc_html_e('Engagement', 'dasher'); ?>
                    </button>
                    <button class="dasher-chart-btn" data-chart="bigbird">
                        <?php esc_html_e('BigBird Performance', 'dasher'); ?>
                    </button>
                </div>
            </div>
            <div class="dasher-chart-canvas">
                <canvas id="dasher-main-chart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Student Performance Leaderboard -->
    <div class="dasher-data-section">
        <div class="dasher-section-header">
            <h3><?php esc_html_e('Student Performance Leaderboard', 'dasher'); ?></h3>
            <div class="dasher-section-controls">
                <input type="text" placeholder="<?php esc_attr_e('Search students...', 'dasher'); ?>" class="dasher-search-input" id="student-search">
                <select class="dasher-filter-select" id="performance-filter">
                    <option value="all"><?php esc_html_e('All Students', 'dasher'); ?></option>
                    <option value="high"><?php esc_html_e('High Performers (80%+)', 'dasher'); ?></option>
                    <option value="medium"><?php esc_html_e('Medium Performers (50-79%)', 'dasher'); ?></option>
                    <option value="low"><?php esc_html_e('At Risk (<50%)', 'dasher'); ?></option>
                </select>
            </div>
        </div>
        
        <div class="dasher-leaderboard">
            <?php foreach (array_slice($engagement_metrics, 0, 10) as $index => $student) : 
                $rank_class = '';
                if ($index === 0) $rank_class = 'gold';
                elseif ($index === 1) $rank_class = 'silver';
                elseif ($index === 2) $rank_class = 'bronze';
                
                $performance_class = 'performance-medium';
                if ($student['completion_rate'] >= 80) $performance_class = 'performance-high';
                elseif ($student['completion_rate'] < 50) $performance_class = 'performance-low';
            ?>
            <div class="dasher-leaderboard-item <?php echo esc_attr($rank_class . ' ' . $performance_class); ?>">
                <div class="dasher-rank">
                    <span class="dasher-rank-number"><?php echo $index + 1; ?></span>
                    <?php if ($rank_class) : ?>
                        <span class="dasher-rank-medal fas fa-award"></span>
                    <?php endif; ?>
                </div>
                
                <div class="dasher-student-info">
                    <?php echo get_avatar($student['student_id'], 50, '', '', array('class' => 'dasher-student-avatar')); ?>
                    <div class="dasher-student-details">
                        <h4 class="dasher-student-name"><?php echo esc_html($student['student_name']); ?></h4>
                        <div class="dasher-student-stats">
                            <span class="dasher-stat">
                                <span class="fas fa-check-circle"></span>
                                <?php echo sprintf(__('%d completed', 'dasher'), $student['courses_completed']); ?>
                            </span>
                            <span class="dasher-stat">
                                <span class="fas fa-clock"></span>
                                <?php echo sprintf(__('%d in progress', 'dasher'), $student['courses_in_progress']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="dasher-progress-section">
                    <div class="dasher-progress-circle" data-progress="<?php echo esc_attr($student['completion_rate']); ?>">
                        <span class="dasher-progress-text"><?php echo esc_html($student['completion_rate']); ?>%</span>
                    </div>
                </div>
                
                <div class="dasher-action-section">
                    <button class="dasher-btn dasher-btn-small" onclick="viewStudentDetail(<?php echo esc_attr($student['student_id']); ?>)">
                        <?php esc_html_e('View Details', 'dasher'); ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- BigBird Management Dashboard -->
    <div class="dasher-data-section">
        <div class="dasher-section-header">
            <h3><?php esc_html_e('BigBird Management Dashboard', 'dasher'); ?></h3>
            <button class="dasher-btn dasher-btn-primary" id="auto-assign-students">
                <span class="fas fa-random"></span>
                <?php esc_html_e('Auto-Assign Students', 'dasher'); ?>
            </button>
        </div>
        
        <div class="dasher-bigbird-grid">
            <?php foreach ($bigbird_performance as $bb) : 
                $workload_status = 'normal';
                if ($bb->student_count > 10) $workload_status = 'overloaded';
                elseif ($bb->student_count < 3) $workload_status = 'underutilized';
            ?>
            <div class="dasher-bigbird-card <?php echo esc_attr($workload_status); ?>">
                <div class="dasher-bigbird-header">
                    <?php echo get_avatar($bb->big_bird_id, 60, '', '', array('class' => 'dasher-bigbird-avatar')); ?>
                    <div class="dasher-bigbird-info">
                        <h4 class="dasher-bigbird-name"><?php echo esc_html($bb->bigbird_name); ?></h4>
                        <p class="dasher-bigbird-email"><?php echo esc_html($bb->bigbird_email); ?></p>
                        <span class="dasher-workload-badge <?php echo esc_attr($workload_status); ?>">
                            <?php echo ucfirst($workload_status); ?>
                        </span>
                    </div>
                </div>
                
                <div class="dasher-bigbird-metrics">
                    <div class="dasher-metric">
                        <span class="dasher-metric-label"><?php esc_html_e('Students', 'dasher'); ?></span>
                        <span class="dasher-metric-value"><?php echo esc_html($bb->student_count); ?></span>
                    </div>
                    <div class="dasher-metric">
                        <span class="dasher-metric-label"><?php esc_html_e('Avg Duration', 'dasher'); ?></span>
                        <span class="dasher-metric-value"><?php echo esc_html(round($bb->avg_assignment_duration ?: 0)); ?>d</span>
                    </div>
                    <div class="dasher-metric">
                        <span class="dasher-metric-label"><?php esc_html_e('Capacity', 'dasher'); ?></span>
                        <span class="dasher-metric-value"><?php echo esc_html(round(($bb->student_count / 10) * 100)); ?>%</span>
                    </div>
                </div>
                
                <div class="dasher-bigbird-actions">
                    <button class="dasher-btn dasher-btn-small" onclick="manageBigBird(<?php echo esc_attr($bb->big_bird_id); ?>)">
                        <?php esc_html_e('Manage', 'dasher'); ?>
                    </button>
                    <button class="dasher-btn dasher-btn-small dasher-btn-outline" onclick="viewBigBirdStats(<?php echo esc_attr($bb->big_bird_id); ?>)">
                        <?php esc_html_e('Stats', 'dasher'); ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php echo Dasher_Dashboard_Customizer::render_customizer_panel('mentor'); ?>

<script>
// Enhanced JavaScript functionality  
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
            (title.textContent.includes('Mentor') || title.textContent.includes('Dashboard'))) {
            title.style.display = 'none';
        }
    });
    
    // Force header background color for mentor dashboard
    const mentorHeader = document.querySelector('.dasher-mentor-dashboard-enhanced .dasher-dashboard-header');
    if (mentorHeader) {
        mentorHeader.style.background = 'linear-gradient(135deg, rgb(54, 87, 112) 0%, rgb(44, 71, 92) 100%)';
        mentorHeader.style.color = 'white';
    }
    // Toggle alerts visibility
    const toggleAlertsBtn = document.querySelector('.dasher-toggle-alerts');
    if (toggleAlertsBtn) {
        toggleAlertsBtn.addEventListener('click', function() {
            const alertsList = document.querySelector('.dasher-alerts-list');
            const isExpanded = this.getAttribute('data-expanded') === 'true';
            
            if (isExpanded) {
                alertsList.style.display = 'none';
                this.setAttribute('data-expanded', 'false');
                this.innerHTML = '<?php esc_html_e('View All', 'dasher'); ?> <span class="fas fa-arrow-down"></span>';
            } else {
                alertsList.style.display = 'block';
                this.setAttribute('data-expanded', 'true');
                this.innerHTML = '<?php esc_html_e('Hide', 'dasher'); ?> <span class="fas fa-arrow-up"></span>';
            }
        });
    }
    
    // Chart functionality
    const chartButtons = document.querySelectorAll('.dasher-chart-btn');
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
    // Implement BigBird management
    window.location.href = '/wp-admin/admin.php?page=dasher-bigbird-assignment&bigbird=' + bigbirdId;
}

function viewBigBirdStats(bigbirdId) {
    // Implement BigBird stats view
    console.log('Viewing stats for BigBird:', bigbirdId);
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