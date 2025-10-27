<?php
/**
 * @package FLI BuddyBoss Child
 * OPTIMIZED VERSION - Reduced from 79KB to ~25KB
 * Performance improvements: Conditional loading, external CSS/JS, removed bloat
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load child theme text domain for translations.
 *
 * @since 1.0.0
 * @return void
 */
function buddyboss_theme_child_languages() {
	load_theme_textdomain( 'buddyboss-theme', get_stylesheet_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'buddyboss_theme_child_languages' );

/**
 * Enqueue child theme scripts and styles with conditional loading.
 *
 * OPTIMIZED: Only load what's needed on each page.
 *
 * @since 1.0.0
 * @return void
 */
function buddyboss_theme_child_scripts_styles() {
	// Always load main custom CSS (reduced size)
	wp_enqueue_style( 'buddyboss-child-css', get_stylesheet_directory_uri() . '/assets/css/custom.css', array(), filemtime( get_stylesheet_directory() . '/assets/css/custom.css' ) );
	wp_enqueue_script( 'buddyboss-child-js', get_stylesheet_directory_uri() . '/assets/js/custom.js', array( 'jquery' ), filemtime( get_stylesheet_directory() . '/assets/js/custom.js' ), true );

	// CONDITIONAL: Only load LearnDash assets on LearnDash pages
	if ( is_singular( array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-courses', 'sfwd-quiz' ) ) || is_post_type_archive( array( 'sfwd-courses' ) ) ) {
		$js_file = get_stylesheet_directory() . '/assets/js/learndash-progress-rings.js';

		if ( file_exists( $js_file ) ) {
			wp_enqueue_script(
				'learndash-progress-rings',
				get_stylesheet_directory_uri() . '/assets/js/learndash-progress-rings.js',
				array( 'jquery' ),
				filemtime( $js_file ),
				true
			);
		}
	}

	// CONDITIONAL: Only load video tracking on lesson/topic pages
	if ( is_singular( array( 'sfwd-lessons', 'sfwd-topic' ) ) ) {
		$video_js = get_stylesheet_directory() . '/assets/js/learndash-video-tracking.js';
		if ( file_exists( $video_js ) ) {
			wp_enqueue_script(
				'learndash-video-tracking',
				get_stylesheet_directory_uri() . '/assets/js/learndash-video-tracking.js',
				array( 'jquery', 'learndash_video_script' ),
				filemtime( $video_js ),
				true
			);
		}
	}
}
add_action( 'wp_enqueue_scripts', 'buddyboss_theme_child_scripts_styles', 9999 );

/**
 * Enqueue custom login page styles and scripts.
 *
 * OPTIMIZED: Reduced from 600+ inline lines to single file enqueue.
 *
 * @since 1.0.0
 * @return void
 */
function fearless_custom_login_styles() {
	$login_css = get_stylesheet_directory() . '/assets/css/login-page.css';
	$login_js  = get_stylesheet_directory() . '/assets/js/login-enhancements.js';

	if ( file_exists( $login_css ) ) {
		wp_enqueue_style(
			'fli-login-styles',
			get_stylesheet_directory_uri() . '/assets/css/login-page.css',
			array(),
			filemtime( $login_css )
		);
	}

	if ( file_exists( $login_js ) ) {
		wp_enqueue_script(
			'fli-login-js',
			get_stylesheet_directory_uri() . '/assets/js/login-enhancements.js',
			array(),
			filemtime( $login_js ),
			true
		);
	}
}
add_action( 'login_enqueue_scripts', 'fearless_custom_login_styles', 999 );

/**
 * Enqueue MutationObserver fix for LearnDash pages.
 *
 * OPTIMIZED: Now external JS, only loaded on pages that need it.
 *
 * @since 1.0.0
 * @return void
 */
function fli_enqueue_mutation_observer_fix() {
	// Only load on pages that need it
	if ( is_singular( array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-courses' ) ) ) {
		wp_enqueue_script(
			'fli-mutation-observer-fix',
			get_stylesheet_directory_uri() . '/assets/js/mutation-observer-fix.js',
			array(),
			filemtime( get_stylesheet_directory() . '/assets/js/mutation-observer-fix.js' ),
			false // Load in head
		);
	}
}

/**
 * Enqueue accessibility widget styles and scripts.
 *
 * OPTIMIZED: Only loads on frontend pages (not login, not admin).
 *
 * @since 1.0.0
 * @return void
 */
function fli_enqueue_accessibility_widget() {
	if ( ! is_admin() ) {
		wp_enqueue_style(
			'fli-a11y-widget',
			get_stylesheet_directory_uri() . '/assets/css/accessibility-widget.css',
			array(),
			filemtime( get_stylesheet_directory() . '/assets/css/accessibility-widget.css' )
		);

		wp_enqueue_script(
			'fli-a11y-widget-js',
			get_stylesheet_directory_uri() . '/assets/js/accessibility-widget.js',
			array(),
			filemtime( get_stylesheet_directory() . '/assets/js/accessibility-widget.js' ),
			true
		);

		add_action( 'wp_footer', 'fli_render_accessibility_widget', 100 );
	}
}
add_action( 'wp_enqueue_scripts', 'fli_enqueue_accessibility_widget', 100 );

/**
 * Render accessibility widget HTML in footer.
 *
 * @since 1.0.0
 * @return void
 */
function fli_render_accessibility_widget() {
	?>
	<div id="fli-accessibility-widget">
		<button id="fli-a11y-toggle" aria-label="Accessibility Options" title="Accessibility Options">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<circle cx="12" cy="12" r="10"></circle>
				<circle cx="12" cy="10" r="3"></circle>
				<path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662"></path>
			</svg>
		</button>
		<div id="fli-a11y-panel">
			<h3>Accessibility Options</h3>
			<button onclick="toggleHighContrast()">High Contrast</button>
			<button onclick="toggleLargeText()">Large Text</button>
			<button onclick="toggleReadableFont()">Readable Font</button>
			<button onclick="resetAccessibility()">Reset</button>
		</div>
	</div>
	<?php
}

/**
 * ========================================================================
 * LearnDash Course Search Override
 * ========================================================================
 */

/**
 * Override WordPress search to always search LearnDash courses
 *
 * This function modifies the main search query to only return LearnDash courses
 * instead of all post types. It provides a focused search experience for course discovery.
 *
 * @param WP_Query $query The WordPress Query object.
 */
function fli_override_search_for_courses( $query ) {
	if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {
		// ONLY sfwd-courses, exclude revisions and groups
		$query->set( 'post_type', 'sfwd-courses' );
		$query->set( 'post_status', 'publish' );
		$query->set( 'posts_per_page', 12 );

		// Prevent duplicates from multiple search matches
		add_filter( 'posts_fields', 'fli_search_distinct_results', 10, 2 );
		add_filter( 'posts_where', 'fli_exclude_revisions_and_groups', 10, 2 );
	}
}
add_action( 'pre_get_posts', 'fli_override_search_for_courses', 1 );

/**
 * Explicitly exclude revisions and groups from search.
 *
 * @since 1.0.0
 * @param string   $where The WHERE clause of the query.
 * @param WP_Query $query The WP_Query instance.
 * @return string Modified WHERE clause.
 */
function fli_exclude_revisions_and_groups( $where, $query ) {
	if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {
		global $wpdb;
		$where .= " AND {$wpdb->posts}.post_type = 'sfwd-courses' AND {$wpdb->posts}.post_status = 'publish'";
	}
	return $where;
}

/**
 * Add DISTINCT to prevent duplicate results.
 *
 * @since 1.0.0
 * @param string   $fields The SELECT clause of the query.
 * @param WP_Query $query The WP_Query instance.
 * @return string Modified SELECT clause.
 */
function fli_search_distinct_results( $fields, $query ) {
	if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {
		global $wpdb;
		$fields = "DISTINCT {$wpdb->posts}.*";
	}
	return $fields;
}

/**
 * Add GROUP BY to ensure unique posts.
 *
 * @since 1.0.0
 * @param string   $groupby The GROUP BY clause of the query.
 * @param WP_Query $query The WP_Query instance.
 * @return string Modified GROUP BY clause.
 */
function fli_search_group_by( $groupby, $query ) {
	if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {
		global $wpdb;
		$groupby = "{$wpdb->posts}.ID";
	}
	return $groupby;
}
add_filter( 'posts_groupby', 'fli_search_group_by', 10, 2 );

/**
 * Modify search form placeholder text to indicate course search
 *
 * Updates the search form placeholder to show users they are searching courses specifically.
 *
 * @param string $placeholder The default placeholder text.
 * @return string Modified placeholder text.
 */
function fli_course_search_placeholder( $placeholder ) {
	$courses_label = LearnDash_Custom_Label::get_label( 'courses' );
	return sprintf( __( 'Search %s...', 'buddyboss-theme' ), $courses_label );
}
add_filter( 'search_placeholder', 'fli_course_search_placeholder', 30 );

// Removed fli_enhance_course_search - was causing revisions to appear in results

/**
 * Add custom body class for course search results
 *
 * Adds a custom body class to help with styling course search results.
 *
 * @param array $classes Array of body classes.
 * @return array Modified array of body classes.
 */
function fli_course_search_body_class( $classes ) {
	if ( is_search() ) {
		$classes[] = 'learndash-course-search';
		$classes[] = 'search-results';
	}
	return $classes;
}
add_filter( 'body_class', 'fli_course_search_body_class' );

/**
 * Enqueue custom JavaScript and CSS for course search.
 *
 * Adds JavaScript to enhance the course search experience.
 *
 * @since 1.0.0
 * @return void
 */
function fli_enqueue_search_scripts() {
	if ( is_search() ) {
		// Enqueue search CSS
		$search_css = get_stylesheet_directory() . '/assets/css/course-search.css';
		if ( file_exists( $search_css ) ) {
			wp_enqueue_style(
				'fli-course-search-css',
				get_stylesheet_directory_uri() . '/assets/css/course-search.css',
				array(),
				filemtime( $search_css )
			);
		}

		// Enqueue search analytics JS
		wp_add_inline_script(
			'buddyboss-child-js',
			"
            jQuery(document).ready(function($) {
                // Log search analytics (optional)
                if (typeof console !== 'undefined' && console.log) {
                    var resultsCount = $('.search-results-count strong').text();
                    var searchTerm = $('.search-term').text();
                    if (searchTerm) {
                        console.log('LearnDash Course Search: Found ' + resultsCount + ' courses for: ' + searchTerm);
                    }
                }
            });
            "
		);
	}
}
add_action( 'wp_enqueue_scripts', 'fli_enqueue_search_scripts', 30 );

/**
 * Add search query to LearnDash archive page
 *
 * Allows the course archive page to also handle search queries.
 *
 * @param WP_Query $query The WordPress Query object.
 */
function fli_add_search_to_course_archive( $query ) {
	if ( ! is_admin() && $query->is_main_query() && is_post_type_archive( 'sfwd-courses' ) ) {
		// Check if there's a search parameter
		if ( isset( $_GET['search'] ) && ! empty( $_GET['search'] ) ) {
			$query->set( 's', sanitize_text_field( wp_unslash( $_GET['search'] ) ) );
		}
	}
}
add_action( 'pre_get_posts', 'fli_add_search_to_course_archive', 25 );

/**
 * ========================================================================
 * LearnDash Helper Functions (for search results)
 * ========================================================================
 */

if ( ! function_exists( 'learndash_get_course_enrolled_users_count' ) ) {
	/**
	 * Get course enrolled users count
	 *
	 * Helper function to get the number of enrolled users for a course.
	 * Uses caching to improve performance.
	 *
	 * @since 1.0.0
	 * @param int $course_id The course ID.
	 * @return int Number of enrolled users.
	 */
	function learndash_get_course_enrolled_users_count( $course_id ) {
		// Check if function already exists from LearnDash
		if ( function_exists( 'learndash_get_users_for_course' ) ) {
			$enrolled_users = learndash_get_users_for_course( $course_id, array(), false );
			return is_array( $enrolled_users ) ? count( $enrolled_users ) : 0;
		}

		// Fallback to meta query
		$cache_key = 'ld_course_enrolled_' . $course_id;
		$count     = wp_cache_get( $cache_key );

		if ( false === $count ) {
			global $wpdb;
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %d",
					'course_completed_' . $course_id,
					1
				)
			);
			wp_cache_set( $cache_key, $count, '', 3600 ); // Cache for 1 hour
		}

		return (int) $count;
	}
}

if ( ! function_exists( 'learndash_get_course_topics_count' ) ) {
	/**
	 * Get course topics count
	 *
	 * Helper function to get the number of topics in a course.
	 *
	 * @since 1.0.0
	 * @param int $course_id The course ID.
	 * @return int Number of topics.
	 */
	function learndash_get_course_topics_count( $course_id ) {
		// Check if function already exists from LearnDash
		if ( function_exists( 'learndash_course_get_topics' ) ) {
			$topics = learndash_course_get_topics( $course_id );
			return is_array( $topics ) ? count( $topics ) : 0;
		}

		// Fallback
		$count   = 0;
		$lessons = learndash_get_course_lessons_list( $course_id );
		if ( ! empty( $lessons ) ) {
			foreach ( $lessons as $lesson ) {
				$lesson_topics = learndash_get_topic_list( $lesson['post']->ID, $course_id );
				$count        += count( $lesson_topics );
			}
		}

		return $count;
	}
}

if ( ! function_exists( 'learndash_get_course_lessons_count' ) ) {
	/**
	 * Get course lessons count
	 *
	 * Helper function to get the number of lessons in a course.
	 *
	 * @since 1.0.0
	 * @param int $course_id The course ID.
	 * @return int Number of lessons.
	 */
	function learndash_get_course_lessons_count( $course_id ) {
		// Check if function already exists from LearnDash
		if ( function_exists( 'learndash_get_course_lessons_list' ) ) {
			$lessons = learndash_get_course_lessons_list( $course_id );
			return is_array( $lessons ) ? count( $lessons ) : 0;
		}

		// Fallback
		return 0;
	}
}

// Disable event subscribe links
add_filter( 'tec_views_v2_use_subscribe_links', '__return_false' );

/**
 * Log password reset attempts for security auditing.
 *
 * @since 1.0.0
 * @param string $user_login The username or email address attempting password reset.
 * @return void
 */
function log_password_reset_attempt( $user_login ) {
	$user = get_user_by( 'login', $user_login );
	if ( $user ) {
		$log_message = sprintf(
			'Password reset attempted for user: %s at %s',
			$user->user_email,
			current_time( 'mysql' )
		);
		error_log( $log_message );
	}
}

/**
 * Enqueue scripts for LearnDash focus mode mobile optimization.
 *
 * Closes the BuddyPanel on mobile devices when in LearnDash focus mode
 * to provide better learning experience.
 *
 * @since 1.0.0
 * @return void
 */
function enqueue_focus_mode_scripts() {
	wp_add_inline_script(
		'jquery',
		"
        jQuery(document).ready(function($) {
            if ($('body').hasClass('ld-focus-mode')) {
                if ($(window).width() <= 768) {
                    let buddyPanel = $('.buddypanel');
                    if (buddyPanel.hasClass('buddypanel--toggle-on')) {
                        $('.bb-toggle-panel').trigger('click');
                    }
                }
            }
        });
    "
	);
}

/**
 * Process shortcodes in WP Fusion restricted content messages.
 *
 * Allows shortcodes to work within restricted content messages.
 *
 * @since 1.0.0
 * @param string $message The restricted content message.
 * @return string Message with processed shortcodes.
 */
function process_shortcodes_in_restricted_content_message( $message ) {
	return do_shortcode( $message );
}

/**
 * Enable Gutenberg block editor for LearnDash certificates.
 *
 * @since 1.0.0
 * @param bool   $can_edit Whether the post type can use Gutenberg.
 * @param string $post_type The post type being checked.
 * @return bool True for certificates, original value otherwise.
 */
function enable_gutenberg_for_certificates( $can_edit, $post_type ) {
	return 'sfwd-certificates' === $post_type ? true : $can_edit;
}

/**
 * Add registration date column to user list table.
 *
 * @since 1.0.0
 * @param array $columns Existing user table columns.
 * @return array Modified columns with registration date.
 */
function add_user_registration_date_column( $columns ) {
	$columns['registration_date'] = __( 'Registration Date', 'text-domain' );
	return $columns;
}
add_filter( 'manage_users_columns', 'add_user_registration_date_column' );

/**
 * Display registration date in user list table column.
 *
 * @since 1.0.0
 * @param string $value The column value.
 * @param string $column_name The column name.
 * @param int    $user_id The user ID.
 * @return string The formatted registration date.
 */
function show_user_registration_date_column( $value, $column_name, $user_id ) {
	if ( 'registration_date' === $column_name ) {
		$user  = get_userdata( $user_id );
		$value = date_i18n( get_option( 'date_format' ), strtotime( $user->user_registered ) );
	}
	return $value;
}
add_filter( 'manage_users_custom_column', 'show_user_registration_date_column', 10, 3 );

/**
 * Make registration date column sortable in user list table.
 *
 * @since 1.0.0
 * @param array $columns Sortable columns.
 * @return array Modified sortable columns.
 */
function make_registration_date_column_sortable( $columns ) {
	$columns['registration_date'] = 'user_registered';
	return $columns;
}
add_filter( 'manage_users_sortable_columns', 'make_registration_date_column_sortable' );

/**
 * Extend login duration when "Remember Me" is checked.
 *
 * Changes the cookie expiration from 14 days to 90 days.
 *
 * @since 1.0.0
 * @param int  $length The default session length in seconds.
 * @param int  $user_id The user ID.
 * @param bool $remember Whether the user checked "Remember Me".
 * @return int Modified session length.
 */
function extend_login_duration( $length, $user_id, $remember ) {
	if ( $remember ) {
		return 90 * DAY_IN_SECONDS; // 90 days instead of 14
	}
	return $length;
}
add_filter( 'auth_cookie_expiration', 'extend_login_duration', 10, 3 );


/**
 * Manage admin bar visibility for administrators.
 *
 * Ensures the admin bar is visible for administrators only.
 *
 * @since 1.0.0
 * @return void
 */
function fli_manage_admin_bar() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	show_admin_bar( true );
	add_filter( 'show_admin_bar', '__return_true', PHP_INT_MAX );

	add_action( 'wp_head', 'fli_admin_bar_css', 1 );
}
add_action( 'after_setup_theme', 'fli_manage_admin_bar', PHP_INT_MAX );

