<?php
/**
 * Caching System for FLI Child Theme
 * 
 * @package FLI BuddyBoss Child
 * @version 1.0.0
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * FLI Caching System
 * 
 * Provides intelligent caching for expensive operations
 * like IP lookups, file checks, and database queries.
 */
class FLI_Caching_System {
    
    /**
     * The single instance of the class
     * 
     * @var FLI_Caching_System
     */
    private static $instance = null;
    
    /**
     * Cache group prefix
     * 
     * @var string
     */
    private $cache_group = 'fli_cache';
    
    /**
     * Default cache expiration (in seconds)
     * 
     * @var int
     */
    private $default_expiration = 3600; // 1 hour
    
    /**
     * Cache statistics
     * 
     * @var array
     */
    private $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0
    ];
    
    /**
     * Get the single instance
     * 
     * @return FLI_Caching_System
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize the caching system
     */
    private function init() {
        // Hook into WordPress
        add_action('init', [$this, 'setup_caching']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_fli_clear_cache', [$this, 'clear_cache']);
        add_action('wp_ajax_fli_cache_stats', [$this, 'get_cache_stats']);
        
        // Cache invalidation hooks
        add_action('switch_theme', [$this, 'invalidate_theme_cache']);
        add_action('activated_plugin', [$this, 'invalidate_plugin_cache']);
        add_action('deactivated_plugin', [$this, 'invalidate_plugin_cache']);
        add_action('save_post', [$this, 'invalidate_post_cache']);
        add_action('delete_post', [$this, 'invalidate_post_cache']);
        add_action('user_register', [$this, 'invalidate_user_cache']);
        add_action('profile_update', [$this, 'invalidate_user_cache']);
        add_action('delete_user', [$this, 'invalidate_user_cache']);
    }
    
    /**
     * Setup caching
     */
    public function setup_caching() {
        // Initialize cache statistics
        $this->stats = get_transient('fli_cache_stats') ?: $this->stats;
    }
    
    /**
     * Get cached data
     * 
     * @param string $key Cache key
     * @param string $group Cache group (optional)
     * @return mixed|false Cached data or false if not found
     */
    public function get($key, $group = '') {
        $cache_key = $this->build_cache_key($key, $group);
        $data = get_transient($cache_key);
        
        if ($data !== false) {
            $this->stats['hits']++;
            fli_log_debug("Cache hit for key: {$cache_key}", ['key' => $cache_key], 'FLI Cache');
        } else {
            $this->stats['misses']++;
            fli_log_debug("Cache miss for key: {$cache_key}", ['key' => $cache_key], 'FLI Cache');
        }
        
        $this->save_stats();
        return $data;
    }
    
    /**
     * Set cached data
     * 
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int $expiration Expiration time in seconds (optional)
     * @param string $group Cache group (optional)
     * @return bool True on success, false on failure
     */
    public function set($key, $data, $expiration = null, $group = '') {
        $cache_key = $this->build_cache_key($key, $group);
        $expiration = $expiration ?: $this->default_expiration;
        
        $result = set_transient($cache_key, $data, $expiration);
        
        if ($result) {
            $this->stats['sets']++;
            fli_log_debug("Cache set for key: {$cache_key}", [
                'key' => $cache_key,
                'expiration' => $expiration,
                'data_size' => strlen(serialize($data))
            ], 'FLI Cache');
        } else {
            fli_log_warning("Failed to set cache for key: {$cache_key}", ['key' => $cache_key], 'FLI Cache');
        }
        
        $this->save_stats();
        return $result;
    }
    
    /**
     * Delete cached data
     * 
     * @param string $key Cache key
     * @param string $group Cache group (optional)
     * @return bool True on success, false on failure
     */
    public function delete($key, $group = '') {
        $cache_key = $this->build_cache_key($key, $group);
        $result = delete_transient($cache_key);
        
        if ($result) {
            $this->stats['deletes']++;
            fli_log_debug("Cache deleted for key: {$cache_key}", ['key' => $cache_key], 'FLI Cache');
        }
        
        $this->save_stats();
        return $result;
    }
    
    /**
     * Clear all cache
     * 
     * @param string $group Cache group (optional)
     * @return int Number of items cleared
     */
    public function clear($group = '') {
        global $wpdb;
        
        $pattern = $this->build_cache_key('*', $group);
        $pattern = str_replace('*', '%', $pattern);
        
        $sql = $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name LIKE %s",
            '_transient_' . $pattern,
            '_transient_timeout_' . $pattern
        );
        
        $result = $wpdb->query($sql);
        
        fli_log_info("Cache cleared for group: {$group}", [
            'group' => $group,
            'items_cleared' => $result
        ], 'FLI Cache');
        
        return $result;
    }
    
    /**
     * Get or set cached data with callback
     * 
     * @param string $key Cache key
     * @param callable $callback Callback to generate data if not cached
     * @param int $expiration Expiration time in seconds (optional)
     * @param string $group Cache group (optional)
     * @return mixed Cached or generated data
     */
    public function remember($key, $callback, $expiration = null, $group = '') {
        $data = $this->get($key, $group);
        
        if ($data === false) {
            $data = call_user_func($callback);
            $this->set($key, $data, $expiration, $group);
        }
        
        return $data;
    }
    
    /**
     * Build cache key with user role consideration
     * 
     * @param string $key Cache key
     * @param string $group Cache group
     * @param bool $include_user_role Whether to include user role in cache key
     * @return string Full cache key
     */
    private function build_cache_key($key, $group = '', $include_user_role = false) {
        $group = $group ?: $this->cache_group;
        $cache_key = "{$group}_{$key}";
        
        // Add user role to cache key for role-specific content
        if ($include_user_role) {
            $user_role = $this->get_user_role_context();
            $cache_key .= "_{$user_role}";
        }
        
        return $cache_key;
    }
    
    /**
     * Get user role context for caching
     * 
     * @return string User role context
     */
    private function get_user_role_context() {
        if (!is_user_logged_in()) {
            return 'guest';
        }
        
        $user = wp_get_current_user();
        $roles = $user->roles;
        
        // Sort roles for consistent caching
        sort($roles);
        
        // Create role context string
        $role_context = implode('_', $roles);
        
        // Add membership level if available (for LearnDash, MemberPress, etc.)
        $membership_level = $this->get_membership_level();
        if ($membership_level) {
            $role_context .= "_level_{$membership_level}";
        }
        
        return $role_context;
    }
    
    /**
     * Get user membership level
     * 
     * @return string|false Membership level or false if not available
     */
    private function get_membership_level() {
        // Check for LearnDash course access
        if (function_exists('learndash_user_get_active_courses')) {
            $user_id = get_current_user_id();
            $courses = learndash_user_get_active_courses($user_id);
            if (!empty($courses)) {
                return 'learndash_active';
            }
        }
        
        // Check for MemberPress membership
        if (class_exists('MeprUser')) {
            $user = new MeprUser(get_current_user_id());
            $memberships = $user->active_product_subscriptions('ids', true);
            if (!empty($memberships)) {
                return 'memberpress_active';
            }
        }
        
        // Check for WooCommerce Memberships
        if (function_exists('wc_memberships_get_user_memberships')) {
            $user_id = get_current_user_id();
            $memberships = wc_memberships_get_user_memberships($user_id);
            if (!empty($memberships)) {
                return 'woocommerce_member';
            }
        }
        
        // Check for custom membership meta
        $membership_level = get_user_meta(get_current_user_id(), 'membership_level', true);
        if (!empty($membership_level)) {
            return sanitize_key($membership_level);
        }
        
        return false;
    }
    
    /**
     * Save cache statistics
     */
    private function save_stats() {
        set_transient('fli_cache_stats', $this->stats, DAY_IN_SECONDS);
    }
    
    /**
     * Get cache statistics
     * 
     * @return array Cache statistics
     */
    public function get_stats() {
        return $this->stats;
    }
    
    /**
     * Get cache hit ratio
     * 
     * @return float Cache hit ratio (0-1)
     */
    public function get_hit_ratio() {
        $total = $this->stats['hits'] + $this->stats['misses'];
        return $total > 0 ? $this->stats['hits'] / $total : 0;
    }
    
    /**
     * Invalidate theme cache
     */
    public function invalidate_theme_cache() {
        $this->clear('theme');
        fli_log_info('Theme cache invalidated', [], 'FLI Cache');
    }
    
    /**
     * Invalidate plugin cache
     */
    public function invalidate_plugin_cache() {
        $this->clear('plugin');
        fli_log_info('Plugin cache invalidated', [], 'FLI Cache');
    }
    
    /**
     * Invalidate post cache
     * 
     * @param int $post_id Post ID
     */
    public function invalidate_post_cache($post_id) {
        $this->delete("post_{$post_id}", 'post');
        $this->clear('post_list');
        fli_log_debug("Post cache invalidated for post ID: {$post_id}", ['post_id' => $post_id], 'FLI Cache');
    }
    
    /**
     * Invalidate user cache
     * 
     * @param int $user_id User ID
     */
    public function invalidate_user_cache($user_id) {
        $this->delete("user_{$user_id}", 'user');
        $this->clear('user_list');
        fli_log_debug("User cache invalidated for user ID: {$user_id}", ['user_id' => $user_id], 'FLI Cache');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_management_page(
            'FLI Cache Management',
            'FLI Cache',
            'manage_options',
            'fli-cache',
            [$this, 'admin_page']
        );
    }
    
    /**
     * Admin page callback
     */
    public function admin_page() {
        $stats = $this->get_stats();
        $hit_ratio = $this->get_hit_ratio();
        
        ?>
        <div class="wrap">
            <h1>FLI Cache Management</h1>
            
            <div class="notice notice-info">
                <p><strong>Cache Hit Ratio:</strong> <?php echo number_format($hit_ratio * 100, 2); ?>%</p>
                <p><strong>Cache Hits:</strong> <?php echo number_format($stats['hits']); ?></p>
                <p><strong>Cache Misses:</strong> <?php echo number_format($stats['misses']); ?></p>
                <p><strong>Cache Sets:</strong> <?php echo number_format($stats['sets']); ?></p>
                <p><strong>Cache Deletes:</strong> <?php echo number_format($stats['deletes']); ?></p>
            </div>
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <button type="button" class="button" onclick="clearAllCache()">Clear All Cache</button>
                    <button type="button" class="button" onclick="clearThemeCache()">Clear Theme Cache</button>
                    <button type="button" class="button" onclick="clearPluginCache()">Clear Plugin Cache</button>
                    <button type="button" class="button" onclick="refreshStats()">Refresh Stats</button>
                </div>
            </div>
            
            <h2>Cache Groups</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Group</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>theme</code></td>
                        <td>Theme-related cache (styles, templates, etc.)</td>
                        <td><button type="button" class="button button-small" onclick="clearGroupCache('theme')">Clear</button></td>
                    </tr>
                    <tr>
                        <td><code>plugin</code></td>
                        <td>Plugin-related cache</td>
                        <td><button type="button" class="button button-small" onclick="clearGroupCache('plugin')">Clear</button></td>
                    </tr>
                    <tr>
                        <td><code>user</code></td>
                        <td>User-related cache (profiles, permissions, etc.)</td>
                        <td><button type="button" class="button button-small" onclick="clearGroupCache('user')">Clear</button></td>
                    </tr>
                    <tr>
                        <td><code>post</code></td>
                        <td>Post-related cache</td>
                        <td><button type="button" class="button button-small" onclick="clearGroupCache('post')">Clear</button></td>
                    </tr>
                    <tr>
                        <td><code>ip_lookup</code></td>
                        <td>IP address lookup cache</td>
                        <td><button type="button" class="button button-small" onclick="clearGroupCache('ip_lookup')">Clear</button></td>
                    </tr>
                    <tr>
                        <td><code>file_check</code></td>
                        <td>File existence and permission checks</td>
                        <td><button type="button" class="button button-small" onclick="clearGroupCache('file_check')">Clear</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <script>
        function clearAllCache() {
            if (confirm('Are you sure you want to clear all cache?')) {
                jQuery.post(ajaxurl, {
                    action: 'fli_clear_cache',
                    group: 'all',
                    nonce: '<?php echo wp_create_nonce('fli_clear_cache'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('All cache cleared successfully!');
                        location.reload();
                    } else {
                        alert('Error clearing cache: ' + response.data);
                    }
                });
            }
        }
        
        function clearThemeCache() {
            clearGroupCache('theme');
        }
        
        function clearPluginCache() {
            clearGroupCache('plugin');
        }
        
        function clearGroupCache(group) {
            if (confirm('Are you sure you want to clear ' + group + ' cache?')) {
                jQuery.post(ajaxurl, {
                    action: 'fli_clear_cache',
                    group: group,
                    nonce: '<?php echo wp_create_nonce('fli_clear_cache'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert(group + ' cache cleared successfully!');
                    } else {
                        alert('Error clearing cache: ' + response.data);
                    }
                });
            }
        }
        
        function refreshStats() {
            location.reload();
        }
        </script>
        <?php
    }
    
    /**
     * Clear cache via AJAX
     */
    public function clear_cache() {
        check_ajax_referer('fli_clear_cache', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $group = sanitize_text_field($_POST['group'] ?? '');
        
        if ($group === 'all') {
            $this->clear();
            wp_send_json_success('All cache cleared successfully');
        } else {
            $this->clear($group);
            wp_send_json_success("{$group} cache cleared successfully");
        }
    }
    
    /**
     * Get cache stats via AJAX
     */
    public function get_cache_stats() {
        check_ajax_referer('fli_cache_stats', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        wp_send_json_success($this->get_stats());
    }
}

// Initialize the caching system
FLI_Caching_System::get_instance();

/**
 * Helper functions for easy caching
 */

/**
 * Get cached data
 * 
 * @param string $key Cache key
 * @param string $group Cache group (optional)
 * @param bool $include_user_role Whether to include user role in cache key
 * @return mixed|false Cached data or false if not found
 */
function fli_cache_get($key, $group = '', $include_user_role = false) {
    return FLI_Caching_System::get_instance()->get($key, $group, $include_user_role);
}

/**
 * Set cached data
 * 
 * @param string $key Cache key
 * @param mixed $data Data to cache
 * @param int $expiration Expiration time in seconds (optional)
 * @param string $group Cache group (optional)
 * @param bool $include_user_role Whether to include user role in cache key
 * @return bool True on success, false on failure
 */
function fli_cache_set($key, $data, $expiration = null, $group = '', $include_user_role = false) {
    return FLI_Caching_System::get_instance()->set($key, $data, $expiration, $group, $include_user_role);
}

/**
 * Delete cached data
 * 
 * @param string $key Cache key
 * @param string $group Cache group (optional)
 * @param bool $include_user_role Whether to include user role in cache key
 * @return bool True on success, false on failure
 */
function fli_cache_delete($key, $group = '', $include_user_role = false) {
    return FLI_Caching_System::get_instance()->delete($key, $group, $include_user_role);
}

/**
 * Get or set cached data with callback
 * 
 * @param string $key Cache key
 * @param callable $callback Callback to generate data if not cached
 * @param int $expiration Expiration time in seconds (optional)
 * @param string $group Cache group (optional)
 * @param bool $include_user_role Whether to include user role in cache key
 * @return mixed Cached or generated data
 */
function fli_cache_remember($key, $callback, $expiration = null, $group = '', $include_user_role = false) {
    return FLI_Caching_System::get_instance()->remember($key, $callback, $expiration, $group, $include_user_role);
}

/**
 * Clear cache group
 * 
 * @param string $group Cache group (optional)
 * @return int Number of items cleared
 */
function fli_cache_clear($group = '') {
    return FLI_Caching_System::get_instance()->clear($group);
}

/**
 * Role-based cache helpers for membership sites
 */

/**
 * Get cached data for current user's role
 * 
 * @param string $key Cache key
 * @param string $group Cache group (optional)
 * @return mixed|false Cached data or false if not found
 */
function fli_cache_get_for_user($key, $group = '') {
    return fli_cache_get($key, $group, true);
}

/**
 * Set cached data for current user's role
 * 
 * @param string $key Cache key
 * @param mixed $data Data to cache
 * @param int $expiration Expiration time in seconds (optional)
 * @param string $group Cache group (optional)
 * @return bool True on success, false on failure
 */
function fli_cache_set_for_user($key, $data, $expiration = null, $group = '') {
    return fli_cache_set($key, $data, $expiration, $group, true);
}

/**
 * Get or set cached data with callback for current user's role
 * 
 * @param string $key Cache key
 * @param callable $callback Callback to generate data if not cached
 * @param int $expiration Expiration time in seconds (optional)
 * @param string $group Cache group (optional)
 * @return mixed Cached or generated data
 */
function fli_cache_remember_for_user($key, $callback, $expiration = null, $group = '') {
    return fli_cache_remember($key, $callback, $expiration, $group, true);
}

/**
 * Clear cache for specific user role
 * 
 * @param string $role User role to clear cache for
 * @param string $group Cache group (optional)
 * @return int Number of items cleared
 */
function fli_cache_clear_for_role($role, $group = '') {
    global $wpdb;
    
    $pattern = "fli_cache_{$group}_%_{$role}%";
    
    $sql = $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND (option_name LIKE %s OR option_name LIKE %s)",
        '_transient_' . $pattern,
        '_transient_timeout_' . $pattern
    );
    
    $result = $wpdb->query($sql);
    
    fli_log_info("Cache cleared for role: {$role}", [
        'role' => $role,
        'group' => $group,
        'items_cleared' => $result
    ], 'FLI Cache');
    
    return $result;
}
