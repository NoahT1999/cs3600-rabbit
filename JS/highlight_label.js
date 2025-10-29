function highlightLabel(input_id,set_color=false){
  var input_element = document.getElementById(input_id);
  var previousSibling = input_element.previousElementSibling;
  while(previousSibling && !(previousSibling.getAttribute("for")==input_id)){
    previousSibling = previousSibling.previousElementSibling;
  }
  if(previousSibling){
    if(set_color){
      previousSibling.classList.add("highlighted-text");
    } else {
      previousSibling.classList.remove("highlighted-text");
    }
  } else {
    console.log("Label not found for: ",input_id);
  }
  
}

function highlightHeader(input_id,set_color=false){
  var input_element = document.getElementById(input_id).parentElement;
  var previousSibling = input_element.previousElementSibling;
  while(previousSibling && !(previousSibling.classList.contains("row_header"))){
    previousSibling = previousSibling.previousElementSibling;
  }
  if(previousSibling){
    if(set_color){
      previousSibling.classList.add("highlighted-text");
    } else {
      previousSibling.classList.remove("highlighted-text");
    }
  } else {
    console.log("Label not found for: ",input_id);
  }
  
}
