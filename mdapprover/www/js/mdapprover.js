$(document).ready(function() {
    wayf.mdapp.init();
});

var wayf = wayf || {};

wayf.mdapp = wayf.mdapp || {};

wayf.mdapp.acceptEntity = function (e) {
    
    $.post("acceptEntity.php", {eid: e.target.id}, function (data, status) {
        if (status == 'success') {
            $(e.target.parentElement.parentElement).remove();
            wayf.mdapp.layouthelper.addEntityToAcceptedTable(data)
        }
    }, 'json');
};

wayf.mdapp.removeEntity = function (e) {
    
    $.post("removeEntity.php", {eid: e.target.id}, function (data, status) {
        if (status == 'success') {
            $(e.target.parentElement.parentElement).remove();
            wayf.mdapp.layouthelper.addEntityToNotAcceptedTable(data)
        }
    }, 'json');
};

wayf.mdapp.init = function () {

    $("#mdapp-notaccepted-table").on('click', '.mdapp-addentity',  wayf.mdapp.acceptEntity);
    $("#mdapp-accepted-table").on('click', '.mdapp-removeentity', wayf.mdapp.removeEntity);   
    $("#content").on('click', '.icon-chevron-down', wayf.mdapp.layouthelper.showAttributes);
    $("#content").on('click', '.icon-chevron-up', wayf.mdapp.layouthelper.hideAttributes);
};

wayf.mdapp.layouthelper = {};

wayf.mdapp.layouthelper.showAttributes = function (e) {
    $(".attr_view", e.target.parentElement).css('display', 'block');
    $(e.target).removeClass('icon-chevron-down').addClass('icon-chevron-up');
};

wayf.mdapp.layouthelper.hideAttributes = function (e) {
    $(".attr_view", e.target.parentElement).css('display', 'none');
    $(e.target).removeClass('icon-chevron-up').addClass('icon-chevron-down');
};

wayf.mdapp.layouthelper.addEntityToAcceptedTable = function (entity) {
    row = $('<tr></tr>').appendTo("#mdapp-accepted-table");
    row.append('<td>' + entity.id  +'</td>');  
    row.append('<td>' + entity.entityid  +'</td>');  
    row.append('<td>' + entity.name  +'</td>');  
    row.append('<td>' + entity.purpose  +'</td>');  
    attr = $('<td></td>').appendTo(row);
    attr.append(wayf.mdapp.layouthelper.formatAttributes(entity.attributes));  
    row.append('<td>' + entity.created  +'</td>');  
    row.append('<td>' + entity.user  +'</td>');  
    row.append('<td><i class="icon-minus-sign mdapp-removeentity" id="' + $.base64.encode(entity.entityid) + '"></i></td>');  
};

wayf.mdapp.layouthelper.addEntityToNotAcceptedTable = function (entity) {
    row = $('<tr></tr>').appendTo("#mdapp-notaccepted-table");
    row.append('<td>' + entity.entityid  +'</td>');  
    row.append('<td>' + entity.name  +'</td>');  
    row.append('<td>' + entity.purpose  +'</td>');  
    attr = $('<td></td>').appendTo(row);
    attr.append(wayf.mdapp.layouthelper.formatAttributes(entity.attributes));  
    row.append('<td>' + entity.created  +'</td>');  
    row.append('<td>' + entity.user  +'</td>');  
    row.append('<td><i class="icon-plus-sign mdapp-addentity" id="' + $.base64.encode(entity.entityid) + '"></i></td>');  
};

wayf.mdapp.layouthelper.formatAttributes = function (attributes) {
    var list = document.createElement('ul');
    $(attributes).each(function (index, element) {
        $(list).append('<li>' + element  + '</li>');      
    });
    return list;
};