/**
 * Add CSS to properly display admin bar.
 *
 * Ensures admin bar is fixed at top and body has proper margin.
 *
 * @since 1.0.0
 * @return void
 */
function fli_admin_bar_css() {
	if ( ! is_admin_bar_showing() ) {
		return;
	}
	?>
	<style>
		body { margin-top: 32px !important; }
		#wpadminbar {
			display: block !important;
			position: fixed !important;
			top: 0 !important;
			z-index: 999999 !important;
		}
		@media screen and (max-width: 782px) {
			body { margin-top: 46px !important; }
		}
	</style>
	<?php
}

/**
 * Add custom CSS for LearnDash lesson lists.
 *
 * Conditionally loads only on LearnDash pages to improve performance.
 *
 * @since 1.0.0
 * @return void
 */
function fli_learndash_lesson_list_inline_css() {
	// Only apply on LearnDash pages
	if ( ! is_singular( array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-courses', 'sfwd-quiz' ) ) && ! is_post_type_archive( array( 'sfwd-courses' ) ) ) {
		return;
	}
	?>
	<style id="fli-learndash-override">
	.ld-item-list,
	ul.ld-item-list,
	.learndash-wrapper .ld-item-list {
		padding: 0;
		margin: 0 0 20px 0;
		list-style: none;
	}

	.ld-item-list .ld-item-list-item,
	.ld-item-list li,
	ul.ld-item-list > li,
	.learndash-wrapper li.ld-item-list-item {
		padding: 20px 25px;
		margin-bottom: 12px;
		background: #ffffff;
		border: 2px solid #e7e9ec;
		border-radius: 12px;
		box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
		transition: all 0.3s ease;
		list-style: none;
		display: block;
	}

	.ld-item-list .ld-item-list-item:hover,
	ul.ld-item-list > li:hover {
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
		transform: translateY(-3px);
		border-color: #59898d;
		background: #f8f9fa;
	}

	@media (max-width: 768px) {
		.ld-item-list .ld-item-list-item,
		ul.ld-item-list > li {
			padding: 15px 18px;
			margin-bottom: 10px;
		}
	}

	@media (max-width: 480px) {
		.ld-item-list .ld-item-list-item,
		ul.ld-item-list > li {
			padding: 12px 15px;
			margin-bottom: 8px;
			border-radius: 8px;
		}
	}
	</style>
	<?php
}

/**
 * Rename "Final Quizzes" to "Final Exams" in LearnDash.
 *
 * @since 1.0.0
 * @param string $translated_text Translated text.
 * @param string $text Original text.
 * @param string $domain Text domain.
 * @return string Modified or original text.
 */
function rename_learndash_final_quiz( $translated_text, $text, $domain ) {
	if ( 'learndash' === $domain ) {
		if ( 'Final Quizzes' === $text ) {
			return 'Final Exams';
		}
	}
	return $translated_text;
}
add_filter( 'gettext', 'rename_learndash_final_quiz', 10, 3 );

/**
 * Rename "Quizzes" to "Final Exam" on course pages.
 *
 * @since 1.0.0
 * @param string $translated_text Translated text.
 * @param string $text Original text.
 * @param string $domain Text domain.
 * @return string Modified or original text.
 */
function rename_learndash_section_quizzes( $translated_text, $text, $domain ) {
	if ( 'learndash' === $domain ) {
		if ( 'Quizzes' === $text && is_singular( 'sfwd-courses' ) ) {
			return 'Final Exam';
		}
	}
	return $translated_text;
}
add_filter( 'gettext', 'rename_learndash_section_quizzes', 10, 3 );

/**
 * Rename "Quizzes" to "Final Exam" on archive pages.
 *
 * @since 1.0.0
 * @param string $translated_text Translated text.
 * @param string $text Original text.
 * @param string $domain Text domain.
 * @return string Modified or original text.
 */
function rename_learndash_course_list_labels( $translated_text, $text, $domain ) {
	if ( 'learndash' === $domain ) {
		if ( 'Quizzes' === $text && is_archive() ) {
			return 'Final Exam';
		}
	}
	return $translated_text;
}
add_filter( 'gettext', 'rename_learndash_course_list_labels', 10, 3 );

// AJAX handler for checking user status and processing WP Fusion data
add_action( 'wp_ajax_check_user_status', 'handle_user_status_check' );
add_action( 'wp_ajax_nopriv_check_user_status', 'handle_user_status_check' );

/**
 * AJAX handler for checking user status and processing WP Fusion data.
 *
 * Processes contact data from forms, creates users if needed, and logs them in.
 *
 * @since 1.0.0
 * @return void Sends JSON response.
 */
function handle_user_status_check() {
	// Verify nonce and POST data exists.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'secure-ajax-nonce' ) ) {
		wp_send_json_error( 'Security check failed' );
		return;
	}

	$contact_id   = isset( $_POST['contact_id'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_id'] ) ) : '';
	$email        = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$course_id    = isset( $_POST['course_id'] ) ? sanitize_text_field( wp_unslash( $_POST['course_id'] ) ) : '';
	$force_create = isset( $_POST['force_create'] ) && 'true' === wp_unslash( $_POST['force_create'] );

	error_log( "AJAX Handler: Processing contact - Email: $email, Contact ID: $contact_id" );

	$wpf_result = process_wpf_contact_data( $email, $contact_id );
	error_log( 'WP Fusion Result: ' . print_r( $wpf_result, true ) );

	$user = get_user_by( 'email', $email );

	if ( ! $user && $force_create ) {
		$user = create_user_from_contact_data( $email, $contact_id );
		error_log( 'Created new user: ' . ( $user ? $user->user_login : 'failed' ) );
	}

	if ( $user ) {
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID );

		$redirect_url = determine_redirect_url( $user, $course_id );

		error_log( "User logged in successfully, redirecting to: $redirect_url" );

		wp_send_json_success(
			array(
				'message'      => 'User logged in successfully',
				'redirect_url' => $redirect_url,
				'user_id'      => $user->ID,
				'wpf_result'   => $wpf_result,
			)
		);
	} else {
		error_log( 'User not found yet, will continue polling...' );
		wp_send_json_error(
			array(
				'message'    => 'User not found, will retry...',
				'wpf_result' => $wpf_result,
			)
		);
	}
}

/**
 * Process contact data through WP Fusion CRM.
 *
 * Adds or updates contact information in the connected CRM system.
 *
 * @since 1.0.0
 * @param string $email The contact email address.
 * @param string $contact_id The CRM contact ID.
 * @return array Result array with success status and message.
 */
function process_wpf_contact_data( $email, $contact_id ) {
	if ( ! function_exists( 'wp_fusion' ) ) {
		error_log( 'WP Fusion is not active or not loaded' );
		return array(
			'success' => false,
			'message' => 'WP Fusion not active',
		);
	}

	try {
		$contact_data = array(
			'user_email'        => $email,
			'send_notification' => false,
			'wpf_action'        => 'add',
		);

		if ( isset( $_POST['first_name'] ) && ! empty( $_POST['first_name'] ) ) {
			$contact_data['first_name'] = sanitize_text_field( wp_unslash( $_POST['first_name'] ) );
		}

		if ( ! empty( $contact_id ) ) {
			$contact_data['infusionsoft_contact_id'] = $contact_id;
		}

		error_log( 'WP Fusion: Attempting to add contact with data: ' . print_r( $contact_data, true ) );

		$crm_contact_id = wp_fusion()->crm->add_contact( $contact_data );

		if ( is_wp_error( $crm_contact_id ) ) {
			error_log( 'WP Fusion Error: ' . $crm_contact_id->get_error_message() );
			return array(
				'success' => false,
				'message' => $crm_contact_id->get_error_message(),
			);
		}

		error_log( 'WP Fusion: Successfully processed contact - ' . $email . ' with CRM ID: ' . $crm_contact_id );

		return array(
			'success'    => true,
			'message'    => 'Contact processed successfully',
			'contact_id' => $crm_contact_id,
		);

	} catch ( Exception $e ) {
		error_log( 'WP Fusion Exception: ' . $e->getMessage() );
		return array(
			'success' => false,
			'message' => $e->getMessage(),
		);
	}
}

/**
 * Create a new WordPress user from contact data.
 *
 * Creates a user account with auto-generated username and password.
 *
 * @since 1.0.0
 * @param string $email The user's email address.
 * @param string $contact_id The CRM contact ID.
 * @return WP_User|false User object on success, false on failure.
 */
function create_user_from_contact_data( $email, $contact_id ) {
	$username = sanitize_user( str_replace( '@', '_', $email ) );

	$original_username = $username;
	$counter           = 1;
	while ( username_exists( $username ) ) {
		$username = $original_username . '_' . $counter;
		++$counter;
	}

	$password = wp_generate_password( 12, false );

	$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';

	$user_data = array(
		'user_login' => $username,
		'user_email' => $email,
		'user_pass'  => $password,
		'first_name' => $first_name,
		'role'       => 'subscriber',
	);

	$user_id = wp_insert_user( $user_data );

	if ( is_wp_error( $user_id ) ) {
		error_log( 'Failed to create user: ' . $user_id->get_error_message() );
		return false;
	}

	if ( ! empty( $contact_id ) ) {
		update_user_meta( $user_id, 'infusionsoft_contact_id', $contact_id );
	}

	error_log( 'Successfully created user: ' . $username . ' with ID: ' . $user_id );

	return get_user_by( 'id', $user_id );
}

/**
 * Determine the appropriate redirect URL after login.
 *
 * Redirects to course page if course_id provided, otherwise to dashboard or my-account.
 *
 * @since 1.0.0
 * @param WP_User $user The logged-in user object.
 * @param string  $course_id Optional course ID to redirect to.
 * @return string The redirect URL.
 */
function determine_redirect_url( $user, $course_id = '' ) {
	if ( ! empty( $course_id ) ) {
		return home_url( "/course/$course_id/" );
	}

	if ( in_array( 'subscriber', $user->roles ) ) {
		return home_url( '/dashboard/' );
	}

	return home_url( '/my-account/' );
}

/**
 * Handle fallback form submission when AJAX fails.
 *
 * Provides a non-AJAX fallback for user login and creation.
 *
 * @since 1.0.0
 * @return void Redirects user on success.
 */
function handle_fallback_form_submission() {
	if ( isset( $_POST['fallback'] ) && 'true' === wp_unslash( $_POST['fallback'] ) ) {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'secure-ajax-nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		$contact_id = isset( $_POST['contact_id'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_id'] ) ) : '';
		$email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$course_id  = isset( $_POST['course_id'] ) ? sanitize_text_field( wp_unslash( $_POST['course_id'] ) ) : '';
		$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';

		error_log( "Fallback form submission for: $email" );

		$_POST['first_name'] = $first_name;
		$wpf_result          = process_wpf_contact_data( $email, $contact_id );

		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			$user = create_user_from_contact_data( $email, $contact_id );
		}

		if ( $user ) {
			wp_set_current_user( $user->ID );
			wp_set_auth_cookie( $user->ID );

			$redirect_url = determine_redirect_url( $user, $course_id );
			wp_redirect( $redirect_url );
			exit;
		} else {
			wp_redirect( wp_login_url() . '?message=account_setup_failed' );
			exit;
		}
	}
}

