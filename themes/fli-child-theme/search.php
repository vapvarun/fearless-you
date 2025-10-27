<?php
/**
 * The template for displaying LearnDash course search results
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#search-result
 *
 * @package FLI Child Theme
 */

get_header();

// Get LearnDash labels
$courses_label = LearnDash_Custom_Label::get_label( 'courses' );
$course_label = LearnDash_Custom_Label::get_label( 'course' );

// Get search query
$search_query = get_search_query();

// If search query is empty but we have an 's' parameter, use that
if ( empty( $search_query ) && isset( $_GET['s'] ) ) {
    $search_query = sanitize_text_field( wp_unslash( $_GET['s'] ) );
}
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <?php if ( have_posts() ) : ?>

            <!-- Search Header -->
            <header class="page-header">
                <div class="container">
                    <h1 class="page-title">
                        <?php
                        printf(
                            esc_html__( "Showing %s results for '%s'", 'buddyboss-theme' ),
                            '<span class="highlight">' . esc_html( $course_label ) . '</span>',
                            '<span class="search-term">' . esc_html( $search_query ) . '</span>'
                        );
                        ?>
                    </h1>
                </div>
            </header><!-- .page-header -->

            <!-- Search Results Count -->
            <div class="search-results-count">
                <div class="container">
                    <?php
                    global $wp_query;
                    $count = $wp_query->found_posts;
                    printf(
                        wp_kses(
                            _n(
                                'Found <strong>%d</strong> ' . $course_label,
                                'Found <strong>%d</strong> ' . $courses_label,
                                $count,
                                'buddyboss-theme'
                            ),
                            array( 'strong' => array() )
                        ),
                        (int) $count
                    );
                    ?>
                </div>
            </div>

            <div id="learndash-content" class="learndash-course-search-results">
                <div class="container">

                    <!-- Search Results -->
                    <div class="search-results">
                        <div id="course-dir-list" class="course-dir-list bs-dir-list">
                            <ul class="bb-course-items ld-course-search-grid"
                                aria-live="polite"
                                aria-relevant="all">
                                <?php
                                /* Start the Loop */
                                while ( have_posts() ) :
                                    the_post();

                                    /**
                                     * Include the course-specific template for search results.
                                     * Falls back to parent theme's template if child theme doesn't have it.
                                     */
                                    get_template_part( 'template-parts/content', 'course-search' );

                                endwhile;
                                ?>
                            </ul>

                            <!-- Pagination -->
                            <div class="bb-lms-pagination">
                                <?php
                                $big = 999999999; // need an unlikely integer
                                $translated = __( 'Page', 'buddyboss-theme' ); // Supply translatable string

                                echo paginate_links(
                                    array(
                                        'base'               => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
                                        'format'             => '?paged=%#%',
                                        'current'            => max( 1, get_query_var( 'paged' ) ),
                                        'total'              => $wp_query->max_num_pages,
                                        'before_page_number' => '<span class="screen-reader-text">' . $translated . ' </span>',
                                        'prev_text'          => '<i class="bb-icon-l bb-icon-angle-left"></i>',
                                        'next_text'          => '<i class="bb-icon-l bb-icon-angle-right"></i>',
                                    )
                                );
                                ?>
                            </div>

                        </div>
                    </div>

                </div><!-- .container -->
            </div><!-- #learndash-content -->

        <?php else : ?>

            <!-- No Results Found -->
            <div class="container">
                <header class="page-header">
                    <div class="container">
                        <h1 class="page-title">
                            <?php
                            printf(
                                esc_html__( "No %s found for '%s'", 'buddyboss-theme' ),
                                esc_html( $courses_label ),
                                '<span class="search-term">' . esc_html( $search_query ) . '</span>'
                            );
                            ?>
                        </h1>
                    </div>
                </header>

                <div class="search-no-results">
                    <aside class="bp-feedback bp-template-notice ld-feedback info">
                        <span class="bp-icon" aria-hidden="true"></span>
                        <p><?php printf( esc_html__( 'Sorry, no %s were found matching your search.', 'buddyboss-theme' ), strtolower( $courses_label ) ); ?></p>
                    </aside>

                    <!-- Search Form -->
                    <div class="search-wrap">
                        <h3><?php esc_html_e( 'Try searching again:', 'buddyboss-theme' ); ?></h3>
                        <?php get_search_form(); ?>
                    </div>

                    <!-- Suggestions -->
                    <div class="search-suggestions">
                        <h3><?php esc_html_e( 'Search Tips:', 'buddyboss-theme' ); ?></h3>
                        <ul>
                            <li><?php esc_html_e( 'Try different keywords', 'buddyboss-theme' ); ?></li>
                            <li><?php esc_html_e( 'Check your spelling', 'buddyboss-theme' ); ?></li>
                            <li><?php esc_html_e( 'Use more general terms', 'buddyboss-theme' ); ?></li>
                            <li><?php printf( esc_html__( 'Browse all %s', 'buddyboss-theme' ), '<a href="' . get_post_type_archive_link( 'sfwd-courses' ) . '">' . strtolower( $courses_label ) . '</a>' ); ?></li>
                        </ul>
                    </div>

                    <?php
                    // Show some recent courses as suggestions
                    $recent_courses = new WP_Query( array(
                        'post_type'      => 'sfwd-courses',
                        'posts_per_page' => 6,
                        'post_status'    => 'publish',
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                    ) );

                    if ( $recent_courses->have_posts() ) :
                        ?>
                        <div class="suggested-courses">
                            <h3><?php printf( esc_html__( 'Recent %s:', 'buddyboss-theme' ), $courses_label ); ?></h3>
                            <ul class="bb-course-items ld-course-search-grid">
                                <?php
                                while ( $recent_courses->have_posts() ) :
                                    $recent_courses->the_post();
                                    get_template_part( 'template-parts/content', 'course-search' );
                                endwhile;
                                wp_reset_postdata();
                                ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

        <?php endif; ?>

    </main><!-- #main -->
</div><!-- #primary -->

<?php
// Include sidebar if theme supports it
if ( is_active_sidebar( 'learndash' ) ) {
    get_sidebar( 'learndash' );
}
?>

<?php
get_footer();
