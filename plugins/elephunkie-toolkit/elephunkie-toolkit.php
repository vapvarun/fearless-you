<?php
/*
Plugin Name: Elephunkie Toolkit
Version: 3.2
Author: Jonathan Albiar
Description: Combines various tools into a single Elephunkie plugin, with toggles for enabling/disabling features.
Text Domain: elephunkie
*/

class Elephunkie_Toolkit {
    public function __construct() {
        if (is_admin()) {
            add_action('admin_menu', [$this, 'add_settings_page']);
            add_action('admin_init', [$this, 'register_settings']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_settings_link']);
            add_action('admin_head', [$this, 'highlight_plugin_row']);
            add_action('admin_head', [$this, 'dismiss_admin_notices']);
        }
    }

    public function add_settings_page() {
        $icon_url = plugin_dir_url(__FILE__) . 'includes/elephunkie-icon.png';
        add_menu_page(
            'Elephunkie Toolkit',
            'Elephunkie Toolkit',
            'manage_options',
            'elephunkie-toolkit',
            [$this, 'render_settings_page'],
            $icon_url,
            6
        );
    }

    public function register_settings() {
        register_setting('elephunkie_toolkit', 'elephunkie_toolkit');
        foreach ($this->get_features() as $feature) {
            register_setting('elephunkie_toolkit', $feature['id']);
        }
    }

    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_elephunkie-toolkit') {
            return;
        }
        // Enqueue Bootstrap Toggle CSS and JS
        wp_enqueue_script('bootstrap-toggle', plugin_dir_url(__FILE__) . 'js/bootstrap-toggle.min.js', ['jquery'], null, true);
        // Custom CSS for plugin settings
        wp_enqueue_style('elephunkie-admin-css', plugin_dir_url(__FILE__) . 'assets/elephunkie-admin.css', [], '3.2.1');
        wp_enqueue_style('elephunkie-toolkit-custom', plugin_dir_url(__FILE__) . 'elephunkie-toolkit.css', ['elephunkie-admin-css'], '3.2.1');
        
        // Localize script for AJAX
        wp_localize_script('bootstrap-toggle', 'elephunkie_ajax_object', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('elephunkie_toggle_feature')
        ]);
    }

    public function render_settings_page() {
        try {
            $features = $this->get_features();
        } catch (Exception $e) {
            $this->send_error_email($e->getMessage());
            echo '<div class="notice notice-error"><p>An error occurred while loading the settings page. Please check your email for details.</p></div>';
            return;
        }
        $toolkit = plugin_dir_url(__FILE__) . 'includes/elephunkie-toolkit.png';
        ?>

        <div class="phunkie-admin-page">
            <div class="phunkie-admin-title-image">
                <img src="<?php echo esc_url($toolkit); ?>" alt="Elephunkie Toolkit" />
            </div>
            <div class="phunkie-wrap">
                <div class="phunkie-settings-header">
                    <h2>Settings</h2>
                    <form method="post" action="options.php">
                        <?php settings_fields('elephunkie_toolkit'); ?>
                        <?php do_settings_sections('elephunkie_toolkit'); ?>
                        <?php foreach ($features as $feature) : ?>
                            <div class="phunkie-feature">
                                <label class="phunkie-toggle">
                                    <input type="checkbox" class="toggle-feature-input" id="feature_<?php echo esc_attr($feature['id']); ?>" name="<?php echo esc_attr($feature['id']); ?>" value="on" <?php checked(get_option($feature['id']), 'on'); ?>>
                                    <span class="slider round"></span>
                                </label>
                                <div class="phunkie-feature-content">
                                    <label class="phunkie-feature-title" for="feature_<?php echo esc_attr($feature['id']); ?>"><?php echo esc_html($feature['name']); ?></label>
                                    <?php if (!empty($feature['description'])) : ?>
                                        <p class="phunkie-feature-description"><?php echo esc_html($feature['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="phunkie-submit-button-container">
                            <?php submit_button('Save Changes', 'primary', 'submit', true, ['class' => 'phunkie-button']); ?>
                        </div>
                    </form>
                </div>
                <div class="wrap-footer">
                    <a href="https://elephunkie.com">&copy; Elephunkie, LLC</a>
                </div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function ($) {
                // Initialize Bootstrap Toggle
                $('.toggle-feature-input').bootstrapToggle();

                // Handle checkbox change event
                $('.toggle-feature-input').change(function () {
                    var $toggle = $(this);
                    var feature = $toggle.attr('name');
                    var isEnabled = $toggle.prop('checked') ? 'on' : 'off';
                    var $featureDiv = $toggle.closest('.phunkie-feature');
                    
                    // Show loading state
                    $featureDiv.addClass('loading');
                    
                    $.ajax({
                        url: elephunkie_ajax_object.ajax_url,
                        method: 'POST',
                        data: {
                            action: 'elephunkie_toggle_feature',
                            feature: feature,
                            status: isEnabled,
                            _wpnonce: elephunkie_ajax_object.nonce
                        },
                        success: function (response) {
                            $featureDiv.removeClass('loading');
                            
                            if (response.success) {
                                // Show success message briefly
                                $featureDiv.addClass('success');
                                setTimeout(function() {
                                    $featureDiv.removeClass('success');
                                }, 2000);
                            } else {
                                // Revert toggle state and show error
                                $toggle.prop('checked', !$toggle.prop('checked')).change();
                                $toggle.bootstrapToggle('toggle');
                                alert('Error: ' + (response.data || 'Unknown error occurred'));
                            }
                        },
                        error: function (xhr, status, error) {
                            $featureDiv.removeClass('loading');
                            // Revert toggle state
                            $toggle.prop('checked', !$toggle.prop('checked')).change();
                            $toggle.bootstrapToggle('toggle');
                            alert('Connection error. Please try again later.');
                        }
                    });
                });
            });
        </script>
        
        <style>
        .phunkie-feature.loading {
            opacity: 0.6;
            pointer-events: none;
        }
        .phunkie-feature.success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            transition: background-color 0.3s ease;
        }
        .phunkie-feature.loading::after {
            content: "Processing...";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
        }
        .phunkie-feature {
            position: relative;
        }
        </style>
        <?php
    }

    public function get_features() {
        $features = [];
        $includes_dir = __DIR__ . '/includes/';
        if (!is_dir($includes_dir)) {
            return $features;
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($includes_dir));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $plugin_data = get_file_data($file->getPathname(), [
                    'Name' => 'Plugin Name',
                    'Description' => 'Description'
                ]);
                if (!empty($plugin_data['Name'])) {
                    // Use underscores instead of hyphens for consistency
                    $feature_id = 'elephunkie_' . str_replace('-', '_', sanitize_title($plugin_data['Name']));
                    if (get_option($feature_id) === false) {
                        update_option($feature_id, 'off'); // Set new features to off by default
                    }
                    $features[] = [
                        'id' => $feature_id,
                        'name' => $plugin_data['Name'],
                        'description' => $plugin_data['Description']
                    ];
                }
            }
        }
        return $features;
    }

    private function send_error_email($error_message) {
        $to = 'jonathan@elephunkie.com';
        $subject = 'Error in Elephunkie Toolkit Plugin';
        $body = "An error occurred in the Elephunkie Toolkit plugin:\n\n" . $error_message;
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        if (!wp_mail($to, $subject, $body, $headers)) {
            error_log('Failed to send error email: ' . $error_message);
        }
    }

    public function add_settings_link($links) {
        $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=elephunkie-toolkit')) . '">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function highlight_plugin_row() {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'plugins') {
            echo '<style>
                tr[data-slug="elephunkie-toolkit"] {
                    background-color: #ffccff !important;
                    border-left: 5px solid #ff69b4 !important;
                }
            </style>';
        }
    }

    public function dismiss_admin_notices() {
        echo '<style>
            .notice {
                display: none !important;
            }
        </style>';
    }
}

