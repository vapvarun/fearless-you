<?php
add_action( 'wp_ajax_ldfc_remove_favorite', 'ldfc_remove_favorite' );
function ldfc_remove_favorite( $post = null ) {

     if( wp_doing_ajax() ) {

          $post_id = $_POST['post_id'];
          $post = get_post( $post_id );

     } else {

          if( $post == null ) {
               global $post;
          }

          if( is_int($post) ) {
               $post = get_post( $post );
          }

     }

     if( !is_user_logged_in() ) {

          wp_send_json_error( array( 'success' => false, 'reason' => 'authentication' ) );

          return false;

     }

     $cuser = wp_get_current_user();
     $learndash_types = ldfc_get_learndash_types();

     if( in_array( get_post_type($post), $learndash_types ) ) {
          $course_id = get_post_meta( $post->ID, 'course_id', true );
     }

     do_action( 'ldfc_before_favorite_delete', $post, $cuser );

     delete_user_meta( $cuser->ID, '_favcon', $post->ID );

     ldfc_decrement_content( $post->ID );

     if( isset($course_id) && $course_id ) {

          delete_user_meta( $cuser->ID, '_favcon_course_' . $course_id, $post->ID );

          // Check to see if this list is now empty
          $course_favorites = get_user_meta( $cuser->ID, '_favcon_course_' . $course_id );
          if( empty($course_favorites) || !$course_favorites ) {
               delete_user_meta( $cuser->ID, '_favcon_courses', $course_id );
          }

          ldfc_decrement_content( $course_id );

     }

     ldfc_decrement_total();
     ldfc_decrement_post_types( $post->post_type );

     do_action( 'ldfc_after_favorite_delete', $post, $cuser );

     wp_send_json_success( array( 'post_id' => $post->ID ) );

}

add_action( 'wp_ajax_ldfc_save_favorite', 'ldfc_save_as_favorite' );
function ldfc_save_as_favorite( $post = null ) {

     if( wp_doing_ajax() ) {

          $post_id = $_POST['post_id'];
          $post = get_post( $post_id );

     } else {

          if( $post == null ) {
               global $post;
          }

          if( is_int($post) ) {
               $post = get_post( $post );
          }

     }

     if( !is_user_logged_in() ) {

          wp_send_json_error( array( 'success' => false, 'reason' => 'authentication' ) );

          return false;

     }

     $cuser = wp_get_current_user();

     $args = array(
          'meta_key'     =>   '_favcon',
          'meta_value'   =>   $post->ID,
          'include'      =>   array( $cuser->ID )
     );

     $has_saved = get_users( $args );

     if( $has_saved ) {

          wp_send_json_error( array( 'success' => false, 'reason' => 'saved' ) );

          return false;

     }

     $learndash_types = ldfc_get_learndash_types();

     if( in_array( get_post_type($post), $learndash_types ) ) {
          $course_id = get_post_meta( $post->ID, 'course_id', true );
     }

     do_action( 'ldfc_before_favorite_saved', $post, $cuser );

     add_user_meta( $cuser->ID, '_favcon', $post->ID );

     // Stats!
     ldfc_increment_content( $post->ID );

     if( isset($course_id) && $course_id ) {

          // Add the content to the course list
          add_user_meta( $cuser->ID, '_favcon_course_' . $course_id, $post->ID );

          // Add to the index of courses saved -- This needs be unique
          $args = array(
               'meta_key'     =>   '_favcon_courses',
               'meta_value'   =>   $course_id,
               'include'      =>   array( $cuser->ID )
          );

          $has_saved = get_users( $args );

          if( !$has_saved ) {
               add_user_meta( $cuser->ID, '_favcon_courses', $course_id );
          }

          // Global stats

          ldfc_increment_content( $course_id );
          ldfc_increment_post_types( $post->post_type );

     }

     ldfc_increment_total();

     do_action( 'ldfc_after_favorite_saved', $post, $cuser  );

     wp_send_json_success( array( 'succes' => true ) );

}


function ldfc_get_learndash_types() {

     return apply_filters( 'ldfc_learndash_types', array(
          'sfwd-courses',
          'sfwd-lessons',
          'sfwd-topic',
          'sfwd-quiz',
          'sfwd-assignments'
     ) );

}

function ldfc_increment_content( $post_id = null ) {

     if( $post_id == null ) {
          $post_id = get_the_ID();
     }

     $favorites = get_post_meta( $post_id, '_favcon_favorites', true );

     if( !$favorites ) {
          $favorites = 1;
     } else {
          $favorites++;
     }

     update_post_meta( $post_id, '_favcon_favorites', apply_filters( 'ldfc_increment_content_count', $favorites ) );

     return true;

}

function ldfc_decrement_content( $post_id = null ) {

     if( $post_id == null ) {
          $post_id = get_the_ID();
     }

     $favorites = get_post_meta( $post_id, '_favcon_favorites', true );

     if( !$favorites ) {
          $favorites = 0;
     } else {
          $favorites--;
     }

     update_post_meta( $post_id, '_favcon_favorites', apply_filters( 'ldfc_decrement_content_count', $favorites ) );

     return true;

}

function ldfc_increment_total() {

     $total_favorites = intval(get_option( 'ldfc_total_favorites', 0 ));
     $total_favorites++;

     update_option( 'ldfc_total_favorites', $total_favorites );

}

function ldfc_decrement_total() {

     $total_favorites = intval(get_option( 'ldfc_total_favorites', 0 ));

     if( $total_favorites !== 0 ) {

          $total_favorites--;
          update_option( 'ldfc_total_favorites', $total_favorites );

     }

}

function ldfc_increment_post_types( $post_type = null ) {

     if( $post_type == null ) {
          return false;
     }

     $post_type_favorites = get_option( 'ldfc_total_post_types', array() );

     if( !isset( $post_type_favorites[$post_type] ) ) {
          $post_type_favorites[ $post_type ] = 0;
     }

     $post_type_favorites[ $post_type ]++;

     update_option( 'ldfc_total_post_types', $post_type_favorites );

}

function ldfc_decrement_post_types( $post_type = null ) {

     if( $post_type == null ) {
          return false;
     }

     $post_type_favorites = get_option( 'ldfc_total_post_types', array() );

     if( !isset( $post_type_favorites[$post_type] ) ) {
          $post_type_favorites[ $post_type ] = 0;
     }

     if( $post_type_favorites[ $post_type ] !== 0 ) {
          $post_type_favorites[ $post_type ]++;
          update_option( 'ldfc_total_post_types', $post_type_favorites );
     }

}
