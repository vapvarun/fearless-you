<?php
add_filter( 'learndash_admin_tabs', 'ldfc_settings_tab' );
function ldfc_settings_tab( $tabs ) {

     $tabs['favcon'] = array(
          'link'    =>   'admin.php?page=favorite-content',
          'name'    =>   __( 'Favorite Content', 'favcon' ),
          'id'      =>   'admin_page_favorite_content',
          'menu_link'    =>  'edit.php?post_type=sfwd-courses&page=sfwd-lms_sfwd_lms.php_post_type_sfwd-courses'
     );

     return $tabs;

}

add_action( 'admin_menu', 'ldfc_admin_menu' );
function ldfc_admin_menu() {
     add_submenu_page( 'learndash-lms-non-existant', __( 'Favorite Content', 'favcon' ), __( 'Favorite Content', 'favcon' ), 'manage_options', 'favorite-content', 'ldfc_admin_page' );
}

add_action( 'admin_init', 'ldfc_register_settings' );
function ldfc_register_settings() {
     register_setting( 'favcon_settings_group', 'favcon_settings' );
}

function ldfc_admin_page() {

     $fields = ldfc_get_settings_fields();

     ldfc_admin_assets(); ?>

     <div class="wrap snap-wrap">

          <div class="snap-branding">
               <a href="https://www.snaporbital.com/" target="_new">
                    <img src="<?php echo esc_url( LDFC_URL . '/assets/img/snaporbital-color.png'); ?>" alt="Snap Orbital">
               </a>
          </div>

          <form method="post" action="options.php">
               <div class="postbox snap-box">
     			<div class="snap-header snap-primary-header">
     				<h2><?php esc_html_e( 'Favorite Content', 'favcon' ); ?></h2>
     				<p class="snap-description"><?php esc_html_e( 'Give learners the ability to save their favorite content!', 'sfwd-lms' ); ?> <span class="snap-pipe">|</span> <a href="http://docs.snaporbital.com/" target="_new"><?php esc_html_e( 'Documentation', 'sfwd-lms' ); ?></a></p>
     			</div>
               </div>
               <?php
               settings_fields('favcon_settings_group');
               foreach( $fields as $group ): ?>
                    <div class="postbox snap-box">
                         <div class="snap-header">
                              <h2><?php echo esc_html( $group['title'] ); ?></h2>
                         </div>
                         <div class="snap-content">
                              <div class="snap-options">
                                   <?php
                                   foreach( $group['fields'] as $field ):
                                        ldfc_admin_setting($field);
                                   endforeach; ?>
                              </div>
                         </div>
                    </div>
               <?php endforeach; ?>

               <div class="submit snap-submit"><?php submit_button(); ?></div>

               <script>
     			jQuery(document).ready(function($) {
     				$( '.wp-color-picker' ).wpColorPicker();
     			});
     		</script>

          </form>

     </div>

     <?php

}

function ldfc_get_settings_fields() {

     $settings = get_option( 'favcon_settings', array() );

     $fields = apply_filters( 'ldfc_admin_settings', array(
          array(
               'title'   =>   __( 'License Key', 'favcon' ),
               'fields'  =>   array(
                    'license_key' => array(
                         'name'    =>   'favcon_license_key',
                         'type'    =>   'license_key',
                         'label'   =>   __( 'License Key', 'favcon' ),
                         'desc'    =>   __( 'Enter and activate your license key', 'favcon' ),
                         'value'    =>   isset($settings['favcon_license_key']) ? $settings['favcon_license_key'] : '' )
                    ),
               ),
          array(
               'title'   =>   __( 'Display', 'favcon' ),
               'fields'  =>   array(
                    'post_types'   =>   array(
                         'name'    =>   'post_types',
                         'type'    =>   'post_types',
                         'label'   =>   __( 'Enable on', 'favcon' ),
                         'desc'    =>   __( 'Which content types can users favorite?', 'favcon' ),
                         'value'   =>   !empty( $settings['post_types'] ) ? $settings['post_types'] : array()
                    ),
                    'method'   =>   array(
                         'name'    =>   'method',
                         'type'    =>   'select',
                         'label'   =>   __( 'Display favorite button', 'favcon' ),
                         'desc'    =>   __( 'How do you want the favorite button added to the page?', 'favcon' ),
                         'value'   =>   !empty( $settings['method'] ) ? $settings['method'] : '',
                         'options' => array(
                              'below_content' => __( 'Below the content', 'favcon' ),
                              'above_content' => __( 'Above the content', 'favcon' ),
                              'manual'        => __( 'Manually using [favorite_button] or widget', 'facon'),
                         )
                    ),
               )
          ),
          array(
               'title'   =>   __( 'Style', 'favcon' ),
               'fields'  =>   array(
                    'button_border_radius' => array(
                         'name'    =>   'button_border_radius',
                         'type'    =>   'number',
                         'label'   =>   __( 'Button border radius (in pixels)', 'favcon' ),
                         'value'   =>   !empty( $settings['button_border_radius'] ) ? $settings['button_border_radius'] : ''
                    ),
                    'table_border_radius' => array(
                         'name'    =>   'table_border_radius',
                         'type'    =>   'number',
                         'label'   =>   __( 'Table border radius (in pixels)', 'favcon' ),
                         'value'   =>   !empty( $settings['table_border_radius'] ) ? $settings['table_border_radius'] : ''
                    ),
                    'accent_color' =>   array(
                         'name'    =>   'accent_color',
                         'type'    =>   'color',
                         'label'   =>   __( 'Accent color', 'favcon' ),
                         'value'   =>   !empty( $settings['accent_color'] ) ? $settings['accent_color'] : ''
                    )
               )
          )
     ) );

     return $fields;

}

