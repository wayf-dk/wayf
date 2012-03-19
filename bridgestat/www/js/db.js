
//
// receiveDatum
//
// Database query callback function. Pushes new data and calls repaintGraph().
//
// INPUT
//   ps - A list of strings representing the expected data to receive.
//        Allowed values are entity id's as well as 'total' and 'others.
//
// OUTPUT
//   A function that can be used as a callback for the request(..) function.
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
	repaintGraph(); 
    };
}

//
// buildQueryProvider
//
// Takes a query object and appends a query for specific other entity.
//
// INPUT
//   p - A entity id (string)
//   o - A query object (see newQueryObject(..) function)
//
// OUTPUT
//   Nothing, but input argument o will be modified.
//
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

//
// buildQueryTotal
//
// Takes a query object and appends a query for a total grouping.
//
// INPUT
//   o - A query object (see newQueryObject(..) function)
//
// OUTPUT
//   Nothing, but input argument o will be modified.
//
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

//
// buildQueryOthers
//
// Takes a query object and appends a query for the others grouping.
//
// INPUT
//   o - A query object (see newQueryObject(..) function)
//
// OUTPUT
//   Nothing, but input argument o will be modified.
//

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


//
// sendQuery
//
// Takes a query object and constructs a callback function and send it
// using the request(..) function.
//
// INPUT
//   o - A query object (see newQueryObject(..) function)
//
// OUTPUT
//   Nothing, but a query will be sent.
//
function sendQuery(o) {
    o.callback = receiveDatum(o.ps);
    request(o);
}

//
// newQueryObject
//
// Constructs an empty query object.
//
// OUTPUT
//   An object with the fields gran, start, end, idpss, idpes, spss, spes, ps where
//     start is a start time POSIX timestamp (int).
//     end is an end time POSIX timestamp (int).
//     gran is a granularity ('h', 'D', 'M' or 'Y').
//     idpss is a list of idp's for each histogram (list of list of strings).
//     spss is a list of sp's for each histogram (list of list of strings).
//     idpExclusions is boolean for each histogram  (list of strings). If '1', use complement of idps.
//     spExclusions is similar to above (list of string).
//     ps is a list of strings where each string is either an entity id or 'total' or 'others'.
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

//
// request
// 
// Send a AJAX request.
//
// INPUT
//   o - A query object (see newQueryObject(..) function)
//
// OUTPUT
//   Nothing, but a request will be sent.
//
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

//
// handleResponse
//
// JSON decodes the response text of a given xml http object
// and passes the result to a given callback function
//
// INPUT
//   xmlhttp - The XML HTTP object.
//   callback - The callback function.
//
// OUTPUT
//   Nothing, but the callback function will be called.
//
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
