<?php
/**
 * Hour Tracker Module for LCCP Systems
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Hour_Tracker {
    
    public function __construct() {
        // Register widget
        add_action('widgets_init', array($this, 'register_widget'));
        
        // Register shortcodes
        add_shortcode('lccp-hour-widget', array($this, 'display_hours_widget'));
        add_shortcode('lccp-hour-form', array($this, 'hour_tracker_form'));
        add_shortcode('lccp-hour-log', array($this, 'display_hour_log'));
        
        // AJAX handlers
        add_action('wp_ajax_lccp_log_hours', array($this, 'ajax_log_hours'));
        add_action('wp_ajax_lccp_get_hours_list', array($this, 'ajax_get_hours_list'));
        add_action('wp_ajax_lccp_get_hour_entry', array($this, 'ajax_get_hour_entry'));
        add_action('wp_ajax_lccp_delete_hour', array($this, 'ajax_delete_hour'));
        add_action('wp_ajax_lccp_get_hour_details', array($this, 'ajax_get_hour_details'));
        add_action('wp_ajax_lccp_export_hours', array($this, 'ajax_export_hours'));
        add_action('wp_ajax_lccp_delete_entry', array($this, 'ajax_delete_entry'));
        
        // Process form submissions
        add_action('init', array($this, 'process_hour_form'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    public function register_widget() {
        register_widget('LCCP_Hours_Widget');
    }
    
    public function enqueue_scripts() {
        // Only enqueue on pages with our shortcodes
        global $post;
        if (!is_a($post, 'WP_Post')) {
            return;
        }
        
        if (has_shortcode($post->post_content, 'lccp-hour-form') || 
            has_shortcode($post->post_content, 'lccp-hour-log') ||
            has_shortcode($post->post_content, 'lccp-hour-widget')) {
            
            // Enqueue styles
            wp_enqueue_style(
                'lccp-hour-tracker',
                plugin_dir_url(dirname(__FILE__)) . 'assets/css/hour-tracker.css',
                array(),
                '1.0.0'
            );
            
            // Enqueue scripts
            wp_enqueue_script(
                'lccp-hour-tracker',
                plugin_dir_url(dirname(__FILE__)) . 'assets/js/hour-tracker.js',
                array('jquery'),
                '1.0.0',
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('lccp-hour-tracker', 'lccp_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('lccp_hour_tracker')
            ));
        }
    }
    
    public function display_hours_widget() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return '<p>Please log in to view your hours.</p>';
        }
        
        $total_hours = $this->get_user_total_hours($user_id);
        $tier_details = $this->get_tier($total_hours);
        
        ob_start();
        ?>
        <div class="lccp-hours-widget" style="border: 1px solid #ddd; padding: 15px; border-radius: 8px; background: #f9f9f9; max-width: 300px; text-align: center;">
            <div class="hours-count" style="font-size: 2em; font-weight: bold; padding-bottom: .3em">
                <?php echo number_format($total_hours, 1); ?>
            </div>
            <div class="hours-label" style="font-size: 1.2em; color: #333; margin-bottom: 10px;">
                HOURS
            </div>
            <div class="tier-info" style="font-size: 1em; color: #555;">
                On your way to earning:<br />
                <strong><?php echo esc_html($tier_details['full']); ?></strong>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function hour_tracker_form() {
        if (!is_user_logged_in()) {
            return '<p class="lccp-login-notice">Please log in to track your coaching hours.</p>';
        }
        
        // Include the styled form template
        $template_path = plugin_dir_path(dirname(__FILE__)) . 'templates/hour-tracker-form.php';
        
        // Check if template exists
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
        
        // Fallback to basic form if template doesn't exist
        ob_start();
        ?>
        <div class="lccp-hour-tracker-container">
            <form id="lccp-hour-tracker-form" method="POST" class="lccp-hour-form">
                <?php wp_nonce_field('lccp_hour_tracker_nonce', 'lccp_nonce'); ?>
                <input type="hidden" name="lccp_action" value="log_hours">
                
                <div class="form-section">
                    <h3>Session Information</h3>
                    
                    <div class="field-group">
                        <label for="session_date">Session Date <span class="required">*</span></label>
                        <input type="date" id="session_date" name="session_date" required max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="field-group">
                        <label for="session_type">Session Type <span class="required">*</span></label>
                        <select id="session_type" name="session_type" required>
                            <option value="">Select Type</option>
                            <?php 
                            $default_types = array('Individual Client', 'Group Session', 'Practice Session', 'Mentor Coaching');
                            $session_types = get_option('lccp_hour_tracker_session_types', $default_types);
                            foreach ($session_types as $type): ?>
                                <option value="<?php echo esc_attr(sanitize_title($type)); ?>"><?php echo esc_html($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="field-group">
                        <label for="client_name">Client Name <span class="required">*</span></label>
                        <input type="text" id="client_name" name="client_name" required>
                    </div>
                    
                    <div class="field-group duration-group">
                        <label>Session Duration <span class="required">*</span></label>
                        <div class="duration-inputs">
                            <input type="number" id="hours" name="hours" min="0" max="10" placeholder="Hours" class="duration-input">
                            <span class="duration-separator">:</span>
                            <input type="number" id="minutes" name="minutes" min="0" max="59" placeholder="Minutes" class="duration-input">
                        </div>
                        <span class="duration-display">0 hours</span>
                    </div>
                    
                    <div class="field-group">
                        <label for="session_number">Session Number</label>
                        <input type="number" id="session_number" name="session_number" min="1" max="99" value="1">
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Session Details</h3>
                    
                    <div class="field-group">
                        <label for="session_focus">Session Focus <span class="required">*</span></label>
                        <textarea id="session_focus" name="session_focus" rows="3" required placeholder="What was the main focus of this session?"></textarea>
                    </div>
                    
                    <div class="field-group">
                        <label for="key_insights">Key Insights <span class="required">*</span></label>
                        <textarea id="key_insights" name="key_insights" rows="3" required placeholder="What were your key learnings or insights?"></textarea>
                    </div>
                    
                    <div class="field-group">
                        <label for="notes">Additional Notes</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Any additional notes or observations"></textarea>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-submit btn-primary">Log Hours</button>
                    <button type="button" class="btn-reset">Clear Form</button>
                </div>
            </form>
        </div>
        
        <div id="lccp-form-message"></div>
        <?php
        return ob_get_clean();
    }
    
    public function display_hour_log() {
        if (!is_user_logged_in()) {
            return '<p>Please log in to view your hour log.</p>';
        }
        
        global $wpdb;
        $user_id = get_current_user_id();
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        $entries = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY session_date DESC, id DESC",
            $user_id
        ));
        
        ob_start();
        ?>
        <div class="lccp-hour-log">
            <h3>Your Hour Tracking Log</h3>
            <?php if ($entries): ?>
                <table class="lccp-log-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="text-align: left; padding: 8px; border-bottom: 2px solid #ddd;">Date</th>
                            <th style="text-align: left; padding: 8px; border-bottom: 2px solid #ddd;">Client</th>
                            <th style="text-align: left; padding: 8px; border-bottom: 2px solid #ddd;">Session #</th>
                            <th style="text-align: left; padding: 8px; border-bottom: 2px solid #ddd;">Hours</th>
                            <th style="text-align: left; padding: 8px; border-bottom: 2px solid #ddd;">Notes</th>
                            <th style="text-align: left; padding: 8px; border-bottom: 2px solid #ddd;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $entry): ?>
                            <tr data-entry-id="<?php echo $entry->id; ?>">
                                <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo esc_html($entry->session_date); ?></td>
                                <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo esc_html($entry->client_name); ?></td>
                                <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo esc_html($entry->session_number); ?></td>
                                <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo esc_html($entry->session_length); ?></td>
                                <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo esc_html($entry->notes ?? ''); ?></td>
                                <td style="padding: 8px; border-bottom: 1px solid #eee;">
                                    <button class="lccp-delete-entry button button-small" data-id="<?php echo $entry->id; ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="padding: 8px; font-weight: bold;">Total Hours:</td>
                            <td style="padding: 8px; font-weight: bold;">
                                <?php echo number_format($this->get_user_total_hours($user_id), 1); ?>
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            <?php else: ?>
                <p>No hours logged yet.</p>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.lccp-delete-entry').click(function() {
                if (!confirm('Are you sure you want to delete this entry?')) return;
                
                var button = $(this);
                var entryId = button.data('id');
                
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'lccp_delete_entry',
                    entry_id: entryId,
                    nonce: '<?php echo wp_create_nonce('lccp_delete_entry'); ?>'
                }, function(response) {
                    if (response.success) {
                        button.closest('tr').fadeOut(function() {
                            $(this).remove();
                            location.reload(); // Refresh to update totals
                        });
                    } else {
                        alert('Error deleting entry: ' + response.data);
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    public function process_hour_form() {
        if (!isset($_POST['lccp_action']) || $_POST['lccp_action'] !== 'log_hours') {
            return;
        }
        
        if (!wp_verify_nonce($_POST['lccp_nonce'], 'lccp_hour_tracker_nonce')) {
            return;
        }
        
        $this->log_hours($_POST);
    }
    
    private function log_hours($data) {
        global $wpdb;
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return false;
        }
        
        // Get duration limits from settings
        $min_duration = floatval(get_option('lccp_hour_tracker_min_duration', 0.25));
        $max_duration = floatval(get_option('lccp_hour_tracker_max_duration', 10));
        
        $session_length = floatval($data['session_length']);
        
        // Validate session duration
        if ($session_length < $min_duration || $session_length > $max_duration) {
            return false; // Session duration out of allowed range
        }
        
        // Check if approval is required
        $approval_required = get_option('lccp_hour_tracker_approval_required', 0);
        
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'client_name' => sanitize_text_field($data['client_name']),
                'session_type' => sanitize_text_field($data['session_type'] ?? ''),
                'session_date' => sanitize_text_field($data['session_date']),
                'session_length' => $session_length,
                'session_number' => intval($data['session_number']),
                'notes' => sanitize_textarea_field($data['notes'] ?? ''),
                'created_at' => current_time('mysql'),
                'status' => $approval_required ? 'pending' : 'approved'
            )
        );
        
        if ($result) {
            // Update user meta with total hours
            $this->update_user_total_hours($user_id);
            
            // Send email notification if enabled
            if (get_option('lccp_hour_tracker_notifications', 1)) {
                $this->send_hour_notification($user_id, $data);
            }
            
            // Redirect to prevent resubmission
            wp_redirect(add_query_arg('hours_logged', 'success', wp_get_referer()));
            exit;
        }
        
        return $result;
    }
    
    public function ajax_log_hours() {
        check_ajax_referer('lccp_hour_tracker_nonce', 'nonce');
        
        $result = $this->log_hours($_POST);
        
        if ($result) {
            wp_send_json_success('Hours logged successfully');
        } else {
            wp_send_json_error('Failed to log hours');
        }
    }
    
    public function ajax_delete_entry() {
        check_ajax_referer('lccp_delete_entry', 'nonce');
        
        global $wpdb;
        $user_id = get_current_user_id();
        $entry_id = intval($_POST['entry_id']);
        
        if (!$user_id || !$entry_id) {
            wp_send_json_error('Invalid request');
        }
        
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        // Verify ownership
        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
            $entry_id,
            $user_id
        ));
        
        if (!$entry) {
            wp_send_json_error('Entry not found');
        }
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $entry_id, 'user_id' => $user_id)
        );
        
        if ($result) {
            $this->update_user_total_hours($user_id);
            wp_send_json_success('Entry deleted');
        } else {
            wp_send_json_error('Failed to delete entry');
        }
    }
    
    private function get_user_total_hours($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(session_length) FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        return floatval($total ?: 0);
    }
    
    private function update_user_total_hours($user_id) {
        $total_hours = $this->get_user_total_hours($user_id);
        update_user_meta($user_id, 'lccp_hours_tracked', $total_hours);
        return $total_hours;
    }
    
    private function send_hour_notification($user_id, $data) {
        $user = get_userdata($user_id);
        $admin_email = get_option('lccp_system_email', get_option('admin_email'));
        
        $subject = sprintf('[LCCP] New Hours Logged by %s', $user->display_name);
        
        $message = sprintf(
            "New coaching hours have been logged:\n\n" .
            "Student: %s\n" .
            "Email: %s\n" .
            "Client: %s\n" .
            "Session Date: %s\n" .
            "Duration: %s hours\n" .
            "Session Number: %d\n" .
            "Notes: %s\n\n" .
            "Total Hours: %.1f\n" .
            "Current Tier: %s\n\n" .
            "View all submissions: %s",
            $user->display_name,
            $user->user_email,
            sanitize_text_field($data['client_name']),
            sanitize_text_field($data['session_date']),
            floatval($data['session_length']),
            intval($data['session_number']),
            sanitize_textarea_field($data['notes'] ?? 'N/A'),
            $this->get_user_total_hours($user_id),
            $this->get_tier($this->get_user_total_hours($user_id))['full'],
            admin_url('admin.php?page=lccp-hour-tracker')
        );
        
        // Send to admin
        wp_mail($admin_email, $subject, $message);
        
        // If approval is required, also send to mentor if assigned
        if (get_option('lccp_hour_tracker_approval_required', 0)) {
            $mentor_id = get_user_meta($user_id, 'assigned_mentor', true);
            if ($mentor_id) {
                $mentor = get_userdata($mentor_id);
                if ($mentor) {
                    wp_mail($mentor->user_email, $subject . ' (Approval Required)', $message);
                }
            }
        }
    }
    
    public function get_tier($hours) {
        // Get customizable tier levels from settings
        $default_tiers = array(
            array('hours' => 75, 'name' => 'Certified Fearless Living Coach', 'abbr' => 'CFLC'),
            array('hours' => 150, 'name' => 'Advanced Certified Fearless Living Coach', 'abbr' => 'ACFLC'),
            array('hours' => 250, 'name' => 'Certified Fearless Trainer', 'abbr' => 'CFT'),
            array('hours' => 500, 'name' => 'Master Certified Fearless Living Coach', 'abbr' => 'MCFLC')
        );
        
        $tiers = get_option('lccp_hour_tracker_tier_levels', $default_tiers);
        
        // Sort tiers by hours in descending order
        usort($tiers, function($a, $b) {
            return $b['hours'] - $a['hours'];
        });
        
        // Find the appropriate tier based on hours
        foreach ($tiers as $tier) {
            if ($hours >= $tier['hours']) {
                return array(
                    'full' => $tier['name'],
                    'abbr' => $tier['abbr']
                );
            }
        }
        
        // Return the lowest tier if no match (shouldn't happen if configured properly)
        $lowest_tier = end($tiers);
        return array(
            'full' => $lowest_tier['name'],
            'abbr' => $lowest_tier['abbr']
        );
    }
    
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>LCCP Hour Tracker</h1>
            
            <div class="lccp-admin-stats">
                <h2>Program Statistics</h2>
                <?php $this->display_program_stats(); ?>
            </div>
            
            <div class="lccp-admin-recent">
                <h2>Recent Hour Entries</h2>
                <?php $this->display_recent_entries(); ?>
            </div>
        </div>
        <?php
    }
    
    private function display_program_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(DISTINCT user_id) as total_users,
                COUNT(*) as total_entries,
                SUM(session_length) as total_hours,
                AVG(session_length) as avg_session_length
            FROM $table_name
        ");
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <tr>
                <th>Total Students Tracking</th>
                <td><?php echo intval($stats->total_users); ?></td>
            </tr>
            <tr>
                <th>Total Sessions Logged</th>
                <td><?php echo intval($stats->total_entries); ?></td>
            </tr>
            <tr>
                <th>Total Hours Tracked</th>
                <td><?php echo number_format(floatval($stats->total_hours), 1); ?></td>
            </tr>
            <tr>
                <th>Average Session Length</th>
                <td><?php echo number_format(floatval($stats->avg_session_length), 1); ?> hours</td>
            </tr>
        </table>
        <?php
    }
    
    private function display_recent_entries() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        $entries = $wpdb->get_results("
            SELECT h.*, u.display_name 
            FROM $table_name h
            LEFT JOIN {$wpdb->users} u ON h.user_id = u.ID
            ORDER BY h.created_at DESC
            LIMIT 20
        ");
        
        if ($entries) {
            ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Hours</th>
                        <th>Session #</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td><?php echo esc_html($entry->display_name); ?></td>
                            <td><?php echo esc_html($entry->client_name); ?></td>
                            <td><?php echo esc_html($entry->session_date); ?></td>
                            <td><?php echo esc_html($entry->session_length); ?></td>
                            <td><?php echo esc_html($entry->session_number); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        } else {
            echo '<p>No entries yet.</p>';
        }
    }
    
    /**
     * AJAX handler for getting hours list
     */
    public function ajax_get_hours_list() {
        check_ajax_referer('lccp_hour_tracker', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Not logged in');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        // Get filters
        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        
        $where_clauses = array("user_id = %d");
        $where_values = array($user_id);
        
        if (!empty($filters['start_date'])) {
            $where_clauses[] = "session_date >= %s";
            $where_values[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $where_clauses[] = "session_date <= %s";
            $where_values[] = $filters['end_date'];
        }
        
        if (!empty($filters['session_type'])) {
            $where_clauses[] = "session_type = %s";
            $where_values[] = $filters['session_type'];
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        $query = "SELECT * FROM $table_name WHERE $where_sql ORDER BY session_date DESC, id DESC";
        $entries = $wpdb->get_results($wpdb->prepare($query, $where_values));
        
        ob_start();
        if ($entries) {
            foreach ($entries as $entry) {
                ?>
                <div class="hour-entry" data-id="<?php echo $entry->id; ?>">
                    <div class="entry-date"><?php echo esc_html($entry->session_date); ?></div>
                    <div class="entry-client"><?php echo esc_html($entry->client_name); ?></div>
                    <div class="entry-hours"><?php echo esc_html($entry->session_length); ?> hours</div>
                    <div class="entry-actions">
                        <button class="btn-edit-hour" data-id="<?php echo $entry->id; ?>">Edit</button>
                        <button class="btn-delete-hour" data-id="<?php echo $entry->id; ?>">Delete</button>
                    </div>
                </div>
                <?php
            }
        } else {
            echo '<p>No hours found matching your filters.</p>';
        }
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * AJAX handler for getting single hour entry
     */
    public function ajax_get_hour_entry() {
        check_ajax_referer('lccp_hour_tracker', 'nonce');
        
        $user_id = get_current_user_id();
        $hour_id = intval($_POST['hour_id']);
        
        if (!$user_id || !$hour_id) {
            wp_send_json_error('Invalid request');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
            $hour_id,
            $user_id
        ), ARRAY_A);
        
        if ($entry) {
            wp_send_json_success($entry);
        } else {
            wp_send_json_error('Entry not found');
        }
    }
    
    /**
     * AJAX handler for deleting hour
     */
    public function ajax_delete_hour() {
        check_ajax_referer('lccp_hour_tracker', 'nonce');
        
        global $wpdb;
        $user_id = get_current_user_id();
        $hour_id = intval($_POST['hour_id']);
        
        if (!$user_id || !$hour_id) {
            wp_send_json_error('Invalid request');
        }
        
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $hour_id, 'user_id' => $user_id)
        );
        
        if ($result) {
            $this->update_user_total_hours($user_id);
            wp_send_json_success('Entry deleted');
        } else {
            wp_send_json_error('Failed to delete entry');
        }
    }
    
    /**
     * AJAX handler for getting hour details
     */
    public function ajax_get_hour_details() {
        check_ajax_referer('lccp_hour_tracker', 'nonce');
        
        $user_id = get_current_user_id();
        $hour_id = intval($_POST['hour_id']);
        
        if (!$user_id || !$hour_id) {
            wp_send_json_error('Invalid request');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
            $hour_id,
            $user_id
        ));
        
        if (!$entry) {
            wp_send_json_error('Entry not found');
        }
        
        ob_start();
        ?>
        <div class="hour-details">
            <p><strong>Date:</strong> <?php echo esc_html($entry->session_date); ?></p>
            <p><strong>Client:</strong> <?php echo esc_html($entry->client_name); ?></p>
            <p><strong>Duration:</strong> <?php echo esc_html($entry->session_length); ?> hours</p>
            <p><strong>Session #:</strong> <?php echo esc_html($entry->session_number); ?></p>
            <?php if (!empty($entry->notes)): ?>
                <p><strong>Notes:</strong> <?php echo nl2br(esc_html($entry->notes)); ?></p>
            <?php endif; ?>
        </div>
        <?php
        $content = ob_get_clean();
        
        wp_send_json_success(array(
            'title' => 'Session Details',
            'content' => $content
        ));
    }
    
    /**
     * AJAX handler for exporting hours
     */
    public function ajax_export_hours() {
        check_ajax_referer('lccp_hour_tracker', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Not logged in');
        }
        
        $format = isset($_POST['format']) ? $_POST['format'] : 'csv';
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        $entries = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY session_date DESC",
            $user_id
        ));
        
        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="coaching-hours-' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, array('Date', 'Client', 'Duration', 'Session #', 'Notes'));
            
            foreach ($entries as $entry) {
                fputcsv($output, array(
                    $entry->session_date,
                    $entry->client_name,
                    $entry->session_length,
                    $entry->session_number,
                    $entry->notes
                ));
            }
            
            fclose($output);
            exit;
        }
        
        wp_send_json_error('Invalid format');
    }
}

// Widget class
class LCCP_Hours_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'lccp_hours_widget',
            'LCCP Hours Tracker Widget',
            array('description' => 'Displays total hours and tier level.')
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        echo $args['before_title'] . 'Total Hours Tracked' . $args['after_title'];
        
        $tracker = new LCCP_Hour_Tracker();
        echo $tracker->display_hours_widget();
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        echo '<p>No configuration required for this widget.</p>';
    }
}