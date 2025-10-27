<?php
/**
 * FLI Image Upload Handler
 *
 * Handles image uploads and gallery display via shortcodes
 *
 * Shortcodes:
 * - [fli_image_upload] - Upload form with preview
 * - [fli_image_gallery] - Display user's uploaded images
 *
 * @package FLI BuddyBoss Child
 * @version 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Image Upload Handler Class
 */
class FLI_Image_Upload_Handler {

    public function __construct() {
        add_action('wp_ajax_fli_upload_image', [$this, 'handle_ajax_upload']);
        add_action('wp_ajax_nopriv_fli_upload_image', [$this, 'handle_ajax_upload']);
        add_shortcode('fli_image_upload', [$this, 'render_upload_form']);
        add_shortcode('fli_image_gallery', 'fli_render_image_gallery');
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
            'max_size' => 5,
            'allowed_types' => 'jpg,jpeg,png,gif,webp',
            'show_preview' => 'yes',
            'class' => ''
        ], $atts);

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

        wp_add_inline_style('buddyboss-child-css', $this->get_inline_styles());
    }

    private function get_inline_styles() {
        return '
            .fli-image-upload-wrapper { margin: 20px 0; }
            .fli-preview-area { position: relative; display: inline-block; margin: 20px 0; }
            .fli-preview-image { max-width: 300px; max-height: 300px; border: 2px solid #ddd; border-radius: 4px; }
            .fli-remove-image { position: absolute; top: -10px; right: -10px; background: #ff4444; color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-size: 20px; line-height: 1; }
            .fli-upload-progress { width: 100%; height: 20px; background: #f0f0f0; border-radius: 10px; margin: 20px 0; overflow: hidden; }
            .fli-progress-bar { height: 100%; background: #4CAF50; width: 0; transition: width 0.3s; }
            .fli-upload-message { margin: 10px 0; padding: 10px; border-radius: 4px; display: none; }
            .fli-upload-message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; display: block; }
            .fli-upload-message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; display: block; }
            .fli-upload-button { margin: 10px 0; }
            .fli-submit-upload { margin: 10px 0; }
        ';
    }
}

/**
 * Image Gallery Shortcode
 *
 * Displays a grid of images uploaded by a user
 *
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
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
        .fli-image-gallery { margin: 20px 0; }
        .fli-gallery-images { display: grid; gap: 20px; margin-top: 20px; }
        .fli-gallery-images.columns-2 { grid-template-columns: repeat(2, 1fr); }
        .fli-gallery-images.columns-3 { grid-template-columns: repeat(3, 1fr); }
        .fli-gallery-images.columns-4 { grid-template-columns: repeat(4, 1fr); }
        .fli-gallery-item { position: relative; overflow: hidden; border-radius: 8px; background: #f5f5f5; }
        .fli-gallery-item img { width: 100%; height: auto; display: block; }
        .fli-gallery-item a { display: block; }
        .fli-gallery-remove { position: absolute; top: 10px; right: 10px; background: rgba(255, 68, 68, 0.9); color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-size: 20px; line-height: 1; opacity: 0; transition: opacity 0.3s; }
        .fli-gallery-item:hover .fli-gallery-remove { opacity: 1; }
        @media (max-width: 768px) { .fli-gallery-images { grid-template-columns: repeat(2, 1fr) !important; } }
        @media (max-width: 480px) { .fli-gallery-images { grid-template-columns: 1fr !important; } }
    </style>
    <?php
    return ob_get_clean();
}

// Initialize the image upload handler
new FLI_Image_Upload_Handler();
