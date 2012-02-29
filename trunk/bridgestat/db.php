<?php

require_once('config.php');

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

//Rename to something else than the function in prelude.php
function dbQuery($start, $end, $gran, $timezone, $idps, $sps, $idpe, $spe) {

  global $config;

  $idpe = $idpe == '1';
  $spe = $spe == '1';

  $connection = mysql_connect($config['dbHost'], $config['dbUser'], $config['dbPwd']);
  if (!$connection)  {
    internalError("Failed to connect to database ".mysql_error());
  }
  mysql_select_db($config['dbName'], $connection);

  $select = 'SELECT UNIX_TIMESTAMP(date), COUNT(*) FROM log ';

  // Make a selection over a date range
  $w = " WHERE date BETWEEN FROM_UNIXTIME($start) AND FROM_UNIXTIME($end)";

  // Filter on sps and idps
  if(count($idps) > 0) {
    $idpstr = implode('\',\'', $idps);
    $in = $idpe ? 'NOT IN' : 'IN'; 
    $w.= " AND idp $in ('$idpstr')";
  }
  if(count($sps) > 0) {
    $spstr = implode('\',\'', $sps);
    $in = $spe ? 'NOT IN' : 'IN'; 
    $w .= " AND sp $in ('$spstr')";
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

  $fh = fopen('/var/www/stat/log/sql.log', 'a');

  fwrite($fh, $sql);
  fwrite($fh, "\n\n");

  fclose($fh);

  $dbResult = mysql_query($sql);

  mysql_close($connection);

  return $dbResult;
}

// Make bins using group by query

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
    $dbResult = dbQuery($start, $end, $gran, $timezone, $idps, $sps, $idpe, $spe);
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
      $dbResult = dbQuery($start, $end, $gran, $timezone, $idps, $sps, $idpe, $spe);
      $hist = histogram($gran, $start, $end, $timezoneOffset, $dbResult);
      outputArray($hist);
      echo "\n";
    }
    echo ']';
  }
}

function nBins($dt, $start, $end) {
  return ceil(($end - $start) / $dt);
}

function dateToBin($date, $dt, $start, $timezoneOffset) {
  $date1 = $date;
  $date = $date - ($date + $timezoneOffset) % $dt;
  $d = getdate($date);
  //error_log(date('c', $date1) .'  '.date('c', $date) .'  ' . (($date1 + 2*60*60) % $dt) );
  return floor(($date - $start) / $dt);
}

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

function checkNumberOfBins($nBins) {
  global $MAX_NUMBER_OF_BINS;
  if($nBins > $MAX_NUMBER_OF_BINS) {
    internalError("Too many bins $nBins. Reduce date range or increase granularity.");
  }
}

function internalError($errMsg) {
  $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
  header("$protocol 500 Internal Server Error");
  echo("ERR: $errMsg");
  exit(0);
}

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

function histBins($binBounds, $nBins, $dbResult) {
  $bins = array();

  for($i = 0; $i < $nBins; $i++) {
    $bins[$i] = 0;
  }

  $index = 0;
  while ($row = mysql_fetch_array($dbResult, MYSQL_NUM)) {
    error_log(print_r($row, true));
    $index = timestampToBin($row[0], $binBounds, $index, $nBins);
    $bins[$index] += $row[1];
  }
  return $bins;
}

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

function floorMonth($d) {
  $date = getdate($d);
  return mktime(0, 0, 0, $date['mon'], 1, $date['year']);
}

function ceilMonth($d) {
  $fd = floorMonth($d);
  return $d == $fd ? $fd : incMonth($fd);
}

function incMonth($d) {
  $date = getdate($d);
  if($date['mon'] == 12) {
    return mktime(0, 0, 0, 1, 1, $date['year'] + 1);
  }
  else {
    return mktime(0, 0, 0, $date['mon']+1,1, $date['year']);
  }
}

function floorYear($d) {
  $date = getdate($d);
  return mktime(0, 0, 0, 1, 1, $date['year']);
}

function ceilYear($d) {
  $fd = floorYear($d);
  return $d == $fd ? $fd : incYear($fd);
}

function incYear($d) {
  $date = getdate($d);
  return mktime(0, 0, 0, 1, 1, $date['year'] + 1);
}

?>