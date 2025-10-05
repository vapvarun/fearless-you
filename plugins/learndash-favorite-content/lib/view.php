<?php
add_shortcode( 'favorite_content', 'ldfc_favorite_content_shortcode' );
function ldfc_favorite_content_shortcode( $atts ) {

     ob_start();

     if( !is_user_logged_in() ): ?>
          <div class="ldfc-notice">
               <p><?php esc_html_e( 'You must be logged in to see your favorites.', 'favcon' ); ?></p>
          </div>
          <?php
          return ob_get_clean();
     endif;

     $cuser = wp_get_current_user();

     $favorites = get_user_meta( $cuser->ID, '_favcon' );

     if( empty($favorites) || !$favorites ): ?>
          <div class="ldfc-notice">
               <p><?php esc_html_e( 'You don\'t currently have any favorites.', 'favcon' ); ?></p>
          </div>
          <?php
          return ob_get_clean();
     endif; ?>

     <div class="ldfc-favorite-table ldfc-search-parent">
          <div class="ldfc-favorite-table-wrap">
               <div class="ldfc-favorite-header">
                    <?php esc_html_e( 'Favorites', 'favcon' ); ?>
                    <input type="text" name="ldfc-favorite-search" value="" class="ldfc-favorite-search-input" placeholder="<?php esc_attr_e( 'Search...', 'favcon' ); ?>">
               </div>
               <div class="ldfc-favorites">
                    <?php foreach( $favorites as $favorite ): ?>
                         <div class="ldfc-favorite <?php echo esc_attr( 'ldfc-favorite-' . $favorite ); ?>">
                              <a href="#" class="js-ldfc-unfavorite" data-post_id="<?php echo esc_attr($favorite); ?>"><?php ldfc_heart_icon( true ); ?></a>
                              <a class="ldfc-favorite__link" href="<?php echo esc_url( get_the_permalink($favorite) ); ?>">
                                   <span class="ldfc-favorite__title"><?php echo esc_html( get_the_title($favorite) ); ?></span>
                                   <?php
                                   $course_id = get_post_meta( $favorite, 'course_id', true );
                                   if( $course_id ): ?>
                                        <span class="ldfc-favorite__course"><?php echo esc_html( get_the_title( $course_id ) ); ?></span>
                                   <?php endif; ?>
                              </a>
                         </div>
                    <?php endforeach; ?>
               </div>
          </div>
     </div>

     <?php
     // TODO: Pagination (maybe do this with JS?)

     return ob_get_clean();

}

add_shortcode( 'favorite_course_content', 'ldfc_favorite_course_content_shortcode' );
function ldfc_favorite_course_content_shortcode( $atts ) {

     ob_start();

     if( !is_user_logged_in() ): ?>
          <div class="ldfc-notice">
               <p><?php esc_html_e( 'You must be logged in to see your favorites.', 'favcon' ); ?></p>
          </div>
          <?php
          return ob_get_clean();
     endif;

     $cuser    = wp_get_current_user();
     if( !isset( $atts['courses'] ) ) {
          $courses  = get_user_meta( $cuser->ID, '_favcon_courses' );
     } else {
          $courses = explode( ',', $atts['courses'] );
     }

     if( empty($courses) || !$courses ): ?>
          <div class="ldfc-notice">
               <p><?php esc_html_e( 'You don\'t currently have any favorites.', 'favcon' ); ?></p>
          </div>
          <?php
          return ob_get_clean();
     endif; ?>

     <div class="ldfc-courses-list">
          <?php
          foreach( $courses as $course_id ):

               $course_favorites = get_user_meta( $cuser->ID, '_favcon_course_' . $course_id );

               if( empty($course_favorites) || !$course_favorites ) {
                    continue;
               } ?>
               <div class="ldfc-favorite-table ldfc-search-parent ldfc-course-<?php echo urlencode( get_the_title($course_id) ); ?>">
                    <div class="ldfc-favorite-table-wrap">
                         <div class="ldfc-favorite-header">
                              <a href="<?php echo esc_url( get_the_permalink($course_id) ); ?>"><?php echo wp_kses_post( get_the_title($course_id) ); ?></a>
                              <input type="text" name="ldfc-favorite-search" value="" class="ldfc-favorite-search-input" placeholder="<?php esc_attr_e( 'Search...', 'favcon' ); ?>">
                         </div>
                    </div>
                    <div class="ldfc-course-list__content">
                         <div class="ldfc-favorites">
                              <?php foreach( $course_favorites as $favorite ): ?>
                                   <div class="ldfc-favorite <?php echo esc_attr( 'ldfc-favorite-' . $favorite ); ?>">
                                        <a href="#" class="js-ldfc-unfavorite" data-post_id="<?php echo esc_attr($favorite); ?>"><?php ldfc_heart_icon( true ); ?></a>
                                        <a class="ldfc-favorite__link" href="<?php echo esc_url( get_the_permalink($favorite) ); ?>"><?php echo esc_html( get_the_title($favorite) ); ?></a>
                                   </div>
                              <?php endforeach; ?>
                         </div>
                    </div>
               </div>

          <?php
          endforeach; ?>
     </div> <!--/.ldfc-courses-list-->

     <?php
     return ob_get_clean();

}

