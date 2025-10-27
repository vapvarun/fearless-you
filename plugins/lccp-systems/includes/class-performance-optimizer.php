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
    
    public function __construct() {
        $this->settings = get_option('lccp_performance_settings', $this->get_default_settings());
        
        // Initialize optimizations
        add_action('init', array($this, 'init_optimizations'));
        
        // Admin settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Scheduled cleanup
        add_action('lccp_systems_daily_cleanup', array($this, 'daily_cleanup'));
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
        
        // Clean up spam comments
        $wpdb->delete($wpdb->comments, array('comment_approved' => 'spam'));
        
        // Clean up old revisions (keep only 3 most recent)
        $wpdb->query("
            DELETE FROM {$wpdb->posts} 
            WHERE post_type = 'revision' 
            AND ID NOT IN (
                SELECT * FROM (
                    SELECT MAX(ID) FROM {$wpdb->posts} p2
                    WHERE p2.post_type = 'revision' 
                    AND p2.post_parent = {$wpdb->posts}.post_parent
                    GROUP BY p2.post_parent
                    ORDER BY p2.post_date DESC
                    LIMIT 3
                ) as t
            )
        ");
        
        // Clean up orphaned post meta
        $wpdb->query("
            DELETE pm FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.ID IS NULL
        ");
        
        // Clean up orphaned user meta
        $wpdb->query("
            DELETE um FROM {$wpdb->usermeta} um
            LEFT JOIN {$wpdb->users} u ON um.user_id = u.ID
            WHERE u.ID IS NULL
        ");
    }
    
    public function cleanup_expired_transients() {
        global $wpdb;
        
        // Delete expired transients
        $wpdb->query("
            DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_timeout_%' 
            AND option_value < UNIX_TIMESTAMP()
        ");
        
        // Delete orphaned transients
        $wpdb->query("
            DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_%' 
            AND option_name NOT LIKE '_transient_timeout_%'
            AND option_name NOT IN (
                SELECT REPLACE(option_name, '_transient_timeout_', '_transient_') 
                FROM (
                    SELECT option_name FROM {$wpdb->options}
                    WHERE option_name LIKE '_transient_timeout_%'
                ) AS t
            )
        ");
    }
    
    public function optimize_autoloaded_options() {
        global $wpdb;
        
        // Find large autoloaded options
        $large_options = $wpdb->get_results("
            SELECT option_name, LENGTH(option_value) as size 
            FROM {$wpdb->options} 
            WHERE autoload = 'yes' 
            AND LENGTH(option_value) > 100000
            ORDER BY size DESC
            LIMIT 10
        ");
        
        $safe_to_disable = array(
            'rewrite_rules', // Can be regenerated
            '_site_transient_browser_', // Browser data
            '_site_transient_timeout_browser_', // Browser timeouts
        );
        
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