<?php
session_start();
include 'db_connection.php';

$message = "";
$error = 1;

if($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = $_POST['username'];
  $password = $_POST['password'];
  $password = password_hash($password, PASSWORD_DEFAULT);

  // Check if username already exists
  $checkEmailStmt = $conn->prepare("SELECT username FROM login where username = ?");
  $checkEmailStmt->bind_param("s",$username);
  $checkEmailStmt->execute();
  $checkEmailStmt->store_result();

  if ($checkEmailStmt->num_rows > 0){
    $message = "Username is not available";
  } else {
    $stmt = $conn->prepare("INSERT INTO login (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss",$username,$password);

    if($stmt->execute()){
      $stmt->close();
      $stmt = $conn->prepare("SELECT id FROM login where username = ?");
      $stmt->bind_param("s",$username);
      if($stmt->execute()){
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
          $stmt->bind_result($id);
          $stmt->fetch();
          $message = "Account created successfully. You will be redirected soon.";
          $error = 0;
          header("refresh:5;url=../dashboard.php");
          session_start();
          $_SESSION['user'] = $id;
          $_SESSION['username'] = $username;
        } else {
          $message = "Error: ".$stmt->error;
        }
      } else {
        $message = "Error: ".$stmt->error;
      }
    } else {
      $message = "Error: ".$stmt->error;
    }
    $stmt->close();
  }
  $checkEmailStmt->close();
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
    <title>Register</title>
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
      breadcrumbs(array(array("home","../index.php"),array("register","javascript:location.reload();")));
    ?>
    <div class="content">
      <h1>Register</h1>
      <div id="submission-message-holder"><p></p></div>
      <?php
      // Lets user know they are already logged in

      if (!isset($_SESSION['user'])) {
        echo '<form method="post" class="form-control">';
          echo '<div>';
            echo '<label for="username">User Name: </label>';
            echo '<input type="text" name="username" id="username" class="text-input-small" required>';
          echo '</div>';
          echo '<div>';
            echo '<label for="password">Password: </label>';
            echo '<input type="password" name="password" id="password" class="text-input-small" required>';
          echo '</div>';
          echo '<div>';
            echo '<button type="submit" name="register" class="submit-button">Submit</button>';
          echo '</div>';
        echo '</form>';
      } else {
        echo "<p>";
          echo "You are already logged in. Would you like to ";
          echo "<a href='./logout.php'>";
            echo "logout";
          echo "</a>";
          echo "?";
        echo "</p>";
        echo "<p>";
          echo "Go to ";
          echo "<a href='../todo.php'>";
            echo "Task dashboard";
          echo "</a>";
        echo "</p>";
      }
      if(isset($message) && !empty($message)){
        echo '<script>submissionMessage("'.$message.'",'.$error.');</script>';
      }
      ?>
    </div>
    <script src="" async defer></script>
    <hr id="foot-rule">
  </body>
  <footer>
    <div class="split-items">
      <p>Last updated: <span>23 October 2025</span></p>
      <p>Author: Josh Gillum</p>
    </div>
    <div class="split-items">
      <a href="./cookies.html">cookies</a>
      <a href="./privacy.html">privacy policy</a>
      <a href="./terms.html">terms and conditions</a>
    </div>
  </footer>
</html>
