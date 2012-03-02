<?php

//
// accessDenied
//
// Halt script and respond with an access denied.
//
// INPUT
//   $errMsg - An error messgage (string.
//
// OUTPUT
//   Nothing.
function accessDenied($errMsg) {
  $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
  header("$protocol 401 Access Denied");
  echo("Access Denied: $errMsg");
  exit(0);
}

//
// internalError
//
// Halt script and respond with an internal error.
//
// INPUT
//   $errMsg - An error messgage (string.
//
// OUTPUT
//   Nothing.
function internalError($errMsg) {
  $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
  header("$protocol 500 Internal Server Error");
  echo("ERR: $errMsg");
  exit(0);
}

//
// logSQL
//
// Log an sql query to the local file /var/www/stat/log/sql.log.
//
// INPUT
//   $sql - The SQL (string)
//
// OUTPUT
//   Nothing.
function logSQL($sql) {
  $fh = fopen('/var/www/stat/log/sql.log', 'a');

  if($fh) {

    fwrite($fh, $sql);
    fwrite($fh, "\n\n");
    
    fclose($fh);
  }
}

//
// dbConnect
//
// Creates a connection to a MySQL database using the configuraion defined in
// config.php. Stores a handle to the connection in the global variable
// $dbConnection.
//
function dbConnect() {
  global $config, $dbConnection;

  $dbConnection = mysql_connect($config['dbHost'], $config['dbUser'], $config['dbPwd']);
  if (!$dbConnection)  {
    internalError("Failed to connect to database: ".mysql_error());
  }

  mysql_select_db($config['dbName'], $dbConnection);
}

//
// Closes the MySQL connection refered to by the global variable
// $dbConnection, but only if it has been opened.
//
function dbClose() {
  global $dbConnection;
  
  if($dbConnection)
    mysql_close($dbConnection);
}

//
// Perform a SQL query. If no connection to a database exist, try to establish
// a connection using dbConnect(). The SQL is logged. This function may result
// in an internal error.
//
// INPUT
//   $sql - The query (string).
//
// OUTPUT
//   A handle to the result (return type of mysql_query)
//
function dbQuery($sql) {

  //TODO sanitize sql.
  // Use PDO, prepared statements

  global $dbConnection;

  if(!$dbConnection){
    error_log("connect!");
    dbConnect();
  }

  logSQL($sql);

  $dbResult = mysql_query($sql);
  if($dbResult) {
    return $dbResult;
  }
  else {
    internalError("Database query failed. Query: $sql \n mysql_error(): " . mysql_error());
  }
}

//
// getAttribute
//
// Gets a SAML attribute value from the session.
//
// INPUT
//   $key - The name of the attribute such as 'eduPersonPrincipalName' (string).
//   $default - If set, this value is returned if the attribute is not present, or
//              if it is not found amongst the allowed values (string).
//   $allowedValues - A list of allowed values (list of string). If the attriubte is
//                    not found in this list and no default value is set, this function
//                    will result in an internal error.
//
// OUTPUT
//   A SAML attribute value (string)
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

//
// Query the database for the role of an eduPerson for a given entity.
//
// INPUT
//   $eppn - The eduPersonPrincipalName (string).
//   $provider - The entity id of the entity (string).
//
// OUTPUT
//   The role ('viewer' or 'admin').
// 
function getRole($eppn, $provider) {

  $sql = <<<EOD
    SELECT role 
    FROM access
    WHERE eppn = '$eppn' AND eid = '$provider'
EOD;

  $dbResult = dbQuery($sql);
  
  $row = mysql_fetch_array($dbResult, MYSQL_NUM);
  if(!$row) {
    accessDenied("You are not authorized to view this entity.");
  }
  else {
    return $row[0];
  }
}

//
// Query the database for a list of users and roles delegeted to view
// a given entity.
//
// INPUT
//   $entity - The entity id of the entity (string).
//
// OUTPUT
//   A list where each element is a two-element list containing
//   two strings where the first is an eduPersonPrincipalName
//   and the second is a role ('admin' or 'viewer').
// 
function getDelegatedEntities($entity) {
  $sql = <<<EOD
    SELECT eppn, role
    FROM access
    WHERE eid = '$entity'
EOD;
  $dbResult = dbQuery($sql);

  $ret = array();
  while($row = mysql_fetch_array($dbResult, MYSQL_NUM)) {
    $ret[] = $row;
  }
  return $ret;
}


?>