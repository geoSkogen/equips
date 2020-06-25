<?php

class Equips_Local_Monster {

  public $local_info = array();
  public $filename = '';

  function __construct($geo_data) {
    $this->filename = $geo_data;
  }

  public function get_local($num_arg) {
    if (!count(array_keys($this->local_info))) {
      error_log('local info array keys not found; using CSV lookup');
      $this->local_info = self::import_csv_geo($num_arg);
    } else {
      error_log('local info array keysfound; using static record');
    }
    return $this->local_info;
  }

  public function get_assoc() {
    return self::impport_geo_keyvals();
  }

  public function impport_geo_keyvals() {
    $result = array();
    $subdir = "resources";
    $result = [];
    $key = "";
    $valid_data = [];
    error_log('logging javascript-swap CSV file path for debugging');
    error_log(__DIR__ . "/../" . $subdir . "/" . $this->filename . ".csv");
    if (($handle = fopen(__DIR__ . "/../" . $subdir . "/" . $this->filename . ".csv", "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $result[$data[0]] = array('name'=>$data[1],'geos'=>$data[2]);
      }
      fclose($handle);
    } else {
      error_log('could not open file');
    }
    return $result;
  }

  public function import_csv_geo($num_arg) {
    $subdir = "resources";
    $result = [];
    $key = "";
    $valid_data = [];
    error_log('logging equips CSV file path for debugging');
    error_log(__DIR__ . "/../" . $subdir . "/" . $this->filename . ".csv");
    if (($handle = fopen(__DIR__ . "/../" . $subdir . "/" . $this->filename . ".csv", "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $valid_data = [];
        $key = strval($data[0]);
        if ($key === $num_arg) {
          $valid_data['city_name'] = strval($data[1]);
          $valid_data['country_code'] = strval($data[2]);
          $valid_data['branch_name'] = strval($data[3]);
          $valid_data['locale'] = strval($data[4]);
          $valid_data['region'] = strval($data[5]);
          $valid_data['phone'] = strval($data[6]);
          $valid_data['service_area'] = explode(",",$data[7]);
          break;
        }
      }
      fclose($handle);
      $result = $valid_data;
    } else {
      error_log('could not open file');
    }
    return $result;
  }

}
?>
