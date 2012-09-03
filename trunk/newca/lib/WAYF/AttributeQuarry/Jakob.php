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
class Jakob implements \WAYF\AttributeQuarry
{
    private $options;
    private $_glue = '';

    public function __construct(array $options) {
        $this->options = $options;
    }

    public function setup() {
        // Init DB
        $dsn      = $this->options['options']['dsn'];
        $username = $this->options['options']['username'];
        $password = $this->options['options']['password'];

        try {
            $this->db = new \WAYF\DB($dsn, $username, $password, array(
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 2,
                \PDO::ATTR_PERSISTENT   
            ));
        } catch (\PDOException $e) {
            
            throw new \WAYF\Exceptions\AttributeQuarryException($e->getMessage());
        }
    } 

    public function mine(array $options) {
        $table    = $this->options['options']['table'];
        // Get jaobhash value
        $source      = $this->options['idp'];
        $destination = $options['sp'];
        $jobid       = $this->getJobHash($source, $destination);

        // Grab job configuration
        $query = "SELECT * FROM `" . $table . "` WHERE `jobid` = :jobid;";

        try{
            $res = $this->db->fetchOne($query, array('jobid' => $jobid));
        } catch (PDOException $e) {
            throw new \WAYF\Exceptions\AttributeQuarryException($e->getMessage());
        }

        // redirect if job exists
        if ($res) {
            $params = array();
            
            $params['silence']      = 'on';
            $params['attributes']   = json_encode($this->options['attributes']);
            $params['returnURL']    = 'dddddd';
            $params['returnMethod'] = 'raw';
            $joburl                 = $this->options['options']['joburl'];
            $jakoburl               = $joburl . $jobid;

            // Generate signature on request
            $this->setUp2($this->options['options']['consumersecret'], $params);
            $params['consumerkey'] = $this->options['options']['consumerkey'];
            $params['signature'] = $this->sign();

            // SSL context so certs are validated
            $context = stream_context_create(array(
                'ssl' => array(
                    'verify_peer' => true,
                    'capath' => '/etc/ssl/certs/' 
                )    
            ));

            $tmp = file_get_contents($jakoburl . "/?" . http_build_query($params), false, $context);
            $tmp = json_decode($tmp, TRUE);

            $attributes = array();
            foreach ($tmp AS $name => $values) {
                foreach ($values AS $val) {
                    $attributes[$name][] = $val['value'];
                }
            }

            return $attributes;
        }
        return NULL;
    }
    
    public function sign()
    {
        ksort($this->_params);

        $glued_params = Array();
        foreach($this->_params AS $key => $value) {
            $glued_params[] = $key . $this->_glue . $value;
        }

        $glued_params = implode($this->_glue, $glued_params);
        
        $message = $this->_key . $glued_params;
        $signature = hash('sha512', $message);

        return $signature;
    }
    
    public function setUp2($key, $document, array $options = null)
    {
        if (!is_string($key)) {
            throw new \InvalidArgumentException('The key must be of type string.');
        }
        $this->_key = $key;
        
        if (!is_array($document)) {
            throw new \InvalidArgumentException('The document to be signed must be an assotiative array.');
        }
        $this->_params = $document;

        if (isset($options['glue']) && is_string($options['glue'])) {
            $this->_glue = $options['glue'];
        }
    }
        
    /**
     * Calculate the jobhash value
     */
    public function getJobHash($source, $destination)
    {
        return hash('sha1', $source . '|' . $this->options['options']['salt'] . '|' . $destination);
    }
}
