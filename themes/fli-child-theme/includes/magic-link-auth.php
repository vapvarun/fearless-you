<?php
/**
 * Magic Link Authentication System
 */

class FearlessLiving_Magic_Link_Auth {
    
    private static $instance = null;
    private $token_expiry = 3600; // 1 hour in seconds
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Hook into WordPress
        add_action('init', array($this, 'handle_magic_link_auth'));
        add_action('wp_ajax_nopriv_request_magic_link', array($this, 'handle_magic_link_request'));
        add_action('wp_ajax_request_magic_link', array($this, 'handle_magic_link_request'));
        add_action('login_form', array($this, 'modify_login_form'), 20);
        add_action('login_enqueue_scripts', array($this, 'enqueue_login_scripts'));
    }
    
    /**
     * Generate a secure magic link token with improved security
     */
    public function generate_magic_link($email) {
        $user = get_user_by('email', $email);
        
        if (!$user) {
            return false;
        }
        
        // Generate cryptographically secure token
        $token = bin2hex(random_bytes(32));
        $hashed_token = hash('sha256', $token . wp_salt('auth'));
        
        // Store token with user ID and expiry
        $magic_link_data = array(
            'user_id' => $user->ID,
            'token' => $hashed_token,
            'expiry' => time() + $this->token_expiry,
            'email' => $email,
            'ip' => $this->get_client_ip(),
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
        );
        
        // Store as transient with 1 hour expiry
        set_transient('magic_link_' . $hashed_token, $magic_link_data, $this->token_expiry);
        
        // Generate the magic link URL
        $magic_link = add_query_arg(array(
            'auth' => $token,
            'email' => urlencode($email),
            'nonce' => wp_create_nonce('magic_link_auth_' . $user->ID)
        ), home_url());
        
        return $magic_link;
    }
    
    /**
     * Get client IP address securely
     */
    private function get_client_ip() {
        $ip_headers = array(
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        );
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Handle comma-separated IPs (from proxies)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validate IP address
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Send magic link email
     */
    public function send_magic_link_email($email) {
        $magic_link = $this->generate_magic_link($email);
        
        if (!$magic_link) {
            return false;
        }
        
        // Check if BuddyBoss Platform is active and use its email system
        if (function_exists('bp_send_email')) {
            return $this->send_buddyboss_email($email, $magic_link);
        } else {
            return $this->send_wp_email($email, $magic_link);
        }
    }
    
    /**
     * Send magic link using BuddyBoss email system
     */
    private function send_buddyboss_email($email, $magic_link) {
        // Get user
        $user = get_user_by('email', $email);
        if (!$user) {
            return false;
        }
        
        // Create or get the email post type
        $email_type = 'magic-link-login';
        
        // Check if email template exists
        $email_query = new WP_Query(array(
            'post_type' => 'bp-email',
            'post_status' => 'publish',
            'meta_key' => '_bp_email_type',
            'meta_value' => $email_type,
            'posts_per_page' => 1
        ));
        
        if (!$email_query->have_posts()) {
            // Create the email template if it doesn't exist
            $this->create_buddyboss_email_template();
        }
        
        // Send the email
        $args = array(
            'tokens' => array(
                'magic.link' => $magic_link,
                'site.name' => get_bloginfo('name'),
                'site.url' => home_url(),
                'recipient.name' => $user->display_name,
                'unsubscribe' => bp_email_get_unsubscribe_link(array('user_id' => $user->ID))
            )
        );
        
        return bp_send_email($email_type, $user->ID, $args);
    }
    
    /**
     * Create BuddyBoss email template for magic links
     */
    private function create_buddyboss_email_template() {
        $email_type = 'magic-link-login';
        
        $subject = 'Your magic link to sign in to {{site.name}}';
        
        $content = '<p>Hi {{recipient.name}},</p>
<p>You requested a magic link to sign in to {{site.name}}.</p>
<p style="text-align: center; margin: 30px 0;">
    <a href="{{magic.link}}" style="display: inline-block; font-size: 14px; line-height: 36px; padding: 0 25px; background-color: #007cff; color: #ffffff; border-radius: 100px; text-decoration: none; font-weight: 500;">Sign In Now</a>
</p>
<p><strong>Or copy and paste this link:</strong><br>
{{magic.link}}</p>
<p>This link will expire in 1 hour for security reasons.</p>
<p>If you didn\'t request this link, you can safely ignore this email.</p>';
        
        $plain_content = 'Hi {{recipient.name}},

You requested a magic link to sign in to {{site.name}}.

Click here to sign in: {{magic.link}}

This link will expire in 1 hour for security reasons.

If you didn\'t request this link, you can safely ignore this email.';
        
        // Create the email post
        $post_id = wp_insert_post(array(
            'post_type' => 'bp-email',
            'post_status' => 'publish',
            'post_title' => $subject,
            'post_content' => $content,
            'post_excerpt' => $plain_content
        ));
        
        if ($post_id) {
            // Add the email type meta
            update_post_meta($post_id, '_bp_email_type', $email_type);
            
            // Register the email type with BuddyBoss
            if (function_exists('bp_core_add_email_type')) {
                bp_core_add_email_type(
                    $email_type,
                    array(
                        'description' => 'A user requested a magic link to sign in',
                        'unsubscribe' => array('meta_key' => 'notification_activity_new_mention'),
                    )
                );
            }
        }
    }
    
    /**
     * Fallback to standard WordPress email
     */
    private function send_wp_email($email, $magic_link) {
        $user = get_user_by('email', $email);
        $display_name = $user ? $user->display_name : 'there';
        
        $subject = 'Your magic link to sign in to ' . get_bloginfo('name');
        
        // Create HTML email that matches BuddyBoss style
        $message = $this->get_styled_email_html($display_name, $magic_link);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($email, $subject, $message, $headers);
    }
    
    /**
     * Get styled HTML email template
     */
    private function get_styled_email_html($display_name, $magic_link) {
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magic Link</title>
    <!--[if mso]>
    <style type="text/css">
    table, td {border-collapse: collapse;}
    </style>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; background-color: #FAFBFD; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #FAFBFD; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 20px 0; text-align: center;">
                            <h1 style="margin: 0; font-size: 20px; color: #122B46; font-weight: 500;"><?php echo esc_html($site_name); ?></h1>
                        </td>
                    </tr>
                    
                    <!-- Main Content -->
                    <tr>
                        <td>
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #FFFFFF; border: 1px solid #E7E9EC; border-radius: 4px;">
                                <tr>
                                    <td style="padding: 40px;">
                                        <p style="margin: 0 0 20px 0; font-size: 16px; color: #7F868F; line-height: 1.618;">Hi <?php echo esc_html($display_name); ?>,</p>
                                        
                                        <p style="margin: 0 0 30px 0; font-size: 16px; color: #7F868F; line-height: 1.618;">You requested a magic link to sign in to <?php echo esc_html($site_name); ?>.</p>
                                        
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="center" style="padding: 10px 0 30px 0;">
                                                    <a href="<?php echo esc_url($magic_link); ?>" style="display: inline-block; font-size: 14px; line-height: 36px; padding: 0 25px; background-color: #007CFF; color: #FFFFFF; border-radius: 100px; text-decoration: none; font-weight: 500;">Sign In Now</a>
                                                </td>
                                            </tr>
                                        </table>
                                        
                                        <p style="margin: 0 0 10px 0; font-size: 16px; color: #122B46; line-height: 1.618;"><strong>Or copy and paste this link:</strong></p>
                                        <p style="margin: 0 0 30px 0; font-size: 14px; color: #007CFF; line-height: 1.618; word-break: break-all;"><?php echo esc_html($magic_link); ?></p>
                                        
                                        <p style="margin: 0 0 20px 0; font-size: 16px; color: #7F868F; line-height: 1.618;">This link will expire in 1 hour for security reasons.</p>
                                        
                                        <p style="margin: 0; font-size: 16px; color: #7F868F; line-height: 1.618;">If you didn't request this link, you can safely ignore this email.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px 0; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: #7F868F; line-height: 1.618;">
                                &copy; <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?>. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle magic link authentication with improved security
     */
    public function handle_magic_link_auth() {
        if (!isset($_GET['auth']) || !isset($_GET['email']) || !isset($_GET['nonce'])) {
            return;
        }
        
        $token = sanitize_text_field($_GET['auth']);
        $email = sanitize_email($_GET['email']);
        $nonce = sanitize_text_field($_GET['nonce']);
        
        // Get user first to verify nonce
        $user = get_user_by('email', $email);
        if (!$user) {
            wp_die('Invalid magic link.', 'Invalid Link');
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($nonce, 'magic_link_auth_' . $user->ID)) {
            wp_die('Security verification failed.', 'Invalid Link');
            return;
        }
        
        // Hash the token to look it up
        $hashed_token = hash('sha256', $token . wp_salt('auth'));
        
        // Get stored magic link data
        $magic_link_data = get_transient('magic_link_' . $hashed_token);
        
        if (!$magic_link_data) {
            wp_die('This magic link is invalid or has expired.', 'Invalid Link');
            return;
        }
        
        // Verify email matches
        if ($magic_link_data['email'] !== $email) {
            wp_die('This magic link is invalid.', 'Invalid Link');
            return;
        }
        
        // Check expiry
        if (time() > $magic_link_data['expiry']) {
            delete_transient('magic_link_' . $hashed_token);
            wp_die('This magic link has expired.', 'Expired Link');
            return;
        }
        
        // Optional: Verify IP address hasn't changed significantly
        $current_ip = $this->get_client_ip();
        if (isset($magic_link_data['ip']) && $magic_link_data['ip'] !== $current_ip) {
            // Log suspicious activity but allow login (IPs can change with mobile networks)
            error_log("Magic link used from different IP. Original: {$magic_link_data['ip']}, Current: $current_ip");
        }
        
        // Log the successful magic link usage
        error_log("Magic link authentication successful for user: {$user->user_login} from IP: $current_ip");
        
        // Log the user in
        wp_set_current_user($magic_link_data['user_id']);
        wp_set_auth_cookie($magic_link_data['user_id'], true); // Remember for extended period
        
        // Delete the used token
        delete_transient('magic_link_' . $hashed_token);
        
        // Redirect to home or intended destination
        $redirect_to = isset($_GET['redirect_to']) ? esc_url_raw($_GET['redirect_to']) : home_url();
        wp_safe_redirect($redirect_to);
        exit;
    }
    
    /**
     * Handle AJAX request for magic link
     */
    public function handle_magic_link_request() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'magic_link_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $email = sanitize_email($_POST['email']);
        
        if (!is_email($email)) {
            wp_send_json_error('Please enter a valid email address');
            return;
        }
        
        if ($this->send_magic_link_email($email)) {
            wp_send_json_success('Magic link sent! Check your email.');
        } else {
            wp_send_json_error('Could not send magic link. Please check your email address.');
        }
    }
    
    /**
     * Modify the login form
     */
    public function modify_login_form() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Replace the entire login form content
            var originalForm = $('#loginform').html();
            var newFormHtml = `
                <style>
                    .magic-login-container {
                        margin: 0;
                        padding: 0;
                    }
                    
                    .magic-login-buttons {
                        display: flex;
                        gap: 10px;
                        margin-top: 15px;
                    }
                    
                    .magic-login-buttons button {
                        flex: 1;
                        padding: 10px 15px;
                        font-size: 14px;
                        cursor: pointer;
                        border: 1px solid #2271b1;
                        background: #fff;
                        color: #2271b1;
                        border-radius: 3px;
                        transition: all 0.3s;
                    }
                    
                    .magic-login-buttons button:hover {
                        background: #2271b1;
                        color: #fff;
                    }
                    
                    .magic-login-buttons button.primary {
                        background: #2271b1;
                        color: #fff;
                    }
                    
                    .magic-login-buttons button.primary:hover {
                        background: #135e96;
                    }
                    
                    #magic-login-message {
                        margin-top: 10px;
                        padding: 10px;
                        border-radius: 3px;
                        display: none;
                    }
                    
                    #magic-login-message.success {
                        background: #d4edda;
                        color: #155724;
                        border: 1px solid #c3e6cb;
                    }
                    
                    #magic-login-message.error {
                        background: #f8d7da;
                        color: #721c24;
                        border: 1px solid #f5c6cb;
                    }
                    
                    
                    #other-options-message.success {
                        background: #d4edda;
                        color: #155724;
                        border: 1px solid #c3e6cb;
                    }
                    
                    #other-options-message.error {
                        background: #f8d7da;
                        color: #721c24;
                        border: 1px solid #f5c6cb;
                    }
                </style>
                
                <div class="magic-login-container">
                    <div id="magic-login-form">
                        <p>
                            <label for="magic_email">Email Address</label>
                            <input type="email" name="magic_email" id="magic_email" class="input" size="20" autocapitalize="off" />
                        </p>
                        
                        <div class="magic-login-buttons">
                            <button type="button" id="magic-link-btn" class="primary">Magic Link</button>
                            <button type="button" id="password-login-btn">Login Password</button>
                        </div>
                        
                        <div id="magic-login-message"></div>
                        
                        <div id="password-login-section" style="display: none; margin-top: 20px; padding-top: 15px; border-top: 1px solid #E7E9EC;">
                            <p style="margin-bottom: 15px; font-size: 14px; color: #7F868F; text-align: center;">Enter your password to login</p>
                            
                            <p>
                                <label for="user_pass_inline">Password</label>
                                <input type="password" name="pwd" id="user_pass_inline" class="input" value="" size="20" placeholder="Enter your password" />
                            </p>
                            <p class="forgetmenot" style="margin: 15px 0;">
                                <label for="rememberme_inline" style="display: flex; align-items: center; gap: 10px; font-size: 14px; cursor: pointer;">
                                    <input name="rememberme" type="checkbox" id="rememberme_inline" value="forever" style="margin: 0; width: 16px; height: 16px; flex-shrink: 0;">
                                    <span style="white-space: nowrap;">Remember Me</span>
                                </label>
                            </p>
                            <p class="submit">
                                <input type="submit" name="wp-submit" id="wp-submit-inline" class="button button-primary button-large" value="Log In" style="width: 100%;" />
                            </p>
                            
                            <p style="margin-top: 15px; text-align: center;">
                                <a href="#" id="hide-password-login" style="color: #7F868F; text-decoration: none; font-size: 12px;">← Back to Magic Link</a>
                            </p>
                        </div>
                        
                        <div class="other-options-link" style="text-align: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #E7E9EC;">
                            <p style="margin: 0; font-size: 14px; color: #7F868F;">
                                Need help with your account? 
                                <a href="#" id="show-other-options" style="color: #2271b1; text-decoration: none;">Other options</a>
                            </p>
                        </div>
                        
                        <div id="other-options-section" style="display: none; margin-top: 20px; padding-top: 15px; border-top: 1px solid #E7E9EC;">
                            <p style="margin-bottom: 15px; font-size: 14px; color: #7F868F; text-align: center;">What would you like to do?</p>
                            
                            <div class="other-options-buttons" style="display: flex; flex-direction: column; gap: 10px;">
                                <button type="button" id="forgot-password-btn" class="other-option-btn" style="padding: 10px 15px; border: 1px solid #2271b1; background: #fff; color: #2271b1; border-radius: 3px; cursor: pointer; transition: all 0.3s;">
                                    🔑 Reset Password
                                </button>
                                <button type="button" id="export-data-btn" class="other-option-btn" style="padding: 10px 15px; border: 1px solid #2271b1; background: #fff; color: #2271b1; border-radius: 3px; cursor: pointer; transition: all 0.3s;">
                                    📥 Export My Data
                                </button>
                                <button type="button" id="delete-account-btn" class="other-option-btn" style="padding: 10px 15px; border: 1px solid #dc3545; background: #fff; color: #dc3545; border-radius: 3px; cursor: pointer; transition: all 0.3s;">
                                    🗑️ Delete Account
                                </button>
                            </div>
                            
                            <div id="other-options-message" style="margin-top: 10px; padding: 10px; border-radius: 3px; display: none;"></div>
                            
                            <p style="margin-top: 15px; text-align: center;">
                                <a href="#" id="hide-other-options" style="color: #7F868F; text-decoration: none; font-size: 12px;">← Back to login</a>
                            </p>
                        </div>
                    </div>
                </div>
            `;
            
            // Replace the form content
            $('#loginform').html(newFormHtml);
            
            // Handle login messages (like "Please login to access this website")
            handleLoginMessages();
            
            // Handle magic link button
            $('#magic-link-btn').on('click', function() {
                var email = $('#magic_email').val();
                
                if (!email) {
                    showMessage('Please enter your email address', 'error');
                    return;
                }
                
                var $btn = $(this);
                $btn.prop('disabled', true).text('Sending...');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'request_magic_link',
                        email: email,
                        nonce: '<?php echo wp_create_nonce('magic_link_nonce'); ?>'
                    },
                    success: function(response) {
                        $btn.prop('disabled', false).text('Magic Link');
                        
                        if (response.success) {
                            showMessage(response.data, 'success');
                            $('#magic_email').val('');
                        } else {
                            showMessage(response.data, 'error');
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).text('Magic Link');
                        showMessage('An error occurred. Please try again.', 'error');
                    }
                });
            });
            
            // Handle password login button
            $('#password-login-btn').on('click', function() {
                $('#password-login-section').slideDown();
                $(this).parent().hide();
                $('#magic-login-message').hide();
            });
            
            // Handle hide password login
            $('#hide-password-login').on('click', function(e) {
                e.preventDefault();
                $('#password-login-section').slideUp();
                $('.magic-login-buttons').show();
            });
            
            // Handle password form submission
            $('#loginform').on('submit', function(e) {
                if ($('#password-login-section').is(':visible')) {
                    // We're in inline password mode, prepare the submission
                    var email = $('#magic_email').val();
                    var password = $('#user_pass_inline').val();
                    var remember = $('#rememberme_inline').is(':checked');
                    
                    if (!email || !password) {
                        e.preventDefault();
                        showMessage('Please enter both email and password', 'error');
                        return;
                    }
                    
                    // Add hidden fields for WordPress login
                    if (!$('input[name="log"]').length) {
                        $(this).append('<input type="hidden" name="log" value="' + email + '">');
                        $(this).append('<input type="hidden" name="pwd" value="' + password + '">');
                        if (remember) {
                            $(this).append('<input type="hidden" name="rememberme" value="forever">');
                        }
                        $(this).append('<input type="hidden" name="wp-submit" value="Log In">');
                    }
                } else {
                    // We're in magic link mode, prevent submission
                    e.preventDefault();
                }
            });
            
            // Handle show/hide other options
            $('#show-other-options').on('click', function(e) {
                e.preventDefault();
                $('#other-options-section').slideDown();
                $(this).parent().parent().hide();
            });
            
            $('#hide-other-options').on('click', function(e) {
                e.preventDefault();
                $('#other-options-section').slideUp();
                $('.other-options-link').show();
            });
            
            // Handle other option buttons
            $('#forgot-password-btn').on('click', function() {
                handleOtherOption('reset-password', $(this));
            });
            
            $('#export-data-btn').on('click', function() {
                handleOtherOption('export-data', $(this));
            });
            
            $('#delete-account-btn').on('click', function() {
                handleOtherOption('delete-account', $(this));
            });
            
            function handleOtherOption(option, $btn) {
                var email = $('#magic_email').val();
                
                if (!email) {
                    showOtherOptionsMessage('Please enter your email address first', 'error');
                    return;
                }
                
                var originalText = $btn.text();
                $btn.prop('disabled', true).text('Processing...');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'handle_other_options',
                        email: email,
                        option: option,
                        nonce: '<?php echo wp_create_nonce('other_options_nonce'); ?>'
                    },
                    success: function(response) {
                        $btn.prop('disabled', false).text(originalText);
                        
                        if (response.success) {
                            showOtherOptionsMessage(response.data.message, 'success');
                            $('#magic_email').val('');
                        } else {
                            showOtherOptionsMessage(response.data, 'error');
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).text(originalText);
                        showOtherOptionsMessage('An error occurred. Please try again.', 'error');
                    }
                });
            }
            
            function showMessage(message, type) {
                $('#magic-login-message')
                    .removeClass('success error')
                    .addClass(type)
                    .text(message)
                    .show();
                    
                setTimeout(function() {
                    $('#magic-login-message').fadeOut();
                }, 5000);
            }
            
            function showOtherOptionsMessage(message, type) {
                $('#other-options-message')
                    .removeClass('success error')
                    .addClass(type)
                    .text(message)
                    .show();
                    
                setTimeout(function() {
                    $('#other-options-message').fadeOut();
                }, 5000);
            }
            
            // Add hover effects for other option buttons
            $(document).on('mouseenter', '.other-option-btn', function() {
                if ($(this).attr('id') === 'delete-account-btn') {
                    $(this).css({
                        'background': '#dc3545',
                        'color': '#fff'
                    });
                } else {
                    $(this).css({
                        'background': '#2271b1',
                        'color': '#fff'
                    });
                }
            }).on('mouseleave', '.other-option-btn', function() {
                if ($(this).attr('id') === 'delete-account-btn') {
                    $(this).css({
                        'background': '#fff',
                        'color': '#dc3545'
                    });
                } else {
                    $(this).css({
                        'background': '#fff',
                        'color': '#2271b1'
                    });
                }
            });
            
            function handleLoginMessages() {
                // Find login messages (like "Please login to access this website")
                var $loginMessages = $('#login_error, .login .message');
                
                if ($loginMessages.length > 0) {
                    $loginMessages.each(function() {
                        var $message = $(this);
                        
                        // Make message dismissible by adding close button
                        if (!$message.find('.message-close').length) {
                            $message.css({
                                'position': 'relative',
                                'padding-right': '40px',
                                'cursor': 'pointer'
                            });
                            
                            var $closeBtn = $('<span class="message-close" style="position: absolute; top: 50%; right: 15px; transform: translateY(-50%); font-size: 18px; font-weight: bold; color: #666; cursor: pointer; line-height: 1;">&times;</span>');
                            
                            $message.append($closeBtn);
                            
                            // Click to dismiss
                            $message.on('click', '.message-close', function(e) {
                                e.stopPropagation();
                                $message.fadeOut(300);
                            });
                            
                            // Click anywhere on message to dismiss
                            $message.on('click', function() {
                                $message.fadeOut(300);
                            });
                            
                            // Auto-fade after 5 seconds
                            setTimeout(function() {
                                if ($message.is(':visible')) {
                                    $message.fadeOut(500);
                                }
                            }, 5000);
                        }
                    });
                }
            }
        });
        </script>
        <?php
    }
    
    /**
     * Enqueue login scripts
     */
    public function enqueue_login_scripts() {
        wp_enqueue_script('jquery');
    }
}

// Initialize the magic link authentication
FearlessLiving_Magic_Link_Auth::get_instance();