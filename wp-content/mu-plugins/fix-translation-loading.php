<?php
/**
 * Plugin Name: Fix Translation Loading Warnings
 * Description: Suppresses "translation loading too early" warnings and ensures translations load properly
 * Version: 1.0.1
 * Author: Fearless You Development Team
 *
 * Fixes WordPress 6.7+ warnings for plugins that load translations too early.
 * This mu-plugin suppresses the warnings (which are informational only)
 * and ensures translations work correctly.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Suppress early translation loading warnings
 *
 * These warnings occur when plugins call translation functions before the 'init' action.
 * The warnings don't affect functionality - translations still work - they just clutter logs.
 * This is a WordPress 6.7+ strictness change that many plugins haven't updated for yet.
 */
add_filter('doing_it_wrong_trigger_error', function($trigger, $function_name, $message, $version) {
    // Only suppress the textdomain loading warning
    if ($function_name === '_load_textdomain_just_in_time') {
        return false;
    }

    return $trigger;
}, 10, 4);

/**
 * Ensure translations are available when needed
 * This loads translations at the proper time as a fallback
 */
add_action('init', 'fearless_ensure_translations_loaded', 1);

function fearless_ensure_translations_loaded() {
    // BuddyBoss Platform
    if (defined('BP_PLATFORM_VERSION') && !is_textdomain_loaded('buddyboss')) {
        load_plugin_textdomain('buddyboss', false, 'buddyboss-platform/languages');
    }

    // Uncanny Automator
    if (defined('AUTOMATOR_PLUGIN_VERSION') && !is_textdomain_loaded('uncanny-automator')) {
        load_plugin_textdomain('uncanny-automator', false, 'uncanny-automator/languages');
    }

    // LCCP Systems
    if (defined('LCCP_SYSTEMS_VERSION') && !is_textdomain_loaded('lccp-systems')) {
        load_plugin_textdomain('lccp-systems', false, 'lccp-systems/languages');
    }

    // BuddyBoss Theme (if active)
    if (function_exists('buddyboss_theme') && !is_textdomain_loaded('buddyboss-theme')) {
        load_theme_textdomain('buddyboss-theme', get_template_directory() . '/languages');
    }
}
