<?php
/**
 * The template for displaying search results pages
 *
 * @package BuddyBoss_Theme
 */

get_header();

$blog_type = 'standard';
$class = 'bb-standard';

if (is_search() && !isset($_GET['post_type'])) {
    // Display Courses by default
    $args = array(
        'post_type' => 'sfwd-courses',
        's' => get_search_query(),
    );
    $course_query = new WP_Query($args);
    ?>
    
    <div id="primary" class="content-area">
        <main id="main" class="site-main">

            <?php if ($course_query->have_posts()) : ?>
                <header class="page-header">
                    <h1 class="page-title">
                        <?php printf(esc_html__("Showing results for '%s'", 'buddyboss-theme'), '<span>' . get_search_query() . '</span>'); ?>
                    </h1>
                </header><!-- .page-header -->

                <div class="post-grid <?php echo esc_attr($class); ?>">
                    <?php
                    // Start the Course Loop
                    while ($course_query->have_posts()) :
                        $course_query->the_post();
                        
                        // Retrieve the icon for 'sfwd-courses' post type
                        $icon_url = phunk_get_post_type_icon('sfwd-courses');
                        if ($icon_url) {
                            echo '<img src="' . esc_url($icon_url) . '" alt="Course icon" style="width: 24px; height: 24px;">';
                        }
                        
                        get_template_part('template-parts/content', 'course');
                    endwhile;
                    wp_reset_postdata();
                    ?>
                </div>
                
            <?php else : ?>
                <?php get_template_part('template-parts/content', 'none'); ?>
            <?php endif; ?>

        </main><!-- #main -->
    </div><!-- #primary -->

<?php
} else {
    // Default search loop for other post types if a filter is set
    ?>
    <div id="primary" class="content-area">
        <main id="main" class="site-main">
            <?php if (have_posts()) : ?>
                <header class="page-header">
                    <h1 class="page-title">
                        <?php printf(esc_html__("Showing results for '%s'", 'buddyboss-theme'), '<span>' . get_search_query() . '</span>'); ?>
                    </h1>
                </header><!-- .page-header -->

                <div class="post-grid <?php echo esc_attr($class); ?>">
                    <?php
                    // Default loop for other post types
                    while (have_posts()) :
                        the_post();
                        $post_type = get_post_type();
                        $icon_url = phunk_get_post_type_icon($post_type);
                        
                        if ($icon_url) {
                            echo '<img src="' . esc_url($icon_url) . '" alt="' . esc_attr($post_type) . ' icon" style="width: 24px; height: 24px;">';
                        }
                        
                        get_template_part('template-parts/content', $post_type);
                    endwhile;
                    ?>
                </div>
                
                <?php buddyboss_pagination(); ?>
            <?php else : ?>
                <?php get_template_part('template-parts/content', 'none'); ?>
            <?php endif; ?>
        </main><!-- #main -->
    </div><!-- #primary -->
<?php
}

get_sidebar('search');
get_footer();