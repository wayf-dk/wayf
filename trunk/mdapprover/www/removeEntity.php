<?php
include "_init.php";
include "samlauth.php";

$im = unserialize($_SESSION['im']);

$db  = new \WAYF\DB($config['database.admin']['dsn'], $config['database.admin']['user'], $config['database.admin']['password']);
$em = new \WAYF\EntityMapper($db, $_SESSION['feed']);


$eid = null;
if (isset($_POST['eid'])) {
    $eid = $_POST['eid'];
} else {
    echo json_encode(array('status' => 'fail', 'msg' => 'eid not set'));
}

if (!is_null($eid)) {
    $entityid = base64_decode($eid);

    $em->deleteByEntityId($entityid);
    
    if ($im->hasId($entityid)) {
        $entity = $im->getobject($entityid);

        echo json_encode($entity);
        exit;
    }
}
echo json_encode(array('status' => 'fail', 'msg' => 'Unknown error'));
