<?php

class Equips_Settings {

  protected $eq_label_toggle;
  protected $go_lable_toggle;

  protected $current_field_index = 0;
  protected $eq_label_toggle_index = 0;
  protected $geo_label_toggle_index = 0;

  public function __construct() {
    $this->eq_label_toggle = array(
      "param",
      "shortcode",
      "fallback",
      "type"
    );

    $this->geo_label_toggle = array(
      "phone",
      "phone_shortcode",
      "locale",
      "locale_shortcode",
      "region",
      "region_shortcode",
      "service_area",
      "service_area_shortcode"
    );

    add_action(
      'admin_init',
      [$this,'settings_api_init']
    );
  }

  public static function settings_api_init() {

    $option = get_option('equips');
    $field_count = !empty($option['field_count']) ?
      intval($option['field_count']) : 1;

    add_settings_section(
      'equips_settings',                         //uniqueID
      'Associate URL Params with Shortcodes',   //Title
      [$this,'cb_equips_settings_section'],//CallBack Function
      'equips'                                //page-slug
    );

    add_settings_section(
      'equips_geo',                         //uniqueID
      'Geo Shortcodes & Fallback Text',        //Title
      [$this,'cb_equips_geo_section'],//CallBack Function
      'equips_geo'                         //page-slug
    );

    add_settings_field(
      'field_count',
      'Number of Queries',
      [$this,'cb_equips_field_count'],
      'equips',
      'equips_settings'
    );
    // settings fields factory 1 -
    for ($i = 1; $i < $field_count + 1; $i++) {
      // foreach EQUIPS field
      $this->current_field_index = $i;
      // foreach subfield - param, shortcode, fallback
      for ($ii = 0; $ii < count($this->eq_label_toggle); $ii++) {
        //
        $field_index = strval($this->current_field_index);
        $field_name = $this->eq_label_toggle[$ii];
        $this_field = $field_name . "_" . $field_index;
        $this_label = ucwords($field_name) . " " . $field_index;

        add_settings_field(
          $this_field,                   //uniqueID - "param_1", etc.
          $this_label,                  //uniqueTitle -
          [$this,'cb_equips_settings_field'],//callback cb_equips_settings_field();
          'equips',                    //page-slug
          'equips_settings'            //section (parent settings-section uniqueID)
        );
      }
    }

    $this->current_field_index = 1;
    // settings fields factory 2 - geo settings
    for ($iii = 0; $iii < count($this->geo_label_toggle); $iii++) {

      $this_geo_field = $this->geo_label_toggle[$iii];

      $this_geo_label = ucwords(str_replace("_", " ", $this_geo_field));

      add_settings_field(
        $this_geo_field,                  //uniqueID - "param_1", etc.
        $this_geo_label,                  //uniqueTitle -
        [$this,'cb_equips_geo_field'],//callback cb_equips_settings_field();
        'equips_geo',                     //page-slug
        'equips_geo'                     //section (parent settings-section uniqueID)
      );
    }

    add_settings_field(
      'include_phone_bar',
      'Inlcude Phone Bar?',
      [$this,'cb_equips_include_phone_bar_field'],
      'equips_geo',
      'equips_geo'
    );

    add_settings_field(
      'phone_bar_text',
      'Phone Bar Text',
      [$this,'cb_equips_phone_bar_field'],
      'equips_geo',
      'equips_geo'
    );

    register_setting( 'equips', 'equips' );

    register_setting( 'equips_geo', 'equips_geo' );
  }

  // utility functions

  protected function trim_fields() {
    // prunes the options table if new fields count is less than previously set
    $result = array();
    $option = get_option('equips');
    $stop = !empty($option['field_count']) ?
      intval($option['field_count']) + 1 : 1;

    $meta_data = ['drop','field_count','prev_field_count'];

    foreach ($meta_data as $meta_datum) {
      $result[$meta_datum] = $option[$meta_datum];
    }

    for ($i = 1; $i < $stop; $i++) {

      foreach ($this->eq_label_toggle as $eq_label) {
        $field_name = $eq_label . '_' . strval($i);
        $result[] =
          ( !empty($option[$field_name]) ) ? $option[$field_name] : '';
      }
    }
    update_option('equips', $result);
    return;
  }

  public function concat_equips_radio($placeholder,$this_field,$divider) {
    //
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
    //
    return $str;
  }

