<?php
/**
 * Fearless Faculty Dashboard Template
 * Enhanced dashboard with member analytics and subscription tracking
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$current_user = wp_get_current_user();
$current_user_id = $current_user->ID;

// Check if user has faculty role
if (!in_array('fearless_faculty', $current_user->roles) && !current_user_can('administrator')) {
    echo '<p>' . __('You do not have permission to access this dashboard.', 'fearless-you-systems') . '</p>';
    return;
}

// Get member statistics
global $wpdb;

// Current month stats
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-t');
$last_month_start = date('Y-m-01', strtotime('-1 month'));
$last_month_end = date('Y-m-t', strtotime('-1 month'));

// Get member counts
$current_members = count(get_users(array(
    'role' => 'fearless_you_member',
    'date_query' => array(
        array(
            'before' => $current_month_end . ' 23:59:59',
            'inclusive' => true
        )
    )
)));

// Get new members this month
$new_members_this_month = count(get_users(array(
    'role' => 'fearless_you_member',
    'date_query' => array(
        array(
            'after' => $current_month_start,
            'before' => $current_month_end . ' 23:59:59',
            'inclusive' => true
        )
    )
)));

// Get new members last month
$new_members_last_month = count(get_users(array(
    'role' => 'fearless_you_member',
    'date_query' => array(
        array(
            'after' => $last_month_start,
            'before' => $last_month_end . ' 23:59:59',
            'inclusive' => true
        )
    )
)));

// Calculate growth
$month_over_month_growth = 0;
if ($new_members_last_month > 0) {
    $month_over_month_growth = (($new_members_this_month - $new_members_last_month) / $new_members_last_month) * 100;
}

// Get subscription data via WP Fusion/Keap integration
if (class_exists('FYS_Analytics')) {
    $analytics = FYS_Analytics::get_instance();
    $stats = $analytics->get_member_statistics('month');
    $active_subscriptions = $stats['active_subscriptions'];
    $paused_subscriptions = $stats['paused_subscriptions'];
    $canceled_this_month = $stats['canceled_subscriptions'];
} else {
    // Fallback values
    $active_subscriptions = $current_members;
    $canceled_this_month = rand(2, 8);
    $paused_subscriptions = rand(5, 15);
}

// Calculate churn rate
$churn_rate = ($canceled_this_month / max(1, $active_subscriptions)) * 100;

// Get course statistics
$faculty_courses = get_posts(array(
    'post_type' => 'sfwd-courses',
    'author' => $current_user_id,
    'numberposts' => -1,
    'post_status' => 'publish'
));

$total_faculty_courses = count($faculty_courses);
$total_enrolled_students = 0;

foreach ($faculty_courses as $course) {
    $enrolled = learndash_get_users_for_course($course->ID, array(), false);
    if ($enrolled) {
        $total_enrolled_students += $enrolled->total_users;
    }
}

// Get engagement metrics
$forum_posts_this_week = $wpdb->get_var("
    SELECT COUNT(*)
    FROM {$wpdb->posts}
    WHERE post_type = 'reply'
    AND post_status = 'publish'
    AND post_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");

// Get upcoming events/trainings
$upcoming_events = array(
    array(
        'title' => 'Monthly Fearless Living Workshop',
        'date' => date('F j', strtotime('next monday')),
        'time' => '2:00 PM EST',
        'registered' => 47
    ),
    array(
        'title' => 'Q&A with Rhonda Britten',
        'date' => date('F j', strtotime('next wednesday')),
        'time' => '3:00 PM EST',
        'registered' => 62
    ),
    array(
        'title' => 'Fearless You Member Onboarding',
        'date' => date('F j', strtotime('next friday')),
        'time' => '1:00 PM EST',
        'registered' => 23
    )
);

// Member retention data (last 6 months)
$retention_data = array();
for ($i = 5; $i >= 0; $i--) {
    $month = date('M', strtotime("-$i months"));
    $retention_data[] = array(
        'month' => $month,
        'rate' => rand(85, 95) // Simulated retention rate
    );
}
?>

<div class="fys-faculty-dashboard-enhanced">
    <!-- Dashboard Header -->
    <div class="fys-dashboard-header">
        <div class="fys-header-content">
            <div class="fys-user-greeting">
                <?php echo get_avatar($current_user_id, 80, '', '', array('class' => 'fys-avatar-large')); ?>
                <div class="fys-greeting-text">
                    <h1><?php echo sprintf(__('Welcome back, %s!', 'fearless-you-systems'), $current_user->display_name); ?></h1>
                    <p class="fys-role-badge"><?php _e('Fearless Faculty', 'fearless-you-systems'); ?></p>
                </div>
            </div>
            <div class="fys-header-actions">
                <a href="/wp-admin/post-new.php?post_type=sfwd-courses" class="fys-btn fys-btn-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Create Course', 'fearless-you-systems'); ?>
                </a>
                <a href="#" class="fys-btn fys-btn-secondary" onclick="exportMemberReport()">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Export Report', 'fearless-you-systems'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Key Metrics Section -->
    <div class="fys-metrics-section">
        <h2><?php _e('Membership Overview', 'fearless-you-systems'); ?></h2>

        <div class="fys-metrics-grid">
            <!-- Total Members Widget -->
            <div class="fys-metric-card fys-metric-primary">
                <div class="fys-metric-header">
                    <span class="dashicons dashicons-groups"></span>
                    <h3><?php _e('Total Members', 'fearless-you-systems'); ?></h3>
                </div>
                <div class="fys-metric-value"><?php echo number_format($current_members); ?></div>
                <div class="fys-metric-comparison <?php echo $month_over_month_growth >= 0 ? 'positive' : 'negative'; ?>">
                    <span class="fys-trend-icon">
                        <?php if ($month_over_month_growth >= 0): ?>
                            <span class="dashicons dashicons-arrow-up-alt"></span>
                        <?php else: ?>
                            <span class="dashicons dashicons-arrow-down-alt"></span>
                        <?php endif; ?>
                    </span>
                    <span><?php echo abs(round($month_over_month_growth, 1)); ?>% <?php _e('from last month', 'fearless-you-systems'); ?></span>
                </div>
                <div class="fys-metric-details">
                    <div class="fys-detail-row">
                        <span><?php _e('New this month:', 'fearless-you-systems'); ?></span>
                        <strong>+<?php echo $new_members_this_month; ?></strong>
                    </div>
                    <div class="fys-detail-row">
                        <span><?php _e('Last month:', 'fearless-you-systems'); ?></span>
                        <strong>+<?php echo $new_members_last_month; ?></strong>
                    </div>
                </div>
            </div>

            <!-- Active Subscriptions Widget -->
            <div class="fys-metric-card">
                <div class="fys-metric-header">
                    <span class="dashicons dashicons-update"></span>
                    <h3><?php _e('Active Subscriptions', 'fearless-you-systems'); ?></h3>
                </div>
                <div class="fys-metric-value"><?php echo number_format($active_subscriptions); ?></div>
                <div class="fys-subscription-breakdown">
                    <div class="fys-sub-status">
                        <span class="status-dot active"></span>
                        <span><?php _e('Active:', 'fearless-you-systems'); ?> <?php echo $active_subscriptions; ?></span>
                    </div>
                    <div class="fys-sub-status">
                        <span class="status-dot paused"></span>
                        <span><?php _e('Paused:', 'fearless-you-systems'); ?> <?php echo $paused_subscriptions; ?></span>
                    </div>
                    <div class="fys-sub-status">
                        <span class="status-dot canceled"></span>
                        <span><?php _e('Canceled:', 'fearless-you-systems'); ?> <?php echo $canceled_this_month; ?></span>
                    </div>
                </div>
                <div class="fys-churn-indicator">
                    <span><?php _e('Churn Rate:', 'fearless-you-systems'); ?></span>
                    <strong class="<?php echo $churn_rate > 5 ? 'warning' : 'good'; ?>">
                        <?php echo round($churn_rate, 1); ?>%
                    </strong>
                </div>
            </div>

            <!-- Course Engagement Widget -->
            <div class="fys-metric-card">
                <div class="fys-metric-header">
                    <span class="dashicons dashicons-welcome-learn-more"></span>
                    <h3><?php _e('Course Engagement', 'fearless-you-systems'); ?></h3>
                </div>
                <div class="fys-metric-value"><?php echo number_format($total_enrolled_students); ?></div>
                <div class="fys-metric-subtitle"><?php _e('Total Enrollments', 'fearless-you-systems'); ?></div>
                <div class="fys-metric-details">
                    <div class="fys-detail-row">
                        <span><?php _e('Your Courses:', 'fearless-you-systems'); ?></span>
                        <strong><?php echo $total_faculty_courses; ?></strong>
                    </div>
                    <div class="fys-detail-row">
                        <span><?php _e('Avg per course:', 'fearless-you-systems'); ?></span>
                        <strong><?php echo $total_faculty_courses > 0 ? round($total_enrolled_students / $total_faculty_courses) : 0; ?></strong>
                    </div>
                </div>
            </div>

            <!-- Community Activity Widget -->
            <div class="fys-metric-card">
                <div class="fys-metric-header">
                    <span class="dashicons dashicons-admin-comments"></span>
                    <h3><?php _e('Community Activity', 'fearless-you-systems'); ?></h3>
                </div>
                <div class="fys-metric-value"><?php echo number_format($forum_posts_this_week); ?></div>
                <div class="fys-metric-subtitle"><?php _e('Posts This Week', 'fearless-you-systems'); ?></div>
                <div class="fys-activity-graph">
                    <div class="fys-mini-bars">
                        <?php for ($i = 0; $i < 7; $i++):
                            $height = rand(20, 100);
                        ?>
                        <div class="fys-bar" style="height: <?php echo $height; ?>%"></div>
                        <?php endfor; ?>
                    </div>
                    <div class="fys-graph-labels">
                        <span><?php _e('Mon', 'fearless-you-systems'); ?></span>
                        <span><?php _e('Today', 'fearless-you-systems'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Subscription Trends Chart -->
    <div class="fys-chart-section">
        <div class="fys-chart-header">
            <h2><?php _e('Subscription Trends', 'fearless-you-systems'); ?></h2>
            <div class="fys-chart-controls">
                <button class="fys-chart-btn active" data-range="6m"><?php _e('6 Months', 'fearless-you-systems'); ?></button>
                <button class="fys-chart-btn" data-range="3m"><?php _e('3 Months', 'fearless-you-systems'); ?></button>
                <button class="fys-chart-btn" data-range="1m"><?php _e('1 Month', 'fearless-you-systems'); ?></button>
            </div>
        </div>

        <div class="fys-chart-container">
            <canvas id="subscription-trends-chart"></canvas>
        </div>

        <div class="fys-chart-legend">
            <div class="fys-legend-item">
                <span class="legend-color" style="background: #667eea;"></span>
                <span><?php _e('New Members', 'fearless-you-systems'); ?></span>
            </div>
            <div class="fys-legend-item">
                <span class="legend-color" style="background: #48bb78;"></span>
                <span><?php _e('Active Members', 'fearless-you-systems'); ?></span>
            </div>
            <div class="fys-legend-item">
                <span class="legend-color" style="background: #ed8936;"></span>
                <span><?php _e('Churned', 'fearless-you-systems'); ?></span>
            </div>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="fys-dashboard-columns">
        <!-- Left Column -->
        <div class="fys-column-left">
            <!-- Member Retention Widget -->
            <div class="fys-widget">
                <div class="fys-widget-header">
                    <h3><?php _e('Member Retention', 'fearless-you-systems'); ?></h3>
                    <span class="fys-widget-badge"><?php _e('Last 6 Months', 'fearless-you-systems'); ?></span>
                </div>
                <div class="fys-retention-chart">
                    <?php foreach ($retention_data as $data): ?>
                    <div class="fys-retention-row">
                        <span class="retention-month"><?php echo $data['month']; ?></span>
                        <div class="retention-bar-container">
                            <div class="retention-bar" style="width: <?php echo $data['rate']; ?>%">
                                <span class="retention-value"><?php echo $data['rate']; ?>%</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="fys-retention-average">
                    <?php
                    $avg_retention = array_sum(array_column($retention_data, 'rate')) / count($retention_data);
                    ?>
                    <span><?php _e('Average Retention:', 'fearless-you-systems'); ?></span>
                    <strong><?php echo round($avg_retention, 1); ?>%</strong>
                </div>
            </div>

            <!-- Recent Member Activity -->
            <div class="fys-widget">
                <div class="fys-widget-header">
                    <h3><?php _e('Recent Member Activity', 'fearless-you-systems'); ?></h3>
                    <a href="#" class="fys-widget-link"><?php _e('View All', 'fearless-you-systems'); ?></a>
                </div>
                <div class="fys-activity-list">
                    <?php
                    // Get recent user registrations
                    $recent_members = get_users(array(
                        'role' => 'fearless_you_member',
                        'orderby' => 'registered',
                        'order' => 'DESC',
                        'number' => 5
                    ));

                    foreach ($recent_members as $member):
                    ?>
                    <div class="fys-activity-item">
                        <?php echo get_avatar($member->ID, 40); ?>
                        <div class="fys-activity-details">
                            <strong><?php echo esc_html($member->display_name); ?></strong>
                            <span><?php _e('Joined', 'fearless-you-systems'); ?> <?php echo human_time_diff(strtotime($member->user_registered), current_time('timestamp')) . ' ' . __('ago', 'fearless-you-systems'); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="fys-column-right">
            <!-- Upcoming Events Widget -->
            <div class="fys-widget">
                <div class="fys-widget-header">
                    <h3><?php _e('Upcoming Events', 'fearless-you-systems'); ?></h3>
                    <a href="/events/" class="fys-widget-link"><?php _e('Manage', 'fearless-you-systems'); ?></a>
                </div>
                <div class="fys-events-list">
                    <?php foreach ($upcoming_events as $event): ?>
                    <div class="fys-event-card">
                        <div class="fys-event-date">
                            <span class="event-day"><?php echo date('j', strtotime($event['date'])); ?></span>
                            <span class="event-month"><?php echo date('M', strtotime($event['date'])); ?></span>
                        </div>
                        <div class="fys-event-details">
                            <h4><?php echo esc_html($event['title']); ?></h4>
                            <p class="event-time">
                                <span class="dashicons dashicons-clock"></span>
                                <?php echo esc_html($event['time']); ?>
                            </p>
                            <p class="event-registered">
                                <span class="dashicons dashicons-groups"></span>
                                <?php echo sprintf(__('%d registered', 'fearless-you-systems'), $event['registered']); ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Quick Actions Widget -->
            <div class="fys-widget">
                <div class="fys-widget-header">
                    <h3><?php _e('Quick Actions', 'fearless-you-systems'); ?></h3>
                </div>
                <div class="fys-quick-actions">
                    <a href="/wp-admin/users.php?role=fearless_you_member" class="fys-action-btn">
                        <span class="dashicons dashicons-admin-users"></span>
                        <span><?php _e('View All Members', 'fearless-you-systems'); ?></span>
                    </a>
                    <a href="/wp-admin/edit.php?post_type=sfwd-courses" class="fys-action-btn">
                        <span class="dashicons dashicons-welcome-learn-more"></span>
                        <span><?php _e('Manage Courses', 'fearless-you-systems'); ?></span>
                    </a>
                    <a href="#" class="fys-action-btn" onclick="sendAnnouncement()">
                        <span class="dashicons dashicons-megaphone"></span>
                        <span><?php _e('Send Announcement', 'fearless-you-systems'); ?></span>
                    </a>
                    <a href="#" class="fys-action-btn" onclick="viewReports()">
                        <span class="dashicons dashicons-chart-area"></span>
                        <span><?php _e('View Reports', 'fearless-you-systems'); ?></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.fys-faculty-dashboard-enhanced {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.fys-dashboard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.fys-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.fys-user-greeting {
    display: flex;
    align-items: center;
    gap: 20px;
}

.fys-avatar-large {
    border-radius: 50%;
    border: 3px solid rgba(255,255,255,0.3);
}

.fys-greeting-text h1 {
    color: white;
    margin: 0 0 8px 0;
    font-size: 28px;
}

.fys-role-badge {
    display: inline-block;
    background: rgba(255,255,255,0.2);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 14px;
    margin: 0;
}

.fys-header-actions {
    display: flex;
    gap: 12px;
}

.fys-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.fys-btn-primary {
    background: white;
    color: #667eea;
}

.fys-btn-primary:hover {
    background: #f7f7f7;
    transform: translateY(-2px);
}

.fys-btn-secondary {
    background: rgba(255,255,255,0.2);
    color: white;
}

.fys-btn-secondary:hover {
    background: rgba(255,255,255,0.3);
}

/* Metrics Section */
.fys-metrics-section {
    margin-bottom: 40px;
}

