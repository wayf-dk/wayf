<?php
/**
 * JAKOB
 *
 * @category   WAYF
 * @package    NEWCA
 * @subpackage Consent
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
class Consent {

    private $userid;
    private $salt;
    private $source;
    private $destination;
    private $attributes; 

    public function __construct(array $options)
    {
        foreach (get_class_vars(get_class($this)) AS $key => $val) {
            if (!isset($options[$key])) {
                throw new \WAYF\Exceptions\ConsentException($key . ' is not set');
            }
        }
        /*
        if (!isset($options['userid'])) {
            throw new \WAYF\Exception\ConsentException('User ID is mising');
        }
        $this->userid = $options['userid'];
        if (!isset($options['salt'])) {
            throw new \WAYF\Exception\ConsentException('Salt is mising');
        }
        $this->salt = $options['salt'];
        if (!isset($options['useris'])) {
            throw new \WAYF\Exception\ConsentException('User ID is mising');
        }
        $this->userid = $options['userid'];
        if (!isset($options['useris'])) {
            throw new \WAYF\Exception\ConsentException('User ID is mising');
        }
        $this->userid = $options['userid'];
        if (!isset($options['useris'])) {
            throw new \WAYF\Exception\ConsentException('User ID is mising');
        }
        $this->userid = $options['userid'];
        
        
        
        $userid, $salt, $source, $destination, $attributes)
         */
    }

    public static function getHashedUserID($userid, $source)
    {
        return hash('sha1', $userid . '|' . SimpleSAML_Utilities::getSecretSalt() . '|' . $source);
    }

    public static function getTargetedID($userid, $source, $destination)
    {
        return hash('sha1', $userid . '|' . SimpleSAML_Utilities::getSecretSalt() . '|' . $source . '|' . $destination);
    }

    public static function getAttributeHash($attributes, $includeValues = false)
    {
        $hashBase = null;
        if ($includeValues) {
            ksort($attributes);
            $hashBase = serialize($attributes);
        } else {
            $names = array_keys($attributes);
            sort($names);
            $hashBase = implode('|', $names);
        }
        return hash('sha1', $hashBase);
    }
}
