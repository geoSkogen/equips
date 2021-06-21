<?php

class Equips {

  protected $db_conn;

  protected $indices;
  protected $params;
  protected $shortcodes;
  protected $fallbacks;

  protected $geo_shortcodes;
  protected $geo_fallbacks;

  protected $options;
  protected $geo_options;

  public static $utm_assoc;

  public function __construct() {
    //
    $this->options = get_option('equips');
    $this->geo_options =  get_option('equips_geo');
    $this->params = [];

    $field_count = !empty($this->options['field_count']) ?
      intval($this->options['field_count']) : 1

    for ($i = 1; $i < $fields_count + 1; $i++) {
      //
      $eq_num_str = strval($i);
      //
      if  ( !empty($this->options['param_' . $eq_num_str]) &&
            !empty($this->options['shortcode_' . $eq_num_str]) ) {
        //
        $this->indices[] = $eq_num_str;
        $this->params[] = $this->options['param_' . $eq_num_str];
        $this->shortcodes[] = $this->options['shortcode_' . $eq_num_str];
        $this->fallbacks[] = (!empty($this->options['fallback_' . $eq_num_str])) ?
          $this->options['fallback_' . $eq_num_str] : '';
      }
      //
      if ( count($this->$params) ) { $this->equips_triage(); }
    }
  }


  protected function equips_triage($eq_options) {
    // merges wordpress query vars with euqips query vars in use
    // registers shotcodes
    $geo_fields = ['locale','region','service_area','phone'];

    add_action('wp_enqueue_scripts', [$this,'init_equips_wp_scripts'] );


    add_filter( 'query_vars', function ( $vars ) {
      $vars = array_merge($vars, $this->params);
      return $vars;
    });
    //
    foreach ($this->indices as $store_key) {
      //
      add_shortcode(
        $this->options['shortcode_' . $store_key],
        function () use ($store_key) {  return $this->do_equips( $store_key ); }
      );
    }
    //register the geo-swap shotcodes
    foreach ($geo_fields as $geo_field) {
      //
      if ( !empty($this->geo_options["{$geo_field}_shortcode"]) ) {

        add_shortcode(
          $this->geo_options["{$geo_field}_shortcode"],
          [$this,"eq_shortcode_handler_{$geo_field}"]
        );

        $this->geo_shortcodes[] = $this->geo_options["{$geo_field}_shortcode"];
        $this->geo_fallbacks[] = $this->geo_options[$geo_field];
      }
    }

    add_action( 'wp_enqueue_scripts', [$this, 'init_equips_wp_scripts'] )

    return;
  }

  public function init_equips_wp_scripts() {
    /*
    $monster = new Equips_Local_Monster('geo20',false);
    $loc_assoc = $monster->get_assoc();
    */
    $loc_assoc = [];
    wp_register_script('equips-append-hrefs',plugin_dir_url(__FILE__) . '../js/equips-append-hrefs.js', array('jquery'));
    wp_localize_script( 'equips-append-hrefs', 'equips_settings_obj',
      array(
        'params' => $this->params,
        'shortcodes' => $this->shortcodes,
        'geo_shortcodes' => $this->geo_shortcodes,
        'fallbacks' => $this->fallbacks,
        'geo_fallbacks' => $this->geo_fallbacks,
        'site_url' => site_url(),
        'loc_assoc' => $loc_assoc
      )
    );
    wp_enqueue_script('equips-append-hrefs');

    wp_register_script(
      'equips-utm-content-gf-injector',
      plugin_dir_url(__FILE__) . '../js/' . 'equips-utm-content-gf-injector' . '.js'
    );
    wp_enqueue_script('equips-utm-content-gf-injector');
  }

  /*  */

  protected function do_equips_utm($key,$val) {

    $result = '';
    switch($key) {

      case 'location' :
      case 'content' :

        $result = $this->do_equips_location('city_name');
        break;

      default :
    }

    return $result;
  }

