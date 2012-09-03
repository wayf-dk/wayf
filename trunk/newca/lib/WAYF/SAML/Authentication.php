<?php
/**
 * Authentication class for NEWCA
 */

/**
 * @namespace
 */
namespace WAYF\SAML;

class Authentication
{
    private $config;

    public function __construct() {
        $this->config = \WAYF\Configuration::getConfig();
    }

    public function isAuthenticated()
    {
        if(isset($_SESSION['SAML']) && (($_SESSION['SAML']['AuthTime']+$this->config['session.duration']) > time())) {
            return true;
        }
        return false;
    }

    public function authenticate($redirect = '/')
    {
        unset($_SESSION['SAML']);
        try {
            $sporto_config = \WAYF\Configuration::getConfig('config_sporto.php');
            $sporto = new \WAYF\SAML\SPorto($sporto_config);
            $_SESSION['SAML'] = $sporto->authenticate();
            $_SESSION['SAML']['AuthTime'] = time(); 
            header("Location: " . $redirect);
        } catch (\WAYF\Exceptions\SPorto\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
