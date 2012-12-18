<?php
include "_init.php";
include "samlauth.php";

$config_feeds = \WAYF\Configuration::getConfig('config_feeds.php');

$template = new \WAYF\Template();
$template->setTemplate('listfeeds')->setData(array('config_feeds' => $config_feeds))->render();
