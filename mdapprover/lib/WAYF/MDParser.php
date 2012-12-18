<?php

namespace WAYF;

class MDParser
{
    private $config = array();

    public function __construct($config = array())
    {
        $this->config = $config;
    }

    public function parse($feed)
    {
        $xml                     = new \DOMDocument();
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput       = true;

        // Break on load error
        $xml->load($feed);

        // Register XPath object
        $xpath = new \DOMXPath($xml);

        // Register namespaces
        $namespaces = array(
            'md' => 'urn:oasis:names:tc:SAML:2.0:metadata',
            'ds' => 'http://www.w3.org/2000/09/xmldsig#',
            'shibmd' => 'urn:mace:shibboleth:metadata:1.0',
        );

        foreach ($namespaces AS $key => $value) {
            $xpath->registerNamespace($key, $value);
        }

        $elms = $xpath->query('md:EntityDescriptor');

        $entities = array();
        foreach ($elms AS $elm) {
            $entityid = $elm->getAttribute('entityID');

            $name = '';
            $tmp = $xpath->query('md:SPSSODescriptor/md:AttributeConsumingService/md:ServiceName[@xml:lang = "en"]', $elm);
            if ($tmp->length > 0) {
                $name = $tmp->item(0)->nodeValue;
            }

            $description = '';
            $tmp = $xpath->query('md:SPSSODescriptor/md:AttributeConsumingService/md:ServiceDescription[@xml:lang = "en"]', $elm);
            if ($tmp->length > 0) {
                $description = $tmp->item(0)->nodeValue;
            }

            $attributes = array();
            $tmp = $xpath->query('md:SPSSODescriptor/md:AttributeConsumingService/md:RequestedAttribute', $elm);
            if ($tmp->length > 0) {
                foreach ($tmp AS $attr) {
                    $attributes[] = $attr->getAttribute('Name');
                }
            } 

            $entity = new \WAYF\Entity();
            $entity->entityid = $entityid;
            $entity->name = $name;
            $entity->purpose = $description;
            $entity->attributes = $attributes;
            $entity->user = "";

            $entities[] = $entity;
        }

        return $entities;
    }
}
