<?php
/**
 * Fearless You Member Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class FYS_Member_Dashboard {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('fys_member_dashboard', array($this, 'render_dashboard'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function render_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your member dashboard.', 'fearless-you-systems') . '</p>';
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        if (!in_array('fearless_you_member', $user->roles)) {
            return '<p>' . __('This dashboard is only available to Fearless You Members.', 'fearless-you-systems') . '</p>';
        }

        ob_start();
        ?>
        <div class="fys-member-dashboard">
            <div class="fys-dashboard-header">
                <h2><?php _e('Welcome back,', 'fearless-you-systems'); ?> <?php echo esc_html($user->display_name); ?>!</h2>
                <p class="fys-member-status"><?php _e('Fearless You Member', 'fearless-you-systems'); ?></p>
            </div>

            <div class="fys-dashboard-content">
                <div class="fys-quick-links">
                    <h3><?php _e('Quick Links', 'fearless-you-systems'); ?></h3>
                    <div class="fys-links-grid">
                        <a href="/courses/" class="fys-link-card">
                            <span class="dashicons dashicons-welcome-learn-more"></span>
                            <span><?php _e('My Courses', 'fearless-you-systems'); ?></span>
                        </a>
                        <a href="/monthly-trainings/" class="fys-link-card">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <span><?php _e('Monthly Trainings', 'fearless-you-systems'); ?></span>
                        </a>
                        <a href="/community/" class="fys-link-card">
                            <span class="dashicons dashicons-groups"></span>
                            <span><?php _e('Community', 'fearless-you-systems'); ?></span>
                        </a>
                        <a href="/resources/" class="fys-link-card">
                            <span class="dashicons dashicons-media-document"></span>
                            <span><?php _e('Resources', 'fearless-you-systems'); ?></span>
                        </a>
                    </div>
                </div>

                <div class="fys-membership-info">
                    <h3><?php _e('Your Membership', 'fearless-you-systems'); ?></h3>
                    <div class="fys-info-card">
                        <p><strong><?php _e('Member Since:', 'fearless-you-systems'); ?></strong>
                           <?php echo date('F j, Y', strtotime($user->user_registered)); ?></p>
                        <p><strong><?php _e('Membership Status:', 'fearless-you-systems'); ?></strong>
                           <span class="fys-status-active"><?php _e('Active', 'fearless-you-systems'); ?></span></p>
                    </div>
                </div>

                <div class="fys-recent-activity">
                    <h3><?php _e('Recent Activity', 'fearless-you-systems'); ?></h3>
                    <?php echo do_shortcode('[ld_profile]'); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function enqueue_assets() {
        if (has_shortcode(get_the_content(), 'fys_member_dashboard')) {
            wp_enqueue_style(
                'fys-member-dashboard',
                FYS_PLUGIN_URL . 'assets/css/member-dashboard.css',
                array(),
                FYS_VERSION
            );
        }
    }
}