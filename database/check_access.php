<?php
  function check_access($user_id, $budget_id, $to_root = "./") {
    // Always load the connection file from THIS folder
    require __DIR__ . '/db_connection.php';

    $stmt = $conn->prepare(
      "SELECT budget_id FROM budget_access WHERE user_id = ? AND budget_id = ?"
    );
    $stmt->bind_param("ss", $user_id, $budget_id);
    $stmt->execute();
    $stmt->store_result();

    $value = false;
    if ($stmt->num_rows > 0) {
      $value = true;
    }

    $stmt->close();
    $conn->close();
    return $value;
  }
?>
