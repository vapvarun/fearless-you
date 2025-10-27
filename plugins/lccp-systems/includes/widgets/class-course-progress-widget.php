<?php
/**
 * Course Progress Widget
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Course_Progress_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'lccp_course_progress',
            __('LCCP Course Progress', 'lccp-systems'),
            array('description' => __('Display course progress for current user', 'lccp-systems'))
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
        $courses = learndash_user_get_enrolled_courses($user_id);
        
        if (empty($courses)) {
            echo '<p>' . __('You are not enrolled in any courses yet.', 'lccp-systems') . '</p>';
        } else {
            echo '<div class="lccp-course-progress-widget">';
            
            foreach ($courses as $course_id) {
                $course_title = get_the_title($course_id);
                $progress = learndash_course_progress(array(
                    'user_id' => $user_id,
                    'course_id' => $course_id,
                    'array' => true
                ));
                
                $percentage = isset($progress['percentage']) ? $progress['percentage'] : 0;
                $completed = isset($progress['completed']) ? $progress['completed'] : 0;
                $total = isset($progress['total']) ? $progress['total'] : 0;
                
                ?>
                <div class="course-progress-item">
                    <h4><?php echo esc_html($course_title); ?></h4>
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: <?php echo $percentage; ?>%;">
                            <span class="progress-text"><?php echo $percentage; ?>%</span>
                        </div>
                    </div>
                    <p class="progress-stats">
                        <?php echo sprintf(__('%d of %d lessons completed', 'lccp-systems'), $completed, $total); ?>
                    </p>
                </div>
                <?php
            }
            
            echo '</div>';
        }
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('My Course Progress', 'lccp-systems');
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