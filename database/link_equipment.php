<?php
$message = "";
$error_type = 1;
$invalid = False;
$direct = array("./login.php?send_back_to=".$_SERVER['REQUEST_URI'].$_SERVER['QUERY_STRING'],"Login");
session_start();
if(!isset($_SESSION['user'])){
  $message = "You must be logged in to access this page.";
  $invalid = True;
}

$budget_id = $_GET['budget_id'];

if(!$invalid && !isset($budget_id)){
  $message = "Invalid referral link.";
  $invalid = True;
  $direct = array("../dashboard.php","Dashboard");
}

if(!$invalid){
  include './check_access.php';
  $has_access = check_access($_SESSION['user'],$budget_id,$to_root="../");
  if(!$has_access){
    $message = "Access denied.";
    $invalid = 1;
    $direct = array("../dashboard.php","Dashboard");
  }
}

try {
  if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['link_equipment']) && !$invalid){
    $equipment_id = $_POST['equipment_id'];

    include './db_connection.php';
    $stmt = $conn->prepare("SELECT id from equipment WHERE id = ?");
    $stmt->bind_param("s",$equipment_id);
    $stmt->execute();
    $stmt->store_result();
    if($stmt->num_rows == 1){
      $stmt->close();
      $stmt = $conn->prepare("INSERT INTO budget_equipment (budget_id,equipment_id,year) VALUES (?,?,?)");
      $stmt->bind_param("sss",$budget_id,$equipment_id,$year);
      foreach(array(1,2,3,4,5) as $year){
        if(!$stmt->execute()){
          $message = "Equipment has already been linked to this budget.";
          $error_type = 1;
        }
      }
      $message = "Successfully linked equipment.";
      $error_type = 0;
      $stmt->close();
    } else {
      $message = "Invalid equipment id.";
      $error_type = 1;
      $invalid_id = 1;
    }
    $conn->close();
  }
} catch(Exception $e){
  $message = "Error: ".$e->getMessage();
  $error_type = 1;
}

if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['unlink_equipment']) && !$invalid){
  $equipment_id = $_POST['equipment_id'];

  include './db_connection.php';
  $stmt = $conn->prepare("SELECT id from equipment WHERE id = ?");
  $stmt->bind_param("s",$equipment_id);
  $stmt->execute();
  $stmt->store_result();
  if($stmt->num_rows == 1){
    $stmt->close();
    $stmt = $conn->prepare("DELETE FROM budget_equipment WHERE budget_id=? AND equipment_id=?");
    $stmt->bind_param("ss",$budget_id,$equipment_id);
    if(!$stmt->execute()){
      $message = "Equipment can not be unlinked.";
      $error_type = 1;
    } else {
      $message = "Successfully unlinked.";
      $error_type = 0;
    }
    $stmt->close();
  } else {
    $message = "Invalid equipment id.";
    $error_type = 1;
    $invalid_id = 1;
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
    <title>Link Large Equipment</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../CSS/style.css">
    <script src="../JS/title.js"></script>
    <script src="../JS/message.js"></script>
    <script src="../JS/highlight_label.js"></script>
  </head>
  <body>
    <!--[if lt IE 7]>
      <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="#">upgrade your browser</a> to improve your experience.</p>
    <![endif]-->
    <?php
      include 'nav.php';
      navigation(isset($_SESSION['user']),$to_root="../");
      include 'breadcrumb.php';
      breadcrumbs(array(array("home","../index.php"),array("dashboard","../dashboard.php"),array("budgets","../edit_budget.php?budget_id=".$budget_id.'&year='.$_GET['year']),array("edit-equipment","../edit_budget_equipment.php?budget_id=".$budget_id."&year=".$_GET['year']),array("link-equipment","javascript:location.reload();")));
    ?>
    <div class="content">
      <h1>Link Large Equipment</h1>
      <div id="submission-message-holder"><p></p></div>
      <?php
      if(!$invalid){
        $content_fields = array (
          array("equipment_id","Equipment ID","ID of the equipment. Use the search to find the ID.","s","123"),
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
          $required = "";
          if($item[0] == 'name'){
            $required = " required";
          }
          echo '<input type="text" name="'.$item[0].'" id="'.$item[0].'"'.$middle.' placeholder="'.$item[4].'" maxlength='.$size.' onfocus="highlightLabel(\''.$item[0].'\',true);" onfocusout="highlightLabel(\''.$item[0].'\',false);"'.$required.'/>';
          echo '</div>';
        }
        echo '<div>';
          echo '<button type="submit" name="link_equipment" class="submit-button">Link</button>';
          echo '<button type="submit" name="unlink_equipment" class="submit-button">Unlink</button>';
        echo '</div>';
        echo '</form>';
        
      } else {
        echo '<a href="'.$direct[0].'">'.$direct[1].'</a>';
      }
      if(isset($message) && !empty($message)){
        echo '<script>submissionMessage("'.$message.'",'.$error_type.');</script>';
      }
      echo '<br>';
      echo '<a href="./search_equipment.php?budget_id='.$budget_id.'&year='.$_GET['year'].'">Search for Equipment</a>';
      ?>
    </div>
    <script src="" async defer></script>
    <hr id="foot-rule">
  </body>
  <?php
    include 'foot.php';
    footer(26,"October",2025,'Josh Gillum',"../");
  ?>
</html>
