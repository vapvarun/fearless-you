<?php
/**
 * Enhanced Frontend Program Candidate Dashboard Template
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
if (!current_user_can('dasher_pc')) {
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

// Get user's course progress data
$user_courses = learndash_user_get_enrolled_courses($current_user_id);
$total_courses = count($user_courses);
$completed_courses = 0;
$in_progress_courses = 0;
$total_hours_logged = 0;

foreach ($user_courses as $course_id) {
    if (learndash_course_completed($current_user_id, $course_id)) {
        $completed_courses++;
    } else {
        $progress = learndash_course_progress($current_user_id, $course_id);
        if ($progress['percentage'] > 0) {
            $in_progress_courses++;
        }
    }
}

// Get LCCP hours if available
$lccp_hours = get_user_meta($current_user_id, 'lccp_hours_tracked', true);
if ($lccp_hours) {
    $total_hours_logged = $lccp_hours;
}

// Calculate completion percentage
$completion_percentage = $total_courses > 0 ? round(($completed_courses / $total_courses) * 100) : 0;

// Get community messages
$community_messages = get_posts(array(
    'post_type' => 'dasher_message',
    'posts_per_page' => 10,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
    'meta_query' => array(
        array(
            'key' => '_dasher_message_target',
            'value' => 'pc',
            'compare' => '='
        )
    )
));

// Get recent achievements
$recent_completions = array();
foreach ($user_courses as $course_id) {
    if (learndash_course_completed($current_user_id, $course_id)) {
        $completion_date = learndash_user_get_course_completed_date($current_user_id, $course_id);
        if ($completion_date && (time() - $completion_date) < (30 * 24 * 60 * 60)) { // Last 30 days
            $recent_completions[] = array(
                'course_title' => get_the_title($course_id),
                'completion_date' => $completion_date,
                'course_id' => $course_id
            );
        }
    }
}

// Sort by completion date
usort($recent_completions, function($a, $b) {
    return $b['completion_date'] - $a['completion_date'];
});
?>

<div class="dasher-frontend-dashboard dasher-pc-dashboard-enhanced">
    <!-- Enhanced Header with Personal Welcome -->
    <div class="dasher-dashboard-header">
        <div class="dasher-header-content">
            <div class="dasher-user-greeting">
                <?php echo get_avatar($current_user_id, 80, '', '', array('class' => 'dasher-user-avatar-large')); ?>
                <div class="dasher-greeting-text">
                    <h2><?php echo sprintf(esc_html__('Welcome to Your Journey, %s!', 'dasher'), $current_user->display_name); ?></h2>
                    <p class="dasher-dashboard-description">
                        <?php esc_html_e('You\'re now part of our life coaching community. This is where your transformation begins.', 'dasher'); ?>
                    </p>
                </div>
            </div>
            <div class="dasher-header-actions">
                <?php echo Dasher_Dashboard_Customizer::render_customize_button(); ?>
                <button class="dasher-btn dasher-btn-primary" id="view-my-progress">
                    <i class="fas fa-chart-line"></i>
                    <?php esc_html_e('My Progress', 'dasher'); ?>
                </button>
                <button class="dasher-btn dasher-btn-outline" id="find-mentor">
                    <i class="fas fa-user-friends"></i>
                    <?php esc_html_e('Connect with Mentors', 'dasher'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Welcome Message Section -->
    <div class="dasher-welcome-section">
        <div class="dasher-welcome-banner">
            <div class="dasher-welcome-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="dasher-welcome-content">
                <h3><?php esc_html_e('Welcome to the Life Coach Community!', 'dasher'); ?></h3>
                <p><?php esc_html_e('As a Program Candidate, you\'re taking the first step toward becoming a certified life coach. Here you\'ll receive guidance, support, and inspiration from our experienced mentors and Big Birds.', 'dasher'); ?></p>
                <div class="dasher-welcome-stats">
                    <span class="dasher-stat-item">
                        <strong><?php echo esc_html($total_courses); ?></strong> 
                        <?php esc_html_e('Courses Available', 'dasher'); ?>
                    </span>
                    <span class="dasher-stat-item">
                        <strong><?php echo esc_html($completion_percentage); ?>%</strong> 
                        <?php esc_html_e('Complete', 'dasher'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Dashboard -->
    <div class="dasher-kpi-grid">
        <!-- My Learning Progress -->
        <div class="dasher-kpi-card primary" data-card-id="learning_progress">
            <div class="dasher-kpi-header">
                <div class="dasher-kpi-icon">
                    <i class="fas fa-book-open"></i>
                </div>
            </div>
            <div class="dasher-kpi-title"><?php esc_html_e('Learning Progress', 'dasher'); ?></div>
            <div class="dasher-kpi-value"><?php echo esc_html($completion_percentage); ?>%</div>
            <div class="dasher-kpi-breakdown">
                <div class="dasher-breakdown-item">
                    <span class="dasher-breakdown-label"><?php esc_html_e('Completed', 'dasher'); ?></span>
                    <span class="dasher-breakdown-value dasher-success"><?php echo esc_html($completed_courses); ?></span>
                </div>
                <div class="dasher-breakdown-item">
                    <span class="dasher-breakdown-label"><?php esc_html_e('In Progress', 'dasher'); ?></span>
                    <span class="dasher-breakdown-value dasher-primary"><?php echo esc_html($in_progress_courses); ?></span>
                </div>
                <div class="dasher-breakdown-item">
                    <span class="dasher-breakdown-label"><?php esc_html_e('Total', 'dasher'); ?></span>
                    <span class="dasher-breakdown-value"><?php echo esc_html($total_courses); ?></span>
                </div>
            </div>
            <div class="dasher-progress-bar">
                <div class="dasher-progress-fill" style="width: <?php echo esc_attr($completion_percentage); ?>%"></div>
            </div>
        </div>

        <!-- LCCP Hours -->
        <div class="dasher-kpi-card success" data-card-id="lccp_hours">
            <div class="dasher-kpi-header">
                <div class="dasher-kpi-icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <div class="dasher-kpi-title"><?php esc_html_e('LCCP Hours', 'dasher'); ?></div>
            <div class="dasher-kpi-value"><?php echo esc_html($total_hours_logged); ?></div>
            <div class="dasher-kpi-description">
                <?php esc_html_e('Hours logged toward certification', 'dasher'); ?>
            </div>
            <button class="dasher-kpi-action" onclick="logHours()">
                <?php esc_html_e('Log Hours', 'dasher'); ?>
            </button>
        </div>

        <!-- Community Engagement -->
        <div class="dasher-kpi-card info" data-card-id="community_messages">
            <div class="dasher-kpi-header">
                <div class="dasher-kpi-icon">
                    <i class="fas fa-comments"></i>
                </div>
            </div>
            <div class="dasher-kpi-title"><?php esc_html_e('Community Messages', 'dasher'); ?></div>
            <div class="dasher-kpi-value"><?php echo count($community_messages); ?></div>
            <div class="dasher-kpi-description">
                <?php esc_html_e('New messages from mentors', 'dasher'); ?>
            </div>
        </div>

        <!-- My Achievements -->
        <div class="dasher-kpi-card warning" data-card-id="achievements">
            <div class="dasher-kpi-header">
                <div class="dasher-kpi-icon">
                    <i class="fas fa-trophy"></i>
                </div>
            </div>
            <div class="dasher-kpi-title"><?php esc_html_e('Recent Achievements', 'dasher'); ?></div>
            <div class="dasher-kpi-value"><?php echo count($recent_completions); ?></div>
            <div class="dasher-kpi-description">
                <?php esc_html_e('Courses completed this month', 'dasher'); ?>
            </div>
        </div>
    </div>

    <!-- Community Message Stream -->
    <div class="dasher-data-section">
        <div class="dasher-section-header">
            <h3><?php esc_html_e('Community Message Stream', 'dasher'); ?></h3>
            <span class="dasher-section-badge">
                <?php echo sprintf(__('%d new messages', 'dasher'), count($community_messages)); ?>
            </span>
        </div>
        
        <div class="dasher-message-stream">
            <?php if (!empty($community_messages)) : ?>
                <?php foreach ($community_messages as $message) : 
                    $sender_id = get_post_meta($message->ID, '_dasher_message_sender', true);
                    $sender_role = get_post_meta($message->ID, '_dasher_message_sender_role', true);
                    $sender_user = get_user_by('ID', $sender_id);
                    $sender_name = $sender_user ? $sender_user->display_name : __('Community Team', 'dasher');
                    
                    $role_class = '';
                    $role_label = '';
                    switch ($sender_role) {
                        case 'dasher_mentor':
                            $role_class = 'mentor';
                            $role_label = __('Mentor', 'dasher');
                            break;
                        case 'dasher_bigbird':
                            $role_class = 'bigbird';
                            $role_label = __('Big Bird', 'dasher');
                            break;
                        default:
                            $role_class = 'admin';
                            $role_label = __('Admin', 'dasher');
                    }
                ?>
                <div class="dasher-message-bubble <?php echo esc_attr($role_class); ?>">
                    <div class="dasher-message-header">
                        <div class="dasher-message-avatar">
                            <?php echo get_avatar($sender_id, 50, '', '', array('class' => 'message-avatar')); ?>
                            <span class="dasher-role-badge <?php echo esc_attr($role_class); ?>"><?php echo esc_html($role_label); ?></span>
                        </div>
                        <div class="dasher-message-meta">
                            <h4 class="dasher-message-sender"><?php echo esc_html($sender_name); ?></h4>
                            <span class="dasher-message-date">
                                <?php echo human_time_diff(strtotime($message->post_date), current_time('timestamp')) . __(' ago', 'dasher'); ?>
                            </span>
                        </div>
                    </div>
                    <div class="dasher-message-content">
                        <h5 class="dasher-message-title"><?php echo esc_html($message->post_title); ?></h5>
                        <div class="dasher-message-text">
                            <?php echo wp_kses_post($message->post_content); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="dasher-empty-state">
                    <div class="dasher-empty-state-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <p class="dasher-empty-state-message"><?php esc_html_e('No community messages yet. Check back soon for updates from your mentors!', 'dasher'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Achievements -->
    <?php if (!empty($recent_completions)) : ?>
    <div class="dasher-data-section">
        <div class="dasher-section-header">
            <h3><?php esc_html_e('My Recent Achievements', 'dasher'); ?></h3>
        </div>
        
        <div class="dasher-achievements-list">
            <?php foreach (array_slice($recent_completions, 0, 5) as $completion) : ?>
            <div class="dasher-achievement-item">
                <div class="dasher-achievement-icon">
                    <i class="fas fa-certificate"></i>
                </div>
                <div class="dasher-achievement-content">
                    <h4 class="dasher-achievement-title"><?php echo esc_html($completion['course_title']); ?></h4>
                    <p class="dasher-achievement-description">
                        <?php esc_html_e('Course completed successfully', 'dasher'); ?>
                    </p>
                    <span class="dasher-achievement-date">
                        <?php echo human_time_diff($completion['completion_date'], current_time('timestamp')) . __(' ago', 'dasher'); ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// PC Dashboard JavaScript
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
            (title.textContent.includes('Program') || title.textContent.includes('Candidate') || title.textContent.includes('Dashboard'))) {
            title.style.display = 'none';
        }
    });
    // Initialize any PC-specific functionality
    console.log('PC Dashboard loaded successfully');
});

function logHours() {
    // Open hours logging interface
    openHourLoggingModal();
}

function openHourLoggingModal() {
    // Create modal if it doesn't exist
    if (!document.getElementById('hour-logging-modal')) {
        createHourLoggingModal();
    }
    
    // Show the modal
    document.getElementById('hour-logging-modal').style.display = 'block';
    document.body.classList.add('modal-open');
}

function createHourLoggingModal() {
    // Create modal HTML
    const modalHTML = `
        <div id="hour-logging-modal" class="lccp-modal" style="display: none;">
            <div class="lccp-modal-content">
                <div class="lccp-modal-header">
                    <h2>Log Coaching Hours</h2>
                    <span class="lccp-modal-close" onclick="closeHourLoggingModal()">&times;</span>
                </div>
                <div class="lccp-modal-body">
                    <div id="hour-logging-form-container">
                        <!-- Hour logging form will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Load the hour logging form via AJAX
    loadHourLoggingForm();
}

function loadHourLoggingForm() {
    // Use the existing hour tracker form
    const formContainer = document.getElementById('hour-logging-form-container');
    
    // Create a temporary element to render the shortcode
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = '<?php echo do_shortcode("[lccp-hour-form]"); ?>';
    
    // Move the form content to the modal
    formContainer.innerHTML = tempDiv.innerHTML;
    
    // Add modal-specific styling
    formContainer.querySelector('.lccp-hour-tracker-container').style.margin = '0';
    formContainer.querySelector('.lccp-hour-tracker-container').style.padding = '0';
}

function closeHourLoggingModal() {
    document.getElementById('hour-logging-modal').style.display = 'none';
    document.body.classList.remove('modal-open');
}

function viewProgress() {
    // Navigate to detailed progress view
    console.log('Viewing detailed progress...');
}

function connectWithMentors() {
    // Open mentor connection interface
    console.log('Opening mentor connection...');
}
</script>

<?php echo Dasher_Dashboard_Customizer::render_customizer_panel('pc'); ?>