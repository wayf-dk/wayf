// Global variables:
var graphType = 'l';
var gran = 'h';

var colorI = 0;
var colorScale = d3.scale.category20();
var colorMap = {};


var totalData = { key : 'total', values : null, color : newColor('total') };
var othersData = { key : 'others', values : null, color : newColor('others') };
var datum = new Array();

//For Moth view
var sameShown;
var ohersShown;


//
// getName
//
// Gets a human readable name given a entity id.
//
// INPUT
//   pid - An entity id, 'total' or 'others' (string)
//
// OUTPUT
//   A human-readable name.
//
function getName(pid) {
    if(pid == 'total')
	return 'All';
    else if(pid == 'others')
	return 'Others';

    for(var i = 0; i < otherProviders.length; i++) {
	var p = otherProviders[i];
	if(p.idint == pid)
	    return p.name;
    }
    return pid;
}

//
// timeToString
//
// Converts a POSIX timestamp to a readable string.
//
// INPUT
//   t - A Posix timestamp (int).
//
// OUTPUT
//   A string of the format 'dd/mm yyyy'.
//
function timeToString(t) {
    var d = new Date(t*1000);
    return d.getDate() + '/' + (d.getMonth()+1) + ' ' + d.getFullYear();
}

//
// updateDisplayTime
//
// Updates the html span in the toolbar to the values of the global
// variables start and end.
//
function updateDisplayTime() {
    $('#widgetField span').get(0).innerHTML = timeToString(start) + '  -  ' + timeToString(end);
}

//
// setSelectedGrahpType
//
// Sets the global variable graphType to whatever radio button is
// selected.
//
function setSelectedGraphType() {
    if(document.getElementById('tl').checked)
	graphType = 'l';
    else if(document.getElementById('ta').checked)
	graphType = 'a';
    else if(document.getElementById('tgb').checked)
	graphType = 'gb';
    else if(document.getElementById('tsa').checked)
	graphType = 'sa';
    else if(document.getElementById('tsb').checked)
	graphType = 'sb';
}

//
// setBestGran
//
// Heuristically sets the optimal granularity given the date range.
// The function sets the global variable gran as well as the
// corresponding html radio button.
//
function setBestGran() {
    var t = end - start;
    var g;
    if(t < 60*60*24*5)  { //Less than 5 days
	g = 'h';
    }
    else if(t < 60*60*24*30*5) { //Less than 4 months
	g = 'D';
    }
    else if(t < 60*60*24*30*365*5) { // Less than 5 years
	g = 'M';
    }
    else {
	g = 'Y';
    }

    document.getElementById('g' + g).checked = true;
    gran = g;
}

//
// graphTypeClicked
//
// This function is called when a graph type radio button is clicked.
// This function may cause the graph to be repainted.
//
// INPUT
//   gt - The radio button.
//
function graphTypeClicked(gt) {
    if(gt.value != graphType) {
	graphType = gt.value;
	repaintGraph();
    }
    return true;
}

//
// granClicked
//
// This function is called when a granularity radio button is clicked.
// This function may cause all data to be refetched from the database,
// and the graph to be repainted.
//
// INPUT
//   g - The radio button.
// 
function granClicked(g) {
    if(g.value != gran) {
	gran = g.value;
	fullRefresh();
    }
    return true;
}

//
// fullRefresh
//
// Throws away all data retreived from the database and makes a new
// request to get all the selected histograms. This function is
// necessary when the granularity or the date range changes.
//
function fullRefresh() {
    //Deallocate all previous data:
    datum = new Array();
    othersData.values = null;
    totalData.values = null;

    var ps = getSelectedGraphs();
    var o = newQueryObject();
    for(var i = 0; i < ps.length; i++) {
	buildQueryProvider(ps[i], o);
    }
    if(totalIsSet()) {
	buildQueryTotal(o);
    }
    if(othersIsSet()) {
	buildQueryOthers(o);
    }
    if(o.ps.length > 0) {
        $('#loader').css('visibility', 'visible');
	sendQuery(o);
    }
}

