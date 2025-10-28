<?php
/**
 * Performance Optimizer Module for LCCP Systems
 * 
 * IMPORTANT: This module has been fixed to not interfere with LearnDash's Mark Complete functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Performance_Optimizer {

    private $settings;
    private $cleanup_lock_key = 'lccp_cleanup_in_progress';
    private $last_cleanup_key = 'lccp_last_cleanup_time';

    public function __construct() {
        $this->settings = get_option('lccp_performance_settings', $this->get_default_settings());

        // Initialize optimizations
        add_action('init', array($this, 'init_optimizations'));

        // Admin settings
        add_action('admin_init', array($this, 'register_settings'));

        // Scheduled cleanup with permission check
        add_action('lccp_systems_daily_cleanup', array($this, 'daily_cleanup'));

        // Admin security notices
        add_action('admin_notices', array($this, 'show_optimization_warnings'));
    }
    
    public function init_optimizations() {
        // Database optimizations
        if ($this->settings['optimize_database']) {
            $this->init_database_optimizations();
        }
        
        // Object cache optimizations
        if ($this->settings['optimize_object_cache']) {
            $this->init_object_cache_optimizations();
        }
        
        // Query optimizations
        if ($this->settings['optimize_queries']) {
            $this->init_query_optimizations();
        }
        
        // Frontend optimizations
        if ($this->settings['optimize_frontend']) {
            $this->init_frontend_optimizations();
        }
        
        // Cleanup optimizations
        if ($this->settings['optimize_cleanup']) {
            $this->init_cleanup_optimizations();
        }
    }
    
    private function init_database_optimizations() {
        // Optimize queries for better performance
        add_action('pre_get_posts', array($this, 'optimize_queries'));
        
        // Clean up database periodically
        add_action('wp_scheduled_delete', array($this, 'cleanup_database'));
        
        // Optimize autoloaded options on admin init
        add_action('admin_init', array($this, 'optimize_autoloaded_options'));
    }
    
    private function init_object_cache_optimizations() {
        // Cache LearnDash progress data
        add_filter('learndash_course_progress', array($this, 'cache_course_progress'), 10, 2);
        
        // Setup persistent cache if available
        if (!wp_using_ext_object_cache()) {
            $this->setup_persistent_cache();
        }
    }
    
    private function init_query_optimizations() {
        // Limit post revisions
        if (!defined('WP_POST_REVISIONS')) {
            define('WP_POST_REVISIONS', 3);
        }
        
        // Disable unnecessary features BUT keep REST API for LearnDash
        $this->disable_unnecessary_features();
    }
    
    private function init_frontend_optimizations() {
        // Defer non-critical JavaScript
        add_filter('script_loader_tag', array($this, 'defer_scripts'), 10, 3);
        
        // Remove unnecessary header items
        $this->remove_header_bloat();
        
        // Optimize emojis and embeds
        if ($this->settings['disable_emojis']) {
            $this->disable_emojis();
        }
        
        if ($this->settings['disable_embeds']) {
            $this->disable_embeds();
        }
    }
    
    private function init_cleanup_optimizations() {
        // Clean up transients periodically
        add_action('wp_scheduled_delete', array($this, 'cleanup_expired_transients'));
    }
    
    /**
     * FIXED: Disable unnecessary features without breaking LearnDash
     */
    private function disable_unnecessary_features() {
        // Disable XML-RPC (not needed for LearnDash)
        add_filter('xmlrpc_enabled', '__return_false');
        
        // IMPORTANT: Do NOT disable REST API entirely!
        // LearnDash needs REST API for Mark Complete functionality
        // Instead, only protect sensitive endpoints
        add_filter('rest_authentication_errors', function($result) {
            // Allow all LearnDash endpoints
            if (isset($_SERVER['REQUEST_URI']) && (
                strpos($_SERVER['REQUEST_URI'], 'learndash') !== false ||
                strpos($_SERVER['REQUEST_URI'], 'sfwd') !== false ||
                strpos($_SERVER['REQUEST_URI'], 'ld-') !== false
            )) {
                return $result; // Don't block LearnDash requests
            }
            
            // Allow logged-in users full access
            if (is_user_logged_in()) {
                return $result;
            }
            
            // Only block non-LearnDash endpoints for non-logged users
            // Check if this is a sensitive endpoint
            $blocked_namespaces = array('wp/v2/users', 'wp/v2/comments');
            $current_route = $this->get_current_rest_route();
            
            foreach ($blocked_namespaces as $namespace) {
                if (strpos($current_route, $namespace) === 0) {
                    return new WP_Error(
                        'rest_forbidden',
                        'REST API access restricted',
                        array('status' => 401)
                    );
                }
            }
            
            return $result;
        });
        
        // Disable pingbacks
        add_filter('pings_open', '__return_false');
        add_filter('xmlrpc_methods', function($methods) {
            unset($methods['pingback.ping']);
            unset($methods['pingback.extensions.getPingbacks']);
            return $methods;
        });
    }
    
    private function get_current_rest_route() {
        $rest_route = $GLOBALS['wp']->query_vars['rest_route'] ?? '';
        return trim($rest_route, '/');
    }
    
    public function optimize_queries($query) {
        if (!is_admin() && $query->is_main_query()) {
            // Limit posts per page for archives
            if ($query->is_home() || $query->is_archive()) {
                $query->set('posts_per_page', 12);
            }
            
            // Disable found rows calculation for singular posts
            if ($query->is_singular()) {
                $query->set('no_found_rows', true);
            }
        }
    }
    
    public function cache_course_progress($progress, $args) {
        if (empty($args['user_id']) || empty($args['course_id'])) {
            return $progress;
        }
        
        $cache_key = 'lccp_course_progress_' . $args['user_id'] . '_' . $args['course_id'];
        $cached_progress = wp_cache_get($cache_key, 'lccp_learndash');
        
        if ($cached_progress === false) {
            wp_cache_set($cache_key, $progress, 'lccp_learndash', 1800); // Cache for 30 minutes
        }
        
        return $progress;
    }
    
    public function defer_scripts($tag, $handle, $src) {
        // Don't defer LearnDash scripts or critical scripts
        $no_defer = array(
            'jquery',
            'jquery-core',
            'learndash',
            'learndash-front',
            'learndash_template_script_js',
            'sfwd-lms-script'
        );
        
        if (in_array($handle, $no_defer)) {
            return $tag;
        }
        
        // Scripts to defer
        $defer_scripts = array(
            'jquery-migrate',
            'buddyboss-theme-js',
            'bp-nouveau'
        );
        
        if (in_array($handle, $defer_scripts)) {
            return str_replace(' src', ' defer src', $tag);
        }
        
        return $tag;
    }
    
    private function remove_header_bloat() {
        // Remove unnecessary meta tags
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
        
        // Remove version strings from assets
        add_filter('style_loader_src', array($this, 'remove_version_strings'), 9999);
        add_filter('script_loader_src', array($this, 'remove_version_strings'), 9999);
    }
    
    private function disable_emojis() {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        add_filter('tiny_mce_plugins', function($plugins) {
            return is_array($plugins) ? array_diff($plugins, array('wpemoji')) : array();
        });
    }
    
    private function disable_embeds() {
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');
        add_filter('embed_oembed_discover', '__return_false');
        remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
        remove_action('rest_api_init', 'wp_oembed_register_route');
        add_filter('rewrite_rules_array', function($rules) {
            foreach($rules as $rule => $rewrite) {
                if(strpos($rewrite, 'embed=true') !== false) {
                    unset($rules[$rule]);
                }
            }
            return $rules;
        });
    }
    
    public function cleanup_database() {
        global $wpdb;

        // Security: Only run if explicitly enabled and scheduled properly
        if (!$this->settings['optimize_cleanup']) {
            return false;
        }

        // Prevent concurrent cleanup operations
        if (get_transient($this->cleanup_lock_key)) {
            error_log('[LCCP Performance] Cleanup already in progress, skipping...');
            return false;
        }

        // Rate limiting: Only run once per day
        $last_cleanup = get_option($this->last_cleanup_key);
        if ($last_cleanup && (time() - $last_cleanup) < DAY_IN_SECONDS) {
            return false;
        }

        // Set lock
        set_transient($this->cleanup_lock_key, true, HOUR_IN_SECONDS);

        try {
            $stats = array(
                'spam_comments' => 0,
                'revisions' => 0,
                'orphaned_postmeta' => 0,
                'orphaned_usermeta' => 0
            );

            // Clean up spam comments (with limit for safety)
            $stats['spam_comments'] = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->comments} WHERE comment_approved = %s LIMIT 1000",
                    'spam'
                )
            );

            // Clean up old revisions (keep only 3 most recent) - Fixed query
            $stats['revisions'] = $wpdb->query("
                DELETE p1 FROM {$wpdb->posts} p1
                LEFT JOIN (
                    SELECT p2.ID, p2.post_parent,
                           (SELECT COUNT(*) FROM {$wpdb->posts} p3
                            WHERE p3.post_parent = p2.post_parent
                            AND p3.post_type = 'revision'
                            AND p3.post_date >= p2.post_date) as row_num
                    FROM {$wpdb->posts} p2
                    WHERE p2.post_type = 'revision'
                ) AS ranked ON p1.ID = ranked.ID
                WHERE p1.post_type = 'revision'
                AND ranked.row_num > 3
                LIMIT 1000
            ");

            // Clean up orphaned post meta (with limit for safety)
            $stats['orphaned_postmeta'] = $wpdb->query("
                DELETE pm FROM {$wpdb->postmeta} pm
                LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE p.ID IS NULL
                LIMIT 1000
            ");

            // Clean up orphaned user meta (with limit for safety)
            $stats['orphaned_usermeta'] = $wpdb->query("
                DELETE um FROM {$wpdb->usermeta} um
                LEFT JOIN {$wpdb->users} u ON um.user_id = u.ID
                WHERE u.ID IS NULL
                LIMIT 1000
            ");

            // Log successful cleanup
            $this->log_cleanup_event('Database cleanup completed', $stats);

            // Update last cleanup time
            update_option($this->last_cleanup_key, time());

            // Send admin notification
            $this->send_cleanup_notification($stats);

            return $stats;

        } catch (Exception $e) {
            error_log('[LCCP Performance] Database cleanup failed: ' . $e->getMessage());
            $this->send_cleanup_error_notification($e->getMessage());
            return false;

        } finally {
            // Always release the lock
            delete_transient($this->cleanup_lock_key);
        }
    }

    /**
     * Log cleanup events
     */
    private function log_cleanup_event($event, $stats = array()) {
        $log_entry = sprintf(
            '[LCCP Performance] %s - Spam: %d, Revisions: %d, Orphaned Meta: %d/%d at %s',
            $event,
            $stats['spam_comments'] ?? 0,
            $stats['revisions'] ?? 0,
            $stats['orphaned_postmeta'] ?? 0,
            $stats['orphaned_usermeta'] ?? 0,
            current_time('mysql')
        );

        error_log($log_entry);

        // Store in database log
        $log = get_option('lccp_performance_cleanup_log', array());
        $log[] = array(
            'event' => $event,
            'stats' => $stats,
            'timestamp' => current_time('mysql')
        );

        // Keep only last 50 entries
        if (count($log) > 50) {
            $log = array_slice($log, -50);
        }

        update_option('lccp_performance_cleanup_log', $log);
    }

    /**
     * Send cleanup notification to admin
     */
    private function send_cleanup_notification($stats) {
        // Only send if significant cleanup occurred
        $total_cleaned = array_sum($stats);
        if ($total_cleaned < 100) {
            return; // Don't spam admin for small cleanups
        }

        $admin_email = get_option('admin_email');
        $subject = '[LCCP Performance] Database Cleanup Report';
        $message = sprintf(
            "Database cleanup completed successfully:\n\n" .
            "Spam Comments Removed: %d\n" .
            "Old Revisions Removed: %d\n" .
            "Orphaned Post Meta Removed: %d\n" .
            "Orphaned User Meta Removed: %d\n\n" .
            "Total Records Cleaned: %d\n" .
            "Time: %s",
            $stats['spam_comments'],
            $stats['revisions'],
            $stats['orphaned_postmeta'],
            $stats['orphaned_usermeta'],
            $total_cleaned,
            current_time('mysql')
        );

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Send cleanup error notification
     */
    private function send_cleanup_error_notification($error) {
        $admin_email = get_option('admin_email');
        $subject = '[LCCP Performance] Database Cleanup Error';
        $message = sprintf(
            "Database cleanup encountered an error:\n\n%s\n\nTime: %s",
            $error,
            current_time('mysql')
        );

        wp_mail($admin_email, $subject, $message);
    }
    
    public function cleanup_expired_transients() {
        global $wpdb;

        // Rate limiting: Only run once per day
        $last_transient_cleanup = get_transient('lccp_last_transient_cleanup');
        if ($last_transient_cleanup) {
            return false;
        }

        set_transient('lccp_last_transient_cleanup', true, DAY_IN_SECONDS);

        try {
            // Delete expired transients (with limit for safety)
            $expired_count = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options}
                    WHERE option_name LIKE %s
                    AND option_value < UNIX_TIMESTAMP()
                    LIMIT 1000",
                    '_transient_timeout_%'
                )
            );

            // Delete corresponding transient values
            $wpdb->query("
                DELETE FROM {$wpdb->options}
                WHERE option_name LIKE '_transient_%'
                AND option_name NOT LIKE '_transient_timeout_%'
                AND CONCAT('_transient_timeout_', SUBSTRING(option_name, 12)) NOT IN (
                    SELECT option_name FROM (
                        SELECT option_name FROM {$wpdb->options}
                        WHERE option_name LIKE '_transient_timeout_%'
                    ) AS tmp
                )
                LIMIT 1000
            ");

            error_log('[LCCP Performance] Cleaned up ' . $expired_count . ' expired transients');

            return $expired_count;

        } catch (Exception $e) {
            error_log('[LCCP Performance] Transient cleanup failed: ' . $e->getMessage());
            return false;
        }
    }
    
    public function optimize_autoloaded_options() {
        global $wpdb;

        // Security: Only run for administrators
        if (!current_user_can('manage_options')) {
            return false;
        }

        // Rate limiting: Only run once per week
        $last_autoload_optimization = get_option('lccp_last_autoload_optimization');
        if ($last_autoload_optimization && (time() - $last_autoload_optimization) < WEEK_IN_SECONDS) {
            return false;
        }

        try {
            // Find large autoloaded options
            $large_options = $wpdb->get_results("
                SELECT option_name, LENGTH(option_value) as size
                FROM {$wpdb->options}
                WHERE autoload = 'yes'
                AND LENGTH(option_value) > 100000
                ORDER BY size DESC
                LIMIT 10
            ");

            // Whitelist of safe options to disable autoloading
            $safe_to_disable = array(
                'rewrite_rules', // Can be regenerated
                '_site_transient_browser_', // Browser data
            );

            $optimized_count = 0;

            foreach ($large_options as $option) {
                foreach ($safe_to_disable as $pattern) {
                    if (strpos($option->option_name, $pattern) !== false) {
                    $wpdb->update(
                        $wpdb->options,
                        array('autoload' => 'no'),
                        array('option_name' => $option->option_name)
                    );
                    break;
                }
            }
        }
    }
    
    private function setup_persistent_cache() {
        // Check if Redis is available and not already configured
        if (extension_loaded('redis') && class_exists('Redis')) {
            if (!file_exists(WP_CONTENT_DIR . '/object-cache.php') && 
                file_exists(WP_PLUGIN_DIR . '/redis-cache/includes/object-cache.php')) {
                // If Redis cache plugin is installed, activate it
                @copy(
                    WP_PLUGIN_DIR . '/redis-cache/includes/object-cache.php',
                    WP_CONTENT_DIR . '/object-cache.php'
                );
            }
        }
    }
    
    public function remove_version_strings($src) {
        if (strpos($src, 'ver=')) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
    }
    
    public function daily_cleanup() {
        // Clean database
        $this->cleanup_database();
        
        // Clean transients
        $this->cleanup_expired_transients();
        
        // Optimize tables weekly (on Sunday)
        if (date('w') == 0) {
            $this->optimize_database_tables();
        }
    }
    
    private function optimize_database_tables() {
        global $wpdb;
        
        // Get all database tables
        $tables = $wpdb->get_col("SHOW TABLES");
        
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE {$table}");
        }
    }
    
    public function register_settings() {
        register_setting('lccp_systems_settings', 'lccp_performance_settings');
    }

    /**
     * Show optimization warnings in admin
     */
    public function show_optimization_warnings() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'lccp') === false) {
            return;
        }

        if ($this->settings['optimize_cleanup']) {
            ?>
            <div class="notice notice-info">
                <p>
                    <strong>Performance Optimization Active:</strong> Database cleanup operations are enabled.
                    These operations automatically remove spam, old revisions, and orphaned data.
                    <a href="<?php echo admin_url('admin.php?page=lccp-systems'); ?>">Review Settings</a>
                </p>
            </div>
            <?php
        }
    }

    private function get_default_settings() {
        return array(
            'optimize_database' => true,
            'optimize_object_cache' => true,
            'optimize_queries' => true,
            'optimize_frontend' => true,
            'optimize_cleanup' => true,
            'disable_emojis' => true,
            'disable_embeds' => true,
            'defer_scripts' => true,
            'cleanup_revisions' => true,
            'cleanup_transients' => true
        );
    }
    
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>LCCP Performance Optimization</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('lccp_systems_settings'); ?>
                
                <h2>Performance Settings</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Database Optimization</th>
                        <td>
                            <label>
                                <input type="checkbox" name="lccp_performance_settings[optimize_database]" 
                                       value="1" <?php checked($this->settings['optimize_database'], true); ?> />
                                Enable database optimization
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Object Cache</th>
                        <td>
                            <label>
                                <input type="checkbox" name="lccp_performance_settings[optimize_object_cache]" 
                                       value="1" <?php checked($this->settings['optimize_object_cache'], true); ?> />
                                Enable object cache optimization
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Query Optimization</th>
                        <td>
                            <label>
                                <input type="checkbox" name="lccp_performance_settings[optimize_queries]" 
                                       value="1" <?php checked($this->settings['optimize_queries'], true); ?> />
                                Enable query optimization
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Frontend Optimization</th>
                        <td>
                            <label>
                                <input type="checkbox" name="lccp_performance_settings[optimize_frontend]" 
                                       value="1" <?php checked($this->settings['optimize_frontend'], true); ?> />
                                Enable frontend optimization
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Disable Emojis</th>
                        <td>
                            <label>
                                <input type="checkbox" name="lccp_performance_settings[disable_emojis]" 
                                       value="1" <?php checked($this->settings['disable_emojis'], true); ?> />
                                Disable WordPress emoji support
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Disable Embeds</th>
                        <td>
                            <label>
                                <input type="checkbox" name="lccp_performance_settings[disable_embeds]" 
                                       value="1" <?php checked($this->settings['disable_embeds'], true); ?> />
                                Disable oEmbed functionality
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Save Settings'); ?>
            </form>
            
            <hr>
            
            <h2>Performance Status</h2>
            <?php $this->display_performance_status(); ?>
            
            <hr>
            
            <h2>Manual Optimization</h2>
            <p>
                <button class="button" onclick="if(confirm('Clean database now?')) { location.href='<?php echo wp_nonce_url(admin_url('admin.php?page=lccp-performance&action=clean_db'), 'lccp_clean_db'); ?>'; }">
                    Clean Database
                </button>
                <button class="button" onclick="if(confirm('Clear all caches?')) { location.href='<?php echo wp_nonce_url(admin_url('admin.php?page=lccp-performance&action=clear_cache'), 'lccp_clear_cache'); ?>'; }">
                    Clear Cache
                </button>
                <button class="button" onclick="if(confirm('Optimize database tables?')) { location.href='<?php echo wp_nonce_url(admin_url('admin.php?page=lccp-performance&action=optimize_tables'), 'lccp_optimize_tables'); ?>'; }">
                    Optimize Tables
                </button>
            </p>
        </div>
        <?php
        
        // Handle manual actions
        if (isset($_GET['action']) && isset($_GET['_wpnonce'])) {
            $action = $_GET['action'];
            $nonce_action = 'lccp_' . str_replace('_', '', $action);
            
            if (wp_verify_nonce($_GET['_wpnonce'], $nonce_action)) {
                switch ($action) {
                    case 'clean_db':
                        $this->cleanup_database();
                        echo '<div class="notice notice-success"><p>Database cleaned successfully.</p></div>';
                        break;
                    case 'clear_cache':
                        wp_cache_flush();
                        $this->cleanup_expired_transients();
                        echo '<div class="notice notice-success"><p>Cache cleared successfully.</p></div>';
                        break;
                    case 'optimize_tables':
                        $this->optimize_database_tables();
                        echo '<div class="notice notice-success"><p>Database tables optimized.</p></div>';
                        break;
                }
            }
        }
    }
    
    private function display_performance_status() {
        global $wpdb;
        
        // Database size
        $db_size = $wpdb->get_var("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ");
        
        // Autoload size
        $autoload_size = $wpdb->get_var("
            SELECT ROUND(SUM(LENGTH(option_value)) / 1024, 1) 
            FROM {$wpdb->options} 
            WHERE autoload = 'yes'
        ");
        
        // Revision count
        $revision_count = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'
        ");
        
        // Transient count
        $transient_count = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'
        ");
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <tr>
                <th>Database Size</th>
                <td><?php echo $db_size; ?> MB</td>
            </tr>
            <tr>
                <th>Autoload Data Size</th>
                <td><?php echo $autoload_size; ?> KB</td>
            </tr>
            <tr>
                <th>Post Revisions</th>
                <td><?php echo $revision_count; ?></td>
            </tr>
            <tr>
                <th>Transients</th>
                <td><?php echo $transient_count; ?></td>
            </tr>
            <tr>
                <th>Object Cache</th>
                <td><?php echo wp_using_ext_object_cache() ? '<span style="color:green">✓ Enabled</span>' : '<span style="color:orange">✗ Not enabled</span>'; ?></td>
            </tr>
        </table>
        <?php
    }
}