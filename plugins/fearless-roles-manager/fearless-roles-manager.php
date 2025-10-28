<?php
/**
 * Plugin Name: Fearless Roles Manager
 * Plugin URI: https://fearlessliving.org
 * Description: Manage WordPress roles with WP Fusion tags, permissions, and dashboard landing pages
 * Version: 1.0.0
 * Author: Fearless Living
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FRM_VERSION', '1.0.0');
define('FRM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FRM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once FRM_PLUGIN_DIR . 'includes/class-roles-manager.php';
require_once FRM_PLUGIN_DIR . 'includes/class-admin-page.php';
require_once FRM_PLUGIN_DIR . 'includes/class-dashboard-redirect.php';

// Initialize the plugin
add_action('plugins_loaded', function() {
    new FearlessRolesManager();
});

// Activation hook
register_activation_hook(__FILE__, function() {
    // Create default settings
    if (!get_option('frm_role_settings')) {
        update_option('frm_role_settings', array());
    }
});

// Main plugin class
class FearlessRolesManager {
    
    private $admin_page;
    private $dashboard_redirect;
    
    public function __construct() {
        $this->admin_page = new FRM_Admin_Page();
        $this->dashboard_redirect = new FRM_Dashboard_Redirect();
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Add AJAX handlers
        add_action('wp_ajax_frm_save_role_settings', array($this, 'save_role_settings'));
        add_action('wp_ajax_frm_get_role_capabilities', array($this, 'get_role_capabilities'));
        add_action('wp_ajax_frm_save_role_visibility', array($this, 'save_role_visibility'));
        add_action('wp_ajax_frm_get_users_by_role', array($this, 'get_users_by_role'));
        add_action('wp_ajax_frm_save_category_assignments', array($this, 'save_category_assignments'));
        add_action('wp_ajax_frm_get_wp_fusion_tags', array($this, 'get_wp_fusion_tags'));
        add_action('wp_ajax_frm_save_role_tags', array($this, 'save_role_tags'));
        add_action('wp_ajax_frm_process_single_role_tags', array($this, 'process_single_role_tags'));

        // Role Categories forms (add/edit/delete)
        add_action('admin_post_frm_add_role_category', array($this, 'handle_add_role_category'));
        add_action('admin_post_frm_edit_role_category', array($this, 'handle_edit_role_category'));
        add_action('admin_post_frm_delete_role_category', array($this, 'handle_delete_role_category'));
        add_action('admin_post_frm_save_category_assignments', array($this, 'handle_save_category_assignments'));

        // Manage roles: rename/delete
        add_action('admin_post_frm_rename_role', array($this, 'handle_rename_role'));
        add_action('admin_post_frm_delete_role', array($this, 'handle_delete_role'));
        add_action('admin_post_frm_process_role_tags', array($this, 'handle_process_role_tags'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Roles Manager',
            'Roles Manager',
            'manage_options',
            'fearless-roles-manager',
            array($this->admin_page, 'render_page'),
            'dashicons-groups',
            25
        );
        
        // Add Settings submenu
        add_submenu_page(
            'fearless-roles-manager',
            'Role Settings',
            'Settings',
            'manage_options',
            'fearless-roles-settings',
            array($this, 'render_settings_page')
        );
        
        // Add User Management submenu
        add_submenu_page(
            'fearless-roles-manager',
            'User Management',
            'User Management',
            'manage_options',
            'fearless-roles-users',
            array($this, 'render_users_page')
        );
    }
    
    public function enqueue_admin_assets($hook) {
        // Load assets on all Roles Manager pages (top-level and submenus)
        $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
        if (strpos($hook, 'fearless-roles-manager') === false
            && strpos($hook, 'fearless-roles-') === false
            && strpos($page, 'fearless-roles-') !== 0) {
            return;
        }

        wp_enqueue_style('frm-admin', FRM_PLUGIN_URL . 'assets/admin.css', array(), FRM_VERSION);
        wp_enqueue_script('frm-admin', FRM_PLUGIN_URL . 'assets/admin.js', array('jquery'), FRM_VERSION, true);

        // If WP Fusion doesn't provide the multiselect function, enqueue Select2
        if (function_exists('wp_fusion') && !function_exists('wpf_render_tag_multiselect')) {
            // Try to use WP Fusion's Select2 if available
            if (defined('WPF_DIR_URL')) {
                wp_enqueue_style('select4', WPF_DIR_URL . 'includes/admin/options/lib/select2/select4.min.css', array(), '4.0.1');
                wp_enqueue_script('select4', WPF_DIR_URL . 'includes/admin/options/lib/select2/select4.min.js', array('jquery'), '4.0.1', true);
            }
        }

        wp_localize_script('frm-admin', 'frm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('frm_ajax_nonce'),
            'wp_fusion_tags' => FRM_Roles_Manager::get_wp_fusion_tags()
        ));
    }
    
    public function save_role_settings() {
        if (!check_ajax_referer('frm_ajax_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
        update_option('frm_role_settings', $settings);
        
        wp_send_json_success('Settings saved successfully');
    }
    
    public function get_role_capabilities() {
        if (!check_ajax_referer('frm_ajax_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        $role_name = sanitize_text_field($_POST['role']);
        $role = get_role($role_name);
        
        if ($role) {
            wp_send_json_success($role->capabilities);
        } else {
            wp_send_json_error('Role not found');
        }
    }
    
    public function save_role_visibility() {
        if (!check_ajax_referer('frm_ajax_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $visibility_settings = isset($_POST['visibility']) ? $_POST['visibility'] : array();
        FRM_Roles_Manager::save_role_visibility_settings($visibility_settings);
        
        wp_send_json_success('Visibility settings saved successfully');
    }
    
    public function get_users_by_role() {
        if (!check_ajax_referer('frm_ajax_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        $role_key = sanitize_text_field($_POST['role']);
        $users = FRM_Roles_Manager::get_users_with_role($role_key);
        
        wp_send_json_success($users);
    }
    
    public function save_category_assignments() {
        if (!check_ajax_referer('frm_ajax_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $assignments = isset($_POST['category_assignments']) ? $_POST['category_assignments'] : array();
        FRM_Roles_Manager::save_role_category_assignments($assignments);
        
        wp_send_json_success('Category assignments saved successfully');
    }
    
    public function get_wp_fusion_tags() {
        if (!check_ajax_referer('frm_ajax_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }

        $tags = FRM_Roles_Manager::get_wp_fusion_tags();
        wp_send_json_success($tags);
    }

    // AJAX: Save role tags immediately when changed
    public function save_role_tags() {
        if (!check_ajax_referer('frm_save_role_tags', 'nonce', false)) {
            wp_send_json_error('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $role = isset($_POST['role']) ? sanitize_key($_POST['role']) : '';
        $tags = isset($_POST['tags']) && is_array($_POST['tags']) ? array_map('sanitize_text_field', $_POST['tags']) : array();

        if (!$role) {
            wp_send_json_error('Invalid role');
        }

        // Get current saved tags
        $saved_tags = get_option('frm_role_wpfusion_tags', array());

        // Update tags for this role
        if (!empty($tags)) {
            $saved_tags[$role] = $tags;
        } else {
            // Remove role from saved tags if no tags selected
            unset($saved_tags[$role]);
        }

        // Save updated tags
        update_option('frm_role_wpfusion_tags', $saved_tags);

        // Get tag names for response
        $tag_names = array();
        if (function_exists('wp_fusion') && !empty($tags)) {
            $available_tags = wp_fusion()->settings->get_available_tags_flat();
            foreach ($tags as $tag_id) {
                if (isset($available_tags[$tag_id])) {
                    $tag_names[] = $available_tags[$tag_id];
                }
            }
        }

        // Get role name
        $role_obj = get_role($role);
        $role_name = $role ? wp_roles()->roles[$role]['name'] : $role;

        wp_send_json_success(array(
            'message' => 'Tags saved successfully',
            'role_name' => $role_name,
            'tag_names' => $tag_names
        ));
    }

    // AJAX: Process users with tags for a single role
    public function process_single_role_tags() {
        if (!check_ajax_referer('frm_process_single_role_tags', 'nonce', false)) {
            wp_send_json_error('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $role = isset($_POST['role']) ? sanitize_key($_POST['role']) : '';
        if (!$role) {
            wp_send_json_error('Invalid role');
        }

        // Get saved tags for this role
        $saved_tags = get_option('frm_role_wpfusion_tags', array());
        if (!isset($saved_tags[$role]) || empty($saved_tags[$role])) {
            wp_send_json_error('No tags configured for this role');
        }

        $role_tags = $saved_tags[$role];
        $users_processed = 0;
        $users_updated = 0;

        // Get all users
        $users = get_users();

        foreach ($users as $user) {
            $users_processed++;

            // Get user's tags using WP Fusion's method
            $user_tags = array();
            if (function_exists('wp_fusion') && method_exists(wp_fusion()->user, 'get_tags')) {
                $user_tags = (array) wp_fusion()->user->get_tags($user->ID);
            }

            // Check if user has any of the role's tags
            $has_tag = false;
            foreach ($role_tags as $tag_id) {
                if (in_array($tag_id, $user_tags)) {
                    $has_tag = true;
                    break;
                }
            }

            // Add role if user has tag but not the role
            if ($has_tag && !in_array($role, $user->roles)) {
                $user->add_role($role);
                $users_updated++;
            }
        }

        wp_send_json_success(array(
            'message' => sprintf('Processed %d users. Added role to %d users.', $users_processed, $users_updated)
        ));
    }

    // Handle: add custom role category
    public function handle_add_role_category() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['_wpnonce'] ?? '', 'frm_add_role_category')) {
            wp_die('Permission denied');
        }
        $key = sanitize_key($_POST['category_key'] ?? '');
        $name = sanitize_text_field($_POST['category_name'] ?? '');
        $desc = sanitize_text_field($_POST['category_description'] ?? '');
        $color = preg_match('/^#([A-Fa-f0-9]{6})$/', $_POST['category_color'] ?? '') ? $_POST['category_color'] : '#6b7280';
        $icon = sanitize_text_field($_POST['category_icon'] ?? 'dashicons-category');
        if (!$key || !$name) {
            wp_redirect(add_query_arg(array('tab' => 'categories', 'frm_notice' => urlencode('Missing slug or name')), admin_url('admin.php?page=fearless-roles-settings')));
            exit;
        }
        $saved = get_option('frm_role_categories', array());
        if (!is_array($saved)) { $saved = array(); }
        if (isset($saved[$key])) {
            wp_redirect(add_query_arg(array('tab' => 'categories', 'frm_notice' => urlencode('Category already exists')), admin_url('admin.php?page=fearless-roles-settings')));
            exit;
        }
        $saved[$key] = array('name' => $name, 'description' => $desc, 'color' => $color, 'icon' => $icon);
        update_option('frm_role_categories', $saved);
        wp_redirect(add_query_arg('tab', 'categories', admin_url('admin.php?page=fearless-roles-settings')));
        exit;
    }

    // Handle: edit custom role category
    public function handle_edit_role_category() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['_wpnonce'] ?? '', 'frm_edit_role_category')) {
            wp_die('Permission denied');
        }

        $original_key = sanitize_key($_POST['original_key'] ?? '');
        $name = sanitize_text_field($_POST['category_name'] ?? '');
        $desc = sanitize_text_field($_POST['category_description'] ?? '');
        $color = preg_match('/^#([A-Fa-f0-9]{6})$/', $_POST['category_color'] ?? '') ? $_POST['category_color'] : '#6b7280';
        $icon = sanitize_text_field($_POST['category_icon'] ?? 'dashicons-category');

        if (!$original_key || !$name) {
            wp_redirect(add_query_arg(array('tab' => 'categories', 'frm_notice' => urlencode('Missing category key or name')), admin_url('admin.php?page=fearless-roles-settings')));
            exit;
        }

        // Don't allow editing default categories
        $defaults = FRM_Roles_Manager::get_default_role_categories();
        if (isset($defaults[$original_key])) {
            wp_redirect(add_query_arg(array('tab' => 'categories', 'frm_notice' => urlencode('Cannot edit default categories')), admin_url('admin.php?page=fearless-roles-settings')));
            exit;
        }

        $saved = get_option('frm_role_categories', array());
        if (!is_array($saved)) { $saved = array(); }

        // Update the category
        if (isset($saved[$original_key])) {
            $saved[$original_key] = array('name' => $name, 'description' => $desc, 'color' => $color, 'icon' => $icon);
            update_option('frm_role_categories', $saved);
            wp_redirect(add_query_arg(array('tab' => 'categories', 'frm_notice' => urlencode('Category updated successfully')), admin_url('admin.php?page=fearless-roles-settings')));
        } else {
            wp_redirect(add_query_arg(array('tab' => 'categories', 'frm_notice' => urlencode('Category not found')), admin_url('admin.php?page=fearless-roles-settings')));
        }
        exit;
    }

    // Handle: delete custom role category
    public function handle_delete_role_category() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['_wpnonce'] ?? '', 'frm_delete_role_category')) {
            wp_die('Permission denied');
        }
        $key = sanitize_key($_POST['category_key'] ?? '');
        if (!$key) {
            wp_redirect(add_query_arg(array('tab' => 'categories', 'frm_notice' => urlencode('Missing category key')), admin_url('admin.php?page=fearless-roles-settings')));
            exit;
        }
        // Do not allow removing default categories
        $defaults = FRM_Roles_Manager::get_default_role_categories();
        if (isset($defaults[$key])) {
            wp_redirect(add_query_arg(array('tab' => 'categories', 'frm_notice' => urlencode('Cannot delete default category')), admin_url('admin.php?page=fearless-roles-settings')));
            exit;
        }
        $saved = get_option('frm_role_categories', array());
        if (is_array($saved) && isset($saved[$key])) {
            unset($saved[$key]);
            update_option('frm_role_categories', $saved);
        }
        // Unassign category from roles
        $assignments = get_option('frm_role_category_assignments', array());
        if (is_array($assignments)) {
            foreach ($assignments as $role => $cat) {
                if ($cat === $key) {
                    unset($assignments[$role]);
                }
            }
            update_option('frm_role_category_assignments', $assignments);
        }
        wp_redirect(add_query_arg('tab', 'categories', admin_url('admin.php?page=fearless-roles-settings')));
        exit;
    }
    
    public function render_settings_page() {
        $roles = FRM_Roles_Manager::get_all_roles();
        $visibility_settings = FRM_Roles_Manager::get_role_visibility_settings();
        $stats = FRM_Roles_Manager::get_role_statistics();
        $categories = FRM_Roles_Manager::get_role_categories();
        $category_assignments = FRM_Roles_Manager::get_role_category_assignments();
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
        $tabs = array(
            'overview' => 'Overview',
            'categories' => 'Categories',
            'assignments' => 'Assignments',
            'visibility' => 'Visibility',
            'manage' => 'Manage Roles',
        );
        ?>
        <div class="wrap frm-settings-wrap">
            <h1><span class="dashicons dashicons-admin-settings"></span> Role Settings</h1>
            <h2 class="nav-tab-wrapper">
                <?php foreach ($tabs as $tab_key => $tab_label): ?>
                    <?php $url = esc_url(add_query_arg('tab', $tab_key, admin_url('admin.php?page=fearless-roles-settings'))); ?>
                    <a href="<?php echo $url; ?>" class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>"><?php echo esc_html($tab_label); ?></a>
                <?php endforeach; ?>
            </h2>
            <?php if (!empty($_GET['frm_notice'])): ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html($_GET['frm_notice']); ?></p></div>
            <?php endif; ?>
            <?php if (!empty($_GET['frm_error'])): ?>
                <div class="notice notice-error is-dismissible"><p><?php echo esc_html($_GET['frm_error']); ?></p></div>
            <?php endif; ?>

            <?php if ($active_tab === 'overview'): ?>
                <div class="frm-stats-container">
                    <div class="frm-stat-card">
                        <h3>Total Roles</h3>
                        <span class="frm-stat-number"><?php echo $stats['total_roles']; ?></span>
                    </div>
                    <div class="frm-stat-card">
                        <h3>Total Users</h3>
                        <span class="frm-stat-number"><?php echo $stats['total_users']; ?></span>
                    </div>
                    <div class="frm-stat-card">
                        <h3>Active Roles</h3>
                        <span class="frm-stat-number"><?php echo $stats['roles_with_users']; ?></span>
                    </div>
                    <div class="frm-stat-card">
                        <h3>Empty Roles</h3>
                        <span class="frm-stat-number"><?php echo $stats['empty_roles']; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($active_tab === 'categories'): ?>
                <div class="frm-settings-container">
                    <div class="frm-section">
                        <h2><span class="dashicons dashicons-category"></span> Role Categories</h2>
                        <p>Organize roles into categories for better management and organization.</p>
                        <div class="frm-category-management">
                            <div class="frm-category-list">
                                <?php
                                $default_categories = FRM_Roles_Manager::get_default_role_categories();
                                $nonce_add_cat = wp_create_nonce('frm_add_role_category');
                                $nonce_del_cat = wp_create_nonce('frm_delete_role_category');

                                // Define icons array for both forms
                                $icons = array(
                                    'dashicons-category' => 'Category',
                                    'dashicons-admin-users' => 'Users',
                                    'dashicons-groups' => 'Groups',
                                    'dashicons-tag' => 'Tag',
                                    'dashicons-wordpress' => 'WordPress',
                                    'dashicons-lock' => 'Lock',
                                    'dashicons-admin-home' => 'Home',
                                    'dashicons-welcome-learn-more' => 'Learn',
                                    'dashicons-cart' => 'Cart',
                                    'dashicons-admin-tools' => 'Tools',
                                    'dashicons-media-document' => 'Document',
                                    'dashicons-star-empty' => 'Star',
                                    'dashicons-awards' => 'Awards',
                                    'dashicons-chart-bar' => 'Chart',
                                    'dashicons-yes' => 'Checkmark',
                                    'dashicons-shield' => 'Shield',
                                    'dashicons-businessman' => 'Business',
                                    'dashicons-portfolio' => 'Portfolio',
                                    'dashicons-book' => 'Book',
                                    'dashicons-clipboard' => 'Clipboard'
                                );

                                // Build user counts per category
                                $users_by_role = FRM_Roles_Manager::get_all_users_by_role();
                                $assignments = FRM_Roles_Manager::get_role_category_assignments();
                                $category_user_counts = array();
                                $core_wp_roles = array('administrator','editor','author','contributor','subscriber');
                                foreach ($users_by_role as $role_key => $role_info) {
                                    $cat = isset($assignments[$role_key]) ? $assignments[$role_key] : 'community';
                                    if (in_array($role_key, $core_wp_roles, true)) {
                                        $cat = 'core';
                                    }
                                    if (!isset($category_user_counts[$cat])) {
                                        $category_user_counts[$cat] = 0;
                                    }
                                    $category_user_counts[$cat] += intval($role_info['count']);
                                }

                                foreach ($categories as $category_key => $category_data):
                                    $is_default = isset($default_categories[$category_key]);
                                    $cat_users = isset($category_user_counts[$category_key]) ? $category_user_counts[$category_key] : 0;
                                ?>
                                    <div class="frm-category-item <?php echo $is_default ? 'frm-category-default' : ''; ?>" data-category="<?php echo esc_attr($category_key); ?>" style="position:relative;">
                                        <div class="frm-category-color" style="background-color: <?php echo esc_attr($category_data['color']); ?>"></div>
                                        <div class="frm-category-details">
                                            <h4>
                                                <?php echo esc_html($category_data['name']); ?>
                                                <span class="frm-badge" style="background: #0073aa; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin-left: 8px;">
                                                    <?php echo intval($cat_users); ?> users
                                                </span>
                                                <?php if ($is_default): ?>
                                                    <span class="frm-badge" style="background: #ddd; color: #666; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin-left: 8px;">Default</span>
                                                <?php endif; ?>
                                            </h4>
                                            <p><?php echo esc_html($category_data['description']); ?></p>
                                            <span class="frm-category-icon"><span class="dashicons <?php echo esc_attr($category_data['icon']); ?>"></span></span>
                                        </div>
                                        <div style="position:absolute;top:10px;right:10px;display:flex;gap:5px;">
                                            <?php if (!$is_default): ?>
                                                <button class="frm-edit-category" data-key="<?php echo esc_attr($category_key); ?>" data-name="<?php echo esc_attr($category_data['name']); ?>" data-description="<?php echo esc_attr($category_data['description']); ?>" data-color="<?php echo esc_attr($category_data['color']); ?>" data-icon="<?php echo esc_attr($category_data['icon']); ?>" title="Edit category" style="background: var(--bb-primary-color, #0073aa); color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer;">
                                                    <span class="dashicons dashicons-edit" style="font-size: 14px; line-height: 1; width: 14px; height: 14px;"></span>
                                                </button>
                                            <?php endif; ?>
                                            <?php if (!$is_default || ($is_default && $category_key !== 'core')): ?>
                                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin:0;" onsubmit="return confirm('Delete this category? Roles using this category will be set to Custom.');">
                                                    <input type="hidden" name="action" value="frm_delete_role_category">
                                                    <input type="hidden" name="category_key" value="<?php echo esc_attr($category_key); ?>">
                                                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_del_cat); ?>">
                                                    <button type="submit" class="button-link-delete" title="Delete category" style="background: #dc3545; color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer;">
                                                        <span class="dashicons dashicons-trash" style="font-size: 14px; line-height: 1; width: 14px; height: 14px;"></span>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Edit Category Form (hidden by default) -->
                            <div class="frm-edit-category-form" style="display: none;">
                                <h3><span class="dashicons dashicons-edit"></span> Edit Category</h3>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                    <input type="hidden" name="action" value="frm_edit_role_category">
                                    <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('frm_edit_role_category'); ?>">
                                    <input type="hidden" name="original_key" id="frm_edit_original_key" value="">
                                    <table class="form-table" role="presentation">
                                        <tr>
                                            <th scope="row"><label for="frm_edit_cat_name">Name <span style="color: red;">*</span></label></th>
                                            <td>
                                                <input id="frm_edit_cat_name" name="category_name" type="text" class="regular-text" required>
                                                <p class="description">Display name for the category.</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="frm_edit_cat_desc">Description</label></th>
                                            <td>
                                                <textarea id="frm_edit_cat_desc" name="category_description" class="regular-text" rows="2"></textarea>
                                                <p class="description">Optional description to explain the category's purpose.</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="frm_edit_cat_color">Color</label></th>
                                            <td>
                                                <input id="frm_edit_cat_color" name="category_color" type="color" value="#6b7280" style="width: 80px; height: 40px; padding: 2px; cursor: pointer;">
                                                <input type="text" id="frm_edit_cat_color_text" value="#6b7280" pattern="^#[0-9A-Fa-f]{6}$" style="width: 100px; margin-left: 10px;">
                                                <p class="description">Choose a color to visually identify this category.</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="frm_edit_cat_icon">Icon</label></th>
                                            <td>
                                                <select id="frm_edit_cat_icon" name="category_icon" style="width: 250px;">
                                                    <?php
                                                    foreach ($icons as $icon => $label): ?>
                                                        <option value="<?php echo esc_attr($icon); ?>">
                                                            <?php echo esc_html($label); ?> (<?php echo esc_html($icon); ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <span id="frm_edit_icon_preview" class="dashicons dashicons-category" style="font-size: 24px; margin-left: 10px; vertical-align: middle;"></span>
                                                <p class="description">Select an icon to represent this category.</p>
                                            </td>
                                        </tr>
                                    </table>
                                    <p class="submit">
                                        <button type="submit" class="button button-primary">Update Category</button>
                                        <button type="button" class="button frm-cancel-edit">Cancel</button>
                                    </p>
                                </form>
                            </div>

                            <div class="frm-add-category-form">
                                <h3><span class="dashicons dashicons-plus-alt"></span> Add New Category</h3>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                    <input type="hidden" name="action" value="frm_add_role_category">
                                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_add_cat); ?>">
                                    <table class="form-table" role="presentation">
                                        <tr>
                                            <th scope="row"><label for="frm_cat_key">Slug <span style="color: red;">*</span></label></th>
                                            <td>
                                                <input id="frm_cat_key" name="category_key" type="text" class="regular-text" placeholder="e.g. leadership" pattern="[a-z0-9_-]+" required>
                                                <p class="description">Lowercase letters, numbers, hyphens and underscores only.</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="frm_cat_name">Name <span style="color: red;">*</span></label></th>
                                            <td>
                                                <input id="frm_cat_name" name="category_name" type="text" class="regular-text" placeholder="e.g. Leadership Team" required>
                                                <p class="description">Display name for the category.</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="frm_cat_desc">Description</label></th>
                                            <td>
                                                <textarea id="frm_cat_desc" name="category_description" class="regular-text" rows="2" placeholder="Brief description of this category"></textarea>
                                                <p class="description">Optional description to explain the category's purpose.</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="frm_cat_color">Color</label></th>
                                            <td>
                                                <input id="frm_cat_color" name="category_color" type="color" value="#6b7280" style="width: 80px; height: 40px; padding: 2px; cursor: pointer;">
                                                <input type="text" id="frm_cat_color_text" value="#6b7280" pattern="^#[0-9A-Fa-f]{6}$" style="width: 100px; margin-left: 10px;">
                                                <p class="description">Choose a color to visually identify this category.</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="frm_cat_icon">Icon</label></th>
                                            <td>
                                                <select id="frm_cat_icon" name="category_icon" style="width: 250px;">
                                                    <?php foreach ($icons as $icon => $label): ?>
                                                        <option value="<?php echo esc_attr($icon); ?>">
                                                            <?php echo esc_html($label); ?> (<?php echo esc_html($icon); ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <span id="frm_icon_preview" class="dashicons dashicons-category" style="font-size: 24px; margin-left: 10px; vertical-align: middle;"></span>
                                                <p class="description">Select an icon to represent this category.</p>
                                            </td>
                                        </tr>
                                    </table>
                                    <p class="submit">
                                        <button type="submit" class="button button-primary">
                                            <span class="dashicons dashicons-plus" style="vertical-align: middle;"></span> Add Category
                                        </button>
                                    </p>
                                </form>
                            </div>

                            <script type="text/javascript">
                            jQuery(document).ready(function($) {
                                // Color picker sync
                                $('#frm_cat_color').on('input', function() {
                                    $('#frm_cat_color_text').val($(this).val());
                                });
                                $('#frm_cat_color_text').on('input', function() {
                                    if (/^#[0-9A-Fa-f]{6}$/.test($(this).val())) {
                                        $('#frm_cat_color').val($(this).val());
                                    }
                                });

                                // Icon preview
                                $('#frm_cat_icon').on('change', function() {
                                    var selectedIcon = $(this).val();
                                    $('#frm_icon_preview').removeClass().addClass('dashicons ' + selectedIcon);
                                });

                                // Slug auto-generate from name
                                $('#frm_cat_name').on('blur', function() {
                                    if (!$('#frm_cat_key').val()) {
                                        var slug = $(this).val().toLowerCase()
                                            .replace(/[^\w\s-]/g, '')
                                            .replace(/\s+/g, '_')
                                            .replace(/-+/g, '_')
                                            .substring(0, 20);
                                        $('#frm_cat_key').val(slug);
                                    }
                                });

                                // Edit category functionality
                                $('.frm-edit-category').on('click', function(e) {
                                    e.preventDefault();
                                    var $btn = $(this);
                                    var key = $btn.data('key');
                                    var name = $btn.data('name');
                                    var description = $btn.data('description');
                                    var color = $btn.data('color');
                                    var icon = $btn.data('icon');

                                    // Populate edit form
                                    $('#frm_edit_original_key').val(key);
                                    $('#frm_edit_cat_name').val(name);
                                    $('#frm_edit_cat_desc').val(description);
                                    $('#frm_edit_cat_color').val(color);
                                    $('#frm_edit_cat_color_text').val(color);
                                    $('#frm_edit_cat_icon').val(icon);
                                    $('#frm_edit_icon_preview').removeClass().addClass('dashicons ' + icon);

                                    // Show edit form, hide add form
                                    $('.frm-edit-category-form').slideDown();
                                    $('.frm-add-category-form').slideUp();
                                });

                                // Cancel edit
                                $('.frm-cancel-edit').on('click', function() {
                                    $('.frm-edit-category-form').slideUp();
                                    $('.frm-add-category-form').slideDown();
                                });

                                // Edit form color sync
                                $('#frm_edit_cat_color').on('input', function() {
                                    $('#frm_edit_cat_color_text').val($(this).val());
                                });
                                $('#frm_edit_cat_color_text').on('input', function() {
                                    var color = $(this).val();
                                    if (/^#[0-9A-Fa-f]{6}$/.test(color)) {
                                        $('#frm_edit_cat_color').val(color);
                                    }
                                });

                                // Edit form icon preview
                                $('#frm_edit_cat_icon').on('change', function() {
                                    var selectedIcon = $(this).val();
                                    $('#frm_edit_icon_preview').removeClass().addClass('dashicons ' + selectedIcon);
                                });
                            });
                            </script>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($active_tab === 'assignments'): ?>
                <div class="frm-settings-container">
                    <div class="frm-section">
                        <h2><span class="dashicons dashicons-move"></span> Role Category Assignments</h2>
                        <p>Assign roles to specific categories for better organization.</p>
                        <form id="frm-category-assignments-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="frm_save_category_assignments">
                            <?php wp_nonce_field('frm_save_category_assignments'); ?>
                            <div class="frm-assignments-grid">
                                <?php foreach ($roles as $role_key => $role_data): ?>
                                    <?php 
                                    $current_category = isset($category_assignments[$role_key]) ? $category_assignments[$role_key] : 'community';
                                    $user_count = count(FRM_Roles_Manager::get_users_with_role($role_key));
                                    ?>
                                    <div class="frm-assignment-item">
                                        <div class="frm-role-info">
                                            <h4><?php echo esc_html($role_data['name']); ?></h4>
                                            <p class="frm-role-key"><?php echo esc_html($role_key); ?></p>
                                            <p class="frm-user-count">
                                                <span class="dashicons dashicons-groups"></span>
                                                <?php echo $user_count; ?> user<?php echo $user_count !== 1 ? 's' : ''; ?>
                                            </p>
                                        </div>
                                        <select name="category_assignments[<?php echo esc_attr($role_key); ?>]" 
                                                class="frm-category-select">
                                            <?php foreach ($categories as $cat_key => $cat_data): ?>
                                                <option value="<?php echo esc_attr($cat_key); ?>" 
                                                        <?php selected($current_category, $cat_key); ?>>
                                                    <?php echo esc_html($cat_data['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="frm-actions">
                                <button type="submit" class="button button-primary">
                                    <span class="dashicons dashicons-saved"></span> Save Category Assignments
                                </button>
                                <span class="frm-save-status"></span>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($active_tab === 'visibility'): ?>
                <div class="frm-settings-container">
                    <form id="frm-visibility-form">
                        <div class="frm-section">
                            <h2><span class="dashicons dashicons-visibility"></span> Role Visibility</h2>
                            <p>Enable or disable roles in the management interface. Disabled roles will not appear in the main roles manager.</p>
                            <div class="frm-visibility-grid">
                                <?php foreach ($roles as $role_key => $role_data): ?>
                                    <?php 
                                    $is_visible = isset($visibility_settings[$role_key]) ? $visibility_settings[$role_key] : true;
                                    $user_count = count(FRM_Roles_Manager::get_users_with_role($role_key));
                                    ?>
                                    <div class="frm-visibility-item">
                                        <label class="frm-toggle-switch">
                                            <input type="checkbox" 
                                                   name="visibility[<?php echo esc_attr($role_key); ?>]" 
                                                   value="1" 
                                                   <?php checked($is_visible, true); ?>
                                                   data-role="<?php echo esc_attr($role_key); ?>">
                                            <span class="frm-toggle-slider"></span>
                                        </label>
                                        <div class="frm-role-info">
                                            <h4><?php echo esc_html($role_data['name']); ?></h4>
                                            <p class="frm-role-key"><?php echo esc_html($role_key); ?></p>
                                            <p class="frm-user-count">
                                                <span class="dashicons dashicons-groups"></span>
                                                <?php echo $user_count; ?> user<?php echo $user_count !== 1 ? 's' : ''; ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="frm-actions">
                            <button type="submit" class="button button-primary">
                                <span class="dashicons dashicons-saved"></span> Save Settings
                            </button>
                            <span class="frm-save-status"></span>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($active_tab === 'manage'): ?>
                <div class="frm-settings-container">
                    <div class="frm-section">
                        <h2><span class="dashicons dashicons-admin-users"></span> Manage Roles</h2>
                        <p>Rename roles, assign WP Fusion tags, or delete non-core roles. Protected core roles: Administrator, Editor, Author, Subscriber.</p>
                        <?php
                        $categories = FRM_Roles_Manager::get_role_categories();
                        $assignments = FRM_Roles_Manager::get_role_category_assignments();
                        $wp_fusion_tags = array();
                        $role_tags = array();

                        // Get WP Fusion tags if available
                        if (function_exists('wp_fusion')) {
                            $raw_tags = wp_fusion()->settings->get('available_tags', array());
                            foreach ($raw_tags as $tag_id => $tag_data) {
                                if (is_array($tag_data)) {
                                    $label = isset($tag_data['label']) ? $tag_data['label'] : 'Tag #' . $tag_id;
                                    if (isset($tag_data['category']) && !empty($tag_data['category'])) {
                                        $label .= ' [' . $tag_data['category'] . ']';
                                    }
                                    $wp_fusion_tags[$tag_id] = $label;
                                } else {
                                    $wp_fusion_tags[$tag_id] = $tag_data;
                                }
                            }

                            // Get saved role tags
                            $role_tags = get_option('frm_role_wpfusion_tags', array());
                        }

                        // Group roles by category
                        $roles_by_category = array();
                        foreach ($roles as $role_key => $role_data) {
                            $category = isset($assignments[$role_key]) ? $assignments[$role_key] : 'community';
                            if (!isset($roles_by_category[$category])) {
                                $roles_by_category[$category] = array();
                            }
                            $roles_by_category[$category][$role_key] = $role_data;
                        }

                        // Sort categories to show core first
                        $category_order = array('core', 'learning', 'fym', 'lccp', 'community');
                        $sorted_categories = array();
                        foreach ($category_order as $cat) {
                            if (isset($roles_by_category[$cat])) {
                                $sorted_categories[$cat] = $roles_by_category[$cat];
                            }
                        }
                        // Add any remaining categories
                        foreach ($roles_by_category as $cat => $roles_in_cat) {
                            if (!isset($sorted_categories[$cat])) {
                                $sorted_categories[$cat] = $roles_in_cat;
                            }
                        }
                        ?>

                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="frm-category-assignments-form">
                            <?php wp_nonce_field('frm_save_category_assignments'); ?>
                            <input type="hidden" name="action" value="frm_save_category_assignments" />

                            <?php foreach ($sorted_categories as $category_key => $category_roles): ?>
                                <?php
                                $category_data = isset($categories[$category_key]) ? $categories[$category_key] : array(
                                    'name' => 'Uncategorized',
                                    'color' => '#6b7280',
                                    'icon' => 'dashicons-admin-generic'
                                );
                                ?>
                                <div class="frm-category-section" style="margin-bottom: 30px;">
                                    <div class="frm-category-header" style="display: flex; align-items: center; padding: 10px 15px; background: linear-gradient(135deg, <?php echo esc_attr($category_data['color']); ?>20 0%, <?php echo esc_attr($category_data['color']); ?>10 100%); border-left: 4px solid <?php echo esc_attr($category_data['color']); ?>; margin-bottom: 0;">
                                        <span class="dashicons <?php echo esc_attr($category_data['icon']); ?>" style="color: <?php echo esc_attr($category_data['color']); ?>; margin-right: 10px; font-size: 24px;"></span>
                                        <h3 style="margin: 0; color: <?php echo esc_attr($category_data['color']); ?>;"><?php echo esc_html($category_data['name']); ?></h3>
                                        <span style="margin-left: auto; color: #666; font-size: 14px;">
                                            <?php echo count($category_roles); ?> role<?php echo count($category_roles) !== 1 ? 's' : ''; ?>
                                        </span>
                                    </div>
                                    <table class="frm-manage-table widefat striped" style="border-top: none; margin-top: 0;">
                                        <thead style="background: <?php echo esc_attr($category_data['color']); ?>15;">
                                            <tr>
                                                <th style="width: 12%;">Role Key</th>
                                                <th style="width: 18%;">Display Name</th>
                                                <th style="width: 5%;">Users</th>
                                                <th style="width: 15%;">Category</th>
                                                <th style="width: 30%;">Linked Tags</th>
                                                <th style="width: 20%;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $protected = array('administrator','editor','author','subscriber');
                                            foreach ($category_roles as $role_key => $role_data):
                                                $user_count = count(FRM_Roles_Manager::get_users_with_role($role_key));
                                                $current_category = isset($assignments[$role_key]) ? $assignments[$role_key] : 'community';
                                                $current_tags = isset($role_tags[$role_key]) ? $role_tags[$role_key] : array();
                                            ?>
                                        <tr>
                                            <td><code><?php echo esc_html($role_key); ?></code></td>
                                            <td>
                                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="frm-rename-form">
                                                    <input type="hidden" name="action" value="frm_rename_role">
                                                    <input type="hidden" name="role_key" value="<?php echo esc_attr($role_key); ?>">
                                                    <?php wp_nonce_field('frm_rename_role'); ?>
                                                    <input type="text" name="role_name" value="<?php echo esc_attr($role_data['name']); ?>" />
                                                    <button class="button button-small" type="submit">Rename</button>
                                                </form>
                                            </td>
                                            <td><?php echo intval($user_count); ?></td>
                                            <td>
                                                <select name="category_assignments[<?php echo esc_attr($role_key); ?>]" class="frm-category-select">
                                                    <?php foreach ($categories as $cat_key => $cat_data): ?>
                                                        <option value="<?php echo esc_attr($cat_key); ?>" <?php selected($current_category, $cat_key); ?>>
                                                            <?php echo esc_html($cat_data['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td class="frm-tag-column">
                                                <?php if (function_exists('wp_fusion')): ?>
                                                    <select name="role_tags[<?php echo esc_attr($role_key); ?>][]"
                                                            multiple="multiple"
                                                            class="frm-tag-multiselect select4-wpf-tags"
                                                            data-placeholder="Select tags..."
                                                            style="width: 100%;">
                                                        <?php foreach ($wp_fusion_tags as $tag_id => $tag_label): ?>
                                                            <option value="<?php echo esc_attr($tag_id); ?>"
                                                                    <?php echo in_array($tag_id, $current_tags) ? 'selected' : ''; ?>>
                                                                <?php echo esc_html($tag_label); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                <?php else: ?>
                                                    <em style="color: #999;">WP Fusion not active</em>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!in_array($role_key, $protected, true)): ?>
                                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="frm-delete-form" onsubmit="return confirm('Delete role?');">
                                                        <input type="hidden" name="action" value="frm_delete_role">
                                                        <input type="hidden" name="role_key" value="<?php echo esc_attr($role_key); ?>">
                                                        <?php wp_nonce_field('frm_delete_role'); ?>
                                                        <?php if ($user_count > 0): ?>
                                                            <select name="reassign_to" required style="width: 120px;">
                                                                <option value="">Reassign to...</option>
                                                                <?php foreach ($roles as $rk => $rd): ?>
                                                                    <?php if ($rk !== $role_key): ?>
                                                                        <option value="<?php echo esc_attr($rk); ?>"><?php echo esc_html($rd['name']); ?></option>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        <?php endif; ?>
                                                        <button class="button button-link-delete" type="submit">Delete</button>
                                                    </form>
                                                <?php else: ?>
                                                    <em style="color: #999;">Protected</em>
                                                <?php endif; ?>
                                            </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endforeach; ?>

                            <p class="submit">
                                <button type="submit" class="button button-primary">Save All Changes</button>
                            </p>
                        </form>

                        <!-- Process Tags Form (separate from main form) -->
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top: 10px;">
                            <?php wp_nonce_field('frm_process_role_tags'); ?>
                            <input type="hidden" name="action" value="frm_process_role_tags" />
                            <button type="submit" class="button">Process Tags</button>
                            <span class="description" style="margin-left: 10px;">Processes all users: adds roles for users who have linked tags.</span>
                        </form>

                        <script type="text/javascript">
                            jQuery(document).ready(function($) {
                                // Initialize Select2/Select4 for tag multiselect
                                if (typeof $.fn.select4 !== 'undefined') {
                                    // Use WP Fusion's Select4
                                    $('.frm-tag-multiselect').select4({
                                        placeholder: 'Select tags...',
                                        allowClear: true,
                                        width: '100%',
                                        closeOnSelect: false
                                    });
                                } else if (typeof $.fn.select2 !== 'undefined') {
                                    // Fallback to Select2
                                    $('.frm-tag-multiselect').select2({
                                        placeholder: 'Select tags...',
                                        allowClear: true,
                                        width: '100%',
                                        closeOnSelect: false
                                    });
                                }

                                // Handle tag selection changes
                                $('.frm-tag-multiselect').on('change', function() {
                                    var $select = $(this);
                                    var roleKey = $select.data('role') || $select.attr('name').match(/role_tags\[([^\]]+)\]/)[1];
                                    var selectedTags = $select.val() || [];

                                    // Save tags via AJAX
                                    $.post(ajaxurl, {
                                        action: 'frm_save_role_tags',
                                        nonce: '<?php echo wp_create_nonce('frm_save_role_tags'); ?>',
                                        role: roleKey,
                                        tags: selectedTags
                                    }, function(response) {
                                        if (response.success) {
                                            // Show success indicator
                                            var $indicator = $('<span class="frm-save-indicator" style="color: green; margin-left: 10px;">✓ Saved</span>');
                                            $select.parent().append($indicator);
                                            setTimeout(function() {
                                                $indicator.fadeOut(function() {
                                                    $(this).remove();
                                                });
                                            }, 2000);

                                            // Ask if user wants to process users with these tags
                                            if (selectedTags.length > 0 && response.data && response.data.tag_names) {
                                                var tagNames = response.data.tag_names.join(', ');
                                                if (confirm('Tags saved for role "' + response.data.role_name + '".\n\nWould you like to process all users with these tags now?\nTags: ' + tagNames)) {
                                                    // Process users with tags
                                                    $.post(ajaxurl, {
                                                        action: 'frm_process_single_role_tags',
                                                        nonce: '<?php echo wp_create_nonce('frm_process_single_role_tags'); ?>',
                                                        role: roleKey
                                                    }, function(processResponse) {
                                                        if (processResponse.success) {
                                                            alert(processResponse.data.message);
                                                        } else {
                                                            alert('Error processing users: ' + (processResponse.data || 'Unknown error'));
                                                        }
                                                    });
                                                }
                                            }
                                        } else {
                                            alert('Error saving tags: ' + (response.data || 'Unknown error'));
                                        }
                                    });
                                });
                            });
                        </script>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    // Process tags: assign roles to users who have linked tags
    public function handle_process_role_tags() {
        if (!current_user_can('manage_options') || !check_admin_referer('frm_process_role_tags')) {
            wp_die('Permission denied');
        }
        if (!function_exists('wp_fusion')) {
            wp_redirect(add_query_arg(array('page' => 'fearless-roles-settings', 'tab' => 'manage', 'frm_error' => urlencode('WP Fusion is not active.')), admin_url('admin.php')));
            exit;
        }

        $role_tags = get_option('frm_role_wpfusion_tags', array());
        if (empty($role_tags) || !is_array($role_tags)) {
            wp_redirect(add_query_arg(array('page' => 'fearless-roles-settings', 'tab' => 'manage', 'frm_notice' => urlencode('No linked tags configured. Nothing to process.')), admin_url('admin.php')));
            exit;
        }

        // Fetch all user IDs in batches
        $paged = 1;
        $per_page = 500;
        $total_assigned = 0;

        do {
            $users = get_users(array(
                'fields' => array('ID'),
                'number' => $per_page,
                'paged' => $paged,
            ));

            if (empty($users)) {
                break;
            }

            foreach ($users as $u) {
                $user_id = is_object($u) ? $u->ID : intval($u);
                if (!$user_id) { continue; }

                // Get user's tags once
                $user_tags = array();
                if (method_exists(wp_fusion()->user, 'get_tags')) {
                    $user_tags = (array) wp_fusion()->user->get_tags($user_id);
                }

                foreach ($role_tags as $role_key => $tags) {
                    if (empty($tags) || !is_array($tags)) { continue; }
                    $should_assign = false;

                    if (!empty($user_tags)) {
                        // Compare arrays
                        foreach ($tags as $tag_id) {
                            if (in_array($tag_id, $user_tags)) { $should_assign = true; break; }
                        }
                    } else {
                        // Fallback per-tag check
                        foreach ($tags as $tag_id) {
                            if (method_exists(wp_fusion()->user, 'has_tag') && wp_fusion()->user->has_tag($tag_id, $user_id)) {
                                $should_assign = true; break;
                            }
                        }
                    }

                    if ($should_assign) {
                        $user = get_user_by('ID', $user_id);
                        if ($user && !in_array($role_key, (array) $user->roles, true)) {
                            $user->add_role($role_key);
                            $total_assigned++;
                        }
                    }
                }
            }

            $paged++;
        } while (count($users) === $per_page);

        wp_redirect(add_query_arg(array('page' => 'fearless-roles-settings', 'tab' => 'manage', 'frm_notice' => urlencode('Processed tags. Roles added: ' . $total_assigned)), admin_url('admin.php')));
        exit;
    }

    // Handle admin-post for saving category assignments and role tags (non-AJAX)
    public function handle_save_category_assignments() {
        if (!current_user_can('manage_options') || !check_admin_referer('frm_save_category_assignments')) {
            wp_die('Permission denied');
        }

        // Save category assignments
        $assignments = isset($_POST['category_assignments']) && is_array($_POST['category_assignments']) ? $_POST['category_assignments'] : array();

        // Sanitize categories
        $clean = array();
        foreach ($assignments as $role_key => $cat_key) {
            $clean[sanitize_key($role_key)] = sanitize_key($cat_key);
        }

        // Always save, even if empty
        FRM_Roles_Manager::save_role_category_assignments($clean);

        // Save WP Fusion tags if plugin is available
        if (function_exists('wp_fusion')) {
            $role_tags = isset($_POST['role_tags']) && is_array($_POST['role_tags']) ? $_POST['role_tags'] : array();

            // Process what was submitted - only roles with tags will be in the array
            $clean_tags = array();

            // Process submitted tags (only roles with selected tags will be here)
            foreach ($role_tags as $role_key => $tags) {
                $role_key = sanitize_key($role_key);
                if (is_array($tags)) {
                    // Filter out empty strings and sanitize
                    $filtered = array_filter($tags, function($tag) {
                        return !empty(trim($tag));
                    });
                    if (!empty($filtered)) {
                        $clean_tags[$role_key] = array_values(array_map('sanitize_text_field', $filtered));
                    }
                }
            }

            // Save the tags (only roles with tags will be saved)
            update_option('frm_role_wpfusion_tags', $clean_tags);
        }

        // Check which tab we came from and redirect accordingly
        $referrer = wp_get_referer();
        $tab = 'assignments'; // default tab
        if (strpos($referrer, 'tab=manage') !== false) {
            $tab = 'manage';
        }

        wp_redirect(add_query_arg(array('page' => 'fearless-roles-settings', 'tab' => $tab, 'frm_notice' => urlencode('Settings saved.')), admin_url('admin.php')));
        exit;
    }

    // Bulk save roles: category assignments and linked WP Fusion tags
    public function handle_bulk_save_roles() {
        if (!current_user_can('manage_options') || !check_admin_referer('frm_bulk_save_roles')) {
            wp_die('Permission denied');
        }
        // Save categories
        $assignments = isset($_POST['category_assignments']) && is_array($_POST['category_assignments']) ? $_POST['category_assignments'] : array();
        $clean_assignments = array();
        foreach ($assignments as $role_key => $cat_key) {
            $clean_assignments[sanitize_key($role_key)] = sanitize_key($cat_key);
        }
        FRM_Roles_Manager::save_role_category_assignments($clean_assignments);

        // Save WP Fusion tags if plugin is available
        if (function_exists('wp_fusion')) {
            $role_tags = isset($_POST['role_tags']) && is_array($_POST['role_tags']) ? $_POST['role_tags'] : array();
            $clean_tags = array();
            foreach ($role_tags as $role_key => $tags) {
                $role_key = sanitize_key($role_key);
                if (!is_array($tags)) { continue; }
                $clean_tags[$role_key] = array_values(array_unique(array_map('sanitize_text_field', $tags)));
            }
            update_option('frm_role_wpfusion_tags', $clean_tags);
        }

        wp_redirect(add_query_arg(array('page' => 'fearless-roles-settings', 'tab' => 'manage', 'frm_notice' => urlencode('Roles updated.')), admin_url('admin.php')));
        exit;
    }

    // Rename role display name
    public function handle_rename_role() {
        if (!current_user_can('manage_options') || !check_admin_referer('frm_rename_role')) {
            wp_die('Permission denied');
        }
        $role_key = sanitize_key($_POST['role_key'] ?? '');
        $role_name = sanitize_text_field($_POST['role_name'] ?? '');
        if (!$role_key || $role_name === '') {
            wp_redirect(add_query_arg(array('page' => 'fearless-roles-settings', 'tab' => 'manage', 'frm_error' => urlencode('Missing role or name')), admin_url('admin.php')));
            exit;
        }
        $wp_roles = wp_roles();
        if (!isset($wp_roles->roles[$role_key])) {
            wp_redirect(add_query_arg(array('page' => 'fearless-roles-settings', 'tab' => 'manage', 'frm_error' => urlencode('Role not found')), admin_url('admin.php')));
            exit;
        }
        // Update name
        $wp_roles->roles[$role_key]['name'] = $role_name;
        if (isset($wp_roles->role_names)) {
            $wp_roles->role_names[$role_key] = $role_name;
        }
        update_option('wp_user_roles', $wp_roles->roles);
        wp_redirect(add_query_arg(array('page' => 'fearless-roles-settings', 'tab' => 'manage', 'frm_notice' => urlencode('Role renamed.')), admin_url('admin.php')));
        exit;
    }

    // Delete non-core role and cleanup assignments
    public function handle_delete_role() {
        if (!current_user_can('manage_options') || !check_admin_referer('frm_delete_role')) {
            wp_die('Permission denied');
        }
        $role_key = sanitize_key($_POST['role_key'] ?? '');
        $protected = array('administrator','editor','author','subscriber');
        if (!$role_key || in_array($role_key, $protected, true)) {
            wp_redirect(add_query_arg(array('page' => 'fearless-roles-settings', 'tab' => 'manage', 'frm_error' => urlencode('Cannot delete protected role')), admin_url('admin.php')));
            exit;
        }
        $reassign_to = sanitize_key($_POST['reassign_to'] ?? '');
        $all_roles = wp_roles()->roles;
        $has_users = count(get_users(array('role' => $role_key, 'fields' => array('ID')))) > 0;
        if ($has_users && (!$reassign_to || !isset($all_roles[$reassign_to]) || $reassign_to === $role_key)) {
            wp_redirect(add_query_arg(array('page' => 'fearless-roles-settings', 'tab' => 'manage', 'frm_error' => urlencode('Please select a valid role to reassign users to.')), admin_url('admin.php')));
            exit;
        }
        if (!$reassign_to || !isset($all_roles[$reassign_to]) || $reassign_to === $role_key) {
            $reassign_to = 'subscriber';
        }
        // Reassign users to the selected role and ensure they have at least one role
        $users = get_users(array('role' => $role_key, 'fields' => array('ID')));
        foreach ($users as $user) {
            $u = get_user_by('ID', $user->ID);
            if (!$u) continue;
            $u->remove_role($role_key);
            // Add target role if not present
            if (!in_array($reassign_to, (array) $u->roles, true)) {
                $u->add_role($reassign_to);
            }
            // Safety: ensure at least one role
            if (empty($u->roles)) {
                $u->add_role('subscriber');
            }
        }
        // Remove role
        remove_role($role_key);
        // Cleanup category assignment
        $assignments = get_option('frm_role_category_assignments', array());
        if (isset($assignments[$role_key])) {
            unset($assignments[$role_key]);
            update_option('frm_role_category_assignments', $assignments);
        }
        wp_redirect(add_query_arg(array('page' => 'fearless-roles-settings', 'tab' => 'manage', 'frm_notice' => urlencode('Role deleted.')), admin_url('admin.php')));
        exit;
    }
    
    public function render_users_page() {
        $users_by_role = FRM_Roles_Manager::get_all_users_by_role();
        ?>
        <div class="wrap frm-users-wrap">
            <h1><span class="dashicons dashicons-groups"></span> User Management</h1>
            
            <div class="frm-users-container">
                <?php foreach ($users_by_role as $role_key => $role_data): ?>
                    <div class="frm-role-section">
                        <div class="frm-role-header">
                            <h2><?php echo esc_html($role_data['name']); ?></h2>
                            <span class="frm-role-key"><?php echo esc_html($role_key); ?></span>
                            <span class="frm-user-count"><?php echo $role_data['count']; ?> user<?php echo $role_data['count'] !== 1 ? 's' : ''; ?></span>
                        </div>
                        
                        <?php if (empty($role_data['users'])): ?>
                            <div class="frm-no-users">
                                <p>No users assigned to this role.</p>
                            </div>
                        <?php else: ?>
                            <div class="frm-users-table-container">
                                <table class="frm-users-table">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Email</th>
                                            <th>Registered</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($role_data['users'] as $user): ?>
                                            <tr>
                                                <td>
                                                    <div class="frm-user-info">
                                                        <strong><?php echo esc_html($user->display_name); ?></strong>
                                                        <span class="frm-username">@<?php echo esc_html($user->user_login); ?></span>
                                                    </div>
                                                </td>
                                                <td><?php echo esc_html($user->user_email); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($user->user_registered)); ?></td>
                                                <td>
                                                    <a href="<?php echo admin_url('user-edit.php?user_id=' . $user->ID); ?>" 
                                                       class="button button-small">
                                                        <span class="dashicons dashicons-edit"></span> Edit
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}