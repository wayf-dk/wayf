
//TODO move assertion definition somewhere else
function AssertException(message) { this.message = message; }
AssertException.prototype.toString = function () {
  return 'AssertException: ' + this.message;
}

function assert(exp, message) {
  if (!exp) {
    throw new AssertException(message);
  }
}

//Append SVG element for graph
function appendSVG(w, h, div) {
    var svg = div.append("svg")
	.attr("height", h)
	.attr("width", w);
    var g = svg.append("svg:g")
	.attr("transform", "translate(0, " + h + ")");
    return g;
}

function transformDatum(datum) {
    var transformedDatum = new Array();
    var n = datum.length;
    var data, transformedData;
    if(n > 0) {
	var m = datum[0].length;
	for(var i = 0; i < n; i++) {
	    data = datum[i];
	    transformedData = new Array();
	    transformedDatum.push(transformedData);
	    assert(data.length == m);
	    for(var j = 0; j < m; j++) {
		transformedData.push({x : j, y : data[j], y0 : 0});
	    }
	}
    }
    return transformedDatum;
}

function getScales(datum, w, h, xMargin, yMargin, start, end, gran, barWidth) {
    var data0 = datum[0].values;
    var grandMax = d3.max(datum.map(function(d) {return d3.max(d.values.map(function(d){ return d.y;}))}));
    var grandMaxStacked = d3.max(datum.map(function(d) {return d3.max(d.values.map(function(d){ return d.y + d.y0;}))}));
    var x = d3.scale.linear()
	.domain([0, data0.length-1])
	.range([0 + yMargin, w - yMargin]);
    var y = d3.scale.linear()
	.domain([0, grandMax])
	.range([0 + xMargin, h - xMargin]);
    var yStacked = d3.scale.linear()
	.domain([0, grandMaxStacked])
	.range([0 + xMargin, h - xMargin]);

    var s = new Date(start*1000);
    var e = new Date(end*1000);

    var time = d3.time.scale()
	.domain([s, e])
	.range([0 + yMargin, w - yMargin ]);
    
    var format;
    switch(gran) {
    case 's' : 
    case 'm' :
    case 'h' : format = s.getFullYear() == e.getFullYear() ? (s.getMonth() == e.getMonth() && s.getDate() == e.getDate() ? '%H:00' : '%e. %b %H:00') : '%e. %b \'%y %H:00'; break;
    case 'D' : format = s.getFullYear() == e.getFullYear() ?  '%e. %b' : '%e. %b \'%y'; break;
    case 'M' : format = s.getFullYear() == e.getFullYear() ? '%B' : '%b \'%y'; break;
    case 'Y' : format = '%Y'; break;
    }

    var tTicks = Math.min(12, data0.length);
    return [x, y, yStacked, time, d3.time.format(format), tTicks];
}

function plotLine(data, x, y, parentElem) {
    var color = data.color;

    var line = d3.svg.line()
	.x(function(d) { return x(d.x); })
	.y(function(d) { return -1 * y(d.y); })
    parentElem.append("svg:path")
	.attr("d", line(data.values))
        .style("stroke", color)
}


function plotArea(data, x, y, parentElem) {

    var color = data.color;
   
    var area = d3.svg.area()
	.x(function(d) { return x(d.x); })
	.y(function(d) { return -1 * y(d.y); })
	.y0(function(d) { return -1 * y(0) });

    parentElem.append("svg:path")
	.attr("class", "area")
	.attr("d", area(data.values))
        .style("fill", color)
        .style("fill-opacity", 0.5)
	.style("stroke", "black")
	.style("stroke-opacity", 0.5);
}


function plotStackedArea(data, x, y, parentElem) {

    var color = data.color;
    
    var area = d3.svg.area()
	.x(function(d) { return x(d.x); })
	.y(function(d) { return -1 * y(d.y + d.y0); })
	.y0(function(d) { return -1 * y(0) });

    parentElem.append("svg:path")
	.attr("class", "area")
	.attr("d", area(data.values))
        .style("fill", color)
        .style("fill-opacity", 1)
	.style("stroke", "black")
	.style("stroke-opacity", 1);
}

