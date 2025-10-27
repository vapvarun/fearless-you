<?php
/**
 * Dasher Dashboard Settings
 * 
 * @package Dasher
 * @since 1.0.3
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Dasher_Dashboard_Settings
 * Handles dashboard page configuration and login redirects
 */
class Dasher_Dashboard_Settings {
    
    /**
     * Initialize the settings system
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_login', array($this, 'handle_login_redirect'), 10, 2);
        add_filter('login_redirect', array($this, 'custom_login_redirect'), 10, 3);
    }
    
    /**
     * Add the dashboard settings page to admin menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'dasher-settings',
            __('Dashboard Settings', 'dasher'),
            __('Dashboard Settings', 'dasher'),
            'manage_options',
            'dasher-dashboard-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings fields
     */
    public function register_settings() {
        // Register settings group
        register_setting('dasher_dashboard_settings', 'dasher_dashboard_pages', array(
            'sanitize_callback' => array($this, 'sanitize_dashboard_pages')
        ));
        
        // Add settings section
        add_settings_section(
            'dasher_dashboard_pages_section',
            __('Dashboard Page Configuration', 'dasher'),
            array($this, 'dashboard_pages_section_callback'),
            'dasher_dashboard_settings'
        );
        
        // Mentor Dashboard Page
        add_settings_field(
            'mentor_dashboard_page',
            __('Mentor Dashboard Page', 'dasher'),
            array($this, 'mentor_dashboard_page_callback'),
            'dasher_dashboard_settings',
            'dasher_dashboard_pages_section'
        );
        
        // BigBird Dashboard Page
        add_settings_field(
            'big_bird_dashboard_page',
            __('Big Bird Dashboard Page', 'dasher'),
            array($this, 'big_bird_dashboard_page_callback'),
            'dasher_dashboard_settings',
            'dasher_dashboard_pages_section'
        );
        
        // PC Dashboard Page
        add_settings_field(
            'pc_dashboard_page',
            __('Program Candidate Dashboard Page', 'dasher'),
            array($this, 'pc_dashboard_page_callback'),
            'dasher_dashboard_settings',
            'dasher_dashboard_pages_section'
        );
        
        // Login Redirect Settings
        add_settings_section(
            'dasher_login_redirect_section',
            __('Login Redirect Settings', 'dasher'),
            array($this, 'login_redirect_section_callback'),
            'dasher_dashboard_settings'
        );
        
        add_settings_field(
            'enable_auto_redirect',
            __('Enable Automatic Login Redirects', 'dasher'),
            array($this, 'enable_auto_redirect_callback'),
            'dasher_dashboard_settings',
            'dasher_login_redirect_section'
        );
        
        add_settings_field(
            'fallback_redirect_page',
            __('Fallback Redirect Page', 'dasher'),
            array($this, 'fallback_redirect_page_callback'),
            'dasher_dashboard_settings',
            'dasher_login_redirect_section'
        );
    }
    
    /**
     * Sanitize dashboard pages settings
     */
    public function sanitize_dashboard_pages($input) {
        $sanitized = array();
        
        if (isset($input['mentor_dashboard_page'])) {
            $sanitized['mentor_dashboard_page'] = intval($input['mentor_dashboard_page']);
        }
        
        if (isset($input['big_bird_dashboard_page'])) {
            $sanitized['big_bird_dashboard_page'] = intval($input['big_bird_dashboard_page']);
        }
        
        if (isset($input['pc_dashboard_page'])) {
            $sanitized['pc_dashboard_page'] = intval($input['pc_dashboard_page']);
        }
        
        if (isset($input['enable_auto_redirect'])) {
            $sanitized['enable_auto_redirect'] = (bool) $input['enable_auto_redirect'];
        }
        
        if (isset($input['fallback_redirect_page'])) {
            $sanitized['fallback_redirect_page'] = intval($input['fallback_redirect_page']);
        }
        
        return $sanitized;
    }
    
