<?php
include 'db_connection.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    //$password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare and execute
    $stmt = $conn->prepare("SELECT id,password FROM login WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id,$db_password);
        $stmt->fetch();

        if (password_verify($password,$db_password)) {
            $message = "Login successful";
            // Start the session and redirect to the dashboard or home page
            session_start();
            $_SESSION['user'] = $id;
            $_SESSION['username'] = $username;
            header("Location: ../dashboard.php");
            exit();
        } else {
            $message = "Incorrect password";
        }
    } else {
        $message = "Username not found";
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
    <title>Login</title>
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
    <div>
      <div class="navigation-head">
        <div class="site-logo">
          <a href="./index.php">
            <h1>RaBBiT</h1>
          </a>
        </div>
        <ul class="navigation-menu">
          <?php
          session_start();
          // Check if user is logged in, if not redirect to login page
          if (!isset($_SESSION['user'])) {
            echo '<a href="../personnel.php" class="menu-item">';
              echo '<li class="underline-hover-effect">Personnel</li>';
            echo '</a>';
            echo '<a href="./login.php" class="menu-item active">';
              echo '<li class="underline-hover-effect">Login</li>';
            echo '</a>';
            echo '<a href="./register.php" class="menu-item">';
              echo '<li class="underline-hover-effect">Register</li>';
            echo '</a>';
          } else {
            echo '<ul class="navigation-menu">';
              echo '<a href="../dashboard.php" class="menu-item">';
                echo '<li class="underline-hover-effect">Budgets</li>';
              echo '</a>';
              echo '<a href="../personnel.php" class="menu-item">';
                echo '<li class="underline-hover-effect">Personnel</li>';
              echo '</a>';
              echo '<div class="menu-item dropdown login">';
                echo '<li class="underline-hover-effect">Account</li>';
                echo '<div class="dropdown-content">';
                  echo '<ul>';
                    echo '<li><a href="./manage_account.php" class="menu-item">Manage</a></li>';
                    echo '<li><a href="./logout.php" class="menu-item">Logout</a></li>';
                    echo '<hr>';
                    echo '<li><a href="./delete_account.php" class="menu-item error">Delete&nbspAccount</a></li>';
                  echo '</ul>';
                echo '</div>';
              echo '</div>';
            echo '</ul>';
          }
          ?>
        </ul>
      </div>
      <hr id="head-rule">
    </div>
    <div class="breadcrumbs">
      <a href="../index.php">home</a>
      <p>></p>
      <p>login</p>
    </div>
    <div class="content">
      <h1>Login</h1>
      <div id="submission-message-holder"><p></p></div>
      <?php
      session_start();
      // Lets user know that they are already logged in

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
        echo '<script>submissionMessage("'.$message.'");</script>';
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
