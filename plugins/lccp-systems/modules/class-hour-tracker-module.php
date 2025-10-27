<?php
/**
 * Hour Tracker Module for LCCP Systems
 * Modular version with feature toggle support
 *
 * @package LCCP Systems
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Hour_Tracker_Module extends LCCP_Module {
    
    protected $module_id = 'hour_tracker';
    protected $module_name = 'Hour Tracker';
    protected $module_description = 'Track coaching hours for certification requirements with tier-based progress tracking.';
    protected $module_version = '1.0.0';
    protected $module_dependencies = array();
    protected $module_settings = array(
        'enable_widget' => true,
        'enable_shortcodes' => true,
        'enable_ajax' => true,
        'tier_thresholds' => array(
            'bronze' => 0,
            'silver' => 100,
            'gold' => 200,
            'platinum' => 300
        ),
        'auto_certification' => false
    );
    
    protected function init() {
        // Only initialize if module is enabled
        if (!$this->is_enabled()) {
            return;
        }
        
        // Register widget
        if ($this->get_setting('enable_widget')) {
            add_action('widgets_init', array($this, 'register_widget'));
        }
        
        // Register shortcodes
        if ($this->get_setting('enable_shortcodes')) {
            add_shortcode('lccp-hour-widget', array($this, 'display_hours_widget'));
            add_shortcode('lccp-hour-form', array($this, 'hour_tracker_form'));
            add_shortcode('lccp-hour-log', array($this, 'display_hour_log'));
        }
        
        // AJAX handlers
        if ($this->get_setting('enable_ajax')) {
            add_action('wp_ajax_lccp_log_hours', array($this, 'ajax_log_hours'));
            add_action('wp_ajax_lccp_get_hours', array($this, 'ajax_get_hours'));
            add_action('wp_ajax_lccp_delete_entry', array($this, 'ajax_delete_entry'));
        }
        
        // Process form submissions
        add_action('init', array($this, 'process_hour_form'));
        
        // Admin page
        add_action('admin_menu', array($this, 'add_admin_page'));
    }
    
    /**
     * Get a specific setting value
     */
    private function get_setting($key) {
        $settings = $this->get_settings();
        return isset($settings[$key]) ? $settings[$key] : null;
    }
    
    /**
     * Called when module is activated
     */
    protected function on_activate() {
        $this->create_database_tables();
    }
    
    /**
     * Called when module is deactivated
     */
    protected function on_deactivate() {
        // Clean up any module-specific data if needed
    }
    
    /**
     * Create database tables for hour tracking
     */
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            client_name varchar(100) NOT NULL,
            session_date date NOT NULL,
            session_length float NOT NULL,
            session_number int NOT NULL,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY session_date (session_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Register widget
     */
    public function register_widget() {
        register_widget('LCCP_Hours_Widget');
    }
    
    /**
     * Display hours widget
     */
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
            <div class="tier-info" style="background: <?php echo esc_attr($tier_details['color']); ?>; color: white; padding: 8px; border-radius: 4px; margin-bottom: 10px;">
                <strong><?php echo esc_html($tier_details['name']); ?></strong>
            </div>
            <div class="progress-info" style="font-size: 0.9em; color: #666;">
                <?php if ($tier_details['next_tier']): ?>
                    <?php echo esc_html($tier_details['hours_to_next']); ?> hours to <?php echo esc_html($tier_details['next_tier']); ?>
                <?php else: ?>
                    <?php esc_html_e('Maximum tier reached!', 'lccp-systems'); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Hour tracker form
     */
    public function hour_tracker_form() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return '<p>Please log in to track hours.</p>';
        }
        
        ob_start();
        ?>
        <div class="lccp-hour-form">
            <form id="lccp-hour-form" method="post">
                <?php wp_nonce_field('lccp_log_hours', 'lccp_hour_nonce'); ?>
                <input type="hidden" name="action" value="lccp_log_hours">
                
                <div class="form-group">
                    <label for="client_name"><?php esc_html_e('Client Name:', 'lccp-systems'); ?></label>
                    <input type="text" id="client_name" name="client_name" required>
                </div>
                
                <div class="form-group">
                    <label for="session_date"><?php esc_html_e('Session Date:', 'lccp-systems'); ?></label>
                    <input type="date" id="session_date" name="session_date" required>
                </div>
                
                <div class="form-group">
                    <label for="session_length"><?php esc_html_e('Session Length (hours):', 'lccp-systems'); ?></label>
                    <input type="number" id="session_length" name="session_length" step="0.1" min="0.1" required>
                </div>
                
                <div class="form-group">
                    <label for="session_number"><?php esc_html_e('Session Number:', 'lccp-systems'); ?></label>
                    <input type="number" id="session_number" name="session_number" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="notes"><?php esc_html_e('Notes:', 'lccp-systems'); ?></label>
                    <textarea id="notes" name="notes" rows="3"></textarea>
                </div>
                
                <button type="submit" class="lccp-btn lccp-btn-primary">
                    <?php esc_html_e('Log Hours', 'lccp-systems'); ?>
                </button>
            </form>
        </div>
        
        <style>
        .lccp-hour-form .form-group {
            margin-bottom: 15px;
        }
        
        .lccp-hour-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .lccp-hour-form input,
        .lccp-hour-form textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .lccp-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .lccp-btn-primary {
            background-color: #007cba;
            color: white;
        }
        
        .lccp-btn-primary:hover {
            background-color: #005a87;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Display hour log
     */
    public function display_hour_log() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return '<p>Please log in to view your hour log.</p>';
        }
        
        $entries = $this->get_user_hour_entries($user_id);
        
        ob_start();
        ?>
        <div class="lccp-hour-log">
            <h3><?php esc_html_e('Your Hour Log', 'lccp-systems'); ?></h3>
            
            <?php if (empty($entries)): ?>
                <p><?php esc_html_e('No hours logged yet.', 'lccp-systems'); ?></p>
            <?php else: ?>
                <table class="lccp-hour-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Date', 'lccp-systems'); ?></th>
                            <th><?php esc_html_e('Client', 'lccp-systems'); ?></th>
                            <th><?php esc_html_e('Session #', 'lccp-systems'); ?></th>
                            <th><?php esc_html_e('Hours', 'lccp-systems'); ?></th>
                            <th><?php esc_html_e('Notes', 'lccp-systems'); ?></th>
                            <th><?php esc_html_e('Actions', 'lccp-systems'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td><?php echo esc_html(date('M j, Y', strtotime($entry->session_date))); ?></td>
                                <td><?php echo esc_html($entry->client_name); ?></td>
                                <td><?php echo esc_html($entry->session_number); ?></td>
                                <td><?php echo esc_html($entry->session_length); ?></td>
                                <td><?php echo esc_html($entry->notes); ?></td>
                                <td>
                                    <button class="lccp-btn lccp-btn-small lccp-btn-danger" 
                                            onclick="deleteHourEntry(<?php echo esc_attr($entry->id); ?>)">
                                        <?php esc_html_e('Delete', 'lccp-systems'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <style>
        .lccp-hour-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .lccp-hour-table th,
        .lccp-hour-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        
        .lccp-hour-table th {
            background-color: #f5f5f5;
            font-weight: 600;
        }
        
        .lccp-btn-small {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .lccp-btn-danger {
            background-color: #dc3232;
            color: white;
        }
        
        .lccp-btn-danger:hover {
            background-color: #a00;
        }
        </style>
        
        <script>
        function deleteHourEntry(entryId) {
            if (confirm('Are you sure you want to delete this entry?')) {
                jQuery.post(ajaxurl, {
                    action: 'lccp_delete_entry',
                    entry_id: entryId,
                    nonce: '<?php echo wp_create_nonce('lccp_delete_entry'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error deleting entry');
                    }
                });
            }
        }
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Process hour form submission
     */
    public function process_hour_form() {
        if (!isset($_POST['lccp_hour_nonce']) || !wp_verify_nonce($_POST['lccp_hour_nonce'], 'lccp_log_hours')) {
            return;
        }
        
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $client_name = sanitize_text_field($_POST['client_name']);
        $session_date = sanitize_text_field($_POST['session_date']);
        $session_length = floatval($_POST['session_length']);
        $session_number = intval($_POST['session_number']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'client_name' => $client_name,
                'session_date' => $session_date,
                'session_length' => $session_length,
                'session_number' => $session_number,
                'notes' => $notes
            ),
            array('%d', '%s', '%s', '%f', '%d', '%s')
        );
        
        if ($result) {
            wp_redirect(add_query_arg('hour_logged', '1', wp_get_referer()));
            exit;
        }
    }
    
    /**
     * AJAX handler for logging hours
     */
    public function ajax_log_hours() {
        check_ajax_referer('lccp_log_hours', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $client_name = sanitize_text_field($_POST['client_name']);
        $session_date = sanitize_text_field($_POST['session_date']);
        $session_length = floatval($_POST['session_length']);
        $session_number = intval($_POST['session_number']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'client_name' => $client_name,
                'session_date' => $session_date,
                'session_length' => $session_length,
                'session_number' => $session_number,
                'notes' => $notes
            ),
            array('%d', '%s', '%s', '%f', '%d', '%s')
        );
        
        if ($result) {
            wp_send_json_success('Hours logged successfully');
        } else {
            wp_send_json_error('Failed to log hours');
        }
    }
    
    /**
     * AJAX handler for getting hours
     */
    public function ajax_get_hours() {
        check_ajax_referer('lccp_get_hours', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $total_hours = $this->get_user_total_hours($user_id);
        $tier_details = $this->get_tier($total_hours);
        
        wp_send_json_success(array(
            'total_hours' => $total_hours,
            'tier' => $tier_details
        ));
    }
    
    /**
     * AJAX handler for deleting entries
     */
    public function ajax_delete_entry() {
        check_ajax_referer('lccp_delete_entry', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $entry_id = intval($_POST['entry_id']);
        $user_id = get_current_user_id();
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        $result = $wpdb->delete(
            $table_name,
            array(
                'id' => $entry_id,
                'user_id' => $user_id
            ),
            array('%d', '%d')
        );
        
        if ($result) {
            wp_send_json_success('Entry deleted');
        } else {
            wp_send_json_error('Failed to delete entry');
        }
    }
    
    /**
     * Get user total hours
     */
    private function get_user_total_hours($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(session_length) FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        return $total ? floatval($total) : 0;
    }
    
    /**
     * Get user hour entries
     */
    private function get_user_hour_entries($user_id, $limit = 50) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY session_date DESC LIMIT %d",
            $user_id,
            $limit
        ));
    }
    
    /**
     * Get tier information based on hours
     */
    private function get_tier($total_hours) {
        $settings = $this->get_settings();
        $thresholds = $settings['tier_thresholds'];
        
        $tiers = array(
            'bronze' => array('name' => 'Bronze', 'color' => '#cd7f32'),
            'silver' => array('name' => 'Silver', 'color' => '#c0c0c0'),
            'gold' => array('name' => 'Gold', 'color' => '#ffd700'),
            'platinum' => array('name' => 'Platinum', 'color' => '#e5e4e2')
        );
        
        $current_tier = 'bronze';
        $next_tier = null;
        
        foreach ($thresholds as $tier => $threshold) {
            if ($total_hours >= $threshold) {
                $current_tier = $tier;
            } else {
                if (!$next_tier) {
                    $next_tier = $tier;
                }
                break;
            }
        }
        
        $tier_info = $tiers[$current_tier];
        $tier_info['next_tier'] = $next_tier ? $tiers[$next_tier]['name'] : null;
        $tier_info['hours_to_next'] = $next_tier ? ($thresholds[$next_tier] - $total_hours) : 0;
        
        return $tier_info;
    }
    
    /**
     * Add admin page
     */
    public function add_admin_page() {
        add_submenu_page(
            'lccp-systems',
            __('Hour Tracker', 'lccp-systems'),
            __('Hour Tracker', 'lccp-systems'),
            'read',
            'lccp-hour-tracker',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $user_id = get_current_user_id();
        $total_hours = $this->get_user_total_hours($user_id);
        $tier_details = $this->get_tier($total_hours);
        $entries = $this->get_user_hour_entries($user_id);
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Hour Tracker', 'lccp-systems'); ?></h1>
            
            <div class="lccp-hour-tracker-admin">
                <div class="lccp-hour-summary">
                    <div class="lccp-hour-card">
                        <h2><?php esc_html_e('Your Progress', 'lccp-systems'); ?></h2>
                        <div class="lccp-hour-display">
                            <div class="lccp-hour-number"><?php echo number_format($total_hours, 1); ?></div>
                            <div class="lccp-hour-label"><?php esc_html_e('Total Hours', 'lccp-systems'); ?></div>
                        </div>
                        <div class="lccp-tier-display" style="background-color: <?php echo esc_attr($tier_details['color']); ?>;">
                            <?php echo esc_html($tier_details['name']); ?>
                        </div>
                        <?php if ($tier_details['next_tier']): ?>
                            <div class="lccp-next-tier">
                                <?php echo esc_html($tier_details['hours_to_next']); ?> hours to <?php echo esc_html($tier_details['next_tier']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="lccp-hour-form-section">
                    <h2><?php esc_html_e('Log New Hours', 'lccp-systems'); ?></h2>
                    <?php echo $this->hour_tracker_form(); ?>
                </div>
                
                <div class="lccp-hour-log-section">
                    <h2><?php esc_html_e('Recent Entries', 'lccp-systems'); ?></h2>
                    <?php echo $this->display_hour_log(); ?>
                </div>
            </div>
        </div>
        
        <style>
        .lccp-hour-tracker-admin {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        
        .lccp-hour-summary {
            grid-column: 1 / -1;
        }
        
        .lccp-hour-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .lccp-hour-display {
            margin: 20px 0;
        }
        
        .lccp-hour-number {
            font-size: 3em;
            font-weight: bold;
            color: #007cba;
        }
        
        .lccp-hour-label {
            font-size: 1.2em;
            color: #666;
            margin-top: 10px;
        }
        
        .lccp-tier-display {
            color: white;
            padding: 15px;
            border-radius: 8px;
            font-size: 1.5em;
            font-weight: bold;
            margin: 20px 0;
        }
        
        .lccp-next-tier {
            color: #666;
            font-size: 1.1em;
        }
        
        .lccp-hour-form-section,
        .lccp-hour-log-section {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        </style>
        <?php
    }
}

/**
 * Hours Widget Class
 */
class LCCP_Hours_Widget_Backup extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'lccp_hours_widget',
            __('LCCP Hours Widget', 'lccp-systems'),
            array('description' => __('Display user\'s coaching hours and tier progress', 'lccp-systems'))
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $hour_tracker = new LCCP_Hour_Tracker_Module();
        echo $hour_tracker->display_hours_widget();
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('My Hours', 'lccp-systems');
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php esc_html_e('Title:', 'lccp-systems'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }
}