    /**
     * Render the settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Show success message if settings were saved
        if (isset($_GET['settings-updated'])) {
            add_settings_error('dasher_dashboard_messages', 'dasher_dashboard_message', __('Settings Saved', 'dasher'), 'updated');
        }
        
        settings_errors('dasher_dashboard_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="dasher-settings-intro">
                <p><?php esc_html_e('Configure which pages serve as dashboards for each user role. Users will be automatically redirected to their appropriate dashboard after login.', 'dasher'); ?></p>
            </div>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('dasher_dashboard_settings');
                do_settings_sections('dasher_dashboard_settings');
                submit_button();
                ?>
            </form>
            
            <div class="dasher-settings-help">
                <h3><?php esc_html_e('Quick Setup Guide', 'dasher'); ?></h3>
                <ol>
                    <li><?php esc_html_e('Create or select pages for each dashboard type', 'dasher'); ?></li>
                    <li><?php esc_html_e('Add the appropriate shortcode to each page:', 'dasher'); ?>
                        <ul style="margin-top: 10px;">
                            <li><code>[dasher_mentor_dashboard]</code> - <?php esc_html_e('For mentor dashboard pages', 'dasher'); ?></li>
                            <li><code>[dasher_big_bird_dashboard]</code> - <?php esc_html_e('For Big Bird dashboard pages', 'dasher'); ?></li>
                            <li><code>[dasher_pc_dashboard]</code> - <?php esc_html_e('For Program Candidate dashboard pages', 'dasher'); ?></li>
                        </ul>
                    </li>
                    <li><?php esc_html_e('Select the pages in the dropdowns above', 'dasher'); ?></li>
                    <li><?php esc_html_e('Enable automatic login redirects', 'dasher'); ?></li>
                    <li><?php esc_html_e('Save settings', 'dasher'); ?></li>
                </ol>
                
                <h4><?php esc_html_e('Need to create pages?', 'dasher'); ?></h4>
                <p>
                    <a href="<?php echo admin_url('post-new.php?post_type=page'); ?>" class="button button-secondary">
                        <?php esc_html_e('Create New Page', 'dasher'); ?>
                    </a>
                </p>
            </div>
        </div>
        
        <style>
        .dasher-settings-intro {
            background: #f1f1f1;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .dasher-settings-help {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .dasher-settings-help h3 {
            margin-top: 0;
        }
        
        .dasher-settings-help code {
            background: #f1f1f1;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        
        .dasher-page-preview {
            margin-top: 10px;
            padding: 10px;
            background: #f9f9f9;
            border-left: 4px solid #00a0d2;
            font-size: 12px;
        }
        </style>
        <?php
    }
    
    /**
     * Dashboard pages section callback
     */
    public function dashboard_pages_section_callback() {
        echo '<p>' . esc_html__('Select which pages should serve as dashboards for each user role. Make sure each page contains the appropriate shortcode.', 'dasher') . '</p>';
    }
    
    /**
     * Login redirect section callback
     */
    public function login_redirect_section_callback() {
        echo '<p>' . esc_html__('Configure how users are redirected after login based on their role.', 'dasher') . '</p>';
    }
    
