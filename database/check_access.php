<?php
  function check_access($user_id,$budget_id){
    include './database/db_connection.php';
    $stmt = $conn->prepare("SELECT budget_id FROM budget_access WHERE user_id = ? AND budget_id = ?");
    $stmt->bind_param("ss", $user_id,$budget_id);
    $stmt->execute();
    $stmt->store_result();
    $value = False;
    if($stmt->num_rows > 0){
      $value = True;
    }    
    $stmt->close();
    $conn->close();
    return $value;
  }
?>
