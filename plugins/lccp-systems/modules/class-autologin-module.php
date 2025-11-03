<?php
/**
 * AutoLogin Module for LCCP Systems
 * Modular version with feature toggle support
 *
 * @package LCCP Systems
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_AutoLogin_Module extends LCCP_Module {
    
    protected $module_id = 'autologin';
    protected $module_name = 'IP Auto-Login';
    protected $module_description = 'Automatic login functionality based on IP addresses for trusted networks.';
    protected $module_version = '1.0.0';
    protected $module_dependencies = array();
    protected $module_settings = array(
        'enable_autologin' => false,
        'trusted_ips' => array(),
        'auto_logout_time' => 3600,
        'enable_logging' => true,
        'default_role' => 'subscriber',
        'bypass_2fa' => false,
        'require_https' => true
    );
    
    protected function init() {
        // Only initialize if module is enabled
        if (!$this->is_enabled()) {
            return;
        }
        
        // Auto-login functionality
        if ($this->get_setting('enable_autologin')) {
            $this->init_autologin();
        }
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_page'));
        
        // AJAX handlers
        add_action('wp_ajax_lccp_test_ip', array($this, 'ajax_test_ip'));
        add_action('wp_ajax_lccp_add_trusted_ip', array($this, 'ajax_add_trusted_ip'));
        add_action('wp_ajax_lccp_remove_trusted_ip', array($this, 'ajax_remove_trusted_ip'));
    }
    
    /**
     * Get a specific setting value
     */
    private function get_setting($key) {
        $settings = $this->get_settings();
        return isset($settings[$key]) ? $settings[$key] : null;
    }
    
    /**
     * Initialize auto-login functionality
     */
    private function init_autologin() {
        // Check for auto-login on init
        add_action('init', array($this, 'check_autologin'), 1);
        
        // Handle logout
        add_action('wp_logout', array($this, 'handle_logout'));
        
        // Add login hooks
        add_action('wp_login', array($this, 'handle_login'), 10, 2);
        
        // Security headers
        add_action('wp_head', array($this, 'add_security_headers'));
    }
    
    /**
     * Check for auto-login
     */
    public function check_autologin() {
        // Skip if user is already logged in
        if (is_user_logged_in()) {
            return;
        }
        
        // Skip in admin area
        if (is_admin()) {
            return;
        }
        
        // Skip AJAX requests
        if (wp_doing_ajax()) {
            return;
        }
        
        // Get client IP
        $client_ip = $this->get_client_ip();
        
        // Check if IP is trusted
        if (!$this->is_ip_trusted($client_ip)) {
            return;
        }
        
        // Check if HTTPS is required
        if ($this->get_setting('require_https') && !is_ssl()) {
            return;
        }
        
        // Perform auto-login
        $this->perform_autologin($client_ip);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',     // CloudFlare
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Check if IP is trusted
     */
    private function is_ip_trusted($ip) {
        $trusted_ips = $this->get_setting('trusted_ips');
        
        if (empty($trusted_ips)) {
            return false;
        }
        
        foreach ($trusted_ips as $trusted_ip) {
            if ($this->ip_in_range($ip, $trusted_ip)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if IP is in range
     */
    private function ip_in_range($ip, $range) {
        if (strpos($range, '/') !== false) {
            // CIDR notation
            list($subnet, $mask) = explode('/', $range);
            $ip_long = ip2long($ip);
            $subnet_long = ip2long($subnet);
            $mask_long = -1 << (32 - $mask);
            
            return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
        } else {
            // Single IP
            return $ip === $range;
        }
    }
    
    /**
     * Perform auto-login
     */
    private function perform_autologin($ip) {
        // Get default user for auto-login
        $default_user = $this->get_default_autologin_user();
        
        if (!$default_user) {
            $this->log_autologin_attempt($ip, 'failed', 'No default user found');
            return;
        }
        
        // Check if user exists and is active
        if (!$this->is_user_active($default_user)) {
            $this->log_autologin_attempt($ip, 'failed', 'Default user is not active');
            return;
        }
        
        // Perform login
        wp_set_current_user($default_user->ID);
        wp_set_auth_cookie($default_user->ID, true);
        
        // Update last login
        update_user_meta($default_user->ID, 'last_login', current_time('mysql'));
        update_user_meta($default_user->ID, 'last_login_ip', $ip);
        
        // Log successful auto-login
        $this->log_autologin_attempt($ip, 'success', 'Auto-login successful');
        
        // Redirect to avoid showing login page
        if (!wp_redirect(home_url())) {
            exit;
        }
    }
    
    /**
     * Get default user for auto-login
     */
    private function get_default_autologin_user() {
        $default_role = $this->get_setting('default_role');
        
        // Get users with the default role
        $users = get_users(array(
            'role' => $default_role,
            'number' => 1,
            'orderby' => 'ID',
            'order' => 'ASC'
        ));
        
        return !empty($users) ? $users[0] : null;
    }
    
    /**
     * Check if user is active
     */
    private function is_user_active($user) {
        // Check if user account is active
        if ($user->user_status != 0) {
            return false;
        }
        
        // Check if user has required capabilities
        if (!$user->has_cap('read')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Handle logout
     */
    public function handle_logout() {
        $user_id = get_current_user_id();
        $ip = $this->get_client_ip();
        
        // Log logout
        $this->log_autologin_attempt($ip, 'logout', 'User logged out', $user_id);
        
        // Clear auto-login session
        delete_user_meta($user_id, 'autologin_session');
    }
    
    /**
     * Handle login
     */
    public function handle_login($user_login, $user) {
        $ip = $this->get_client_ip();
        
        // Log manual login
        $this->log_autologin_attempt($ip, 'manual_login', 'Manual login', $user->ID);
        
        // Update last login info
        update_user_meta($user->ID, 'last_login', current_time('mysql'));
        update_user_meta($user->ID, 'last_login_ip', $ip);
    }
    
    /**
     * Add security headers
     */
    public function add_security_headers() {
        if ($this->get_setting('require_https')) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
    }
    
    /**
     * Log auto-login attempt
     */
    private function log_autologin_attempt($ip, $status, $message, $user_id = null) {
        if (!$this->get_setting('enable_logging')) {
            return;
        }
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'lccp_autologin_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'ip_address' => $ip,
                'user_id' => $user_id,
                'status' => $status,
                'message' => $message,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Add admin page
     */
    public function add_admin_page() {
        add_submenu_page(
            'lccp-systems',
            __('Auto-Login', 'lccp-systems'),
            __('Auto-Login', 'lccp-systems'),
            'manage_options',
            'lccp-autologin',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $settings = $this->get_settings();
        $trusted_ips = $settings['trusted_ips'];
        $logs = $this->get_recent_logs();
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('IP Auto-Login Settings', 'lccp-systems'); ?></h1>
            
            <div class="lccp-autologin-dashboard">
                <div class="lccp-autologin-settings">
                    <h2><?php esc_html_e('Settings', 'lccp-systems'); ?></h2>
                    <form method="post" action="options.php">
                        <?php settings_fields('lccp_autologin_settings'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('Enable Auto-Login', 'lccp-systems'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="lccp_autologin_settings[enable_autologin]" 
                                               value="1" <?php checked($settings['enable_autologin'], true); ?> />
                                        <?php esc_html_e('Enable automatic login for trusted IP addresses', 'lccp-systems'); ?>
                                    </label>
                                    <p class="description"><?php esc_html_e('Warning: This feature bypasses normal authentication. Use with caution.', 'lccp-systems'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php esc_html_e('Default Role', 'lccp-systems'); ?></th>
                                <td>
                                    <select name="lccp_autologin_settings[default_role]">
                                        <?php
                                        $roles = wp_roles()->get_names();
                                        foreach ($roles as $role_key => $role_name) {
                                            echo '<option value="' . esc_attr($role_key) . '" ' . selected($settings['default_role'], $role_key, false) . '>' . esc_html($role_name) . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <p class="description"><?php esc_html_e('Role assigned to auto-logged users', 'lccp-systems'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php esc_html_e('Auto-Logout Time', 'lccp-systems'); ?></th>
                                <td>
                                    <input type="number" name="lccp_autologin_settings[auto_logout_time]" 
                                           value="<?php echo esc_attr($settings['auto_logout_time']); ?>" 
                                           min="300" max="86400" />
                                    <p class="description"><?php esc_html_e('Auto-logout time in seconds (300-86400)', 'lccp-systems'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php esc_html_e('Require HTTPS', 'lccp-systems'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="lccp_autologin_settings[require_https]" 
                                               value="1" <?php checked($settings['require_https'], true); ?> />
                                        <?php esc_html_e('Only allow auto-login over HTTPS connections', 'lccp-systems'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php esc_html_e('Enable Logging', 'lccp-systems'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="lccp_autologin_settings[enable_logging]" 
                                               value="1" <?php checked($settings['enable_logging'], true); ?> />
                                        <?php esc_html_e('Log all auto-login attempts', 'lccp-systems'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(); ?>
                    </form>
                </div>
                
                <div class="lccp-trusted-ips">
                    <h2><?php esc_html_e('Trusted IP Addresses', 'lccp-systems'); ?></h2>
                    <div class="lccp-ip-management">
                        <div class="lccp-add-ip">
                            <input type="text" id="new-ip" placeholder="<?php esc_attr_e('Enter IP address or CIDR range', 'lccp-systems'); ?>" />
                            <button type="button" class="button" onclick="addTrustedIP()">
                                <?php esc_html_e('Add IP', 'lccp-systems'); ?>
                            </button>
                        </div>
                        
                        <div class="lccp-ip-list">
                            <?php if (empty($trusted_ips)): ?>
                                <p><?php esc_html_e('No trusted IP addresses configured.', 'lccp-systems'); ?></p>
                            <?php else: ?>
                                <ul>
                                    <?php foreach ($trusted_ips as $ip): ?>
                                        <li>
                                            <span class="lccp-ip-address"><?php echo esc_html($ip); ?></span>
                                            <button type="button" class="button button-small" onclick="removeTrustedIP('<?php echo esc_js($ip); ?>')">
                                                <?php esc_html_e('Remove', 'lccp-systems'); ?>
                                            </button>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="lccp-autologin-logs">
                    <h2><?php esc_html_e('Recent Logs', 'lccp-systems'); ?></h2>
                    <div class="lccp-logs-table">
                        <?php if (empty($logs)): ?>
                            <p><?php esc_html_e('No logs available.', 'lccp-systems'); ?></p>
                        <?php else: ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('IP Address', 'lccp-systems'); ?></th>
                                        <th><?php esc_html_e('Status', 'lccp-systems'); ?></th>
                                        <th><?php esc_html_e('Message', 'lccp-systems'); ?></th>
                                        <th><?php esc_html_e('Date', 'lccp-systems'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?php echo esc_html($log->ip_address); ?></td>
                                            <td>
                                                <span class="lccp-status-<?php echo esc_attr($log->status); ?>">
                                                    <?php echo esc_html(ucfirst($log->status)); ?>
                                                </span>
                                            </td>
                                            <td><?php echo esc_html($log->message); ?></td>
                                            <td><?php echo esc_html(date('M j, Y H:i', strtotime($log->created_at))); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .lccp-autologin-dashboard {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        
        .lccp-autologin-settings {
            grid-column: 1 / -1;
        }
        
        .lccp-trusted-ips,
        .lccp-autologin-logs {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .lccp-add-ip {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .lccp-add-ip input {
            flex: 1;
        }
        
        .lccp-ip-list ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .lccp-ip-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        .lccp-ip-address {
            font-family: monospace;
            font-weight: 500;
        }
        
        .lccp-status-success {
            color: #46b450;
            font-weight: 600;
        }
        
        .lccp-status-failed {
            color: #dc3232;
            font-weight: 600;
        }
        
        .lccp-status-logout {
            color: #666;
            font-weight: 600;
        }
        
        .lccp-status-manual_login {
            color: #007cba;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .lccp-autologin-dashboard {
                grid-template-columns: 1fr;
            }
        }
        </style>
        
        <script>
        function addTrustedIP() {
            var ip = document.getElementById('new-ip').value.trim();
            if (!ip) {
                alert('Please enter an IP address or CIDR range.');
                return;
            }
            
            jQuery.post(ajaxurl, {
                action: 'lccp_add_trusted_ip',
                ip: ip,
                nonce: '<?php echo wp_create_nonce('lccp_autologin_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            });
        }
        
        function removeTrustedIP(ip) {
            if (confirm('Are you sure you want to remove this IP address?')) {
                jQuery.post(ajaxurl, {
                    action: 'lccp_remove_trusted_ip',
                    ip: ip,
                    nonce: '<?php echo wp_create_nonce('lccp_autologin_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            }
        }
        </script>
        <?php
    }
    
    /**
     * Get recent logs
     */
    private function get_recent_logs($limit = 20) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'lccp_autologin_logs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return array();
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d",
            $limit
        ));
    }
    
    /**
     * AJAX handler for testing IP
     */
    public function ajax_test_ip() {
        check_ajax_referer('lccp_autologin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $ip = sanitize_text_field($_POST['ip']);
        $is_trusted = $this->is_ip_trusted($ip);
        
        wp_send_json_success(array(
            'ip' => $ip,
            'is_trusted' => $is_trusted
        ));
    }
    
    /**
     * AJAX handler for adding trusted IP
     */
    public function ajax_add_trusted_ip() {
        check_ajax_referer('lccp_autologin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $ip = sanitize_text_field($_POST['ip']);
        
        // Validate IP format
        if (!$this->validate_ip_format($ip)) {
            wp_send_json_error('Invalid IP format');
        }
        
        $settings = $this->get_settings();
        $trusted_ips = $settings['trusted_ips'];
        
        if (!in_array($ip, $trusted_ips)) {
            $trusted_ips[] = $ip;
            $settings['trusted_ips'] = $trusted_ips;
            $this->update_settings($settings);
        }
        
        wp_send_json_success('IP address added');
    }
    
    /**
     * AJAX handler for removing trusted IP
     */
    public function ajax_remove_trusted_ip() {
        check_ajax_referer('lccp_autologin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $ip = sanitize_text_field($_POST['ip']);
        
        $settings = $this->get_settings();
        $trusted_ips = $settings['trusted_ips'];
        
        $key = array_search($ip, $trusted_ips);
        if ($key !== false) {
            unset($trusted_ips[$key]);
            $settings['trusted_ips'] = array_values($trusted_ips);
            $this->update_settings($settings);
        }
        
        wp_send_json_success('IP address removed');
    }
    
    /**
     * Validate IP format
     */
    private function validate_ip_format($ip) {
        // Check for CIDR notation
        if (strpos($ip, '/') !== false) {
            list($subnet, $mask) = explode('/', $ip);
            return filter_var($subnet, FILTER_VALIDATE_IP) && $mask >= 0 && $mask <= 32;
        }
        
        // Check for single IP
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
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
        // Clear any auto-login sessions
        $this->clear_autologin_sessions();
    }
    
    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Auto-login logs table
        $table_name = $wpdb->prefix . 'lccp_autologin_logs';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            status varchar(20) NOT NULL,
            message text,
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ip_address (ip_address),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Clear auto-login sessions
     */
    private function clear_autologin_sessions() {
        global $wpdb;
        
        // Clear all auto-login sessions from user meta
        $wpdb->delete(
            $wpdb->usermeta,
            array('meta_key' => 'autologin_session'),
            array('%s')
        );
    }
}
