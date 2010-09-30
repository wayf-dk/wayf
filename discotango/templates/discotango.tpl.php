<?php
foreach ($this->data['idplist'] AS $idpentry) {
	if (isset($idpentry['name'])) {
		$this->includeInlineTranslation('idpname_' . $idpentry['entityid'], $idpentry['name']);
	} elseif (isset($idpentry['OrganizationDisplayName'])) {
		$this->includeInlineTranslation('idpname_' . $idpentry['entityid'], $idpentry['OrganizationDisplayName']);
	}
	if (isset($idpentry['description']))
		$this->includeInlineTranslation('idpdesc_' . $idpentry['entityid'], $idpentry['description']);
}

if(!array_key_exists('header', $this->data)) {
	$this->data['header'] = 'selectidp';
}
$this->data['header'] = $this->t($this->data['header']);
$this->data['jquery'] = array('version' => '1.6', 'core' => TRUE, 'ui' => TRUE, 'css' => TRUE);

$coords2 = "var tmp = null;\n";
foreach ($this->data['idplist'] AS $idpentry) {
    if(isset($idpentry['GeolocationHint'])) {
        $coords2 .= "tmp = new discotango.IdP(\"" . htmlspecialchars($idpentry['entityid']) . "\", \"" . htmlspecialchars($this->t('idpname_' . $idpentry['entityid'])) . "\");\n";
        foreach($idpentry['GeolocationHint'] AS $hint) {
            $coords2 .= "tmp.addLocation(new google.maps.LatLng(" . $hint['coord'] . "), \"" . $hint['description'] . "\");\n";
        }
        $coords2 .= "discotango.idps[\"" . htmlspecialchars($idpentry['entityid']) . "\"] = tmp;\n";
    }
}

$this->data['head'] = '    
        <meta name="viewport" content="initial-scale=1.0, user-scalable=no" /> 
        <link href="http://code.google.com/apis/maps/documentation/javascript/examples/default.css" rel="stylesheet" type="text/css" /> 
        <script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=true"></script> 
        <script type="text/javascript"> 

// Local namespace
var discotango = discotango || {};

discotango.IdP = function(entityid, name) {
    this.entityid = entityid;
    this.name = name;
    this.locations = new Array();
};

discotango.location = function(coord, description) {
    this.coord = coord;
    this.description = description;
    this.infowindow = null;
    this.marker = null;
}

discotango.IdP.prototype.addLocation = function(coord, description) {
    var location = new discotango.location(coord, description);
    this.locations.push(location);
}

discotango.init = function() {
    $("#textcontent").css("height", "400px");
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
    var tmp, 
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
' . $coords2 . '
    
discotango.map = null;
discotango.browserSupportFlag =  new Boolean();
discotango.infowindowVisible = new google.maps.InfoWindow();
discotango.startlocation = new google.maps.LatLng(55.677857, 12.56452);

</script> 
';

$this->data['onLoad'] = 'discotango.init();';

$this->includeAtTemplateBase('includes/header.php');
?>
        <form method="get" action="<?php echo $this->data['urlpattern']; ?>" id="disco_form">
        <input type="hidden" name="entityID" value="<?php echo htmlspecialchars($this->data['entityID']); ?>" />
        <input type="hidden" name="return" value="<?php echo htmlspecialchars($this->data['return']); ?>" />
        <input type="hidden" name="returnIDParam" value="<?php echo htmlspecialchars($this->data['returnIDParam']); ?>" />
		<select id="dropdownlist" name="idpentityid" style="display: none;">
		<?php
		foreach ($this->data['idplist'] AS $idpentry) {
			echo '<option value="'.htmlspecialchars($idpentry['entityid']).'"';
			if (isset($this->data['preferredidp']) && 
				$idpentry['entityid'] == $this->data['preferredidp']) 
				echo ' selected="selected"';
			    echo '>'.htmlspecialchars($this->t('idpname_' . $idpentry['entityid'])).'</option>';
		    }
		?>
		</select>
		<input type="submit" value="<?php echo $this->t('select'); ?>" style="display: none;"/>
		</form>

        <div>
            <div id="map_canvas" style="width: 350px; height: 400px; float: left;"></div>
            <div style="float: right; width: 350px;">
		        <p><?php echo $this->t('selectidp_full'); ?></p>
                <h2>Visible IdP's</h2>
                <div id="idp_list">
                </div>
            </div>
        </div>
		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