    /**
     * Mentor dashboard page field
     */
    public function mentor_dashboard_page_callback() {
        $options = get_option('dasher_dashboard_pages', array());
        $selected = isset($options['mentor_dashboard_page']) ? $options['mentor_dashboard_page'] : 0;
        
        echo '<select name="dasher_dashboard_pages[mentor_dashboard_page]" id="mentor_dashboard_page">';
        echo '<option value="0">' . esc_html__('— Select a page —', 'dasher') . '</option>';
        
        $pages = get_pages();
        foreach ($pages as $page) {
            echo '<option value="' . esc_attr($page->ID) . '" ' . selected($selected, $page->ID, false) . '>';
            echo esc_html($page->post_title) . ' (/' . esc_html($page->post_name) . ')';
            echo '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">' . esc_html__('Page where mentors will be redirected after login. Should contain [dasher_mentor_dashboard] shortcode.', 'dasher') . '</p>';
        
        if ($selected > 0) {
            $this->show_page_preview($selected, 'dasher_mentor_dashboard');
        }
    }
    
    /**
     * BigBird dashboard page field
     */
    public function big_bird_dashboard_page_callback() {
        $options = get_option('dasher_dashboard_pages', array());
        $selected = isset($options['big_bird_dashboard_page']) ? $options['big_bird_dashboard_page'] : 0;
        
        echo '<select name="dasher_dashboard_pages[big_bird_dashboard_page]" id="big_bird_dashboard_page">';
        echo '<option value="0">' . esc_html__('— Select a page —', 'dasher') . '</option>';
        
        $pages = get_pages();
        foreach ($pages as $page) {
            echo '<option value="' . esc_attr($page->ID) . '" ' . selected($selected, $page->ID, false) . '>';
            echo esc_html($page->post_title) . ' (/' . esc_html($page->post_name) . ')';
            echo '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">' . esc_html__('Page where Big Birds will be redirected after login. Should contain [dasher_big_bird_dashboard] shortcode.', 'dasher') . '</p>';
        
        if ($selected > 0) {
            $this->show_page_preview($selected, 'dasher_big_bird_dashboard');
        }
    }
    
    /**
     * PC dashboard page field
     */
    public function pc_dashboard_page_callback() {
        $options = get_option('dasher_dashboard_pages', array());
        $selected = isset($options['pc_dashboard_page']) ? $options['pc_dashboard_page'] : 0;
        
        echo '<select name="dasher_dashboard_pages[pc_dashboard_page]" id="pc_dashboard_page">';
        echo '<option value="0">' . esc_html__('— Select a page —', 'dasher') . '</option>';
        
        $pages = get_pages();
        foreach ($pages as $page) {
            echo '<option value="' . esc_attr($page->ID) . '" ' . selected($selected, $page->ID, false) . '>';
            echo esc_html($page->post_title) . ' (/' . esc_html($page->post_name) . ')';
            echo '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">' . esc_html__('Page where Program Candidates will be redirected after login. Should contain [dasher_pc_dashboard] shortcode.', 'dasher') . '</p>';
        
        if ($selected > 0) {
            $this->show_page_preview($selected, 'dasher_pc_dashboard');
        }
    }
    
    /**
     * Enable auto redirect field
     */
    public function enable_auto_redirect_callback() {
        $options = get_option('dasher_dashboard_pages', array());
        $enabled = isset($options['enable_auto_redirect']) ? $options['enable_auto_redirect'] : false;
        
        echo '<label for="enable_auto_redirect">';
        echo '<input type="checkbox" name="dasher_dashboard_pages[enable_auto_redirect]" id="enable_auto_redirect" value="1" ' . checked($enabled, true, false) . '>';
        echo ' ' . esc_html__('Automatically redirect users to their role-specific dashboard after login', 'dasher');
        echo '</label>';
        echo '<p class="description">' . esc_html__('When enabled, users will be automatically redirected to their appropriate dashboard based on their role.', 'dasher') . '</p>';
    }
    
    /**
     * Fallback redirect page field
     */
    public function fallback_redirect_page_callback() {
        $options = get_option('dasher_dashboard_pages', array());
        $selected = isset($options['fallback_redirect_page']) ? $options['fallback_redirect_page'] : 0;
        
        echo '<select name="dasher_dashboard_pages[fallback_redirect_page]" id="fallback_redirect_page">';
        echo '<option value="0">' . esc_html__('WordPress Dashboard', 'dasher') . '</option>';
        
        $pages = get_pages();
        foreach ($pages as $page) {
            echo '<option value="' . esc_attr($page->ID) . '" ' . selected($selected, $page->ID, false) . '>';
            echo esc_html($page->post_title) . ' (/' . esc_html($page->post_name) . ')';
            echo '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">' . esc_html__('Where to redirect users who don\'t have a specific dashboard configured (admins, subscribers, etc.).', 'dasher') . '</p>';
    }
    
    /**
     * Show page preview with shortcode check
     */
    private function show_page_preview($page_id, $expected_shortcode) {
        $page = get_post($page_id);
        if (!$page) return;
        
        $has_shortcode = has_shortcode($page->post_content, $expected_shortcode);
        $status_class = $has_shortcode ? 'notice-success' : 'notice-warning';
        $status_text = $has_shortcode ? 
            sprintf(__('✓ Page contains %s shortcode', 'dasher'), '<code>' . $expected_shortcode . '</code>') :
            sprintf(__('⚠ Page is missing %s shortcode', 'dasher'), '<code>' . $expected_shortcode . '</code>');
        
        echo '<div class="dasher-page-preview notice ' . esc_attr($status_class) . ' inline">';
        echo '<p><strong>' . esc_html__('Selected Page:', 'dasher') . '</strong> ' . esc_html($page->post_title) . '</p>';
        echo '<p>' . $status_text . '</p>';
        echo '<p><a href="' . esc_url(get_edit_post_link($page_id)) . '" target="_blank">' . esc_html__('Edit Page', 'dasher') . '</a> | ';
        echo '<a href="' . esc_url(get_permalink($page_id)) . '" target="_blank">' . esc_html__('View Page', 'dasher') . '</a></p>';
        echo '</div>';
    }
    
    /**
     * Handle login redirect based on user role
     */
    public function handle_login_redirect($user_login, $user) {
        // This method can be used for additional login processing if needed
    }
    
    /**
     * Custom login redirect filter
     */
    public function custom_login_redirect($redirect_to, $request, $user) {
        // Only redirect if auto redirect is enabled
        $options = get_option('dasher_dashboard_pages', array());
        if (empty($options['enable_auto_redirect'])) {
            return $redirect_to;
        }
        
        // Only redirect if user object is available and not an error
        if (!isset($user->ID) || is_wp_error($user)) {
            return $redirect_to;
        }
        
        // Don't redirect if there's a specific redirect_to parameter (like WooCommerce checkout)
        if (!empty($_REQUEST['redirect_to']) && $_REQUEST['redirect_to'] !== admin_url()) {
            return $redirect_to;
        }
        
        // Check user role and redirect accordingly
        if (user_can($user, 'dasher_mentor') && !empty($options['mentor_dashboard_page'])) {
            $page_url = get_permalink($options['mentor_dashboard_page']);
            if ($page_url) {
                return $page_url;
            }
        }
        
        if (user_can($user, 'dasher_bigbird') && !empty($options['big_bird_dashboard_page'])) {
            $page_url = get_permalink($options['big_bird_dashboard_page']);
            if ($page_url) {
                return $page_url;
            }
        }
        
        if (user_can($user, 'dasher_pc') && !empty($options['pc_dashboard_page'])) {
            $page_url = get_permalink($options['pc_dashboard_page']);
            if ($page_url) {
                return $page_url;
            }
        }
        
        // Fallback redirect
        if (!empty($options['fallback_redirect_page'])) {
            $fallback_url = get_permalink($options['fallback_redirect_page']);
            if ($fallback_url) {
                return $fallback_url;
            }
        }
        
        return $redirect_to;
    }
    
    /**
     * Get the dashboard page URL for a specific role
     */
    public static function get_dashboard_url($role) {
        $options = get_option('dasher_dashboard_pages', array());
        
        switch ($role) {
            case 'mentor':
            case 'dasher_mentor':
                if (!empty($options['mentor_dashboard_page'])) {
                    return get_permalink($options['mentor_dashboard_page']);
                }
                break;
                
            case 'bigbird':
            case 'dasher_bigbird':
                if (!empty($options['big_bird_dashboard_page'])) {
                    return get_permalink($options['big_bird_dashboard_page']);
                }
                break;
                
            case 'pc':
            case 'dasher_pc':
                if (!empty($options['pc_dashboard_page'])) {
                    return get_permalink($options['pc_dashboard_page']);
                }
                break;
        }
        
        return false;
    }
}

// Initialize the dashboard settings
new Dasher_Dashboard_Settings();