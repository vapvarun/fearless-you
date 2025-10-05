<?php
/**
 * @package FLI BuddyBoss Child
 * The parent theme functions are located at /buddyboss-theme/inc/theme/functions.php
 * Add your own functions at the bottom of this file.
 */

// Fix BuddyForms PHP 8 compatibility issues
// This runs on every load to ensure the plugin works even after updates

// add_action('muplugins_loaded', 'fix_buddyforms_php8_compatibility', 1);
// function fix_buddyforms_php8_compatibility() {
//     // Check if BuddyForms plugin files exist
//     $files_to_fix = [
//         WP_PLUGIN_DIR . '/buddyforms-premium/includes/form/form-control.php',
//         WP_PLUGIN_DIR . '/buddyforms/includes/form/form-control.php',
//     ];
    
//     foreach ($files_to_fix as $file_path) {
//         if (file_exists($file_path) && is_readable($file_path) && is_writable($file_path)) {
//             fix_buddyforms_ternary_operators($file_path);
//         }
//     }
// }

// function fix_buddyforms_ternary_operators($file_path) {
//     // Check cache first to avoid repeated file operations
//     $cache_key = 'buddyforms_fix_' . md5($file_path);
//     $last_modified = filemtime($file_path);
//     $cached_data = fli_cache_get($cache_key, 'file_check');
    
//     if ($cached_data && $cached_data['last_modified'] === $last_modified) {
//         fli_log_debug('BuddyForms file already processed, skipping', ['file' => $file_path], 'BuddyForms Fix');
//         return;
//     }
    
//     // Validate file path
//     if (!is_file($file_path) || !is_readable($file_path) || !is_writable($file_path)) {
//         fli_log_error("BuddyForms fix: Cannot access file $file_path", ['file' => $file_path], 'BuddyForms Fix');
//         return;
//     }
    
//     // Read the file with error handling
//     $content = file_get_contents($file_path);
//     if ($content === false) {
//         fli_log_error("BuddyForms fix: Failed to read file $file_path", ['file' => $file_path], 'BuddyForms Fix');
//         return;
//     }
    
//     // Store original content to check if changes were made
//     $original_content = $content;
    
//     // Fix the specific line 514 issue with nested ternary operators
//     // Pattern: ! empty( $new_user->user_nicename ) ? $new_user->user_nicename : ! empty( $new_user->user_login ) ? $new_user->user_login : __( 'none', 'buddyforms' )
//     $pattern = '/\$default_post_title\s*=\s*!\s*empty\(\s*\$new_user->user_nicename\s*\)\s*\?\s*\$new_user->user_nicename\s*:\s*!\s*empty\(\s*\$new_user->user_login\s*\)\s*\?\s*\$new_user->user_login\s*:\s*__\(\s*[\'"]none[\'"]\s*,\s*[\'"]buddyforms[\'"]\s*\)\s*;/';
//     $replacement = '$default_post_title = ! empty( $new_user->user_nicename ) ? $new_user->user_nicename : (! empty( $new_user->user_login ) ? $new_user->user_login : __( \'none\', \'buddyforms\' ));';
    
//     $content = preg_replace($pattern, $replacement, $content);
    
//     // Generic pattern to fix other potential unparenthesized ternary operators
//     // This catches patterns like: a ? b : c ? d : e and converts to a ? b : (c ? d : e)
//     // More specific pattern to avoid false positives
//     $generic_pattern = '/(\$[a-zA-Z_][a-zA-Z0-9_]*\s*=\s*[^?]+)\s*\?\s*([^:]+)\s*:\s*([^?]+)\s*\?\s*([^:]+)\s*:\s*([^;]+);/';
//     $generic_replacement = '$1 ? $2 : ($3 ? $4 : $5);';
    
//     $content = preg_replace($generic_pattern, $generic_replacement, $content);
    
//     // Only write back if changes were made and content is valid
//     if ($content !== $original_content && !empty($content)) {
//         $result = file_put_contents($file_path, $content, LOCK_EX);
//         if ($result !== false) {
//             fli_log_info("Fixed BuddyForms PHP 8 compatibility issues in: $file_path", ['file' => $file_path], 'BuddyForms Fix');
            
//             // Cache the successful fix
//             fli_cache_set($cache_key, ['last_modified' => $last_modified, 'fixed' => true], 86400, 'file_check');
//         } else {
//             fli_log_error("BuddyForms fix: Failed to write to file $file_path", ['file' => $file_path], 'BuddyForms Fix');
//         }
//     } else {
//         // Cache that no changes were needed
//         fli_cache_set($cache_key, ['last_modified' => $last_modified, 'fixed' => false], 86400, 'file_check');
//     }
// }

// Load child theme languages
function buddyboss_theme_child_languages() {
    load_theme_textdomain('buddyboss-theme', get_stylesheet_directory() . '/languages');
}
add_action('after_setup_theme', 'buddyboss_theme_child_languages');

// Enqueue child theme scripts and styles
function buddyboss_theme_child_scripts_styles() {
    wp_enqueue_style('buddyboss-child-css', get_stylesheet_directory_uri() . '/assets/css/custom.css');
    wp_enqueue_script('buddyboss-child-js', get_stylesheet_directory_uri() . '/assets/js/custom.js', ['jquery'], null, true);
    
    // Enqueue LearnDash custom styles with cache busting
    if (is_singular(['sfwd-lessons', 'sfwd-topic', 'sfwd-courses', 'sfwd-quiz']) || is_post_type_archive(['sfwd-courses'])) {
        // Check if files exist before trying to enqueue
        // $css_file = get_stylesheet_directory() . '/assets/css/learndash-custom.css';
        $js_file = get_stylesheet_directory() . '/assets/js/learndash-progress-rings.js';
        
        // if (file_exists($css_file)) {
        //     wp_enqueue_style(
        //         'learndash-custom-css', 
        //         get_stylesheet_directory_uri() . '/assets/css/learndash-custom.css',
        //         ['buddyboss-child-css'],
        //         filemtime($css_file)
        //     );
        // }
        
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'learndash-progress-rings',
                get_stylesheet_directory_uri() . '/assets/js/learndash-progress-rings.js',
                ['jquery'],
                filemtime($js_file),
                true
            );
        }
    }
    
    // Enqueue LearnDash video tracking script on lesson/topic pages
    if (is_singular(['sfwd-lessons', 'sfwd-topic'])) {
        wp_enqueue_script(
            'learndash-video-tracking', 
            get_stylesheet_directory_uri() . '/assets/js/learndash-video-tracking.js', 
            ['jquery', 'learndash_video_script'], 
            filemtime(get_stylesheet_directory() . '/assets/js/learndash-video-tracking.js'), 
            true
        );
    }

}
add_action('wp_enqueue_scripts', 'buddyboss_theme_child_scripts_styles', 9999);

// Disable event subscribe links
add_filter('tec_views_v2_use_subscribe_links', '__return_false');

// Shortcode to display the current post title
add_shortcode('post_title', 'post_title_shortcode');
function post_title_shortcode() {
    return get_the_title();
}

// Log password reset attempts
add_action('retrieve_password', 'log_password_reset_attempt');
function log_password_reset_attempt($user_login) {
    $user = get_user_by('login', $user_login);
    if ($user) {
        $log_message = sprintf(
            'Password reset attempted for user: %s at %s',
            $user->user_email,
            current_time('mysql')
        );
        error_log($log_message); // Log the message
    }
}

// Dynamic menu items for profile links
add_filter('wp_nav_menu_objects', 'dynamic_menu_items');
function dynamic_menu_items($menu_items) {
    foreach ($menu_items as $menu_item) {
        if ($menu_item->url === '#profile_link#') {
            $menu_item->url = site_url('/profile');
        }
    }
    return $menu_items;
}

