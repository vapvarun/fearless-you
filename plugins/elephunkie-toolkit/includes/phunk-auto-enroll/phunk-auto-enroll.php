<?php
/**
 * Plugin Name: Phunkie Auto Enroll for WP Fusion and LearnDash
 * Plugin URI: https://elephunkie.com
 * Description: Automatically enrolls users into LearnDash courses based on their WP Fusion tags, hooking into both the WP Fusion batch process and individual user tag resync.
 * Version: 1.0.0
 * Author: Jonathan Albiar
 * Author URI: https://elephunkie.com
 * Text Domain: phunkie-auto-enroll
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

register_activation_hook( __FILE__, 'phunkie_auto_enroll_dependency_check' );

function phunkie_auto_enroll_dependency_check() {
    if ( ! class_exists( 'LDLMS_Factory_Post' ) || ! function_exists( 'wp_fusion' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-warning is-dismissible"><p>Phunkie Auto Enroll for WP Fusion and LearnDash requires both the LearnDash and WP Fusion plugins to be active.</p></div>';
        });
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }
}

add_action( 'wp_ajax_resync_contact', 'phunk_handle_resync_contact' );

function phunk_handle_resync_contact() {
    check_ajax_referer( 'wpf_admin_nonce', '_ajax_nonce' );
    $user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
    
    if ( $user_id && current_user_can( 'edit_user', $user_id ) ) {
        phunk_auto_enroll_all_courses( $user_id );
        wp_send_json_success( array( 'message' => 'User tags resynced and auto-enrollment completed.' ) );
    } else {
        wp_send_json_error( 'Error: Invalid user ID or insufficient permissions.' );
    }

    wp_die();
}

add_filter( 'wpf_export_options', 'phunk_register_batch_operation' );

function phunk_register_batch_operation( $options ) {
    $options['phunk_auto_enroll'] = array(
        'label'   => 'Auto-enroll Users',
        'title'   => 'Users',
        'tooltip' => 'Automatically enroll users in courses based on their tags.',
    );

    return $options;
}

add_filter( 'wpf_batch_phunk_auto_enroll_init', 'phunk_auto_enroll_init' );

function phunk_auto_enroll_init() {
    $args = array(
        'fields' => 'ids',
    );

    $users = get_users( $args );
    return $users;
}

add_action( 'wpf_batch_phunk_auto_enroll', 'phunk_auto_enroll_step' );

function phunk_auto_enroll_step( $user_id ) {
    $error_count = get_option( 'phunk_auto_enroll_error_count', 0 );
    if ( $error_count >= 2 ) {
        delete_option( 'phunk_auto_enroll_error_count' );
        error_log( 'Batch process canceled due to multiple errors.' );
        return;
    }

    try {
        phunk_auto_enroll_all_courses( $user_id );
        update_option( 'phunk_auto_enroll_error_count', 0 );
    } catch ( Exception $e ) {
        update_option( 'phunk_auto_enroll_error_count', ++$error_count );
        error_log( 'Error in batch process: ' . $e->getMessage() );
    }
}

function phunk_auto_enroll_all_courses( $user_id ) {
    if ( ! class_exists( 'LDLMS_Factory_Post' ) || ! function_exists( 'wp_fusion' ) ) {
        return;
    }

    $user_tags = wp_fusion()->user->get_tags( $user_id );
    $courses = learndash_get_courses();

    foreach ( $courses as $course_id => $course ) {
        $wpf_settings = get_post_meta( $course_id, 'wpf-settings', true );
        $access_tags = isset( $wpf_settings['allow_tags'] ) ? $wpf_settings['allow_tags'] : array();

        if ( !empty( $access_tags ) ) {
            $has_access = array_intersect( $user_tags, $access_tags );

            if ( !empty( $has_access ) && !ld_course_check_user_access( $course_id, $user_id ) ) {
                ld_update_course_access( $user_id, $course_id, false );
            }
        }
    }
}
