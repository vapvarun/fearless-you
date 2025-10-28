<?php
/**
 * Membership Roles Management
 * Automatically assigns roles based on membership tags
 * 
 * Role Hierarchy:
 * - Fearless You Member (has 'fearless you membership - active' tag)
 * - Public User (has any purchase tag)
 * - Free User (no purchase tags)
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Membership_Roles {

    private $fearless_you_tag = 'fearless you membership - active';
    private $fearless_you_role = 'fearless_you_member';
    private $public_user_role = 'public_user';
    private $free_user_role = 'free_user';
    private $audit_log_option = 'lccp_role_change_audit_log';
    private $max_log_entries = 500;

    public function __construct() {
        // Create custom roles on activation
        add_action('init', array($this, 'create_membership_roles'));
        
        // Hook into user registration
        add_action('user_register', array($this, 'assign_role_on_registration'), 10, 1);
        
        // Hook into profile updates
        add_action('profile_update', array($this, 'update_role_on_profile_save'), 10, 2);
        
        // Hook into tag changes (for various membership plugins)
        add_action('added_user_meta', array($this, 'check_role_on_meta_change'), 10, 4);
        add_action('updated_user_meta', array($this, 'check_role_on_meta_change'), 10, 4);
        add_action('deleted_user_meta', array($this, 'check_role_on_meta_change'), 10, 4);
        
        // LearnDash course enrollment hooks
        add_action('learndash_update_course_access', array($this, 'check_role_on_course_access'), 10, 3);
        
        // WooCommerce membership hooks (if using WooCommerce Memberships)
        add_action('wc_memberships_user_membership_status_changed', array($this, 'check_role_on_membership_change'), 10, 3);
        
        // BuddyBoss/BuddyPress group membership
        add_action('groups_join_group', array($this, 'check_role_on_group_join'), 10, 2);
        add_action('groups_leave_group', array($this, 'check_role_on_group_leave'), 10, 2);
        
        // Admin interface for bulk role updates
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // AJAX handler for manual role sync
        add_action('wp_ajax_lccp_sync_membership_roles', array($this, 'ajax_sync_all_users'));
        
        // Daily cron to sync roles
        add_action('wp', array($this, 'schedule_daily_sync'));
        add_action('lccp_daily_role_sync', array($this, 'sync_all_user_roles'));
    }
    
    /**
     * Create custom membership roles
     */
    public function create_membership_roles() {
        // Fearless You Member role
        if (!get_role($this->fearless_you_role)) {
            add_role(
                $this->fearless_you_role,
                __('Fearless You Member', 'lccp-systems'),
                array(
                    'read' => true,
                    'upload_files' => true,
                    'edit_posts' => false,
                    'delete_posts' => false,
                    'publish_posts' => false,
                    'edit_published_posts' => false,
                    'delete_published_posts' => false,
                    'edit_others_posts' => false,
                    'delete_others_posts' => false,
                    'read_private_posts' => true,
                    'edit_private_posts' => false,
                    'delete_private_posts' => false,
                    'manage_categories' => false,
                    'moderate_comments' => false,
                    'unfiltered_html' => false,
                    'edit_dashboard' => true,
                    'level_2' => true,
                    'level_1' => true,
                    'level_0' => true,
                    // Custom capabilities for premium content
                    'access_premium_content' => true,
                    'access_fearless_you' => true,
                    'view_premium_courses' => true
                )
            );
        }
        
        // Public User role (has purchases)
        if (!get_role($this->public_user_role)) {
            add_role(
                $this->public_user_role,
                __('Public User', 'lccp-systems'),
                array(
                    'read' => true,
                    'upload_files' => false,
                    'level_1' => true,
                    'level_0' => true,
                    'access_purchased_content' => true,
                    'view_purchased_courses' => true
                )
            );
        }
        
        // Free User role (no purchases)
        if (!get_role($this->free_user_role)) {
            add_role(
                $this->free_user_role,
                __('Free User', 'lccp-systems'),
                array(
                    'read' => true,
                    'level_0' => true,
                    'access_free_content' => true
                )
            );
        }
    }
    
    /**
     * Get user's tags/memberships
     */
    private function get_user_tags($user_id) {
        $tags = array();
        
        // Check for tags in user meta
        $meta_tags = get_user_meta($user_id, 'user_tags', true);
        if ($meta_tags) {
            $tags = array_merge($tags, (array) $meta_tags);
        }
        
        // Check for membership tags
        $membership_tags = get_user_meta($user_id, 'membership_tags', true);
        if ($membership_tags) {
            $tags = array_merge($tags, (array) $membership_tags);
        }
        
        // Check for LearnDash tags
        if (function_exists('learndash_get_users_group_ids')) {
            $group_ids = learndash_get_users_group_ids($user_id);
            foreach ($group_ids as $group_id) {
                $group_tag = get_post_meta($group_id, 'group_tag', true);
                if ($group_tag) {
                    $tags[] = $group_tag;
                }
            }
        }
        
        // Check for WooCommerce Memberships
        if (function_exists('wc_memberships_get_user_active_memberships')) {
            $memberships = wc_memberships_get_user_active_memberships($user_id);
            foreach ($memberships as $membership) {
                $plan = $membership->get_plan();
                if ($plan) {
                    $tags[] = $plan->get_slug();
                    $tags[] = $plan->get_name();
                }
            }
        }
        
        // Check BuddyBoss/BuddyPress groups
        if (function_exists('groups_get_user_groups')) {
            $groups = groups_get_user_groups($user_id);
            if (!empty($groups['groups'])) {
                foreach ($groups['groups'] as $group_id) {
                    $group = groups_get_group($group_id);
                    if ($group) {
                        $tags[] = $group->slug;
                        $tags[] = $group->name;
                    }
                }
            }
        }
        
        // Apply filters so other plugins can add tags
        $tags = apply_filters('lccp_user_tags', $tags, $user_id);
        
        // Normalize tags (lowercase, trim)
        $tags = array_map('strtolower', array_map('trim', $tags));
        
        return array_unique($tags);
    }
    
    /**
     * Check if user has any purchase
     */
    private function user_has_purchases($user_id) {
        // Check WooCommerce orders
        if (class_exists('WooCommerce')) {
            $customer_orders = wc_get_orders(array(
                'customer_id' => $user_id,
                'status' => array('wc-completed', 'wc-processing'),
                'limit' => 1
            ));
            
            if (!empty($customer_orders)) {
                return true;
            }
        }
        
        // Check LearnDash course enrollments
        if (function_exists('learndash_user_get_enrolled_courses')) {
            $enrolled_courses = learndash_user_get_enrolled_courses($user_id);
            if (!empty($enrolled_courses)) {
                return true;
            }
        }
        
        // Check for purchase tags in user meta
        $purchase_tags = get_user_meta($user_id, 'purchase_tags', true);
        if (!empty($purchase_tags)) {
            return true;
        }
        
        // Check for any tag containing 'purchase' or 'bought' or 'customer'
        $tags = $this->get_user_tags($user_id);
        foreach ($tags as $tag) {
            if (strpos($tag, 'purchase') !== false || 
                strpos($tag, 'bought') !== false || 
                strpos($tag, 'customer') !== false ||
                strpos($tag, 'enrolled') !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Determine appropriate role for user
     */
    private function determine_user_role($user_id) {
        // Don't change administrators or special LCCP roles
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return false;
        }
        
        // Preserve special roles
        $protected_roles = array('administrator', 'lccp_mentor', 'lccp_big_bird', 'lccp_pc', 'editor', 'author');
        $current_roles = $user->roles;
        
        foreach ($protected_roles as $protected_role) {
            if (in_array($protected_role, $current_roles)) {
                return false; // Don't change protected roles
            }
        }
        
        // Get user tags
        $tags = $this->get_user_tags($user_id);
        
        // Check for Fearless You membership
        $fearless_you_tag_normalized = strtolower(trim($this->fearless_you_tag));
        
        foreach ($tags as $tag) {
            if ($tag === $fearless_you_tag_normalized || 
                strpos($tag, 'fearless you') !== false && strpos($tag, 'active') !== false) {
                return $this->fearless_you_role;
            }
        }
        
        // Check if user has any purchases
        if ($this->user_has_purchases($user_id)) {
            return $this->public_user_role;
        }
        
        // Default to free user
        return $this->free_user_role;
    }
    
    /**
     * Update user role based on tags
     */
    public function update_user_role($user_id, $reason = 'Automatic role assignment') {
        $new_role = $this->determine_user_role($user_id);

        if ($new_role) {
            $user = new WP_User($user_id);
            $old_roles = $user->roles;

            // Security check: Prevent privilege escalation
            if (!$this->is_role_change_safe($user_id, $old_roles, $new_role)) {
                $this->log_security_event(
                    'Blocked privilege escalation attempt',
                    $user_id,
                    $old_roles,
                    $new_role,
                    $reason
                );
                return false;
            }

            // Rate limiting: Check if too many role changes in short time
            if (!$this->check_role_change_rate_limit($user_id)) {
                $this->log_security_event(
                    'Rate limit exceeded for role changes',
                    $user_id,
                    $old_roles,
                    $new_role,
                    $reason
                );
                return false;
            }

            // Remove old membership roles
            $membership_roles = array($this->fearless_you_role, $this->public_user_role, $this->free_user_role, 'subscriber');
            foreach ($membership_roles as $role) {
                $user->remove_role($role);
            }

            // Add new role
            $user->add_role($new_role);

            // Log the change to user meta
            update_user_meta($user_id, 'lccp_last_role_update', current_time('mysql'));
            update_user_meta($user_id, 'lccp_role_reason', $reason);

            // Store previous role for rollback capability
            update_user_meta($user_id, 'lccp_previous_role', $old_roles);

            // Add to audit log
            $this->add_to_audit_log($user_id, $old_roles, $new_role, $reason);

            // Alert admin if suspicious change detected
            if ($this->is_suspicious_role_change($user_id, $old_roles, $new_role)) {
                $this->send_role_change_alert($user_id, $old_roles, $new_role, $reason);
            }

            do_action('lccp_user_role_updated', $user_id, $new_role, $old_roles);

            return true;
        }

        return false;
    }

    /**
     * Check if role change is safe (prevent privilege escalation)
     */
    private function is_role_change_safe($user_id, $old_roles, $new_role) {
        // Never allow changing administrator or editor roles
        $protected_roles = array('administrator', 'editor', 'shop_manager');

        foreach ($old_roles as $role) {
            if (in_array($role, $protected_roles)) {
                return false;
            }
        }

        // Only allow changes between membership roles
        $allowed_target_roles = array(
            $this->fearless_you_role,
            $this->public_user_role,
            $this->free_user_role,
            'subscriber'
        );

        return in_array($new_role, $allowed_target_roles);
    }

    /**
     * Check rate limit for role changes (max 5 changes per hour per user)
     */
    private function check_role_change_rate_limit($user_id) {
        $cache_key = 'lccp_role_changes_' . $user_id;
        $changes = get_transient($cache_key);

        if ($changes === false) {
            set_transient($cache_key, 1, HOUR_IN_SECONDS);
            return true;
        }

        if ($changes >= 5) {
            return false; // Too many changes
        }

        set_transient($cache_key, $changes + 1, HOUR_IN_SECONDS);
        return true;
    }

    /**
     * Check if role change is suspicious
     */
    private function is_suspicious_role_change($user_id, $old_roles, $new_role) {
        // Flag if changing from paid to free within 7 days
        if ($new_role === $this->free_user_role) {
            $last_update = get_user_meta($user_id, 'lccp_last_role_update', true);
            if ($last_update) {
                $last_update_time = strtotime($last_update);
                $days_since = (time() - $last_update_time) / DAY_IN_SECONDS;

                if ($days_since < 7 && in_array($this->fearless_you_role, $old_roles)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Add role change to audit log
     */
    private function add_to_audit_log($user_id, $old_roles, $new_role, $reason) {
        $log = get_option($this->audit_log_option, array());

        // Limit log size
        if (count($log) >= $this->max_log_entries) {
            $log = array_slice($log, -($this->max_log_entries - 1));
        }

        $user = get_user_by('ID', $user_id);
        $log[] = array(
            'user_id' => $user_id,
            'username' => $user ? $user->user_login : 'Unknown',
            'old_roles' => $old_roles,
            'new_role' => $new_role,
            'reason' => $reason,
            'timestamp' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        );

        update_option($this->audit_log_option, $log);
    }

    /**
     * Log security event
     */
    private function log_security_event($event, $user_id, $old_roles, $new_role, $reason) {
        error_log(sprintf(
            '[LCCP Membership Roles Security] %s - User ID: %d, Old Roles: %s, New Role: %s, Reason: %s',
            $event,
            $user_id,
            implode(', ', $old_roles),
            $new_role,
            $reason
        ));

        // Add to security log
        $security_log = get_option('lccp_role_security_log', array());
        $security_log[] = array(
            'event' => $event,
            'user_id' => $user_id,
            'old_roles' => $old_roles,
            'new_role' => $new_role,
            'reason' => $reason,
            'timestamp' => current_time('mysql')
        );

        // Keep only last 100 security events
        if (count($security_log) > 100) {
            $security_log = array_slice($security_log, -100);
        }

        update_option('lccp_role_security_log', $security_log);
    }

    /**
     * Send alert email to admin about suspicious role change
     */
    private function send_role_change_alert($user_id, $old_roles, $new_role, $reason) {
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return;
        }

        $admin_email = get_option('admin_email');
        $subject = '[LCCP Security Alert] Suspicious Role Change Detected';
        $message = sprintf(
            "A suspicious role change was detected:\n\n" .
            "User: %s (%s)\n" .
            "Old Roles: %s\n" .
            "New Role: %s\n" .
            "Reason: %s\n" .
            "Time: %s\n" .
            "IP Address: %s\n\n" .
            "Please review this change at: %s",
            $user->user_login,
            $user->user_email,
            implode(', ', $old_roles),
            $new_role,
            $reason,
            current_time('mysql'),
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            admin_url('admin.php?page=lccp-membership-roles')
        );

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Rollback user to previous role
     */
    public function rollback_user_role($user_id) {
        if (!current_user_can('manage_options')) {
            return false;
        }

        $previous_roles = get_user_meta($user_id, 'lccp_previous_role', true);
        if (empty($previous_roles)) {
            return false;
        }

        $user = new WP_User($user_id);

        // Remove current roles
        foreach ($user->roles as $role) {
            $user->remove_role($role);
        }

        // Restore previous roles
        foreach ($previous_roles as $role) {
            $user->add_role($role);
        }

        $this->add_to_audit_log($user_id, $user->roles, implode(', ', $previous_roles), 'Manual rollback by admin');

        return true;
    }
    
    /**
     * Assign role when user registers
     */
    public function assign_role_on_registration($user_id) {
        // Give a moment for other plugins to set their data
        wp_schedule_single_event(time() + 5, 'lccp_check_new_user_role', array($user_id));
    }
    
    /**
     * Update role when profile is saved
     */
    public function update_role_on_profile_save($user_id, $old_user_data) {
        $this->update_user_role($user_id);
    }
    
    /**
     * Check role when user meta changes
     */
    public function check_role_on_meta_change($meta_id, $user_id, $meta_key, $meta_value) {
        // Only check for relevant meta keys
        $relevant_keys = array('user_tags', 'membership_tags', 'purchase_tags', 'membership_status');
        
        if (in_array($meta_key, $relevant_keys)) {
            $this->update_user_role($user_id);
        }
    }
    
    /**
     * Check role when course access changes
     */
    public function check_role_on_course_access($user_id, $course_id, $access_type) {
        $this->update_user_role($user_id);
    }
    
    /**
     * Check role when membership changes
     */
    public function check_role_on_membership_change($membership, $old_status, $new_status) {
        if ($membership && $membership->get_user_id()) {
            $this->update_user_role($membership->get_user_id());
        }
    }
    
    /**
     * Check role when user joins group
     */
    public function check_role_on_group_join($group_id, $user_id) {
        $this->update_user_role($user_id);
    }
    
    /**
     * Check role when user leaves group
     */
    public function check_role_on_group_leave($group_id, $user_id) {
        $this->update_user_role($user_id);
    }
    
    /**
     * Add admin menu for role management
     */
    public function add_admin_menu() {
        add_submenu_page(
            'lccp-systems',
            __('Membership Roles', 'lccp-systems'),
            __('Membership Roles', 'lccp-systems'),
            'manage_options',
            'lccp-membership-roles',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Membership Role Management', 'lccp-systems'); ?></h1>
            
            <div class="card">
                <h2><?php _e('Role Assignment Rules', 'lccp-systems'); ?></h2>
                <ul>
                    <li><strong>Fearless You Member:</strong> Users with "fearless you membership - active" tag</li>
                    <li><strong>Public User:</strong> Users with any purchase history</li>
                    <li><strong>Free User:</strong> Users without purchases (default)</li>
                </ul>
            </div>
            
            <div class="card">
                <h2><?php _e('Sync All Users', 'lccp-systems'); ?></h2>
                <p><?php _e('Re-check all users and update their roles based on current tags and purchases.', 'lccp-systems'); ?></p>
                <button id="sync-all-roles" class="button button-primary">
                    <?php _e('Sync All User Roles', 'lccp-systems'); ?>
                </button>
                <div id="sync-status"></div>
            </div>
            
            <div class="card">
                <h2><?php _e('Role Statistics', 'lccp-systems'); ?></h2>
                <?php $this->display_role_statistics(); ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#sync-all-roles').click(function() {
                var $button = $(this);
                var $status = $('#sync-status');
                
                $button.prop('disabled', true);
                $status.html('<p>Syncing roles... Please wait.</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'lccp_sync_membership_roles',
                        nonce: '<?php echo wp_create_nonce('lccp_sync_roles'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.html('<p style="color: green;">' + response.data.message + '</p>');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $status.html('<p style="color: red;">Error: ' + response.data + '</p>');
                        }
                        $button.prop('disabled', false);
                    },
                    error: function() {
                        $status.html('<p style="color: red;">Ajax error occurred.</p>');
                        $button.prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Display role statistics
     */
    private function display_role_statistics() {
        $roles = array(
            $this->fearless_you_role => __('Fearless You Members', 'lccp-systems'),
            $this->public_user_role => __('Public Users', 'lccp-systems'),
            $this->free_user_role => __('Free Users', 'lccp-systems'),
            'subscriber' => __('Subscribers (legacy)', 'lccp-systems')
        );
        
        echo '<table class="widefat">';
        echo '<thead><tr><th>Role</th><th>Count</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($roles as $role_key => $role_name) {
            $users = get_users(array('role' => $role_key));
            $count = count($users);
            echo '<tr>';
            echo '<td>' . esc_html($role_name) . '</td>';
            echo '<td>' . $count . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    /**
     * AJAX handler to sync all users
     */
    public function ajax_sync_all_users() {
        check_ajax_referer('lccp_sync_roles', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $result = $this->sync_all_user_roles();
        
        wp_send_json_success(array(
            'message' => sprintf('Successfully synced %d users', $result['updated']),
            'updated' => $result['updated'],
            'skipped' => $result['skipped']
        ));
    }
    
    /**
     * Sync all user roles
     */
    public function sync_all_user_roles() {
        $users = get_users(array('fields' => 'ID'));
        $updated = 0;
        $skipped = 0;
        
        foreach ($users as $user_id) {
            $new_role = $this->determine_user_role($user_id);
            if ($new_role) {
                $this->update_user_role($user_id);
                $updated++;
            } else {
                $skipped++;
            }
        }
        
        update_option('lccp_last_role_sync', current_time('mysql'));
        
        return array(
            'updated' => $updated,
            'skipped' => $skipped
        );
    }
    
    /**
     * Schedule daily sync
     */
    public function schedule_daily_sync() {
        if (!wp_next_scheduled('lccp_daily_role_sync')) {
            wp_schedule_event(time(), 'daily', 'lccp_daily_role_sync');
        }
    }
}

// Initialize the membership roles system
new LCCP_Membership_Roles();

// Handle scheduled new user role check
add_action('lccp_check_new_user_role', function($user_id) {
    $roles = new LCCP_Membership_Roles();
    $roles->update_user_role($user_id);
});