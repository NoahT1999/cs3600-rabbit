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
    <title>Task Tracker</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./CSS/style.css">
    <script src="./JS/title.js"></script>
    <script src="./JS/tabs.js"></script>
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
      <p>edit-budget</p>
    </div>
    <div class="content">
      <div class="tab">
        <div class="tab-buttons">
          <?php
          $names = array(
            array("year-1","Year 1"),
            array("year-2","Year 2"),
            array("year-3","Year 3"),
            array("year-4","Year 4"),
            array("year-5","Year 5")
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
            echo '<li><a href="edit_budget_personnel.php?year='.$item[0].'&budget_id='.$budget_id.'">Personnel</a></li>';
            echo '<li><a href="edit_budget_other_personnel.php?year='.$item[0].'&budget_id='.$budget_id.'">Other Personnel</a></li>';
            echo '<li><a href="edit_budget_fringe.php?year='.$item[0].'&budget_id='.$budget_id.'">Fringe</a></li>';
            echo '<li><a href="edit_budget_equipment.php?year='.$item[0].'&budget_id='.$budget_id.'">Large Equipment</a></li>';
            echo '<li><a href="edit_budget_travel.php?year='.$item[0].'&budget_id='.$budget_id.'">Travel</a></li>';
            echo '<li><a href="edit_budget_other_costs.php?year='.$item[0].'&budget_id='.$budget_id.'">Other Costs</a></li>';
          echo '</ul>';
          echo '</div>';
          echo '</div>';
        }

        ?>
        <script>
          // Get the element with id="defaultOpen" and click on it
          document.getElementById("defaultOpen").click();
        </script>
      </div>
    <div><p id="demo"></p></div>
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
