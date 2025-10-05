<?php
/**
 * Plugin Name: Custom Login Page with Tabs
 * Description: Adds a tabbed interface to the BuddyBoss login page for sign in, forgot password, export data, and delete account.
 * Version: 1.9
 * Author: Jonathan Albiar
 * Author URI: https://elephunkie.com
 * Text Domain: phunk-custom-requests
 */

// Enqueue custom styles and scripts
function phunk_custom_login_enqueue_scripts() {
    echo '
    <style>
        .custom-login-tabs {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }
        .custom-login-tabs ul {
            list-style: none;
            padding: 0;
            display: flex;
            justify-content: space-around;
            border-bottom: 2px solid #ddd;
            margin-bottom: 20px;
        }
        .custom-login-tabs ul li {
            margin: 0;
        }
        .custom-login-tabs ul li a {
            display: block;
            padding: 10px;
            text-decoration: none;
            color: #0073aa;
            border-bottom: 2px solid transparent;
        }
        .custom-login-tabs ul li.active a,
        .custom-login-tabs ul li a:hover {
            border-bottom: 2px solid #0073aa;
        }
        .custom-login-tabs .tab-content {
            display: none;
        }
        .custom-login-tabs .tab-content.active {
            display: block;
        }
        .custom-form {
            display: block;
            margin: 0 auto;
            padding: 15px;
            border: 1px solid #ddd;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,.13);
        }
        .custom-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .custom-form input[type="text"],
        .custom-form input[type="email"],
        .custom-form input[type="password"],
        .custom-form select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            box-sizing: border-box;
        }
        .custom-form input[type="submit"] {
            width: 100%;
            padding: 10px;
            background: #0073aa;
            border: none;
            color: #fff;
            border-radius: 3px;
            cursor: pointer;
        }
        .custom-form input[type="submit"]:hover {
            background: #005f8d;
        }
        .login #nav,
        .login #backtoblog {
            display: none;
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".custom-login-tabs ul li a").forEach(function(tab) {
                tab.addEventListener("click", function(event) {
                    event.preventDefault();
                    document.querySelectorAll(".custom-login-tabs ul li").forEach(function(li) {
                        li.classList.remove("active");
                    });
                    tab.parentElement.classList.add("active");
                    document.querySelectorAll(".custom-login-tabs .tab-content").forEach(function(content) {
                        content.classList.remove("active");
                    });
                    document.querySelector(tab.getAttribute("href")).classList.add("active");
                });
            });
        });
    </script>
    ';
}
add_action('login_enqueue_scripts', 'phunk_custom_login_enqueue_scripts');

// Add custom tabs and forms to the login page
function phunk_custom_login_form() {
    echo '
    <div class="custom-login-tabs">
        <ul class="tab-links">
            <li class="active"><a href="#tab-signin">Sign In</a></li>
            <li><a href="#tab-forgot-password">Forgot Password</a></li>
            <li><a href="#tab-export-data">Export Data</a></li>
            <li><a href="#tab-delete-account">Delete Account</a></li>
        </ul>
        <div class="tab-content active" id="tab-signin">
            ' . wp_login_form(array('echo' => false)) . '
        </div>
        <div class="tab-content" id="tab-forgot-password">
            <form method="post" action="' . esc_url(site_url('wp-login.php?action=lostpassword', 'login_post')) . '" class="custom-form">
                <label for="user_login">Email:</label>
                <input type="email" name="user_login" id="user_login" required>
                <input type="submit" name="wp-submit" id="wp-submit" value="Get New Password">
            </form>
        </div>
        <div class="tab-content" id="tab-export-data">
            <form id="custom-request-form-export" method="post" class="custom-form">
                ' . wp_nonce_field('phunk_custom_request_nonce_action', 'phunk_custom_request_nonce', true, false) . '
                <label for="phunk_first_name_export">First Name:</label>
                <input type="text" id="phunk_first_name_export" name="phunk_first_name" required>
                <label for="phunk_email_export">Email:</label>
                <input type="email" id="phunk_email_export" name="phunk_email" required>
                <input type="hidden" name="phunk_request_type" value="export_data">
                <input type="submit" name="submit_export" value="Submit Request">
            </form>
        </div>
        <div class="tab-content" id="tab-delete-account">
            <form id="custom-request-form-delete" method="post" class="custom-form">
                ' . wp_nonce_field('phunk_custom_request_nonce_action', 'phunk_custom_request_nonce', true, false) . '
                <label for="phunk_first_name_delete">First Name:</label>
                <input type="text" id="phunk_first_name_delete" name="phunk_first_name" required>
                <label for="phunk_email_delete">Email:</label>
                <input type="email" id="phunk_email_delete" name="phunk_email" required>
                <input type="hidden" name="phunk_request_type" value="delete_account">
                <input type="submit" name="submit_delete" value="Submit Request">
            </form>
        </div>
    </div>
    ';
}
add_action('login_form', 'phunk_custom_login_form');

