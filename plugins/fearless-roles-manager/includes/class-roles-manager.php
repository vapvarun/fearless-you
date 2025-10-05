<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class FRM_Roles_Manager {
    
    /**
     * Get all WordPress roles with their details
     */
    public static function get_all_roles() {
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        $roles = array();
        $settings = get_option('frm_role_settings', array());
        
        foreach ($wp_roles->roles as $role_key => $role_data) {
            $role_settings = isset($settings[$role_key]) ? $settings[$role_key] : array();
            
            $roles[$role_key] = array(
                'name' => $role_data['name'],
                'capabilities' => $role_data['capabilities'],
                'wp_fusion_tags' => isset($role_settings['wp_fusion_tags']) ? $role_settings['wp_fusion_tags'] : array(),
                'dashboard_page' => isset($role_settings['dashboard_page']) ? $role_settings['dashboard_page'] : '',
                'description' => isset($role_settings['description']) ? $role_settings['description'] : ''
            );
        }
        
        return $roles;
    }
    
    /**
     * Get WP Fusion tags if plugin is active
     */
    public static function get_wp_fusion_tags() {
        if (!function_exists('wp_fusion')) {
            return array();
        }
        
        $tags = array();
        
        // Get WP Fusion settings
        $wpf_settings = get_option('wpf_options', array());
        
        // Check if we have available tags from the CRM
        if (isset($wpf_settings['available_tags']) && is_array($wpf_settings['available_tags'])) {
            $tags = $wpf_settings['available_tags'];
        } else {
            // Try to load tags directly from the CRM
            if (function_exists('wp_fusion')) {
                $wpf = wp_fusion();
                if (isset($wpf->crm) && method_exists($wpf->crm, 'sync_tags')) {
                    $wpf->crm->sync_tags();
                    $wpf_settings = get_option('wpf_options', array());
                    if (isset($wpf_settings['available_tags'])) {
                        $tags = $wpf_settings['available_tags'];
                    }
                }
            }
        }
        
        return $tags;
    }
    
    /**
     * Get available dashboard pages
     */
    public static function get_dashboard_pages() {
        // Get the homepage from Reading Settings
        $homepage_id = get_option('page_on_front');
        $homepage_title = '-- Default (Homepage from Reading Settings) --';

        if ($homepage_id) {
            $homepage = get_post($homepage_id);
            if ($homepage) {
                $homepage_title = '-- Default: ' . $homepage->post_title . ' --';
            }
        }

        // Start with default option
        $pages = array(
            '' => $homepage_title,
        );

        // Get all published pages from WordPress
        $wp_pages = get_pages(array(
            'post_status' => 'publish',
            'sort_column' => 'menu_order,post_title',
            'hierarchical' => false,
        ));

        // Separate dashboard pages and regular pages
        $dashboard_pages = array();
        $regular_pages = array();

        // Define the dashboard types we want to consolidate
        $dashboard_types = array(
            'pc' => null,
            'big_bird' => null,
            'mentor' => null,
            'faculty' => null,
            'student' => null,
            'ambassador' => null,
            'my_dashboard' => null,
        );

        foreach ($wp_pages as $page) {
            $page_url = get_permalink($page->ID);
            $page_title = $page->post_title;
            $title_lower = strtolower($page_title);

            // Check if "Dashboard" is in the title (case-insensitive)
            if (stripos($page_title, 'dashboard') !== false) {
                // Categorize and keep only the first of each type
                if ((strpos($title_lower, 'pc ') !== false || strpos($title_lower, 'lccp') !== false || strpos($title_lower, 'program coordinator') !== false) && $dashboard_types['pc'] === null) {
                    $dashboard_types['pc'] = array('url' => $page_url, 'title' => 'PC Dashboard');
                } elseif ((strpos($title_lower, 'big bird') !== false || strpos($title_lower, 'bigbird') !== false) && $dashboard_types['big_bird'] === null) {
                    $dashboard_types['big_bird'] = array('url' => $page_url, 'title' => 'Big Bird Dashboard');
                } elseif (strpos($title_lower, 'mentor') !== false && $dashboard_types['mentor'] === null) {
                    $dashboard_types['mentor'] = array('url' => $page_url, 'title' => 'Mentor Dashboard');
                } elseif ((strpos($title_lower, 'faculty') !== false || strpos($title_lower, 'instructor') !== false) && $dashboard_types['faculty'] === null) {
                    $dashboard_types['faculty'] = array('url' => $page_url, 'title' => 'Faculty Dashboard');
                } elseif (strpos($title_lower, 'student') !== false && $dashboard_types['student'] === null) {
                    $dashboard_types['student'] = array('url' => $page_url, 'title' => 'Student Dashboard');
                } elseif (strpos($title_lower, 'ambassador') !== false && $dashboard_types['ambassador'] === null) {
                    $dashboard_types['ambassador'] = array('url' => $page_url, 'title' => 'Ambassador Dashboard');
                } elseif (strpos($title_lower, 'my dashboard') !== false && $dashboard_types['my_dashboard'] === null) {
                    $dashboard_types['my_dashboard'] = array('url' => $page_url, 'title' => 'My Dashboard');
                }
            } else {
                $regular_pages[$page_url] = $page_title;
            }
        }

        // Add consolidated dashboard pages in order
        $dashboard_order = array('pc', 'big_bird', 'mentor', 'faculty', 'student', 'ambassador', 'my_dashboard');
        foreach ($dashboard_order as $type) {
            if ($dashboard_types[$type] !== null) {
                $pages[$dashboard_types[$type]['url']] = '📊 ' . $dashboard_types[$type]['title'];
            }
        }

        // Add a separator if we have dashboard pages
        $has_dashboards = false;
        foreach ($dashboard_types as $type) {
            if ($type !== null) {
                $has_dashboards = true;
                break;
            }
        }
        if ($has_dashboards) {
            $pages['--separator--'] = '────────────────────';
        }

        // Add regular pages (sorted alphabetically)
        asort($regular_pages);
        foreach ($regular_pages as $url => $title) {
            $pages[$url] = $title;
        }

        // Add another separator for admin pages
        $pages['--separator2--'] = '──── Admin Pages ────';

        // Add admin pages
        $admin_pages = array(
            'profile.php' => 'User Profile',
            'edit.php' => 'Posts',
            'edit.php?post_type=page' => 'Pages',
            'admin.php?page=bp-activity' => 'BuddyBoss Activity',
            'admin.php?page=bp-members' => 'BuddyBoss Members',
            'admin.php?page=bp-groups' => 'BuddyBoss Groups',
            'admin.php?page=learndash-lms' => 'LearnDash Courses',
            'edit.php?post_type=sfwd-courses' => 'LearnDash Courses List',
            'edit.php?post_type=sfwd-lessons' => 'LearnDash Lessons',
            'admin.php?page=learndash-lms-reports' => 'LearnDash Reports',
        );

        // Add custom pages if BuddyBoss is active
        if (class_exists('BuddyPress')) {
            $admin_pages['admin.php?page=bp-profile'] = 'BuddyBoss Profile';
            $admin_pages['admin.php?page=bp-settings'] = 'BuddyBoss Settings';
            $admin_pages['admin.php?page=bp-notifications'] = 'BuddyBoss Notifications';
        }

        // Add custom pages if LearnDash is active
        if (defined('LEARNDASH_VERSION')) {
            $admin_pages['admin.php?page=learndash-lms-certificate'] = 'LearnDash Certificates';
            $admin_pages['admin.php?page=learndash-lms-assignments'] = 'LearnDash Assignments';
        }

        // Add admin pages to the list
        foreach ($admin_pages as $url => $title) {
            $pages[$url] = $title;
        }

        // Allow filtering of dashboard pages
        $pages = apply_filters('frm_dashboard_pages', $pages);

        return $pages;
    }
    
    /**
     * Get formatted capabilities for a role
     */
    public static function get_formatted_capabilities($capabilities) {
        $grouped_caps = array(
            'Posts' => array(),
            'Pages' => array(),
            'Media' => array(),
            'Users' => array(),
            'Themes' => array(),
            'Plugins' => array(),
            'General' => array(),
            'LearnDash' => array(),
            'BuddyBoss' => array(),
            'WooCommerce' => array(),
            'Other' => array()
        );
        
        foreach ($capabilities as $cap => $granted) {
            if (!$granted) continue;
            
            if (strpos($cap, 'edit_post') !== false || strpos($cap, 'delete_post') !== false || strpos($cap, 'publish_post') !== false) {
                $grouped_caps['Posts'][] = $cap;
            } elseif (strpos($cap, 'edit_page') !== false || strpos($cap, 'delete_page') !== false || strpos($cap, 'publish_page') !== false) {
                $grouped_caps['Pages'][] = $cap;
            } elseif (strpos($cap, 'upload_files') !== false || strpos($cap, 'edit_files') !== false) {
                $grouped_caps['Media'][] = $cap;
            } elseif (strpos($cap, 'edit_user') !== false || strpos($cap, 'list_user') !== false || strpos($cap, 'create_user') !== false || strpos($cap, 'delete_user') !== false || strpos($cap, 'promote_user') !== false) {
                $grouped_caps['Users'][] = $cap;
            } elseif (strpos($cap, 'edit_theme') !== false || strpos($cap, 'switch_theme') !== false || strpos($cap, 'install_theme') !== false) {
                $grouped_caps['Themes'][] = $cap;
            } elseif (strpos($cap, 'activate_plugin') !== false || strpos($cap, 'edit_plugin') !== false || strpos($cap, 'install_plugin') !== false) {
                $grouped_caps['Plugins'][] = $cap;
            } elseif (strpos($cap, 'learndash') !== false || strpos($cap, 'wpProQuiz') !== false || strpos($cap, 'course') !== false || strpos($cap, 'lesson') !== false) {
                $grouped_caps['LearnDash'][] = $cap;
            } elseif (strpos($cap, 'bp_') !== false || strpos($cap, 'bbp_') !== false) {
                $grouped_caps['BuddyBoss'][] = $cap;
            } elseif (strpos($cap, 'woocommerce') !== false || strpos($cap, 'product') !== false || strpos($cap, 'shop') !== false) {
                $grouped_caps['WooCommerce'][] = $cap;
            } elseif (in_array($cap, array('manage_options', 'manage_categories', 'moderate_comments', 'manage_links', 'import', 'export', 'read'))) {
                $grouped_caps['General'][] = $cap;
            } else {
                $grouped_caps['Other'][] = $cap;
            }
        }
        
        // Remove empty groups
        return array_filter($grouped_caps, function($caps) {
            return !empty($caps);
        });
    }
    
    /**
     * Save role settings
     */
    public static function save_role_settings($role_key, $settings) {
        $all_settings = get_option('frm_role_settings', array());
        $all_settings[$role_key] = $settings;
        update_option('frm_role_settings', $all_settings);
    }
    
    /**
     * Get role settings
     */
    public static function get_role_settings($role_key = null) {
        $settings = get_option('frm_role_settings', array());
        
        if ($role_key) {
            return isset($settings[$role_key]) ? $settings[$role_key] : array();
        }
        
        return $settings;
    }
    
    /**
     * Get users with a specific role
     */
    public static function get_users_with_role($role_key) {
        $users = get_users(array(
            'role' => $role_key,
            'fields' => array('ID', 'user_login', 'user_email', 'display_name', 'user_registered')
        ));
        
        return $users;
    }
    
    /**
     * Get all users grouped by role
     */
    public static function get_all_users_by_role() {
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        $users_by_role = array();
        
        foreach ($wp_roles->roles as $role_key => $role_data) {
            $users = self::get_users_with_role($role_key);
            $users_by_role[$role_key] = array(
                'name' => $role_data['name'],
                'users' => $users,
                'count' => count($users)
            );
        }
        
        return $users_by_role;
    }
    
    /**
     * Get role visibility settings
     */
    public static function get_role_visibility_settings() {
        return get_option('frm_role_visibility', array());
    }
    
    /**
     * Save role visibility settings
     */
    public static function save_role_visibility_settings($settings) {
        update_option('frm_role_visibility', $settings);
    }
    
    /**
     * Check if a role is visible/enabled
     */
    public static function is_role_visible($role_key) {
        $visibility_settings = self::get_role_visibility_settings();
        return isset($visibility_settings[$role_key]) ? $visibility_settings[$role_key] : true;
    }
    
    /**
     * Get visible roles only
     */
    public static function get_visible_roles() {
        $all_roles = self::get_all_roles();
        $visible_roles = array();
        
        foreach ($all_roles as $role_key => $role_data) {
            if (self::is_role_visible($role_key)) {
                $visible_roles[$role_key] = $role_data;
            }
        }
        
        return $visible_roles;
    }
    
    /**
     * Get role statistics
     */
    public static function get_role_statistics() {
        $users_by_role = self::get_all_users_by_role();
        $stats = array(
            'total_roles' => count($users_by_role),
            'total_users' => 0,
            'roles_with_users' => 0,
            'empty_roles' => 0
        );
        
        foreach ($users_by_role as $role_data) {
            $stats['total_users'] += $role_data['count'];
            if ($role_data['count'] > 0) {
                $stats['roles_with_users']++;
            } else {
                $stats['empty_roles']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Get default role categories
     */
    public static function get_default_role_categories() {
        return array(
            'core' => array(
                'name' => 'Core WordPress',
                'description' => 'Default WordPress roles',
                'color' => '#0073aa',
                'icon' => 'dashicons-wordpress'
            ),
            'learning' => array(
                'name' => 'Learning & Education',
                'description' => 'Roles related to courses and learning',
                'color' => '#00a32a',
                'icon' => 'dashicons-welcome-learn-more'
            ),
            'community' => array(
                'name' => 'Community & Social',
                'description' => 'Roles for community features and social interaction',
                'color' => '#d63638',
                'icon' => 'dashicons-groups'
            )
        );
    }
    
    /**
     * Get role categories
     */
    public static function get_role_categories() {
        $default_categories = self::get_default_role_categories();
        $saved_categories = get_option('frm_role_categories', array());
        $removed = get_option('frm_role_categories_removed', array());
        if (!is_array($saved_categories)) { $saved_categories = array(); }
        if (!is_array($removed)) { $removed = array(); }

        // Remove any keys listed as removed from defaults and saved
        foreach ($removed as $rm_key) {
            unset($default_categories[$rm_key]);
            unset($saved_categories[$rm_key]);
        }

        // Merge default categories with saved ones
        return array_merge($default_categories, $saved_categories);
    }
    
    /**
     * Save role categories
     */
    public static function save_role_categories($categories) {
        update_option('frm_role_categories', $categories);
    }
    
    /**
     * Get role category assignments
     */
    public static function get_role_category_assignments() {
        return get_option('frm_role_category_assignments', array());
    }
    
    /**
     * Save role category assignments
     */
    public static function save_role_category_assignments($assignments) {
        update_option('frm_role_category_assignments', $assignments);
    }
    
    /**
     * Get roles grouped by category
     */
    public static function get_roles_by_category() {
        $roles = self::get_all_roles();
        $categories = self::get_role_categories();
        $assignments = self::get_role_category_assignments();
        
        $grouped_roles = array();
        
        // Initialize categories
        foreach ($categories as $category_key => $category_data) {
            $grouped_roles[$category_key] = array(
                'category' => $category_data,
                'roles' => array()
            );
        }
        
        // Assign roles to categories
        foreach ($roles as $role_key => $role_data) {
            $category_key = isset($assignments[$role_key]) ? $assignments[$role_key] : 'community';
            
            // Auto-assign core WordPress roles
            if (in_array($role_key, array('administrator', 'editor', 'author', 'contributor', 'subscriber'))) {
                $category_key = 'core';
            }
            
            // Auto-assign LearnDash roles
            if (strpos($role_key, 'learndash') !== false || strpos($role_key, 'course') !== false || strpos($role_key, 'lesson') !== false) {
                $category_key = 'learning';
            }
            
            // Auto-assign BuddyBoss roles
            if (strpos($role_key, 'bp_') !== false || strpos($role_key, 'bbp_') !== false || strpos($role_key, 'participant') !== false) {
                $category_key = 'community';
            }
            
            // Auto-assign WooCommerce roles
            if (strpos($role_key, 'woocommerce') !== false || strpos($role_key, 'customer') !== false || strpos($role_key, 'shop') !== false) {
                $category_key = 'commerce';
            }
            
            if (!isset($grouped_roles[$category_key])) {
                // Use community as fallback if category doesn't exist
                $fallback_category = isset($categories[$category_key]) ? $categories[$category_key] : $categories['community'];
                $grouped_roles[$category_key] = array(
                    'category' => $fallback_category,
                    'roles' => array()
                );
            }
            
            $grouped_roles[$category_key]['roles'][$role_key] = $role_data;
        }
        
        // Remove empty categories
        return array_filter($grouped_roles, function($category_data) {
            return !empty($category_data['roles']);
        });
    }
    
    /**
     * Get category statistics
     */
    public static function get_category_statistics() {
        $roles_by_category = self::get_roles_by_category();
        $stats = array();
        
        foreach ($roles_by_category as $category_key => $category_data) {
            $total_users = 0;
            $roles_with_users = 0;
            
            foreach ($category_data['roles'] as $role_key => $role_data) {
                $user_count = count(self::get_users_with_role($role_key));
                $total_users += $user_count;
                if ($user_count > 0) {
                    $roles_with_users++;
                }
            }
            
            $stats[$category_key] = array(
                'name' => $category_data['category']['name'],
                'role_count' => count($category_data['roles']),
                'user_count' => $total_users,
                'active_roles' => $roles_with_users
            );
        }
        
        return $stats;
    }
}