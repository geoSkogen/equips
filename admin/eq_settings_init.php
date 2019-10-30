<?php

class Equips_Settings_Init {

  public static $field_count = 5;
  public static $eq_label_toggle = array(
    "param",
    "shortcode",
    "format",
    "fallback"
  );
  public static $geo_label_toggle = array(
    "phone",
    "phone_shortcode",
    "locale",
    "locale_shortcode",
    "region",
    "region_shortcode",
    "service_area",
    "service_area_shortcode"
  );
  public static $current_field_index = 0;
  public static $eq_label_toggle_index = 0;
  public static $eq_current_img_index = 0;
  public static $geo_label_toggle_index = 0;

  public static function settings_api_init() {
    add_settings_section(
      'equips_settings',                         //uniqueID
      'Associate URL Params with Shortcodes',   //Title
      array('Equips_Settings_Init','cb_equips_settings_section'),//CallBack Function
      'equips'                                //page-slug
    );

    add_settings_section(
    'equips_images',                         //uniqueID
    'Associate Keywords with Images',        //Title
    array('Equips_Settings_Init','cb_equips_images_section'),             //CallBack Function
    'equips_images'                         //page-slug
  );

    add_settings_section(
    'equips_geo',                         //uniqueID
    'Assign Fallback Values To geo Text',        //Title
    array('Equips_Settings_Init','cb_equips_geo_section'),//CallBack Function
    'equips_geo'                         //page-slug
  );

    for ($i = 1; $i < self::$field_count + 1; $i++) {
      self::$current_field_index = $i;
      self::$eq_current_img_index = $i;
      $eq_images_field = "image_" . strval(self::$eq_current_img_index);
      $eq_images_label = "Image " . strval(self::$eq_current_img_index);
      for ($ii = 0; $ii < count(self::$eq_label_toggle); $ii++) {
        $field_name = self::$eq_label_toggle[$ii];
        $this_field = $field_name . "_" . strval(self::$current_field_index);
        $this_label = ucwords($field_name) . " " . strval(self::$current_field_index);

        add_settings_field(
          $this_field,                   //uniqueID - "param_1", etc.
          $this_label,                  //uniqueTitle -
          array('Equips_Settings_Init','cb_equips_settings_field'),//callback cb_equips_settings_field();
          'equips',                   //page-slug
          'equips_settings'          //section (parent settings-section uniqueID)
        );
      }

      add_settings_field(
        $eq_images_field,
        $eq_images_label,
        array('Equips_Settings_Init','cb_equips_images_field'),
        'equips_images',
        'equips_images'
      );
    }
    self::$current_field_index = 1;
    self::$eq_current_img_index = 1;

    for ($iii = 0; $iii < count(self::$geo_label_toggle); $iii++) {
      $geo_field_name = self::$geo_label_toggle[$iii];
      $this_geo_field = $geo_field_name;
      $this_geo_label = ucwords(str_replace("_", " ", $geo_field_name));

      add_settings_field(
        $this_geo_field,                   //uniqueID - "param_1", etc.
        $this_geo_label,                  //uniqueTitle -
        array('Equips_Settings_Init','cb_equips_geo_field'),//callback cb_equips_settings_field();
        'equips_geo',                   //page-slug
        'equips_geo'          //section (parent settings-section uniqueID)
      );
    }

    register_setting( 'equips', 'equips' );
    register_setting( 'equips_images', 'equips_images' );
    register_setting( 'equips_geo', 'equips_geo' );
  }

  //Templates

  ////template 3 - settings section field - dynamically rendered <input/>

