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

// Budget ID can come from GET (normal load) or POST (form submits)
$budget_id = $_GET["budget_id"] ?? $_POST["budget_id"] ?? null;
$length = "";
$error = False;

$other_costs = [];
$equipment = [];
$personnel = [];      // linked to THIS budget
$all_personnel = [];  // ALL staff + students in DB (for dropdown)
$personnel_effort = [];   // effort % per year for linked personnel
$personnel_growth = []; // annual growth rate per person

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
      // Fetch growth rates for linked personnel (annual %)
      if (!$error) {
        $stmt = $conn->prepare("
          SELECT personnel_type, personnel_id, growth_rate_percent
          FROM budget_personnel_growth
          WHERE budget_id = ?
        ");
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

          // Open form so personnel dropdowns get submitted
          echo '<form method="post" action="edit_budget.php">';
            echo '<input type="hidden" name="budget_id" value="'.htmlspecialchars($budget_id).'">';

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
                    // type="button" so it doesn't submit the form
                    echo '<button type="button" onclick="toggle_edit_mode([\'personnel\']);">Toggle Edit Mode</button>';
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

                        // Compute annual cost for this budget from grown_base and effort
                        $cost = ($grown_base > 0 && $effort > 0)
                          ? $grown_base * ($effort / 100.0)
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

              echo '</tbody>';


              echo '<tbody id="equipment">';
                echo '<tr><th colspan="'.$table_width.'" class="table_subsection_header"><div class="split-items">Large Equipment<button type="button" onclick="toggle_edit_mode([\'equipment\']);">Toggle Edit Mode</button></div></th></tr>';
                  if(isset($equipment) && !empty($equipment)){
                    foreach(array_keys($equipment) as $key){
                      echo '<tr>';
                      // View mode
                      echo '<td class="row_header">'.ucfirst(str_replace("_"," ",$key)).'</td>';
                      for($i = 1; $i < $length+1; $i++){
                        $cost = isset($equipment[$key][$i]) ? $equipment[$key][$i] : 0;
                        if($cost == "0.00" or $cost == 0){
                          $cost = '-';
                        } else {
                          $cost = "$".$cost;
                        }
                        // View mode
                        echo '<td class="data-view" id="equipment_'.$key.'_'.$i.'_view">'.$cost.'</td>';
                        // Edit mode
                        echo '<td class="data-edit hidden"><input id="equipment_'.$key.'_'.$i.'_edit" name="equipment_'.$key.'_'.$i.'_edit" type="text" placeholder="'.$cost.'" onfocus="highlightHeader(\'equipment_'.$key.'_'.$i.'_edit\',true);" onfocusout="highlightHeader(\'equipment_'.$key.'_'.$i.'_edit\',false);"></input></td>';
                      }
                      echo '</tr>';
                    }
                  }
                echo '<tr><th colspan="'.$table_width.'" class="table_subsection_header"><a href="edit_budget_equipment.php?budget_id='.$budget_id.'">Edit Equipment</a></th></tr>';
              echo '</tbody>';
              echo '<tbody id="travel">';
                echo '<tr><th colspan="'.$table_width.'" class="table_subsection_header">Travel</th></tr>';
                echo '<tr>';
                  echo '<td></td>';
                  for($i = 0; $i < $length; $i++){
                    echo '<td>Value '.($i+1).'</td>';
                  }
                echo '</tr>';
                echo '<tr><th colspan="'.$table_width.'" class="table_subsection_header"><a href="edit_budget_travel.php?budget_id='.$budget_id.'">Edit Travel</a></th></tr>';
              echo '</tbody>';
              echo '<tbody id="other_costs">';
                echo '<tr><th colspan="'.$table_width.'" class="table_subsection_header"><div class="split-items">Other Costs<button type="button" onclick="toggle_edit_mode([\'other_costs\']);">Toggle Edit Mode</button></div></th></tr>';
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
                        echo '<td class="data-view" id="other_costs_'.$key.'_'.$i.'_view">'.$cost.'</td>';
                        // Edit mode
                        echo '<td class="data-edit hidden"><input id="other_costs_'.$key.'_'.$i.'_edit" name="other_costs_'.$key.'_'.$i.'_edit" type="text" placeholder="'.$cost.'" onfocus="highlightHeader(\'other_costs_'.$key.'_'.$i.'_edit\',true);" onfocusout="highlightHeader(\'other_costs_'.$key.'_'.$i.'_edit\',false);"></input></td>';
                      }
                      echo '</tr>';
                    }
                  }
              echo '</tbody>';
            echo '</table>';

            // Save button for personnel effort
            echo '<div style="margin-top:1rem;">';
              echo '<button type="submit" name="save_personnel_effort" class="styled-button submit-button">Save Personnel Effort</button>';
            echo '</div>';

          echo '</form>';
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
    footer(28,"October",2025,'Josh Gillum Noah Turner');
  ?>
</html>
