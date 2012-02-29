

//Database query callback function. Pushes new data and calls createGraph().
function receiveDatum(ps) {
    return function(newDatum) {
	assert(ps.length == newDatum.length);
	newDatum = transformDatum(newDatum);
	var p;
	for(var i = 0; i < ps.length; i++) {
	    p = ps[i];
	    if(p == 'total') {
		totalData.values = newDatum[i];
	    }
	    else if(p == 'others') {
		othersData.values = newDatum[i];
	    }
	    else {
		datum.push({key : p, values : newDatum[i], color : newColor(p)});
	    }
	}
	createGraph(); 
    };
}


function buildQueryProvider(p, o) {
    o.ps.push(p);
    o.spes.push(0);
    o.idpes.push(0);

    var idpss, spss;
    if(idpMode) {
	o.idpss.push([mainProvider]);
	o.spss.push([p]);
    }
    else {
	o.spss.push([mainProvider]);
	o.idpss.push([p]);
    }
}

function buildQueryTotal(o) {
    o.ps.push('total');
    o.idpes.push(0);
    o.spes.push(0);

    if(idpMode) {
	o.idpss.push([mainProvider]);
	o.spss.push([]);
    }
    else {
	o.spss.push([mainProvider]);
	o.idpss.push([]);
    }
}

function buildQueryOthers(o) {
    o.ps.push('others');
    var others = new Array();
    var b = othersShown < otherProviders.length / 2;
    if(b) {
	for(var i = 0; i < othersShown; i++) {
	    others.push(getOtherProvider(i));
	}
    }
    else {
	for(var i = othersShown; i < otherProviders.length; i++) {
	    others.push(getOtherProvider(i));
	}
    }
    
    var idpss, spss;
    if(idpMode) {
	o.idpss.push([mainProvider]);
	o.spss.push(others);
	o.idpes.push(0);
	o.spes.push(b ? 1 : 0);
    }
    else {
	o.spss.push([mainProvider]);
	o.idpss.push(others);
	o.idpes.push(b ? 1 : 0);
	o.spes.push(0);
    }
}

function sendQuery(o) {
    o.callback = receiveDatum(o.ps);
    request(o);
}

function newQueryObject() {
    var o =  
	{     gran     : getGran()
	      , start    : getStart()
	      , end      : getEnd()
	      , idpss    : new Array()
	      , idpes    : new Array()
	      , spss     : new Array()
	      , spes     : new Array()
	      , ps       : new Array()
	};
    return o;
}



function request(o) {
    var xmlhttp;
    try{
	console.log(o);
        if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
	    xmlhttp=new XMLHttpRequest();
  	}
	else{// code for IE6, IE5
	    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	xmlhttp.onreadystatechange = handleResponse(xmlhttp, o.callback);
	var tz = get_time_zone();
	var params
	    = "g="     + o.gran
	    + "&s="    + o.start
	    + "&e="    + o.end
	    + "&tz="   + encodeURIComponent(tz)
	    + "&spss=" + encodeURIComponent(JSON.stringify(o.spss))
	    + "&idpss="+ encodeURIComponent(JSON.stringify(o.idpss))
	    + "&idpes=" + encodeURIComponent(JSON.stringify(o.idpes))
	    + "&spes="  + encodeURIComponent(JSON.stringify(o.spes));


        var url = "db.php";
        xmlhttp.open("POST",url);
	
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xmlhttp.setRequestHeader("Content-length", params.length);
	xmlhttp.setRequestHeader("Connection", "close");

        xmlhttp.send(params);
    }
    catch(err) {
        alert(err);
    }
}

function handleResponse(xmlhttp, callback)  {
    return function() {
	try{
	    if (xmlhttp.readyState==4) {
		if(xmlhttp.status==200) {
		    var datum = JSON.parse(xmlhttp.responseText);
		    console.log(datum);
		    callback(datum);
		}
		else {
		    alert(xmlhttp.responseText);
		}
	    }
	}
	catch(err) {console.log(err);alert(err);}
    };
}