// Mobile BuddyPanel flip functionality for LearnDash focus mode
add_action('wp_enqueue_scripts', 'enqueue_focus_mode_scripts');
function enqueue_focus_mode_scripts() {
    wp_add_inline_script('jquery', "
        jQuery(document).ready(function($) {
            if ($('body').hasClass('ld-focus-mode')) {
                // Only handle mobile BuddyPanel behavior
                if ($(window).width() <= 768) {
                    let buddyPanel = $('.buddypanel');
                    if (buddyPanel.hasClass('buddypanel--toggle-on')) {
                        $('.bb-toggle-panel').trigger('click');
                    }
                }
            }
        });
    ");
}

// Add LearnDash courses to menu editor
add_action('admin_init', 'add_learndash_courses_to_menu');
function add_learndash_courses_to_menu() {
    add_meta_box(
        'learndash-courses-menu-metabox',
        __('LearnDash Courses', 'text-domain'),
        'learndash_courses_menu_metabox_callback',
        'nav-menus',
        'side',
        'default'
    );
}

function learndash_courses_menu_metabox_callback() {
    wp_nonce_field('learndash_courses_nonce_action', 'learndash_courses_nonce');
    echo '<p><input type="text" id="learndash-course-search" style="width: 100%;" placeholder="' . esc_attr__('Search Courses', 'text-domain') . '"></p>';
    echo '<ul id="learndash-course-list" class="categorychecklist form-no-clear">';
    $courses = get_posts(['post_type' => 'sfwd-courses', 'post_status' => 'publish', 'numberposts' => 5]);
    foreach ($courses as $course) {
        echo '<li><label><input type="checkbox" value="' . $course->ID . '"> ' . $course->post_title . '</label></li>';
    }
    echo '</ul>';
}

// Process shortcodes in restricted content messages
add_filter('wpf_restricted_content_message', 'process_shortcodes_in_restricted_content_message');
function process_shortcodes_in_restricted_content_message($message) {
    return do_shortcode($message);
}

// Enable Gutenberg for LearnDash certificates
add_filter('use_block_editor_for_post_type', 'enable_gutenberg_for_certificates', 10, 2);
function enable_gutenberg_for_certificates($can_edit, $post_type) {
    return $post_type === 'sfwd-certificates' ? true : $can_edit;
}

// Add user registration date to user list table
add_filter('manage_users_columns', 'add_user_registration_date_column');
function add_user_registration_date_column($columns) {
    $columns['registration_date'] = __('Registration Date', 'text-domain');
    return $columns;
}

add_filter('manage_users_custom_column', 'show_user_registration_date_column', 10, 3);
function show_user_registration_date_column($value, $column_name, $user_id) {
    if ($column_name === 'registration_date') {
        $user = get_userdata($user_id);
        $value = date_i18n(get_option('date_format'), strtotime($user->user_registered));
    }
    return $value;
}

add_filter('manage_users_sortable_columns', 'make_registration_date_column_sortable');
function make_registration_date_column_sortable($columns) {
    $columns['registration_date'] = 'user_registered';
    return $columns;
}

// Include error logging system
require_once get_stylesheet_directory() . '/includes/error-logging.php';

// Include caching system
require_once get_stylesheet_directory() . '/includes/caching-system.php';

// Include magic link authentication
// require_once get_stylesheet_directory() . '/includes/magic-link-auth.php';

// Include other options handler
require_once get_stylesheet_directory() . '/includes/other-options-handler.php';

// Include role-based logo handler
require_once get_stylesheet_directory() . '/includes/role-based-logo.php';

// REMOVED: BuddyPanel force display - keeping only mobile flip functionality

// Include breadcrumbs
// require_once get_stylesheet_directory() . '/includes/enable-breadcrumbs.php';

// Include theme options
require_once get_stylesheet_directory() . '/inc/admin/admin-init.php';
require_once get_stylesheet_directory() . '/inc/admin/theme-functions.php';
require_once get_stylesheet_directory() . '/inc/admin/dynamic-styles.php';

// Include LearnDash Customizer settings
// Temporarily disabled for debugging
// require_once get_stylesheet_directory() . '/inc/learndash-customizer.php';

// Extend login duration for "Remember Me"
add_filter('auth_cookie_expiration', 'extend_login_duration', 10, 3);
function extend_login_duration($length, $user_id, $remember) {
    if ($remember) {
        // Extend to 90 days instead of 14 days
        return 90 * DAY_IN_SECONDS;
    }
    return $length;
}

// REMOVED: Old auto-login function - replaced by fearless_ip_auto_login_updated() below

// Image Upload Handler
class FLI_Image_Upload_Handler {
    
    public function __construct() {
        add_action('wp_ajax_fli_upload_image', [$this, 'handle_ajax_upload']);
        add_action('wp_ajax_nopriv_fli_upload_image', [$this, 'handle_ajax_upload']);
        add_shortcode('fli_image_upload', [$this, 'render_upload_form']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    public function handle_ajax_upload() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fli_image_upload')) {
            wp_send_json_error(['message' => 'Security check failed']);
        }
        
        // Check if user has permission
        if (!is_user_logged_in() && !apply_filters('fli_allow_guest_uploads', false)) {
            wp_send_json_error(['message' => 'Please log in to upload images']);
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => 'No file uploaded or upload error']);
        }
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = wp_check_filetype($_FILES['image']['name']);
        
        if (!in_array($_FILES['image']['type'], $allowed_types) || !$file_type['ext']) {
            wp_send_json_error(['message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP allowed.']);
        }
        
        // Validate file size (default 5MB)
        $max_size = apply_filters('fli_max_upload_size', 5 * 1024 * 1024);
        if ($_FILES['image']['size'] > $max_size) {
            wp_send_json_error(['message' => 'File too large. Maximum size: ' . size_format($max_size)]);
        }
        
        // Handle the upload
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $upload_overrides = [
            'test_form' => false,
            'mimes' => [
                'jpg|jpeg|jpe' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp'
            ]
        ];
        
        $uploaded = wp_handle_upload($_FILES['image'], $upload_overrides);
        
        if (isset($uploaded['error'])) {
            wp_send_json_error(['message' => $uploaded['error']]);
        }
        
        // Create attachment
        $attachment_data = [
            'post_mime_type' => $uploaded['type'],
            'post_title' => sanitize_file_name(pathinfo($_FILES['image']['name'], PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit'
        ];
        
        $attachment_id = wp_insert_attachment($attachment_data, $uploaded['file']);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => 'Failed to create attachment']);
        }
        
        // Generate metadata
        $metadata = wp_generate_attachment_metadata($attachment_id, $uploaded['file']);
        wp_update_attachment_metadata($attachment_id, $metadata);
        
        // Get image URLs
        $image_urls = [
            'full' => wp_get_attachment_url($attachment_id),
            'thumbnail' => wp_get_attachment_image_url($attachment_id, 'thumbnail'),
            'medium' => wp_get_attachment_image_url($attachment_id, 'medium')
        ];
        
        // Allow plugins to process the upload
        do_action('fli_after_image_upload', $attachment_id, $uploaded);
        
        wp_send_json_success([
            'attachment_id' => $attachment_id,
            'urls' => $image_urls,
            'filename' => basename($uploaded['file'])
        ]);
    }
    
    public function render_upload_form($atts) {
        $atts = shortcode_atts([
            'title' => 'Upload Image',
            'button_text' => 'Select Image',
            'max_size' => 5, // MB
            'allowed_types' => 'jpg,jpeg,png,gif,webp',
            'show_preview' => 'yes',
            'class' => ''
        ], $atts);
        
        // Check if user can upload
        if (!is_user_logged_in() && !apply_filters('fli_allow_guest_uploads', false)) {
            return '<p class="fli-upload-notice">Please log in to upload images.</p>';
        }
        
        ob_start();
        ?>
        <div class="fli-image-upload-wrapper <?php echo esc_attr($atts['class']); ?>">
            <?php if ($atts['title']): ?>
                <h3><?php echo esc_html($atts['title']); ?></h3>
            <?php endif; ?>
            
            <form class="fli-image-upload-form" data-max-size="<?php echo esc_attr($atts['max_size']); ?>">
                <input type="file" 
                       id="fli-image-input" 
                       class="fli-image-input" 
                       accept=".<?php echo str_replace(',', ',.', esc_attr($atts['allowed_types'])); ?>"
                       style="display: none;">
                
                <button type="button" class="fli-upload-button button">
                    <?php echo esc_html($atts['button_text']); ?>
                </button>
                
                <?php if ($atts['show_preview'] === 'yes'): ?>
                    <div class="fli-preview-area" style="display: none;">
                        <img class="fli-preview-image" src="" alt="Preview">
                        <button type="button" class="fli-remove-image">×</button>
                    </div>
                <?php endif; ?>
                
                <div class="fli-upload-progress" style="display: none;">
                    <div class="fli-progress-bar"></div>
                </div>
                
                <div class="fli-upload-message"></div>
                
                <button type="submit" class="fli-submit-upload button button-primary" style="display: none;">
                    Upload Image
                </button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function enqueue_scripts() {
        // Only enqueue on pages that need it
        if (!is_singular() && !is_page() && !is_archive()) {
            return;
        }
        
        wp_enqueue_script(
            'fli-image-upload',
            get_stylesheet_directory_uri() . '/assets/js/image-upload.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_localize_script('fli-image-upload', 'fli_upload', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fli_image_upload'),
            'max_size_error' => 'File size exceeds maximum allowed size',
            'type_error' => 'Invalid file type',
            'upload_error' => 'Upload failed. Please try again.',
            'uploading' => 'Uploading...'
        ]);
        
        // Add inline styles
        wp_add_inline_style('buddyboss-child-css', $this->get_inline_styles());
    }
    
    private function get_inline_styles() {
        return '
            .fli-image-upload-wrapper {
                margin: 20px 0;
            }
            .fli-preview-area {
                position: relative;
                display: inline-block;
                margin: 20px 0;
            }
            .fli-preview-image {
                max-width: 300px;
                max-height: 300px;
                border: 2px solid #ddd;
                border-radius: 4px;
            }
            .fli-remove-image {
                position: absolute;
                top: -10px;
                right: -10px;
                background: #ff4444;
                color: white;
                border: none;
                border-radius: 50%;
                width: 30px;
                height: 30px;
                cursor: pointer;
                font-size: 20px;
                line-height: 1;
            }
            .fli-upload-progress {
                width: 100%;
                height: 20px;
                background: #f0f0f0;
                border-radius: 10px;
                margin: 20px 0;
                overflow: hidden;
            }
            .fli-progress-bar {
                height: 100%;
                background: #4CAF50;
                width: 0;
                transition: width 0.3s;
            }
            .fli-upload-message {
                margin: 10px 0;
                padding: 10px;
                border-radius: 4px;
                display: none;
            }
            .fli-upload-message.success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
                display: block;
            }
            .fli-upload-message.error {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
                display: block;
            }
            .fli-upload-button {
                margin: 10px 0;
            }
            .fli-submit-upload {
                margin: 10px 0;
            }
        ';
    }
}

