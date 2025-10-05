<?php
/**
 * Membership-Specific Caching Examples
 * 
 * This file demonstrates how to use the role-based caching system
 * for membership sites with different user levels and permissions.
 * 
 * @package FLI BuddyBoss Child
 * @version 1.0.0
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Example: Cache course progress for LearnDash users
 */
function fli_cache_course_progress($user_id, $course_id) {
    $cache_key = "course_progress_{$course_id}";
    
    return fli_cache_remember_for_user($cache_key, function() use ($user_id, $course_id) {
        // Expensive operation: Get course progress
        if (function_exists('learndash_user_get_course_progress')) {
            return learndash_user_get_course_progress($user_id, $course_id);
        }
        return false;
    }, 1800, 'learndash'); // Cache for 30 minutes
}

/**
 * Example: Cache user permissions based on membership level
 */
function fli_cache_user_permissions($user_id) {
    $cache_key = "user_permissions_{$user_id}";
    
    return fli_cache_remember_for_user($cache_key, function() use ($user_id) {
        $permissions = [];
        
        // Check WordPress capabilities
        $user = get_user_by('id', $user_id);
        if ($user) {
            $permissions['wp_capabilities'] = $user->allcaps;
        }
        
        // Check LearnDash access
        if (function_exists('learndash_user_get_active_courses')) {
            $permissions['learndash_courses'] = learndash_user_get_active_courses($user_id);
        }
        
        // Check MemberPress membership
        if (class_exists('MeprUser')) {
            $mepr_user = new MeprUser($user_id);
            $permissions['memberpress_memberships'] = $mepr_user->active_product_subscriptions('ids', true);
        }
        
        // Check WooCommerce Memberships
        if (function_exists('wc_memberships_get_user_memberships')) {
            $memberships = wc_memberships_get_user_memberships($user_id);
            $permissions['woocommerce_memberships'] = wp_list_pluck($memberships, 'id');
        }
        
        return $permissions;
    }, 3600, 'permissions'); // Cache for 1 hour
}

/**
 * Example: Cache content visibility based on user role
 */
function fli_cache_content_visibility($post_id, $user_id) {
    $cache_key = "content_visibility_{$post_id}";
    
    return fli_cache_remember_for_user($cache_key, function() use ($post_id, $user_id) {
        $visibility = [
            'can_view' => false,
            'restriction_reason' => '',
            'access_level' => 'none'
        ];
        
        // Check if user can view the post
        if (current_user_can('read_post', $post_id)) {
            $visibility['can_view'] = true;
            $visibility['access_level'] = 'full';
        } else {
            // Check for membership restrictions
            $post_meta = get_post_meta($post_id, '_fli_membership_required', true);
            if ($post_meta) {
                $user_permissions = fli_cache_user_permissions($user_id);
                
                if (in_array($post_meta, $user_permissions['memberpress_memberships'] ?? [])) {
                    $visibility['can_view'] = true;
                    $visibility['access_level'] = 'member';
                } else {
                    $visibility['restriction_reason'] = 'membership_required';
                }
            }
        }
        
        return $visibility;
    }, 1800, 'content'); // Cache for 30 minutes
}

/**
 * Example: Cache user dashboard data
 */
function fli_cache_user_dashboard_data($user_id) {
    $cache_key = "dashboard_data_{$user_id}";
    
    return fli_cache_remember_for_user($cache_key, function() use ($user_id) {
        $dashboard_data = [];
        
        // Get user info
        $user = get_user_by('id', $user_id);
        if ($user) {
            $dashboard_data['user_info'] = [
                'display_name' => $user->display_name,
                'email' => $user->user_email,
                'roles' => $user->roles,
                'registration_date' => $user->user_registered
            ];
        }
        
        // Get LearnDash progress
        if (function_exists('learndash_user_get_course_progress')) {
            $courses = learndash_user_get_active_courses($user_id);
            $dashboard_data['learndash_progress'] = [];
            
            foreach ($courses as $course_id) {
                $progress = learndash_user_get_course_progress($user_id, $course_id);
                $dashboard_data['learndash_progress'][$course_id] = $progress;
            }
        }
        
        // Get recent activity
        $dashboard_data['recent_activity'] = get_user_meta($user_id, 'recent_activity', true) ?: [];
        
        return $dashboard_data;
    }, 900, 'dashboard'); // Cache for 15 minutes
}

/**
 * Example: Cache membership level checks
 */
function fli_cache_membership_level($user_id) {
    $cache_key = "membership_level_{$user_id}";
    
    return fli_cache_remember_for_user($cache_key, function() use ($user_id) {
        $level = 'free';
        
        // Check MemberPress
        if (class_exists('MeprUser')) {
            $mepr_user = new MeprUser($user_id);
            $memberships = $mepr_user->active_product_subscriptions('ids', true);
            if (!empty($memberships)) {
                $level = 'premium';
            }
        }
        
        // Check WooCommerce Memberships
        if (function_exists('wc_memberships_get_user_memberships')) {
            $memberships = wc_memberships_get_user_memberships($user_id);
            if (!empty($memberships)) {
                $level = 'member';
            }
        }
        
        // Check custom membership meta
        $custom_level = get_user_meta($user_id, 'membership_level', true);
        if ($custom_level) {
            $level = $custom_level;
        }
        
        return $level;
    }, 3600, 'membership'); // Cache for 1 hour
}

/**
 * Clear user-specific cache when membership changes
 */
add_action('user_register', 'fli_clear_user_cache');
add_action('profile_update', 'fli_clear_user_cache');
add_action('set_user_role', 'fli_clear_user_cache');

function fli_clear_user_cache($user_id) {
    // Clear all user-specific cache
    $cache_groups = ['menu', 'permissions', 'content', 'dashboard', 'membership', 'learndash'];
    
    foreach ($cache_groups as $group) {
        fli_cache_clear_for_role('', $group); // Clear all roles for this user
    }
    
    fli_log_info("User cache cleared for user ID: {$user_id}", ['user_id' => $user_id], 'FLI Cache');
}

/**
 * Clear cache when membership status changes
 */
add_action('mepr_membership_status_changed', 'fli_clear_membership_cache');
add_action('wc_memberships_user_membership_status_changed', 'fli_clear_membership_cache');

function fli_clear_membership_cache($membership) {
    $user_id = $membership->user_id ?? $membership->get_user_id();
    
    if ($user_id) {
        fli_clear_user_cache($user_id);
        fli_log_info("Membership cache cleared due to status change", [
            'user_id' => $user_id,
            'membership_id' => $membership->id ?? $membership->get_id()
        ], 'FLI Cache');
    }
}

/**
 * Usage Examples:
 * 
 * // Get cached course progress
 * $progress = fli_cache_course_progress($user_id, $course_id);
 * 
 * // Get cached user permissions
 * $permissions = fli_cache_user_permissions($user_id);
 * 
 * // Check content visibility
 * $visibility = fli_cache_content_visibility($post_id, $user_id);
 * 
 * // Get dashboard data
 * $dashboard = fli_cache_user_dashboard_data($user_id);
 * 
 * // Get membership level
 * $level = fli_cache_membership_level($user_id);
 * 
 * // Manual cache clearing
 * fli_cache_clear_for_role('subscriber', 'content');
 * fli_cache_clear_for_role('premium_member', 'dashboard');
 */
