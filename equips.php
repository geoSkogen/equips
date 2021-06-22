<?php
/*
Plugin Name:  equips
Description:  Extensible Queries of URL-Injected Placenames for Shortcode
Version:      2021.06.20
Author:       Joseph Scoggins
Author URI:   https://github.com/geoSkogen/equips
Text Domain:  equips
*/

defined( 'ABSPATH' ) or die( 'We make the path by walking.' );

if (is_admin()) {

  $admin = new stdClass;

  if ( !class_exists( 'Equips_Options' ) ) {
     include_once 'admin/eq_options.php';

     $admin->options = new Equips_Options();
  }

  if ( !class_exists( 'Equips_Settings' ) ) {
     include_once 'admin/eq_settings.php';

     $admin->settings = new Equips_Settings();
  }
} else {
  // frontend
  if ( !class_exists( 'Equips' ) ) {
     include_once 'classes/equips.php';
     
     $equips = new Equips();
  }
  //
//
}

if ( !class_exists( 'Equips_DB_Conn' ) ) {
   include_once 'classes/equips_db_conn.php';

   $eq_db_conn = new Equips_DB_Conn();
}

register_activation_hook( __FILE__, [$eq_db_conn,'eq_init_database'] );