function plotStackedBars(data, x, y, xMargin, barWidth, parentElem) {

    var color = data.color;

    parentElem.append("svg:g").selectAll("rect")
	.data(data.values)
	.enter().append("rect")
	.attr("x", function(d) { return 2 + x(d.x); })
	.attr("y", function(d) {return -1 * y(d.y0+d.y);})
	.attr("height", function(d) {return y(d.y)-xMargin;})
	.attr("width", barWidth-4)
	.style("stroke", "rgb(0,0,0)")
        .style("fill", color)
        .style("fill-opacity", 1);

}

function plotBars(data, x, y, xMargin, index, N, barWidth, parentElem) {

    var color = data.color;

    var outerPad = 4;
    var innerPad = -2;

    parentElem.append("svg:g").selectAll("rect")
	.data(data.values)
	.enter().append("rect")
	.attr("x", function(d) { return outerPad + innerPad + (barWidth-2*outerPad) * index / N + x(d.x); })
	.attr("y", function(d) {return -1 * y(d.y);})
	.attr("height", function(d) {return y(d.y)-xMargin;})
	.attr("width", (barWidth - 2*outerPad) / N - (2*innerPad)) 
	.style("stroke", "rgb(0,0,0)")
        .style("fill", color)
        .style("fill-opacity", 1);
}

function plot(data, x, y, xMargin, index, N, barWidth, parentElem, type) {
    switch(type) {
    case 'l' : plotLine(data, x, y, parentElem); break;
    case 'a' : plotArea(data, x, y, parentElem); break;
    case 'sa' : plotStackedArea(data, x, y, parentElem); break;
    case 'gb' : plotBars(data, x, y, xMargin, index, N, barWidth, parentElem); break;
    case 'sb' : plotStackedBars(data, x, y, xMargin, barWidth, parentElem); break;
    }
}

function grid(x, xTicks, y, xMargin, yMargin, barWidth, w, h, parentElem) {
    var rules = parentElem.selectAll("g.xRule")
	.data(x.ticks(xTicks))
	.enter().append("g")
	.attr("class", "xRule");
    
    rules.append("line")
	.attr("x1", x)
	.attr("x2", x)
	.attr("y1", - xMargin)
	.attr("y2", xMargin - h);

    rules = parentElem.selectAll("g.yRule")
	.data(y.ticks(10))
	.enter().append("g")
	.attr("class", "yRule");
    
    rules.append("line")
	.attr("y1", function(d) { return -1* y(d);})
	.attr("y2", function(d) { return -1* y(d);})
	.attr("x1", yMargin)
	.attr("x2", w - yMargin);
}

function gridBar(y, yMargin, w, barWidth, parentElem) {
    rules = parentElem.selectAll("g.yRule")
	.data(y.ticks(10))
	.enter().append("g")
	.attr("class", "yRule");
    
    rules.append("line")
	.attr("y1", function(d) { return -1* y(d);})
	.attr("y2", function(d) { return -1* y(d);})
	.attr("x1", yMargin)
	.attr("x2", w - yMargin + barWidth);
}