//
// Floors a date to a given granularity.
//
// INPUT
//   t - POSIX timestamp (int)
//   g - 'h', 'D', 'M' or 'Y'
//
// OUTPUT
//   A new POSIX timestamp (int).
function floorDate(t, g) {
    var d;
    switch(g) {
    case 's' : break;
    case 'm' : break;
    case 'h' : 
	d = new Date(1000 * t);
	d.setSeconds(0);
	d.setMinutes(0);
	return Math.floor(d.getTime() / 1000);
    case 'D' : 
	d = new Date(1000 * t);
	d.setSeconds(0);
	d.setMinutes(0);
	d.setHours(0);
	return Math.floor(d.getTime() / 1000);
    case 'M' : 
	d = new Date(1000 * t);
	d.setSeconds(0);
	d.setMinutes(0);
	d.setHours(0);
	d.setDate(1);
	return Math.floor(d.getTime() / 1000);
	break;
    case 'Y' :  
	d = new Date(1000 * t);
	d.setSeconds(0);
	d.setMinutes(0);
	d.setHours(0);
	d.setDate(1);
	d.setMonth(0);
	return Math.floor(d.getTime() / 1000);
	break;
    }
}

//
// granularirtChanged
//
function granularityChanged() {
    fullRefresh();
}

//
// more
//
// The function that expands an entity list in the moth view (when
// clicking view all). The function repaints the entire moth view.
//
// INPUT
//   same - The entity list is of the same type as the selected entity (boolean).
//   left - The entity list is the left entity list in the moth view (boolean.
//
function more(same, left) {
    if(same) {
	sameShown = sameProviders.length;
    }
    else {
	othersShown = otherProviders.length;
    }
    repaintMothView();
}

//
// checkboxTotal
//
// This function us called when the All checkbox is checked.
//
// INPUT
//   checkBox - The checkbox (d3 SVG elem)
//
function checkboxTotal(checkBox) {
    checkBoxCheck(checkBox);
    if(totalData.values) {
	repaintGraph();
    }
    else {
$('#loader').css('visibility', 'visible');
	var o = newQueryObject();
	buildQueryTotal(o);
	sendQuery(o);
    }
}

//
// checkboxOthers
//
// This function is called when the Others checkbox is checked.
//
// INPUT
//   checkBox - The checkbox (d3 SVG elem)
//
function checkboxOthers(checkBox) {
    checkBoxCheck(checkBox);

    if(othersData.values) {
	repaintGraph();
    }
    else {
        $('#loader').css('visibility', 'visible');
	var o = newQueryObject();
	buildQueryOthers(o);
	sendQuery(o);
    }
}

//
// checkboxOthers
//
// This function is called when an entity checkbox is checked.
//
// INPUT
//   checkBox - The checkbox (d3 SVG elem)
//   i - The index of the entity in the global variable otherProviders
//
function checkboxOtherProvider(checkBox, i) {

    checkBoxCheck(checkBox);

    var p = getOtherProviderId(i);

    if(hasData(p)) {
	repaintGraph();
    }
    else {
    $('#loader').css('visibility', 'visible');
	var o = newQueryObject();
	buildQueryProvider(p, o);
	sendQuery(o);
    }
}

//
// Toggles a checkbox, by either adding or removing the css class 'checked'
//
// INPUT
//   checkBox - The checkbox (d3 SVG elem)
//
function checkBoxCheck(checkBox) {
    var s = d3.select(checkBox).select(".check"),
        tmp = d3.select(checkBox);
    tmp.classed('mark', !tmp.classed('mark'));
    s.classed("checked", ! s.classed("checked"));
}

