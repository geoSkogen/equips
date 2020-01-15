<?php
/*
Plugin Name:  equips_1
Description:  Extensible Queries of URL Parameters for Shortcode
Version:      2020.01.12
Author:       City Ranked Media
Author URI:
Text Domain:  equips
*/

defined( 'ABSPATH' ) or die( 'We make the path by walking.' );

//Global Namespace

$eq_store = array(
  'indices' => array(),
  'params' => array()
);

//Locale lookup

function import_csv_geo($filename, $num_arg) {
  $subdir = "resources";
  $result = array();
  $key = "";
  $valid_data = [];
  if (($handle = fopen(__DIR__ . "/" . $subdir . "/" . $filename . ".csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
      $valid_data = [];
      $key = strval($data[0]);
      if ($key === $num_arg) {
        $valid_data['city_name'] = strval($data[1]);
        $valid_data['country_code'] = strval($data[2]);
        $valid_data['branch_name'] = strval($data[3]);
        $valid_data['geo_title'] = strval($data[4]);
        $valid_data['branch_region'] = strval($data[5]);
        $valid_data['branch_phone'] = strval($data[6]);
        $valid_data['service_area'] = explode(",",$data[7]);
        break;
      }
    }
    fclose($handle);
    return $valid_data;
  } else {
    error_log('could not open file');
    return false;
  }
}

function eq_locale_lookup($num_arg) {
  $result = "";
  $eq_locales = import_csv_geo('geo6.1', $num_arg);
  $result = $eq_locales ? : $result;
  return $result;
}

//Admin

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

//Procedure - shortcode-URL-param association

function do_equips_location($db_slug) {
  $result = '';
  if (get_query_var('location', false)) {
    $raw_query = get_query_var('location', false);
    $stripped_query = strip_tags($raw_query);
    if (is_numeric($stripped_query)) {
      $loc_data = eq_locale_lookup($stripped_query);
      if ($loc_data) {
        $result = $loc_data[$db_slug];
      }
    }
  } else {
    // do geopluign lookup ()
  }
  return $result;
}

function do_equips($num_str) {
  $eq_options = get_option('equips');
  $fallback = $eq_options['fallback_' . $num_str] ? : '';
  $result = '';
  //NOTE: RE: security - this plugin is currently only configured to lookup locations
  //$stripped_query requires further validation before being injected into text content
  if (get_query_var($eq_options['param_' . $num_str], false)) {
    switch ($eq_options['param_' . $num_str]) {
      case 'location' :
        $result = do_equips_location('city_name');
        break;
    }
  }
  return $result ? : $fallback;
}

// begin GEOBLOCK SERVICE AREA

function iterate_service_area($name_arr) {
  $result = "";
  $result .= "<div>";
  for($i = 0; $i < count($name_arr); $i++) {
    $result .= "<span> ";
    $result .= $name_arr[$i];
    $result .= " </span>";
    $result .= ($i === count($name_arr)-1) ? "" : "|";
  }
  $result .= "</div>";
  return $result;
}

function eq_shortcode_handler_service_area() {
  $eq_geo_options = get_option('equips_geo');
  $fallback = $eq_geo_options['service_area'] ? : '';
  $service_area =  do_equips_location('service_area');
  return ($service_area) ?
    iterate_service_area( $service_area ) : $fallback;
}

function eq_shortcode_handler_region() {
  $eq_geo_options = get_option('equips_geo');
  $fallback = $eq_geo_options['region'] ? : '';
  $region = do_equips_location('branch_region');
  return ($region) ? $region : $fallback;
}

function eq_shortcode_handler_locale() {
  $eq_geo_options = get_option('equips_geo');
  $fallback = $eq_geo_options['locale'] ? : '';
  $locale = do_equips_location('geo_title');
  return $locale ? : $fallback;
}

function eq_shortcode_handler_phone( $atts = array() ) {

  $eq_geo_options = get_option('equips_geo');
  //NOTE:recommend ultimate fallback phone number here in place of ''
  $fallback = $eq_geo_options['phone'] ? : '';
  $phone = do_equips_location('branch_phone');
  $phone = $phone ? : $fallback;
  $href = str_replace( ['(',')','-','.',' '] ,"", $phone );
  $icon = '';
  $phone_bar_text = $eq_geo_options['phone_bar_text'] ? : '';

  extract(shortcode_atts(array(
     'class' => '',
     'icon' => ''
    ), $atts));

  if ($atts) {
    $icon = ($atts['icon']) ?
      '<i class="fa fa-phone" aria-hidden="true"></i>' :
      $icon;
    $class = $atts['class'] ? : 'no_class';
  } else {
    $icon = '';
    $class = 'no_class';
  }
  if ($eq_geo_options['include_phone_bar'] === 'include') {
    add_action( 'wp_footer' , function () use ($href, $phone, $phone_bar_text) {
      $result = "<div id='sticky-bar'><p>";
      $result .= "<a href='tel:+1$href'><span class='sticky-main-txt-desk display-span'>";
      $result .= "<i class='fa fa-phone' aria-hidden='true'></i> $phone </span>";
      $result .= "<span class='sb-deal-text'>$phone_bar_text</span></a></p></div>";
      echo $result;
    });
  }
  return "<a class='$class' href='tel:+1" . $href . "' >$icon $phone</a>";
}

// end GEOBLOCK SERVICE AREA
// begin INIT - plugin baseline actions

function init_equips_wp_scripts() {
  global $eq_store;
  wp_register_script('equips-append-hrefs',plugin_dir_url(__FILE__) . 'js/equips-append-hrefs.js', array('jquery'));
  wp_localize_script( 'equips-append-hrefs', 'equips_settings_obj',
    array(
      'params' => $eq_store['params']
    )
  );
  wp_enqueue_script('equips-append-hrefs');
}

function equips_triage() {
  global $eq_store;
  $eq_options = get_option('equips');
  $eq_geo_options = get_option('equips_geo');
  add_action('wp_enqueue_scripts','init_equips_wp_scripts');

  add_filter( 'query_vars', function ( $vars ) {
    global $eq_store;
    $vars = array_merge($vars, $eq_store['params']);
    return $vars;
  });
  foreach ($eq_store['indices'] as $store_key) {
    add_shortcode(
      $eq_options['shortcode_' . $store_key],
      function () use ($store_key) {  return do_equips($store_key); }
    );
  }
  if ($eq_geo_options['phone_shortcode']) {
    add_shortcode(
      $eq_geo_options['phone_shortcode'], 'eq_shortcode_handler_phone'
    );
  }
  if ($eq_geo_options['locale_shortcode']) {
    add_shortcode(
      $eq_geo_options['locale_shortcode'], 'eq_shortcode_handler_locale'
    );
  }
  if ($eq_geo_options['region_shortcode']) {
    add_shortcode(
      $eq_geo_options['region_shortcode'], 'eq_shortcode_handler_region'
    );
  }
  if ($eq_geo_options['service_area_shortcode']) {
    add_shortcode(
      $eq_geo_options['service_area_shortcode'], 'eq_shortcode_handler_service_area'
    );
  }
  return;
}

function init_equips($counter) {
  global $eq_store;
  $eq_num_str = "";
  $eq_options = get_option('equips');
  for ($i = 1; $i < $counter + 1; $i++) {
    $eq_num_str = strval($i);
    if (
        ( isset($eq_options['param_' . $eq_num_str]) &&
          "" != $eq_options['param_' . $eq_num_str]
        ) &&
        ( isset($eq_options['shortcode_' . $eq_num_str]) &&
          "" != $eq_options['shortcode_' . $eq_num_str]
        )
       )
       {
      $eq_store['indices'][] = $eq_num_str;
      $eq_store['params'][] = $eq_options['param_' . $eq_num_str];
    }
  }
  equips_triage();
}

init_equips(Equips_Settings_Init::get_field_count());
