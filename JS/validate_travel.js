function validateTravelForm(formId,budget_start,budget_length){
  console.log(budget_start);
  let form = document.forms[formId];
  let type = form["destination-type"];
  
  if(type.value == 'domestic'){
    let domestic_state = form["domestic_state"];
    let domestic_county = form["domestic_county"];
    let domestic_destination = form["domestic_destination"];
    if(domestic_state.value == 'default' || domestic_county.value == 'default' || domestic_destination.value == 'default'){
      submissionMessage("You must select a valid location",1);
      return false;
    }
  } else if(type.value == 'international'){
    
  } else {
    submissionMessage("Invalid destination type",1);
    return false;
  }
  let depart_date = form["departure_date"];
  let return_date = form["return_date"];
  console.log("Depart: ",depart_date.value, " Return: ",return_date.value);
  if(depart_date.value > return_date.value){
    submissionMessage("Departure date can not be later than return date.",1);
    return false;
  }
  if(budget_start && budget_length){
    var split = budget_start.split("-");
    split[0] = Number(split[0])+budget_length;
    budget_end = split.join("-")
    console.log("Start: ", budget_start," End: ",budget_end);
    if(depart_date.value < budget_start){
      submissionMessage("Departure date can not be before the start of the budget",1);
      return false;
    }
    if(return_date.value >= budget_end){
      submissionMessage("Return date can not be after the end of the budget",1);
      return false;
    }
  } else {
    submissionMessage("Internal error. No budget start or length.",1);
    return false;
  }
  let cost = form["transportation_cost"];
  let working_cost = cost.value.trim();
  if(working_cost.indexOf("$") > 0){
    submissionMessage("Invalid placement of $ symbol. It may only appear at the beginning of the number.",1);
  } else {
    if(working_cost[0] == "$"){
      working_cost = working_cost.slice(1);
    }
    let split = working_cost.split(".");
    if(split.length == 2){
      if(split[0].length > 8){
        submissionMessage("Whole number portion is too large.",1);
        return false;
      }
      if(isNaN(split[0])){
        submissionMessage("Invalid character in whole number portion of number.",1);
        return false;
      }
      if(split[1].length > 2){
        split[1] = split[1].slice(0,2);
      }
      if(isNaN(split[1])){
        submissionMessage("Invalid character in decimal portion of number.",1);
        return false;
      }
    } else if(split.length == 1) {
      if(split[0].length > 8){
        submissionMessage("Whole number portion is too large.",1);
        return false;
      }
      if(isNaN(split[0])){
        submissionMessage("Invalid character in whole number portion of number.",1);
        return false;
      }
    } else {
      submissionMessage("Invalid number of decimal places \'.\'.",1);
      return false;
    }
  }
  submissionMessage("Submitting to the database.",0);
  return true;
}
