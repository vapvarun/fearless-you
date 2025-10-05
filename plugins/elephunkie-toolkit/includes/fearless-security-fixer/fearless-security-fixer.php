<?php
/**
 * Plugin Name: Fearless Security Fixer
 * Description: Fixes critical security vulnerabilities and code issues in the Fearless Living WordPress installation
 * Version: 1.0.0
 * Author: Fearless Living Dev Team
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class Elephunkie_Fearless_Security_Fixer {
    
    private $security_log = [];
    
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_ajax_fearless_security_check', [$this, 'ajax_security_check']);
        add_action('wp_ajax_nopriv_fearless_security_check', [$this, 'ajax_security_check']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_notices', [$this, 'show_security_warnings']);
    }
    
    public function init() {
        $this->fix_child_theme_security();
        $this->fix_deprecated_functions();
        $this->add_security_headers();
        $this->monitor_wp_config_security();
    }
    
    // BuddyBoss Child Theme Security Fixes
    private function fix_child_theme_security() {
        if (!is_admin()) {
            add_action('wp_ajax_fearless_secure_login', [$this, 'secure_ajax_login']);
            add_action('wp_ajax_nopriv_fearless_secure_login', [$this, 'secure_ajax_login']);
        }
        
        remove_action('wp_ajax_nopriv_user_signup_login', 'user_signup_login');
        remove_action('wp_ajax_user_signup_login', 'user_signup_login');
        
        add_filter('wp_kses_allowed_html', [$this, 'secure_kses_allowed_html'], 10, 2);
    }
    
    public function secure_ajax_login() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'fearless_secure_login')) {
            wp_die('Security check failed');
        }
        
        $email = sanitize_email($_POST['email'] ?? '');
        $password = sanitize_text_field($_POST['password'] ?? '');
        
        if (empty($email) || empty($password)) {
            wp_send_json_error('Missing required fields');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            wp_send_json_error('Invalid email format');
        }
        
        $user = wp_authenticate($email, $password);
        
        if (is_wp_error($user)) {
            $this->log_security_event('failed_login', $email, $_SERVER['REMOTE_ADDR'] ?? '');
            wp_send_json_error('Authentication failed');
        }
        
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);
        
        $this->log_security_event('successful_login', $email, $_SERVER['REMOTE_ADDR'] ?? '');
        wp_send_json_success(['redirect' => home_url()]);
    }
    
    public function secure_kses_allowed_html($allowed, $context) {
        if ($context === 'post') {
            unset($allowed['script']);
            unset($allowed['iframe']);
            unset($allowed['object']);
            unset($allowed['embed']);
        }
        return $allowed;
    }
    
    // Deprecated Functions Fix
    private function fix_deprecated_functions() {
        add_filter('deprecated_function_run', [$this, 'log_deprecated_function'], 10, 3);
        
        if (function_exists('create_function')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning"><p>Warning: Deprecated create_function() detected. Please update your code.</p></div>';
            });
        }
    }
    
    public function log_deprecated_function($function, $replacement, $version) {
        $this->log_security_event('deprecated_function', $function, "Deprecated in $version, use $replacement instead");
    }
    
    // Security Headers
    private function add_security_headers() {
        add_action('send_headers', function() {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
        });
    }
    
    // WP-Config Security Monitoring
    private function monitor_wp_config_security() {
        add_action('wp_loaded', function() {
            if (defined('WP_DEBUG') && WP_DEBUG === true && !WP_DEBUG_LOG) {
                $this->log_security_event('debug_mode_active', 'WP_DEBUG is enabled without logging', 'Production site should not have debug mode enabled');
            }
            
            if (!defined('DISALLOW_FILE_EDIT') || DISALLOW_FILE_EDIT !== true) {
                $this->log_security_event('file_editing_enabled', 'File editing is allowed', 'Consider disabling file editing for security');
            }
        });
    }
    
    // Admin Menu
    public function add_admin_menu() {
        add_management_page(
            'Security Report',
            'Security Report',
            'manage_options',
            'fearless-security-report',
            [$this, 'render_security_report']
        );
    }
    
    public function render_security_report() {
        $security_issues = $this->scan_security_issues();
        ?>
        <div class="wrap">
            <h1>Fearless Security Report</h1>
            
            <div class="security-status">
                <h2>Security Status</h2>
                <?php if (empty($security_issues)): ?>
                    <p class="security-good">✅ No critical security issues detected</p>
                <?php else: ?>
                    <p class="security-warning">⚠️ <?php echo count($security_issues); ?> security issues detected</p>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($security_issues)): ?>
            <div class="security-issues">
                <h3>Issues Found</h3>
                <ul>
                    <?php foreach ($security_issues as $issue): ?>
                        <li class="security-issue-<?php echo esc_attr($issue['level']); ?>">
                            <strong><?php echo esc_html($issue['title']); ?></strong>: 
                            <?php echo esc_html($issue['description']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="security-log">
                <h3>Recent Security Events</h3>
                <?php if (!empty($this->security_log)): ?>
                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Event</th>
                                <th>Details</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($this->security_log, -10) as $event): ?>
                                <tr>
                                    <td><?php echo esc_html($event['timestamp']); ?></td>
                                    <td><?php echo esc_html($event['event']); ?></td>
                                    <td><?php echo esc_html($event['details']); ?></td>
                                    <td><?php echo esc_html($event['ip']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No security events logged yet.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .security-good { color: #46b450; font-weight: bold; }
        .security-warning { color: #ffb900; font-weight: bold; }
        .security-issue-high { color: #dc3232; }
        .security-issue-medium { color: #ffb900; }
        .security-issue-low { color: #00a0d2; }
        </style>
        <?php
    }
    
    private function scan_security_issues() {
        $issues = [];
        
        // Check for hardcoded credentials
        if (defined('AWS_ACCESS_KEY_ID') && AWS_ACCESS_KEY_ID === 'PLEASE_REPLACE_WITH_NEW_KEY') {
            $issues[] = [
                'level' => 'high',
                'title' => 'Placeholder AWS Credentials',
                'description' => 'AWS credentials are still set to placeholder values'
            ];
        }
        
        // Check debug mode
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            $issues[] = [
                'level' => 'medium',
                'title' => 'Debug Mode Enabled',
                'description' => 'WP_DEBUG is enabled in production'
            ];
        }
        
        // Check file permissions
        if (is_writable(ABSPATH . 'wp-config.php')) {
            $issues[] = [
                'level' => 'high',
                'title' => 'wp-config.php Writable',
                'description' => 'wp-config.php file has write permissions'
            ];
        }
        
        return $issues;
    }
    
    private function log_security_event($event, $details, $ip) {
        $this->security_log[] = [
            'timestamp' => current_time('mysql'),
            'event' => $event,
            'details' => $details,
            'ip' => $ip
        ];
        
        // Store in database for persistence
        $log_option = get_option('fearless_security_log', []);
        $log_option[] = end($this->security_log);
        
        // Keep only last 100 entries
        if (count($log_option) > 100) {
            $log_option = array_slice($log_option, -100);
        }
        
        update_option('fearless_security_log', $log_option);
    }
    
    public function show_security_warnings() {
        $critical_issues = array_filter($this->scan_security_issues(), function($issue) {
            return $issue['level'] === 'high';
        });
        
        if (!empty($critical_issues)) {
            echo '<div class="notice notice-error"><p><strong>Security Alert:</strong> ' . count($critical_issues) . ' critical security issues detected. <a href="' . admin_url('tools.php?page=fearless-security-report') . '">View Security Report</a></p></div>';
        }
    }
    
    public function ajax_security_check() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'fearless_security_check')) {
            wp_send_json_error('Security check failed');
        }
        
        $issues = $this->scan_security_issues();
        wp_send_json_success(['issues' => $issues]);
    }
}

// Initialize the Fearless Security Fixer
new Elephunkie_Fearless_Security_Fixer();