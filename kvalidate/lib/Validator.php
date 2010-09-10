<?php
/**
 * Kalmar2 metadata validator
 *
 * PHP version 5
 *
 * The Kalmar2 metadata validator is free software: you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General Public License
 * as published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * The Kalmar2 metadata validator is distributed in the hope that it will be
 * useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser
 * General Public License for more details.
 *
 * You should haveKalmar2 metadata validator. If not, see
 * <http://www.gnu.org/licenses/>.
 *
 * @package    SimpleSAMLphp
 * @subpackege Module
 * @author     Jacob Christiansen <jach@wayf.dk>
 * @copyright  2010 Jacob Christiansen 
 * @license    http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version    SVN: $Id$
 * @link       http://code.google.com/p/wayf/
 */

/**#@+
 * Constants
 *
 * Flags for error messages and status
 */ 
define('KV_STATUS_UNDEFINED', 0);
define('KV_STATUS_SUCCESS', 1);
define('KV_STATUS_WARNING', 2);
define('KV_STATUS_ERROR', 3);
/**#@-*/

/**
 * Kalmar2 metadata validator
 *
 * This class is used for validating metadata used in the Kalmar2
 * interfederation. The checkes implemented secures that the metadata
 * complies with Kalmar2 Appendix A and derived requirements.
 *
 * @package    SimpleSAMLphp
 * @subpackege Module 
 * @author     Jacob Christiansen <jach@wayf.dk>
 * @copyright  2010 Jacob Christiansen 
 * @license    http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version    SVN: $Id$
 * @link       http://code.google.com/p/wayf/
 */
class sspmod_kvalidate_Validator {

	/**
	 * Pointer to DOMDocument containing metadata
	 *
	 * @var DOMDocument
	 * @see PHP_MANUAL#class.domdocument.php
	 */
    private $_xml = null;

	/**
	 * Pointer to DOMXPath object
	 *
	 * @var DOMXPath
	 * @see PHP_MANUAL#class.domxpath.php
	 */    
    private $_xpath = null;

	/**
	 * Validation status flag
	 *
	 * @var string 
	 * @uses KV_STATUS_UNDEFINED, KV_STATUS_SUCCESS, KV_STATUS_WARNING, KV_STATUS_ERROR
	 */
    private $_status = KV_STATUS_UNDEFINED;

    /** 
     * Array for holding vlidation messages
     *
     * A message is an array containing 3 keys:
     *  - level, the type of message, see {@link KV_STATUS_UNDEFINED, KV_STATUS_SUCCESS, KV_STATUS_WARNING, KV_STATUS_ERROR}
     *  - msg, a textual description of the message
     *  - line, the line number on which the message originated
     *
     * @var array An array of messages
     * @uses KV_STATUS_UNDEFINED, KV_STATUS_SUCCESS, KV_STATUS_WARNING, KV_STATUS_ERROR	
     */
    private $_messages = array();

    /**
     * the schema used for validating the metadata
     *
     * @var string URL for the schema
     * @see PHP_MANUAL#domdocument.schemavalidate.php
     */
    private $_schema = 'http://docs.oasis-open.org/security/saml/v2.0/saml-schema-metadata-2.0.xsd';
    
    /**
     * Config array
     *
     * Array holding the different configuration options for the validator. The
     * following options can be set:
     * - <i>bool</i> REMOVE_ENTITYDESCRIPTOR, if set to true all EntityDescriptor that do not validate will be removed
     *
     * @var array
     */
    private $_config = array(
    	'REMOVE_ENTITYDESCRIPTOR' => false,
    );
    
    /**
	 * Create a new validator
	 *
	 * The constructor sets libxml_use_internal_errors to <i>true</i>
	 *
	 * @param array $config Array containing options for the validator. Defaults to <i>null</i>
	 *
	 * @see sspmod_kvalidate_Validator::$_config, PHP_MANUAL#function.libxml-use-internal-errors.php
	 */
    public function __construct(Array $config = null)
    {
        // Enable user error handling of XML errors
        libxml_use_internal_errors(true); 
        
        // Overwrite config options if parsed to the validator
        $this->_config = array_merge($this->_config, (array)$config);
    }
    
    /**
	 * Destroy the validator
	 *
	 * The destructor sets libxml_use_internal_errors to <i>false</i>
	 *
	 * @see PHP_MANUAL#function.libxml-use-internal-errors.php
	 */
    public function __destruct() {
    	// Disable user error handling of XML errors
        libxml_use_internal_errors(false); 
    }

