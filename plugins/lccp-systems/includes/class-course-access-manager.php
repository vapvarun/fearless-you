<?php
/**
 * Course Access Manager
 * Manages automatic course access for different roles
 * 
 * - Admins: Full access to ALL courses
 * - Big Birds & Mentors: Full access to LCCP category courses
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Course_Access_Manager {
    
    private $lccp_category_slug = 'lccp';
    private $lccp_category_id = null;
    
    public function __construct() {
        // Only initialize if LearnDash is active
        if (!class_exists('SFWD_LMS')) {
            return;
        }
        
        // Hook into LearnDash access checks with lower priority to avoid conflicts
        add_filter('learndash_course_access', array($this, 'grant_course_access'), 5, 2);
        add_filter('learndash_lesson_access', array($this, 'grant_lesson_access'), 5, 2);
        add_filter('learndash_topic_access', array($this, 'grant_topic_access'), 5, 2);
        add_filter('learndash_quiz_access', array($this, 'grant_quiz_access'), 5, 2);
        
        // Hook into course enrollment checks with lower priority
        add_filter('learndash_is_course_enrolled', array($this, 'check_course_enrollment'), 5, 3);
        // Removed non-existent filter: learndash_is_user_in_course
        
        // Override course price display for eligible users
        add_filter('learndash_course_price', array($this, 'override_course_price'), 10, 2);
        
        // Add capabilities to roles
        add_action('init', array($this, 'add_course_capabilities'), 99);
        
        // Auto-enroll users when they visit courses they should have access to
        add_action('template_redirect', array($this, 'auto_enroll_eligible_users'));
        
        // Admin notices
        add_action('admin_notices', array($this, 'show_access_notice'));
        
        // Create LCCP course category if it doesn't exist
        add_action('init', array($this, 'ensure_lccp_category'), 5);
    }
    
    /**
     * Ensure LCCP category exists
     */
    public function ensure_lccp_category() {
        if (!taxonomy_exists('ld_course_category')) {
            return;
        }
        
        $category = term_exists($this->lccp_category_slug, 'ld_course_category');
        
        if (!$category) {
            $result = wp_insert_term(
                'LCCP',
                'ld_course_category',
                array(
                    'slug' => $this->lccp_category_slug,
                    'description' => 'Life Coach Certification Program Courses'
                )
            );
            
            if (!is_wp_error($result)) {
                $this->lccp_category_id = $result['term_id'];
            }
        } else {
            $this->lccp_category_id = $category['term_id'];
        }
    }
    
    /**
     * Check if user should have automatic access
     */
    private function user_has_automatic_access($user_id, $course_id = null) {
        if (!$user_id) {
            return false;
        }
        
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return false;
        }
        
        // Check if user is Rhonda
        if ($user->user_email === 'rhonda@fearlessliving.org') {
            return true;
        }
        
        // Check if user is admin
        if (user_can($user_id, 'manage_options') || in_array('administrator', $user->roles)) {
            return true;
        }
        
        // For course-specific checks
        if ($course_id) {
            // Check if course is in LCCP category
            if ($this->is_lccp_course($course_id)) {
                // Big Birds and Mentors get access to LCCP courses
                if (in_array('lccp_big_bird', $user->roles) || in_array('lccp_mentor', $user->roles)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check if course is in LCCP category
     */
    private function is_lccp_course($course_id) {
        if (!$course_id) {
            return false;
        }
        
        // Check if course has LCCP category
        $categories = wp_get_post_terms($course_id, 'ld_course_category', array('fields' => 'slugs'));
        
        if (is_array($categories) && in_array($this->lccp_category_slug, $categories)) {
            return true;
        }
        
        // Also check if LCCP is in the title or content
        $course = get_post($course_id);
        if ($course) {
            if (stripos($course->post_title, 'LCCP') !== false || 
                stripos($course->post_title, 'Life Coach Certification') !== false ||
                stripos($course->post_content, 'Life Coach Certification Program') !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Grant course access
     */
    public function grant_course_access($has_access, $course_id) {
        $user_id = get_current_user_id();
        
        if (!$user_id || !$course_id) {
            return $has_access;
        }
        
        // If user already has access, don't override
        if ($has_access) {
            return $has_access;
        }
        
        // Check if user should have automatic access
        if ($this->user_has_automatic_access($user_id, $course_id)) {
            // Auto-enroll the user if not already enrolled
            $this->enroll_user_in_course($user_id, $course_id);
            return true;
        }
        
        return $has_access;
    }
    
    /**
     * Grant lesson access
     */
    public function grant_lesson_access($has_access, $lesson_id) {
        $user_id = get_current_user_id();
        
        if (!$user_id || !$lesson_id) {
            return $has_access;
        }
        
        if ($has_access) {
            return $has_access;
        }
        
        // Get course ID for this lesson
        $course_id = learndash_get_course_id($lesson_id);
        
        if ($course_id && $this->user_has_automatic_access($user_id, $course_id)) {
            return true;
        }
        
        return $has_access;
    }
    
    /**
     * Grant topic access
     */
    public function grant_topic_access($has_access, $topic_id) {
        $user_id = get_current_user_id();
        
        if (!$user_id || !$topic_id) {
            return $has_access;
        }
        
        if ($has_access) {
            return $has_access;
        }
        
        // Get course ID for this topic
        $course_id = learndash_get_course_id($topic_id);
        
        if ($course_id && $this->user_has_automatic_access($user_id, $course_id)) {
            return true;
        }
        
        return $has_access;
    }
    
    /**
     * Grant quiz access
     */
    public function grant_quiz_access($has_access, $quiz_id) {
        $user_id = get_current_user_id();
        
        if (!$user_id || !$quiz_id) {
            return $has_access;
        }
        
        if ($has_access) {
            return $has_access;
        }
        
        // Get course ID for this quiz
        $course_id = learndash_get_course_id($quiz_id);
        
        if ($course_id && $this->user_has_automatic_access($user_id, $course_id)) {
            return true;
        }
        
        return $has_access;
    }
    
    /**
     * Check course enrollment
     */
    public function check_course_enrollment($enrolled, $user_id, $course_id) {
        if ($enrolled) {
            return $enrolled;
        }
        
        if ($this->user_has_automatic_access($user_id, $course_id)) {
            return true;
        }
        
        return $enrolled;
    }
    
    /**
     * Check if user is in course
     */
    public function check_user_in_course($in_course, $user_id, $course_id) {
        if ($in_course) {
            return $in_course;
        }
        
        if ($this->user_has_automatic_access($user_id, $course_id)) {
            return true;
        }
        
        return $in_course;
    }
    
    /**
     * Override course price display
     */
    public function override_course_price($price, $course_id) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return $price;
        }
        
        if ($this->user_has_automatic_access($user_id, $course_id)) {
            return __('Free Access', 'lccp-systems');
        }
        
        return $price;
    }
    
    /**
     * Add course capabilities to roles
     */
    public function add_course_capabilities() {
        // Add capabilities to administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('learndash_course_access_all', true);
            $admin_role->add_cap('learndash_lesson_access_all', true);
            $admin_role->add_cap('learndash_topic_access_all', true);
            $admin_role->add_cap('learndash_quiz_access_all', true);
        }
        
        // Add capabilities to mentor role
        $mentor_role = get_role('lccp_mentor');
        if ($mentor_role) {
            $mentor_role->add_cap('learndash_course_access_lccp', true);
            $mentor_role->add_cap('learndash_lesson_access_lccp', true);
            $mentor_role->add_cap('learndash_topic_access_lccp', true);
            $mentor_role->add_cap('learndash_quiz_access_lccp', true);
        }
        
        // Add capabilities to bigbird role
        $bigbird_role = get_role('lccp_big_bird');
        if ($bigbird_role) {
            $bigbird_role->add_cap('learndash_course_access_lccp', true);
            $bigbird_role->add_cap('learndash_lesson_access_lccp', true);
            $bigbird_role->add_cap('learndash_topic_access_lccp', true);
            $bigbird_role->add_cap('learndash_quiz_access_lccp', true);
        }
    }
    
    /**
     * Enroll user in course
     */
    private function enroll_user_in_course($user_id, $course_id) {
        // Check if already enrolled using the correct LearnDash function
        if (ld_course_check_user_access($course_id, $user_id)) {
            return;
        }
        
        // Enroll user
        ld_update_course_access($user_id, $course_id, false);
        
        // Log the enrollment
        update_user_meta($user_id, 'lccp_auto_enrolled_' . $course_id, current_time('mysql'));
        
        // Trigger enrollment action
        do_action('lccp_auto_enrollment', $user_id, $course_id);
    }
    
    /**
     * Auto-enroll eligible users when they visit a course
     */
    public function auto_enroll_eligible_users() {
        if (!is_singular('sfwd-courses')) {
            return;
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return;
        }
        
        $course_id = get_the_ID();
        if (!$course_id) {
            return;
        }
        
        // Check if user should have automatic access
        if ($this->user_has_automatic_access($user_id, $course_id)) {
            $this->enroll_user_in_course($user_id, $course_id);
        }
    }
    
    /**
     * Show admin notice about access
     */
    public function show_access_notice() {
        if (!is_singular('sfwd-courses')) {
            return;
        }
        
        $user = wp_get_current_user();
        if (!$user) {
            return;
        }
        
        $message = null;
        
        if (in_array('administrator', $user->roles)) {
            $message = __('As an administrator, you have automatic access to all courses.', 'lccp-systems');
        } elseif (in_array('lccp_mentor', $user->roles) || in_array('lccp_big_bird', $user->roles)) {
            $course_id = get_the_ID();
            if ($this->is_lccp_course($course_id)) {
                $message = __('As a Mentor/Big Bird, you have automatic access to all LCCP courses.', 'lccp-systems');
            }
        }
        
        if ($message) {
            ?>
            <div class="notice notice-info is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
            <?php
        }
    }
    
    /**
     * Grant all courses access to specific user (utility function)
     */
    public function grant_all_courses_to_user($user_id) {
        $courses = learndash_get_courses();
        
        foreach ($courses as $course) {
            $this->enroll_user_in_course($user_id, $course->ID);
        }
        
        return count($courses);
    }
    
    /**
     * Grant LCCP courses to user (utility function)
     */
    public function grant_lccp_courses_to_user($user_id) {
        $courses = learndash_get_courses();
        $enrolled_count = 0;
        
        foreach ($courses as $course) {
            if ($this->is_lccp_course($course->ID)) {
                $this->enroll_user_in_course($user_id, $course->ID);
                $enrolled_count++;
            }
        }
        
        return $enrolled_count;
    }
}

// Initialize the course access manager
new LCCP_Course_Access_Manager();