//
// Get whether or not the Others checkbox is checked.
//
// OUTPUT
//   True if the checkbox is checked. 
//
function othersIsSet() {
    var x = d3.select("#othersCheckbox .check");
    return x.node() && x.classed("checked");
}

//
// Get whether or not the All checkbox is checked.
//
// OUTPUT
//   True if the checkbox is checked. 
//

function totalIsSet() {
    var x = d3.select("#totalCheckbox .check");
    return x.node() && x.classed("checked");
}

//
// Check whether the histogram data of a given entity is cached in the
// global variable datum.
//
// INPUT
//   p - Entity id (string).
//
// OUTPUT 
//   True if the histogram data exists in datum.
//

function hasData(p) {
    for(var i = 0; i < datum.length; i++) {
	if(datum[i].key == p) {
	    return true;
	}
    }
    return false;
}

//
// Get the grand maximum count of all histograms in datum (maximum value on the y-axis).
//
// INPUT
//   datum - The datum (list of list of objects w. 'x', 'y' and 'y0' as fields).
//   stakced - True if the histograms are stacked (boolean).
//
// OUTPUT
//   The grand max (int)
//
function getGrandMax(datum, stacked) {
    if(stacked) {
	return d3.max(datum.map(function(d) {return d3.max(d.values.map(function(d){ return d.y + d.y0;}))}));
    }
    else {
	return d3.max(datum.map(function(d) {return d3.max(d.values.map(function(d){ return d.y;}))}));
    }
}


//
// repaintGraph
//
// Draws or redraws the graphs
//
function repaintGraph() {


    var graphDiv = d3.select("#graphDiv");
    graphDiv.select("svg").remove();



    //Find out what graphs to plot
    var activeGraphs = getSelectedGraphs();
    var toPlotDatum = new Array();
    if(totalIsSet()) {
	toPlotDatum.push(totalData);
    }
    for(var j = 0; j < datum.length; j++) {  
	for(var i = 0; i < activeGraphs.length; i++) {
	    if(datum[j].key == activeGraphs[i]) {
		toPlotDatum.push(datum[j]);
	    }
	}
    }
    if(othersIsSet()) {
	toPlotDatum.push(othersData);
    }
    if(toPlotDatum.length > 0) {

	var nBins = toPlotDatum[0].values.length;

	var graphType = getGraphType();
	var b2 = isBar(graphType);
    
	var w = b2 ?  CONST.graphW * ((nBins - 0.5) / (nBins)) : CONST.graphW;
	var h = CONST.graphH;
	var signLineW = CONST.signatureLineW;
	var signLineH = CONST.signatureLineH;
	var signH = CONST.signatureSpacing;
	var svg = graphDiv.append("svg")
	    .attr("height", h)
	    .attr("width", CONST.graphW + CONST.signatureW);
	var g = svg.append("svg:g")
	    .attr("transform", "translate(0, " + h + ")");
	var signG = svg.append("svg:g")
	    .attr("transform", "translate("+CONST.graphW+", 0)");





	//Transition moth view to make room for graph
	d3.select("#mothDiv g")
	    .transition()
	    .delay(0)
	    .duration(CONST.transitionDuration)
	    .attr("transform", "translate(0, "+(h)+")");
	
	var b1 = isStacked(graphType);

	if(b1) {
	    stack(toPlotDatum);
	}

	var xMargin = CONST.graphXMarg;
	var yMargin = CONST.graphYMargFactor*(("" + getGrandMax(toPlotDatum, b1)).length + 1)+10;

	var gran = getGran();

	var barWidth = (w - 2 * yMargin) / nBins;

	var scales = getScales(toPlotDatum, w, h, xMargin, yMargin, floorDate(start, gran), floorDate(end, gran), gran, barWidth);
	var x,y,t,tFormat;

	x = scales[0];
	y = scales[b1 ? 2 : 1];
	t = scales[3];
	tFormat = scales[4];
	tTicks = scales[5];

	if(b2) {
	    gridBar(y, yMargin, w, barWidth, g);
	}
	else {
	    grid(t, tTicks, y, xMargin, yMargin, barWidth, w, h, g);
	}

	var N = toPlotDatum.length;
	for(var i = 0; i < N; i++) {  
	    plot(toPlotDatum[i], x, y, xMargin, i, N, barWidth, g, graphType);
	}
	
	if(b2) {
	    axisBars(x, y, t, tFormat, tTicks, yMargin, barWidth, 'Logins', g);
	}
	else{
	    axis(x, y, t, tFormat, tTicks, yMargin, barWidth, 'Logins', g);
	}
    }
    else {
	//Transition moth view back up.
	d3.select("#mothDiv g")
	    .transition()
	    .delay(0)
	    .duration(CONST.transitionDuration)
	    .attr("transform", "translate(0, 0)");
    }

    if(toPlotDatum.length > 0) {
    //Signatures
	signG.append("svg:rect")
	    .attr("x", 0)
	    .attr("y", 0)
	    .attr("width", CONST.signatureW)
	    .attr("height", signH * toPlotDatum.length)
	    .style("stroke", "black")
	    .style("fill", "white");

	signatures = signG.selectAll("g")
	    .data(toPlotDatum)
	    .enter()
	    .append("g")
	    .attr("transform", function(d,i) {return "translate(0, " + (i * signH) + ")"; });
	
	signatures.append("rect")
	    .attr("x", 5)
	    .attr("y", signH / 2 - signLineH / 2)
	    .attr("width", signLineW)
	    .attr("height", signLineH)
	    .style("stroke", function(d,i) {return d.color;})
	    .style("fill", function(d,i) {return d.color;})
	    .classed("signature", true);

	
	signatures.append("text")
	    .attr("x", signLineW + 10)
	    .attr("y", signH / 2 + 4 )
	    .text(function(d) {return getName(d.key);});
    }

}