	/**
	 * Validate metadata
	 *
	 * The function validates the metadata according to the options set when
	 * the validator was created, see
	 * {@link sspmod_kvalidate_Validator::$_config}. The validator will do some
	 * prettyfing of the metadata in order to better display accurate error 
	 * messages. This means that the outputtet metadata should not be used as 
	 * input to other systems, since the signature on the metadata will not be
	 * valid after the prettyfication
	 *
	 * @param  string $xml The metadata in XML format to be validated
	 *
	 * @return string The metadata in xml format prettyfied or an empty string
	 */
    public function validate($xml)
    {
	    assert('is_string($xml)');
	    
		$this->_xml = new DOMDocument();
		$this->_xml->preserveWhiteSpace = false; 
		$this->_xml->formatOutput = true;
		
		// Break on load error
		if(!$this->_xml->loadXML($xml)) {
			$this->_getLibxmlErrors();
			$this->_status = KV_STATUS_ERROR;
			return '';
		}
		
		/* 
		 * Create dublicate instance of the XML to enable signature validation 
		 * and still be able to pretty print the XML for improved readability 
		 * and easy debugging on errors.
		 */
		$sigXML = new DOMDocument();
		$sigXML->loadXML($xml);
		
		// Save XML and reload to prettify it for improved readability
		if(!$xml = $this->_xml->saveXML()) {
			$this->_getLibxmlErrors();
			$this->_status = KV_STATUS_ERROR;
			return false;
		}
		if(!$this->_xml->loadXML($xml)) {
			$this->_getLibxmlErrors();
			$this->_status = KV_STATUS_ERROR;
			return false;
		}
		
		// Register XPath object and namespaces
        $this->_xpath = new DOMXPath($this->_xml);
        if(!$this->_xpath->registerNamespace('md', "urn:oasis:names:tc:SAML:2.0:metadata")) {
        	$this->_getLibxmlErrors();
			$this->_status = KV_STATUS_ERROR;
			return '';
		}
        if(!$this->_xpath->registerNamespace('ds', "http://www.w3.org/2000/09/xmldsig#")) {
        	$this->_getLibxmlErrors();
			$this->_status = KV_STATUS_ERROR;
			return '';
		}

        $this->_status = KV_STATUS_SUCCESS;

		// Start by schema validation the input
        if(!$this->_vSchema($this->_xml)) {
        	return '';
        }
        
		// Start processing according to the root element
        $root_element = $this->_xml->documentElement;
    	
        if($root_element->localName == 'EntitiesDescriptor') {
        	// Validate signature on root EntitiesDescriptor element
        	$this->_vEDSignature($sigXML->documentElement);
            $this->_processEntitiesDescriptor($root_element);
        } else if($root_element->localName == 'EntityDescriptor'){
            $this->_vEntityValidUntil($root_element);
            $this->_processEntityDescriptor($root_element);
        } else {
            $this->_messages[] = array( 
                'level' => KV_STATUS_ERROR,
                'msg' => '[DOCUMENT] Document root must either be an EntitiesDescriptor or an EntitDescriptor',
                'line' => $root_element->getLineNo(),
            ); 
            $this->_status = KV_STATUS_ERROR;
            return '';
        }
        
        // Check for errors before returning the XML
        if(!$xml= $this->_xml->saveXML()) {
			$this->_getLibxmlErrors();
			$this->_status = KV_STATUS_ERROR;
			return '';
		}
		
        return $xml;
    }

	/**
	 * Validate an EntitiesDescriptor
	 *
	 * All embeded EntityDescriptor and EntitiesDescriptor will be validated. 
     * Nested EntitiesDescriptor is not allowed and will be removed if option 
     * set in config.
	 *
	 * @param DOMElement $input_elm The element to be validated
	 *
	 * @return bool True on success and false on error
	 */
    private function _processEntitiesDescriptor(DOMElement $input_elm)
    {	
		// EntitiesDescriptors can be nested
		$query = 'md:EntitiesDescriptor';
        $elms = $this->_xpath->query($query, $input_elm);
        
        if($elms->length > 0) {
            foreach($elms AS $elm) {
                $this->_messages[] = array( 
                    'level' => KV_STATUS_ERROR,
                    'msg' => '[DOCUMENT] Nested EntitiesDescriptor not allowed',
                    'line' => $elm->getLineNo(),
                ); 
                // Remove EntitiesDescriptor because nested EntitiesDescritor is 
                // not allowed.
                if($this->_config['REMOVE_ENTITYDESCRIPTOR']) {
                    $this->_messages[] = array( 
                        'level' => KV_STATUS_WARNING,
                        'msg' => '[DOCUMENT] Nested EntitiesDescriptor has been removed',
                        'line' => $elm->getLineNo(),
                    );
                    $parentNode = $elm->parentNode->removeChild($elm);
                }
            }
        }
        
        // Start by doing checks on the EntityDescriptor it self
        $status['vED'] = $this->_vEntitiesValidUntil($input_elm);
	
		// Validate all EntityDescriptor
		$query = 'md:EntityDescriptor';
        $elms = $this->_xpath->query($query, $input_elm);
    
    	if($elms->length > 0) {
        	foreach($elms AS $elm) {
            	$this->_processEntityDescriptor($elm);
        	}
        }
        
        return true;
    }

	/**
	 * Validate an EntityDescriptor
	 *
     * All embeded IDPSSODescriptor and SPSSODescriptor is precessed. If the 
     * the option REMOVE_ENTITYDESCRIPTOR was set when the validator was 
     * created, EntityDescriptor with faulty IDPSSODescriptor or 
     * SPSSODescriptor will be removed from the metadata.
	 *
	 * @param DOMElement $input_elm The element to be validated
	 *
	 * @return bool True if all checks clears othervise false
	 */
    private function _processEntityDescriptor(DOMElement $input_elm)
    {
    	$status = array();

		// Validate all IDPSSODescriptors
		$query = 'md:IDPSSODescriptor';
        $elms = $this->_xpath->query($query, $input_elm);
    
        foreach($elms AS $elm) {
            $status[$input_elm->getAttribute('entityID')] = $this->_processIDPSSODescriptor($elm);
        }

		// Validate all SPSSODescriptors
		$query = 'md:SPSSODescriptor';
        $elms = $this->_xpath->query($query, $input_elm);
   
        foreach($elms AS $elm) {
            $status[$input_elm->getAttribute('entityID')] = $this->_processSPSSODescriptor($elm);
        }
        
        // Remove entityDescriptor if it does not validate
        if($this->_config['REMOVE_ENTITYDESCRIPTOR'] && in_array(false, $status)) {
        	$this->_messages[] = array( 
            	'level' => KV_STATUS_WARNING,
            	'msg' => '[' . $input_elm->getAttribute('entityID') . '] EntityDescriptor has been removed',
            	'line' => $input_elm->getLineNo(),
        	);
        	$parentNode = $input_elm->parentNode->removeChild($input_elm);
        }
        
        return !in_array(false, $status);
    }

