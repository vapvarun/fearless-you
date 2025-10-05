<?php
/**
 * Plugin Name: Fearless You Systems
 * Plugin URI: https://fearlessliving.org
 * Description: Membership management system for Fearless You Members, Faculty, and Ambassadors
 * Version: 1.0.0
 * Author: Fearless Living Institute
 * License: GPL-2.0-or-later
 * Text Domain: fearless-you-systems
 */

if (!defined('ABSPATH')) {
    exit;
}

define('FYS_VERSION', '1.0.0');
define('FYS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FYS_PLUGIN_URL', plugin_dir_url(__FILE__));

class Fearless_You_Systems {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));

        // Load includes
        $this->load_includes();

        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    private function load_includes() {
        require_once FYS_PLUGIN_DIR . 'includes/class-role-manager.php';
        require_once FYS_PLUGIN_DIR . 'admin/class-fym-settings.php';
        require_once FYS_PLUGIN_DIR . 'includes/class-member-dashboard.php';
        require_once FYS_PLUGIN_DIR . 'includes/class-faculty-dashboard.php';
        require_once FYS_PLUGIN_DIR . 'includes/class-ambassador-dashboard.php';
        require_once FYS_PLUGIN_DIR . 'includes/class-analytics.php';
    }

    public function load_textdomain() {
        load_plugin_textdomain('fearless-you-systems', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function init() {
        // Initialize components
        FYS_Role_Manager::get_instance();
        FYM_Settings::get_instance();
        FYS_Member_Dashboard::get_instance();
        FYS_Faculty_Dashboard::get_instance();
        FYS_Ambassador_Dashboard::get_instance();
    }

    public function activate() {
        // Create/update roles on activation
        FYS_Role_Manager::get_instance()->setup_roles();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

// Initialize the plugin
Fearless_You_Systems::get_instance();