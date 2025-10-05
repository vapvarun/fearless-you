<?php
/**
 * Role-based logo display for LCCP roles
 * Shows LCCP logo for PCs, Mentors, and Big Birds
 */

// Filter the logo based on user role
add_filter('get_custom_logo', 'fli_role_based_logo', 10, 2);
add_filter('buddyboss_logo_url', 'fli_role_based_logo_url', 10);
add_filter('theme_mod_custom_logo', 'fli_role_based_logo_id', 10);

/**
 * Get LCCP logo URL
 */
function fli_get_lccp_logo_url() {
    return 'https://you.fearlessliving.org/wp-content/uploads/2025/09/LCCP_lt.svg';
}

/**
 * Check if current user should see LCCP logo
 */
function fli_should_show_lccp_logo() {
    if (!is_user_logged_in()) {
        return false;
    }
    
    $current_user = wp_get_current_user();
    $lccp_roles = ['pc', 'mentor', 'big_bird'];
    
    foreach ($lccp_roles as $role) {
        if (in_array($role, $current_user->roles)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Filter logo URL for LCCP roles
 */
function fli_role_based_logo_url($logo_url) {
    if (fli_should_show_lccp_logo()) {
        return fli_get_lccp_logo_url();
    }
    return $logo_url;
}

/**
 * Filter logo ID for LCCP roles
 */
function fli_role_based_logo_id($logo_id) {
    if (fli_should_show_lccp_logo()) {
        // Try to get the attachment ID for the LCCP logo
        global $wpdb;
        $lccp_logo_url = fli_get_lccp_logo_url();
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM $wpdb->posts WHERE guid = %s",
            $lccp_logo_url
        ));
        
        if ($attachment_id) {
            return $attachment_id;
        }
    }
    return $logo_id;
}

/**
 * Add custom CSS to properly display SVG logo
 */
add_action('wp_head', 'fli_lccp_logo_styles');
function fli_lccp_logo_styles() {
    if (fli_should_show_lccp_logo()) {
        ?>
        <style>
            /* LCCP Logo Styles */
            .site-logo img,
            .bb-buddypanel-logo img,
            .site-header .site-logo img,
            .buddypanel .bb-buddypanel-logo img {
                max-height: 50px !important;
                width: auto !important;
            }
            
            /* Override for SVG display */
            .site-logo img[src$=".svg"],
            .bb-buddypanel-logo img[src$=".svg"] {
                height: 50px !important;
                width: auto !important;
            }
        </style>
        <?php
    }
}

/**
 * Filter BuddyPanel logo specifically
 */
add_filter('buddyboss_buddypanel_logo', 'fli_buddypanel_lccp_logo');
function fli_buddypanel_lccp_logo($logo_html) {
    if (fli_should_show_lccp_logo()) {
        $lccp_logo_url = fli_get_lccp_logo_url();
        $logo_html = '<img src="' . esc_url($lccp_logo_url) . '" alt="LCCP" class="lccp-logo" />';
    }
    return $logo_html;
}

/**
 * JavaScript to replace logo dynamically
 */
add_action('wp_footer', 'fli_role_based_logo_js');
function fli_role_based_logo_js() {
    if (fli_should_show_lccp_logo()) {
        ?>
        <script>
        (function($) {
            $(document).ready(function() {
                // Replace all logo instances with LCCP logo
                var lccpLogoUrl = '<?php echo esc_js(fli_get_lccp_logo_url()); ?>';
                
                // Site header logo
                $('.site-logo img, .site-header .site-logo img').attr('src', lccpLogoUrl).attr('alt', 'LCCP');
                
                // BuddyPanel logo
                $('.bb-buddypanel-logo img, .buddypanel .bb-buddypanel-logo img').attr('src', lccpLogoUrl).attr('alt', 'LCCP');
                
                // Mobile logo
                $('.bb-mobile-header .site-logo img').attr('src', lccpLogoUrl).attr('alt', 'LCCP');
                
                // Any other logo instances
                $('img[class*="logo"]').each(function() {
                    if (!$(this).hasClass('lccp-logo')) {
                        $(this).attr('src', lccpLogoUrl).attr('alt', 'LCCP').addClass('lccp-logo');
                    }
                });
            });
        })(jQuery);
        </script>
        <?php
    }
}