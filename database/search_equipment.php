<?php
function write_to_console($data) {
 $console = $data;
 if (is_array($console))
 $console = implode(',', $console);

 echo "<script>console.log('Console: " . $console . "' );</script>";
}
$message = "";
$error_type = 1;
$invalid = False;
$direct = array("./login.php?send_back_to=".$_SERVER['REQUEST_URI'].$_SERVER['QUERY_STRING'],"Login");
session_start();
if(!isset($_SESSION['user'])){
  $message = "You must be logged in to access this page.";
  $invalid = True;
}


if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['search_equipment']) && !$invalid){
  $name = $_POST['name'];

  include './db_connection.php';
  $stmt = $conn->prepare("SELECT id,name,description from equipment WHERE name LIKE ?");
  $stmt->bind_param("s",$name);
  $stmt->execute();
  $result = $stmt->get_result();
  $data = $result->fetch_all(MYSQLI_ASSOC);
  if(isset($data) && !empty($data)){
    if(sizeof($data) == 1){
      $message = "1 result";
    } else {
      $message = sizeof($data)." results";
    }
    $error_type = 2;
    $total_usage_stmt = $conn->prepare("SELECT COUNT(DISTINCT budget_id) as total FROM budget_equipment WHERE equipment_id = ?");
    $access_usage_stmt = $conn->prepare("SELECT COUNT(DISTINCT budget_equipment.budget_id) as access FROM budget_equipment JOIN budget_access  ON budget_equipment.budget_id = budget_access.budget_id WHERE budget_equipment.equipment_id = ? AND budget_access.user_id = ?");
    for($i = 0; $i < sizeof($data); $i++){
      $item = $data[$i];
      $total_usage_stmt->bind_param("s",$item['id']);
      $access_usage_stmt->bind_param("ss",$item['id'],$_SESSION['user']);
      if($total_usage_stmt->execute()){
        $total_result = $total_usage_stmt->get_result();
        $data[$i]['total_usage'] = $total_result->fetch_assoc()['total'];
      } else {
        $data[$i]['total_usage'] = 0;
      }
      $access_usage_stmt->execute();
      $access_result = $access_usage_stmt->get_result();
      $data[$i]['accessible_usage'] = $access_result->fetch_assoc()['access'];
    }
  } else {
    $message = "No results found.";
    $error_type = 2;
  }
  $stmt->close();
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
      breadcrumbs(array(array("home","../index.php"),array("budget","../edit_budget.php?budget_id=".$budget_id),array("search-equipment","javascript:location.reload();")));
    ?>
    <div class="content">
      <h1>Search for Large Equipment</h1>
      <div id="submission-message-holder"><p></p></div>
      <?php
      if(!$invalid){
        if(isset($data) && !empty($data)){
          foreach($data as $result){
            echo '<div class="split-items">';
            if(isset($result['description']) && !empty($result['description'])){
              echo '<p class="tooltip">(ID: '.$result['id'].') '.$result['name'].'<span class="tooltiptext">'.$result['description'].'</span></p>';
            } else {
              echo '<p>(ID: '.$result['id'].') '.$result['name'].'</p>';
            }
            echo '<p>Used in '.$result['total_usage'].' budgets, '.$result['accessible_usage'].' of which you have access to.</p>';
            echo '</div>';
          }
          echo '<hr>';
        }
        $content_fields = array (
          array("name","Name","Equipment name. Search is case insensitive, so 'FLUX' == 'flux'. Wildcard '%' represents 0 or more characters. Wildcard '_' represents a single character.","s","Flux capacitor."),
        );
        echo '<h3>Search</h3>';
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
          echo '<button type="submit" name="search_equipment" class="submit-button">Search</button>';
        echo '</div>';
        echo '</form>';
        
      } else {
        echo '<a href="'.$direct[0].'">'.$direct[1].'</a>';
      }
      if(isset($message) && !empty($message)){
        echo '<script>submissionMessage("'.$message.'",'.$error_type.');</script>';
      }
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
