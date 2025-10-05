<?php
/*
Plugin Name: Phunk Plugin Logger
Description: Logs resource usage for each plugin and emails the log daily.
Version: 1.4
Author: Jonathan Albiar
Author URI: https://elephunkie.com
Text Domain: phunk-plugin-logger
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!function_exists('phunk_log')) {
    function phunk_log($message) {
        $log_file = WP_CONTENT_DIR . '/plugin_resource_usage.log';
        if (!file_exists($log_file)) {
            file_put_contents($log_file, '');
            chmod($log_file, 0644);
        }
        error_log($message . "\n", 3, $log_file);
    }
}

if (!function_exists('log_plugin_resource_usage')) {
    function log_plugin_resource_usage() {
        $active_plugins = get_option('active_plugins');
        foreach ($active_plugins as $plugin_file) {
            try {
                $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file);
                $start_time = microtime(true);
                $start_memory = memory_get_usage();

                // Attempt to include and simulate plugin loading
                @include_once WP_PLUGIN_DIR . '/' . $plugin_file;

                $elapsed_time = microtime(true) - $start_time;
                $memory_used = memory_get_usage() - $start_memory;

                phunk_log("Plugin: " . $plugin_data['Name'] . " - Elapsed Time: " . $elapsed_time . " seconds, Memory Used: " . $memory_used . " bytes");
            } catch (Exception $e) {
                phunk_log("Error loading plugin: " . $plugin_data['Name'] . " - Error: " . $e->getMessage());
            } catch (Error $e) {
                phunk_log("Fatal error loading plugin: " . $plugin_data['Name'] . " - Error: " . $e->getMessage());
            }
        }
    }
    add_action('wp_loaded', 'log_plugin_resource_usage');
}

if (!function_exists('send_daily_log')) {
    function send_daily_log() {
        $to = 'jonathan@fearlessliving.org';
        $date = date('Y-m-d');
        $subject = 'Daily Plugin Resource Usage Log - ' . $date;
        $log_file = WP_CONTENT_DIR . '/plugin_resource_usage.log';
        $php_log_file = ini_get('error_log'); // Path to the PHP error log file

        $message = '';

        if (file_exists($log_file)) {
            $message .= "=== Plugin Resource Usage ===\n";
            $message .= file_get_contents($log_file);
        }

        if (file_exists($php_log_file)) {
            $message .= "\n\n=== PHP Log Errors ===\n";
            $php_errors = file_get_contents($php_log_file);
            // Limit the size of the log sent to avoid huge emails
            $message .= substr($php_errors, -10000); // Last 10,000 characters of the log
        }

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: support@fearlessliving.org'
        );

        if (wp_mail($to, $subject, $message, $headers)) {
            // Clear the plugin log file after sending the email
            file_put_contents($log_file, '');
            // Optionally clear the PHP error log if needed
            // file_put_contents($php_log_file, '');
        }
    }

    if (!wp_next_scheduled('phunk_daily_log_event')) {
        wp_schedule_event(strtotime('17:05:00'), 'daily', 'phunk_daily_log_event');
    }

    add_action('phunk_daily_log_event', 'send_daily_log');
}

if (!function_exists('phunk_plugin_logger_deactivate')) {
    function phunk_plugin_logger_deactivate() {
        wp_clear_scheduled_hook('phunk_daily_log_event');
    }

    register_deactivation_hook(__FILE__, 'phunk_plugin_logger_deactivate');
}
?>