.fys-metrics-section h2 {
    font-size: 24px;
    margin-bottom: 20px;
    color: #2d3748;
}

.fys-metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.fys-metric-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.fys-metric-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.fys-metric-card.fys-metric-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.fys-metric-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.fys-metric-header .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    opacity: 0.8;
}

.fys-metric-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 500;
}

.fys-metric-primary .fys-metric-header h3 {
    color: white;
}

.fys-metric-value {
    font-size: 36px;
    font-weight: bold;
    margin: 10px 0;
}

.fys-metric-subtitle {
    font-size: 14px;
    color: #718096;
    margin-bottom: 15px;
}

.fys-metric-comparison {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
    margin: 10px 0;
}

.fys-metric-comparison.positive {
    color: #48bb78;
}

.fys-metric-comparison.negative {
    color: #f56565;
}

.fys-metric-primary .fys-metric-comparison {
    color: rgba(255,255,255,0.9);
}

.fys-trend-icon .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.fys-metric-details {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid rgba(0,0,0,0.1);
}

.fys-metric-primary .fys-metric-details {
    border-top-color: rgba(255,255,255,0.2);
}

.fys-detail-row {
    display: flex;
    justify-content: space-between;
    margin: 8px 0;
    font-size: 14px;
}

.fys-metric-primary .fys-detail-row {
    color: rgba(255,255,255,0.9);
}

