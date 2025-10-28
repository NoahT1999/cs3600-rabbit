<?php
function navigation($logged_in=True,$to_root="./"){
  echo '<div>';
    echo '<div class="navigation-head">';
      echo '<div class="site-logo">';
        echo '<a href="'.$to_root.'index.php">';
          echo '<h1>RaBBiT</h1>';
        echo '</a>';
      echo '</div>';
      echo '<ul class="navigation-menu">';
  if($logged_in){
        echo '<div class="menu-item dropdown login">';
          echo '<li class="underline-hover-effect">Budgets</li>';
          echo '<div class="dropdown-content">';
            echo '<ul>';
              echo '<li><a href="'.$to_root.'dashboard.php" class="menu-item">Dashboard</a></li>';
              echo '<li><a href="'.$to_root.'create_budget.php" class="menu-item">Create&nbspBudget</a></li>';
              echo '<hr>';
              echo '<li><a href="'.$to_root.'database/budget_settings.php" class="menu-item">Manage&nbspBudgets</a></li>';
            echo '</ul>';
          echo '</div>';
        echo '</div>';
        echo '<a href="'.$to_root.'personnel.php" class="menu-item">';
          echo '<li class="underline-hover-effect">Personnel</li>';
        echo '</a>';
        echo '<div class="menu-item dropdown login">';
          echo '<li class="underline-hover-effect">Account</li>';
          echo '<div class="dropdown-content">';
            echo '<ul>';
              echo '<li><a href="'.$to_root.'database/manage_account.php" class="menu-item">Manage</a></li>';
              echo '<li><a href="'.$to_root.'database/logout.php" class="menu-item">Logout</a></li>';
              echo '<hr>';
              echo '<li><a href="'.$to_root.'database/delete_account.php" class="menu-item error">Delete&nbspAccount</a></li>';
            echo '</ul>';
          echo '</div>';
        echo '</div>';
  } else {
        echo '<a href="'.$to_root.'personnel.php" class="menu-item">';
          echo '<li class="underline-hover-effect">Personnel</li>';
        echo '</a>';
        echo '<a href="'.$to_root.'database/login.php" class="menu-item">';
          echo '<li class="underline-hover-effect">Login</li>';
        echo '</a>';
        echo '<a href="'.$to_root.'database/register.php" class="menu-item">';
          echo '<li class="underline-hover-effect">Register</li>';
        echo '</a>';
  }
      echo '</ul>';
    echo '</div>';
    echo '<hr id="head-rule">';
  echo '</div>';
}
?>
