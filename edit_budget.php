<?php

session_start();
// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user'])) {
  header("Location: database/login.php");
  exit();
}

// Budget ID can come from GET (normal load) or POST (form submits)
$budget_id = $_GET["budget_id"] ?? $_POST["budget_id"] ?? null;
$length = "";
$message = "";
$error = False;
$error_type = 0;

function subsection_header($t_w,$title,$subsection_id) {
  echo '<tr><th colspan="'.$t_w.'" class="table_subsection_header"><div class="split-items">'.$title.'<div><button type="button" onclick="toggle_edit_mode([\''.$subsection_id.'\']);">Toggle Edit Mode</button><button type="submit" name="submit_'.$subsection_id.'" id="submit_'.$subsection_id.'" class="submit-button data-edit hidden">Modify</button></div></div></th></tr>';
}

function subsection_data($data_array,$budget_length,$name) {
  if(isset($data_array) && !empty($data_array)){
    foreach(array_keys($data_array) as $key){
      echo '<tr>';
      // View mode
      echo '<td class="row_header">'.ucfirst(str_replace("_"," ",$key)).'</td>';
      for($i = 1; $i < $budget_length+1; $i++){
        $cost = isset($data_array[$key][$i]) ? $data_array[$key][$i] : 0;
        if($cost == "0.00" or $cost == 0){
          $cost = '-';
        } else {
          $cost = "$".$cost;
        }
        // View mode
        echo '<td class="data-view" id="'.$name.'_'.$key.'_'.$i.'_view">'.$cost.'</td>';
        // Edit mode
        echo '<td class="data-edit hidden"><input id="'.$name.'_'.$key.'_'.$i.'_edit" name="'.$name.'_'.$key.'_'.$i.'_edit" type="text" placeholder="'.$cost.'" onfocus="highlightHeader(\''.$name.'_'.$key.'_'.$i.'_edit\',true);" onfocusout="highlightHeader(\''.$name.'_'.$key.'_'.$i.'_edit\',false);"></input></td>';
      }
      echo '</tr>';
    }
  }
}

function table_section($table_width,$title,$id_name,$budget_length,$data_array,$links=null) {
  echo '<tbody id="'.$id_name.'">';
    echo '<form method="POST">';
      subsection_header($table_width,$title,$id_name);
      subsection_data($data_array,$budget_length,$id_name);
    echo '</form>';
    if(isset($links) && !empty($links)){
      foreach($links as $link){
        echo '<tr><th colspan="'.$table_width.'" class="table_subsection_header"><a href="'.$link[0].'">'.$link[1].'</a></th></tr>';
      }
    }
  echo '</tbody>';
}

function update_values($fields,$table,$budget_id,$budget_id_name="budget_id"){
  include './database/format_numbers.php';
  // $fields stores an array of fields in the database that can be updated.
  // Index 0 - Explicitly define variable holding data. Set to NULL to fetch data from $_POST
  //       1 - Name of column in the database
  //       2 - Name of value in form
  //       3 - Data type for updating database. 's' for string, 'i' for integer, etc.
  // $table is the database table to be updated
  $updated = ""; // SQL query string of which columns to update
  $format = ""; // Format specifiers for bind_param
  $values = []; // Variables holding values for bind_param.
  $invalid_number = 0;
  include './database/db_connection.php';
  foreach($fields as $field){
    // Checks if value was updated in POST
    foreach($field[0] as $key => $value){
      if($value == 0 || (isset($value) && !empty($value))){
        write_to_console($key."=".$value);
        $code = "";
        if(!($code=format_number($value))){ // Checks if the number is correctly formatted.
          write_to_console("Updating ".$field[1]);
          $stmt = $conn->prepare('UPDATE '.$table.' SET '.$field[1].'=? WHERE '.$budget_id_name.'=? AND year=?');
          write_to_console('UPDATE '.$table.' SET '.$field[1].'=? WHERE '.$budget_id_name.'=? AND year=?');
          write_to_console(array($value,$budget_id,$key));
          $stmt->bind_param($field[3]."ss",$value,$budget_id,$key);
          if(!$stmt->execute()){
            write_to_console($stmt->error);
          }
          $stmt->close();
        } else { // Displays a message stating what went wrong
          $message = "Invalid number: \'".$field[0]."\' for field: \'".$field[1]."\'. ".$code;
          $error_type = 1;
          $invalid_number = 1;
          write_to_console($message);
        }
      }
    }
  }
  header("Location: edit_budget.php?budget_id=".$budget_id);
  exit();
}

