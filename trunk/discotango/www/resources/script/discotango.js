// Local namespace
var discotango = discotango || {};

// Location container
discotango.location = function(coord, description) {
    this.coord = coord;
    this.description = description;
    this.infowindow = null;
    this.marker = null;
}

// IdP container
discotango.IdP = function(entityid, name) {
    this.entityid = entityid;
    this.name = name;
    this.locations = new Array();

    // Add a location
    this.addLocation = function(coord, description) {
        this.locations.push(new discotango.location(coord, description));
    }
};

discotango.init = function() {
    // Fix for WAYF theme. Uncomment if WAYF theme is used
    //$("#textcontent").css("height", "400px");
    var myOptions = {
        zoom: 9,
        mapTypeId: google.maps.MapTypeId.TERRAIN,
        mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU},
        mapTypeControl: false,
        navigationControlOptions: {style: google.maps.NavigationControlStyle.SMALL}
    };
    discotango.map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
    discotango.map.setCenter(discotango.startlocation);

    // Try W3C Geolocation method (Preferred)
    if(navigator.geolocation) {
        discotango.browserSupportFlag = true;
        navigator.geolocation.getCurrentPosition(function(position) {
            initialLocation = new google.maps.LatLng(position.coords.latitude,position.coords.longitude);
            discotango.map.setCenter(discotango.startlocation);
        }, function() {
            discotango.handleNoGeolocation(discotango.browserSupportFlag);
        });
    } else {
        // Browser doesn´t support Geolocation
        discotango.browserSupportFlag = false;
        discotango.handleNoGeolocation(discotango.browserSupportFlag);
    }
    discotango.setMarker();
    google.maps.event.addListener(discotango.map, "bounds_changed", function() {
        setTimeout(discotango.updateVisible, 500);
    });
};

discotango.handleNoGeolocation = function(errorFlag) {
    if (errorFlag == true) {
        contentString = "<div><b>Error</b></div><br /><div>The Geolocation service failed.</div>";
    } else {
        contentString = "<div><b>Error</b></div><br /><div>Your browser doesn´t support geolocation.</div>";
    }
    discotango.map.setCenter(discotango.startlocation);

    discotango.infowindowVisible = new google.maps.InfoWindow
    discotango.infowindowVisible.setContent(contentString);
    discotango.infowindowVisible.setPosition(discotango.startlocation);
    discotango.infowindowVisible.open(discotango.map);
}

discotango.setMarker = function() {
    for (x in discotango.idps) {
        for (y in discotango.idps[x].locations) {
            discotango.idps[x].locations[y].marker = new google.maps.Marker({
                position: discotango.idps[x].locations[y].coord,
                map: discotango.map,
                title: discotango.idps[x].name,
                clickable: true,
                id: discotango.idps[x].entityid
            });

            discotango.idps[x].locations[y].infowindow = new google.maps.InfoWindow(
                {content: "<div><b>" + discotango.idps[x].name  + "</b></div><br /><div>" + discotango.idps[x].locations[y].description + "</div>"}
            );
            
            google.maps.event.addListener(discotango.idps[x].locations[y].marker, "click", function(e) {
                discotango.infowindowVisible.close();
                for (y in discotango.idps) {
                    if(y == this.id) {
                        for (x in discotango.idps[this.id].locations) {
                            if(discotango.idps[this.id].locations[x].coord == this.position) {
                                discotango.idps[this.id].locations[x].infowindow.open(discotango.map, discotango.idps[this.id].locations[x].marker);
                                discotango.infowindowVisible = discotango.idps[this.id].locations[x].infowindow;
                            }
                        }
                    }
                }
            });
          
            google.maps.event.addListener(discotango.idps[x].locations[y].marker, "dblclick", function() {
                $("#dropdownlist").val(this.id);    
                $("#disco_form").submit();
            });
        }
    }
}

discotango.updateVisible = function() {
    var tmp = null, 
        found = new Boolean();
        map_bounds = discotango.map.getBounds();
        
    $("#idp_list").empty();
        
    for (x in discotango.idps) {
        found = false;
        for (y in discotango.idps[x].locations) {
            if(map_bounds.contains(discotango.idps[x].locations[y].coord)) {
                found = true;
            }
        }
        if(found) {
            tmp = $("<p id=\"" + discotango.idps[x].entityid + "\">" + discotango.idps[x].name + "</p>").click(function(){
                $("#dropdownlist").val($(this).attr("id"));    
                $("#disco_form").submit();
            });
            $(tmp).css("font-weight", "bold");
            $(tmp).mouseover(function() {
                $(this).css("text-decoration", "underline");
            });
            $(tmp).mouseout(function() {
                $(this).css("text-decoration", "none");
            });
            $("#idp_list").append(tmp);
        }
    }
}


discotango.idps = new Array();
discotango.map = null;
discotango.browserSupportFlag =  new Boolean();
discotango.infowindowVisible = new google.maps.InfoWindow();
discotango.startlocation = new google.maps.LatLng(55.677857, 12.56452);