  //Templates
  ////template 3 - settings section field - dynamically rendered <input/>
  public function cb_equips_settings_field() {
    //
    $options = get_option('equips');
    // dynamic field props
    $field_name = $this->eq_label_toggle[$this->eq_label_toggle_index];
    $this_field = $field_name . "_" . strval($this->current_field_index);
    $this_label = ucwords($field_name) . " " . strval($this->current_field_index);
    // dynamic HTML attributes
    $placeholder = !empty($options[$this_field]) ? $options[$this_field] : "(not set)";
    $value_tag = !empty($options[$this_field]) ?  "value" : "placeholder" ;
    $divider = ($this->eq_label_toggle_index < count($this->eq_label_toggle)-1) ?
      "" : "<br/><br/><hr/>";
    //reset counters - increment the label toggle or subtract it from itself to get zero
    $this->eq_label_toggle_index +=
      ($this->eq_label_toggle_index < count($this->eq_label_toggle)-1 ) ?
      1 : -(count($this->eq_label_toggle)-1);
    //  increment the field toggle for each lable toggle returns to zero
    $this->current_field_index += ($this->eq_label_toggle_index === 0) ?
      1 : 0;
    //make an <input/> with dynamic attributes
    if ($field_name==='type') {
      //
      $str = $this->concat_equips_radio($placeholder,$this_field,$divider);
    } else {
      // default inmput
      $str = "<input type='text' name=equips[{$this_field}] {$value_tag}='{$placeholder}'/>" . $divider;
    }
    echo $str;
  }

  public function cb_equips_field_count() {
    $result = '<div>';
    $options = get_option('equips');

    $this_field = 'field_count';
    $ghost_field = 'prev_field_count';
    $val = !empty($options[$this_field]) ? $options[$this_field] : strval(1);
    $ghost_val = !empty($options[$ghost_field]) ? $options[$ghost_field] : strval(1);

    $invis_atts = "class='invis-input' id='prev_field_count'";
    $style_rule = "style='display:none'";

    $result .= "<input name=equips[{$this_field}] type='number' value='{$val}'/>";
    $result .= "<input name='submit' type='submit' id='update' class='button-primary' value='Update' />";
    $result .= "<input {$style_rule} {$invis_atts} name=equips[{$ghost_field}] type='number' value='{$val}'/>";
    $result .= "</div><hr/>";
    //
    echo $result;
  }

  public function cb_equips_geo_field() {

    $options = get_option('equips_geo');
    $field_name = $this->geo_label_toggle[$this->geo_label_toggle_index];

    $this_field = $field_name;
    $this_label = ucwords($field_name);

    $placeholder = !empty( $options[$this_field] ) ?
      $options[$this_field] : "(not set)";
    $value_tag = !empty( $options[$this_field] ) ?  "value" : "placeholder";
    $divider = (
        $this->geo_label_toggle_index === count($this->geo_label_toggle)-1 ||
        strpos($field_name,'shortcode')
        ) ?
         "<br/><br/><hr/>" : "" ;
    //reset globals - toggle label and increment pairing series as needed
    $this->geo_label_toggle_index +=
      ($this->geo_label_toggle_index < count($this->geo_label_toggle)-1 ) ?
      1 : -(count($this->geo_label_toggle)-1);
    //make an <input/> with dynamic attributes
    echo "<input type='text' name=equips_geo[{$this_field}] {$value_tag}='{$placeholder}'/>" . $divider;
  }

  public function cb_equips_include_phone_bar_field() {
    $result = '';
    $options = get_option('equips_geo');
    //
    $this_field = 'include_phone_bar';
    $incl_is_checked = ( $options[$this_field] ||
      "include" === ($options[$this_field])) ? "checked" : "";
    $excl_is_checked = ("exclude" === ($options[$this_field])) ? "checked" : "";
    //
    $result .= "<input type='radio' name=equips_geo[{$this_field}] value='include' $incl_is_checked/>";
    $result .= "<label for='include'>include</label>";
    $result .= "<input type='radio' name=equips_geo[{$this_field}] value='exclude' $excl_is_checked/>";
    $result .= "<label for='exclude'>exclude</label>";
    //
    echo $result;
  }

  public function cb_equips_phone_bar_field() {
    $options = get_option('equips_geo');
    //
    $this_field = 'phone_bar_text';
    $placeholder = !empty($options[$this_field]) ?
      $options[$this_field] : "(not set)";
    $value_tag = !empty($options[$this_field]) ? "value" : "placeholder";
    //
    echo "<input type='text' name=equips_geo[{$this_field}] {$value_tag}='{$placeholder}'/>";
  }

  ////template 2 - after settings section title
  static function cb_equips_geo_section() {
    $this->cb_equips_dynamic_section('equips_geo');
  }

  static function cb_equips_settings_section() {
    $this->cb_equips_dynamic_section('equips');
  }

  static function cb_equips_dynamic_section($db_slug) {
    //
    $options = get_option($db_slug);
    //
    $dropped = $options['drop'];

    if ($dropped === "TRUE") {
      error_log('got drop');
      delete_option($db_slug);
      //
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
