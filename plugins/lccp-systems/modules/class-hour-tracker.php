<?php
/**
 * LCCP Hour Tracker Module
 * 
 * Manages coaching hour tracking for certification students
 * 
 * @package LCCP_Systems
 * @subpackage Modules
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Hour_Tracker_Module {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'), 25);
        add_action('wp_ajax_lccp_save_hours', array($this, 'ajax_save_hours'));
        add_action('wp_ajax_lccp_get_hours', array($this, 'ajax_get_hours'));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'lccp-systems',
            __('Hour Tracker', 'lccp-systems'),
            __('Hour Tracker', 'lccp-systems'),
            'read',
            'lccp-hour-tracker',
            array($this, 'render_admin_page')
        );
    }
    
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('LCCP Hour Tracker', 'lccp-systems'); ?></h1>
            <p><?php _e('Track your coaching hours for certification requirements.', 'lccp-systems'); ?></p>
            
            <div class="lccp-hour-tracker-container">
                <h2><?php _e('Log New Session', 'lccp-systems'); ?></h2>
                <form method="post" id="lccp-hour-form">
                    <table class="form-table">
                        <tr>
                            <th><label for="client_name"><?php _e('Client Name', 'lccp-systems'); ?></label></th>
                            <td><input type="text" id="client_name" name="client_name" required /></td>
                        </tr>
                        <tr>
                            <th><label for="session_date"><?php _e('Session Date', 'lccp-systems'); ?></label></th>
                            <td><input type="date" id="session_date" name="session_date" required /></td>
                        </tr>
                        <tr>
                            <th><label for="session_length"><?php _e('Session Length (hours)', 'lccp-systems'); ?></label></th>
                            <td><input type="number" id="session_length" name="session_length" step="0.25" min="0.25" required /></td>
                        </tr>
                        <tr>
                            <th><label for="session_number"><?php _e('Session Number', 'lccp-systems'); ?></label></th>
                            <td><input type="number" id="session_number" name="session_number" min="1" required /></td>
                        </tr>
                        <tr>
                            <th><label for="notes"><?php _e('Notes', 'lccp-systems'); ?></label></th>
                            <td><textarea id="notes" name="notes" rows="4" cols="50"></textarea></td>
                        </tr>
                    </table>
                    <?php submit_button(__('Log Hours', 'lccp-systems')); ?>
                </form>
                
                <h2><?php _e('Your Logged Hours', 'lccp-systems'); ?></h2>
                <?php $this->display_hours_table(); ?>
            </div>
        </div>
        <?php
    }
    
    private function display_hours_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        $user_id = get_current_user_id();
        
        $hours = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY session_date DESC",
            $user_id
        ));
        
        if ($hours) {
            ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Client Name', 'lccp-systems'); ?></th>
                        <th><?php _e('Date', 'lccp-systems'); ?></th>
                        <th><?php _e('Hours', 'lccp-systems'); ?></th>
                        <th><?php _e('Session #', 'lccp-systems'); ?></th>
                        <th><?php _e('Notes', 'lccp-systems'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hours as $hour): ?>
                        <tr>
                            <td><?php echo esc_html($hour->client_name); ?></td>
                            <td><?php echo esc_html($hour->session_date); ?></td>
                            <td><?php echo esc_html($hour->session_length); ?></td>
                            <td><?php echo esc_html($hour->session_number); ?></td>
                            <td><?php echo esc_html($hour->notes); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        } else {
            echo '<p>' . __('No hours logged yet.', 'lccp-systems') . '</p>';
        }
    }
    
    public function ajax_save_hours() {
        check_ajax_referer('lccp_hour_tracker_nonce', 'nonce');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => get_current_user_id(),
                'client_name' => sanitize_text_field($_POST['client_name']),
                'session_date' => sanitize_text_field($_POST['session_date']),
                'session_length' => floatval($_POST['session_length']),
                'session_number' => intval($_POST['session_number']),
                'notes' => sanitize_textarea_field($_POST['notes'])
            )
        );
        
        if ($result) {
            wp_send_json_success(__('Hours logged successfully!', 'lccp-systems'));
        } else {
            wp_send_json_error(__('Failed to log hours.', 'lccp-systems'));
        }
    }
    
    public function ajax_get_hours() {
        check_ajax_referer('lccp_hour_tracker_nonce', 'nonce');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_tracker';
        $user_id = get_current_user_id();
        
        $total_hours = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(session_length) FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        wp_send_json_success(array('total_hours' => $total_hours ?: 0));
    }
}

// Initialize the module
LCCP_Hour_Tracker_Module::get_instance();