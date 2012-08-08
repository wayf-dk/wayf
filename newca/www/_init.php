<?php
// Define the root path of NEWCA 
define('ROOT', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
define('CONFIGROOT', ROOT . 'config' . DIRECTORY_SEPARATOR);
define('LOGROOT', ROOT . 'log' . DIRECTORY_SEPARATOR);

// System status
define('STATUS', 'development');

// Set error logging
ini_set('log_errors', TRUE);
ini_set('error_reporting', -1);

// Disable magic_quotes_*
ini_set('magic_quotes_gpc', 'off');
ini_set('magic_quotes_runtime', 'off');
ini_set('magic_quotes_sybase', 'off');

// Check what status we have
switch (STATUS) {
    case 'production': {
        ini_set('display_errors', 'off');
        ini_set('display_startup_errors', FALSE);
        break;
    }
    case 'development': {
        // Display all errors in development
        ini_set('display_errors', 'on');
        ini_set('display_startup_errors', TRUE);
        break;
    }
    default: {
        die('Application status not set. Terminating execution.');
    }
}

// Include the autoloader
include ROOT . 'lib' . DIRECTORY_SEPARATOR . 'WAYF' . DIRECTORY_SEPARATOR . 'AutoLoader.php';

// Register all classes under WAYF
$classLoader = new \WAYF\AutoLoader('WAYF', ROOT . 'lib');
$classLoader->register();

// Get SPorto configuration
$sporto_config = \WAYF\Configuration::getConfig('config_sporto.php');
$config = \WAYF\Configuration::getConfig();

// Make a template object available
$template = new \WAYF\Template();

try {
    $logger = \WAYF\LoggerFactory::createInstance($config['logger']);
} catch (\WAYF\LoggerException $e) {
    $data = array('errortitle' => 'Logger could not be initiated', 'errormsg' => $e->getMessage());
    $template->setTemplate('error')->setData($data)->render();
}

// Set exception handler
$exceptionHandler = new \WAYF\ExceptionHandler();
$exceptionHandler->setLogger($logger);
set_exception_handler(array($exceptionHandler, 'handleException'));

// Set error handler
$errorHandler = new \WAYF\ErrorHandler();
set_error_handler(array($errorHandler, 'handleError'));

register_shutdown_function('shutdown');

function shutdown() {
    $e = error_get_last();
    if (!is_null($e)) {
        // Instiansiate all objects to be able to log FATAL errors
        $jakob_config = \WAYF\Configuration::getConfig();
        $logger = \WAYF\LoggerFactory::createInstance($config['logger']);
        $exceptionHandler = new \WAYF\ExceptionHandler();
        $exceptionHandler->setLogger($logger);
        $exception = new \ErrorException($e['message'], 0, $e['type'], $e['file'], $e['line']);
        $exceptionHandler->handleException($exception);
    }
}

// Start session
session_start();

// Protection against session fixation attacks
session_regenerate_id(true);

// Authenticate user (Session duration is 30 min. hard coded)
if(!isset($_SESSION['SAML']) || (($_SESSION['SAML']['AuthTime']+1800) < time())) {
    unset($_SESSION['SAML']);
    try {
        $sporto = new \WAYF\SAML\SPorto($sporto_config);
        $_SESSION['SAML'] = $sporto->authenticate();
        $_SESSION['SAML']['AuthTime'] = time(); 
    } catch (Exception $e) {
        echo $e->getMessage();
        exit;
    }
}

// Handle translation
if (isset($_REQUEST['lang']) && in_array($_REQUEST['lang'], $config['languages'])) {
    $_SESSION['lang'] = $_REQUEST['lang'];
} else if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}
$t = new \WAYF\Translation($_SESSION['lang']);


// For debug purpose ONLY. Delete in production
function debug($var)
{
    echo "<pre>";
    var_dump($var);
    echo "</pre>";
}
