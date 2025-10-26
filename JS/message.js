function submissionMessage(text,error = 0) {
  const wrapper = document.getElementById("submission-message-holder");
  holder = wrapper.querySelector("p");
  console.log(error);
  holder.textContent = text;
  if (error == 0) {
    holder.className += "success";
  } else if (error == 1){
    holder.className += "error";
  }
}
