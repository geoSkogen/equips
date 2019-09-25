<?php
/*
Plugin Name:  equips
Description:  Extensible Queries of URL Parameters for Shortcode
Version:      2019.09.19
Author:       City Ranked Media
Author URI:
Text Domain:  equips
*/

//Global Namespace - update to OOP protocal in future versions

$eq_store = array(
  'indices' => array(),
  'params' => array()
);

// Activation - instantiate & populate database

function import_csv_geo($filename) {
  $subdir = "resources";
  $result = [];
  $key = "";
  $valid_data = [];
  if (($handle = fopen(__DIR__ . "/" . $subdir . "/" . $filename . ".csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
      $valid_data = array(
        'criteria_id' => strval($data[0]),
        'city_name' => strval($data[1]),
        'branch_name' => strval($data[3]),
        'geo_title' => strval($data[4]),
        'service_area' => strval($data[5])
      );
      array_push($result, $valid_data);
    }
    fclose($handle);
    return $result;
  } else {
    error_log('could not open file');
    return false;
  }
}

function import_csv($filename,$table_type) {
  $subdir = "resources";
  $result = [];
  $key = "";
  $valid_data = [];
  if (($handle = fopen(__DIR__ . "/" . $subdir . "/" . $filename . ".csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
      switch($table_type) {
        case 'geo' :
          $valid_data = array(
            'criteria_id' => strval($data[0]),
            'city_name' => strval($data[1]),
            'branch_name' => strval($data[3]),
            'geo_title' => strval($data[4]),
            'service_area' => strval($data[5])
          );
          array_push($result, $valid_data);
          break;
        case 'zip' :
          $result[$data[0]] = $data[1];
          break;
        default :
          error_log('unsupported table type for wp_eq_equips');
          return false;
      }
    }
    fclose($handle);
    return $result;
  } else {
    error_log('could not open file');
    return false;
  }
}

function eq_activate_db () {
  global $wpdb;

  $table_rows = array();
  $import_filename = "geo2-csvnest-row";
  $table_name = $wpdb->prefix . "eq_equips";
  $charset_collate = $wpdb->get_charset_collate();

  $sql = "CREATE TABLE $table_name (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    criteria_id mediumint(7) NOT NULL,
    city_name text NOT NULL,
    branch_name text NOT NULL,
    geo_title text NOT NULL,
    service_area varchar(255) DEFAULT '' NOT NULL,
    PRIMARY KEY  (id)
    ) $charset_collate;";
  $test_query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );
  if ( $wpdb->get_var( $test_query ) == $table_name ) {
    error_log('db table: ' . $table_name . ' is already associated with this install');
  } else {
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    error_log('created new db table: ' . $table_name . ' for this install');
    $table_rows = import_csv($import_filename, 'geo');
    foreach($table_rows as $row) {
      $wpdb->insert($table_name, $row);
    }
    error_log('added ' . strval(count($table_rows)) . ' rows to db table : ' . $table_name . ' - for this install');
  }
}

register_activation_hook( __FILE__, 'eq_activate_db' );


