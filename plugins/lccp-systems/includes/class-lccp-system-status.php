<?php
/**
 * System Status Checker for LCCP Systems
 * 
 * Monitors the health of critical system components
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_System_Status {
    
    /**
     * Check system status
     * 
     * @return array Status information
     */
    public static function check_status() {
        $status = array(
            'overall' => 'green',
            'checks' => array()
        );
        
        // Check home page accessibility
        $home_check = self::check_page_accessibility(home_url('/'));
        $status['checks']['home'] = $home_check;
        if ($home_check['status'] !== 'green') {
            $status['overall'] = self::downgrade_status($status['overall'], $home_check['status']);
        }
        
        // Check courses page accessibility
        $courses_url = home_url('/courses/');
        $courses_check = self::check_page_accessibility($courses_url);
        $status['checks']['courses'] = $courses_check;
        if ($courses_check['status'] !== 'green') {
            $status['overall'] = self::downgrade_status($status['overall'], $courses_check['status']);
        }
        
        // Check a random lesson if LearnDash is active
        if (class_exists('SFWD_LMS')) {
            $lesson_check = self::check_random_lesson();
            $status['checks']['lesson'] = $lesson_check;
            if ($lesson_check['status'] !== 'green') {
                $status['overall'] = self::downgrade_status($status['overall'], $lesson_check['status']);
            }
        }
        
        // Check database connectivity
        $db_check = self::check_database();
        $status['checks']['database'] = $db_check;
        if ($db_check['status'] !== 'green') {
            $status['overall'] = self::downgrade_status($status['overall'], $db_check['status']);
        }
        
        // Check critical plugins
        $plugins_check = self::check_critical_plugins();
        $status['checks']['plugins'] = $plugins_check;
        if ($plugins_check['status'] !== 'green') {
            $status['overall'] = self::downgrade_status($status['overall'], $plugins_check['status']);
        }
        
        // Store last check time
        $status['last_check'] = current_time('mysql');
        
        // Cache the status for 5 minutes
        set_transient('lccp_system_status', $status, 300);
        
        return $status;
    }
    
    /**
     * Check page accessibility
     * 
     * @param string $url URL to check
     * @return array Check result
     */
    private static function check_page_accessibility($url) {
        $result = array(
            'name' => 'Page Accessibility',
            'url' => $url,
            'status' => 'green',
            'message' => 'Accessible'
        );
        
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            $result['status'] = 'red';
            $result['message'] = 'Error: ' . $response->get_error_message();
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code >= 200 && $response_code < 300) {
                $result['status'] = 'green';
                $result['message'] = 'OK (' . $response_code . ')';
            } elseif ($response_code >= 300 && $response_code < 400) {
                $result['status'] = 'yellow';
                $result['message'] = 'Redirect (' . $response_code . ')';
            } else {
                $result['status'] = 'red';
                $result['message'] = 'Error (' . $response_code . ')';
            }
        }
        
        return $result;
    }
    
    /**
     * Check a random lesson
     * 
     * @return array Check result
     */
    private static function check_random_lesson() {
        $result = array(
            'name' => 'Random Lesson Check',
            'status' => 'green',
            'message' => 'No lessons found'
        );
        
        // Get random lesson
        $lessons = get_posts(array(
            'post_type' => 'sfwd-lessons',
            'posts_per_page' => 1,
            'orderby' => 'rand',
            'post_status' => 'publish'
        ));
        
        if (!empty($lessons)) {
            $lesson = $lessons[0];
            $lesson_url = get_permalink($lesson->ID);
            $result = self::check_page_accessibility($lesson_url);
            $result['name'] = 'Lesson: ' . $lesson->post_title;
            $result['lesson_id'] = $lesson->ID;
        } else {
            $result['status'] = 'yellow';
            $result['message'] = 'No lessons found to check';
        }
        
        return $result;
    }
    
    /**
     * Check database connectivity
     * 
     * @return array Check result
     */
    private static function check_database() {
        global $wpdb;
        
        $result = array(
            'name' => 'Database Connection',
            'status' => 'green',
            'message' => 'Connected'
        );
        
        try {
            // Simple query to test database
            $test = $wpdb->get_var("SELECT 1");
            if ($test == 1) {
                $result['status'] = 'green';
                $result['message'] = 'Connected successfully';
            } else {
                $result['status'] = 'red';
                $result['message'] = 'Connection test failed';
            }
        } catch (Exception $e) {
            $result['status'] = 'red';
            $result['message'] = 'Error: ' . $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Check critical plugins status
     * 
     * @return array Check result
     */
    private static function check_critical_plugins() {
        $result = array(
            'name' => 'Critical Plugins',
            'status' => 'green',
            'message' => 'All active'
        );
        
        // Ensure we have the plugin functions
        if (!function_exists('is_plugin_active')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        $critical_plugins = array(
            'buddyboss-platform/bp-loader.php' => 'BuddyBoss Platform',
            'sfwd-lms/sfwd_lms.php' => 'LearnDash LMS'
        );
        
        $inactive_plugins = array();
        
        foreach ($critical_plugins as $plugin_file => $plugin_name) {
            if (!is_plugin_active($plugin_file)) {
                $inactive_plugins[] = $plugin_name;
            }
        }
        
        if (!empty($inactive_plugins)) {
            $result['status'] = 'yellow';
            $result['message'] = 'Inactive: ' . implode(', ', $inactive_plugins);
        }
        
        return $result;
    }
    
    /**
     * Downgrade status based on priority
     * 
     * @param string $current Current status
     * @param string $new New status to compare
     * @return string Downgraded status
     */
    private static function downgrade_status($current, $new) {
        $priority = array('green' => 0, 'yellow' => 1, 'red' => 2);
        
        if ($priority[$new] > $priority[$current]) {
            return $new;
        }
        
        return $current;
    }
    
    /**
     * Get cached status or perform new check
     * 
     * @param bool $force_check Force a new check
     * @return array Status information
     */
    public static function get_status($force_check = false) {
        if (!$force_check) {
            $cached = get_transient('lccp_system_status');
            if ($cached !== false) {
                return $cached;
            }
        }
        
        return self::check_status();
    }
    
    /**
     * AJAX handler for status check
     */
    public static function ajax_check_status() {
        check_ajax_referer('lccp_system_status', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $force_check = isset($_POST['force']) && $_POST['force'] === 'true';
        $status = self::get_status($force_check);
        
        wp_send_json_success($status);
    }
}

// Register AJAX handlers
add_action('wp_ajax_lccp_check_system_status', array('LCCP_System_Status', 'ajax_check_status'));