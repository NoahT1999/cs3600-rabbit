<?php
session_start();

// Only allow logged in users to export
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo "You must be logged in to export the database.";
    exit;
}

include 'database/db_connection.php';

// Name the file with today's date/time
$filename = "rabbit_full_export_" . date('Ymd_His') . ".csv";

// Tell the browser this is a CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Open output stream
$output = fopen('php://output', 'w');

// Helper: export a table with a header line "TABLE: name"
function export_table($conn, $output, $table_name, $query) {
    // Section header
    fputcsv($output, ["TABLE: " . $table_name]);
    fputcsv($output, []); // blank line

    if ($result = $conn->query($query)) {
        // Column headers
        $fields = $result->fetch_fields();
        $headers = [];
        foreach ($fields as $field) {
            $headers[] = $field->name;
        }
        fputcsv($output, $headers);

        // Rows
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }

        $result->free();
    } else {
        // If query fails, write an error row instead of crashing
        fputcsv($output, ["ERROR running query on table ".$table_name.": ".$conn->error]);
    }

    // Extra blank line between tables
    fputcsv($output, []);
}

// ---- Export each table ----

// Personnel tables
export_table($conn, $output, 'staff',  "SELECT * FROM staff ORDER BY id");
export_table($conn, $output, 'student',"SELECT * FROM student ORDER BY id");

// Budget-related tables
export_table($conn, $output, 'budget',            "SELECT * FROM budget ORDER BY id");
export_table($conn, $output, 'budget_access',     "SELECT * FROM budget_access ORDER BY user_id, budget_id");
export_table($conn, $output, 'budget_equipment',  "SELECT * FROM budget_equipment ORDER BY budget_id, equipment_id, year");
export_table($conn, $output, 'budget_other_costs',"SELECT * FROM budget_other_costs ORDER BY id, year");

// Travel + equipment reference data
export_table($conn, $output, 'domestic_travel_per_diem', "SELECT * FROM domestic_travel_per_diem ORDER BY id");
export_table($conn, $output, 'equipment',                 "SELECT * FROM equipment ORDER BY id");

fclose($output);
$conn->close();
exit;
?>