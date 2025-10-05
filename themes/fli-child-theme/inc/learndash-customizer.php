<?php
/**
 * LearnDash Customizer Settings
 * Replaces LearnDash Visual Customizer plugin functionality
 */

// Add LearnDash section to WordPress Customizer
add_action('customize_register', 'fli_learndash_customizer_settings');
function fli_learndash_customizer_settings($wp_customize) {
    
    // Add LearnDash Panel
    $wp_customize->add_panel('learndash_settings', array(
        'title' => __('LearnDash Settings', 'buddyboss-theme'),
        'description' => __('Customize LearnDash appearance and functionality', 'buddyboss-theme'),
        'priority' => 160,
    ));
    
    // General Settings Section
    $wp_customize->add_section('learndash_general', array(
        'title' => __('General Settings', 'buddyboss-theme'),
        'panel' => 'learndash_settings',
        'priority' => 10,
    ));
    
    // Course Grid Columns
    $wp_customize->add_setting('fli_learndash_grid_columns', array(
        'default' => '3',
        'sanitize_callback' => 'absint',
        'transport' => 'refresh',
    ));
    
    $wp_customize->add_control('fli_learndash_grid_columns', array(
        'label' => __('Course Grid Columns', 'buddyboss-theme'),
        'section' => 'learndash_general',
        'type' => 'select',
        'choices' => array(
            '2' => '2 Columns',
            '3' => '3 Columns',
            '4' => '4 Columns',
        ),
    ));
    
    // Show Progress Bar
    $wp_customize->add_setting('fli_learndash_show_progress', array(
        'default' => 'yes',
        'sanitize_callback' => 'sanitize_text_field',
        'transport' => 'refresh',
    ));
    
    $wp_customize->add_control('fli_learndash_show_progress', array(
        'label' => __('Show Progress Bars', 'buddyboss-theme'),
        'section' => 'learndash_general',
        'type' => 'radio',
        'choices' => array(
            'yes' => 'Yes',
            'no' => 'No',
        ),
    ));
    
    // Progress Ring Style
    $wp_customize->add_setting('fli_learndash_progress_style', array(
        'default' => 'ring',
        'sanitize_callback' => 'sanitize_text_field',
        'transport' => 'refresh',
    ));
    
    $wp_customize->add_control('fli_learndash_progress_style', array(
        'label' => __('Progress Indicator Style', 'buddyboss-theme'),
        'section' => 'learndash_general',
        'type' => 'select',
        'choices' => array(
            'ring' => 'Progress Ring',
            'bar' => 'Progress Bar',
            'percentage' => 'Percentage Only',
            'checkmark' => 'Checkmark Only',
        ),
    ));
    
    // Quiz Pass Percentage
    $wp_customize->add_setting('fli_learndash_quiz_pass', array(
        'default' => '70',
        'sanitize_callback' => 'absint',
        'transport' => 'refresh',
    ));
    
    $wp_customize->add_control('fli_learndash_quiz_pass', array(
        'label' => __('Quiz Pass Percentage', 'buddyboss-theme'),
        'description' => __('Minimum score to show green indicator', 'buddyboss-theme'),
        'section' => 'learndash_general',
        'type' => 'number',
        'input_attrs' => array(
            'min' => 0,
            'max' => 100,
            'step' => 5,
        ),
    ));
    
    // Appearance Section
    $wp_customize->add_section('learndash_appearance', array(
        'title' => __('Appearance', 'buddyboss-theme'),
        'panel' => 'learndash_settings',
        'priority' => 20,
    ));
    
    // Enable Custom Styling
    $wp_customize->add_setting('fli_learndash_custom_styles', array(
        'default' => 'yes',
        'sanitize_callback' => 'sanitize_text_field',
        'transport' => 'refresh',
    ));
    
    $wp_customize->add_control('fli_learndash_custom_styles', array(
        'label' => __('Enable Custom Styling', 'buddyboss-theme'),
        'description' => __('Use our custom LearnDash styles', 'buddyboss-theme'),
        'section' => 'learndash_appearance',
        'type' => 'radio',
        'choices' => array(
            'yes' => 'Yes - Use Custom Styles',
            'no' => 'No - Use Default LearnDash Styles',
        ),
    ));
    
    // Animation Style
    $wp_customize->add_setting('fli_learndash_animation', array(
        'default' => 'smooth',
        'sanitize_callback' => 'sanitize_text_field',
        'transport' => 'refresh',
    ));
    
    $wp_customize->add_control('fli_learndash_animation', array(
        'label' => __('Animation Style', 'buddyboss-theme'),
        'section' => 'learndash_appearance',
        'type' => 'select',
        'choices' => array(
            'none' => 'No Animations',
            'smooth' => 'Smooth Transitions',
            'bounce' => 'Bounce Effects',
            'fade' => 'Fade Effects',
        ),
    ));
    
    // Course Card Style
    $wp_customize->add_setting('fli_learndash_card_style', array(
        'default' => 'rounded',
        'sanitize_callback' => 'sanitize_text_field',
        'transport' => 'refresh',
    ));
    
    $wp_customize->add_control('fli_learndash_card_style', array(
        'label' => __('Course Card Style', 'buddyboss-theme'),
        'section' => 'learndash_appearance',
        'type' => 'select',
        'choices' => array(
            'square' => 'Square Corners',
            'rounded' => 'Rounded Corners',
            'circular' => 'Highly Rounded',
        ),
    ));
    
    // Mobile Settings Section
    $wp_customize->add_section('learndash_mobile', array(
        'title' => __('Mobile Settings', 'buddyboss-theme'),
        'panel' => 'learndash_settings',
        'priority' => 30,
    ));
    
    // Mobile Grid Columns
    $wp_customize->add_setting('fli_learndash_mobile_columns', array(
        'default' => '1',
        'sanitize_callback' => 'absint',
        'transport' => 'refresh',
    ));
    
    $wp_customize->add_control('fli_learndash_mobile_columns', array(
        'label' => __('Mobile Grid Columns', 'buddyboss-theme'),
        'section' => 'learndash_mobile',
        'type' => 'select',
        'choices' => array(
            '1' => '1 Column',
            '2' => '2 Columns',
        ),
    ));
    
    // Compact Mobile View
    $wp_customize->add_setting('fli_learndash_mobile_compact', array(
        'default' => 'yes',
        'sanitize_callback' => 'sanitize_text_field',
        'transport' => 'refresh',
    ));
    
    $wp_customize->add_control('fli_learndash_mobile_compact', array(
        'label' => __('Compact Mobile View', 'buddyboss-theme'),
        'description' => __('Use smaller spacing on mobile devices', 'buddyboss-theme'),
        'section' => 'learndash_mobile',
        'type' => 'radio',
        'choices' => array(
            'yes' => 'Yes',
            'no' => 'No',
        ),
    ));
}

