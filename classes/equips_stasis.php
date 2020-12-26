<?php

class Equips_Stasis {

  public static $eq_store = array('indices'=>[],'params=[]');
  public static $options = array();
  public static $geo_options = array();
  public static $local_info = array();
  public static $utm_assoc = array();

  function __construct() {

  }

  public static function init_equips($counter) {

    $eq_num_str = "";
    $eq_options = get_option('equips');
    self::$options = $eq_options;
    for ($i = 1; $i < $counter + 1; $i++) {
      $eq_num_str = strval($i);
      if  ( !empty($eq_options['param_' . $eq_num_str]) &&
            !empty($eq_options['shortcode_' . $eq_num_str]) ) {
        self::$eq_store['indices'][] = $eq_num_str;
        self::$eq_store['params'][] = $eq_options['param_' . $eq_num_str];
        self::$eq_store['shortcodes'][] = $eq_options['shortcode_' . $eq_num_str];
        self::$eq_store['fallbacks'][] = (!empty($eq_options['fallback_' . $eq_num_str])) ?
          $eq_options['fallback_' . $eq_num_str] : null;
      }
    }
    self::equips_triage($eq_options);
    return;
  }

  public static function equips_triage($eq_options) {

    $eq_geo_options = get_option('equips_geo');
    self::$geo_options = $eq_geo_options;
    self::$eq_store['geo_shortcodes'] = [];
    self::$eq_store['geo_fallbacks'] = [];
    add_action('wp_enqueue_scripts',array('Equips_Stasis','init_equips_wp_scripts'));
    //register the URL parameters and their dynamic shortcode handler
    add_filter( 'query_vars', function ( $vars ) {
      $vars = array_merge($vars, self::$eq_store['params']);
      return $vars;
    });
    foreach (self::$eq_store['indices'] as $store_key) {
      add_shortcode(
        $eq_options['shortcode_' . $store_key],
        function () use ($store_key) {  return self::do_equips($store_key); }
      );
    }
    //register the geo-swap shotcodes
    if ($eq_geo_options['phone_shortcode']) {
      add_shortcode(
        $eq_geo_options['phone_shortcode'], array('Equips_Stasis','eq_shortcode_handler_phone')
      );
      self::$eq_store['geo_shortcodes'][] = $eq_geo_options['phone_shortcode'];
      self::$eq_store['geo_fallbacks'][] = $eq_geo_options['phone'];

    }
    if ($eq_geo_options['locale_shortcode']) {
      add_shortcode(
        $eq_geo_options['locale_shortcode'], array('Equips_Stasis','eq_shortcode_handler_locale')
      );
      self::$eq_store['geo_shortcodes'][] = $eq_geo_options['locale_shortcode'];
      self::$eq_store['geo_fallbacks'][] = $eq_geo_options['locale'];
    }
    if ($eq_geo_options['region_shortcode']) {
      add_shortcode(
        $eq_geo_options['region_shortcode'], array('Equips_Stasis','eq_shortcode_handler_region')
      );
      self::$eq_store['geo_shortcodes'][] = $eq_geo_options['region_shortcode'];
      self::$eq_store['geo_fallbacks'][] = $eq_geo_options['region'];
    }
    if ($eq_geo_options['service_area_shortcode']) {
      add_shortcode(
        $eq_geo_options['service_area_shortcode'], array('Equips_Stasis','eq_shortcode_handler_service_area')
      );
      self::$eq_store['geo_shortcodes'][] = $eq_geo_options['service_area_shortcode'];
      self::$eq_store['geo_fallbacks'][] = $eq_geo_options['service_area'];
    }
    return;
  }

  public static function init_equips_wp_scripts() {
    self::$eq_store;
    $monster = new Equips_Local_Monster('geo20',false);
    $loc_assoc = $monster->get_assoc();
    wp_register_script('equips-append-hrefs',plugin_dir_url(__FILE__) . '../js/equips-append-hrefs.js', array('jquery'));
    wp_localize_script( 'equips-append-hrefs', 'equips_settings_obj',
      array(
        'params' => self::$eq_store['params'],
        'shortcodes' => self::$eq_store['shortcodes'],
        'geo_shortcodes' => self::$eq_store['geo_shortcodes'],
        'fallbacks' => self::$eq_store['fallbacks'],
        'geo_fallbacks' => self::$eq_store['geo_fallbacks'],
        'site_url' => site_url(),
        'loc_assoc' => $loc_assoc
      )
    );
    wp_enqueue_script('equips-append-hrefs');
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

  public static function do_equips_utm($key,$val) {
    $result = '';
    switch($key) {
      //NOTE: RE: security - this plugin is currently only configured to lookup locations
      //$stripped_query requires further validation before being injected into text content
      case 'location' :
      case 'content' :
        $result = self::do_equips_location('city_name');
        break;
      default :
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
