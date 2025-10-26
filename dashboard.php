<?php

function write_to_console($data) {
 $console = $data;
 if (is_array($console))
 $console = implode(',', $console);

 echo "<script>console.log('Console: " . $console . "' );</script>";
}
session_start();
// Check if user is logged in, if not redirect to login page
$budgets = [];
if (!isset($_SESSION['user'])) {
  header("Location: ./database/login.php");
  exit();
} else {
  include './database/db_connection.php';
  $stmt = $conn->prepare("SELECT budget.id, budget.name FROM budget_access JOIN budget ON budget_access.budget_id = budget.id WHERE budget_access.user_id = ?");
  $stmt->bind_param("s", $_SESSION['user']);
  $stmt->execute();
  $result = $stmt->get_result();
  $data = $result->fetch_all(MYSQLI_ASSOC);
  foreach($data as $row){
    write_to_console($row);
    $budgets[] = $row;
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
    <title>Budgets Overview</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./CSS/style.css">
    <script src="./JS/title.js"></script>
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
          <a href="./dashboard.php" class="menu-item active">
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
      <p>budgets-overview</p>
    </div>
    <div class="content">
      <h1>Budgets</h1>
      <?php
        if(isset($budgets) && !empty($budgets)){
          echo '<p>Budgets</p>';
          foreach($budgets as $item){
            echo '<p>Budget '.$item['id'].': '.$item['name'].'</p>';
          }
        }
      ?>
    </div>
    <script src="" async defer></script>
    <hr id="foot-rule">
  </body>
  <footer>
    <div class="split-items">
      <p>Last updated: <span>25 October 2025</span></p>
      <p>Author: Josh Gillum</p>
    </div>
    <div class="split-items">
      <a href="./cookies.html">cookies</a>
      <a href="./privacy.html">privacy policy</a>
      <a href="./terms.html">terms and conditions</a>
    </div>
  </footer>
</html>
