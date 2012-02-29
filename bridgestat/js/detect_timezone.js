// script by Josh Fraser (http://www.onlineaspect.com)

function pad(number, length){
    var str = "" + number
    while (str.length < length) {
        str = '0'+str
    }
    return str
}

function get_time_zone() {
    var offset = new Date().getTimezoneOffset()
    offset = ((offset<0? '+':'-') + pad(Math.abs(offset/60), 2) + ':' + pad(Math.abs(offset%60), 2))
    return offset;
}