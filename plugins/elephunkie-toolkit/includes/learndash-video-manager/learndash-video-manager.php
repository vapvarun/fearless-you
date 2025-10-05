<?php
/**
 * Plugin Name: LearnDash Video Manager
 * Description: Manage video progression URLs for LearnDash lessons and topics
 * Version: 1.0.1
 * Author: Your Name
 * Text Domain: learndash-video-manager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Elephunkie_LearnDash_Video_Manager {
    private $option_name = 'learndash_video_manager_settings';

    public function __construct() {
        // Only initialize if LearnDash is active
        add_action('admin_init', array($this, 'init_if_learndash_active'));
    }
    
    public function init_if_learndash_active() {
        if (!defined('LEARNDASH_VERSION')) {
            return;
        }
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_save_video_urls', array($this, 'save_video_urls'));
        
        // Create necessary files on initialization
        $this->create_necessary_files();
    }

    public function add_admin_menu() {
        add_submenu_page(
            'learndash-lms',
            __('Video Manager', 'learndash-video-manager'),
            __('Video Manager', 'learndash-video-manager'),
            'manage_options',
            'learndash-video-manager',
            array($this, 'render_settings_page')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if ('learndash-lms_page_learndash-video-manager' !== $hook) {
            return;
        }

        $plugin_url = plugin_dir_url(dirname(__DIR__));

        wp_enqueue_style(
            'learndash-video-manager',
            $plugin_url . 'assets/css/learndash-video-manager.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'learndash-video-manager',
            $plugin_url . 'assets/js/learndash-video-manager.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('learndash-video-manager', 'ldvmAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ldvm_save_videos')
        ));
    }

    public function init_settings() {
        register_setting(
            'learndash_video_manager_group',
            $this->option_name
        );
    }

    public function get_lessons_and_topics() {
        $items = array();
        $title_map = array();

        // Get all lessons and topics
        $posts = get_posts(array(
            'post_type' => array('sfwd-lessons', 'sfwd-topic'),
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        // First pass: collect all items and create title map
        foreach ($posts as $post) {
            $settings = function_exists('learndash_get_setting') ? learndash_get_setting($post) : [];
            $type = $post->post_type === 'sfwd-lessons' ? 'lesson' : 'topic';
            
            $item = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'type' => $type,
                'video_url' => isset($settings['lesson_video_url']) ? $settings['lesson_video_url'] : '',
                'video_enabled' => isset($settings['lesson_video_enabled']) ? $settings['lesson_video_enabled'] : ''
            );
            
            $items[] = $item;
            
            // Create map of titles to items for finding duplicates
            if (!isset($title_map[$post->post_title])) {
                $title_map[$post->post_title] = array();
            }
            $title_map[$post->post_title][] = count($items) - 1;
        }

        // Second pass: sync settings for matching titles
        foreach ($title_map as $title => $indices) {
            if (count($indices) > 1) {
                // Find the item with non-empty settings
                $source_index = -1;
                foreach ($indices as $idx) {
                    if (!empty($items[$idx]['video_url']) || !empty($items[$idx]['video_enabled'])) {
                        $source_index = $idx;
                        break;
                    }
                }

                // If we found an item with settings, copy to others
                if ($source_index >= 0) {
                    foreach ($indices as $idx) {
                        if ($idx !== $source_index && empty($items[$idx]['video_url'])) {
                            $items[$idx]['video_url'] = $items[$source_index]['video_url'];
                            $items[$idx]['video_enabled'] = $items[$source_index]['video_enabled'];
                            
                            // Update the settings in database
                            if (function_exists('learndash_get_setting') && function_exists('learndash_update_setting')) {
                                $settings = learndash_get_setting($items[$idx]['id']);
                                $settings['lesson_video_url'] = $items[$source_index]['video_url'];
                                $settings['lesson_video_enabled'] = $items[$source_index]['video_enabled'];
                                learndash_update_setting($items[$idx]['id'], $settings);
                            }
                        }
                    }
                }
            }
        }

        return $items;
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $items = $this->get_lessons_and_topics();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="ldvm-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Title', 'learndash-video-manager'); ?></th>
                            <th><?php _e('Type', 'learndash-video-manager'); ?></th>
                            <th><?php _e('Video Enabled', 'learndash-video-manager'); ?></th>
                            <th><?php _e('Video URL', 'learndash-video-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo esc_html($item['title']); ?></td>
                                <td><?php echo esc_html(ucfirst($item['type'])); ?></td>
                                <td>
                                    <label class="ldvm-switch">
                                        <input type="checkbox" 
                                               class="ldvm-video-enabled" 
                                               data-id="<?php echo esc_attr($item['id']); ?>"
                                               data-type="<?php echo esc_attr($item['type']); ?>"
                                               <?php checked($item['video_enabled'], 'on'); ?>>
                                        <span class="ldvm-slider"></span>
                                    </label>
                                </td>
                                <td>
                                    <input type="text" 
                                           class="ldvm-video-url regular-text" 
                                           data-id="<?php echo esc_attr($item['id']); ?>"
                                           data-type="<?php echo esc_attr($item['type']); ?>"
                                           value="<?php echo esc_attr($item['video_url']); ?>"
                                    >
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="ldvm-actions">
                <button type="button" class="button button-primary" id="ldvm-save-changes">
                    <?php _e('Save Changes', 'learndash-video-manager'); ?>
                </button>
                <span class="ldvm-save-status"></span>
            </div>
        </div>
        <?php
    }

    public function save_video_urls() {
        if (!check_ajax_referer('ldvm_save_videos', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        $videos = isset($_POST['videos']) ? $_POST['videos'] : array();

        foreach ($videos as $video) {
            $post_id = absint($video['id']);
            $url = esc_url_raw($video['url']);
            $enabled = isset($video['enabled']) ? 'on' : '';

            if ($post_id && function_exists('learndash_get_setting') && function_exists('learndash_update_setting')) {
                $settings = learndash_get_setting($post_id);
                $settings['lesson_video_url'] = $url;
                $settings['lesson_video_enabled'] = $enabled;
                learndash_update_setting($post_id, $settings);
            }
        }

        wp_send_json_success('Video settings updated successfully');
    }
    
    public function create_necessary_files() {
        $css_dir = plugin_dir_path(dirname(__DIR__)) . 'assets/css';
        if (!file_exists($css_dir)) {
            mkdir($css_dir, 0755, true);
        }

        $css_content = <<<CSS
.ldvm-table-container {
    margin: 20px 0;
}
.ldvm-video-url {
    width: 100%;
}
.ldvm-actions {
    margin: 20px 0;
}
.ldvm-save-status {
    margin-left: 10px;
    display: inline-block;
    vertical-align: middle;
}
.ldvm-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}
.ldvm-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.ldvm-slider {
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
.ldvm-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}
input:checked + .ldvm-slider {
    background-color: #2196F3;
}
input:checked + .ldvm-slider:before {
    transform: translateX(26px);
}
CSS;

        if (!file_exists($css_dir . '/learndash-video-manager.css')) {
            file_put_contents($css_dir . '/learndash-video-manager.css', $css_content);
        }

        $js_dir = plugin_dir_path(dirname(__DIR__)) . 'assets/js';
        if (!file_exists($js_dir)) {
            mkdir($js_dir, 0755, true);
        }

        $js_content = <<<JS
jQuery(document).ready(function($) {
    $('#ldvm-save-changes').on('click', function() {
        const button = $(this);
        const statusEl = $('.ldvm-save-status');
        const videos = [];

        $('.ldvm-video-url').each(function() {
            const row = $(this).closest('tr');
            videos.push({
                id: $(this).data('id'),
                type: $(this).data('type'),
                url: $(this).val(),
                enabled: row.find('.ldvm-video-enabled').prop('checked') ? 'on' : ''
            });
        });

        button.prop('disabled', true);
        statusEl.html('Saving...');

        $.ajax({
            url: ldvmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'save_video_urls',
                nonce: ldvmAjax.nonce,
                videos: videos
            },
            success: function(response) {
                if (response.success) {
                    statusEl.html('✓ Saved successfully');
                    setTimeout(() => statusEl.html(''), 3000);
                } else {
                    statusEl.html('❌ Error saving');
                }
            },
            error: function() {
                statusEl.html('❌ Error saving');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
});
JS;

        if (!file_exists($js_dir . '/learndash-video-manager.js')) {
            file_put_contents($js_dir . '/learndash-video-manager.js', $js_content);
        }
    }
}

// Initialize the plugin only if LearnDash is available
add_action('plugins_loaded', function() {
    if (defined('LEARNDASH_VERSION')) {
        new Elephunkie_LearnDash_Video_Manager();
    }
});