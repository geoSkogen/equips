<?php
/*
Plugin Name:  equips
Description:  Extensible Queries of URL Parameters for Shortcode
Version:      2020.01.10
Author:       City Ranked Media
Author URI:
Text Domain:  equips
*/

defined( 'ABSPATH' ) or die( 'We make the path by walking.' );

if ( !class_exists( 'Equips_Options_Init' ) ) {
   include_once 'admin/eq_options_init.php';
   add_action(
    'admin_menu',
    array('Equips_Options_Init','equips_register_menu_page')
  );
}
if ( !class_exists( 'Equips_Settings_Init' ) ) {
   include_once 'admin/eq_settings_init.php';
   add_action(
     'admin_init',
     array('Equips_Settings_Init','settings_api_init')
   );
}

if ( !class_exists( 'Equips_Local_Monster' ) ) {
   include_once 'classes/equips_local_monster.php';
}

if ( !class_exists( 'Equips_Stasis' ) ) {
   include_once 'classes/equips_stasis.php';
}

Equips_Stasis::init_equips(Equips_Settings_Init::get_field_count());

function local_utm_content_gf_injector() {
  wp_register_script('equips-utm-content-gf-injector', plugin_dir_url(__FILE__) . 'js/' . 'equips-utm-content-gf-injector' . '.js');
  wp_enqueue_script('equips-utm-content-gf-injector');
}

add_action( 'wp_enqueue_scripts','local_utm_content_gf_injector');

//register_activation_hook( __FILE__, ['Equips_Local_Monster','eq_activate_db'] );
