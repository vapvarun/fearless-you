<?php
/**
 * Hour Tracker Frontend - Modern styled form and display
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Hour_Tracker_Frontend {
    
    public function __construct() {
        // Add shortcodes
        add_shortcode('lccp_hour_tracker', array($this, 'render_hour_tracker'));
        add_shortcode('lccp_hours_dashboard', array($this, 'render_hours_dashboard'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_lccp_submit_hours', array($this, 'ajax_submit_hours'));
        add_action('wp_ajax_lccp_get_hour_stats', array($this, 'ajax_get_hour_stats'));
        add_action('wp_ajax_lccp_delete_hour_entry', array($this, 'ajax_delete_hour_entry'));
    }
    
    public function enqueue_assets() {
        if (is_singular() && (has_shortcode(get_post()->post_content, 'lccp_hour_tracker') || has_shortcode(get_post()->post_content, 'lccp_hours_dashboard'))) {
            // Enqueue styles
            wp_enqueue_style('lccp-hour-tracker', LCCP_SYSTEMS_PLUGIN_URL . 'assets/css/hour-tracker.css', array(), LCCP_SYSTEMS_VERSION);
            
            // Enqueue scripts
            wp_enqueue_script('lccp-hour-tracker', LCCP_SYSTEMS_PLUGIN_URL . 'assets/js/hour-tracker.js', array('jquery'), LCCP_SYSTEMS_VERSION, true);
            
            // Localize script
            wp_localize_script('lccp-hour-tracker', 'lccp_hour_tracker', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('lccp_hour_tracker'),
                'user_id' => get_current_user_id()
            ));
        }
    }
    
    public function render_hour_tracker($atts) {
        if (!is_user_logged_in()) {
            return '<div class="lccp-notice lccp-notice-warning">Please log in to track your coaching hours.</div>';
        }
        
        $atts = shortcode_atts(array(
            'show_stats' => 'true',
            'show_log' => 'true'
        ), $atts);
        
        ob_start();
        ?>
        <div class="lccp-hour-tracker-container">
            <?php if ($atts['show_stats'] === 'true'): ?>
                <?php echo $this->render_stats_section(); ?>
            <?php endif; ?>
            
            <div class="lccp-hour-form-wrapper">
                <h2 class="lccp-section-title">Log Coaching Hours</h2>
                
                <form id="lccp-hour-tracker-form" class="lccp-modern-form">
                    <div class="lccp-form-row">
                        <div class="lccp-form-group lccp-col-half">
                            <label for="session_date">
                                <i class="dashicons dashicons-calendar-alt"></i>
                                Session Date
                            </label>
                            <input type="date" id="session_date" name="session_date" required 
                                   max="<?php echo date('Y-m-d'); ?>" 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="lccp-form-group lccp-col-half">
                            <label for="client_name">
                                <i class="dashicons dashicons-admin-users"></i>
                                Client Name
                            </label>
                            <input type="text" id="client_name" name="client_name" 
                                   placeholder="Enter client's name" required>
                        </div>
                    </div>
                    
                    <div class="lccp-form-group">
                        <label>
                            <i class="dashicons dashicons-clock"></i>
                            Session Duration
                        </label>
                        <div class="lccp-duration-selector">
                            <label class="lccp-duration-option">
                                <input type="radio" name="session_length" value="0.5" required>
                                <span class="lccp-duration-label">
                                    <span class="duration-time">30</span>
                                    <span class="duration-unit">min</span>
                                </span>
                            </label>
                            <label class="lccp-duration-option">
                                <input type="radio" name="session_length" value="1" required checked>
                                <span class="lccp-duration-label">
                                    <span class="duration-time">1</span>
                                    <span class="duration-unit">hour</span>
                                </span>
                            </label>
                            <label class="lccp-duration-option">
                                <input type="radio" name="session_length" value="1.5" required>
                                <span class="lccp-duration-label">
                                    <span class="duration-time">1.5</span>
                                    <span class="duration-unit">hours</span>
                                </span>
                            </label>
                            <label class="lccp-duration-option">
                                <input type="radio" name="session_length" value="2" required>
                                <span class="lccp-duration-label">
                                    <span class="duration-time">2</span>
                                    <span class="duration-unit">hours</span>
                                </span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="lccp-form-row">
                        <div class="lccp-form-group lccp-col-third">
                            <label for="session_number">
                                <i class="dashicons dashicons-tag"></i>
                                Session Number
                            </label>
                            <select id="session_number" name="session_number" required>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>">Session <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="lccp-form-group lccp-col-two-thirds">
                            <label for="session_type">
                                <i class="dashicons dashicons-category"></i>
                                Session Type
                            </label>
                            <select id="session_type" name="session_type">
                                <option value="individual">Individual Coaching</option>
                                <option value="group">Group Coaching</option>
                                <option value="practice">Practice Session</option>
                                <option value="mentor">Mentor Session</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="lccp-form-group">
                        <label for="notes">
                            <i class="dashicons dashicons-edit"></i>
                            Session Notes (Optional)
                        </label>
                        <textarea id="notes" name="notes" rows="4" 
                                  placeholder="Add any notes about the session..."></textarea>
                    </div>
                    
                    <div class="lccp-form-actions">
                        <button type="submit" class="lccp-btn lccp-btn-primary">
                            <i class="dashicons dashicons-plus-alt"></i>
                            Log Hours
                        </button>
                        <button type="reset" class="lccp-btn lccp-btn-secondary">
                            <i class="dashicons dashicons-dismiss"></i>
                            Clear Form
                        </button>
                    </div>
                </form>
                
                <div id="lccp-form-message" class="lccp-message" style="display: none;"></div>
            </div>
            
            <?php if ($atts['show_log'] === 'true'): ?>
                <?php echo $this->render_hour_log(); ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function render_stats_section() {
        $user_id = get_current_user_id();
        $total_hours = $this->get_user_total_hours($user_id);
        $this_month = $this->get_hours_this_month($user_id);
        $this_week = $this->get_hours_this_week($user_id);
        $tier_info = $this->get_tier_info($total_hours);
        
        ob_start();
        ?>
        <div class="lccp-stats-grid">
            <div class="lccp-stat-card lccp-stat-primary">
                <div class="lccp-stat-icon">
                    <i class="dashicons dashicons-awards"></i>
                </div>
                <div class="lccp-stat-content">
                    <div class="lccp-stat-value"><?php echo number_format($total_hours, 1); ?></div>
                    <div class="lccp-stat-label">Total Hours</div>
                    <div class="lccp-stat-progress">
                        <div class="lccp-progress-bar">
                            <div class="lccp-progress-fill" style="width: <?php echo min(100, ($total_hours / 75) * 100); ?>%"></div>
                        </div>
                        <div class="lccp-progress-text"><?php echo $tier_info['message']; ?></div>
                    </div>
                </div>
            </div>
            
            <div class="lccp-stat-card">
                <div class="lccp-stat-icon">
                    <i class="dashicons dashicons-calendar"></i>
                </div>
                <div class="lccp-stat-content">
                    <div class="lccp-stat-value"><?php echo number_format($this_month, 1); ?></div>
                    <div class="lccp-stat-label">This Month</div>
                </div>
            </div>
            
            <div class="lccp-stat-card">
                <div class="lccp-stat-icon">
                    <i class="dashicons dashicons-backup"></i>
                </div>
                <div class="lccp-stat-content">
                    <div class="lccp-stat-value"><?php echo number_format($this_week, 1); ?></div>
                    <div class="lccp-stat-label">This Week</div>
                </div>
            </div>
            
            <div class="lccp-stat-card">
                <div class="lccp-stat-icon">
                    <i class="dashicons dashicons-flag"></i>
                </div>
                <div class="lccp-stat-content">
                    <div class="lccp-stat-value"><?php echo $tier_info['tier']; ?></div>
                    <div class="lccp-stat-label">Current Level</div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function render_hour_log() {
        global $wpdb;
        $user_id = get_current_user_id();
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        $entries = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY session_date DESC, id DESC LIMIT 20",
            $user_id
        ));
        
        ob_start();
        ?>
        <div class="lccp-hour-log-wrapper">
            <h2 class="lccp-section-title">Recent Sessions</h2>
            
            <?php if ($entries): ?>
                <div class="lccp-table-responsive">
                    <table class="lccp-hour-log-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Client</th>
                                <th>Session</th>
                                <th>Duration</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries as $entry): ?>
                                <tr data-entry-id="<?php echo $entry->id; ?>">
                                    <td data-label="Date">
                                        <?php echo date('M j, Y', strtotime($entry->session_date)); ?>
                                    </td>
                                    <td data-label="Client"><?php echo esc_html($entry->client_name); ?></td>
                                    <td data-label="Session">#<?php echo esc_html($entry->session_number); ?></td>
                                    <td data-label="Duration">
                                        <span class="lccp-duration-badge">
                                            <?php echo number_format($entry->session_length, 1); ?> hrs
                                        </span>
                                    </td>
                                    <td data-label="Notes">
                                        <?php if ($entry->notes): ?>
                                            <span class="lccp-truncate" title="<?php echo esc_attr($entry->notes); ?>">
                                                <?php echo esc_html(substr($entry->notes, 0, 50)); ?>
                                                <?php echo strlen($entry->notes) > 50 ? '...' : ''; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="lccp-text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Actions">
                                        <button class="lccp-btn-icon lccp-delete-entry" 
                                                data-id="<?php echo $entry->id; ?>"
                                                title="Delete Entry">
                                            <i class="dashicons dashicons-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="lccp-empty-state">
                    <i class="dashicons dashicons-clock"></i>
                    <p>No coaching hours logged yet.</p>
                    <p>Start tracking your progress by logging your first session above!</p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function get_user_total_hours($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(session_length) FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        return $total ? floatval($total) : 0;
    }
    
    private function get_hours_this_month($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(session_length) FROM $table_name 
             WHERE user_id = %d AND MONTH(session_date) = MONTH(CURRENT_DATE()) 
             AND YEAR(session_date) = YEAR(CURRENT_DATE())",
            $user_id
        ));
        
        return $total ? floatval($total) : 0;
    }
    
    private function get_hours_this_week($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(session_length) FROM $table_name 
             WHERE user_id = %d AND session_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)",
            $user_id
        ));
        
        return $total ? floatval($total) : 0;
    }
    
    private function get_tier_info($hours) {
        $tiers = array(
            array('min' => 0, 'max' => 25, 'tier' => 'Beginner', 'next' => 'Associate Coach'),
            array('min' => 25, 'max' => 50, 'tier' => 'Associate', 'next' => 'Professional Coach'),
            array('min' => 50, 'max' => 75, 'tier' => 'Professional', 'next' => 'Master Coach'),
            array('min' => 75, 'max' => 999, 'tier' => 'Master', 'next' => null)
        );
        
        foreach ($tiers as $tier) {
            if ($hours >= $tier['min'] && $hours < $tier['max']) {
                $remaining = $tier['max'] - $hours;
                return array(
                    'tier' => $tier['tier'],
                    'next' => $tier['next'],
                    'remaining' => $remaining,
                    'message' => $tier['next'] ? 
                        sprintf('%s hours to %s', number_format($remaining, 1), $tier['next']) : 
                        'Certification Complete!'
                );
            }
        }
        
        return array('tier' => 'Master', 'next' => null, 'remaining' => 0, 'message' => 'Certification Complete!');
    }
    
    public function ajax_submit_hours() {
        check_ajax_referer('lccp_hour_tracker', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('You must be logged in to log hours.');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        $data = array(
            'user_id' => get_current_user_id(),
            'client_name' => sanitize_text_field($_POST['client_name']),
            'session_date' => sanitize_text_field($_POST['session_date']),
            'session_length' => floatval($_POST['session_length']),
            'session_number' => intval($_POST['session_number']),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
            'created_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($table_name, $data);
        
        if ($result) {
            $new_total = $this->get_user_total_hours(get_current_user_id());
            wp_send_json_success(array(
                'message' => 'Hours logged successfully!',
                'total_hours' => $new_total,
                'tier_info' => $this->get_tier_info($new_total)
            ));
        } else {
            wp_send_json_error('Failed to log hours. Please try again.');
        }
    }
    
    public function ajax_delete_hour_entry() {
        check_ajax_referer('lccp_hour_tracker', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('You must be logged in.');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        $entry_id = intval($_POST['entry_id']);
        $user_id = get_current_user_id();
        
        // Verify ownership
        $owner = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM $table_name WHERE id = %d",
            $entry_id
        ));
        
        if ($owner != $user_id && !current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to delete this entry.');
        }
        
        $result = $wpdb->delete($table_name, array('id' => $entry_id));
        
        if ($result) {
            wp_send_json_success('Entry deleted successfully.');
        } else {
            wp_send_json_error('Failed to delete entry.');
        }
    }
}

// Initialize
new LCCP_Hour_Tracker_Frontend();