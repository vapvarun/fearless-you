<?php
/**
 * Fearless You Systems Role Manager
 * Manages Fearless You membership roles and permissions
 */

if (!defined('ABSPATH')) {
    exit;
}

class FYS_Role_Manager {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Role setup actions
        add_action('init', array($this, 'check_roles'));
    }

    public function setup_roles() {
        // Define Fearless You membership roles
        $roles = array(
            'fearless_you_member' => array(
                'display_name' => 'Fearless You Member',
                'capabilities' => array(
                    'read' => true,
                    'access_fearless_you_content' => true,
                    'view_membership_dashboard' => true,
                    'participate_in_community' => true,
                    'access_monthly_trainings' => true,
                    'download_resources' => true
                )
            ),
            'fearless_faculty' => array(
                'display_name' => 'Fearless Faculty',
                'capabilities' => array(
                    'read' => true,
                    'access_fearless_you_content' => true,
                    'teach_courses' => true,
                    'create_content' => true,
                    'moderate_discussions' => true,
                    'view_faculty_dashboard' => true,
                    'access_faculty_resources' => true
                )
            ),
            'fearless_ambassador' => array(
                'display_name' => 'Fearless Ambassador',
                'capabilities' => array(
                    'read' => true,
                    'access_fearless_you_content' => true,
                    'promote_fearless_living' => true,
                    'access_ambassador_resources' => true,
                    'view_ambassador_dashboard' => true,
                    'participate_in_community' => true,
                    'refer_members' => true
                )
            )
        );

        foreach ($roles as $role_slug => $role_data) {
            // Remove existing role if it exists
            remove_role($role_slug);

            // Add the role with capabilities
            add_role($role_slug, $role_data['display_name'], $role_data['capabilities']);
        }

        // Store role hierarchy and settings
        $this->setup_role_hierarchy();
    }

    private function setup_role_hierarchy() {
        $hierarchy = array(
            'fearless_faculty' => array(
                'level' => 80,
                'dashboard' => home_url('/faculty-dashboard/'),
                'can_teach' => true,
                'can_create_content' => true
            ),
            'fearless_you_member' => array(
                'level' => 40,
                'dashboard' => home_url('/member-dashboard/'),
                'access_courses' => true,
                'access_community' => true
            ),
            'fearless_ambassador' => array(
                'level' => 30,
                'dashboard' => home_url('/ambassador-dashboard/'),
                'can_refer' => true,
                'access_promotional_materials' => true
            )
        );

        update_option('fys_role_hierarchy', $hierarchy);
    }

    public function check_roles() {
        // Check if roles exist and create them if they don't
        $roles = array('fearless_you_member', 'fearless_faculty', 'fearless_ambassador');

        foreach ($roles as $role_slug) {
            if (!get_role($role_slug)) {
                $this->setup_roles();
                break;
            }
        }
    }

    public function get_user_dashboard_url($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return home_url();
        }

        $hierarchy = get_option('fys_role_hierarchy', array());

        // Check user roles and return appropriate dashboard
        foreach ($user->roles as $role) {
            if (isset($hierarchy[$role]['dashboard'])) {
                return $hierarchy[$role]['dashboard'];
            }
        }

        return home_url('/my-account/');
    }

    public function user_has_membership($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $membership_roles = array('fearless_you_member', 'fearless_faculty', 'fearless_ambassador');
        return !empty(array_intersect($membership_roles, $user->roles));
    }
}