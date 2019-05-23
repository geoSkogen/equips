<?php
/*
Plugin Name:  equips
Description:  Extensible Queries of URL Parameters for Shortcode
Version:      2019.05.06
Author:       Joseph Scoggins
Author URI:   https://github.com/geoSkogen
Text Domain:  equips
*/

//Global Namespace - update to OOP protocal in future versions

$eq_field_count = 5;
$eq_current_field_index = 0;
$eq_label_toggle = array("param","shortcode","fallback");
$eq_label_toggle_index = 0;
$eq_store = array(
  'indices' => array(),
  'params' => array()
);

// local data setup

register_activation_hook(__FILE__, 'equips_register_dir');

register_deactivation_hook(__FILE__, 'equips_drop_dir');

function equips_register_dir() {
  $eq_rows = csv_to_array("/rows.csv",",");
  $eq_rose = csv_to_array("/rose.csv",",");
  add_option('equips_rows',$eq_rows);
  add_option('equips_rose',$eq_rose);
  error_log("ran activation hook");
}

function equips_drop_dir() {
  delete_option('equips_rows',$eq_rows);
  delete_option('equips_rose',$eq_rose);
  error_log("dropped equips dir options");
}

// helper functions

function csv_to_array($file, $delimiter) {
    $filename = __DIR__ . $file;
    if(!file_exists($filename) || !is_readable($filename)) {
      error_log("file not found");
        return FALSE;
    }
    $header = NULL;
    $data = array(
      'ids' => array(),
      'names' => array()
    );
    if (($handle = fopen($filename, 'r')) !== FALSE)
    {
        while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
        {
            //error_log(print_r($row));
            array_push($data['ids'], $row[0]);
            array_push($data['names'], $row[1]);
        }
        fclose($handle);
    }
    return $data;
}

//Admin

function equips_register_menu_page() {
    add_menu_page(
        'equips',                        // Page Title
        'equips',                       // Menu Title
        'manage_options',             // for Capabilities level of user with:
        'equips',                    // menu Slug(page)
        'plugin_options_page',     // CB Function plugin_options_page()
        'dashicons-editor-code',  // Menu Icon
        20

    );
}
add_action( 'admin_menu', 'equips_register_menu_page' );

function equips_settings_api_init() {
  global $eq_field_count, $eq_current_field_index, $eq_label_toggle;

  add_settings_section(
    'equips_settings',                         //uniqueID
    'Associate URL Params with Shortcodes',   //Title
    'cb_equips_settings_section',            //CallBack Function
    'equips'                                //page-slug
  );

  for ($i = 1; $i < $eq_field_count + 1; $i++) {
    $eq_current_field_index = $i;
    for ($ii = 0; $ii < count($eq_label_toggle); $ii++) {
      $field_name = $eq_label_toggle[$ii];
      $this_field = $field_name . "_" . strval($eq_current_field_index);
      $this_label = ucwords($field_name) . " " . strval($eq_current_field_index);

      add_settings_field(
        $this_field,                   //uniqueID - "param_1", etc.
        $this_label,                  //uniqueTitle -
        'cb_equips_settings_field',  //callback cb_equips_settings_field();
        'equips',                   //page-slug
        'equips_settings'          //section (parent settings-section uniqueID)
      );
    }
  }
  $eq_current_field_index = 1;
  register_setting( 'equips', 'equips' );
}

//Templates

////template 1 - settings section field - dynamically rendered <input/>

function cb_equips_settings_field() {
  global  $eq_current_field_index, $eq_label_toggle, $eq_label_toggle_index;
  $options = get_option('equips');

  //local namespace assignments based on global settings &/or database state
  $divider = ($eq_label_toggle_index < 2) ? "" : "<br/><br/><hr/>";
  $field_name = $eq_label_toggle[$eq_label_toggle_index];
  $this_field = $field_name . "_" . strval($eq_current_field_index);
  $this_label = ucwords($field_name) . " " . strval($eq_current_field_index);
  $placeholder = ("" != ($options[$this_field])) ? $options[$this_field] : "(not set)";
  $value_tag = ($placeholder === "(not set)") ? "placeholder" : "value";
  //reset globals - toggle label and increment pairing series as needed
  $eq_label_toggle_index += ($eq_label_toggle_index < 2) ? 1 : -2;
  $eq_current_field_index += ($eq_label_toggle_index === 0) ? 1 : 0;
  //make an <input/> with dynamic attributes
  echo "<input class='equips-input' type='text' name=equips[{$this_field}] {$value_tag}='{$placeholder}'/>" . $divider;
}

////template 2 - after settings section title

function cb_equips_settings_section() {
  $options = get_option('equips');
  $dropped = $options['drop'];
  if ($dropped === "TRUE") {
    error_log('got drop');
    delete_option('equips');
  } else {
    error_log("drop=false");
  }
  wp_enqueue_script('equips-unset-all', plugin_dir_url(__FILE__) . 'equips-unset-all.js');
  ?>
  <hr/>
  <div style="display:flex;flex-flow:row wrap;justify-content:space-between;">
    <input name='submit' type='submit' id='submit' class='button-primary' value='<?php _e("Save Changes") ?>' />
    <button id='drop_button' class='button-primary' style='border:1.5px solid red;'>
      <?php _e("Delete All") ?>
    </button>
  </div>
  <?php
}

//// template 3 - <form> body

function plugin_options_page() {
?>
<div class='wrap'>
          <h2>equips Settings</h2>
          <form id='equips-settings' method='post' action='options.php'>
          <?php
               settings_fields( 'equips' );
               do_settings_sections( 'equips' );
          ?>   <div class='inivs-div' style="display:none;">
                    <input class='invis-input' id='drop_field' name=equips[drop] type='text'/>
               </div>
               <p class='submit'>
                    <input name='submit' type='submit' id='submit' class='button-primary' value='<?php _e("Save Changes") ?>' />
               </p>
          </form>
     </div>
<?php
}

add_action( 'admin_init', 'equips_settings_api_init');

//Procedure - shortcode-URL-param association

function do_equips($num_str) {
  $eq_options = get_option('equips');
  $result = $eq_options['fallback_' . $num_str];
  if (get_query_var($eq_options['param_' . $num_str], false)) {
    $result = get_query_var($eq_options['param_' . $num_str], false);
  }
  return $result;
}

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
    add_shortcode( $eq_options['shortcode_' . $store_key], 'eq_shortcode_handler_' . $store_key);
  //  error_log('adding shortcode: ' . $eq_options['shortcode_' . $store_key]);
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

init_equips($eq_field_count);
