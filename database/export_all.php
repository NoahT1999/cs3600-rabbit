<?php
session_start();

// 1. Must be logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit();
}

// 2. Get budget_id
$budget_id = $_GET['budget_id'] ?? null;
if (!$budget_id) {
    die("Missing budget_id");
}

// 3. Check access (correct folder)
require_once __DIR__ . '/check_access.php';
if (!check_access($_SESSION['user'], $budget_id)) {
    die("Access denied for this budget.");
}

// 4. Load database connection once (correct folder)
require __DIR__ . '/db_connection.php';

// DEBUG: verify connection is loaded
if (!isset($conn)) {
    die("db_connection.php did not create \$conn!");
}

// ---------- CONSTANTS (same as edit_budget.php) ----------
const FRINGE_RATE_FACULTY      = 0.31;   // 31.0%
const FRINGE_RATE_PROF_STAFF   = 0.413;  // 41.3%
const FRINGE_RATE_GRAS_UGRADS  = 0.025;  // 2.5%
const FRINGE_RATE_TEMP_HELP    = 0.083;  // 8.3%

const FRINGE_RATE_STAFF   = FRINGE_RATE_PROF_STAFF;
const FRINGE_RATE_STUDENT = FRINGE_RATE_GRAS_UGRADS;

const FA_RATE             = 0.50;       // 50%

// Helper: sum years from array[field][year] = value
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

// ---------- 1. FETCH BUDGET LENGTH ----------
$stmt = $conn->prepare("SELECT length FROM budget WHERE id = ?");
$stmt->bind_param("s", $budget_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) {
    die("Budget not found.");
}
$length = (int)$res['length'];

// ---------- 2. FETCH PERSONNEL LINKED TO BUDGET ----------
$personnel = [];
$personnel_effort = [];
$personnel_growth = [];

// Staff
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
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
foreach ($data as $row) {
    $personnel[] = $row;
}

// Students
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
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
foreach ($data as $row) {
    $personnel[] = $row;
}

