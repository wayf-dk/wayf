<?php
include '_init.php';

// Authentication
$auth = new \WAYF\SAML\Authentication();
if (!$auth->isAuthenticated()) {
    $auth->authenticate();
}

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

// Get Attribute quarry config
$attrquarry_config = \WAYF\Configuration::getConfig('config_attibutequarry.php');

// Process all services
$noconsentservices = array();
$consentservices = array();
foreach ($sps AS $entityid => $data) {
    // Restore the original attributes
    $consent->setAttribute($attributes);

    $spconsent = $consent->haveServiceConsent($entityid);

    if ($spconsent) {
        /**
         * Consent found
         *
         * Verify that consent is correct else delete it
         */
        // Run all configured AttributreQuarries
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
        $spconsent = $consent->haveFullConsent($entityid, $data['attributes']);
        
        if ($spconsent['consent']) {
            // Full consent found
            // Services with consent
            $consentservices[$entityid] = array(
                'name' => $data['name'][$_SESSION['lang']],
                'description' => $data['description'][$_SESSION['lang']],
                'consent' => $spconsent,
                'serviceid' => $consent->getServiceId($entityid),
            );
        } else {
            $logger->log(JAKOB_INFO, 'Delete consent : ' . $entityid);
            // Full consent not found
            // Services without consent
            $noconsentservices[$entityid] = array(
                'name' => $data['name'][$_SESSION['lang']],
                'description' => $data['description'][$_SESSION['lang']],
                'consent' => $spconsent,
                'serviceid' => $consent->getServiceId($entityid),
            );
            // Delete  not full consent
            $consent->removeConsent($entityid);
        }
    } else {
        // Services without consent
        $noconsentservices[$entityid] = array(
            'name' => $data['name'][$_SESSION['lang']],
            'description' => $data['description'][$_SESSION['lang']],
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
$data['trans'] = $t;
$data['lang'] = $_SESSION['lang'];

// Generate output
$template->setTemplate('consentadmin')->setData($data)->render();
