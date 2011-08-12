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
 * @category  SimpleSAMLphp
 * @package   Kvalidate
 * @author    Jacob Christiansen <jach@wayf.dk>
 * @copyright 2010 Jacob Christiansen 
 * @license   http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version   SVN: $Id$
 * @link      http://code.google.com/p/wayf/
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
 * @category  SimpleSAMLphp
 * @package   Kvalidate
 * @author    Jacob Christiansen <jach@wayf.dk>
 * @copyright 2010 Jacob Christiansen 
 * @license   http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version   SVN: $Id$
 * @link      http://code.google.com/p/wayf/
 */
class sspmod_kvalidate_Validator
{
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
     * @uses KV_STATUS_UNDEFINED
     * @uses KV_STATUS_SUCCESS
     * @uses KV_STATUS_WARNING
     * @uses KV_STATUS_ERROR
     */
    private $_status = KV_STATUS_UNDEFINED;

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
     * - <i>bool</i> REMOVE_ENTITYDESCRIPTOR, if set to true all EntityDescriptor 
     * that do not validate will be removed
     *
     * @var array
     */
    private $_config = array(
        'REMOVE_ENTITYDESCRIPTOR' => false,
    );

    private $_logger;

    /**
     * Create a new validator
     *
     * The constructor sets libxml_use_internal_errors to <i>true</i>
     *
     * @param array $config Array containing options for the validator.
     *
     * @see sspmod_kvalidate_Validator::$_config
     * @see PHP_MANUAL#function.libxml-use-internal-errors.php
     */
    public function __construct(Array $config = null)
    {
        SimpleSAML_Logger::debug('[Kvalidate] Initializing validator');
        // Enable user error handling of XML errors
        libxml_use_internal_errors(true);

        // Overwrite config options if parsed to the validator
        $this->_config = array_merge($this->_config, (array)$config);

        $this->_logger = new sspmod_kvalidate_Logger();
    }

