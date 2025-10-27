<?php
/**
 * LCCP Mentor System Module
 * 
 * Manages mentor-student relationships
 * 
 * @package LCCP_Systems
 * @subpackage Modules
 */

if (!defined('ABSPATH')) exit;

class LCCP_Mentor_System_Module {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'), 30);
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'lccp-systems',
            __('Mentor System', 'lccp-systems'),
            __('Mentors', 'lccp-systems'),
            'manage_options',
            'lccp-mentors',
            array($this, 'render_admin_page')
        );
    }
    
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('LCCP Mentor System', 'lccp-systems'); ?></h1>
            <p><?php _e('Manage mentor-student relationships for the certification program.', 'lccp-systems'); ?></p>
        </div>
        <?php
    }
}

LCCP_Mentor_System_Module::get_instance();