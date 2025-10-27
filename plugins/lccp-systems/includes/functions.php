<?php
/**
 * Utility functions for Dasher plugin
 *
 * @package Dasher
 * @since 1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get all student assignments.
 *
 * @since 1.0.0
 * @return array Array of student assignments
 */
function lccp_get_all_student_assignments() {
    try {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_assignments';
        
        // Verify table exists
        if (!lccp_table_exists($table_name)) {
            throw new Exception(__('Assignment table does not exist', 'lccp-systems'));
        }
        
        $results = $wpdb->get_results(
            "SELECT student_id, big_bird_id, assigned_date, assigned_by FROM $table_name",
            ARRAY_A
        );
        
        if ($wpdb->last_error) {
            throw new Exception($wpdb->last_error);
        }
        
        return $results ?: array();
        
    } catch (Exception $e) {
        lccp_log('Error getting student assignments: ' . $e->getMessage(), 'error');
        return array();
    }
}

/**
 * Check if a table exists in the database.
 *
 * @since 1.0.0
 * @param string $table_name The table name to check.
 * @return bool True if the table exists, false otherwise.
 */
function lccp_table_exists($table_name) {
    try {
        global $wpdb;
        
        // Sanitize table name
        $table_name = $wpdb->prefix . sanitize_key(str_replace($wpdb->prefix, '', $table_name));
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $wpdb->esc_like($table_name)
        ));
        
        return !empty($result);
        
    } catch (Exception $e) {
        lccp_log('Error checking table existence: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Log an event to the plugin's log file.
 *
 * @since 1.0.0
 * @param string $message The message to log.
 * @param string $level   The log level ('info', 'warning', 'error').
 * @return void
 */
function lccp_log($message, $level = 'info') {
    try {
        if (!in_array($level, array('info', 'warning', 'error'))) {
            $level = 'info';
        }
        
        $upload_dir = wp_upload_dir();
        if (is_wp_error($upload_dir)) {
            throw new Exception($upload_dir->get_error_message());
        }
        
        $log_dir = trailingslashit($upload_dir['basedir']) . 'dasher-logs/';
        
        if (!file_exists($log_dir)) {
            if (!wp_mkdir_p($log_dir)) {
                throw new Exception(__('Failed to create log directory', 'dasher'));
            }
            
            // Create .htaccess to prevent direct access
            $htaccess = $log_dir . '.htaccess';
            if (!file_exists($htaccess)) {
                if (!file_put_contents($htaccess, 'deny from all')) {
                    throw new Exception(__('Failed to create .htaccess file', 'dasher'));
                }
            }
        }
        
        $log_file = $log_dir . 'dasher-log-' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        
        if (!error_log('[' . $timestamp . '] [' . strtoupper($level) . '] ' . $message . PHP_EOL, 3, $log_file)) {
            throw new Exception(__('Failed to write to log file', 'dasher'));
        }
        
    } catch (Exception $e) {
        // Fallback to WordPress error log if our logging fails
        error_log('Dasher Logging Error: ' . $e->getMessage());
    }
}

/**
 * Check if the plugin's dependent plugins are active.
 *
 * @since 1.0.0
 * @return bool True if all dependencies are active, false otherwise.
 */
function lccp_check_dependencies() {
    // Check for LearnDash
    if (!class_exists('SFWD_LMS')) {
        add_action('admin_notices', 'lccp_dependency_notice_learndash');
        return false;
    }
    
    return true;
}

/**
 * Display an admin notice for missing LearnDash dependency.
 *
 * @since 1.0.0
 * @return void
 */
function lccp_dependency_notice_learndash() {
    ?>
    <div class="notice notice-error">
        <p><?php esc_html_e('LCCP Systems requires LearnDash LMS to be installed and activated.', 'lccp-systems'); ?></p>
    </div>
    <?php
}

/**
 * Retrieve and format plugin settings with defaults.
 *
 * @since 1.0.0
 * @return array The plugin settings.
 */
function lccp_get_settings() {
    $defaults = array(
        'indicator_enabled' => 'yes',
        'visualization_enabled' => 'yes',
        'rename_final_quiz' => 'yes',
        'redirect_after_exam' => 'yes',
        'redirect_url' => 'https://you.fearlessliving.org/lccp/',
    );
    
    $settings = get_option('lccp_settings', array());
    
    return wp_parse_args($settings, $defaults);
}

/**
 * Render the frontend tab navigation.
 *
 * @since 1.0.0
 * @param array $tabs Array of tab data (id, label).
 * @return void
 */
function lccp_render_frontend_tabs($tabs) {
    if (empty($tabs)) {
        return;
    }
    
    echo '<ul class="dasher-tab-nav">';
    
    $first_tab = true;
    foreach ($tabs as $tab) {
        $active_class = $first_tab ? ' active' : '';
        echo '<li class="dasher-tab' . esc_attr($active_class) . '" data-tab="' . esc_attr($tab['id']) . '">' . esc_html($tab['label']) . '</li>';
        $first_tab = false;
    }
    
    echo '</ul>';
}

/**
 * Check if user can access dasher.
 *
 * @since 1.0.0
 * @param int $user_id The user ID to check.
 * @return bool True if the user can access, false otherwise.
 */
function lccp_user_can_access($user_id) {
    $user = get_userdata($user_id);
    
    if (!$user) {
        return false;
    }
    
    // Check if user has any of the required roles
    $required_roles = array('administrator', 'lccp_mentor', 'lccp_big_bird');
    
    foreach ($required_roles as $role) {
        if (in_array($role, (array) $user->roles)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Check if a user has a specific role.
 *
 * @since 1.0.0
 * @param int    $user_id The user ID to check.
 * @param string $role    The role to check for.
 * @return bool True if the user has the role, false otherwise.
 */
function lccp_user_has_role($user_id, $role) {
    $user = get_userdata($user_id);
    
    if (!$user) {
        return false;
    }
    
    return in_array($role, (array) $user->roles);
}

/**
 * Get all users with a specific role.
 *
 * @since 1.0.0
 * @param string $role    The role to get users for.
 * @return array Array of WP_User objects.
 */
function lccp_get_users_by_role($role) {
    $args = array(
        'role' => $role,
        'number' => -1,
    );
    
    return get_users($args);
}

/**
 * Format a timestamp as a human-readable "time ago" string.
 *
 * @since 1.0.0
 * @param int|string $timestamp The timestamp to format.
 * @return string The formatted "time ago" string.
 */
function lccp_time_ago($timestamp) {
    if (!is_numeric($timestamp)) {
        $timestamp = strtotime($timestamp);
    }
    
    return human_time_diff($timestamp, current_time('timestamp')) . ' ' . __('ago', 'dasher');
}

/**
 * Format a percentage value with proper handling.
 *
 * @since 1.0.0
 * @param float $percentage The percentage value to format.
 * @return string The formatted percentage string.
 */
function lccp_format_percentage($percentage) {
    // Ensure percentage is a valid number
    if (!is_numeric($percentage)) {
        return '0%';
    }
    
    // Clamp between 0 and 100
    $percentage = max(0, min(100, $percentage));
    
    // Format to whole number with % sign
    return round($percentage) . '%';
}

/**
 * Get post content by post ID.
 *
 * @since 1.0.0
 * @param int $post_id The post ID.
 * @return string The post content.
 */
function lccp_get_post_content($post_id) {
    $post = get_post($post_id);
    
    if (!$post) {
        return '';
    }
    
    return $post->post_content;
}

/**
 * Clear all Dasher dashboard caches
 *
 * @since 1.0.0
 * @return void
 */
function lccp_clear_dashboard_caches() {
    global $wpdb;
    
    // Clear all dasher transients
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_lccp_%' OR option_name LIKE '_transient_timeout_lccp_%'");
    
    // Clear object cache if available
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    lccp_log('Dashboard caches cleared', 'info');
}

/**
 * Get student count for performance metrics
 *
 * @since 1.0.0
 * @param string $role Role to count
 * @return int User count
 */
function lccp_get_user_count_by_role($role) {
    global $wpdb;
    
    $cache_key = 'lccp_user_count_' . $role;
    $count = get_transient($cache_key);
    
    if ($count === false) {
        // Use direct query for performance
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT u.ID) 
             FROM {$wpdb->users} u 
             INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
             WHERE um.meta_key = %s 
             AND um.meta_value LIKE %s",
            $wpdb->prefix . 'capabilities',
            '%' . $wpdb->esc_like('"' . $role . '"') . '%'
        ));
        
        set_transient($cache_key, $count, 3600); // Cache for 1 hour
    }
    
    return intval($count);
} 