function isStacked(type) {
    return type == 'sa' || type == 'sb';
}

function isBar(type) {
    return type == 'gb' || type == 'sb';
}

function getGran() {
    return gran;
}

function getStart() {
    return start;
}

function getEnd() {
    return end;
}

function getGraphType() {
    return graphType;
}

//
// Get the list of other entities where the checkbox is checked.
//
// OUTPUT
//   A list of entity id's (list of strings)
//
function getSelectedGraphs() {
    var ret = new Array();

    d3.selectAll('.provider .checked').each(function(i) { ret.push(getOtherProviderId(i)); });
    
    return  ret;
}

//
// Get the list of other entitiy names where the checkbox is checked.
//
// OUTPUT
//   A list of readable entity names (list of strings)
//
function getSelectedGraphNames() {
    var ret = new Array();

    d3.selectAll('.provider .checked').each(function(i) { ret.push(getOtherProviderName(i)); });
    
    return  ret;
}

// Javascript date object to POSIX time int.
function dateToTimestamp(date) {
    return Math.round(date.getTime() / 1000) 
}

//
// dateRangeChanged
//
// This function is called when a new range has been set in the date range picker.
//
function dateRangeChanged() {
    setBestGran();
    fullRefresh();
}

//
// main
//
// This is the body.onload function. It will create the date range
// picker, set the 'best' initial granularity calculate the amount of
// entities to show in the moth view, and draw the moth view.
//
function main() {

    var now3 = new Date();
    var now4 = new Date();
    $('#widgetCalendar').DatePicker({
	flat: true,
	format: 'd B, Y',
	date: [new Date(now3), new Date(now4)],
	calendars: 3,
	mode: 'range',
	starts: 1,
	onChange: function(formated, dates) {
	    start = dateToTimestamp(dates[0]);
	    end = dateToTimestamp(dates[1]);
	    updateDisplayTime();
	}
    });
    
    
    var state = false;
    var oldDate;
    $('#widgetField>a').bind('click', function(){
	$('#widgetCalendar').stop().animate({height: state ? 0 : $('#widgetCalendar div.datepicker').get(0).offsetHeight}, 500);

	if(state && $('#widgetField span').get(0).innerHTML != oldDate) {
	    dateRangeChanged();
	}
	else {
	    oldDate = $('#widgetField span').get(0).innerHTML;
	}

	state = !state;
	return false;
    });
    $('#widgetCalendar div.datepicker').css('position', 'absolute');    
    

    updateDisplayTime();

    setBestGran();

    setSelectedGraphType();

    sameShown = Math.min(CONST.entitiesToShow, sameProviders.length);
    othersShown = Math.min(CONST.entitiesToShow, otherProviders.length);

    repaintMothView();
}

