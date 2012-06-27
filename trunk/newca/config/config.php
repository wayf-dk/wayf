<?php
/**
 * NEWCA global configuration file
 *
 * @category   WAYF
 * @package    NEWCA
 * @author     Jacob Christiansen <jach@wayf.dk>
 * @copyright  Copyright (c) 2011 Jacob Christiansen, WAYF (http://www.wayf.dk)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 * @version    $Id: config.php 199 2012-01-19 12:14:01Z jach@wayf.dk $
 * @link       $URL: https://jakob.googlecode.com/svn/trunk/config/config.php $
 */
$config = array(
    // Logger configuration
    'logger' => array(
        'type' => 'File',  
        'options' => array('file' => 'newca.log'),
    ),
);