// Initialize the image upload handler
new FLI_Image_Upload_Handler();

// Image Gallery Shortcode
add_shortcode('fli_image_gallery', 'fli_render_image_gallery');
function fli_render_image_gallery($atts) {
    $atts = shortcode_atts([
        'title' => 'Image Gallery',
        'max_images' => 10,
        'columns' => 3,
        'allow_upload' => 'yes',
        'user_id' => get_current_user_id()
    ], $atts);
    
    ob_start();
    ?>
    <div class="fli-image-gallery" data-max="<?php echo esc_attr($atts['max_images']); ?>">
        <?php if ($atts['title']): ?>
            <h3><?php echo esc_html($atts['title']); ?></h3>
        <?php endif; ?>
        
        <?php if ($atts['allow_upload'] === 'yes' && is_user_logged_in()): ?>
            <div class="fli-gallery-upload">
                <?php echo do_shortcode('[fli_image_upload title="" button_text="Add to Gallery" class="gallery-upload"]'); ?>
            </div>
        <?php endif; ?>
        
        <div class="fli-gallery-images columns-<?php echo esc_attr($atts['columns']); ?>">
            <?php
            // Get user's uploaded images
            $args = [
                'post_type' => 'attachment',
                'post_mime_type' => 'image',
                'post_status' => 'inherit',
                'posts_per_page' => $atts['max_images'],
                'author' => $atts['user_id']
            ];
            
            $images = get_posts($args);
            
            foreach ($images as $image) {
                $medium_url = wp_get_attachment_image_url($image->ID, 'medium');
                $full_url = wp_get_attachment_image_url($image->ID, 'full');
                ?>
                <div class="fli-gallery-item" data-id="<?php echo $image->ID; ?>">
                    <a href="<?php echo esc_url($full_url); ?>" target="_blank">
                        <img src="<?php echo esc_url($medium_url); ?>" alt="<?php echo esc_attr($image->post_title); ?>">
                    </a>
                    <button class="fli-gallery-remove" data-id="<?php echo $image->ID; ?>">×</button>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
    
    <style>
        .fli-image-gallery {
            margin: 20px 0;
        }
        .fli-gallery-images {
            display: grid;
            gap: 20px;
            margin-top: 20px;
        }
        .fli-gallery-images.columns-2 {
            grid-template-columns: repeat(2, 1fr);
        }
        .fli-gallery-images.columns-3 {
            grid-template-columns: repeat(3, 1fr);
        }
        .fli-gallery-images.columns-4 {
            grid-template-columns: repeat(4, 1fr);
        }
        .fli-gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 8px;
            background: #f5f5f5;
        }
        .fli-gallery-item img {
            width: 100%;
            height: auto;
            display: block;
        }
        .fli-gallery-item a {
            display: block;
        }
        .fli-gallery-remove {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 68, 68, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            font-size: 20px;
            line-height: 1;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .fli-gallery-item:hover .fli-gallery-remove {
            opacity: 1;
        }
        @media (max-width: 768px) {
            .fli-gallery-images {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }
        @media (max-width: 480px) {
            .fli-gallery-images {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
    <?php
    return ob_get_clean();
}

// Helper function to get user's real IP address with caching
function get_user_ip_address() {
    // Check cache first
    $cache_key = 'user_ip_' . md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $cached_ip = fli_cache_get($cache_key, 'ip_lookup');
    
    if ($cached_ip !== false) {
        fli_log_debug('IP address retrieved from cache', ['ip' => $cached_ip], 'IP Lookup');
        return $cached_ip;
    }
    
    // Check for various headers that might contain the real IP
    $ip_headers = array(
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_CLIENT_IP',            // Proxy
        'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
        'HTTP_X_FORWARDED',          // Proxy
        'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
        'HTTP_FORWARDED_FOR',        // Proxy
        'HTTP_FORWARDED',            // Proxy
        'REMOTE_ADDR'                // Standard
    );
    
    foreach ($ip_headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            
            // Handle comma-separated IPs (from proxies)
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            
            // Validate IP address
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                // Cache the result for 1 hour
                fli_cache_set($cache_key, $ip, 3600, 'ip_lookup');
                fli_log_debug('IP address determined and cached', ['ip' => $ip, 'header' => $header], 'IP Lookup');
                return $ip;
            }
        }
    }
    
    // Fallback to REMOTE_ADDR even if it's private/reserved
    $fallback_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    fli_cache_set($cache_key, $fallback_ip, 3600, 'ip_lookup');
    fli_log_debug('IP address fallback used and cached', ['ip' => $fallback_ip], 'IP Lookup');
    
    return $fallback_ip;
}

// Add admin page to manage Jonathan's IP addresses
add_action('admin_menu', 'add_jonathan_ip_management_page');
function add_jonathan_ip_management_page() {
    add_options_page(
        'Jonathan IP Management',
        'Jonathan Auto-Login IPs',
        'manage_options',
        'jonathan-ip-management',
        'jonathan_ip_management_page'
    );
}

function jonathan_ip_management_page() {
    if (isset($_POST['save_ips'])) {
        $ips = sanitize_textarea_field($_POST['jonathan_ips']);
        $ip_array = array_filter(array_map('trim', explode("\n", $ips)));
        update_option('jonathan_auto_login_ips', $ip_array);
        echo '<div class="notice notice-success"><p>IP addresses saved!</p></div>';
    }
    
    $current_ip = get_user_ip_address();
    $saved_ips = get_option('jonathan_auto_login_ips', array());
    $ips_text = implode("\n", $saved_ips);
    
    ?>
    <div class="wrap">
        <h1>Jonathan Auto-Login IP Management</h1>
        <p><strong>Your current IP address:</strong> <?php echo esc_html($current_ip); ?></p>
        
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row">Allowed IP Addresses</th>
                    <td>
                        <textarea name="jonathan_ips" rows="10" cols="50" class="large-text"><?php echo esc_textarea($ips_text); ?></textarea>
                        <p class="description">Enter one IP address per line. Jonathan will be automatically logged in when accessing from these IPs.</p>
                        <p><button type="button" onclick="addCurrentIP()" class="button">Add Current IP (<?php echo esc_html($current_ip); ?>)</button></p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save IP Addresses', 'primary', 'save_ips'); ?>
        </form>
    </div>
    
    <script>
    function addCurrentIP() {
        var textarea = document.querySelector('textarea[name="jonathan_ips"]');
        var currentIP = '<?php echo esc_js($current_ip); ?>';
        var currentText = textarea.value.trim();
        
        if (currentText && !currentText.endsWith('\n')) {
            currentText += '\n';
        }
        
        if (currentText.indexOf(currentIP) === -1) {
            textarea.value = currentText + currentIP;
        } else {
            alert('This IP address is already in the list.');
        }
    }
    </script>
    <?php
}

// Update the IP checking function to use the saved IPs
add_action('init', 'fearless_ip_auto_login_updated');
function fearless_ip_auto_login_updated() {
    // Only run if user is not already logged in
    if (is_user_logged_in()) {
        return;
    }
    
    // Skip auto-login if debug parameter is present
    if (isset($_GET['debug']) || isset($_GET['no_auto_login'])) {
        return;
    }
    
    // Check if user has declined auto-login for this session
    if (isset($_COOKIE['fearless_decline_autologin']) && $_COOKIE['fearless_decline_autologin'] === 'true') {
        return;
    }
    
    // Get user's IP address
    $user_ip = get_user_ip_address();
    
    // Define IP to user mappings
    $ip_user_mappings = array(
        '72.132.26.73' => 'jonathan-fym',
        '97.97.68.210' => 'support@fearlessliving.org'
    );
    
    // Check if current IP has a mapping
    if (array_key_exists($user_ip, $ip_user_mappings)) {
        $user_identifier = $ip_user_mappings[$user_ip];
        
        // Check if user has already confirmed auto-login
        if (isset($_COOKIE['fearless_confirmed_autologin']) && $_COOKIE['fearless_confirmed_autologin'] === 'true') {
            // Get user account by email or login
            $user = strpos($user_identifier, '@') !== false 
                ? get_user_by('email', $user_identifier) 
                : get_user_by('login', $user_identifier);
            
            if ($user) {
                // Auto-login user
                wp_set_current_user($user->ID);
                wp_set_auth_cookie($user->ID, true); // Remember for 90 days
                
                // Log the auto-login for security
                error_log("Auto-login for {$user->user_login} from IP: $user_ip at " . current_time('mysql'));
                
                // Redirect to refresh the page
                wp_redirect(home_url($_SERVER['REQUEST_URI']));
                exit;
            }
        } else {
            // Show confirmation bar
            add_action('wp_footer', 'fearless_autologin_confirmation_bar');
        }
    }
}

// Old function already removed above

/**
 * Display auto-login confirmation bar
 */
function fearless_autologin_confirmation_bar() {
    // Get user's IP to determine which user they'll be logged in as
    $user_ip = get_user_ip_address();
    $ip_user_mappings = array(
        '72.132.26.73' => 'Jonathan',
        '97.97.68.210' => 'Support'
    );
    $user_name = isset($ip_user_mappings[$user_ip]) ? $ip_user_mappings[$user_ip] : 'User';
    ?>
    <div id="fearless-autologin-bar" style="position: fixed; bottom: 0; left: 0; right: 0; background: #2c3e50; color: white; padding: 15px; text-align: center; z-index: 99999; box-shadow: 0 -2px 10px rgba(0,0,0,0.3);">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
            <div style="flex: 1;">
                <strong>Auto-login available</strong> - Would you like to stay logged in as <?php echo esc_html($user_name); ?> on this device?
            </div>
            <div style="display: flex; gap: 10px;">
                <button onclick="confirmAutoLogin()" style="background: #27ae60; color: white; border: none; padding: 8px 20px; border-radius: 4px; cursor: pointer; font-size: 14px;">Yes, keep me logged in</button>
                <button onclick="declineAutoLogin()" style="background: #e74c3c; color: white; border: none; padding: 8px 20px; border-radius: 4px; cursor: pointer; font-size: 14px;">No, I'll login manually</button>
            </div>
        </div>
    </div>
    <script>
    function confirmAutoLogin() {
        // Set cookie to remember the choice
        document.cookie = "fearless_confirmed_autologin=true; path=/; max-age=31536000"; // 1 year
        // Reload the page to trigger auto-login
        window.location.reload();
    }
    
    function declineAutoLogin() {
        // Set session cookie to decline auto-login
        document.cookie = "fearless_decline_autologin=true; path=/"; // Session cookie
        // Hide the bar
        document.getElementById('fearless-autologin-bar').style.display = 'none';
    }
    </script>
    <?php
}

/**
 * Clear auto-login preference on logout
 */
add_action('wp_logout', 'clear_fearless_autologin_preference');
function clear_fearless_autologin_preference() {
    // Get user's IP
    $user_ip = get_user_ip_address();
    $ip_user_mappings = array(
        '72.132.26.73' => 'jonathan-fym',
        '97.97.68.210' => 'support@fearlessliving.org'
    );
    
    // Only clear if this IP has auto-login configured
    if (array_key_exists($user_ip, $ip_user_mappings)) {
        // Set cookie to expire both preferences
        setcookie('fearless_confirmed_autologin', '', time() - 3600, '/');
        setcookie('fearless_decline_autologin', 'true', 0, '/'); // Session cookie to prevent auto-login
    }
}

// Sort users by registration date in admin
add_action('pre_get_users', 'sort_users_by_registration_date');
function sort_users_by_registration_date($query) {
    if (is_admin() && isset($query->query_vars['orderby']) && $query->query_vars['orderby'] === 'user_registered') {
        $query->query_vars['orderby'] = 'user_registered';
    }
}

// Remove .map references in CSS/JS
add_action('admin_menu', 'register_remove_map_references_page');
function register_remove_map_references_page() {
    add_management_page(
        __('Remove .map References', 'text-domain'),
        __('Remove .map References', 'text-domain'),
        'manage_options',
        'remove-map-references',
        'execute_remove_map_references'
    );
}

function execute_remove_map_references() {
    $theme_dir = get_template_directory();
    $file_types = ['css', 'js'];
    $updated_files = 0;

    foreach ($file_types as $type) {
        $files = glob($theme_dir . "/**/*.$type");
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $updated_content = preg_replace('/(\/\*# sourceMappingURL=.*\*\/|\/\/# sourceMappingURL=.*$)/m', '', $content);
            if ($content !== $updated_content) {
                file_put_contents($file, $updated_content);
                $updated_files++;
            }
        }
    }
    echo '<p>' . sprintf(__('%d files updated.', 'text-domain'), $updated_files) . '</p>';
}
// Consolidated admin bar management - single function to handle all admin bar requirements
add_action('after_setup_theme', 'fli_manage_admin_bar', PHP_INT_MAX);
function fli_manage_admin_bar() {
    // Only for administrators
    if (!current_user_can('administrator')) {
        return;
    }
    
    // Force admin bar to show
    show_admin_bar(true);
    add_filter('show_admin_bar', '__return_true', PHP_INT_MAX);
    
    // Add CSS to ensure admin bar is visible
    add_action('wp_head', 'fli_admin_bar_css', 1);
}

function fli_admin_bar_css() {
    if (!is_admin_bar_showing()) {
        return;
    }
    ?>
    <style>
        body { margin-top: 32px !important; }
        #wpadminbar { 
            display: block !important; 
            position: fixed !important; 
            top: 0 !important; 
            z-index: 999999 !important;
        }
        @media screen and (max-width: 782px) {
            body { margin-top: 46px !important; }
        }
    </style>
    <?php
}