add_shortcode( 'favorite_button', 'ldfc_favorite_button_shortcode' );
function ldfc_favorite_button_shortcode() {

     if( !is_user_logged_in() ) {
          return false;
     }

     $cuser = wp_get_current_user();

     global $post;

     $args = array(
          'meta_key'     =>   '_favcon',
          'meta_value'   =>   $post->ID,
          'include'      =>   array( $cuser->ID )
     );

     $has_saved = get_users( $args );
     $class     = 'ldfc-button js-favcon-favorite';

     if( $has_saved ) {
          $class .= ' favcon-saved';
     }

     return '<div class="ldfc-shortcode"><div class="ldfc-favorite-button"><a href="#" class="' . $class . '" data-post_id="' . $post->ID . '"><span class="ld-icon">' . ldfc_heart_icon() . '</span> <span class="ld-favorite-label">' . __( 'Favorite', 'favcon' ) . '</a></a></div></div>';

}

add_filter( 'the_content', 'ldfc_automatic_favorite_button' );
function ldfc_automatic_favorite_button( $content ) {

     $settings = get_option( 'favcon_settings', array() );

     if( !isset( $settings['post_types'] ) || empty( $settings['post_types'] ) ) {
          return $content;
     }

     if( !in_array( get_post_type(), $settings['post_types'] ) ) {
          return $content;
     }

     if( isset( $settings['method'] ) && $settings['method'] == 'manual' ) {
          return $content;
     }

     if( isset( $settings['method'] ) && $settings['method'] == 'above_content' ) {
          $content = do_shortcode('[favorite_button]') . $content;
          return $content;
     }

     $content = $content . do_shortcode('[favorite_button]');

     return $content;

}

function ldfc_heart_icon( $echo = false ) {

     ob_start(); ?>

     <span class="ldfc-icon ldfc-icon-heart"></span>

     <?php
     if( $echo ) {
          echo ob_get_clean();
          return;
     }

     return ob_get_clean();

}

add_filter( 'learndash_content_tabs', 'ldfc_favorited_course_content_tab' );
function ldfc_favorited_course_content_tab( $tabs ) {

     if( !is_user_logged_in() ) {
          return $tabs;
     }

     $cuser = wp_get_current_user();
     $course_favorites = get_user_meta( $cuser->ID, '_favcon_course_' . get_the_ID() );

     if( !$course_favorites ) {
          return $tabs;
     }

     ob_start(); ?>

     <div class="ldfc-favorite-table ldfc-search-parent">
          <div class="ldfc-favorite-table-wrap">
               <div class="ldfc-favorite-header">
                    <?php esc_html_e( 'Favorites', 'favcon' ); ?>
                    <input type="text" name="ldfc-favorite-search" value="" class="ldfc-favorite-search-input" placeholder="<?php esc_attr_e( 'Search...', 'favcon' ); ?>">
               </div>
               <div class="ldfc-favorites">
                    <?php foreach( $course_favorites as $favorite ): ?>
                         <div class="ldfc-favorite <?php echo esc_attr( 'ldfc-favorite-' . $favorite ); ?>">
                              <a href="#" class="js-ldfc-unfavorite" data-post_id="<?php echo esc_attr($favorite); ?>"><?php ldfc_heart_icon( true ); ?></a>
                              <a class="ldfc-favorite__link" href="<?php echo esc_url( get_the_permalink($favorite) ); ?>">
                                   <span class="ldfc-favorite__title"><?php echo esc_html( get_the_title($favorite) ); ?></span>
                              </a>
                         </div>
                    <?php endforeach; ?>
               </div>
          </div>
     </div>

     <?php
     $content = ob_get_clean();

     $tabs[] = array(
          'id'      => 'favorites',
          'icon'    =>   'ld-icon-favorites',
          'label'   =>   __( 'Favorites', 'favcon' ),
          'content' =>   $content
     );

     return $tabs;

}