// Effort %
$stmt = $conn->prepare("
    SELECT personnel_type, personnel_id, year, effort_percent
    FROM budget_personnel_effort
    WHERE budget_id = ?
");
$stmt->bind_param("s", $budget_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($data as $row) {
    $key = $row['personnel_type'].'|'.$row['personnel_id'];
    $personnel_effort[$key][(int)$row['year']] = (float)$row['effort_percent'];
}

// Growth %
$stmt = $conn->prepare("
    SELECT personnel_type, personnel_id, growth_rate_percent
    FROM budget_personnel_growth
    WHERE budget_id = ?
");
$stmt->bind_param("s", $budget_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($data as $row) {
    $key = $row['personnel_type'].'|'.$row['personnel_id'];
    $personnel_growth[$key] = (float)$row['growth_rate_percent'];
}

// ---------- 3. PERSONNEL COSTS PER YEAR ----------
$year_personnel_totals = array_fill(1, $length, 0.0);

foreach ($personnel as $p) {
    $key = $p['type'].'|'.$p['id'];

    // Base annual cost
    $base_cost = 0.0;
    if ($p['type'] === 'staff') {
        $base_cost = isset($p['salary']) ? (float)$p['salary'] : 0.0;
    } else {
        $base_cost = isset($p['tuition']) ? (float)$p['tuition'] : 0.0;
    }

    $growth_rate = $personnel_growth[$key] ?? 0.0;

    for ($year = 1; $year <= $length; $year++) {
        $effort = $personnel_effort[$key][$year] ?? 0.0;

        // Apply growth
        $grown_base = $base_cost;
        if ($base_cost > 0 && $growth_rate != 0.0) {
            $factor = 1 + ($growth_rate / 100.0);
            $grown_base = $base_cost * pow($factor, $year - 1);
        }

        $salary_cost = ($grown_base > 0 && $effort > 0)
            ? $grown_base * ($effort / 100.0)
            : 0.0;

        // Fringe
        $fringe = 0.0;
        if ($salary_cost > 0) {
            if ($p['type'] === 'staff') {
                $fringe = $salary_cost * FRINGE_RATE_STAFF;
            } else {
                $fringe = $salary_cost * FRINGE_RATE_STUDENT;
            }
        }

        $total_direct = $salary_cost + $fringe;
        $year_personnel_totals[$year] += $total_direct;
    }
}

// ---------- 4. EQUIPMENT > $5000 ----------
$equipment = []; // equipment[name][year] = cost
$stmt = $conn->prepare("
    SELECT b.year, b.cost, e.name
    FROM budget_equipment AS b
    JOIN equipment AS e ON b.equipment_id = e.id
    WHERE b.budget_id = ?
");
$stmt->bind_param("s", $budget_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($data as $row) {
    $name = $row['name'];
    if (!isset($equipment[$name])) {
        $equipment[$name] = [];
    }
    $equipment[$name][(int)$row['year']] = (float)$row['cost'];
}

$year_equipment_totals = sum_years_from_array($equipment, $length);

// ---------- 5. TRAVEL ----------
$travel = []; // travel[domestic|international][year] = cost
$stmt = $conn->prepare("
    SELECT year, domestic, international
    FROM budget_travel
    WHERE budget_id = ?
");
$stmt->bind_param("s", $budget_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($data as $row) {
    $y = (int)$row['year'];
    $travel['Domestic'][$y]      = (float)$row['domestic'];
    $travel['International'][$y] = (float)$row['international'];
}

$year_travel_totals = sum_years_from_array($travel, $length);

// ---------- 6. OTHER DIRECT COSTS ----------
$other_costs = []; // same shape as in edit_budget
$stmt = $conn->prepare("SELECT * FROM budget_other_costs WHERE id = ?");
$stmt->bind_param("s", $budget_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (!empty($data)) {
    $keys = array_keys($data[0]);  // id, year, materials_and_supplies, ...
    foreach ($data as $row) {
        foreach ($keys as $k) {
            if ($k === 'id' || $k === 'year') continue;
            if (!isset($other_costs[$k])) {
                $other_costs[$k] = [];
            }
            $other_costs[$k][(int)$row['year']] = (float)$row[$k];
        }
    }
}

$year_other_totals = sum_years_from_array($other_costs, $length);

// ---------- 7. TOTALS & INDIRECT ----------
$year_total_direct    = [];
$year_modified_direct = []; // here = total direct (no exclusions)
$year_overhead        = [];
$year_project_total   = [];

for ($year = 1; $year <= $length; $year++) {
    $person = $year_personnel_totals[$year] ?? 0.0;
    $equip  = $year_equipment_totals[$year] ?? 0.0;
    $trav   = $year_travel_totals[$year]    ?? 0.0;
    $other  = $year_other_totals[$year]     ?? 0.0;

    $total_direct = $person + $equip + $trav + $other;
    $year_total_direct[$year] = $total_direct;

    $modified_direct = $total_direct; // adjust here later if needed
    $year_modified_direct[$year] = $modified_direct;

    $overhead = $modified_direct * FA_RATE;
    $year_overhead[$year] = $overhead;

    $year_project_total[$year] = $modified_direct + $overhead;
}

// Helper for printing a single row in the Excel table
function print_excel_row($label, $values, $length, $include_hourly = false) {
    echo "<tr>";
    echo "<td>".htmlspecialchars($label)."</td>";
    if ($include_hourly) {
        echo "<td>-</td>"; // Hourly rate col (we don't have it in DB)
    }
    $total = 0.0;
    for ($y = 1; $y <= $length; $y++) {
        $v = $values[$y] ?? 0.0;
        $total += $v;
        echo "<td>".($v > 0 ? '$'.number_format($v, 2) : "-")."</td>";
    }
    echo "<td>".($total > 0 ? '$'.number_format($total, 2) : "-")."</td>";
    echo "</tr>";
}

// ---------- 8. SEND HEADERS SO BROWSER DOWNLOADS AS EXCEL ----------
$filename = "budget_{$budget_id}.xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header("Pragma: no-cache");
header("Expires: 0");

// ---------- 9. OUTPUT HTML TABLE THAT MATCHES THE TEMPLATE STYLE ----------
?>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
<table border="1" cellspacing="0" cellpadding="3">
    <!-- Top info like screenshot -->
    <tr><td colspan="<?php echo $length + 2; ?>"><strong>Title:</strong></td></tr>
    <tr><td colspan="<?php echo $length + 2; ?>"><strong>Funding source:</strong></td></tr>
    <tr><td colspan="<?php echo $length + 2; ?>"><strong>PI:</strong></td></tr>
    <tr><td colspan="<?php echo $length + 2; ?>"><strong>CoPIs:</strong></td></tr>
    <tr><td colspan="<?php echo $length + 2; ?>"><strong>Project Start and End Dates:</strong></td></tr>

    <!-- Header row similar to screenshot -->
    <tr>
        <th></th>
        <th>Hourly rate at start date</th>
        <?php for ($y = 1; $y <= $length; $y++): ?>
            <th>Y<?php echo $y; ?></th>
        <?php endfor; ?>
        <th>Total</th>
    </tr>

    <!-- PERSONNEL COMPENSATION SECTION -->
    <tr>
        <th colspan="<?php echo $length + 2; ?>" style="background:#d9d9d9;">Personnel Compensation</th>
    </tr>
    <?php
    // One summary line for all personnel (you could break out PI/Co-PI etc. if you store that)
    print_excel_row("All Personnel", $year_personnel_totals, $length, true);
    ?>
    <!-- EQUIPMENT > $5000 SECTION -->
    <tr>
        <th colspan="<?php echo $length + 2; ?>" style="background:#d9d9d9;">Equipment &gt;$5000.00</th>
    </tr>
    <?php
    // One row per equipment item by name
    if (!empty($equipment)) {
        foreach ($equipment as $name => $years) {
            // build per-year array 1..$length
            $row_vals = array_fill(1, $length, 0.0);
            foreach ($years as $yr => $val) {
                $row_vals[(int)$yr] = (float)$val;
            }
            print_excel_row($name, $row_vals, $length, true);
        }
    }
    // Total equipment row
    print_excel_row("Total Equipment", $year_equipment_totals, $length, true);
    ?>


    <!-- TRAVEL SECTION -->
    <tr>
        <th colspan="<?php echo $length + 2; ?>" style="background:#d9d9d9;">Travel</th>
    </tr>
    <?php
    // domestic vs international, plus combined
    $year_domestic = $travel['Domestic']      ?? array_fill(1, $length, 0.0);
    $year_intern   = $travel['International'] ?? array_fill(1, $length, 0.0);
    $year_travel_total = [];
    for ($y = 1; $y <= $length; $y++) {
        $year_travel_total[$y] = ($year_domestic[$y] ?? 0) + ($year_intern[$y] ?? 0);
    }
    print_excel_row("Domestic",      $year_domestic,       $length, true);
    print_excel_row("International", $year_intern,         $length, true);
    print_excel_row("Total Travel",  $year_travel_total,   $length, true);
    ?>

    <!-- OTHER DIRECT COSTS SECTION -->
    <tr>
        <th colspan="<?php echo $length + 2; ?>" style="background:#d9d9d9;">Other Direct Costs</th>
    </tr>
    <?php
    // One row per "other_costs" field (materials_and_supplies, software, etc.)
    if (!empty($other_costs)) {
        foreach ($other_costs as $field_name => $years) {
            // Prettify label: materials_and_supplies -> Materials And Supplies
            $label = ucwords(str_replace('_', ' ', $field_name));

            $row_vals = array_fill(1, $length, 0.0);
            foreach ($years as $yr => $val) {
                $row_vals[(int)$yr] = (float)$val;
            }

            print_excel_row($label, $row_vals, $length, true);
        }
    }

    // Final total row
    print_excel_row("Total Other Direct Costs", $year_other_totals, $length, true);
    ?>


    <!-- TOTALS + INDIRECT LIKE BOTTOM OF TEMPLATE -->
    <tr>
        <th colspan="<?php echo $length + 2; ?>" style="background:#d9d9d9;">Totals</th>
    </tr>
    <?php
    print_excel_row("Total Direct Cost",           $year_total_direct,    $length, true);
    print_excel_row("Modified Total Direct Costs", $year_modified_direct, $length, true);
    print_excel_row("Indirect Costs (50%)",        $year_overhead,        $length, true);
    print_excel_row("Total Project Cost",          $year_project_total,   $length, true);
    ?>
</table>
</body>
</html>
