<?php
/*
Plugin Name: Phunkie Custom Login
Plugin URI: https://elephunkie.com
Description: Custom login form with an "Other Options" section for account-related actions.
Version: 2.8.3
Author: Jonathan Albiar
Author URI: https://elephunkie.com
Text Domain: phunkie-custom-login
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Handle form submissions
function phunkie_custom_login_form_handler() {
    if (isset($_POST['otheroptions-submit'])) {
        $email = sanitize_email($_POST['user_email']);
        $action = sanitize_text_field($_POST['action_option']);

        if ($action === 'reset_password') {
            // Trigger the WordPress reset password function
            $success_message = 'A password reset link has been sent to your email address.';
            wp_lostpassword_url();
        } elseif ($action === 'delete_account') {
            $support_email = 'Support@Fearlessliving.org';
            $subject = 'Account Deletion Request';
            $message = '
                <!doctype html>
                <html>
                <head>
                <meta charset="UTF-8">
                <title>Account Deletion Request</title>
                </head>
                <body style="display: flex; flex-direction: column; background-color: #e5e5e5; align-content: center; align-items: center; text-align: center;">
                    <img style="align-self: center; margin-bottom: 10px;" src="https://you.fearlessliving.org/wp-content/uploads/2022/09/fy_dk_teal.png" width="60" height="39" alt=""/>
                    <div style="display: flex; flex-direction: column; background-color: #fff; align-content: center; align-items: center; padding: 1em">
                        Account deletion request from: ' . $email . '
                    </div>
                    <p style="margin-top:1em">© 2024 Rhonda Britten and Fearless Living. All Rights Reserved.</p>
                </body>
                </html>
            ';
            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail($support_email, $subject, $message, $headers);

            $success_message = 'Your request to delete your account has been submitted. You will receive a confirmation email shortly.';
        } elseif ($action === 'export_data') {
            $support_email = 'Support@Fearlessliving.org';
            $subject = 'Data Export Request';
            $message = '
                <!doctype html>
                <html>
                <head>
                <meta charset="UTF-8">
                <title>Data Export Request</title>
                </head>
                <body style="display: flex; flex-direction: column; background-color: #e5e5e5; align-content: center; align-items: center; text-align: center;">
                    <img style="align-self: center; margin-bottom: 10px;" src="https://you.fearlessliving.org/wp-content/uploads/2022/09/fy_dk_teal.png" width="60" height="39" alt=""/>
                    <div style="display: flex; flex-direction: column; background-color: #fff; align-content: center; align-items: center; padding: 1em">
                        Data export request from: ' . $email . '
                    </div>
                    <p style="margin-top:1em">© 2024 Rhonda Britten and Fearless Living. All Rights Reserved.</p>
                </body>
                </html>
            ';
            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail($support_email, $subject, $message, $headers);

            $success_message = 'Your request to export your data has been submitted. You will receive a confirmation email shortly.';
        }

        // Display success message
        if (isset($success_message)) {
            add_filter('login_message', function() use ($success_message) {
                return '<div style="background-color: #dff0d8 !important; color: #3c763d !important; padding: 15px !important; margin-bottom: 20px !important; border: 1px solid #d6e9c6 !important; border-radius: 5px !important; text-align: center !important;">' . $success_message . '</div>';
            });
        }
    }
}
add_action('login_init', 'phunkie_custom_login_form_handler');

// Override the default login form
function phunkie_custom_login_form() {
    if (isset($_GET['action']) && $_GET['action'] === 'otheroptions') {
        ?>
        <style>
            #loginform {
                display: none !important;
            }
            #otheroptionsform {
                max-width: 400px !important;
                margin: auto !important;
                background-color: rgba(255, 255, 255, 0.9) !important;
                padding: 40px 20px !important;
                border-radius: 10px !important;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.13) !important;
            }
            #otheroptionsform p {
                font-weight: bold !important;
                color: #555 !important;
                margin-bottom: 15px !important;
            }
            #otheroptionsform input, #otheroptionsform label {
                display: block !important;
                width: 100% !important;
                padding: 10px !important;
                border: 1px solid #ddd !important;
                border-radius: 5px !important;
                margin-bottom: 15px !important;
                font-size: 14px !important;
            }
            #otheroptionsform .submit {
                display: flex !important;
                justify-content: center !important;
            }
            #otheroptionsform .button-primary {
                padding: 10px 20px !important;
                background-color: #0073aa !important;
                border: none !important;
                border-radius: 5px !important;
                color: white !important;
                cursor: pointer !important;
                width: 100% !important;
                font-size: 16px !important;
            }
            .back-to-signin, .terms-of-use {
                display: block !important;
                margin-top: 10px !important;
                text-align: center !important;
                font-size: 14px !important;
                color: #555 !important;
            }
        </style>
        <form id="otheroptionsform" action="" method="post">
            <p>Please enter your username or email address and select your action below:</p>
            <label for="user_email">Email Address</label>
            <input type="email" name="user_email" id="user_email" class="input" value="" size="20">
            
            <label><input type="radio" name="action_option" value="reset_password"> Reset Password</label>
            <label><input type="radio" name="action_option" value="delete_account"> Delete Account</label>
            <label><input type="radio" name="action_option" value="export_data"> Export User Data</label>

            <p class="submit">
                <input type="submit" name="otheroptions-submit" id="otheroptions-submit" class="button button-primary" value="Submit Request">
            </p>
            <a class="back-to-signin" href="<?php echo wp_login_url(); ?>">Back to Sign In</a>
            <a class="terms-of-use" href="#">Terms of Use</a>
        </form>
        <?php
    } else {
        ?>
        <style>
            #loginform {
                max-width: 400px !important;
                margin: auto !important;
                background-color: rgba(255, 255, 255, 0.9) !important;
                padding: 40px 20px !important;
                border-radius: 10px !important;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.13) !important;
            }
            #loginform p {
                font-weight: bold !important;
                color: #555 !important;
                margin-bottom: 15px !important;
            }
            #loginform input, #loginform label {
                display: block !important;
                width: 100% !important;
                padding: 10px !important;
                border: 1px solid #ddd !important;
                border-radius: 5px !important;
                margin-bottom: 15px !important;
                font-size: 14px !important;
            }
            #loginform .submit {
                display: flex !important;
                justify-content: center !important;
            }
            #loginform .button-primary {
                padding: 10px 20px !important;
                background-color: #0073aa !important;
                border: none !important;
                border-radius: 5px !important;
                color: white !important;
                cursor: pointer !important;
                width: 100% !important;
                font-size: 16px !important;
            }
            .other-options {
                display: block !important;
                margin-top: 10px !important;
                text-align: center !important;
                font-size: 14px !important;
                color: #555 !important;
            }
        </style>
        <form id="loginform" action="<?php echo esc_url(site_url('wp-login.php', 'login_post')); ?>" method="post">
            <p>
                <label for="user_login">Username or Email Address</label>
                <input type="text" name="log" id="user_login" class="input" value="" size="20">
            </p>
            <p>
                <label for="user_pass">Password</label>
                <input type="password" name="pwd" id="user_pass" class="input" value="" size="20">
            </p>
            <p class="submit">
                <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary" value="Log In">
            </p>
            <a class="other-options" href="<?php echo esc_url(add_query_arg('action', 'otheroptions', wp_login_url())); ?>">Other Options</a>
        </form>
        <?php
    }
}
add_action('login_form', 'phunkie_custom_login_form');