	/**
	 * Validate an IDPSSODescriptor
	 *
	 * The following checks are performed:
	 * - vCert
	 * - vSSO
	 * - vSLO
	 * - vScope
	 * - vSign
	 * - vOrgName
	 *
	 * @param DOMElement $input_elm The element to be validated
	 *
	 * @return bool True if all checks clears othervise false
	 */
    private function _processIDPSSODescriptor(DOMElement $input_elm)
    {
    	$status = array();
    	
    	// Run checks
        $status['vCert'] = $this->_vCert($input_elm);
        $status['vSSO'] = $this->_vSSO($input_elm);
        $status['vSLO'] = $this->_vSLO($input_elm);
        $status['vScope'] = $this->_vScope($input_elm);
        $status['vExtension'] = $this->_vExtension($input_elm);
        $status['vSign'] = $this->_vSign($input_elm);
        $status['vOrgName'] = $this->_vOrgName($input_elm);
        
        return !in_array(false, $status);
    }

	/**
	 * Validate an SPSSODescriptor
	 *
	 * The following checks are performed:
	 * - vCert
	 * - vACS
	 * - vSLO
	 * - vRequestAttr
	 * - vEnc
     * - vNameDesc
	 *
	 * @param DOMElement $input_elm The element to be validated
	 *
	 * @return bool True if all checks clears othervise false
	 */
    private function _processSPSSODescriptor(DOMElement $input_elm)
    {
    	$status = array();
    	
    	// Run checks
        $status['vCert'] = $this->_vCert($input_elm);
        $status['vACS'] = $this->_vACS($input_elm);
        $status['vSLO'] = $this->_vSLO($input_elm);
        $status['vRequestAttr'] = $this->_vRequestAttr($input_elm);
        $status['vEnc'] = $this->_vEnc($input_elm);
        $status['vNameDesc'] = $this->_vNameDesc($input_elm);
        
        return !in_array(false, $status);
    }

	/**
	 * vNameDesc validation check
	 *
     * <md:AttributeCinsumingService> must contain a english name and 
     * description.
	 * 
	 * @param DOMElement $input_elm The element to be validated
	 *
	 * @return bool True if the check clears othervise false
	 */
    private function _vNameDesc(DOMElement $input_elm)
    {
        $query = 'md:AttributeConsumingService';

        $elms = $this->_xpath->query($query, $input_elm);

        foreach($elms AS $elm) {
            $query_name = 'md:ServiceName';

            $elms_name = $this->_xpath->query($query_name, $elm);

            $found_name = false;
            foreach($elms_name AS $elm_name) {
                if($elm_name->getAttribute('xml:lang') == 'en') {
                    $found_name = true;
                    break;
                }
            }
            if(!$found_name) {
                $this->_messages[] = array( 
                    'level' => KV_STATUS_ERROR,
                    'msg' => '[' . $input_elm->parentNode->getAttribute('entityID') . '] No english name found for service',
                    'line' => $input_elm->getLineNo(),
                );
                return true;
            }

            $query_desc = 'md:ServiceDescription';

            $elms_desc = $this->_xpath->query($query_desc, $elm);

            $found_desc = false;
            foreach($elms_desc AS $elm_desc) {
                if($elm_desc->getAttribute('xml:lang') == 'en') {
                    $found_desc = true;
                    break;
                }
            }
            if(!$found_desc) {
                $this->_messages[] = array( 
                    'level' => KV_STATUS_ERROR,
                    'msg' => '[' . $input_elm->parentNode->getAttribute('entityID') . '] No english description found for service',
                    'line' => $input_elm->getLineNo(),
                );
                return true;
            }
        }

        if($found_name && $found_desc) {
            $this->_messages[] = array( 
                'level' => KV_STATUS_SUCCESS,
                'msg' => '[' . $input_elm->parentNode->getAttribute('entityID') . '] vNameDesc check parsed',
                'line' => $input_elm->getLineNo(),
            );
            return true;
        }
    }

	/**
	 * vOrgName validation check
	 *
     * <md:EntityDescriptor> must contain a english name in the 
     * <md:Organization> element.
	 * 
	 * @param DOMElement $input_elm The element to be validated
	 *
	 * @return bool True if the check clears othervise false
	 */
    private function _vOrgName(DOMElement $input_elm)
    {
        $query = 'md:Organization';

        $elms = $this->_xpath->query($query, $input_elm->parentNode);

        foreach($elms AS $elm) {
            $query_name = 'md:OrganizationName';

            $elms_name = $this->_xpath->query($query_name, $elm);

            $found_name = false;
            foreach($elms_name AS $elm_name) {
                if($elm_name->getAttribute('xml:lang') == 'en') {
                    $found_name = true;
                    break;
                }
            }
            if(!$found_name) {
                $this->_messages[] = array( 
                    'level' => KV_STATUS_ERROR,
                    'msg' => '[' . $input_elm->parentNode->getAttribute('entityID') . '] No english name found for IdP',
                    'line' => $input_elm->getLineNo(),
                );
                return true;
            }
        }

        if($found_name) {
            $this->_messages[] = array( 
                'level' => KV_STATUS_SUCCESS,
                'msg' => '[' . $input_elm->parentNode->getAttribute('entityID') . '] vOrgName check parsed',
                'line' => $input_elm->getLineNo(),
            );
            return true;
        }
    }

