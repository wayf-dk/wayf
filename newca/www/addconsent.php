<?php
include('_init.php');

// Get input parameter
// @TODO Sanitize
$entityid = $_GET['id'];

// Grab attributes from authentication
$attributes = $_SESSION['SAML']['attributes'];

// Grab authentication IdP
$document = new \DOMDocument();
$document->loadXML($_SESSION['SAML']['response']);
$xp = new \DomXPath($document);
$xp->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');
$xp->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
$idp = $xp->query('/samlp:Response/saml:Assertion/saml:AuthnStatement/saml:AuthnContext/saml:AuthenticatingAuthority')->item(0)->textContent;

// Grab SP metadata
$sps = \WAYF\Configuration::getConfig('metadata/metadata-sp.php');
if (!isset($sps[$entityid])) {
    header('HTTP/1.0 404 Not Found');
    die();
}
$data = $sps[$entityid];

// Connect to consent database
$db = new \WAYF\DB($config['database']['dsn'], $config['database']['username'], $config['database']['password']);

$consent = new \WAYF\Consent(
    array(
        'userid' => $attributes['eduPersonPrincipalName'][0],
        'salt' => $config['consent.salt'],
        'source' => 'saml20-idp-remote|' . $idp,
        'attributes' => $attributes,
        'database' => $db,
    )
);

// Run all configured AttributreQuarries
$attrquarry_config = \WAYF\Configuration::getConfig('config_attibutequarry.php');
foreach ($attrquarry_config AS $aqid => $aqconfig) {
    // Create AttributeQuarry object
    $classname = 'WAYF\\AttributeQuarry\\' . $aqconfig['class'];
    $aq = new $classname(
        array(
            'options' => $aqconfig['options'],
            'idp' => $idp, 
            'attributes' => $attributes,
        )
    );

    // Run setup
    $aq->setup();

    // Run AttributeQuarry
    $res = $aq->mine(array(
        'sp' => $entityid,
        'attributes' => $data['attributes'], 
    ));

    // Only set new attributes
    if (!is_null($res)) {
        $consent->setAttribute($res);
    }

    // Destroy AttributeQuarry ect to prevent memory leak
    unset($aq);
} 

// Check consent
$spconsent = $consent->addConsent($entityid, $data['attributes']);


$result = array();
$result['entityid'] = $entityid;
$result['serviceid'] = $consent->getServiceId($entityid);
if ($spconsent) {
    $result['success'] = true;
} else {
    $result['success'] = false;
}

echo json_encode($result);
