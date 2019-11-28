function download() {
  var s1;
  for(var i=0; i<finalYear-BASEYEAR+1; i++) {
    s1 = new Date().getTime();

    getdata(BASEYEAR+i, function(response){
      yearsdata.push(JSON.parse(response) );
    });

    console.log("loaded " + (BASEYEAR+i).toString() + " in " + ((new Date().getTime()-s1)/1000).toString() + "s");
  }
}

function getdata(year, callback){
  var xobj = new XMLHttpRequest();
  xobj.overrideMimeType("application/json");
  xobj.open('GET', 'INEdata/Poblacion' + year + ".json", true); // Replace 'my_data' with the path to your file

  xobj.onreadystatechange = function () {
    if (xobj.readyState == 4 && xobj.status == "200") {
      // Required use of an anonymous callback as .open will NOT return a value but simply returns undefined in asynchronous mode
      callback(xobj.responseText);
    }
  };

  xobj.send(null);
}
