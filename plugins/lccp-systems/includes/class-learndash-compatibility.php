<?php
/**
 * LearnDash Compatibility Class
 * Ensures Mark Complete button and other LearnDash features work correctly
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_LearnDash_Compatibility {
    
    public function __construct() {
        // Only initialize if LearnDash is active
        if (!class_exists('SFWD_LMS')) {
            return;
        }
        
        // Fix Mark Complete button
        add_action('wp_enqueue_scripts', array($this, 'fix_mark_complete_scripts'), 999);
        add_filter('learndash_mark_complete_process', array($this, 'ensure_mark_complete_works'), 5, 3);
        
        // Remove any conflicting scripts
        add_action('wp_print_scripts', array($this, 'remove_conflicting_scripts'), 100);
        
        // Ensure AJAX handlers work
        add_action('init', array($this, 'ensure_ajax_handlers'));
        
        // Fix any theme conflicts
        add_action('after_setup_theme', array($this, 'fix_theme_conflicts'), 20);
    }
    
    public function fix_mark_complete_scripts() {
        if (!is_singular()) {
            return;
        }
        
        $post_type = get_post_type();
        $learndash_post_types = array('sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz');
        
        if (!in_array($post_type, $learndash_post_types)) {
            return;
        }
        
        // Add inline script to ensure mark complete works
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                // Ensure LearnDash mark complete button works
                $(document).on("click", ".learndash_mark_complete_button, .wpProQuiz_button", function(e) {
                    // Don\'t prevent default - let LearnDash handle it
                    console.log("Mark Complete button clicked");
                });
                
                // Remove any other click handlers that might interfere
                $(".learndash_mark_complete_button").off("click.lccp");
                $("#sfwd-mark-complete").off("submit.lccp");
                
                // Ensure form submission works
                $(document).on("submit", "#sfwd-mark-complete", function(e) {
                    // Let LearnDash handle the submission
                    return true;
                });
                
                // Fix quiz completion
                if (typeof wpProQuiz_loadData !== "undefined") {
                    // Ensure quiz data loads properly
                    $(document).trigger("learndash_quiz_loaded");
                }
                
                // Monitor for AJAX completion
                $(document).ajaxComplete(function(event, xhr, settings) {
                    if (settings.data && settings.data.indexOf("learndash_") !== -1) {
                        // Refresh any UI elements after LearnDash AJAX
                        $(document).trigger("learndash_ajax_complete");
                    }
                });
            });
        ');
        
        // Add CSS to ensure button is visible and clickable
        wp_add_inline_style('learndash_style', '
            .learndash_mark_complete_button,
            .wpProQuiz_button {
                position: relative !important;
                z-index: 999 !important;
                pointer-events: auto !important;
                opacity: 1 !important;
                visibility: visible !important;
            }
            
            #sfwd-mark-complete {
                position: relative !important;
                z-index: 999 !important;
            }
            
            .learndash_mark_complete_button:hover {
                cursor: pointer !important;
            }
            
            /* Ensure button is not covered by other elements */
            .learndash_mark_complete_wrapper {
                position: relative;
                z-index: 1000;
                clear: both;
            }
        ');
    }
    
    public function remove_conflicting_scripts() {
        if (!is_singular()) {
            return;
        }
        
        $post_type = get_post_type();
        $learndash_post_types = array('sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz');
        
        if (!in_array($post_type, $learndash_post_types)) {
            return;
        }
        
        // List of potentially conflicting script handles
        $conflicting_scripts = array(
            'custom-mark-complete', // Example of conflicting script
            'theme-lesson-handler'  // Another example
        );
        
        foreach ($conflicting_scripts as $handle) {
            if (wp_script_is($handle, 'enqueued')) {
                wp_dequeue_script($handle);
            }
        }
    }
    
    public function ensure_mark_complete_works($return, $post, $user) {
        // Ensure the mark complete process works correctly
        if (!$return) {
            // Log the issue for debugging
            error_log('LCCP: Mark Complete failed for post ' . $post->ID . ' and user ' . $user->ID);
            
            // Try to fix common issues
            if (!learndash_is_lesson_complete($user->ID, $post->ID)) {
                // Force completion if conditions are met
                $course_id = learndash_get_course_id($post->ID);
                
                if ($course_id && learndash_is_user_in_course($user->ID, $course_id)) {
                    // User is in course, allow completion
                    return true;
                }
            }
        }
        
        return $return;
    }
    
    public function ensure_ajax_handlers() {
        // Ensure LearnDash AJAX handlers are registered
        if (!has_action('wp_ajax_learndash_mark_complete')) {
            // Re-register if missing
            add_action('wp_ajax_learndash_mark_complete', 'learndash_mark_complete');
        }
        
        if (!has_action('wp_ajax_nopriv_learndash_mark_complete')) {
            add_action('wp_ajax_nopriv_learndash_mark_complete', 'learndash_mark_complete');
        }
        
        // Ensure quiz AJAX works
        if (!has_action('wp_ajax_wp_pro_quiz_completed_quiz')) {
            add_action('wp_ajax_wp_pro_quiz_completed_quiz', 'learndash_quiz_completed');
        }
    }
    
    public function fix_theme_conflicts() {
        // Remove theme functions that might interfere with LearnDash
        remove_action('learndash_lesson_completed', 'theme_lesson_completed_override', 10);
        remove_filter('learndash_completion_redirect', 'theme_completion_redirect_override', 10);
        
        // Ensure LearnDash templates load correctly
        add_filter('learndash_template', array($this, 'ensure_template_loads'), 999, 5);
    }
    
    public function ensure_template_loads($filepath, $name, $args, $echo, $return_file_path) {
        // Ensure template file exists and is readable
        if (!file_exists($filepath) || !is_readable($filepath)) {
            // Try to find alternative template
            $alternative = locate_template(array(
                'learndash/' . $name . '.php',
                $name . '.php'
            ));
            
            if ($alternative) {
                return $alternative;
            }
        }
        
        return $filepath;
    }
    
    /**
     * Additional helper to debug mark complete issues
     */
    public function debug_mark_complete() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        add_action('admin_bar_menu', function($wp_admin_bar) {
            if (is_singular(array('sfwd-lessons', 'sfwd-topic', 'sfwd-quiz'))) {
                $post_id = get_the_ID();
                $user_id = get_current_user_id();
                $is_complete = learndash_is_lesson_complete($user_id, $post_id);
                
                $wp_admin_bar->add_node(array(
                    'id' => 'lccp-ld-debug',
                    'title' => 'LD Debug: ' . ($is_complete ? 'Complete' : 'Incomplete'),
                    'href' => '#',
                    'meta' => array(
                        'class' => $is_complete ? 'lccp-complete' : 'lccp-incomplete'
                    )
                ));
            }
        }, 999);
    }
}

// Initialize the compatibility class
new LCCP_LearnDash_Compatibility();