/**
 * Change login button text to "Login with Password".
 *
 * @since 1.0.0
 * @param string $translated_text Translated text.
 * @param string $text Original text.
 * @param string $domain Text domain.
 * @return string Modified or original text.
 */
function fearless_change_login_button_text( $translated_text, $text, $domain ) {
	if ( 'Log In' === $text && 'wp-login.php' === $GLOBALS['pagenow'] ) {
		return 'Login with Password';
	}
	return $translated_text;
}

/**
 * Allow login with email address or username.
 *
 * Converts email to username before authentication.
 *
 * @since 1.0.0
 * @param WP_User|WP_Error|null $user User object or error.
 * @param string                $username Username or email.
 * @param string                $password User password.
 * @return WP_User|WP_Error User object on success, error on failure.
 */
function fearless_email_login_auth( $user, $username, $password ) {
	if ( ! empty( $username ) && ! is_wp_error( $user ) ) {
		if ( is_email( $username ) ) {
			$user_data = get_user_by( 'email', $username );
			if ( $user_data ) {
				$username = $user_data->user_login;
			}
		}

		return wp_authenticate_username_password( null, $username, $password );
	}

	return $user;
}

/**
 * Add conditional welcome message to dashboard menu items.
 *
 * Replaces "Dashboard" link with personalized welcome message.
 *
 * @since 1.0.0
 * @param array $menu_items Array of menu item objects.
 * @return array Modified menu items.
 */
