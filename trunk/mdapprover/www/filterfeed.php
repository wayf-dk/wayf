<?php
// Bootstrap
include "_init.php";

// Get feed configuration
$config_feeds = \WAYF\Configuration::getConfig('config_feeds.php');

// Verify valid feed is requested
if (isset($_GET['feed']) && isset($config_feeds[$_GET['feed']])) {
    $feed = $_GET['feed'];
    $feed_config = $config_feeds[$_GET['feed']];
} else {
    header("HTTP/1.0 404 Not Found");
    exit();
}

// Init
$db  = new \WAYF\DB($config['database.filter']['dsn'], $config['database.filter']['user'], $config['database.filter']['password']);
$em = new \WAYF\EntityMapper($db, $feed);
$im = new \WAYF\IdentityMap();

$entities = $em->findAll();
foreach ($entities AS $entity) {
    $im->set($entity->entityid, $entity);
}

// Grab feed
$mdfetcher = new \WAYF\MDFetcher($feed_config);
$xml = $mdfetcher->grabFeed();

// Validate signature on feed
if (isset($feed_config['validate.signature']) && $feed_config['validate.signature']) {
    try {
        $ms = new \WAYF\MDSignature();
        $ms->verifySignature($xml, $feed_config['validate.certificate']);
    }
    catch (\Exception $e) {
        exit($e->getMessage());
    }
}

// Filter metadata feed
$mdp = new \WAYF\MDFilter($im);
$filteredxml = $mdp->filter($xml, $feed_config);

// Output filtered metadata
header('Content-type: application/xml');
echo $filteredxml;
