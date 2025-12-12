<?php
function write_to_console($data) {
  $console = $data;
  if (is_array($console)) $console = implode(',', $console);
  echo "<script>console.log('Console: " . $console . "' );</script>";
}

session_start();
// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user'])) {
  header("Location: database/login.php");
  exit();
}

// Budget and optional tab/year
$budget_id = $_GET["budget_id"] ?? $_POST["budget_id"] ?? null;
$tab = isset($_GET["year"]) ? (int)$_GET["year"] : 1;  // safe default

$message = "";
$error_type = 1;
$has_access = false;

include './database/db_connection.php';
include './database/check_access.php';

if ($budget_id === null) {
  $message = "Invalid budget id.";
} else {
  $has_access = check_access($_SESSION['user'], $budget_id);
  if (!$has_access) {
    $message = "Access denied for this budget.";
  }
}

// Handle link / unlink / growth actions
if ($_SERVER["REQUEST_METHOD"] === "POST" && $has_access) {

  // LINK
  if (isset($_POST["link_personnel_key"])) {
    $key = $_POST["link_personnel_key"];
    $parts = explode('|', $key, 2);

    if (count($parts) === 2) {
      $type = strtolower($parts[0]);
      $personnel_id = strtolower($parts[1]);

      if ($type === 'staff' || $type === 'student') {
        $stmt = $conn->prepare("
          INSERT IGNORE INTO budget_personnel (budget_id, personnel_type, personnel_id)
          VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iss", $budget_id, $type, $personnel_id);

        if ($stmt->execute()) {
          $message = "Linked ".ucfirst($type)." ".$personnel_id." to this budget.";
          $error_type = 0;
        } else {
          $message = "Error linking personnel: ".$stmt->error;
          $error_type = 1;
        }
        $stmt->close();
      }
    }

  // UNLINK
  } elseif (isset($_POST["unlink_personnel_type"], $_POST["unlink_personnel_id"])) {
    $type = strtolower($_POST["unlink_personnel_type"]);
    $personnel_id = strtolower($_POST["unlink_personnel_id"]);

    $stmt = $conn->prepare("
      DELETE FROM budget_personnel
      WHERE budget_id = ? AND personnel_type = ? AND personnel_id = ?
    ");
    $stmt->bind_param("iss", $budget_id, $type, $personnel_id);

    if ($stmt->execute()) {
      $message = "Unlinked ".ucfirst($type)." ".$personnel_id." from this budget.";
      $error_type = 0;

      // also clear any effort rows for this person on this budget
      $eff = $conn->prepare("
        DELETE FROM budget_personnel_effort
        WHERE budget_id = ? AND personnel_type = ? AND personnel_id = ?
      ");
      $eff->bind_param("iss", $budget_id, $type, $personnel_id);
      $eff->execute();
      $eff->close();

      // also clear any growth rows
      $g = $conn->prepare("
        DELETE FROM budget_personnel_growth
        WHERE budget_id = ? AND personnel_type = ? AND personnel_id = ?
      ");
      $g->bind_param("iss", $budget_id, $type, $personnel_id);
      $g->execute();
      $g->close();
    } else {
      $message = "Error unlinking personnel: ".$stmt->error;
      $error_type = 1;
    }
    $stmt->close();

  // SAVE GROWTH RATE
  } elseif (isset($_POST["save_growth_rate"])) {
    $type = strtolower($_POST["growth_personnel_type"] ?? '');
    $personnel_id = strtolower($_POST["growth_personnel_id"] ?? '');
    $rate = isset($_POST["growth_rate"]) ? floatval($_POST["growth_rate"]) : 0.0;

    if ($type !== 'staff' && $type !== 'student') {
      $message = "Invalid personnel type for growth rate.";
      $error_type = 1;
    } else {
      // clamp to sane limits
      if ($rate < 0) $rate = 0;
      if ($rate > 100) $rate = 100;

      $stmt = $conn->prepare("
        INSERT INTO budget_personnel_growth (budget_id, personnel_type, personnel_id, growth_rate_percent)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE growth_rate_percent = VALUES(growth_rate_percent)
      ");
      $stmt->bind_param("issd", $budget_id, $type, $personnel_id, $rate);

      if ($stmt->execute()) {
        $message = "Saved growth rate of ".$rate."% per year for ".ucfirst($type)." ".$personnel_id.".";
        $error_type = 0;
      } else {
        $message = "Error saving growth rate: ".$stmt->error;
        $error_type = 1;
      }
      $stmt->close();
    }
  }

  // Avoid form resubmit on refresh
  header("Location: edit_budget_personnel.php?budget_id=".$budget_id."&year=".$tab);
  exit();
}


// If user has access, load all personnel + linked map
$all_personnel = [];
$linked_map = [];
$growth_map = [];

if ($has_access && $budget_id !== null) {
  // Linked
  $stmt = $conn->prepare("
    SELECT personnel_type, personnel_id
    FROM budget_personnel
    WHERE budget_id = ?
  ");
  $stmt->bind_param("i", $budget_id);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $linked_map[$row['personnel_type'].'|'.$row['personnel_id']] = true;
  }
  $stmt->close();
  // Growth rates
  $stmt = $conn->prepare("
    SELECT personnel_type, personnel_id, growth_rate_percent
    FROM budget_personnel_growth
    WHERE budget_id = ?
  ");
  $stmt->bind_param("i", $budget_id);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $growth_map[$row['personnel_type'].'|'.$row['personnel_id']] = (float)$row['growth_rate_percent'];
  }
  $stmt->close();

  // All staff
  $stmt = $conn->prepare("
    SELECT id, 'staff' AS type, first_name, last_name, salary
    FROM staff
    ORDER BY last_name, first_name
  ");
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $all_personnel[] = $row;
  }
  $stmt->close();

  // All students
  $stmt = $conn->prepare("
    SELECT id, 'student' AS type, first_name, last_name, tuition
    FROM student
    ORDER BY last_name, first_name
  ");
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $all_personnel[] = $row;
  }
  $stmt->close();
}

// Do NOT close $conn here if other includes still need it.
// If this file is done with DB entirely, you *can*:
// $conn->close();
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
    <title>Edit Budget Personnel</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./CSS/style.css">
    <script src="./JS/title.js"></script>
  </head>
  <body>
    <!--[if lt IE 7]>
      <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="#">upgrade your browser</a> to improve your experience.</p>
    <![endif]-->
    <?php
      include 'database/nav.php';
      navigation(isset($_SESSION['user']));
      include 'database/breadcrumb.php';
      breadcrumbs(array(
        array("home","./index.php"),
        array("budgets","./dashboard.php"),
        array("edit-budget","./edit_budget.php?budget_id=".$budget_id."&year=".$tab),
        array("personnel","javascript:location.reload();")
      ));

    ?>
        <div class="content">
      <h1>Edit Budget Personnel</h1>
      <div id="submission-message-holder"><p></p></div>

      <?php
        if (isset($message) && !empty($message)) {
          echo '<script>submissionMessage("'.$message.'",'.$error_type.');</script>';
        }

        if (!$has_access || $budget_id === null) {
          echo '<p>Access denied or invalid budget.</p>';
          echo '<a href="dashboard.php">Back to dashboard</a>';
        } else {
          echo '<p>Budget ID: '.htmlspecialchars($budget_id).'</p>';

          if (empty($all_personnel)) {
            echo '<p>No staff or students in the system yet.</p>';
          } else {
            echo '<table class="data-table">';
              echo '<tr>';
                echo '<th>Name</th>';
                echo '<th>Type</th>';
                echo '<th>ID</th>';
                echo '<th>Base Cost</th>';
                echo '<th>Action</th>';
              echo '</tr>';

              foreach ($all_personnel as $p) {
                $name = ucfirst($p['first_name']).' '.ucfirst($p['last_name']);
                $type = $p['type'];
                $id   = $p['id'];
                $key  = $type.'|'.$id;
                $growth = isset($growth_map[$key]) ? $growth_map[$key] : 0.0;
                $growth_display = $growth > 0 ? rtrim(rtrim((string)$growth,'0'),'.').'% / yr' : '0% / yr';

                // Base cost = salary or tuition
                if ($type === 'staff') {
                  $base = isset($p['salary']) ? (float)$p['salary'] : 0;
                  $base_label = $base > 0 ? '$'.number_format($base, 2).' (Salary)' : '-';
                } else {
                  $base = isset($p['tuition']) ? (float)$p['tuition'] : 0;
                  $base_label = $base > 0 ? '$'.number_format($base, 2).' (Tuition)' : '-';
                }

                echo '<tr>';
                  echo '<td>'.$name.'</td>';
                  echo '<td>'.ucfirst($type).'</td>';
                  echo '<td>'.$id.'</td>';
                  echo '<td>'.$base_label.'</td>';
                echo '<td>';
                // Show current growth rate
                if (isset($linked_map[$key])) {
                  echo '<div>Growth: '.$growth_display.'</div>';
                }
                // UNLINK or LINK form
                if (isset($linked_map[$key])) {
                  // UNLINK form
                  echo '<form method="post" style="display:inline-block" onsubmit="return confirm(\'Unlink this person from this budget?\');">';
                    echo '<input type="hidden" name="budget_id" value="'.htmlspecialchars($budget_id).'">';
                    echo '<input type="hidden" name="unlink_personnel_type" value="'.htmlspecialchars($type).'">';
                    echo '<input type="hidden" name="unlink_personnel_id" value="'.htmlspecialchars($id).'">';
                    echo '<button type="submit" class="styled-button submit-button">Unlink</button>';
                  echo '</form>';

                  // GROWTH RATE form
                  echo '<form method="post" style="display:inline-block; margin-left:0.5rem;">';
                    echo '<input type="hidden" name="budget_id" value="'.htmlspecialchars($budget_id).'">';
                    echo '<input type="hidden" name="growth_personnel_type" value="'.htmlspecialchars($type).'">';
                    echo '<input type="hidden" name="growth_personnel_id" value="'.htmlspecialchars($id).'">';
                    echo '<input type="number" name="growth_rate" min="0" max="100" step="0.1" value="'.htmlspecialchars($growth).'" style="width:5rem;">';
                    echo '<button type="submit" name="save_growth_rate" class="styled-button submit-button">Save %</button>';
                  echo '</form>';

                } else {
                  // LINK form
                  echo '<form method="post" style="display:inline-block">';
                    echo '<input type="hidden" name="budget_id" value="'.htmlspecialchars($budget_id).'">';
                    echo '<button type="submit" name="link_personnel_key" value="'.htmlspecialchars($key).'" class="styled-button submit-button">';
                      echo 'Link to Budget';
                    echo '</button>';
                  echo '</form>';
                }
                echo '</td>';
                echo '</tr>';
              }

            echo '</table>';
          }

          echo '<p><a href="edit_budget.php?budget_id='.$budget_id.'&year='.$tab.'">Back to Edit Budget</a></p>';
        }
      ?>
    </div>
    <script src="" async defer></script>
    <hr id="foot-rule">
  </body>
  <?php
    include 'database/foot.php';
    footer(26,"October",2025,'Josh Gillum Noah Turner');
  ?>
</html>
