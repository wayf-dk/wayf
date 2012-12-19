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
$mdfilter = new \WAYF\MDFilter($im, $feed_config);
$filteredxml = $mdfilter->filter($xml);

if (!empty($mdfilter->changedentities)) {
    $message = "The following entities have changed since they was last approved:\n\n";
    foreach ($mdfilter->changedentities AS $ce) {
        $message .= " - {$ce->entityid} : {$ce->name}\n";
    }
    $event = new \WAYF\Event();
    $event->message = $message;
    $event->title = "Entities changed";
    $event->user = "SYSTEM";
    $event->time = date('c');
    $ml = new \WAYF\MailLogger($feed_config);
    $ml->log($event);
}

// Output filtered metadata
header('Content-type: application/xml');
echo $filteredxml;
