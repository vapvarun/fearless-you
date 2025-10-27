<?php
/**
 * LCCP Systems Module Manager
 * Handles module registration, loading, and feature toggles
 *
 * @package LCCP Systems
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base Module Class
 * All modules should extend this class
 */
abstract class LCCP_Module {
    
    protected $module_id;
    protected $module_name;
    protected $module_description;
    protected $module_version;
    protected $module_dependencies = array();
    protected $module_settings = array();
    protected $is_active = false;
    
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialize the module
     * Override this method in child classes
     */
    abstract protected function init();
    
    /**
     * Get module information
     */
    public function get_module_info() {
        return array(
            'id' => $this->module_id,
            'name' => $this->module_name,
            'description' => $this->module_description,
            'version' => $this->module_version,
            'dependencies' => $this->module_dependencies,
            'settings' => $this->module_settings,
            'is_active' => $this->is_active
        );
    }
    
    /**
     * Check if module dependencies are met
     */
    public function check_dependencies() {
        foreach ($this->module_dependencies as $dependency) {
            if (!$this->is_dependency_met($dependency)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Check if a specific dependency is met
     */
    protected function is_dependency_met($dependency) {
        switch ($dependency) {
            case 'learndash':
                return class_exists('SFWD_LMS');
            case 'buddypress':
                return class_exists('BuddyPress');
            case 'buddyboss':
                return class_exists('BuddyBoss_Platform');
            case 'wp_fusion':
                return class_exists('WP_Fusion');
            case 'woocommerce':
                return class_exists('WooCommerce');
            default:
                return true;
        }
    }
    
    /**
     * Activate the module
     */
    public function activate() {
        $this->is_active = true;
        $this->on_activate();
    }
    
    /**
     * Deactivate the module
     */
    public function deactivate() {
        $this->is_active = false;
        $this->on_deactivate();
    }
    
    /**
     * Called when module is activated
     * Override in child classes
     */
    protected function on_activate() {
        // Override in child classes
    }
    
    /**
     * Called when module is deactivated
     * Override in child classes
     */
    protected function on_deactivate() {
        // Override in child classes
    }
    
    /**
     * Get module settings
     */
    public function get_settings() {
        return get_option('lccp_module_' . $this->module_id . '_settings', $this->module_settings);
    }
    
    /**
     * Update module settings
     */
    public function update_settings($settings) {
        return update_option('lccp_module_' . $this->module_id . '_settings', $settings);
    }
    
    /**
     * Check if module is enabled
     */
    public function is_enabled() {
        $module_settings = get_option('lccp_modules_settings', array());
        return isset($module_settings[$this->module_id]) && $module_settings[$this->module_id]['enabled'];
    }
}

/**
 * Module Manager Class
 * Handles module registration, loading, and management
 */
class LCCP_Module_Manager {
    
    private static $instance = null;
    private $modules = array();
    private $registered_modules = array();
    private $module_settings = array();
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->module_settings = get_option('lccp_modules_settings', array());
        add_action('init', array($this, 'load_enabled_modules'), 5);
        // Run deeper, runtime-dependent self-tests later in init
        add_action('init', array($this, 'run_deferred_self_tests'), 99);
        add_action('admin_menu', array($this, 'add_module_management_page'), 20);
        add_action('wp_ajax_lccp_toggle_module', array($this, 'ajax_toggle_module'));
        add_action('wp_ajax_lccp_save_module_settings', array($this, 'ajax_save_module_settings'));
        add_action('wp_ajax_lccp_clear_module_error', array($this, 'ajax_clear_module_error'));
        add_action('admin_notices', array($this, 'display_module_errors'));
    }
    
    /**
     * Register a module
     */
    public function register_module($module_class, $module_info) {
        $this->registered_modules[$module_info['id']] = array(
            'class' => $module_class,
            'info' => $module_info
        );
    }
    
    /**
     * Load enabled modules
     */
    public function load_enabled_modules() {
        // Get all defined modules
        $modules = $this->get_modules();
        
        foreach ($modules as $module_id => $module_info) {
            if ($this->is_module_enabled($module_id)) {
                $loaded = $this->load_module_file($module_id);
                if ($loaded) {
                    // Run self-test immediately; auto-disable on failure
                    $test = $this->self_test_module($module_id);
                    if ($test !== true) {
                        $error_message = is_wp_error($test) ? $test->get_error_message() : 'Unknown self-test failure';
                        $this->handle_module_error($module_id, 'Self-test failed: ' . $error_message);
                    }
                }
            }
        }
    }
    
    /**
     * Load a module file with error protection
     */
    public function load_module_file($module_id) {
        $modules = $this->get_modules();
        
        if (!isset($modules[$module_id])) {
            return false;
        }
        
        // Map module IDs to actual file paths
        $module_files = array(
            'dashboards' => array('modules/class-dashboards.php', 'includes/class-enhanced-dashboards.php'),
            'hour_tracker' => 'includes/class-hour-tracker.php',
            'document_manager' => 'includes/document-manager.php',
            'course_access_manager' => 'includes/class-course-access-manager.php',
            'accessibility_manager' => 'includes/class-accessibility-manager.php',
            'ip_autologin' => 'includes/class-ip-autologin.php',
            'membership_roles' => 'includes/class-membership-roles.php', // Includes mentor functionality
            'performance' => 'includes/class-performance-optimizer.php',
            'learndash_integration' => 'includes/class-learndash-integration.php',
            'learndash_compatibility' => 'includes/class-learndash-compatibility.php',
            'learndash_widgets' => 'includes/class-learndash-widgets.php',
            'advanced_widgets' => 'includes/class-learndash-widgets.php',
            'roles' => 'includes/class-roles-manager.php',
            'checklist' => 'includes/class-checklist-manager.php',
            'messages' => 'includes/class-message-system.php',
            'settings_manager' => 'includes/class-settings-manager.php',
            'system_status' => 'includes/class-lccp-system-status.php',
            'events_integration' => 'modules/class-events-integration.php'
        );
        
        if (empty($module_files[$module_id])) {
            // Module has no file to load
            return true;
        }
        
        // Handle both single files and arrays of files
        $files_to_load = is_array($module_files[$module_id]) ? $module_files[$module_id] : array($module_files[$module_id]);
        
        // Set up error handler to catch fatal errors
        $previous_error_handler = set_error_handler(array($this, 'module_error_handler'));
        
        try {
            foreach ($files_to_load as $file) {
                $file_path = LCCP_SYSTEMS_PLUGIN_DIR . $file;
                
                if (!file_exists($file_path)) {
                    $this->add_module_error($module_id, 'Module file not found: ' . $file);
                    // Restore error handler before returning
                    if ($previous_error_handler) {
                        set_error_handler($previous_error_handler);
                    }
                    return false;
                }
                
                // Attempt to load the module file
                require_once $file_path;
            }
            
            // Mark module as loaded
            $this->modules[$module_id] = true;
            
            // Restore previous error handler
            if ($previous_error_handler) {
                set_error_handler($previous_error_handler);
            }
            
            return true;
            
        } catch (Exception $e) {
            // Module failed to load - disable it
            $this->handle_module_error($module_id, $e->getMessage());
        } catch (Error $e) {
            // PHP 7+ fatal errors
            $this->handle_module_error($module_id, $e->getMessage());
        } finally {
            // Always restore the previous error handler
            if ($previous_error_handler) {
                set_error_handler($previous_error_handler);
            }
        }
        
        return false;
    }
    
    /**
     * Load a specific module with error protection (legacy method for compatibility)
     */
    public function load_module($module_id) {
        if (!isset($this->registered_modules[$module_id])) {
            return false;
        }
        
        $module_data = $this->registered_modules[$module_id];
        $module_class = $module_data['class'];
        
        // Check dependencies
        if (!$this->check_module_dependencies($module_id)) {
            $this->add_module_error($module_id, 'Dependencies not met');
            return false;
        }
        
        // Set up error handler to catch fatal errors
        $previous_error_handler = set_error_handler(array($this, 'module_error_handler'));
        
        try {
            // Attempt to load the module
            if (class_exists($module_class)) {
                $this->modules[$module_id] = new $module_class();
                $this->modules[$module_id]->activate();
                
                // Restore previous error handler
                if ($previous_error_handler) {
                    set_error_handler($previous_error_handler);
                }
                
                return true;
            }
        } catch (Exception $e) {
            // Module failed to load - disable it
            $this->handle_module_error($module_id, $e->getMessage());
        } catch (Error $e) {
            // PHP 7+ fatal errors
            $this->handle_module_error($module_id, $e->getMessage());
        } finally {
            // Always restore the previous error handler
            if ($previous_error_handler) {
                set_error_handler($previous_error_handler);
            }
        }
        
        return false;
    }
    
    /**
     * Handle module loading errors
     */
    private function handle_module_error($module_id, $error_message) {
        // Disable the module
        $this->disable_module($module_id);
        
        // Log the error
        error_log(sprintf('LCCP Module Manager: Module "%s" auto-disabled due to error: %s', $module_id, $error_message));
        
        // Store error for admin notice
        $this->add_module_error($module_id, $error_message);
    }
    
    /**
     * Custom error handler for module loading
     */
    public function module_error_handler($severity, $message, $file, $line) {
        // Convert errors to exceptions
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    
    /**
     * Add module error for admin notice
     */
    private function add_module_error($module_id, $error_message) {
        $module_errors = get_transient('lccp_module_errors');
        if (!is_array($module_errors)) {
            $module_errors = array();
        }
        
        $module_info = $this->get_modules()[$module_id] ?? array('name' => $module_id);
        $module_errors[$module_id] = array(
            'module_name' => $module_info['name'],
            'error' => $error_message,
            'time' => current_time('mysql')
        );
        
        set_transient('lccp_module_errors', $module_errors, HOUR_IN_SECONDS);
    }
    
    /**
     * Check if module dependencies are met
     */
    private function check_module_dependencies($module_id) {
        $module_data = $this->registered_modules[$module_id];
        $dependencies = $module_data['info']['dependencies'] ?? array();
        
        foreach ($dependencies as $dependency) {
            if (!$this->is_dependency_met($dependency)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check if a dependency is met
     */
    private function is_dependency_met($dependency) {
        switch ($dependency) {
            case 'learndash':
                return class_exists('SFWD_LMS');
            case 'buddypress':
                return class_exists('BuddyPress');
            case 'buddyboss':
                return class_exists('BuddyBoss_Platform');
            case 'wp_fusion':
                return class_exists('WP_Fusion');
            case 'woocommerce':
                return class_exists('WooCommerce');
            default:
                return true;
        }
    }
    
    /**
     * Check if module is enabled
     */
    public function is_module_enabled($module_id) {
        if (!isset($this->module_settings[$module_id])) {
            return false;
        }

        $setting = $this->module_settings[$module_id];

        // Handle both array format ['enabled' => true] and string format 'on'
        if (is_array($setting)) {
            return isset($setting['enabled']) && $setting['enabled'];
        } else {
            // Legacy string format: 'on' or true
            return $setting === 'on' || $setting === true || $setting === 1;
        }
    }
    
    /**
     * Enable a module with error protection
     */
    public function enable_module($module_id) {
        // Ensure we have an array format, not legacy string format
        if (!isset($this->module_settings[$module_id]) || !is_array($this->module_settings[$module_id])) {
            $this->module_settings[$module_id] = array();
        }
        $this->module_settings[$module_id]['enabled'] = true;
        update_option('lccp_modules_settings', $this->module_settings);
        
        // Load the module file if not already loaded (with error protection)
        if (!isset($this->modules[$module_id])) {
            $result = $this->load_module_file($module_id);
            
            // If module failed to load, it will be auto-disabled
            if ($result !== true) {
                return $result;
            }
            // Run a self-test; on failure, auto-disable and surface error
            $test = $this->self_test_module($module_id);
            if ($test !== true) {
                $error_message = is_wp_error($test) ? $test->get_error_message() : 'Unknown self-test failure';
                $this->handle_module_error($module_id, 'Self-test failed: ' . $error_message);
                return false;
            }
            return true;
        }
        
        return true;
    }

    /**
     * Run a lightweight self-test for a module
     * Returns true on success or WP_Error on failure
     */
    public function self_test_module($module_id) {
        // Map module IDs to expected symbols to verify basic functionality
        $expectations = array(
            'dashboards' => array('class' => 'LCCP_Enhanced_Dashboards'),
            'hour_tracker' => array('class' => 'LCCP_Hour_Tracker'),
            'document_manager' => array('class' => 'Dasher_Document_Manager'),
            'course_access_manager' => array('class' => 'LCCP_Course_Access_Manager'),
            'accessibility_manager' => array('class' => 'LCCP_Accessibility_Manager'),
            'ip_autologin' => array('class' => 'LCCP_IP_AutoLogin'),
            'membership_roles' => array('class' => 'LCCP_Membership_Roles'),
            'performance' => array('class' => 'LCCP_Performance_Optimizer'),
            'learndash_integration' => array('class' => 'LCCP_LearnDash_Integration'),
            'learndash_compatibility' => array('class' => 'LCCP_LearnDash_Compatibility'),
            'learndash_widgets' => array('class' => 'LCCP_LearnDash_Widgets'),
            'advanced_widgets' => array('class' => 'LCCP_LearnDash_Widgets'),
            'roles' => array('class' => 'LCCP_Roles_Manager'),
            'checklist' => array('class' => 'LCCP_Checklist_Manager'),
            'messages' => array('class' => 'Dasher_Message_System'),
            'settings_manager' => array('class' => 'LCCP_Settings_Manager'),
            'system_status' => array('class' => 'LCCP_System_Status'),
            'events_integration' => array('class' => 'LCCP_Events_Integration'),
        );

        // If we don't have an expectation, consider the module passed
        if (!isset($expectations[$module_id])) {
            return true;
        }

        $expect = $expectations[$module_id];

        if (isset($expect['class'])) {
            if (!class_exists($expect['class'])) {
                return new WP_Error('self_test_failed', sprintf('Expected class missing: %s', $expect['class']));
            }
        }

        if (isset($expect['function'])) {
            if (!function_exists($expect['function'])) {
                return new WP_Error('self_test_failed', sprintf('Expected function missing: %s', $expect['function']));
            }
        }

        // Optional: allow modules to expose a deeper test via filter
        $result = apply_filters('lccp_module_self_test_' . $module_id, true);
        if ($result !== true) {
            $message = is_wp_error($result) ? $result->get_error_message() : (is_string($result) ? $result : 'Module-specific self-test failed');
            return new WP_Error('self_test_failed', $message);
        }

        return true;
    }

    /**
     * Run deeper self-tests that require WordPress runtime state (after most init hooks registered)
     */
    public function run_deferred_self_tests() {
        // Only run in admin or when DOING_CRON to avoid frontend performance hit
        if (!is_admin() && !defined('DOING_CRON')) {
            return;
        }

        foreach ($this->get_modules() as $module_id => $info) {
            if (!$this->is_module_enabled($module_id)) {
                continue;
            }
            // Skip if already marked as loaded failure earlier in this request
            if (!isset($this->modules[$module_id])) {
                continue;
            }

            $error = null;

            switch ($module_id) {
                case 'hour_tracker':
                    global $wpdb;
                    $table = $wpdb->prefix . 'lccp_hour_tracker';
                    $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
                    if ($found !== $table) {
                        $error = 'Required database table missing: ' . $table;
                    }
                    break;

                case 'document_manager':
                    if (!post_type_exists('dasher_document')) {
                        $error = 'Post type "dasher_document" not registered';
                    } else if (!shortcode_exists('dasher_document_library')) {
                        $error = 'Shortcode [dasher_document_library] not available';
                    }
                    break;

                case 'events_integration':
                    if (!class_exists('Tribe__Events__Main')) {
                        $error = 'The Events Calendar dependency is missing';
                    } else {
                        // If shortcodes are enabled, ensure they exist
                        if (get_option('lccp_events_shortcodes_enabled', 'on') === 'on' && !shortcode_exists('lccp_events')) {
                            $error = 'Events shortcode [lccp_events] not registered';
                        }
                    }
                    break;

                case 'learndash_integration':
                    if (!class_exists('SFWD_LMS')) {
                        $error = 'LearnDash dependency is missing';
                    }
                    break;

                case 'roles':
                case 'membership_roles':
                    $roles_ok = function_exists('get_role') && get_role('lccp_mentor') && get_role('lccp_big_bird');
                    if (!$roles_ok) {
                        $error = 'Required roles not registered (lccp_mentor, lccp_big_bird)';
                    }
                    break;

                case 'messages':
                    if (!post_type_exists('dasher_message')) {
                        $error = 'Post type "dasher_message" not registered';
                    }
                    break;

                case 'system_status':
                    if (!method_exists('LCCP_System_Status', 'get_status')) {
                        $error = 'System status API unavailable';
                    } else {
                        $s = LCCP_System_Status::get_status();
                        if (!is_array($s) || empty($s['overall'])) {
                            $error = 'System status returned invalid data';
                        }
                    }
                    break;
            }

            if ($error) {
                // Auto-disable module and record error; takes effect next request if code already ran
                $this->handle_module_error($module_id, $error);
            }
        }
    }
    
    /**
     * Disable a module
     */
    public function disable_module($module_id) {
        // Check for dependent modules
        $dependents = $this->get_dependent_modules($module_id);
        if (!empty($dependents)) {
            return new WP_Error('has_dependents', 'Cannot disable module with active dependencies');
        }

        if (isset($this->module_settings[$module_id])) {
            // Handle both array and string formats
            if (is_array($this->module_settings[$module_id])) {
                $this->module_settings[$module_id]['enabled'] = false;
            } else {
                // Convert legacy string format to array format
                $this->module_settings[$module_id] = array('enabled' => false);
            }
            update_option('lccp_modules_settings', $this->module_settings);
        }
        
        // Deactivate the module if loaded
        if (isset($this->modules[$module_id])) {
            // Since modules are now just file includes (true/false), not objects
            // we just need to unset them from the loaded modules array
            unset($this->modules[$module_id]);
        }
        
        return true;
    }
    
    /**
     * Get modules that depend on a given module
     */
    private function get_dependent_modules($module_id) {
        $dependents = array();
        foreach ($this->get_modules() as $id => $module) {
            if ($id !== $module_id && $this->is_module_enabled($id)) {
                if (in_array($module_id, $module['dependencies'])) {
                    $dependents[] = $id;
                }
            }
        }
        return $dependents;
    }
    
    /**
     * Get all registered modules
     */
    public function get_registered_modules() {
        return $this->registered_modules;
    }
    
    /**
     * Get loaded modules
     */
    public function get_loaded_modules() {
        return $this->modules;
    }
    
    /**
     * Get module by ID
     */
    public function get_module($module_id) {
        return isset($this->modules[$module_id]) ? $this->modules[$module_id] : null;
    }
    
    /**
     * Get all modules with their info formatted for admin display
     */
    public function get_modules() {
        // Define all available modules
        $modules = array(
            'dashboards' => array(
                'name' => __('Enhanced Dashboards', 'lccp-systems'),
                'description' => __('Advanced student dashboards with customizable widgets, role-specific views, and progress tracking for the certification program.', 'lccp-systems'),
                'category' => 'core',
                'dependencies' => array(),
                'has_admin_page' => true,
                'requires_plugin' => false,
                'security_warning' => false
            ),
            'hour_tracker' => array(
                'name' => __('Hour Tracker', 'lccp-systems'),
                'description' => __('Track and manage coaching hours required for certification completion.', 'lccp-systems'),
                'category' => 'certification',
                'dependencies' => array(),
                'has_admin_page' => true,
                'requires_plugin' => false,
                'security_warning' => false
            ),
            'document_manager' => array(
                'name' => __('Document Manager', 'lccp-systems'),
                'description' => __('Manage and organize program documents, resources, and downloadable materials.', 'lccp-systems'),
                'category' => 'core',
                'dependencies' => array(),
                'has_admin_page' => true,
                'requires_plugin' => false,
                'security_warning' => false
            ),
            'course_access_manager' => array(
                'name' => __('Course Access Manager', 'lccp-systems'),
                'description' => __('Control and manage student access to courses based on enrollment and progress.', 'lccp-systems'),
                'category' => 'certification',
                'dependencies' => array(),
                'has_admin_page' => true,
                'requires_plugin' => 'LearnDash',
                'security_warning' => false
            ),
            'accessibility_manager' => array(
                'name' => __('Accessibility Features', 'lccp-systems'),
                'description' => __('Enhanced accessibility options including screen reader support and keyboard navigation.', 'lccp-systems'),
                'category' => 'system',
                'dependencies' => array(),
                'has_admin_page' => true,
                'requires_plugin' => false,
                'security_warning' => false
            ),
            'ip_autologin' => array(
                'name' => __('IP Auto-Login', 'lccp-systems'),
                'description' => __('Automatic login for trusted IP addresses (use with caution).', 'lccp-systems'),
                'category' => 'system',
                'dependencies' => array(),
                'has_admin_page' => true,
                'requires_plugin' => false,
                'security_warning' => true
            ),
            'membership_roles' => array(
                'name' => __('Membership & Mentor System', 'lccp-systems'),
                'description' => __('Extended role management for membership levels, mentor assignments, student-mentor relationships, and special access permissions.', 'lccp-systems'),
                'category' => 'system',
                'dependencies' => array('roles'),
                'has_admin_page' => true,
                'requires_plugin' => false,
                'security_warning' => true
            ),
            'performance' => array(
                'name' => __('Performance Optimizer', 'lccp-systems'),
                'description' => __('Optimize site performance with caching and database optimizations.', 'lccp-systems'),
                'category' => 'system',
                'dependencies' => array(),
                'has_admin_page' => true,
                'requires_plugin' => false,
                'security_warning' => true
            ),
            'learndash_integration' => array(
                'name' => __('LearnDash Integration', 'lccp-systems'),
                'description' => __('Enhanced integration with LearnDash for course management.', 'lccp-systems'),
                'category' => 'integrations',
                'dependencies' => array(),
                'has_admin_page' => false,
                'requires_plugin' => 'LearnDash',
                'security_warning' => false
            ),
            'learndash_compatibility' => array(
                'name' => __('LearnDash Compatibility', 'lccp-systems'),
                'description' => __('Compatibility layer ensuring smooth operation with various LearnDash versions.', 'lccp-systems'),
                'category' => 'integrations',
                'dependencies' => array('learndash_integration'),
                'has_admin_page' => false,
                'requires_plugin' => 'LearnDash',
                'security_warning' => false
            ),
            'learndash_widgets' => array(
                'name' => __('LearnDash Widgets', 'lccp-systems'),
                'description' => __('Custom widgets for displaying LearnDash course information and progress.', 'lccp-systems'),
                'category' => 'integrations',
                'dependencies' => array('learndash_integration'),
                'has_admin_page' => false,
                'requires_plugin' => 'LearnDash',
                'security_warning' => false
            ),
            'roles' => array(
                'name' => __('Roles Manager', 'lccp-systems'),
                'description' => __('Manage custom roles for mentors, big birds, and program coordinators.', 'lccp-systems'),
                'category' => 'system',
                'dependencies' => array(),
                'has_admin_page' => true,
                'requires_plugin' => false,
                'security_warning' => true
            ),
            'checklist' => array(
                'name' => __('Checklist Manager', 'lccp-systems'),
                'description' => __('Create and manage interactive checklists for certification requirements and course completion tracking.', 'lccp-systems'),
                'category' => 'core',
                'dependencies' => array(),
                'has_admin_page' => true,
                'requires_plugin' => false,
                'security_warning' => false
            ),
            'messages' => array(
                'name' => __('Message System', 'lccp-systems'),
                'description' => __('Internal messaging system for students and mentors.', 'lccp-systems'),
                'category' => 'communication',
                'dependencies' => array(),
                'has_admin_page' => true,
                'requires_plugin' => false,
                'security_warning' => false
            ),
            'settings_manager' => array(
                'name' => __('Settings Manager', 'lccp-systems'),
                'description' => __('Centralized settings management for all LCCP Systems modules.', 'lccp-systems'),
                'category' => 'system',
                'dependencies' => array(),
                'has_admin_page' => true,
                'requires_plugin' => false,
                'security_warning' => false
            ),
            'system_status' => array(
                'name' => __('System Status Monitor', 'lccp-systems'),
                'description' => __('Monitor system health, dependencies, and performance metrics.', 'lccp-systems'),
                'category' => 'system',
                'dependencies' => array(),
                'has_admin_page' => true,
                'requires_plugin' => false,
                'security_warning' => false
            ),
            'events_integration' => array(
                'name' => __('Events Integration', 'lccp-systems'),
                'description' => __('Consolidates Events Virtual, Events Block, and Events Shortcode functionality into a single module.', 'lccp-systems'),
                'category' => 'integrations',
                'dependencies' => array(),
                'has_admin_page' => true,
                'requires_plugin' => 'The Events Calendar',
                'security_warning' => false
            )
        );
        
        return $modules;
    }
    
    /**
     * Get module categories
     */
    public function get_categories() {
        return array(
            'core' => __('Core Features', 'lccp-systems'),
            'certification' => __('Certification', 'lccp-systems'),
            'system' => __('System & Security', 'lccp-systems'),
            'integrations' => __('Integrations', 'lccp-systems'),
            'communication' => __('Communication', 'lccp-systems')
        );
    }
    
    /**
     * Get modules by category
     */
    public function get_modules_by_category($category) {
        $modules = $this->get_modules();
        $filtered = array();
        
        foreach ($modules as $id => $module) {
            if (isset($module['category']) && $module['category'] === $category) {
                $filtered[$id] = $module;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Check if user can manage a specific module
     */
    public function user_can_manage_module($module_id) {
        // For now, only admins can manage modules
        return current_user_can('manage_options');
    }
    
    /**
     * Add module management page
     */
    public function add_module_management_page() {
        add_submenu_page(
            'lccp-systems',
            __('Module Manager', 'lccp-systems'),
            __('Module Manager', 'lccp-systems'),
            'manage_options',
            'lccp-module-manager',
            array($this, 'render_module_management_page')
        );
    }
    
    /**
     * Render module management page
     */
    public function render_module_management_page() {
        $modules = $this->get_modules();
        // Load any stored module errors to reflect "problem" state
        $module_errors = get_transient('lccp_module_errors');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('LCCP Systems - Module Manager', 'lccp-systems'); ?></h1>
            
            <div class="lccp-module-manager">
                <div class="lccp-modules-grid">
                    <?php foreach ($modules as $module_id => $module_data): ?>
                        <?php
                        $is_enabled = $this->is_module_enabled($module_id);
                        $is_loaded = isset($this->modules[$module_id]);
                        $dependencies_met = $this->check_module_dependencies($module_id);
                        // get_modules() returns flat array, not nested ['info']
                        $module_info = $module_data;
                        // Ensure version exists (not in get_modules() data)
                        if (!isset($module_info['version'])) {
                            $module_info['version'] = '1.0.0';
                        }
                        ?>
                        <div class="lccp-module-card <?php echo $is_enabled ? 'enabled' : 'disabled'; ?> <?php echo !$dependencies_met ? 'dependencies-missing' : ''; ?>">
                            <div class="lccp-module-header">
                                <h3><?php echo esc_html($module_info['name']); ?></h3>
                                <div class="lccp-module-toggle">
                                    <label class="lccp-toggle-switch">
                                        <input type="checkbox" 
                                               class="lccp-module-toggle-input" 
                                               data-module-id="<?php echo esc_attr($module_id); ?>"
                                               <?php checked($is_enabled); ?>
                                               <?php disabled(!$dependencies_met); ?>>
                                        <span class="lccp-toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="lccp-module-content">
                                <p class="lccp-module-description">
                                    <?php echo esc_html($module_info['description']); ?>
                                </p>
                                
                                <div class="lccp-module-meta">
                                    <div class="lccp-module-version">
                                        <strong><?php esc_html_e('Version:', 'lccp-systems'); ?></strong>
                                        <?php echo esc_html($module_info['version']); ?>
                                    </div>
                                    
                                    <?php if (!empty($module_info['dependencies'])): ?>
                                        <div class="lccp-module-dependencies">
                                            <strong><?php esc_html_e('Dependencies:', 'lccp-systems'); ?></strong>
                                            <?php foreach ($module_info['dependencies'] as $dependency): ?>
                                                <span class="lccp-dependency <?php echo $this->is_dependency_met($dependency) ? 'met' : 'missing'; ?>">
                                                    <?php echo esc_html(ucfirst($dependency)); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php $has_error = is_array($module_errors) && isset($module_errors[$module_id]); ?>
                                    <div class="lccp-module-status">
                                        <strong><?php esc_html_e('Status:', 'lccp-systems'); ?></strong>
                                        <?php if ($is_loaded): ?>
                                            <span class="lccp-status-active"><?php esc_html_e('Active', 'lccp-systems'); ?></span>
                                        <?php elseif ($has_error): ?>
                                            <span class="lccp-status-problem"><?php esc_html_e('Problem — Auto-disabled', 'lccp-systems'); ?></span>
                                        <?php else: ?>
                                            <span class="lccp-status-disabled"><?php esc_html_e('Inactive', 'lccp-systems'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (!$dependencies_met): ?>
                                    <div class="lccp-module-warning">
                                        <span class="dashicons dashicons-warning"></span>
                                        <?php esc_html_e('Some dependencies are missing. This module cannot be enabled.', 'lccp-systems'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <style>
        .lccp-modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .lccp-module-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .lccp-module-card.enabled {
            border-color: #46b450;
            box-shadow: 0 2px 8px rgba(70, 180, 80, 0.2);
        }
        
        .lccp-module-card.dependencies-missing {
            border-color: #dc3232;
            background-color: #fef7f7;
        }
        
        .lccp-module-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .lccp-module-header h3 {
            margin: 0;
            color: #23282d;
        }
        
        .lccp-toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .lccp-module-toggle-input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .lccp-toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        
        .lccp-toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        .lccp-module-toggle-input:checked + .lccp-toggle-slider {
            background-color: #46b450;
        }
        
        .lccp-module-toggle-input:checked + .lccp-toggle-slider:before {
            transform: translateX(26px);
        }
        
        .lccp-module-description {
            color: #666;
            margin-bottom: 15px;
        }
        
        .lccp-module-meta {
            font-size: 13px;
            color: #666;
        }
        
        .lccp-module-meta > div {
            margin-bottom: 8px;
        }
        
        .lccp-dependency {
            display: inline-block;
            padding: 2px 8px;
            margin: 2px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .lccp-dependency.met {
            background-color: #d4edda;
            color: #155724;
        }
        
        .lccp-dependency.missing {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .lccp-status-active {
            color: #46b450;
            font-weight: 600;
        }
        
        .lccp-status-disabled {
            color: #dc3232;
            font-weight: 600;
        }
        
        .lccp-status-problem {
            color: #ffb900;
            font-weight: 600;
        }
        
        .lccp-module-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 10px;
            border-radius: 4px;
            margin-top: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('.lccp-module-toggle-input').on('change', function() {
                var moduleId = $(this).data('module-id');
                var isEnabled = $(this).is(':checked');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'lccp_toggle_module',
                        module_id: moduleId,
                        enabled: isEnabled ? 1 : 0,
                        nonce: '<?php echo wp_create_nonce('lccp_module_toggle'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for toggling modules
     */
    public function ajax_toggle_module() {
        check_ajax_referer('lccp_module_toggle', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $module_id = sanitize_text_field($_POST['module_id']);
        $enabled = filter_var($_POST['enabled'], FILTER_VALIDATE_BOOLEAN);
        
        if ($enabled) {
            $result = $this->enable_module($module_id);
            if ($result === true) {
                wp_send_json_success(array(
                    'message' => 'Module enabled successfully',
                    'reload' => false
                ));
            } else {
                wp_send_json_error(array(
                    'message' => 'Failed to enable module. Check dependencies or error log.'
                ));
            }
        } else {
            $result = $this->disable_module($module_id);
            if ($result === true || !is_wp_error($result)) {
                wp_send_json_success(array(
                    'message' => 'Module disabled successfully',
                    'reload' => false
                ));
            } else {
                wp_send_json_error(array(
                    'message' => is_wp_error($result) ? $result->get_error_message() : 'Failed to disable module'
                ));
            }
        }
    }
    
    /**
     * AJAX handler for saving module settings
     */
    public function ajax_save_module_settings() {
        check_ajax_referer('lccp_module_settings', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $module_id = sanitize_text_field($_POST['module_id']);
        $settings = $_POST['settings'];
        
        if (isset($this->modules[$module_id])) {
            $this->modules[$module_id]->update_settings($settings);
            wp_send_json_success('Settings saved');
        } else {
            wp_send_json_error('Module not found');
        }
    }
    
    /**
     * Display admin notices for module errors
     */
    public function display_module_errors() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $module_errors = get_transient('lccp_module_errors');
        if (empty($module_errors) || !is_array($module_errors)) {
            return;
        }
        
        foreach ($module_errors as $module_id => $error_data) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <strong><?php _e('LCCP Module Error:', 'lccp-systems'); ?></strong>
                    <?php 
                    printf(
                        __('The module "%s" was automatically disabled due to an error: %s', 'lccp-systems'),
                        esc_html($error_data['module_name']),
                        esc_html($error_data['error'])
                    );
                    ?>
                </p>
                <p>
                    <small><?php printf(__('Error occurred at: %s', 'lccp-systems'), esc_html($error_data['time'])); ?></small>
                </p>
                <p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=lccp-module-settings')); ?>" class="button button-secondary">
                        <?php _e('Go to Module Settings', 'lccp-systems'); ?>
                    </a>
                    <button type="button" class="button button-link-delete clear-module-error" data-module="<?php echo esc_attr($module_id); ?>">
                        <?php _e('Clear this error', 'lccp-systems'); ?>
                    </button>
                </p>
            </div>
            <?php
        }
        
        // Add JavaScript to handle clearing individual errors
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.clear-module-error').on('click', function() {
                var moduleId = $(this).data('module');
                var $notice = $(this).closest('.notice');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'lccp_clear_module_error',
                        module_id: moduleId,
                        nonce: '<?php echo wp_create_nonce('lccp_clear_module_error'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $notice.fadeOut();
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for clearing module errors
     */
    public function ajax_clear_module_error() {
        check_ajax_referer('lccp_clear_module_error', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $module_id = sanitize_text_field($_POST['module_id']);
        $module_errors = get_transient('lccp_module_errors');
        
        if (is_array($module_errors) && isset($module_errors[$module_id])) {
            unset($module_errors[$module_id]);
            
            if (empty($module_errors)) {
                delete_transient('lccp_module_errors');
            } else {
                set_transient('lccp_module_errors', $module_errors, HOUR_IN_SECONDS);
            }
            
            wp_send_json_success('Error cleared');
        }
        
        wp_send_json_error('Error not found');
    }
}