  static function cb_equips_settings_field() {
    $options = get_option('equips');
    $input_elm = "";
    //local namespace assignments based on global settings &/or database state
    $divider = (self::$eq_label_toggle_index < sizeof(self::$eq_label_toggle)-1) ? "" : "<br/><br/><hr/>";
    $field_name = self::$eq_label_toggle[self::$eq_label_toggle_index];
    $this_field = $field_name . "_" . strval(self::$current_field_index);
    $this_label = ucwords($field_name) . " " . strval(self::$current_field_index);
    $this_index = self::$current_field_index;
    $path_name = "img_fb_path_" . $this_index;
    $this_path =
      (isset($options[$path_name]) && "" != ($options[$path_name]) ) ?
        $options[$path_name] : "";
    $placeholder =
      (isset($options[$this_field]) && "" != ($options[$this_field]) ) ?
        $options[$this_field] : "(not set)";
    $value_tag = ($placeholder === "(not set)") ? "placeholder" : "value";
    //reset globals - toggle label and increment pairing series as needed
    self::$eq_label_toggle_index += (self::$eq_label_toggle_index < sizeof(self::$eq_label_toggle)-1) ? 1 : -(sizeof(self::$eq_label_toggle)-1);
    self::$current_field_index += (self::$eq_label_toggle_index === 0) ? 1 : 0;
    //make an <input/> with dynamic attributes
    if ($field_name === "format") {
      $img_sel = ($options[$this_field] === "img") ? "selected" : "";
      $input_elm = "<select class='{$field_name}' name=equips[{$this_field}]>
                      <option value='txt'>plain text</option>
                      <option value='img'" . $img_sel . ">image file</option>
                    </select>" . $divider;
    } else {
      $input_elm = "<input type='text' name=equips[{$this_field}] {$value_tag}='{$placeholder}'/>" . $divider;
      if (isset($options["format_" . $this_index])) {
        $input_elm = ($field_name === "fallback" && $options["format_" . $this_index] === "img") ?
            "<input type='text' name=equips[{$path_name}] value='{$this_path}'/>
             <input type='file' name=equips[{$this_field}] class='equips-img-select'/>" .
            $divider :
            $input_elm;
      }
    }
    echo $input_elm;
  }

static function cb_equips_images_field() {
  $elm_arr = "";
  $eq_options = get_option('equips');
  $img_options = get_option('equips_images');
  $img_assoc_path = "";
  // dynamic headband values
  $eq_param_setting = (isset($eq_options['param_' . strval(self::$eq_current_img_index)]) &&
    $eq_options['param_' . strval(self::$eq_current_img_index)] != "") ?
    $eq_options['param_' . strval(self::$eq_current_img_index)] :
    "<span style='font-weight:700;'>Set the URL parameter for this fallback image.</span>";
  $eq_shortcode_setting = (isset($eq_options['shortcode_' . strval(self::$eq_current_img_index)]) &&
    $eq_options['shortcode_' . strval(self::$eq_current_img_index)] != "") ?
    $eq_options['shortcode_' . strval(self::$eq_current_img_index)] :
    "<span style='font-weight:700;'>Set the shortcode for this fallback image.</span>";
  //NOTE: outsource styles to stylesheet
  $eq_button_style = "background-color:#0085ba;border-color:#0073aa #006799 #006799;color:#fff;height:28px;width:94px;box-shadow:0 1px 0 #006799;text-shadow:0 -1px 1px #006799, 1px 0 1px #006799, 0 1px 1px #006799, -1px 0 1px #006799;border-radius:3px;padding:3px 10px 0 10px;margin:1em;font-size:13px;line-height:26px;cursor:pointer;";
  // set key string base slugs
  $img_assoc_id = 'img_assoc_id_' . strval(self::$eq_current_img_index);
  $img_fb_path = (isset($eq_options['img_fb_path_' . strval(self::$eq_current_img_index)]) &&
    $eq_options['img_fb_path_' . strval(self::$eq_current_img_index)] != "") ?
    $eq_options['img_fb_path_' . strval(self::$eq_current_img_index)] :
    "http://localhost/lotuseaters/wp-content/uploads/woocommerce-placeholder.png";
  $img_assoc_field = 'img_assoc_path_' . strval(self::$eq_current_img_index) . '_';
  $img_assoc_file_field = 'img_assoc_file_' . strval(self::$eq_current_img_index) . '_';
  $img_assoc_count_field = 'img_assoc_count_' . strval(self::$eq_current_img_index);
  // important procedural step
  $img_assoc_count = (isset($img_options[$img_assoc_count_field])) ?
    $img_options[$img_assoc_count_field] :
    0;
  self::$eq_current_img_index += 1;
  // make base settings field
  $elm_arr .= "<div class='img_assoc' id='" . $img_assoc_id . "'>";
  $elm_arr .= "<div class='eq_assoc_settings' style='display:flex;flex-flow:row wrap;justify-content:flex-start;font-size: 16px;'/>";
  $elm_arr .= "<div class='key-name'>URL Param:&nbsp;</div><div class='value'>" .  $eq_param_setting . "&nbsp;&nbsp;&nbsp;</div>";
  $elm_arr .= "<div class='key-name'>Shortcode:&nbsp;</div><div class='value'>[" . $eq_shortcode_setting . "]</div>";
  $elm_arr .= "</div>";
  $elm_arr .= ($img_assoc_count > 0) ? "" : "<span style='font-weight:700;'>Upload images to associate with URL Param values </span><br/>";
  $elm_arr .= "<div class='eq_assoc_panel' style='display:flex;flex-flow:row wrap;justify-content:flex-start;font-size:16px;'/>";
  $elm_arr .= "<div class='eq-add-assoc-button' style='margin: 2em 1em 2em 1em;" . $eq_button_style . "'>Add New Image</div>";
  $elm_arr .= "<img style='width:80px;height:80px;margin:0.5em;' src='" . $img_fb_path . "' >";
  $elm_arr .= "</div>";
  $elm_arr .= "<div class='inivs-div' style='display:none;'>";
  $elm_arr .= "<input type='number' name=equips_images[$img_assoc_count_field] value='{$img_assoc_count}' class='eq_assoc_count'>";
  $elm_arr .= "</div>";
  $elm_arr .= "<div class='eq_assoc_images' style='display:flex;flex-flow:row wrap;justify-content:flex-start;'/>";
  $elm_arr .= "</div>";
//begin associate-images-with-keywords
if ($img_assoc_count) {
    for ($i = 1; $i < $img_assoc_count+1; $i++) {
      $this_img_assoc_field = $img_assoc_field . strval($i);
      $img_assoc_path = (isset($img_options[$this_img_assoc_field])) ?
        $img_options[$this_img_assoc_field] :
        "";
      $img_assoc_keywords_field = 'img_assoc_keywords_' . strval(self::$eq_current_img_index-1) . '_' . strval($i);
      $img_assoc_keywords = (isset($img_options[$img_assoc_keywords_field])) ?
        $img_options[$img_assoc_keywords_field] :
        "";
      $elm_arr .= "<div class='eq_assoc_section' style='display:flex;flex-flow:row wrap;justify-content:flex-start;'/>";
      $elm_arr .= "<img class='eq_assoc' style='width:80px;height:80px;margin:0.5em 0.5em 0.5em 3em;' src='" . $img_assoc_path . "' >";
      $elm_arr .= "<textarea name=equips_images[$img_assoc_keywords_field] form='equips-images-form' rows='4' cols='50' style='margin:0.5em 1em;border-radius:3px;border:none;'>";
      $elm_arr .= $img_assoc_keywords;
      $elm_arr .= "</textarea><br/>";
      $elm_arr .= "</div>";
      $elm_arr .= "<div class='inivs-div' style='display:none;'>";
      $elm_arr .= "<input type='text' name=equips_images[$this_img_assoc_field] value='{$img_assoc_path}'>";
      $elm_arr .= "</div>";
    }
  }
  $elm_arr .= "</div>";
  echo $elm_arr;
}

