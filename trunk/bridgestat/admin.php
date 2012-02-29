<?php

require_once('config.php');
require_once('sporto.php');
require_once('language.php');
require_once('prelude.php');

global $config;
global $language;

session_start();


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
  $lang = 'da';
  $eppn = 'fmma@itu.dk';
}

$L = $language[$lang];


if(isset($_GET['eid'])) {
  $entity = $_GET['eid'];
}
else {
  internalError("You must set eid");
}


$dbConnection = dbConnect();

$role = getRole($eppn, $entity);

if($role == 'admin') {

  if(isset($_GET['action'])) {
    if($_GET['action'] == 'add') {
      $toAddEppn = $_GET['eppn'];
      $toAddRole = $_GET['role'];
      $sql = "INSERT INTO access VALUES ('$toAddEppn', '$entity', '$toAddRole');";
      dbQuery($sql);
    }
    else if($_GET['action'] == 'del') {
      $toDelete = $_GET['arg'];
      $sql = "DELETE FROM access WHERE eppn = '$toDelete' AND eid = '$entity';";
      dbQuery($sql);
    }
  }

  $delegated = getDelegatedEntities($entity);
}
else {
  accessDenied("You do not have admin rights");
}

mysql_close($dbConnection);


?>

<html>
<head>
<script type = "text/javascript">
  function del(eppn) {
    document.getElementById('action').value = 'del';
    document.getElementById('arg').value = eppn;
    document.form.submit();
  }
</script>
</head>


<body>

<form name ="form">
<input type = 'hidden' name = 'eid' value = '<?echo $entity?>' />
<input type = 'hidden' id = 'action' name = 'action' value = 'add' />
<input type = 'hidden' id = 'arg' name = 'arg' value = '' />
<table>

<tr>
<th>
eduPersonPrincipalName
</th>
<th>
Role
</th>
</tr>
<?php
for($i = 0; $i < count($delegated); $i++) {
  $user = $delegated[$i];
  echo "<tr>";
  echo "<td>";
  echo $user[0];
  echo "</td>";
  echo "<td>";
  echo $user[1];
  echo "</td>";
  echo "<td>";
  echo "<button type='button' onclick = 'del(\"".$user[0]."\");'>";
  echo "Delete";
  echo "</button>";
  echo "</td>";
  echo "</tr>";
}
?>
<tr>
<td>
<input type = 'text' name = 'eppn' />
</td>
<td>
<select name = 'role'>
<option value = 'viewer'> viewer
<option value = 'admin'> admin
</select>
</td>
<td>
<input type = "submit" value = 'Add'/>
</td>
</tr>
</table>
</form>
</body>

</html>