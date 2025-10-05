<?php
/*
Plugin Name: 01 Custom Extended Audio Block
Description: Extend the WordPress audio block to include a title, album art, and album name.
Version: 1.0
Author: Your Name
*/
function custom_audio_block_editor_assets() {
    // Enqueue script for handling media uploads
    wp_enqueue_script(
        'custom-audio-block-editor',
        plugin_dir_url( __FILE__ ) . 'js/editor.js',
        array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-media-utils' ),
        filemtime( plugin_dir_path( __FILE__ ) . 'js/editor.js' )
    );

    // Enqueue CSS for styling the custom block
    wp_enqueue_style(
        'custom-audio-block-editor-style',
        plugin_dir_url( __FILE__ ) . 'css/editor-style.css',
        array( 'wp-edit-blocks' ),
        filemtime( plugin_dir_path( __FILE__ ) . 'css/editor-style.css' )
    );
}
add_action( 'enqueue_block_editor_assets', 'custom_audio_block_editor_assets' );

// Register custom block
function register_custom_audio_block() {
    register_block_type( 'custom-audio-block/main', array(
        'attributes' => array(
            'title'      => array(
                'type'    => 'string',
                'default' => '',
            ),
            'albumArt'   => array(
                'type'    => 'string',
                'default' => '',
            ),
            'albumName'  => array(
                'type'    => 'string',
                'default' => '',
            ),
            'audioURL'   => array(
                'type'    => 'string',
                'default' => '',
            ),
        ),
        'render_callback' => 'render_custom_audio_block',
    ) );
}
add_action( 'init', 'register_custom_audio_block' );

// Render custom audio block
function render_custom_audio_block( $attributes ) {
    $title     = $attributes['title'];
    $albumArt  = $attributes['albumArt'];
    $albumName = $attributes['albumName'];
    $audioURL  = $attributes['audioURL'];

    ob_start();
    ?>
    <div class="custom-audio-block">
        <?php if ( ! empty( $title ) ) : ?>
            <h3><?php echo esc_html( $title ); ?></h3>
        <?php endif; ?>

        <?php if ( ! empty( $albumArt ) ) : ?>
            <img src="<?php echo esc_url( $albumArt ); ?>" alt="<?php echo esc_attr( $albumName ); ?>">
        <?php endif; ?>

        <?php if ( ! empty( $albumName ) ) : ?>
            <p><?php echo esc_html( $albumName ); ?></p>
        <?php endif; ?>

        <?php if ( ! empty( $audioURL ) ) : ?>
            <audio controls>
                <source src="<?php echo esc_url( $audioURL ); ?>" type="audio/mpeg">
                Your browser does not support the audio element.
            </audio>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// Validate and sanitize block attributes
function sanitize_custom_audio_block_attributes( $attributes ) {
    $sanitized_attributes = array();

    // Sanitize title
    if ( isset( $attributes['title'] ) ) {
        $sanitized_attributes['title'] = sanitize_text_field( $attributes['title'] );
    }

    // Sanitize album name
    if ( isset( $attributes['albumName'] ) ) {
        $sanitized_attributes['albumName'] = sanitize_text_field( $attributes['albumName'] );
    }

    // Sanitize audio URL
    if ( isset( $attributes['audioURL'] ) ) {
        $sanitized_attributes['audioURL'] = esc_url_raw( $attributes['audioURL'] );
    }

    // Sanitize album art URL
    if ( isset( $attributes['albumArt'] ) ) {
        $sanitized_attributes['albumArt'] = esc_url_raw( $attributes['albumArt'] );
    }

    return $sanitized_attributes;
}
add_filter( 'block_type_metadata_settings', 'sanitize_custom_audio_block_attributes', 10, 2 );


// Enqueue block editor assets
function custom_audio_block_editor_assets_script() {
    wp_enqueue_script(
        'custom-audio-block-editor-script',
        plugins_url( '/js/editor.js', __FILE__ ),
        array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-media-utils' ),
        filemtime( plugin_dir_path( __FILE__ ) . 'js/editor.js' )
    );
}
add_action( 'enqueue_block_editor_assets', 'custom_audio_block_editor_assets_script' );

// Localization
function custom_audio_block_localize_script() {
    wp_localize_script( 'custom-audio-block-editor-script', 'customAudioBlock', array(
        'mediaTitle' => esc_html__( 'Choose Album Art', 'custom-audio-block' ),
        'buttonTitle' => esc_html__( 'Set Album Art', 'custom-audio-block' ),
    ) );
}
add_action( 'enqueue_block_editor_assets', 'custom_audio_block_localize_script' );
