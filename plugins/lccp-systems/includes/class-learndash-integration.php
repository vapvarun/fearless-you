<?php
/**
 * LearnDash Integration Module for LCCP Systems
 * 
 * IMPORTANT: This module ensures LearnDash Mark Complete functionality works properly
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_LearnDash_Integration {
    
    public function __construct() {
        // Only initialize if LearnDash is active
        if (!class_exists('SFWD_LMS')) {
            return;
        }
        
        // Fix for Mark Complete button
        add_action('wp_enqueue_scripts', array($this, 'ensure_learndash_scripts'), 999);
        
        // Video progression support
        add_action('init', array($this, 'enable_video_progression'));
        
        // Custom naming (Modules instead of Lessons, Lessons instead of Topics)
        add_filter('learndash_post_type_labels', array($this, 'customize_labels'), 10, 2);
        
        // Progress tracking enhancements
        add_action('learndash_lesson_completed', array($this, 'on_lesson_completed'), 10, 1);
        add_action('learndash_topic_completed', array($this, 'on_topic_completed'), 10, 1);
        add_action('learndash_course_completed', array($this, 'on_course_completed'), 10, 1);
        
        // Fix AJAX handlers for Mark Complete with lower priority to avoid conflicts
        add_action('wp_ajax_learndash_mark_complete', array($this, 'fix_mark_complete_handler'), 5);
        add_action('wp_ajax_nopriv_learndash_mark_complete', array($this, 'fix_mark_complete_handler'), 5);
        
        // Ensure proper nonces for LearnDash
        add_filter('learndash_nonce_verify', array($this, 'verify_learndash_nonce'), 10, 2);
    }
    
    /**
     * Ensure LearnDash scripts are properly loaded for Mark Complete functionality
     */
    public function ensure_learndash_scripts() {
        if (is_singular(array('sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz'))) {
            // Ensure jQuery is loaded
            wp_enqueue_script('jquery');
            
            // Ensure LearnDash front script is loaded
            if (!wp_script_is('learndash-front', 'enqueued')) {
                wp_enqueue_script('learndash-front');
            }
            
            // Add inline script to fix Mark Complete if needed
            wp_add_inline_script('learndash-front', "
                jQuery(document).ready(function($) {
                    // Ensure Mark Complete button works
                    $(document).on('click', '.learndash_mark_complete_button, .ld-button-complete', function(e) {
                        if (!$(this).hasClass('learndash_mark_complete_processing')) {
                            var button = $(this);
                            var form = button.closest('form');
                            
                            // If no form, create one
                            if (!form.length) {
                                console.log('LCCP Systems: Creating form for Mark Complete button');
                                button.wrap('<form method=\"post\" class=\"ld-mark-complete-form\"></form>');
                                form = button.closest('form');
                            }
                            
                            // Ensure form has proper data
                            if (!form.find('input[name=\"post\"]').length) {
                                var postId = button.data('post-id') || button.data('id') || '" . get_the_ID() . "';
                                form.append('<input type=\"hidden\" name=\"post\" value=\"' + postId + '\">');
                            }
                            
                            if (!form.find('input[name=\"learndash_mark_complete_nonce\"]').length) {
                                form.append('<input type=\"hidden\" name=\"learndash_mark_complete_nonce\" value=\"' + (window.sfwd_data ? window.sfwd_data.nonce : '') + '\">');
                            }
                        }
                    });
                    
                    // Fix video progression if enabled
                    if (window.learndash_video_data) {
                        console.log('LCCP Systems: Video progression enabled');
                        // Ensure video completion tracking works
                        $(document).on('ended', 'video', function() {
                            var video = $(this);
                            if (video.closest('.ld-video-wrapper').length) {
                                // Trigger LearnDash video completion
                                $(document).trigger('learndash_video_complete', [video]);
                            }
                        });
                    }
                });
            ");
            
            // Ensure AJAX URL is available
            wp_localize_script('learndash-front', 'learndash_ajax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('learndash_mark_complete')
            ));
        }
    }
    
    /**
     * Enable video progression for lessons and topics
     */
    public function enable_video_progression() {
        // Enable video progression settings if not already enabled
        $video_settings = get_option('learndash_settings_videos', array());
        
        if (empty($video_settings['enabled'])) {
            $video_settings['enabled'] = 'yes';
            $video_settings['videos_auto_start'] = 'yes';
            $video_settings['videos_show_controls'] = 'yes';
            $video_settings['videos_focus_pause'] = '';
            $video_settings['videos_track_time'] = 'yes';
            $video_settings['videos_hide_complete_button'] = ''; // Don't hide, just disable until video completes
            
            update_option('learndash_settings_videos', $video_settings);
        }
    }
    
    /**
     * Customize LearnDash labels (Modules/Lessons instead of Lessons/Topics)
     */
    public function customize_labels($labels, $post_type) {
        switch ($post_type) {
            case 'sfwd-lessons':
                // Lessons become "Modules"
                $labels['name'] = 'Modules';
                $labels['singular_name'] = 'Module';
                $labels['add_new'] = 'Add New Module';
                $labels['add_new_item'] = 'Add New Module';
                $labels['edit_item'] = 'Edit Module';
                $labels['new_item'] = 'New Module';
                $labels['view_item'] = 'View Module';
                $labels['search_items'] = 'Search Modules';
                $labels['not_found'] = 'No modules found';
                $labels['not_found_in_trash'] = 'No modules found in Trash';
                $labels['all_items'] = 'All Modules';
                $labels['menu_name'] = 'Modules';
                break;
                
            case 'sfwd-topic':
                // Topics become "Lessons"
                $labels['name'] = 'Lessons';
                $labels['singular_name'] = 'Lesson';
                $labels['add_new'] = 'Add New Lesson';
                $labels['add_new_item'] = 'Add New Lesson';
                $labels['edit_item'] = 'Edit Lesson';
                $labels['new_item'] = 'New Lesson';
                $labels['view_item'] = 'View Lesson';
                $labels['search_items'] = 'Search Lessons';
                $labels['not_found'] = 'No lessons found';
                $labels['not_found_in_trash'] = 'No lessons found in Trash';
                $labels['all_items'] = 'All Lessons';
                $labels['menu_name'] = 'Lessons';
                break;
        }
        
        return $labels;
    }
    
    /**
     * Fix Mark Complete AJAX handler
     */
    public function fix_mark_complete_handler() {
        // Only intervene if LearnDash isn't handling it properly
        if (!isset($_POST['post']) || !isset($_POST['learndash_mark_complete_nonce'])) {
            // Try to get data from alternative sources
            if (isset($_REQUEST['post_id'])) {
                $_POST['post'] = $_REQUEST['post_id'];
            }
            
            if (isset($_REQUEST['nonce'])) {
                $_POST['learndash_mark_complete_nonce'] = $_REQUEST['nonce'];
            }
        }
        
        // Log for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('LCCP Systems: Mark Complete request - Post ID: ' . ($_POST['post'] ?? 'not set'));
        }
    }
    
    /**
     * Verify LearnDash nonce
     */
    public function verify_learndash_nonce($verified, $nonce) {
        // If already verified, return true
        if ($verified) {
            return $verified;
        }
        
        // Additional verification for our modified forms
        if (isset($_POST['learndash_mark_complete_nonce'])) {
            $verified = wp_verify_nonce($_POST['learndash_mark_complete_nonce'], 'learndash_mark_complete');
        }
        
        return $verified;
    }
    
    /**
     * Track lesson (module) completion
     */
    public function on_lesson_completed($data) {
        $user_id = $data['user']->ID;
        $lesson_id = $data['lesson']->ID;
        $course_id = $data['course']->ID;
        
        // Log completion
        $this->log_completion('lesson', $user_id, $lesson_id, $course_id);
        
        // Clear related caches
        $this->clear_progress_cache($user_id, $course_id);
    }
    
    /**
     * Track topic (lesson) completion
     */
    public function on_topic_completed($data) {
        $user_id = $data['user']->ID;
        $topic_id = $data['topic']->ID;
        $lesson_id = $data['lesson']->ID;
        $course_id = $data['course']->ID;
        
        // Log completion
        $this->log_completion('topic', $user_id, $topic_id, $course_id, $lesson_id);
        
        // Clear related caches
        $this->clear_progress_cache($user_id, $course_id);
    }
    
    /**
     * Track course completion
     */
    public function on_course_completed($data) {
        $user_id = $data['user']->ID;
        $course_id = $data['course']->ID;
        
        // Log completion
        $this->log_completion('course', $user_id, $course_id);
        
        // Clear all caches for this user
        $this->clear_all_progress_cache($user_id);
        
        // Award achievement if applicable
        $this->check_for_achievements($user_id, $course_id);
    }
    
    /**
     * Log completion events
     */
    private function log_completion($type, $user_id, $item_id, $course_id = null, $parent_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'lccp_completions';
        
        // Create table if it doesn't exist
        $wpdb->query("
            CREATE TABLE IF NOT EXISTS $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                item_type varchar(20) NOT NULL,
                item_id bigint(20) NOT NULL,
                course_id bigint(20) DEFAULT NULL,
                parent_id bigint(20) DEFAULT NULL,
                completed_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY item_id (item_id),
                KEY course_id (course_id)
            ) " . $wpdb->get_charset_collate()
        );
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'item_type' => $type,
                'item_id' => $item_id,
                'course_id' => $course_id,
                'parent_id' => $parent_id,
                'completed_at' => current_time('mysql')
            )
        );
    }
    
    /**
     * Clear progress cache
     */
    private function clear_progress_cache($user_id, $course_id) {
        // Clear LearnDash progress cache
        delete_transient('learndash_course_progress_' . $user_id . '_' . $course_id);
        delete_user_meta($user_id, '_sfwd-course_progress');
        
        // Clear our custom cache
        wp_cache_delete('lccp_course_progress_' . $user_id . '_' . $course_id, 'lccp_learndash');
    }
    
    /**
     * Clear all progress cache for a user
     */
    private function clear_all_progress_cache($user_id) {
        // Clear all LearnDash caches for this user
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s",
            $user_id,
            '%_sfwd-%'
        ));
        
        // Clear object cache
        wp_cache_flush_group('lccp_learndash');
    }
    
    /**
     * Check for achievements after course completion
     */
    private function check_for_achievements($user_id, $course_id) {
        // Check if user has completed all required courses for certification
        $required_courses = get_option('lccp_required_courses', array());
        
        if (empty($required_courses)) {
            return;
        }
        
        $completed_courses = learndash_user_get_completed_courses($user_id);
        
        // Check if all required courses are completed
        $all_completed = true;
        foreach ($required_courses as $required_course_id) {
            if (!in_array($required_course_id, $completed_courses)) {
                $all_completed = false;
                break;
            }
        }
        
        if ($all_completed) {
            // Award certification achievement
            update_user_meta($user_id, 'lccp_certification_achieved', true);
            update_user_meta($user_id, 'lccp_certification_date', current_time('mysql'));
            
            // Trigger notification
            do_action('lccp_certification_achieved', $user_id);
        }
    }
    
    /**
     * Get enhanced progress data
     */
    public function get_enhanced_progress($user_id, $course_id) {
        $progress = learndash_course_progress(array(
            'user_id' => $user_id,
            'course_id' => $course_id,
            'array' => true
        ));
        
        // Add additional data
        $progress['modules_completed'] = $progress['completed'] ?? 0;
        $progress['modules_total'] = $progress['total'] ?? 0;
        
        // Get lesson/topic level progress
        $lessons = learndash_get_course_lessons_list($course_id, $user_id);
        $lesson_progress = array();
        
        foreach ($lessons as $lesson) {
            $topics = learndash_get_topic_list($lesson->ID, $course_id);
            $topics_completed = 0;
            
            foreach ($topics as $topic) {
                if (learndash_is_topic_complete($user_id, $topic->ID)) {
                    $topics_completed++;
                }
            }
            
            $lesson_progress[$lesson->ID] = array(
                'title' => $lesson->post_title,
                'completed' => learndash_is_lesson_complete($user_id, $lesson->ID),
                'topics_completed' => $topics_completed,
                'topics_total' => count($topics)
            );
        }
        
        $progress['lessons'] = $lesson_progress;
        
        return $progress;
    }
}