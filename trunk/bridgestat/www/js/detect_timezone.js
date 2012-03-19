// script by Josh Fraser (http://www.onlineaspect.com)

//
// pad
//
// Converts an integer to a string an pads a number
// of trailing zeroes so the string will have a specific
// length.
//
// INPUT
//   number - The number to convert to a string (int). 
//   length - The desired length of the string (int).
//
// OUTPUT
//   A string.
//
// EXAMPLE
//   pad(7, 2) = '07'
//
function pad(number, length){
    var str = "" + number
    while (str.length < length) {
        str = '0'+str
    }
    return str
}

//
// get_time_zome
//
// Get a string representation of the current time zone offset.
//
// OUTPUT
//   A string of the form '+/-xx:yy' eg. '+01:00'.
//
function get_time_zone() {
    var offset = new Date().getTimezoneOffset()
    offset = ((offset<0? '+':'-') + pad(Math.abs(offset/60), 2) + ':' + pad(Math.abs(offset%60), 2))
    return offset;
}