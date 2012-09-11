var wayf = wayf || {};

// Function for searching in the consent tables
wayf.consentfilter = function (e) {
    var patt1 = new RegExp($(this).val(), "i");
    for(var x = 0; x < e.data.len; x++){
        if(patt1.test(e.data.entities_search[x])) {
            $(e.data.entities[x]).show();
        } else {
            $(e.data.entities[x]).hide();
        }
    }
};

wayf.setupSearch = function () {
    var consententities = $('#consenttable tr'),
        noconsententities = $('#noconsenttable tr'),
        consentlen = $(consententities).length,
        noconsentlen = $(noconsententities).length,
        consent_entities_search = Array(),
        noconsent_entities_search = Array();

    // Get searchable content
    for(var x = 0; x < consentlen; x++){
        consent_entities_search[x] = $(consententities[x]).find('td:eq(0)').html();
    }
    for(var x = 0; x < noconsentlen; x++){
        noconsent_entities_search[x] = $(noconsententities[x]).find('td:eq(0)').html();
    }
    
    $('#consent_search').keyup({entities: consententities, entities_search: consent_entities_search, len: consentlen}, wayf.consentfilter);
    $('#noconsent_search').keyup({entities: noconsententities, entities_search: noconsent_entities_search, len: noconsentlen}, wayf.consentfilter);
};

/**
 * Layout init function 
 * Takes care of all layout changes that needs to be taken care of on start
 */
wayf.layout = {};
wayf.layout.init = function () {
    var cover = $("<div id=\"cover\"></div>");
    $("body").append(cover);
};

/**
 * On load function
 */
(function () {
    "use strict";

    // Init layout
    wayf.layout.init();

    // Setup search
    wayf.setupSearch();

    /**
     * Display a popup box with consent information
     */
    $(".service").each(function () {
        $(this).click(function (e) {
            // Display cover over page
            $('#cover').css('display', 'block');
            $('#cover').css('height', $('html').height());

            // Display popupbox
            var div = $('<div></div>').addClass('consentbox').attr('id', 'consentpopup'),
                loaderimg = $('<img src="/images/loader.gif" alt="Loading" id="loaderimg"/>');
            
            div.append(loaderimg);
            $('body').append(div);
    
            // Generic handler for AJAX errors
            $(div).ajaxError(function(event, request, settings, error) {
                var reauthbutton;

                // Remove ESC keyup event
                $(document).off('keyup');
                
                // Remove popup box content
                div.html('');

                if (request.status === 401) {
                    div.append('<h2>' + lang.SESSIONTIMEOUT + '</h2>');
                } else {
                    div.append('<h2>' + lang.AJAXERROR.replace('ERROR', error) + '</h2>');
                }
                // Add reauth button
                reauthbutton = $('<div class="button"></div>');
                reauthbutton.append(lang.REAUTH);
                reauthbutton.click(div, function () {
                    window.location = '/';
                });
                div.append(reauthbutton);
            });

            // Grab consent info
            $.getJSON('/getconsentinfo.php', {id: $(this).attr('id')}, function (data) {
                var attributecontainer,
                    consentbutton,
                    cancelbutton;

                // Remove loader image
                loaderimg.remove();

                // Display consent info
                div.append('<img src="/images/x-mark2.png" alt="Close" id="close"/>');
                div.append('<h1>' + data.name + '</h1>');
                div.append(lang.PURPOSE.replace('SPNAME', data.name).replace('SPDESC', data.description));
                if (data.consent !== false) {
                    div.append(lang.CONSENTGIVEN.replace('CONSENTDATE', data.consent.consent_date));
                    div.append(lang.CONSENTUSED.replace('USEDATE', data.consent.usage_date));
                }
                div.append(lang.ATTRRELEASEINFO.replace('SPNAME', data.name + '</b></p>'));

                // Display attribute info
                if (data.attributes.length == 0) {
                    attributecontainer = $('<ul><li>' + lang.NOATTRIBUTES + '</li></ul>');
                } else {
                    attributecontainer = $('<ul></ul>');
                }
                $.each(data.attributes, function (i, val) {
                    var attribute = $('<li></li>'),
                        attributevals;

                    if (val.length > 1) {
                        attributevals = $('<ul></ul>');
                        $.each(val, function (i, val2) {
                            attributevals.append($('<li>' + val2 + '</li>'));
                        });
                        attribute.text(lang[i]).append(attributevals);
                    } else {
                        attribute.text(lang[i] + ': ' + val);
                    }

                    attributecontainer.append(attribute);
                });
                div.append(attributecontainer);

                // Add consent button
                consentbutton = $('<div class="consentbutton button"></div>');
                if (data.consent === false) {
                    consentbutton.append(lang.GIVECONSENT);
                } else {
                    consentbutton.append(lang.WITHDRAWCONSENT);
                }
                consentbutton.data('consent', data);

                // Toggle consent event
                consentbutton.click(wayf.consent.clickfunc);
                div.append(consentbutton);
                
                /**
                 * Cancel events
                 */
                // Cancel button
                cancelbutton = $('<div class="button"></div>');
                cancelbutton.append(lang.CANCEL);
                cancelbutton.click(div, function () {
                    div.unbind('click');
                    div.remove();
                    $('#cover').css('display', 'none');
                });
                div.append(cancelbutton);

                // Register ESC key to remove box
                $(document).keyup(div, function (e) {
                    if (e.keyCode === 27) {
                        div.unbind('keyup');
                        div.remove();
                        $('#cover').css('display', 'none');
                    }
                });
                // Close icon in top right corner
                $("#close").click(div, function () {
                    div.unbind('click');
                    div.remove();
                    $('#cover').css('display', 'none');
                });
            });
        });
    });
}());

wayf.consent = {};

// Concent click function
wayf.consent.clickfunc = function (e) {
    var data = $(e.currentTarget).data("consent");
    
    if (data.consent === false) {
        $.getJSON('/addconsent.php', {id: data.entityid}, function (data2) {
            var elm, elm2;
            if (data2.success) {
                wayf.consent.insertIntoConsentTable(data2.serviceid, 'consenttable');
            }
        });
        data.consent = {};
        $(e.currentTarget).data("consent", data);
    } else {
        $.getJSON('/removeconsent.php', {id: data.entityid}, function (data2) {
            var elm;
            if (data2.success) {
                wayf.consent.insertIntoConsentTable(data2.serviceid, 'noconsenttable');
            }
        });
        data.consent = false;
        $(e.currentTarget).data("consent", data);
    }
};

/**
 * Insert table row in correct place after concent have been given or withdrawn
 */
wayf.consent.insertIntoConsentTable = function (elmid, tableid) {
    var elm = $("#" + elmid).detach(),
        len = $("#" + tableid + " tr").length,
        origitem = elm.find('td').text().toLowerCase();

    $("#" + tableid + " tr").each(function (i, val) {
        curitem = $(val).find('td');
        if (wayf.consent.caseInsensitiveCompare(curitem.text(), origitem) > 0) {
            $(val).before(elm);
            $("#consentpopup").remove();
            $('#cover').css('display', 'none');
            return false;
        }
        if (i+1 >= len) {
            $(val).after(elm);
            $("#consentpopup").remove();
            $('#cover').css('display', 'none'); 
        }
    });
};

wayf.consent.caseInsensitiveCompare = function(str1, str2) {
    var test1 = String(str1).toLowerCase();
    var test2 = String(str2).toLowerCase();

    if (test1 < test2) {
        return -1;
    } else if (test1 == test2) {
        return 0;
    } else {
        return 1;
    }
};
