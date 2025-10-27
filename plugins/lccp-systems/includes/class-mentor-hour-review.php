<?php
/**
 * LCCP Mentor Hour Review System
 * Handles mentor review of student hour submissions with notification bubbles
 */

if (!defined('ABSPATH')) {
    exit;
}

class LCCP_Mentor_Hour_Review {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Shortcodes
        add_shortcode('lccp_mentor_hour_reviews', array($this, 'render_review_dashboard'));
        add_shortcode('lccp_hour_notification_bubble', array($this, 'render_notification_bubble'));

        // AJAX handlers
        add_action('wp_ajax_lccp_approve_hours', array($this, 'ajax_approve_hours'));
        add_action('wp_ajax_lccp_request_revision', array($this, 'ajax_request_revision'));
        add_action('wp_ajax_lccp_get_pending_count', array($this, 'ajax_get_pending_count'));
        add_action('wp_ajax_lccp_play_audio', array($this, 'ajax_get_audio_player'));

        // Add notification bubble to admin bar
        add_action('admin_bar_menu', array($this, 'add_notification_to_admin_bar'), 100);

        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function enqueue_assets() {
        wp_enqueue_style('lccp-mentor-review',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/mentor-review.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script('lccp-mentor-review',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/mentor-review.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('lccp-mentor-review', 'lccp_mentor_review', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lccp_mentor_review')
        ));
    }

    public function render_notification_bubble($atts) {
        $user_id = get_current_user_id();
        if (!$this->user_can_review_hours($user_id)) {
            return '';
        }

        $count = $this->get_pending_review_count($user_id);
        if ($count == 0) {
            return '';
        }

        return sprintf(
            '<span class="lccp-notification-bubble">%d</span>',
            $count
        );
    }

    public function render_review_dashboard($atts) {
        $user_id = get_current_user_id();
        if (!$this->user_can_review_hours($user_id)) {
            return '<p>You do not have permission to review hour submissions.</p>';
        }

        $pending_submissions = $this->get_pending_submissions($user_id);

        ob_start();
        ?>
        <div class="lccp-mentor-review-dashboard">
            <h2>
                Hour Submissions for Review
                <?php echo $this->render_notification_bubble(array()); ?>
            </h2>

            <?php if (empty($pending_submissions)): ?>
                <div class="lccp-no-submissions">
                    <p>No pending hour submissions to review.</p>
                </div>
            <?php else: ?>
                <div class="lccp-submissions-list">
                    <?php foreach ($pending_submissions as $submission): ?>
                        <div class="lccp-submission-card" data-submission-id="<?php echo $submission->id; ?>">
                            <div class="lccp-submission-header">
                                <div class="lccp-student-info">
                                    <strong><?php echo esc_html($submission->student_name); ?></strong>
                                    <span class="lccp-notification-indicator">NEW</span>
                                </div>
                                <div class="lccp-submission-date">
                                    <?php echo date('M j, Y', strtotime($submission->submitted_at)); ?>
                                </div>
                            </div>

                            <div class="lccp-submission-details">
                                <div class="lccp-detail-row">
                                    <label>Session Date:</label>
                                    <span><?php echo date('M j, Y', strtotime($submission->session_date)); ?></span>
                                </div>
                                <div class="lccp-detail-row">
                                    <label>Hours:</label>
                                    <span class="lccp-hours-count"><?php echo number_format($submission->hours, 1); ?></span>
                                </div>
                                <div class="lccp-detail-row">
                                    <label>Type:</label>
                                    <span><?php echo esc_html($submission->session_type); ?></span>
                                </div>
                                <div class="lccp-detail-row">
                                    <label>Description:</label>
                                    <p><?php echo esc_html($submission->description); ?></p>
                                </div>

                                <?php if ($submission->audio_file_url): ?>
                                <div class="lccp-audio-section">
                                    <label>Audio Recording:</label>
                                    <div class="lccp-audio-player">
                                        <audio controls>
                                            <source src="<?php echo esc_url($submission->audio_file_url); ?>" type="audio/mpeg">
                                            Your browser does not support the audio element.
                                        </audio>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="lccp-mentor-notes">
                                    <label>Mentor Notes (optional):</label>
                                    <textarea class="lccp-notes-input" placeholder="Add notes for the student..."></textarea>
                                </div>

                                <div class="lccp-hour-adjustment">
                                    <label>Adjust Hours (if needed):</label>
                                    <input type="number"
                                           class="lccp-hours-input"
                                           value="<?php echo $submission->hours; ?>"
                                           min="0"
                                           max="100"
                                           step="0.5">
                                </div>
                            </div>

                            <div class="lccp-submission-actions">
                                <button class="lccp-approve-btn" data-submission-id="<?php echo $submission->id; ?>">
                                    ✓ Approve Hours
                                </button>
                                <button class="lccp-revision-btn" data-submission-id="<?php echo $submission->id; ?>">
                                    ↩ Request Revision
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <style>
        .lccp-mentor-review-dashboard {
            max-width: 900px;
            margin: 20px 0;
        }

        .lccp-notification-bubble {
            display: inline-block;
            background: #ff4444;
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .lccp-submission-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .lccp-submission-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            margin-bottom: 15px;
        }

        .lccp-student-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .lccp-notification-indicator {
            background: #4CAF50;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }

        .lccp-detail-row {
            margin-bottom: 12px;
        }

        .lccp-detail-row label {
            font-weight: 600;
            display: inline-block;
            width: 120px;
            color: #555;
        }

        .lccp-hours-count {
            font-size: 1.2em;
            font-weight: bold;
            color: #2271b1;
        }

        .lccp-audio-section {
            margin: 20px 0;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
        }

        .lccp-audio-player audio {
            width: 100%;
            max-width: 400px;
        }

        .lccp-mentor-notes textarea,
        .lccp-hours-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 5px;
        }

        .lccp-mentor-notes textarea {
            min-height: 80px;
            resize: vertical;
        }

        .lccp-hours-input {
            max-width: 100px;
        }

        .lccp-submission-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .lccp-approve-btn,
        .lccp-revision-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .lccp-approve-btn {
            background: #4CAF50;
            color: white;
        }

        .lccp-approve-btn:hover {
            background: #45a049;
        }

        .lccp-revision-btn {
            background: #ff9800;
            color: white;
        }

        .lccp-revision-btn:hover {
            background: #e68900;
        }

        .lccp-no-submissions {
            text-align: center;
            padding: 40px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        </style>
        <?php
        return ob_get_clean();
    }

    private function user_can_review_hours($user_id) {
        $user = get_userdata($user_id);
        if (!$user) return false;

        $allowed_roles = array('administrator', 'lccp_mentor', 'lccp_big_bird', 'lccp_program_coordinator');
        return !empty(array_intersect($allowed_roles, $user->roles));
    }

    private function get_pending_review_count($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_submissions';

        // For mentors, only show their assigned students
        $user = get_userdata($user_id);
        if (in_array('lccp_mentor', $user->roles)) {
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE mentor_id = %d AND status = 'pending'",
                $user_id
            );
        } else {
            // For PC and BigBird, show all pending
            $query = "SELECT COUNT(*) FROM $table_name WHERE status = 'pending'";
        }

        return $wpdb->get_var($query);
    }

    private function get_pending_submissions($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_submissions';

        $user = get_userdata($user_id);
        if (in_array('lccp_mentor', $user->roles)) {
            $query = $wpdb->prepare(
                "SELECT h.*, u.display_name as student_name
                 FROM $table_name h
                 JOIN {$wpdb->users} u ON h.student_id = u.ID
                 WHERE h.mentor_id = %d AND h.status = 'pending'
                 ORDER BY h.submitted_at DESC",
                $user_id
            );
        } else {
            $query = "SELECT h.*, u.display_name as student_name
                     FROM $table_name h
                     JOIN {$wpdb->users} u ON h.student_id = u.ID
                     WHERE h.status = 'pending'
                     ORDER BY h.submitted_at DESC";
        }

        return $wpdb->get_results($query);
    }

    public function ajax_approve_hours() {
        check_ajax_referer('lccp_mentor_review', 'nonce');

        $submission_id = intval($_POST['submission_id']);
        $adjusted_hours = floatval($_POST['adjusted_hours']);
        $notes = sanitize_textarea_field($_POST['notes']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_submissions';

        $updated = $wpdb->update(
            $table_name,
            array(
                'status' => 'approved',
                'hours' => $adjusted_hours,
                'mentor_notes' => $notes,
                'reviewed_at' => current_time('mysql')
            ),
            array('id' => $submission_id),
            array('%s', '%f', '%s', '%s'),
            array('%d')
        );

        if ($updated) {
            // Send notification to student
            $submission = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $submission_id
            ));

            $this->send_approval_notification($submission);

            wp_send_json_success(array('message' => 'Hours approved successfully'));
        } else {
            wp_send_json_error('Failed to approve hours');
        }
    }

