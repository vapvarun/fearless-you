<?php
/**
 * Plugin Name: Fix Translation Loading Warnings
 * Description: Suppresses "translation loaded too early" warnings from third-party plugins and ensures proper translation loading timing
 * Version: 1.0.0
 * Author: LCCP Systems
 *
 * This MU plugin fixes PHP notices about translations being loaded too early,
 * which commonly occur with third-party plugins like Uncanny Automator.
 *
 * Must-Use Plugin - loads before all regular plugins
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Fix_Translation_Warnings {

    private $suppressed_domains = array();
    private $early_text_domains = array();

    public function __construct() {
        // Define which plugin text domains commonly cause early loading issues
        $this->suppressed_domains = array(
            'uncanny-automator',
            'buddyboss',
            'learndash',
            'woocommerce',
        );

        // Hook early to intercept translation loading
        add_action('plugins_loaded', array($this, 'setup_translation_fixes'), -9999);

        // Filter doing_it_wrong notices for translation loading
        add_filter('doing_it_wrong_trigger_error', array($this, 'filter_translation_warnings'), 10, 4);

        // Ensure translations load at proper time
        add_action('init', array($this, 'load_delayed_translations'), 1);
    }

    /**
     * Setup translation fixes before plugins load
     */
    public function setup_translation_fixes() {
        // Store any early-loaded text domains for later proper loading
        add_filter('override_load_textdomain', array($this, 'intercept_early_translations'), 10, 3);
    }

    /**
     * Intercept early translation loading and delay until init
     */
    public function intercept_early_translations($override, $domain, $mofile) {
        // Only intercept if we're before 'init' action
        if (!did_action('init') && in_array($domain, $this->suppressed_domains)) {
            // Store for later loading
            if (!isset($this->early_text_domains[$domain])) {
                $this->early_text_domains[$domain] = array();
            }
            $this->early_text_domains[$domain][] = $mofile;

            // Return true to prevent the early load
            return true;
        }

        return $override;
    }

    /**
     * Load delayed translations at proper init time
     */
    public function load_delayed_translations() {
        foreach ($this->early_text_domains as $domain => $mofiles) {
            foreach ($mofiles as $mofile) {
                load_textdomain($domain, $mofile);
            }
        }

        // Clear the stored translations
        $this->early_text_domains = array();
    }

    /**
     * Filter out translation "doing it wrong" warnings
     */
    public function filter_translation_warnings($trigger, $function, $message, $version) {
        // Check if this is a translation loading warning
        if ($function === '_load_textdomain_just_in_time') {
            // Extract domain from message
            foreach ($this->suppressed_domains as $domain) {
                if (strpos($message, $domain) !== false) {
                    // Suppress this specific warning
                    return false;
                }
            }
        }

        return $trigger;
    }

    /**
     * Add a domain to suppression list
     */
    public function add_suppressed_domain($domain) {
        if (!in_array($domain, $this->suppressed_domains)) {
            $this->suppressed_domains[] = $domain;
        }
    }

    /**
     * Remove a domain from suppression list
     */
    public function remove_suppressed_domain($domain) {
        $key = array_search($domain, $this->suppressed_domains);
        if ($key !== false) {
            unset($this->suppressed_domains[$key]);
        }
    }

    /**
     * Get list of suppressed domains
     */
    public function get_suppressed_domains() {
        return $this->suppressed_domains;
    }
}

// Initialize the fix
new LCCP_Fix_Translation_Warnings();

/**
 * Optional: Completely suppress all translation timing warnings in development
 * Uncomment the code below if you want to completely disable these notices
 */
/*
add_filter('doing_it_wrong_trigger_error', function($trigger, $function) {
    if ($function === '_load_textdomain_just_in_time') {
        return false;
    }
    return $trigger;
}, 10, 2);
*/

/**
 * Alternative approach: Modify error reporting to exclude notices
 * Uncomment if the above doesn't work and you want to silence all notices
 */
/*
add_action('init', function() {
    // Only affect frontend and admin (not wp-cli or REST API)
    if (!defined('WP_CLI') && !defined('REST_REQUEST')) {
        // Keep errors and warnings, but remove notices
        error_reporting(E_ERROR | E_WARNING | E_PARSE);
    }
}, 0);
*/
