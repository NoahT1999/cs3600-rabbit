<?php
function footer($day,$month,$year,$author,$to_root="./"){
  echo '<footer>';
    echo '<div class="split-items">';
      echo '<p>Last updated: <span>'.trim($day).' '.trim($month).' '.trim($year).'</span></p>';
      echo '<p>Author: '.trim($author).'</p>';
    echo '</div>';
    echo '<div class="split-items">';
      echo '<a href="'.$to_root.'cookies.html">cookies</a>';
      echo '<a href="'.$to_root.'privacy.html">privacy policy</a>';
      echo '<a href="'.$to_root.'terms.html">terms and conditions</a>';
    echo '</div>';
  echo '</footer>';
} 
?>
