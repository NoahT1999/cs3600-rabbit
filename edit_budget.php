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

$tab = 'year-'.$_GET["year"];
$budget_id = $_GET["budget_id"];
$length = "";
$error = False;

$other_costs = [];
$equipment = [];

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
  } else {
    include './database/db_connection.php';
    $stmt = $conn->prepare("SELECT length from budget where id=?");
    $stmt->bind_param("s",$budget_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    if(isset($data) && !empty($data)){
      $length = $data['length'];

      // Fetch other costs and store them
      $stmt = $conn->prepare("SELECT * from budget_other_costs where id=?");
      $stmt->bind_param("s",$budget_id);
      if($stmt->execute()){
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        if(isset($data) && !empty($data)){
          $keys = array_keys($data[0]);
          unset($keys[array_search('id',$keys)]);
          foreach($data as $row){
            foreach($keys as $key){
              if($key != 'year'){
                if(!isset($other_costs[$key])){
                  $other_costs[$key] = [];
                }
                $other_costs[$key][$row['year']] = $row[$key];
              }
            }
          }
        } else {
          $message = "Failed to fetch other costs.";
          $error = True;
        }
      } else {
        $message = "Failed to fetch other costs.";
        $error = True;
      }

      // Fetches large equipment data
      if(!$error){
        $stmt = $conn->prepare("SELECT b.year, b.cost, e.name FROM budget_equipment as b JOIN equipment as e on b.equipment_id = e.id WHERE b.budget_id=?");
        $stmt->bind_param("s",$budget_id);
        if($stmt->execute()){
          $result = $stmt->get_result();
          $data = $result->fetch_all(MYSQLI_ASSOC);
          $stmt->close();
          if(isset($data) && !empty($data)){
            foreach($data as $row){
              $key = $row['name'];
                if(!isset($equipment[$key])){
                  $equipment[$key] = [];
                }
                $equipment[$key][$row['year']] = $row['cost'];
              }
          } else {
            $message = "Failed to fetch large equipment.";
            $error = True;
          }
        } else {
          $message = "Failed to fetch large equipment.";
          $error = True;
        }
      }

    } else {
      $message = "Error fetching budget.";
      $error = True;
    }
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
    <title>Edit Budget</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./CSS/style.css">
    <script src="./JS/title.js"></script>
    <script src="./JS/message.js"></script>
    <script src="./JS/edit_budget.js"></script>
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
      breadcrumbs(array(array("home","./index.php"),array("budgets","./dashboard.php"),array("edit-budget","./javascript:location.reload();")));
    ?>
    <div class="content">
      <div class="split-items">
        <h1>Edit Budget</h1>
        <button onclick="toggle_edit_mode(['equipment','other_costs']);">Toggle Edit Mode</button>
      </div>
      <div id="submission-message-holder"><p></p></div>
        <?php
        if($has_access && !$error){
          $table_width = $length + 1;
          echo '<table class="budget_table">';
            echo '<thead>';
              echo '<tr>';
                echo '<th></th>';
                for($i = 0; $i < $length; $i++){
                  echo '<th>Year '.($i+1).'</th>';
                }
              echo '</tr>';
            echo '</thead>';
            echo '<tbody id="personnel">';
              echo '<tr><th colspan="'.$table_width.'" class="table_subsection_header">Personnel</th></tr>';
              echo '<tr>';
                echo '<td></td>';
                for($i = 0; $i < $length; $i++){
                  echo '<td>Value '.($i+1).'</td>';
                }
              echo '</tr>';
            echo '</tbody>';
            echo '<tbody id="equipment">';
              echo '<tr><th colspan="'.$table_width.'" class="table_subsection_header">Large Equipment</th></tr>';
                if(isset($equipment) && !empty($equipment)){
                  foreach(array_keys($equipment) as $key){
                    echo '<tr>';
                    // View mode
                    echo '<td class="row_header">'.ucfirst(str_replace("_"," ",$key)).'</td>';
                    for($i = 1; $i < $length+1; $i++){
                      $cost = $equipment[$key][$i];
                      if($cost == "0.00" or $cost == 0){
                        $cost = '-';
                      } else {
                        $cost = "$".$cost;
                      }
                      // View mode
                      echo '<td class="data-view">'.$cost.'</td>';
                      // Edit mode
                      echo '<td class="data-edit hidden"><input id="equipment_'.$key.'_'.$i.'" name="equipment_'.$key.'_'.$i.'" type="text" placeholder="'.$cost.'" onfocus="highlightHeader(\'equipment_'.$key.'_'.$i.'\',true);" onfocusout="highlightHeader(\'equipment_'.$key.'_'.$i.'\',false);"></input></td>';
                    }
                    echo '</tr>';
                  }
                }
            echo '</tbody>';
            echo '<tbody id="travel">';
              echo '<tr><th colspan="'.$table_width.'" class="table_subsection_header">Travel</th></tr>';
              echo '<tr>';
                echo '<td></td>';
                for($i = 0; $i < $length; $i++){
                  echo '<td>Value '.($i+1).'</td>';
                }
              echo '</tr>';
            echo '</tbody>';
            echo '<tbody id="other_costs">';
              echo '<tr><th colspan="'.$table_width.'" class="table_subsection_header">Other Costs</th></tr>';
                if(isset($other_costs) && !empty($other_costs)){
                  foreach(array_keys($other_costs) as $key){
                    echo '<tr>';
                    echo '<td class="row_header">'.ucfirst(str_replace("_"," ",$key)).'</td>';
                    for($i = 1; $i < $length+1; $i++){
                      $cost = $other_costs[$key][$i];
                      if($cost == "0.00" or $cost == 0){
                        $cost = '-';
                      } else {
                        $cost = "$".$cost;
                      }
                      // View mode
                      echo '<td class="data-view">'.$cost.'</td>';
                      // Edit mode
                      echo '<td class="data-edit hidden"><input id="other_costs_'.$key.'_'.$i.'" name="other_costs_'.$key.'_'.$i.'" type="text" placeholder="'.$cost.'" onfocus="highlightHeader(\'other_costs_'.$key.'_'.$i.'\',true);" onfocusout="highlightHeader(\'other_costs_'.$key.'_'.$i.'\',false);"></input></td>';
                    }
                    echo '</tr>';
                  }
                }
            echo '</tbody>';
          echo '</table>';
        } else {
          echo '<a href="./dashboard.php">Return to dashboard.</a>';
        }
        if(isset($message) && !empty($message)){
          echo '<script>submissionMessage("'.$message.'",'.$error_type.');</script>';
        }
        ?>
      </div>
    </div>
    <script src="" async defer></script>
    <hr id="foot-rule">
  </body>
  <?php
    include 'database/foot.php';
    footer(28,"October",2025,'Josh Gillum');
  ?>
</html>