	/**
	 * vEnc validation check
	 *
	 * <md:SPSSOdescriptor> must contain a certificate for encrypting if ACS
	 * endpoint is HTTP. A warning i shown if the included certificate is not 
     * marked for encryption.
	 * 
	 * @param DOMElement $input_elm The element to be validated
	 *
	 * @return bool True if the check clears othervise false
	 */
    private function _vEnc(DOMElement $input_elm)
    {
        $kd_found = false;
        $query = 'md:AssertionConsumerService';
        $elms = $this->_xpath->query($query, $input_elm);

        foreach($elms AS $elm) {
            $binding = $elm->getAttribute('Binding');
        
            if($binding = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST') {
                $location = $elm->getAttribute('Location');
               
                if(preg_match('/^https/', $location) == 0) {
                    $query = 'md:KeyDescriptor';

                    $elms = $this->_xpath->query($query, $input_elm); 

                    foreach($elms AS $elm) {
                        if($elm->hasAttribute('use')) {
                            if($elm->getAttribute('use') == 'encryption') {
                                $kd_found = true;
                            }
                        } else {
                            $this->_messages[] = array( 
                                'level' => KV_STATUS_WARNING,
                                'msg' => '[' . $input_elm->patentNode->getAttribute('entityID') . '] `use` attribute not given in `KeyDescriptor`. Should be set to `encryption`',
                                'line' => $input_elm->getLineNo(),
                            );
                            $kd_found = true;
                        }
                    }
                    if($kd_found) {
                        $this->_messages[] = array( 
                            'level' => KV_STATUS_SUCCESS,
                            'msg' => '[' . $input_elm->parentNode->getAttribute('entityID') . '] vEnc check parsed',
                            'line' => $elm->getLineNo(),
                        );
                        return true; 
                    }

                    $this->_messages[] = array( 
                        'level' => KV_STATUS_ERROR,
                        'msg' => '[' . $input_elm->parentNode->getAttribute('entityID') . '] No certificate for encryption found',
                        'line' => $input_elm->getLineNo(),
                    ); 
                    $this->_status = KV_STATUS_ERROR;

                    return false;
                }
            }
        }

        return true;
    }

	/**
	 * vSign validation check
	 *
	 * <md:IDPSSOdescriptor> must contain a certificate for signing
	 * 
	 * @param DOMElement $input_elm The element to be validated
	 *
	 * @return bool True if the check clears othervise false
	 */   
    private function _vSign(DOMElement $input_elm)
    {
        $query = 'md:KeyDescriptor';

        $elms = $this->_xpath->query($query, $input_elm); 

        foreach($elms AS $elm) {
            if($elm->hasAttribute('use')) {
                if($elm->getAttribute('use') == 'signing') {
                    $this->_messages[] = array( 
                        'level' => KV_STATUS_SUCCESS,
                        'msg' => '[' . $input_elm->parentNode->getAttribute('entityID') . '] vSign check parsed',
                        'line' => $elm->getLineNo(),
                    );
                    return true; 
                }
            }
        }
        $this->_messages[] = array( 
            'level' => KV_STATUS_ERROR,
            'msg' => '[' . $input_elm->parentNode->getAttribute('entityID') . '] No certificate for signing found',
            'line' => $input_elm->getLineNo(),
        ); 
        $this->_status = KV_STATUS_ERROR;

        return false;
    }
 
 	/**
	 * vRequestAttr validation check
	 *
	 * <md:SPSSODescriptor> must contain at least one <md:RequestedAttributes>
	 * Each <md:RequestedAttributes> has the NameFormat attribute and set to
	 * urn:oasis:names:tc:SAML:2.0:attrname-format:uri
	 * 
	 * @param DOMElement $input_elm The element to be validated
	 *
	 * @return bool True if the check clears othervise false
	 */   
    private function _vRequestAttr(DOMElement $input_elm)
    {
        $query = 'md:AttributeConsumingService/md:RequestedAttribute';

        $elms = $this->_xpath->query($query, $input_elm);

        if($elms->length < 1) {
        	$this->_messages[] = array( 
            	'level' => KV_STATUS_ERROR,
            	'msg' => '[' . $input_elm->parentNode->getAttribute('entityID') . '] No RequestedAttribute given',
            	'line' => $input_elm->getLineNo(),
        	); 
        	$this->_status = KV_STATUS_ERROR;

        	return false;    
        }
        
        $error = false;
        
        foreach($elms AS $elm) {
        	if(!$elm->hasAttribute('NameFormat')) {
        		$this->_messages[] = array( 
            		'level' => KV_STATUS_ERROR,
            		'msg' => '[' . $input_elm->parentNode->getAttribute('entityID') . '] RequestedAttribute do not have the NameFormat attribute',
            		'line' => $elm->getLineNo(),
        		); 
        		$this->_status = KV_STATUS_ERROR;
        		$error = true;
        	} else {
        		$nameFormat = $elm->getAttribute('NameFormat');
        		if($nameFormat != 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri') {
        			$this->_messages[] = array( 
            			'level' => KV_STATUS_ERROR,
            			'msg' => '[' . $input_elm->parentNode->getAttribute('entityID') . '] RequestedAttribute do not have the correct NameFormat.<br />\'urn:oasis:names:tc:SAML:2.0:attrname-format:uri\' is required but ' . $nameFormat . ' specified',
            			'line' => $elm->getLineNo(),
        			); 
        			$this->_status = KV_STATUS_ERROR;
        			$error = true;
        		}
        	}
        }

		if(!$error) {        
        	$this->_messages[] = array( 
                'level' => KV_STATUS_SUCCESS,
                'msg' => '[' . $input_elm->parentNode->getAttribute('entityID') . '] vRequestAttr check parsed',
                'line' => $input_elm->getLineNo(),
            );
			return true; 
        }
        return false;
    }

