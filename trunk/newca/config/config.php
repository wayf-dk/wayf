<?php
/**
 * NEWCA global configuration file
 *
 * @category   WAYF
 * @package    NEWCA
 * @author     Jacob Christiansen <jach@wayf.dk>
 * @copyright  Copyright (c) 2011 Jacob Christiansen, WAYF (http://www.wayf.dk)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 * @version    $Id$
 * @link       $URL$
 */
$config = array(
    // Session duration in seconds (1800 sec. = 30 min.)
    'session.duration' => 1800,

    // Database configuration
    'database' => array(
        'dsn'      => 'mysql:host=HOST;dbname=DATABASE',
        'username' => 'USERNAME',
        'password' => 'PASSWORD',
    ),

    // Logger configuration
    'logger' => array(
        'type' => 'File',  
        'options' => array('file' => 'newca.log'),
    ),

    // Consent configuration
    'consent.salt' => 'SECRETSALT',

    // Allowed languages
    'languages' => array('en', 'da'),
);
