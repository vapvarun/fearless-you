<?php
/**
 * Resource Library Widget
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Resource_Library_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'lccp_resource_library',
            __('LCCP Resource Library', 'lccp-systems'),
            array('description' => __('Display resource library links and downloads', 'lccp-systems'))
        );
    }
    
    public function widget($args, $instance) {
        if (!is_user_logged_in()) {
            return;
        }
        
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $category = isset($instance['category']) ? $instance['category'] : 'all';
        $show_count = isset($instance['show_count']) ? intval($instance['show_count']) : 5;
        
        $resources = $this->get_resources($category, $show_count);
        
        ?>
        <div class="lccp-resource-library-widget">
            <?php if (empty($resources)): ?>
                <p><?php _e('No resources available.', 'lccp-systems'); ?></p>
            <?php else: ?>
                <ul class="resource-list">
                    <?php foreach ($resources as $resource): ?>
                        <li class="resource-item">
                            <div class="resource-icon">
                                <?php echo $this->get_resource_icon($resource['type']); ?>
                            </div>
                            <div class="resource-details">
                                <a href="<?php echo esc_url($resource['url']); ?>" 
                                   class="resource-title" 
                                   <?php echo $resource['type'] == 'download' ? 'download' : 'target="_blank"'; ?>>
                                    <?php echo esc_html($resource['title']); ?>
                                </a>
                                <div class="resource-meta">
                                    <span class="resource-type"><?php echo esc_html($resource['type_label']); ?></span>
                                    <?php if (!empty($resource['size'])): ?>
                                        <span class="resource-size"><?php echo esc_html($resource['size']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <p class="view-all">
                    <a href="<?php echo esc_url(home_url('/resources/')); ?>">
                        <?php _e('Browse all resources', 'lccp-systems'); ?> →
                    </a>
                </p>
            <?php endif; ?>
        </div>
        <?php
        
        echo $args['after_widget'];
    }
    
    private function get_resources($category = 'all', $limit = 5) {
        $args = array(
            'post_type' => 'lccp_resource',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        if ($category !== 'all') {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'resource_category',
                    'field' => 'slug',
                    'terms' => $category
                )
            );
        }
        
        $query = new WP_Query($args);
        $resources = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $resource_type = get_post_meta($post_id, '_resource_type', true);
                $file_url = get_post_meta($post_id, '_resource_file', true);
                $external_url = get_post_meta($post_id, '_resource_url', true);
                
                $resources[] = array(
                    'title' => get_the_title(),
                    'url' => $file_url ?: $external_url,
                    'type' => $resource_type ?: 'document',
                    'type_label' => $this->get_type_label($resource_type),
                    'size' => $this->get_file_size($file_url)
                );
            }
            wp_reset_postdata();
        }
        
        // Fallback resources if no custom post type exists
        if (empty($resources)) {
            $resources = array(
                array(
                    'title' => 'Wheel of Fear & Freedom Worksheet',
                    'url' => '#',
                    'type' => 'worksheet',
                    'type_label' => 'Worksheet',
                    'size' => '2.5 MB'
                ),
                array(
                    'title' => 'Daily Fearless Practice Guide',
                    'url' => '#',
                    'type' => 'guide',
                    'type_label' => 'Guide',
                    'size' => '1.8 MB'
                ),
                array(
                    'title' => 'Fearless Conversations Script',
                    'url' => '#',
                    'type' => 'template',
                    'type_label' => 'Template',
                    'size' => '0.5 MB'
                ),
                array(
                    'title' => 'Monthly Accountability Checklist',
                    'url' => '#',
                    'type' => 'checklist',
                    'type_label' => 'Checklist',
                    'size' => '0.3 MB'
                ),
                array(
                    'title' => 'Fearless Living Audio Meditation',
                    'url' => '#',
                    'type' => 'audio',
                    'type_label' => 'Audio',
                    'size' => '15.2 MB'
                )
            );
        }
        
        return array_slice($resources, 0, $limit);
    }
    
    private function get_resource_icon($type) {
        $icons = array(
            'worksheet' => '📝',
            'guide' => '📖',
            'template' => '📋',
            'checklist' => '✅',
            'audio' => '🎧',
            'video' => '🎥',
            'document' => '📄',
            'download' => '⬇️'
        );
        
        return isset($icons[$type]) ? $icons[$type] : '📎';
    }
    
    private function get_type_label($type) {
        $labels = array(
            'worksheet' => __('Worksheet', 'lccp-systems'),
            'guide' => __('Guide', 'lccp-systems'),
            'template' => __('Template', 'lccp-systems'),
            'checklist' => __('Checklist', 'lccp-systems'),
            'audio' => __('Audio', 'lccp-systems'),
            'video' => __('Video', 'lccp-systems'),
            'document' => __('Document', 'lccp-systems')
        );
        
        return isset($labels[$type]) ? $labels[$type] : __('Resource', 'lccp-systems');
    }
    
    private function get_file_size($file_url) {
        if (empty($file_url)) {
            return '';
        }
        
        $upload_dir = wp_upload_dir();
        $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $file_url);
        
        if (file_exists($file_path)) {
            $size = filesize($file_path);
            return size_format($size);
        }
        
        return '';
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Resource Library', 'lccp-systems');
        $category = !empty($instance['category']) ? $instance['category'] : 'all';
        $show_count = !empty($instance['show_count']) ? $instance['show_count'] : 5;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php _e('Title:', 'lccp-systems'); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('category')); ?>">
                <?php _e('Category:', 'lccp-systems'); ?>
            </label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('category')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('category')); ?>">
                <option value="all" <?php selected($category, 'all'); ?>><?php _e('All Categories', 'lccp-systems'); ?></option>
                <option value="worksheets" <?php selected($category, 'worksheets'); ?>><?php _e('Worksheets', 'lccp-systems'); ?></option>
                <option value="guides" <?php selected($category, 'guides'); ?>><?php _e('Guides', 'lccp-systems'); ?></option>
                <option value="templates" <?php selected($category, 'templates'); ?>><?php _e('Templates', 'lccp-systems'); ?></option>
                <option value="audio" <?php selected($category, 'audio'); ?>><?php _e('Audio', 'lccp-systems'); ?></option>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('show_count')); ?>">
                <?php _e('Number of resources to show:', 'lccp-systems'); ?>
            </label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('show_count')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_count')); ?>" 
                   type="number" min="1" max="10" value="<?php echo esc_attr($show_count); ?>">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['category'] = (!empty($new_instance['category'])) ? sanitize_text_field($new_instance['category']) : 'all';
        $instance['show_count'] = (!empty($new_instance['show_count'])) ? intval($new_instance['show_count']) : 5;
        return $instance;
    }
}