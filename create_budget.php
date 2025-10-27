<?php
session_start();
// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user'])) {
  header("Location: ./database/login.php");
  exit();
}

function write_to_console($data) {
 $console = $data;
 if (is_array($console))
 $console = implode(',', $console);

 echo "<script>console.log('Console: " . $console . "' );</script>";
}


$message = "";
$error_type = 1;
$fail = False;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_budget'])){
  $name = $_POST['name'];
  write_to_console($name);
  include './database/db_connection.php';

  // Create budget in budget table
  $stmt = $conn->prepare("INSERT INTO budget (name) VALUES (?)");
  $stmt->bind_param("s",$name);
  if(!$stmt->execute()){
    $message = "Failed to add new budget.";
    $fail = True;
  } else {
    $budget_id = $stmt->insert_id;
    $stmt->close();
  }

  if(!$fail){
    // Give user access to budget in budget_access table
    $stmt = $conn->prepare("INSERT INTO budget_access (user_id,budget_id) VALUES (?,?)");
    $stmt->bind_param("ss",$_SESSION['user'],$budget_id);
    if(!$stmt->execute()){
      $message = "Failed to add budget access.";
      $fail = True;
    } else {
      $stmt->close();
    }
  }

  if(!$fail){
    // Add each year into budget_other_costs
    $stmt = $conn->prepare("INSERT INTO budget_other_costs (id,year) VALUES (?,?)");
    foreach(array(1,2,3,4,5) as $year){
      $stmt->bind_param("ss",$budget_id,$year);
      if(!$stmt->execute()){
        $message = "Failed to add year ".$year." to budget_other_costs.";
        $fail = True;
        break;
      }
    }
    if(!$fail){
      $stmt->close();
    }
  }

  $conn->close();
  if(!$fail){
    $message = "Successfully created new budget.";
    $error_type = 0;
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
    <title>Create New Budget</title>
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
    <?php
      include 'database/nav.php';
      navigation(isset($_SESSION['user']),$from_database=True);
      include 'database/breadcrumb.php';
      breadcrumbs(array(array("home","./index.php"),array("budgets","./dashboard.php"),array("create-budget","javascript:location.reload();")));
    ?>
    <div class="content">
      <h1>Create New Budget</h1>
      <div id="submission-message-holder"><p></p></div>
      <form method="post">
        <div class="split-items">
          <label for="name" class="tooltip">Name<span class="tooltiptext">Name of the budget.</span></label> 
          <input type="text" name="name" id="name" placeholder="Sample budget" maxlength="45" required class="text-input-small" onfocus="highlightLabel('name',true);" onfocusout="highlightLabel('name',false);"/>
        </div>
        <div>
          <button type="submit" name="create_budget" class="styled-button submit-button">Create</button>
        </div>
      </form>
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
      <a href="../cookies.html">cookies</a>
      <a href="../privacy.html">privacy policy</a>
      <a href="../terms.html">terms and conditions</a>
    </div>
  </footer>
</html>
