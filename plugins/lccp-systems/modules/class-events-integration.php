<?php
/**
 * LCCP Events Integration Module
 * 
 * Consolidates functionality from Events Virtual, Events Block, and Events Shortcode plugins
 * Provides toggle options for each feature set
 * 
 * @package LCCP_Systems
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Events_Integration {
    
    private static $instance = null;
    private $settings = array();
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_settings();
        $this->init_hooks();
    }
    
    private function load_settings() {
        $this->settings = array(
            'virtual_events' => get_option('lccp_events_virtual_enabled', 'on'),
            'events_blocks' => get_option('lccp_events_blocks_enabled', 'on'),
            'events_shortcodes' => get_option('lccp_events_shortcodes_enabled', 'on')
        );
    }
    
    private function init_hooks() {
        // Core initialization
        add_action('init', array($this, 'init_features'), 20);
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_settings_submenu'), 100);
            add_action('admin_init', array($this, 'register_settings'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        }
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_lccp_toggle_event_feature', array($this, 'handle_toggle_feature'));
    }
    
    public function init_features() {
        // Check if The Events Calendar is active
        if (!class_exists('Tribe__Events__Main')) {
            add_action('admin_notices', array($this, 'show_dependency_notice'));
            return;
        }
        
        // Initialize Virtual Events features
        if ($this->settings['virtual_events'] === 'on') {
            $this->init_virtual_events();
        }
        
        // Initialize Events Blocks
        if ($this->settings['events_blocks'] === 'on') {
            $this->init_events_blocks();
        }
        
        // Initialize Events Shortcodes
        if ($this->settings['events_shortcodes'] === 'on') {
            $this->init_events_shortcodes();
        }
    }
    
    /**
     * Virtual Events Functionality
     */
    private function init_virtual_events() {
        // Add virtual event fields to event edit screen
        add_action('tribe_events_meta_box_section', array($this, 'add_virtual_event_fields'), 10, 2);
        add_action('tribe_events_update_meta', array($this, 'save_virtual_event_data'), 10, 2);
        
        // Add virtual event display to frontend
        add_filter('tribe_events_single_event_before_the_content', array($this, 'display_virtual_event_info'));
        add_filter('tribe_events_event_schedule_details', array($this, 'add_virtual_badge'), 10, 2);
        
        // Add virtual event filters
        add_filter('tribe_events_views_v2_view_repository_args', array($this, 'filter_virtual_events'), 10, 3);
    }
    
    public function add_virtual_event_fields($event_id, $event) {
        $is_virtual = get_post_meta($event_id, '_lccp_virtual_event', true);
        $virtual_url = get_post_meta($event_id, '_lccp_virtual_url', true);
        $virtual_provider = get_post_meta($event_id, '_lccp_virtual_provider', true);
        ?>
        <div class="lccp-virtual-event-section">
            <h3><?php _e('Virtual Event Settings', 'lccp-systems'); ?></h3>
            
            <div class="lccp-field">
                <label>
                    <input type="checkbox" name="lccp_virtual_event" value="1" <?php checked($is_virtual, '1'); ?>>
                    <?php _e('This is a virtual event', 'lccp-systems'); ?>
                </label>
            </div>
            
            <div class="lccp-virtual-fields" style="<?php echo $is_virtual ? '' : 'display:none;'; ?>">
                <div class="lccp-field">
                    <label for="lccp_virtual_url"><?php _e('Virtual Event URL', 'lccp-systems'); ?></label>
                    <input type="url" id="lccp_virtual_url" name="lccp_virtual_url" value="<?php echo esc_url($virtual_url); ?>" class="regular-text">
                </div>
                
                <div class="lccp-field">
                    <label for="lccp_virtual_provider"><?php _e('Platform', 'lccp-systems'); ?></label>
                    <select id="lccp_virtual_provider" name="lccp_virtual_provider">
                        <option value=""><?php _e('Select Platform', 'lccp-systems'); ?></option>
                        <option value="zoom" <?php selected($virtual_provider, 'zoom'); ?>>Zoom</option>
                        <option value="teams" <?php selected($virtual_provider, 'teams'); ?>>Microsoft Teams</option>
                        <option value="meet" <?php selected($virtual_provider, 'meet'); ?>>Google Meet</option>
                        <option value="webex" <?php selected($virtual_provider, 'webex'); ?>>Webex</option>
                        <option value="youtube" <?php selected($virtual_provider, 'youtube'); ?>>YouTube Live</option>
                        <option value="facebook" <?php selected($virtual_provider, 'facebook'); ?>>Facebook Live</option>
                        <option value="other" <?php selected($virtual_provider, 'other'); ?>>Other</option>
                    </select>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(function($) {
            $('input[name="lccp_virtual_event"]').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.lccp-virtual-fields').slideDown();
                } else {
                    $('.lccp-virtual-fields').slideUp();
                }
            });
        });
        </script>
        <?php
    }
    
    public function save_virtual_event_data($event_id, $data) {
        if (isset($_POST['lccp_virtual_event'])) {
            update_post_meta($event_id, '_lccp_virtual_event', '1');
            
            if (isset($_POST['lccp_virtual_url'])) {
                update_post_meta($event_id, '_lccp_virtual_url', esc_url_raw($_POST['lccp_virtual_url']));
            }
            
            if (isset($_POST['lccp_virtual_provider'])) {
                update_post_meta($event_id, '_lccp_virtual_provider', sanitize_text_field($_POST['lccp_virtual_provider']));
            }
        } else {
            delete_post_meta($event_id, '_lccp_virtual_event');
            delete_post_meta($event_id, '_lccp_virtual_url');
            delete_post_meta($event_id, '_lccp_virtual_provider');
        }
    }
    
    public function display_virtual_event_info($content) {
        global $post;
        
        if (get_post_meta($post->ID, '_lccp_virtual_event', true)) {
            $virtual_url = get_post_meta($post->ID, '_lccp_virtual_url', true);
            $virtual_provider = get_post_meta($post->ID, '_lccp_virtual_provider', true);
            
            ob_start();
            ?>
            <div class="lccp-virtual-event-info">
                <h3><?php _e('Virtual Event Access', 'lccp-systems'); ?></h3>
                <?php if ($virtual_url): ?>
                    <p>
                        <a href="<?php echo esc_url($virtual_url); ?>" target="_blank" class="lccp-virtual-link button">
                            <?php _e('Join Virtual Event', 'lccp-systems'); ?>
                        </a>
                    </p>
                <?php endif; ?>
                <?php if ($virtual_provider): ?>
                    <p class="lccp-virtual-provider">
                        <?php printf(__('Platform: %s', 'lccp-systems'), ucfirst($virtual_provider)); ?>
                    </p>
                <?php endif; ?>
            </div>
            <?php
            $virtual_info = ob_get_clean();
            $content = $virtual_info . $content;
        }
        
        return $content;
    }
    
    public function add_virtual_badge($schedule_html, $event_id) {
        if (get_post_meta($event_id, '_lccp_virtual_event', true)) {
            $badge = '<span class="lccp-virtual-badge">' . __('Virtual', 'lccp-systems') . '</span> ';
            $schedule_html = $badge . $schedule_html;
        }
        return $schedule_html;
    }
    
    public function filter_virtual_events($args, $context, $view) {
        if (isset($_GET['virtual_only']) && $_GET['virtual_only'] === '1') {
            $args['meta_query'][] = array(
                'key' => '_lccp_virtual_event',
                'value' => '1',
                'compare' => '='
            );
        }
        return $args;
    }
    
    /**
     * Events Blocks Functionality
     */
    private function init_events_blocks() {
        // Register block
        add_action('init', array($this, 'register_events_block'));
    }
    
    public function register_events_block() {
        if (!function_exists('register_block_type')) {
            return;
        }
        
        wp_register_script(
            'lccp-events-block',
            LCCP_SYSTEMS_PLUGIN_URL . 'assets/js/events-block.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
            LCCP_SYSTEMS_VERSION
        );
        
        wp_register_style(
            'lccp-events-block-editor',
            LCCP_SYSTEMS_PLUGIN_URL . 'assets/css/events-block-editor.css',
            array('wp-edit-blocks'),
            LCCP_SYSTEMS_VERSION
        );
        
        register_block_type('lccp/events-list', array(
            'editor_script' => 'lccp-events-block',
            'editor_style' => 'lccp-events-block-editor',
            'render_callback' => array($this, 'render_events_block'),
            'attributes' => array(
                'limit' => array(
                    'type' => 'number',
                    'default' => 5
                ),
                'category' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'showPastEvents' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'showVirtualOnly' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'layout' => array(
                    'type' => 'string',
                    'default' => 'list'
                )
            )
        ));
    }
    
    public function render_events_block($attributes) {
        $args = array(
            'post_type' => 'tribe_events',
            'posts_per_page' => $attributes['limit'],
            'meta_key' => '_EventStartDate',
            'orderby' => 'meta_value',
            'order' => 'ASC'
        );
        
        // Filter by category
        if (!empty($attributes['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'tribe_events_cat',
                    'field' => 'slug',
                    'terms' => $attributes['category']
                )
            );
        }
        
        // Filter past events
        if (!$attributes['showPastEvents']) {
            $args['meta_query'][] = array(
                'key' => '_EventEndDate',
                'value' => date('Y-m-d H:i:s'),
                'compare' => '>='
            );
        }
        
        // Filter virtual events
        if ($attributes['showVirtualOnly']) {
            $args['meta_query'][] = array(
                'key' => '_lccp_virtual_event',
                'value' => '1',
                'compare' => '='
            );
        }
        
        $events = new WP_Query($args);
        
        ob_start();
        
        if ($events->have_posts()): ?>
            <div class="lccp-events-block lccp-layout-<?php echo esc_attr($attributes['layout']); ?>">
                <?php while ($events->have_posts()): $events->the_post(); ?>
                    <div class="lccp-event-item widget">
                        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                        <div class="lccp-event-meta">
                            <?php echo tribe_events_event_schedule_details(); ?>
                        </div>
                        <?php if (has_excerpt()): ?>
                            <div class="lccp-event-excerpt"><?php the_excerpt(); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p><?php _e('No events found.', 'lccp-systems'); ?></p>
        <?php endif;
        
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    /**
     * Events Shortcodes Functionality
     */
    private function init_events_shortcodes() {
        add_shortcode('lccp_events', array($this, 'events_shortcode'));
        add_shortcode('lccp_event_calendar', array($this, 'calendar_shortcode'));
        
        // Add shortcode button to editor
        add_action('media_buttons', array($this, 'add_shortcode_button'), 15);
    }
    
    public function events_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 5,
            'category' => '',
            'past' => 'no',
            'virtual' => '',
            'layout' => 'list',
            'columns' => 3,
            'excerpt' => 'yes',
            'thumb' => 'yes',
            'date_format' => 'M j, Y',
            'time_format' => 'g:i a'
        ), $atts);
        
        $args = array(
            'post_type' => 'tribe_events',
            'posts_per_page' => intval($atts['limit']),
            'meta_key' => '_EventStartDate',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_query' => array()
        );
        
        // Category filter
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'tribe_events_cat',
                    'field' => 'slug',
                    'terms' => explode(',', $atts['category'])
                )
            );
        }
        
        // Past events filter
        if ($atts['past'] !== 'yes') {
            $args['meta_query'][] = array(
                'key' => '_EventEndDate',
                'value' => date('Y-m-d H:i:s'),
                'compare' => '>='
            );
        }
        
        // Virtual events filter
        if ($atts['virtual'] === 'only') {
            $args['meta_query'][] = array(
                'key' => '_lccp_virtual_event',
                'value' => '1',
                'compare' => '='
            );
        }
        
        $events = new WP_Query($args);
        
        ob_start();
        
        if ($events->have_posts()): ?>
            <div class="lccp-events-shortcode lccp-layout-<?php echo esc_attr($atts['layout']); ?>" 
                 data-columns="<?php echo esc_attr($atts['columns']); ?>">
                <?php while ($events->have_posts()): $events->the_post(); ?>
                    <div class="lccp-event widget">
                        <?php if ($atts['thumb'] === 'yes' && has_post_thumbnail()): ?>
                            <div class="lccp-event-thumb">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('medium'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="lccp-event-content">
                            <h3 class="lccp-event-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h3>
                            
                            <div class="lccp-event-date">
                                <?php
                                $start_date = tribe_get_start_date(null, false, $atts['date_format']);
                                $start_time = tribe_get_start_time(null, $atts['time_format']);
                                echo $start_date . ' @ ' . $start_time;
                                ?>
                            </div>
                            
                            <?php if (get_post_meta(get_the_ID(), '_lccp_virtual_event', true)): ?>
                                <span class="lccp-virtual-indicator"><?php _e('Virtual Event', 'lccp-systems'); ?></span>
                            <?php endif; ?>
                            
                            <?php if ($atts['excerpt'] === 'yes' && has_excerpt()): ?>
                                <div class="lccp-event-excerpt">
                                    <?php the_excerpt(); ?>
                                </div>
                            <?php endif; ?>
                            
                            <a href="<?php the_permalink(); ?>" class="lccp-event-link">
                                <?php _e('View Event', 'lccp-systems'); ?> →
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="lccp-no-events"><?php _e('No events found.', 'lccp-systems'); ?></p>
        <?php endif;
        
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    public function calendar_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'virtual' => ''
        ), $atts);
        
        ob_start();
        ?>
        <div class="lccp-event-calendar" 
             data-category="<?php echo esc_attr($atts['category']); ?>"
             data-virtual="<?php echo esc_attr($atts['virtual']); ?>">
            <div id="lccp-calendar-container">
                <!-- Calendar will be rendered here via JavaScript -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function add_shortcode_button() {
        echo '<button type="button" class="button lccp-shortcode-button" onclick="lccp_insert_shortcode()">
                <span class="dashicons dashicons-calendar-alt"></span> ' . __('Insert Events', 'lccp-systems') . '
              </button>';
    }
    
    /**
     * Admin Settings Page
     */
    public function add_settings_submenu() {
        add_submenu_page(
            'lccp-systems',
            __('Events Integration', 'lccp-systems'),
            __('Events Integration', 'lccp-systems'),
            'manage_options',
            'lccp-events-integration',
            array($this, 'render_settings_page')
        );
    }
    
    public function render_settings_page() {
        ?>
        <div class="wrap lccp-events-settings">
            <h1><?php _e('LCCP Events Integration Settings', 'lccp-systems'); ?></h1>
            
            <div class="lccp-settings-info">
                <p><?php _e('This module consolidates functionality from multiple event-related plugins into a single, optimized solution.', 'lccp-systems'); ?></p>
            </div>
            
            <form method="post" action="options.php">
                <?php settings_fields('lccp_events_settings'); ?>
                
                <div class="lccp-feature-toggles">
                    <h2><?php _e('Event Features', 'lccp-systems'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Virtual Events', 'lccp-systems'); ?></th>
                            <td>
                                <label class="lccp-toggle">
                                    <input type="checkbox" name="lccp_events_virtual_enabled" value="on" 
                                           <?php checked($this->settings['virtual_events'], 'on'); ?>>
                                    <span class="lccp-slider"></span>
                                </label>
                                <p class="description">
                                    <?php _e('Enable virtual event functionality including Zoom, Teams, and other platform integrations.', 'lccp-systems'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Event Blocks', 'lccp-systems'); ?></th>
                            <td>
                                <label class="lccp-toggle">
                                    <input type="checkbox" name="lccp_events_blocks_enabled" value="on" 
                                           <?php checked($this->settings['events_blocks'], 'on'); ?>>
                                    <span class="lccp-slider"></span>
                                </label>
                                <p class="description">
                                    <?php _e('Enable Gutenberg blocks for displaying events in the block editor.', 'lccp-systems'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Event Shortcodes', 'lccp-systems'); ?></th>
                            <td>
                                <label class="lccp-toggle">
                                    <input type="checkbox" name="lccp_events_shortcodes_enabled" value="on" 
                                           <?php checked($this->settings['events_shortcodes'], 'on'); ?>>
                                    <span class="lccp-slider"></span>
                                </label>
                                <p class="description">
                                    <?php _e('Enable shortcodes for displaying events anywhere on your site.', 'lccp-systems'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php if ($this->settings['events_shortcodes'] === 'on'): ?>
                <div class="lccp-shortcode-reference">
                    <h2><?php _e('Available Shortcodes', 'lccp-systems'); ?></h2>
                    
                    <div class="lccp-shortcode-examples">
                        <h3><?php _e('Events List', 'lccp-systems'); ?></h3>
                        <code>[lccp_events limit="5" category="workshops" layout="grid" columns="3"]</code>
                        
                        <h3><?php _e('Event Calendar', 'lccp-systems'); ?></h3>
                        <code>[lccp_event_calendar category="training" virtual="only"]</code>
                        
                        <h4><?php _e('Parameters:', 'lccp-systems'); ?></h4>
                        <ul>
                            <li><strong>limit</strong> - Number of events to show (default: 5)</li>
                            <li><strong>category</strong> - Event category slug (optional)</li>
                            <li><strong>past</strong> - Show past events: yes/no (default: no)</li>
                            <li><strong>virtual</strong> - Filter virtual events: only/exclude (optional)</li>
                            <li><strong>layout</strong> - Display layout: list/grid (default: list)</li>
                            <li><strong>columns</strong> - Grid columns: 2/3/4 (default: 3)</li>
                            <li><strong>excerpt</strong> - Show excerpt: yes/no (default: yes)</li>
                            <li><strong>thumb</strong> - Show thumbnail: yes/no (default: yes)</li>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="lccp-migration-notice">
                    <h2><?php _e('Plugin Migration', 'lccp-systems'); ?></h2>
                    <p><?php _e('After enabling these features and verifying they work correctly, you can safely deactivate:', 'lccp-systems'); ?></p>
                    <ul>
                        <li>Events Virtual</li>
                        <li>Events Block for The Events Calendar</li>
                        <li>The Events Calendar Shortcode</li>
                    </ul>
                    <p class="warning"><?php _e('Note: The main Events Calendar plugin must remain active.', 'lccp-systems'); ?></p>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <style>
            .lccp-events-settings {
                max-width: 800px;
            }
            .lccp-settings-info {
                background: #f0f0f1;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
            }
            .lccp-feature-toggles {
                background: white;
                padding: 20px;
                border: 1px solid #ccd0d4;
                border-radius: 5px;
                margin: 20px 0;
            }
            .lccp-toggle {
                position: relative;
                display: inline-block;
                width: 50px;
                height: 24px;
            }
            .lccp-toggle input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            .lccp-slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
                border-radius: 24px;
            }
            .lccp-slider:before {
                position: absolute;
                content: "";
                height: 18px;
                width: 18px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
            }
            .lccp-toggle input:checked + .lccp-slider {
                background-color: #667eea;
            }
            .lccp-toggle input:checked + .lccp-slider:before {
                transform: translateX(26px);
            }
            .lccp-shortcode-reference {
                background: white;
                padding: 20px;
                border: 1px solid #ccd0d4;
                border-radius: 5px;
                margin: 20px 0;
            }
            .lccp-shortcode-examples code {
                display: block;
                padding: 10px;
                background: #f0f0f1;
                border-radius: 3px;
                margin: 10px 0;
            }
            .lccp-migration-notice {
                background: #fff8e5;
                border-left: 4px solid #ffb900;
                padding: 15px;
                margin: 20px 0;
            }
            .lccp-migration-notice .warning {
                color: #d63638;
                font-weight: bold;
            }
            .lccp-virtual-badge {
                display: inline-block;
                background: #667eea;
                color: white;
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: bold;
                text-transform: uppercase;
                margin-right: 5px;
            }
            .lccp-virtual-event-info {
                background: #f0f8ff;
                border: 1px solid #667eea;
                padding: 20px;
                border-radius: 5px;
                margin-bottom: 20px;
            }
            .lccp-virtual-link {
                background: #667eea !important;
                color: white !important;
                padding: 10px 20px !important;
                text-decoration: none !important;
                border-radius: 5px !important;
                display: inline-block !important;
            }
            .lccp-virtual-link:hover {
                background: #5569d6 !important;
            }
        </style>
        <?php
    }
    
    public function register_settings() {
        register_setting('lccp_events_settings', 'lccp_events_virtual_enabled');
        register_setting('lccp_events_settings', 'lccp_events_blocks_enabled');
        register_setting('lccp_events_settings', 'lccp_events_shortcodes_enabled');
    }
    
    public function handle_toggle_feature() {
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'lccp_toggle_event_feature')) {
            wp_send_json_error('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $feature = sanitize_text_field($_POST['feature'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? '');
        
        $option_map = array(
            'virtual' => 'lccp_events_virtual_enabled',
            'blocks' => 'lccp_events_blocks_enabled',
            'shortcodes' => 'lccp_events_shortcodes_enabled'
        );
        
        if (isset($option_map[$feature])) {
            update_option($option_map[$feature], $status);
            wp_send_json_success('Feature updated');
        }
        
        wp_send_json_error('Invalid feature');
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'lccp-events-integration') === false) {
            return;
        }
        
        wp_enqueue_script('lccp-events-admin', 
            LCCP_SYSTEMS_PLUGIN_URL . 'assets/js/events-admin.js', 
            array('jquery'), 
            LCCP_SYSTEMS_VERSION, 
            true
        );
    }
    
    public function enqueue_frontend_assets() {
        if (!is_singular('tribe_events') && !has_shortcode(get_post_field('post_content', get_the_ID()), 'lccp_events')) {
            return;
        }
        
        wp_enqueue_style('lccp-events-frontend', 
            LCCP_SYSTEMS_PLUGIN_URL . 'assets/css/events-frontend.css', 
            array(), 
            LCCP_SYSTEMS_VERSION
        );
        
        if ($this->settings['events_shortcodes'] === 'on') {
            wp_enqueue_script('lccp-events-frontend', 
                LCCP_SYSTEMS_PLUGIN_URL . 'assets/js/events-frontend.js', 
                array('jquery'), 
                LCCP_SYSTEMS_VERSION, 
                true
            );
        }
    }
    
    public function show_dependency_notice() {
        ?>
        <div class="notice notice-warning">
            <p><?php _e('LCCP Events Integration requires The Events Calendar plugin to be active.', 'lccp-systems'); ?></p>
        </div>
        <?php
    }
}

// Initialize the module
LCCP_Events_Integration::get_instance();