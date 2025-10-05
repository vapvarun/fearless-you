<?php
/**
 * Enable and display breadcrumbs
 */

// Enable BuddyBoss breadcrumbs
add_filter('buddyboss_breadcrumbs', '__return_true');
add_filter('theme_mod_breadcrumb', '__return_true');

// Add breadcrumbs to pages
add_action('buddyboss_before_content', 'fli_display_breadcrumbs', 5);
add_action('buddyboss_before_single_content', 'fli_display_breadcrumbs', 5);
function fli_display_breadcrumbs() {
    if (function_exists('buddyboss_breadcrumb')) {
        buddyboss_breadcrumb();
    } else {
        // Fallback breadcrumbs
        fli_custom_breadcrumbs();
    }
}

// Custom breadcrumbs function
function fli_custom_breadcrumbs() {
    // Don't show on homepage
    if (is_front_page()) {
        return;
    }
    
    echo '<div class="bb-breadcrumbs breadcrumbs">';
    echo '<a href="' . home_url() . '">Home</a>';
    echo '<span class="separator"> / </span>';
    
    if (is_category() || is_single()) {
        $category = get_the_category();
        if ($category) {
            echo '<a href="' . get_category_link($category[0]->term_id) . '">' . $category[0]->cat_name . '</a>';
            echo '<span class="separator"> / </span>';
        }
        if (is_single()) {
            echo '<span class="current">' . get_the_title() . '</span>';
        }
    } elseif (is_page()) {
        // Check for parent pages
        global $post;
        if ($post->post_parent) {
            $ancestors = array_reverse(get_post_ancestors($post->ID));
            foreach ($ancestors as $ancestor) {
                echo '<a href="' . get_permalink($ancestor) . '">' . get_the_title($ancestor) . '</a>';
                echo '<span class="separator"> / </span>';
            }
        }
        echo '<span class="current">' . get_the_title() . '</span>';
    } elseif (is_post_type_archive()) {
        echo '<span class="current">' . post_type_archive_title('', false) . '</span>';
    } elseif (is_archive()) {
        echo '<span class="current">' . get_the_archive_title() . '</span>';
    } elseif (is_search()) {
        echo '<span class="current">Search Results</span>';
    } elseif (is_404()) {
        echo '<span class="current">404 Not Found</span>';
    }
    
    echo '</div>';
}

// Enable LearnDash breadcrumbs
add_filter('learndash_breadcrumbs', '__return_true');
add_filter('learndash_settings_fields', 'fli_enable_ld_breadcrumbs');
function fli_enable_ld_breadcrumbs($fields) {
    if (isset($fields['settings_appearance_breadcrumbs']['settings_appearance_breadcrumbs_enabled'])) {
        $fields['settings_appearance_breadcrumbs']['settings_appearance_breadcrumbs_enabled']['default'] = 'yes';
    }
    return $fields;
}

// Force LearnDash breadcrumbs to display
add_action('learndash_before_main_content', 'fli_force_ld_breadcrumbs', 5);
add_action('learndash-course-before', 'fli_force_ld_breadcrumbs', 5);
add_action('learndash-lesson-before', 'fli_force_ld_breadcrumbs', 5);
add_action('learndash-topic-before', 'fli_force_ld_breadcrumbs', 5);
add_action('learndash-quiz-before', 'fli_force_ld_breadcrumbs', 5);
function fli_force_ld_breadcrumbs() {
    // Don't show on course pages
    if (get_post_type() === 'sfwd-courses') {
        return;
    }
    
    if (function_exists('learndash_get_breadcrumbs')) {
        $breadcrumbs = learndash_get_breadcrumbs();
        if ($breadcrumbs) {
            // Process the breadcrumbs to format titles
            $breadcrumbs = preg_replace_callback('/>([^<]+)</s', function($matches) {
                return '>' . fli_format_breadcrumb_title($matches[1]) . '<';
            }, $breadcrumbs);
            echo '<div class="ld-breadcrumbs learndash-breadcrumbs fli-ld-breadcrumbs">' . $breadcrumbs . '</div>';
        }
    }
}

// Add breadcrumbs for LearnDash course archive
add_action('learndash_before_course_list', 'fli_course_archive_breadcrumbs');
function fli_course_archive_breadcrumbs() {
    ?>
    <div class="ld-breadcrumbs">
        <a href="<?php echo home_url(); ?>">Home</a>
        <span class="ld-breadcrumbs-separator">/</span>
        <span class="current">Courses</span>
    </div>
    <?php
}

// Custom breadcrumbs for specific LearnDash pages
add_filter('the_content', 'fli_add_learndash_breadcrumbs', 5);
function fli_add_learndash_breadcrumbs($content) {
    // Only for LearnDash post types
    if (!is_singular(['sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz'])) {
        return $content;
    }
    
    $post_type = get_post_type();
    
    // Don't show breadcrumbs on main course page
    if ($post_type === 'sfwd-courses') {
        return $content;
    }
    
    $breadcrumb = '';
    $breadcrumb .= '<div class="ld-breadcrumbs fli-ld-breadcrumbs">';
    
    // Start with course (no Home > Courses)
    $course_id = learndash_get_course_id();
    if ($course_id) {
        $course_title = fli_format_breadcrumb_title(get_the_title($course_id));
        $breadcrumb .= '<a href="' . get_permalink($course_id) . '">' . $course_title . '</a>';
    }
    
    // Add lesson link if in topic/quiz
    if ($post_type === 'sfwd-topic' || $post_type === 'sfwd-quiz') {
        $lesson_id = learndash_get_lesson_id();
        if ($lesson_id) {
            $breadcrumb .= '<span class="ld-breadcrumbs-separator"> / </span>';
            $lesson_title = fli_format_breadcrumb_title(get_the_title($lesson_id));
            $breadcrumb .= '<a href="' . get_permalink($lesson_id) . '">' . $lesson_title . '</a>';
        }
    }
    
    // Add current page
    $breadcrumb .= '<span class="ld-breadcrumbs-separator"> / </span>';
    $current_title = fli_format_breadcrumb_title(get_the_title());
    $breadcrumb .= '<span class="current">' . $current_title . '</span>';
    $breadcrumb .= '</div>';
    
    // Add breadcrumb before content
    return $breadcrumb . $content;
}

// Helper function to format breadcrumb titles with weighted text
function fli_format_breadcrumb_title($title) {
    // Check for colon or dash
    if (strpos($title, ':') !== false) {
        $parts = explode(':', $title, 2);
        return '<span class="breadcrumb-title-heavy">' . trim($parts[0]) . ':</span> <span class="breadcrumb-title-light">' . trim($parts[1]) . '</span>';
    } elseif (strpos($title, ' - ') !== false) {
        $parts = explode(' - ', $title, 2);
        return '<span class="breadcrumb-title-heavy">' . trim($parts[0]) . '</span> - <span class="breadcrumb-title-light">' . trim($parts[1]) . '</span>';
    }
    return $title;
}