// Use with GeoPlugin API
function get_user_ip() {
    if(!empty($_SERVER['HTTP_CLIENT_IP'])){
        //ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
        //ip pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }else{
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function validate_ip($str) {
  $result = $str;
  return $result;
}

function get_geo_zip() {
  $found_ip = get_user_ip();
  $valid_ip = validate_ip($found_ip);
  $geo_data = unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip='. $found_ip));
  $zip_data = unserialize(
    file_get_contents(
      'http://www.geoplugin.net/extras/postalcode.gp?lat=' .
        $geo_data['geoplugin_latitude'] . '&lon='. $geo_data['geoplugin_longitude'] .
        '&format=php'
    )
  );
  return $zip_data['geoplugin_postCode'];
  //error_log(var_dump($zip_data));
}


function eq_locale_lookup($num_arg) {
  global $wpdb;
  $result = "";
  $table_name = $wpdb->prefix . "eq_equips";
  $result = $wpdb->get_row(
    "SELECT * FROM wp_eq_equips WHERE criteria_id = " . $num_arg,
    ARRAY_A
  );
  return ($result) ?  $result : '';
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

function do_equips($num_str) {
  $eq_options = get_option('equips');
  $result = $eq_options['fallback_' . $num_str];
  $raw_query = '';
  $stripped_query = '';
  //NOTE: RE: security - this plugin is currently only configured to lookup locations
  //$stripped_query requires further validation before being injected into text content
  if (get_query_var($eq_options['param_' . $num_str], false)) {
    $raw_query = get_query_var($eq_options['param_' . $num_str], false);
    $stripped_query = strip_tags($raw_query);
    // location - error & content handling for location
    //must be numeric input; returns fallback if not found -
    //Update to array of discrete param handling functions per param type in future versions
    if ($eq_options['param_' . $num_str] === 'location') {
      if (is_numeric($stripped_query)) {
        $loc_data = eq_locale_lookup($stripped_query);
        $result = ($loc_data) ? $loc_data['city_name'] : $result;
      }
    }
  // end location -
  }
  return $result;
}

// begin GEOBLOCK SERVICE AREA
//Currently using hard-coded shortcode:

//[eq_zip_nest]

function iterate_zip_nest($name_arr) {
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

function eq_shortcode_handler_zip_nest() {
  $result = "<div>Pittsburgh, PA | Baltimore, MD | Buffalo, NY | Reading, PA | Manchester, NH | Norristown, PA | Nashua, NH | Parkville, MD | Portland, ME | Niagara Falls, NY</div>";
  $raw_query = '';
  $stripped_query = '';
  if (get_query_var('location', false)) {
    $raw_query = get_query_var('location', false);
    $stripped_query = strip_tags($raw_query);
    if (is_numeric($stripped_query)) {
      $loc_data = eq_locale_lookup($stripped_query);
      $zip_nest = ($loc_data['service_area']) ?
        explode(',', $loc_data['service_area']) :
        array(
          "Pittsburgh, PA","Baltimore, MD","Buffalo, NY","Reading, PA",
          "Manchester, NH", "Norristown, PA", "Nashua, NH", "Parkville, MD",
          "Portland, ME", "Niagara Falls, NY"
        );
      $result = iterate_zip_nest($zip_nest);
    }
  }
  return $result;
}

add_shortcode('eq_zip_nest','eq_shortcode_handler_zip_nest');

//[eq_branch_name]

function eq_shortcode_handler_branch_name() {
  $result = "Maine, New Hampshire, Maryland, Pennsylvania and New York";
  if (get_query_var('location', false)) {
    $raw_query = get_query_var('location', false);
    $stripped_query = strip_tags($raw_query);
    if (is_numeric($stripped_query)) {
      $loc_data = eq_locale_lookup($stripped_query);
      $result = ($loc_data['geo_title']) ?
        $loc_data['geo_title'] :
        "Maine, New Hampshire, Maryland, Pennsylvania and New York";
    }
  }
  return $result;
}

add_shortcode('eq_branch_name','eq_shortcode_handler_branch_name');

// end GEOBLOCK SERVICE AREA

//-- not the intended design; still pursuing a workaround to hard-coded shortcode handlers.
//-- see README.txt

function eq_shortcode_handler_1() {
  return do_equips('1');
}

function eq_shortcode_handler_2() {
  return do_equips('2');
}

function eq_shortcode_handler_3() {
  return do_equips('3');
}

function eq_shortcode_handler_4() {
  return do_equips('4');
}

function eq_shortcode_handler_5() {
  return do_equips('5');
}

function equips_triage() {
  global $eq_store;
  $eq_options = get_option('equips');
  add_filter( 'query_vars', function ( $vars ) {
    global $eq_store;
    $vars = array_merge($vars, $eq_store['params']);
    foreach ($vars as $var) {
      //error_log("query var added: " . $var);
    }
    return $vars;
  });
  foreach ($eq_store['indices'] as $store_key) {
    add_shortcode(
      $eq_options['shortcode_' . $store_key], 'eq_shortcode_handler_' . $store_key);
    //error_log('adding shortcode: ' . $eq_options['shortcode_' . $store_key]);
  }
  return;
}

function init_equips($counter) {
  global $eq_store;
  $eq_num_str = "";
  $eq_options = get_option('equips');
  if ($eq_options) {
    for ($i = 1; $i < $counter + 1; $i++) {
      $eq_num_str = strval($i);
      if ($eq_options['param_' . $eq_num_str] && $eq_options['shortcode_' . $eq_num_str]) {
        $eq_store['indices'][] = $eq_num_str;
        $eq_store['params'][] = $eq_options['param_' . $eq_num_str];
      }
    }
    equips_triage();
    return true;
  } else {
    return false;
  }
}

init_equips(Equips_Settings_Init::$field_count);
