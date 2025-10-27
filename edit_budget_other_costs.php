<?php

function write_to_console($data) {
 $console = $data;
 if (is_array($console))
 $console = implode(',', $console);

 echo "<script>console.log('Console: " . $console . "' );</script>";
}

session_start();
// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user'])) {
  header("Location: database/login.php");
  exit();
}

$year = $_GET["year"];
$budget_id = $_GET["budget_id"];
$message = "";
$error_type = 1;
$invalid = 0;

// Ensures that year and budget id are set in the url.
if(isset($year) && isset($budget_id) && !empty($year) && !empty($budget_id)){
  include './database/check_access.php';
  $has_access = check_access($_SESSION['user'],$budget_id);
  if(!$has_access){
    $message = "Access denied.";
    $invalid = 1;
    $error_type = 1;
  }
} else {
  $message = "Invalid referrel link.";
  $error_type = 1;
  $invalid = 1;
}

if (!$invalid){
  include './database/db_connection.php';
  $stmt = $conn->prepare("SELECT materials_and_supplies, small_equipment, publication, computer_services, software, facility_fees, conference_registration, other FROM budget_other_costs WHERE id=? and year=?");
  $stmt->bind_param("ss",$year,$budget_id);
  if($stmt->execute()){
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    if(!isset($data) || empty($data)){
      $message = "Error: No entry found.";
      $error_type = 1;
      $invalid = 1;
    }
  } else {
    $message = "Error: ".$stmt->error;
    $error_type = 1;
    $invalid = 1;
  }
}


$updated_placeholders = [];
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_other_costs']) && !$invalid){
  $table = "budget_other_costs";
  include './database/format_numbers.php';
  $fields = array( // Stores each field that can be updated.
    array(NULL,'materials_and_supplies','materials_and_supplies','s'),
    array(NULL,'small_equipment','small_equipment','s'),
    array(NULL,'publication','publication','s'),
    array(NULL,'computer_services','computer_services','s'),
    array(NULL,'software','software','s'),
    array(NULL,'facility_fees','facility_fees','s'),
    array(NULL,'conference_registration','conference_registration','s'),
    array(NULL,'other','other','s')
  );
  $updated = ""; // SQL query string of which columns to update
  $format = ""; // Format specifiers for bind_param
  $values = []; // Variables holding values for bind_param.
  $invalid_number = 0;
  foreach($fields as $field){
    // If variable is not explicitly set, fetch from POST
    if(is_null($field[0])){ 
      $field[0] = $_POST[$field[2]];
    }
    // Checks if value was updated in POST
    if(isset($field[0]) && !empty($field[0])){
      $code = "";
      if(!($code=format_number($field[0]))){ // Checks if the number is correctly formatted.
        $updated = $updated.$field[1]."=?,";
        $updated_placeholders[] = array($field[2],$field[0]);
        $format = $format.$field[3];
        $values[] = $field[0];
      } else { // Displays a message stating what went wrong
        $message = "Invalid number: \'".$field[0]."\' for field: \'".$field[1]."\'. ".$code;
        $error_type = 1;
        $invalid_number = 1;
        break;
      }
    }
  }
  if(!$invalid_number){
    if(isset($updated) && !empty($updated)){ // Ensures at least one value will be updated
      $updated = substr($updated,0,-1);
      $stmt = $conn->prepare('UPDATE '.$table.' SET '.$updated.' WHERE id=? AND year=?');
      $values[] = $budget_id; // Appends id to list of values so it can be unpacked.
      $values[] = $year;
      write_to_console($values);
      $stmt->bind_param($format."ss",...$values);
      if($stmt->execute()){
        $message = "Successfully updated database.";
        $error_type = 0;
      } else {
        $message = "Error: ".$stmt->error;
        $error_type = 1;
        $updated_placeholders = [];
      }
      $stmt->close();
      $conn->close();
    }
  } else {
    $updated_placeholders = [];
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
    <title>Edit Budget Other Costs</title>
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
    <div>
      <div class="navigation-head">
        <div class="site-logo">
          <a href="./index.php">
            <h1>RaBBiT</h1>
          </a>
        </div>
        <ul class="navigation-menu">
          <a href="./dashboard.php" class="menu-item">
            <li class="underline-hover-effect">Budgets</li>
          </a>
          <a href="./personnel.php" class="menu-item">
            <li class="underline-hover-effect">Personnel</li>
          </a>
          <div class="menu-item dropdown login">
            <li class="underline-hover-effect">Account</li>
            <div class="dropdown-content">
              <ul>
                <li><a href="./database/manage_account.php" class="menu-item">Manage</a></li>
                <li><a href="./database/logout.php" class="menu-item">Logout</a></li>
                <hr>
                <li><a href="./database/delete_account.php" class="menu-item error">Delete&nbspAccount</a></li>
              </ul>
            </div>
          </div>
        </ul>
      </div>
      <hr id="head-rule">
    </div>
    <div class="breadcrumbs">
      <a href="./index.php">home</a>
      <p>></p>
      <a href="./dashboard.php">budgets</a>
      <p>></p>
      <a href="./edit_budget.php">edit-budget</a>
      <p>></p>
      <p>other-costs</p>
    </div>
    <div class="content">
    <h1>Edit Budget Other Costs</h1>
    <div id="submission-message-holder"><p></p></div>
    <?php
    if (!$invalid && $has_access){
      // Stores information for input fields.
      // Index: 0 - name / id of input
      //        1 - label text
      //        2 - tooltip text (Use NULL for no tooltip)
      //        3 - input field size (s for small, w for wide)
      $content_fields = array (
        array("materials_and_supplies","Materials and Supplies:","Various materials and supplies. Added all together.","s"),
        array("small_equipment","Small Equipment:","Any equipment or tools with individual values below $5,000.","s"),
        array("publication","Publication:","Costs for publishing papers or other documents.","s"),
        array("computer_services","Computer Services:","Costs for various computer services such as IT.","s"),
        array("software","Software:","Cost of softare or other computer programs.","s"),
        array("facility_fees","Facility Fees:","Fees for renting out facilities or conference centers.","s"),
        array("conference_registration","Conference Registration Fees:","Ticket costs and fees for attending or hosting conferences.","s"),
        array("other","Other/Miscellaneous Costs:","Any extra costs or fees that do not fit in any other category.","s")
      );
      echo '<form method="POST">';
      foreach($content_fields as $item){
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
        echo '<input type="text" name="'.$item[0].'" id="'.$item[0].'"'.$middle.' placeholder="$'.$data[$item[0]].'" maxlength='.$size.' onfocus="highlightLabel(\''.$item[0].'\',true);" onfocusout="highlightLabel(\''.$item[0].'\',false);"/>';
        echo '</div>';
      }
      echo '<div>';
        echo '<button type="submit" name="update_other_costs" class="submit-button">Modify</button>';
      echo '</div>';
      echo '</form>';
    } else {
      echo '<a href="./dashboard.php">Return to dashboard</a>';
    }
    ?>
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
    </div>
    <script src="" async defer></script>
    <hr id="foot-rule">
  </body>
  <footer>
    <div class="split-items">
      <p>Last updated: <span>26 October 2025</span></p>
      <p>Author: Josh Gillum</p>
    </div>
    <div class="split-items">
      <a href="./cookies.html">cookies</a>
      <a href="./privacy.html">privacy policy</a>
      <a href="./terms.html">terms and conditions</a>
    </div>
  </footer>
</html>

