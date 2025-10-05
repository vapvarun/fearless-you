<?php
/**
 * Plugin Name: Cleanup Utility
 * Description: Utility to clean up empty folders and manage directory structure
 * Version: 1.0.0
 * Author: Elephunkie
 */

if (!defined('ABSPATH')) {
    exit;
}

class Elephunkie_Cleanup_Utility {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_cleanup_empty_folders', [$this, 'ajax_cleanup_empty_folders']);
    }
    
    public function add_admin_menu() {
        add_management_page(
            'Cleanup Utility',
            'Cleanup Utility',
            'manage_options',
            'cleanup-utility',
            [$this, 'render_cleanup_page']
        );
    }
    
    public function render_cleanup_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        ?>
        <div class="wrap">
            <h1>Cleanup Utility</h1>
            <p>This utility helps clean up empty folders and manage directory structure in your WordPress installation.</p>
            
            <div class="cleanup-actions">
                <h2>Available Actions</h2>
                
                <div class="cleanup-section">
                    <h3>Empty Folder Cleanup</h3>
                    <p>Remove empty folders from the plugins directory to keep your installation clean.</p>
                    <button type="button" class="button button-primary" id="cleanup-empty-folders">
                        Clean Up Empty Folders
                    </button>
                    <div id="cleanup-results" style="margin-top: 10px;"></div>
                </div>
                
                <div class="cleanup-section" style="margin-top: 30px;">
                    <h3>Directory Scan</h3>
                    <p>Scan for potential cleanup opportunities in your WordPress directories.</p>
                    <button type="button" class="button button-secondary" id="scan-directories">
                        Scan Directories
                    </button>
                    <div id="scan-results" style="margin-top: 10px;"></div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#cleanup-empty-folders').on('click', function() {
                var button = $(this);
                var resultsDiv = $('#cleanup-results');
                
                button.prop('disabled', true).text('Cleaning...');
                resultsDiv.html('<p>Processing...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cleanup_empty_folders',
                        nonce: '<?php echo wp_create_nonce('cleanup_empty_folders'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            resultsDiv.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                            if (response.data.deleted_folders.length > 0) {
                                resultsDiv.append('<h4>Deleted Folders:</h4><ul>');
                                response.data.deleted_folders.forEach(function(folder) {
                                    resultsDiv.find('ul').append('<li>' + folder + '</li>');
                                });
                                resultsDiv.append('</ul>');
                            }
                        } else {
                            resultsDiv.html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                        }
                    },
                    error: function() {
                        resultsDiv.html('<div class="notice notice-error"><p>Ajax request failed</p></div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Clean Up Empty Folders');
                    }
                });
            });
            
            $('#scan-directories').on('click', function() {
                var button = $(this);
                var resultsDiv = $('#scan-results');
                
                button.prop('disabled', true).text('Scanning...');
                resultsDiv.html('<p>Scanning directories...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'scan_directories',
                        nonce: '<?php echo wp_create_nonce('scan_directories'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            resultsDiv.html('<div class="notice notice-info"><p>' + response.data.message + '</p></div>');
                            if (response.data.empty_folders.length > 0) {
                                resultsDiv.append('<h4>Empty Folders Found:</h4><ul>');
                                response.data.empty_folders.forEach(function(folder) {
                                    resultsDiv.find('ul').append('<li>' + folder + '</li>');
                                });
                                resultsDiv.append('</ul>');
                            }
                        } else {
                            resultsDiv.html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                        }
                    },
                    error: function() {
                        resultsDiv.html('<div class="notice notice-error"><p>Ajax request failed</p></div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Scan Directories');
                    }
                });
            });
        });
        </script>
        
        <style>
        .cleanup-section {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
            background: #f9f9f9;
        }
        .cleanup-section h3 {
            margin-top: 0;
        }
        </style>
        <?php
    }
    
    public function ajax_cleanup_empty_folders() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cleanup_empty_folders')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $deleted_folders = [];
        $plugin_dir = WP_PLUGIN_DIR;
        
        try {
            $this->delete_empty_folders($plugin_dir, $deleted_folders);
            
            $message = count($deleted_folders) > 0 
                ? sprintf('Cleanup completed. %d empty folders were removed.', count($deleted_folders))
                : 'Cleanup completed. No empty folders found.';
                
            wp_send_json_success([
                'message' => $message,
                'deleted_folders' => $deleted_folders
            ]);
        } catch (Exception $e) {
            wp_send_json_error('Error during cleanup: ' . $e->getMessage());
        }
    }
    
    public function ajax_scan_directories() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'scan_directories')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $empty_folders = [];
        $plugin_dir = WP_PLUGIN_DIR;
        
        try {
            $this->scan_empty_folders($plugin_dir, $empty_folders);
            
            $message = count($empty_folders) > 0 
                ? sprintf('Scan completed. %d empty folders found.', count($empty_folders))
                : 'Scan completed. No empty folders found.';
                
            wp_send_json_success([
                'message' => $message,
                'empty_folders' => $empty_folders
            ]);
        } catch (Exception $e) {
            wp_send_json_error('Error during scan: ' . $e->getMessage());
        }
    }
    
    private function delete_empty_folders($dir, &$deleted_folders) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $is_empty = true;
        $files = glob($dir . '/*');
        
        foreach ($files as $file) {
            if (is_dir($file)) {
                if (!$this->delete_empty_folders($file, $deleted_folders)) {
                    $is_empty = false;
                }
            } else {
                $is_empty = false;
            }
        }
        
        if ($is_empty && $dir !== WP_PLUGIN_DIR) {
            if (rmdir($dir)) {
                $deleted_folders[] = str_replace(WP_PLUGIN_DIR, '', $dir);
            }
        }
        
        return $is_empty;
    }
    
    private function scan_empty_folders($dir, &$empty_folders) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $is_empty = true;
        $files = glob($dir . '/*');
        
        foreach ($files as $file) {
            if (is_dir($file)) {
                if (!$this->scan_empty_folders($file, $empty_folders)) {
                    $is_empty = false;
                }
            } else {
                $is_empty = false;
            }
        }
        
        if ($is_empty && $dir !== WP_PLUGIN_DIR) {
            $empty_folders[] = str_replace(WP_PLUGIN_DIR, '', $dir);
        }
        
        return $is_empty;
    }
}

// Initialize the Cleanup Utility
new Elephunkie_Cleanup_Utility();

// Add AJAX handlers
add_action('wp_ajax_scan_directories', function() {
    $cleanup = new Elephunkie_Cleanup_Utility();
    $cleanup->ajax_scan_directories();
});