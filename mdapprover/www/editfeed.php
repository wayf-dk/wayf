<?php
include "_init.php";
include "samlauth.php";

$config_feeds = \WAYF\Configuration::getConfig('config_feeds.php');

// Grab feed
if (isset($_GET['feed']) && isset($config_feeds[$_GET['feed']])) {
    $_SESSION['feed'] = $_GET['feed'];
} else if (!isset($_SESSION['feed'])) {
    exit('fid not set');
}

$db  = new \WAYF\DB($config['database.admin']['dsn'], $config['database.admin']['user'], $config['database.admin']['password']);
$em  = new \WAYF\EntityMapper($db, $_SESSION['feed']);
$im  = new \WAYF\IdentityMap();
$mdp = new \WAYF\MDParser();

$mdurl = $config_feeds[$_SESSION['feed']]['feedurl'];
$entities = $mdp->parse($mdurl);

$accepted = array();
$notaccepted = array();

foreach ($entities AS $entity) {
    $im->set($entity->entityid, $entity);

    $tmp = $em->getByEntityId($entity->entityid);

    if (empty($tmp)) {
        $notaccepted[] = $entity;
    } else {
        $accepted[$tmp[0]->entityid]['entity'] = $tmp[0];
        $accepted[$tmp[0]->entityid]['same'] = $tmp[0]->isEquivalent($entity);
    }
}

// Save loaded data for use with AJAX calls
$_SESSION['im'] = serialize($im);

// Output
$template                    = new \WAYF\Template();
$templatedata['accepted']    = $accepted;
$templatedata['notaccepted'] = $notaccepted;
$templatedata['mdurl']       = $mdurl;
$templatedata['attrMap']     = \WAYF\Configuration::getConfig('oid2name.php');

$template->setTemplate('editfeed')->setData($templatedata)->render();