function conditional_dashboard_menu_items( $menu_items ) {
	if ( ! is_user_logged_in() ) {
		return $menu_items;
	}

	$current_user = wp_get_current_user();
	$user_id      = $current_user->ID;

	$first_name   = get_user_meta( $user_id, 'first_name', true );
	$welcome_text = ! empty( $first_name ) ? "Welcome {$first_name}" : 'Welcome';

	foreach ( $menu_items as $key => $menu_item ) {
		if ( strpos( $menu_item->title, 'Dashboard' ) !== false ||
			strpos( $menu_item->url, 'dashboard' ) !== false ) {

			$menu_item->title     = $welcome_text;
			$menu_item->url       = '#';
			$menu_item->classes[] = 'welcome-message';
			$menu_items[ $key ]   = $menu_item;

			break;
		}
	}

	return $menu_items;
}

/**
 * Enqueue consolidated error prevention script.
 *
 * Loads JavaScript to prevent common frontend errors.
 *
 * @since 1.0.0
 * @return void
 */
function fli_enqueue_consolidated_error_prevention() {
	wp_enqueue_script(
		'fli-error-prevention',
		get_stylesheet_directory_uri() . '/assets/js/error-prevention.js',
		array(),
		'1.0.2',
		false
	);
}
add_action( 'wp_enqueue_scripts', 'fli_enqueue_consolidated_error_prevention', 1 );

