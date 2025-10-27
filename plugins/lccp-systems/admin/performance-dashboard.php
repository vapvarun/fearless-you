<?php
// Performance Dashboard Template
if (!defined('ABSPATH')) exit;

// Get metrics and settings from the calling context
global $fearless_performance_instance;
if (isset($fearless_performance_instance)) {
    $metrics = $fearless_performance_instance->analyze_performance();
    $settings = $fearless_performance_instance->settings;
} else {
    $metrics = array(
        'performance_score' => 0,
        'db_size' => 0,
        'autoload_size' => 0,
        'revision_count' => 0,
        'spam_comments' => 0
    );
    $settings = array(
        'optimize_database' => false,
        'optimize_object_cache' => false,
        'optimize_queries' => false,
        'optimize_memory' => false,
        'optimize_frontend' => false,
        'optimize_cleanup' => false,
        'disable_emojis' => false,
        'disable_embeds' => false
    );
}
?>

<div class="wrap">
    <h1>Fearless Performance Optimizer</h1>
    
    <!-- Performance Overview -->
    <div class="performance-overview" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
        
        <div class="performance-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0; color: #0073aa;">Performance Score</h3>
            <div style="font-size: 48px; font-weight: bold; color: <?php echo $metrics['performance_score'] >= 80 ? '#00a32a' : ($metrics['performance_score'] >= 60 ? '#dba617' : '#d63638'); ?>;">
                <?php echo $metrics['performance_score']; ?>%
            </div>
            <p style="margin: 10px 0 0;">Overall site performance rating</p>
        </div>
        
        <div class="performance-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0; color: #0073aa;">Database Size</h3>
            <div style="font-size: 32px; font-weight: bold;"><?php echo $metrics['db_size']; ?> MB</div>
            <p style="margin: 10px 0 0;">Total database size</p>
        </div>
        
        <div class="performance-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0; color: #0073aa;">Autoloaded Data</h3>
            <div style="font-size: 32px; font-weight: bold; color: <?php echo $metrics['autoload_size'] > 1000 ? '#d63638' : '#00a32a'; ?>;">
                <?php echo $metrics['autoload_size']; ?> KB
            </div>
            <p style="margin: 10px 0 0;">Data loaded on every request</p>
        </div>
        
        <div class="performance-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0; color: #0073aa;">Post Revisions</h3>
            <div style="font-size: 32px; font-weight: bold; color: <?php echo $metrics['revision_count'] > 1000 ? '#dba617' : '#00a32a'; ?>;">
                <?php echo number_format($metrics['revision_count']); ?>
            </div>
            <p style="margin: 10px 0 0;">Old post revisions in database</p>
        </div>
        
    </div>
    
    <!-- Current Status -->
    <div class="current-status" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 20px 0;">
        <h2>Current Status</h2>
        <table class="form-table">
            <tr>
                <th>PHP Version</th>
                <td><?php echo PHP_VERSION; ?> <?php echo version_compare(PHP_VERSION, '8.0', '>=') ? '<span style="color: green;">✓ Good</span>' : '<span style="color: red;">⚠ Outdated</span>'; ?></td>
            </tr>
            <tr>
                <th>Memory Limit</th>
                <td><?php echo ini_get('memory_limit'); ?></td>
            </tr>
            <tr>
                <th>OPcache</th>
                <td><?php echo extension_loaded('Zend OPcache') ? '<span style="color: green;">✓ Enabled</span>' : '<span style="color: red;">✗ Disabled</span>'; ?></td>
            </tr>
            <tr>
                <th>Object Cache</th>
                <td><?php echo wp_using_ext_object_cache() ? '<span style="color: green;">✓ External Cache Active</span>' : '<span style="color: orange;">⚠ Using Default Cache</span>'; ?></td>
            </tr>
            <tr>
                <th>Redis</th>
                <td><?php echo extension_loaded('redis') ? '<span style="color: green;">✓ Available</span>' : '<span style="color: red;">✗ Not Available</span>'; ?></td>
            </tr>
            <tr>
                <th>Active Plugins</th>
                <td><?php echo count(get_option('active_plugins', array())); ?> plugins</td>
            </tr>
        </table>
    </div>
    
    <!-- Optimization Settings -->
    <div class="optimization-settings" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 20px 0;">
        <h2>Optimization Settings</h2>
        
        <form method="post" action="options.php">
            <?php settings_fields('fearless_performance'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Database Optimization</th>
                    <td>
                        <label>
                            <input type="checkbox" name="fearless_performance_settings[optimize_database]" value="1" <?php checked(isset($settings['optimize_database']) ? $settings['optimize_database'] : false); ?>>
                            Enable database optimization (cleanup, indexing, autoload optimization)
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Object Cache Optimization</th>
                    <td>
                        <label>
                            <input type="checkbox" name="fearless_performance_settings[optimize_object_cache]" value="1" <?php checked(isset($settings['optimize_object_cache']) ? $settings['optimize_object_cache'] : false); ?>>
                            Enable advanced object caching for queries and metadata
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Query Optimization</th>
                    <td>
                        <label>
                            <input type="checkbox" name="fearless_performance_settings[optimize_queries]" value="1" <?php checked(isset($settings['optimize_queries']) ? $settings['optimize_queries'] : false); ?>>
                            Optimize LearnDash and BuddyBoss queries
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Memory Optimization</th>
                    <td>
                        <label>
                            <input type="checkbox" name="fearless_performance_settings[optimize_memory]" value="1" <?php checked(isset($settings['optimize_memory']) ? $settings['optimize_memory'] : false); ?>>
                            Enable memory optimization and cleanup
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Frontend Optimization</th>
                    <td>
                        <label>
                            <input type="checkbox" name="fearless_performance_settings[optimize_frontend]" value="1" <?php checked(isset($settings['optimize_frontend']) ? $settings['optimize_frontend'] : false); ?>>
                            Optimize CSS/JS delivery and remove header bloat
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Disable Emojis</th>
                    <td>
                        <label>
                            <input type="checkbox" name="fearless_performance_settings[disable_emojis]" value="1" <?php checked(isset($settings['disable_emojis']) ? $settings['disable_emojis'] : false); ?>>
                            Remove WordPress emoji scripts and styles
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Disable Embeds</th>
                    <td>
                        <label>
                            <input type="checkbox" name="fearless_performance_settings[disable_embeds]" value="1" <?php checked(isset($settings['disable_embeds']) ? $settings['disable_embeds'] : false); ?>>
                            Disable WordPress oEmbed functionality
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Cleanup Optimization</th>
                    <td>
                        <label>
                            <input type="checkbox" name="fearless_performance_settings[optimize_cleanup]" value="1" <?php checked(isset($settings['optimize_cleanup']) ? $settings['optimize_cleanup'] : false); ?>>
                            Enable daily cleanup of old files, logs, and database entries
                        </label>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Save Optimization Settings'); ?>
        </form>
    </div>
    
    <!-- Quick Actions -->
    <div class="quick-actions" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 20px 0;">
        <h2>Quick Actions</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            
            <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="cleanup_database">
                <?php wp_nonce_field('fearless_performance_action', 'nonce'); ?>
                <button type="submit" class="button button-secondary" style="width: 100%; padding: 10px;">
                    🗂️ Clean Database<br>
                    <small>Remove spam, revisions, orphaned data</small>
                </button>
            </form>
            
            <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="clear_cache">
                <?php wp_nonce_field('fearless_performance_action', 'nonce'); ?>
                <button type="submit" class="button button-secondary" style="width: 100%; padding: 10px;">
                    🗑️ Clear All Cache<br>
                    <small>Object cache, transients, page cache</small>
                </button>
            </form>
            
            <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="optimize_autoload">
                <?php wp_nonce_field('fearless_performance_action', 'nonce'); ?>
                <button type="submit" class="button button-secondary" style="width: 100%; padding: 10px;">
                    ⚡ Optimize Autoload<br>
                    <small>Reduce autoloaded options</small>
                </button>
            </form>
            
            <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="analyze_performance">
                <?php wp_nonce_field('fearless_performance_action', 'nonce'); ?>
                <button type="submit" class="button button-primary" style="width: 100%; padding: 10px;">
                    📊 Run Analysis<br>
                    <small>Full performance audit</small>
                </button>
            </form>
            
        </div>
    </div>
    
    <!-- Recommendations -->
    <div class="recommendations" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 20px 0;">
        <h2>Recommendations</h2>
        
        <?php if ($metrics['autoload_size'] > 1000): ?>
        <div class="notice notice-warning" style="margin: 10px 0;">
            <p><strong>High Autoload Size:</strong> Your autoloaded data is <?php echo $metrics['autoload_size']; ?>KB. Consider optimizing autoloaded options.</p>
        </div>
        <?php endif; ?>
        
        <?php if ($metrics['revision_count'] > 1000): ?>
        <div class="notice notice-warning" style="margin: 10px 0;">
            <p><strong>Too Many Revisions:</strong> You have <?php echo number_format($metrics['revision_count']); ?> post revisions. Clean them up to reduce database size.</p>
        </div>
        <?php endif; ?>
        
        <?php if (!wp_using_ext_object_cache()): ?>
        <div class="notice notice-error" style="margin: 10px 0;">
            <p><strong>No Object Cache:</strong> Enable Redis or Memcached for better performance with your large user base.</p>
        </div>
        <?php endif; ?>
        
        <?php if (count(get_option('active_plugins', array())) > 30): ?>
        <div class="notice notice-warning" style="margin: 10px 0;">
            <p><strong>Many Active Plugins:</strong> You have <?php echo count(get_option('active_plugins', array())); ?> active plugins. Consider reviewing if all are necessary.</p>
        </div>
        <?php endif; ?>
        
        <?php if ($metrics['spam_comments'] > 50): ?>
        <div class="notice notice-warning" style="margin: 10px 0;">
            <p><strong>Spam Comments:</strong> You have <?php echo $metrics['spam_comments']; ?> spam comments. Clean them up to reduce database bloat.</p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- PHP Configuration Recommendations -->
    <div class="php-recommendations" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 20px 0;">
        <h2>PHP Configuration Recommendations</h2>
        
        <table class="form-table">
            <tr>
                <th>Setting</th>
                <th>Current</th>
                <th>Recommended</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>memory_limit</td>
                <td><?php echo ini_get('memory_limit'); ?></td>
                <td>512M</td>
                <td><?php echo (int)ini_get('memory_limit') >= 512 || ini_get('memory_limit') == '-1' ? '✅' : '⚠️'; ?></td>
            </tr>
            <tr>
                <td>max_execution_time</td>
                <td><?php echo ini_get('max_execution_time'); ?></td>
                <td>300</td>
                <td><?php echo ini_get('max_execution_time') >= 300 || ini_get('max_execution_time') == 0 ? '✅' : '⚠️'; ?></td>
            </tr>
            <tr>
                <td>max_input_vars</td>
                <td><?php echo ini_get('max_input_vars'); ?></td>
                <td>3000</td>
                <td><?php echo ini_get('max_input_vars') >= 3000 ? '✅' : '⚠️'; ?></td>
            </tr>
            <tr>
                <td>upload_max_filesize</td>
                <td><?php echo ini_get('upload_max_filesize'); ?></td>
                <td>64M</td>
                <td><?php echo (int)ini_get('upload_max_filesize') >= 64 ? '✅' : '⚠️'; ?></td>
            </tr>
            <tr>
                <td>post_max_size</td>
                <td><?php echo ini_get('post_max_size'); ?></td>
                <td>128M</td>
                <td><?php echo (int)ini_get('post_max_size') >= 128 ? '✅' : '⚠️'; ?></td>
            </tr>
        </table>
        
        <h3>Server Configuration Notes:</h3>
        <ul>
            <li><strong>OPcache:</strong> <?php echo extension_loaded('Zend OPcache') ? 'Enabled ✅' : 'Not enabled - contact hosting provider ⚠️'; ?></li>
            <li><strong>Redis:</strong> <?php echo extension_loaded('redis') ? 'Available ✅' : 'Not available - contact hosting provider ⚠️'; ?></li>
            <li><strong>Memcached:</strong> <?php echo extension_loaded('memcached') ? 'Available ✅' : 'Not available'; ?></li>
            <li><strong>APCu:</strong> <?php echo extension_loaded('apcu') ? 'Available ✅' : 'Not available'; ?></li>
        </ul>
    </div>
</div>

<style>
.performance-card h3 {
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
}

.form-table th {
    width: 200px;
}

.quick-actions button {
    transition: all 0.3s ease;
}

.quick-actions button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>

<?php
// Handle quick actions
if (isset($_POST['action']) && wp_verify_nonce($_POST['nonce'], 'fearless_performance_action')) {
    $action = sanitize_text_field($_POST['action']);
    
    switch ($action) {
        case 'cleanup_database':
            if (isset($fearless_performance_instance)) {
                $fearless_performance_instance->cleanup_database();
                echo '<div class="notice notice-success"><p>Database cleanup completed!</p></div>';
            }
            break;
            
        case 'clear_cache':
            wp_cache_flush();
            if (isset($fearless_performance_instance)) {
                $fearless_performance_instance->cleanup_expired_transients();
            }
            echo '<div class="notice notice-success"><p>All caches cleared!</p></div>';
            break;
            
        case 'optimize_autoload':
            if (isset($fearless_performance_instance)) {
                $fearless_performance_instance->optimize_autoloaded_options();
                echo '<div class="notice notice-success"><p>Autoloaded options optimized!</p></div>';
            }
            break;
            
        case 'analyze_performance':
            echo '<div class="notice notice-success"><p>Performance analysis completed! Results updated above.</p></div>';
            break;
    }
}
?>