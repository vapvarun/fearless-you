<?php
/**
 * LCCP Roles Manager Module
 * 
 * Manages custom roles for LCCP
 * 
 * @package LCCP_Systems
 * @subpackage Modules
 */

if (!defined('ABSPATH')) exit;

class LCCP_Roles_Module {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'setup_roles'));
        add_action('admin_menu', array($this, 'add_admin_menu'), 40);
    }
    
    public function setup_roles() {
        // Add LCCP custom roles if they don't exist
        if (!get_role('lccp_pc')) {
            add_role('lccp_pc', __('Program Candidate', 'lccp-systems'), array(
                'read' => true,
                'upload_files' => true
            ));
        }
        
        if (!get_role('lccp_mentor')) {
            add_role('lccp_mentor', __('LCCP Mentor', 'lccp-systems'), array(
                'read' => true,
                'upload_files' => true,
                'edit_posts' => true
            ));
        }
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'lccp-systems',
            __('Roles', 'lccp-systems'),
            __('Roles', 'lccp-systems'),
            'manage_options',
            'lccp-roles',
            array($this, 'render_admin_page')
        );
    }
    
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('LCCP Roles Manager', 'lccp-systems'); ?></h1>
            <p><?php _e('Manage user roles for the certification program.', 'lccp-systems'); ?></p>
        </div>
        <?php
    }
}

LCCP_Roles_Module::get_instance();