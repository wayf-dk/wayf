<?php
require_once('config.php');
require_once('sporto.php');
require_once('language.php');
require_once('prelude.php');

global $config;
global $language;

session_start();

//Perform authentication and get SAML attributes
if($config['requireAuth']) {
  if(!isset($_SESSION['SAML'])) {
    $sportoConfig = new config();
    $_SESSION['SAML'] = sporto($sportoConfig);
    $saml = $_SESSION['SAML'];
  }
  $lang = getAttribute('preferredLanguage', 'en', array('en', 'da'));
  $eppn = getAttribute('eduPersonPrincipalName', false, false);
}
else {
  $lang = 'en';
  $eppn = 'fmma@itu.dk';
}

$L = $language[$lang];


//Get GET variables
if(isset($_GET['idp'])) {
  $provider = $_GET['idp'];
  $idpMode = true;
  $mainProviderStringShort = 'idp';
  $otherProviderStringShort = 'sp';
}
else if(isset($_GET['sp'])) {
  $provider = $_GET['sp'];
  $idpMode = false;
  $mainProviderStringShort = 'sp';
  $otherProviderStringShort = 'idp';
}
else {

  $idpMode = true;
  $mainProviderStringShort = 'idp';
  $otherProviderStringShort = 'sp';

  $provider = false;
}

$sameProviders = getSameProviders($lang, $eppn);
if(!$provider)
  $provider = $sameProviders[0]['id'];
$role = getRole($eppn, $provider);
$otherProviders = getOtherProviders($lang, $eppn, $provider);
$dateRange = getMaxDateRange($provider);
dbClose();

function getMaxDateRange($provider) {
  $sql = 'SELECT MIN(UNIX_TIMESTAMP(date)), MAX(UNIX_TIMESTAMP(date)) FROM log';
  $dbResult = dbQuery($sql);

  $row = mysql_fetch_array($dbResult, MYSQL_NUM);
  if(!$row) {
    internalError("Failed to retrieve date range: ".mysql_error());
  }
  else{
    $s = $row[0];
    $e = $row[1];
    $ret = array($s, $e);
    return $ret;
  }
}

function getSameProviders($lang, $eppn) {
  global $mainProviderStringShort;
  $p = $mainProviderStringShort;
  $sql = "SELECT l.$p, e.$lang FROM log l JOIN access a on a.eid = l.$p LEFT JOIN entitytoname e ON l.$p = e.entityid WHERE a.eppn = '$eppn' GROUP BY l.$p";

  $dbResult = dbQuery($sql);

  $ret = array();
  while ($row = mysql_fetch_array($dbResult, MYSQL_NUM)) {
    if(is_null($row[1]))
	$row[1] = $row[0];

    $ret[] = array('id' => $row[0], 'name' => $row[1]);
  }
  return $ret;
}

function getOtherProviders($lang, $eppn, $provider) {
  global $otherProviderStringShort, $mainProviderStringShort;
  $sp = $otherProviderStringShort;
  $idp = $mainProviderStringShort;
  $sql = "SELECT l.$sp, COUNT(*), e.$lang, a.role FROM log l LEFT JOIN access a ON l.$sp = a.eid LEFT JOIN entitytoname e ON l.$sp = e.entityid GROUP BY $sp ORDER BY COUNT(*) DESC;";
  //  $sql = "SELECT l.$sp, COUNT(*), e.$lang FROM log l LEFT JOIN access a on a.eid = l.$sp LEFT JOIN entitytoname e ON l.$sp = e.entityid WHERE l.$idp='$provider' AND a.eppn = '$eppn' GROUP BY l.$sp ORDER BY COUNT(*) DESC";

  $dbResult = dbQuery($sql);

  $ret = array();
  while ($row = mysql_fetch_array($dbResult, MYSQL_NUM)) {
    if(is_null($row[2]))
      $row[2] = $row[0];
    $ret[] = array('id' => $row[0], 'own' => isset($row[3]), 'count' => $row[1], 'name' => $row[2] );
  }
  return $ret;
}

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>

  <head>
    <link rel="stylesheet" type="text/css" href="css/style.css"/>

    <script type="text/javascript" src="js/d3.js"></script>
    <script type="text/javascript" src="js/d3.time.js"></script>
    <script type="text/javascript" src="js/d3.layout.js"></script>
    <script type="text/javascript" src="js/db.js"></script>
    <script type="text/javascript" src="js/graph.js"></script>
    <script type="text/javascript" src="js/jquery.js"></script>
    <script type="text/javascript" src="js/datepicker.js"></script>
    <script type="text/javascript" src="js/eye.js"></script>
    <script type="text/javascript" src="js/utils.js"></script>
    <script type="text/javascript" src="js/detect_timezone.js"></script>
    <script type="text/javascript" src="js/stat.js"></script>
    <script type="text/javascript">