/**
 * Generate dynamic CSS based on customizer settings
 */
add_action('wp_head', 'fli_learndash_customizer_css', 100);
function fli_learndash_customizer_css() {
    // Skip if custom styles are disabled
    if (get_theme_mod('fli_learndash_custom_styles', 'yes') === 'no') {
        return;
    }
    
    $grid_columns = get_theme_mod('fli_learndash_grid_columns', '3');
    $mobile_columns = get_theme_mod('fli_learndash_mobile_columns', '1');
    $card_style = get_theme_mod('fli_learndash_card_style', 'rounded');
    $animation = get_theme_mod('fli_learndash_animation', 'smooth');
    $progress_style = get_theme_mod('fli_learndash_progress_style', 'ring');
    $show_progress = get_theme_mod('fli_learndash_show_progress', 'yes');
    $mobile_compact = get_theme_mod('fli_learndash_mobile_compact', 'yes');
    $quiz_pass = get_theme_mod('fli_learndash_quiz_pass', '70');
    
    // Determine border radius
    $border_radius = '8px';
    if ($card_style === 'square') {
        $border_radius = '0';
    } elseif ($card_style === 'circular') {
        $border_radius = '20px';
    }
    
    // Animation duration
    $animation_duration = '0.3s';
    if ($animation === 'none') {
        $animation_duration = '0s';
    } elseif ($animation === 'bounce') {
        $animation_duration = '0.5s';
    } elseif ($animation === 'fade') {
        $animation_duration = '0.4s';
    }
    
    ?>
    <style id="fli-learndash-customizer-styles">
        /* Grid Columns */
        .ld-course-grid {
            display: grid;
            grid-template-columns: repeat(<?php echo esc_attr($grid_columns); ?>, 1fr);
            gap: 30px;
        }
        
        /* Card Style */
        .ld-course-grid .course,
        .ld-item-list-item,
        .ld-table-list-item {
            border-radius: <?php echo esc_attr($border_radius); ?> !important;
            transition: all <?php echo esc_attr($animation_duration); ?> ease !important;
        }
        
        /* Progress Display */
        <?php if ($show_progress === 'no'): ?>
        .ld-progress,
        .ld-progress-bar,
        .ld-progress-ring {
            display: none !important;
        }
        <?php endif; ?>
        
        /* Progress Style Variations */
        <?php if ($progress_style === 'bar'): ?>
        .ld-progress-ring {
            display: none !important;
        }
        .ld-progress {
            display: block !important;
        }
        <?php elseif ($progress_style === 'percentage'): ?>
        .ld-progress-ring svg {
            display: none !important;
        }
        .ld-progress-ring .progress-text {
            display: block !important;
            position: static !important;
            font-size: 18px !important;
            font-weight: bold !important;
        }
        <?php elseif ($progress_style === 'checkmark'): ?>
        .ld-progress-ring.in_progress,
        .ld-progress-ring.not_started {
            display: none !important;
        }
        <?php endif; ?>
        
        /* Quiz Pass Threshold */
        :root {
            --quiz-pass-threshold: <?php echo esc_attr($quiz_pass); ?>;
        }
        
        /* Mobile Columns */
        @media (max-width: 768px) {
            .ld-course-grid {
                grid-template-columns: repeat(<?php echo esc_attr($mobile_columns); ?>, 1fr) !important;
                gap: <?php echo $mobile_compact === 'yes' ? '15px' : '25px'; ?> !important;
            }
            
            <?php if ($mobile_compact === 'yes'): ?>
            .ld-item-list-item,
            .ld-table-list-item {
                padding: 12px !important;
                margin-bottom: 10px !important;
            }
            
            .ld-progress-ring {
                width: 40px !important;
                height: 40px !important;
            }
            <?php endif; ?>
        }
        
        /* Animation Effects */
        <?php if ($animation === 'bounce'): ?>
        @keyframes bounce-in {
            0% { transform: scale(0.9); opacity: 0; }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .ld-item-list-item {
            animation: bounce-in 0.5s ease-out;
        }
        <?php elseif ($animation === 'fade'): ?>
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .ld-item-list-item {
            animation: fade-in 0.4s ease-out;
        }
        <?php endif; ?>
    </style>
    <?php
}

/**
 * Add LearnDash settings to admin menu
 */
add_action('admin_menu', 'fli_learndash_settings_menu');
function fli_learndash_settings_menu() {
    add_submenu_page(
        'learndash-lms',
        __('Visual Settings', 'buddyboss-theme'),
        __('Visual Settings', 'buddyboss-theme'),
        'manage_options',
        'fli-learndash-settings',
        'fli_learndash_settings_page'
    );
}

/**
 * LearnDash Settings Page
 */
function fli_learndash_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('LearnDash Visual Settings', 'buddyboss-theme'); ?></h1>
        
        <div class="notice notice-info">
            <p><?php _e('These settings have been moved to the WordPress Customizer for easier live preview.', 'buddyboss-theme'); ?></p>
            <p><a href="<?php echo admin_url('customize.php?autofocus[panel]=learndash_settings'); ?>" class="button button-primary">
                <?php _e('Open Customizer', 'buddyboss-theme'); ?>
            </a></p>
        </div>
        
        <div class="card">
            <h2><?php _e('Current Settings', 'buddyboss-theme'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php _e('Custom Styles', 'buddyboss-theme'); ?></th>
                    <td><?php echo get_theme_mod('fli_learndash_custom_styles', 'yes') === 'yes' ? 'Enabled' : 'Disabled'; ?></td>
                </tr>
                <tr>
                    <th><?php _e('Grid Columns', 'buddyboss-theme'); ?></th>
                    <td><?php echo get_theme_mod('fli_learndash_grid_columns', '3'); ?> columns</td>
                </tr>
                <tr>
                    <th><?php _e('Progress Style', 'buddyboss-theme'); ?></th>
                    <td><?php echo ucfirst(get_theme_mod('fli_learndash_progress_style', 'ring')); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Animation', 'buddyboss-theme'); ?></th>
                    <td><?php echo ucfirst(get_theme_mod('fli_learndash_animation', 'smooth')); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Card Style', 'buddyboss-theme'); ?></th>
                    <td><?php echo ucfirst(get_theme_mod('fli_learndash_card_style', 'rounded')); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Quiz Pass %', 'buddyboss-theme'); ?></th>
                    <td><?php echo get_theme_mod('fli_learndash_quiz_pass', '70'); ?>%</td>
                </tr>
            </table>
        </div>
        
        <div class="card">
            <h2><?php _e('Migration Notice', 'buddyboss-theme'); ?></h2>
            <p><?php _e('The LearnDash Visual Customizer plugin can now be safely deactivated. All functionality has been integrated into the theme.', 'buddyboss-theme'); ?></p>
            
            <?php if (is_plugin_active('ld-visual-customizer/lds-visual-customizer.php')): ?>
                <p><a href="<?php echo wp_nonce_url(admin_url('plugins.php?action=deactivate&plugin=ld-visual-customizer/lds-visual-customizer.php'), 'deactivate-plugin_ld-visual-customizer/lds-visual-customizer.php'); ?>" class="button button-secondary">
                    <?php _e('Deactivate Visual Customizer Plugin', 'buddyboss-theme'); ?>
                </a></p>
            <?php else: ?>
                <p style="color: green;">✓ <?php _e('Visual Customizer plugin is already deactivated.', 'buddyboss-theme'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Migrate settings from Visual Customizer plugin if they exist
 */
add_action('init', 'fli_migrate_visual_customizer_settings');
function fli_migrate_visual_customizer_settings() {
    // Check if migration has already been done
    if (get_option('fli_learndash_settings_migrated')) {
        return;
    }
    
    // Migrate settings if they exist
    $old_settings = array(
        'lds_grid_columns' => 'fli_learndash_grid_columns',
        'lds_show_leaderboard' => 'fli_learndash_show_progress',
        'lds_animation' => 'fli_learndash_animation',
        'lds_icon_style' => 'fli_learndash_card_style',
    );
    
    foreach ($old_settings as $old_key => $new_key) {
        $old_value = get_option($old_key);
        if ($old_value !== false && !get_theme_mod($new_key)) {
            set_theme_mod($new_key, $old_value);
        }
    }
    
    // Mark migration as complete
    update_option('fli_learndash_settings_migrated', true);
}

/**
 * Add body classes based on LearnDash settings
 */
add_filter('body_class', 'fli_learndash_body_classes');
function fli_learndash_body_classes($classes) {
    if (get_theme_mod('fli_learndash_custom_styles', 'yes') === 'yes') {
        $classes[] = 'fli-learndash-styled';
    }
    
    $classes[] = 'fli-ld-' . get_theme_mod('fli_learndash_animation', 'smooth');
    $classes[] = 'fli-ld-' . get_theme_mod('fli_learndash_card_style', 'rounded');
    
    return $classes;
}

/**
 * Pass settings to JavaScript
 */
add_action('wp_enqueue_scripts', 'fli_learndash_localize_settings');
function fli_learndash_localize_settings() {
    if (wp_script_is('learndash-progress-rings', 'enqueued')) {
        wp_localize_script('learndash-progress-rings', 'fliLearnDashSettings', array(
            'progressStyle' => get_theme_mod('fli_learndash_progress_style', 'ring'),
            'showProgress' => get_theme_mod('fli_learndash_show_progress', 'yes'),
            'quizPass' => get_theme_mod('fli_learndash_quiz_pass', '70'),
            'animation' => get_theme_mod('fli_learndash_animation', 'smooth'),
        ));
    }
}