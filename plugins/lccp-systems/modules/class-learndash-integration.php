<?php
/**
 * LCCP LearnDash Integration Module
 * 
 * Enhanced LearnDash integration
 * 
 * @package LCCP_Systems
 * @subpackage Modules
 */

if (!defined('ABSPATH')) exit;

class LCCP_LearnDash_Module {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        if (!class_exists('SFWD_LMS')) {
            return;
        }
        
        add_action('learndash_course_completed', array($this, 'on_course_completed'), 10, 2);
        add_filter('learndash_course_grid_html', array($this, 'customize_course_grid'), 10, 2);
    }
    
    public function on_course_completed($data, $user) {
        // Handle course completion
        $user_id = $user->ID;
        $course_id = $data['course']->ID;
        
        // Log milestone
        update_user_meta($user_id, 'lccp_course_' . $course_id . '_completed', current_time('mysql'));
    }
    
    public function customize_course_grid($html, $course_id) {
        // Customize course grid display
        return $html;
    }
}

LCCP_LearnDash_Module::get_instance();