/**
 * Get category color by slug.
 *
 * @since 1.0.0
 * @param string $category_slug The category slug.
 * @return string Hex color code.
 */
function fli_get_category_color( $category_slug ) {
	if ( class_exists( 'FearlessLiving_Category_Colors' ) ) {
		return FearlessLiving_Category_Colors::instance()->get_category_color( $category_slug );
	}
	return '#7f868f';
}

/**
 * Get category color by term ID.
 *
 * @since 1.0.0
 * @param int $term_id The category term ID.
 * @return string Hex color code.
 */
function fli_get_category_color_by_id( $term_id ) {
	if ( class_exists( 'FearlessLiving_Category_Colors' ) ) {
		return FearlessLiving_Category_Colors::instance()->get_category_color_by_id( $term_id );
	}
	return '#7f868f';
}

/**
 * Get the category color for the current post.
 *
 * @since 1.0.0
 * @return string Hex color code.
 */
function fli_get_current_post_category_color() {
	if ( class_exists( 'FearlessLiving_Category_Colors' ) ) {
		return FearlessLiving_Category_Colors::instance()->get_current_post_category_color();
	}
	return '#7f868f';
}

/**
 * Get the category color for archive pages.
 *
 * @since 1.0.0
 * @return string Hex color code.
 */
function fli_get_archive_category_color() {
	if ( class_exists( 'FearlessLiving_Category_Colors' ) ) {
		return FearlessLiving_Category_Colors::instance()->get_archive_category_color();
	}
	return '#7f868f';
}

