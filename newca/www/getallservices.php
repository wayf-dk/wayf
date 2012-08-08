<?php
include '_init.php';

// Grab attributes from authentication
$attributes = $_SESSION['SAML']['attributes'];

// Grab authentication IdP
$document = new \DOMDocument();
$document->loadXML($_SESSION['SAML']['response']);
$xp = new \DomXPath($document);
$xp->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');
$xp->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
$idp = $xp->query('/samlp:Response/saml:Assertion/saml:AuthnStatement/saml:AuthnContext/saml:AuthenticatingAuthority')->item(0)->textContent;

// Connect to consent database
$db = new \WAYF\DB($config['database']['dsn'], $config['database']['username'], $config['database']['password']);

// Consnet
$consent = new \WAYF\Consent(
    array(
        'userid' => $attributes['eduPersonPrincipalName'][0],
        'salt' => $config['consent.salt'],
        'source' => 'saml20-idp-remote|' . $idp,
        'attributes' => $attributes,
        'database' => $db,
    )
);

// Grab the list of SPs
$sps = \WAYF\Configuration::getConfig('metadata/metadata-sp.php');

// Process all services
$noconsentservices = array();
$consentservices = array();
foreach ($sps AS $entityid => $data) {
    // Restore the original attributes
    $consent->setAttribute($attributes);

    $spconsent = $consent->haveServiceConsent($entityid);

    if ($spconsent) {
        // Services with consent
        $consentservices[$entityid] = array(
            'name' => $data['name']['da'],
            'description' => $data['description']['da'],
            'consent' => $spconsent,
            'serviceid' => $consent->getServiceId($entityid),
        );
    } else {
        // Services without consent
        $noconsentservices[$entityid] = array(
            'name' => $data['name']['da'],
            'description' => $data['description']['da'],
            'consent' => $spconsent,
            'serviceid' => $consent->getServiceId($entityid),
        );
    }
}

function cmpService($a, $b) {
    return strcasecmp($a['name'], $b['name']);
}

uasort($consentservices, 'cmpService');
uasort($noconsentservices, 'cmpService');

// Set data to template
$data['consentservice'] = $consentservices;
$data['noconsentservice'] = $noconsentservices;

// Generate output
$template->setTemplate('consentadmin')->setData($data)->render();
