<?php

class Equips_Settings {

  protected $eq_label_toggle;
  protected $go_lable_toggle;

  protected $current_field_index = 0;
  protected $eq_label_toggle_index = 0;
  protected $geo_label_toggle_index = 0;

  protected $eq_current_img_index = 0;
  protected $eq_current_img_style_index = 0;

  public function __construct() {
    $this->eq_label_toggle = array(
      "param",
      "shortcode",
      "format",
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

    add_settings_section(
     'equips_images',                         //uniqueID
     'Associate Keywords with Images',        //Title
     [$this,'cb_equips_images_section'],  //CallBack Function
     'equips_images'                         //page-slug
   );

   add_settings_section(
     'equips_image_styles',                         //uniqueID
     'Associate Style Rules with Images',        //Title
     [$this,'cb_equips_image_styles_section'],             //CallBack Function
     'equips_image_styles'                         //page-slug
   );
    // settings fields factory 1 -
    for ($i = 1; $i < $field_count + 1; $i++) {
      // foreach EQUIPS field
      $this->current_field_index = $i;

      $this->eq_current_img_index = $i;
      $this->eq_current_img_style_index = $i;
      $eq_images_field = "image_" . strval($this->eq_current_img_index);
      $eq_images_label = "Image " . strval($this->eq_current_img_index);
      $eq_image_styles_field = "image_style_" . strval($this->eq_current_img_style_index);
      $eq_image_styles_label = "Image " . strval($this->eq_current_img_style_index) . " Styles";
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

      add_settings_field(
        $eq_images_field,
        $eq_images_label,
        [$this,'cb_equips_images_field'],
        'equips_images',
        'equips_images'
      );

      add_settings_field(
        $eq_image_styles_field,
        $eq_image_styles_label,
        [$this,'cb_equips_image_styles_field'],
        'equips_image_styles',
        'equips_image_styles'
      );
    }

    $this->current_field_index = 1;

    $this->eq_current_img_index = 1;
    $this->eq_current_img_style_index = 1;
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

    register_setting( 'equips_images', 'equips_images' );

    register_setting( 'equips_image_styles', 'equips_image_styles' );
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
    $path_name = "img_fb_path_" . $this->current_field_index;
    $this_path = !empty($options[$path_name]) ? $options[$path_name] : "";
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

    } else if ($field_name==='format') {

      $img_sel = ($options[$this_field] === "img") ? "selected" : "";

      $str = "<select class='{$field_name}' name=equips[{$this_field}]>
                      <option value='txt'>plain text</option>
                      <option value='img'" . $img_sel . ">image file</option>
                    </select>" . $divider;
    } else {
      // default inmput
      $str = "<input type='text' name=equips[{$this_field}] {$value_tag}='{$placeholder}'/>" . $divider;
      //
      if (isset($options["format_" .$this->current_field_index])) {

        $str = ($field_name === "fallback" && $options["format_" . $this->current_field_index] === "img") ?

            "<input type='text' name=equips[{$path_name}] value='{$this_path}'/>
             <input type='file' name=equips[{$this_field}] class='equips-img-select'/>" .  $divider :

            $str;
      }
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

  public function cb_equips_images_field() {


    $eq_options = get_option('equips');
    $img_options = get_option('equips_images');
    $elm_arr = "";
    $img_assoc_path = "";

    $this_index = strval($this->eq_current_img_index);
    // dynamic headband values
    $eq_param_setting = !empty($this->eq_options['param_' . $this_index]) ?
      $eq_options['param_' . $this_index ] :
      "<span style='font-weight:700;'>Set the URL parameter for this fallback image.</span>";
      //
    $eq_shortcode_setting = !empty($eq_options['shortcode_' . $this_index ]) ?
      $eq_options['shortcode_' . $this_index ] :
      "<span style='font-weight:700;'>Set the shortcode for this fallback image.</span>";
    //
    //NOTE: outsource styles to stylesheet
    $eq_button_style = "background-color:#0085ba;border-color:#0073aa #006799 #006799;color:#fff;height:28px;width:94px;box-shadow:0 1px 0 #006799;text-shadow:0 -1px 1px #006799, 1px 0 1px #006799, 0 1px 1px #006799, -1px 0 1px #006799;border-radius:3px;padding:3px 10px 0 10px;margin:1em;font-size:13px;line-height:26px;cursor:pointer;";
    // set key string base slugs
    $img_assoc_id = 'img_assoc_id_' . $this_index ;
    $img_fb_path = !empty($eq_options['img_fb_path_' . $this_index ]) ?
      $eq_options['img_fb_path_' . $this_index ] : "#";
    $img_assoc_field = 'img_assoc_path_' . $this_index  . '_';
    $img_assoc_file_field = 'img_assoc_file_' . $this_index  . '_';
    $img_assoc_count_field = 'img_assoc_count_' . $this_index ;
    // important procedural step
    $img_assoc_count = (isset($img_options[$img_assoc_count_field])) ?
      $img_options[$img_assoc_count_field] :
      0;
    $this->eq_current_img_index += 1;
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
        $img_assoc_keywords_field = 'img_assoc_keywords_' . strval($this->eq_current_img_index-1) . '_' . strval($i);
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

  static function cb_equips_image_styles_field() {
    echo "<textarea></textarea>";
  }

  ////template 2 - after settings section title
  static function cb_equips_geo_section() {
    $this->cb_equips_dynamic_section('equips_geo');
  }

  static function cb_equips_settings_section() {
    $this->cb_equips_dynamic_section('equips');
  }

  static function cb_equips_image_styles_section() {
    $this->cb_equips_dynamic_section('equips_image_styles');
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
      //error_log("drop=false");
    }

    if ($db_slug==='equips') {
      wp_enqueue_media();
      wp_register_script( 'equips-wp-media', plugins_url('../js/equips-wp-media.js', __FILE__), array('jquery') );
      wp_enqueue_script('equips-select-submit', plugin_dir_url(__FILE__) . '../js/equips-select-submit.js');
      wp_enqueue_script( 'equips-wp-media' );
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

  public function cb_equips_images_section() {
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
  }

}

?>
