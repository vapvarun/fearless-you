<?php
/**
 * Module Settings Page for LCCP Systems
 * 
 * Provides UI for enabling/disabling individual modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Module_Settings_Page {
    
    private $module_manager;
    
    public function __construct($module_manager) {
        $this->module_manager = $module_manager;
        
        // Handle form submissions
        add_action('admin_init', array($this, 'handle_module_toggle'));
        
        // Note: AJAX handler is in the module manager class
    }
    
    /**
     * Render the module settings page
     */
    public function render_page() {
        $modules = $this->module_manager->get_modules();
        $categories = $this->module_manager->get_categories();
        $module_errors = get_transient('lccp_module_errors');
        
        // Include system status class
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-lccp-system-status.php';
        $system_status = LCCP_System_Status::get_status();
        
        ?>
        <div class="wrap lccp-modules-settings">
            <div class="lccp-header-with-status">
                <h1><?php _e('LCCP Modules Management', 'lccp-systems'); ?></h1>
                <div class="lccp-system-status-indicator">
                    <span class="status-label"><?php _e('System Status:', 'lccp-systems'); ?></span>
                    <span class="status-circle status-<?php echo esc_attr($system_status['overall']); ?>" 
                          title="<?php esc_attr_e('Click for details', 'lccp-systems'); ?>"
                          data-status="<?php echo esc_attr($system_status['overall']); ?>"></span>
                    <span class="status-text"><?php echo ucfirst($system_status['overall']); ?></span>
                    <button class="button button-link status-refresh" title="<?php esc_attr_e('Refresh status', 'lccp-systems'); ?>">
                        <span class="dashicons dashicons-update"></span>
                    </button>
                </div>
            </div>
            
            <div class="lccp-modules-header">
                <p><?php _e('Enable or disable individual modules to customize your LCCP Systems installation. Some modules depend on others and cannot be disabled if they have active dependencies.', 'lccp-systems'); ?></p>
                
                <div class="lccp-module-stats">
                    <?php
                    $enabled_count = 0;
                    foreach ($modules as $id => $module) {
                        if ($this->module_manager->is_module_enabled($id)) {
                            $enabled_count++;
                        }
                    }
                    ?>
                    <span class="stat-item">
                        <strong><?php echo $enabled_count; ?></strong> <?php _e('of', 'lccp-systems'); ?> 
                        <strong><?php echo count($modules); ?></strong> <?php _e('modules enabled', 'lccp-systems'); ?>
                    </span>
                </div>
            </div>
            
            <?php if (isset($_GET['message'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html($_GET['message']); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php echo esc_html($_GET['error']); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="lccp-modules-grid">
                <?php foreach ($categories as $category_id => $category_name): ?>
                    <?php $category_modules = $this->module_manager->get_modules_by_category($category_id); ?>
                    <?php if (!empty($category_modules)): ?>
                        <div class="module-category">
                            <h2 class="category-title"><?php echo esc_html($category_name); ?></h2>
                            
                            <div class="modules-list">
                                <?php foreach ($category_modules as $module_id => $module): ?>
                                    <?php $this->render_module_card($module_id, $module); ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <style>
        .lccp-modules-settings {
            max-width: 1200px;
            margin: 20px auto;
        }
        
        .lccp-header-with-status {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .lccp-system-status-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fff;
            padding: 10px 15px;
            border: 1px solid #ccd0d4;
            border-radius: 5px;
        }
        
        .status-label {
            font-weight: 600;
            color: #555;
        }
        
        .status-circle {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .status-circle.status-green {
            background-color: #4CAF50;
            box-shadow: 0 0 10px rgba(76, 175, 80, 0.5);
        }
        
        .status-circle.status-yellow {
            background-color: #FFC107;
            box-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
        }
        
        .status-circle.status-red {
            background-color: #F44336;
            box-shadow: 0 0 10px rgba(244, 67, 54, 0.5);
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 10px rgba(244, 67, 54, 0.5);
            }
            50% {
                box-shadow: 0 0 20px rgba(244, 67, 54, 0.8);
            }
            100% {
                box-shadow: 0 0 10px rgba(244, 67, 54, 0.5);
            }
        }
        
        .status-text {
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .status-refresh {
            padding: 0 5px !important;
            height: auto !important;
            line-height: 1 !important;
        }
        
        .status-refresh .dashicons {
            font-size: 18px;
            width: 18px;
            height: 18px;
            transition: transform 0.3s ease;
        }
        
        .status-refresh.refreshing .dashicons {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            100% { transform: rotate(360deg); }
        }
        
        .lccp-modules-header {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            margin-bottom: 20px;
        }
        
        .lccp-module-stats {
            margin-top: 10px;
            padding: 10px;
            background: #f0f0f1;
            border-radius: 3px;
            display: inline-block;
        }
        
        .module-category {
            margin-bottom: 30px;
        }
        
        .category-title {
            font-size: 1.3em;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ddd;
        }
        
        .modules-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .module-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 15px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .module-card:hover {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .module-card.disabled {
            opacity: 0.7;
            background: #f9f9f9;
        }
        
        .module-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        
        .module-title {
            font-size: 1.1em;
            font-weight: 600;
            margin: 0;
            flex: 1;
        }
        
        .module-toggle {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .module-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: #2271b1;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
        
        input:disabled + .toggle-slider {
            cursor: not-allowed;
            opacity: 0.5;
        }
        
        .module-description {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        
        .module-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 0.85em;
            color: #666;
        }
        
        .module-status { margin-top: 8px; }
        .lccp-status-active { color: #46b450; font-weight: 600; }
        .lccp-status-disabled { color: #dc3232; font-weight: 600; }
        .lccp-status-problem { color: #ffb900; font-weight: 600; }
        
        .module-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .module-meta-item .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        
        .module-dependencies {
            margin-top: 10px;
            padding: 10px;
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 3px;
            font-size: 0.9em;
        }
        
        .module-warning {
            margin-top: 10px;
            padding: 10px;
            background: #f8d7da;
            border: 1px solid #dc3545;
            border-radius: 3px;
            font-size: 0.9em;
            color: #721c24;
        }
        
        .module-loading {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.8);
            display: none;
            align-items: center;
            justify-content: center;
        }
        
        .module-loading.active {
            display: flex;
        }
        
        /* Status Modal Styles */
        .lccp-status-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .lccp-status-modal {
            background: #fff;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow: auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
        
        .lccp-status-modal .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .lccp-status-modal .modal-header h2 {
            margin: 0;
            font-size: 1.5em;
        }
        
        .lccp-status-modal .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            transition: color 0.3s;
        }
        
        .lccp-status-modal .modal-close:hover {
            color: #000;
        }
        
        .lccp-status-modal .modal-body {
            padding: 20px;
        }
        
        .lccp-status-modal .overall-status {
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2em;
        }
        
        .lccp-status-modal .status-checks {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .lccp-status-modal .status-check-item {
            padding: 10px;
            background: #fafafa;
            border-left: 3px solid #ddd;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .lccp-status-modal .status-check-item .status-circle {
            flex-shrink: 0;
        }
        
        .lccp-status-modal .last-check {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
            color: #666;
            font-size: 0.9em;
            text-align: center;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // System status functionality
            var statusInterval;
            
            function updateSystemStatus(force = false) {
                var $refreshBtn = $('.status-refresh');
                var $statusCircle = $('.status-circle');
                var $statusText = $('.status-text');
                
                $refreshBtn.addClass('refreshing');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'lccp_check_system_status',
                        force: force,
                        nonce: '<?php echo wp_create_nonce('lccp_system_status'); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            var status = response.data.overall;
                            
                            // Update status circle
                            $statusCircle.removeClass('status-green status-yellow status-red')
                                        .addClass('status-' + status)
                                        .attr('data-status', status);
                            
                            // Update status text
                            $statusText.text(status.charAt(0).toUpperCase() + status.slice(1));
                            
                            // Create detailed tooltip
                            var tooltip = 'System Status: ' + status.toUpperCase() + '\n\n';
                            if (response.data.checks) {
                                for (var key in response.data.checks) {
                                    var check = response.data.checks[key];
                                    tooltip += check.name + ': ' + check.message + '\n';
                                }
                            }
                            $statusCircle.attr('title', tooltip);
                        }
                    },
                    error: function() {
                        console.error('Failed to check system status');
                    },
                    complete: function() {
                        $refreshBtn.removeClass('refreshing');
                    }
                });
            }
            
            // Manual refresh button
            $('.status-refresh').on('click', function(e) {
                e.preventDefault();
                updateSystemStatus(true);
            });
            
            // Click on status circle for details
            $('.status-circle').on('click', function() {
                var $this = $(this);
                var status = $this.attr('data-status');
                
                // Show detailed status modal
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'lccp_check_system_status',
                        force: false,
                        nonce: '<?php echo wp_create_nonce('lccp_system_status'); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            showStatusModal(response.data);
                        }
                    }
                });
            });
            
            function showStatusModal(statusData) {
                var modal = '<div class="lccp-status-modal-overlay">';
                modal += '<div class="lccp-status-modal">';
                modal += '<div class="modal-header">';
                modal += '<h2>System Status Details</h2>';
                modal += '<button class="modal-close">&times;</button>';
                modal += '</div>';
                modal += '<div class="modal-body">';
                
                modal += '<div class="overall-status status-' + statusData.overall + '">';
                modal += '<span class="status-circle status-' + statusData.overall + '"></span>';
                modal += ' Overall Status: <strong>' + statusData.overall.toUpperCase() + '</strong>';
                modal += '</div>';
                
                if (statusData.checks) {
                    modal += '<div class="status-checks">';
                    for (var key in statusData.checks) {
                        var check = statusData.checks[key];
                        modal += '<div class="status-check-item">';
                        modal += '<span class="status-circle status-' + check.status + '"></span>';
                        modal += '<strong>' + check.name + ':</strong> ' + check.message;
                        if (check.url) {
                            modal += ' <small>(' + check.url + ')</small>';
                        }
                        modal += '</div>';
                    }
                    modal += '</div>';
                }
                
                if (statusData.last_check) {
                    modal += '<div class="last-check">Last checked: ' + statusData.last_check + '</div>';
                }
                
                modal += '</div>';
                modal += '</div>';
                modal += '</div>';
                
                $('body').append(modal);
                
                // Close modal handlers
                $('.lccp-status-modal-overlay, .modal-close').on('click', function(e) {
                    if (e.target === this) {
                        $('.lccp-status-modal-overlay').remove();
                    }
                });
            }
            
            // Auto-refresh status every 5 minutes
            statusInterval = setInterval(function() {
                updateSystemStatus(false);
            }, 300000);
            
            // Module toggle functionality
            $('.module-toggle input').on('change', function() {
                var $toggle = $(this);
                var $card = $toggle.closest('.module-card');
                var moduleId = $toggle.data('module');
                var enabled = $toggle.prop('checked');
                
                // Show loading
                $card.find('.module-loading').addClass('active');
                
                // Make AJAX request
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'lccp_toggle_module',
                        module_id: moduleId,
                        enabled: enabled,
                        nonce: '<?php echo wp_create_nonce('lccp_module_toggle'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update UI
                            if (enabled) {
                                $card.removeClass('disabled');
                            } else {
                                $card.addClass('disabled');
                            }
                            
                            // Show success message
                            if (response.data.message) {
                                alert(response.data.message);
                            }
                            
                            // Reload if dependencies changed
                            if (response.data.reload) {
                                location.reload();
                            }
                        } else {
                            // Revert toggle
                            $toggle.prop('checked', !enabled);
                            alert(response.data.message || 'Error toggling module');
                        }
                    },
                    error: function() {
                        // Revert toggle
                        $toggle.prop('checked', !enabled);
                        alert('Error communicating with server');
                    },
                    complete: function() {
                        $card.find('.module-loading').removeClass('active');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render individual module card
     */
    private function render_module_card($module_id, $module) {
        $enabled = $this->module_manager->is_module_enabled($module_id);
        $can_manage = $this->module_manager->user_can_manage_module($module_id);
        $loaded_modules = $this->module_manager->get_loaded_modules();
        $is_loaded = isset($loaded_modules[$module_id]);
        $module_errors = get_transient('lccp_module_errors');
        $has_error = is_array($module_errors) && isset($module_errors[$module_id]);
        
        // Check if module can be disabled
        $dependents = array();
        foreach ($this->module_manager->get_modules() as $id => $m) {
            if (in_array($module_id, $m['dependencies']) && $this->module_manager->is_module_enabled($id)) {
                $dependents[] = $m['name'];
            }
        }
        
        $can_disable = empty($dependents) && $can_manage;
        
        ?>
        <div class="module-card <?php echo !$enabled ? 'disabled' : ''; ?>" data-module="<?php echo esc_attr($module_id); ?>">
            <div class="module-header">
                <h3 class="module-title"><?php echo esc_html($module['name']); ?></h3>
                <label class="module-toggle">
                    <input type="checkbox" 
                           data-module="<?php echo esc_attr($module_id); ?>"
                           <?php checked($enabled); ?>
                           <?php echo (!$can_manage || ($enabled && !$can_disable)) ? 'disabled' : ''; ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>
            
            <p class="module-description"><?php echo esc_html($module['description']); ?></p>
            
            <div class="module-meta">
                <?php if (!empty($module['requires_plugin'])): ?>
                    <span class="module-meta-item">
                        <span class="dashicons dashicons-admin-plugins"></span>
                        <?php _e('Requires plugin', 'lccp-systems'); ?>
                    </span>
                <?php endif; ?>
                
                <?php if (!empty($module['has_admin_page'])): ?>
                    <span class="module-meta-item">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php _e('Has settings', 'lccp-systems'); ?>
                    </span>
                <?php endif; ?>
                
                <?php if (!empty($module['security_warning'])): ?>
                    <span class="module-meta-item" style="color: #dc3545;">
                        <span class="dashicons dashicons-warning"></span>
                        <?php _e('Security sensitive', 'lccp-systems'); ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="module-status">
                <strong><?php _e('Status:', 'lccp-systems'); ?></strong>
                <?php if ($is_loaded): ?>
                    <span class="lccp-status-active"><?php esc_html_e('Active', 'lccp-systems'); ?></span>
                <?php elseif ($has_error): ?>
                    <span class="lccp-status-problem"><?php esc_html_e('Problem — Auto-disabled', 'lccp-systems'); ?></span>
                <?php else: ?>
                    <span class="lccp-status-disabled"><?php esc_html_e('Inactive', 'lccp-systems'); ?></span>
                <?php endif; ?>
                <?php if ($has_error && !empty($module_errors[$module_id]['error'])): ?>
                    <div class="module-warning" style="margin-top:8px;">
                        <?php echo esc_html($module_errors[$module_id]['error']); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($module['dependencies'])): ?>
                <div class="module-dependencies">
                    <strong><?php _e('Requires:', 'lccp-systems'); ?></strong>
                    <?php 
                    $dep_names = array();
                    foreach ($module['dependencies'] as $dep) {
                        if (isset($this->module_manager->get_modules()[$dep])) {
                            $dep_names[] = $this->module_manager->get_modules()[$dep]['name'];
                        }
                    }
                    echo esc_html(implode(', ', $dep_names));
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($dependents)): ?>
                <div class="module-warning">
                    <strong><?php _e('Cannot disable:', 'lccp-systems'); ?></strong>
                    <?php echo esc_html(implode(', ', $dependents)); ?> <?php _e('depend on this module', 'lccp-systems'); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($module['security_warning'])): ?>
                <div class="module-warning">
                    <strong><?php _e('Security Warning:', 'lccp-systems'); ?></strong>
                    <?php _e('This module affects security settings. Enable with caution.', 'lccp-systems'); ?>
                </div>
            <?php endif; ?>
            
            <div class="module-loading">
                <span class="spinner is-active"></span>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle module toggle via AJAX
     */
    public function ajax_toggle_module() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'lccp_module_toggle')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        $module_id = sanitize_text_field($_POST['module_id']);
        $enabled = filter_var($_POST['enabled'], FILTER_VALIDATE_BOOLEAN);
        
        // Check permissions
        if (!$this->module_manager->user_can_manage_module($module_id)) {
            wp_send_json_error(array('message' => __('You do not have permission to manage this module', 'lccp-systems')));
        }
        
        if ($enabled) {
            $result = $this->module_manager->enable_module($module_id);
            if ($result === true) {
                wp_send_json_success(array(
                    'message' => __('Module enabled successfully', 'lccp-systems'),
                    'reload' => false
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Failed to enable module. Check dependencies.', 'lccp-systems')
                ));
            }
        } else {
            $result = $this->module_manager->disable_module($module_id);
            if ($result === true) {
                wp_send_json_success(array(
                    'message' => __('Module disabled successfully', 'lccp-systems'),
                    'reload' => false
                ));
            } elseif (is_wp_error($result)) {
                wp_send_json_error(array(
                    'message' => $result->get_error_message()
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Failed to disable module', 'lccp-systems')
                ));
            }
        }
    }
    
    /**
     * Handle non-AJAX form submission
     */
    public function handle_module_toggle() {
        if (!isset($_POST['lccp_module_action'])) {
            return;
        }
        
        if (!wp_verify_nonce($_POST['_wpnonce'], 'lccp_module_settings')) {
            wp_die('Security check failed');
        }
        
        $module_id = sanitize_text_field($_POST['module_id']);
        $action = sanitize_text_field($_POST['lccp_module_action']);
        
        if ($action === 'enable') {
            $this->module_manager->enable_module($module_id);
            $message = __('Module enabled successfully', 'lccp-systems');
        } else {
            $this->module_manager->disable_module($module_id);
            $message = __('Module disabled successfully', 'lccp-systems');
        }
        
        wp_redirect(add_query_arg('message', urlencode($message), $_SERVER['HTTP_REFERER']));
        exit;
    }
}