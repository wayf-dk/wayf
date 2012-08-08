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
class TargetedId {

    private $salt;
    private $idp;
    private $userid;

    public function __construct($salt, $idp, $userid) {
        $this->salt = $salt;
        $this->idp = $idp;
        $this->userid = $userid;
    }

    public function calculateId($sp) {
        // Targeted id code
        $uidData = 'uidhashbase' . $this->salt;
        $uidData .= strlen($this->idp) . ':' . $this->idp;
        $uidData .= strlen($sp) . ':' . $sp;
        $uidData .= strlen($this->userid) . ':' . $this->userid;
        $uidData .= $this->salt;

        return 'WAYF-DK-' . hash('sha1', $uidData);
    }
}
