<?php
/**
 * FYM Settings - Admin page for Fearless You Membership Settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class FYM_Settings {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function add_admin_menu() {
        add_menu_page(
            __('FYM Settings', 'fearless-you-systems'),
            __('FYM Settings', 'fearless-you-systems'),
            'manage_options',
            'fym-settings',
            array($this, 'settings_page'),
            'dashicons-groups',
            30
        );

        // Add submenu pages
        add_submenu_page(
            'fym-settings',
            __('Member Management', 'fearless-you-systems'),
            __('Members', 'fearless-you-systems'),
            'manage_options',
            'fym-members',
            array($this, 'members_page')
        );

        add_submenu_page(
            'fym-settings',
            __('Faculty Management', 'fearless-you-systems'),
            __('Faculty', 'fearless-you-systems'),
            'manage_options',
            'fym-faculty',
            array($this, 'faculty_page')
        );

        add_submenu_page(
            'fym-settings',
            __('Ambassador Management', 'fearless-you-systems'),
            __('Ambassadors', 'fearless-you-systems'),
            'manage_options',
            'fym-ambassadors',
            array($this, 'ambassadors_page')
        );
    }

    public function settings_init() {
        register_setting('fym_settings', 'fym_options');

        // General Settings Section
        add_settings_section(
            'fym_general_section',
            __('General Settings', 'fearless-you-systems'),
            array($this, 'general_section_callback'),
            'fym-settings'
        );

        add_settings_field(
            'enable_member_dashboards',
            __('Enable Member Dashboards', 'fearless-you-systems'),
            array($this, 'checkbox_field_callback'),
            'fym-settings',
            'fym_general_section',
            array('field' => 'enable_member_dashboards')
        );

        add_settings_field(
            'member_welcome_message',
            __('Member Welcome Message', 'fearless-you-systems'),
            array($this, 'textarea_field_callback'),
            'fym-settings',
            'fym_general_section',
            array('field' => 'member_welcome_message')
        );

        add_settings_field(
            'faculty_permissions',
            __('Faculty Permissions', 'fearless-you-systems'),
            array($this, 'permissions_field_callback'),
            'fym-settings',
            'fym_general_section',
            array('field' => 'faculty_permissions')
        );

        // WP Fusion Integration Section
        if (function_exists('wp_fusion')) {
            add_settings_section(
                'fym_wpfusion_section',
                __('WP Fusion/Keap Integration', 'fearless-you-systems'),
                array($this, 'wpfusion_section_callback'),
                'fym-settings'
            );

            add_settings_field(
                'wpfusion_active_tags',
                __('Active Membership Tags', 'fearless-you-systems'),
                array($this, 'wpfusion_tags_field_callback'),
                'fym-settings',
                'fym_wpfusion_section',
                array('field' => 'wpfusion_active_tags', 'description' => 'Tags that indicate an active membership in Keap')
            );

            add_settings_field(
                'wpfusion_paused_tags',
                __('Paused/On-Hold Tags', 'fearless-you-systems'),
                array($this, 'wpfusion_tags_field_callback'),
                'fym-settings',
                'fym_wpfusion_section',
                array('field' => 'wpfusion_paused_tags', 'description' => 'Tags that indicate a paused or on-hold membership')
            );

            add_settings_field(
                'wpfusion_canceled_tags',
                __('Canceled Membership Tags', 'fearless-you-systems'),
                array($this, 'wpfusion_tags_field_callback'),
                'fym-settings',
                'fym_wpfusion_section',
                array('field' => 'wpfusion_canceled_tags', 'description' => 'Tags that indicate a canceled membership')
            );

            add_settings_field(
                'wpfusion_auto_sync',
                __('Auto Sync with Keap', 'fearless-you-systems'),
                array($this, 'checkbox_field_callback'),
                'fym-settings',
                'fym_wpfusion_section',
                array('field' => 'wpfusion_auto_sync', 'description' => 'Automatically sync membership data with Keap daily')
            );
        }
    }

    public function general_section_callback() {
        echo '<p>' . __('Configure Fearless You membership settings.', 'fearless-you-systems') . '</p>';
    }

    public function wpfusion_section_callback() {
        echo '<p>' . __('Configure WP Fusion tags for tracking membership status in Keap.', 'fearless-you-systems') . '</p>';

        if (function_exists('wp_fusion')) {
            $connection = wp_fusion()->crm->connection;
            if ($connection) {
                echo '<div class="notice notice-success inline"><p>' . __('✓ Connected to Keap', 'fearless-you-systems') . '</p></div>';
            } else {
                echo '<div class="notice notice-warning inline"><p>' . __('⚠ WP Fusion is not connected to Keap. Please configure WP Fusion settings first.', 'fearless-you-systems') . '</p></div>';
            }
        }
    }

    public function checkbox_field_callback($args) {
        $options = get_option('fym_options');
        $value = isset($options[$args['field']]) ? $options[$args['field']] : 0;
        ?>
        <input type="checkbox"
               name="fym_options[<?php echo esc_attr($args['field']); ?>]"
               value="1"
               <?php checked($value, 1); ?> />
        <?php if (isset($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
    }

    public function textarea_field_callback($args) {
        $options = get_option('fym_options');
        $value = isset($options[$args['field']]) ? $options[$args['field']] : '';
        ?>
        <textarea name="fym_options[<?php echo esc_attr($args['field']); ?>]"
                  rows="5"
                  cols="50"><?php echo esc_textarea($value); ?></textarea>
        <?php
    }

    public function permissions_field_callback($args) {
        $options = get_option('fym_options');
        $permissions = array(
            'create_courses' => __('Create Courses', 'fearless-you-systems'),
            'moderate_forums' => __('Moderate Forums', 'fearless-you-systems'),
            'view_all_members' => __('View All Members', 'fearless-you-systems'),
            'send_announcements' => __('Send Announcements', 'fearless-you-systems')
        );
        ?>
        <div class="fym-permissions">
            <?php foreach ($permissions as $perm_key => $perm_label):
                $field_name = $args['field'] . '[' . $perm_key . ']';
                $checked = isset($options[$args['field']][$perm_key]) ? $options[$args['field']][$perm_key] : 0;
            ?>
                <label>
                    <input type="checkbox"
                           name="fym_options[<?php echo esc_attr($field_name); ?>]"
                           value="1"
                           <?php checked($checked, 1); ?> />
                    <?php echo esc_html($perm_label); ?>
                </label><br>
            <?php endforeach; ?>
        </div>
        <?php
    }

    public function wpfusion_tags_field_callback($args) {
        $options = get_option('fym_options');
        $saved_tags = isset($options[$args['field']]) ? $options[$args['field']] : array();

        // Get available tags from WP Fusion and process them
        $available_tags = array();
        if (function_exists('wp_fusion')) {
            $raw_tags = wp_fusion()->settings->get('available_tags', array());

            // Process tags to get proper labels with categories
            foreach ($raw_tags as $tag_id => $tag_data) {
                if (is_array($tag_data)) {
                    if (isset($tag_data['label'])) {
                        $label = $tag_data['label'];
                        // Add category if available
                        if (isset($tag_data['category']) && !empty($tag_data['category'])) {
                            $label .= ' [' . $tag_data['category'] . ']';
                        }
                        $available_tags[$tag_id] = $label;
                    } elseif (isset($tag_data[0]) && !is_array($tag_data[0])) {
                        $available_tags[$tag_id] = $tag_data[0];
                    } else {
                        $available_tags[$tag_id] = 'Tag #' . $tag_id;
                    }
                } else {
                    $available_tags[$tag_id] = $tag_data;
                }
            }
        }

        // Get saved tag names for display
        $saved_tag_names = array();
        foreach ($saved_tags as $tag_id) {
            if (isset($available_tags[$tag_id])) {
                $saved_tag_names[$tag_id] = $available_tags[$tag_id];
            }
        }

        ?>
        <div class="fym-wpfusion-tags" data-field="<?php echo esc_attr($args['field']); ?>">
            <?php if (!empty($available_tags)): ?>
                <div class="fym-tag-input-wrapper">
                    <input type="text"
                           class="fym-tag-search"
                           placeholder="<?php _e('Type to search for tags...', 'fearless-you-systems'); ?>"
                           style="width: 100%; max-width: 500px; padding: 8px;">

                    <div class="fym-tag-suggestions" style="display: none;">
                        <!-- Suggestions will be populated by JavaScript -->
                    </div>

                    <div class="fym-selected-tags">
                        <h4><?php _e('Selected Tags:', 'fearless-you-systems'); ?></h4>
                        <div class="fym-tags-list">
                            <?php foreach ($saved_tag_names as $tag_id => $tag_name): ?>
                                <span class="fym-tag-item" data-tag-id="<?php echo esc_attr($tag_id); ?>">
                                    <?php echo esc_html($tag_name); ?>
                                    <button type="button" class="fym-remove-tag" aria-label="<?php _e('Remove tag', 'fearless-you-systems'); ?>">×</button>
                                    <input type="hidden"
                                           name="fym_options[<?php echo esc_attr($args['field']); ?>][]"
                                           value="<?php echo esc_attr($tag_id); ?>">
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <?php if (empty($saved_tag_names)): ?>
                            <p class="fym-no-tags"><?php _e('No tags selected yet.', 'fearless-you-systems'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <p class="description">
                    <?php echo esc_html($args['description']); ?><br>
                    <small><?php _e('Start typing to search for tags. Click on a tag to add it.', 'fearless-you-systems'); ?></small>
                </p>

                <script type="text/javascript">
                    // Store available tags for this field
                    if (!window.fymAvailableTags) window.fymAvailableTags = {};
                    window.fymAvailableTags['<?php echo esc_js($args['field']); ?>'] = <?php echo json_encode($available_tags); ?>;
                </script>

            <?php else: ?>
                <p class="notice notice-warning" style="padding: 10px;">
                    <?php _e('No tags available. Please sync WP Fusion with Keap first.', 'fearless-you-systems'); ?>
                    <a href="<?php echo admin_url('admin.php?page=wpf-settings'); ?>" class="button button-small" style="margin-left: 10px;">
                        <?php _e('Go to WP Fusion Settings', 'fearless-you-systems'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>

        <style>
            .fym-tag-input-wrapper {
                position: relative;
                margin-bottom: 15px;
            }

            .fym-tag-suggestions {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                max-width: 500px;
                max-height: 200px;
                overflow-y: auto;
                background: white;
                border: 1px solid #ddd;
                border-top: none;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                z-index: 1000;
            }

            .fym-tag-suggestion {
                padding: 8px 12px;
                cursor: pointer;
                border-bottom: 1px solid #f0f0f0;
            }

            .fym-tag-suggestion:hover {
                background: #f0f0f0;
            }

            .fym-tag-suggestion.selected {
                background: #0073aa;
                color: white;
            }

            .fym-tag-suggestion-id {
                font-size: 11px;
                opacity: 0.7;
                margin-left: 10px;
            }

            .fym-selected-tags {
                margin-top: 15px;
                padding: 10px;
                background: #f9f9f9;
                border: 1px solid #e0e0e0;
                border-radius: 3px;
                max-width: 500px;
            }

            .fym-selected-tags h4 {
                margin: 0 0 10px 0;
                font-size: 13px;
                font-weight: 600;
            }

            .fym-tags-list {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }

            .fym-tag-item {
                display: inline-flex;
                align-items: center;
                background: #0073aa;
                color: white;
                padding: 4px 8px;
                border-radius: 3px;
                font-size: 13px;
            }

            .fym-remove-tag {
                background: none;
                border: none;
                color: white;
                font-size: 18px;
                line-height: 1;
                margin-left: 5px;
                cursor: pointer;
                padding: 0;
                width: 16px;
                height: 16px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .fym-remove-tag:hover {
                opacity: 0.8;
            }

            .fym-no-tags {
                color: #666;
                font-style: italic;
                margin: 0;
            }
        </style>
        <?php
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="fym-dashboard">
                <div class="fym-stats">
                    <h2><?php _e('Membership Statistics', 'fearless-you-systems'); ?></h2>
                    <?php $this->display_membership_stats(); ?>
                </div>

                <form action="options.php" method="post">
                    <?php
                    settings_fields('fym_settings');
                    do_settings_sections('fym-settings');
                    submit_button();
                    ?>
                </form>

                <div class="fym-role-management">
                    <h2><?php _e('Role Management', 'fearless-you-systems'); ?></h2>
                    <?php $this->display_role_management(); ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function display_membership_stats() {
        $roles = array(
            'fearless_you_member' => __('Fearless You Members', 'fearless-you-systems'),
            'fearless_faculty' => __('Faculty Members', 'fearless-you-systems'),
            'fearless_ambassador' => __('Ambassadors', 'fearless-you-systems')
        );

        echo '<div class="fym-stats-grid">';
        foreach ($roles as $role => $label) {
            $users = get_users(array('role' => $role));
            $count = count($users);
            ?>
            <div class="fym-stat-card">
                <h3><?php echo esc_html($label); ?></h3>
                <div class="fym-stat-number"><?php echo $count; ?></div>
                <a href="<?php echo admin_url('admin.php?page=fym-' . str_replace('fearless_', '', str_replace('_', '-', $role))); ?>"
                   class="button button-secondary"><?php _e('Manage', 'fearless-you-systems'); ?></a>
            </div>
            <?php
        }
        echo '</div>';
    }

    private function display_role_management() {
        ?>
        <div class="fym-role-actions">
            <h3><?php _e('Quick Actions', 'fearless-you-systems'); ?></h3>
            <p>
                <a href="<?php echo admin_url('users.php'); ?>" class="button">
                    <?php _e('Manage Users', 'fearless-you-systems'); ?>
                </a>
                <button type="button" class="button" onclick="fymSyncRoles()">
                    <?php _e('Sync Roles', 'fearless-you-systems'); ?>
                </button>
                <button type="button" class="button" onclick="fymExportMembers()">
                    <?php _e('Export Members', 'fearless-you-systems'); ?>
                </button>
            </p>
        </div>

        <div class="fym-role-hierarchy">
            <h3><?php _e('Role Hierarchy', 'fearless-you-systems'); ?></h3>
            <ol>
                <li><strong><?php _e('Fearless Faculty', 'fearless-you-systems'); ?></strong> - <?php _e('Teaching and content creation', 'fearless-you-systems'); ?></li>
                <li><strong><?php _e('Fearless You Member', 'fearless-you-systems'); ?></strong> - <?php _e('Full membership access', 'fearless-you-systems'); ?></li>
                <li><strong><?php _e('Fearless Ambassador', 'fearless-you-systems'); ?></strong> - <?php _e('Promotional and referral access', 'fearless-you-systems'); ?></li>
                <li><strong><?php _e('Student', 'fearless-you-systems'); ?></strong> - <?php _e('Default registration role (basic access)', 'fearless-you-systems'); ?></li>
            </ol>
        </div>
        <?php
    }

    public function members_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Fearless You Members', 'fearless-you-systems'); ?></h1>
            <?php $this->display_users_table('fearless_you_member'); ?>
        </div>
        <?php
    }

    public function faculty_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Fearless Faculty', 'fearless-you-systems'); ?></h1>
            <?php $this->display_users_table('fearless_faculty'); ?>
        </div>
        <?php
    }

    public function ambassadors_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Fearless Ambassadors', 'fearless-you-systems'); ?></h1>
            <?php $this->display_users_table('fearless_ambassador'); ?>
        </div>
        <?php
    }

    private function display_users_table($role) {
        $users = get_users(array('role' => $role));

        if (empty($users)) {
            echo '<p>' . __('No users found with this role.', 'fearless-you-systems') . '</p>';
            return;
        }

        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Name', 'fearless-you-systems'); ?></th>
                    <th><?php _e('Email', 'fearless-you-systems'); ?></th>
                    <th><?php _e('Joined', 'fearless-you-systems'); ?></th>
                    <th><?php _e('Actions', 'fearless-you-systems'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($user->display_name); ?></strong>
                            <br><small><?php echo esc_html($user->user_login); ?></small>
                        </td>
                        <td><?php echo esc_html($user->user_email); ?></td>
                        <td><?php echo date('M j, Y', strtotime($user->user_registered)); ?></td>
                        <td>
                            <a href="<?php echo admin_url('user-edit.php?user_id=' . $user->ID); ?>"
                               class="button button-small"><?php _e('Edit', 'fearless-you-systems'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'fym-') === false) {
            return;
        }

        wp_enqueue_style(
            'fym-admin',
            FYS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            FYS_VERSION
        );

        wp_enqueue_script(
            'fym-admin',
            FYS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            FYS_VERSION,
            true
        );

        wp_localize_script('fym-admin', 'fym_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fym_admin')
        ));
    }
}