new Elephunkie_Toolkit();

// AJAX Handler for toggling features
add_action('wp_ajax_elephunkie_toggle_feature', 'elephunkie_toggle_feature');
function elephunkie_toggle_feature() {
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'elephunkie_toggle_feature')) {
        wp_send_json_error('Security check failed');
    }
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    $feature = sanitize_text_field($_POST['feature'] ?? '');
    $status = sanitize_text_field($_POST['status'] ?? '');
    
    if (empty($feature)) {
        wp_send_json_error('Missing feature parameter');
    }
    
    // If turning on, test load the module first
    if ($status === 'on') {
        $test_result = elephunkie_test_load_feature($feature);
        if (!$test_result['success']) {
            wp_send_json_error('Module failed to load: ' . $test_result['error']);
        }
    }
    
    update_option($feature, $status);
    wp_send_json_success(['message' => 'Feature ' . ($status === 'on' ? 'enabled' : 'disabled') . ' successfully']);
}

// Test load a feature to check for errors
function elephunkie_test_load_feature($feature_id) {
    $includes_dir = __DIR__ . '/includes/';
    if (!is_dir($includes_dir)) {
        return ['success' => false, 'error' => 'Includes directory not found'];
    }
    
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($includes_dir));
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $plugin_data = get_file_data($file->getPathname(), [
                'Name' => 'Plugin Name'
            ]);
            if (!empty($plugin_data['Name'])) {
                $test_feature_id = 'elephunkie_' . str_replace('-', '_', sanitize_title($plugin_data['Name']));
                if ($test_feature_id === $feature_id) {
                    // Test loading the file
                    ob_start();
                    $error_handler = set_error_handler(function($severity, $message, $file, $line) {
                        throw new ErrorException($message, 0, $severity, $file, $line);
                    });
                    
                    try {
                        // Use require_once instead of include_once for more strict checking
                        require_once $file->getPathname();
                        $output = ob_get_clean();
                        if ($error_handler) {
                            restore_error_handler();
                        }
                        return ['success' => true];
                    } catch (Throwable $e) {
                        ob_end_clean();
                        if ($error_handler) {
                            restore_error_handler();
                        }
                        return ['success' => false, 'error' => $e->getMessage()];
                    }
                }
            }
        }
    }
    
    return ['success' => false, 'error' => 'Feature not found'];
}

