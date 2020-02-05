if (document.querySelector('#smogBuster') != null || document.querySelector('#smogBuster') != undefined) {
  // Collecting data
  let objectsHolder = {};
  let myPoints = [];
  let markerColor = null;
  let smogBuster = new XMLHttpRequest();

  smogBuster.open("GET", "/wp-json/smogbuster/stations", true);

  smogBuster.addEventListener('load', function () {
    if (this.status === 200) {
      const smogBusterJson = JSON.parse(this.responseText);

      for (var i = 0; i < smogBusterJson.length; i++) {
        objectsHolder[smogBusterJson[i].station_id] = {
          city: smogBusterJson[i].city,
          address: smogBusterJson[i].address,
          latitude: smogBusterJson[i].latitude,
          longitude: smogBusterJson[i].longitude,
          qualityID: smogBusterJson[i].st,
          no2QualityID: smogBusterJson[i].no2,
          o3QualityID: smogBusterJson[i].o3,
          c6h6QualityID: smogBusterJson[i].c6h6,
          pm10QualityID: smogBusterJson[i].pm10,
          pm25QualityID: smogBusterJson[i].pm25,
          coQualityID: smogBusterJson[i].co,
          so2QualityID: smogBusterJson[i].so2
        };
      }
      setAllPoints();

      makeCheckboxes();
    } else {
      console.log('Połączenie ze SmogBuster zakończyło się statusem ' + this.status);
    }
  })

  smogBuster.addEventListener('error', function (e) {
    console.log(e);
    console.log('Wystąpił błąd połączenia ze SmogBuster');
  });

  smogBuster.send();


  // Map initialization
  L.mapbox.accessToken = 'pk.eyJ1IjoiZG9zcyIsImEiOiI1NFItUWs4In0.-9qpbOfE3jC68WDUQA1Akg';
  // let map = L.mapbox.map('map', 'mapbox.streets').setView([52.230147, 23.164152], 6);
  let map;
  if (window.innerWidth > 700) {
    map = L.mapbox.map('map', 'mapbox.streets').setView([52.230147, 23.164152], 6);
  } else {
    map = L.mapbox.map('map', 'mapbox.streets').setView([52.230147, 19.164152], 6);
  }

  if (map.scrollWheelZoom) {
    map.scrollWheelZoom.disable();
  }

  // Set marker color depending on it's qualityID
  function setColor(qualityID, show) {
    qualityID = '' + qualityID;
    switch (qualityID) {
      case '-1':
        markerColor = '#bfbfbf';
        break;
      case '0':
        markerColor = '#57b108';
        break;
      case '1':
        markerColor = '#b0dd10';
        break;
      case '2':
        markerColor = '#ffd911';
        break;
      case '3':
        markerColor = '#e58100';
        break;
      case '4':
        markerColor = '#e50000';
        break;
      case '5':
        markerColor = '#990000';
        break;
      default:
        markerColor = null;
        break;
    }

    if (show == false && qualityID == '-1') {
      markerColor = null;
    }
  }

  function setPoints(category) {
    for (point in objectsHolder) {
      if (objectsHolder[point].latitude != undefined && objectsHolder[point].longitude != undefined) {
        // Set markers colors
        switch (category) {
          case "default":
            setColor(objectsHolder[point].qualityID, true);
            break;
          case "so2":
            setColor(objectsHolder[point].so2QualityID, false);
            break;
          case "no2":
            setColor(objectsHolder[point].no2QualityID, false);
            break;
          case "co":
            setColor(objectsHolder[point].coQualityID, false);
            break;
          case "pm10":
            setColor(objectsHolder[point].pm10QualityID, false);
            break;
          case "pm25":
            setColor(objectsHolder[point].pm25QualityID, false);
            break;
          case "o3":
            setColor(objectsHolder[point].o3QualityID, false);
            break;
          case "benzen":
            setColor(objectsHolder[point].c6h6QualityID, false);
            break;
        }

        // Check address (if is too small, don't show it)
        let address = objectsHolder[point].address;
        if (address != null || address != undefined) {
          if (address.length <= 3) {
            address = null;
          }
        }

        // Set new point
        if (markerColor != null) {
          myPoint = {
            "type": "Feature",
            "properties": {
              "title": objectsHolder[point].city,
              "description": address,
              "marker-color": markerColor,
              "marker-size": "medium",
              "marker-symbol": "",
              "CAT_CODE": category
            },
            "geometry": {
              "type": "Point",
              "coordinates": [
                objectsHolder[point].longitude,
                objectsHolder[point].latitude
              ]
            }
          };

          // Add new point
          myPoints.push(myPoint);
        }
      }
    }
  }

  // Set points
  function setAllPoints() {
    setPoints("default");
    setPoints("so2");
    setPoints("no2");
    setPoints("co");
    setPoints("pm10");
    setPoints("pm25");
    setPoints("o3");
    setPoints("benzen");
  }

  // Show all points on map
  var featureLayerGeoJSON = {
    "type": "FeatureCollection",
    "features": myPoints
  };
  map.featureLayer.setGeoJSON(featureLayerGeoJSON);


  // Find and store a variable reference to the list of filters.
  var filters = document.getElementById('filters');

  var makeCheckboxes = function () {
    var typesObj = {},
      types = [];

    map.featureLayer.eachLayer(function (entity) {
      typesObj[entity.feature.properties['CAT_CODE']] = true;
    })
    for (var k in typesObj) types.push(k);

    // Filter action
    const checkboxes = document.querySelectorAll("#filters .filters__item input");
    for (var i = 0; i < checkboxes.length; i++) {
      checkboxes[i].addEventListener('click', function (el) {
        let checkedCheckbox = document.querySelector(".radio-checked");
        checkedCheckbox.classList.remove('radio-checked');

        this.classList.add('radio-checked');
        toogleLegend(this.id);

        update();
      });
    }

    function toogleLegend(legendID) {
      let legendItems = document.querySelectorAll(".filter-legend ul");
      for (item of legendItems) {
        if (!item.classList.contains('filter-legend--hidden'))
          item.classList.add('filter-legend--hidden');
        if (item.classList.contains('filter-legend--' + legendID))
          item.classList.remove('filter-legend--hidden');
      }
    }

    function update() {
      var enabled = {};
      for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].classList.contains('radio-checked')) enabled[checkboxes[i].id] = true;
      }
      map.featureLayer.setFilter(function (feature) {
        return (feature.properties['CAT_CODE'] in enabled);
      });
    }
    update();
  }
  // makeCheckboxes();

  const showHideFilter = document.querySelector('.filter-ui');
  const showHideBtn = showHideFilter.querySelector('.filter-info');
  const showHideText = showHideBtn.querySelectorAll('.filter-info--show');
  showHideBtn.addEventListener('click', function () {
    for (item of showHideText) {
      item.classList.toggle('hidden');
    }
    showHideFilter.classList.toggle('collapsed');
  });

  if (window.innerWidth < 700) {
    for (item of showHideText) {
      item.classList.toggle('hidden');
    }
    showHideFilter.classList.toggle('collapsed');
  }
}