function write_to_console($data) {
  $console = $data;
  if (is_array($console))
  $console = implode(',', $console);

  echo "<script>console.log('Console: " . $console . "' );</script>";
}

$other_costs = [];
$equipment = [];
$travel = [];
$personnel = [];      // linked to THIS budget
$all_personnel = [];  // ALL staff + students in DB (for dropdown)
$personnel_effort = [];   // effort % per year for linked personnel

// If user submitted a link-personnel form, handle it first
// If user submitted a link-personnel form, handle it first
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["link_personnel"])) {
  include './database/check_access.php';

  if ($budget_id === null || !check_access($_SESSION['user'], $budget_id)) {
    $message = "Access denied for this budget.";
    $error_type = 1;
  } else {

    if (empty($_POST['personnel_key'])) {
      $message = "Please select a person to link.";
      $error_type = 1;
    } else {
      // personnel_key format: "staff|123456" or "student|124455"
      $parts = explode('|', $_POST['personnel_key'], 2);
      if (count($parts) != 2) {
        $message = "Invalid personnel selection.";
        $error_type = 1;
      } else {
        $type = strtolower($parts[0]);
        $personnel_id = strtolower($parts[1]);

        if ($type !== 'staff' && $type !== 'student') {
          $message = "Invalid personnel type.";
          $error_type = 1;
        } else {
          include './database/db_connection.php';

          // Check that the person actually exists
          if ($type === 'staff') {
            $check = $conn->prepare("SELECT id FROM staff WHERE id = ?");
          } else {
            $check = $conn->prepare("SELECT id FROM student WHERE id = ?");
          }
          $check->bind_param("s", $personnel_id);
          $check->execute();
          $check->store_result();

          if ($check->num_rows == 0) {
            $message = "No ".$type." with ID ".$personnel_id." exists.";
            $error_type = 1;
            $check->close();
            $conn->close();
          } else {
            $check->close();

            // Insert into budget_personnel (ignore duplicates)
            $stmt = $conn->prepare("
              INSERT IGNORE INTO budget_personnel (budget_id, personnel_type, personnel_id)
              VALUES (?, ?, ?)
            ");
            $stmt->bind_param("iss", $budget_id, $type, $personnel_id);

            if ($stmt->execute()) {
              $message = "Linked ".ucfirst($type)." with ID ".$personnel_id." to this budget.";
              $error_type = 0;
            } else {
              $message = "Error linking personnel: ".$stmt->error;
              $error_type = 1;
            }
            $stmt->close();
            $conn->close();
          }
        }
      }
    }
  }

  // After handling POST, redirect back to same page (GET) to avoid resubmits
  header("Location: edit_budget.php?budget_id=".$budget_id);
  exit();
}



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
    if(!$stmt){
      write_to_console("Failed prepare stmt");
    } else {
      write_to_console("Success");
    }
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
     
      // Fetches travel data
      if(!$error){
        $stmt = $conn->prepare("SELECT b.year, b.domestic, b.international FROM budget_travel as b WHERE b.budget_id=?");
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
                  if(!isset($travel[$key])){
                    $travel[$key] = [];
                  }
                  $travel[$key][$row['year']] = $row[$key];
                }
              }
            }
          } else {
            $message = "Failed to fetch travel.";
            $error = True;
          }
        } else {
          $message = "Failed to fetch travel.";
          $error = True;
        }
      }

    } else {
      $message = "Error fetching budget.";
      $error = True;
    }

    // Fetch personnel linked to this budget
    if (!$error) {
      // Staff on this budget
      $stmt = $conn->prepare("
        SELECT bp.personnel_id AS id,
               'staff' AS type,
               s.first_name,
               s.last_name,
               s.salary
        FROM budget_personnel bp
        JOIN staff s ON s.id = bp.personnel_id
        WHERE bp.budget_id = ? AND bp.personnel_type = 'staff'
        ORDER BY s.last_name, s.first_name
      ");
      $stmt->bind_param("s", $budget_id);
      if ($stmt->execute()) {
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        foreach ($data as $row) {
          $personnel[] = $row;
        }
      }
      $stmt->close();

      // Students on this budget
      $stmt = $conn->prepare("
        SELECT bp.personnel_id AS id,
               'student' AS type,
               st.first_name,
               st.last_name,
               st.level,
               st.tuition
        FROM budget_personnel bp
        JOIN student st ON st.id = bp.personnel_id
        WHERE bp.budget_id = ? AND bp.personnel_type = 'student'
        ORDER BY st.last_name, st.first_name
      ");
      $stmt->bind_param("s", $budget_id);
      if ($stmt->execute()) {
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        foreach ($data as $row) {
          $personnel[] = $row;
        }
      }
      $stmt->close();
    }
    // Fetch effort percentages for linked personnel
    if (!$error) {
      $stmt = $conn->prepare("
        SELECT personnel_type, personnel_id, year, effort_percent
        FROM budget_personnel_effort
        WHERE budget_id = ?
      ");
      $stmt->bind_param("s", $budget_id);
      if ($stmt->execute()) {
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        foreach ($data as $row) {
          $key = $row['personnel_type'].'|'.$row['personnel_id'];
          $personnel_effort[$key][$row['year']] = $row['effort_percent'];
        }
      }
      $stmt->close();
    }

    // Fetch ALL personnel for dropdown
    if (!$error) {
      // Staff
      $stmt = $conn->prepare("
        SELECT id, 'staff' AS type, first_name, last_name
        FROM staff
        ORDER BY last_name, first_name
      ");
      if ($stmt->execute()) {
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        foreach ($data as $row) {
          $all_personnel[] = $row;
        }
      }
      $stmt->close();

      // Students
      $stmt = $conn->prepare("
        SELECT id, 'student' AS type, first_name, last_name
        FROM student
        ORDER BY last_name, first_name
      ");
      if ($stmt->execute()) {
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        foreach ($data as $row) {
          $all_personnel[] = $row;
        }
      }
      $stmt->close();
    }

    $conn->close();
  }
}

function organize_data(&$fields,$budget_length){
  foreach($_POST as $key => $value){
    if($value == '0' || (isset($_POST[$key]) && !empty($_POST[$key]))){
      write_to_console($key." ".$value);
      $str = explode("_",$key);
      if(intval($str[2]) > 0 && intval($str[2]) <= $budget_length){
        for($i = 0; $i < sizeof($fields); $i++){
          if($str[1] == $fields[$i][2]){
            $fields[$i][0][intval($str[2])] = $value;
            write_to_console($fields[$i][0]);
            break;
          }
        }
      }
    }
  }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_travel'])){
  $fields = array(
    array(array(),'domestic','domestic','s'),
    array(array(),'international','international','s')
  );
  organize_data($fields,$length);
  update_values($fields,"budget_travel",$budget_id);
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
      <h1>Edit Budget</h1>
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
              // Header with toggle button
              echo '<tr><th colspan="'.$table_width.'" class="table_subsection_header">';
                echo '<div class="split-items">Personnel';
                  echo '<button onclick="toggle_edit_mode([\'personnel\']);">Toggle Edit Mode</button>';
                echo '</div>';
              echo '</th></tr>';

              if (!empty($personnel)) {
                foreach ($personnel as $p) {
                  $name = ucfirst($p['first_name']).' '.ucfirst($p['last_name']);
                  $key  = $p['type'].'|'.$p['id'];

                  // Determine base annual cost (salary or tuition) for display and cost calc
                  $base_cost = 0;
                  $extra = '';
                  if ($p['type'] === 'staff') {
                    $base_cost = isset($p['salary']) ? (float)$p['salary'] : 0;
                    if ($base_cost > 0) {
                      $extra = ' | Salary: $'.number_format($base_cost, 2);
                    }
                  } else { // student
                    $base_cost = isset($p['tuition']) ? (float)$p['tuition'] : 0;
                    if ($base_cost > 0) {
                      $extra = ' | Tuition: $'.number_format($base_cost, 2);
                    }
                  }

                  $label = $name.' ('.ucfirst($p['type']).' - '.$p['id'].')'.$extra;

                  echo '<tr>';
                    // Row header = person name + type + id + salary/tuition
                    echo '<td class="row_header">'.$label.'</td>';

                    // One cell per year; we use years 1..$length like other tables
                    for ($year = 1; $year <= $length; $year++) {
                      $effort = isset($personnel_effort[$key][$year])
                                ? (float)$personnel_effort[$key][$year]
                                : 0.0;

                      // Nicely formatted effort (e.g., "25%" or "-")
                      $effort_display = $effort > 0
                        ? rtrim(rtrim((string)$effort, '0'), '.').'%'
                        : '-';

                      // Compute annual cost for this budget from base_cost and effort
                      $cost = ($base_cost > 0 && $effort > 0)
                        ? $base_cost * ($effort / 100.0)
                        : 0.0;

                      $cost_display = $cost > 0
                        ? ' ($'.number_format($cost, 2).')'
                        : '';

                      // VIEW MODE CELL
                      echo '<td class="data-view" id="personnel_'.$p['type'].'_'.$p['id'].'_'.$year.'_view">';
                        echo $effort_display.$cost_display;
                      echo '</td>';

                      // EDIT MODE CELL (hidden until toggle_edit_mode is called)
                      echo '<td class="data-edit hidden">';
                        echo '<input id="personnel_'.$p['type'].'_'.$p['id'].'_'.$year.'_edit" ';
                        echo '       name="personnel_'.$p['type'].'_'.$p['id'].'_'.$year.'_effort" ';
                        echo '       type="number" min="0" max="100" step="0.1" ';
                        echo '       placeholder="'.($effort > 0 ? $effort : 0).'" ';
                        echo '       onfocus="highlightHeader(\'personnel_'.$p['type'].'_'.$p['id'].'_'.$year.'_edit\',true);" ';
                        echo '       onfocusout="highlightHeader(\'personnel_'.$p['type'].'_'.$p['id'].'_'.$year.'_edit\',false);" ';
                        echo '>';
                        echo ' %';
                      echo '</td>';
                    }
                  echo '</tr>';
                }
              } else {
                // No personnel yet linked to this budget
                echo '<tr>';
                  echo '<td class="row_header">No personnel linked to this budget.</td>';
                  for ($i = 0; $i < $length; $i++) {
                    echo '<td>-</td>';
                  }
                echo '</tr>';
              }

              // Link to separate personnel editing page (link/unlink)
              echo '<tr><th colspan="'.$table_width.'" class="table_subsection_header">';
                echo '<a href="edit_budget_personnel.php?budget_id='.$budget_id.'">Edit Personnel Links</a>';
              echo '</th></tr>';

            echo '</tbody>';
            table_section($table_width,"Large Equipment","equipment",$length,$equipment,array(array('edit_budget_equipment.php?budget_id='.$budget_id,"Edit Equipment")));
            table_section($table_width,"Travel","travel",$length,$travel);
            table_section($table_width,"Other Costs","other_costs",$length,$other_costs);
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
    footer(11,"December",2025,'Josh Gillum Noah Turner');
  ?>
</html>
