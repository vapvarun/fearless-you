<?php
/**
 * LearnDash-specific functions for Dasher plugin
 *
 * @package Dasher
 * @since 1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get all LearnDash courses (filtered to LCCP category only).
 *
 * @since 1.0.0
 * @return array Array of course objects.
 */
function dasher_get_learndash_courses() {
    // Check if user has access to LCCP courses
    if (!dasher_user_can_access_lccp()) {
        return array();
    }
    
    $args = array(
        'post_type' => 'sfwd-courses',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'tax_query' => array(
            array(
                'taxonomy' => 'ld_course_category',
                'field' => 'slug',
                'terms' => 'lccp',
            ),
        ),
    );
    
    return get_posts($args);
}

/**
 * Get course progress for a user.
 *
 * @since 1.0.0
 * @param int $user_id   The user ID.
 * @param int $course_id The course ID.
 * @return array Course progress data.
 */
function dasher_get_course_progress($user_id, $course_id) {
    if (!function_exists('learndash_get_course_progress')) {
        return array();
    }
    
    $progress = learndash_get_course_progress($user_id, $course_id);
    
    if (empty($progress)) {
        return array(
            'completed' => 0,
            'total' => 0,
            'percentage' => 0,
        );
    }
    
    return array(
        'completed' => $progress['completed'],
        'total' => $progress['total'],
        'percentage' => round(($progress['completed'] / $progress['total']) * 100),
    );
}

/**
 * Get quiz results for a user.
 *
 * @since 1.0.0
 * @param int $user_id The user ID.
 * @param int $quiz_id The quiz ID.
 * @return array Quiz results data.
 */
function dasher_get_quiz_results($user_id, $quiz_id) {
    if (!function_exists('learndash_get_quiz_statistics_by_user')) {
        return array();
    }
    
    $statistics = learndash_get_quiz_statistics_by_user($user_id, $quiz_id);
    
    if (empty($statistics)) {
        return array(
            'score' => 0,
            'pass' => false,
            'time' => 0,
        );
    }
    
    return array(
        'score' => $statistics['score'],
        'pass' => $statistics['pass'],
        'time' => $statistics['time'],
    );
}

/**
 * Get all lessons in a course.
 *
 * @since 1.0.0
 * @param int $course_id The course ID.
 * @return array Array of lesson objects.
 */
function dasher_get_course_lessons($course_id) {
    if (!function_exists('learndash_get_course_lessons_list')) {
        return array();
    }
    
    return learndash_get_course_lessons_list($course_id);
}

/**
 * Get all topics in a lesson.
 *
 * @since 1.0.0
 * @param int $lesson_id The lesson ID.
 * @return array Array of topic objects.
 */
function dasher_get_lesson_topics($lesson_id) {
    if (!function_exists('learndash_get_topic_list')) {
        return array();
    }
    
    return learndash_get_topic_list($lesson_id);
}

/**
 * Get all quizzes in a course.
 *
 * @since 1.0.0
 * @param int $course_id The course ID.
 * @return array Array of quiz objects.
 */
function dasher_get_course_quizzes($course_id) {
    if (!function_exists('learndash_get_course_quiz_list')) {
        return array();
    }
    
    return learndash_get_course_quiz_list($course_id);
}

/**
 * Check if a user has completed a course.
 *
 * @since 1.0.0
 * @param int $user_id   The user ID.
 * @param int $course_id The course ID.
 * @return bool True if completed, false otherwise.
 */
function dasher_has_user_completed_course($user_id, $course_id) {
    if (!function_exists('learndash_course_completed')) {
        return false;
    }
    
    return learndash_course_completed($user_id, $course_id);
}

/**
 * Get the final quiz for a course.
 *
 * @since 1.0.0
 * @param int $course_id The course ID.
 * @return int|false The quiz ID or false if not found.
 */
function dasher_get_course_final_quiz($course_id) {
    $quizzes = dasher_get_course_quizzes($course_id);
    
    if (empty($quizzes)) {
        return false;
    }
    
    // Get the last quiz in the course
    $last_quiz = end($quizzes);
    
    return $last_quiz->ID;
}

