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
        'dsn'      => 'mysql:host=test.wayf.dk;dbname=jach_db',
        'username' => 'jach',
        'password' => 'Jacob82NG',
    ),

    // Logger configuration
    'logger' => array(
        'type' => 'File',  
        'options' => array('file' => 'newca.log'),
    ),

    // Consent configuration
    'consent.salt' => 'defaultsdfsdfsecretsalt',

    // Allowed languages
    'languages' => array('en', 'da'),
);
