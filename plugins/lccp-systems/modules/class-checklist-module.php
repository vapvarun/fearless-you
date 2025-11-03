<?php
/**
 * Checklist Module for LCCP Systems
 * Interactive checklists for course completion tracking
 *
 * @package LCCP Systems
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Checklist_Module extends LCCP_Module {
    
    protected $module_id = 'checklist';
    protected $module_name = 'Checklist Management';
    protected $module_description = 'Interactive checklists for course completion tracking and progress management.';
    protected $module_version = '1.0.0';
    protected $module_dependencies = array();
    protected $module_settings = array(
        'enable_checklists' => true,
        'enable_progress_tracking' => true,
        'enable_notifications' => true,
        'auto_save_progress' => true,
        'enable_custom_checklists' => true,
        'default_checklist_templates' => array()
    );
    
    protected function init() {
        if (!$this->is_enabled()) {
            return;
        }
        
        // Register shortcodes
        add_shortcode('lccp_checklist', array($this, 'render_checklist'));
        add_shortcode('lccp_progress_checklist', array($this, 'render_progress_checklist'));
        
        // AJAX handlers
        add_action('wp_ajax_lccp_update_checklist_item', array($this, 'ajax_update_checklist_item'));
        add_action('wp_ajax_lccp_get_checklist_progress', array($this, 'ajax_get_checklist_progress'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_page'));
    }
    
    private function get_setting($key) {
        $settings = $this->get_settings();
        return isset($settings[$key]) ? $settings[$key] : null;
    }
    
    /**
     * Render checklist shortcode
     */
    public function render_checklist($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'title' => 'Checklist',
            'items' => '',
            'user_id' => get_current_user_id()
        ), $atts);
        
        if (!$atts['user_id']) {
            return '<p>Please log in to view checklists.</p>';
        }
        
        $checklist_id = $atts['id'] ?: 'default_' . sanitize_title($atts['title']);
        $items = $this->parse_checklist_items($atts['items']);
        
        ob_start();
        ?>
        <div class="lccp-checklist" data-checklist-id="<?php echo esc_attr($checklist_id); ?>" data-user-id="<?php echo esc_attr($atts['user_id']); ?>">
            <h3 class="lccp-checklist-title"><?php echo esc_html($atts['title']); ?></h3>
            <div class="lccp-checklist-progress">
                <div class="lccp-progress-bar">
                    <div class="lccp-progress-fill" style="width: 0%"></div>
                </div>
                <span class="lccp-progress-text">0% Complete</span>
            </div>
            <ul class="lccp-checklist-items">
                <?php foreach ($items as $index => $item): ?>
                    <li class="lccp-checklist-item" data-item-index="<?php echo esc_attr($index); ?>">
                        <label class="lccp-checkbox-label">
                            <input type="checkbox" class="lccp-checkbox" <?php checked($this->is_item_completed($checklist_id, $atts['user_id'], $index)); ?>>
                            <span class="lccp-checkbox-custom"></span>
                            <span class="lccp-checkbox-text"><?php echo esc_html($item); ?></span>
                        </label>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <style>
        .lccp-checklist {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .lccp-checklist-title {
            margin-top: 0;
            color: #23282d;
        }
        
        .lccp-checklist-progress {
            margin-bottom: 20px;
        }
        
        .lccp-progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .lccp-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #007cba, #46b450);
            transition: width 0.3s ease;
        }
        
        .lccp-progress-text {
            font-weight: 600;
            color: #666;
        }
        
        .lccp-checklist-items {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .lccp-checklist-item {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .lccp-checklist-item:hover {
            background: #f9f9f9;
        }
        
        .lccp-checklist-item.completed {
            background: #f0f8f0;
            border-color: #46b450;
        }
        
        .lccp-checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            margin: 0;
        }
        
        .lccp-checkbox {
            display: none;
        }
        
        .lccp-checkbox-custom {
            width: 20px;
            height: 20px;
            border: 2px solid #ddd;
            border-radius: 4px;
            margin-right: 10px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .lccp-checkbox:checked + .lccp-checkbox-custom {
            background: #46b450;
            border-color: #46b450;
        }
        
        .lccp-checkbox:checked + .lccp-checkbox-custom::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-weight: bold;
            font-size: 12px;
        }
        
        .lccp-checkbox-text {
            flex: 1;
            font-size: 16px;
            line-height: 1.4;
        }
        
        .lccp-checklist-item.completed .lccp-checkbox-text {
            text-decoration: line-through;
            color: #666;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('.lccp-checkbox').on('change', function() {
                var $item = $(this).closest('.lccp-checklist-item');
                var $checklist = $(this).closest('.lccp-checklist');
                var checklistId = $checklist.data('checklist-id');
                var userId = $checklist.data('user-id');
                var itemIndex = $item.data('item-index');
                var isCompleted = $(this).is(':checked');
                
                // Update visual state
                $item.toggleClass('completed', isCompleted);
                
                // Update progress
                updateChecklistProgress($checklist);
                
                // Save to server
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'lccp_update_checklist_item',
                        checklist_id: checklistId,
                        user_id: userId,
                        item_index: itemIndex,
                        completed: isCompleted ? 1 : 0,
                        nonce: '<?php echo wp_create_nonce('lccp_checklist_nonce'); ?>'
                    },
                    success: function(response) {
                        if (!response.success) {
                            console.error('Failed to save checklist item');
                        }
                    }
                });
            });
            
            function updateChecklistProgress($checklist) {
                var totalItems = $checklist.find('.lccp-checklist-item').length;
                var completedItems = $checklist.find('.lccp-checklist-item.completed').length;
                var percentage = totalItems > 0 ? Math.round((completedItems / totalItems) * 100) : 0;
                
                $checklist.find('.lccp-progress-fill').css('width', percentage + '%');
                $checklist.find('.lccp-progress-text').text(percentage + '% Complete');
            }
            
            // Initialize progress
            $('.lccp-checklist').each(function() {
                updateChecklistProgress($(this));
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Parse checklist items from string
     */
    private function parse_checklist_items($items_string) {
        if (empty($items_string)) {
            return array(
                'Complete course introduction',
                'Watch all video lessons',
                'Complete assignments',
                'Take final quiz',
                'Submit final project'
            );
        }
        
        return array_map('trim', explode("\n", $items_string));
    }
    
    /**
     * Check if checklist item is completed
     */
    private function is_item_completed($checklist_id, $user_id, $item_index) {
        $progress = get_user_meta($user_id, 'lccp_checklist_' . $checklist_id, true);
        return isset($progress[$item_index]) && $progress[$item_index];
    }
    
    /**
     * AJAX handler for updating checklist item
     */
    public function ajax_update_checklist_item() {
        check_ajax_referer('lccp_checklist_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $checklist_id = sanitize_text_field($_POST['checklist_id']);
        $user_id = intval($_POST['user_id']);
        $item_index = intval($_POST['item_index']);
        $completed = (bool) $_POST['completed'];
        
        // Verify user can update this checklist
        if ($user_id !== get_current_user_id() && !current_user_can('edit_users')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $progress = get_user_meta($user_id, 'lccp_checklist_' . $checklist_id, true);
        if (!is_array($progress)) {
            $progress = array();
        }
        
        $progress[$item_index] = $completed;
        update_user_meta($user_id, 'lccp_checklist_' . $checklist_id, $progress);
        
        wp_send_json_success('Checklist item updated');
    }
    
    /**
     * AJAX handler for getting checklist progress
     */
    public function ajax_get_checklist_progress() {
        check_ajax_referer('lccp_checklist_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $checklist_id = sanitize_text_field($_POST['checklist_id']);
        $user_id = intval($_POST['user_id']);
        
        $progress = get_user_meta($user_id, 'lccp_checklist_' . $checklist_id, true);
        $total_items = count($progress);
        $completed_items = array_sum($progress);
        $percentage = $total_items > 0 ? round(($completed_items / $total_items) * 100) : 0;
        
        wp_send_json_success(array(
            'total_items' => $total_items,
            'completed_items' => $completed_items,
            'percentage' => $percentage
        ));
    }
    
    /**
     * Add admin page
     */
    public function add_admin_page() {
        add_submenu_page(
            'lccp-systems',
            __('Checklists', 'lccp-systems'),
            __('Checklists', 'lccp-systems'),
            'manage_options',
            'lccp-checklists',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Checklist Management', 'lccp-systems'); ?></h1>
            
            <div class="lccp-checklist-admin">
                <div class="lccp-checklist-templates">
                    <h2><?php esc_html_e('Checklist Templates', 'lccp-systems'); ?></h2>
                    <p><?php esc_html_e('Use these shortcodes to display checklists:', 'lccp-systems'); ?></p>
                    
                    <div class="lccp-template-examples">
                        <h3><?php esc_html_e('Basic Checklist', 'lccp-systems'); ?></h3>
                        <code>[lccp_checklist title="Course Completion" items="Complete introduction&#10;Watch videos&#10;Take quiz"]</code>
                        
                        <h3><?php esc_html_e('Progress Checklist', 'lccp-systems'); ?></h3>
                        <code>[lccp_progress_checklist id="course_progress" title="Course Progress"]</code>
                    </div>
                </div>
                
                <div class="lccp-checklist-settings">
                    <h2><?php esc_html_e('Settings', 'lccp-systems'); ?></h2>
                    <form method="post" action="options.php">
                        <?php settings_fields('lccp_checklist_settings'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('Enable Checklists', 'lccp-systems'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="lccp_checklist_settings[enable_checklists]" 
                                               value="1" <?php checked($this->get_setting('enable_checklists'), true); ?> />
                                        <?php esc_html_e('Enable checklist functionality', 'lccp-systems'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php esc_html_e('Auto-save Progress', 'lccp-systems'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="lccp_checklist_settings[auto_save_progress]" 
                                               value="1" <?php checked($this->get_setting('auto_save_progress'), true); ?> />
                                        <?php esc_html_e('Automatically save checklist progress', 'lccp-systems'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(); ?>
                    </form>
                </div>
            </div>
        </div>
        
        <style>
        .lccp-checklist-admin {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        
        .lccp-checklist-templates,
        .lccp-checklist-settings {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .lccp-template-examples {
            margin-top: 15px;
        }
        
        .lccp-template-examples code {
            display: block;
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            font-family: monospace;
            word-break: break-all;
        }
        
        @media (max-width: 768px) {
            .lccp-checklist-admin {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
    }
    
    protected function on_activate() {
        $this->create_database_tables();
    }
    
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_name = $wpdb->prefix . 'lccp_checklists';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            checklist_id varchar(100) NOT NULL,
            user_id bigint(20) NOT NULL,
            item_index int NOT NULL,
            completed tinyint(1) DEFAULT 0,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY checklist_id (checklist_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