// Handle form submission
function phunk_handle_custom_request() {
    if (isset($_POST['submit_export']) || isset($_POST['submit_delete'])) {
        // Verify nonce for security
        if (!isset($_POST['phunk_custom_request_nonce']) || !wp_verify_nonce($_POST['phunk_custom_request_nonce'], 'phunk_custom_request_nonce_action')) {
            wp_die('Security check failed.');
        }

        // Sanitize user inputs
        $first_name = sanitize_text_field($_POST['phunk_first_name']);
        $email = sanitize_email($_POST['phunk_email']);
        $request_type = sanitize_text_field($_POST['phunk_request_type']);

        // Check if the email is valid
        if (!is_email($email)) {
            wp_die('Invalid email address.');
        }

        // Verify if the email exists and is not an administrator
        if (email_exists($email) && !user_can(get_user_by('email', $email), 'administrator')) {
            // Generate new password and prevent login for 24 hours
            $user = get_user_by('email', $email);
            $new_password = wp_generate_password();
            wp_set_password($new_password, $user->ID);
            update_user_meta($user->ID, 'account_lockout', time());

            // Log the request to the database
            global $wpdb;
            $wpdb->insert($wpdb->prefix . 'custom_requests', [
                'name' => $first_name,
                'email' => $email,
                'request_type' => $request_type,
                'submitted_at' => current_time('mysql')
            ]);

            // Send email to support
            $to = 'support@fearlessliving.org';
            $subject = 'New Request: ' . $request_type;
            $message = "First Name: $first_name\nEmail: $email\nRequest Type: $request_type";
            $headers = ['Content-Type: text/plain; charset=UTF-8'];

            if (wp_mail($to, $subject, $message, $headers)) {
                error_log('Email sent successfully to ' . $to);
            } else {
                error_log('Failed to send email to ' . $to);
            }

            // Sign out the user if logged in
            if (is_user_logged_in()) {
                wp_logout();
            }

            wp_die('Your request has been submitted. Please check your email for further instructions.');
        } else {
            wp_die('Invalid request. Either the email does not exist or belongs to an administrator.');
        }
    }
}
add_action('init', 'phunk_handle_custom_request');

// Create table to log requests
function phunk_create_custom_requests_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_requests';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name tinytext NOT NULL,
            email text NOT NULL,
            request_type tinytext NOT NULL,
            submitted_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, 'phunk_create_custom_requests_table');

// Prevent login if account is locked out
function phunk_prevent_login($user, $username, $password) {
    if (!is_wp_error($user)) {
        $lockout_time = get_user_meta($user->ID, 'account_lockout', true);
        if ($lockout_time && (time() - $lockout_time) < 86400) {
            return new WP_Error('account_locked', __('Your account is temporarily locked. Please try again later.'));
        }
    }
    return $user;
}
add_filter('authenticate', 'phunk_prevent_login', 30, 3);

// Add admin menu to view logs
function phunk_add_admin_menu() {
    add_menu_page('Custom Requests', 'Custom Requests', 'manage_options', 'custom-requests', 'phunk_display_logs');
}
add_action('admin_menu', 'phunk_add_admin_menu');

// Display logs in admin area
function phunk_display_logs() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_requests';
    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY submitted_at DESC");

    echo '<div class="wrap">';
    echo '<h1>Custom Requests</h1>';
    echo '<table class="widefat fixed" cellspacing="0">';
    echo '<thead><tr><th>First Name</th><th>Email</th><th>Request Type</th><th>Submitted At</th></tr></thead>';
    echo '<tbody>';

    if ($results) {
        foreach ($results as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row->name) . '</td>';
            echo '<td>' . esc_html($row->email) . '</td>';
            echo '<td>' . esc_html($row->request_type) . '</td>';
            echo '<td>' . esc_html($row->submitted_at) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="4">No requests found.</td></tr>';
    }

    echo '</tbody></table></div>';
}
?>