// add_action( 'learndash-lesson-components-after', 'ldfc_add_favorite_to_row' );
function ldfc_add_favorite_to_row( $post_id ) {

     if( !is_user_logged_in() ) {
          return;
     }

     $cuser         = wp_get_current_user();
     $favorites     = get_user_meta( $cuser->ID, '_favcon' );

     $has_favorited = ( in_array( $post_id, $favorites ) );

     if( $has_favorited ): ?>
          <span class="ldfc-icon ldfc-icon-heart ldfc-icon-heart-closed"></span> Favorite
     <?php
     endif;

}

add_action( 'wp_dashboard_setup', 'ldfc_dashboard_widgets' );
function ldfc_dashboard_widgets() {

     global $wp_meta_boxes;

     wp_add_dashboard_widget( 'ldfc_dashboard_stats_widget', __( 'Favorite Content', 'favcon' ), 'ldfc_dashboard_stats' );

}

function ldfc_dashboard_stats() {

     $total_favorites = get_option( 'ldfc_total_favorites', 0 );
     $post_type_favorites = get_option( 'ldfc_total_post_types', array() );

     $post_types = apply_filters( 'ldfc_dashboard_stats_post_types', array(
          'sfwd-courses',
          'sfwd-lessons',
          'sfwd-topic',
          'sfwd-quiz'
     ) ); ?>

     <div class="favcon-stats__wrap">
          <div class="favcon-stats__colorgroup">
               <div class="favcon-stat favcon-stat-total">
                    <div class="favcon-stat__value"><?php echo esc_html( $total_favorites ); ?></div>
                    <div class="favcon-stat__title"><?php esc_html_e( 'Total Favorites', 'favcon' ); ?></div>
               </div>

               <?php
               if( !empty( $post_type_favorites ) ): ?>
                    <div class="favcon-stats favcon-stats-post-types">
                         <?php
                         foreach( $post_type_favorites as $slug => $value ): $post_type = get_post_type_object($slug); ?>
                              <div class="favcon-stat favcon-stat-post-type">
                                   <div class="favcon-stat__value"><?php echo esc_html($value); ?></div>
                                   <div class="favcon-stat__title"><?php echo esc_html($post_type->labels->name); ?></div>
                              </div>
                         <?php endforeach;
                         if( count($post_type_favorites) % 2 !== 0 ): ?>
                              <div class="favcon-stat favcon-stat-post-type"></div>
                         <?php endif; ?>
                    </div>
               <?php endif; ?>
          </div>

          <?php
          foreach( $post_types as $post_type ):

               $args = apply_filters( 'ldfc_dashboard_stats_popular_courses_args', array(
                    'post_type'         =>   $post_type,
                    'posts_per_page'    =>   apply_filters( 'ldfc_dashboard_stats_popular_courses', 3 ),
                    'meta_query'        =>   array(
                         array(
                              'key' =>   '_favcon_favorites',
                              'compare' =>   '>',
                              'value'   =>   0,
                              'type'    =>   'NUMERIC'
                         )
                    ),
                    'orderby' => 'meta_value_num',
               ) );

               $popular_content = new WP_Query($args);

               if( $popular_content->have_posts() ):  $post_type_obj = get_post_type_object($post_type); ?>
                    <div class="favcon-stat favcon-stat-posts favcon-stats-post-<?php echo esc_attr($post_type); ?>">
                         <div class="favcon-stat-title"><?php echo esc_html_e( 'Top ', 'favcon' ) . ' ' . $post_type_obj->labels->name ; ?></div>
                         <div class="favcon-stat__posts">
                              <?php
                              while( $popular_content->have_posts() ): $popular_content->the_post(); $favorites = get_post_meta( get_the_ID(), '_favcon_favorites', true ); ?>
                                   <div class="favcon-stat__post">
                                        <a class="favcon-stat__post-link" href="<?php echo get_edit_post_link( get_the_ID() ); ?>" target="_new"><?php the_title(); ?></a>
                                        <span class="ldfc-count"><?php echo esc_html($favorites); ?> <span class="ldfc-icon ldfc-icon-heart-closed"></span> </span>
                                   </div>
                              <?php endwhile; ?>
                         </div>
                    </div>
               <?php endif;
          endforeach; wp_reset_query(); ?>
     </div>

     <?php

}
