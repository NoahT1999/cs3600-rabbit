function domesticSelection(stateObject) {
  var stateSel = document.getElementById("domestic_state");
  var countySel = document.getElementById("domestic_county");
  var destinationSel = document.getElementById("domestic_destination");
  for (var x in stateObject) {
    stateSel.options[stateSel.options.length] = new Option(x, x);
  }
  countySel.classList.add("hidden");
  destinationSel.classList.add("hidden");
  stateSel.onchange = function() {
    //empty Chapters- and Topics- dropdowns
    destinationSel.length = 1;
    countySel.length = 1;
    //display correct values
    for (var y in stateObject[this.value]) {
      countySel.options[countySel.options.length] = new Option(y, y);
    }
    destinationSel.classList.add("hidden");
    if(stateSel.value == 'default'){
      countySel.classList.add("hidden");
    } else {
      countySel.classList.remove("hidden");
    }
  }
  countySel.onchange = function() {
    //empty Chapters dropdown
    destinationSel.length = 1;
    //display correct values
    if(countySel.value == 'default'){
      destinationSel.classList.add("hidden");
      destinationSel.length = 1;
    } else {
      var z = stateObject[stateSel.value][this.value];
      for (var i = 0; i < z.length; i++) {
        destinationSel.options[destinationSel.options.length] = new Option(z[i], z[i]);
      }
      destinationSel.classList.remove("hidden");
    }
  }
}

function internationalSelection(stateObject){
  console.log("In international selection function");
  console.log(stateObject);
}

function travelTypeSelection(typeSelectionId,domesticId,internationalId,domesticObject,internationalObject){
  domesticSelection(domesticObject);
  internationalSelection(internationalObject);
  var typeSel = document.getElementById(typeSelectionId);
  var domesticSel = document.getElementById(domesticId);
  var internationalSel = document.getElementById(internationalId);
  domesticSel.classList.add("hidden");
  internationalSel.classList.add("hidden");
  typeSel.onchange = function() {
    var option = typeSel.value;
    domesticSel.classList.add("hidden");
    internationalSel.classList.add("hidden");
    if(option == 'domestic'){
      domesticSel.classList.remove("hidden");
    } else if (option == 'international'){
      internationalSel.classList.remove("hidden");
    }
  }
}


function validateForm(){
  let form = document.forms["travel"];
  console.log(form);

  if(form['destination-type'].value != 'default'){
    console.log("Selected destination.");
  }
}
