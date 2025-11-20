<?php
session_start();
function write_to_console($data) {
 $console = $data;
 if (is_array($console))
 $console = implode(',', $console);

 echo "<script>console.log('Console: " . $console . "' );</script>";
}

$message = "";
$error_type = 1;
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["fetch_personnel_info"])){
  $person = "";
  include 'database/db_connection.php';
  $type = strtolower($_GET["personnel_type"]);
  $id = strtolower($_GET["personnel_id"]);

  try {
    $stmt = $conn->prepare("SELECT first_name, last_name FROM ".$type." WHERE id=?");
    $stmt->bind_param("s",$id);
    if($stmt->execute()){
      $result = $stmt->get_result();
      $data = $result->fetch_assoc();
      if($result->num_rows > 0){
        $person = $data;
      }
    } else {
      $message = "Error: ".$stmt->error;
      $error_type = 1;
    }
    $stmt->close();
  } catch (Exception $e) {
    $message = 'Invalid personnel type.';
    $error_type = 1;
  }
  $conn->close();
}

if(isset($_SESSION['user'])){
  if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_person"])){
    $id = strtolower($_POST['add_personnel_id']);
    $type = strtolower($_POST['add_personnel_type']);
    $first = strtolower($_POST["first_name"]);
    $last = strtolower($_POST["last_name"]);

    $salary = NULL;
    $level = NULL;
    $tuition = NULL;

    if($type === 'staff'){
      if (isset($_POST['salary']) && $_POST['salary'] !== '') {
        $salary = $_POST['salary'];
      }
    } elseif ($type === 'student') {

      if (isset($_POST['level']) && $_POST['level'] !== '') {
        $level = strtolower($_POST['level']);
      }
      if (isset($_POST['tuition']) && $_POST['tuition'] !== '') {
        $tuition = $_POST['tuition'];
      }
    }

    include 'database/db_connection.php';
    $check_unused = $conn->prepare("SELECT id FROM ".$type." where id=?");
    $check_unused->bind_param("s",$id);
    if($check_unused->execute()){
      $check_unused->store_result();
    if($check_unused->num_rows == 0){
        // CHANGED: handle INSERT differently based on type
        if ($type === 'staff') {
          // ADDED: Insert salary for staff/faculty
          $stmt = $conn->prepare("INSERT INTO staff (id, first_name, last_name, salary) VALUES (?, ?, ?, ?)");
          // Using 's' for everything is fine; MySQL will cast numeric strings.
          $stmt->bind_param("ssss",$id,$first,$last,$salary);
        } elseif ($type === 'student') {
          // ADDED: Insert level + tuition for students
          $stmt = $conn->prepare("INSERT INTO student (id, first_name, last_name, level, tuition) VALUES (?, ?, ?, ?, ?)");
          $stmt->bind_param("sssss",$id,$first,$last,$level,$tuition);
        } else {
          // Fallback to original behavior if some other type appears
          $stmt = $conn->prepare("INSERT INTO ".$type." (id,first_name,last_name) VALUES (?, ?, ?)");
          $stmt->bind_param("sss",$id,$first,$last);
        }
        if($stmt->execute()){
          $message = "Successfully updated database.";
          $error_type = 0;
        } else {
          $message = "Error: ".$stmt->error;
          $error_type = 1;
        }
        $stmt->close();
      } else {
        $message = "Duplicate id found. Perform search by id again.";
        $error_type = 1;
      }
    } else {
      $message = "Error: ".$stmt->error;
      $error_type = 1;
    }
    $check_unused->close();
    $conn->close();
  } 
  if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["modify_person"])){
    $id = strtolower($_POST['modify_personnel_id']);
    $type = strtolower($_POST['modify_personnel_type']);
    $first = strtolower($_POST["first_name"]);
    $last = strtolower($_POST["last_name"]);
    
    // ADDED: extra fields for modify
    $salary = isset($_POST['salary']) ? $_POST['salary'] : null;
    $level = isset($_POST['level']) ? strtolower($_POST['level']) : null;
    $tuition = isset($_POST['tuition']) ? $_POST['tuition'] : null;

    include 'database/db_connection.php';
    // Check if the id is already in the database. If not, do not attempt update.
    $check_unused = $conn->prepare("SELECT first_name,last_name FROM ".$type." where id=?");
    $check_unused->bind_param("s",$id);
    try{
      if($check_unused->execute()){
        $result = $check_unused->get_result();
        $data = $result->fetch_assoc();

        
        if(!empty($data)){
          // Dynammically decides which values to update.
          $fields = array( // Stores each field that can be updated. May need to be split between types
            array($first,'first_name','s'),
            array($last,'last_name','s')
          );
          if ($type === 'staff') {
            // ADDED: salary for staff
            $fields[] = array($salary,'salary','s');
          } elseif ($type === 'student') {
            // ADDED: level + tuition for students
            $fields[] = array($level,'level','s');
            $fields[] = array($tuition,'tuition','s');
          }
          $updated = ""; // SQL query string of which columns to update
          $format = ""; // Format specifiers for bind_param
          $values = []; // Variables holding values for bind_param.
          foreach($fields as $field){
            if(isset($field[0]) && !empty($field[0])){
              $updated = $updated.$field[1]."=?,";
              $format = $format.$field[2];
              $values[] = $field[0];
            }
          }
          if(isset($updated) && !empty($updated)){ // Ensures at least one value will be updated
            $updated = substr($updated,0,-1);
            $stmt = $conn->prepare('UPDATE '.$type.' SET '.$updated.' WHERE id=?');
            $values[] = $id; // Appends id to list of values so it can be unpacked.
            $stmt->bind_param($format."s",...$values);
            if($stmt->execute()){
              $message = "Successfully updated database.";
              $error_type = 0;
            } else {
              $message = "Error: ".$stmt->error;
              $error_type = 1;
            }
            $stmt->close();
          }
        } else {
          $message = "ID not found. Perform search by id again.";
          $error_type = 1;
        }
      } else {
        $message = "Error: ".$stmt->error;
        $error_type = 1;
      }
      $check_unused->close();
    } catch (Exception $e){
      $message = "Unknown error while updating database.";
      $error_type = 1;    
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
    <title>Personnel Management</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./CSS/style.css">
    <script src="./JS/title.js"></script>
    <script src="./JS/message.js"></script>
  </head>
  <body>
    <!--[if lt IE 7]>
      <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="#">upgrade your browser</a> to improve your experience.</p>
    <![endif]-->
    <div>
    <?php
      include 'database/nav.php';
      navigation(isset($_SESSION['user']));
      include 'database/breadcrumb.php';
      breadcrumbs(array(array("home","./index.php"),array("personnel-management","javascript:location.reload();")));
    ?>
    <div class="content">
      <h1>Personnel</h1>
      <div id="submission-message-holder"><p></p></div>
      <form method="get">
        <div>
          <label for="personnel_type">Type: </label>
          <select name="personnel_type">
            <option value="student">Student</option>
            <option value="staff">Staff/Faculty</option>
          </select>
        </div>
        <div>
          <label for="personnel_id">ID: </label>
          <input type="text" name="personnel_id" id="personnel_id" class="text-input-small" placeholder="v12345678" required>
        </div>
        <div>
          <button type="submit" name="fetch_personnel_info" class="submit-button">Submit</button>
        </div>
      </form>
      <?php
      if(isset($person)){
        if(!empty($person)){
          echo '<p>'.ucfirst($person['first_name']).' '.ucfirst($person['last_name']).'</p>';
          if(isset($_SESSION['user'])){
            echo '<form method="POST">';
              echo '<div>';
                echo '<label for="first_name">First Name: </label>';
                echo '<input type="text" name="first_name" id="first_name" class="text-input-small" placeholder="John" maxlength="45"/>';
              echo '</div>';
              echo '<div>';
                echo '<label for="last_name">Last Name: </label>';
                echo '<input type="text" name="last_name" id="last_name" class="text-input-small" placeholder="Doe" maxlength="45"/>';
              echo '</div>';
              // ADDED: extra fields for MODIFY, based on type
              if ($type === 'staff') {
                echo '<div>';
                  echo '<label for="salary">Salary: </label>';
                  echo '<input type="number" step="0.01" name="salary" id="salary" class="text-input-small" placeholder="50000.00"/>';
                echo '</div>';
              } elseif ($type === 'student') {
                echo '<div>';
                  echo '<label for="level">Level: </label>';
                  echo '<select name="level" id="level" class="text-input-small">';
                    echo '<option value="">-- Select --</option>';
                    echo '<option value="undergraduate">Undergraduate</option>';
                    echo '<option value="graduate">Graduate</option>';
                  echo '</select>';
                echo '</div>';
                echo '<div>';
                  echo '<label for="tuition">Tuition: </label>';
                  echo '<input type="number" step="0.01" name="tuition" id="tuition" class="text-input-small" placeholder="12000.00"/>';
                echo '</div>';
              }
              echo '<div>';
              echo '<input type="hidden" name="modify_personnel_id" id="modify_personnel_id" value="'.$id.'"/>';
              echo '<input type="hidden" name="modify_personnel_type" id="modify_personnel_type" value="'.$type.'"/>';
              echo '</div>';
              echo '<div>';
                echo '<button type="submit" name="modify_person" class="styled-button submit-button">Modify</button>';
              echo '</div>';
            echo '</form>';
          }
        } else {
          echo '<p>No results found.</p>';
          if(isset($_SESSION['user'])){
            echo '<form method="POST">';
              echo '<div>';
                echo '<label for="first_name">First Name: </label>';
                echo '<input type="text" name="first_name" id="first_name" class="text-input-small" required placeholder="John" maxlength="45"/>';
              echo '</div>';
              echo '<div>';
                echo '<label for="last_name">Last Name: </label>';
                echo '<input type="text" name="last_name" id="last_name" class="text-input-small" required placeholder="Doe" maxlength="45"/>';
              echo '</div>';
              // ADDED: extra fields for ADD, based on type
              if ($type === 'staff') {
                echo '<div>';
                  echo '<label for="salary">Salary: </label>';
                  echo '<input type="number" step="0.01" name="salary" id="salary" class="text-input-small" required placeholder="50000.00"/>';
                echo '</div>';
              } elseif ($type === 'student') {
                echo '<div>';
                  echo '<label for="level">Level: </label>';
                  echo '<select name="level" id="level" class="text-input-small" required>';
                    echo '<option value="">-- Select --</option>';
                    echo '<option value="undergraduate">Undergraduate</option>';
                    echo '<option value="graduate">Graduate</option>';
                  echo '</select>';
                echo '</div>';
                echo '<div>';
                  echo '<label for="tuition">Tuition: </label>';
                  echo '<input type="number" step="0.01" name="tuition" id="tuition" class="text-input-small" required placeholder="12000.00"/>';
                echo '</div>';
              }
              echo '<div>';
              echo '<input type="hidden" name="add_personnel_id" id="add_personnel_id" value="'.$id.'"/>';
              echo '<input type="hidden" name="add_personnel_type" id="add_personnel_type" value="'.$type.'"/>';
              echo '</div>';
              echo '<div>';
                echo '<button type="submit" name="add_person" class="styled-button submit-button">Add</button>';
              echo '</div>';
            echo '</form>';
          }
        }
      }
      ?>
      <?php
        if(isset($message) && !empty($message)){
          echo '<script>submissionMessage("'.$message.'",'.$error_type.');</script>';
        }
      ?>
    </div>
    <script src="" async defer></script>
    <hr id="foot-rule">
  </body>
  <?php
    include 'database/foot.php';
    footer(26,"October",2025,'Josh Gillum');
  ?>
</html>
