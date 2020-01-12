<?php

class RankSchema {

  function __contstruct() {

  }

  public static function highestInArr($arr) {
    $highest = -1;
    for ($i = 0; $i < count($arr); $i++) {
      if ($arr[$i] > $highest) {
        $highest = $arr[$i];
      }
    }
    return $highest;
  }

  public static function testForPartialMatch($long_arr,$short_arr) {
    $slug_length = count($short_arr);
    $tiers = count($short_arr);
    $trials = 0;
    $test_slug = "";
    $score = 0;
    for ($tier_index = $tiers; $tier_index > 0; $tier_index--) {
      $trials = count($short_arr) - $slug_length + 1;
      $slug_index = 0;
      for ($trial_index = 0; $trial_index < $trials; $trial_index++) {
        $test_slug = "";
        for ($elm_index = 0; $elm_index < $slug_length; $elm_index++) {
          $test_slug .= ($elm_index === $slug_length-1) ? $short_arr[$elm_index + $slug_index] :
            $short_arr[$elm_index + $slug_index] . " ";
        }
        if (strlen($test_slug)) {
          $score += ( strpos(implode(" ",$long_arr),$test_slug) || strpos(implode(" ",$long_arr),$test_slug) === 0 ) ?
            $slug_length + $slug_length-1 : 0;
          $slug_index++;
        }
      }
      $slug_length--;
    }
    return $score;
  }
 //($stripped_query, $num_str, get_option('equips_images'), 'img')
  public static function testForBestMatch ($stripped_query, $num_str, $field_options, $field_type) {
      $best_match_index = 0;
      $exact_match = false;
      $field_key = $field_type . '_assoc_count_' . $num_str;
      $count = $field_options[$field_key];
      $match_string = trim(preg_replace('/\s\s+/',' ', $stripped_query));
      $match_arr = explode(" ", $match_string);
      if ($count) {
/*
schema is a three dimensional array of 1) associative images' index numbers and
2 ) their associated queries, indexed, 3) broken into arrays of separate words
*/
        $schema = array();
        $scheme_scores = array();
        for ($i = 0; $i < $count; $i++) {
          $this_scheme = array();
          $this_scheme_score = 0;
          $index = $i+1;
          $iterator =  $num_str . '_' . strval($index);
          if ($field_options[$field_type . '_assoc_path_' .  $iterator] &&
              $field_options[$field_type . '_assoc_keywords_' .   $iterator]) {
              //raw schema is an indexed array of untrimmed space-separated values:
              // [0] => "this is  fun", [1] => " so fun"
              $raw_schema = explode(",", $field_options[$field_type . '_assoc_keywords_' .   $iterator]);
              error_log("raw schema");
              error_log("\t\t" . $iterator);
              error_log($field_options[$field_type . '_assoc_keywords_' .   $iterator]);
              foreach ($raw_schema as $raw_scheme) {
                $clean_scheme = trim(preg_replace('/\s\s+/',' ', $raw_scheme));
                $match_score = 0;
                // just in case the keywords are an exact match
                if ($clean_scheme === $match_string) {
                  $result = $iterator;
                  $exact_match = true;
                  error_log('exact match');
                  $best_match_index = $index;
                } else {
                  $this_query = explode(" ", $clean_scheme);
                  $this_scheme[] = $this_query;
                  $match_score += ( count($this_query) >= count($match_arr) ) ?
                     self::testForPartialMatch($this_query, $match_arr) :
                     self::testForPartialMatch($match_arr, $this_query);
                }//end
                //this scheme is an indexed array of indexed arrays of single words:
                // [0] = > ([0] => "this", [1] => "is", [2] => "fun" ),
                // [1] => ([0] => "so", "[1]" => "fun")
                $this_scheme_score += $match_score;
              }//end scheme iteration
          }//end 'if both path and keywords exist'
          //push data to schema for each associative image
          $schema[] = $this_scheme;
          $scheme_scores[] = $this_scheme_score;
        }// end associate image scheme
      }// end associateive images schema
      $top_score = self::highestInArr($scheme_scores);
      error_log("top score");
      error_log($top_score);
      if ($top_score > 0 && !$exact_match) {
        $best_match_index = array_search($top_score, $scheme_scores)+1;
        error_log("best_match_index");
        error_log($best_match_index);
      }
    return $best_match_index;
  }
}

?>
