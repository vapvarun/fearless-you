<?php
/**
 * Dasher Community Message System
 * 
 * @package Dasher
 * @since 1.0.3
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Dasher_Message_System
 * Handles community messaging between mentors/bigbirds and PCs
 */
class Dasher_Message_System {
    
    /**
     * Initialize the message system
     */
    public function __construct() {
        add_action('init', array($this, 'register_message_post_type'));
        add_action('add_meta_boxes', array($this, 'add_message_meta_boxes'));
        add_action('save_post', array($this, 'save_message_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_message_scripts'));
        
        // Add AJAX handlers
        add_action('wp_ajax_send_community_message', array($this, 'handle_send_message'));
        add_action('wp_ajax_get_community_messages', array($this, 'handle_get_messages'));
    }
    
    /**
     * Register the community message post type
     */
    public function register_message_post_type() {
        $labels = array(
            'name' => __('Community Messages', 'dasher'),
            'singular_name' => __('Community Message', 'dasher'),
            'menu_name' => __('Community Messages', 'dasher'),
            'add_new' => __('Add New Message', 'dasher'),
            'add_new_item' => __('Add New Community Message', 'dasher'),
            'edit_item' => __('Edit Community Message', 'dasher'),
            'new_item' => __('New Community Message', 'dasher'),
            'view_item' => __('View Community Message', 'dasher'),
            'search_items' => __('Search Community Messages', 'dasher'),
            'not_found' => __('No community messages found', 'dasher'),
            'not_found_in_trash' => __('No community messages found in trash', 'dasher'),
            'parent_item_colon' => __('Parent Community Message:', 'dasher'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'dasher-settings',
            'show_in_admin_bar' => false,
            'show_in_nav_menus' => false,
            'can_export' => true,
            'has_archive' => false,
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'rewrite' => false,
            'capability_type' => 'post',
            'capabilities' => array(
                'edit_post' => 'dasher_mentor',
                'read_post' => 'dasher_mentor',
                'delete_post' => 'dasher_mentor',
                'edit_posts' => 'dasher_mentor',
                'edit_others_posts' => 'dasher_mentor',
                'publish_posts' => 'dasher_mentor',
                'read_private_posts' => 'dasher_mentor',
            ),
            'supports' => array('title', 'editor', 'author'),
            'menu_icon' => 'dashicons-admin-comments',
        );
        
        register_post_type('dasher_message', $args);
    }
    
    /**
     * Add meta boxes for message settings
     */
    public function add_message_meta_boxes() {
        add_meta_box(
            'dasher_message_settings',
            __('Message Settings', 'dasher'),
            array($this, 'render_message_settings_meta_box'),
            'dasher_message',
            'side',
            'high'
        );
        
        add_meta_box(
            'dasher_quick_message',
            __('Quick Message Composer', 'dasher'),
            array($this, 'render_quick_message_meta_box'),
            array('dasher_mentor_dashboard', 'dasher_big_bird_dashboard'),
            'normal',
            'high'
        );
    }
    
    /**
     * Render message settings meta box
     */
    public function render_message_settings_meta_box($post) {
        wp_nonce_field('dasher_message_meta', 'dasher_message_nonce');
        
        $sender_id = get_post_meta($post->ID, '_dasher_message_sender', true);
        $sender_role = get_post_meta($post->ID, '_dasher_message_sender_role', true);
        $target = get_post_meta($post->ID, '_dasher_message_target', true);
        
        // Get mentors and bigbirds for sender dropdown
        $mentors = get_users(array('role' => 'dasher_mentor'));
        $bigbirds = get_users(array('role' => 'dasher_bigbird'));
        ?>
        <table class="form-table">
            <tr>
                <th><label for="message_sender"><?php esc_html_e('Send As', 'dasher'); ?></label></th>
                <td>
                    <select name="message_sender" id="message_sender" class="widefat">
                        <option value=""><?php esc_html_e('Select Sender', 'dasher'); ?></option>
                        <?php if (!empty($mentors)) : ?>
                            <optgroup label="<?php esc_attr_e('Mentors', 'dasher'); ?>">
                                <?php foreach ($mentors as $mentor) : ?>
                                    <option value="<?php echo esc_attr($mentor->ID . '|dasher_mentor'); ?>" 
                                            <?php selected($sender_id . '|' . $sender_role, $mentor->ID . '|dasher_mentor'); ?>>
                                        <?php echo esc_html($mentor->display_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                        <?php if (!empty($bigbirds)) : ?>
                            <optgroup label="<?php esc_attr_e('Big Birds', 'dasher'); ?>">
                                <?php foreach ($bigbirds as $bigbird) : ?>
                                    <option value="<?php echo esc_attr($bigbird->ID . '|dasher_bigbird'); ?>" 
                                            <?php selected($sender_id . '|' . $sender_role, $bigbird->ID . '|dasher_bigbird'); ?>>
                                        <?php echo esc_html($bigbird->display_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                    </select>
                    <p class="description"><?php esc_html_e('Choose who this message appears to be from', 'dasher'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="message_target"><?php esc_html_e('Send To', 'dasher'); ?></label></th>
                <td>
                    <select name="message_target" id="message_target" class="widefat">
                        <option value="pc" <?php selected($target, 'pc'); ?>><?php esc_html_e('All Program Candidates', 'dasher'); ?></option>
                        <option value="mentor" <?php selected($target, 'mentor'); ?>><?php esc_html_e('All Mentors', 'dasher'); ?></option>
                        <option value="bigbird" <?php selected($target, 'bigbird'); ?>><?php esc_html_e('All Big Birds', 'dasher'); ?></option>
                        <option value="all" <?php selected($target, 'all'); ?>><?php esc_html_e('Everyone', 'dasher'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render quick message composer meta box for dashboard pages
     */
    public function render_quick_message_meta_box($post) {
        ?>
        <div id="dasher-quick-message-composer">
            <form id="quick-message-form">
                <?php wp_nonce_field('dasher_quick_message', 'quick_message_nonce'); ?>
                
                <div class="dasher-message-form-row">
                    <label for="quick_message_title"><?php esc_html_e('Message Title', 'dasher'); ?></label>
                    <input type="text" id="quick_message_title" name="message_title" class="widefat" 
                           placeholder="<?php esc_attr_e('Enter a title for your message...', 'dasher'); ?>">
                </div>
                
                <div class="dasher-message-form-row">
                    <label for="quick_message_content"><?php esc_html_e('Message Content', 'dasher'); ?></label>
                    <?php
                    wp_editor('', 'quick_message_content', array(
                        'textarea_name' => 'message_content',
                        'media_buttons' => false,
                        'textarea_rows' => 8,
                        'teeny' => true,
                        'quicktags' => false,
                    ));
                    ?>
                </div>
                
                <div class="dasher-message-form-row">
                    <div class="dasher-message-form-cols">
                        <div class="dasher-col">
                            <label for="quick_message_sender"><?php esc_html_e('Send As', 'dasher'); ?></label>
                            <select id="quick_message_sender" name="message_sender" class="widefat">
                                <option value=""><?php esc_html_e('Select Sender', 'dasher'); ?></option>
                                <?php
                                $mentors = get_users(array('role' => 'dasher_mentor'));
                                $bigbirds = get_users(array('role' => 'dasher_bigbird'));
                                
                                if (!empty($mentors)) {
                                    echo '<optgroup label="' . esc_attr__('Mentors', 'dasher') . '">';
                                    foreach ($mentors as $mentor) {
                                        echo '<option value="' . esc_attr($mentor->ID . '|dasher_mentor') . '">' . esc_html($mentor->display_name) . '</option>';
                                    }
                                    echo '</optgroup>';
                                }
                                
                                if (!empty($bigbirds)) {
                                    echo '<optgroup label="' . esc_attr__('Big Birds', 'dasher') . '">';
                                    foreach ($bigbirds as $bigbird) {
                                        echo '<option value="' . esc_attr($bigbird->ID . '|dasher_bigbird') . '">' . esc_html($bigbird->display_name) . '</option>';
                                    }
                                    echo '</optgroup>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="dasher-col">
                            <label for="quick_message_target"><?php esc_html_e('Send To', 'dasher'); ?></label>
                            <select id="quick_message_target" name="message_target" class="widefat">
                                <option value="pc"><?php esc_html_e('All Program Candidates', 'dasher'); ?></option>
                                <option value="mentor"><?php esc_html_e('All Mentors', 'dasher'); ?></option>
                                <option value="bigbird"><?php esc_html_e('All Big Birds', 'dasher'); ?></option>
                                <option value="all"><?php esc_html_e('Everyone', 'dasher'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="dasher-message-form-row">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-email-alt"></span>
                        <?php esc_html_e('Send Message', 'dasher'); ?>
                    </button>
                    <button type="button" class="button" id="preview-message">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php esc_html_e('Preview', 'dasher'); ?>
                    </button>
                </div>
            </form>
            
            <div id="message-preview" style="display: none;">
                <h4><?php esc_html_e('Message Preview', 'dasher'); ?></h4>
                <div id="preview-content"></div>
                <button type="button" class="button" id="close-preview"><?php esc_html_e('Close Preview', 'dasher'); ?></button>
            </div>
        </div>
        
        <style>
        #dasher-quick-message-composer {
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #e1e1e1;
            border-radius: 4px;
        }
        
        .dasher-message-form-row {
            margin-bottom: 20px;
        }
        
        .dasher-message-form-row label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .dasher-message-form-cols {
            display: flex;
            gap: 15px;
        }
        
        .dasher-col {
            flex: 1;
        }
        
        #message-preview {
            margin-top: 20px;
            padding: 15px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        </style>
        <?php
    }
    
    /**
     * Save message meta data
     */
    public function save_message_meta($post_id) {
        if (!isset($_POST['dasher_message_nonce']) || !wp_verify_nonce($_POST['dasher_message_nonce'], 'dasher_message_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (isset($_POST['message_sender'])) {
            $sender_data = explode('|', sanitize_text_field($_POST['message_sender']));
            if (count($sender_data) == 2) {
                update_post_meta($post_id, '_dasher_message_sender', intval($sender_data[0]));
                update_post_meta($post_id, '_dasher_message_sender_role', sanitize_text_field($sender_data[1]));
            }
        }
        
        if (isset($_POST['message_target'])) {
            update_post_meta($post_id, '_dasher_message_target', sanitize_text_field($_POST['message_target']));
        }
    }
    
    /**
     * Enqueue scripts for message system
     */
    public function enqueue_message_scripts($hook) {
        if (strpos($hook, 'dasher') !== false) {
            wp_enqueue_script('dasher-messages', DASHER_PLUGIN_URL . 'assets/js/messages.js', array('jquery', 'wp-util'), DASHER_VERSION, true);
            wp_localize_script('dasher-messages', 'dasher_messages', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dasher_messages_nonce'),
            ));
        }
    }
    
    /**
     * Handle AJAX request to send community message
     */
    public function handle_send_message() {
        if (!wp_verify_nonce($_POST['nonce'], 'dasher_messages_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('dasher_mentor') && !current_user_can('dasher_bigbird')) {
            wp_die('Insufficient permissions');
        }
        
        $title = sanitize_text_field($_POST['title']);
        $content = wp_kses_post($_POST['content']);
        $sender_data = explode('|', sanitize_text_field($_POST['sender']));
        $target = sanitize_text_field($_POST['target']);
        
        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'dasher_message',
            'post_author' => get_current_user_id(),
        );
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id) {
            if (count($sender_data) == 2) {
                update_post_meta($post_id, '_dasher_message_sender', intval($sender_data[0]));
                update_post_meta($post_id, '_dasher_message_sender_role', sanitize_text_field($sender_data[1]));
            }
            update_post_meta($post_id, '_dasher_message_target', $target);
            
            wp_send_json_success(array('message' => __('Message sent successfully!', 'dasher')));
        } else {
            wp_send_json_error(array('message' => __('Failed to send message.', 'dasher')));
        }
    }
    
    /**
     * Handle AJAX request to get community messages
     */
    public function handle_get_messages() {
        if (!wp_verify_nonce($_POST['nonce'], 'dasher_messages_nonce')) {
            wp_die('Security check failed');
        }
        
        $target = sanitize_text_field($_POST['target']);
        $limit = intval($_POST['limit']) ?: 10;
        
        $messages = get_posts(array(
            'post_type' => 'dasher_message',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_dasher_message_target',
                    'value' => $target,
                    'compare' => '='
                )
            )
        ));
        
        $formatted_messages = array();
        foreach ($messages as $message) {
            $sender_id = get_post_meta($message->ID, '_dasher_message_sender', true);
            $sender_role = get_post_meta($message->ID, '_dasher_message_sender_role', true);
            $sender_user = get_user_by('ID', $sender_id);
            
            $formatted_messages[] = array(
                'id' => $message->ID,
                'title' => $message->post_title,
                'content' => $message->post_content,
                'date' => $message->post_date,
                'sender_name' => $sender_user ? $sender_user->display_name : 'Community Team',
                'sender_role' => $sender_role,
                'sender_avatar' => get_avatar_url($sender_id, array('size' => 50)),
            );
        }
        
        wp_send_json_success($formatted_messages);
    }
}

// Initialize the message system
new Dasher_Message_System();