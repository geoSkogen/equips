<?php

class Equips_DB_Conn {

  public $table_names;
  public $impot_filename;
  public $props;
  public $local_props;

  function __construct() {

    $this->table_names = ['eq_equips_ids', 'eq_equips_locales', 'eq_equips_utm_locales'];
    $this->import_filenames = [ 'geo5-ids', 'geo5-locales','geo20'];
    $this->props = [
      'ids' => ['criteria_id','city_name','country_code','region_id'],
      'locales' => ['branch_name','locale','region','phone','service_area']
    ];
    $this->local_props = [
      'city_name','place_name','country_code','branch_name','locale','region',
      'phone','service_area'
    ];
    $this->utm_local_props = [
      'slug','city_name','place_name','country','branch_name','locale','region',
      'phone','service_area'
    ];

  }

  public function eq_local_lookup($num_arg) {
    $result = [];
    $local_info = [];
    $place_ids = $this->criteria_id_lookup($num_arg);
    if ( !empty( $place_ids['region_id'] )) {

      $local_info = $this->region_id_lookup( $place_ids['region_id'] );
    }
    return is_array($place_ids) ? array_merge($place_ids,$local_info) : [];
  }

  public function eq_utm_local_lookup($str_arg) {
    $local_info = [];
    $result = $this->locale_slug_lookup($str_arg);
    if ( !empty( $place_ids['region_id'] )) {

      $local_info = $this->region_id_lookup( $place_ids['region_id'] );
    }
    return ( is_array($result) && count(array_keys($result)) ) ?
      $result : [];
  }

  public function eq_init_database() {

    $table_results = $this->eq_create_db_tables();

    error_log(print_r($table_results,true));

    $insert_results = $this->eq_write_equips_data();

    error_log(print_r($insert_results,true));

    return;
  }

  protected function criteria_id_lookup($num_arg) {
    global $wpdb;
    $result = "";
    $table_name = $wpdb->prefix . "eq_equips";
    $result = $wpdb->get_row(
     "SELECT * FROM wp_eq_equips_ids WHERE criteria_id = " . $num_arg,
     ARRAY_A
    );
    return ($result) ?  $result : '';
  }


  protected function region_id_lookup($num_arg) {
    global $wpdb;
    $result = "";
    $table_name = $wpdb->prefix . "eq_equips";
    $result = $wpdb->get_row(
     "SELECT * FROM wp_eq_equips_locales WHERE id = " . $num_arg,
     ARRAY_A
    );
    return ($result) ?  $result : '';
  }

  protected function locale_slug_lookup($slug) {
    global $wpdb;
    $result = "";
    $table_name = $wpdb->prefix . "eq_equips";
    $result = $wpdb->get_row(
     "SELECT * FROM wp_eq_equips_utm_locales WHERE slug = '" . $slug . "'",
     ARRAY_A
    );
    return ($result) ?  $result : '';
  }


