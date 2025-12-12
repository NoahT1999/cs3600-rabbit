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
        $key_fixed = str_replace("_","-",$key);
        $key_fixed = str_replace(" ","-",$key_fixed);
        $key_fixed = strtolower($key_fixed);
        echo '<td class="data-view" id="'.$name.'_'.$key_fixed.'_'.$i.'_view">'.$cost.'</td>';
        // Edit mode
        echo '<td class="data-edit hidden"><input id="'.$name.'_'.$key_fixed.'_'.$i.'_edit" name="'.$name.'_'.$key_fixed.'_'.$i.'_edit" type="text" placeholder="'.$cost.'" onfocus="highlightHeader(\''.$name.'_'.$key_fixed.'_'.$i.'_edit\',true);" onfocusout="highlightHeader(\''.$name.'_'.$key_fixed.'_'.$i.'_edit\',false);"></input></td>';
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

function update_values($fields,$table,$budget_id,$budget_id_name="budget_id",$larger_fields=false){
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
        $stmt = null;
        if(!($code=format_number($value))){ // Checks if the number is correctly formatted.
          write_to_console("Updating ".$field[1]);
          if(!$larger_fields){
            $stmt = $conn->prepare('UPDATE '.$table.' SET '.$field[1].'=? WHERE '.$budget_id_name.'=? AND year=?');
            write_to_console('UPDATE '.$table.' SET '.$field[1].'=? WHERE '.$budget_id_name.'=? AND year=?');
            write_to_console(array($value,$budget_id,$key));
            $stmt->bind_param($field[3]."ss",$value,$budget_id,$key);
          } else {
            $stmt = $conn->prepare('UPDATE '.$table.' SET '.$field[1].'=? WHERE '.$budget_id_name.'=? AND year=? AND '.$field[4].'=?');
            write_to_console('UPDATE '.$table.' SET '.$field[1].'=? WHERE '.$budget_id_name.'=? AND year=? AND '.$field[4].'=?');
            $stmt->bind_param($field[3]."sss",$value,$budget_id,$key,$field[5]);
            write_to_console($value.",".$budget_id.",".$key.",".$field[5]);
          }
          if(!$stmt->execute()){
            write_to_console($stmt->error);
          }
          $stmt->close();
        } else { // Displays a message stating what went wrong
          $message = "Invalid number: \'".$value."\' for field: \'".$key."\'. ".$code;
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
$personnel_growth = []; // annual growth rate per person

// === Fringe & F&A rates (from FY23-24 sheet) ===
const FRINGE_RATE_FACULTY      = 0.31;   // 31.0%
const FRINGE_RATE_PROF_STAFF   = 0.413;  // 41.3%
const FRINGE_RATE_GRAS_UGRADS  = 0.025;  // 2.5%
const FRINGE_RATE_TEMP_HELP    = 0.083;  // 8.3%

// In your schema we just have "staff" & "student":
const FRINGE_RATE_STAFF   = FRINGE_RATE_PROF_STAFF;
const FRINGE_RATE_STUDENT = FRINGE_RATE_GRAS_UGRADS;

// Indirect (F&A)
const FA_RATE             = 0.50; // 50.0%


// Handle saving personnel effort (percentages per year)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["save_personnel_effort"])) {
  include './database/check_access.php';

  // budget_id must come from the form
  $budget_id = $_POST["budget_id"] ?? null;

  if ($budget_id === null || !check_access($_SESSION['user'], $budget_id)) {
    $message = "Access denied for this budget.";
    $error_type = 1;
  } else {
    include './database/db_connection.php';

    foreach ($_POST as $key => $value) {
      // Match fields like: personnel_staff_123456_1_effort or personnel_student_v00000001_2_effort
      if (preg_match('/^personnel_(staff|student)_([^_]+)_([0-9]+)_effort$/', $key, $matches)) {
        $type = $matches[1];          // 'staff' or 'student'
        $personnel_id = $matches[2];  // e.g. '123456' or 'v00000001'
        $year = (int)$matches[3];     // 1, 2, 3, ...

        // Convert effort; empty → 0
        $effort = trim($value) === '' ? 0.0 : (float)$value;

        // Clamp to 0–100 for safety
        if ($effort < 0) $effort = 0;
        if ($effort > 100) $effort = 100;

        $stmt = $conn->prepare("
          INSERT INTO budget_personnel_effort (budget_id, personnel_type, personnel_id, year, effort_percent)
          VALUES (?, ?, ?, ?, ?)
          ON DUPLICATE KEY UPDATE effort_percent = VALUES(effort_percent)
        ");
        $stmt->bind_param("issid", $budget_id, $type, $personnel_id, $year, $effort);
        $stmt->execute();
        $stmt->close();
      }
    }

    $conn->close();

    $message = "Personnel effort saved.";
    $error_type = 0;
  }

  // Redirect to avoid resubmission on refresh
  header("Location: edit_budget.php?budget_id=".$budget_id);
  exit();
}

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
      if (!$error) {
        $stmt = $conn->prepare("
          SELECT b.year, b.cost, e.name
          FROM budget_equipment AS b
          JOIN equipment AS e ON b.equipment_id = e.id
          WHERE b.budget_id = ?
        ");
        if (!$stmt) {
          die("Prepare failed (equipment): " . $conn->error);
        }

        $stmt->bind_param("s", $budget_id);

        if ($stmt->execute()) {
          $result = $stmt->get_result();
          $data = $result->fetch_all(MYSQLI_ASSOC);
          $stmt->close();

          if (!empty($data)) {
            foreach ($data as $row) {
              $key = $row['name'];
              if (!isset($equipment[$key])) {
                $equipment[$key] = [];
              }
              $equipment[$key][$row['year']] = $row['cost'];
            }
          }
          // NOTE: no "else" here — empty data is fine, just means no equipment yet
        } else {
          $message = "Failed to fetch large equipment: " . $stmt->error;
          $error = true;
        }
      }
      // Fetches travel data
      if (!$error) {
        $stmt = $conn->prepare("
          SELECT year, domestic, international
          FROM budget_travel
          WHERE budget_id = ?
        ");
        if (!$stmt) {
          die("Prepare failed (travel): " . $conn->error);
        }

        $stmt->bind_param("s", $budget_id);

        if ($stmt->execute()) {
          $result = $stmt->get_result();
          $data = $result->fetch_all(MYSQLI_ASSOC);
          $stmt->close();

          if (!empty($data)) {
            $keys = array_keys($data[0]); // ['year', 'domestic', 'international']

            foreach ($data as $row) {
              foreach ($keys as $key) {
                if ($key !== 'year') {
                  if (!isset($travel[$key])) {
                    $travel[$key] = [];
                  }
                  $travel[$key][$row['year']] = $row[$key];
                }
              }
            }
          }
          // Again: no error if empty; just no travel rows for this budget
        } else {
          $message = "Failed to fetch travel: " . $stmt->error;
          $error = true;
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
        if (!$stmt) {
        die("Prepare failed (effort): " . $conn->error);
      }
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
      // Fetch growth rates for linked personnel (annual %)
      if (!$error) {
        $stmt = $conn->prepare("
          SELECT personnel_type, personnel_id, growth_rate_percent
          FROM budget_personnel_growth
          WHERE budget_id = ?
        ");
        if (!$stmt) {
          die("Prepare failed (growth): " . $conn->error);
        }
        $stmt->bind_param("s", $budget_id);
        if ($stmt->execute()) {
          $result = $stmt->get_result();
          $data = $result->fetch_all(MYSQLI_ASSOC);
          foreach ($data as $row) {
            $key = $row['personnel_type'].'|'.$row['personnel_id'];
            $personnel_growth[$key] = (float)$row['growth_rate_percent'];
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
function sum_years_from_array($data_array, $length) {
  $totals = array_fill(1, $length, 0.0);
  foreach ($data_array as $field => $years) {
    foreach ($years as $year => $value) {
      $y = (int)$year;
      if ($y >= 1 && $y <= $length) {
        $totals[$y] += (float)$value;
      }
    }
  }
  return $totals;
}

function print_totals_row($label, $values, $length) {
  echo '<tr>';
    echo '<td class="row_header">'.$label.'</td>';
    for ($year = 1; $year <= $length; $year++) {
      $v = isset($values[$year]) ? $values[$year] : 0;
      echo '<td>'.($v > 0 ? '$'.number_format($v, 2) : '-').'</td>';
    }
  echo '</tr>';
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_travel'])){
  $fields = array(
    array(array(),'domestic','domestic','s'),
    array(array(),'international','international','s')
  );
  organize_data($fields,$length);
  update_values($fields,"budget_travel",$budget_id);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_other-costs'])){
  $fields = array(
    array(array(),'materials_and_supplies','materials-and-supplies','s'),
    array(array(),'small_equipment','small-equipment','s'),
    array(array(),'publication','publication','s'),
    array(array(),'computer_services','computer-services','s'),
    array(array(),'software','software','s'),
    array(array(),'facility_fees','facility-fees','s'),
    array(array(),'conference_registration','conference-registration','s'),
    array(array(),'other','other','s'),
  );
  organize_data($fields,$length);
  update_values($fields,"budget_other_costs",$budget_id,"id");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_equipment'])){
  include './database/db_connection.php';
  $stmt = $conn->prepare("Select DISTINCT equipment_id,name from budget_equipment JOIN equipment on budget_equipment.equipment_id = equipment.id where budget_id = ?");
  $stmt->bind_param("s",$budget_id);
  $translations = [];
  if($stmt->execute()){
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    if(isset($data) && !empty($data)){
      foreach($data as $row){
        $cleaned_name = str_replace(" ","-",strtolower($row['name']));
        write_to_console($cleaned_name);
        $translations[] = array(array(),"cost",$cleaned_name,'s',"equipment_id",$row["equipment_id"]); // Last two values are additional specifiers
      }
    }
  }
  $conn->close();
  organize_data($translations,$length);
  update_values($translations,"budget_equipment",$budget_id,"budget_id",true);
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

        <?php if ($has_access && !$error): ?>
          <!-- Export CURRENT budget to Excel -->
          <form method="get" action="database/export_all.php" style="margin: 0.5rem 0 1rem 0;">
            <input type="hidden" name="budget_id" value="<?php echo htmlspecialchars($budget_id); ?>">
            <button type="submit" class="styled-button submit-button">
              Export This Budget to Excel
            </button>
          </form>
        <?php endif; ?>

        <div id="submission-message-holder"><p></p></div>
        <?php
        if($has_access && !$error){
          $table_width = $length + 1;
          // Yearly totals for summary rows at the bottom
          $year_personnel_totals = array_fill(1, $length, 0.0);
          $year_equipment_totals = array_fill(1, $length, 0.0);
          $year_travel_totals    = array_fill(1, $length, 0.0);
          $year_other_totals     = array_fill(1, $length, 0.0);

          // Open form so personnel dropdowns get submitted
            echo '<table class="budget_table">';
              echo '<thead>';
                echo '<tr>';
                  echo '<th></th>';
                  for ($i = 0; $i < $length; $i++) {
                    echo '<th>Year '.($i+1).'</th>';
                  }
                echo '</tr>';
              echo '</thead>';
                // PERSONNEL SECTION (its own form)
              echo '<tbody id="personnel">';
                echo '<form method="post" action="edit_budget.php">';
                  echo '<input type="hidden" name="budget_id" value="'.htmlspecialchars($budget_id).'">';
                // Header with toggle button
                echo '<tr><th colspan="'.$table_width.'" class="table_subsection_header">';
                  echo '<div class="split-items">Personnel';
                    // inner div to group the buttons on the right
                    echo '<div>';
                      // type="button" so it doesn't submit the form
                      echo '<button type="button" onclick="toggle_edit_mode([\'personnel\']);">Toggle Edit Mode</button>';

                      // submits and triggers save_personnel_effort
                      echo '<button type="submit" name="save_personnel_effort" class="submit-button data-edit hidden">';
                        echo 'Modify';
                      echo '</button>';
                    echo '</div>';
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

                        // Apply growth rate to base salary/tuition
                        $growth_rate = isset($personnel_growth[$key])
                          ? (float)$personnel_growth[$key]
                          : 0.0;

                        $grown_base = $base_cost;
                        if ($base_cost > 0 && $growth_rate != 0.0) {
                          $factor = 1 + ($growth_rate / 100.0);
                          // Year 1 => base, Year 2 => base * factor, Year 3 => base * factor^2, ...
                          $grown_base = $base_cost * pow($factor, $year - 1);
                        }

                        // Base salary/tuition charged to this budget for that year (direct salary)
                        $salary_cost = ($grown_base > 0 && $effort > 0)
                          ? $grown_base * ($effort / 100.0)
                          : 0.0;

                        // Fringe benefits: apply to salaried personnel using university rates
                        $fringe_cost = 0.0;
                        if ($salary_cost > 0) {
                          if ($p['type'] === 'staff') {
                            // Staff get fringe
                            $fringe_cost = $salary_cost * FRINGE_RATE_STAFF;
                          } else {
                            // Students: only apply if your syllabus says they also get fringe
                            $fringe_cost = $salary_cost * FRINGE_RATE_STUDENT;
                          }
                        }

                        // Total direct personnel cost for this person in this year (salary + fringe)
                        $total_personnel_direct = $salary_cost + $fringe_cost;
                        // Add into per-year personnel total
                        $year_personnel_totals[$year] += $total_personnel_direct;

                        // What we show in the cell
                        if ($total_personnel_direct > 0) {
                          // Simple version: show total only
                          $cost_display = ' ($'.number_format($total_personnel_direct, 2).')';

                          // If you want to see the breakdown in the UI, use this instead:
                          // $cost_display = ' ($'.number_format($salary_cost, 2).' salary + $'
                          //                  .number_format($fringe_cost, 2).' fringe)';
                        } else {
                          $cost_display = '';
                        }

                        // VIEW MODE CELL
                        echo '<td class="data-view" id="personnel_'.$p['type'].'_'.$p['id'].'_'.$year.'_view">';
                          echo $effort_display.$cost_display;
                        echo '</td>';

                        // EDIT MODE CELL (hidden until toggle_edit_mode is called)
                        echo '<td class="data-edit hidden">';

                          // cap by type: students 50% max FTE, staff up to 100% (change if needed)
                          $maxEffort = ($p['type'] === 'student') ? 50 : 100;

                          $element_id = 'personnel_'.$p['type'].'_'.$p['id'].'_'.$year.'_edit';
                          $element_name = 'personnel_'.$p['type'].'_'.$p['id'].'_'.$year.'_effort';

                          echo '<select id="'.$element_id.'" name="'.$element_name.'" ';
                          echo '        onfocus="highlightHeader(\''.$element_id.'\',true);" ';
                          echo '        onfocusout="highlightHeader(\''.$element_id.'\',false);">';

                          for ($percent = 0; $percent <= $maxEffort; $percent++) {
                            $selected = ((int)round($effort) === $percent) ? ' selected' : '';
                            echo '<option value="'.$percent.'"'.$selected.'>'.$percent.'%</option>';
                          }

                          echo '</select>';

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
            echo '</form>'; // end personnel form
            echo '</tbody>';
            table_section($table_width,"Large Equipment","equipment",$length,$equipment,array(array('edit_budget_equipment.php?budget_id='.$budget_id,"Edit Equipment")));
            table_section($table_width,"Travel","travel",$length,$travel);
            table_section($table_width,"Other Costs","other-costs",$length,$other_costs);
            // === Yearly totals for non-personnel sections ===
            $year_equipment_totals = sum_years_from_array($equipment, $length);
            $year_travel_totals    = sum_years_from_array($travel, $length);
            $year_other_totals     = sum_years_from_array($other_costs, $length);

            // === Combine & compute F&A ===
            $year_total_direct    = [];
            $year_modified_direct = [];
            $year_overhead        = [];
            $year_project_total   = [];

            for ($year = 1; $year <= $length; $year++) {
              $personnel = $year_personnel_totals[$year];
              $equip     = $year_equipment_totals[$year];
              $travel    = $year_travel_totals[$year];
              $other     = $year_other_totals[$year];

              $total_direct = $personnel + $equip + $travel + $other;
              $year_total_direct[$year] = $total_direct;

              // If later you need to "back out" things (e.g., >$5k equipment, tuition),
              // adjust this line. For now, Modified = Total.
              $modified_direct = $total_direct;
              $year_modified_direct[$year] = $modified_direct;

              $overhead = $modified_direct * FA_RATE;
              $year_overhead[$year] = $overhead;

              $year_project_total[$year] = $modified_direct + $overhead;
            }

            // === Totals section like bottom of the Excel template ===
            echo '<tbody id="totals">';
              echo '<tr><th colspan="'.$table_width.'" class="table_subsection_header">Totals</th></tr>';

              print_totals_row('Total Direct Cost',            $year_total_direct,    $length);
              print_totals_row('Modified Total Direct Costs',  $year_modified_direct, $length);
              print_totals_row('Indirect Costs ('.(FA_RATE*100).'%)', $year_overhead,  $length);
              print_totals_row('Total Project Cost',           $year_project_total,   $length);
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
    footer(11,"December",2025,'Josh Gillum Noah Turner');
  ?>
</html>