/**
 * Generate CSS property with category color.
 *
 * @since 1.0.0
 * @param string $category_slug The category slug.
 * @param string $property CSS property name (default: 'color').
 * @return string CSS property declaration.
 */
function fli_category_color_css( $category_slug, $property = 'color' ) {
	$color = fli_get_category_color( $category_slug );
	return "{$property}: {$color};";
}

/**
 * Generate CSS class name for category color.
 *
 * @since 1.0.0
 * @param string $category_slug The category slug.
 * @return string CSS class name.
 */
function fli_category_color_class( $category_slug ) {
	return "category-{$category_slug}";
}

/**
 * Add custom BuddyBoss CSS variables to site head.
 *
 * Outputs CSS custom properties for Fearless Living brand colors.
 *
 * @since 1.0.0
 * @return void
 */
function fli_add_custom_buddyboss_variables() {
	$theme_options = get_option( 'buddyboss_theme_options', array() );

	$fli_brand_teal        = isset( $theme_options['fli_brand_teal'] ) ? $theme_options['fli_brand_teal'] : '#59898D';
	$fli_brand_light_teal  = isset( $theme_options['fli_brand_light_teal'] ) ? $theme_options['fli_brand_light_teal'] : '#EAEDAF';
	$fli_brand_dark_teal   = isset( $theme_options['fli_brand_dark_teal'] ) ? $theme_options['fli_brand_dark_teal'] : '#0a738a';
	$fli_brand_yellow      = isset( $theme_options['fli_brand_yellow'] ) ? $theme_options['fli_brand_yellow'] : '#E6ED5A';
	$fli_brand_dark_yellow = isset( $theme_options['fli_brand_dark_yellow'] ) ? $theme_options['fli_brand_dark_yellow'] : '#BFC046';
	$fli_brand_pink        = isset( $theme_options['fli_brand_pink'] ) ? $theme_options['fli_brand_pink'] : '#ff69b4';
	$fli_brand_orange      = isset( $theme_options['fli_brand_orange'] ) ? $theme_options['fli_brand_orange'] : '#ff6b00';
	$fli_brand_white       = isset( $theme_options['fli_brand_white'] ) ? $theme_options['fli_brand_white'] : '#ffffff';
	$fli_brand_gray_light  = isset( $theme_options['fli_brand_gray_light'] ) ? $theme_options['fli_brand_gray_light'] : '#f9f9f9';
	$fli_brand_gray_medium = isset( $theme_options['fli_brand_gray_medium'] ) ? $theme_options['fli_brand_gray_medium'] : '#393939';
	$fli_brand_gray_dark   = isset( $theme_options['fli_brand_gray_dark'] ) ? $theme_options['fli_brand_gray_dark'] : '#1B181C';

	?>
	<style>
	:root {
		--fli-brand-teal: <?php echo esc_attr( $fli_brand_teal ); ?>;
		--fli-brand-light-teal: <?php echo esc_attr( $fli_brand_light_teal ); ?>;
		--fli-brand-dark-teal: <?php echo esc_attr( $fli_brand_dark_teal ); ?>;
		--fli-brand-yellow: <?php echo esc_attr( $fli_brand_yellow ); ?>;
		--fli-brand-dark-yellow: <?php echo esc_attr( $fli_brand_dark_yellow ); ?>;
		--fli-brand-pink: <?php echo esc_attr( $fli_brand_pink ); ?>;
		--fli-brand-orange: <?php echo esc_attr( $fli_brand_orange ); ?>;
		--fli-brand-white: <?php echo esc_attr( $fli_brand_white ); ?>;
		--fli-brand-gray-light: <?php echo esc_attr( $fli_brand_gray_light ); ?>;
		--fli-brand-gray-medium: <?php echo esc_attr( $fli_brand_gray_medium ); ?>;
		--fli-brand-gray-dark: <?php echo esc_attr( $fli_brand_gray_dark ); ?>;
		--fli-border-radius: var(--bb-border-radius, 8px);
		--fli-box-shadow: var(--bb-box-shadow, 0 2px 4px rgba(0, 0, 0, 0.1));
		--fli-transition: all 0.3s ease;
	}
	</style>
	<?php
}
add_action( 'wp_head', 'fli_add_custom_buddyboss_variables', 1 );

