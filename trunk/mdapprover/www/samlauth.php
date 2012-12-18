<?php
// Authentication
$auth = new \WAYF\SAML\Authentication();

if (!$auth->isAuthenticated()) {
    $auth->authenticate();
}
// Grab attributes from authentication
$attributes = $_SESSION['SAML']['attributes'];
$authUser = $attributes['eduPersonPrincipalName'];

$users = \WAYF\Configuration::getConfig('config_users.php');
if (count(array_intersect($authUser, $users)) != 1) {
    exit('NOOOO! Your not allowed in...');
}
$_SESSION['SAML']['AuthUser'] = $authUser[0];