  // returns a local attribute by name and URL param
  public static function do_equips_location($db_slug) {

    $result = '';
    //check if the static property already exists
    if (count(array_keys(self::$local_info)) && !empty(self::$local_info[$db_slug])) {
      $result = self::$local_info[$db_slug];
      //error_log('found static record of local info; no lookup required');
    } else {
      //if not, try looking it up
      //error_log('eq_location');
      $raw_query = (get_query_var('location', false)) ?
        get_query_var('location', false) : '';
        //locations can only be looked up by unique numeric key
      $stripped_query = is_numeric(strip_tags($raw_query)) ?
        strip_tags($raw_query) : '';
      $query_str = $_SERVER['QUERY_STRING'];
      $key_val = (self::get_equips_utm('content',$query_str)) ?
        self::get_equips_utm('content',$query_str) : '';
      $stripped_query = empty($stripped_query) ?
       (!empty(self::$utm_assoc['content']) ? self::$utm_assoc['content'] :
          (!empty(self::$utm_assoc['location']) ? self::$utm_assoc['location'] : $stripped_query)
        ) : $stripped_query;
      $db_file = ($raw_query) ? 'geo5' : 'geo20';
      $db_format = ($raw_query) ? true : false;
      //error_log("looking up $db_file locale");
      //error_log($stripped_query);
      //error_log($raw_query);
      $equips_local_monster = new Equips_Local_Monster($db_file,$db_format);
      $loc_data = $equips_local_monster->get_local($stripped_query);
      $result = (count(array_keys(($loc_data))) && isset($loc_data[$db_slug])) ?
        $loc_data[$db_slug] : $result;
      //if the location was found, commit it to the static var for future use
      if ($result) {
        //error_log('looked up geo locale; committed to static record');
        //error_log($result);
        self::$local_info = $loc_data;
      }
        // do geopluign lookup ()
    }
    return $result;
  }

  public static function get_equips_utm($param,$query_str) {
    $result = '';
    if ($query_str) {

      $utm_arr = explode('&',$query_str);

      foreach($utm_arr as $item) {
        $key_val = explode('=',$item);

        if ($key_val[0]=='utm_' . $param) {

          $key = str_replace('utm_','',$key_val[0]);
          $val = strip_tags($key_val[1]);
          $result = array('key'=>$key,'val'=>$val);
          self::$utm_assoc[$key] = $val;
          break;
        }
      }
    } else {
      error_log('querystring item not found');
      error_log($query_str);
    }
    return $result;
  }
  // DYNAMIC shortcode handler
  // determines which URL param is being used, and whether it has a routine
  public static function do_equips($num_str) {
    $result = '';
    $type = self::$options['type_' . $num_str];
    $fallback = self::$options['fallback_' . $num_str] ? : '';
    switch ($type) {
      case 'standard' :
        if (get_query_var(self::$options['param_' . $num_str], false)) {
          switch (self::$options['param_' . $num_str]) {
            //NOTE: RE: security - this plugin is currently only configured to lookup locations
            //$stripped_query requires further validation before being injected into text content
            case 'location' :
              $result = self::do_equips_location('city_name');
              break;
            default :
          }
        }
        break;
      case 'utm' :
        $query_str = $_SERVER['QUERY_STRING'];
        $key_val = self::get_equips_utm(self::$options['param_' . $num_str],$query_str);
        $result = ($key_val) ? self::do_equips_utm($key_val['key'],$key_val['val']) : '';

        break;
      default :
    }
    return $result ? : $fallback;
  }

  // GEOBLOCK & SERVICE AREA shortcode handlers

  static function iterate_service_area($name_arr) {
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

  public static function eq_shortcode_handler_service_area() {
    return self::eq_shortcode_handler_geo_dynamic('service_area');
  }

  public static function eq_shortcode_handler_region() {
    return self::eq_shortcode_handler_geo_dynamic('region');
  }

  public static function eq_shortcode_handler_locale() {
    return self::eq_shortcode_handler_geo_dynamic('locale');
  }

  static function eq_shortcode_handler_geo_dynamic($slug) {
    $eq_geo_options = get_option('equips_geo');
    $fallback = $eq_geo_options[$slug] ? : '';
    $val = self::do_equips_location($slug);
    if ($slug==='service_area') {
      $result = ($val) ? self::iterate_service_area($val) : $fallback;
    } else {
      $result = $val ? : $fallback;
    }
    return $result;
  }

  public static function eq_shortcode_handler_phone( $atts = array() ) {

    $eq_geo_options = get_option('equips_geo');
    $fallback = $eq_geo_options['phone'] ? : '';
    $phone = self::do_equips_location('phone');
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
    if ( !empty($eq_geo_options['include_phone_bar'])
         && $eq_geo_options['include_phone_bar'] === 'include') {
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
}

?>