    /**
     * vExtension check
     *
     * <md:Extensions> must not contain other elements than <shibmd:Scope>
     *
	 * @param DOMElement $input_elm The element to be validated
	 *
	 * @return bool True if the check clears othervise false
     */
    private function _vExtension(DOMElement $input_elm)
    {
        $error = false;

        $query = 'md:Extensions';

        $elms = $this->_xpath->query($query, $input_elm);

        foreach($elms AS $elm) {
            if($elm->hasChildNodes()) {
                $sub_elms = $elm->childNodes;
                foreach($sub_elms AS $sub_elm) {
                    $nodeName = $sub_elm->nodeName;
                    if($nodeName != 'shibmd:Scope') {
                        $this->_messages[] = array( 
                            'level' => KV_STATUS_ERROR,
                            'msg' => '[' . $input_elm->parentNode->getAttribute('entityID') . '] Only `shibmd:Scope` element allowed in `Extensions` element. `' . $nodeName . '` given',
                            'line' => $elm->getLineNo(),
                        ); 
                        $error = true;
                        $this->_status = KV_STATUS_ERROR;
                    }    
                }
            }
        }

        if(!$error) {
            $this->_messages[] = array( 
                'level' => KV_STATUS_SUCCESS,
                'msg' => '[' . $input_elm->parentNode->getAttribute('entityID') . '] vExtension check parsed',
                'line' => $input_elm->getLineNo(),
            );
            return true; 
        }
        
        return false;
    }

	/**
	 * vScope validation check
	 *
	 * <md:IDPSSOdescriptor> must contain at least <shibmd:Scope> and `regexp` 
     * attribute must be set to false.
	 * 
	 * @param DOMElement $input_elm The element to be validated
	 *
	 * @return bool True if the check clears othervise false
	 * @todo Validate the value of the scope elements
	 */   
    private function _vScope(DOMElement $input_elm)
    {
        $error = false;

        $this->_xpath->registerNamespace('shibmd', 'urn:mace:shibboleth:metadata:1.0');
        $query = 'md:Extensions/shibmd:Scope';

        $elms = $this->_xpath->query($query, $input_elm);

        if($elms->length == 0) {
            $this->_messages[] = array( 
                'level' => KV_STATUS_ERROR,
                'msg' => '[' . $input_elm->parentNode->getAttribute('entityID') . '] `shibmd:Scope` is missing',
                'line' => $input_elm->getLineNo(),
            );
            $this->_status = KV_STATUS_ERROR;
            return false;
        }

        foreach($elms AS $elm) {
            if(empty($elm->nodeValue)) {
                $this->_messages[] = array( 
                    'level' => KV_STATUS_ERROR,
                    'msg' => '[' . $input_elm->parentNode->getAttribute('entityID') . '] shibmd:Scope element is empty',
                    'line' => $elm->getLineNo(),
                ); 
                $error = true;
                $this->_status = KV_STATUS_ERROR;
            }    
            if($elm->hasAttribute('regexp')) {
                $attr = $elm->getAttribute('regexp');
                if($attr != 'false') {
                    $this->_messages[] = array( 
                        'level' => KV_STATUS_ERROR,
                        'msg' => '[' . $input_elm->parentNode->getAttribute('entityID') . '] `regexp` attribute on ´shibmd:Scope´ is set to ' . $attr . '. MUST be set to `false` or omitted',
                        'line' => $elm->getLineNo(),
                    ); 
                    $error = true;
                    $this->_status = KV_STATUS_ERROR;
                }
            }
        }

        if(!$error) {
            $this->_messages[] = array( 
                'level' => KV_STATUS_SUCCESS,
                'msg' => '[' . $input_elm->parentNode->getAttribute('entityID') . '] vScope check parsed',
                'line' => $input_elm->getLineNo(),
            );
            return true; 
        }
        return false;
    }

