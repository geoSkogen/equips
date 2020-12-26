<?php

class Equips_Local_Monster {

  public $local_info = array();
  public $filename = '';

  function __construct($geo_data,$format) {
    $this->filename = $geo_data;
    $this->query_mode = $format;
  }

  public function get_local($num_arg) {
    if (!count(array_keys($this->local_info))) {
      error_log('local info array keys not found; using CSV lookup');
      $this->local_info = self::import_geo_row($num_arg);
      //$this->local_info = $this->eq_locale_lookup($num_arg);
    } else {
      error_log('local info array keysfound; using static record');
    }
    return $this->local_info;
  }

  public function eq_locale_lookup($num_arg) {
    global $wpdb;
    $result = "";
    $table_name = $wpdb->prefix . "eq_equips";
    $result = $wpdb->get_row(
     "SELECT * FROM wp_eq_equips WHERE criteria_id = " . $num_arg,
     ARRAY_A
    );
    return ($result) ?  $result : '';
  }

  public function get_assoc() {
    return self::import_geo_rows();
  }

  public function import_geo_rows() {
    //imports entire file to use as associative array in frontend swap
    $result = array();
    $subdir = "resources";
    $result = [];
    $key = "";
    $valid_data = [];
    error_log('logging javascript-swap CSV file path for debugging');
    error_log(__DIR__ . "/../" . $subdir . "/" . $this->filename . ".csv");
    if (($handle = fopen(__DIR__ . "/../" . $subdir . "/" . $this->filename . ".csv", "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $result[$data[0]] = array(
          'city_name'=>$data[1],
          'place_name'=>$data[2],
          'country_code'=>$data[3],
          'branch_name'=>$data[4],
          'locale'=>$data[5],
          'region'=>$data[6],
          'phone'=>$data[7],
          'service_area'=>$data[8]
        );
      }
      fclose($handle);
    } else {
      error_log('could not open file');
    }
    return $result;
  }

  public function import_geo_row($arg) {
    //terminates the file iteration as soon as the arg finds its match
    //return the row only
    $subdir = "resources";
    $result = [];
    $key = "";
    $valid_data = [];
    error_log('logging equips CSV file path for debugging');
    error_log(__DIR__ . "/../" . $subdir . "/" . $this->filename . ".csv");
    switch($this->query_mode) {

      case false :

        if (($handle = fopen(__DIR__ . "/../" . $subdir . "/" . $this->filename . ".csv", "r")) !== FALSE) {
          while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $valid_data = [];
            $key = strval($data[0]);
            if ($key === $arg) {
              $valid_data = array(
                'city_name'=>$data[1],
                'place_name'=>$data[2],
                'country_code'=>$data[3],
                'branch_name'=>$data[4],
                'locale'=>$data[5],
                'region'=>$data[6],
                'phone'=>$data[7],
                'service_area'=>explode(',',$data[8])
              );
              break;
            }
          }
          fclose($handle);
          $result = $valid_data;
        } else {
          error_log('could not open file');
        }
        break;

      case true :

        $rel_id = -1;
        if (($handle = fopen(__DIR__ . "/../" . $subdir . "/" . $this->filename . "-ids.csv", "r")) !== FALSE) {
          while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $valid_data = [];
            $key = strval($data[0]);
            if ($key === $arg) {
              $valid_data = array(
                'city_name'=>$data[1],
                'country_code'=>$data[2]
              );
              $rel_id = intval($data[3]);
              break;
            }
          }
          fclose($handle);
        } else {
          error_log('could not open file');
        }

        if ($rel_id > -1) {
          $row_index = 0;
          if (($handle = fopen(__DIR__ . "/../" . $subdir . "/" . $this->filename . "-locales.csv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
              $local_data = array();
              if ($rel_id === $row_index) {
                $local_data = array(
                  'branch_name'=>$data[0],
                  'locale'=>$data[1],
                  'region'=>$data[2],
                  'phone'=>$data[3],
                  'service_area'=>explode(',',$data[4])
                );
                break;
              }
              $row_index++;
            }
            fclose($handle);
            $result = array_merge($valid_data,$local_data);
          } else {
            error_log('could not open file');
          }
        }
      break;
    }

    return $result;
  }

  public static function eq_import_csv($filename,$table_type) {
    $subdir = "resources";
    $result = [];
    $key = "";
    $valid_data = [];
    if (($handle = fopen(__DIR__ . "/../" . $subdir . "/" . $filename . ".csv", "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        switch($table_type) {
          case 'geo' :
            $valid_data = array(
              'criteria_id' => $data[0],
              'city_name' => strval($data[1]),
              'branch_name' => strval($data[3]),
              'geo_title' => strval($data[4]),
              'branch_region' => strval($data[5]),
              'service_area' => strval($data[6])
            );
            array_push($result, $valid_data);
            break;
          case 'zip' :
            $result[$data[0]] = $data[1];
            break;
          default :
            error_log('unsupported table type for wp_eq_equips');
            return false;
        }
      }
      fclose($handle);
      return $result;
    } else {
      error_log('could not open file');
      return false;
    }
  }

  public static function eq_activate_db () {
    global $wpdb;

    $table_rows = array();
    $import_filename = "geo4";
    $table_name = $wpdb->prefix . "eq_equips";
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      criteria_id int(7) NOT NULL,
      city_name text NOT NULL,
      branch_name text NOT NULL,
      geo_title text NOT NULL,
      branch_region text NOT NULL,
      service_area varchar(255) DEFAULT '' NOT NULL,
      PRIMARY KEY  (id)
      ) $charset_collate;";
    $test_query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );
    if ( $wpdb->get_var( $test_query ) == $table_name ) {
      error_log('db table: ' . $table_name . ' is already associated with this install');
    } else {
      if ( is_admin() ) {
        //set_time_limit( 300 );
        //define( 'WP_MEMORY_LIMIT' , '256M' );
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        error_log('created new db table: ' . $table_name . ' for this install');
        $table_rows = self::eq_import_csv($import_filename, 'geo');
        if ($table_rows) {
          foreach($table_rows as $row) {
            $wpdb->insert($table_name, $row);
          }
          error_log('added ' . strval(count($table_rows)) . ' rows to db table : ' . $table_name . ' - for this install');
        }
      }
    }
  }


}
?>