// Floating button Contact
if ( defined( 'THEME_HOOK_PREFIX' ) ) {
	add_action( THEME_HOOK_PREFIX . 'after_page', 'wbcom_add_floating_ask_button' );
} else {
	add_action( 'wp_footer', 'wbcom_add_floating_ask_button' );
}

/**
 * Add floating "Ask a Question" button to site footer.
 *
 * Displays a fixed email contact button for support.
 *
 * @since 1.0.0
 * @return void
 */
function wbcom_add_floating_ask_button() {
	?>
	<a href="mailto:support@fearlessliving.org" class="floating-ask-btn" title="Ask a Question">
		<i class="bb-icon-envelope bb-icon-l"></i>
	</a>
	<?php
}

/**
 * Custom redirect after user login.
 *
 * Redirects all users to home page after login.
 *
 * @since 1.0.0
 * @param string           $redirect_to The redirect destination URL.
 * @param string           $request The requested redirect destination URL.
 * @param WP_User|WP_Error $user The user object or error.
 * @return string Modified redirect URL.
 */
function custom_redirect_after_login( $redirect_to, $request, $user ) {
	if ( isset( $user->ID ) ) {
		return home_url();
	}
	return $redirect_to;
}
add_filter( 'login_redirect', 'custom_redirect_after_login', 100, 3 );

add_action(
	'template_redirect',
	function () {
		// Check if the current page is an author archive
		if ( is_author() ) {
			wp_redirect( home_url( '/profile/' ) );
			exit;
		}
	}
);

/**
 * Disable BuddyBoss license check requests.
 *
 * Blocks all requests to licenses.caseproof.com to prevent
 * unnecessary API calls and improve site performance.
 *
 * @since 1.0.0
 * @param false|array|WP_Error $preempt Whether to preempt the HTTP request.
 * @param array                $args Request arguments.
 * @param string               $url Request URL.
 * @return false|array False to continue request, array to preempt.
 */
function fli_disable_buddyboss_license_checks( $preempt, $args, $url ) {
	// Check if this is a BuddyBoss license check request
	if ( strpos( $url, 'licenses.caseproof.com' ) !== false ) {
		// Return a fake successful response to prevent the request
		return array(
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'body'     => json_encode( array(
				'success' => true,
				'data'    => array(),
			) ),
			'headers'  => array(),
			'cookies'  => array(),
		);
	}

	return $preempt;
}
add_filter( 'pre_http_request', 'fli_disable_buddyboss_license_checks', 1, 3 );
