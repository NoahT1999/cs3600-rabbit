function domesticSelection(stateObject) {
  var stateSel = document.getElementById("domestic_state");
  var countySel = document.getElementById("domestic_county");
  var destinationSel = document.getElementById("domestic_destination");
  for (var x in stateObject) {
    stateSel.options[stateSel.options.length] = new Option(x, x);
  }
  stateSel.onchange = function() {
    //empty Chapters- and Topics- dropdowns
    destinationSel.length = 1;
    countySel.length = 1;
    //display correct values
    for (var y in stateObject[this.value]) {
      countySel.options[countySel.options.length] = new Option(y, y);
    }
  }
  countySel.onchange = function() {
    //empty Chapters dropdown
    destinationSel.length = 1;
    //display correct values
    var z = stateObject[stateSel.value][this.value];
    for (var i = 0; i < z.length; i++) {
      destinationSel.options[destinationSel.options.length] = new Option(z[i], z[i]);
    }
  }
}
