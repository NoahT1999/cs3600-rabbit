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

$tab = $_GET["year"];
$budget_id = $_GET["budget_id"];

if(!isset($budget_id) || empty($budget_id)){
  $has_access = False;
  $message = "Invalid referral link";
  $error_type = 1;
} else {
  include './database/check_access.php';
  $has_access = check_access($_SESSION['user'],$budget_id);
  if(!$has_access){
    $message = "Access denied.";
    $error_type = 1;
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
    <title>Edit Budget</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./CSS/style.css">
    <script src="./JS/title.js"></script>
    <script src="./JS/tabs.js"></script>
    <script src="./JS/message.js"></script>
  </head>
  <body>
    <!--[if lt IE 7]>
      <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="#">upgrade your browser</a> to improve your experience.</p>
    <![endif]-->
    <?php
      include 'database/nav.php';
      navigation(isset($_SESSION['user']));
      include 'database/breadcrumb.php';
      breadcrumbs(array(array("home","./index.php"),array("budgets","./dashboard.php"),array("edit-budget","./javascript:location.reload();")));
    ?>
    <div class="content">
      <h1>Edit Budget</h1>
      <div id="submission-message-holder"><p></p></div>
        <?php
        if($has_access){
          echo '<div class="tab">';
            echo '<div class="tab-buttons">';
            $names = array(
              array("year-1","Year 1","1"),
              array("year-2","Year 2","2"),
              array("year-3","Year 3","3"),
              array("year-4","Year 4","4"),
              array("year-5","Year 5","5")
            ); 
                if(!isset($tab)){
                  $tab="year-1";
                }

                foreach($names as $item){
                  $prefix = '<button class="tablinks"';
                  $middle = '';
                  $suffix = ' onclick="openTab(event,\''.$item[0].'\')">'.$item[1].'</button>';
                  if($tab == $item[0]){
                    $middle = ' id="defaultOpen"';
                  }
                  echo $prefix.$middle.$suffix;
            }
          echo '</div>';
          echo '<!-- Tab content -->';
          


          foreach($names as $item){
            echo '<div id="'.$item[0].'" class="tabcontent">';
            echo '<div class="tab-title">';
            echo '<h3>'.$item[1].'</h3>';
            echo '</div>';
            echo '<div>';
            echo '<ul class="overview-list">';
              echo '<li><a href="edit_budget_personnel.php?year='.$item[2].'&budget_id='.$budget_id.'">Personnel</a></li>';
              echo '<li><a href="edit_budget_other_personnel.php?year='.$item[2].'&budget_id='.$budget_id.'">Other Personnel</a></li>';
              echo '<li><a href="edit_budget_fringe.php?year='.$item[2].'&budget_id='.$budget_id.'">Fringe</a></li>';
              echo '<li><a href="edit_budget_equipment.php?year='.$item[2].'&budget_id='.$budget_id.'">Large Equipment</a></li>';
              echo '<li><a href="edit_budget_travel.php?year='.$item[2].'&budget_id='.$budget_id.'">Travel</a></li>';
              echo '<li><a href="edit_budget_other_costs.php?year='.$item[2].'&budget_id='.$budget_id.'">Other Costs</a></li>';
            echo '</ul>';
            echo '</div>';
            echo '</div>';
          }  
        } else {
          echo '<a href="./dashboard.php">Return to dashboard.</a>';
        }
        if(isset($message) && !empty($message)){
          echo '<script>submissionMessage("'.$message.'",'.$error_type.');</script>';
        }
        ?>
        <script>
          // Get the element with id="defaultOpen" and click on it
          document.getElementById("defaultOpen").click();
        </script>
      </div>
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
