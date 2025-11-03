<?php
/**
 * LCCP Checklist Manager
 *
 * Provides checklist functionality for posts, pages, and lessons
 * with user progress tracking in database
 *
 * @package LCCP_Systems
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Checklist_Manager {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Database table name for checklist progress
     */
    private $table_name;
    
    /**
     * Get instance of this class
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
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'lccp_checklist_progress';
        
        // Initialize hooks
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Add shortcode
        add_shortcode('lccp_checklist', array($this, 'render_checklist_shortcode'));
        
        // Backward compatibility with old checklist plugin
        add_shortcode('checklist_in_post', array($this, 'render_checklist_shortcode'));
        
        // Add TinyMCE button
        add_filter('mce_external_plugins', array($this, 'add_tinymce_plugin'));
        add_filter('mce_buttons', array($this, 'register_tinymce_button'));
        
        // AJAX handlers
        add_action('wp_ajax_lccp_update_checklist', array($this, 'ajax_update_checklist'));
        add_action('wp_ajax_nopriv_lccp_update_checklist', array($this, 'ajax_update_checklist_guest'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Add meta box for post/page editor
        add_action('add_meta_boxes', array($this, 'add_checklist_meta_box'));
        add_action('save_post', array($this, 'save_checklist_meta'));
        
        // Create database table on activation
        add_action('lccp_systems_activated', array($this, 'create_database_table'));
    }
    
    /**
     * Create database table for storing checklist progress
     */
    public function create_database_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            post_id bigint(20) NOT NULL,
            checklist_id varchar(100) NOT NULL,
            item_index int(11) NOT NULL,
            checked tinyint(1) DEFAULT 0,
            checked_date datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_post_checklist_item (user_id, post_id, checklist_id, item_index),
            KEY user_id (user_id),
            KEY post_id (post_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Render checklist shortcode
     */
    public function render_checklist_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'id' => uniqid('checklist_'),
            'title' => '',
            'save_progress' => 'yes',
            'show_progress' => 'yes',
            'style' => 'default',
            'course_id' => 0,
            'lesson_id' => 0
        ), $atts);
        
        // Parse content for list items
        $content = do_shortcode($content);
        $content = wp_kses_post($content);
        
        // Convert content to list if it's not already
        if (strpos($content, '<ul>') === false && strpos($content, '<ol>') === false) {
            // Convert line breaks to list items
            $lines = array_filter(explode("\n", strip_tags($content)));
            if (!empty($lines)) {
                $content = '<ul>';
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line)) {
                        $content .= '<li>' . esc_html($line) . '</li>';
                    }
                }
                $content .= '</ul>';
            }
        }
        
        // Get user progress if logged in
        $user_id = get_current_user_id();
        $post_id = get_the_ID();
        $progress_data = array();
        
        if ($user_id && $atts['save_progress'] === 'yes') {
            $progress_data = $this->get_user_progress($user_id, $post_id, $atts['id']);
        }
        
        // Build output
        $output = '<div class="lccp-checklist" data-checklist-id="' . esc_attr($atts['id']) . '" 
                        data-post-id="' . esc_attr($post_id) . '"
                        data-save-progress="' . esc_attr($atts['save_progress']) . '"
                        data-style="' . esc_attr($atts['style']) . '">';
        
        if (!empty($atts['title'])) {
            $output .= '<h4 class="lccp-checklist-title">' . esc_html($atts['title']) . '</h4>';
        }
        
        if ($atts['show_progress'] === 'yes' && $user_id) {
            $output .= '<div class="lccp-checklist-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" data-progress="0"></div>
                            </div>
                            <span class="progress-text">0% Complete</span>
                        </div>';
        }
        
        $output .= '<div class="lccp-checklist-content">';
        
        // Process the list items
        $content = preg_replace_callback(
            '/<li>(.*?)<\/li>/s',
            function($matches) use ($progress_data) {
                static $item_index = 0;
                $checked = isset($progress_data[$item_index]) && $progress_data[$item_index] ? 'checked' : '';
                $checked_class = $checked ? 'lccp-checked' : '';
                
                $item = '<li class="lccp-checklist-item ' . $checked_class . '" data-index="' . $item_index . '">
                            <label class="lccp-checklist-label">
                                <input type="checkbox" class="lccp-checklist-checkbox" ' . $checked . ' data-index="' . $item_index . '">
                                <span class="lccp-checkbox-custom"></span>
                                <span class="lccp-checklist-text">' . $matches[1] . '</span>
                            </label>
                        </li>';
                
                $item_index++;
                return $item;
            },
            $content
        );
        
        $output .= $content;
        $output .= '</div></div>';
        
        // Add nonce for AJAX
        if ($user_id) {
            $output .= wp_nonce_field('lccp_checklist_nonce', 'lccp_checklist_nonce', true, false);
        }
        
        return $output;
    }
    
    /**
     * Get user's checklist progress
     */
    private function get_user_progress($user_id, $post_id, $checklist_id) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT item_index, checked FROM {$this->table_name} 
             WHERE user_id = %d AND post_id = %d AND checklist_id = %s",
            $user_id, $post_id, $checklist_id
        ));
        
        $progress = array();
        foreach ($results as $row) {
            $progress[$row->item_index] = $row->checked;
        }
        
        return $progress;
    }
    
    /**
     * AJAX handler to update checklist progress
     */
    public function ajax_update_checklist() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'lccp_checklist_nonce')) {
            wp_die('Security check failed');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $post_id = intval($_POST['post_id']);
        $checklist_id = sanitize_text_field($_POST['checklist_id']);
        $item_index = intval($_POST['item_index']);
        $checked = $_POST['checked'] === 'true' ? 1 : 0;
        
        global $wpdb;
        
        // Insert or update the progress
        $wpdb->replace(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'post_id' => $post_id,
                'checklist_id' => $checklist_id,
                'item_index' => $item_index,
                'checked' => $checked,
                'checked_date' => $checked ? current_time('mysql') : null
            ),
            array('%d', '%d', '%s', '%d', '%d', '%s')
        );
        
        // Calculate overall progress
        $total = intval($_POST['total_items']);
        $checked_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
             WHERE user_id = %d AND post_id = %d AND checklist_id = %s AND checked = 1",
            $user_id, $post_id, $checklist_id
        ));
        
        $progress = $total > 0 ? round(($checked_count / $total) * 100) : 0;
        
        // If this is a LearnDash lesson, update course progress
        if (function_exists('learndash_get_lesson_id') && $post_id) {
            $this->maybe_update_learndash_progress($user_id, $post_id, $progress);
        }
        
        wp_send_json_success(array(
            'progress' => $progress,
            'checked_count' => $checked_count,
            'total' => $total
        ));
    }
    
    /**
     * AJAX handler for guest users (saves to session/cookies)
     */
    public function ajax_update_checklist_guest() {
        // For non-logged in users, return success but don't save
        wp_send_json_success(array(
            'message' => 'Progress saved locally'
        ));
    }
    
    /**
     * Maybe update LearnDash lesson progress
     */
    private function maybe_update_learndash_progress($user_id, $post_id, $checklist_progress) {
        if (!class_exists('SFWD_LMS')) {
            return;
        }
        
        $post_type = get_post_type($post_id);
        if (!in_array($post_type, array('sfwd-lessons', 'sfwd-topic'))) {
            return;
        }
        
        // If checklist is 100% complete, mark lesson as complete
        if ($checklist_progress >= 100) {
            $course_id = learndash_get_course_id($post_id);
            if ($course_id) {
                // Trigger LearnDash completion
                learndash_process_mark_complete($user_id, $post_id, false, $course_id);
            }
        }
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (is_singular()) {
            wp_enqueue_style(
                'lccp-checklist',
                LCCP_SYSTEMS_PLUGIN_URL . 'assets/css/checklist.css',
                array(),
                LCCP_SYSTEMS_VERSION
            );
            
            wp_enqueue_script(
                'lccp-checklist',
                LCCP_SYSTEMS_PLUGIN_URL . 'assets/js/checklist.js',
                array('jquery'),
                LCCP_SYSTEMS_VERSION,
                true
            );
            
            wp_localize_script('lccp-checklist', 'lccp_checklist', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('lccp_checklist_nonce'),
                'is_logged_in' => is_user_logged_in(),
                'strings' => array(
                    'complete' => __('Complete!', 'lccp-systems'),
                    'progress' => __('%d%% Complete', 'lccp-systems')
                )
            ));
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (in_array($hook, array('post.php', 'post-new.php'))) {
            wp_enqueue_style(
                'lccp-checklist-admin',
                LCCP_SYSTEMS_PLUGIN_URL . 'assets/css/checklist-admin.css',
                array(),
                LCCP_SYSTEMS_VERSION
            );
        }
    }
    
    /**
     * Add TinyMCE plugin
     */
    public function add_tinymce_plugin($plugins) {
        $plugins['lccp_checklist'] = LCCP_SYSTEMS_PLUGIN_URL . 'assets/js/tinymce-checklist.js';
        return $plugins;
    }
    
    /**
     * Register TinyMCE button
     */
    public function register_tinymce_button($buttons) {
        array_push($buttons, 'lccp_checklist');
        return $buttons;
    }
    
    /**
     * Add meta box for checklist settings
     */
    public function add_checklist_meta_box() {
        $post_types = array('post', 'page', 'sfwd-lessons', 'sfwd-topic', 'sfwd-courses');
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'lccp_checklist_settings',
                __('Checklist Settings', 'lccp-systems'),
                array($this, 'render_meta_box'),
                $post_type,
                'side',
                'default'
            );
        }
    }
    
    /**
     * Render meta box content
     */
    public function render_meta_box($post) {
        wp_nonce_field('lccp_checklist_meta', 'lccp_checklist_meta_nonce');
        
        $enable_checklist = get_post_meta($post->ID, '_lccp_enable_checklist', true);
        $checklist_title = get_post_meta($post->ID, '_lccp_checklist_title', true);
        $require_completion = get_post_meta($post->ID, '_lccp_require_completion', true);
        
        ?>
        <p>
            <label>
                <input type="checkbox" name="lccp_enable_checklist" value="1" <?php checked($enable_checklist, '1'); ?>>
                <?php _e('Enable checklist for this content', 'lccp-systems'); ?>
            </label>
        </p>
        <p>
            <label><?php _e('Checklist Title:', 'lccp-systems'); ?></label>
            <input type="text" name="lccp_checklist_title" value="<?php echo esc_attr($checklist_title); ?>" class="widefat">
        </p>
        <?php if (in_array($post->post_type, array('sfwd-lessons', 'sfwd-topic'))): ?>
        <p>
            <label>
                <input type="checkbox" name="lccp_require_completion" value="1" <?php checked($require_completion, '1'); ?>>
                <?php _e('Require checklist completion to mark lesson complete', 'lccp-systems'); ?>
            </label>
        </p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Save meta box data
     */
    public function save_checklist_meta($post_id) {
        // Check nonce
        if (!isset($_POST['lccp_checklist_meta_nonce']) || 
            !wp_verify_nonce($_POST['lccp_checklist_meta_nonce'], 'lccp_checklist_meta')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save meta
        update_post_meta($post_id, '_lccp_enable_checklist', isset($_POST['lccp_enable_checklist']) ? '1' : '0');
        update_post_meta($post_id, '_lccp_checklist_title', sanitize_text_field($_POST['lccp_checklist_title']));
        update_post_meta($post_id, '_lccp_require_completion', isset($_POST['lccp_require_completion']) ? '1' : '0');
    }
    
    /**
     * Get checklist statistics for a user
     */
    public function get_user_checklist_stats($user_id) {
        global $wpdb;
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(DISTINCT post_id, checklist_id) as total_checklists,
                COUNT(DISTINCT CASE WHEN checked = 1 THEN CONCAT(post_id, '-', checklist_id, '-', item_index) END) as items_completed,
                COUNT(DISTINCT CONCAT(post_id, '-', checklist_id, '-', item_index)) as total_items
             FROM {$this->table_name}
             WHERE user_id = %d",
            $user_id
        ));
        
        return $stats;
    }
}

// Initialize the class
LCCP_Checklist_Manager::get_instance();