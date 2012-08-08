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
namespace WAYF\AttributeQuarry;

/**
 * Configuration class
 *
 * Class for holding and processing simple configuration.
 *
 * @author Jacob Christiansen <jach@wayf.dk>
 */
//class TargetedId implements \WAYF\AttributeQuarry
class TargetedId
{
    private $salt;
    private $idp;
    private $userid;

    public function __construct(array $options)
    {
        $this->salt = $options['options']['salt'];
        $this->idp = $options['idp'];
        $this->userid = $options['attributes'][$options['options']['attribute']][0];
    }

    public function setup() {} 

    public function mine(array $options)
    {
        $uidData = 'uidhashbase' . $this->salt;
        $uidData .= strlen($this->idp) . ':' . $this->idp;
        $uidData .= strlen($options['sp']) . ':' . $options['sp'];
        $uidData .= strlen($this->userid) . ':' . $this->userid;
        $uidData .= $this->salt;

        return array(
            'eduPersonTargetedID' => array('WAYF-DK-' . hash('sha1', $uidData))
        );
    }
}
