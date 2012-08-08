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
    private $attributes; 
    private $database;
    private $consents = null;

    public function __construct(array $options)
    {
        $this->userid     = $options['userid'];
        $this->salt       = $options['salt'];
        $this->source     = $options['source'];
        $this->attributes = $options['attributes']; 
        $this->database   = $options['database'];
    }

    private function fetchAllUserConsents()
    {
        // Grab all user consents from database
        $query = "SELECT * FROM `consent` WHERE `hashed_user_id` = :user;";
        $params = array(
            'user' => $this->getHashedUserID(),
        );
        try {
            $consents = $this->database->fetchAll($query, $params);
        } catch (\PDOException $e) {
            throw new \WAYF\Exceptions\ConsentException('Consents could not be loaded from the database'); 
        }

        $this->consents = array();
        foreach ($consents AS $consent) {
            $this->consents[$consent->service_id] = $consent;
        }
    }
    
    public function haveServiceConsent($destination)
    {
        if (is_null($this->consents) || !is_array($this->consents)) {
            $this->fetchAllUserConsents();
        }

        $service_id = $this->getServiceId($destination);

        if (isset($this->consents[$service_id])) {
            return $this->consents[$service_id];
        }

        return false;
    }

    public function haveFullConsent($destination, array $subset)
    { 
        $hashAttributes = array();
        foreach ($subset AS $attr) {
            if (isset($this->attributes[$attr])) {
                $hashAttributes[$attr] = $this->attributes[$attr];
            }
        }

        $query = "SELECT * FROM `consent` WHERE `hashed_user_id` = :user AND `service_id` = :service AND `attribute` = :attribute;";

        $params = array(
            'user' => $this->getHashedUserID(),
            'service' => $this->getServiceId($destination),
            'attribute' => $this->getAttributeHash($subset, TRUE)
        );
        $consent = $this->database->fetchOne($query, $params);

        ksort($hashAttributes);

        $data = array(
            'consent' => $consent,    
            'attributes' => $hashAttributes,
        );

        return $data;
    }

    public function addConsent($destination, array $subset)
    {
        $hashAttributes = array();
        foreach ($subset AS $attr) {
            if (isset($this->attributes[$attr])) {
                $hashAttributes[$attr] = $this->attributes[$attr];
            }
        }

        $query = "INSERT INTO `consent` (`consent_date`, `usage_date`, `hashed_user_id`, `service_id`, `attribute`) VALUES (NOW(), NOW(), :user, :service, :attribute)";

        $params = array(
            'user' => $this->getHashedUserID(),
            'service' => $this->getServiceId($destination),
            'attribute' => $this->getAttributeHash($subset, TRUE)
        );
        $consent = $this->database->insert($query, $params);

        return true;
    }

    public function removeConsent($destination)
    {
        $query = "DELETE FROM `consent` WHERE `hashed_user_id` = :user AND `service_id` = :service;";

        $params = array(
            'user' => $this->getHashedUserID(),
            'service' => $this->getServiceId($destination),
        );

        $res = $this->database->modify($query, $params);

        return $res;
    }

    public function setAttribute(array $attr) {
        foreach ($attr AS $key => $val) {
            $this->attributes[$key] = $val;
        }
    }

    public function getHashedUserID()
    {
        return hash('sha1', $this->userid . '|' . $this->salt . '|' . $this->source);
    }

    public function getServiceId($destination)
    {
        return hash('sha1', $this->userid . '|' . $this->salt . '|' . $this->source . '|saml20-sp-remote|'  . $destination);
    }

    public function getAttributeHash(array $subset, $includeValues = false)
    {
        $hashAttributes = array();
        foreach ($subset AS $attr) {
            if (isset($this->attributes[$attr])) {
                $hashAttributes[$attr] = $this->attributes[$attr];
            }
        }

        $hashBase = null;
        if ($includeValues) {
            foreach ($hashAttributes AS &$values) {
                sort($values);
            }
            ksort($hashAttributes);
            $hashBase = serialize($hashAttributes);
        } else {
            $names = array_keys($hashAttributes);
            sort($names);
            $hashBase = implode('|', $names);
        }
        return hash('sha1', $hashBase);
    }
}