// Add LearnDash lesson list styling with less aggressive overrides
add_action('wp_head', 'fli_learndash_lesson_list_inline_css', 999);
function fli_learndash_lesson_list_inline_css() {
    // Only apply on LearnDash pages
    if (!is_singular(['sfwd-lessons', 'sfwd-topic', 'sfwd-courses', 'sfwd-quiz']) && !is_post_type_archive(['sfwd-courses'])) {
        return;
    }
    ?>
    <style id="fli-learndash-override">
    /* LearnDash Lesson List Styling - Less Aggressive */
    .ld-item-list,
    ul.ld-item-list,
    .learndash-wrapper .ld-item-list {
        padding: 0;
        margin: 0 0 20px 0;
        list-style: none;
    }
    
    .ld-item-list .ld-item-list-item,
    .ld-item-list li,
    ul.ld-item-list > li,
    .learndash-wrapper li.ld-item-list-item {
        padding: 20px 25px;
        margin-bottom: 12px;
        background: #ffffff;
        border: 2px solid #e7e9ec;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        list-style: none;
        display: block;
    }
    
    .ld-item-list .ld-item-list-item:hover,
    ul.ld-item-list > li:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transform: translateY(-3px);
        border-color: #59898d;
        background: #f8f9fa;
    }
    
    /* Mobile responsiveness */
    @media (max-width: 768px) {
        .ld-item-list .ld-item-list-item,
        ul.ld-item-list > li {
            padding: 15px 18px;
            margin-bottom: 10px;
        }
    }
    
    @media (max-width: 480px) {
        .ld-item-list .ld-item-list-item,
        ul.ld-item-list > li {
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 8px;
        }
    }
    </style>
    <?php
}

// REMOVED: Duplicate admin bar function - consolidated above



