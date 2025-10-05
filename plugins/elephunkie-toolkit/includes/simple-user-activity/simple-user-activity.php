<?php
/**
 * Plugin Name: Simple User Activity Tracker
 * Description: A lightweight user activity tracker that tracks basic user activity without interfering with other systems
 * Version: 1.0.0
 * Author: Elephunkie
 */

if (!defined('ABSPATH')) {
    exit;
}

class Elephunkie_Simple_User_Activity {
    
    public function __construct() {
        // Only add hooks if we're in admin
        if (is_admin()) {
            add_action('current_screen', [$this, 'maybe_add_user_columns']);
            add_action('admin_menu', [$this, 'add_admin_menu']);
        }
    }
    
    public function maybe_add_user_columns() {
        $screen = get_current_screen();
        
        // Only add columns on the main users.php page
        if ($screen && $screen->id === 'users') {
            add_filter('manage_users_columns', [$this, 'add_user_columns']);
            add_filter('manage_users_custom_column', [$this, 'populate_user_columns'], 10, 3);
        }
    }
    
    public function add_user_columns($columns) {
        $columns['last_login'] = 'Last Login';
        $columns['registration_date'] = 'Registered';
        return $columns;
    }
    
    public function populate_user_columns($value, $column_name, $user_id) {
        if ($column_name === 'last_login') {
            $last_login = get_user_meta($user_id, 'last_login_time', true);
            if ($last_login) {
                return date('M j, Y', $last_login);
            } else {
                return 'Never';
            }
        }
        
        if ($column_name === 'registration_date') {
            $user = get_userdata($user_id);
            if ($user) {
                return date('M j, Y', strtotime($user->user_registered));
            }
            return 'Unknown';
        }
        
        return $value;
    }
    
    public function add_admin_menu() {
        add_users_page(
            'Simple Activity Report',
            'Activity Report',
            'manage_options',
            'simple-activity-report',
            [$this, 'render_activity_report']
        );
    }
    
    public function render_activity_report() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Get some basic statistics
        $total_users = count_users();
        $recent_users = get_users([
            'date_query' => [
                'after' => '30 days ago'
            ],
            'number' => 10
        ]);
        
        ?>
        <div class="wrap">
            <h1>Simple Activity Report</h1>
            
            <div class="activity-stats">
                <h2>User Statistics</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Total Users</td>
                            <td><?php echo esc_html($total_users['total_users']); ?></td>
                        </tr>
                        <tr>
                            <td>New Users (Last 30 Days)</td>
                            <td><?php echo esc_html(count($recent_users)); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <?php if (!empty($recent_users)): ?>
            <div class="recent-users">
                <h2>Recent Registrations (Last 30 Days)</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Registered</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_users as $user): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_edit_user_link($user->ID); ?>">
                                        <?php echo esc_html($user->display_name); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($user->user_email); ?></td>
                                <td><?php echo date('M j, Y', strtotime($user->user_registered)); ?></td>
                                <td><?php echo esc_html(implode(', ', $user->roles)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <div class="activity-actions">
                <h2>Export Options</h2>
                <p>For advanced user data exports, use the existing WordPress export functionality:</p>
                <a href="<?php echo admin_url('export.php'); ?>" class="button button-primary">WordPress Export Tool</a>
            </div>
        </div>
        
        <style>
        .activity-stats, .recent-users, .activity-actions {
            margin: 20px 0;
        }
        .activity-stats table, .recent-users table {
            max-width: 600px;
        }
        </style>
        <?php
    }
}

// Track user login times
add_action('wp_login', function($user_login, $user) {
    update_user_meta($user->ID, 'last_login_time', time());
}, 10, 2);

// Initialize the simple user activity tracker
new Elephunkie_Simple_User_Activity();