function axis(x, y, t, tFormat, tTicks, yMargin, barWidth, lbl, parentElem) {
    parentElem.append("svg:line")
	.attr("x1", x(0))
	.attr("y1", -1 * y(0))
	.attr("x2", x(x.domain()[1]))
	.attr("y2", -1 * y(0))
    
    parentElem.append("svg:line")
	.attr("x1", x(0))
	.attr("y1", -1 * y(0))
	.attr("x2", x(0))
	.attr("y2", -1 * y(y.domain()[1]))


    parentElem.append("svg:text")
	.text(lbl)
	.attr("y", - y.range()[1] - 10)
	.attr("x", yMargin - 20);

    parentElem.selectAll(".xLabel")
	.data(t.ticks(tTicks))
	.enter().append("svg:text")
	.attr("class", "xLabel")
	.text(function(d) {return tFormat(d);})
	.attr("transform", function(d){ return "translate("+ t(d)+"," + (- CONST.graphXMarg + 10 ) + ") rotate(45)";})
	.attr("text-anchor", "left");
 
    parentElem.selectAll(".yLabel")
	.data(y.ticks(10))
	.enter().append("svg:text")
	.attr("class", "yLabel")
	.text(String)
	.attr("x", yMargin - 8)
	.attr("y", function(d) { return -1 * y(d) })
	.style("text-anchor", "end")
	.attr("dy", 4);

    parentElem.selectAll(".xTicks")
	.data(t.ticks(tTicks))
	.enter().append("svg:line")
	.attr("class", "xTicks")
	.attr("x1", function(d) { return t(d); })
	.attr("y1", -1 * y(0))
	.attr("x2", function(d) { return t(d);})
	.attr("y2", -1 * y(0) + 5);
    
    parentElem.selectAll(".yTicks")
	.data(y.ticks(10))
	.enter().append("svg:line")
	.attr("class", "yTicks")
	.attr("y1", function(d) { return -1 * y(d); })
	.attr("x1", x(0) - 5)
	.attr("y2", function(d) { return -1 * y(d); })
	.attr("x2", x(0));
}


function axisBars(x, y, t, tFormat, tTicks, yMargin, barWidth, lbl, parentElem) {

    var barWidth2 = barWidth / 2;

    parentElem.append("svg:line")
	.attr("x1", x(0))
	.attr("y1", -1 * y(0))
	.attr("x2", x(x.domain()[1]) + barWidth)
	.attr("y2", -1 * y(0))
    
    parentElem.append("svg:line")
	.attr("x1", x(0))
	.attr("y1", -1 * y(0))
	.attr("x2", x(0))
	.attr("y2", -1 * y(y.domain()[1]))



    parentElem.append("svg:text")
	.text(lbl)
	.attr("y", - y.range()[1] - 10)
	.attr("x", yMargin - 20);


    parentElem.selectAll(".xTicks")
	.data(t.ticks(tTicks))
	.enter().append("svg:line")
	.attr("class", "xTicks")
	.attr("x1", function(d) { return t(d) + barWidth2;})
	.attr("y1", -1 * y(0))
	.attr("x2", function(d) { return t(d) + barWidth2;})
	.attr("y2", -1 * y(0) + 5)

    parentElem.selectAll(".xLabel")
	.data(t.ticks(tTicks))
	.enter().append("svg:text")
	.attr("class", "xLabel")
	.text(function(d) {return tFormat(d);})
	.attr("transform", function(d){ return "translate("+ (t(d) + barWidth2) +"," + (- CONST.graphXMarg + 10 ) + ") rotate(45)";})
	.attr("text-anchor", "left");
 
    parentElem.selectAll(".yLabel")
	.data(y.ticks(10))
	.enter().append("svg:text")
	.attr("class", "yLabel")
	.text(String)
	.attr("x", yMargin - 8)
	.attr("y", function(d) { return -1 * y(d) })
	.style("text-anchor", "end")
	.attr("dy", 4)
    
    parentElem.selectAll(".yTicks")
	.data(y.ticks(10))
	.enter().append("svg:line")
	.attr("class", "yTicks")
	.attr("y1", function(d) { return -1 * y(d); })
	.attr("x1", x(0) - 5)
	.attr("y2", function(d) { return -1 * y(d); })
	.attr("x2", x(0))
}


function stack(datum) {
    var stack = d3.layout.stack()
	.values(function(d) { return d.values; })
	.x(function(d) { return d.x; })
	.y(function(d) { return d.y; })
	.out(function(d, y0, y) { d.y0 = y0; })
	.order("reverse");

    stack(datum);
}