function rename_learndash_final_quiz( $translated_text, $text, $domain ) {
    if ( $domain === 'learndash' ) {
        if ( $text === 'Final Quizzes' ) {
            return 'Final Exams';
        }
    }
    return $translated_text;
}
add_filter( 'gettext', 'rename_learndash_final_quiz', 10, 3 );
function rename_learndash_section_quizzes( $translated_text, $text, $domain ) {
    if ( $domain === 'learndash' ) {
        // Change "Quizzes" to "Final Exam" ONLY if it's the section title
        if ( $text === 'Quizzes' && is_singular( 'sfwd-courses' ) ) {
            return 'Final Exam';
        }
    }
    return $translated_text;
}
add_filter( 'gettext', 'rename_learndash_section_quizzes', 10, 3 );
function rename_learndash_course_list_labels( $translated_text, $text, $domain ) {
    if ( $domain === 'learndash' ) {
        // Check if the text is "Quizzes" in the course list and change to "Final Exam"
        if ( $text === 'Quizzes' && is_archive() ) {
            return 'Final Exam';
        }
    }
    return $translated_text;
}
add_filter( 'gettext', 'rename_learndash_course_list_labels', 10, 3 );


// AJAX handler for checking user status and processing WP Fusion data
add_action('wp_ajax_check_user_status', 'handle_user_status_check');
add_action('wp_ajax_nopriv_check_user_status', 'handle_user_status_check');

function handle_user_status_check() {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'secure-ajax-nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }

    // Get the parameters from the AJAX call
    $contact_id = sanitize_text_field($_POST['contact_id']);
    $email = sanitize_email($_POST['email']);
    $course_id = sanitize_text_field($_POST['course_id']);
    $force_create = isset($_POST['force_create']) && $_POST['force_create'] === 'true';

    error_log("AJAX Handler: Processing contact - Email: $email, Contact ID: $contact_id");

    // Step 1: Process with WP Fusion first
    $wpf_result = process_wpf_contact_data($email, $contact_id);
    error_log("WP Fusion Result: " . print_r($wpf_result, true));
    
    // Step 2: Check if WordPress user exists
    $user = get_user_by('email', $email);
    
    if (!$user && $force_create) {
        // Create user if forced and doesn't exist
        $user = create_user_from_contact_data($email, $contact_id);
        error_log("Created new user: " . ($user ? $user->user_login : 'failed'));
    }
    
    if ($user) {
        // Log the user in
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        
        // Determine where to redirect
        $redirect_url = determine_redirect_url($user, $course_id);
        
        error_log("User logged in successfully, redirecting to: $redirect_url");
        
        wp_send_json_success(array(
            'message' => 'User logged in successfully',
            'redirect_url' => $redirect_url,
            'user_id' => $user->ID,
            'wpf_result' => $wpf_result
        ));
    } else {
        // User doesn't exist yet, continue polling
        error_log("User not found yet, will continue polling...");
        wp_send_json_error(array(
            'message' => 'User not found, will retry...',
            'wpf_result' => $wpf_result
        ));
    }
}

/**
 * Process contact data with WP Fusion
 */
function process_wpf_contact_data($email, $contact_id) {
    // Check if WP Fusion is active
    if (!function_exists('wp_fusion')) {
        error_log('WP Fusion is not active or not loaded');
        return array('success' => false, 'message' => 'WP Fusion not active');
    }

    try {
        // Prepare contact data for WP Fusion
        $contact_data = array(
            'user_email' => $email,
            'send_notification' => false,
            'wpf_action' => 'add'
        );

        // Add first name if available
        if (isset($_POST['first_name']) && !empty($_POST['first_name'])) {
            $contact_data['first_name'] = sanitize_text_field($_POST['first_name']);
        }

        // Add Infusionsoft contact ID if available
        if (!empty($contact_id)) {
            $contact_data['infusionsoft_contact_id'] = $contact_id;
        }

        error_log('WP Fusion: Attempting to add contact with data: ' . print_r($contact_data, true));

        // Create or update contact in CRM
        $crm_contact_id = wp_fusion()->crm->add_contact($contact_data);

        if (is_wp_error($crm_contact_id)) {
            error_log('WP Fusion Error: ' . $crm_contact_id->get_error_message());
            return array(
                'success' => false, 
                'message' => $crm_contact_id->get_error_message()
            );
        }

        // Log success
        error_log('WP Fusion: Successfully processed contact - ' . $email . ' with CRM ID: ' . $crm_contact_id);
        
        return array(
            'success' => true,
            'message' => 'Contact processed successfully',
            'contact_id' => $crm_contact_id
        );

    } catch (Exception $e) {
        error_log('WP Fusion Exception: ' . $e->getMessage());
        return array(
            'success' => false,
            'message' => $e->getMessage()
        );
    }
}

/**
 * Create a WordPress user from contact data
 */
function create_user_from_contact_data($email, $contact_id) {
    // Generate username from email
    $username = sanitize_user(str_replace('@', '_', $email));
    
    // Make sure username is unique
    $original_username = $username;
    $counter = 1;
    while (username_exists($username)) {
        $username = $original_username . '_' . $counter;
        $counter++;
    }
    
    $password = wp_generate_password(12, false);
    
    // Get first name from POST data
    $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
    
    $user_data = array(
        'user_login' => $username,
        'user_email' => $email,
        'user_pass' => $password,
        'first_name' => $first_name,
        'role' => 'subscriber' // or whatever default role you want
    );
    
    $user_id = wp_insert_user($user_data);
    
    if (is_wp_error($user_id)) {
        error_log('Failed to create user: ' . $user_id->get_error_message());
        return false;
    }
    
    // Add contact ID as user meta if available
    if (!empty($contact_id)) {
        update_user_meta($user_id, 'infusionsoft_contact_id', $contact_id);
    }
    
    error_log('Successfully created user: ' . $username . ' with ID: ' . $user_id);
    
    return get_user_by('id', $user_id);
}

/**
 * Determine where to redirect the user after login
 */
function determine_redirect_url($user, $course_id = '') {
    // If course ID is provided, redirect to course
    if (!empty($course_id)) {
        // Modify this URL based on your course structure
        return home_url("/course/$course_id/");
    }
    
    // Check if user has a specific role-based redirect
    if (in_array('subscriber', $user->roles)) {
        return home_url('/dashboard/'); // or wherever subscribers should go
    }
    
    // Default redirect
    return home_url('/my-account/');
}

/**
 * Handle fallback form submission (when AJAX fails)
 */
add_action('init', 'handle_fallback_form_submission');

function handle_fallback_form_submission() {
    if (isset($_POST['fallback']) && $_POST['fallback'] === 'true') {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'secure-ajax-nonce')) {
            wp_die('Security check failed');
        }
        
        $contact_id = sanitize_text_field($_POST['contact_id']);
        $email = sanitize_email($_POST['email']);
        $course_id = sanitize_text_field($_POST['course_id']);
        $first_name = sanitize_text_field($_POST['first_name']);
        
        error_log("Fallback form submission for: $email");
        
        // Process WP Fusion
        $_POST['first_name'] = $first_name; // Make sure it's available for the function
        $wpf_result = process_wpf_contact_data($email, $contact_id);
        
        // Try to find or create user
        $user = get_user_by('email', $email);
        if (!$user) {
            $user = create_user_from_contact_data($email, $contact_id);
        }
        
        if ($user) {
            // Log user in
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID);
            
            $redirect_url = determine_redirect_url($user, $course_id);
            wp_redirect($redirect_url);
            exit;
        } else {
            // Fallback to login page with message
            wp_redirect(wp_login_url() . '?message=account_setup_failed');
            exit;
        }
    }
}

/**
 * Custom Login Page Styles - Remove split layout and use background images
 */
