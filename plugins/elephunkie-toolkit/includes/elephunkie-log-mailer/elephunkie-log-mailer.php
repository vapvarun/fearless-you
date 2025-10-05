<?php
/**
 * Plugin Name: Elephunkie Log Mailer
 * Description: Scans all log files, sends the last 50 unique PHP errors every Monday at 9 AM, and purges old entries.
 * Version: 1.2.0
 * Author: Jonathan Albiar & ChatGPT
 * Author URI: https://elephunkie.com
 * Text Domain: elephunkie-log-mailer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Elephunkie_Log_Mailer {
    
    private $log_dir;
    private $log_to;
    private $log_from;
    private $preserve_filename;
    
    public function __construct() {
        // Configuration
        $this->log_dir = '/home/master/applications/your_app/logs';
        $this->log_to = ['support@fearlessliving.org', 'jonathan@fearlessliving.org'];
        $this->log_from = 'no-reply@fearlessliving.org';
        $this->preserve_filename = 'error-preserved.log';
        
        // Setup hooks
        add_action('init', [$this, 'setup_cron']);
        add_action('elephunkie_send_error_log', [$this, 'send_error_log_email']);
        
        // Admin settings
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    public function setup_cron() {
        if (!wp_next_scheduled('elephunkie_send_error_log')) {
            wp_schedule_event(strtotime('next monday 09:00'), 'weekly', 'elephunkie_send_error_log');
        }
    }
    
    public function register_settings() {
        register_setting('elephunkie_toolkit', 'elephunkie_log_mailer_dir');
        register_setting('elephunkie_toolkit', 'elephunkie_log_mailer_emails');
    }
    
    public function send_error_log_email() {
        $logDir = get_option('elephunkie_log_mailer_dir', $this->log_dir);
        
        if (!is_dir($logDir)) {
            error_log("Elephunkie Log Mailer: Directory not found: $logDir");
            return;
        }

        $logFiles = glob($logDir . '/*.log');
        if (empty($logFiles)) {
            error_log("Elephunkie Log Mailer: No .log files found.");
            return;
        }

        // Sort newest first
        usort($logFiles, fn($a, $b) => filemtime($b) - filemtime($a));

        $errorLines = [];

        foreach ($logFiles as $file) {
            if (basename($file) === $this->preserve_filename) {
                continue; // skip the preserved file
            }

            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (stripos($line, 'error') !== false) {
                    $errorLines[] = "[" . basename($file) . "] " . $line;
                }
            }
        }

        $unique = array_unique($errorLines);
        $last50 = array_slice($unique, -50);

        if (empty($last50)) {
            error_log("Elephunkie Log Mailer: No error entries to send.");
            return;
        }

        $body = "Last 50 unique PHP error log entries:\n\n" . implode("\n", $last50);
        $headers = ['From: Fearless Living Logs <' . $this->log_from . '>'];

        $recipients = get_option('elephunkie_log_mailer_emails', implode(',', $this->log_to));
        $recipients = explode(',', $recipients);

        foreach ($recipients as $recipient) {
            wp_mail(trim($recipient), 'Last 50 PHP Errors - ' . date('F j, Y'), $body, $headers);
        }

        // Delete all original log files (except preserved)
        foreach ($logFiles as $file) {
            if (basename($file) !== $this->preserve_filename) {
                unlink($file);
            }
        }

        // Save the last 50 errors in a new preserved log file
        $preservePath = $logDir . '/' . $this->preserve_filename;
        file_put_contents($preservePath, implode("\n", $last50));
    }
    
    public function deactivate() {
        wp_clear_scheduled_hook('elephunkie_send_error_log');
    }
}

// Initialize the log mailer
new Elephunkie_Log_Mailer();