var idpMode = <?echo $idpMode ? 'true' : 'false';?>;
//TODO does our PHP support JSON_HEX_APOS
var otherProviders = JSON.parse('<?echo json_encode($otherProviders,  JSON_HEX_APOS);?>');
var sameProviders = JSON.parse('<?echo json_encode($sameProviders,  JSON_HEX_APOS);?>');
var mainProvider = '<?echo $provider?>';
var role = '<?echo $role?>';
var delegated = JSON.parse('<?echo json_encode($delegated,  JSON_HEX_APOS);?>');
var start = <?echo $dateRange[0]?>;
var end = <?echo $dateRange[1]?>;

var CONST = {
  //Moth view constants
providerBoxW : 350
, wayfBoxW : 100
, providerBoxH : 25
, boxCornerRadius : 3
, colGap : 200
, rowGap : 3
, wayfY : 5 
, entitiesToShow : 10
, boxTextY : 17
, boxTextXSame : 5
, boxTextXOthers : 25
, checkboxSize : 11
, checkSize : 20
, checkOffsetX : -2
, checkOffsetY : -8

// Graphs constants
, graphH : 400
, graphW : 1000
, graphXMarg : 80
, graphYMargFactor : 8
, signatureLineW : 50
, signatureLineH : 2
, signatureW : 200
, signatureSpacing : 20
, transitionDuration : 1000
};

    </script>
    
  </head>
  
  <body onload="main();">
  <h1> WAYF Statitistics </h1>
    <div id = "toolsDiv">
  <table id = "toolBar">
  <tr>
  <td class = "toolTD" >
        <div id="widget">
          <div id="widgetField">
            <span> </span>
	    <a href="#">Select date range</a>
	  </div>
	  <div id="widgetCalendar">
	  </div>
	</div>
    <div>
  </td>
  <td class = "toolTD" style='padding-right: 5px;'>
  <input id="gh" type="radio" name="gran" value ="h" onclick="granClicked(this);" checked = "checked"/> <?echo $L['Hours'];?>
    <input id="gD" type="radio" name="gran" value ="D" onclick="granClicked(this);" /> <?echo $L['Days'];?>
    <input id="gM" type="radio" name="gran" value ="M" onclick="granClicked(this);" /> <?echo $L['Months'];?>
    <input id="gY" type="radio" name="gran" value ="Y" onclick="granClicked(this);" /> <?echo $L['Years'];?>
    </td>
    <td  class = "toolTD" style='padding-right: 5px;'>
    <input id="tl" type="radio" name="graphType" value ="l" onclick="graphTypeClicked(this);" checked = "checked"/> <?echo $L['Lines'];?>
    <input id="ta" type="radio" name="graphType" value ="a" onclick="graphTypeClicked(this);" /> <?echo $L['Areas'];?>
    <input id="tgb" type="radio" name="graphType" value ="gb" onclick="graphTypeClicked(this);"/> <?echo $L['Bars'];?>
    <input id="tsa" type="radio" name="graphType" value ="sa" onclick="graphTypeClicked(this);"/> <?echo $L['StackedAreas'];?>
    <input id="tsb" type="radio" name="graphType" value ="sb" onclick="graphTypeClicked(this);" /> <?echo $L['StackedBars'];?>
    </td>
    <td class = "toolTD" style='padding-right: 5px;'>
    <input id="gbl" type="radio" name="groupBy" value ="logins" onclick="groupByClicked(this);" checked="checked" /> Logins
    <input id="gbu" type="radio" name="groupBy" value ="users" onclick="groupByClicked(this);" disabled /> Users
    </td>
    </tr>
    </table>
    </div>
    </div>
    <div id='graphDiv'>
    </div>
    <div id='mothDiv'>
    </div>
  </body>
  
</html>
