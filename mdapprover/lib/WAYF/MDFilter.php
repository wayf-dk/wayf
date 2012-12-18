<?php

namespace WAYF;

class MDfilter
{
    public $accerpted = array();

    public function __construct(\WAYF\IdentityMap $accepted)
    {
        $this->accepted = $accepted;
    }

    public function filter($xml)
    {
        $dom                     = new \DOMDocument();
        $dom->preserveWhiteSpace = true;

        // TODO handle load errors
        $dom->loadXML($xml);

        // Register XPath object
        $xpath = new \DOMXPath($dom);

        // Register namespaces
        $namespaces = array(
            'md' => 'urn:oasis:names:tc:SAML:2.0:metadata',
            'ds' => 'http://www.w3.org/2000/09/xmldsig#',
            'shibmd' => 'urn:mace:shibboleth:metadata:1.0',
        );

        foreach ($namespaces AS $key => $value) {
            $xpath->registerNamespace($key, $value);
        }

        // Remove signature
        $signature = $xpath->query('ds:Signature[1]')->item(0);
        $signature->parentNode->removeChild($signature); 

        $elms = $xpath->query('md:EntityDescriptor');

        $entities = array();
        foreach ($elms AS $elm) {
            $entityid = $elm->getAttribute('entityID');

            if (!$this->accepted->hasId($entityid)) {
                $elm->parentNode->removeChild($elm);
            }
        }

        return $dom->saveXML();
    }
}
