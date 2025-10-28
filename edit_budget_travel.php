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
$direct = array("./database/login.php?send_back_to=".$_SERVER['REQUEST_URI'].$_SERVER['QUERY_STRING'],"Login");
session_start();
// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user'])) {
  $message = "You must be logged in to access this page";
  $invalid = True;
}

$year = $_GET["year"];
$budget_id = $_GET["budget_id"];
$type_invalid = False;

if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['submit_type']) && !$invalid){
  $_SESSION['travel_type'] = verify_type($type_invalid,$message,$_POST['destination-type']);
  if($type_invalid){
    $_SESSION['travel_type'] = NULL;
  } else {
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
            write_to_console($states[$s][$c]);
          }
        } else {
          $states[$s] = array($s);
          $states[$s][$c] = array($c,$d);
          write_to_console($states[$s]);
          write_to_console($states[$s][$c]);
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
      write_to_console($str);
      $select_domestic_destination_json = $str;
    }
    $stmt->close();
    $conn->close();
  }
}

$selected_destination = False;

if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['submit_domestic_destination']) && !$invalid){
  $_SESSION['travel_state'] = $_POST['domestic_state'];
  $_SESSION['travel_county'] = $_POST['domestic_county'];
  $_SESSION['travel_destination'] = $_POST['domestic_destination'];
  $_SESSION['selected_destination'] = True;
  
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
    <script src="./JS/title.js"></script>
    <script src="./JS/message.js"></script>
    <script src="./JS/highlight_label.js"></script>
    <script src="./JS/travel_selections.js"></script>
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
      if(!$invalid){
        if(!isset($_SESSION) || empty($_SESSION['travel_type']) || $type_invalid){ // Gets destination-type from user
        echo '<div id="input-form">';
          echo '<form method="POST">';
            echo '<select id="destination-type" name="destination-type">';
              echo '<option value="default">Select travel type</option>';
              echo '<option value="domestic">Domestic</option>';
              echo '<option value="international">International</option>';
            echo '</select>';
            echo '<div>';
              echo '<button type="submit" name="submit_type" class="submit-button">Next</button>';
            echo '</div>';
          echo '</form>';
        echo '</div>';
        } else { // Gets destination from user
          if($_SESSION['selected_destination']){
            
          } else {
            if($_SESSION['travel_type'] == 'domestic'){
              echo '<form method="POST">';
                echo '<select name="domestic_state" id="domestic_state">';
                  echo '<option value="" selected="selected">state</option>';
                echo '</select>';
                echo '<select name="domestic_county" id="domestic_county">';
                  echo '<option value="" selected="selected">county</option>';
                echo '</select>';
                echo '<select name="domestic_destination" id="domestic_destination">';
                  echo '<option value="" selected="selected">destination</option>';
                echo '</select>';
                echo '<div>';
                  echo '<button type="submit" name="submit_domestic_destination" class="submit-button">Next</button>';
                echo '</div>';
              echo '</form>';
                
            } else if($_SESSION['travel_type'] == 'international'){
              echo '<p>International</p>';
            }
          }
        }
      } else {
        echo '<a href="'.$direct[0].'">'.$direct[1].'</a>';
      }
      ?>
    <?php
      if(isset($message) && !empty($message)){
        echo '<script>submissionMessage("'.$message.'",'.$error_type.');</script>';
      }
      if(isset($select_domestic_destination_json) && !empty($select_domestic_destination_json)){
        echo '<script>domesticSelection(JSON.parse(\''.$select_domestic_destination_json.'\'))</script>';
      }
    ?>
    </div>
    <script src="" async defer></script>
    <hr id="foot-rule">
  </body>
  <?php
    include 'database/foot.php';
    footer(26,"October",2025,'Josh Gillum');
  ?>
</html>

