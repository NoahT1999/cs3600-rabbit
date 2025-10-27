<?php
function breadcrumbs($crumbs){
  // Crumbs should be an array of two element arrays. Each subarray should be (name,path)
  echo '<div class="breadcrumbs">';
  for($i = 0; $i < sizeof($crumbs); $i++){
    $crumb = $crumbs[$i];
    echo '<a href="'.$crumb[1].'">'.$crumb[0].'</a>';
    if($i != sizeof($crumbs)-1){
      echo '<p>></p>';
    }
  }
  echo '</div>';
}

?>