// Load features based on options
add_action('plugins_loaded', 'elephunkie_load_features');
function elephunkie_load_features() {
    try {
        $includes_dir = __DIR__ . '/includes/';
        if (!is_dir($includes_dir)) {
            return;
        }
        
        // Get enabled features first to avoid unnecessary file reads
        $enabled_features = [];
        $all_options = wp_load_alloptions();
        foreach ($all_options as $option_name => $option_value) {
            if (strpos($option_name, 'elephunkie_') === 0 && $option_value === 'on') {
                $enabled_features[] = $option_name;
            }
        }
        
        if (empty($enabled_features)) {
            return;
        }
        
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($includes_dir));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                try {
                    $plugin_data = get_file_data($file->getPathname(), [
                        'Name' => 'Plugin Name'
                    ]);
                    
                    if (!empty($plugin_data['Name'])) {
                        $feature_id = 'elephunkie_' . str_replace('-', '_', sanitize_title($plugin_data['Name']));
                        
                        if (in_array($feature_id, $enabled_features)) {
                            try {
                                require_once $file->getPathname();
                            } catch (Throwable $e) {
                                // Auto-disable the feature on error
                                update_option($feature_id, 'off');
                                
                                // Log the error
                                error_log("Elephunkie Toolkit: Disabled feature '{$plugin_data['Name']}' due to error: " . $e->getMessage());
                            }
                        }
                    }
                } catch (Exception $e) {
                    // Skip problematic files
                    error_log("Elephunkie Toolkit: Skipped problematic file {$file->getPathname()}: " . $e->getMessage());
                    continue;
                }
            }
        }
    } catch (Exception $e) {
        error_log("Elephunkie Toolkit: Critical error in feature loading: " . $e->getMessage());
    }
}

// Adjust plugins_url to use the actual plugin folder path
add_filter('plugins_url', function($url, $path, $plugin) {
    $includes_dir = __DIR__ . '/includes/';
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($includes_dir));

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $plugin_data = get_file_data($file->getPathname(), [
                'Name' => 'Plugin Name'
            ]);
            if (!empty($plugin_data['Name'])) {
                $feature_id = 'elephunkie_' . str_replace('-', '_', sanitize_title($plugin_data['Name']));
                $constant_name = 'ELEPHUNKIE_CURRENT_PLUGIN_PATH_' . strtoupper($feature_id);
                if (defined($constant_name) && strpos($plugin, constant($constant_name)) !== false) {
                    $base = str_replace(WP_PLUGIN_DIR, '', dirname($file->getPathname()));
                    return plugins_url($base . $path);
                }
            }
        }
    }
    return $url;
}, 10, 3);

add_filter('admin_body_class', 'elephunkie_admin_body_class');
function elephunkie_admin_body_class($classes) {
    // Check if we are on the Elephunkie Toolkit settings page
    $screen = get_current_screen();
    if ($screen && $screen->id === 'elephunkie-toolkit') {
        $classes .= ' elephunkie-body';
    }
    return $classes;
}

// Register a custom REST API route
add_action( 'rest_api_init', function () {
    register_rest_route( 'phunk-api/v1', '/metadata/(?P<old_id>\d+)', array(
        'methods'  => 'GET',
        'callback' => 'phunk_export_metadata_by_old_id',
        'permission_callback' => '__return_true',
    ));
});

function phunk_export_metadata_by_old_id( $request ) {
    // Verify this is a REST API request
    if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
        return new WP_Error( 'rest_forbidden', 'This endpoint is only accessible via the REST API.', array( 'status' => 403 ) );
    }

    $old_id = $request['old_id'];

    // Find the attachment with the Old ID meta
    $query = new WP_Query( array(
        'post_type'  => 'attachment',
        'meta_key'   => 'old_id',
        'meta_value' => $old_id,
        'post_status' => 'inherit',
    ));

    if ( ! $query->have_posts() ) {
        return new WP_Error( 'no_attachment', 'No attachment found with this Old ID.', array( 'status' => 404 ));
    }

    $attachment = $query->posts[0];
    $metadata = get_post_meta( $attachment->ID );

    // Clean metadata by removing unwanted keys
    foreach ( $metadata as $key => $value ) {
        if ( is_serialized( $value[0] ) ) {
            $metadata[$key] = maybe_unserialize( $value[0] );
        } else {
            $metadata[$key] = $value[0];
        }
    }

    return rest_ensure_response( array(
        'ID'         => $attachment->ID,
        'file_name'  => basename( get_attached_file( $attachment->ID ) ),
        'url'        => wp_get_attachment_url( $attachment->ID ),
        'mime_type'  => get_post_mime_type( $attachment->ID ),
        'metadata'   => $metadata,
    ));
}
?>