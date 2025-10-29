function submissionMessage(text,error = 0) {
  const wrapper = document.getElementById("submission-message-holder");
  holder = wrapper.querySelector("p");
  console.log(error);
  holder.textContent = text;
  if (error == 0) {
    holder.classList.add("success");
    holder.classList.remove("error");
  } else if (error == 1){
    holder.classList.add("error");
    holder.classList.remove("success");
  } else {
    holder.classList.remove("error");
    holder.classList.remove("success");
  }
}
