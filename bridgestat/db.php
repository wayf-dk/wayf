<?php

require_once('config.php');
require_once('prelude.php');
//global $dbConnection;
//unset($dbConnection);

//TODO use value from config
$MAX_NUMBER_OF_BINS = 10000;

if(isset($_POST['g'])) {
  $gran = $_POST['g'];
}
else {
  internalError("Missing parameter granularity. Parameter 'g' must be set to either 's', 'm', 'h', 'D', 'M' 'Y'.");
}

if(isset($_POST['s'])) {
  $start = $_POST['s'];
}
else {
  internalError("Missing parameter start date. Parameter 's' must be set to a POSIX timestamp.");
}

if(isset($_POST['e'])) {
  $end = $_POST['e'];
}
else {
  internalError("Missing parameter end date. Parameter 'e' must be set to a POSIX timestamp.");
}

if(isset($_POST['tz'])) {
  $timezone = $_POST['tz'];
}
else {
  internalError("Missing parameter time zone. Parameter 'tz' must be set to a timezone offset of the form +/-xx:yy");
}

if(isset($_POST['spss'])) {
  $spss = json_decode($_POST['spss']);
  if(is_null($spss)) {
    internalError("Invalid parameter 'spss'. 'spss' must be set to a JSON encoded list of lists of enitity ID's (strings).");
  }
}
else {
  internalError("Missing parameter 'spss'. 'spss' must be set to a JSON encoded list of lists of enitity ID's (strings).");
}

if(isset($_POST['idpss'])) {
  $idpss = json_decode($_POST['idpss']);
  if(is_null($idpss)) {
    internalError("Invalid parameter 'idpss'. 'idpss' must be set to a JSON encoded list of lists of enitity ID's (strings).");
  }
}
else {
  internalError("Missing parameter 'idpss'. 'idpss' must be set to a JSON encoded list of lists of enitity ID's (strings).");
}

if(isset($_POST['idpes'])) {
  $idpExclusions = json_decode($_POST['idpes']);
  if(is_null($idpExclusions)) {
    internalError("Invalid parameter 'idpes'. 'idpes' must be set to a list of booleans (1 or 0).");
  }
}
else {
  internalError("Missing parameter 'idpes'.  'idpes' must be set to a list of booleans (1 or 0).");
}

if(isset($_POST['spes'])) {
  $spExclusions = json_decode($_POST['spes']);
  if(is_null($spExclusions)) {
    internalError("Invalid parameter 'spes'. 'spes' must be set to a list of booleans (1 or 0).");
  }
}
else {
  internalError("Missing parameter 'spes'.  'spes' must be set to a list of booleans (1 or 0).");
}

$nHists = count($idpss);
if(count($spss) != $nHists || count($spExclusions) != $nHists || count($idpExclusions) != $nHists) {
  internalError("'idpss', 'spss', 'idpes' and 'spes' must have equal length.");
}

//main('h', '1328911238', '1328997599', array(), array());
main($gran, $start, $end, $timezone, $idpss, $spss, $idpExclusions, $spExclusions, $nHists);

//TODO: Rename to something else than the function in prelude.php


