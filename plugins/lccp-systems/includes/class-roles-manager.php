<?php
/**
 * Roles Manager Module for LCCP Systems
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Roles_Manager {
    
    private $custom_roles = array(
        'lccp_mentor' => 'LCCP Mentor',
        'lccp_big_bird' => 'LCCP BigBird',
        'lccp_pc' => 'LCCP Program Candidate'
    );
    
    public function __construct() {
        // Setup roles on activation
        add_action('lccp_systems_activate', array($this, 'setup_roles'));
        
        // Role management hooks
        add_filter('editable_roles', array($this, 'make_roles_editable'));
        add_action('admin_init', array($this, 'add_capabilities'));

        // Admin UI for managing roles & capabilities
        add_action('admin_menu', array($this, 'add_roles_admin_page'));
        add_action('admin_post_lccp_add_role', array($this, 'handle_add_role'));
        add_action('admin_post_lccp_delete_role', array($this, 'handle_delete_role'));
        add_action('admin_post_lccp_save_capabilities', array($this, 'handle_save_capabilities'));
        add_action('admin_post_lccp_migrate_legacy_roles', array($this, 'handle_migrate_legacy_roles'));
        // Role categories CRUD
        add_action('admin_post_lccp_add_role_category', array($this, 'handle_add_role_category'));
        add_action('admin_post_lccp_delete_role_category', array($this, 'handle_delete_role_category'));
        add_action('admin_post_lccp_assign_role_categories', array($this, 'handle_assign_role_categories'));
    }
    
    public function setup_roles() {
        // Add Mentor role
        add_role('lccp_mentor', 'LCCP Mentor', array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'upload_files' => true,
            'view_admin_dashboard' => true,
            'lccp_view_students' => true,
            'lccp_track_hours' => true,
            'lccp_view_reports' => true
        ));
        
        // Add BigBird role
        add_role('lccp_big_bird', 'LCCP BigBird', array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'upload_files' => true,
            'view_admin_dashboard' => true,
            'lccp_view_students' => true,
            'lccp_manage_students' => true,
            'lccp_track_hours' => true,
            'lccp_view_reports' => true
        ));
        
        // Add Program Candidate role
        add_role('lccp_pc', 'LCCP Program Candidate', array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'upload_files' => true,
            'view_admin_dashboard' => true,
            'lccp_view_all_students' => true,
            'lccp_manage_all_students' => true,
            'lccp_track_hours' => true,
            'lccp_view_all_reports' => true,
            'lccp_manage_mentors' => true
        ));
        
        // Add capabilities to administrators
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('lccp_manage_all');
            $admin_role->add_cap('lccp_view_all_students');
            $admin_role->add_cap('lccp_manage_all_students');
            $admin_role->add_cap('lccp_view_all_reports');
            $admin_role->add_cap('lccp_manage_mentors');
        }
    }
    
    public function make_roles_editable($roles) {
        // Ensure custom roles are editable
        foreach ($this->custom_roles as $role_slug => $role_name) {
            if (!isset($roles[$role_slug]) && get_role($role_slug)) {
                $roles[$role_slug] = get_role($role_slug);
            }
        }
        return $roles;
    }
    
    public function add_capabilities() {
        // Ensure all custom capabilities exist
        $custom_caps = array(
            'lccp_view_students',
            'lccp_manage_students',
            'lccp_track_hours',
            'lccp_view_reports',
            'lccp_view_all_students',
            'lccp_manage_all_students',
            'lccp_view_all_reports',
            'lccp_manage_mentors',
            'lccp_manage_all'
        );
        
        // Add to administrator role if missing
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($custom_caps as $cap) {
                if (!$admin_role->has_cap($cap)) {
                    $admin_role->add_cap($cap);
                }
            }
        }
    }

    /**
     * Add Roles & Capabilities admin page
     */
    public function add_roles_admin_page() {
        add_submenu_page(
            'lccp-systems',
            __('Roles & Capabilities', 'lccp-systems'),
            __('Roles & Capabilities', 'lccp-systems'),
            'manage_options',
            'lccp-roles',
            array($this, 'render_roles_page')
        );
    }

    /**
     * Render the Roles & Capabilities admin UI
     */
    public function render_roles_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'lccp-systems'));
        }

        $wp_roles = wp_roles();
        $roles = $wp_roles->roles;
        $selected_role = isset($_GET['role']) ? sanitize_key($_GET['role']) : '';
        $capabilities = $this->get_all_known_capabilities();
        $nonce_add = wp_create_nonce('lccp_add_role');
        $nonce_delete = wp_create_nonce('lccp_delete_role');
        $nonce_save_caps = wp_create_nonce('lccp_save_capabilities');
        $nonce_migrate = wp_create_nonce('lccp_migrate_legacy_roles');
        // Categories
        $role_categories = $this->get_role_categories();
        $role_category_map = $this->get_role_category_map();
        $nonce_add_cat = wp_create_nonce('lccp_add_role_category');
        $nonce_delete_cat = wp_create_nonce('lccp_delete_role_category');
        $nonce_assign_cat = wp_create_nonce('lccp_assign_role_categories');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('LCCP Roles & Capabilities', 'lccp-systems'); ?></h1>

            <div class="lccp-roles-grid" style="display:grid;grid-template-columns:1fr 2fr;gap:20px;">
                <div class="lccp-roles-sidebar" style="background:#fff;border:1px solid #ccd0d4;padding:15px;">
                    <h2 style="margin-top:0;"><?php esc_html_e('Roles', 'lccp-systems'); ?></h2>
                    <ul>
                        <?php foreach ($roles as $slug => $role): ?>
                            <li>
                                <a href="<?php echo esc_url(add_query_arg('role', $slug)); ?>">
                                    <?php echo esc_html($role['name']); ?>
                                </a>
                                <?php if (strpos($slug, 'lccp_') === 0): ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;" onsubmit="return confirm('Delete role & remove from users?');">
                                        <input type="hidden" name="action" value="lccp_delete_role">
                                        <input type="hidden" name="role_slug" value="<?php echo esc_attr($slug); ?>">
                                        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_delete); ?>">
                                        <button class="button-link-delete" type="submit" title="<?php esc_attr_e('Delete role', 'lccp-systems'); ?>">&times;</button>
                                    </form>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <hr>
                    <h3><?php esc_html_e('Add New Role', 'lccp-systems'); ?></h3>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="lccp_add_role">
                        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_add); ?>">
                        <p>
                            <label><?php esc_html_e('Role Slug', 'lccp-systems'); ?></label><br>
                            <input type="text" name="role_slug" value="lccp_" class="regular-text" required>
                        </p>
                        <p>
                            <label><?php esc_html_e('Display Name', 'lccp-systems'); ?></label><br>
                            <input type="text" name="role_name" class="regular-text" required>
                        </p>
                        <p>
                            <label><?php esc_html_e('Copy capabilities from', 'lccp-systems'); ?></label><br>
                            <select name="copy_from" class="regular-text">
                                <option value="">— <?php esc_html_e('None', 'lccp-systems'); ?> —</option>
                                <?php foreach ($roles as $slug => $role): ?>
                                    <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($role['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                        <p>
                            <button type="submit" class="button button-primary"><?php esc_html_e('Add Role', 'lccp-systems'); ?></button>
                        </p>
                    </form>

                    <hr>
                    <h3><?php esc_html_e('Role Categories', 'lccp-systems'); ?></h3>
                    <?php if (!empty($role_categories)): ?>
                        <ul>
                            <?php foreach ($role_categories as $cat_slug => $cat_name):
                                $count = 0;
                                foreach ($role_category_map as $rslug => $cats) { if (is_array($cats) && in_array($cat_slug, $cats, true)) { $count++; } }
                            ?>
                                <li>
                                    <strong><?php echo esc_html($cat_name); ?></strong>
                                    <span style="color:#666;">(<?php echo intval($count); ?>)</span>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;" onsubmit="return confirm('Delete this category? Roles will be unassigned from it.');">
                                        <input type="hidden" name="action" value="lccp_delete_role_category">
                                        <input type="hidden" name="category_slug" value="<?php echo esc_attr($cat_slug); ?>">
                                        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_delete_cat); ?>">
                                        <button class="button-link-delete" type="submit" title="<?php esc_attr_e('Delete category', 'lccp-systems'); ?>">&times;</button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p><?php esc_html_e('No categories yet.', 'lccp-systems'); ?></p>
                    <?php endif; ?>

                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="lccp_add_role_category">
                        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_add_cat); ?>">
                        <p>
                            <label><?php esc_html_e('Category Slug', 'lccp-systems'); ?></label><br>
                            <input type="text" name="category_slug" class="regular-text" placeholder="team_leads" required>
                        </p>
                        <p>
                            <label><?php esc_html_e('Category Name', 'lccp-systems'); ?></label><br>
                            <input type="text" name="category_name" class="regular-text" placeholder="Team Leads" required>
                        </p>
                        <p><button type="submit" class="button"><?php esc_html_e('Add Category', 'lccp-systems'); ?></button></p>
                    </form>

                    <hr>
                    <h3><?php esc_html_e('Migrate Legacy Roles', 'lccp-systems'); ?></h3>
                    <p><?php esc_html_e('Convert legacy roles (dasher_*, bigbird) to LCCP roles.', 'lccp-systems'); ?></p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('Run role migration now?');">
                        <input type="hidden" name="action" value="lccp_migrate_legacy_roles">
                        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_migrate); ?>">
                        <button type="submit" class="button"><?php esc_html_e('Run Migration', 'lccp-systems'); ?></button>
                    </form>
                </div>

                <div class="lccp-roles-main" style="background:#fff;border:1px solid #ccd0d4;padding:15px;">
                    <h2 style="margin-top:0;">
                        <?php echo $selected_role && isset($roles[$selected_role]) ? esc_html($roles[$selected_role]['name']) : esc_html__('Select a role', 'lccp-systems'); ?>
                    </h2>

                    <?php if ($selected_role && isset($roles[$selected_role])): ?>
                        <?php $role_obj = get_role($selected_role); ?>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="lccp_save_capabilities">
                            <input type="hidden" name="role_slug" value="<?php echo esc_attr($selected_role); ?>">
                            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_save_caps); ?>">
                            <div class="cap-grid" style="columns:3 300px;-webkit-columns:3 300px;column-gap:20px;">
                                <?php foreach ($capabilities as $cap): ?>
                                    <label style="display:block;padding:4px 0;">
                                        <input type="checkbox" name="caps[]" value="<?php echo esc_attr($cap); ?>" <?php checked($role_obj && $role_obj->has_cap($cap)); ?>>
                                        <span style="<?php echo strpos($cap, 'lccp_') === 0 ? 'font-weight:600;' : ''; ?>"><?php echo esc_html($cap); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <p>
                                <button type="submit" class="button button-primary"><?php esc_html_e('Save Capabilities', 'lccp-systems'); ?></button>
                            </p>
                        </form>

                        <hr>
                        <h3><?php esc_html_e('Categories for this Role', 'lccp-systems'); ?></h3>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="lccp_assign_role_categories">
                            <input type="hidden" name="role_slug" value="<?php echo esc_attr($selected_role); ?>">
                            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_assign_cat); ?>">
                            <div style="columns:2 300px; -webkit-columns:2 300px; column-gap:20px;">
                                <?php if (!empty($role_categories)):
                                    $assigned = isset($role_category_map[$selected_role]) && is_array($role_category_map[$selected_role]) ? $role_category_map[$selected_role] : array();
                                    foreach ($role_categories as $cat_slug => $cat_name): ?>
                                        <label style="display:block;padding:4px 0;">
                                            <input type="checkbox" name="categories[]" value="<?php echo esc_attr($cat_slug); ?>" <?php checked(in_array($cat_slug, $assigned, true)); ?>>
                                            <?php echo esc_html($cat_name); ?>
                                        </label>
                                <?php endforeach; else: ?>
                                    <p><?php esc_html_e('No categories defined yet.', 'lccp-systems'); ?></p>
                                <?php endif; ?>
                            </div>
                            <p>
                                <button type="submit" class="button button-secondary"><?php esc_html_e('Save Categories', 'lccp-systems'); ?></button>
                            </p>
                        </form>
                    <?php else: ?>
                        <p><?php esc_html_e('Choose a role from the list to edit its capabilities.', 'lccp-systems'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Gather a comprehensive list of known capabilities
     */
    private function get_all_known_capabilities() {
        $caps = array();
        $wp_roles = wp_roles();
        foreach ($wp_roles->roles as $role) {
            if (!empty($role['capabilities']) && is_array($role['capabilities'])) {
                $caps = array_merge($caps, array_keys($role['capabilities']));
            }
        }
        // Ensure common caps exist
        $baseline = array('read', 'edit_posts', 'delete_posts', 'upload_files', 'list_users', 'promote_users', 'create_users', 'delete_users', 'edit_users', 'manage_options', 'view_admin_dashboard');
        $lccp_caps = array('lccp_view_students','lccp_manage_students','lccp_track_hours','lccp_view_reports','lccp_view_all_students','lccp_manage_all_students','lccp_view_all_reports','lccp_manage_mentors','lccp_manage_all');
        $caps = array_unique(array_merge($baseline, $lccp_caps, $caps));
        natcasesort($caps);
        // Sort: lccp_ first
        usort($caps, function($a, $b){
            $pa = strpos($a, 'lccp_') === 0 ? 0 : 1;
            $pb = strpos($b, 'lccp_') === 0 ? 0 : 1;
            if ($pa === $pb) return strnatcasecmp($a, $b);
            return $pa - $pb;
        });
        return $caps;
    }

    /**
     * Handle add role action
     */
    public function handle_add_role() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['_wpnonce'] ?? '', 'lccp_add_role')) {
            wp_die(__('Permission denied', 'lccp-systems'));
        }
        $slug = sanitize_key($_POST['role_slug'] ?? '');
        $name = sanitize_text_field($_POST['role_name'] ?? '');
        $copy_from = sanitize_key($_POST['copy_from'] ?? '');
        if (empty($slug) || empty($name)) {
            wp_redirect(add_query_arg('error', urlencode('Missing role slug or name'), wp_get_referer()));
            exit;
        }
        if (get_role($slug)) {
            wp_redirect(add_query_arg('error', urlencode('Role already exists'), wp_get_referer()));
            exit;
        }
        $caps = array('read' => true);
        if ($copy_from && ($src = get_role($copy_from))) {
            $caps = $src->capabilities;
        }
        add_role($slug, $name, $caps);
        wp_redirect(remove_query_arg('error', add_query_arg('role', $slug, wp_get_referer())));
        exit;
    }

    /**
     * Handle delete role action
     */
    public function handle_delete_role() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['_wpnonce'] ?? '', 'lccp_delete_role')) {
            wp_die(__('Permission denied', 'lccp-systems'));
        }
        $slug = sanitize_key($_POST['role_slug'] ?? '');
        if (!$slug || strpos($slug, 'lccp_') !== 0) {
            wp_die(__('Only LCCP roles can be removed here.', 'lccp-systems'));
        }
        // Remove role from users
        $users = get_users(array('role' => $slug, 'fields' => array('ID')));
        foreach ($users as $user) {
            $u = get_user_by('ID', $user->ID);
            if ($u) {
                $u->remove_role($slug);
            }
        }
        remove_role($slug);
        wp_redirect(remove_query_arg(array('role','error'), wp_get_referer()));
        exit;
    }

    /**
     * Handle save capabilities action
     */
    public function handle_save_capabilities() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['_wpnonce'] ?? '', 'lccp_save_capabilities')) {
            wp_die(__('Permission denied', 'lccp-systems'));
        }
        $slug = sanitize_key($_POST['role_slug'] ?? '');
        $set_caps = isset($_POST['caps']) && is_array($_POST['caps']) ? array_map('sanitize_text_field', $_POST['caps']) : array();
        $role = get_role($slug);
        if (!$role) {
            wp_redirect(add_query_arg('error', urlencode('Role not found'), wp_get_referer()));
            exit;
        }
        $all_caps = $this->get_all_known_capabilities();
        foreach ($all_caps as $cap) {
            $has = $role->has_cap($cap);
            $want = in_array($cap, $set_caps, true);
            if ($want && !$has) {
                $role->add_cap($cap);
            } elseif (!$want && $has) {
                $role->remove_cap($cap);
            }
        }
        wp_redirect(add_query_arg('role', $slug, wp_get_referer()));
        exit;
    }

    /**
     * Migrate legacy roles (dasher_* and bigbird) to LCCP roles
     */
    public function handle_migrate_legacy_roles() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['_wpnonce'] ?? '', 'lccp_migrate_legacy_roles')) {
            wp_die(__('Permission denied', 'lccp-systems'));
        }
        $map = array(
            'dasher_mentor' => 'lccp_mentor',
            'dasher_bigbird' => 'lccp_bigbird',
            'dasher_pc' => 'lccp_pc',
            'bigbird' => 'lccp_bigbird'
        );
        foreach ($map as $from => $to) {
            // Ensure target role exists
            if (!get_role($to)) {
                add_role($to, ucwords(str_replace(array('lccp_', '_'), array('', ' '), $to)), array('read' => true));
            }
            $users = get_users(array('role' => $from, 'fields' => array('ID')));
            foreach ($users as $user) {
                $u = get_user_by('ID', $user->ID);
                if ($u) {
                    $u->add_role($to);
                    $u->remove_role($from);
                }
            }
        }
        wp_redirect(remove_query_arg('error', wp_get_referer()));
        exit;
    }

    /**
     * Role categories: storage helpers
     */
    private function get_role_categories() {
        $cats = get_option('lccp_role_categories', array());
        if (!is_array($cats)) { $cats = array(); }
        return $cats;
    }
    private function save_role_categories($cats) {
        return update_option('lccp_role_categories', $cats);
    }
    private function get_role_category_map() {
        $map = get_option('lccp_role_category_map', array());
        if (!is_array($map)) { $map = array(); }
        return $map;
    }
    private function save_role_category_map($map) {
        return update_option('lccp_role_category_map', $map);
    }

    /**
     * Handle: add role category
     */
    public function handle_add_role_category() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['_wpnonce'] ?? '', 'lccp_add_role_category')) {
            wp_die(__('Permission denied', 'lccp-systems'));
        }
        $slug = sanitize_key($_POST['category_slug'] ?? '');
        $name = sanitize_text_field($_POST['category_name'] ?? '');
        if (empty($slug) || empty($name)) {
            wp_redirect(add_query_arg('error', urlencode('Missing category slug or name'), wp_get_referer()));
            exit;
        }
        $cats = $this->get_role_categories();
        if (isset($cats[$slug])) {
            wp_redirect(add_query_arg('error', urlencode('Category already exists'), wp_get_referer()));
            exit;
        }
        $cats[$slug] = $name;
        $this->save_role_categories($cats);
        wp_redirect(remove_query_arg('error', wp_get_referer()));
        exit;
    }

    /**
     * Handle: delete role category
     */
    public function handle_delete_role_category() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['_wpnonce'] ?? '', 'lccp_delete_role_category')) {
            wp_die(__('Permission denied', 'lccp-systems'));
        }
        $slug = sanitize_key($_POST['category_slug'] ?? '');
        if (empty($slug)) {
            wp_redirect(add_query_arg('error', urlencode('Missing category slug'), wp_get_referer()));
            exit;
        }
        $cats = $this->get_role_categories();
        if (isset($cats[$slug])) {
            unset($cats[$slug]);
            $this->save_role_categories($cats);
        }
        // Remove from map
        $map = $this->get_role_category_map();
        foreach ($map as $role_slug => $assigned) {
            if (is_array($assigned)) {
                $map[$role_slug] = array_values(array_diff($assigned, array($slug)));
            }
        }
        $this->save_role_category_map($map);
        wp_redirect(remove_query_arg('error', wp_get_referer()));
        exit;
    }

    /**
     * Handle: assign categories to a role
     */
    public function handle_assign_role_categories() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['_wpnonce'] ?? '', 'lccp_assign_role_categories')) {
            wp_die(__('Permission denied', 'lccp-systems'));
        }
        $role_slug = sanitize_key($_POST['role_slug'] ?? '');
        $categories = isset($_POST['categories']) && is_array($_POST['categories']) ? array_map('sanitize_key', $_POST['categories']) : array();
        if (empty($role_slug)) {
            wp_redirect(add_query_arg('error', urlencode('Missing role'), wp_get_referer()));
            exit;
        }
        $map = $this->get_role_category_map();
        $map[$role_slug] = $categories;
        $this->save_role_category_map($map);
        wp_redirect(add_query_arg('role', $role_slug, wp_get_referer()));
        exit;
    }
    
    public function remove_roles() {
        // Remove custom roles on deactivation
        foreach ($this->custom_roles as $role_slug => $role_name) {
            remove_role($role_slug);
        }
    }
    
    public function get_user_role_display($user_id) {
        $user = get_user_by('ID', $user_id);
        if (!$user) return '';
        
        foreach ($this->custom_roles as $role_slug => $role_name) {
            if (in_array($role_slug, $user->roles)) {
                return $role_name;
            }
        }
        
        return '';
    }
    
    public function user_has_lccp_role($user_id) {
        $user = get_user_by('ID', $user_id);
        if (!$user) return false;
        
        foreach ($this->custom_roles as $role_slug => $role_name) {
            if (in_array($role_slug, $user->roles)) {
                return true;
            }
        }

        return false;
    }
}

// Initialize roles manager when this module is loaded
new LCCP_Roles_Manager();