<?php
/**
 * Dashboard Access Control
 * 
 * Controls access to dashboard pages based on user capabilities
 *
 * @package Dasher
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Restrict access to dashboard pages
 */
add_action('template_redirect', 'dasher_restrict_dashboard_access');
function dasher_restrict_dashboard_access() {
    // Check if we're on a dashboard page
    if (is_page('dashboard-m')) {
        // Mentor Dashboard - check capability
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        }
        
        if (!current_user_can('view_all_student_progress')) {
            wp_die(__('You do not have permission to access this dashboard.', 'dasher'), __('Access Denied', 'dasher'), array('response' => 403));
        }
    }
    
    if (is_page('dashboard-bb')) {
        // BigBird Dashboard - check capability
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        }
        
        if (!current_user_can('view_assigned_student_progress')) {
            wp_die(__('You do not have permission to access this dashboard.', 'dasher'), __('Access Denied', 'dasher'), array('response' => 403));
        }
    }
}

/**
 * Add body classes for dashboard pages
 */
add_filter('body_class', 'dasher_dashboard_body_classes');
function dasher_dashboard_body_classes($classes) {
    if (is_page('dashboard-m')) {
        $classes[] = 'dasher-mentor-dashboard-page';
    }
    
    if (is_page('dashboard-bb')) {
        $classes[] = 'dasher-big-bird-dashboard-page';
    }
    
    return $classes;
}

/**
 * Hide admin bar for non-admin users on dashboard pages
 */
add_filter('show_admin_bar', 'dasher_dashboard_admin_bar');
function dasher_dashboard_admin_bar($show) {
    if ((is_page('dashboard-m') || is_page('dashboard-bb')) && !current_user_can('manage_options')) {
        return false;
    }
    return $show;
}

/**
 * Hide admin-only menu items from non-admin users
 */
add_filter('wp_nav_menu_objects', 'dasher_hide_admin_menu_items', 10, 2);
function dasher_hide_admin_menu_items($items, $args) {
    // Only filter if user is not an admin
    if (current_user_can('manage_options')) {
        return $items;
    }
    
    // List of admin-only menu item IDs
    $admin_only_items = array(
        226162, // Continue...
        226165, // Admin Dashboard
        226166, // Edit This Post
    );
    
    // Remove admin-only items for non-admin users
    foreach ($items as $key => $item) {
        if (in_array($item->ID, $admin_only_items)) {
            unset($items[$key]);
        }
    }
    
    return $items;
}

/**
 * Redirect users after login based on their role
 */
add_filter('login_redirect', 'dasher_role_based_login_redirect', 10, 3);
function dasher_role_based_login_redirect($redirect_to, $request, $user) {
    // Check if we have a user object
    if (isset($user->roles) && is_array($user->roles)) {
        // Check for mentor role
        if (in_array('dasher_mentor', $user->roles) || in_array('mentor', $user->roles)) {
            return home_url('/dashboard-m/');
        }
        
        // Check for bigbird role
        if (in_array('dasher_bigbird', $user->roles) || in_array('bigbird', $user->roles)) {
            return home_url('/dashboard-bb/');
        }
    }
    
    return $redirect_to;
}

/**
 * Add dashboard switcher to admin bar for administrators
 */
add_action('admin_bar_menu', 'dasher_add_dashboard_switcher', 100);
function dasher_add_dashboard_switcher($wp_admin_bar) {
    // Only show for administrators
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Add parent node
    $wp_admin_bar->add_node(array(
        'id'    => 'dasher-view-as',
        'title' => '<span class="ab-icon dashicons dashicons-visibility"></span><span class="ab-label">View Dashboard As</span>',
        'href'  => '#',
        'meta'  => array(
            'title' => __('Switch between dashboard views', 'dasher'),
        ),
    ));
    
    // Add Mentor Dashboard
    $wp_admin_bar->add_node(array(
        'id'     => 'dasher-view-mentor',
        'parent' => 'dasher-view-as',
        'title'  => __('Mentor Dashboard', 'dasher'),
        'href'   => home_url('/dashboard-m/'),
        'meta'   => array(
            'title' => __('View the Mentor Dashboard', 'dasher'),
        ),
    ));
    
    // Add BigBird Dashboard
    $wp_admin_bar->add_node(array(
        'id'     => 'dasher-view-bigbird',
        'parent' => 'dasher-view-as',
        'title'  => __('BigBird Dashboard', 'dasher'),
        'href'   => home_url('/dashboard-bb/'),
        'meta'   => array(
            'title' => __('View the BigBird Dashboard', 'dasher'),
        ),
    ));
}

/**
 * Add CSS for the dashboard switcher icon
 */
add_action('wp_head', 'dasher_dashboard_switcher_styles');
add_action('admin_head', 'dasher_dashboard_switcher_styles');
function dasher_dashboard_switcher_styles() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <style>
        #wpadminbar #wp-admin-bar-dasher-view-as .ab-icon:before {
            content: "\f177";
            top: 2px;
        }
        #wpadminbar #wp-admin-bar-dasher-view-as .ab-label {
            margin-left: 6px;
        }
    </style>
    <?php
}