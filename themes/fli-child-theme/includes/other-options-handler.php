<?php
/**
 * Other Options Handler
 * Handles password reset, account deletion, and data export requests
 */

class FearlessLiving_Other_Options_Handler {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('wp_ajax_handle_other_options', array($this, 'handle_request'));
        add_action('wp_ajax_nopriv_handle_other_options', array($this, 'handle_request'));
        add_action('init', array($this, 'handle_confirmation_links'));
    }
    
    /**
     * Handle AJAX request for other options
     */
    public function handle_request() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'other_options_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $email = sanitize_email($_POST['email']);
        $option = sanitize_text_field($_POST['option']);
        
        if (!is_email($email)) {
            wp_send_json_error('Please enter a valid email address');
            return;
        }
        
        switch ($option) {
            case 'reset-password':
                $result = $this->handle_password_reset($email);
                break;
            case 'export-data':
                $result = $this->handle_data_export($email);
                break;
            case 'delete-account':
                $result = $this->handle_account_deletion($email);
                break;
            default:
                wp_send_json_error('Invalid option selected');
                return;
        }
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Handle password reset request
     */
    private function handle_password_reset($email) {
        $user = get_user_by('email', $email);
        
        if (!$user) {
            return array('success' => false, 'message' => 'No account found with that email address.');
        }
        
        // Use WordPress built-in password reset
        $result = retrieve_password($user->user_login);
        
        if (is_wp_error($result)) {
            return array('success' => false, 'message' => $result->get_error_message());
        }
        
        return array(
            'success' => true,
            'message' => 'Password reset link has been sent to your email address.'
        );
    }
    
    /**
     * Handle data export request
     */
    private function handle_data_export($email) {
        $user = get_user_by('email', $email);
        
        if (!$user) {
            return array('success' => false, 'message' => 'No account found with that email address.');
        }
        
        // Generate export token
        $token = wp_generate_password(32, false);
        $hashed_token = wp_hash($token);
        
        // Store export request
        $export_data = array(
            'user_id' => $user->ID,
            'email' => $email,
            'token' => $hashed_token,
            'expiry' => time() + (2 * 3600), // 2 hours
            'status' => 'pending'
        );
        
        set_transient('data_export_' . $hashed_token, $export_data, 2 * 3600);
        
        // Generate user data
        $user_data = $this->generate_user_data_export($user);
        
        // Save export file
        $export_file = $this->save_export_file($user_data, $token);
        
        if (!$export_file) {
            return array('success' => false, 'message' => 'Failed to create export file.');
        }
        
        // Update export data with file path
        $export_data['file_path'] = $export_file;
        $export_data['status'] = 'ready';
        set_transient('data_export_' . $hashed_token, $export_data, 2 * 3600);
        
        // Send download link email
        $download_link = add_query_arg(array(
            'action' => 'download_export',
            'token' => $token,
            'email' => urlencode($email)
        ), home_url());
        
        $email_sent = $this->send_export_email($user, $download_link);
        
        if (!$email_sent) {
            return array('success' => false, 'message' => 'Failed to send download link email.');
        }
        
        return array(
            'success' => true,
            'message' => 'Your data export is ready! A download link has been sent to your email address. The link will expire in 2 hours.'
        );
    }
    
    /**
     * Handle account deletion request
     */
    private function handle_account_deletion($email) {
        $user = get_user_by('email', $email);
        
        if (!$user) {
            return array('success' => false, 'message' => 'No account found with that email address.');
        }
        
        // Generate deletion token
        $token = wp_generate_password(32, false);
        $hashed_token = wp_hash($token);
        
        // Store deletion request
        $deletion_data = array(
            'user_id' => $user->ID,
            'email' => $email,
            'token' => $hashed_token,
            'expiry' => time() + (24 * 3600), // 24 hours
            'status' => 'pending'
        );
        
        set_transient('account_deletion_' . $hashed_token, $deletion_data, 24 * 3600);
        
        // Send confirmation email
        $confirmation_link = add_query_arg(array(
            'action' => 'confirm_deletion',
            'token' => $token,
            'email' => urlencode($email)
        ), home_url());
        
        $email_sent = $this->send_deletion_confirmation_email($user, $confirmation_link);
        
        if (!$email_sent) {
            return array('success' => false, 'message' => 'Failed to send confirmation email.');
        }
        
        return array(
            'success' => true,
            'message' => 'A confirmation email has been sent to your email address. Please click the link to confirm account deletion.'
        );
    }
    
    /**
     * Generate user data export
     */
    private function generate_user_data_export($user) {
        $data = array(
            'user_info' => array(
                'ID' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'registration_date' => $user->user_registered,
                'role' => implode(', ', $user->roles)
            ),
            'user_meta' => array(),
            'posts' => array(),
            'comments' => array(),
            'buddypress_data' => array(),
            'learndash_data' => array()
        );
        
        // Get user meta
        $user_meta = get_user_meta($user->ID);
        foreach ($user_meta as $key => $value) {
            if (!str_starts_with($key, '_')) { // Skip private meta
                $data['user_meta'][$key] = is_array($value) ? $value[0] : $value;
            }
        }
        
        // Get user posts
        $posts = get_posts(array(
            'author' => $user->ID,
            'post_status' => 'any',
            'numberposts' => -1
        ));
        
        foreach ($posts as $post) {
            $data['posts'][] = array(
                'title' => $post->post_title,
                'content' => $post->post_content,
                'date' => $post->post_date,
                'status' => $post->post_status,
                'type' => $post->post_type
            );
        }
        
        // Get user comments
        $comments = get_comments(array(
            'user_id' => $user->ID,
            'number' => 0
        ));
        
        foreach ($comments as $comment) {
            $data['comments'][] = array(
                'content' => $comment->comment_content,
                'date' => $comment->comment_date,
                'post_title' => get_the_title($comment->comment_post_ID),
                'status' => $comment->comment_approved
            );
        }
        
        // BuddyPress data
        if (function_exists('bp_is_active')) {
            $data['buddypress_data'] = $this->get_buddypress_data($user->ID);
        }
        
        // LearnDash data
        if (function_exists('learndash_get_user_courses_from_meta')) {
            $data['learndash_data'] = $this->get_learndash_data($user->ID);
        }
        
        return $data;
    }
    
    /**
     * Get BuddyPress data
     */
    private function get_buddypress_data($user_id) {
        $bp_data = array();
        
        // Profile data
        if (function_exists('bp_get_profile_field_data')) {
            $bp_data['profile_fields'] = bp_get_profile_field_data('field=1&user_id=' . $user_id);
        }
        
        // Activity data
        if (function_exists('bp_activity_get')) {
            $activities = bp_activity_get(array(
                'user_id' => $user_id,
                'per_page' => 0
            ));
            
            if (!empty($activities['activities'])) {
                foreach ($activities['activities'] as $activity) {
                    $bp_data['activities'][] = array(
                        'type' => $activity->type,
                        'content' => $activity->content,
                        'date' => $activity->date_recorded
                    );
                }
            }
        }
        
        return $bp_data;
    }
    
    /**
     * Get LearnDash data
     */
    private function get_learndash_data($user_id) {
        $ld_data = array();
        
        // User courses
        if (function_exists('learndash_user_get_enrolled_courses')) {
            $courses = learndash_user_get_enrolled_courses($user_id);
            foreach ($courses as $course_id) {
                $course_progress = learndash_user_get_course_progress($user_id, $course_id);
                $ld_data['courses'][] = array(
                    'title' => get_the_title($course_id),
                    'progress' => $course_progress,
                    'completed' => learndash_course_completed($user_id, $course_id)
                );
            }
        }
        
        // Quiz attempts
        if (function_exists('learndash_get_user_quiz_attempts')) {
            $quiz_attempts = learndash_get_user_quiz_attempts($user_id);
            foreach ($quiz_attempts as $attempt) {
                $ld_data['quiz_attempts'][] = array(
                    'quiz_title' => get_the_title($attempt->quiz_id),
                    'score' => $attempt->score,
                    'pass' => $attempt->pass,
                    'date' => $attempt->time
                );
            }
        }
        
        return $ld_data;
    }
    
    /**
     * Save export file
     */
    private function save_export_file($data, $token) {
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/user-exports/';
        
        // Create directory if it doesn't exist
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }
        
        $filename = 'user-export-' . $token . '.json';
        $filepath = $export_dir . $filename;
        
        $json_data = json_encode($data, JSON_PRETTY_PRINT);
        
        if (file_put_contents($filepath, $json_data) !== false) {
            return $filepath;
        }
        
        return false;
    }
    
    /**
     * Send data export email
     */
    private function send_export_email($user, $download_link) {
        $subject = 'Your data export from ' . get_bloginfo('name');
        
        $message = $this->get_export_email_html($user->display_name, $download_link);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($user->user_email, $subject, $message, $headers);
    }
    
    /**
     * Send account deletion confirmation email
     */
    private function send_deletion_confirmation_email($user, $confirmation_link) {
        $subject = 'Confirm account deletion - ' . get_bloginfo('name');
        
        $message = $this->get_deletion_email_html($user->display_name, $confirmation_link);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($user->user_email, $subject, $message, $headers);
    }
    
    /**
     * Get export email HTML
     */
    private function get_export_email_html($display_name, $download_link) {
        $site_name = get_bloginfo('name');
        
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Export Ready</title>
</head>
<body style="margin: 0; padding: 0; background-color: #FAFBFD; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #FAFBFD; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">
                    <tr>
                        <td style="padding: 20px 0; text-align: center;">
                            <h1 style="margin: 0; font-size: 20px; color: #122B46; font-weight: 500;"><?php echo esc_html($site_name); ?></h1>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #FFFFFF; border: 1px solid #E7E9EC; border-radius: 4px;">
                                <tr>
                                    <td style="padding: 40px;">
                                        <p style="margin: 0 0 20px 0; font-size: 16px; color: #7F868F; line-height: 1.618;">Hi <?php echo esc_html($display_name); ?>,</p>
                                        
                                        <p style="margin: 0 0 30px 0; font-size: 16px; color: #7F868F; line-height: 1.618;">Your data export is ready for download.</p>
                                        
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="center" style="padding: 10px 0 30px 0;">
                                                    <a href="<?php echo esc_url($download_link); ?>" style="display: inline-block; font-size: 14px; line-height: 36px; padding: 0 25px; background-color: #007CFF; color: #FFFFFF; border-radius: 100px; text-decoration: none; font-weight: 500;">Download Your Data</a>
                                                </td>
                                            </tr>
                                        </table>
                                        
                                        <p style="margin: 0 0 20px 0; font-size: 16px; color: #7F868F; line-height: 1.618;">This download link will expire in 2 hours for security reasons.</p>
                                        
                                        <p style="margin: 0; font-size: 16px; color: #7F868F; line-height: 1.618;">If you didn't request this export, you can safely ignore this email.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
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
     * Get deletion confirmation email HTML
     */
    private function get_deletion_email_html($display_name, $confirmation_link) {
        $site_name = get_bloginfo('name');
        
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Account Deletion</title>
</head>
<body style="margin: 0; padding: 0; background-color: #FAFBFD; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #FAFBFD; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">
                    <tr>
                        <td style="padding: 20px 0; text-align: center;">
                            <h1 style="margin: 0; font-size: 20px; color: #122B46; font-weight: 500;"><?php echo esc_html($site_name); ?></h1>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #FFFFFF; border: 1px solid #E7E9EC; border-radius: 4px;">
                                <tr>
                                    <td style="padding: 40px;">
                                        <p style="margin: 0 0 20px 0; font-size: 16px; color: #7F868F; line-height: 1.618;">Hi <?php echo esc_html($display_name); ?>,</p>
                                        
                                        <p style="margin: 0 0 30px 0; font-size: 16px; color: #7F868F; line-height: 1.618;">You have requested to delete your account. This action cannot be undone.</p>
                                        
                                        <div style="background-color: #FFF3CD; border: 1px solid #FFEAA7; border-radius: 4px; padding: 15px; margin: 20px 0;">
                                            <p style="margin: 0; font-size: 14px; color: #856404; font-weight: 500;">⚠️ Warning: This will permanently delete:</p>
                                            <ul style="margin: 10px 0 0 20px; padding: 0; color: #856404; font-size: 14px;">
                                                <li>Your user account and profile</li>
                                                <li>All your posts and comments</li>
                                                <li>Your course progress and certificates</li>
                                                <li>All associated data</li>
                                            </ul>
                                        </div>
                                        
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="center" style="padding: 20px 0 30px 0;">
                                                    <a href="<?php echo esc_url($confirmation_link); ?>" style="display: inline-block; font-size: 14px; line-height: 36px; padding: 0 25px; background-color: #DC3545; color: #FFFFFF; border-radius: 100px; text-decoration: none; font-weight: 500;">Confirm Account Deletion</a>
                                                </td>
                                            </tr>
                                        </table>
                                        
                                        <p style="margin: 0 0 20px 0; font-size: 16px; color: #7F868F; line-height: 1.618;">This confirmation link will expire in 24 hours.</p>
                                        
                                        <p style="margin: 0; font-size: 16px; color: #7F868F; line-height: 1.618;">If you didn't request this deletion, you can safely ignore this email.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
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
     * Handle confirmation links
     */
    public function handle_confirmation_links() {
        if (!isset($_GET['action']) || !isset($_GET['token']) || !isset($_GET['email'])) {
            return;
        }
        
        $action = sanitize_text_field($_GET['action']);
        $token = sanitize_text_field($_GET['token']);
        $email = sanitize_email($_GET['email']);
        
        switch ($action) {
            case 'download_export':
                $this->handle_download_export($token, $email);
                break;
            case 'confirm_deletion':
                $this->handle_confirm_deletion($token, $email);
                break;
        }
    }
    
    /**
     * Handle download export
     */
    private function handle_download_export($token, $email) {
        $hashed_token = wp_hash($token);
        $export_data = get_transient('data_export_' . $hashed_token);
        
        if (!$export_data || $export_data['email'] !== $email) {
            wp_die('Invalid or expired download link.', 'Download Error');
            return;
        }
        
        if (time() > $export_data['expiry']) {
            delete_transient('data_export_' . $hashed_token);
            wp_die('Download link has expired.', 'Download Error');
            return;
        }
        
        if (!file_exists($export_data['file_path'])) {
            wp_die('Export file not found.', 'Download Error');
            return;
        }
        
        // Send file download
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="user-data-export.json"');
        header('Content-Length: ' . filesize($export_data['file_path']));
        
        readfile($export_data['file_path']);
        
        // Clean up
        unlink($export_data['file_path']);
        delete_transient('data_export_' . $hashed_token);
        
        exit;
    }
    
    /**
     * Handle confirm deletion
     */
    private function handle_confirm_deletion($token, $email) {
        $hashed_token = wp_hash($token);
        $deletion_data = get_transient('account_deletion_' . $hashed_token);
        
        if (!$deletion_data || $deletion_data['email'] !== $email) {
            wp_die('Invalid or expired deletion link.', 'Deletion Error');
            return;
        }
        
        if (time() > $deletion_data['expiry']) {
            delete_transient('account_deletion_' . $hashed_token);
            wp_die('Deletion link has expired.', 'Deletion Error');
            return;
        }
        
        $user = get_user_by('ID', $deletion_data['user_id']);
        
        if (!$user) {
            wp_die('User not found.', 'Deletion Error');
            return;
        }
        
        // Send notification to support
        $this->notify_support_of_deletion($user);
        
        // Clean up the token
        delete_transient('account_deletion_' . $hashed_token);
        
        // Show confirmation message
        wp_die('
            <h1>Account Deletion Confirmed</h1>
            <p>Thank you for confirming your account deletion request.</p>
            <p>Our support team has been notified and will process your request shortly.</p>
            <p>You will receive a final confirmation email once your account has been deleted.</p>
            <p><a href="' . home_url() . '">Return to homepage</a></p>
        ', 'Account Deletion Confirmed');
    }
    
    /**
     * Notify support of confirmed deletion
     */
    private function notify_support_of_deletion($user) {
        $support_email = 'support@fearlessliving.org';
        $subject = 'Account Deletion Request Confirmed - ' . $user->display_name;
        
        $message = sprintf(
            "A user has confirmed their account deletion request.\n\n" .
            "User Details:\n" .
            "Name: %s\n" .
            "Email: %s\n" .
            "Username: %s\n" .
            "User ID: %d\n" .
            "Registration Date: %s\n\n" .
            "The user has already confirmed their choice to delete their account.\n" .
            "Please process this deletion request according to your standard procedures.\n\n" .
            "Timestamp: %s",
            $user->display_name,
            $user->user_email,
            $user->user_login,
            $user->ID,
            $user->user_registered,
            current_time('mysql')
        );
        
        $headers = array(
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        wp_mail($support_email, $subject, $message, $headers);
    }
}

// Initialize the handler
FearlessLiving_Other_Options_Handler::get_instance();