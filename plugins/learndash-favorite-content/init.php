<?php
/*
Plugin Name: Favorite Content for LearnDash
Plugin URI:  http://www.snaporbital.com/favorite-content/
Description: Allow your students to mark content as favorites to quickly revisit later!
Version:     1.0.3
Author:      SnapOrbital
Author URI:  http://www.snaporbital.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: favcon
Domain Path: /languages
*/

$ld_constants = array(
	'LDFC_URL'		=>	plugin_dir_url( __FILE__ ),
	'LDFC_PATH'		=>	plugin_dir_path( __FILE__ ),
	'LDFC_STORE_URL'	=>	'https://www.snaporbital.com',
	'LDFC_ITEM_NAME'	=>	'Favorite Content',
	'LDFC_VER'		=>	'1.0.3',
);

foreach( $ld_constants as $constant => $value ) {
	if( !defined($constant) ) define( $constant, $value );
}

if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
	// load our custom updater
	include( dirname( __FILE__ ) . '/EDD_SL_Plugin_Updater.php' );
}

do_action( 'ldfc_note_before_initialize' );

$inits = array(
     'admin',
     'assets',
	'model',
     'view',
     'controller'
);

foreach( $inits as $init ) {
	include_once( 'lib/' . $init . '.php' );
}

do_action( 'ldfc_after_initialize' );

// retrieve our license key from the DB
$license_key = trim( get_option( 'ldfc_notes_license_key' ) );

add_action( 'plugins_loaded', 'ldfc_translation_i18ize' );
function ldfc_translation_i18ize() {
	load_plugin_textdomain( 'favcon', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

// setup the updater
$edd_updater = new EDD_SL_Plugin_Updater( LDFC_STORE_URL, __FILE__, array(
		'version' 	 => LDFC_VER, 		// current version number
		'license' 	 => $license_key, 	// license key (used get_option above to retrieve from DB)
		'item_name'     => LDFC_ITEM_NAME, 	// name of this plugin
		'author' 		 => 'Snap Orbital',  // author of this plugin
		'url'           => home_url()
	)
);
