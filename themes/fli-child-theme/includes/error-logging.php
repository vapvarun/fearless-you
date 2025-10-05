<?php
/**
 * Error Logging and Debugging System
 * 
 * @package FLI BuddyBoss Child
 * @version 1.0.0
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * FLI Error Logging System
 * 
 * Provides comprehensive error logging, debugging, and monitoring
 * for the Fearless Living child theme.
 */
class FLI_Error_Logging {
    
    /**
     * The single instance of the class
     * 
     * @var FLI_Error_Logging
     */
    private static $instance = null;
    
    /**
     * Debug mode status
     * 
     * @var bool
     */
    private $debug_mode = false;
    
    /**
     * Log file path
     * 
     * @var string
     */
    private $log_file = '';
    
    /**
     * Maximum log file size (in bytes)
     * 
     * @var int
     */
    private $max_log_size = 10485760; // 10MB
    
    /**
     * Get the single instance
     * 
     * @return FLI_Error_Logging
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize the error logging system
     */
    private function init() {
        // Set debug mode
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        
        // Set log file path
        $upload_dir = wp_upload_dir();
        $this->log_file = $upload_dir['basedir'] . '/fli-debug.log';
        
        // Create log directory if it doesn't exist
        $log_dir = dirname($this->log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        // Hook into WordPress
        add_action('init', [$this, 'setup_error_handling']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_fli_clear_log', [$this, 'clear_log']);
        add_action('wp_ajax_fli_download_log', [$this, 'download_log']);
        
        // Custom error handlers
        register_shutdown_function([$this, 'handle_fatal_errors']);
        set_error_handler([$this, 'handle_php_errors']);
    }
    
    /**
     * Setup error handling
     */
    public function setup_error_handling() {
        // Only run in debug mode or for administrators
        if (!$this->debug_mode && !current_user_can('administrator')) {
            return;
        }
        
        // Log theme activation/deactivation
        add_action('switch_theme', [$this, 'log_theme_switch'], 10, 3);
        
        // Log plugin events
        add_action('activated_plugin', [$this, 'log_plugin_activation'], 10, 2);
        add_action('deactivated_plugin', [$this, 'log_plugin_deactivation'], 10, 2);
        
        // Log user actions
        add_action('wp_login', [$this, 'log_user_login'], 10, 2);
        add_action('wp_logout', [$this, 'log_user_logout']);
        
        // Log database errors
        add_action('wp_db_error', [$this, 'log_database_error']);
    }
    
    /**
     * Log a message with context
     * 
     * @param string $message The log message
     * @param string $level Log level (info, warning, error, debug)
     * @param array $context Additional context data
     * @param string $source Source of the log (function, file, etc.)
     */
    public function log($message, $level = 'info', $context = [], $source = '') {
        if (!$this->should_log($level)) {
            return;
        }
        
        $timestamp = current_time('Y-m-d H:i:s');
        $user_id = get_current_user_id();
        $ip_address = $this->get_client_ip();
        $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        
        $log_entry = [
            'timestamp' => $timestamp,
            'level' => strtoupper($level),
            'message' => $message,
            'source' => $source,
            'user_id' => $user_id,
            'ip_address' => $ip_address,
            'user_agent' => $user_agent,
            'context' => $context,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];
        
        $this->write_to_log($log_entry);
    }
    
    /**
     * Log an error
     * 
     * @param string $message Error message
     * @param array $context Additional context
     * @param string $source Source of the error
     */
    public function log_error($message, $context = [], $source = '') {
        $this->log($message, 'error', $context, $source);
    }
    
    /**
     * Log a warning
     * 
     * @param string $message Warning message
     * @param array $context Additional context
     * @param string $source Source of the warning
     */
    public function log_warning($message, $context = [], $source = '') {
        $this->log($message, 'warning', $context, $source);
    }
    
    /**
     * Log debug information
     * 
     * @param string $message Debug message
     * @param array $context Additional context
     * @param string $source Source of the debug info
     */
    public function log_debug($message, $context = [], $source = '') {
        $this->log($message, 'debug', $context, $source);
    }
    
    /**
     * Log info message
     * 
     * @param string $message Info message
     * @param array $context Additional context
     * @param string $source Source of the info
     */
    public function log_info($message, $context = [], $source = '') {
        $this->log($message, 'info', $context, $source);
    }
    
    /**
     * Check if we should log this level
     * 
     * @param string $level Log level
     * @return bool
     */
    private function should_log($level) {
        // Always log errors and warnings
        if (in_array($level, ['error', 'warning'])) {
            return true;
        }
        
        // Only log info and debug in debug mode
        return $this->debug_mode;
    }
    
    /**
     * Write log entry to file
     * 
     * @param array $log_entry Log entry data
     */
    private function write_to_log($log_entry) {
        // Check log file size and rotate if necessary
        if (file_exists($this->log_file) && filesize($this->log_file) > $this->max_log_size) {
            $this->rotate_log();
        }
        
        // Format log entry
        $formatted_entry = $this->format_log_entry($log_entry);
        
        // Write to file
        file_put_contents($this->log_file, $formatted_entry . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Format log entry for file output
     * 
     * @param array $log_entry Log entry data
     * @return string Formatted log entry
     */
    private function format_log_entry($log_entry) {
        $context_str = !empty($log_entry['context']) ? ' | Context: ' . json_encode($log_entry['context']) : '';
        $source_str = !empty($log_entry['source']) ? ' | Source: ' . $log_entry['source'] : '';
        
        return sprintf(
            '[%s] %s: %s | User: %d | IP: %s%s%s | Memory: %s/%s',
            $log_entry['timestamp'],
            $log_entry['level'],
            $log_entry['message'],
            $log_entry['user_id'],
            $log_entry['ip_address'],
            $source_str,
            $context_str,
            size_format($log_entry['memory_usage']),
            size_format($log_entry['peak_memory'])
        );
    }
    
    /**
     * Rotate log file
     */
    private function rotate_log() {
        $backup_file = $this->log_file . '.' . date('Y-m-d-H-i-s') . '.bak';
        rename($this->log_file, $backup_file);
        
        // Keep only last 5 backup files
        $backup_files = glob($this->log_file . '.*.bak');
        if (count($backup_files) > 5) {
            usort($backup_files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            $files_to_delete = array_slice($backup_files, 0, count($backup_files) - 5);
            foreach ($files_to_delete as $file) {
                unlink($file);
            }
        }
    }
    
    /**
     * Get client IP address
     * 
     * @return string
     */
    private function get_client_ip() {
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Handle PHP errors
     * 
     * @param int $errno Error number
     * @param string $errstr Error string
     * @param string $errfile Error file
     * @param int $errline Error line
     * @return bool
     */
    public function handle_php_errors($errno, $errstr, $errfile, $errline) {
        $error_types = [
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSE',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE_ERROR',
            E_CORE_WARNING => 'CORE_WARNING',
            E_COMPILE_ERROR => 'COMPILE_ERROR',
            E_COMPILE_WARNING => 'COMPILE_WARNING',
            E_USER_ERROR => 'USER_ERROR',
            E_USER_WARNING => 'USER_WARNING',
            E_USER_NOTICE => 'USER_NOTICE',
            E_STRICT => 'STRICT',
            E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
            E_DEPRECATED => 'DEPRECATED',
            E_USER_DEPRECATED => 'USER_DEPRECATED'
        ];
        
        $error_type = $error_types[$errno] ?? 'UNKNOWN';
        $message = "PHP {$error_type}: {$errstr} in {$errfile} on line {$errline}";
        
        $this->log_error($message, [
            'errno' => $errno,
            'errfile' => $errfile,
            'errline' => $errline
        ], 'PHP Error Handler');
        
        // Don't execute PHP internal error handler
        return true;
    }
    
    /**
     * Handle fatal errors
     */
    public function handle_fatal_errors() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $message = "FATAL ERROR: {$error['message']} in {$error['file']} on line {$error['line']}";
            $this->log_error($message, $error, 'Fatal Error Handler');
        }
    }
    
    /**
     * Log theme switch
     * 
     * @param string $new_name New theme name
     * @param WP_Theme $new_theme New theme object
     * @param WP_Theme $old_theme Old theme object
     */
    public function log_theme_switch($new_name, $new_theme, $old_theme) {
        $this->log_info("Theme switched from '{$old_theme->get('Name')}' to '{$new_name}'", [
            'old_theme' => $old_theme->get('Name'),
            'new_theme' => $new_name,
            'old_version' => $old_theme->get('Version'),
            'new_version' => $new_theme->get('Version')
        ], 'Theme Switch');
    }
    
    /**
     * Log plugin activation
     * 
     * @param string $plugin Plugin file
     * @param bool $network_wide Network wide activation
     */
    public function log_plugin_activation($plugin, $network_wide) {
        $this->log_info("Plugin activated: {$plugin}", [
            'plugin' => $plugin,
            'network_wide' => $network_wide
        ], 'Plugin Activation');
    }
    
    /**
     * Log plugin deactivation
     * 
     * @param string $plugin Plugin file
     * @param bool $network_wide Network wide deactivation
     */
    public function log_plugin_deactivation($plugin, $network_wide) {
        $this->log_info("Plugin deactivated: {$plugin}", [
            'plugin' => $plugin,
            'network_wide' => $network_wide
        ], 'Plugin Deactivation');
    }
    
    /**
     * Log user login
     * 
     * @param string $user_login User login
     * @param WP_User $user User object
     */
    public function log_user_login($user_login, $user) {
        $this->log_info("User logged in: {$user_login}", [
            'user_id' => $user->ID,
            'user_login' => $user_login,
            'user_email' => $user->user_email,
            'roles' => $user->roles
        ], 'User Login');
    }
    
    /**
     * Log user logout
     */
    public function log_user_logout() {
        $user = wp_get_current_user();
        if ($user->ID) {
            $this->log_info("User logged out: {$user->user_login}", [
                'user_id' => $user->ID,
                'user_login' => $user->user_login
            ], 'User Logout');
        }
    }
    
    /**
     * Log database error
     * 
     * @param string $error Database error message
     */
    public function log_database_error($error) {
        $this->log_error("Database error: {$error}", [
            'error' => $error,
            'query' => $GLOBALS['wpdb']->last_query ?? 'Unknown'
        ], 'Database Error');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_management_page(
            'FLI Debug Log',
            'FLI Debug Log',
            'manage_options',
            'fli-debug-log',
            [$this, 'admin_page']
        );
    }
    
    /**
     * Admin page callback
     */
    public function admin_page() {
        $log_content = '';
        $log_size = 0;
        
        if (file_exists($this->log_file)) {
            $log_content = file_get_contents($this->log_file);
            $log_size = filesize($this->log_file);
        }
        
        ?>
        <div class="wrap">
            <h1>FLI Debug Log</h1>
            
            <div class="notice notice-info">
                <p><strong>Debug Mode:</strong> <?php echo $this->debug_mode ? 'Enabled' : 'Disabled'; ?></p>
                <p><strong>Log File:</strong> <?php echo esc_html($this->log_file); ?></p>
                <p><strong>Log Size:</strong> <?php echo size_format($log_size); ?></p>
            </div>
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <button type="button" class="button" onclick="clearLog()">Clear Log</button>
                    <button type="button" class="button" onclick="downloadLog()">Download Log</button>
                    <button type="button" class="button" onclick="refreshLog()">Refresh</button>
                </div>
            </div>
            
            <div id="log-content" style="background: #f1f1f1; padding: 15px; border: 1px solid #ddd; font-family: monospace; white-space: pre-wrap; max-height: 600px; overflow-y: auto;">
                <?php echo esc_html($log_content); ?>
            </div>
        </div>
        
        <script>
        function clearLog() {
            if (confirm('Are you sure you want to clear the log?')) {
                jQuery.post(ajaxurl, {
                    action: 'fli_clear_log',
                    nonce: '<?php echo wp_create_nonce('fli_clear_log'); ?>'
                }, function(response) {
                    if (response.success) {
                        document.getElementById('log-content').innerHTML = '';
                        alert('Log cleared successfully!');
                    } else {
                        alert('Error clearing log: ' + response.data);
                    }
                });
            }
        }
        
        function downloadLog() {
            window.location.href = ajaxurl + '?action=fli_download_log&nonce=<?php echo wp_create_nonce('fli_download_log'); ?>';
        }
        
        function refreshLog() {
            location.reload();
        }
        </script>
        <?php
    }
    
    /**
     * Clear log via AJAX
     */
    public function clear_log() {
        check_ajax_referer('fli_clear_log', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (file_exists($this->log_file)) {
            unlink($this->log_file);
        }
        
        wp_send_json_success('Log cleared successfully');
    }
    
    /**
     * Download log via AJAX
     */
    public function download_log() {
        check_ajax_referer('fli_download_log', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        if (!file_exists($this->log_file)) {
            wp_die('Log file not found');
        }
        
        $filename = 'fli-debug-log-' . date('Y-m-d-H-i-s') . '.txt';
        
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($this->log_file));
        
        readfile($this->log_file);
        exit;
    }
}

// Initialize the error logging system
FLI_Error_Logging::get_instance();

/**
 * Helper functions for easy logging
 */

/**
 * Log an error message
 * 
 * @param string $message Error message
 * @param array $context Additional context
 * @param string $source Source of the error
 */
function fli_log_error($message, $context = [], $source = '') {
    FLI_Error_Logging::get_instance()->log_error($message, $context, $source);
}

/**
 * Log a warning message
 * 
 * @param string $message Warning message
 * @param array $context Additional context
 * @param string $source Source of the warning
 */
function fli_log_warning($message, $context = [], $source = '') {
    FLI_Error_Logging::get_instance()->log_warning($message, $context, $source);
}

/**
 * Log a debug message
 * 
 * @param string $message Debug message
 * @param array $context Additional context
 * @param string $source Source of the debug info
 */
function fli_log_debug($message, $context = [], $source = '') {
    FLI_Error_Logging::get_instance()->log_debug($message, $context, $source);
}

/**
 * Log an info message
 * 
 * @param string $message Info message
 * @param array $context Additional context
 * @param string $source Source of the info
 */
function fli_log_info($message, $context = [], $source = '') {
    FLI_Error_Logging::get_instance()->log_info($message, $context, $source);
}
