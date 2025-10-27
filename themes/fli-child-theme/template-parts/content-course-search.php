<?php
/**
 * Template part for displaying LearnDash course in search results
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package FLI Child Theme
 */

// Get course ID
$course_id = get_the_ID();

// Get course settings
$course_settings = learndash_get_setting( $course_id );

// Get course thumbnail
$has_thumbnail = has_post_thumbnail( $course_id );
$thumbnail_html = '';

if ( $has_thumbnail ) {
    $thumbnail_html = get_the_post_thumbnail( $course_id, 'medium_large' );
} else {
    // Use BuddyBoss style - no image, just background color
    // Or use LearnDash placeholder if available
    $placeholder_url = '';

    // Check if LearnDash has a default image
    if ( function_exists( 'learndash_get_thumb_url' ) ) {
        $placeholder_url = learndash_get_thumb_url();
    }

    // If no LearnDash placeholder, check BuddyBoss theme
    if ( empty( $placeholder_url ) ) {
        $buddyboss_placeholder = get_template_directory_uri() . '/assets/images/course-placeholder.jpg';
        if ( file_exists( get_template_directory() . '/assets/images/course-placeholder.jpg' ) ) {
            $placeholder_url = $buddyboss_placeholder;
        }
    }

    // Final fallback - create inline placeholder
    if ( ! empty( $placeholder_url ) ) {
        $thumbnail_html = '<img src="' . esc_url( $placeholder_url ) . '" alt="' . esc_attr( get_the_title() ) . '" loading="lazy" class="attachment-medium_large size-medium_large wp-post-image">';
    } else {
        // No image, use CSS background with course icon
        $thumbnail_html = '<div class="course-no-image"><i class="bb-icon-l bb-icon-book"></i></div>';
    }
}

// Get course price
$course_price = '';
if ( isset( $course_settings['course_price_type'] ) ) {
    $price_type = $course_settings['course_price_type'];

    if ( 'free' === $price_type || 'open' === $price_type ) {
        $course_price = __( 'Free', 'buddyboss-theme' );
    } elseif ( ! empty( $course_settings['course_price'] ) ) {
        $course_price = learndash_get_course_price( $course_id );
    }
}

// Get course stats
$lessons_count = learndash_get_course_lessons_count( $course_id );
$topics_count = learndash_get_course_topics_count( $course_id );
$students_count = learndash_get_course_enrolled_users_count( $course_id );

// Check if user is enrolled
$user_id = get_current_user_id();
$is_enrolled = sfwd_lms_has_access( $course_id, $user_id );

// Get course progress if enrolled
$progress = 0;
if ( $is_enrolled && $user_id > 0 ) {
    $progress = learndash_course_progress( array(
        'user_id'   => $user_id,
        'course_id' => $course_id,
        'array'     => true
    ) );
    $progress = isset( $progress['percentage'] ) ? $progress['percentage'] : 0;
}

?>

<li id="course-<?php the_ID(); ?>" <?php post_class( 'ld-course-search-result' ); ?>>
    <div class="course-item-wrap">

        <!-- Course Thumbnail -->
        <div class="course-thumbnail <?php echo ! $has_thumbnail ? 'no-thumbnail' : ''; ?>">
            <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
                <?php echo $thumbnail_html; ?>
            </a>

            <?php if ( $course_price ) : ?>
                <span class="course-price"><?php echo esc_html( $course_price ); ?></span>
            <?php endif; ?>

            <?php if ( $is_enrolled ) : ?>
                <span class="course-badge enrolled"><?php _e( 'Enrolled', 'buddyboss-theme' ); ?></span>
            <?php else : ?>
                <span class="course-badge"><?php echo LearnDash_Custom_Label::get_label( 'course' ); ?></span>
            <?php endif; ?>
        </div>

        <!-- Course Content -->
        <div class="course-content">

            <!-- Course Title -->
            <h2 class="course-title">
                <a href="<?php the_permalink(); ?>" rel="bookmark">
                    <?php the_title(); ?>
                </a>
            </h2>

            <!-- Course Excerpt -->
            <?php if ( has_excerpt() ) : ?>
                <div class="course-excerpt">
                    <?php echo wp_trim_words( get_the_excerpt(), 25, '...' ); ?>
                </div>
            <?php else : ?>
                <div class="course-excerpt">
                    <?php echo wp_trim_words( get_the_content(), 25, '...' ); ?>
                </div>
            <?php endif; ?>

            <!-- Progress Bar (if enrolled) -->
            <?php if ( $is_enrolled && $progress > 0 ) : ?>
                <div class="course-progress-wrap">
                    <div class="course-progress-bar">
                        <div class="course-progress-fill" style="width: <?php echo esc_attr( $progress ); ?>%;">
                            <span class="course-progress-text"><?php echo esc_html( round( $progress ) ); ?>% <?php _e( 'Complete', 'buddyboss-theme' ); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Course Meta -->
            <div class="course-meta">

                <?php if ( $lessons_count > 0 ) : ?>
                    <div class="course-meta-item">
                        <i class="bb-icon-l bb-icon-book"></i>
                        <span>
                            <?php
                            printf(
                                _n(
                                    '%d Lesson',
                                    '%d Lessons',
                                    $lessons_count,
                                    'buddyboss-theme'
                                ),
                                $lessons_count
                            );
                            ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ( $topics_count > 0 ) : ?>
                    <div class="course-meta-item">
                        <i class="bb-icon-l bb-icon-file-text"></i>
                        <span>
                            <?php
                            printf(
                                _n(
                                    '%d Topic',
                                    '%d Topics',
                                    $topics_count,
                                    'buddyboss-theme'
                                ),
                                $topics_count
                            );
                            ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ( $students_count > 0 ) : ?>
                    <div class="course-meta-item">
                        <i class="bb-icon-l bb-icon-user"></i>
                        <span>
                            <?php
                            printf(
                                _n(
                                    '%d Student',
                                    '%d Students',
                                    $students_count,
                                    'buddyboss-theme'
                                ),
                                $students_count
                            );
                            ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php
                // Get course categories
                $categories = get_the_terms( $course_id, 'ld_course_category' );
                if ( $categories && ! is_wp_error( $categories ) ) :
                    ?>
                    <div class="course-meta-item course-categories">
                        <i class="bb-icon-l bb-icon-folder"></i>
                        <span>
                            <?php
                            $cat_names = array();
                            foreach ( $categories as $category ) {
                                $cat_names[] = $category->name;
                            }
                            echo esc_html( implode( ', ', array_slice( $cat_names, 0, 2 ) ) );
                            ?>
                        </span>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Call to Action -->
            <div class="course-action">
                <?php if ( $is_enrolled ) : ?>
                    <a href="<?php the_permalink(); ?>" class="button bb-primary-btn">
                        <?php _e( 'Continue Learning', 'buddyboss-theme' ); ?>
                    </a>
                <?php else : ?>
                    <a href="<?php the_permalink(); ?>" class="button bb-primary-btn">
                        <?php _e( 'View Course', 'buddyboss-theme' ); ?>
                    </a>
                <?php endif; ?>
            </div>

        </div>

    </div>
</li>
