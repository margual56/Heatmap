let BASEYEAR = 1999;
let finalYear = 2018;

let year = BASEYEAR;
let yearInterval = 0.082;
let yearIntervalOld = 0.1;
let maxPopulation = 400;

let yearsdata = [];
let yearscount = BASEYEAR;

let months = [ "January", "February", "March", "April", "May", "June",
     "July", "August", "September", "October", "November", "December" ];

function GetMap() {
  let pos = [40.098388671875, -4.043139934539795];
  var map = new Microsoft.Maps.Map('#myMap', {
    credentials: "AhDgOTA-iLKJ8ovYzesQjwB1wPIoyEYUjf6rj8wp6290h8BoHoTVSajFChVkFQV6",
    mapTypeId: Microsoft.Maps.MapTypeId.canvasDark,
    labelOverlay: Microsoft.Maps.LabelOverlay.hidden,
    center: new Microsoft.Maps.Location(pos[0],pos[1]),
    zoom: 7,
    options: {
      "allowHidingLabelsOfRoad": true,
      "enableClickableLogo": false,
      "liteMode": true
    }
  });

  loading(true)
  download();
  loading(false)

  Microsoft.Maps.loadModule(['Microsoft.Maps.GeoJson', 'Microsoft.Maps.HeatMap'], function () {
    let shapes = [new Microsoft.Maps.Location(0,0)];
    var heatmap = new Microsoft.Maps.HeatMapLayer(shapes,
      {
        weight: 0.001,
        intensity: 0.1,
        radius: 10,
        units: 'metres',
        aggregateLocationWeights: true
      });

    map.layers.insert(heatmap);
    console.log("heatmap done");

    setTimeout(function(){updatePrintout(heatmap, 80);}, 1000);

  });
}

function updatePrintout(heatmap, delay){
  setTimeout(function () {
    //let s1 = new Date().getTime();
    getLocations3(year).then(function(x) {
      let points = []

      for(let i = 0; i<x.length; i++){
        points = points.concat(x[i]);
      }

      heatmap.setLocations(points);
    });

    //console.log("The year " + year + " computed in: " + ((new Date().getTime()-s1)/1000).toString() + "s");

    updateOverlayYear(year);

    if(year+yearInterval<finalYear-yearInterval*2){
      year = year+yearInterval;
    }else {
      year = BASEYEAR;
    }

    updatePrintout(heatmap, delay);
  }, delay);
}

function getNumberPoints(element1, element2, population, yearf, mag){

  let pp = [];
  let coordinates = element1["geometry"]["coordinates"];

  let x = maxPopulation;

  if(mag != "total")
    x /= 2;

  for(let i=5; i>=0; i--) {
    if(population<x) {
      pp.push(new Microsoft.Maps.Location(parseFloat(coordinates[0]) + 0.0001*(Math.random()-0.5),parseFloat(coordinates[1]) + 0.0001*(Math.random()-0.5)));
      x = f(x);
    }else {
      break;
    }
  }

  return pp;
}

async function getLocations3(yearf) {
  let mag;
  switch (getGender()) {
    case 0:
      mag = "total";
    break;

    case 1:
      mag = "hombres";
    break;

    case 2:
      mag = "mujeres";
    break;

    default:
      mag = "total";
  }

  let y1 = Math.floor(yearf);
  let y2 = Math.ceil(yearf);

  let features1 = yearsdata[y1-BASEYEAR];
  let features2 = yearsdata[y2-BASEYEAR];

  if(typeof features1 === 'undefined' || typeof features2 === 'undefined'){
    return [];
  }
  let promises = [];
  let element1 = features1["Features"];
  let element2 = features2["Features"];
  let lerpValue = yearf%1;
  for(let j = 0; j<element1.length && j<element2.length; j++) {
    let population = lerp(element1[j]["properties"][mag], element2[j]["properties"][mag], lerpValue, mag);

    if(population<=maxPopulation){
      promises.push(getNumberPoints(element1[j], element2[j], population, yearf));
    }
  }

  return Promise.all(promises);
}

function getGender(){
  let both = document.getElementById("Both").checked;
  let male = document.getElementById("Male").checked;
  let female = document.getElementById("Female").checked;

  /*
    gender = 0 -> total
    gender = 1 -> men
    gender = 2 -> woman
  */

  return both * 0 + male * 1 + female * 2;
}