	/**
	 * vEntitiesValidUntil validation check
	 *
	 * <md:EntitiesDescriptor> can contain a validUntil attribute. The
	 * validUntil timestamp must be between 6 and 96 hours in the furure. If a 
     * valid validUntil attribute is not found, all child EntityDescriptor is 
     * searched for valid validUntil attributes,
	 * 
	 * @param DOMElement $input_elm The element to be validated
	 *
	 * @return bool True if the check clears othervise false
	 */   
    private function _vEntitiesValidUntil(DOMElement $input_elm)
    {
        $error = false;

        $att_validUntil = $input_elm->getAttribute('validUntil');

        // Check if the EntitiesDescriptor contains a validUntil attribute
        if(!empty($att_validUntil)) {
        	// Validate the timestamp
        	$validTime = strtotime($att_validUntil);
        	$minTime = time() + (60*60*6-30);
        	$maxTime = time() + (60*60*96+30);
        	
        	if( ($validTime-$minTime) < 0 ) {
        		$this->_messages[] = array(
                	'level' => KV_STATUS_ERROR,
                	'msg' => '[DOCUMENT] validUntil MUST be at least 6 hours in the future. validUntil set to ' . $att_validUntil . '<br />MUST be at least ' . date('c', $minTime),
                	'line' => $input_elm->getLineNo(),
            	);
            	$this->_status = KV_STATUS_ERROR;
                $error = true;
            }

        	if( ($maxTime - $validTime) < 0 ) {
        		$this->_messages[] = array(
                	'level' => KV_STATUS_ERROR,
                	'msg' => '[DOCUMENT] validUntil MUST not be more that 96 hours in the future. validUntil set to ' . $att_validUntil . '<br />MUST not be more than ' . date('c', $maxTime),
                	'line' => $input_elm->getLineNo(),
            	);
            	$this->_status = KV_STATUS_ERROR;
                $error = true;
        	}

            // validUntil is good. No need to check all EntityDescriptor
            if(!$error) {
                $this->_messages[] = array(
                    'level' => KV_STATUS_SUCCESS,
                    'msg' => '[DOCUMENT] vEntitiesValidUntil check parsed',
                    'line' => $input_elm->getLineNo(),
                ); 
                return true;
            }
        }

		// Validate validUntil on all EntityDescriptor
		$query = 'md:EntityDescriptor';
        $elms = $this->_xpath->query($query, $input_elm);

        $status = array();

        foreach($elms AS $elm) {
            $status[$input_elm->getAttribute('entityID')] = $this->_vEntityValidUntil($elm);
        }

        if(!in_array(false, $status)) {
            $this->_messages[] = array(
                'level' => KV_STATUS_SUCCESS,
                'msg' => '[DOCUMENT] vEntitiesValidUntil check parsed',
                'line' => $input_elm->getLineNo(),
            ); 
            return true;
        }

        return false;
    }

	/**
	 * vEntityValidUntil validation check
	 *
	 * <md:EntityDescriptor> must contain a validUntil attribute. The
     * validUntil timestamp must be between 6 and 96 hours in the furure.
	 * 
	 * @param DOMElement $input_elm The element to be validated
	 *
	 * @return bool True if the check clears othervise false
	 */   
    private function _vEntityValidUntil(DOMElement $input_elm)
    {
        $att_validUntil = $input_elm->getAttribute('validUntil');

        if(empty($att_validUntil)) {
            $this->_messages[] = array(
                'level' => KV_STATUS_ERROR,
                'msg' => '[' . $input_elm->getAttribute('entityID') . '] EntityDescriptor does not contain a validUntil attribute',
                'line' => $input_elm->getLineNo(),
            ); 
            $this->_status = KV_STATUS_ERROR;
            return false;
        } else {
            // Validate the timestamp
            $validTime = strtotime($att_validUntil);
            $minTime = time() + (60*60*6-30);
            $maxTime = time() + (60*60*96+30);

            if( ($validTime-$minTime) < 0 ) {
                $this->_messages[] = array(
                    'level' => KV_STATUS_ERROR,
                    'msg' => '[' . $input_elm->getAttribute('entityID') . '] validUntil MUST be at least 6 hours in the future. validUntil set to ' . $att_validUntil . '<br />MUST be at least ' . date('c', $minTime),
                    'line' => $input_elm->getLineNo(),
                );
                $this->_status = KV_STATUS_ERROR;
                return false;
            }

            if( ($maxTime - $validTime) < 0 ) {
                $this->_messages[] = array(
                    'level' => KV_STATUS_ERROR,
                    'msg' => '[' . $input_elm->getAttribute('entityID') . '] validUntil MUST not be more that 96 hours in the future. validUntil set to ' . $att_validUntil . '<br />MUST not be more than ' . date('c', $maxTime),
                    'line' => $input_elm->getLineNo(),
                );
                $this->_status = KV_STATUS_ERROR;
                return false;
            }
        }

        $this->_messages[] = array(
            'level' => KV_STATUS_SUCCESS,
            'msg' => '[' . $input_elm->getAttribute('entityID') . '] vED check parsed',
            'line' => $input_elm->getLineNo(),
        ); 
        return true;
    }

    /**
     * vSLO validation check
     *
     * <md:SingleLogoutService> must use the HTTP-REDIRECT binding
     * 
	 * @param DOMElement $input_elm The element to be validated
	 *
	 * @return bool True if the check clears othervise false
	 */   
    private function _vSLO(DOMElement $input_elm)
    {
        $query = 'md:SingleLogoutService';
        $elms = $this->_xpath->query($query, $input_elm);

        if($elms->length == 0) {
            return true;
        }

        foreach($elms AS $elm) {
            $binding = $elm->getAttribute('Binding');
        
            if($binding = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-REDIRECT') {
                $this->_messages[] = array(
                    'level' => KV_STATUS_SUCCESS,
                    'msg' => '[' . $elm->parentNode->parentNode->getAttribute('entityID') . '] vSLO check parsed',
                    'line' => $elm->getLineNo(),
                ); 
                return true;
            }
        }

        $this->_messages[] = array(
            'level' => KV_STATUS_ERROR,
            'msg' => '[' . $input_elm->parentNode->getAttribute('entityID') . '] SingleLogoutService MUST use HTTP-REDIRECT binding. No endpoints using this binding was found.',
            'line' => $input_elm->getLineNo(),
        ); 
        $this->_status = KV_STATUS_ERROR;
        return false;
    }

