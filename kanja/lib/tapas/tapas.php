<?
include('../lib/sporto.php');

session_start();

if(!isset($_SESSION['SAML'])) {
    $config = new config();
    $_SESSION['SAML'] = sporto($config);
}

$SAML = $_SESSION['SAML'];
$uid = $SAML['eduPersonPrincipalName'][0];
$iid = $SAML['schacHomeOrganization'][0];
$role = $SAML['eduPersonPrimaryAffiliation'][0];

$dbhandle = sqlite_open('../db/links.db', 0666, $error);
if (!$dbhandle) die ($error);

// Only let in people with access
$query = "SELECT COUNT(*) AS 'admin' FROM Tabs WHERE tabid IN (SELECT tabid FROM Access where (iid = '$iid' OR iid IS NULL) AND (role = '$role' OR role IS NULL) AND (uid = '$uid' OR uid IS NULL) AND tabid = 'tapas');";

$result = sqlite_query($dbhandle, $query);
if (!$result) die("Cannot execute query.");

$data = sqlite_fetch_all($result, SQLITE_ASSOC);

// Do not show admin panel if user do not have access
if (intval($data[0]['admin']) < 1) {
    die('You do not have access to admin TAPAS.');
}

$url = isset($_POST['url']) ? $_POST['url'] : '';
$name = isset($_POST['name']) ? $_POST['name'] : '';
$id = isset($_POST['id']) ? $_POST['id'] : '';
$action = isset($_POST['action']) ? $_POST['action'] : '';
$arg = isset($_POST['arg']) ? $_POST['arg'] : '';
$iid = isset($_POST['iid']) ? $_POST['iid'] : '*';
$role = isset($_POST['role']) ? $_POST['role'] : '*';
$uid = isset($_POST['uid']) ? $_POST['uid'] : '*';
$tabid = isset($_POST['tabid']) ? $_POST['tabid'] : '';

$err = false;
$msg = '';

if($action == 'add') {
	$query = "INSERT INTO Tabs VALUES ('$id', '$name', '$url')";
	$result = sqlite_query($dbhandle, $query);
    var_dump(sqlite_error_string(sqlite_last_error($dbhandle)));
	if (!$result) {
		$err = true;
	}
}
else if($action == 'del') {
	$query = "DELETE FROM Tabs Where tabid = '$arg'";
	$result = sqlite_query($dbhandle, $query);
	if (!$result) {
		$err = true;
	}
}
else if($action == 'addAccess') {
	$query = str_replace("'*'", "NULL", "INSERT INTO Access VALUES ('$iid', '$role', '$uid', '$tabid');");
	$result = sqlite_query($dbhandle, $query);
	if (!$result) {
		$err = true;
	}
}
else if($action == 'delAcces') {
	$query = "DELETE FROM Access Where ROWID = '$arg'";
	$result = sqlite_query($dbhandle, $query);
	if (!$result) {
		$err = true;
	}
}

$query = "SELECT * FROM Tabs";
$result = sqlite_query($dbhandle, $query);
if (!$result) die("Cannot execute query.");

$tapas = sqlite_fetch_all($result, SQLITE_NUM); 

$query = "SELECT *, ROWID FROM Access";
$result = sqlite_query($dbhandle, $query);
if (!$result) die("Cannot execute query.");

$access = sqlite_fetch_all($result, SQLITE_NUM); 

sqlite_close($dbhandle);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <script type="text/javascript" src="js/jquery-1.5.1.min.js" ></script>	
	<script type="text/javascript">
		function del(id) {
			$('#action').val('del');
			$('#arg').val(id);
		}
		
		function add() {
			$('#action').val('add');
		}
		function delAccess(rowid) {
			$('#action').val('delAcces');
			$('#arg').val(rowid);
		}
		
		function addAccess() {
			$('#action').val('addAccess');
		}
	</script>
</head>
<body>

<form method="post">
<input type="hidden" name="action" id="action" />
<input type="hidden" name="arg" id="arg" />
<h3> Tabs </h3>
<table>
	<tr>
		<th> ID </th>
		<th> Name </th>
		<th> URL </th>
		<th> Delete </th>
	</tr>
	<?foreach($tapas as $tab) {?>
	<tr>
		<td> <?echo $tab[0];?> </td>
		<td> <?echo $tab[1];?> </td>
		<td> <?echo $tab[2];?> </td>
		<td> <button onClick="del('<?echo $tab[0];?>');"> Delete </button> </td>
	</tr>
	<?}?>
	<tr>
		<td> <input name="id" value="<?echo $id;?>" /> </td>
		<td> <input name="name" value="<?echo $name;?>" /> </td>
		<td> <input name="url" value="<?echo $url;?>" /> </td>
		<td> <button onClick="add();"> Add </button> </td>
	</tr>
</table>

<h3> Access Rights </h3>
<table>
	<tr>
		<th> Institution </th>
		<th> Role </th>
		<th> Username </th>
		<th> Tab ID </th>
		<th> Delete </th>
	</tr>
	<?foreach($access as $row) {?>
	<tr>
		<td> <?echo $row[0] == '' ? '*' : $row[0];?> </td>
		<td> <?echo $row[1] == '' ? '*' : $row[1];?> </td>
		<td> <?echo $row[2] == '' ? '*' : $row[2];?> </td>
		<td> <?echo $row[3];?> </td>
		<td> <button onClick="delAccess(<?echo $row[4];?>);"> Delete </button> </td>
	</tr>
	<?}?>
	<tr>
		<td> <input name="iid" value="<?echo $iid;?>" /> </td>
		<td> <input name="role" value="<?echo $role;?>" /> </td>
		<td> <input name="uid" value="<?echo $uid;?>" /> </td>
		<td> <input name="tabid" value="<?echo $tabid;?>" /> </td>
		<td> <button onClick="addAccess();"> Add </button> </td>
	</tr>
</table>
</form>
<p>
	<?echo $msg;?>
</p>
</body>
