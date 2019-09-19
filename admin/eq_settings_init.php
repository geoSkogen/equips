<?php

class Equips_Settings_Init {

  public static $field_count = 5;
  public static $label_toggle = array("param","shortcode","fallback");
  public static $current_field_index = 0;
  public static $label_toggle_index = 0;

  public static function settings_api_init() {
    add_settings_section(
      'equips_settings',                         //uniqueID
      'Associate URL Params with Shortcodes',   //Title
      array('Equips_Settings_Init','cb_equips_settings_section'),//CallBack Function
      'equips'                                //page-slug
    );

    for ($i = 1; $i < self::$field_count + 1; $i++) {
      self::$current_field_index = $i;
      for ($ii = 0; $ii < count(self::$label_toggle); $ii++) {
        $field_name = self::$label_toggle[$ii];
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
    }
    self::$current_field_index = 1;
    register_setting( 'equips', 'equips' );
  }

  //Templates

  ////template 3 - settings section field - dynamically rendered <input/>

  static function cb_equips_settings_field() {
    $options = get_option('equips');
    //error_log(print_r($options));
    //local namespace assignments based on global settings &/or database state
    $divider = (self::$label_toggle_index < count(self::$label_toggle)-1) ?
      "" : "<br/><br/><hr/>";
    $field_name = self::$label_toggle[self::$label_toggle_index];
    $this_field = $field_name . "_" . strval(self::$current_field_index);
    $this_label = ucwords($field_name) . " " . strval(self::$current_field_index);
    $placeholder = ("" != ($options[$this_field])) ? $options[$this_field] : "(not set)";
    $value_tag = ($placeholder === "(not set)") ? "placeholder" : "value";
    //reset globals - toggle label and increment pairing series as needed
    self::$label_toggle_index +=
      (self::$label_toggle_index < count(self::$label_toggle)-1 ) ?
      1 : -(count(self::$label_toggle)-1);
    self::$current_field_index += (self::$label_toggle_index === 0) ?
      1 : 0;
    //make an <input/> with dynamic attributes
    echo "<input type='text' name=equips[{$this_field}] {$value_tag}='{$placeholder}'/>" . $divider;
  }

  ////template 2 - after settings section title

  static function cb_equips_settings_section() {
    $options = get_option('equips');
    $dropped = $options['drop'];
    if ($dropped === "TRUE") {
      error_log('got drop');
      delete_option('equips');
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
