<?php
/**
 * Fearless Ambassador Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class FYS_Ambassador_Dashboard {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('fys_ambassador_dashboard', array($this, 'render_dashboard'));
    }

    public function render_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view the ambassador dashboard.', 'fearless-you-systems') . '</p>';
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        if (!in_array('fearless_ambassador', $user->roles)) {
            return '<p>' . __('This dashboard is only available to Fearless Ambassadors.', 'fearless-you-systems') . '</p>';
        }

        ob_start();
        ?>
        <div class="fys-ambassador-dashboard">
            <div class="fys-dashboard-header">
                <h2><?php _e('Ambassador Dashboard', 'fearless-you-systems'); ?></h2>
                <p class="fys-ambassador-status"><?php echo esc_html($user->display_name); ?> - <?php _e('Fearless Ambassador', 'fearless-you-systems'); ?></p>
            </div>

            <div class="fys-dashboard-content">
                <div class="fys-ambassador-stats">
                    <h3><?php _e('Your Impact', 'fearless-you-systems'); ?></h3>
                    <div class="fys-stats-grid">
                        <div class="fys-stat-card">
                            <span class="fys-stat-label"><?php _e('Referrals', 'fearless-you-systems'); ?></span>
                            <span class="fys-stat-value"><?php echo $this->get_referral_count($user_id); ?></span>
                        </div>
                        <div class="fys-stat-card">
                            <span class="fys-stat-label"><?php _e('Active Members', 'fearless-you-systems'); ?></span>
                            <span class="fys-stat-value"><?php echo $this->get_active_referrals($user_id); ?></span>
                        </div>
                    </div>
                </div>

                <div class="fys-ambassador-tools">
                    <h3><?php _e('Ambassador Resources', 'fearless-you-systems'); ?></h3>
                    <div class="fys-resources-grid">
                        <a href="/ambassador-resources/promotional-materials/" class="fys-resource-card">
                            <span class="dashicons dashicons-megaphone"></span>
                            <span><?php _e('Promotional Materials', 'fearless-you-systems'); ?></span>
                        </a>
                        <a href="/ambassador-resources/referral-link/" class="fys-resource-card">
                            <span class="dashicons dashicons-admin-links"></span>
                            <span><?php _e('Your Referral Link', 'fearless-you-systems'); ?></span>
                        </a>
                        <a href="/ambassador-resources/training/" class="fys-resource-card">
                            <span class="dashicons dashicons-welcome-learn-more"></span>
                            <span><?php _e('Ambassador Training', 'fearless-you-systems'); ?></span>
                        </a>
                        <a href="/ambassador-resources/social-media/" class="fys-resource-card">
                            <span class="dashicons dashicons-share"></span>
                            <span><?php _e('Social Media Kit', 'fearless-you-systems'); ?></span>
                        </a>
                    </div>
                </div>

                <div class="fys-referral-link">
                    <h3><?php _e('Your Unique Referral Link', 'fearless-you-systems'); ?></h3>
                    <div class="fys-link-box">
                        <input type="text"
                               readonly
                               value="<?php echo $this->get_referral_link($user_id); ?>"
                               id="fys-referral-link"
                               class="fys-referral-input">
                        <button onclick="copyReferralLink()" class="button"><?php _e('Copy Link', 'fearless-you-systems'); ?></button>
                    </div>
                </div>

                <div class="fys-ambassador-community">
                    <h3><?php _e('Ambassador Community', 'fearless-you-systems'); ?></h3>
                    <p><?php _e('Connect with other Fearless Ambassadors and share best practices.', 'fearless-you-systems'); ?></p>
                    <a href="/groups/ambassadors/" class="button button-primary"><?php _e('Visit Ambassador Forum', 'fearless-you-systems'); ?></a>
                </div>
            </div>
        </div>

        <script>
        function copyReferralLink() {
            var copyText = document.getElementById("fys-referral-link");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            document.execCommand("copy");
            alert("Referral link copied to clipboard!");
        }
        </script>
        <?php
        return ob_get_clean();
    }

    private function get_referral_count($user_id) {
        // This would integrate with a referral tracking system
        return get_user_meta($user_id, 'fys_total_referrals', true) ?: 0;
    }

    private function get_active_referrals($user_id) {
        // This would check for active referred members
        return get_user_meta($user_id, 'fys_active_referrals', true) ?: 0;
    }

    private function get_referral_link($user_id) {
        $user = get_userdata($user_id);
        return home_url('/join/?ref=' . $user->user_login);
    }
}