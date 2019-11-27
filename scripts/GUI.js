function loading(visible) {
  if (visible) {
      document.getElementById('loadingOverlay').style.display="block";
  } else {
      document.getElementById('loadingOverlay').style.display="none";
  }
}

function info(visible) {
  if (visible) {
      document.getElementById('infoOverlay').style.display="block";
  } else {
      document.getElementById('infoOverlay').style.display="none";
  }
}

async function updateOverlayYear(y){
  let date = months[Math.floor(12*(y%1)).toString()] + ", " + Math.floor(y).toString();
  let value = (100*(y-BASEYEAR)/(finalYear-BASEYEAR));


  document.getElementById("infoOverlay").innerHTML = date +
  "\n<progress id=\"progressBar\" max=\"100\" value=" + value.toString() + "></progress>";
}