  public function eq_import_csv($filename,$paginate) {
    $result = [];
    $subdir = "resources";
    $row_index = 0;
    $page_index = 0;

    if ( ($handle = fopen(__DIR__ . "/../" . $subdir . "/" . $filename . ".csv", "r")) !== FALSE) {

      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        //
        if ($paginate) {

          if ($row_index > 999) {
            $page_index++;
            $row_index = 0;
          }
          //
          $result[$page_index][] = $data;
        } else {
          //
          $result[] = $data;
        }
        $row_index++;
      }

      fclose($handle);
      return $result;
    } else {
      error_log('could not open file');
      return false;
    }
  }


  public function eq_associate_rows( $rows, $props) {
    foreach ($rows as $row) {
      $new_row = [];
      for ($i = 0; $i < count($props); $i++) {
        $new_row[ $props[$i] ] = $row[$i+1];
      }
      $table[$row[0]] = $new_row;
    }
    return $table;
  }


  protected function eq_bulk_insert($table_name,$data,$props) {

    global $wpdb;
    $this_table_name = $wpdb->prefix . $table_name;
    $test_query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $this_table_name) );

    if ( $wpdb->get_var( $test_query) == $this_table_name ) {

      $insert = "INSERT INTO $this_table_name ";
      $insert .= $this->assemble_sql_list($props,false) . ' VALUES ';

      for ($i = 0; $i < count($data); $i++) {
        //
        $insert .= $this->assemble_sql_list($data[$i],true);
        $insert .= ($i!=count($data)-1) ? ', ' : ';';
      }

      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
      //
      dbDelta( $insert );
    } else {
      error_log("the table $this_table_name was not found for eq bulk insert");
    }
    return;
  }


  protected function eq_paginate_bulk_insert($table_name,$paginated_data,$props) {

    $results = [];
    foreach($paginated_data as $data_page) {

      $results[] = $this->eq_bulk_insert($table_name,$data_page,$props);
    }
    return $results;
  }


  protected function assemble_sql_list($props,$str_args) {
    $result = '(';
    for ($i = 0 ; $i < count($props) ; $i++) {
      $result .= (!$str_args) ? '' : '"';
      $result .= "$props[$i]";
      $result .= (!$str_args) ? '' : '"';
      $result .= ($i!=count($props)-1) ? ', ' : '';
    }
    $result .= ')';
    return $result;
  }


  protected function eq_write_equips_data() {

    $results = ['paginated'=>[], 'bulk'=>[] ];
    $criteria_id_table = $this->eq_import_csv('geo5-ids',true);
    $locales_info_table = $this->eq_import_csv('geo5-locales',false);
    $utm_locales_info_table = $this->eq_import_csv('geo20',false);

    if (!empty($criteria_id_table)) {
      $results['paginated'] = $this->eq_paginate_bulk_insert('eq_equips_ids',$criteria_id_table,$this->props['ids']);
    } else {
      error_log('id csv table import failed');
    }

    if (!empty($locales_info_table)) {
      $results['bulk'][] = $this->eq_bulk_insert('eq_equips_locales',$locales_info_table,$this->props['locales']);
    } else {
      error_log('local csv table import failed');
    }

    if (!empty($utm_locales_info_table)) {
      $results['bulk'][] = $this->eq_bulk_insert('eq_equips_utm_locales',$utm_locales_info_table,$this->utm_local_props);
    } else {
      error_log('local UTM csv table import failed');
    }
    $result = array_merge( $results['paginated'], $results['bulk'] );
    return $result;
  }


  protected function eq_create_db_tables() {
    global $wpdb;
    $results = [];
    $charset_collate = $wpdb->get_charset_collate();

    $sql['ids'] = "CREATE TABLE <%table_name%> (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      criteria_id int(10) NOT NULL,
      city_name text NOT NULL,
      country_code text NOT NULL,
      region_id int(3),
      PRIMARY KEY  (id)
      ) $charset_collate;";

    $sql['locales'] = "CREATE TABLE <%table_name%> (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      branch_name text NOT NULL,
      locale text NOT NULL,
      region text NOT NULL,
      phone text NOT NULL,
      service_area text NOT NULL,
      PRIMARY KEY  (id)
      ) $charset_collate;";

    $sql['utm_locales'] = "CREATE TABLE <%table_name%> (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      slug text NOT NULL,
      city_name text NOT NULL,
      place_name text NOT NULL,
      country text NOT NULL,
      branch_name text NOT NULL,
      locale text NOT NULL,
      region text NOT NULL,
      phone text NOT NULL,
      service_area text NOT NULL,
      PRIMARY KEY  (id)
      ) $charset_collate;";

    foreach ( $this->table_names as $table_name) {

      $this_table_name = $wpdb->prefix . $table_name;
      $test_query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $this_table_name) );

      if ( $wpdb->get_var( $test_query ) == $this_table_name ) {

        error_log('db table: ' . $this_table_name . ' is already associated with this install');
      } else {
        //
        if ( is_admin() ) {

          error_log('creating new db table: ' . $this_table_name . ' for this install');

          $prop = str_replace('eq_equips_','',$table_name);

          $query = str_replace('<%table_name%>',$this_table_name,$sql[$prop]);

          require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
          //
          $results[] = dbDelta( $query );
        }
      }
    }// ends table name iteration
    return $results;
  }

}
?>
