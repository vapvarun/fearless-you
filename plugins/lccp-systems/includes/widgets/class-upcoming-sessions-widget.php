<?php
/**
 * Upcoming Sessions Widget
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Upcoming_Sessions_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'lccp_upcoming_sessions',
            __('LCCP Upcoming Sessions', 'lccp-systems'),
            array('description' => __('Display upcoming live sessions and events', 'lccp-systems'))
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
        
        $sessions = $this->get_upcoming_sessions();
        $show_count = isset($instance['show_count']) ? intval($instance['show_count']) : 3;
        
        ?>
        <div class="lccp-upcoming-sessions-widget">
            <?php if (empty($sessions)): ?>
                <p><?php _e('No upcoming sessions scheduled.', 'lccp-systems'); ?></p>
            <?php else: ?>
                <ul class="sessions-list">
                    <?php 
                    $count = 0;
                    foreach ($sessions as $session): 
                        if ($count >= $show_count) break;
                        $count++;
                        
                        $session_date = new DateTime($session['date']);
                        $is_today = $session_date->format('Y-m-d') == date('Y-m-d');
                        $is_tomorrow = $session_date->format('Y-m-d') == date('Y-m-d', strtotime('+1 day'));
                    ?>
                        <li class="session-item">
                            <div class="session-date">
                                <span class="month"><?php echo $session_date->format('M'); ?></span>
                                <span class="day"><?php echo $session_date->format('j'); ?></span>
                            </div>
                            <div class="session-details">
                                <h4 class="session-title"><?php echo esc_html($session['title']); ?></h4>
                                <div class="session-meta">
                                    <span class="session-time">
                                        <?php echo $session_date->format('g:i A'); ?>
                                    </span>
                                    <?php if ($is_today): ?>
                                        <span class="session-badge today"><?php _e('Today', 'lccp-systems'); ?></span>
                                    <?php elseif ($is_tomorrow): ?>
                                        <span class="session-badge tomorrow"><?php _e('Tomorrow', 'lccp-systems'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($session['zoom_link'])): ?>
                                    <a href="<?php echo esc_url($session['zoom_link']); ?>" 
                                       class="session-link" target="_blank">
                                        <?php _e('Join Session', 'lccp-systems'); ?> →
                                    </a>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <?php if (count($sessions) > $show_count): ?>
                    <p class="view-all">
                        <a href="<?php echo esc_url(home_url('/events/')); ?>">
                            <?php _e('View all sessions', 'lccp-systems'); ?> →
                        </a>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
        
        echo $args['after_widget'];
    }
    
    private function get_upcoming_sessions() {
        // Check if The Events Calendar is active
        if (class_exists('Tribe__Events__Main')) {
            $events = tribe_get_events(array(
                'posts_per_page' => 10,
                'start_date' => date('Y-m-d H:i:s'),
                'meta_query' => array(
                    array(
                        'key' => '_lccp_session_type',
                        'value' => array('coaching', 'workshop', 'qa'),
                        'compare' => 'IN'
                    )
                )
            ));
            
            $sessions = array();
            foreach ($events as $event) {
                $sessions[] = array(
                    'title' => $event->post_title,
                    'date' => tribe_get_start_date($event, false, 'Y-m-d H:i:s'),
                    'zoom_link' => get_post_meta($event->ID, '_zoom_link', true),
                    'type' => get_post_meta($event->ID, '_lccp_session_type', true)
                );
            }
            
            return $sessions;
        }
        
        // Fallback to custom events if Events Calendar not active
        $custom_events = get_posts(array(
            'post_type' => 'lccp_event',
            'posts_per_page' => 5,
            'meta_query' => array(
                array(
                    'key' => 'event_date',
                    'value' => current_time('Y-m-d'),
                    'compare' => '>='
                )
            ),
            'orderby' => 'meta_value',
            'order' => 'ASC'
        ));
        
        $sessions = array();
        foreach ($custom_events as $event) {
            $event_date = get_post_meta($event->ID, 'event_date', true);
            $event_time = get_post_meta($event->ID, 'event_time', true);
            $zoom_link = get_post_meta($event->ID, 'zoom_link', true);
            $session_type = get_post_meta($event->ID, 'session_type', true);
            
            $sessions[] = array(
                'title' => $event->post_title,
                'date' => $event_date . ' ' . $event_time,
                'zoom_link' => $zoom_link ?: '#',
                'type' => $session_type ?: 'coaching',
                'event_id' => $event->ID
            );
        }
        
        return $sessions;
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Upcoming Sessions', 'lccp-systems');
        $show_count = !empty($instance['show_count']) ? $instance['show_count'] : 3;
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
            <label for="<?php echo esc_attr($this->get_field_id('show_count')); ?>">
                <?php _e('Number of sessions to show:', 'lccp-systems'); ?>
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
        $instance['show_count'] = (!empty($new_instance['show_count'])) ? intval($new_instance['show_count']) : 3;
        return $instance;
    }
}