<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class FRM_Dashboard_Redirect {
    
    public function __construct() {
        // Hook into login redirect
        add_filter('login_redirect', array($this, 'custom_login_redirect'), 10, 3);
        
        // Hook into admin init for dashboard redirect
        add_action('admin_init', array($this, 'redirect_dashboard'));
    }
    
    /**
     * Custom login redirect based on user role
     */
    public function custom_login_redirect($redirect_to, $request, $user) {
        // Check if user is valid
        if (!isset($user->roles) || !is_array($user->roles)) {
            return $redirect_to;
        }

        // Get role settings
        $settings = get_option('frm_role_settings', array());

        // Check each user role for custom dashboard
        foreach ($user->roles as $role) {
            if (isset($settings[$role]) && isset($settings[$role]['dashboard_page'])) {
                $dashboard_page = $settings[$role]['dashboard_page'];

                // If empty, use the homepage from Reading Settings
                if (empty($dashboard_page)) {
                    $homepage_id = get_option('page_on_front');
                    if ($homepage_id) {
                        $redirect_to = get_permalink($homepage_id);
                    } else {
                        $redirect_to = home_url('/');
                    }
                } elseif (strpos($dashboard_page, 'http') === 0) {
                    // It's a full URL (from pages)
                    $redirect_to = $dashboard_page;
                } else {
                    // It's an admin page
                    $redirect_to = admin_url($dashboard_page);
                }
                break; // Use first role with custom dashboard
            }
        }

        return $redirect_to;
    }
    
    /**
     * Redirect from default dashboard if role has custom landing page
     */
    public function redirect_dashboard() {
        // Only redirect on dashboard page
        global $pagenow;
        if ($pagenow !== 'index.php') {
            return;
        }

        // Don't redirect if already redirecting
        if (isset($_GET['frm_redirected'])) {
            return;
        }

        // Get current user
        $user = wp_get_current_user();
        if (!$user || !isset($user->roles) || !is_array($user->roles)) {
            return;
        }

        // Get role settings
        $settings = get_option('frm_role_settings', array());

        // Check each user role for custom dashboard
        foreach ($user->roles as $role) {
            if (isset($settings[$role]) && isset($settings[$role]['dashboard_page'])) {
                $dashboard_page = $settings[$role]['dashboard_page'];

                // Skip if no custom page is set (empty means use default)
                if (empty($dashboard_page)) {
                    continue;
                }

                if (strpos($dashboard_page, 'http') === 0) {
                    // It's a full URL (from pages)
                    $redirect_url = $dashboard_page;
                } else {
                    // It's an admin page
                    $redirect_url = admin_url($dashboard_page);
                }

                // Add parameter to prevent redirect loop
                if (strpos($redirect_url, '?') !== false) {
                    $redirect_url .= '&frm_redirected=1';
                } else {
                    $redirect_url .= '?frm_redirected=1';
                }

                wp_redirect($redirect_url);
                exit;
            }
        }
    }
    
    /**
     * Check if WP Fusion is active and sync tags to roles
     */
    public static function sync_wp_fusion_tags($user_id) {
        if (!function_exists('wp_fusion')) {
            return;
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }
        
        // Get user's tags from WP Fusion
        $user_tags = wp_fusion()->user->get_tags($user_id);
        if (empty($user_tags)) {
            return;
        }
        
        // Get role settings
        $settings = get_option('frm_role_settings', array());
        
        // Check each role for matching tags
        foreach ($settings as $role_key => $role_settings) {
            if (!isset($role_settings['wp_fusion_tags']) || empty($role_settings['wp_fusion_tags'])) {
                continue;
            }
            
            // Check if user has any of the required tags
            $matching_tags = array_intersect($user_tags, $role_settings['wp_fusion_tags']);
            
            if (!empty($matching_tags)) {
                // Add role if user doesn't have it
                if (!in_array($role_key, $user->roles)) {
                    $user->add_role($role_key);
                }
            } else {
                // Optionally remove role if user doesn't have tags
                // Uncomment if you want to remove roles when tags are removed
                // if (in_array($role_key, $user->roles)) {
                //     $user->remove_role($role_key);
                // }
            }
        }
    }
}

// Hook into WP Fusion tag updates
add_action('wpf_tags_modified', array('FRM_Dashboard_Redirect', 'sync_wp_fusion_tags'), 10, 1);