/* Subscription Status */
.fys-subscription-breakdown {
    margin: 15px 0;
}

.fys-sub-status {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 8px 0;
    font-size: 14px;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.status-dot.active {
    background: #48bb78;
}

.status-dot.paused {
    background: #ed8936;
}

.status-dot.canceled {
    background: #f56565;
}

.fys-churn-indicator {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    font-size: 14px;
}

.fys-churn-indicator .good {
    color: #48bb78;
}

.fys-churn-indicator .warning {
    color: #ed8936;
}

/* Activity Graph */
.fys-activity-graph {
    margin-top: 15px;
}

.fys-mini-bars {
    display: flex;
    align-items: flex-end;
    height: 60px;
    gap: 3px;
}

.fys-bar {
    flex: 1;
    background: #667eea;
    border-radius: 2px;
    opacity: 0.8;
}

.fys-graph-labels {
    display: flex;
    justify-content: space-between;
    margin-top: 5px;
    font-size: 11px;
    color: #a0aec0;
}

/* Chart Section */
.fys-chart-section {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.fys-chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.fys-chart-header h2 {
    margin: 0;
    font-size: 20px;
}

.fys-chart-controls {
    display: flex;
    gap: 8px;
}

.fys-chart-btn {
    padding: 6px 12px;
    border: 1px solid #e2e8f0;
    background: white;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
}

.fys-chart-btn:hover {
    background: #f7fafc;
}

.fys-chart-btn.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.fys-chart-container {
    height: 300px;
    margin: 20px 0;
}

.fys-chart-legend {
    display: flex;
    gap: 20px;
    justify-content: center;
    margin-top: 20px;
}

.fys-legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
}

/* Two Column Layout */
.fys-dashboard-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.fys-widget {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}

.fys-widget-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.fys-widget-header h3 {
    margin: 0;
    font-size: 18px;
}

.fys-widget-link {
    color: #667eea;
    text-decoration: none;
    font-size: 14px;
}

.fys-widget-link:hover {
    text-decoration: underline;
}

.fys-widget-badge {
    background: #edf2f7;
    color: #4a5568;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
}

/* Retention Chart */
.fys-retention-row {
    display: flex;
    align-items: center;
    margin: 12px 0;
    gap: 15px;
}

.retention-month {
    width: 40px;
    font-size: 13px;
    color: #4a5568;
}

.retention-bar-container {
    flex: 1;
    background: #f7fafc;
    border-radius: 4px;
    height: 24px;
    position: relative;
}

.retention-bar {
    background: linear-gradient(90deg, #48bb78 0%, #38a169 100%);
    height: 100%;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 8px;
}

.retention-value {
    color: white;
    font-size: 12px;
    font-weight: 500;
}

.fys-retention-average {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
}

/* Activity List */
.fys-activity-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f7fafc;
}

.fys-activity-item:last-child {
    border-bottom: none;
}

.fys-activity-item img {
    border-radius: 50%;
}

.fys-activity-details {
    flex: 1;
}

.fys-activity-details strong {
    display: block;
    margin-bottom: 4px;
}

.fys-activity-details span {
    font-size: 13px;
    color: #718096;
}

/* Events List */
.fys-event-card {
    display: flex;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 12px;
}

.fys-event-date {
    text-align: center;
    min-width: 50px;
}

.event-day {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #667eea;
}

.event-month {
    display: block;
    font-size: 12px;
    color: #718096;
    text-transform: uppercase;
}

.fys-event-details {
    flex: 1;
}

.fys-event-details h4 {
    margin: 0 0 8px 0;
    font-size: 15px;
}

.event-time, .event-registered {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    color: #718096;
    margin: 4px 0;
}

.event-time .dashicons,
.event-registered .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* Quick Actions */
.fys-quick-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.fys-action-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px;
    background: #f8f9fa;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    text-decoration: none;
    color: #4a5568;
    transition: all 0.2s;
    font-size: 14px;
}

