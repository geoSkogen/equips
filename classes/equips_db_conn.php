<?php

class Equips_DB_Conn {

  public $table_names;
  public $impot_filename;

  function __construct() {

    $this->table_names = ['eq_equips_ids', 'eq_equips_locales'];
    $this->import_filenames = [ 'geo5-ids', 'geo5-locales'];

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

  protected function eq_import_csv($filename) {
    $subdir = "resources";
    $result = [];
    $key = "";
    $valid_data = [];
    if (($handle = fopen(__DIR__ . "/../" . $subdir . "/" . $filename . ".csv", "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

      }
      fclose($handle);
      return $result;
    } else {
      error_log('could not open file');
      return false;
    }
  }

  public function eq_create_db_tables () {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $sql['ids'] = "CREATE TABLE eq_equips_ids (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      criteria_id int(10) NOT NULL,
      city_name text NOT NULL,
      region_id int(3),
      PRIMARY KEY  (id)
      ) $charset_collate;";

    $sql['locales'] = "CREATE TABLE eq_equips_locales (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      branch_name text NOT NULL,
      branch_area text NOT NULL,
      region text NOT NULL,
      phone text NOT NULL,
      service_area text NOT NULL,
      PRIMARY KEY  (id)
      ) $charset_collate;";

    foreach ( $this->table_names as $table_name) {

      $this_table_name = $wpdb->prefix . $table_name;
      $test_query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $this_table_name ) );

      if ( $wpdb->get_var( $test_query ) == $this_table_name ) {
        //
        error_log('db table: ' . $this_table_name . ' is already associated with this install');
      } else {
        //
        error_log('db table: ' . $this_table_name . ' was not found; creating new tables . . . ');
        if ( is_admin() ) {

          $prop = str_replace('eq_equips_','',$table_name);

          require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
          //
          dbDelta( $sql[$prop] );
        }
      }
    }// ends table name iteration
  }


}
?>
