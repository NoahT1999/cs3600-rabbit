<?php
  function format_number(&$number){
    // Return values: 0 - Success
    //                1 - Too many dollar signs
    //                2 - Number of digits on left of dollar sign is more than one
    //                3 - More than one decimal point.
    //                4 - Number contains non-numeric characters
    $cleaned_number = trim($number);
    // Removes dollar sign
    $split = explode("$",$cleaned_number);
    if(sizeof($split) == 2){ // Has dollar sign at beginning
      $cleaned_number = $split[1];
    } else if (sizeof($split) == 1){ // Doesn't have dollar sign at beginning
      $cleaned_number = $split[0];
    } else {
      return "Number can only contain one or zero of \'$\'";
    }
    // Checks if number has decimal point
    $split = explode(".",$cleaned_number);
    $max_whole_digits = 8;
    if(sizeof($split) == 2){ // Has decimal
      if(strlen($split[0]) > $max_whole_digits){ // Number is too large for db
        return "Number of digits on left of decimal is too large.";
      }
      if(strlen($split[1]) > 2){ // More than two digits
        $cleaned_number = $split[0].".".substr($split[1],0,2);
      } else if(strlen($split[1]) == 1){ // One digit
        $cleaned_number = $split[0].".".$split[1]."0";
      } else if(strlen($split[1]) == 0){ // No digits (had decimal with no digits)
        $cleaned_number = $split[0].".00";
      }
    } else if (sizeof($split) == 1){ // Doesn't have decimal
      if(strlen($cleaned_number) > $max_whole_digits){ // Number is too large for db
        return "Number of digits on left of decimal is too large.";
      }
      $cleaned_number = $split[0].".00";
    } else {
      return "At most 1 decimal point (\'.\') is allowed.";
    }
    if(is_numeric($cleaned_number)){
      $number = $cleaned_number;
      return NULL;
    }
    return "Number contains non-numeric characters." ;
  }

?>
