<?php

class Equips_Settings_Init {

  public static $eq_label_toggle = array(
    "param",
    "shortcode",
    "fallback",
    "type"
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
  public static $geo_label_toggle_index = 0;

  public static function get_field_count() {
    $result = '';
    $option = get_option('equips');
    if (isset($option['field_count'])) {
      $result = $option['field_count'];
    } else {
      $result = 1;
    }
    return $result;
  }

  public static function trim_fields() {
    $option = get_option('equips');
    $stop = intval($option['field_count']) + 1;
    $result = array();
    $meta_data = ['drop','field_count','prev_field_count'];
    foreach ($meta_data as $meta_datum) {
      $result[$meta_datum] = $option[$meta_datum];
    }
    for ($i = 1; $i < $stop; $i++) {
      foreach (self::$eq_label_toggle as $eq_label) {
        $result[$eq_label . '_' . strval($i)] =
          (isset($option[$eq_label . '_' . strval($i)]) &&
            "" != $option[$eq_label . '_' . strval($i)]) ?
              $option[$eq_label . '_' . strval($i)] : '';
      }
    }
    update_option('equips', $result);
    return;
  }

  public static function settings_api_init() {
    add_settings_section(
      'equips_settings',                         //uniqueID
      'Associate URL Params with Shortcodes',   //Title
      array('Equips_Settings_Init','cb_equips_settings_section'),//CallBack Function
      'equips'                                //page-slug
    );

    add_settings_section(
      'equips_geo',                         //uniqueID
      'Geo Shortcodes & Fallback Text',        //Title
      array('Equips_Settings_Init','cb_equips_geo_section'),//CallBack Function
      'equips_geo'                         //page-slug
    );

    add_settings_field(
      'field_count',
      'Number of Queries',
      array('Equips_Settings_Init','cb_equips_field_count'),
      'equips',
      'equips_settings'
    );

    for ($i = 1; $i < self::get_field_count() + 1; $i++) {
      self::$current_field_index = $i;
      for ($ii = 0; $ii < count(self::$eq_label_toggle); $ii++) {
        $field_name = self::$eq_label_toggle[$ii];
        $this_field = $field_name . "_" . strval(self::$current_field_index);
        $this_label = ucwords($field_name) . " " . strval(self::$current_field_index);

        add_settings_field(
          $this_field,                   //uniqueID - "param_1", etc.
          $this_label,                  //uniqueTitle -
          array('Equips_Settings_Init','cb_equips_settings_field'),//callback cb_equips_settings_field();
          'equips',                    //page-slug
          'equips_settings'            //section (parent settings-section uniqueID)
        );
      }
    }
    self::$current_field_index = 1;
    for ($iii = 0; $iii < count(self::$geo_label_toggle); $iii++) {
      $geo_field_name = self::$geo_label_toggle[$iii];
      $this_geo_field = $geo_field_name;
      $this_geo_label = ucwords(str_replace("_", " ", $geo_field_name));

      add_settings_field(
        $this_geo_field,                  //uniqueID - "param_1", etc.
        $this_geo_label,                  //uniqueTitle -
        array('Equips_Settings_Init','cb_equips_geo_field'),//callback cb_equips_settings_field();
        'equips_geo',                     //page-slug
        'equips_geo'                     //section (parent settings-section uniqueID)
      );
    }

    add_settings_field(
      'include_phone_bar',
      'Inlcude Phone Bar?',
      array('Equips_Settings_Init','cb_equips_include_phone_bar_field'),
      'equips_geo',
      'equips_geo'
    );

    add_settings_field(
      'phone_bar_text',
      'Phone Bar Text',
      array('Equips_Settings_Init','cb_equips_phone_bar_field'),
      'equips_geo',
      'equips_geo'
    );

    register_setting( 'equips', 'equips' );
    register_setting( 'equips_geo', 'equips_geo' );
  }

  //Templates
  ////template 3 - settings section field - dynamically rendered <input/>

  static function cb_equips_settings_field() {
    $options = get_option('equips');
    $divider = (self::$eq_label_toggle_index < count(self::$eq_label_toggle)-1) ?
      "" : "<br/><br/><hr/>";
    $field_name = self::$eq_label_toggle[self::$eq_label_toggle_index];
    $this_field = $field_name . "_" . strval(self::$current_field_index);
    $this_label = ucwords($field_name) . " " . strval(self::$current_field_index);
    $placeholder =
      (isset($options[$this_field]) && "" != $options[$this_field]) ?
      $options[$this_field] : "(not set)";
    $value_tag = ($placeholder === "(not set)") ? "placeholder" : "value";
    //reset globals - toggle label and increment pairing series as needed
    self::$eq_label_toggle_index +=
      (self::$eq_label_toggle_index < count(self::$eq_label_toggle)-1 ) ?
      1 : -(count(self::$eq_label_toggle)-1);
    self::$current_field_index += (self::$eq_label_toggle_index === 0) ?
      1 : 0;
    //make an <input/> with dynamic attributes
    if ($field_name==='type') {
      $placeholder = ($placeholder==='(not set)') ? 'standard' : $placeholder;
      $is_selected = ['standard'=>'','utm'=>''];
      $is_selected[$placeholder] = 'checked';
      $str = "<div style='display:flex;flex-flow:row wrap;justify-content:flex-start;'/>";
      $str .= "<input type='radio' name=equips[{$this_field}] value='standard' ";
      $str .= " {$is_selected['standard']} style='margin:0.5em' />";
      $str .= "<label for='$this_field'>standard</label>";
      $str .= "<input type='radio' name=equips[{$this_field}] value='utm' ";
      $str .= " {$is_selected['utm']} style='margin:0 0.5em 0 1em' />";
      $str .= "<label for='$this_field'>UTM parameter</label>";
      $str .= "</div>" . $divider;
    } else {
      $str = "<input type='text' name=equips[{$this_field}] {$value_tag}='{$placeholder}'/>" . $divider;
    }
    echo $str;
  }

  static function cb_equips_field_count() {
    $result = '<div>';
    $options = get_option('equips');
    $this_field = 'field_count';
    $ghost_field = 'prev_field_count';
    $invis_atts = "class='invis-input' id='prev_field_count'";
    $style_rule = "style='display:none'";
    $val = (isset($options[$this_field]) && "" != $options[$this_field]) ?
      $options[$this_field] : strval(1);
    $ghost_val = (isset($options[$ghost_field]) && "" != $options[$ghost_field]) ?
      $options[$ghost_field] : strval(1);
    $result .= "<input name=equips[{$this_field}] type='number' value='{$val}'/>";
    $result .= "<input name='submit' type='submit' id='update' class='button-primary' value='Update' />";
    $result .= "<input {$style_rule} {$invis_atts} name=equips[{$ghost_field}] type='number' value='{$val}'/>";
    $result .= "</div><hr/>";
    echo $result;
  }

  static function cb_equips_geo_field() {
    $options = get_option('equips_geo');
    $field_name = self::$geo_label_toggle[self::$geo_label_toggle_index];
    $divider = (
        self::$geo_label_toggle_index === count(self::$geo_label_toggle)-1 ||
        strpos($field_name,'shortcode')
      ) ? "<br/><br/><hr/>" : "" ;
    $this_field = $field_name;
    $this_label = ucwords($field_name);
    $placeholder = (isset($options[$this_field]) && "" != $options[$this_field]) ?
      $options[$this_field] : "(not set)";
    $value_tag = ($placeholder === "(not set)") ? "placeholder" : "value";
    //reset globals - toggle label and increment pairing series as needed
    self::$geo_label_toggle_index +=
      (self::$geo_label_toggle_index < count(self::$geo_label_toggle)-1 ) ?
      1 : -(count(self::$geo_label_toggle)-1);
    //make an <input/> with dynamic attributes
    echo "<input type='text' name=equips_geo[{$this_field}] {$value_tag}='{$placeholder}'/>" . $divider;
  }

  static function cb_equips_include_phone_bar_field() {
    $result = '';
    $options = get_option('equips_geo');
    $this_field = 'include_phone_bar';
    $incl_is_checked = ( $options[$this_field] ||
      "include" === ($options[$this_field])) ? "checked" : "";
    $excl_is_checked = ("exclude" === ($options[$this_field])) ? "checked" : "";
    $result .= "<input type='radio' name=equips_geo[{$this_field}] value='include' $incl_is_checked/>";
    $result .= "<label for='include'>include</label>";
    $result .= "<input type='radio' name=equips_geo[{$this_field}] value='exclude' $excl_is_checked/>";
    $result .= "<label for='exclude'>exclude</label>";
    echo $result;
  }

  static function cb_equips_phone_bar_field() {
    $options = get_option('equips_geo');
    $this_field = 'phone_bar_text';
    $placeholder = (isset($options[$this_field]) && "" != $options[$this_field]) ?
      $options[$this_field] : "(not set)";
    $value_tag = ($placeholder === "(not set)") ? "placeholder" : "value";
    echo "<input type='text' name=equips_geo[{$this_field}] {$value_tag}='{$placeholder}'/>";
  }

  ////template 2 - after settings section title
  static function cb_equips_geo_section() {
    self::cb_equips_dynamic_section('equips_geo');
  }

  static function cb_equips_settings_section() {
    self::cb_equips_dynamic_section('equips');
  }

  static function cb_equips_dynamic_section($db_slug) {
    $options = get_option($db_slug);
    $dropped = $options['drop'];
    if ($dropped === "TRUE") {
      error_log('got drop');
      delete_option($db_slug);
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

}

?>