    /**
     * Destroy the validator
     *
     * The destructor sets libxml_use_internal_errors to <i>false</i>
     *
     * @see PHP_MANUAL#function.libxml-use-internal-errors.php
     */
    public function __destruct()
    {
        SimpleSAML_Logger::debug('[Kvalidate] Destroying validator');
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
     * @param string $xml The metadata in XML format to be validated
     *
     * @return string The metadata in xml format prettyfied or an empty string
     */
    public function validate($xml)
    {
        SimpleSAML_Logger::debug('[Kvalidate] Function: validate');
        assert('is_string($xml)');

        $this->_xml                     = new DOMDocument();
        $this->_xml->preserveWhiteSpace = false;
        $this->_xml->formatOutput       = true;

        // Break on load error
        if (!$this->_xml->loadXML($xml)) {
            $this->_getLibxmlErrors();
            $this->_status = KV_STATUS_ERROR;
            return '';
        }

        /**
         * Create dublicate instance of the XML to enable signature validation
         * and still be able to pretty print the XML for improved readability
         * and easy debugging on errors.
         */
        $sigXML = new DOMDocument();
        $sigXML->loadXML($xml);

        // Save XML and reload to prettify it for improved readability
        if (!$xml = $this->_xml->saveXML()) {
            $this->_getLibxmlErrors();
            $this->_status = KV_STATUS_ERROR;
            return false;
        }
        if (!$this->_xml->loadXML($xml)) {
            $this->_getLibxmlErrors();
            $this->_status = KV_STATUS_ERROR;
            return false;
        }

        // Register XPath object
        $this->_xpath = new DOMXPath($this->_xml);

        // Register namespaces
        $namespaces = array(
            'md' => 'urn:oasis:names:tc:SAML:2.0:metadata',
            'ds' => 'http://www.w3.org/2000/09/xmldsig#',
            'shibmd' => 'urn:mace:shibboleth:metadata:1.0',
        );

        foreach ($namespaces AS $key => $value) {
            if (!$this->_xpath->registerNamespace($key, $value)) {
                $this->_getLibxmlErrors();
                $this->_status = KV_STATUS_ERROR;
                return '';
            }
        }

        $this->_status = KV_STATUS_SUCCESS;

        // Start by schema validation the input
        if (!$this->_vSchema($this->_xml)) {
            return '';
        }

        // Start processing according to the root element
        $root_element = $this->_xml->documentElement;

        if ($root_element->localName == 'EntitiesDescriptor') {
            // Validate signature on root EntitiesDescriptor element
            if (!$this->_vEDSignature($sigXML->documentElement) && isset($this->_config['REMOVE_ENTITYDESCRIPTOR']) && $this->_config['REMOVE_ENTITYDESCRIPTOR']) {
                $this->_logger->logError(
                    'Signature not valid',
                    $root_element->getLineNo()
                );
                return '';
            }
            $this->_processEntitiesDescriptor($root_element);
        } else if ($root_element->localName == 'EntityDescriptor') {
            $this->_vEntityValidUntil($root_element);
            $this->_processEntityDescriptor($root_element);
        } else {
            // Shoould not happen. Should be caught by schema validation
            $this->_logger->logError(
                'Document root must be EntitiesDescriptor or EntitDescriptor',
                $root_element->getLineNo()
            );
            $this->_status = KV_STATUS_ERROR;
            return '';
        }

        // Check for errors before returning the XML
        if (!$xml= $this->_xml->saveXML()) {
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
        SimpleSAML_Logger::debug('[Kvalidate] Function: _processEntitiesDescriptor');
        // EntitiesDescriptors can be nested
        $elms = $this->_xpath->query('md:EntitiesDescriptor', $input_elm);

        if ($elms->length > 0) {
            foreach ($elms AS $elm) {
                $this->_logger->logError(
                    'Nested EntitiesDescriptor not allowed',
                    $elm->getLineNo()
                );
                // Remove EntitiesDescriptor because nested EntitiesDescritor is
                // not allowed.
                if ($this->_config['REMOVE_ENTITYDESCRIPTOR']) {
                    $this->_logger->logWarning(
                        'Nested EntitiesDescriptor has been removed',
                        $elm->getLineNo()
                    );
                    // Remove the entity
                    $elm->parentNode->removeChild($elm);
                }
            }
        }

        // Start by doing checks on the EntityDescriptor it self
        $this->_vEntitiesValidUntil($input_elm);

        // Validate all EntityDescriptor
        $elms = $this->_xpath->query('md:EntityDescriptor', $input_elm);

        if ($elms->length > 0) {
            foreach ($elms AS $elm) {
                $this->_processEntityDescriptor($elm);
            }
        }
        
        if ($input_elm->hasAttribute('Name')) {
            $id = $input_elm->getAttribute('Name');
        } else if ($input_elm->hasAttribute('ID')) {
            $id = $input_elm->getAttribute('ID');
        } else {
            $id = 'EntitiesDescriptor';
        }

        $status['vExtension'] = $this->_vExtension($input_elm, $id);

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
        SimpleSAML_Logger::debug('[Kvalidate] Function: _processEntityDescriptor');
        $status = array();

        // Validate all IDPSSODescriptors
        $elms = $this->_xpath->query('md:IDPSSODescriptor', $input_elm);

        foreach ($elms AS $elm) {
            $status[$input_elm->getAttribute('entityID')]
                = $this->_processIDPSSODescriptor($elm);
        }

        // Validate all SPSSODescriptors
        $elms = $this->_xpath->query('md:SPSSODescriptor', $input_elm);

        foreach ($elms AS $elm) {
            $status[$input_elm->getAttribute('entityID')]
                = $this->_processSPSSODescriptor($elm);
        }

        // Remove entityDescriptor if it does not validate
        if ($this->_config['REMOVE_ENTITYDESCRIPTOR'] && in_array(false, $status)) {
            $this->_logger->logWarning(
                'EntityDescriptor has been removed',
                $input_elm->getLineNo(),
                $input_elm->getAttribute('entityID')
            );
            // Remove EntityDescriptor
            $input_elm->parentNode->removeChild($input_elm);
        }

        $status['vExtension']   = $this->_vExtension($input_elm, $input_elm->getAttribute('entityID'));
        
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
     * - vOrganization
     *
     * @param DOMElement $input_elm The element to be validated
     *
     * @return bool True if all checks clears othervise false
     */
    private function _processIDPSSODescriptor(DOMElement $input_elm)
    {
        SimpleSAML_Logger::debug('[Kvalidate] Function: _processIDPSSODescriptor');
        $status = array();

        // Run checks
        $status['vCert']         = $this->_vCert($input_elm);
        $status['vSSO']          = $this->_vSSO($input_elm);
        $status['vSLO']          = $this->_vSLO($input_elm);
        $status['vScope']        = $this->_vScope($input_elm);
        $status['vExtension']    = $this->_vExtension($input_elm, $input_elm->parentNode->getAttribute('entityID'));
        $status['vSign']         = $this->_vSign($input_elm);
        $status['vOrganization'] = $this->_vOrganization($input_elm);

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
        SimpleSAML_Logger::debug('[Kvalidate] Function: _processSPSSODescriptor');
        $status = array();

        // Run checks
        $status['vCert']        = $this->_vCert($input_elm);
        $status['vACS']         = $this->_vACS($input_elm);
        $status['vSLO']         = $this->_vSLO($input_elm);
        $status['vRequestAttr'] = $this->_vRequestAttr($input_elm);
        $status['vEnc']         = $this->_vEnc($input_elm);
        $status['vNameDesc']    = $this->_vNameDesc($input_elm);
        $status['vExtension']   = $this->_vExtension($input_elm, $input_elm->parentNode->getAttribute('entityID'));

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
        SimpleSAML_Logger::debug('[Kvalidate] Function: _vNameDesc');
        $elms = $this->_xpath->query('md:AttributeConsumingService', $input_elm);

        $found_name = false;
        $found_desc = false;

        foreach ($elms AS $elm) {
            $elms_name = $this->_xpath->query('md:ServiceName', $elm);

            foreach ($elms_name AS $elm_name) {
                if ($elm_name->getAttribute('xml:lang') == 'en') {
                    $found_name = true;
                    break;
                }
            }
            if (!$found_name) {
                $this->_logger->logError(
                    'No english name found for service',
                    $input_elm->getLineNo(),
                    $input_elm->parentNode->getAttribute('entityID')
                );
                return true;
            }

            $elms_desc = $this->_xpath->query('md:ServiceDescription', $elm);

            foreach ($elms_desc AS $elm_desc) {
                if ($elm_desc->getAttribute('xml:lang') == 'en') {
                    $found_desc = true;
                    break;
                }
            }
            if (!$found_desc) {
                $this->_logger->logError(
                    'No english description found for service',
                    $input_elm->getLineNo(),
                    $input_elm->parentNode->getAttribute('entityID')
                );
                return true;
            }
        }

        if ($found_name && $found_desc) {
            $this->_logger->logSuccess(
                'vNameDesc check parsed',
                $input_elm->getLineNo(),
                $input_elm->parentNode->getAttribute('entityID')
            );
            return true;
        }
    }

    /**
     * vOrganization validation check
     *
     * <md:EntityDescriptor> must contain a english name in the 
     * <md:Organization> element.
     *
     * @param DOMElement $input_elm The element to be validated
     *
     * @return bool True if the check clears othervise false
     */
    private function _vOrganization(DOMElement $input_elm)
    {
        SimpleSAML_Logger::debug('[Kvalidate] Function: vOrganization');
        $elms = $this->_xpath->query('md:Organization', $input_elm->parentNode);

        // The organization element must be present
        if ($elms->length < 1) {
            $this->_logger->logError(
                'No organization elements found for IdP.',
                $input_elm->getLineNo(),
                $input_elm->parentNode->getAttribute('entityID')
            );
            return true;
        }

        $found_name         = false;
        $found_display_name = false;
        $found_url          = false;
        $url_error          = true;

        $elm = $elms->item(0);

        // Check that at least en english name exists
        $elms_name = $this->_xpath->query('md:OrganizationName', $elm);

        foreach ($elms_name AS $elm_name) {
            if ($elm_name->getAttribute('xml:lang') == 'en') {
                $found_name = true;
                break;
            }
        }

        // Check that at least en english display name exists
        $elms_name = $this->_xpath->query('md:OrganizationDisplayName', $elm);

        foreach ($elms_name AS $elm_name) {
            if ($elm_name->getAttribute('xml:lang') == 'en') {
                $found_display_name = true;
                break;
            }
        }

        // Check that at least en english url exists and no empty URLs are found
        $elms_name = $this->_xpath->query('md:OrganizationURL', $elm);

        foreach ($elms_name AS $elm_name) {
            if ($elm_name->getAttribute('xml:lang') == 'en') {
                $found_url = true;
            }
            if (empty($elm_name->nodeValue)) {
                $this->_logger->logError(
                    'Empty URL found for IdP. Empty URL not allowed.',
                    $elm_name->getLineNo(),
                    $elm_name->parentNode->parentNode->getAttribute('entityID')
                );
                $url_error = false;
            }
        }

        if (!$found_name) {
            $this->_logger->logError(
                'No english name found for IdP.',
                $input_elm->getLineNo(),
                $input_elm->parentNode->getAttribute('entityID')
            );
        }

        if (!$found_display_name) {
            $this->_logger->logError(
                'No english display name found for IdP',
                $input_elm->getLineNo(),
                $input_elm->parentNode->getAttribute('entityID')
            );
        }

        if (!$found_url) {
            $this->_logger->logError(
                'No english URL found for IdP.',
                $input_elm->getLineNo(),
                $input_elm->parentNode->getAttribute('entityID')
            );
        }

        if ($found_name && $found_display_name && $found_url && $url_error) {
            $this->_logger->logSuccess(
                'vOrganization check parsed.',
                $input_elm->getLineNo(),
                $input_elm->parentNode->getAttribute('entityID')
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
        SimpleSAML_Logger::debug('[Kvalidate] Function: _vEnc');
        $kd_found = false;
        $elms     = $this->_xpath->query('md:AssertionConsumerService', $input_elm);

        foreach ($elms AS $elm) {
            $binding = $elm->getAttribute('Binding');

            if ($binding = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST') {
                $location = $elm->getAttribute('Location');

                if (preg_match('/^https/', $location) == 0) {
                    $elms = $this->_xpath->query('md:KeyDescriptor', $input_elm);

                    foreach ($elms AS $elm) {
                        if (   !$elm->hasAttribute('use')
                            || ($elm->hasAttribute('use')
                            && $elm->getAttribute('use') == 'encryption')
                        ) {
                            $kd_found = true;
                        }
                    }
                    if ($kd_found) {
                        $this->_logger->logSuccess(
                            'vEnc check parsed',
                            $input_elm->getLineNo(),
                            $input_elm->parentNode->getAttribute('entityID')
                        );
                        return true;
                    }

                    $this->_logger->logError(
                        'No certificate for encryption found',
                        $input_elm->getLineNo(),
                        $input_elm->parentNode->getAttribute('entityID')
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
        SimpleSAML_Logger::debug('[Kvalidate] Function: _vSign');
        $elms = $this->_xpath->query('md:KeyDescriptor', $input_elm);

        foreach ($elms AS $elm) {
            if (   !$elm->hasAttribute('use')
                || ($elm->hasAttribute('use')
                && $elm->getAttribute('use') == 'signing')
            ) {
                $this->_logger->logSuccess(
                    'vSign check parsed',
                    $input_elm->getLineNo(),
                    $input_elm->parentNode->getAttribute('entityID')
                );
                return true;
            }
        }

        $this->_logger->logError(
            'No certificate for signing found',
            $input_elm->getLineNo(),
            $input_elm->parentNode->getAttribute('entityID')
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
        SimpleSAML_Logger::debug('[Kvalidate] Function: _vRequestAttr');
        $elms = $this->_xpath->query(
            'md:AttributeConsumingService/md:RequestedAttribute',
            $input_elm
        );

        if ($elms->length < 1) {
            $this->_logger->logError(
                'No RequestedAttribute given',
                $input_elm->getLineNo(),
                $input_elm->parentNode->getAttribute('entityID')
            );
            $this->_status = KV_STATUS_ERROR;

            return false;
        }

        $error = false;

        foreach ($elms AS $elm) {
            if (!$elm->hasAttribute('Name')) {
                $this->_logger->logError(
                    'RequestedAttribute do not have the Name attribute',
                    $elm->getLineNo(),
                    $input_elm->parentNode->getAttribute('entityID')
                );
                $this->_status = KV_STATUS_ERROR;
                $error         = true;
            } else {
                $name = $elm->getAttribute('Name');
                preg_match_all('/^urn:oid:([0-9]+\.)*([0-9]+)$/', $name, $matches);
                if (count($matches[0]) != 1) {
                    $this->_logger->logError(
                        'RequestedAttribute do not have the correct Name. The value is required to be in urn:oid format but ' . $name . ' specified',
                        $elm->getLineNo(),
                        $input_elm->parentNode->getAttribute('entityID')
                    );
                    $this->_status = KV_STATUS_ERROR;
                    $error         = true;
                }
            }
            if (!$elm->hasAttribute('NameFormat')) {
                $this->_logger->logError(
                    'RequestedAttribute do not have the NameFormat attribute',
                    $elm->getLineNo(),
                    $input_elm->parentNode->getAttribute('entityID')
                );
                $this->_status = KV_STATUS_ERROR;
                $error         = true;
            } else {
                $nameFormat = $elm->getAttribute('NameFormat');
                if ($nameFormat != 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri') {
                    $this->_logger->logError(
                        'RequestedAttribute do not have the correct NameFormat.<br />\'urn:oasis:names:tc:SAML:2.0:attrname-format:uri\' is required but ' . $nameFormat . ' specified',
                        $elm->getLineNo(),
                        $input_elm->parentNode->getAttribute('entityID')
                    );
                    $this->_status = KV_STATUS_ERROR;
                    $error         = true;
                }
            }
        }

        if (!$error) {
            $this->_logger->logSuccess(
                'vRequestAttr check parsed',
                $input_elm->getLineNo(),
                $input_elm->parentNode->getAttribute('entityID')
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
     * @param string     $id        Id of the parent element. Used for logging
     *
     * @return bool True if the check clears othervise false
     */
    private function _vExtension(DOMElement $input_elm, $id)
    {
        SimpleSAML_Logger::debug('[Kvalidate] Function: _vExtension');
        $error = false;

        $allowed_elements = array(
            'shibmd:Scope',
            'DiscoveryResponse',
            'mdui:UIInfo',
            'mdui:DiscoHints',
            'mdrpi:RegistrationInfo',
            'mdrpi:RegistrationInfo',
            'mdrpi:PublicationPath',
            'mdrpi:PublicationInfo'
        );

        $elms = $this->_xpath->query('md:Extensions', $input_elm);

        foreach ($elms AS $elm) {
            if ($elm->hasChildNodes()) {
                $sub_elms = $elm->childNodes;
                foreach ($sub_elms AS $sub_elm) {
                    $nodeName = $sub_elm->nodeName;
                    if (!in_array($nodeName, $allowed_elements)) {
                        $this->_logger->logError(
                            '`' . $nodeName . '`element is not allowed in the `Extension` element.',
                            $elm->getLineNo(),
                            $id
                        );
                        $error         = true;
                        $this->_status = KV_STATUS_ERROR;
                    }
                }
            }
        }

        if (!$error) {
            $this->_logger->logSuccess(
                'vExtension check parsed',
                $input_elm->getLineNo(),
                $id
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
        SimpleSAML_Logger::debug('[Kvalidate] Function: _vScope');
        $error = false;

        $elms = $this->_xpath->query('md:Extensions/shibmd:Scope', $input_elm);

        if ($elms->length == 0) {
            $this->_logger->logError(
                '`shibmd:Scope` is missing',
                $input_elm->getLineNo(),
                $input_elm->parentNode->getAttribute('entityID')
            );
            $this->_status = KV_STATUS_ERROR;
            return false;
        }

        foreach ($elms AS $elm) {
            if (empty($elm->nodeValue)) {
                $this->_logger->logError(
                    '`shibmd:Scope` element is empty',
                    $elm->getLineNo(),
                    $input_elm->parentNode->getAttribute('entityID')
                );
                $error         = true;
                $this->_status = KV_STATUS_ERROR;
            }
            if ($elm->hasAttribute('regexp')) {
                $attr = $elm->getAttribute('regexp');
                if ($attr != 'false') {
                    $this->_logger->logError(
                        '`regexp` attribute on ´shibmd:Scope´ is set to ' . $attr . '. MUST be set to `false` or omitted',
                        $elm->getLineNo(),
                        $input_elm->parentNode->getAttribute('entityID')
                    );
                    $error         = true;
                    $this->_status = KV_STATUS_ERROR;
                }
            }
        }

        if (!$error) {
            $this->_logger->logSuccess(
                'vScope check parsed',
                $input_elm->getLineNo(),
                $input_elm->parentNode->getAttribute('entityID')
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
        SimpleSAML_Logger::debug('[Kvalidate] Function: _vEntitiesValidateUntil');
        $error = false;
        
        if ($input_elm->hasAttribute('Name')) {
            $id = $input_elm->getAttribute('Name');
        } else if ($input_elm->hasAttribute('ID')) {
            $id = $input_elm->getAttribute('ID');
        } else {
            $id = 'EntitiesDescriptor';
        }

        $att_validUntil = $input_elm->getAttribute('validUntil');

        // Check if the EntitiesDescriptor contains a validUntil attribute
        if (!empty($att_validUntil)) {
            // Validate the timestamp
            $validTime = strtotime($att_validUntil);
            $minTime   = time() + (60*60*6-30);
            $maxTime   = time() + (60*60*240+30);

            if ( ($validTime-$minTime) < 0 ) {
                $this->_logger->logError(
                    'validUntil MUST be at least 6 hours in the future. validUntil set to ' . $att_validUntil . '<br />MUST be at least ' . date('c', $minTime),
                    $input_elm->getLineNo(),
                    $id
                );
                $this->_status = KV_STATUS_ERROR;
                $error         = true;
            }

            if ( ($maxTime - $validTime) < 0 ) {
                $this->_logger->logError(
                    'validUntil MUST not be more that 240 hours in the future. validUntil set to ' . $att_validUntil . '<br />MUST not be more than ' . date('c', $maxTime),
                    $input_elm->getLineNo(),
                    $id
                );
                $this->_status = KV_STATUS_ERROR;
                $error         = true;
            }

            // validUntil is good. No need to check all EntityDescriptor
            if (!$error) {
                $this->_logger->logSuccess(
                    'vEntitiesValidUntil check parsed',
                    $input_elm->getLineNo(),
                    $id
                );
                return true;
            }
        }

        // Validate validUntil on all EntityDescriptor
        $elms = $this->_xpath->query('md:EntityDescriptor', $input_elm);

        $status = array();

        foreach ($elms AS $elm) {
            $status[$input_elm->getAttribute('entityID')]
                = $this->_vEntityValidUntil($elm);
        }

        if (!in_array(false, $status)) {
            $this->_logger->logSuccess(
                'vEntitiesValidUntil check parsed',
                $input_elm->getLineNo(),
                $id
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
        SimpleSAML_Logger::debug('[Kvalidate] Function: _vEntiytyValidUntil');
        $att_validUntil = $input_elm->getAttribute('validUntil');

        if (empty($att_validUntil)) {
            $this->_logger->logError(
                'EntityDescriptor does not contain a validUntil attribute',
                $input_elm->getLineNo(),
                $input_elm->getAttribute('entityID')
            );
            $this->_status = KV_STATUS_ERROR;
            return false;
        } else {
            // Validate the timestamp
            $validTime = strtotime($att_validUntil);
            $minTime   = time() + (60*60*6-30);
            $maxTime   = time() + (60*60*240+30);

            if ( ($validTime-$minTime) < 0 ) {
                $this->_logger->logError(
                    'validUntil MUST be at least 6 hours in the future. validUntil set to ' . $att_validUntil . '<br />MUST be at least ' . date('c', $minTime),
                    $input_elm->getLineNo(),
                    $input_elm->getAttribute('entityID')
                );
                $this->_status = KV_STATUS_ERROR;
                return false;
            }

            if ( ($maxTime - $validTime) < 0 ) {
                $this->_logger->logError(
                    'validUntil MUST not be more that 240 hours in the future. validUntil set to ' . $att_validUntil . '<br />MUST not be more than ' . date('c', $maxTime),
                    $input_elm->getLineNo(),
                    $input_elm->getAttribute('entityID')
                );
                $this->_status = KV_STATUS_ERROR;
                return false;
            }
        }

        $this->_logger->logSuccess(
            'vED check parsed',
            $input_elm->getLineNo(),
            $input_elm->getAttribute('entityID')
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
        SimpleSAML_Logger::debug('[Kvalidate] Function: _vSLO');
        $elms = $this->_xpath->query('md:SingleLogoutService', $input_elm);

        if ($elms->length == 0) {
            return true;
        }

        foreach ($elms AS $elm) {
            $binding = $elm->getAttribute('Binding');

            if ($binding = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-REDIRECT') {
                $this->_logger->logSuccess(
                    'vSLO check parsed',
                    $elm->getLineNo(),
                    $elm->parentNode->parentNode->getAttribute('entityID')
                );
                return true;
            }
        }

        $this->_logger->logError(
            'SingleLogoutService MUST use HTTP-REDIRECT binding. No endpoints using this binding was found.',
            $input_elm->getLineNo(),
            $input_elm->getAttribute('entityID')
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
        SimpleSAML_Logger::debug('[Kvalidate] Function: _vACS');
        $elms = $this->_xpath->query('md:AssertionConsumerService', $input_elm);

        foreach ($elms AS $elm) {
            $binding = $elm->getAttribute('Binding');

            if ($binding == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST') {
                $this->_logger->logSuccess(
                    'vACS check parsed',
                    $elm->getLineNo(),
                    $elm->parentNode->parentNode->getAttribute('entityID')
                );
                return true;
            }
        }

        $this->_logger->logError(
            'AssertionConsumerService MUST use HTTP-POST binding. No endpoints using this binding was found.',
            $input_elm->getLineNo(),
            $input_elm->parentNode->getAttribute('entityID')
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
        SimpleSAML_Logger::debug('[Kvalidate] Function: _vSSO');
        $elms = $this->_xpath->query('md:SingleSignOnService', $input_elm);

        foreach ($elms AS $elm) {
            $binding = $elm->getAttribute('Binding');

            if ($binding == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect') {
                $this->_logger->logSuccess(
                    'vSSO check parsed',
                    $elm->getLineNo(),
                    $elm->parentNode->parentNode->getAttribute('entityID')
                );
                return true;
            }
        }

        $this->_logger->logError(
            'SingleSignOnService MUST use HTTP-Redirect binding. No endpoints using this binding was found.',
            $input_elm->getLineNo(),
            $input_elm->parentNode->getAttribute('entityID')
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
        SimpleSAML_Logger::debug('[Kvalidate] Function: _vEDSignature');
        if ($input_elm->hasAttribute('Name')) {
            $id = $input_elm->getAttribute('Name');
        } else if ($input_elm->hasAttribute('ID')) {
            $id = $input_elm->getAttribute('ID');
        } else {
            $id = 'EntitiesDescriptor';
        }

        try {
            $entity_descriptor = new SAML2_XML_md_EntitiesDescriptor($input_elm);
            
            // Will throw an exception if signature is invalid
            $validCerts = $entity_descriptor->getValidatingCertificates();
            if (empty($validCerts)) {
                throw new Exception('Invalid signature on EntitiesDescriptor');
            }

            if (isset($this->_config['validateFingerprint']) && $this->_config['validateFingerprint'] !== NULL) {
                $found = false;
                $fingerprint = strtolower(str_replace(":", "", $this->_config['validateFingerprint']));
                foreach ($validCerts as $cert) {
                    $fp = strtolower(sha1(base64_decode($cert)));
                    if ($fp === $fingerprint) {
                        $found = true;
                    }
                }
                if (!$found) {
                    throw new Exception('Supplied fingerprint do not match the fingerprint of the signing certificate');
                }
            }
        } catch(Exception $e) {
            $this->_logger->logError(
                $e->getMessage(),
                $input_elm->getLineNo(),
                $id
            );
            $this->_status = KV_STATUS_ERROR;
            return false;
        }

        $this->_logger->logSuccess(
            'vEDSignature check parsed',
            $input_elm->getLineNo(),
            $id
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
     * @todo If both KeyValue and X509Certificate is present, check to see that
     * they both contain the same key.
     * @todo Check to see if the certificates are expired        
     */
    private function _vCert(DOMElement $input_elm)
    {
        SimpleSAML_Logger::debug('[Kvalidate] Function: _vCert');
        $error = 0;

        // Get all KeyDescriptors
        $elms = $this->_xpath->query('md:KeyDescriptor', $input_elm);

        foreach ($elms AS $elm) {
            $elms2 = $this->_xpath->query('ds:KeyInfo', $elm);

            // Check that only one KeyInfo is located in the KeyDescriptor
            if ($elms2->length > 1) {
                $this->_logger->logError(
                    'Only one KeyInfo element allowed. ' . $elms2->length . ' given.',
                    $elm->getLineNo(),
                    $elm->parentNode->parentNode->getAttribute('entityID')
                );
                $this->_status = KV_STATUS_ERROR;
                $error         = 1;
                continue;
            }
            if ($elms2->length < 1) {
                $this->_logger->logError(
                    'One KeyInfo element is required.',
                    $elm->getLineNo(),
                    $elm->parentNode->parentNode->getAttribute('entityID')
                );
                $this->_status = KV_STATUS_ERROR;
                $error         = 1;
                continue;
            }

            // Only one element given
            $elm2 = $elms2->item(0);

            $kv = $this->_xpath->query('ds:KeyValue', $elm2);

            $cert = $this->_xpath->query('ds:X509Data/ds:X509Certificate', $elm2);

            // See if KeyValue or X509Certificate is located in the KeyInfo
            if ($kv->length < 1 && $cert->length != 1) {
                $this->_logger->logError(
                    'At least one KeyValue or only one X509Certificate must be present',
                    $elm->getLineNo(),
                    $elm->parentNode->parentNode->getAttribute('entityID')
                );
                $this->_status = KV_STATUS_ERROR;
                $error         = 1;
                continue;
            }

            if ($cert->length == 1) {
                /**
                 * Validate that the certificate contains a public key
                 * Appending, prepending and wrapping the cert, so that OpenSSL 
                 * will accept it.
                 */
                $key  = wordwrap($cert->item(0)->nodeValue, 64, "\n", true);
                $key  = trim($key);
                $key  = "-----BEGIN CERTIFICATE-----\n" .$key;
                $key .= "\n-----END CERTIFICATE-----";

                $res = openssl_get_publickey($key);

                if (is_resource($res) && get_resource_type($res) == 'OpenSSL key') {
                    $info = openssl_pkey_get_details($res);
                    if (!$info) {
                        while ($msg = openssl_error_string()) {
                            $this->_logger->logError(
                                'OpenSSL error: ' . $msg,
                                $cert->item(0)->getLineNo(),
                                $elm->parentNode->parentNode->getAttribute('entityID')
                            );
                        }
                        $error         = 1;
                        $this->_status = KV_STATUS_ERROR;
                        continue;
                    }

                    // Does the public key has recognized type
                    if ($info['type'] < 0 || $info['type'] > 3) {
                        $this->_logger->logError(
                            'Certificate in the X509Certificate element is not valid',
                            $cert->item(0)->getLineNo(),
                            $elm->parentNode->parentNode->getAttribute('entityID')
                        );
                        $this->_status = KV_STATUS_ERROR;
                        $error         = 1;
                        continue;
                    }
                } else {
                    while ($msg = openssl_error_string()) {
                        $this->_logger->logError(
                            'OpenSSL error: ' . $msg,
                            $cert->item(0)->getLineNo(),
                            $elm->parentNode->parentNode->getAttribute('entityID')
                        );
                    }
                    $error         = 1;
                    $this->_status = KV_STATUS_ERROR;
                }
            }
        }
        if ($error == 0) {
            $this->_logger->logSuccess(
                'vCert check parsed',
                $input_elm->getLineNo(),
                $input_elm->parentNode->getAttribute('entityID')
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
        SimpleSAML_Logger::debug('[Kvalidate] Function: _vSchema');

        $elm = $input_elm->documentElement;

        if ($elm->hasAttribute('Name')) {
            $id = $elm->getAttribute('Name');
        } else if ($elm->hasAttribute('ID')) {
            $id = $elm->getAttribute('ID');
        } else {
            $id = 'EntitiesDescriptor';
        }

        if ($input_elm->schemaValidate($this->_schema)) {
            $this->_logger->logSuccess(
                'vSchema check parsed',
                $input_elm->getLineNo(),
                $id
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
     * @return void
     * @see PHP_MANUAL#book.libxml.php 
     */
    private function _getLibxmlErrors()
    {
        SimpleSAML_Logger::debug('[Kvalidate] Function: _getLibxmlErrors');
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

            $this->_logger->logError(
                $error->file ?
                trim($error->message) . ' in <b>' . $error->file . '</b>' :
                trim($error->message),
                $error->line
            );
        }
        libxml_clear_errors();
    }

    /**
     * Get that status of the last validation
     *
     * @return int Status flag
     * @see    KV_STATUS_UNDEFINED, 
     * @see    KV_STATUS_SUCCESS, 
     * @see    KV_STATUS_WARNING, 
     * @see    KV_STATUS_ERROR
     */
    public function getStatus()
    {
        SimpleSAML_Logger::debug('[Kvalidate] Function: getStatus');
        return $this->_status;
    }

    /**
     * Get logger
     *
     * @return array Array of messages
     */
    public function getLogger()
    {
        SimpleSAML_Logger::debug('[Kvalidate] Function: getLogger');
        return $this->_logger;
    }
}