/**
 * Get user's course enrollment date.
 *
 * @since 1.0.0
 * @param int $user_id   The user ID.
 * @param int $course_id The course ID.
 * @return string|false The enrollment date or false if not found.
 */
function dasher_get_user_enrollment_date($user_id, $course_id) {
    if (!function_exists('learndash_user_get_enrolled_date')) {
        return false;
    }
    
    return learndash_user_get_enrolled_date($user_id, $course_id);
}

/**
 * Get all users enrolled in a course.
 *
 * @since 1.0.0
 * @param int $course_id The course ID.
 * @return array Array of user IDs.
 */
function dasher_get_course_enrolled_users($course_id) {
    if (!function_exists('learndash_get_course_users_access_from_meta')) {
        return array();
    }
    
    return learndash_get_course_users_access_from_meta($course_id);
}

/**
 * Check if LearnDash is active.
 *
 * @since 1.0.0
 * @return bool True if LearnDash is active, false otherwise.
 */
function dasher_is_learndash_active() {
    return class_exists('SFWD_LMS');
}

/**
 * Get all LearnDash courses with optional filtering.
 *
 * @since 1.0.0
 * @param array $args Optional. Additional arguments to pass to get_posts.
 * @return array Array of course post objects.
 */
function dasher_get_courses($args = array()) {
    if (!dasher_is_learndash_active()) {
        return array();
    }
    
    $defaults = array(
        'post_type' => 'sfwd-courses',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    );
    
    $args = wp_parse_args($args, $defaults);
    
    return get_posts($args);
}

/**
 * Get all quizzes for a course, lesson, or topic.
 *
 * @since 1.0.0
 * @param int    $post_id   The post ID (course, lesson, or topic).
 * @param string $post_type The post type ('course', 'lesson', 'topic').
 * @param int    $course_id The course ID (if getting quizzes for a lesson or topic).
 * @return array Array of quiz data.
 */
function dasher_get_quizzes($post_id, $post_type = 'course', $course_id = 0) {
    if (!dasher_is_learndash_active()) {
        return array();
    }
    
    switch ($post_type) {
        case 'course':
            return learndash_get_course_quiz_list($post_id);
        
        case 'lesson':
            return learndash_get_lesson_quiz_list($post_id, null, $course_id);
        
        case 'topic':
            return learndash_get_lesson_quiz_list($post_id, null, $course_id);
        
        default:
            return array();
    }
}

/**
 * Get the courses a user is enrolled in.
 *
 * @since 1.0.0
 * @param int $user_id The user ID.
 * @return array Array of course IDs.
 */
function dasher_get_user_courses($user_id) {
    if (!dasher_is_learndash_active()) {
        return array();
    }
    
    return learndash_user_get_enrolled_courses($user_id);
}

/**
 * Get a user's progress in a course.
 *
 * @since 1.0.0
 * @param int $user_id   The user ID.
 * @param int $course_id The course ID.
 * @return array User progress data.
 */
function dasher_get_user_course_progress($user_id, $course_id) {
    if (!dasher_is_learndash_active()) {
        return array();
    }
    
    return learndash_user_get_course_progress($user_id, $course_id);
}

/**
 * Check if a user has completed a lesson.
 *
 * @since 1.0.0
 * @param int $user_id   The user ID.
 * @param int $lesson_id The lesson ID.
 * @param int $course_id The course ID.
 * @return bool True if the lesson is completed, false otherwise.
 */
function dasher_is_lesson_completed($user_id, $lesson_id, $course_id) {
    if (!dasher_is_learndash_active()) {
        return false;
    }
    
    return learndash_is_lesson_complete($user_id, $lesson_id, $course_id);
}

/**
 * Check if a user has completed a topic.
 *
 * @since 1.0.0
 * @param int $user_id   The user ID.
 * @param int $topic_id  The topic ID.
 * @param int $course_id The course ID.
 * @return bool True if the topic is completed, false otherwise.
 */
