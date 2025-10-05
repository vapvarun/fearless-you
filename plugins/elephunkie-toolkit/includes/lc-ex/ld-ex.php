<?php
/**
 * Plugin Name: LearnDash Course Exporter with Meta Keys
 * Description: Exports a specific LearnDash course, including lessons, topics, and quizzes, and retrieves all associated meta keys from the database.
 * Version: 1.3
 * Author: Jonathan Albiar
 * Author URI: https://elephunkie.com
 * Text Domain: phunk-learndash-export
 */

// Add a button in the WordPress admin bar to trigger the export
add_action('admin_bar_menu', 'phunk_add_export_button', 100);

function phunk_add_export_button($admin_bar) {
    if (current_user_can('manage_options')) {
        $admin_bar->add_menu(array(
            'id'    => 'learndash-course-export',
            'title' => 'Export LearnDash Course',
            'href'  => wp_nonce_url(admin_url('admin-post.php?action=learndash_course_export'), 'phunk_export_course'),
        ));
    }
}

// Handle the export request
add_action('admin_post_learndash_course_export', 'phunk_handle_export');

function phunk_handle_export() {
    // Verify nonce for security
    check_admin_referer('phunk_export_course');

    // The specific course ID to export
    $course_id = 224082;

    // Lesson IDs that need to be exported
    $lesson_ids = array(224119, 224117, 224115, 224113, 224112, 224110, 224109, 224108, 224106, 224104, 224103, 224102, 224101);

    // Generate the export XML
    $xml = phunk_generate_learndash_export($course_id, $lesson_ids);

    if ($xml) {
        // Set headers for file download
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="learndash_course_export.xml"');
        header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);

        echo $xml;
        exit;
    } else {
        wp_die('Failed to generate export.');
    }
}

// Function to generate the XML for the specific course, the provided lessons, and their child elements
function phunk_generate_learndash_export($course_id, $lesson_ids) {
    global $wpdb;

    // Initialize the XML content with WXR headers and version
    $xml_content = "<?xml version='1.0' encoding='UTF-8' ?>\n";
    $xml_content .= "<!-- Export of LearnDash Course ID: $course_id -->\n";
    $xml_content .= "<rss version='2.0' xmlns:excerpt='http://wordpress.org/export/1.2/excerpt/' xmlns:content='http://purl.org/rss/1.0/modules/content/' xmlns:wfw='http://wellformedweb.org/CommentAPI/' xmlns:dc='http://purl.org/dc/elements/1.1/' xmlns:wp='http://wordpress.org/export/1.2/'>\n";
    $xml_content .= "<channel>\n";
    $xml_content .= "<wp:wxr_version>1.2</wp:wxr_version>\n";  // Required WXR version

    // Export the course
    $xml_content .= phunk_export_post_with_meta($course_id);

    // Recursively export lessons, topics, and quizzes associated with the course
    $xml_content .= phunk_export_course_children($course_id);

    // Manually add the provided lesson IDs
    foreach ($lesson_ids as $lesson_id) {
        $xml_content .= phunk_export_post_with_meta($lesson_id);

        // Export all child topics and quizzes for each lesson
        $xml_content .= phunk_export_course_children($lesson_id);
    }

    $xml_content .= "</channel>\n";
    $xml_content .= "</rss>\n";

    return $xml_content;
}

// Function to export a post and all its meta keys
function phunk_export_post_with_meta($post_id) {
    $output = "";
    $post = get_post($post_id);

    if (!$post) {
        return '';
    }

    // Prepare post data in XML format
    $output .= "<item>\n";
    $output .= "<title>" . esc_xml($post->post_title) . "</title>\n";
    $output .= "<link>" . esc_url(get_permalink($post->ID)) . "</link>\n";
    $output .= "<pubDate>" . esc_html($post->post_date) . "</pubDate>\n";
    $output .= "<dc:creator><![CDATA[" . esc_html(get_the_author_meta('display_name', $post->post_author)) . "]]></dc:creator>\n";
    $output .= "<guid isPermaLink='false'>" . esc_url(get_permalink($post->ID)) . "</guid>\n";
    $output .= "<description><![CDATA[" . esc_html($post->post_excerpt) . "]]></description>\n";
    $output .= "<content:encoded><![CDATA[" . esc_html($post->post_content) . "]]></content:encoded>\n";

    // Export post meta
    $meta = get_post_meta($post_id);
    foreach ($meta as $meta_key => $meta_value) {
        $output .= "<wp:postmeta>\n";
        $output .= "<wp:meta_key>" . esc_xml($meta_key) . "</wp:meta_key>\n";
        $output .= "<wp:meta_value><![CDATA[" . esc_html(serialize($meta_value)) . "]]></wp:meta_value>\n";
        $output .= "</wp:postmeta>\n";
    }

    $output .= "</item>\n";

    return $output;
}

// Function to recursively export all child posts (topics, quizzes) of a parent post (course, lesson)
function phunk_export_course_children($parent_id) {
    global $wpdb;

    $output = "";
    
    // Post types for topics and quizzes
    $child_types = array('sfwd-topic', 'sfwd-quiz');

    foreach ($child_types as $type) {
        // Get child posts (topics, quizzes) associated with the parent lesson or course
        $children = $wpdb->get_results($wpdb->prepare("
            SELECT ID FROM $wpdb->posts 
            WHERE post_type = %s 
            AND post_parent = %d
        ", $type, $parent_id));

        foreach ($children as $child) {
            // Export the child post and its metadata
            $output .= phunk_export_post_with_meta($child->ID);

            // Recursively export child posts (if any) of this child (e.g., quizzes of topics)
            $output .= phunk_export_course_children($child->ID);
        }
    }

    return $output;
}
