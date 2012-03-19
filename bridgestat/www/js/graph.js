
//TODO move assertion definition somewhere else
function AssertException(message) { this.message = message; }
AssertException.prototype.toString = function () {
  return 'AssertException: ' + this.message;
}

//
// assert
//
// Assert a given epxression is true. If not, throw an exception
//
// INPUT
//   exp - The expression (boolean).
//   message - A message for the exception (string).
//
// OUTPUT
//   None, but may throw an exception.
function assert(exp, message) {
  if (!exp) {
    throw new AssertException(message);
  }
}

//
// Transform the data into a format that d3 better can work with.
//
// INPUT
//   datum - Plain histogram data for several histograms. A list of list of integers.
//
// OUTPUT
//   The transformed datum. Each integer in the input list is transformed to
//   an object with the fields x, y and y0. x is the bin index, y is the bin count
//   and y0 is a baseline value initiated to 0 used for stacked graphs.
//
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

//
// getScales
//
// Compute scales used to map bin indeces and counts to screen coordinates.
//
// INPUT
//   datum - Histogram data (list of list of objects w. fields x, y and y0)
//   w - Desired width of the graph in screen coord. (int)
//   h - Desired height of the graph in screen coord. (int)
//   xMargin - Extra space below the x-axis (int)
//   yMargin - Extra space left of y-axis (int)
//   start - Start date as a POSIX timestamp (int)
//   end - End date as a POSIX timestamp (int)
//   gran - Granularity ('h', 'D', 'M', 'Y')
//   barWidth - Width of bars used for bar diagrams (int).
//
// OUTPUT
//   A list of values [x, y, yStacked, t, tFormat, tTicks] where
//    x is a scale mapping bin indeces to screen X-coordinate (d3 scale)
//    y is a scale mapping bin counts to screen Y-coordinate (d3 scale)
//    yStacked is the same as y but where the domain uses stacked counts (d3 scale)
//    t us a scale mapping dates to screen X-coordinates (d3 scale)
//    tFormat is a time format string usable by d3 (string).
//    tTick is a heuristic optimal number of ticks along the x-axis (int).
//
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

//
// plotLine
//
// INPUT
//   data - The data to plot (list of objects w. x and y fields).
//   x - The x scale (d3 scale).
//   y - The y scale (d3 scale).
//   parentElem - The parent element of the graph (d3 SVG elem)
//
// OUTPUT
//   Nothing, but the graph will be added as a child to parentElem.
//
function plotLine(data, x, y, parentElem) {
    var color = data.color;

    var line = d3.svg.line()
	.x(function(d) { return x(d.x); })
	.y(function(d) { return -1 * y(d.y); })
    parentElem.append("svg:path")
	.attr("d", line(data.values))
        .style("stroke", color)
}

//
// plotArea
//
// Similar to plotLine
//
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

//
// plotStackedArea
//
// Similar to plotLine
//
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

//
// plotBars
//
// INPUT
//   data - The data to plot (list of objects w. x and y fields).
//   x - The x scale (d3 scale).
//   y - The y scale (d3 scale).
//   xMargin - The spacing below the x-axis (int).
//   index - The histogram index (int).
//   N - The total number of histograms (int).
//   barWidth - The computed bar width (int).
//   parentElem - The parent element of the graph (d3 SVG elem)
//
// OUTPUT
//   Nothing, but the graph will be added as a child to parentElem.
//
function plotBars(data, x, y, xMargin, index, N, barWidth, parentElem) {

    var color = data.color;

    var outerPad = CONST.BarsOuterPad;
    var innerPad = CONST.BarsInnerPad;

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

//
// plotBars
//
// INPUT
//   data - The data to plot (list of objects w. x and y fields).
//   x - The x scale (d3 scale).
//   y - The y scale (d3 scale).
//   xMargin - The spacing below the x-axis (int).
//   barWidth - The computed bar width (int).
//   parentElem - The parent element of the graph (d3 SVG elem)
//
// OUTPUT
//   Nothing, but the graph will be added as a child to parentElem.
//
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


//
// plot
//
// Plots a graph
//
// INPUT
//   data - The data to plot (list of objects w. x and y fields).
//   x - The x scale (d3 scale).
//   y - The y scale (d3 scale).
//   xMargin - The spacing below the x-axis (int).
//   index - The histogram index (int).
//   N - The total number of histograms (int).
//   barWidth - The computed bar width (int).
//   parentElem - The parent element of the graph (d3 SVG elem)
//   type - The graph type ('l', 'a', 'sa', 'gb', 'sb').
//
// OUTPUT
//   Nothing, but the graph will be added as a child to parentElem.
//
function plot(data, x, y, xMargin, index, N, barWidth, parentElem, type) {
    switch(type) {
    case 'l' : plotLine(data, x, y, parentElem); break;
    case 'a' : plotArea(data, x, y, parentElem); break;
    case 'sa' : plotStackedArea(data, x, y, parentElem); break;
    case 'gb' : plotBars(data, x, y, xMargin, index, N, barWidth, parentElem); break;
    case 'sb' : plotStackedBars(data, x, y, xMargin, barWidth, parentElem); break;
    }
}

//
// grid
//
// Add an underlying grid to a graph
//
// INPUT
//   t - A scale from dates to screen X-coordinates (d3 scale).
//   tTicks - The desired number of ticks on the x-axis (int).
//   y - A scale from counts to screen Y-coordinates (d3 scale).
//   xMargin -int
//   yMargin - int
//   barWidth - int
//   w - int
//   h - int
//   parentElem - The SVG element where the grid should be appended (d3 SVG elem)
//
// OUTPUT
//   Nothing, but grid will be appended to parentElem.
// 
function grid(t, tTicks, y, xMargin, yMargin, barWidth, w, h, parentElem) {
    var rules = parentElem.selectAll("g.xRule")
	.data(t.ticks(tTicks))
	.enter().append("g")
	.attr("class", "xRule");
    
    rules.append("line")
	.attr("x1", t)
	.attr("x2", t)
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

// 
// gridBar
//
// same as grid.
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

//
// axis
//
// Add axis to a graph.
//
// INPUT
//   x - d3 scale
//   y - d3 scale
//   t - d3 scale
//   tFormat - int
//   tTicks - int
//   yMargin - int
//   barWidth - int
//   lbl - Label for the y-axis (string).
//   parentElem - d3 SVG elem.
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

//
// axisBars
//
// see axis
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

//
// stack
//
// stack computes the baseline values (y0) for a set of histograms.
//
// INPUT
//   datum - List of list of objects w fields x, y and y0.
//
// OUTPUT
//   None, but datum may change.
function stack(datum) {
    var stack = d3.layout.stack()
	.values(function(d) { return d.values; })
	.x(function(d) { return d.x; })
	.y(function(d) { return d.y; })
	.out(function(d, y0, y) { d.y0 = y0; })
	.order("reverse");

    stack(datum);
}