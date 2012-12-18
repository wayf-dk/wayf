<?php
/**
 * Configuration
 *
 * @category   WAYF
 * @subpackage Configuration
 * @author     Jacob Christiansen <jach@wayf.dk>
 * @copyright  Copyright (c) 2011 Jacob Christiansen, WAYF (http://www.wayf.dk)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 * @version    $Id$
 * @link       $URL$
 */

/**
 * @namespace
 */
namespace WAYF;

/**
 * Configuration class
 *
 * Class for holding and processing simple configuration.
 *
 * @author Jacob Christiansen <jach@wayf.dk>
 */
class Configuration
{
    /**
     * Loads a parsed configuration
     *
     * @param  array $config Configuration
     * @return void
     */
    public static function getConfig($configfile = 'config.php')
    {
        $path = CONFIGROOT . DIRECTORY_SEPARATOR . $configfile;
        
        if (!file_exists($path)) {
            throw new \InvalidArgumentException($configfile . ' config file does not exists');
        }

        require $path;

        return $config;
    }
}