//
// dbHistogram
//
// Get histogram data from database.
//
// INPUT: 
//   $start - Start time POSIX timestamp (string).
//   $end - End time POSIX timestamp (string).
//   $gran - Granularity ('h', 'D', 'M' or 'Y').
//   $timezone - Timezone ('+/-xx:yy').
//   $idps - List of entity ids (string) refering to idps (empty list means all).
//   $sps - List of entity ids (string) refering to sps (empty list means all).
//   $idpe - Exclusive selection for idp's ('1' or '0'). If $idpe = '1' then the query will select all dates where idp is NOT in $idps.
//   $spe - Exclusive selection for sp's.
// OUTPUT: 
//   A handle to the result (mysql php api). The result will contain one
//   row for each histogram bin and two columns. The first column is an
//   arbitrary date in the bin, and the second column is a count.
//
function dbHistogram($start, $end, $gran, $timezone, $idps, $sps, $idpe, $spe) {

  $idpe = $idpe == '1';
  $spe = $spe == '1';

  $select = 'SELECT UNIX_TIMESTAMP(date), COUNT(*) FROM log ';

  // Make a selection over a date range
  $w = " WHERE date BETWEEN FROM_UNIXTIME($start) AND FROM_UNIXTIME($end)";

  // Filter on sps and idps
  if(count($idps) > 0) {
    $idpstr = implode('\',\'', $idps);
    $in = $idpe ? 'NOT IN' : 'IN'; 
    $w.= " AND idp_id $in ('$idpstr')";
  }
  if(count($sps) > 0) {
    $spstr = implode('\',\'', $sps);
    $in = $spe ? 'NOT IN' : 'IN'; 
    $w .= " AND sp_id $in ('$spstr')";
  }

  $date = "CONVERT_TZ(date, 'SYSTEM', '$timezone')";

  $gb = '';
  switch ($gran) {
  case 's' :
    $gb = " GROUP BY YEAR($date), MONTH($date), DAY($date), HOUR($date), MINUTE($date), SECOND($date)";
    break;
  case 'm' : 
    $gb = " GROUP BY YEAR($date), MONTH($date), DAY($date), HOUR($date), MINUTE($date)";
    break;
  case 'h' : 
    $gb = " GROUP BY YEAR($date), MONTH($date), DAY($date), HOUR($date)";
    break;
  case 'D' : 
    $gb = " GROUP BY YEAR($date), MONTH($date), DAY($date)";
    break;
  case 'M' : 
    $gb = " GROUP BY YEAR($date), MONTH($date)";
    break;
  case 'Y' : 
    $gb = " GROUP BY YEAR($date)";
    break;
  }

  $sql= $select . $w. $gb;

  $dbResult = dbQuery($sql);
  return $dbResult;
}

// Make bins using group by query

//
// histogram
//
// Given a database result handle, create histogram data and output it using echo.
//
// INPUT:
//   $gran Granularity.
//   $start Start time.
//   $end End time.
//   $timezoneOffset time offset in seconds (int).
//   $dbResult Database result hanlde.
// OUTPUT: 
//   Nothing is returned. Output is echoed.
//
function histogram($gran, $start, $end, $timezoneOffset, $dbResult) {
  switch ($gran) {
  case 's' :
    return equalBins(1, $start, $end, 0, $dbResult);
  case 'm' :
    return  equalBins(60, $start, $end, 0, $dbResult);
  case 'h' : 
    return  equalBins(3600, $start, $end, 0, $dbResult);
  case 'D' :
    return  equalBins(86400, $start, $end, $timezoneOffset, $dbResult);
  case 'M' :
    return  monthBins($start, $end, $dbResult);
  case 'Y' :
    return  yearBins($start, $end, $dbResult);
  }
  internalError("Invalid parameter granularity. Parameter 'g' must be set to either 's', 'm', 'h', 'D', 'M' 'Y'.");
}

//
// main
//
// main function for this script. Outputs JSON encoded histogram data.
//
// INPUT:
//   $gran - Granularity.
//   $start - Start time.
//   $end - End time.
//   $timezone - Timezone string.
//   $idpss - A list of idp's for each histogram (list of list of strings).
//   $spss - A list of sp's for each histogram (list of list of strings).
//   $idpExclusions - A boolean for each histogram  (list of strings). If '1', use complement of idps.
//   $spExclusions - Similar to above (list of string).
//   $nHists - The number of histograms (int). This number must be equal to the length of $idpss, $spss, $idpExclusions and $spExclusions.
// OUTPUT: 
//   Nothing is returned. Output is echoed. JSON encoded list of list of ints (eg [[1,4,3,4], [40,10,0,32]]).
//
// EXAMPLE:
//   main('d', 1000, 2000, '+10:30', [['a'], ['a', 'b']], [['x'], ['y']], ['0', '1'], ['0', '0'], 2)
//   
//   request of two histograms from time(1000) to time(2000) grouped by days using the timezone '+10:30'.
//   The first histogram should count dates for sp IN {'a'} and idp IN {'x'}.
//   The second histogram should count dates for sp NOT IN {'a', 'b'} and idp IN {'y'}.
//
function main($gran, $start, $end, $timezone, $idpss, $spss, $idpExclusions, $spExclusions, $nHists) {
  $t = explode(':', $timezone);

  $timezoneOffset = intval(substr($t[0], 1)*60*60) + intval($t[1])*60;

  if($nHists == 0) {
    echo '[]';
  }
  else {
    echo '[ ';

    //First histogram:
    $idps = $idpss[0];
    $sps = $spss[0];
    $idpe = $idpExclusions[0];
    $spe = $spExclusions[0];
    //Get data from db:
    $dbResult = dbHistogram($start, $end, $gran, $timezone, $idps, $sps, $idpe, $spe);
    //Include count 0 bins:
    $hist = histogram($gran, $start, $end, $timezoneOffset, $dbResult);
    //Send data to output:
    outputArray($hist);
    echo "\n";

    //Remaining histograms
    for($i = 1; $i < $nHists; $i++) {
      echo ', ';
      $idps = $idpss[$i];
      $sps = $spss[$i];
      $idpe = $idpExclusions[$i];
      $spe = $spExclusions[$i];
      $dbResult = dbHistogram($start, $end, $gran, $timezone, $idps, $sps, $idpe, $spe);
      $hist = histogram($gran, $start, $end, $timezoneOffset, $dbResult);
      outputArray($hist);
      echo "\n";
    }
    echo ']';
  }
  dbClose();
}

