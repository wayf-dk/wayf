/**
 * moth.js - Simple library for building WAYF style moth diagrams
 *
 * Based on the original work done by Frederik Meisner Madsen.  
 *
 * Authors: Frederik Meisner Madsen
 *          Jacob Christiansen, jach@wayf.dk
 *
 * Copyright (c) 2012 Jacob Christiansen Permission is hereby granted, free
 * of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use, copy, modify,
 * merge, publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

var moth = moth || {};

moth.DEFAULTCONFIG = {
    boxW : 350 // Box størrelse
    , boxH : 25 // Box størrelse
    , wayfBoxW : 100 // WAYF box størrelse
    , colGap : 200 // Afstand mellem boxene vertikalt
    , rowGap : 3 // Aftsand mellem boxe horisontalt
    , wayfY : 2 // Hvormange boxe nede skal WAYF boxen være 
    , leftY : 1 // Hvormange boxe nede skal boxen til højre være 
    , rightY : 1 // Hvormange boxe nede skal boxen til højre være 
    , boxCornerRadius : 3 // Radius på hjørner
    , boxTextY : 17 // Y offset på tekst i boxe
    , boxTextX : 5 // X offset på tekst uden checkbox
    , boxTextXCheck : 25 // X offset på tekst ved checkbox
    , checkboxSize : 11 // Størrelse på checkboxe
    , checkSize : 20 // Størrelse på checksign image
    , checkOffsetX : -2 // X offset på checkmark image relative to checkbox
    , checkOffsetY : -8 // Y offset på checkmark image relative to checkbox
    , checkboxLeft: true
    , checkboxRight: false 
    , checkboxLeftFunction: function() {return;}
    , checkboxRightFunction: function() {return;}
    , leftLink: false
    , rightLink: true
};

moth.parseConfig = function(config) {
    this.config                       = this.DEFAULTCONFIG;
    this.config.boxW                  = config.boxW || this.DEFAULTCONFIG.boxW;
    this.config.boxH                  = config.boxH || this.DEFAULTCONFIG.boxH;
    this.config.wayfBoxW              = config.wayfBoxW || this.DEFAULTCONFIG.wayfBoxW;
    this.config.colGap                = config.colGap || this.DEFAULTCONFIG.colGap;
    this.config.rowGap                = config.rowGap || this.DEFAULTCONFIG.rowGap;
    this.config.wayfY                 = config.wayfY || this.DEFAULTCONFIG.wayfY;
    this.config.leftY                 = config.leftY || this.DEFAULTCONFIG.leftY;
    this.config.rightY                = config.rightY || this.DEFAULTCONFIG.rightY;
    this.config.boxCornerRadius       = config.boxCornerRadius || this.DEFAULTCONFIG.boxCornerRadius;
    this.config.boxTextY              = config.boxTextY || this.DEFAULTCONFIG.boxTextY;
    this.config.boxTextX              = config.boxTextX || this.DEFAULTCONFIG.boxTextX;
    this.config.boxTextXCheck         = config.boxTextXCheck || this.DEFAULTCONFIG.boxTextXCheck;
    this.config.checkboxSize          = config.checkboxSize || this.DEFAULTCONFIG.checkboxSize;
    this.config.checkSize             = config.checkSize || this.DEFAULTCONFIG.checkSize;
    this.config.checkOffsetX          = config.checkOffsetX || this.DEFAULTCONFIG.checkOffsetX;
    this.config.checkOffsetY          = config.checkOffsetY || this.DEFAULTCONFIG.checkOffsetY;
    this.config.checkboxLeft          = (config.checkboxLeft == undefined) ? this.DEFAULTCONFIG.checkboxLeft : config.checkboxLeft;
    this.config.checkboxRight         = (config.checkboxRight == undefined) ? this.DEFAULTCONFIG.checkboxRight : config.checkboxRight;
    this.config.checkboxLeftFunction  = config.checkboxLeftFunction || this.DEFAULTCONFIG.checkboxLeftFunction;
    this.config.checkboxRightFunction = config.checkboxRightFunction || this.DEFAULTCONFIG.checkboxRightFunction;
    this.config.leftLink              = (config.leftLink == undefined) ? this.DEFAULTCONFIG.leftLink : config.leftLink;
    this.config.rightLink             = (config.rightLink == undefined) ? this.DEFAULTCONFIG.rightLink : config.rightLink;
    return
};

moth.draw = function(target, leftElements, rightElements, config) {
    
    // Parse config
    this.parseConfig(config);

    /*
     * Configuration
     */
    var rightShown = rightElements.length;
    var leftShown = leftElements.length;

    var x, y, z;

    // TODO swap
    var wayfW = this.config.wayfBoxW; // Bredde på WAYF box
    //var boxW = this.config.boxW; // bredde på sp box
    var boxH = this.config.boxH; // højde på sp box

    // TODO swap
    var colGap = this.config.colGap; // 200
    var rowGap = this.config.rowGap; // 3
    var w = wayfW + 2 * this.config.boxW + 2*colGap + 2; // Størrelsen på SVG = WAYFbox + spbox*2 + 2*kollonnegap+2 (ved ikke hvorfor +2)
    var h = Math.max(leftShown, rightShown) * (this.config.boxH + rowGap) - rowGap + 2; // Størrelse på SVG
    var wayfX = this.config.boxW + colGap; // Placeringen af WAYFbox 
    var wayfY = (this.config.wayfY - 1) * (this.config.boxH + rowGap); // Placeringen af WAYFbox
    var leftY = (boxH+rowGap) * (this.config.leftY-1);
    var rightY = (boxH+rowGap) * (this.config.rightY-1);

    /*
     * Generate basic SVG image
     */
    var mothDiv = d3.select("#" + target);

    var moth =  mothDiv.append("svg:svg")
        .attr("height", h)
        .attr("width", w)
        .append("g");
    
    // Left box
    var gLeft = moth.append("svg:g")
        .attr("id", "left")
        .attr("transform", "translate(1, " + leftY + ")");
    
    // Center box
    var gMid = moth.append("svg:g")
        .attr("transform", "translate(" + (wayfX+1) + ", " + (wayfY+1) + ")");
    
    // Right box
    var gRight = moth.append("svg:g")
        .attr("id", "right")
        .attr("transform", "translate(" + (1 + this.config.boxW + wayfW + 2*colGap) + ", " + rightY + ")");

    /*
     * Generation of boxes
     */

    // Create arrays with indexes for the elements
    var leftRange = d3.range(leftShown);
    var rightRange = d3.range(rightShown);
    
    // Set classes and ID on all left g
    gLeft.selectAll("g")
        .data(leftRange)
        .enter()
        .append("g")
        //.attr("id", function(d,i) { return "left-"+i+"-"+leftElements[i].id; })
        .attr("id", function(d,i) { return "left"+leftElements[i].id; })
        .classed("mothbox", true)
        .classed("left", true);
    
    // Set classes and ID on all right g
    gRight.selectAll("g")
        .data(rightRange)
        .enter()
        .append("g")
        //.attr("id", function(d,i) { return "right-"+i+"-"+rightElements[i].id; })
        .attr("id", function(d,i) { return "right"+rightElements[i].id; })
        .classed("mothbox", true)
        .classed("right", true);
    
    // Set classes on WAYF box 
    gMid.classed("center", true)
        .classed("mothbox", true);
    
    // Set position on all left side boxes 
    gLeft.selectAll(".mothbox")
        .attr("transform", function(d,i) { return "translate(0, " + (i*(boxH + rowGap)) +")";});
    
    // Set position on all right side boxes
    gRight.selectAll(".mothbox")
        .attr("transform", function(d,i) { return "translate(0, " + (i*(boxH + rowGap)) +")";});
    
    //Create all boxes
    moth.selectAll(".mothbox")
        .append("rect")
        .attr("x", 0)
        .attr("y", 0)
        .attr("rx", this.config.boxCornerRadius)
        .attr("ry", this.config.boxCornerRadius)
        .attr("width", this.config.boxW)
        .attr("height", this.config.boxH);
    
    // Set specific width of WAYF box
    gMid.select(".mothbox rect")
        .attr("width", wayfW);
    
    /**
     * Set Text and links on right side
     */
    if (this.config.rightLink) {
        // Set Links with names on right side
        gRight.selectAll(".mothbox")
            .append("svg:a")
            .attr("xlink:href", function(i) {return "?" + rightElements[i].id;})
            .append("text")
            .attr("x", this.config.boxTextX)
            .attr("y",  this.config.boxTextY)
            .text(function(i) {return rightElements[i].name;});
    } else {
        gRight.selectAll(".mothbox")
            .append("text")
            .attr("x", this.config.boxTextX)
            .attr("y",  this.config.boxTextY)
            .text(function(i) {return rightElements[i].name;});
    }
    
    // Set text and links on left boxes
    if (this.config.leftLink) {
        // Set Links with names on right side
        gLeft.selectAll(".mothbox")
            .append("svg:a")
            .attr("xlink:href", function(i) {return "?" + leftElements[i].id;})
            .append("text")
            .attr("x", this.config.boxTextX)
            .attr("y",  this.config.boxTextY)
            .text(function(i) {return leftElements[i].name;});
    } else {
        gLeft.selectAll(".mothbox")
            .append("text")
            .attr("x", this.config.boxTextX)
            .attr("y",  this.config.boxTextY)
            .text(function(i) {return leftElements[i].name;});
    }

    // Text on WAYF box
    x = gMid.append("text")
        .attr("y",  this.config.boxTextY)
        .text("WAYF");

    // placement of text in WAYF box
    x.attr("x", wayfW / 2 - (x.node().getBBox().width / 2));

    // Add checkboxes to left
    if (this.config.checkboxLeft) {
        //Checkboxes
        x = (this.config.boxH - this.config.checkboxSize) / 2;

        // Set links for checkboxes on left side
        gLeft.selectAll(".mothbox")
            .append("svg:a")
            //.attr("xlink:href", "#")
            .attr("transform", "translate("+x+","+x+")")
            .classed("checkbox", true);

        // Get checkbox links on left side
        x = gLeft.selectAll(".checkbox");

        // Create checkboxes    
        x.append("rect")
            .attr("x", 0)
            .attr("y", 0)
            .attr("rx", 2)
            .attr("ry", 2)
            .attr("width", this.config.checkboxSize)
            .attr("height", this.config.checkboxSize)
            .style("fill", "white")
            .style("stroke-width", 0.5);

        // Set checkmarks if checked
        x.append("svg:image")
            .attr("x", this.config.checkOffsetX)
            .attr("y", this.config.checkOffsetY)
            .attr("width", this.config.checkSize)
            .attr("height", this.config.checkSize)
            .attr("xlink:href", "images/check2.png")
            .classed("check", true);

        // Check left checkbox if clicked
        gLeft.selectAll(".mothbox .checkbox")
            .on("click.checkbox", function() {checkBoxCheck(this);})
            .on("click.userfunc", this.config.checkboxLeftFunction);

        // Set X value of text if checkboxes is shown
        gLeft.selectAll(".mothbox text")
            .attr("x", this.config.boxTextXCheck);
    }
    
    // Add checkboxes to right
    if (this.config.checkboxRight) {
        //Checkboxes
        x = (this.config.boxH - this.config.checkboxSize) / 2;

        // Set links for checkboxes on left side
        gRight.selectAll(".mothbox")
            .append("svg:a")
            //.attr("xlink:href", "#")
            .attr("transform", "translate("+x+","+x+")")
            .classed("checkbox", true);

        // Get checkbox links on left side
        x = gRight.selectAll(".checkbox");

        // Create checkboxes    
        x.append("rect")
            .attr("x", 0)
            .attr("y", 0)
            .attr("rx", 2)
            .attr("ry", 2)
            .attr("width", this.config.checkboxSize)
            .attr("height", this.config.checkboxSize)
            .style("fill", "white")
            .style("stroke-width", 0.5);

        // Set checkmarks if checked
        x.append("svg:image")
            .attr("x", this.config.checkOffsetX)
            .attr("y", this.config.checkOffsetY)
            .attr("width", this.config.checkSize)
            .attr("height", this.config.checkSize)
            .attr("xlink:href", "images/check2.png")
            .classed("check", true);

        // Check left checkbox if clicked
        gRight.selectAll(".mothbox .checkbox")
            .on("click.checkbox", function() {checkBoxCheck(this);})
            .on("click.userfunc", this.config.checkboxRightFunction);
        
        // Set X value of text if checkboxes is shown
        gRight.selectAll(".mothbox text")
            .attr("x", this.config.boxTextXCheck);
    }

    /*
     * Line generation starts here
     */

    // Set lines on left
    gLeft.selectAll("line")
        .data(leftRange)
        .enter().append("line")
        .attr("x1", this.config.boxW)
        .attr("x2", wayfX)
        .attr("y2", wayfY-leftY + this.config.boxH / 2);

    // Set lines on right
    gRight.selectAll("line")
        .data(rightRange)
        .enter().append("line")
        .attr("x1", 0)
        .attr("x2", -colGap)
        .attr("y2", wayfY-rightY + this.config.boxH / 2);    

    // Set correct Y value on left lines
    gLeft.selectAll("line")
        .attr("y1", function(d, i) {return boxH / 2 + i*(boxH + rowGap);})

    // Set correct Y value on right lines
    gRight.selectAll("line")
        .attr("y1", function(d, i) {return boxH / 2 + i*(boxH + rowGap);})
    return;
};

function checkBoxCheck(checkBox) {
    var s = d3.select(checkBox).select(".check");
    s.classed("checked", ! s.classed("checked"));
}