function dasher_is_topic_completed($user_id, $topic_id, $course_id) {
    if (!dasher_is_learndash_active()) {
        return false;
    }
    
    return learndash_is_topic_complete($user_id, $topic_id, $course_id);
}

/**
 * Get a user's course progress percentage.
 *
 * @since 1.0.0
 * @param int $user_id   The user ID.
 * @param int $course_id The course ID.
 * @return int The progress percentage (0-100).
 */
function dasher_get_course_progress_percentage($user_id, $course_id) {
    if (!dasher_is_learndash_active()) {
        return 0;
    }
    
    $total_steps = learndash_get_course_steps_count($course_id);
    
    if ($total_steps <= 0) {
        return 0;
    }
    
    $completed_steps = count(learndash_user_get_completed_steps($user_id, $course_id));
    
    return round(($completed_steps / $total_steps) * 100);
}

/**
 * Get the next incomplete step in a course for a user.
 *
 * @since 1.0.0
 * @param int $user_id   The user ID.
 * @param int $course_id The course ID.
 * @return array|null The next step data or null if all steps are completed.
 */
function dasher_get_next_incomplete_step($user_id, $course_id) {
    if (!dasher_is_learndash_active()) {
        return null;
    }
    
    $course_progress = learndash_user_get_course_progress($user_id, $course_id);
    
    // If the course is completed, return null
    if (isset($course_progress['completed']) && $course_progress['completed'] === true) {
        return null;
    }
    
    // Get all course steps
    $steps = learndash_get_course_steps($course_id);
    
    // Get completed steps
    $completed_steps = learndash_user_get_completed_steps($user_id, $course_id);
    
    // Find the first incomplete step
    foreach ($steps as $step_id) {
        if (!in_array($step_id, $completed_steps)) {
            $step_type = get_post_type($step_id);
            $step_title = get_the_title($step_id);
            
            return array(
                'id' => $step_id,
                'type' => $step_type,
                'title' => $step_title,
            );
        }
    }
    
    return null;
}

/**
 * Get all LearnDash post types.
 *
 * @since 1.0.0
 * @return array Array of LearnDash post types.
 */
function dasher_get_learndash_post_types() {
    return array(
        'sfwd-courses',
        'sfwd-lessons',
        'sfwd-topic',
        'sfwd-quiz',
        'sfwd-assignment',
        'sfwd-certificates',
        'groups',
    );
}

/**
 * Get human-readable name for a LearnDash post type.
 *
 * @since 1.0.0
 * @param string $post_type The post type.
 * @return string The human-readable name.
 */
function dasher_get_learndash_post_type_name($post_type) {
    $names = array(
        'sfwd-courses' => __('Course', 'dasher'),
        'sfwd-lessons' => __('Lesson', 'dasher'),
        'sfwd-topic' => __('Topic', 'dasher'),
        'sfwd-quiz' => __('Quiz', 'dasher'),
        'sfwd-assignment' => __('Assignment', 'dasher'),
        'sfwd-certificates' => __('Certificate', 'dasher'),
        'groups' => __('Group', 'dasher'),
    );
    
    return isset($names[$post_type]) ? $names[$post_type] : $post_type;
}

/**
 * Get a user's completed courses, lessons, or topics.
 *
 * @since 1.0.0
 * @param int    $user_id   The user ID.
 * @param string $post_type The post type ('sfwd-courses', 'sfwd-lessons', 'sfwd-topic').
 * @return array Array of completed post IDs.
 */
