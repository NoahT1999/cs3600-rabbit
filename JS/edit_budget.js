function toggle_edit_mode(tbody_ids){
  var body,cells,tbody_id;
  for(var j = 0; j < tbody_ids.length; ++j){
    tbody_id = tbody_ids[j]
    body = document.getElementById(tbody_id);
    console.log(body);
    cells = body.querySelectorAll(".data-edit, .data-view");
    for(i = 0; i < cells.length; ++i){
      console.log(cells[i]);
      cells[i].classList.toggle("hidden");
    }
  }
}