	/**
	 * vACS validation check
	 *
	 * <md:AssertionConsumerService> must use the HTTP-POST binding
	 * 
	 * @param DOMElement $input_elm The element to be validated
	 *
	 * @return bool True if the check clears othervise false
	 */    
    private function _vACS(DOMElement $input_elm)
    {
        $query = 'md:AssertionConsumerService';
        $elms = $this->_xpath->query($query, $input_elm);

        foreach($elms AS $elm) {
            $binding = $elm->getAttribute('Binding');
        
            if($binding == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST') {
                $this->_messages[] = array(
                    'level' => KV_STATUS_SUCCESS,
                    'msg' => '[' . $elm->parentNode->parentNode->getAttribute('entityID') . '] vACS check parsed',
                    'line' => $elm->getLineNo(),
                ); 
                return true;
            }
        }

        $this->_messages[] = array(
            'level' => KV_STATUS_ERROR,
            'msg' => '[' . $input_elm->parentNode->getAttribute('entityID') . '] AssertionConsumerService MUST use HTTP-POST binding. No endpoints using this binding was found.',
            'line' => $input_elm->getLineNo(),
        ); 
        $this->_status = KV_STATUS_ERROR;
        
        return false;
    }
 
 	/**
	 * vSSO validation check
	 *
	 * <md:SingleSignOnService> must use the HTTP-REDIRECT binding
	 * 
	 * @param DOMElement $input_elm The element to be validated
	 *
	 * @return bool True if the check clears othervise false
	 */ 
    private function _vSSO(DOMElement $input_elm)
    {
        $query = 'md:SingleSignOnService';
        $elms = $this->_xpath->query($query, $input_elm);

        foreach($elms AS $elm) {
            $binding = $elm->getAttribute('Binding');
        
            if($binding == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-REDIRECT') {
                $this->_messages[] = array(
                    'level' => KV_STATUS_SUCCESS,
                    'msg' => '[' . $elm->parentNode->parentNode->getAttribute('entityID') . '] vSSO check parsed',
                    'line' => $elm->getLineNo(),
                ); 
                return true;
            }
        }

        $this->_messages[] = array(
            'level' => KV_STATUS_ERROR,
            'msg' => '[' . $input_elm->parentNode->getAttribute('entityID') . '] SingleSignOnService MUST use HTTP-REDIRECT binding. No endpoints using this binding was found.',
            'line' => $input_elm->getLineNo(),
        ); 
        $this->_status = KV_STATUS_ERROR;
        return false;
    }

 	/**
	 * vEDSignature validation check
	 *
	 * If present, the <md:EntitiesDescriptor> is signed
	 * 
	 * @param DOMElement $input_elm The element to be validated
	 *
	 * @return bool True if the check clears othervise false
	 * @todo Check that certificate used for signing is accepted
	 */    
    private function _vEDSignature(DOMElement $input_elm)
    {
        try {
            $ed = new SAML2_XML_md_EntitiesDescriptor($input_elm);
            // Will throw an exception if signature is invalid
            $validCerts = $ed->getValidatingCertificates();
        } catch(Exception $e) {
			$this->_messages[] = array(
            	'level' => KV_STATUS_ERROR,
            	'msg' => '[DOCUMENT] ' . $e->getMessage(),
            	'line' => $input_elm->getLineNo(),
        	); 
        	$this->_status = KV_STATUS_ERROR;
        	return false;
        }

		if(empty($validCerts)) {
			$this->_messages[] = array(
            	'level' => KV_STATUS_ERROR,
            	'msg' => '[DOCUMENT] Invalid signature on EntitiesDescriptor',
            	'line' => $input_elm->getLineNo(),
        	); 
        	$this->_status = KV_STATUS_ERROR;
        	return false;
		}
		
		$this->_messages[] = array(
        	'level' => KV_STATUS_SUCCESS,
            'msg' => '[DOCUMENT] vEDSignature check parsed',
            'line' => $input_elm->getLineNo(),
        ); 
        return true;
    }

