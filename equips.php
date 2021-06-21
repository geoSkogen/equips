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
  // frontend
} else {


  if ( !class_exists( 'Equips' ) ) {
     //include_once 'classes/equips.php';
     $equips = new stdClass;
  }
  //
//  $equips = new Equips();
  //$equips->db_conn = new Equips_DB_Conn();
}

if ( !class_exists( 'Equips_DB_Conn' ) ) {
   include_once 'classes/equips_db_conn.php';

   $eq_db_conn = new Equips_DB_Conn();

}


function local_utm_content_gf_injector() {
  //included for hidden forms inside parent elements containing classnames:
  //query_var_container, query_var_gclid_container, query_var_msclkid_container,
  //utm_source_container, utm_medium_container, utm_campaign_container, utm_content_container

  wp_register_script(
    'equips-utm-content-gf-injector',
    plugin_dir_url(__FILE__) . 'js/' . 'equips-utm-content-gf-injector' . '.js'
  );
  wp_enqueue_script('equips-utm-content-gf-injector');
}

add_action( 'wp_enqueue_scripts','local_utm_content_gf_injector');

register_activation_hook( __FILE__, [$eq_db_conn,'eq_create_db_tables'] );