function dasher_get_user_completed_items($user_id, $post_type) {
    if (!dasher_is_learndash_active()) {
        return array();
    }
    
    switch ($post_type) {
        case 'sfwd-courses':
            return learndash_user_get_completed_courses($user_id);
        
        case 'sfwd-lessons':
            $completed_lessons = array();
            $user_courses = learndash_user_get_enrolled_courses($user_id);
            
            foreach ($user_courses as $course_id) {
                $course_progress = learndash_user_get_course_progress($user_id, $course_id);
                
                if (isset($course_progress['lessons']) && is_array($course_progress['lessons'])) {
                    foreach ($course_progress['lessons'] as $lesson_id => $lesson_status) {
                        if ($lesson_status === true || $lesson_status == 1) {
                            $completed_lessons[] = $lesson_id;
                        }
                    }
                }
            }
            
            return array_unique($completed_lessons);
        
        case 'sfwd-topic':
            $completed_topics = array();
            $user_courses = learndash_user_get_enrolled_courses($user_id);
            
            foreach ($user_courses as $course_id) {
                $course_progress = learndash_user_get_course_progress($user_id, $course_id);
                
                if (isset($course_progress['topics']) && is_array($course_progress['topics'])) {
                    foreach ($course_progress['topics'] as $lesson_id => $topics) {
                        if (is_array($topics)) {
                            foreach ($topics as $topic_id => $topic_status) {
                                if ($topic_status === true || $topic_status == 1) {
                                    $completed_topics[] = $topic_id;
                                }
                            }
                        }
                    }
                }
            }
            
            return array_unique($completed_topics);
        
        default:
            return array();
    }
}

/**
 * Check if the current user can access LCCP courses.
 *
 * @since 1.0.3
 * @return bool True if user can access LCCP courses, false otherwise.
 */
function dasher_user_can_access_lccp() {
    // Allow administrators full access
    if (current_user_can('administrator')) {
        return true;
    }
    
    // Allow LCCP-specific roles
    if (current_user_can('dasher_mentor') || 
        current_user_can('dasher_bigbird') || 
        current_user_can('dasher_pc')) {
        return true;
    }
    
    // Deny access to all other users
    return false;
}

/**
 * Filter course queries to hide LCCP courses from unauthorized users.
 *
 * @since 1.0.3
 * @param WP_Query $query The WP_Query instance.
 */
function dasher_restrict_lccp_course_access($query) {
    // Only filter frontend queries for courses
    if (is_admin() || !$query->is_main_query()) {
        return;
    }
    
    // Only filter course queries
    if (!isset($query->query_vars['post_type']) || $query->query_vars['post_type'] !== 'sfwd-courses') {
        return;
    }
    
    // If user can't access LCCP, exclude LCCP courses
    if (!dasher_user_can_access_lccp()) {
        $existing_tax_query = $query->get('tax_query') ?: array();
        
        $lccp_exclusion = array(
            'taxonomy' => 'ld_course_category',
            'field' => 'slug',
            'terms' => 'lccp',
            'operator' => 'NOT IN',
        );
        
        if (empty($existing_tax_query)) {
            $existing_tax_query = array($lccp_exclusion);
        } else {
            $existing_tax_query['relation'] = 'AND';
            $existing_tax_query[] = $lccp_exclusion;
        }
        
        $query->set('tax_query', $existing_tax_query);
    }
}
add_action('pre_get_posts', 'dasher_restrict_lccp_course_access');

/**
 * Block direct access to LCCP courses for unauthorized users.
 *
 * @since 1.0.3
 */
function dasher_block_lccp_course_access() {
    global $post;
    
    // Only check on single course pages
    if (!is_singular('sfwd-courses') || !$post) {
        return;
    }
    
    // Check if this course is in LCCP category
    $course_categories = wp_get_post_terms($post->ID, 'ld_course_category', array('fields' => 'slugs'));
    
    if (is_wp_error($course_categories)) {
        return;
    }
    
    // If course is in LCCP category and user doesn't have access
    if (in_array('lccp', $course_categories) && !dasher_user_can_access_lccp()) {
        // Redirect to login or show access denied message
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        } else {
            wp_die(
                '<h1>Access Denied</h1><p>You do not have permission to access LCCP courses. Please contact your administrator for access.</p>',
                'Access Denied',
                array('response' => 403)
            );
        }
    }
}
add_action('template_redirect', 'dasher_block_lccp_course_access'); 