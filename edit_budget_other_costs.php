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
$split = explode("-",$year);
if($split[0] == "year"){
  $year = $split[1];
}
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


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_other_costs']) && !$invalid){
  write_to_console("Allowed");
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
    if (!$invalid){
      echo '<form method="POST">';
        echo '<div class="split-items">';
          echo '<label for="materials_and_supplies" class="tooltip">Materials and Supplies: ';
            echo '<span class="tooltiptext">Various materials and supplies. Added all together.</span>';
          echo '</label>';
          echo '<input type="text" name="materials_and_supplies" id="materials_and_supplies" class="text-input-small" placeholder="$'.$data['materials_and_supplies'].'" maxlength="45"/>';
        echo '</div>';
        echo '<div class="split-items">';
          echo '<label for="small_equipment" class="tooltip">Small Equipment: ';
            echo '<span class="tooltiptext">Any equipment or tools with individual values below $5,000.</span>';
          echo '</label>';
          echo '<input type="text" name="small_equipment" id="small_equipment" class="text-input-small" placeholder="$'.$data['small_equipment'].'" maxlength="45"/>';
        echo '</div>';
        echo '<div class="split-items">';
          echo '<label for="publication" class="tooltip">Publication: ';
            echo '<span class="tooltiptext">Costs for publishing papers or other documents.</span>';
          echo '</label>';
          echo '<input type="text" name="publication" id="publication" class="text-input-small" placeholder="$'.$data['publication'].'" maxlength="45"/>';
        echo '</div>';
        echo '<div class="split-items">';
          echo '<label for="computer_services" class="tooltip">Computer Services: ';
            echo '<span class="tooltiptext">Costs for various computer services such as IT.</span>';
          echo '</label>';
          echo '<input type="text" name="computer_services" id="computer_services" class="text-input-small" placeholder="$'.$data['computer_services'].'" maxlength="45"/>';
        echo '</div>';
        echo '<div class="split-items">';
          echo '<label for="software" class="tooltip">Software: ';
            echo '<span class="tooltiptext">Cost of software or other computer programs.</span>';
          echo '</label>';
          echo '<input type="text" name="software" id="software" class="text-input-small" placeholder="$'.$data['software'].'" maxlength="45"/>';
        echo '</div>';
        echo '<div class="split-items">';
          echo '<label for="facility_fees" class="tooltip">Facility Fees: ';
            echo '<span class="tooltiptext">Fees for renting out facilities or conference centers.</span>';
          echo '</label>';
          echo '<input type="text" name="facility_fees" id="facility_fees" class="text-input-small" placeholder="$'.$data['facility_fees'].'" maxlength="45"/>';
        echo '</div>';
        echo '<div class="split-items">';
          echo '<label for="conference_registration" class="tooltip">Conference Registration Fees: ';
            echo '<span class="tooltiptext">Ticket costs and fees for attending or hosting conferences.</span>';
          echo '</label>';
          echo '<input type="text" name="conference_registration" id="conference_registration" class="text-input-small" placeholder="$'.$data['conference_registration'].'" maxlength="45"/>';
        echo '</div>';
        echo '<div class="split-items">';
          echo '<label for="other" class="tooltip">Other/Miscellaneous Costs: ';
            echo '<span class="tooltiptext">Any extra costs or fees that do not fit in any other category.</span>';
          echo '</label>';
          echo '<input type="text" name="other" id="other" class="text-input-small" placeholder="$'.$data['other'].'" maxlength="45"/>';
        echo '</div>';
        echo '<div>';
        echo '<input type="hidden" name="year" id="year" value="'.$year.'"/>';
        echo '<input type="hidden" name="budget_id" id="budget_id" value="'.$budget_id.'"/>';
        echo '</div>';
        echo '<div>';
          echo '<button type="submit" name="update_other_costs" class="styled-button submit-button">Modify</button>';
        echo '</div>';
      echo '</form>';
    }
    ?>
    <?php
      if(isset($message) && !empty($message)){
        echo '<script>submissionMessage("'.$message.'",'.$error_type.');</script>';
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

