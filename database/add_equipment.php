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

if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['add_equipment']) && !$invalid){
  $fields = array(
    array(NULL,'name','name','s'),
    array(NULL,'description','description','s')
  );
  $columns = ""; // SQL query string of which question markes for prepare statement
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
        $columns = $columns."?,";
        $updated = $updated.$field[1].",";
        $format = $format.$field[3];
        $values[] = $field[0];
    }  
  }
  if(isset($updated) && !empty($updated)){
    $table = 'equipment';
    include './db_connection.php';
    $columns = substr($columns,0,-1);
    $updated = substr($updated,0,-1);
    $stmt = $conn->prepare('INSERT INTO '.$table.' ('.$updated.') VALUES ('.$columns.')');
    $stmt->bind_param($format,...$values);
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
    <title>Create Large Equipment</title>
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
      breadcrumbs(array(array("home","../index.php"),array("dashboard","../dashboard.php"),array("budgets","../edit_budget.php?budget_id=".$budget_id.'&year='.$_GET['year']),array("edit-equipment","../edit_budget_equipment.php?budget_id=".$budget_id."&year=".$_GET['year']),array("add-equipment","javascript:location.reload();")));
    ?>
    <div class="content">
      <h1>Create Large Equipment</h1>
      <div id="submission-message-holder"><p></p></div>
      <?php
      if(!$invalid){
        $content_fields = array (
          array("name","Name","Short name of the equipment for reference.","s","Flux capacitor"),
          array("description","Description","Longer description of the equipment. Shows as a tooltip when hovering over the equipment's name","w","The flux capicitor is...")
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
          echo '<button type="submit" name="add_equipment" class="submit-button">Create</button>';
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