//
// Get the entity id of same provider with index i.
//
function getSameProvider(i) {
    return sameProviders[i].id;
}

function getSameProviderId(i) {
    return sameProviders[i].idint;
}


function getOtherProviderId(i) {
    return otherProviders[i].idint;
}

//
// Get the entity readable name of same provider with index i.
//
function getSameProviderName(i) {
    var n = sameProviders[i].name;
    var max = 45;
    return n.length < max ? n : n.substr(0,max) + "...";
}


function getOtherProvider(i) {
    return otherProviders[i].id;
}

function getOtherProviderName(i) {
    var n = otherProviders[i].name;
    var max = 40;
    if (n == null) {
        return otherProviders[i].id;
    }
    return n.length < max ? n : n.substr(0,max) + "...";
}

//
// repaintMothView
//
// Draws or redraws the moth view
//
function repaintMothView() {

    var boxW = CONST.providerBoxW;
    var wayfW = CONST.wayfBoxW;
    var boxH = CONST.providerBoxH;

    var colGap = CONST.colGap;
    var rowGap = CONST.rowGap;

    var wayfX = boxW + colGap;
    var wayfY = (CONST.wayfY) * (boxH + rowGap) - rowGap;

    var sameOffsetY, othersOffestY;

    //var wayfH = Math.max(CONST.wayfHFactor * Math.max(othersShown, sameShown), boxH);
    var wayfH = CONST.providerBoxH;

    if(sameShown < CONST.entitiesToShow) {
	sameOffsetY = wayfY + wayfH/2 - boxH/2 - ((sameShown - 1) / 2 * (rowGap + boxH));
    }
    else {
	sameOffsetY = 0;
    }
    if(othersShown < CONST.entitiesToShow) {
	othersOffsetY = wayfY + wayfH/2 - boxH/2 - ((othersShown) / 2 * (rowGap + boxH));
    }
    else {
	othersOffsetY = 0;
    }
    var selP = getSelectedGraphs();

    var selT = totalIsSet();
    var selO = othersIsSet();

    var x, y, z;

    var w = wayfW + 2 * boxW + 2*colGap + 2;
    var h = CONST.graphH + Math.max(sameOffsetY, othersOffsetY) + Math.max(othersShown+2, sameShown+1) * (boxH + rowGap) - rowGap + 2;


    var mothDiv = d3.select("#mothDiv");
    mothDiv.select('svg').remove();

    var moth = 	mothDiv.append("svg:svg")
	.attr("height", h)
	.attr("width", w)
	.append("g");
    
    //If a graph is displayed translate moth view down.
    
    if(selP.length > 0 || selT || selO) {
	moth.attr('transform', 'translate(0, '+ CONST.graphH +')');
    }

    var gLeft = moth.append("svg:g")
	.attr("id", "left")
	.attr("transform", "translate(1, 1)");
    
    var gMid = moth.append("svg:g")
	.attr("transform", "translate(" + (wayfX+1) + ", " + (wayfY+1) + ")");
    
    var gRight = moth.append("svg:g")
	.attr("id", "right")
	.attr("transform", "translate(" + (1 + boxW + wayfW + 2*colGap) + ", 1)");
    
    var sameRange = d3.range(sameShown);
    var othersRange = d3.range(othersShown);
    
    var gSame, gOthers, lRange, rRange;
    if(idpMode) {
	gSame = gRight; gOthers = gLeft;
	lRange = othersRange; rRange = sameRange;
    }
    else {
	gSame = gLeft; gOthers = gRight;
	lRange = sameRange; rRange = othersRange;
    }

    //Grouping structure
    gSame.selectAll("g")
	.data(sameRange)
	.enter()
	.append("g")
	.classed("mothbox", true)
	.classed("provider", true);
    
    if(sameShown < sameProviders.length) {
	gSame.append("g")
	    .classed("mothbox", true)
	    .classed("more", true)
    }
    
    
    gOthers.selectAll("g")
	.data(othersRange)
	.enter()
	.append("g")
	.attr("id", function(d,i) { return "other"+i; })
	.classed("mothbox", true)
	.classed("provider", true);
    
    
    gOthers.insert("g", "g")
	.classed("mothbox", true)
	.classed("total", true);
    
    if(othersShown < otherProviders.length) {
	gOthers.append("g")
	    .classed("mothbox", true)
	    .classed("more", true);
    }
    
    gLeft.selectAll(".provider")
	.classed("sp", true);
    
    gMid.classed("provider", true)
	.classed("wayf", true)
	.classed("mothbox", true);
    
    gRight.selectAll(".provider")
	.classed("idp", true);
    
    gOthers.selectAll(".mothbox")
	.attr("transform", function(d,i) { return "translate(0, " + (othersOffsetY + i*(boxH + rowGap)) +")";});
    
    gSame.selectAll(".mothbox")
	.attr("transform", function(d,i) { return "translate(0, " + (sameOffsetY + i*(boxH + rowGap)) +")";});
    
    //Boxes
    moth.selectAll(".mothbox")
	.append("rect")
	.attr("x", 0)
	.attr("y", 0)
	.attr("rx", CONST.boxCornerRadius)
	.attr("ry", CONST.boxCornerRadius)
	.attr("width", boxW)
	.attr("height", boxH);
    
    gMid.select(".mothbox rect")
	.attr("width", wayfW)
	.attr("height", wayfH);
    
    //Text
    x = gSame.selectAll(".provider");
	
    x.append("svg:a")
	.attr("xlink:href", function(i) {return "?" + (idpMode ? "idp" : "sp") + "=" + getSameProviderId(i);})
	.append("text")
	.attr("x", CONST.boxTextXSame)
	.attr("y",  CONST.boxTextY)
	.text(getSameProviderName);
    
    x.classed("selected", function(i) {return getSameProviderId(i) == mainProvider;});
    
    gOthers.selectAll(".provider")
	.classed("owned", function(i) {return otherProviders[i].own;})
	.append("text")
	.attr("x", CONST.boxTextXOthers)
	.attr("y",  CONST.boxTextY)
	.text(getOtherProviderName);
    
    
    gOthers.selectAll(".owned text")
	.remove()
    
    gOthers.selectAll(".owned")
	.append("svg:a")
	.attr("xlink:href", "#")
    .on("click.link", function(i) {
        var mode = idpMode ? "sp" : "idp",
            id = getOtherProviderId(i);
        window.location = "/?" + mode + "=" + id;
    })
	.append("text")
	.attr("x", CONST.boxTextXOthers)
	.attr("y",  CONST.boxTextY)
	.text(getOtherProviderName);
    

    x = gMid.append("text")
	.text("WAYF");

    x.attr("x", wayfW / 2 - (x.node().getBBox().width / 2));
    x.attr("y", wayfH / 2 - (x.node().getBBox().height / 2) + 12);

    x = gOthers.select(".total");

    x.append("text")
	.attr("x", CONST.boxTextXOthers)
	.attr("y",  CONST.boxTextY)
	.text("All");

    x.append("text")
	.attr("x", boxW / 2 + CONST.boxTextXOthers)
	.attr("y",  CONST.boxTextY)
	.text("Others");

    // Counts
    gOthers.selectAll(".provider")
	.append("svg:text")
	.attr("x", boxW - 5)
	.attr("y", CONST.boxTextY)
	.attr("text-anchor", "end")
	.text(function(i) {return ""+otherProviders[i].count;});

    var totalCount = 0;
    var othersCount = 0;

    for(var i = 0; i < otherProviders.length; i++) {
	totalCount += parseInt(otherProviders[i].count);
	if(i >= othersShown) {
	    othersCount += parseInt(otherProviders[i].count);
	}
    }

    var total = gOthers.select(".total");
    total.append("svg:text")
	.attr("x", boxW/2 - 5)
	.attr("y", CONST.boxTextY)
	.attr("text-anchor", "end")
	.text(totalCount);

    total.append("svg:text")
	.attr("x", boxW - 5)
	.attr("y", CONST.boxTextY)
	.attr("text-anchor", "end")
	.text(othersCount);


    //Show all buttons
    x = gOthers.select(".more")
	.append("svg:a")
	.attr("xlink:href", "#")
	.attr("onclick", "more(false, " + idpMode + "); return false;")
	.append("text")
	.attr("y", CONST.boxTextY)
	.text("Show all (" + (otherProviders.length - othersShown) + " more)" );

    if(x.node()) {
	x.attr("x", boxW / 2 - x.node().getBBox().width / 2)
    }

    x = gSame.select(".more")
	.append("svg:a")
	.attr("xlink:href", "#")
	.attr("onclick", "more(true, " + (!idpMode) + "); return false;")
	.append("text")
	.attr("y", CONST.boxTextY)
	.text("Show all (" + (sameProviders.length - sameShown) + " more)" );
    if(x.node()) {
	x.attr("x", boxW / 2 - x.node().getBBox().width / 2)
    }


    //Checkboxes
    x = (boxH - CONST.checkboxSize) / 2;

    gOthers.selectAll(".provider")
	.append("svg:a")
	.attr("xlink:href", "#")
	.attr("transform", "translate("+x+","+x+")")
	.classed("checkbox", true);

    gOthers.select(".total")
	.append("svg:a")
	.attr("xlink:href", "#")
	.attr("transform", "translate("+x+","+x+")")
	.attr("id", "totalCheckbox")
	.classed("checkbox", true)
	.attr("onclick", "checkboxTotal(this); return false;");

    gOthers.select(".total")
	.append("svg:a")
	.attr("xlink:href", "#")
	.attr("transform", "translate(" + (boxW / 2 + x) +", "+x+")")
	.attr("id", "othersCheckbox")
	.classed("checkbox", true)
	.attr("onclick", "checkboxOthers(this); return false;");
    

    x = gOthers.selectAll(".checkbox");
	
    x.append("rect")
	.attr("x", 0)
	.attr("y", 0)
	.attr("rx", 2)
	.attr("ry", 2)
	.attr("width", CONST.checkboxSize)
	.attr("height", CONST.checkboxSize)
	.style("fill", "white")
	.style("stroke-width", 0.5);
    
    x.append("svg:image")
	.attr("x", CONST.checkOffsetX)
	.attr("y", CONST.checkOffsetY)
	.attr("width", CONST.checkSize)
	.attr("height", CONST.checkSize)
	.attr("xlink:href", "images/check2.png")
	.classed("check", true);
    
    if(selT) {
	gOthers.select("#totalCheckbox .check")
	    .classed("checked", true);
    }


    if(selO) {
	gOthers.select("#othersCheckbox .check")
	    .classed("checked", true);
    }

    gOthers.selectAll(".provider").attr("onclick", function (d, i) {
        return "checkboxOtherProvider(this, "+d+"); return false;";
    });

    if(selP.length > 0) {
	gOthers.selectAll(".provider .check")
	    .classed("checked", function(d,i) { return $.inArray(getOtherProvider(i), selP) != -1;});
    }

    //admin button
    if(role == 'admin') {
	x = gSame.select(".selected");
	
	y = x.append('svg:a')
	    .attr('xlink:href', 'admin/index.php?eid=' + mainProvider)
	    .attr('target', '_blank')
	    .append('svg:text')
	    .attr('y', CONST.boxTextY)
	    .text('ADMIN');

	z = y.node().getBBox();

	y.attr('x', boxW - z.width - 5);

	x.append('svg:rect')
	    .attr('x', boxW - z.width - 6)
	    .attr('y', z.y)
	    .attr('width', z.width + 2)
	    .attr('height', z.height)
	    .style('stroke-width', '1')
	    .style('fill', 'none');
    }

    //Lines

    var offsetLeft = !idpMode ? sameOffsetY : othersOffsetY;
    var offsetRight = idpMode ? sameOffsetY : othersOffsetY;
    var leftShown = !idpMode ? sameShown : othersShown;
    var rightShown = idpMode ? sameShown : othersShown;

    x = idpMode ? 1 : 0;
/*
    var diagonal = d3.svg.diagonal()
	.projection(function(d) { return [d.y, d.x];})

	.target(function(i) {return { y: boxW, x :  offsetLeft + boxH / 2 + (i + x)*(boxH + rowGap)};});

    if(leftShown == 1) {
	diagonal.source({y : wayfX, x : wayfY + wayfH / 2});
    }
    else {
	diagonal.source(function(i) {return {y : wayfX, x : wayfY + CONST.boxCornerRadius +(wayfH-2*CONST.boxCornerRadius) * i / (leftShown-1) };})
    }


    gLeft.selectAll("path.link")
	.data(lRange)
	.enter().append("path")
	.attr("class", "link")
	.attr("d", diagonal);

    diagonal = d3.svg.diagonal()
	.projection(function(d) { return [d.y, d.x];})
//	.source({y :  -colGap, x : wayfY + boxH / 2})
	.target(function(i) {return { y: 0, x :  offsetRight + boxH / 2 + (i + 1 - x)*(boxH + rowGap)};});

    if(rightShown == 1) {
	diagonal.source({y : -colGap, x : wayfY + wayfH / 2});
    }
    else {
	diagonal.source(function(i) {return {y : -colGap, x : wayfY + CONST.boxCornerRadius +(wayfH-2*CONST.boxCornerRadius) * i / (rightShown-1) };})
    }



    gRight.selectAll("path.link")
	.data(rRange)
	.enter().append("path")
	.attr("class", "link")
	.attr("d", diagonal);
*/
    gLeft.selectAll("line")
	.data(lRange)
	.enter().append("line")
	.attr("x1", boxW)
	.attr("x2", wayfX)
	.attr("y2", wayfY + boxH / 2);

    gRight.selectAll("line")
	.data(rRange)
	.enter().append("line")
	.attr("x1", 0)
	.attr("x2", -colGap)
	.attr("y2", wayfY + boxH / 2);    

    gOthers.selectAll("line")
	.attr("y1", function(i) {return othersOffsetY + boxH / 2 + (i+1)*(boxH + rowGap);})

    gSame.selectAll("line")
	.attr("y1", function(i) {return sameOffsetY + boxH / 2 + i*(boxH + rowGap);})
    return;
}

//
// Get a color given an identifier
//
// INPUT
//   p - A entity id, 'total' or 'others' (string)
//
// OUTPUT
//   A color (string).
//
function newColor(p) {

    if(colorMap[p]) 
	return colorMap[p];

    

    var c = colorScale(colorI);
    colorMap[p] = c;
    colorI ++;

    //  d3.select("#mothDiv").select("#other" + i)
    
    return c;
}
