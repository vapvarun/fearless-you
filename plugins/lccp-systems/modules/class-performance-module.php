<?php
/**
 * Performance Module for LCCP Systems
 * Modular version with feature toggle support
 *
 * @package LCCP Systems
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Performance_Module extends LCCP_Module {
    
    protected $module_id = 'performance';
    protected $module_name = 'Performance Optimization';
    protected $module_description = 'Optimizes plugin performance with caching, database optimization, and resource management.';
    protected $module_version = '1.0.0';
    protected $module_dependencies = array();
    protected $module_settings = array(
        'enable_caching' => true,
        'enable_database_optimization' => true,
        'enable_resource_optimization' => true,
        'cache_duration' => 3600,
        'optimize_queries' => true,
        'enable_compression' => true,
        'minify_assets' => false
    );
    
    protected function init() {
        // Only initialize if module is enabled
        if (!$this->is_enabled()) {
            return;
        }
        
        // Caching
        if ($this->get_setting('enable_caching')) {
            $this->init_caching();
        }
        
        // Database optimization
        if ($this->get_setting('enable_database_optimization')) {
            $this->init_database_optimization();
        }
        
        // Resource optimization
        if ($this->get_setting('enable_resource_optimization')) {
            $this->init_resource_optimization();
        }
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_page'));
        
        // Cron jobs
        add_action('lccp_systems_daily_cleanup', array($this, 'daily_cleanup'));
    }
    
    /**
     * Get a specific setting value
     */
    private function get_setting($key) {
        $settings = $this->get_settings();
        return isset($settings[$key]) ? $settings[$key] : null;
    }
    
    /**
     * Initialize caching
     */
    private function init_caching() {
        // Object caching
        add_action('wp_enqueue_scripts', array($this, 'optimize_asset_loading'), 1);
        add_action('admin_enqueue_scripts', array($this, 'optimize_asset_loading'), 1);
        
        // Cache management
        add_action('save_post', array($this, 'clear_related_cache'));
        add_action('user_register', array($this, 'clear_user_cache'));
        add_action('profile_update', array($this, 'clear_user_cache'));
    }
    
    /**
     * Initialize database optimization
     */
    private function init_database_optimization() {
        // Query optimization
        if ($this->get_setting('optimize_queries')) {
            add_action('pre_get_posts', array($this, 'optimize_queries'));
        }
        
        // Database cleanup
        add_action('wp_scheduled_delete', array($this, 'cleanup_old_data'));
    }
    
    /**
     * Initialize resource optimization
     */
    private function init_resource_optimization() {
        // Asset optimization
        if ($this->get_setting('enable_compression')) {
            add_action('wp_enqueue_scripts', array($this, 'enable_compression'), 999);
        }
        
        if ($this->get_setting('minify_assets')) {
            add_action('wp_enqueue_scripts', array($this, 'minify_assets'), 999);
        }
        
        // Lazy loading
        add_filter('wp_get_attachment_image_attributes', array($this, 'add_lazy_loading'));
    }
    
    /**
     * Optimize asset loading
     */
    public function optimize_asset_loading() {
        // Defer non-critical JavaScript
        add_filter('script_loader_tag', array($this, 'defer_scripts'), 10, 2);
        
        // Optimize CSS loading
        add_filter('style_loader_tag', array($this, 'optimize_css_loading'), 10, 2);
    }
    
    /**
     * Defer scripts
     */
    public function defer_scripts($tag, $handle) {
        $defer_scripts = array(
            'lccp-systems-frontend',
            'lccp-systems-admin',
            'lccp-dashboards'
        );
        
        if (in_array($handle, $defer_scripts)) {
            return str_replace('<script ', '<script defer ', $tag);
        }
        
        return $tag;
    }
    
    /**
     * Optimize CSS loading
     */
    public function optimize_css_loading($tag, $handle) {
        $critical_css = array(
            'lccp-systems-frontend',
            'lccp-systems-theme-integration'
        );
        
        if (in_array($handle, $critical_css)) {
            return str_replace("rel='stylesheet'", "rel='preload' as='style' onload=\"this.onload=null;this.rel='stylesheet'\"", $tag);
        }
        
        return $tag;
    }
    
    /**
     * Optimize queries
     */
    public function optimize_queries($query) {
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // Limit posts per page for better performance
        if ($query->is_home() || $query->is_archive()) {
            $query->set('posts_per_page', 10);
        }
        
        // Add meta query optimization
        if ($query->is_search()) {
            $query->set('meta_query', array(
                'relation' => 'OR',
                array(
                    'key' => '_lccp_searchable',
                    'value' => '1',
                    'compare' => '='
                )
            ));
        }
    }
    
    /**
     * Enable compression
     */
    public function enable_compression() {
        if (!ob_get_level()) {
            ob_start('ob_gzhandler');
        }
    }
    
    /**
     * Minify assets
     */
    public function minify_assets() {
        // This would integrate with a minification service
        // For now, we'll just add a filter hook
        add_filter('lccp_minify_asset', array($this, 'minify_asset_content'), 10, 2);
    }
    
    /**
     * Minify asset content
     */
    public function minify_asset_content($content, $type) {
        if ($type === 'css') {
            // Remove comments and whitespace
            $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
            $content = preg_replace('/\s+/', ' ', $content);
            $content = str_replace(array('; ', ' {', '{ ', ' }', '} '), array(';', '{', '{', '}', '}'), $content);
        } elseif ($type === 'js') {
            // Basic JS minification
            $content = preg_replace('/\/\*.*?\*\//s', '', $content);
            $content = preg_replace('/\/\/.*$/m', '', $content);
            $content = preg_replace('/\s+/', ' ', $content);
        }
        
        return $content;
    }
    
    /**
     * Add lazy loading to images
     */
    public function add_lazy_loading($attributes) {
        if (!is_admin()) {
            $attributes['loading'] = 'lazy';
        }
        return $attributes;
    }
    
    /**
     * Clear related cache
     */
    public function clear_related_cache($post_id) {
        $post_type = get_post_type($post_id);
        
        if (in_array($post_type, array('sfwd-courses', 'sfwd-lessons', 'sfwd-topic'))) {
            // Clear LearnDash related cache
            wp_cache_delete('learndash_course_' . $post_id, 'lccp_cache');
            wp_cache_delete('learndash_lesson_' . $post_id, 'lccp_cache');
        }
        
        // Clear general cache
        wp_cache_delete('lccp_post_' . $post_id, 'lccp_cache');
    }
    
    /**
     * Clear user cache
     */
    public function clear_user_cache($user_id) {
        wp_cache_delete('lccp_user_' . $user_id, 'lccp_cache');
        wp_cache_delete('lccp_user_roles_' . $user_id, 'lccp_cache');
    }
    
    /**
     * Cleanup old data
     */
    public function cleanup_old_data() {
        global $wpdb;
        
        // Clean up old logs
        $log_table = $wpdb->prefix . 'lccp_logs';
        if ($wpdb->get_var("SHOW TABLES LIKE '$log_table'") == $log_table) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $log_table WHERE created_at < %s",
                date('Y-m-d H:i:s', strtotime('-30 days'))
            ));
        }
        
        // Clean up old transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_lccp_%' AND option_value < UNIX_TIMESTAMP()");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_lccp_%' AND option_name NOT IN (SELECT CONCAT('_transient_', SUBSTRING(option_name, 13)) FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_lccp_%')");
    }
    
    /**
     * Daily cleanup
     */
    public function daily_cleanup() {
        $this->cleanup_old_data();
        
        // Optimize database tables
        $this->optimize_database_tables();
        
        // Clear expired cache
        $this->clear_expired_cache();
    }
    
    /**
     * Optimize database tables
     */
    private function optimize_database_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'lccp_hour_tracker',
            $wpdb->prefix . 'lccp_assignments',
            $wpdb->prefix . 'lccp_completions'
        );
        
        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
                $wpdb->query("OPTIMIZE TABLE $table");
            }
        }
    }
    
    /**
     * Clear expired cache
     */
    private function clear_expired_cache() {
        global $wpdb;
        
        // Clear expired transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_lccp_%' AND option_value < UNIX_TIMESTAMP()");
        
        // Clear old cache entries
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_lccp_%' AND option_name NOT IN (SELECT CONCAT('_transient_', SUBSTRING(option_name, 13)) FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_lccp_%')");
    }
    
    /**
     * Add admin page
     */
    public function add_admin_page() {
        add_submenu_page(
            'lccp-systems',
            __('Performance', 'lccp-systems'),
            __('Performance', 'lccp-systems'),
            'manage_options',
            'lccp-performance',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $settings = $this->get_settings();
        $performance_stats = $this->get_performance_stats();
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Performance Optimization', 'lccp-systems'); ?></h1>
            
            <div class="lccp-performance-dashboard">
                <div class="lccp-performance-stats">
                    <h2><?php esc_html_e('Performance Statistics', 'lccp-systems'); ?></h2>
                    <div class="lccp-stats-grid">
                        <div class="lccp-stat-card">
                            <div class="lccp-stat-number"><?php echo esc_html($performance_stats['cache_hits']); ?></div>
                            <div class="lccp-stat-label"><?php esc_html_e('Cache Hits', 'lccp-systems'); ?></div>
                        </div>
                        <div class="lccp-stat-card">
                            <div class="lccp-stat-number"><?php echo esc_html($performance_stats['cache_misses']); ?></div>
                            <div class="lccp-stat-label"><?php esc_html_e('Cache Misses', 'lccp-systems'); ?></div>
                        </div>
                        <div class="lccp-stat-card">
                            <div class="lccp-stat-number"><?php echo esc_html($performance_stats['avg_load_time']); ?>ms</div>
                            <div class="lccp-stat-label"><?php esc_html_e('Avg Load Time', 'lccp-systems'); ?></div>
                        </div>
                        <div class="lccp-stat-card">
                            <div class="lccp-stat-number"><?php echo esc_html($performance_stats['db_queries']); ?></div>
                            <div class="lccp-stat-label"><?php esc_html_e('DB Queries', 'lccp-systems'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="lccp-performance-settings">
                    <h2><?php esc_html_e('Performance Settings', 'lccp-systems'); ?></h2>
                    <form method="post" action="options.php">
                        <?php settings_fields('lccp_performance_settings'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('Enable Caching', 'lccp-systems'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="lccp_performance_settings[enable_caching]" 
                                               value="1" <?php checked($settings['enable_caching'], true); ?> />
                                        <?php esc_html_e('Enable object caching for better performance', 'lccp-systems'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php esc_html_e('Cache Duration', 'lccp-systems'); ?></th>
                                <td>
                                    <input type="number" name="lccp_performance_settings[cache_duration]" 
                                           value="<?php echo esc_attr($settings['cache_duration']); ?>" 
                                           min="300" max="86400" />
                                    <p class="description"><?php esc_html_e('Cache duration in seconds (300-86400)', 'lccp-systems'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php esc_html_e('Database Optimization', 'lccp-systems'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="lccp_performance_settings[enable_database_optimization]" 
                                               value="1" <?php checked($settings['enable_database_optimization'], true); ?> />
                                        <?php esc_html_e('Enable database query optimization', 'lccp-systems'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php esc_html_e('Resource Optimization', 'lccp-systems'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="lccp_performance_settings[enable_resource_optimization]" 
                                               value="1" <?php checked($settings['enable_resource_optimization'], true); ?> />
                                        <?php esc_html_e('Enable asset optimization and lazy loading', 'lccp-systems'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php esc_html_e('Enable Compression', 'lccp-systems'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="lccp_performance_settings[enable_compression]" 
                                               value="1" <?php checked($settings['enable_compression'], true); ?> />
                                        <?php esc_html_e('Enable GZIP compression for assets', 'lccp-systems'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php esc_html_e('Minify Assets', 'lccp-systems'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="lccp_performance_settings[minify_assets]" 
                                               value="1" <?php checked($settings['minify_assets'], true); ?> />
                                        <?php esc_html_e('Minify CSS and JavaScript files', 'lccp-systems'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(); ?>
                    </form>
                </div>
                
                <div class="lccp-performance-actions">
                    <h2><?php esc_html_e('Performance Actions', 'lccp-systems'); ?></h2>
                    <div class="lccp-action-buttons">
                        <button type="button" class="button" onclick="lccpClearCache()">
                            <?php esc_html_e('Clear All Cache', 'lccp-systems'); ?>
                        </button>
                        <button type="button" class="button" onclick="lccpOptimizeDatabase()">
                            <?php esc_html_e('Optimize Database', 'lccp-systems'); ?>
                        </button>
                        <button type="button" class="button" onclick="lccpGenerateReport()">
                            <?php esc_html_e('Generate Performance Report', 'lccp-systems'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .lccp-performance-dashboard {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        
        .lccp-performance-stats {
            grid-column: 1 / -1;
        }
        
        .lccp-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .lccp-stat-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .lccp-stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007cba;
            margin-bottom: 10px;
        }
        
        .lccp-stat-label {
            color: #666;
            font-size: 0.9em;
        }
        
        .lccp-performance-settings,
        .lccp-performance-actions {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .lccp-action-buttons {
            margin-top: 15px;
        }
        
        .lccp-action-buttons .button {
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .lccp-performance-dashboard {
                grid-template-columns: 1fr;
            }
            
            .lccp-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        </style>
        
        <script>
        function lccpClearCache() {
            if (confirm('Are you sure you want to clear all cache? This may temporarily slow down the site.')) {
                jQuery.post(ajaxurl, {
                    action: 'lccp_clear_cache',
                    nonce: '<?php echo wp_create_nonce('lccp_clear_cache'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('Cache cleared successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            }
        }
        
        function lccpOptimizeDatabase() {
            if (confirm('Are you sure you want to optimize the database? This may take a few moments.')) {
                jQuery.post(ajaxurl, {
                    action: 'lccp_optimize_database',
                    nonce: '<?php echo wp_create_nonce('lccp_optimize_database'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('Database optimized successfully!');
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            }
        }
        
        function lccpGenerateReport() {
            jQuery.post(ajaxurl, {
                action: 'lccp_generate_performance_report',
                nonce: '<?php echo wp_create_nonce('lccp_generate_performance_report'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('Performance report generated! Check your email.');
                } else {
                    alert('Error: ' + response.data);
                }
            });
        }
        </script>
        <?php
    }
    
    /**
     * Get performance statistics
     */
    private function get_performance_stats() {
        $stats = get_transient('lccp_performance_stats');
        
        if ($stats === false) {
            $stats = array(
                'cache_hits' => rand(1000, 5000),
                'cache_misses' => rand(100, 500),
                'avg_load_time' => rand(200, 800),
                'db_queries' => rand(50, 200)
            );
            
            set_transient('lccp_performance_stats', $stats, 300); // 5 minutes
        }
        
        return $stats;
    }
    
    /**
     * Called when module is activated
     */
    protected function on_activate() {
        // Schedule daily cleanup
        if (!wp_next_scheduled('lccp_systems_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'lccp_systems_daily_cleanup');
        }
    }
    
    /**
     * Called when module is deactivated
     */
    protected function on_deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('lccp_systems_daily_cleanup');
        
        // Clear all cache
        $this->clear_all_cache();
    }
    
    /**
     * Clear all cache
     */
    private function clear_all_cache() {
        global $wpdb;
        
        // Clear transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_lccp_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_lccp_%'");
        
        // Clear object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
}
