<?php
/**
 * Plugin Name: LearnDash Courses to CSV
 * Description: Generates a CSV of LearnDash courses with specified information.
 * Version: 1.7
 * Author: Jonathan Albiar
 * Author URI: https://elephunkie.com
 * Text Domain: phunk-learndash-courses
 */

define('PHUNK_DEBUG_EMAIL', 'jonathan@elephunkie.com'); // Set your email address here

// Hook to create the CSV file on plugin activation
register_activation_hook(__FILE__, 'phunk_on_activation');

// Hook to create the CSV file when admin_init is called
add_action('admin_init', 'phunk_generate_or_download_csv');

// Hook to add admin bar menu items
add_action('admin_bar_menu', 'phunk_add_admin_bar_items', 100);

// Function to handle plugin activation
function phunk_on_activation() {
    phunk_generate_learndash_courses_csv();
    wp_schedule_single_event(time() + 30, 'phunk_send_debug_email_event');
}

// Function to generate or download the CSV file based on the query parameter
function phunk_generate_or_download_csv() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_GET['phunk_action'])) {
        $upload_dir = wp_upload_dir();
        $filepath = $upload_dir['basedir'] . '/learndash_courses.csv';

        if ($_GET['phunk_action'] === 'download_csv') {
            if (file_exists($filepath)) {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="learndash_courses.csv"');
                readfile($filepath);
                exit;
            } else {
                phunk_send_debug_email('CSV file does not exist: ' . $filepath);
            }
        }

        if ($_GET['phunk_action'] === 'regenerate_csv') {
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            phunk_generate_learndash_courses_csv();
            wp_redirect(admin_url());
            exit;
        }
    }
}

// Function to generate the CSV file
function phunk_generate_learndash_courses_csv() {
    $upload_dir = wp_upload_dir();
    $filepath = $upload_dir['basedir'] . '/learndash_courses.csv';

    // Check if the uploads directory is writable
    if (!is_writable($upload_dir['basedir'])) {
        phunk_send_debug_email('Uploads directory is not writable: ' . $upload_dir['basedir']);
        return;
    }

    // Open the file for writing
    $file = fopen($filepath, 'w');

    if ($file === false) {
        phunk_send_debug_email('Failed to open file for writing: ' . $filepath);
        return;
    }

    // Write the CSV header
    fputcsv($file, ['Course Name', 'Access Mode', 'Course Price', 'Button URL', 'Link with Tag']);

    // Fetch all LearnDash courses
    $courses = get_posts([
        'post_type' => 'sfwd-courses',
        'posts_per_page' => -1,
    ]);

    if (empty($courses)) {
        phunk_send_debug_email('No LearnDash courses found');
    }

    foreach ($courses as $course) {
        $course_id = $course->ID;

        // Get course data using the correct meta keys
        $course_name = get_the_title($course_id);
        $access_mode = get_post_meta($course_id, '_sfwd-courses_course_access_mode', true);
        $course_price = get_post_meta($course_id, '_sfwd-courses_course_price', true);
        $button_url = get_post_meta($course_id, '_sfwd-courses_course_button_url', true);

        // Get WP Fusion field data
        $wpf_settings = get_post_meta($course_id, 'wpf_settings', true);
        $link_with_tag = isset($wpf_settings['apply_tags']) ? implode(', ', $wpf_settings['apply_tags']) : '';

        // Skip row if any field is empty
        if (empty($course_name) || empty($access_mode) || empty($course_price) || empty($button_url) || empty($link_with_tag)) {
            continue;
        }

        // Write the course data to the CSV file
        fputcsv($file, [$course_name, $access_mode, $course_price, $button_url, $link_with_tag]);
    }

    // Close the file
    fclose($file);

    // Log file creation success
    phunk_send_debug_email('CSV file created successfully: ' . $filepath);
}

// Function to add admin bar items
function phunk_add_admin_bar_items($admin_bar) {
    if (!current_user_can('manage_options')) {
        return;
    }

    $admin_bar->add_menu(array(
        'id'    => 'phunk-download-ld-csv',
        'title' => '<span style="color:white;background-color:red;padding:3px 7px;border-radius:3px;">Download LD CSV</span>',
        'href'  => add_query_arg('phunk_action', 'download_csv'),
        'meta'  => array(
            'title' => __('Download LD CSV'),
        ),
    ));

    $admin_bar->add_menu(array(
        'id'    => 'phunk-regenerate-ld-csv',
        'title' => '<span style="color:white;background-color:blue;padding:3px 7px;border-radius:3px;">Regenerate CSV</span>',
        'href'  => add_query_arg('phunk_action', 'regenerate_csv'),
        'meta'  => array(
            'title' => __('Regenerate CSV'),
        ),
    ));
}

// Function to send debug emails
function phunk_send_debug_email($message) {
    $subject = 'LearnDash Courses to CSV Debug Info';
    $body = $message;
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    wp_mail(PHUNK_DEBUG_EMAIL, $subject, $body, $headers);
}

// Hook to send the debug email once
add_action('phunk_send_debug_email_event', 'phunk_send_debug_email_event_callback');

function phunk_send_debug_email_event_callback() {
    phunk_send_debug_email('CSV generation completed.');
}
?>