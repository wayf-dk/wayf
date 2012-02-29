<?php
function accessDenied($errMsg) {
  $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
  header("$protocol 401 Access Denied");
  echo("Access Denied: $errMsg");
  exit(0);
}

function internalError($errMsg) {
  $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
  header("$protocol 500 Internal Server Error");
  echo("ERR: $errMsg");
  exit(0);
}


function logSQL($sql) {
  $fh = fopen('/var/www/stat/log/sql.log', 'a');

  fwrite($fh, $sql);
  fwrite($fh, "\n\n");

  fclose($fh);
}

function dbConnect() {
  global $config;

  $connection = mysql_connect($config['dbHost'], $config['dbUser'], $config['dbPwd']);
  if (!$connection)  {
    internalError("Failed to connect to database: ".mysql_error());
  }

  mysql_select_db($config['dbName'], $connection);

  return $connection;
}

function dbClose() {
  global $dbConnection;
  
  if($dbConnection)
    mysql_close($dbConnection);
}

function dbQuery($sql) {

  //TODO sanitize sql.
  // Use PDO, prepared statements

  global $dbConnection;

  if(!$dbConnection)
    dbConnect();

  logSQL($sql);

  $dbResult = mysql_query($sql);
  if($dbResult) {
    return $dbResult;
  }
  else {
    internalError("Database query failed. Query: $sql \n mysql_error(): " . mysql_error());
  }
}



function getAttribute($key, $default, $allowedValues) {
  $saml = $_SESSION['SAML'];
  if(isset($saml[$key])) {
    $ret = $saml[$key];
    if($allowedValues) {
      if(in_array($ret, $allowedValues)) {
	return $ret;
      }
      else {
	if($default) {
	  return $default;
	}
	else {
	  internalError("Attribute value not allowed $key => $value");
	}
      }
    }
    else {
      return $ret;
    }
  }
  if($default) {
    return $default;
  }
  else {
    internalError("Missing attribute " . $key);
  }
}

function getRole($eppn, $provider) {

  $sql = "SELECT role FROM access WHERE eppn = '$eppn' AND eid = '$provider'";
  $dbResult = dbQuery($sql);
  
  $row = mysql_fetch_array($dbResult, MYSQL_NUM);
  if(!$row) {
    accessDenied("You are not authorized to view this entity.");
  }
  else {
    return $row[0];
  }
}

function getDelegatedEntities($entity) {
  $sql = "SELECT eppn, role FROM access WHERE eid = '$entity'";
  $dbResult = dbQuery($sql);

  $ret = array();
  while($row = mysql_fetch_array($dbResult, MYSQL_NUM)) {
    $ret[] = $row;
  }
  return $ret;
}


?>