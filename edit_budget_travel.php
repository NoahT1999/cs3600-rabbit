<?php

function write_to_console($data) {
 $console = $data;
 if (is_array($console))
 $console = implode(',', $console);

 echo "<script>console.log('Console: " . $console . "' );</script>";
}

function verify_type(&$type_invalid,&$message,$value){
  $type_invalid = True;
  if(strtolower(trim($value)) == 'domestic'){
    $type_invalid = False;
    $type = 'domestic';
  }
  else if(strtolower(trim($value)) == 'international'){
    $type_invalid = False;
    $type = 'international';
  }
  if($type_invalid){
    if($value == 'default'){
      $message = "You must select either Domestic or International";
    } else {
      $message = "Invalid destination type"; 
    }
    return NULL;
  }
  return $type;
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
$start_date = "";
$length = "";
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

if($has_access && !$invalid){
  // builds a tree like structure for dynamic select options for destination
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


if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['submit_destination']) && !$invalid){
  include './database/format_numbers.php';

  $price = $_POST['transportation_cost'];
  if(isset($price) && !empty($price)){
    $code = "";
    $fail = False;
    if(!($code = format_number($price))){
      // Sets various destination variables
      if(isset($_POST['domestic'])){
        foreach(array('domestic_state','domestic_county','domestic_destination') as $i){
          if(!isset($_POST[$i]) || empty($_POST[$i])){
            $fail = True;
            $message = "You must select a destination";
            break;
          }
        }
        if(!$fail){
          $_SESSION['travel_state'] = $_POST['domestic_state'];
          $_SESSION['travel_county'] = $_POST['domestic_county'];
          $_SESSION['travel_destination'] = $_POST['domestic_destination'];
        }
      }
      if(!$fail){
        $_SESSION['transportation_cost'] = $price;
        $_SESSION['selected_destination'] = True;
      }
    } else {
      $message = "Invalid number '".$price."'. ".$code;
    }
  } else {
    $message = "Transportation cost must be set";
    $error_type = 1;
  }
  
}

if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['submit_dates']) && !$invalid){
  write_to_console(array_keys($_POST));
  if(isset($_POST['departure_date']) && isset($_POST['return_date'])){
    $depart = $_POST['departure_date'];
    $return = $_POST['return_date'];
    if($depart <= $return){
      include './database/db_connection';
      $stmt = $conn->prepare("SELECT effective_date, year from budget where budget_id=?");
    } else {
      $message = "Departure date can not be later than return date.";
    }
    if(False){
      $_SESSION['departure_date'] = $depart;
      $_SESSION['return_date'] = $return;
      $_SESSION['selected_dates'] = True;
    }
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
          echo '<form method="POST" name="travel" onsubmit="return validateTravelForm(\'travel\','.$start_date.','.$length.')">';  
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