add_action('login_enqueue_scripts', 'fearless_custom_login_styles', 999);
function fearless_custom_login_styles() {
    $theme_url = get_stylesheet_directory_uri();
    ?>
    <style>
        /* Force remove default split layout */
        /* body.login,
        body.login-split-page {
            background: none !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-height: 100vh !important;
            position: relative !important;
            overflow: hidden !important;
        }
         */
        /* Full screen background for desktop */
        body.login::before {
            content: '' !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background-image: url('<?php echo $theme_url; ?>/FYM-Login-Desktop.jpg') !important;
            background-size: cover !important;
            background-position: center !important;
            background-repeat: no-repeat !important;
            z-index: -1 !important;
        }
        
        /* Remove ALL BuddyBoss split layouts */
        .login-split,
        .bb-login-section,
        .bb-login-right-section,
        .login-split-left,
        .login-split-right {
            display: none !important;
        }
        
        /* Override BuddyBoss container styles */
        .bb-login .login-form-wrap,
        .login .login-form-wrap {
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
            max-width: none !important;
        }
        
        /* Position login container properly */
        #login {
            width: 350px !important;
            position: absolute !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            margin: 0 !important;
            padding: 0 !important;
            z-index: 10 !important;
        }
        
        /* Adjust form positioning to align with white board */
        #loginform,
        #registerform,
        #lostpasswordform {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            padding: 30px !important;
            margin: 0 !important;
            transform: translateX(-10%) translateY(-10%) !important;
        }
        
        /* Hide background for form elements */
        .login-heading,
        form,
        .login form {
            background: transparent !important;
        }

        body.login.login-action-magic_login div#login{
            left: calc(50% - 0px) !important;
            max-width: 350px !important;
            padding: 30px !important;
        }

        body.login.login-action-magic_login .privacy-policy-page-link{
            transform: translateX(0%) translateY(-10%) !important;
        }
                
        /* Style form inputs */
        /* .login input[type="text"],
        .login input[type="password"],
        .login input[type="email"] {
            background: rgba(255, 255, 255, 0.9) !important;
            border: 1px solid #ddd !important;
            padding: 10px 15px !important;
            font-size: 16px !important;
            width: 100% !important;
            margin-bottom: 15px !important;
            box-shadow: none !important;
            color: #333 !important;
        }
         */
        /* Ensure placeholder text is visible with good contrast */
        /* .login input::placeholder {
            color: #666 !important;
            opacity: 1 !important;
        }
        
        .login input::-webkit-input-placeholder {
            color: #666 !important;
            opacity: 1 !important;
        }
        
        .login input::-moz-placeholder {
            color: #666 !important;
            opacity: 1 !important;
        }
        
        .login input:-ms-input-placeholder {
            color: #666 !important;
            opacity: 1 !important;
        } */
        
        /* Focus styles for accessibility */
        /* .login input:focus,
        .login button:focus,
        .login .button:focus {
            outline: 2px solid #5A7891 !important;
            outline-offset: 2px !important;
            box-shadow: 0 0 0 3px rgba(90, 120, 145, 0.2) !important;
        } */
        
        /* Style submit button */
        /* .login .button-primary,
        .login input[type="submit"] {
            background: #5A7891 !important;
            border: none !important;
            color: white !important;
            padding: 12px 30px !important;
            font-size: 16px !important;
            font-weight: 600 !important;
            width: 100% !important;
            cursor: pointer !important;
            transition: background 0.3s !important;
            box-shadow: none !important;
        }
        
        .login .button-primary:hover,
        .login input[type="submit"]:hover {
            background: #486478 !important;
        }
         */

        .login .button-primary,
        .login input[type="submit"]{
            margin-top: 0px !important;
            line-height: normal !important;
        } 

        /* Style links */
        .login a {
            color: #5A7891 !important;
            text-decoration: none !important;
        }
        
        .login a:hover {
            text-decoration: underline !important;
        }
        

        #login form label#user_label:before{
            content: "\ef52";
            color: var(--bb-body-text-color);
        }

        .magic-login-or-separator:before{
            background-color: #f6f7fb !important;
        }

        /* Tablet and Mobile styles */
        @media (max-width: 1024px) {
            body.login::before {
                background-image: url('<?php echo $theme_url; ?>/rhonda-mobile-login.jpg') !important;
            }
            
            #login {
                width: 90% !important;
                max-width: 300px !important;
                top: 70% !important;
                left: 50% !important;
                transform: translate(calc(-50% - 20px), calc(-50% - 10px)) !important;
            }
            
            #loginform,
            #registerform,
            #lostpasswordform {
                transform: translateX(0) translateY(0) !important;
                padding: 15px !important;
            }
            
            /* Hide labels on mobile, use placeholders instead */
            .login-heading h2 {
                display: none !important;
            }
            
            /* .login label {
                display: none !important;
            } */
            
            /* Compact form inputs */
            /* .login input[type="text"],
            .login input[type="password"],
            .login input[type="email"] {
                padding: 8px 12px !important;
                font-size: 14px !important;
                margin-bottom: 10px !important;
            } */
            
            /* Compact submit button */
            /* .login .button-primary,
            .login input[type="submit"] {
                padding: 10px 20px !important;
                font-size: 14px !important;
                margin-top: 10px !important;
            } */
            
            /* Compact Remember Me */
            /* .login .forgetmenot {
                margin: 10px 0 !important;
            }
            
            .login .forgetmenot label {
                font-size: 12px !important;
                gap: 6px !important;
            }
            
            .login .forgetmenot input[type="checkbox"] {
                width: 14px !important;
                height: 14px !important;
            } */

        }
        
        /* Mobile specific adjustments */
        @media (max-width: 768px) {
            #login {
                top: 75% !important;
                max-width: 280px !important;
                transform: translate(calc(-50% - 20px), calc(-50% - 10px)) !important;
            }
            
            #loginform,
            #registerform,
            #lostpasswordform {
                padding: 10px !important;
            }

            body.login #login .privacy-policy-page-link {
                transform: translateX(0%) translateY(0%) !important;
            }
        }
        
        /* Hide WordPress logo */
        .login h1,
        #login h1 {
            display: none !important;
        }
        
        /* Style error messages */
        .login .message,
        .login #login_error {
            background: rgba(255, 255, 255, 0.95) !important;
            border-left: 4px solid #dc3545 !important;
            padding: 12px !important;
            margin-bottom: 20px !important;
            color: #333 !important;
        }
        
        .login #login_error {
            border-left-color: #dc3545 !important;
        }
        
        .login .message {
            border-left-color: #5A7891 !important;
        }
        
        /* Ensure all text in messages is visible */
        .login .message *,
        .login #login_error * {
            color: #333 !important;
        }
        
        /* Fine-tune positioning for larger screens */
        @media (min-width: 1200px) {
            #login {
                transform: translate(-45%, -50%) !important;
            }
        }
        
        /* Fine-tune mobile positioning */
        @media (max-width: 480px) {
            #login {
                top: 80% !important;
                transform: translate(calc(-50% - 20px), calc(-50% - 10px)) !important;
            }
        }
        
        /* Force hide any BuddyBoss theme elements */
        .bb-login-subtitle,
        .bb-login-footer,
        .login-split-part,
        .login-split-right,
        body.login-split-page .login-split {
            display: none !important;
        }
        
        /* Ensure proper form container behavior */
        .login-form-wrap {
            position: static !important;
            transform: none !important;
            margin: 0 !important;
            padding: 0 !important;
            width: auto !important;
        }
        
        /* Remove any default WordPress login positioning */
        body.login div#login {
            position: absolute !important;
            left: calc(55% - 20px) !important;
            top: 60% !important;
            transform: translate(-50%, -50%) !important;
            margin: 0 auto !important;
            padding: 0 !important;
        }
        
        /* Ensure the heading stays with the form */
        .login-heading {
            margin-top: 0px !important;
            margin-bottom: 10px !important;
            text-align: center !important;
        }
        
        .login-heading h2 {
            color: #333 !important;
            font-size: 24px !important;
            margin: 0 0 10px 0 !important;
            text-align: center !important;
        }
        
        .login-heading span a {
            font-size: 14px !important;
        }
        
        /* Hide Create an Account link */
        .login-heading span {
            display: none !important;
        }
        
        /* Style Remember Me checkbox */
        /* .login .forgetmenot {
            margin: 15px 0 !important;
            text-align: left !important;
        } */
        
        /* .login .forgetmenot label {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            font-size: 14px !important;
            cursor: pointer !important;
            color: #333 !important;
        } */
        
        /* .login .forgetmenot input[type="checkbox"] {
            margin: 0 !important;
            width: 16px !important;
            height: 16px !important;
            flex-shrink: 0 !important;
            opacity: 1 !important;
            position: relative !important;
            z-index: 1 !important;
        } */
        
        /* Style submit button container */
        /* .login .submit {
            margin-top: 20px !important;
            margin-bottom: 15px !important;
        } */
        
        /* Hide bottom navigation links */
        .login #nav {
            display: none !important;
        }
        
        /* Hide password hint text */
        /* .login .message,
        .login .login-message {
            display: none !important;
        } */
        
        /* Hide HR separator */
        .login hr {
            display: none !important;
        }
        
        /* Center terms of use text */
        .login .privacy-policy-page-link,
        .login .privacy-policy-link,
        .login .terms-of-use-link,
        .login .terms-link {
            text-align: center !important;
            display: block !important;
            margin: 15px auto !important;
        }
        
        body.login .privacy-policy-page-link{
            transform: translateX(-15%) translateY(-10%) !important;
            margin-top: 0 !important;
        }

       body.login .privacy-policy-page-link .terms-link{
            margin: 0 !important;
        }


        /* Center any footer links */
        .login .login-footer,
        .login .login-links {
            text-align: center !important;
        }

        .login #login_error{
            margin-left: -30px;
        }

    </style>
    <script>
        // Add placeholders and accessibility improvements
        document.addEventListener('DOMContentLoaded', function() {
            // Add placeholders for ALL devices (not just mobile) for better accessibility
            var userLogin = document.getElementById('user_login');
            var userPass = document.getElementById('user_pass');
            
            if (userLogin) {
                userLogin.placeholder = 'Username or Email Address';
                userLogin.setAttribute('aria-label', 'Username or Email Address');
                userLogin.setAttribute('autocomplete', 'username');
            }
            if (userPass) {
                userPass.placeholder = 'Password';
                userPass.setAttribute('aria-label', 'Password');
                userPass.setAttribute('autocomplete', 'current-password');
            }
            
            // Add aria-label to remember me checkbox
            var rememberMe = document.getElementById('rememberme');
            if (rememberMe) {
                rememberMe.setAttribute('aria-label', 'Remember Me');
            }
            
            // Add aria-label to submit button
            var submitBtn = document.getElementById('wp-submit');
            if (submitBtn) {
                submitBtn.setAttribute('aria-label', 'Login with Password');
            }
            
            if (window.innerWidth <= 1024) {
                // Hide any emoji or decorative elements on mobile
                var emojis = document.querySelectorAll('.emoji, [data-emoji], .wp-emoji');
                emojis.forEach(function(emoji) {
                    emoji.style.display = 'none';
                });
            }
        });
    </script>
    <?php
}

