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
  header("Location: ./login.php");
  exit();
}

$budget_id = $_GET["budget_id"];
$message = "";
$error_type = 1;
$invalid = 0;
$has_access = False;
$current_effective_date = "";


if (!$invalid){
  include './db_connection.php';
  $stmt = $conn->prepare("SELECT budget_access.budget_id, budget.name, budget.effective_date from budget_access JOIN budget on budget.id = budget_access.budget_id WHERE user_id=?");
  $stmt->bind_param("s",$_SESSION['user']);
  if($stmt->execute()){
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    if(!isset($data) || empty($data)){
      $message = "Error: No entry found. ID: ".$budget_id." YEAR: ".$year;
      $error_type = 1;
      $invalid = 1;
    } else {
      if(isset($budget_id) && !empty($budget_id)){
        foreach($data as $row){
          if($row['budget_id'] == $budget_id){
            $has_access = True;
            $current_effective_date = $row['effective_date'];
            break;
          }
        }
        if(!$has_access){
          $message = "Access denied.";
          $invalid = 1;
        }
      }
    }
  } else {
    $message = "Error: ".$stmt->error;
    $error_type = 1;
    $invalid = 1;
  }
}


if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_budget_settings"]) && !$invalid){
  include './check_access.php';
  $has_access = check_access($_SESSION['user'],$budget_id,$to_root="../");
  if($has_access) {
    $start = $_POST['effective_date'];
    if(isset($start) && !empty($start)){
      include './db_connection.php';
      $stmt = $conn->prepare("UPDATE budget SET effective_date=? where id=?");
      $stmt->bind_param("ss",$start,$budget_id);
      if($stmt->execute()){
        $message = "Successfully updated database.";
        $error_type = 0;
        $current_effective_date = $start;
      } else {
        $message = "Error: ".$stmt->error;
        $error_type = 1;
      }
      $stmt->close();
      $conn->close();
    }
  }
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_budget"]) && !$invalid){
  include './check_access.php';
  $has_access = check_access($_SESSION['user'],$budget_id,$to_root="../");
  if($has_access) {
    include './db_connection.php';
    $stmt = $conn->prepare("DELETE FROM budget where id=?");
    $stmt->bind_param("s",$budget_id);
    if($stmt->execute()){
      $message = "Successfully updated database.";
      $error_type = 0;
      $current_effective_date = $start;
      $invalid = True;
    } else {
      $message = "Error: ".$stmt->error;
      $error_type = 1;
    }
    $stmt->close();
    $conn->close();
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
    <title>Budget Settings</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../CSS/style.css">
    <script src="../JS/title.js"></script>
    <script src="../JS/message.js"></script>
    <script src="../JS/placeholder.js"></script>
    <script src="../JS/highlight_label.js"></script>
  </head>
  <body>
    <!--[if lt IE 7]>
      <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="#">upgrade your browser</a> to improve your experience.</p>
    <![endif]-->
    <?php
      include './nav.php';
      navigation(isset($_SESSION['user']),$to_root="../");
      include './breadcrumb.php';
      breadcrumbs(array(array("home","./index.php"),array("budgets","./dashboard.php"),array("edit-budget","./edit_budget.php?budget_id=".$budget_id.'&year='.$year),array("other-costs","javascript:location.reload();")));
    ?>
    <div class="content">
    <h1>Budget Settings</h1>
    <div id="submission-message-holder"><p></p></div>
    <?php
    if(!$invalid){
      if ($has_access){
        echo '<p>Current start date: '.$current_effective_date.'</p>';
        echo '<form method="POST">';
          echo '<div class="split-items">';
          echo '<label for="effective_date" class="tooltip">Effective Start Date<span class="tooltiptext">The day that this budget takes effect on</span></label>';
            echo '<input type="date" id="effective_date" name="effective_date" onfocus="highlightLabel(\'effective_date\',true);" onfocusout="highlightLabel(\'effective_date\',false);">';
          echo '</div>';
        echo '<div>';
          echo '<button type="submit" name="update_budget_settings" class="submit-button">Modify</button>';
        echo '</div>';
        echo '</form>';
        // Danger zone
        echo '<hr>';
        echo '<h3 class="error">Danger Zone</h3>';
        echo '<form method="POST">';
          echo '<div>';
            echo '<button type="submit" name="delete_budget" class="submit-button">Delete Budget</button>';
          echo '</div>';
        echo '</form>';
      } else {
        if(isset($data) && !empty($data)){
          foreach($data as $row){
            echo '<a href="./budget_settings.php?budget_id='.$row['budget_id'].'">'.$row['name'].'</a><br>';
          }
        } else {
          $message = "Error...";
          $invalid = True;
        }
      }
    } else {
      echo '<a href="../dashboard.php">Return to dashboard</a>';
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
  <?php
    include './foot.php';
    footer(27,"October",2025,'Josh Gillum');
  ?>
</html>