function ldfc_admin_setting( $field ) { ?>

     <div class="snap-input snap-input-<?php echo esc_attr($field['type']); ?>">
          <label class="snap-label"><?php echo esc_html( $field['label'] ); ?></label>
          <span class="snap-input-option snap-input-<?php echo esc_attr($field['type']); ?>">
               <?php call_user_func( 'ldfc_' . $field['type'] . '_callback', $field ); ?>
          </span>
     </div>

     <?php

}

function ldfc_license_key_callback( $field ) {

     $status = get_option( 'ldfc_license_status' );

     if( isset( $_GET['message'] ) ): ?>
          <div class="snap-status-message">
               <?php echo urldecode( $_GET['message'] ); ?>
          </div>
     <?php
     endif;
     if( isset( $_GET['ldfc_activate_response'] ) ): ?>
          <div class="snap-status-message">
               <pre>
                    <?php ldfc_check_activation_response(); ?>
               </pre>
          </div>
     <?php endif; ?>

     <div class="snap-input-option snap-text">
          <input type="text" name="favcon_settings[<?php echo esc_attr($field['name']); ?>]" value="<?php echo esc_attr($field['value']); ?>">
          <?php
          if( !empty($field['value'] ) ) {
               if( $status !== false && $status == 'valid' && !empty($field['value']) ) { ?>
                    <span style="color:green; padding: 5px 10px; background: #f9f9f9; border-radius: 3px; display: inline-block; margin: 5px 10px 0 0;"><?php _e( 'Active', 'favcon' ); ?></span>
                    <?php wp_nonce_field( 'ldfc_activate_nonce', 'ldfc_activate_nonce' ); ?>
                    <input type="submit" style="margin-top: 5px;" class="button-secondary" name="ldfc_license_deactivate" value="<?php esc_attr_e('Deactivate License','favcon'); ?>"/>
               <?php } else {
                         wp_nonce_field( 'ldfc_activate_nonce', 'ldfc_activate_nonce' ); ?>
                         <input type="submit"  style="margin-top: 5px;" class="button-secondary" name="ldfc_license_activate" value="<?php esc_attr_e('Activate License','favcon'); ?>"/>
               <?php }
          } ?>
     </div>

     <?php

}

function ldfc_post_types_callback( $field ) {

     $settings = get_option( 'favcon_settings', array() );
     $post_types = get_post_types( array( 'public' => true ), 'objects' ); ?>

     <div class="snap-input-option snap-checkboxes">
          <?php
          foreach( $post_types as $type ):

                if( $type->name == 'favcon' ):
                     continue;
                endif;

                $checked = '';

                if( isset($settings[$field['name']]) && !empty($settings[$field['name']]) && in_array( $type->name, $settings[$field['name']] ) ):
                     $checked = 'checked';
                endif; ?>

                <span class="snap-checkbox-option"><label for="ldfc_type_<?php echo $type->name; ?>"><input id="ldfc_type_<?php echo $type->name; ?>" type="checkbox" name="favcon_settings[<?php echo esc_attr($field['name']); ?>][<?php echo esc_attr($type->name); ?>]" value="<?php echo esc_attr( $type->name ); ?>" <?php echo $checked; ?> ><?php echo $type->labels->name; ?></label></span>
          <?php
          endforeach; ?>
     </div>

     <?php

}