//
// nBins
//
// Calculates a number of bins required.
//
// INPUT
//   $dt - Delta time. The size of each bin in seconds (int).
//   $start - The start time in POSIX timestamp (int).
//   $end - The end time in POSIX timestamp (int).
//
// OUTPUT
//   The number of bins (int).
function nBins($dt, $start, $end) {
  return ceil(($end - $start) / $dt);
}

//
// dateToBin
//
// Converts a POSIX timestamp to a bin index.
//
// INPUT
//   $date - Posix timestamp (int).
//   $dt - Delta time. The size of each bin in seconds (int).
//   $start - The start time in POSIX timestamp (int).
//   $timezoneOffset - The timezone offset in seconds (int).
//
// OUTPUT
//   The number of bins (int).
function dateToBin($date, $dt, $start, $timezoneOffset) {
  $date1 = $date;
  $date = $date - ($date + $timezoneOffset) % $dt;
  $d = getdate($date);
  return floor(($date - $start) / $dt);
}

//
// outputArray
//
// Print an array using echo.
//
// INPUT
//   $arr - The array
//
// OUTPUT
//   Nothing. Output is echoed.
function outputArray($arr) {
  $n = count($arr);
  if($n == 0)
    echo '[]';
  else {
    echo "[ $arr[0]\n";
    
    for($i = 1; $i < $n; $i++) {
      echo "  , $arr[$i]\n";
    }
    echo '  ]';
  }
}

//
// checkNumberOfBins
//
// Check if number of bins required is below the maximum allowed
// number of bins.
//
// INPUT
//   $nBins - The number of bins required (int).
//
// OUTPUT
//   Nothing, but the script may throw an internal error.
function checkNumberOfBins($nBins) {
  global $MAX_NUMBER_OF_BINS;
  if($nBins > $MAX_NUMBER_OF_BINS) {
    internalError("Too many bins $nBins. Reduce date range or increase granularity.");
  }
}

//
// equalBins
//
// Compute histogram data using equal bin sizes.
//
// INPUT
//   $dt - Bin size (int).
//   $start - Start time (int).
//   $end - End time (int). 
//   $timezoneOffset - Timezone offset in seconds (int).
//   $dbResult - MySQL result handle.
//
// OUTPUT
//   Bins (array of ints). 
function equalBins($dt, $start, $end, $timezoneOffset, $dbResult) {
  $bins = array();
  $start = $start - ($start  + $timezoneOffset) % $dt;
  $x = ($end+1) % $dt;
  $end = $end - $x + ($x > 0 ? 1 : 0);
  $nBins = nBins($dt, $start, $end);

  checkNumberOfBins($nBins);

  for($i = 0; $i < $nBins; $i++) {
    $bins[$i] = 0;
  }

  $index = 0;
  $binerr = 0;
  while ($row = mysql_fetch_array($dbResult, MYSQL_NUM)) {
    $index = dateToBin($row[0], $dt, $start, $timezoneOffset);
    if($bins[$index] != 0) {
      $binerr++;
    }
    $bins[$index] = $row[1];
  }
  if($binerr > 0) internalError("non-unique bin error $binerr bins");
  return $bins;

}


