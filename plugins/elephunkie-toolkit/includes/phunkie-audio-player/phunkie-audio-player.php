<?php
/*
Plugin Name: Phunkie Audio Player
Plugin URI: https://elephunkie.com
Description: Customizes the WordPress audio player and displays the embedded image from the MP3 file's ID3 tags.
Version: 1.0
Author: Jonathan Albiar
Author URI: https://elephunkie.com
License: GPL2
*/

// Enqueue custom CSS for the audio player
function phunkie_audio_styles() {
    wp_enqueue_style('phunkie-audio-style', plugins_url('css/custom-audio-style.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'phunkie_audio_styles');

// Override the default audio shortcode to include ID3 image if available
function phunkie_custom_audio_shortcode($output, $atts, $audio, $post_id, $library) {
    if (!empty($atts['src'])) {
        require_once(ABSPATH . 'wp-includes/ID3/getid3.php');
        $getID3 = new getID3;
        $ThisFileInfo = $getID3->analyze($atts['src']);
        if (isset($ThisFileInfo['id3v2']['APIC'][0]['data'])) {
            $image_data = 'data:' . $ThisFileInfo['id3v2']['APIC'][0]['mime'] . ';base64,' . base64_encode($ThisFileInfo['id3v2']['APIC'][0]['data']);
            $image_html = '<img src="' . esc_url($image_data) . '" style="max-width:100%; display:block; margin-bottom:10px;">';
            $output = '<div class="wp-block-audio">' . $image_html . $audio . '</div>';
        }
    }
    return $output;
}
add_filter('wp_audio_shortcode', 'phunkie_custom_audio_shortcode', 10, 5);


// Ensure that the getID3 library is loaded from WordPress core
function phunkie_load_getid3() {
    require_once(ABSPATH . 'wp-admin/includes/media.php');
}
add_action('init', 'phunkie_load_getid3');
