<?php
/**
 * Plugin Name: Inactive Plugin Manager
 * Description: Manage inactive plugins with an interface similar to the Plugins page, and add actions to move plugins to an inactive folder.
 * Version: 1.2
 * Author: Elephunkie
 */

if (!defined('ABSPATH')) {
    exit;
}

class Elephunkie_Inactive_Plugin_Manager {
    
    private $inactive_plugins_dir;
    
    public function __construct() {
        $this->inactive_plugins_dir = WP_CONTENT_DIR . '/inactive-plugins';
        
        add_action('plugins_loaded', [$this, 'create_inactive_plugins_folder']);
        add_action('deactivated_plugin', [$this, 'move_deactivated_plugin_to_inactive']);
        add_action('admin_menu', [$this, 'add_inactive_plugins_submenu']);
        add_action('admin_init', [$this, 'handle_inactive_plugins_actions']);
        add_action('admin_init', [$this, 'handle_move_to_inactive_action']);
        add_filter('plugin_action_links', [$this, 'add_move_to_inactive_link'], 10, 4);
    }
    
    public function create_inactive_plugins_folder() {
        if (!is_dir($this->inactive_plugins_dir)) {
            wp_mkdir_p($this->inactive_plugins_dir);
        }
    }
    
    public function move_deactivated_plugin_to_inactive($plugin) {
        $plugin_dir = WP_PLUGIN_DIR . '/' . dirname($plugin);

        if (is_dir($plugin_dir)) {
            rename($plugin_dir, $this->inactive_plugins_dir . '/' . dirname($plugin));
        }
    }
    
    public function add_inactive_plugins_submenu() {
        add_submenu_page(
            'plugins.php',
            'Manage Inactive Plugins',
            'Inactive Plugins',
            'manage_options',
            'manage-inactive-plugins',
            [$this, 'render_inactive_plugins_page']
        );
    }
    
    public function render_inactive_plugins_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $inactive_plugins = $this->get_inactive_plugins();

        ?>
        <div class="wrap">
            <h1>Inactive Plugins</h1>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col">Plugin</th>
                        <th scope="col">Description</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($inactive_plugins)) : ?>
                        <?php foreach ($inactive_plugins as $plugin) : ?>
                            <tr>
                                <td><strong><?php echo esc_html($plugin['name']); ?></strong></td>
                                <td><?php echo esc_html($plugin['description']); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=manage-inactive-plugins&action=move_to_active&plugin=' . urlencode($plugin['slug']) . '&_wpnonce=' . wp_create_nonce('move_to_active'))); ?>" class="button">Move to Active</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="3">No inactive plugins found in the inactive folder.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function handle_inactive_plugins_actions() {
        if (isset($_GET['action'], $_GET['plugin'], $_GET['_wpnonce']) && $_GET['action'] === 'move_to_active') {
            if (!wp_verify_nonce($_GET['_wpnonce'], 'move_to_active')) {
                wp_die(__('Security check failed.'));
            }

            $plugin = sanitize_text_field($_GET['plugin']);
            $plugin_dir = $this->inactive_plugins_dir . '/' . $plugin;

            if (is_dir($plugin_dir)) {
                rename($plugin_dir, WP_PLUGIN_DIR . '/' . $plugin);
                activate_plugin($plugin);
                wp_redirect(admin_url('admin.php?page=manage-inactive-plugins&success=1'));
                exit;
            }
        }
    }
    
    public function add_move_to_inactive_link($actions, $plugin_file, $plugin_data, $context) {
        if ($context === 'inactive') {
            $actions['move_to_inactive'] = sprintf(
                '<a href="%s" class="move-to-inactive">%s</a>',
                esc_url(admin_url('plugins.php?action=move_to_inactive&plugin=' . urlencode($plugin_file) . '&_wpnonce=' . wp_create_nonce('move_to_inactive'))),
                __('Move to Inactive Folder', 'text-domain')
            );
        }
        return $actions;
    }
    
    public function handle_move_to_inactive_action() {
        if (isset($_GET['action'], $_GET['plugin'], $_GET['_wpnonce']) && $_GET['action'] === 'move_to_inactive') {
            if (!wp_verify_nonce($_GET['_wpnonce'], 'move_to_inactive')) {
                wp_die(__('Security check failed.'));
            }

            $plugin = sanitize_text_field($_GET['plugin']);
            $plugin_dir = WP_PLUGIN_DIR . '/' . dirname($plugin);

            if (is_dir($plugin_dir)) {
                deactivate_plugins($plugin);
                rename($plugin_dir, $this->inactive_plugins_dir . '/' . dirname($plugin));
                wp_redirect(admin_url('plugins.php?success=1'));
                exit;
            }
        }
    }
    
    public function get_inactive_plugins() {
        $inactive_plugins = [];

        if (is_dir($this->inactive_plugins_dir)) {
            $plugin_dirs = scandir($this->inactive_plugins_dir);

            foreach ($plugin_dirs as $plugin_dir) {
                if ($plugin_dir === '.' || $plugin_dir === '..') {
                    continue;
                }

                $plugin_file = $this->inactive_plugins_dir . '/' . $plugin_dir . '/' . basename($plugin_dir) . '.php';
                if (file_exists($plugin_file)) {
                    $plugin_data = get_plugin_data($plugin_file);
                    $inactive_plugins[] = [
                        'slug' => $plugin_dir,
                        'name' => $plugin_data['Name'],
                        'description' => $plugin_data['Description'],
                    ];
                }
            }
        }

        return $inactive_plugins;
    }
}

// Initialize the Inactive Plugin Manager
new Elephunkie_Inactive_Plugin_Manager();