 	/**
	 * vCert validation check
	 *
	 * Each <md:KeyDescriptor> only contains one key <md:KeyDescriptor>
	 * contains at least one <ds:KeyValue> or one
	 * <ds:X509Data><ds:X509Certificate>. If given, the
	 * <ds:X509Certificate> contains a public key with recognized type.
	 * 
	 * @param DOMElement $input_elm The element to be validated
	 *
	 * @return bool True if the check clears othervise false
	 * @todo If both KeyValue and X509Certificate is present, check to see that they both contain the same key.
     * @todo Check to see if the certificates are expired        
	 */
    private function _vCert(DOMElement $input_elm) 
    {   
        $error = 0;
        
        // Get all KeyDescriptors
        $query = 'md:KeyDescriptor';
        $elms = $this->_xpath->query($query, $input_elm);
        
        foreach($elms AS $elm) {
            $query = 'ds:KeyInfo';
            $elms2 = $this->_xpath->query($query, $elm);
            
            // Check that only one KeyInfo is located in the KeyDescriptor
            if($elms2->length > 1) {
                $this->_messages[] = array(
                    'level' => KV_STATUS_ERROR,
                    'msg' => '[' . $elm->parentNode->parentNode->getAttribute('entityID') . '] Only one KeyInfo element allowed. ' . $elms2->length . ' given.',
                    'line' => $elm->getLineNo(),
                ); 
                $this->_status = KV_STATUS_ERROR;
                $error = 1;
                continue;
            }
            if($elms2->length < 1) {
                $this->_messages[] = array(
                    'level' => KV_STATUS_ERROR,
                    'msg' => '[' . $elm->parentNode->parentNode->getAttribute('entityID') . '] One KeyInfo element is required.',
                    'line' => $elm->getLineNo(),
                ); 
                $this->_status = KV_STATUS_ERROR;
                $error = 1;
                continue;
            }

            // Only one element given
            $elm2 = $elms2->item(0);
            
            $query = 'ds:KeyValue';
            $kv = $this->_xpath->query($query, $elm2);

            $query = 'ds:X509Data/ds:X509Certificate';
            $cert = $this->_xpath->query($query, $elm2);

            // See if KeyValue or X509Certificate is located in the KeyInfo 
            if($kv->length < 1 && $cert->length != 1) {
                $this->_messages[] = array(
                    'level' => KV_STATUS_ERROR,
                    'msg' => '[' . $elm->parentNode->parentNode->getAttribute('entityID') . '] At least one KeyValue or only one X509Certificate must be present',
                    'line' => $elm2->getLineNo(),
                ); 
                $this->_status = KV_STATUS_ERROR;
                $error = 1;
                continue;
            }
            
            if($cert->length == 1) {
                /**
                 * Validate that the certificate contains a public key
                 * Appending, prepending and wrapping the cert, so that OpenSSL 
                 * will accept it.
                 */
                $key = "-----BEGIN CERTIFICATE-----\n";
                $key .= wordwrap($cert->item(0)->nodeValue, 64, "\n", true);
                $key .= "\n-----END CERTIFICATE-----";

                $res = openssl_get_publickey($key); 

                if(is_resource($res) && get_resource_type($res) == 'OpenSSL key') {
                    $info = openssl_pkey_get_details($res);
                    if(!$info) {
                        while ($msg = openssl_error_string()) {
                            $this->_messages[] = array(
                                'level' => KV_STATUS_ERROR,
                                'msg' => '[' . $elm->parentNode->parentNode->getAttribute('entityID') . '] OpenSSL error: ' . $msg,
                                'line' => $cert->item(0)->getLineNo(),
                            ); 
                        }
                        $error = 1;
                        $this->_status = KV_STATUS_ERROR;
                        continue;
                    }
                    
                    // Does the public key has recognized type
                    if($info['type'] < 0 || $info['type'] > 3) {
                        $this->_messages[] = array(
                            'level' => KV_STATUS_ERROR,
                            'msg' => '[' . $elm->parentNode->parentNode->getAttribute('entityID') . '] Certificate in the X509Certificate element is not valid',
                        	'line' => $cert->item(0)->getLineNo(),
                        ); 
                        $this->_status = KV_STATUS_ERROR;
                        $error = 1;
                        continue;
                    }
                } else {
                    while ($msg = openssl_error_string()) {
                        $this->_messages[] = array(
                            'level' => KV_STATUS_ERROR,
                            'msg' => '[' . $elm->parentNode->parentNode->getAttribute('entityID') . ']dd OpenSSL error: ' . $msg,
                        	'line' => $cert->item(0)->getLineNo(),
                        ); 
                    }
                    $error = 1;
                    $this->_status = KV_STATUS_ERROR;
                }
            }
        }
        if($error == 0) {
            $this->_messages[] = array(
                'level' => KV_STATUS_SUCCESS,
                'msg' => '[' . $input_elm->parentNode->getAttribute('entityID') . '] vCert check parsed',
                'line' => $input_elm->getLineNo(),
            ); 
            return true;
        }
        return false;
    }

 	/**
	 * vSchema validation check
	 *
	 * Schema validation according to the schema given by the SAML2 spec.
	 * 
	 * @param DOMDocument $input_elm The element to be validated
	 *
	 * @return bool True if the check clears othervise false
	 */
    private function _vSchema(DOMDocument $input_elm)
    {
        if($input_elm->schemaValidate($this->_schema)) {
            $this->_messages[] = array(
                'level' => KV_STATUS_SUCCESS,
                'msg' => '[DOCUMENT] vSchema check parsed',  
                'line' => $input_elm->getLineNo(),
            ); 
            return true;
        } else {
            $this->_getLibxmlErrors();
            $this->_status = KV_STATUS_ERROR;
        }
        return false;
    }

	/**
	 * Get libXML errors
	 *
	 * Custom error handler for libXML errors. All errors from libXML is parsed
	 * and aproiate error message is generated.
	 *
	 * @see PHP_MANUAL#book.libxml.php 
	 */
    private function _getLibxmlErrors()
    {
        $errors = libxml_get_errors();
        foreach ($errors as $error) {
            $e = array();
            // Get error type
            switch ($error->level) {
                case LIBXML_ERR_WARNING:
                    $e['level'] = KV_STATUS_WARNING;
                    break;
                case LIBXML_ERR_ERROR:
                    $e['level'] = KV_STATUS_ERROR;
                    break;
                case LIBXML_ERR_FATAL:
                    $e['level'] = KV_STATUS_ERROR;
                    break;
            }

            // Construct error message
            $e['msg'] = '[DOCUMENT] ' . trim($error->message);
            if ($error->file) {
                $e['msg'] .= ' in <b>' . $error->file . '</b>';
            }
            $e['line'] = $error->line;

            $this->_messages[] = $e;
        }
        libxml_clear_errors();
    }

	/**
	 * Get that status of the last validation
	 *
	 * @return int Status flag
	 * @see    KV_STATUS_UNDEFINED, KV_STATUS_SUCCESS, KV_STATUS_WARNING, KV_STATUS_ERROR
	 */
    public function getStatus()
    {
        return $this->_status;
    }
    
    /**
	 * Get messages from last validation
	 *
	 * @return array Array of messages
	 */
    public function getMessages()
    {
        return $this->_messages;
    }
}
