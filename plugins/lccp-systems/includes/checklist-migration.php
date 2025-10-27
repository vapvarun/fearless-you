<?php
/**
 * Checklist Migration Script
 * Converts old [checklist_in_post] shortcodes to new [lccp_checklist]
 */

// Add this function to your theme or run it once
function migrate_old_checklists() {
    global $wpdb;
    
    // Find all posts with the old shortcode
    $posts = $wpdb->get_results("
        SELECT ID, post_content 
        FROM {$wpdb->posts} 
        WHERE post_content LIKE '%[checklist_in_post]%'
        AND post_status IN ('publish', 'draft', 'private')
    ");
    
    $updated = 0;
    
    foreach ($posts as $post) {
        $content = $post->post_content;
        $original = $content;
        
        // Replace opening shortcode
        $content = str_replace('[checklist_in_post]', '[lccp_checklist]', $content);
        
        // Replace closing shortcode
        $content = str_replace('[/checklist_in_post]', '[/lccp_checklist]', $content);
        
        // Update if changed
        if ($content !== $original) {
            $wpdb->update(
                $wpdb->posts,
                array('post_content' => $content),
                array('ID' => $post->ID)
            );
            $updated++;
        }
    }
    
    return $updated;
}

// Function to add backward compatibility
add_shortcode('checklist_in_post', 'lccp_checklist_backward_compat');

function lccp_checklist_backward_compat($atts, $content = null) {
    // Use the new shortcode handler
    return do_shortcode('[lccp_checklist]' . $content . '[/lccp_checklist]');
}