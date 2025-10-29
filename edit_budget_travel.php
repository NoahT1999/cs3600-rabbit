<?php

function write_to_console($data) {
 $console = $data;
 if (is_array($console))
 $console = implode(',', $console);

 echo "<script>console.log('Console: " . $console . "' );</script>";
}


function execute_query(&$stmt,&$message){
  if($stmt->execute()){
    return True;
  } else {
    $message = $stmt->error;
    return False;
  }
}

$message = "";
$error_type = 1;
$invalid = False;
$direct = array("./database/login.php","Login");
$budget_id = $_GET["budget_id"];
$has_access = False;
$start_date = NULL;
$length = NULL;
$year_of_budget = NULL;
session_start();
// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user'])) {
  $message = "You must be logged in to access this page";
  $invalid = True;
} else {
  if(isset($budget_id) && !empty($budget_id)){
    include 'database/check_access.php';
    if(check_access($_SESSION['user'],$budget_id)){
      $has_access = True;
    } else {
      $message = "Access denied.";
      $direct = array("./dashboard.php","Dashboard");
      $has_access = False;
    }
  } else {
    $message = "Invalid referral link.";
    $direct = array("./dashboard.php","Dashboard");
    $has_access = False;
  }
}


if($has_access && !$invalid){
  // Stores budget start date and length
  include './database/db_connection.php';
  $stmt = $conn->prepare("SELECT effective_date,length FROM budget WHERE id=?");
  $stmt->bind_param("s",$budget_id);
  if($stmt->execute()){
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $start_date = $data["effective_date"];
    $length = $data["length"];
  } else {
    $message = "Error: ".$stmt->error;
    $invalid = True;
  }
}

// builds a tree like structure for dynamic select options for destination
if($has_access && !$invalid){
  include './database/db_connection.php';
  $stmt = $conn->prepare("SELECT DISTINCT state,county,destination from domestic_travel_per_diem;");
  if(execute_query($stmt,$message)){
    $results = $stmt->get_result();
    $data = $results->fetch_all(MYSQLI_ASSOC);
    $nested = [];
    $states = [];
    $counties = [];
    foreach($data as $row){
      $s = $row['state'];
      $c = $row['county'];
      $d = $row['destination'];
      
      $found_state = False;
      if(in_array($s,array_keys($states))){
        $state = $states[$s];
        if(in_array($c,array_keys($state))){
          $states[$s][$c][] = $d;
        } else {
          $states[$s][$c] = array($c,$d);
          //write_to_console($states[$s][$c]);
        }
      } else {
        $states[$s] = array($s);
        $states[$s][$c] = array($c,$d);
        //write_to_console($states[$s]);
        //write_to_console($states[$s][$c]);
      }
    }
    $str = "";
    foreach(array_keys($states) as $key){
      $str .= '"'.$key.'": { ';
      foreach(array_keys($states[$key]) as $county){
        if($county != 0){
          $str .= '"'.$county.'": [';
          for($i = 1; $i < sizeof($states[$key][$county]); $i++){
            $str .= '"'.$states[$key][$county][$i].'"';
            if($i < sizeof($states[$key][$county])-1){
              $str .= ', ';
            } else {
              $str .= '],';
            }
          }
        }
      }
      $str = substr($str,0,-1);
      $str .= '},';
    }
    $str = substr($str,0,-1);
    $str = "{".$str."}";
    //write_to_console($str);
    $select_domestic_destination_json = $str;
  }
  $stmt->close();
  $conn->close();
}



