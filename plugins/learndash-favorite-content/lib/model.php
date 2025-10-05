<?php
// Register Custom Post Type
function ldfc_post_types() {

	$labels = array(
		'name'                  => _x( 'Favorited Content', 'Post Type General Name', 'text_domain' ),
		'singular_name'         => _x( 'Favorited Content', 'Post Type Singular Name', 'text_domain' ),
		'menu_name'             => __( 'Favorited Content', 'text_domain' ),
		'name_admin_bar'        => __( 'Favorited Content', 'text_domain' ),
		'archives'              => __( 'Favorited Content Archives', 'text_domain' ),
		'attributes'            => __( 'Favorited Content Attributes', 'text_domain' ),
		'parent_item_colon'     => __( 'Parent Favorited Content:', 'text_domain' ),
		'all_items'             => __( 'All Favorited Content', 'text_domain' ),
		'add_new_item'          => __( 'Add New Favorited Content', 'text_domain' ),
		'add_new'               => __( 'Add New', 'text_domain' ),
		'new_item'              => __( 'New Favorited Content', 'text_domain' ),
		'edit_item'             => __( 'Edit Favorited Content', 'text_domain' ),
		'update_item'           => __( 'Update Favorited Content', 'text_domain' ),
		'view_item'             => __( 'View Favorited Content', 'text_domain' ),
		'view_items'            => __( 'View Favorited Content', 'text_domain' ),
		'search_items'          => __( 'Search Favorited Content', 'text_domain' ),
		'not_found'             => __( 'Not found', 'text_domain' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'text_domain' ),
		'featured_image'        => __( 'Featured Image', 'text_domain' ),
		'set_featured_image'    => __( 'Set featured image', 'text_domain' ),
		'remove_featured_image' => __( 'Remove featured image', 'text_domain' ),
		'use_featured_image'    => __( 'Use as featured image', 'text_domain' ),
		'insert_into_item'      => __( 'Insert into item', 'text_domain' ),
		'uploaded_to_this_item' => __( 'Uploaded to this item', 'text_domain' ),
		'items_list'            => __( 'Items list', 'text_domain' ),
		'items_list_navigation' => __( 'Items list navigation', 'text_domain' ),
		'filter_items_list'     => __( 'Filter items list', 'text_domain' ),
	);
	$args = array(
		'label'                 => __( 'User Favorite', 'text_domain' ),
		'description'           => __( 'Post Type Description', 'text_domain' ),
		'labels'                => $labels,
		'supports'              => array( 'title' ),
		'hierarchical'          => false,
		'public'                => false,
		'show_ui'               => false,
		'show_in_menu'          => true,
		'menu_position'         => 5,
		'show_in_admin_bar'     => false,
		'show_in_nav_menus'     => false,
		'can_export'            => true,
		'has_archive'           => true,
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'page',
	);
	register_post_type( 'favcon', $args );

}
add_action( 'init', 'ldfc_post_types', 0 );

add_filter( 'manage_sfwd-quiz_posts_columns', 'ldfc_course_favorites_post_columns' );
add_filter( 'manage_sfwd-topic_posts_columns', 'ldfc_course_favorites_post_columns' );
add_filter( 'manage_sfwd-lessons_posts_columns', 'ldfc_course_favorites_post_columns' );
add_filter( 'manage_sfwd-courses_posts_columns', 'ldfc_course_favorites_post_columns' );
function ldfc_course_favorites_post_columns( $columns ) {

	$new_columns = array();

	foreach( $columns as $key => $value ) {

		if( $key == 'date' ) {
			$new_columns['favorites'] = __( 'Favorites', 'favcon' );
		}

		$new_columns[ $key ] = $value;

	}

	return $new_columns;

}

add_filter( 'manage_sfwd-quiz_posts_custom_column', 'ldfc_favorites_post_columns_content', 10, 2 );
add_filter( 'manage_sfwd-topic_posts_custom_column', 'ldfc_favorites_post_columns_content', 10, 2 );
add_filter( 'manage_sfwd-lessons_posts_custom_column', 'ldfc_favorites_post_columns_content', 10, 2 );
add_filter( 'manage_sfwd-courses_posts_custom_column', 'ldfc_favorites_post_columns_content', 10, 2 );
function ldfc_favorites_post_columns_content( $column, $post_id ) {

	if( 'favorites' === $column ) {

		$favorites = get_post_meta( $post_id, '_favcon_favorites', true );
		$favorites = ( $favorites ? $favorites : 0 );

		echo '<span class="ldfc-icon ldfc-icon-heart ldfc-icon-heart-closed"></span> ' . $favorites;

	}

}
