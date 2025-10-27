<?php

function write_to_console($data) {
 $console = $data;
 if (is_array($console))
 $console = implode(',', $console);

 echo "<script>console.log('Console: " . $console . "' );</script>";
}

$direct = array("./database/login.php","Login");
$message = "";
$error_type = 1;
$invalid = 0;
session_start();
// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user'])) {
  $invalid = 1;
  $message = "You must be logged in to access this page.";
}

$year = $_GET["year"];
$budget_id = $_GET["budget_id"];

if(!$invalid){
  // Ensures that year and budget id are set in the url.
  if(isset($year) && isset($budget_id) && !empty($year) && !empty($budget_id)){
    include './database/check_access.php';
    $has_access = check_access($_SESSION['user'],$budget_id);
    if(!$has_access){
      $message = "Access denied.";
      $invalid = 1;
      $direct = array("./dashboard.php","Dashboard");
    }
  } else {
    $message = "Invalid referrel link.";
    $invalid = 1;
    $direct = array("./dashboard.php","Dashboard");
  }
}

if (!$invalid){
  include './database/db_connection.php';
  $stmt = $conn->prepare("SELECT equipment.id as equipment_id,equipment.name,equipment.description,budget_equipment.cost FROM equipment JOIN budget_equipment on equipment.id=budget_equipment.equipment_id WHERE budget_id=? AND year=?");
  $stmt->bind_param("ss",$budget_id,$year);
  if($stmt->execute()){
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    if(!isset($data) || empty($data)){
      $message = "Error: No entry found. ID: ".$budget_id;
      $error_type = 1;
      $invalid = 1;
      $direct = array("./dashboard.php","Dashboard");
    }
  } else {
    $message = "Error: ".$stmt->error;
    $error_type = 1;
    $invalid = 1;
    $direct = array("./dashboard.php","Dashboard");
  }
}


$updated_placeholders = [];
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_equipment_costs']) && !$invalid){
  $table = "budget_equipment";
  $invalid_number = 0;
  include './database/format_numbers.php';
  // $fields stores an array of fields in the database that can be updated.
  // Index 0 - Explicitly define variable holding data. Set to NULL to fetch data from $_POST
  //       1 - Name of column in the database
  $fields = [];
  foreach($data as $row){
    $fields[] = array(NULL,$row['equipment_id']);
  }
  $stmt = $conn->prepare('UPDATE '.$table.' SET cost=? WHERE budget_id=? AND equipment_id=? AND year=?');
  foreach($fields as $field){
    // If variable is not explicitly set, fetch from POST
    if(is_null($field[0])){ 
      $field[0] = $_POST[$field[1]];
    }
    // Checks if value was updated in POST
    if(isset($field[0]) && !empty($field[0])){
      $code = "";
      if(!($code=format_number($field[0]))){ // Checks if the number is correctly formatted.
        $updated_placeholders[] = array($field[1],$field[0]);
        $stmt->bind_param("ssss",$field[0],$budget_id,$field[1],$year);
        if(!$stmt->execute()){
          $message = "Error: ".$stmt->error;
          $invalid_number = 1;
          $updated_placeholders = [];
          break;
        }
      } else {
        $message = "Invalid number: \'".$field[0]."\' for field: \'".$field[1]."\'. ".$code;
        $invalid_number = 1;
        $updated_placeholders = [];
        break;
      }
    }
  }
  if(!$invalid_number){
    $message = "Successfully updated database.";
    $error_type = 0;
    $stmt->close();
  }
  $conn->close();
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
    <title>Edit Budget Equipment</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./CSS/style.css">
    <script src="./JS/title.js"></script>
    <script src="./JS/message.js"></script>
    <script src="./JS/placeholder.js"></script>
    <script src="./JS/highlight_label.js"></script>
  </head>
  <body>
    <!--[if lt IE 7]>
      <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="#">upgrade your browser</a> to improve your experience.</p>
    <![endif]-->
    <?php
      include 'database/nav.php';
      navigation(isset($_SESSION['user']));
      include 'database/breadcrumb.php';
      breadcrumbs(array(array("home","./index.php"),array("budgets","./dashboard.php"),array("edit-budget","./edit_budget.php?budget_id=".$budget_id),array("equipment","javascript:location.reload();")));
    ?>
    <div class="content">
    <h1>Edit Budget Equipment</h1>
    <div id="submission-message-holder"><p></p></div>
    <?php
    if (!$invalid && $has_access){
      // Stores information for input fields.
      // Index: 0 - name / id of input
      //        1 - label text
      //        2 - tooltip text (Use NULL for no tooltip)
      //        3 - input field size (s for small, w for wide)
      echo '<form method="POST">';
      foreach($data as $row){
        $item = array($row['equipment_id'],$row['name'],$row['description'],"s");
        echo '<div class="split-items">';
        $middle = '';
        if(!is_null($item[2])){
          $middle = ' class="tooltip"';
        }
        echo '<label for="'.$item[0].'"'.$middle.'>'.$item[1];
        if(!is_null($item[2])){
          echo '<span class="tooltiptext">'.$item[2].'</span>';
        }
        echo '</label>';
        $middle = "";
        $size = "45";
        if($item[3] == "s"){
          $middle = ' class="text-input-small"';
        } else if($item[3] == "w"){
          $middle = ' class="text-input-wide"';
          $size = 255;
        }
        echo '<input type="text" name="'.$item[0].'" id="'.$item[0].'"'.$middle.' placeholder="$'.$row['cost'].'" maxlength='.$size.' onfocus="highlightLabel(\''.$item[0].'\',true);" onfocusout="highlightLabel(\''.$item[0].'\',false);"/>';
        echo '</div>';
      }
        echo '<a href="database/add_equipment.php?budget_id='.$budget_id.'">Add Equipment</a>';
        echo '<div>';
          echo '<button type="submit" name="update_equipment_costs" class="submit-button">Modify</button>';
        echo '</div>';
      echo '</form>';
    } else {
      echo '<a href="'.$direct[0].'">'.$direct[1].'</a>';
    }
    ?>
    </div>
    <?php
      if(isset($message) && !empty($message)){
        echo '<script>submissionMessage("'.$message.'",'.$error_type.');</script>';
      }
      if(isset($updated_placeholders) && !empty($updated_placeholders)){
        foreach($updated_placeholders as $item){
          echo '<script>updatePlaceholder("'.$item[0].'","$'.$item[1].'");</script>';
        }
      }
    ?>
    <script src="" async defer></script>
    <hr id="foot-rule">
  </body>
  <?php
    include 'database/foot.php';
    footer(26,"October",2025,'Josh Gillum');
  ?>
</html>