// Verifies the form is filled out correctly and performs various operations
if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['submit_trip']) && $has_access && !$invalid){
  $message = "Received PHP";
  $error_type = 0;

  $type = $_POST['destination-type'];
  $d_state = $_POST['domestic_state'];
  $d_county = $_POST['domestic_county'];
  $d_destination = $_POST['domestic_destination'];

  $depart = $_POST['departure_date'];
  $return = $_POST['return_date'];

  $cost = $_POST['transportation_cost'];

  $destination_pricing = NULL;

  $error = False;
  if(($type ?? '') == 'domestic'){
    if(empty($d_state) || empty($d_county) || empty($d_destination)){
      $message = "Invalid domestic destination.";
      $error_type = 1;
      $error = True;
    }
  }

  if(!$error && !empty($depart) && !empty($return)){
    $depart = date_create($depart);
    $return = date_create($return);
    if($depart > $return){
      $message = "Departure date can not be after the return date.";
      $error_type = 1;
      $error = True;
    }
    if(!$error && $depart < $start_date){
      $message = "Departure date can not be before the start of the budget.";
      $error_type = 1;
      $error = True;
    }
    if(!$error){
      $end_of_budget = clone $depart;
      $end_of_budget = date_add($end_of_budget,date_interval_create_from_date_string($length." years"));
      //write_to_console("END: ".date_format($end_of_budget,'Y-m-d'));
      if($return >= $end_of_budget){
        $message = "Return date can not be after the end of the budget.";
        $error_type = 1;
        $error = True;
      }
    }

    if(!$error){
      include './database/db_connection.php';
      if($type == 'domestic'){
        $stmt = $conn->prepare("SELECT season_start, season_end, lodging, mie FROM domestic_travel_per_diem WHERE state=? AND destination=? AND county=?");
        $stmt->bind_param("sss",$d_state,$d_destination,$d_county);
        if(execute_query($stmt,$message)){
          $result = $stmt->get_result();
          $destination_pricing = $result->fetch_all(MYSQLI_ASSOC);
          if(sizeof($destination_pricing) == 0){
            $message = "Failed fetching per diem pricing";
            $error = True;
            $error_type = 1;
          }
          $stmt->close();
        } else {
          $error = True;
          $error_type = 1;
        }
      }
      $conn->close();
    }
    if(!$error){
      $working_date = date_create($start_date);
      $start_year = array(1,date_format($working_date,'Y'));
      //write_to_console("Depart: ".date_format($depart,'Y-m-d'));
      while($depart >= ($working_date = date_add($working_date,date_interval_create_from_date_string("1 year")))){
        //write_to_console("Working: ".date_format($working_date,'Y-m-d'));
        $start_year[0] += 1;
        $start_year[1] += 1;
      }
      //write_to_console("Starting year: ".$start_year[0]." ~ ".$start_year[1]);
      
      // Determines which year of the budget various dates are in
      $comparison_date = clone $working_date; // Stores the end of the set year
      write_to_console("Comparison: ".date_format($comparison_date,"Y-m-d"));
      $working_date = clone $depart;
      $year_split = [array($start_year[0],$start_year[1],1)]; // Stores structures of (year of budget, actual year,number of days)
      $current_year = 0; // Index of year_split array
      while($return >= ($working_date = date_add($working_date,date_interval_create_from_date_string("1 day")))){
        //write_to_console("Working: ".date_format($working_date,"Y-m-d"));
        if($comparison_date > $working_date){
          $year_split[$current_year][2] += 1;
          //write_to_console($year_split[$current_year][2]);
        } else {
          $comparison_date = date_add($comparison_date,date_interval_create_from_date_string("1 year"));
          $year_split[] = array($year_split[$current_year][0]+1, date_format($working_date,'Y'),1);
          $current_year++;
        }

      }
      //foreach($year_split as $y){
        //write_to_console("Budget year ".$y[0]." (".$y[1].") ~ ".$y[2]);
      //}
    }
  }

  if(!$error && !empty($cost)){
    include 'database/format_numbers.php';
    if($code = format_number($cost)){
      $message = "Number formatting error. ".$code;
      $error_type = 1;
      $error;
    } else {
    }
  }

  if(!$error){
    $message = "All backend checks passed.";
    $error_type = 0;
  }

}
?>