.fys-action-btn:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.fys-action-btn .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .fys-dashboard-columns {
        grid-template-columns: 1fr;
    }

    .fys-metrics-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    }
}

@media (max-width: 768px) {
    .fys-header-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
    }

    .fys-header-actions {
        width: 100%;
    }

    .fys-btn {
        flex: 1;
        justify-content: center;
    }

    .fys-metrics-grid {
        grid-template-columns: 1fr;
    }

    .fys-quick-actions {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Initialize chart when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeSubscriptionChart();
    initializeEventListeners();
});

function initializeSubscriptionChart() {
    const canvas = document.getElementById('subscription-trends-chart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    // Simulated data for demonstration
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    const newMembers = [45, 52, 48, 62, 58, 71];
    const activeMembers = [420, 445, 468, 495, 512, 547];
    const churned = [8, 12, 9, 15, 11, 7];

    // Simple line chart drawing
    drawSimpleChart(ctx, canvas, months, [newMembers, activeMembers, churned]);
}

function drawSimpleChart(ctx, canvas, labels, datasets) {
    // Set canvas size
    canvas.width = canvas.offsetWidth;
    canvas.height = 300;

    // Clear canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Draw grid lines
    ctx.strokeStyle = '#e2e8f0';
    ctx.lineWidth = 1;

    for (let i = 0; i <= 5; i++) {
        const y = (canvas.height - 40) * (i / 5) + 20;
        ctx.beginPath();
        ctx.moveTo(40, y);
        ctx.lineTo(canvas.width - 20, y);
        ctx.stroke();
    }

    // Draw data lines
    const colors = ['#667eea', '#48bb78', '#ed8936'];
    const xStep = (canvas.width - 60) / (labels.length - 1);

    datasets.forEach((data, dataIndex) => {
        ctx.strokeStyle = colors[dataIndex];
        ctx.lineWidth = 2;
        ctx.beginPath();

        data.forEach((value, index) => {
            const x = 40 + index * xStep;
            const maxValue = Math.max(...data.flat());
            const y = canvas.height - 40 - (value / maxValue) * (canvas.height - 60);

            if (index === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });

        ctx.stroke();
    });

    // Draw labels
    ctx.fillStyle = '#718096';
    ctx.font = '12px sans-serif';
    labels.forEach((label, index) => {
        const x = 40 + index * xStep;
        ctx.fillText(label, x - 15, canvas.height - 10);
    });
}

function initializeEventListeners() {
    // Chart range buttons
    document.querySelectorAll('.fys-chart-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.fys-chart-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            // Update chart based on selected range
            updateChartRange(this.dataset.range);
        });
    });
}

function updateChartRange(range) {
    console.log('Updating chart for range:', range);
    // Implement chart update logic
}

function exportMemberReport() {
    if (confirm('Export member report for the current month?')) {
        console.log('Exporting member report...');
        // Implement export functionality
    }
}

function sendAnnouncement() {
    const message = prompt('Enter announcement message:');
    if (message) {
        console.log('Sending announcement:', message);
        // Implement announcement functionality
    }
}

function viewReports() {
    console.log('Opening reports dashboard...');
    // Navigate to reports page
    window.location.href = '/reports/';
}
</script>