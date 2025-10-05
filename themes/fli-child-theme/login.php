<?php
/*
Template Name: Custom Login
*/

/**
 * Check if it's a login page.
 */
function rx_is_login_page() {
    return in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) );
}

$rx_custom_login = buddyboss_theme_get_option( 'boss_custom_login' );
if ( !$rx_custom_login ) {
    add_action( 'login_enqueue_scripts', 'rx_login_enqueue_scripts' );
}

// Always add our custom login styles
add_action( 'login_enqueue_scripts', 'rx_fearless_login_styles', 999 );

/**
 * Enqueue login scripts and styles.
 */
function rx_login_enqueue_scripts() {
    $rtl_css      = is_rtl() ? '-rtl' : '';
    $mincss       = buddyboss_theme_get_option( 'boss_minified_css' ) ? '.min' : '';

    // Enqueue necessary styles.
    if ( ! function_exists( 'buddypress' ) || ( function_exists( 'buddypress' ) && defined( 'BP_PLATFORM_VERSION' ) && version_compare( BP_PLATFORM_VERSION, '1.4.0', '<' ) ) ) {
        wp_enqueue_style( 'buddyboss-theme-icons-map', get_template_directory_uri() . '/assets/css/icons-map' . $mincss . '.css', '', buddyboss_theme()->version() );
        wp_enqueue_style( 'buddyboss-theme-icons', get_template_directory_uri() . '/assets/icons/css/bb-icons' . $mincss . '.css', '', buddyboss_theme()->version() );
    }

    wp_enqueue_style( 'buddyboss-theme-login', get_template_directory_uri() . '/assets/css' . $rtl_css . '/login' . $mincss . '.css', '', buddyboss_theme()->version() );
    wp_enqueue_style( 'buddyboss-theme-fonts', get_template_directory_uri() . '/assets/fonts/fonts.css', '', buddyboss_theme()->version() );
}

/**
 * Handle login redirection.
 */
add_filter( 'login_redirect', 'rx_redirect_previous_page', 10, 3 );

function rx_redirect_previous_page( $redirect_to, $request, $user ) {
    // Avoid redirect loops.
    $admin_url_info       = wp_parse_url( admin_url() );
    $redirect_to_url_info = wp_parse_url( $redirect_to );

    if ( isset( $admin_url_info['path'] ) && isset( $redirect_to_url_info['path'] ) && $redirect_to_url_info['path'] === $admin_url_info['path'] ) {
        $redirect_to = home_url(); // Redirect to homepage if attempting to access the admin area.
    }

    // Handle mobile app redirection.
    if ( ! is_user_logged_in() && wp_is_mobile() ) {
        $path = wp_parse_url( $request );
        if ( isset( $path['query'] ) && ! empty( $path['query'] ) ) {
            parse_str( $path['query'], $output );
            if ( isset( $output['redirect_to'] ) ) {
                return $output['redirect_to'];
            }
        }
    }

    return $redirect_to;
}

/**
 * Add nonce to login forms.
 */
function rx_add_login_nonce() {
    wp_nonce_field( 'rx_login_action', 'rx_login_nonce' );
}
add_action( 'login_form', 'rx_add_login_nonce' );

/**
 * Verify login nonce.
 */
function rx_verify_login_nonce() {
    if ( ! isset( $_POST['rx_login_nonce'] ) || ! wp_verify_nonce( $_POST['rx_login_nonce'], 'rx_login_action' ) ) {
        wp_die( __( 'Security check failed, please try again.', 'buddyboss-theme' ) );
    }
}
add_action( 'wp_authenticate', 'rx_verify_login_nonce', 1 );

/**
 * Handle custom register message.
 */
function rx_change_register_message( $message ) {
    $confirm_admin_email_page = false;
    if ( $GLOBALS['pagenow'] === 'wp-login.php' && ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] === 'confirm_admin_email' ) {
        $confirm_admin_email_page = true;
    }

    if ( strpos( $message, 'Register For This Site' ) !== false && $confirm_admin_email_page === false ) {
        $newMessage = __( 'Other Account Options', 'buddyboss-theme' );
        $login_url  = sprintf( '<a href="%s">%s</a>', esc_url( home_url( '/other-options' ) ), __( 'Other options', 'buddyboss-theme' ) );
        return '<div class="login-heading"><p class="message register bs-register-message">' . $newMessage . '</p><span>' . $login_url . '</span></div>';
    } else {
        return $message;
    }
}
add_filter( 'login_message', 'rx_change_register_message' );

/**
 * Add custom login page classes.
 */
