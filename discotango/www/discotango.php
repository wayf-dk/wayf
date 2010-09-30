<?php

/**
 * Builtin IdP discovery service.
 */

$discoHandler = new sspmod_discotango_Discotango(array('saml20-idp-remote', 'shib13-idp-remote'), 'saml');
$discoHandler->handleRequest();