//
// monthBins
//
// Compute histogram data using month-sized bins.
//
// INPUT
//   $start - Start time (int).
//   $end - End time (int). 
//   $dbResult - MySQL result handle.
//
// OUTPUT
//   Bins (array of ints). 
function monthBins($start, $end, $dbResult) {

  checkNumberOfBins(nBins(2629744, $start, $end));

  $binBounds = array();
  $end = ceilMonth($end);
  for($d = floorMonth($start); $d <= $end; $d = incMonth($d)) {
    $binBounds[] = $d;
  }
  $nBins = count($binBounds) - 1;
  return histBins($binBounds, $nBins, $dbResult);
}


//
// yearBins
//
// Compute histogram data using year-sized bins.
//
// INPUT
//   $start - Start time (int).
//   $end - End time (int). 
//   $dbResult - MySQL result handle.
//
// OUTPUT
//   Bins (array of ints). 
function yearBins($start, $end, $dbResult) {

  checkNumberOfBins(nBins(31556926, $start, $end));

  $binBounds = array();
  $end = ceilYear($end);
  for($d = floorYear($start); $d <= $end; $d = incYear($d)) {
    $binBounds[] = $d;
  }
  $nBins = count($binBounds) - 1;
  return histBins($binBounds, $nBins, $dbResult);
}


//
// histBins
//
// Compute histogram data given and array of bin boundaries.
//
// INPUT
//   $binBounds - Bin boundaries given in POSIX timestamps (array of int).
//   $nBins - Number of bins (int).
//   $dbResult - MySQL result handle.
//
// OUTPUT
//   Bins (array of ints).
function histBins($binBounds, $nBins, $dbResult) {
  $bins = array();

  for($i = 0; $i < $nBins; $i++) {
    $bins[$i] = 0;
  }

  $index = 0;
  while ($row = mysql_fetch_array($dbResult, MYSQL_NUM)) {
    $index = timestampToBin($row[0], $binBounds, $index, $nBins);
    $bins[$index] += $row[1];
  }
  return $bins;
}

//
// timestampToBin
//
// Converts a timestamp to a bin index given an array of bin boundaries.
//
// INPUT
//   $time - Timestamp (int).
//   $bins - Bin boundaries (array of ints). 
//   $startIndex - Perform the seach by starting at this index (used for optimization).
//   $nBins - Number of bins
//
// OUTPUT
//   Bins (array of ints). 
function timestampToBin($time, $bins, $startIndex, $nBins) {
  $index = $startIndex;
  while(true) {
    $nextIndex = ($index + 1) % ($nBins+1);
    if($time >= $bins[$index] && $time < $bins[$nextIndex]) {
      return $index;
    }
    $index = $nextIndex;
    if($index == $startIndex)
      break;
  }
}

//
// floorMonth
//
// Takes a timestamp and floors the value to the nearest month by
// setting hours, minutes and seconds to 0, and setting the day of the
// month to 1.
//
// INPUT
//   $d - POSIX timestamp (int).
//
// OUTPUT
//   A floored timestamp (int).
function floorMonth($d) {
  $date = getdate($d);
  return mktime(0, 0, 0, $date['mon'], 1, $date['year']);
}


//
// ceilMonth
//
// Takes a timestamp and ceils the value to the nearest month by
// setting hours, minutes and seconds to 0, and setting the day of the
// month to 1 and possibly adding one month.
//
// INPUT
//   $d - POSIX timestamp (int).
//
// OUTPUT
//   A ceiled timestamp (int).
function ceilMonth($d) {
  $fd = floorMonth($d);
  return $d == $fd ? $fd : incMonth($fd);
}

//
// incMonth
//
// Add one month to a unix timestamp.
//
// INPUT
//   $d - POSIX timestamp (int).
//
// OUTPUT
//   A new timestamp (int).
function incMonth($d) {
  $date = getdate($d);
  if($date['mon'] == 12) {
    return mktime(0, 0, 0, 1, 1, $date['year'] + 1);
  }
  else {
    return mktime(0, 0, 0, $date['mon']+1,1, $date['year']);
  }
}

//Similar to floorMonth.
function floorYear($d) {
  $date = getdate($d);
  return mktime(0, 0, 0, 1, 1, $date['year']);
}

//Similar to ceilMonth.
function ceilYear($d) {
  $fd = floorYear($d);
  return $d == $fd ? $fd : incYear($fd);
}

//Similar to incMonth.
function incYear($d) {
  $date = getdate($d);
  return mktime(0, 0, 0, 1, 1, $date['year'] + 1);
}

?>