function rx_custom_login_classes( $classes ) {
    $rx_custom_login = buddyboss_theme_get_option( 'boss_custom_login' );

    // BuddyBoss theme template class.
    $template_class = 'bb-template-v1';

    if ( $rx_custom_login ) {
        if ( ( $GLOBALS['pagenow'] === 'wp-login.php' ) ) {
            $classes[] = 'fearless-custom-login bb-login ' . $template_class;
        } else {
            $classes[] = 'bb-login ' . $template_class;
        }
    }

    return $classes;
}
add_filter( 'login_body_class', 'rx_custom_login_classes' );
function alt_auto_login_var( $var ) {
    return 'contactId';
}
add_filter( 'wpf_auto_login_query_var', 'alt_auto_login_var' );


/**
 * Add custom forget password link.
 */
function rx_login_custom_form() {
    $rx_custom_login = buddyboss_theme_get_option( 'boss_custom_login' );

    if ( $rx_custom_login ) {
        ?>
        <p class="lostmenot"><a href="<?php echo wp_lostpassword_url(); ?>"><?php esc_html_e('Forgot Password?', 'buddyboss-theme'); ?></a></p>
        <?php
    }
}
add_action( 'login_form', 'rx_login_custom_form' );

/**
 * Custom login form scripts.
 */
function rx_login_scripts() {
    ?>
    <script>
        jQuery(document).ready(function() {
            jQuery('#loginform').append('<input type="hidden" name="rx_custom_field" value="custom_value">'); // Example custom field.
        });
    </script>
    <?php
}
add_action( 'login_head', 'rx_login_scripts', 150 );

/**
 * Add custom login styles
 */
function rx_custom_login_styles() {
    $theme_url = get_stylesheet_directory_uri();
    ?>
    <style>
        /* Remove default split layout */
        body.fearless-custom-login {
            background: none !important;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }
        
        /* Full screen background for desktop */
        body.fearless-custom-login::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('<?php echo $theme_url; ?>/FYM-Login-Desktop.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            z-index: -1;
        }
        
        /* Remove default BuddyBoss split layout */
        body.fearless-custom-login .login-split {
            display: none !important;
        }
        
        /* Center login container */
        body.fearless-custom-login #login {
            width: 100%;
            max-width: 400px;
            margin: 0;
            padding: 20px;
            position: relative;
            z-index: 10;
        }
        
        /* Style login form to appear within the white board */
        body.fearless-custom-login #loginform,
        body.fearless-custom-login #registerform,
        body.fearless-custom-login #lostpasswordform {
            background: transparent;
            border: none;
            box-shadow: none;
            padding: 40px;
            margin-top: 15vh; /* Position form in the white board area */
            margin-left: 10%;
        }
        
        /* Hide background for form elements */
        body.fearless-custom-login .login-heading,
        body.fearless-custom-login form {
            background: transparent !important;
        }
        
        /* Style form inputs */
        body.fearless-custom-login input[type="text"],
        body.fearless-custom-login input[type="password"],
        body.fearless-custom-login input[type="email"] {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid #ddd;
            padding: 10px 15px;
            font-size: 16px;
            width: 100%;
            margin-bottom: 15px;
        }
        
        /* Style submit button */
        body.fearless-custom-login .button-primary,
        body.fearless-custom-login input[type="submit"] {
            background: #5A7891;
            border: none;
            color: white;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        body.fearless-custom-login .button-primary:hover,
        body.fearless-custom-login input[type="submit"]:hover {
            background: #486478;
        }
        
        /* Style links */
        body.fearless-custom-login a {
            color: #5A7891;
            text-decoration: none;
        }
        
        body.fearless-custom-login a:hover {
            text-decoration: underline;
        }
        
        /* Mobile styles */
        @media (max-width: 768px) {
            body.fearless-custom-login::before {
                background-image: url('<?php echo $theme_url; ?>/rhonda-mobile-login.png');
            }
            
            body.fearless-custom-login #loginform,
            body.fearless-custom-login #registerform,
            body.fearless-custom-login #lostpasswordform {
                margin-top: 25vh; /* Adjust for mobile white board position */
                margin-left: 0;
                padding: 20px;
            }
            
            body.fearless-custom-login #login {
                padding: 10px;
            }
        }
        
        /* Hide WordPress logo */
        body.fearless-custom-login h1 {
            display: none;
        }
        
        /* Style error messages */
        body.fearless-custom-login .message,
        body.fearless-custom-login #login_error {
            background: rgba(255, 255, 255, 0.95);
            border-left: 4px solid #5A7891;
            padding: 12px;
            margin-bottom: 20px;
        }
        
        /* Additional adjustments for form positioning */
        @media (min-width: 1200px) {
            body.fearless-custom-login #loginform,
            body.fearless-custom-login #registerform,
            body.fearless-custom-login #lostpasswordform {
                margin-left: 8%;
                max-width: 350px;
            }
        }
        
        /* Fine-tune mobile positioning */
        @media (max-width: 480px) {
            body.fearless-custom-login #loginform,
            body.fearless-custom-login #registerform,
            body.fearless-custom-login #lostpasswordform {
                margin-top: 30vh;
            }
        }
    </style>
    <?php
}
add_action( 'login_head', 'rx_custom_login_styles', 20 );