/**
 * Change login button text to "Login with Password"
 */
add_filter('gettext', 'fearless_change_login_button_text', 20, 3);
function fearless_change_login_button_text($translated_text, $text, $domain) {
    if ($text === 'Log In' && $GLOBALS['pagenow'] === 'wp-login.php') {
        return 'Login with Password';
    }
    return $translated_text;
}

/**
 * Remove default error message on login screen
 */
add_action('login_enqueue_scripts', 'fearless_remove_default_login_errors');
function fearless_remove_default_login_errors() {
    ?>
    <style>
        /* Hide default error messages that appear on first load */
        .login #login_error:empty {
            display: none !important;
        }
        
        /* Move sign in button 30px to the left */
        /* .login .button-primary,
        .login input[type="submit"] {
            margin-left: -30px !important;
        }
         */
        /* Ensure button container accommodates the shift */
        .login .submit {
            position: relative !important;
            overflow: visible !important;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Remove empty error messages on page load
            var errorDiv = document.getElementById('login_error');
            if (errorDiv && errorDiv.innerHTML.trim() === '') {
                errorDiv.style.display = 'none';
            }
        });
    </script>
    <?php
}

/**
 * Allow login with email or username
 */
add_filter('authenticate', 'fearless_email_login_auth', 20, 3);
function fearless_email_login_auth($user, $username, $password) {
    // Only process if username is provided and no user found yet
    if (!empty($username) && !is_wp_error($user)) {
        // Check if username is an email
        if (is_email($username)) {
            $user_data = get_user_by('email', $username);
            if ($user_data) {
                $username = $user_data->user_login;
            }
        }
        
        // Let WordPress handle the actual authentication
        return wp_authenticate_username_password(null, $username, $password);
    }
    
    return $user;
}

/**
 * Update login form placeholder text
 */
add_filter('gettext', 'fearless_change_login_placeholder', 20, 3);
function fearless_change_login_placeholder($translated_text, $text, $domain) {
    if ($text === 'Username or Email Address' && $GLOBALS['pagenow'] === 'wp-login.php') {
        return 'Username or Email Address';
    }
    return $translated_text;
}

// Simple welcome message in menu for all users
add_filter('wp_nav_menu_objects', 'conditional_dashboard_menu_items');
function conditional_dashboard_menu_items($menu_items) {
    // Only apply to logged-in users
    if (!is_user_logged_in()) {
        return $menu_items;
    }

    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Get user's first name, fallback to 'Welcome' if not set
    $first_name = get_user_meta($user_id, 'first_name', true);
    $welcome_text = !empty($first_name) ? "Welcome {$first_name}" : "Welcome";

    foreach ($menu_items as $key => $menu_item) {
        // Check if this is a dashboard menu item
        if (strpos($menu_item->title, 'Dashboard') !== false || 
            strpos($menu_item->url, 'dashboard') !== false) {

            // Replace with welcome message and remove the link
            $menu_item->title = $welcome_text;
            $menu_item->url = '#'; // Make it non-clickable
            $menu_item->classes[] = 'welcome-message'; // Add class for styling
            $menu_items[$key] = $menu_item;

            // Only modify one dashboard menu item
            break;
        }
    }

    return $menu_items;
}

/**
 * Consolidated error prevention script - replaces multiple error prevention scripts
 * Prevents common JavaScript errors from breaking the site
 */
add_action('wp_enqueue_scripts', 'fli_enqueue_consolidated_error_prevention', 1);
function fli_enqueue_consolidated_error_prevention() {
    wp_enqueue_script(
        'fli-error-prevention',
        get_stylesheet_directory_uri() . '/assets/js/error-prevention.js',
        [],
        '1.0.2',
        false // Load in head to catch errors early
    );
}

/**
 * Add inline MutationObserver fix immediately in head
 * This catches errors even before external scripts load
 */
add_action('wp_head', 'fli_inline_mutation_observer_fix', 1);
function fli_inline_mutation_observer_fix() {
    ?>
    <script>
    (function() {
        'use strict';
        // Store original immediately
        var OrigMO = window.MutationObserver || window.WebKitMutationObserver || window.MozMutationObserver;
        if (!OrigMO) return;
        
        // Create safe wrapper
        function SafeMutationObserver(callback) {
            // Wrap callback to catch errors
            var safeCallback = function(mutations, observer) {
                try {
                    if (callback) callback(mutations, observer);
                } catch(e) {
                    console.warn('MutationObserver callback error prevented:', e);
                }
            };
            
            // Create instance with safe callback
            var instance = new OrigMO(safeCallback);
            
            // Store original observe method
            var originalObserve = instance.observe;
            
            // Override observe with safety checks
            instance.observe = function(target, options) {
                // Comprehensive validation
                if (!target) {
                    console.warn('MutationObserver.observe called with null/undefined target');
                    return;
                }
                if (typeof target !== 'object') {
                    console.warn('MutationObserver.observe called with non-object target:', target);
                    return;
                }
                if (!target.nodeType || target.nodeType < 1 || target.nodeType > 11) {
                    console.warn('MutationObserver.observe called with invalid node:', target);
                    return;
                }
                
                try {
                    return originalObserve.call(instance, target, options || {});
                } catch(e) {
                    console.warn('MutationObserver.observe error prevented:', e);
                }
            };
            
            // Override disconnect to handle errors
            var originalDisconnect = instance.disconnect;
            instance.disconnect = function() {
                try {
                    return originalDisconnect.call(instance);
                } catch(e) {
                    console.warn('MutationObserver.disconnect error prevented:', e);
                }
            };
            
            return instance;
        }
        
        // Copy static properties
        for (var prop in OrigMO) {
            if (OrigMO.hasOwnProperty(prop)) {
                SafeMutationObserver[prop] = OrigMO[prop];
            }
        }
        
        // Set prototype
        SafeMutationObserver.prototype = OrigMO.prototype;
        
        // Replace global MutationObserver
        window.MutationObserver = SafeMutationObserver;
        window.WebKitMutationObserver = SafeMutationObserver;
        window.MozMutationObserver = SafeMutationObserver;
        
        console.log('MutationObserver safety wrapper installed');
    })();
    </script>
    <?php
}

// Category Colors Helper Functions
if ( ! function_exists( 'fli_get_category_color' ) ) {
	/**
	 * Get category color by slug
	 */
	function fli_get_category_color( $category_slug ) {
		if ( class_exists( 'FearlessLiving_Category_Colors' ) ) {
			return FearlessLiving_Category_Colors::instance()->get_category_color( $category_slug );
		}
		return '#7f868f'; // Default fallback
	}
}

if ( ! function_exists( 'fli_get_category_color_by_id' ) ) {
	/**
	 * Get category color by term ID
	 */
	function fli_get_category_color_by_id( $term_id ) {
		if ( class_exists( 'FearlessLiving_Category_Colors' ) ) {
			return FearlessLiving_Category_Colors::instance()->get_category_color_by_id( $term_id );
		}
		return '#7f868f'; // Default fallback
	}
}

