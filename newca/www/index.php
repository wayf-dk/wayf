<?php
include '_init.php';
echo "<pre>";


$sps = \WAYF\Configuration::getConfig('metadata/metadata-sp.php');

$attributes = $_SESSION['SAML'];

$consent = new \WAYF\Consent(array(
    'userid' => 'diller',
    'salt' => 'diller',
    'source' => 'diller',
    'destination' => 'diller',
    'attributes' => 'diller',
));

foreach ($sps AS $entityid => $data) {
    echo htmlentities($data['name']['da']) . "<br />";
    foreach($data['attributes'] AS $name) {
        echo "\t" . $name . "\n";
        if (isset($attributes[$name])) {
            foreach ($attributes[$name] AS $index => $value) {
                echo "\t\t" . $value . "\n";
            }
        }
    }
}