/**
 * Force load custom Fearless login styles
 */
function rx_fearless_login_styles() {
    $theme_url = get_stylesheet_directory_uri();
    ?>
    <style>
        /* Force remove default split layout */
        body.login,
        body.login-split-page {
            background: none !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-height: 100vh !important;
            position: relative !important;
            overflow: hidden !important;
        }
        
        /* Full screen background for desktop */
        body.login::before {
            content: '' !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background-image: url('<?php echo $theme_url; ?>/FYM-Login-Desktop.jpg') !important;
            background-size: cover !important;
            background-position: center !important;
            background-repeat: no-repeat !important;
            z-index: -1 !important;
        }
        
        /* Remove ALL BuddyBoss split layouts */
        .login-split,
        .bb-login-section,
        .bb-login-right-section {
            display: none !important;
        }
        
        /* Override BuddyBoss container styles */
        .bb-login .login-form-wrap,
        .login .login-form-wrap {
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
            max-width: none !important;
        }
        
        /* Center login container */
        #login {
            width: 100% !important;
            max-width: 400px !important;
            margin: 0 !important;
            padding: 20px !important;
            position: relative !important;
            z-index: 10 !important;
        }
        
        /* Style login form to appear within the white board */
        #loginform,
        #registerform,
        #lostpasswordform {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            padding: 40px !important;
            margin-top: 15vh !important;
            margin-left: 10% !important;
        }
        
        /* Hide background for form elements */
        .login-heading,
        form,
        .login form {
            background: transparent !important;
        }
        
        /* Style form inputs */
        .login input[type="text"],
        .login input[type="password"],
        .login input[type="email"] {
            background: rgba(255, 255, 255, 0.9) !important;
            border: 1px solid #ddd !important;
            padding: 10px 15px !important;
            font-size: 16px !important;
            width: 100% !important;
            margin-bottom: 15px !important;
            box-shadow: none !important;
        }
        
        /* Style submit button */
        .login .button-primary,
        .login input[type="submit"] {
            background: #5A7891 !important;
            border: none !important;
            color: white !important;
            padding: 12px 30px !important;
            font-size: 16px !important;
            font-weight: 600 !important;
            width: 100% !important;
            cursor: pointer !important;
            transition: background 0.3s !important;
            box-shadow: none !important;
        }
        
        .login .button-primary:hover,
        .login input[type="submit"]:hover {
            background: #486478 !important;
        }
        
        /* Style links */
        .login a {
            color: #5A7891 !important;
            text-decoration: none !important;
        }
        
        .login a:hover {
            text-decoration: underline !important;
        }
        
        /* Mobile styles */
        @media (max-width: 768px) {
            body.login::before {
                background-image: url('<?php echo $theme_url; ?>/rhonda-mobile-login.png') !important;
            }
            
            #loginform,
            #registerform,
            #lostpasswordform {
                margin-top: 25vh !important;
                margin-left: 0 !important;
                padding: 20px !important;
            }
            
            #login {
                padding: 10px !important;
            }
        }
        
        /* Hide WordPress logo */
        .login h1,
        #login h1 {
            display: none !important;
        }
        
        /* Style error messages */
        .login .message,
        .login #login_error {
            background: rgba(255, 255, 255, 0.95) !important;
            border-left: 4px solid #5A7891 !important;
            padding: 12px !important;
            margin-bottom: 20px !important;
        }
        
        /* Additional adjustments for form positioning */
        @media (min-width: 1200px) {
            #loginform,
            #registerform,
            #lostpasswordform {
                margin-left: 8% !important;
                max-width: 350px !important;
            }
        }
        
        /* Fine-tune mobile positioning */
        @media (max-width: 480px) {
            #loginform,
            #registerform,
            #lostpasswordform {
                margin-top: 30vh !important;
            }
        }
        
        /* Force hide any BuddyBoss theme elements */
        .bb-login-subtitle,
        .bb-login-footer,
        .login-split-part,
        .login-split-right {
            display: none !important;
        }
    </style>
    <?php
}