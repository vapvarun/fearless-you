<?php
add_action( 'wp_enqueue_scripts', 'ldfc_assets' );
function ldfc_assets() {

     global $post;

     // Scripts
     wp_register_script( 'favcon', LDFC_URL . '/assets/js/favcon.js', array('jquery'), LDFC_VER );
     wp_enqueue_script( 'favcon' );

     // Styles
     wp_register_style( 'favcon', LDFC_URL . '/assets/css/favcon.css', array(), LDFC_VER );
     wp_enqueue_style( 'favcon' );

     wp_localize_script( 'favcon', 'favcon_ajax_call', array(
        'adminAjax' => admin_url('admin-ajax.php'),
    ) );

    $settings   = get_option( 'favcon_settings', array() );
    $favcon_css = ':root {';

    if( isset($settings['accent_color']) && !empty($settings['accent_color']) ) {
         $favcon_css .= '--ldfc-primary: ' . $settings['accent_color'] . ' !important; ';
    }

    if( isset( $settings['button_border_radius']) && !empty($settings['button_border_radius']) ) {
         $favcon_css .= '--ldfc-button-radius: ' . $settings['button_border_radius'] . 'px !important; ';
    }

    if( isset( $settings['table_border_radius']) && !empty($settings['table_border_radius']) ) {
         $favcon_css .= '--ldfc-table-radius: ' . $settings['table_border_radius'] . 'px !important; ';
    }

    $favcon_css .= '}';

    wp_add_inline_style( 'favcon', $favcon_css );

}

function ldfc_admin_assets() {

     wp_register_style( 'ldfc-snap-admin', LDFC_URL . 'assets/css/admin/snap-admin.css', array(), LDFC_VER );

     wp_enqueue_script( 'wp-color-picker' );
     wp_enqueue_style( 'ldfc-snap-admin' );
     wp_enqueue_style( 'wp-color-picker' );

}

add_action( 'admin_enqueue_scripts', 'ldfc_admin_view_assets' );
function ldfc_admin_view_assets() {

     wp_register_style( 'ldfc-admin', LDFC_URL . 'assets/css/ldfc-admin.css', array(), LDFC_VER );
     wp_enqueue_style( 'ldfc-admin' );

}

add_action( 'admin_head', 'ldfc_dashboard_custom_styling' );
function ldfc_dashboard_custom_styling() {

     $settings   = get_option( 'favcon_settings', array() );
     $favcon_css = ':root {';

     if( isset($settings['accent_color']) && !empty($settings['accent_color']) ) {
          $favcon_css .= '--ldfc-primary: ' . $settings['accent_color'] . ' !important; ';
     }

     if( isset( $settings['button_border_radius']) && !empty($settings['button_border_radius']) ) {
          $favcon_css .= '--ldfc-button-radius: ' . $settings['button_border_radius'] . 'px !important; ';
     }

     if( isset( $settings['table_border_radius']) && !empty($settings['table_border_radius']) ) {
          $favcon_css .= '--ldfc-table-radius: ' . $settings['table_border_radius'] . 'px !important; ';
     }

     $favcon_css .= '}';

     echo '<style type="text/css">' . $favcon_css . '</style>';

}