if ( ! function_exists( 'fli_get_current_post_category_color' ) ) {
	/**
	 * Get category color for current post
	 */
	function fli_get_current_post_category_color() {
		if ( class_exists( 'FearlessLiving_Category_Colors' ) ) {
			return FearlessLiving_Category_Colors::instance()->get_current_post_category_color();
		}
		return '#7f868f'; // Default fallback
	}
}

if ( ! function_exists( 'fli_get_archive_category_color' ) ) {
	/**
	 * Get category color for archive page
	 */
	function fli_get_archive_category_color() {
		if ( class_exists( 'FearlessLiving_Category_Colors' ) ) {
			return FearlessLiving_Category_Colors::instance()->get_archive_category_color();
		}
		return '#7f868f'; // Default fallback
	}
}

if ( ! function_exists( 'fli_category_color_css' ) ) {
	/**
	 * Output inline CSS with category color
	 */
	function fli_category_color_css( $category_slug, $property = 'color' ) {
		$color = fli_get_category_color( $category_slug );
		return "{$property}: {$color};";
	}
}

if ( ! function_exists( 'fli_category_color_class' ) ) {
	/**
	 * Get CSS class for category color
	 */
	function fli_category_color_class( $category_slug ) {
		return "category-{$category_slug}";
	}
}

// Add custom BuddyBoss CSS variables
add_action('wp_head', 'fli_add_custom_buddyboss_variables', 1);
function fli_add_custom_buddyboss_variables() {
    $theme_options = get_option('buddyboss_theme_options', array());
    
    // Get custom brand colors
    $fli_brand_teal = isset($theme_options['fli_brand_teal']) ? $theme_options['fli_brand_teal'] : '#59898D';
    $fli_brand_light_teal = isset($theme_options['fli_brand_light_teal']) ? $theme_options['fli_brand_light_teal'] : '#EAEDAF';
    $fli_brand_dark_teal = isset($theme_options['fli_brand_dark_teal']) ? $theme_options['fli_brand_dark_teal'] : '#0a738a';
    $fli_brand_yellow = isset($theme_options['fli_brand_yellow']) ? $theme_options['fli_brand_yellow'] : '#E6ED5A';
    $fli_brand_dark_yellow = isset($theme_options['fli_brand_dark_yellow']) ? $theme_options['fli_brand_dark_yellow'] : '#BFC046';
    $fli_brand_pink = isset($theme_options['fli_brand_pink']) ? $theme_options['fli_brand_pink'] : '#ff69b4';
    $fli_brand_orange = isset($theme_options['fli_brand_orange']) ? $theme_options['fli_brand_orange'] : '#ff6b00';
    $fli_brand_white = isset($theme_options['fli_brand_white']) ? $theme_options['fli_brand_white'] : '#ffffff';
    $fli_brand_gray_light = isset($theme_options['fli_brand_gray_light']) ? $theme_options['fli_brand_gray_light'] : '#f9f9f9';
    $fli_brand_gray_medium = isset($theme_options['fli_brand_gray_medium']) ? $theme_options['fli_brand_gray_medium'] : '#393939';
    $fli_brand_gray_dark = isset($theme_options['fli_brand_gray_dark']) ? $theme_options['fli_brand_gray_dark'] : '#1B181C';
    
    ?>
    <style>
    :root {
        /* Fearless Living Brand Colors */
        --fli-brand-teal: <?php echo $fli_brand_teal; ?>;
        --fli-brand-light-teal: <?php echo $fli_brand_light_teal; ?>;
        --fli-brand-dark-teal: <?php echo $fli_brand_dark_teal; ?>;
        --fli-brand-yellow: <?php echo $fli_brand_yellow; ?>;
        --fli-brand-dark-yellow: <?php echo $fli_brand_dark_yellow; ?>;
        --fli-brand-pink: <?php echo $fli_brand_pink; ?>;
        --fli-brand-orange: <?php echo $fli_brand_orange; ?>;
        --fli-brand-white: <?php echo $fli_brand_white; ?>;
        --fli-brand-gray-light: <?php echo $fli_brand_gray_light; ?>;
        --fli-brand-gray-medium: <?php echo $fli_brand_gray_medium; ?>;
        --fli-brand-gray-dark: <?php echo $fli_brand_gray_dark; ?>;
        
        /* Additional utility variables */
        --fli-border-radius: var(--bb-border-radius, 8px);
        --fli-box-shadow: var(--bb-box-shadow, 0 2px 4px rgba(0, 0, 0, 0.1));
        --fli-transition: all 0.3s ease;
    }
    </style>
    <?php
}

// Simple Accessibility Widget - Also show on login page
add_action('wp_footer', 'fli_render_accessibility_widget', 100);
add_action('login_footer', 'fli_render_accessibility_widget', 100);
function fli_render_accessibility_widget() {
    ?>
    <div id="fli-accessibility-widget" style="position: fixed; bottom: 20px; right: 20px; z-index: 99999;">
        <button id="fli-a11y-toggle" style="background: #2271b1; color: white; border: none; border-radius: 50%; width: 50px; height: 50px; cursor: pointer; box-shadow: 0 2px 10px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center;" aria-label="Accessibility Options" title="Accessibility Options">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <circle cx="12" cy="10" r="3"></circle>
                <path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662"></path>
            </svg>
        </button>
        <div id="fli-a11y-panel" style="display: none; position: absolute; bottom: 60px; right: 0; background: white; border-radius: 8px; box-shadow: 0 2px 20px rgba(0,0,0,0.2); padding: 20px; min-width: 250px;">
            <h3 style="margin: 0 0 15px 0; font-size: 18px;">Accessibility Options</h3>
            <button onclick="toggleHighContrast()" style="width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">High Contrast</button>
            <button onclick="toggleLargeText()" style="width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">Large Text</button>
            <button onclick="toggleReadableFont()" style="width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">Readable Font</button>
            <button onclick="resetAccessibility()" style="width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; background: #f0f0f0;">Reset</button>
        </div>
    </div>
    <script>
    document.getElementById('fli-a11y-toggle').addEventListener('click', function() {
        var panel = document.getElementById('fli-a11y-panel');
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    });
    
    function toggleHighContrast() {
        document.body.classList.toggle('high-contrast');
        localStorage.setItem('a11y-high-contrast', document.body.classList.contains('high-contrast'));
    }
    
    function toggleLargeText() {
        document.body.classList.toggle('large-text');
        localStorage.setItem('a11y-large-text', document.body.classList.contains('large-text'));
    }
    
    function toggleReadableFont() {
        document.body.classList.toggle('readable-font');
        localStorage.setItem('a11y-readable-font', document.body.classList.contains('readable-font'));
    }
    
    function resetAccessibility() {
        document.body.classList.remove('high-contrast', 'large-text', 'readable-font');
        localStorage.removeItem('a11y-high-contrast');
        localStorage.removeItem('a11y-large-text');
        localStorage.removeItem('a11y-readable-font');
    }
    
    // Load saved preferences
    if (localStorage.getItem('a11y-high-contrast') === 'true') document.body.classList.add('high-contrast');
    if (localStorage.getItem('a11y-large-text') === 'true') document.body.classList.add('large-text');
    if (localStorage.getItem('a11y-readable-font') === 'true') document.body.classList.add('readable-font');
    </script>
    <style>
    body.high-contrast {
        background: #000 !important;
        color: #fff !important;
    }
    body.high-contrast * {
        background: #000 !important;
        color: #fff !important;
        border-color: #fff !important;
    }
    body.high-contrast a {
        color: #ffff00 !important;
        text-decoration: underline !important;
    }
    body.large-text {
        font-size: 120% !important;
    }
    body.large-text * {
        font-size: inherit !important;
    }
    body.readable-font {
        font-family: Arial, sans-serif !important;
        line-height: 1.8 !important;
    }
    body.readable-font * {
        font-family: inherit !important;
        line-height: inherit !important;
    }
    </style>
    <?php
}





/* Floating button Contact */

if (defined('THEME_HOOK_PREFIX')) {
    add_action(THEME_HOOK_PREFIX . 'after_page', 'wbcom_add_floating_ask_button');
} else {
    add_action('wp_footer', 'wbcom_add_floating_ask_button');
}

function wbcom_add_floating_ask_button() {
    ?>
    <a href="mailto:support@fearlessliving.org" class="floating-ask-btn" title="Ask a Question">
        <i class="bb-icon-envelope bb-icon-l"></i>
    </a>
    <?php

}