function ldfc_select_callback( $field ) { ?>

     <div class="snap-input-option snap-select">
          <select name="favcon_settings[<?php echo esc_attr($field['name']); ?>]">
               <?php
               foreach( $field['options'] as $value => $label ):
                    $selected = ( $value == $field['value'] ? 'selected' : '' ); ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php echo $selected; ?>><?php echo esc_html($label); ?></option>
               <?php
               endforeach; ?>
          </select>
     </div>

     <?php

}

function ldfc_number_callback( $field ) {  ?>

     <div class="snap-input-option snap-text">
          <input type="number" name="favcon_settings[<?php echo esc_attr($field['name']); ?>]" value="<?php echo esc_attr($field['value']); ?>">
     </div>

     <?php
}

function ldfc_color_callback( $field ) { ?>

     <div class="snap-input-option snap-color">
          <input type="text" name="favcon_settings[<?php echo esc_attr($field['name']); ?>]" class="wp-color-picker" value="<?php echo esc_attr($field['value']); ?>">
     </div>

     <?php
}


function ldfc_activate_license() {
	// listen for our activate button to be clicked
	if( isset( $_POST['ldfc_license_activate'] ) ) {
		// run a quick security check
	 	if( ! check_admin_referer( 'ldfc_activate_nonce', 'ldfc_activate_nonce' ) )
			return; // get out if we didn't click the Activate button
		// retrieve the license from the database
          $settings = get_option( 'favcon_settings' );
          $license  = trim( $settings['favcon_license_key'] );
     	// data to send in our API request
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'item_name'  => urlencode( LDFC_ITEM_NAME ), // the name of our product in EDD
			'url'        => home_url()
		);
		// Call the custom API.
		$response = wp_remote_post( LDFC_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$get_error_message = $response->get_error_message();
			$message =  ( is_wp_error( $response ) && ! empty( $get_error_message ) ) ? $get_error_message : __( 'An error occurred, please try again.' );
		} else {
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			if ( false === $license_data->success ) {
				switch( $license_data->error ) {
					case 'expired' :
						$message = sprintf(
							__( 'Your license key expired on %s.' ),
							date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
						);
						break;
					case 'revoked' :
						$message = __( 'Your license key has been disabled.' );
						break;
					case 'missing' :
						$message = __( 'Invalid license.' );
						break;
					case 'invalid' :
					case 'site_inactive' :
						$message = __( 'Your license is not active for this URL.' );
						break;
					case 'item_name_mismatch' :
						$message = sprintf( __( 'This appears to be an invalid license key for %s.' ), EDD_SAMPLE_ITEM_NAME );
						break;
					case 'no_activations_left':
						$message = __( 'Your license key has reached its activation limit.' );
						break;
					default :
						$message = __( 'An error occurred, please try again.' );
						break;
				}
			}
		}
		// Check if anything passed on a message constituting a failure
		if ( ! empty( $message ) ) {
			$base_url = admin_url( 'admin.php?page=favorite-content' );
			$redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );
			wp_redirect( $redirect );
			exit();
		}
		// $license_data->license will be either "valid" or "invalid"
		update_option( 'ldfc_license_status', $license_data->license );
		wp_redirect( admin_url( 'admin.php?page=favorite-content' ) );
		exit();
	}
}
add_action('admin_init', 'ldfc_activate_license');

add_action('admin_init', 'ldfc_decactivate_license');
function ldfc_decactivate_license() {

	// listen for our activate button to be clicked
	if( isset( $_POST['ldfc_license_deactivate'] ) ) {
		// run a quick security check
	 	if( ! check_admin_referer( 'ldfc_activate_nonce', 'ldfc_activate_nonce' ) )
			return; // get out if we didn't click the Activate button
		// retrieve the license from the database

          $settings = get_option( 'favcon_settings' );

		$license = trim( $settings['favcon_license_key'] );
		// data to send in our API request
		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license,
			'item_name'  => urlencode( LDFC_ITEM_NAME ), // the name of our product in EDD
			'url'        => home_url()
		);

		$response = wp_remote_post( LDFC_STORE_URL , array( 'body' => $api_params, 'timeout' => 15, 'sslverify' => false ) );
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		update_option( 'ldfc_license_status', $license_data->license );
		wp_redirect( admin_url( 'admin.php?page=favorite-content' ) );
		exit();

	}
}
