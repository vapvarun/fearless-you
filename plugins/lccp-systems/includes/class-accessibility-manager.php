<?php
/**
 * Accessibility Manager
 * Provides comprehensive accessibility features and responsive design
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Accessibility_Manager {
    
    private $settings;
    
    public function __construct() {
        $this->settings = get_option('lccp_accessibility_settings', $this->get_defaults());
        
        // Ensure settings are saved if they don't exist
        if (false === get_option('lccp_accessibility_settings')) {
            add_option('lccp_accessibility_settings', $this->settings);
        }
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('wp_footer', array($this, 'render_accessibility_widget'));
        add_action('wp_head', array($this, 'add_responsive_meta'));
        add_action('wp_head', array($this, 'add_accessibility_styles'), 999);
        
        // Admin hooks
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_head', array($this, 'add_admin_accessibility_styles'), 999);
        
        // AJAX handlers
        add_action('wp_ajax_save_accessibility_preferences', array($this, 'save_preferences'));
        add_action('wp_ajax_nopriv_save_accessibility_preferences', array($this, 'save_preferences'));
        
        // Body class filters
        add_filter('body_class', array($this, 'add_body_classes'));
        
        // Content filters for accessibility
        add_filter('the_content', array($this, 'enhance_content_accessibility'), 20);
        add_filter('widget_text', array($this, 'enhance_content_accessibility'), 20);
    }
    
    private function get_defaults() {
        return array(
            'enable_widget' => true,
            'widget_position' => 'bottom-left',
            'features' => array(
                'high_contrast' => true,
                'font_size' => true,
                'readable_font' => true,
                'highlight_links' => true,
                'keyboard_navigation' => true,
                'screen_reader' => true,
                'disable_animations' => true,
                'reading_guide' => true,
                'text_spacing' => true,
                'cursor_size' => true,
            )
        );
    }
    
    public function enqueue_frontend_assets() {
        // Main accessibility CSS
        wp_enqueue_style(
            'lccp-accessibility',
            LCCP_SYSTEMS_PLUGIN_URL . 'assets/css/accessibility.css',
            array(),
            LCCP_SYSTEMS_VERSION
        );
        
        // Responsive CSS
        wp_enqueue_style(
            'lccp-responsive',
            LCCP_SYSTEMS_PLUGIN_URL . 'assets/css/responsive.css',
            array(),
            LCCP_SYSTEMS_VERSION
        );
        
        // Accessibility JavaScript
        wp_enqueue_script(
            'lccp-accessibility',
            LCCP_SYSTEMS_PLUGIN_URL . 'assets/js/accessibility.js',
            array('jquery'),
            LCCP_SYSTEMS_VERSION,
            true
        );
        
        wp_localize_script('lccp-accessibility', 'lccp_accessibility', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lccp_accessibility'),
            'preferences' => $this->get_user_preferences(),
            'labels' => array(
                'toggle' => __('Accessibility Options', 'lccp-systems'),
                'high_contrast' => __('High Contrast', 'lccp-systems'),
                'large_text' => __('Large Text', 'lccp-systems'),
                'readable_font' => __('Readable Font', 'lccp-systems'),
                'highlight_links' => __('Highlight Links', 'lccp-systems'),
                'keyboard_nav' => __('Keyboard Navigation', 'lccp-systems'),
                'screen_reader' => __('Screen Reader Mode', 'lccp-systems'),
                'disable_animations' => __('Disable Animations', 'lccp-systems'),
                'reading_guide' => __('Reading Guide', 'lccp-systems'),
                'text_spacing' => __('Text Spacing', 'lccp-systems'),
                'large_cursor' => __('Large Cursor', 'lccp-systems'),
                'reset' => __('Reset Settings', 'lccp-systems'),
            )
        ));
    }
    
    public function enqueue_admin_assets() {
        wp_enqueue_style(
            'lccp-admin-responsive',
            LCCP_SYSTEMS_PLUGIN_URL . 'assets/css/admin-responsive.css',
            array(),
            LCCP_SYSTEMS_VERSION
        );
    }
    
    public function render_accessibility_widget() {
        if (!$this->settings['enable_widget']) {
            return;
        }
        ?>
        <div id="lccp-accessibility-widget" class="lccp-a11y-widget <?php echo esc_attr($this->settings['widget_position']); ?>" role="complementary" aria-label="<?php esc_attr_e('Accessibility Options', 'lccp-systems'); ?>">
            <button id="lccp-a11y-toggle" class="lccp-a11y-toggle" aria-expanded="false" aria-controls="lccp-a11y-panel">
                <span class="screen-reader-text"><?php _e('Open Accessibility Panel', 'lccp-systems'); ?></span>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <circle cx="12" cy="10" r="3"></circle>
                    <path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662"></path>
                </svg>
            </button>
            
            <div id="lccp-a11y-panel" class="lccp-a11y-panel" hidden>
                <div class="lccp-a11y-header">
                    <h2><?php _e('Accessibility Options', 'lccp-systems'); ?></h2>
                    <button class="lccp-a11y-close" aria-label="<?php esc_attr_e('Close panel', 'lccp-systems'); ?>">×</button>
                </div>
                
                <div class="lccp-a11y-content">
                    <?php if ($this->settings['features']['high_contrast']): ?>
                    <div class="lccp-a11y-option">
                        <button data-action="high-contrast" class="lccp-a11y-btn">
                            <span class="lccp-a11y-icon">🎨</span>
                            <span><?php _e('High Contrast', 'lccp-systems'); ?></span>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($this->settings['features']['font_size']): ?>
                    <div class="lccp-a11y-option">
                        <button data-action="font-size" class="lccp-a11y-btn">
                            <span class="lccp-a11y-icon">🔤</span>
                            <span><?php _e('Large Text', 'lccp-systems'); ?></span>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($this->settings['features']['readable_font']): ?>
                    <div class="lccp-a11y-option">
                        <button data-action="readable-font" class="lccp-a11y-btn">
                            <span class="lccp-a11y-icon">📖</span>
                            <span><?php _e('Readable Font', 'lccp-systems'); ?></span>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($this->settings['features']['highlight_links']): ?>
                    <div class="lccp-a11y-option">
                        <button data-action="highlight-links" class="lccp-a11y-btn">
                            <span class="lccp-a11y-icon">🔗</span>
                            <span><?php _e('Highlight Links', 'lccp-systems'); ?></span>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($this->settings['features']['keyboard_navigation']): ?>
                    <div class="lccp-a11y-option">
                        <button data-action="keyboard-nav" class="lccp-a11y-btn">
                            <span class="lccp-a11y-icon">⌨️</span>
                            <span><?php _e('Keyboard Navigation', 'lccp-systems'); ?></span>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($this->settings['features']['screen_reader']): ?>
                    <div class="lccp-a11y-option">
                        <button data-action="screen-reader" class="lccp-a11y-btn">
                            <span class="lccp-a11y-icon">🔊</span>
                            <span><?php _e('Screen Reader Mode', 'lccp-systems'); ?></span>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($this->settings['features']['disable_animations']): ?>
                    <div class="lccp-a11y-option">
                        <button data-action="no-animations" class="lccp-a11y-btn">
                            <span class="lccp-a11y-icon">⏸️</span>
                            <span><?php _e('Disable Animations', 'lccp-systems'); ?></span>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($this->settings['features']['reading_guide']): ?>
                    <div class="lccp-a11y-option">
                        <button data-action="reading-guide" class="lccp-a11y-btn">
                            <span class="lccp-a11y-icon">📏</span>
                            <span><?php _e('Reading Guide', 'lccp-systems'); ?></span>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($this->settings['features']['text_spacing']): ?>
                    <div class="lccp-a11y-option">
                        <button data-action="text-spacing" class="lccp-a11y-btn">
                            <span class="lccp-a11y-icon">↔️</span>
                            <span><?php _e('Text Spacing', 'lccp-systems'); ?></span>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($this->settings['features']['cursor_size']): ?>
                    <div class="lccp-a11y-option">
                        <button data-action="large-cursor" class="lccp-a11y-btn">
                            <span class="lccp-a11y-icon">🖱️</span>
                            <span><?php _e('Large Cursor', 'lccp-systems'); ?></span>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="lccp-a11y-option lccp-a11y-reset">
                        <button data-action="reset" class="lccp-a11y-btn lccp-a11y-btn-reset">
                            <span class="lccp-a11y-icon">🔄</span>
                            <span><?php _e('Reset All', 'lccp-systems'); ?></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="lccp-reading-guide" class="lccp-reading-guide" hidden aria-hidden="true"></div>
        <?php
    }
    
    public function add_responsive_meta() {
        ?>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
        <meta name="HandheldFriendly" content="true">
        <meta name="MobileOptimized" content="320">
        <?php
    }
    
    public function add_accessibility_styles() {
        ?>
        <style id="lccp-a11y-critical">
        /* Skip to content link */
        .skip-link {
            position: absolute;
            left: -9999px;
            z-index: 999999;
            padding: 1em;
            background: #000;
            color: #fff;
            text-decoration: none;
        }
        .skip-link:focus {
            left: 50%;
            transform: translateX(-50%);
            top: 1em;
        }
        
        /* Focus visible for keyboard navigation */
        *:focus-visible {
            outline: 3px solid #005fcc !important;
            outline-offset: 2px !important;
        }
        
        /* Screen reader only text */
        .screen-reader-text {
            border: 0;
            clip: rect(1px, 1px, 1px, 1px);
            -webkit-clip-path: inset(50%);
            clip-path: inset(50%);
            height: 1px;
            margin: -1px;
            overflow: hidden;
            padding: 0;
            position: absolute !important;
            width: 1px;
            word-wrap: normal !important;
        }
        
        /* Ensure interactive elements are large enough */
        button, a, input, select, textarea {
            min-height: 44px;
            min-width: 44px;
        }
        
        /* Responsive images */
        img {
            max-width: 100%;
            height: auto;
        }
        
        /* Responsive tables */
        @media screen and (max-width: 767px) {
            table {
                display: block;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
        </style>
        <?php
    }
    
    public function add_admin_accessibility_styles() {
        ?>
        <style id="lccp-admin-a11y">
        /* Admin responsive fixes */
        @media screen and (max-width: 782px) {
            .wrap {
                margin: 10px;
            }
            
            .widefat {
                max-width: 100%;
                display: block;
                overflow-x: auto;
            }
            
            .form-table th,
            .form-table td {
                display: block;
                width: 100%;
                padding-left: 0;
            }
        }
        </style>
        <?php
    }
    
    public function add_body_classes($classes) {
        $preferences = $this->get_user_preferences();
        
        if ($preferences) {
            foreach ($preferences as $key => $value) {
                if ($value) {
                    $classes[] = 'lccp-a11y-' . $key;
                }
            }
        }
        
        // Add device classes
        if (wp_is_mobile()) {
            $classes[] = 'lccp-mobile';
            
            if ($this->is_tablet()) {
                $classes[] = 'lccp-tablet';
            } else {
                $classes[] = 'lccp-phone';
            }
        } else {
            $classes[] = 'lccp-desktop';
        }
        
        return $classes;
    }
    
    private function is_tablet() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $user_agent);
    }
    
    public function enhance_content_accessibility($content) {
        // Add alt text reminder for images without alt
        $content = preg_replace_callback('/<img([^>]+)>/i', function($matches) {
            if (strpos($matches[1], 'alt=') === false) {
                return '<img' . $matches[1] . ' alt="" role="presentation">';
            }
            return $matches[0];
        }, $content);
        
        // Add ARIA labels to links that open in new windows
        $content = preg_replace_callback('/<a([^>]+)target=["\']_blank["\']([^>]*)>/i', function($matches) {
            if (strpos($matches[0], 'aria-label') === false) {
                return '<a' . $matches[1] . 'target="_blank"' . $matches[2] . ' aria-label="Opens in new window">';
            }
            return $matches[0];
        }, $content);
        
        // Ensure headings are in proper order
        $content = $this->fix_heading_hierarchy($content);
        
        return $content;
    }
    
    private function fix_heading_hierarchy($content) {
        // This is a simplified version - in production, you'd want more sophisticated heading analysis
        $last_heading = 1;
        
        $content = preg_replace_callback('/<h([1-6])([^>]*)>/i', function($matches) use (&$last_heading) {
            $level = intval($matches[1]);
            
            // Don't skip heading levels
            if ($level > $last_heading + 1) {
                $level = $last_heading + 1;
            }
            
            $last_heading = $level;
            return '<h' . $level . $matches[2] . '>';
        }, $content);
        
        return $content;
    }
    
    private function get_user_preferences() {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            return get_user_meta($user_id, 'lccp_accessibility_preferences', true);
        } else {
            // For non-logged in users, use session/cookie
            if (isset($_COOKIE['lccp_a11y_prefs'])) {
                return json_decode(stripslashes($_COOKIE['lccp_a11y_prefs']), true);
            }
        }
        
        return array();
    }
    
    public function save_preferences() {
        check_ajax_referer('lccp_accessibility', 'nonce');
        
        $preferences = isset($_POST['preferences']) ? $_POST['preferences'] : array();
        
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            update_user_meta($user_id, 'lccp_accessibility_preferences', $preferences);
        } else {
            // Set cookie for non-logged in users
            setcookie('lccp_a11y_prefs', json_encode($preferences), time() + (86400 * 30), '/');
        }
        
        wp_send_json_success();
    }
}

// Initialize accessibility manager
new LCCP_Accessibility_Manager();