<?php
/**
 * Learning Streak Widget
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Learning_Streak_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'lccp_learning_streak',
            __('LCCP Learning Streak', 'lccp-systems'),
            array('description' => __('Display learning streak and activity', 'lccp-systems'))
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
        
        $user_id = get_current_user_id();
        $current_streak = $this->calculate_streak($user_id);
        $longest_streak = get_user_meta($user_id, '_lccp_longest_streak', true) ?: 0;
        $total_days = get_user_meta($user_id, '_lccp_total_learning_days', true) ?: 0;
        
        ?>
        <div class="lccp-learning-streak-widget">
            <div class="streak-stats">
                <div class="streak-item current-streak">
                    <div class="streak-number"><?php echo $current_streak; ?></div>
                    <div class="streak-label"><?php _e('Current Streak', 'lccp-systems'); ?></div>
                    <div class="streak-icon">🔥</div>
                </div>
                
                <div class="streak-item longest-streak">
                    <div class="streak-number"><?php echo $longest_streak; ?></div>
                    <div class="streak-label"><?php _e('Longest Streak', 'lccp-systems'); ?></div>
                    <div class="streak-icon">🏆</div>
                </div>
                
                <div class="streak-item total-days">
                    <div class="streak-number"><?php echo $total_days; ?></div>
                    <div class="streak-label"><?php _e('Total Days', 'lccp-systems'); ?></div>
                    <div class="streak-icon">📚</div>
                </div>
            </div>
            
            <?php if ($current_streak > 0): ?>
            <div class="streak-message">
                <?php 
                if ($current_streak >= 30) {
                    echo __('Amazing dedication! Keep up the fearless learning!', 'lccp-systems');
                } elseif ($current_streak >= 14) {
                    echo __('Two weeks strong! You\'re building great habits!', 'lccp-systems');
                } elseif ($current_streak >= 7) {
                    echo __('One week streak! You\'re on fire!', 'lccp-systems');
                } elseif ($current_streak >= 3) {
                    echo __('Great momentum! Keep it going!', 'lccp-systems');
                } else {
                    echo __('You\'re building consistency!', 'lccp-systems');
                }
                ?>
            </div>
            <?php endif; ?>
            
            <div class="recent-activity">
                <h4><?php _e('This Week\'s Activity', 'lccp-systems'); ?></h4>
                <?php echo $this->get_week_activity_grid($user_id); ?>
            </div>
        </div>
        <?php
        
        echo $args['after_widget'];
    }
    
    private function calculate_streak($user_id) {
        global $wpdb;
        
        // Get user's course activity from the last 30 days
        $activities = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT DATE(activity_completed) as activity_date 
             FROM {$wpdb->prefix}learndash_user_activity 
             WHERE user_id = %d 
             AND activity_completed >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             ORDER BY activity_date DESC",
            $user_id
        ));
        
        if (empty($activities)) {
            return 0;
        }
        
        $streak = 0;
        $today = new DateTime();
        $expected_date = clone $today;
        
        foreach ($activities as $activity) {
            $activity_date = new DateTime($activity->activity_date);
            
            if ($activity_date->format('Y-m-d') == $expected_date->format('Y-m-d')) {
                $streak++;
                $expected_date->modify('-1 day');
            } else {
                break;
            }
        }
        
        return $streak;
    }
    
    private function get_week_activity_grid($user_id) {
        global $wpdb;
        
        $grid = '<div class="activity-grid">';
        
        for ($i = 6; $i >= 0; $i--) {
            $date = new DateTime();
            $date->modify("-{$i} days");
            $date_str = $date->format('Y-m-d');
            
            // Check if user had activity on this day
            $activity = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}learndash_user_activity 
                 WHERE user_id = %d 
                 AND DATE(activity_completed) = %s",
                $user_id,
                $date_str
            ));
            
            $has_activity = $activity > 0 ? 'active' : 'inactive';
            $day_name = $date->format('D');
            
            $grid .= sprintf(
                '<div class="activity-day %s" title="%s">
                    <span class="day-label">%s</span>
                    <span class="activity-indicator"></span>
                </div>',
                $has_activity,
                $date->format('M j'),
                substr($day_name, 0, 1)
            );
        }
        
        $grid .= '</div>';
        
        return $grid;
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('My Learning Streak', 'lccp-systems');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php _e('Title:', 'lccp-systems'); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }
}