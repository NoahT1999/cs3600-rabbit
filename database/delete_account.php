<?php
session_start();
// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user'])) {
  header("Location: ./login.php");
  exit();
}

$message = "";
$error = 1;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_account'])) {
  include 'db_connection.php';

  // Prepare and execute
  $stmt = $conn->prepare("DELETE from login WHERE id = ?");
  $stmt->bind_param("s", $_SESSION['user']);
  // Deletes user data from login database
  if ($stmt->execute()){
    $stmt->close();
    $conn->close();


      $message = "Account deletion successful.";
      $error = 0;
      $_SESSION = array();
      session_destroy();
      header("refresh:5;url=../index.php");
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
    <title>Delete Account</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../CSS/style.css">
    <script src="../JS/title.js"></script>
    <script src="../JS/message.js"></script>
  </head>
  <body>
    <!--[if lt IE 7]>
      <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="#">upgrade your browser</a> to improve your experience.</p>
    <![endif]-->
    <?php
      include 'nav.php';
      navigation(isset($_SESSION['user']),$to_root="../");
      include 'breadcrumb.php';
      breadcrumbs(array(array("home","../index.php"),array("delete-account","javascript:location.reload();")));
    ?>
    <div class="content">
      <?php
      echo '<h1>Delete Account</h1>';
      echo '<div id="submission-message-holder"><p></p></div>';
      if(isset($_SESSION['user'])){
        echo '<h2>You are currently logged in as '.$_SESSION["username"];
        echo '<form method="post" class="form-control">';
          echo '<div>';
            echo '<button type="submit" name="delete_account" class="submit-button">Delete Account</button>';
          echo '</div>';
        echo '</form>';
      }
      if(isset($message) && !empty($message)){
        echo '<script>submissionMessage("'.$message.'");</script>';
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
