<?php
/*
Plugin Name:  equips
Description:  Extensible Queries of URL Parameters for Shortcode
Version:      2019.09.18
Author:       City Ranked Media
Author URI:
Text Domain:  equips
*/

//Global Namespace - update to OOP protocal in future versions

$eq_store = array(
  'indices' => array(),
  'params' => array()
);

// Helper Functions - for geolocation lookup

function import_csv_columns($filename, $keys) {
  $subdir = "resources";
  $csvnest = strpos($filename,'-csvnest-');
  $result = ($csvnest) ? array() : array(
      $keys[0] => array(),
      $keys[1] => array()
    );
  $row_index = -1;
  $key = "";
  $valid_data = [];
  if (($handle = fopen(__DIR__ . "/" . $subdir . "/" . $filename . ".csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
      $row_index += 1;
      $valid_data = [];
      if ($csvnest) {
        for ($i = 0; $i < count($data); $i++) {
          if (strpos($filename,'-row')) {
            switch ($i) {
              case 0:
                $key = strval($data[$i]);
                break;
              case 1:
                $valid_data['city_name'] = strval($data[$i]);
                break;
              case 2:
                $valid_data['branch_name'] = strval($data[$i]);
                break;
              case 3:
                $valid_data['service_area'] = explode(",",$data[$i]);
                break;
            }
            if ($i === count($data)-1) {
              $result[$key] = $valid_data;
            }
          } else if (strpos($filename,'-col')) {
            if ($row_index === 0) {
              $result[strval($data[$i])] = array();
              array_push($keys, $data[$i]);
            } else {
              if ($data[$i]) {
                array_push($result[$keys[$i]],$data[$i]);
              }
            }
          } else {
            error_log('could not identify csvnest file');
          }
        }
      } else {
        array_push($result[$keys[0]], $data[0]);
        array_push($result[$keys[1]], $data[1]);
      }
    }
    fclose($handle);
    return $result;
  } else {
    error_log('could not open file');
    return false;
  }
}

function eq_decode_zip($zip_arg) {
  $eq_zipdecoder = import_csv_columns('zipcodes', array('zips','names'));
  $loc_key = array_search($zip_arg,$eq_zipdecoder['zips']);
  $loc_name = $eq_zipdecoder['names'][$loc_key];
  return $loc_name;
}

function eq_decode_fsa($fsa_arg) {
  $eq_fsadecoder = import_csv_columns('fsas', array('fsas','names'));
  $loc_key = array_search($fsa_arg,$eq_fsadecoder['fsas']);
  $loc_name = $eq_fsadecoder['names'][$loc_key];
  return $loc_name;
}

function eq_locale_lookup($id_num_arg, $return_code) {
  $eq_locales = import_csv_columns('locales', array('ids','names'));
  $fsa_regex = '/^[A-Z]{1}[0-9]{1}[A-Z]{1}$/';
  $loc_key = array_search($id_num_arg,$eq_locales['ids']);
  $loc_name = $eq_locales['names'][$loc_key];
  if (is_numeric($loc_name)) {
    if (strlen($loc_name) === 4) { $loc_name = '0' . $loc_name; }
    return ($return_code) ? $loc_name : eq_decode_zip($loc_name);
  } else if (preg_match($fsa_regex,$loc_name,$matches)) {
    return ($return_code) ? $loc_name : eq_decode_fsa($loc_name);
  } else {
    return $loc_name;
  }
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
        $loc_name = eq_locale_lookup($stripped_query, false);
        $result = ($loc_name && $loc_name != 'City' && $loc_name != 'Name') ?
          ucwords(strtolower($loc_name)) :
          $result;
      }
    }
  // end location -
  }
  return $result;
}

// begin GEOBLOCK SERVICE AREA
//Currently using hard-coded shortcode:

//[eq_zip_nest]

//update HTML until analogous to CR-SUITE GEOBLOCK
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
  $eq_service_areas = import_csv_columns('geo0-csvnest-row', array());
  $result = "<div>Baltimore</div>";
  if (get_query_var('location', false)) {
    $raw_query = get_query_var('location', false);
    $stripped_query = strip_tags($raw_query);
    $loc_code = eq_locale_lookup($stripped_query, true);
    $zip_nest = ($eq_service_areas[strval($loc_code)]['service_area']) ?
      $eq_service_areas[strval($loc_code)]['service_area'] :
      array("Baltimore");
    $result = iterate_zip_nest($zip_nest);
  }
  return $result;
}

add_shortcode('eq_zip_nest','eq_shortcode_handler_zip_nest');

//[eq_branch_name]

function eq_shortcode_handler_branch_name() {
  $eq_service_areas = import_csv_columns('geo0-csvnest-row', array());
  $result = "Baltimore - Ehrlich Pest Control in Baltimore and Northern Maryland";
  if (get_query_var('location', false)) {
    $raw_query = get_query_var('location', false);
    $stripped_query = strip_tags($raw_query);
    $loc_code = eq_locale_lookup($stripped_query, true);
    $result = ($eq_service_areas[strval($loc_code)]['branch_name']) ?
      $eq_service_areas[strval($loc_code)]['branch_name'] :
      "Baltimore - Ehrlich Pest Control in Baltimore and Northern Maryland";
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