  static function cb_equips_geo_field() {
    $options = get_option('equips_geo');
    //error_log(print_r($options));
    //local namespace assignments based on global settings &/or database state
    $divider = (self::$geo_label_toggle_index < count(self::$geo_label_toggle)-1) ?
      "" : "<br/><br/><hr/>";
    $field_name = self::$geo_label_toggle[self::$geo_label_toggle_index];
    $this_field = $field_name;
    $this_label = ucwords($field_name);
    $placeholder = ("" != ($options[$this_field])) ? $options[$this_field] : "(not set)";
    $value_tag = ($placeholder === "(not set)") ? "placeholder" : "value";
    //reset globals - toggle label and increment pairing series as needed
    self::$geo_label_toggle_index +=
      (self::$geo_label_toggle_index < count(self::$geo_label_toggle)-1 ) ?
      1 : -(count(self::$geo_label_toggle)-1);
    //make an <input/> with dynamic attributes
    echo "<input type='text' name=equips_geo[{$this_field}] {$value_tag}='{$placeholder}'/>" . $divider;
  }

  ////template 2 - after settings section title

  static function cb_equips_geo_section() {
    $options = get_option('equips_geo');
    $dropped = $options['drop'];
    if ($dropped === "TRUE") {
      error_log('got drop');
      delete_option('equips_geo');
    } else {
      error_log("drop=false");
    }
    wp_enqueue_script('equips-unset-all', plugin_dir_url(__FILE__) . '../js/equips-unset-all.js');
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

static function cb_equips_images_section() {
  $options = get_option('equips_images');
  $dropped = (isset($options['drop'])) ?
    $options['drop'] :
    "FALSE";
  if ($dropped === "TRUE") {
    delete_option('equips_images');
  } else {
    if ($options) {
      error_log("current equips_images");
      foreach ($options as $key => $val) {
        error_log($key . " =>");
        error_log("\t" . $val);
      }
    }
  }
  wp_enqueue_media();
  wp_register_script( 'equips-wp-media', plugins_url('../js/equips-wp-media.js', __FILE__), array('jquery') );
  wp_enqueue_script('equips-unset-all', plugin_dir_url(__FILE__) . '../js/equips-unset-all.js');
  wp_enqueue_script('equips-add-assoc-img', plugin_dir_url(__FILE__) . '../js/equips-add-assoc-img.js');
  wp_enqueue_script('equips-set-assoc-counts', plugin_dir_url(__FILE__) . '../js/equips-set-assoc-counts.js');
  wp_enqueue_script( 'equips-wp-media' );
  ?>
  <hr/>
  <div style="display:flex;flex-flow:row wrap;justify-content:space-between;">
    <input name='submit' type='submit' id='submit_top' class='button-primary' value='<?php _e("Save Changes") ?>' />
    <button id='drop_button' class='button-primary' style='border:1.5px solid red;margin-right:36px;'>
      <?php _e("Delete All") ?>
    </button>
  </div>
  <?php
}

  static function cb_equips_settings_section() {
    $options = get_option('equips');
    $dropped = $options['drop'];
    if ($dropped === "TRUE") {
      error_log('got drop');
      delete_option('equips');
    } else {
      error_log("drop=false");
    }
    wp_enqueue_media();
    wp_register_script( 'equips-wp-media', plugins_url('../js/equips-wp-media.js', __FILE__), array('jquery') );
    wp_enqueue_script('equips-unset-all', plugin_dir_url(__FILE__) . '../js/equips-unset-all.js');
    wp_enqueue_script('equips-select-submit', plugin_dir_url(__FILE__) . '../js/equips-select-submit.js');
    wp_enqueue_script( 'equips-wp-media' );
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

}

?>