    public function ajax_request_revision() {
        check_ajax_referer('lccp_mentor_review', 'nonce');

        $submission_id = intval($_POST['submission_id']);
        $notes = sanitize_textarea_field($_POST['notes']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'lccp_hour_submissions';

        $updated = $wpdb->update(
            $table_name,
            array(
                'status' => 'revision_requested',
                'mentor_notes' => $notes,
                'reviewed_at' => current_time('mysql')
            ),
            array('id' => $submission_id),
            array('%s', '%s', '%s'),
            array('%d')
        );

        if ($updated) {
            // Send notification to student
            $submission = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $submission_id
            ));

            $this->send_revision_notification($submission);

            wp_send_json_success(array('message' => 'Revision request sent'));
        } else {
            wp_send_json_error('Failed to request revision');
        }
    }

    private function send_approval_notification($submission) {
        $student = get_userdata($submission->student_id);
        if (!$student) return;

        $subject = 'Your coaching hours have been approved';
        $message = sprintf(
            "Hi %s,\n\nYour %s hours for %s have been approved.\n\n",
            $student->display_name,
            $submission->hours,
            date('M j, Y', strtotime($submission->session_date))
        );

        if ($submission->mentor_notes) {
            $message .= "Mentor notes: " . $submission->mentor_notes . "\n\n";
        }

        wp_mail($student->user_email, $subject, $message);
    }

    private function send_revision_notification($submission) {
        $student = get_userdata($submission->student_id);
        if (!$student) return;

        $subject = 'Your hour submission needs revision';
        $message = sprintf(
            "Hi %s,\n\nYour hour submission for %s requires revision.\n\n",
            $student->display_name,
            date('M j, Y', strtotime($submission->session_date))
        );

        if ($submission->mentor_notes) {
            $message .= "Mentor notes: " . $submission->mentor_notes . "\n\n";
        }

        $message .= "Please review and resubmit your hours.\n";

        wp_mail($student->user_email, $subject, $message);
    }

    public function add_notification_to_admin_bar($wp_admin_bar) {
        $user_id = get_current_user_id();
        if (!$this->user_can_review_hours($user_id)) {
            return;
        }

        $count = $this->get_pending_review_count($user_id);
        if ($count > 0) {
            $title = sprintf('Hour Reviews <span class="lccp-admin-bar-bubble">%d</span>', $count);

            $wp_admin_bar->add_node(array(
                'id' => 'lccp-hour-reviews',
                'title' => $title,
                'href' => home_url('/mentor-dashboard/'),
                'meta' => array(
                    'class' => 'lccp-hour-reviews-menu'
                )
            ));
        }
    }
}

// Initialize
LCCP_Mentor_Hour_Review::get_instance();