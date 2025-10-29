function validateTravelForm(formId,budget_start,budget_length){
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
    console.log("Start: ", budget_start," End: ",budget_start + budget_length);
    if(depart_date.value < budget_start){
      submissionMessage("Departure date can not be before the start of the budget",1);
      return false;
    }
    if(return_date.value > budget_start + budget_length){
      submissionMessage("Return date can not be after the end of the budget",1);
      return false;
    }
  }
  return false;
}
