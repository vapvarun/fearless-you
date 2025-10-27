<?php
/**
 * LCCP Hours Tracker Integration for Dasher Plugin
 * 
 * Integrates the LCCP Hours Tracker plugin with Dasher dashboards
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Dasher_LCCP_Integration {
    
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Only initialize if LCCP plugin is active
        if (!$this->is_lccp_active()) {
            return;
        }
        
        // Add AJAX handlers
        add_action('wp_ajax_dasher_get_lccp_stats', array($this, 'ajax_get_lccp_stats'));
        add_action('wp_ajax_dasher_get_student_lccp_data', array($this, 'ajax_get_student_lccp_data'));
        
        // Add shortcodes
        add_shortcode('dasher_pc_dashboard', array($this, 'render_pc_dashboard'));
        
        // Enqueue additional scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_lccp_scripts'));
        
        // Add admin menu items
        add_action('admin_menu', array($this, 'add_admin_menu_items'), 25);
        
        // Hook into existing dashboard rendering
        add_filter('dasher_mentor_dashboard_cards', array($this, 'add_lccp_mentor_cards'));
        add_filter('dasher_big_bird_dashboard_cards', array($this, 'add_lccp_big_bird_cards'));
    }
    
    /**
     * Check if LCCP Hours Tracker plugin is active
     */
    private function is_lccp_active() {
        return is_plugin_active('lccp-hours-tracker/lccp.php') || function_exists('lccp_display_hours_widget');
    }
    
    /**
     * Enqueue LCCP-specific scripts
     */
    public function enqueue_lccp_scripts() {
        wp_enqueue_script(
            'dasher-lccp-integration',
            plugin_dir_url(__FILE__) . '../assets/js/lccp-integration.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('dasher-lccp-integration', 'dasher_lccp', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dasher_lccp_nonce'),
            'strings' => array(
                'loading' => __('Loading...', 'dasher'),
                'error' => __('Error loading data', 'dasher'),
                'no_data' => __('No hours logged yet', 'dasher')
            )
        ));
    }
    
    /**
     * Add admin menu items
     */
    public function add_admin_menu_items() {
        // Add Program Candidate Dashboard
        add_submenu_page(
            'dasher-settings',
            'Program Candidate Dashboard',
            'PC Dashboard',
            'read',
            'dasher-pc-dashboard',
            array($this, 'render_admin_pc_dashboard')
        );
    }
    
    /**
     * Add LCCP cards to Mentor dashboard
     */
    public function add_lccp_mentor_cards($cards) {
        $lccp_stats = $this->get_overall_lccp_stats();
        
        $cards[] = array(
            'title' => 'LCCP Hours Overview',
            'icon' => 'dashicons-clock',
            'class' => 'warning',
            'content' => $this->render_lccp_mentor_card($lccp_stats)
        );
        
        return $cards;
    }
    
    /**
     * Add LCCP cards to Big Bird dashboard
     */
    public function add_lccp_big_bird_cards($cards) {
        $assigned_students = $this->get_assigned_students_lccp_data();
        
        $cards[] = array(
            'title' => 'Student LCCP Progress',
            'icon' => 'dashicons-chart-area',
            'class' => 'info',
            'content' => $this->render_lccp_big_bird_card($assigned_students)
        );
        
        return $cards;
    }
    
    /**
     * Get overall LCCP statistics
     */
    private function get_overall_lccp_stats() {
        global $wpdb;
        
        // Get all users with Program Candidate role
        $pc_users = get_users(array(
            'meta_key' => $wpdb->prefix . 'capabilities',
            'meta_value' => 'dasher_pc',
            'meta_compare' => 'LIKE'
        ));
        
        $stats = array(
            'total_students' => count($pc_users),
            'total_hours' => 0,
            'avg_hours' => 0,
            'tier_distribution' => array(
                'CFLC' => 0,  // 75+ hours
                'ACFLC' => 0, // 150+ hours
                'CFT' => 0,   // 250+ hours
                'MCFLC' => 0  // 500+ hours
            ),
            'students_by_tier' => array()
        );
        
        foreach ($pc_users as $user) {
            $user_hours = (float) get_user_meta($user->ID, 'lccp_hours_tracked', true);
            $stats['total_hours'] += $user_hours;
            
            $tier = lccp_get_tier($user_hours);
            $tier_abbr = $tier['abbr'];
            
            if (isset($stats['tier_distribution'][$tier_abbr])) {
                $stats['tier_distribution'][$tier_abbr]++;
            }
            
            $stats['students_by_tier'][] = array(
                'user_id' => $user->ID,
                'display_name' => $user->display_name,
                'hours' => $user_hours,
                'tier' => $tier
            );
        }
        
        $stats['avg_hours'] = $stats['total_students'] > 0 ? $stats['total_hours'] / $stats['total_students'] : 0;
        
        return $stats;
    }
    
    /**
     * Get LCCP data for assigned students (Big Bird view)
     */
    private function get_assigned_students_lccp_data() {
        global $wpdb;
        
        $current_user_id = get_current_user_id();
        $assignments_table = $wpdb->prefix . 'dasher_student_assignments';
        
        // Get assigned students
        $assigned_students = $wpdb->get_results($wpdb->prepare(
            "SELECT student_id FROM $assignments_table WHERE big_bird_id = %d",
            $current_user_id
        ));
        
        $students_data = array();
        
        foreach ($assigned_students as $assignment) {
            $user = get_user_by('ID', $assignment->student_id);
            if ($user) {
                $hours = (float) get_user_meta($user->ID, 'lccp_hours_tracked', true);
                $tier = lccp_get_tier($hours);
                
                // Get recent sessions
                $recent_sessions = $this->get_recent_sessions($user->ID, 5);
                
                $students_data[] = array(
                    'user_id' => $user->ID,
                    'display_name' => $user->display_name,
                    'email' => $user->user_email,
                    'hours' => $hours,
                    'tier' => $tier,
                    'recent_sessions' => $recent_sessions,
                    'progress_percentage' => $this->calculate_progress_percentage($hours)
                );
            }
        }
        
        return $students_data;
    }
    
    /**
     * Get recent sessions for a user
     */
    private function get_recent_sessions($user_id, $limit = 5) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE user_id = %d 
             ORDER BY session_date DESC 
             LIMIT %d",
            $user_id,
            $limit
        ), ARRAY_A);
    }
    
    /**
     * Calculate progress percentage towards next tier
     */
    private function calculate_progress_percentage($hours) {
        $tier_thresholds = array(75, 150, 250, 500);
        
        foreach ($tier_thresholds as $threshold) {
            if ($hours < $threshold) {
                $previous_threshold = 0;
                foreach ($tier_thresholds as $prev) {
                    if ($prev < $threshold) {
                        $previous_threshold = $prev;
                    } else {
                        break;
                    }
                }
                
                $progress = (($hours - $previous_threshold) / ($threshold - $previous_threshold)) * 100;
                return min(100, max(0, $progress));
            }
        }
        
        return 100; // Max tier achieved
    }
    
    /**
     * Render LCCP card for Mentor dashboard
     */
    private function render_lccp_mentor_card($stats) {
        ob_start();
        ?>
        <div class="dasher-card-value"><?php echo number_format($stats['total_hours'], 1); ?></div>
        <div class="dasher-card-description">Total hours logged by <?php echo $stats['total_students']; ?> students</div>
        <div class="dasher-card-stats">
            <div class="stat-item">
                <span class="stat-label">Avg Hours:</span>
                <span class="stat-value"><?php echo number_format($stats['avg_hours'], 1); ?></span>
            </div>
        </div>
        <div class="tier-distribution">
            <h4>Tier Distribution:</h4>
            <div class="tier-stats">
                <?php foreach ($stats['tier_distribution'] as $tier => $count): ?>
                    <div class="tier-stat">
                        <span class="tier-name"><?php echo esc_html($tier); ?>:</span>
                        <span class="tier-count"><?php echo $count; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="dasher-card-actions">
            <a href="<?php echo admin_url('admin.php?page=dasher-pc-dashboard'); ?>" class="dasher-btn secondary">
                View All Students
            </a>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render LCCP card for BigBird dashboard
     */
    private function render_lccp_big_bird_card($students) {
        ob_start();
        ?>
        <div class="dasher-card-value"><?php echo count($students); ?></div>
        <div class="dasher-card-description">Students with LCCP progress</div>
        
        <?php if (!empty($students)): ?>
            <div class="student-lccp-list">
                <?php foreach ($students as $student): ?>
                    <div class="student-lccp-item">
                        <div class="student-info">
                            <strong><?php echo esc_html($student['display_name']); ?></strong>
                            <span class="hours-count"><?php echo number_format($student['hours'], 1); ?> hrs</span>
                            <span class="tier-badge tier-<?php echo strtolower($student['tier']['abbr']); ?>">
                                <?php echo esc_html($student['tier']['abbr']); ?>
                            </span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $student['progress_percentage']; ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No assigned students with LCCP hours yet.</p>
        <?php endif; ?>
        
        <div class="dasher-card-actions">
            <button class="dasher-btn secondary" onclick="refreshLCCPData()">
                Refresh Data
            </button>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render Program Candidate Dashboard
     */
    public function render_pc_dashboard($atts = array()) {
        if (!is_user_logged_in()) {
            return '<p>Please log in to view your dashboard.</p>';
        }
        
        $current_user_id = get_current_user_id();
        $user_hours = (float) get_user_meta($current_user_id, 'lccp_hours_tracked', true);
        $tier_info = lccp_get_tier($user_hours);
        $recent_sessions = $this->get_recent_sessions($current_user_id, 10);
        $progress_percentage = $this->calculate_progress_percentage($user_hours);
        
        ob_start();
        ?>
        <div class="dasher-pc-dashboard">
            <div class="dashboard-header">
                <h2>Program Candidate Dashboard</h2>
                <p>Welcome, <?php echo esc_html(wp_get_current_user()->display_name); ?>!</p>
            </div>
            
            <div class="dashboard-cards">
                <!-- LCCP Hours Card -->
                <div class="dasher-card primary lccp-main-card">
                    <div class="dasher-card-icon">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                    <div class="dasher-card-title">LCCP Hours Tracked</div>
                    <div class="dasher-card-value"><?php echo number_format($user_hours, 1); ?></div>
                    <div class="dasher-card-description">
                        Working towards: <strong><?php echo esc_html($tier_info['full']); ?></strong>
                    </div>
                    <div class="dasher-progress-bar">
                        <div class="dasher-progress-fill" style="width: <?php echo $progress_percentage; ?>%"></div>
                    </div>
                    <div class="tier-badge tier-<?php echo strtolower($tier_info['abbr']); ?>">
                        <?php echo esc_html($tier_info['abbr']); ?>
                    </div>
                </div>
                
                <!-- Quick Log Hours Card -->
                <div class="dasher-card success">
                    <div class="dasher-card-icon">
                        <span class="dashicons dashicons-plus-alt"></span>
                    </div>
                    <div class="dasher-card-title">Log New Session</div>
                    <div class="dasher-card-description">Record your latest coaching session</div>
                    <div class="dasher-card-actions">
                        <button class="dasher-btn primary" onclick="toggleLogForm()">
                            Log Hours
                        </button>
                    </div>
                </div>
                
                <!-- Recent Sessions Card -->
                <div class="dasher-card info">
                    <div class="dasher-card-icon">
                        <span class="dashicons dashicons-list-view"></span>
                    </div>
                    <div class="dasher-card-title">Recent Sessions</div>
                    <div class="dasher-card-value"><?php echo count($recent_sessions); ?></div>
                    <div class="dasher-card-description">Sessions logged</div>
                    <div class="dasher-card-actions">
                        <button class="dasher-btn secondary" onclick="viewAllSessions()">
                            View All
                        </button>
                    </div>
                </div>
                
                <!-- Progress to Next Tier Card -->
                <div class="dasher-card warning">
                    <div class="dasher-card-icon">
                        <span class="dashicons dashicons-chart-line"></span>
                    </div>
                    <div class="dasher-card-title">Progress to Next Tier</div>
                    <div class="dasher-card-value"><?php echo round($progress_percentage); ?>%</div>
                    <div class="dasher-card-description">
                        <?php echo $this->get_next_tier_message($user_hours); ?>
                    </div>
                    <div class="dasher-progress-bar">
                        <div class="dasher-progress-fill" style="width: <?php echo $progress_percentage; ?>%"></div>
                    </div>
                </div>
            </div>
            
            <!-- Hour Logging Form (Hidden by default) -->
            <div id="lccp-log-form" class="lccp-log-form" style="display: none;">
                <div class="form-container">
                    <h3>Log Coaching Session</h3>
                    <?php echo do_shortcode('[lccp-hour-tracker-form]'); ?>
                </div>
            </div>
            
            <!-- Recent Sessions List -->
            <div class="recent-sessions-section">
                <h3>Recent Sessions</h3>
                <?php if (!empty($recent_sessions)): ?>
                    <div class="sessions-table">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Client</th>
                                    <th>Duration</th>
                                    <th>Session #</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_sessions as $session): ?>
                                    <tr>
                                        <td><?php echo esc_html(date('M j, Y', strtotime($session['session_date']))); ?></td>
                                        <td><?php echo esc_html($session['client_name']); ?></td>
                                        <td><?php echo esc_html($session['session_length']); ?> hrs</td>
                                        <td><?php echo esc_html($session['session_number']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No sessions logged yet. <a href="#" onclick="toggleLogForm()">Log your first session!</a></p>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .dasher-pc-dashboard {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .dashboard-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .lccp-main-card {
            position: relative;
        }
        
        .tier-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
            color: white;
        }
        
        .tier-cflc { background-color: #28a745; }
        .tier-acflc { background-color: #007bff; }
        .tier-cft { background-color: #ffc107; color: #000; }
        .tier-mcflc { background-color: #dc3545; }
        
        .lccp-log-form {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .sessions-table {
            margin-top: 15px;
        }
        
        .recent-sessions-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        </style>
        
        <script>
        function toggleLogForm() {
            const form = document.getElementById('lccp-log-form');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
        
        function viewAllSessions() {
            // Expand or scroll to sessions table
            document.querySelector('.recent-sessions-section').scrollIntoView({ behavior: 'smooth' });
        }
        
        function refreshLCCPData() {
            location.reload();
        }
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get message for next tier progress
     */
    private function get_next_tier_message($hours) {
        if ($hours < 75) {
            return (75 - $hours) . " hours to CFLC certification";
        } elseif ($hours < 150) {
            return (150 - $hours) . " hours to ACFLC certification";
        } elseif ($hours < 250) {
            return (250 - $hours) . " hours to CFT certification";
        } elseif ($hours < 500) {
            return (500 - $hours) . " hours to MCFLC certification";
        } else {
            return "Master tier achieved!";
        }
    }
    
    /**
     * AJAX: Get LCCP statistics
     */
    public function ajax_get_lccp_stats() {
        check_ajax_referer('dasher_lccp_nonce', 'nonce');
        
        if (!current_user_can('dasher_mentor') && !current_user_can('administrator')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Access denied')));
        }
        
        $stats = $this->get_overall_lccp_stats();
        
        wp_die(json_encode(array(
            'success' => true,
            'data' => $stats
        )));
    }
    
    /**
     * AJAX: Get student LCCP data
     */
    public function ajax_get_student_lccp_data() {
        check_ajax_referer('dasher_lccp_nonce', 'nonce');
        
        $student_id = intval($_POST['student_id']);
        
        if (!current_user_can('dasher_mentor') && !current_user_can('dasher_bigbird') && get_current_user_id() != $student_id) {
            wp_die(json_encode(array('success' => false, 'message' => 'Access denied')));
        }
        
        $hours = (float) get_user_meta($student_id, 'lccp_hours_tracked', true);
        $tier = lccp_get_tier($hours);
        $recent_sessions = $this->get_recent_sessions($student_id, 10);
        
        wp_die(json_encode(array(
            'success' => true,
            'data' => array(
                'hours' => $hours,
                'tier' => $tier,
                'recent_sessions' => $recent_sessions,
                'progress_percentage' => $this->calculate_progress_percentage($hours)
            )
        )));
    }
    
    /**
     * Render admin PC dashboard
     */
    public function render_admin_pc_dashboard() {
        echo '<div class="wrap">';
        echo '<h1>Program Candidate Dashboard</h1>';
        echo '<p>Use shortcode: <code>[dasher_pc_dashboard]</code> to display the Program Candidate dashboard on any page.</p>';
        echo '<hr>';
        echo do_shortcode('[dasher_pc_dashboard]');
        echo '</div>';
    }
}

// Initialize the integration
Dasher_LCCP_Integration::getInstance();