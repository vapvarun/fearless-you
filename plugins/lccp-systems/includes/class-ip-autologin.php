<?php
/**
 * IP Auto Login Module for LCCP Systems
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_IP_AutoLogin {

    private $allowed_ips = array();
    private $max_attempts_per_hour = 5;
    private $attempt_cache_key = 'lccp_autologin_attempts';

    public function __construct() {
        // Get allowed IPs from settings (no default IPs for security)
        $this->allowed_ips = get_option('lccp_autologin_ips', array());

        // Initialize auto-login
        add_action('init', array($this, 'check_ip_and_login'));

        // Extend cookie expiration
        add_filter('auth_cookie_expiration', array($this, 'extend_cookie_expiration'), 10, 3);

        // Admin settings
        add_action('admin_init', array($this, 'register_settings'));

        // Admin notices for security warnings
        add_action('admin_notices', array($this, 'show_security_notices'));
    }
    
    public function check_ip_and_login() {
        // Don't run in admin area
        if (is_admin()) {
            return;
        }

        // Don't run if user is already logged in
        if (is_user_logged_in()) {
            return;
        }

        // Don't run if disabled
        if (!get_option('lccp_enable_autologin', false)) {
            return;
        }

        $user_ip = $this->get_user_ip();

        // Check rate limiting to prevent abuse
        if (!$this->check_rate_limit($user_ip)) {
            $this->log_security_event('Rate limit exceeded', $user_ip);
            return;
        }

        // Check if IP is in allowed list
        if (isset($this->allowed_ips[$user_ip])) {
            $username = $this->allowed_ips[$user_ip];
            $user = get_user_by('login', $username);

            if ($user && !$this->is_user_locked($user->ID)) {
                // Verify IP hasn't changed during session (for security)
                $this->store_verified_ip($user->ID, $user_ip);

                // Set authentication cookies with extended expiration
                $remember = true;
                wp_set_auth_cookie($user->ID, $remember);
                wp_set_current_user($user->ID);

                // Log the auto-login event
                $this->log_autologin($username, $user_ip, true);

                // Clear rate limit attempts for successful login
                $this->clear_rate_limit($user_ip);

                // Redirect to avoid any issues
                wp_redirect(home_url());
                exit;
            } else {
                // Failed login attempt
                $this->log_autologin($username, $user_ip, false);
                $this->increment_rate_limit($user_ip);
            }
        }
    }

    /**
     * Check if IP has exceeded rate limit
     */
    private function check_rate_limit($ip) {
        $attempts = get_transient($this->attempt_cache_key . '_' . md5($ip));
        return $attempts === false || $attempts < $this->max_attempts_per_hour;
    }

    /**
     * Increment rate limit counter
     */
    private function increment_rate_limit($ip) {
        $cache_key = $this->attempt_cache_key . '_' . md5($ip);
        $attempts = get_transient($cache_key);
        $attempts = $attempts ? $attempts + 1 : 1;
        set_transient($cache_key, $attempts, HOUR_IN_SECONDS);
    }

    /**
     * Clear rate limit counter
     */
    private function clear_rate_limit($ip) {
        delete_transient($this->attempt_cache_key . '_' . md5($ip));
    }

    /**
     * Check if user account is locked
     */
    private function is_user_locked($user_id) {
        $locked_until = get_user_meta($user_id, 'lccp_account_locked_until', true);
        if ($locked_until && $locked_until > time()) {
            return true;
        }
        return false;
    }

    /**
     * Store verified IP for session validation
     */
    private function store_verified_ip($user_id, $ip) {
        update_user_meta($user_id, 'lccp_last_verified_ip', $ip);
        update_user_meta($user_id, 'lccp_last_verified_time', time());
    }

    /**
     * Log security events
     */
    private function log_security_event($event, $ip, $details = '') {
        if (get_option('lccp_log_security_events', true)) {
            error_log(sprintf(
                '[LCCP Auto Login Security] %s from IP %s at %s. Details: %s',
                $event,
                $ip,
                current_time('mysql'),
                $details
            ));

            // Send admin notification for security events
            $this->send_security_alert($event, $ip, $details);
        }
    }

    /**
     * Send security alert to admin
     */
    private function send_security_alert($event, $ip, $details) {
        $admin_email = get_option('admin_email');
        $subject = '[LCCP Security Alert] Auto Login Event';
        $message = sprintf(
            "Security Event: %s\nIP Address: %s\nTime: %s\nDetails: %s",
            $event,
            $ip,
            current_time('mysql'),
            $details
        );

        wp_mail($admin_email, $subject, $message);
    }
    
    public function extend_cookie_expiration($expiration, $user_id, $remember) {
        // Check if this user has auto-login enabled
        $user = get_user_by('ID', $user_id);
        if ($user) {
            $user_ip = $this->get_user_ip();
            if (isset($this->allowed_ips[$user_ip]) && $this->allowed_ips[$user_ip] === $user->user_login) {
                // Reduced from 1 year to 30 days for better security
                $max_duration = get_option('lccp_autologin_duration', 30);
                return $max_duration * DAY_IN_SECONDS;
            }
        }

        // For remember me, set to 14 days
        if ($remember) {
            return 14 * DAY_IN_SECONDS;
        }

        // Default expiration
        return $expiration;
    }
    
    private function get_user_ip() {
        // Check for various IP headers in case of proxy/load balancer
        $ip_headers = array(
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        );
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Handle comma-separated IPs (X-Forwarded-For can contain multiple IPs)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        // Fallback to REMOTE_ADDR
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    private function log_autologin($username, $ip, $success = true) {
        if (get_option('lccp_log_autologins', true)) {
            $status = $success ? 'Success' : 'Failed';
            error_log(sprintf(
                '[LCCP Auto Login] %s - User %s from IP %s at %s',
                $status,
                $username,
                $ip,
                current_time('mysql')
            ));

            // Also store in database for admin review
            $log = get_option('lccp_autologin_log', array());

            // Keep only last 100 entries
            if (count($log) >= 100) {
                array_shift($log);
            }

            $log[] = array(
                'username' => $username,
                'ip' => $ip,
                'time' => current_time('mysql'),
                'success' => $success,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            );

            update_option('lccp_autologin_log', $log);

            // Alert admin on failed attempts
            if (!$success) {
                $this->log_security_event('Failed auto-login attempt', $ip, "Username: $username");
            }
        }
    }
    
    public function register_settings() {
        register_setting('lccp_systems_settings', 'lccp_enable_autologin');
        register_setting('lccp_systems_settings', 'lccp_autologin_ips');
        register_setting('lccp_systems_settings', 'lccp_log_autologins');
        register_setting('lccp_systems_settings', 'lccp_log_security_events');
        register_setting('lccp_systems_settings', 'lccp_autologin_duration');
    }

    /**
     * Show security notices in admin
     */
    public function show_security_notices() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'lccp') === false) {
            return;
        }

        if (get_option('lccp_enable_autologin', false) && !empty($this->allowed_ips)) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong>Security Notice:</strong> IP Auto-Login is enabled.
                    This feature bypasses normal WordPress authentication and should only be used in controlled environments.
                    <a href="<?php echo admin_url('admin.php?page=lccp-systems'); ?>">Review Settings</a>
                </p>
            </div>
            <?php
        }
    }
    
    public function get_settings_fields() {
        return array(
            array(
                'id' => 'lccp_enable_autologin',
                'title' => 'Enable Auto Login',
                'type' => 'checkbox',
                'default' => true,
                'description' => 'Enable automatic login from specified IP addresses'
            ),
            array(
                'id' => 'lccp_autologin_ips',
                'title' => 'Allowed IP Addresses',
                'type' => 'textarea',
                'description' => 'Enter IP addresses and usernames (one per line, format: IP|username)',
                'sanitize' => array($this, 'sanitize_ip_list')
            ),
            array(
                'id' => 'lccp_log_autologins',
                'title' => 'Log Auto Logins',
                'type' => 'checkbox',
                'default' => true,
                'description' => 'Keep a log of auto-login events'
            )
        );
    }
    
    public function sanitize_ip_list($input) {
        $ips = array();
        
        if (is_string($input)) {
            $lines = explode("\n", $input);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                if (strpos($line, '|') !== false) {
                    list($ip, $username) = explode('|', $line, 2);
                    $ip = trim($ip);
                    $username = trim($username);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        $ips[$ip] = sanitize_user($username);
                    }
                }
            }
        } elseif (is_array($input)) {
            $ips = $input;
        }
        
        return $ips;
    }
    
    public function display_admin_settings() {
        $enabled = get_option('lccp_enable_autologin', true);
        $ips = get_option('lccp_autologin_ips', array());
        $logging = get_option('lccp_log_autologins', true);
        ?>
        <h3>Auto Login Settings</h3>
        <table class="form-table">
            <tr>
                <th scope="row">Enable Auto Login</th>
                <td>
                    <label>
                        <input type="checkbox" name="lccp_enable_autologin" value="1" <?php checked($enabled); ?> />
                        Enable automatic login from specified IP addresses
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">Allowed IP Addresses</th>
                <td>
                    <textarea name="lccp_autologin_ips_text" rows="5" cols="50" class="large-text"><?php 
                        foreach ($ips as $ip => $username) {
                            echo esc_html($ip . '|' . $username) . "\n";
                        }
                    ?></textarea>
                    <p class="description">
                        Enter one IP address per line in the format: IP|username<br>
                        Example: 72.132.26.73|Jonathan-FYM
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">Log Auto Logins</th>
                <td>
                    <label>
                        <input type="checkbox" name="lccp_log_autologins" value="1" <?php checked($logging); ?> />
                        Keep a log of auto-login events
                    </label>
                </td>
            </tr>
        </table>
        
        <?php if ($logging): ?>
            <h3>Recent Auto Login Activity</h3>
            <?php $this->display_autologin_log(); ?>
        <?php endif;
    }
    
    private function display_autologin_log() {
        $log = get_option('lccp_autologin_log', array());
        
        if (empty($log)) {
            echo '<p>No auto-login events logged yet.</p>';
            return;
        }
        
        // Show last 20 entries
        $recent_log = array_slice($log, -20);
        $recent_log = array_reverse($recent_log);
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Username</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_log as $entry): ?>
                    <tr>
                        <td><?php echo esc_html($entry['time']); ?></td>
                        <td><?php echo esc_html($entry['username']); ?></td>
                        <td><?php echo esc_html($entry['ip']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p>
            <button type="button" class="button" onclick="if(confirm('Clear auto-login log?')) { document.getElementById('clear_autologin_log').value='1'; document.getElementById('lccp-settings-form').submit(); }">
                Clear Log
            </button>
            <input type="hidden" id="clear_autologin_log" name="clear_autologin_log" value="0">
        </p>
        <?php
    }
    
    public function handle_settings_save() {
        // Handle IP list
        if (isset($_POST['lccp_autologin_ips_text'])) {
            $ips = $this->sanitize_ip_list($_POST['lccp_autologin_ips_text']);
            update_option('lccp_autologin_ips', $ips);
        }
        
        // Handle log clearing
        if (isset($_POST['clear_autologin_log']) && $_POST['clear_autologin_log'] === '1') {
            update_option('lccp_autologin_log', array());
        }
    }
}