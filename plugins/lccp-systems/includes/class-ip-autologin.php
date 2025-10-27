<?php
/**
 * IP Auto Login Module for LCCP Systems
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_IP_AutoLogin {
    
    private $allowed_ips = array();
    
    public function __construct() {
        // Get allowed IPs from settings
        $this->allowed_ips = get_option('lccp_autologin_ips', array(
            '72.132.26.73' => 'Jonathan-FYM'
        ));
        
        // Initialize auto-login
        add_action('init', array($this, 'check_ip_and_login'));
        
        // Extend cookie expiration
        add_filter('auth_cookie_expiration', array($this, 'extend_cookie_expiration'), 10, 3);
        
        // Admin settings
        add_action('admin_init', array($this, 'register_settings'));
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
        if (!get_option('lccp_enable_autologin', true)) {
            return;
        }
        
        $user_ip = $this->get_user_ip();
        
        // Check if IP is in allowed list
        if (isset($this->allowed_ips[$user_ip])) {
            $username = $this->allowed_ips[$user_ip];
            $user = get_user_by('login', $username);
            
            if ($user) {
                // Set authentication cookies with extended expiration
                $remember = true;
                wp_set_auth_cookie($user->ID, $remember);
                wp_set_current_user($user->ID);
                
                // Log the auto-login event
                $this->log_autologin($username, $user_ip);
                
                // Redirect to avoid any issues
                wp_redirect(home_url());
                exit;
            }
        }
    }
    
    public function extend_cookie_expiration($expiration, $user_id, $remember) {
        // Check if this user has auto-login enabled
        $user = get_user_by('ID', $user_id);
        if ($user) {
            $user_ip = $this->get_user_ip();
            if (isset($this->allowed_ips[$user_ip]) && $this->allowed_ips[$user_ip] === $user->user_login) {
                // Set cookie to expire in 1 year for auto-login users
                return YEAR_IN_SECONDS;
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
    
    private function log_autologin($username, $ip) {
        if (get_option('lccp_log_autologins', true)) {
            error_log(sprintf(
                '[LCCP Auto Login] User %s automatically logged in from IP %s at %s',
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
                'time' => current_time('mysql')
            );
            
            update_option('lccp_autologin_log', $log);
        }
    }
    
    public function register_settings() {
        register_setting('lccp_systems_settings', 'lccp_enable_autologin');
        register_setting('lccp_systems_settings', 'lccp_autologin_ips');
        register_setting('lccp_systems_settings', 'lccp_log_autologins');
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