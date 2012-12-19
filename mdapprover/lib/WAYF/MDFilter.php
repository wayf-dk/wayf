<?php

namespace WAYF;

class MDfilter
{
    public $accerpted = array();
    public $removechanged = true;
    public $changedentities = array();

    public function __construct(\WAYF\IdentityMap $accepted, $config)
    {
        $this->accepted = $accepted;
        if (isset($config['removeChangedEntities'])) {
            $this->removechanged = (bool)$removechanged;
        }
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
            } else { 
                // Remove entity if it has changed
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

                $origEntity = $this->accepted->getObject($entityid);
                
                // Compare original entity to newly parsed
                if (!$origEntity->isEquivalent($entity)) {
                    $this->changedentities[] = $entity;
                    if ($this->removechanged) {
                        $elm->parentNode->removeChild($elm);
                    }
                }
            }
        }


        return $dom->saveXML();
    }
}