<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]>      <html class="no-js"> <!--<![endif]-->
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Edit Budget Travel</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./CSS/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined&icon_names=arrow_downward&display=block" rel="stylesheet" />
    <script src="./JS/title.js"></script>
    <script src="./JS/message.js"></script>
    <script src="./JS/highlight_label.js"></script>
    <script src="./JS/travel_selections.js"></script>
    <script src="./JS/validate_travel.js"></script>
  </head>
  <body>
    <!--[if lt IE 7]>
      <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="#">upgrade your browser</a> to improve your experience.</p>
    <![endif]-->
    <?php
      include 'database/nav.php';
      navigation(isset($_SESSION['user']));
      include 'database/breadcrumb.php';
      breadcrumbs(array(array("home","./index.php"),array("budgets","./dashboard.php"),array("edit-budget","./edit_budget.php"),array("travel","javascript:location.reload();")));
    ?>
    <div class="content">
      <h1>Edit Budget Travel</h1>
      <div id="submission-message-holder"><p></p></div>
      <?php
      if(!$invalid && $has_access){
        echo '<div id="input-form">';
          echo '<form method="POST" name="travel" onsubmit="return validateTravelForm(\'travel\',\''.$start_date.'\','.$length.')">';  
            echo '<div id="location_selection">';
              echo '<select id="destination-type" name="destination-type">';
                echo '<option value="default">Select travel type</option>';
                echo '<option value="domestic">Domestic</option>';
                echo '<option value="international">International</option>';
              echo '</select>';
              echo '<div id="domestic_location">';
                echo '<select name="domestic_state" id="domestic_state">';
                  echo '<option value="default" selected="selected">state</option>';
                echo '</select>';
                echo '<select name="domestic_county" id="domestic_county">';
                  echo '<option value="default" selected="selected">county</option>';
                echo '</select>';
                echo '<select name="domestic_destination" id="domestic_destination">';
                  echo '<option value="default" selected="selected">destination</option>';
                echo '</select>';
              echo '</div>';
              echo '<div id="international_location">';
                echo '<p>International selection</p>';
              echo '</div>';
              echo '<div>';
                echo '<button class="icon-button" onclick="this.classList.add(\'hidden\');document.getElementById(\'date_selection\').classList.remove(\'hidden\');"><span class="material-symbols-outlined size-48">arrow_downward</span></button>';
              echo '<div>';
            echo '</div>';
            echo '<div id="date_selection" class="hidden">';
              echo '<div class="split-items">';
              echo '<label for="departure_date" class="tooltip">Departure Date<span class="tooltiptext">The day that you will leave for the trip on</span></label>';
                echo '<input type="date" required id="departure_date" name="departure_date" onfocus="highlightLabel(\'departure_date\',true);" onfocusout="highlightLabel(\'departure_date\',false);">';
              echo '</div>';
              echo '<div class="split-items">';
              echo '<label for="return_date"class="tooltip">Return Date<span class="tooltiptext">The day you will return from the trip on.</span></label>';
                echo '<input type="date" required id="return_date" name="return_date" onfocus="highlightLabel(\'return_date\',true);" onfocusout="highlightLabel(\'return_date\',false);">';
              echo '</div>';
              echo '<button class="icon-button" onclick="this.classList.add(\'hidden\');document.getElementById(\'cost\').classList.remove(\'hidden\');document.getElementById(\'submission_button\').classList.remove(\'hidden\');"><span class="material-symbols-outlined size-48">arrow_downward</span></button>';
            echo '</div>';
            echo '<div id="cost" class="hidden">';
              echo '<div class="split-items">';
                echo '<label for="transportation_cost" class="tooltip">Transportation Cost (Round trip)<span class="tooltiptext">The total cost of transporting yourself to the location and back.</span></label>';
                echo '<input type="text" name="transportation_cost" class="text-input-small" id="transportation_cost" required placeholder="$0.00" maxlength="45" onfocus="highlightLabel(\'transportation_cost\',true);" onfocusout="highlightLabel(\'transportation_cost\',false);"/>';
              echo '</div>';
            echo '</div>';
            echo '<div id="submission_button" class="hidden">';
              echo '<button type="submit" name="submit_trip" class="submit-button">submit</button>';
            echo '</div>';
          echo '</form>';
        echo '</div>';
      } else {
        echo '<a href="'.$direct[0].'">'.$direct[1].'</a>';
      }
      ?>
    <?php
      if(isset($message) && !empty($message)){
        echo '<script>submissionMessage("'.$message.'",'.$error_type.');</script>';
      }
      if(isset($select_domestic_destination_json) && !empty($select_domestic_destination_json)){
        echo '<script>travelTypeSelection("destination-type","domestic_location","international_location",JSON.parse(\''.$select_domestic_destination_json.'\'),"Test");</script>';
      }
    ?>
    <script>validateForm()</script>
    </div>
    <script src="" async defer></script>
    <hr id="foot-rule">
  </body>
  <?php
    include 'database/foot.php';
    footer(26,"October",2025,'Josh Gillum');
  ?>
</html>

