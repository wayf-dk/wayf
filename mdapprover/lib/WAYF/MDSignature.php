<?php
namespace WAYF;

class MDSignature
{
    public function verifySignature($xml, $certificate)
    {
        $dom                     = new \DOMDocument();
        $dom->preserveWhiteSpace = true;

        $dom->loadXML($xml);

        $context = $dom->documentElement;

        // Register XPath object
        $xpath = new \DOMXPath($dom);

        // Register namespaces
        $namespaces = array(
            'md' => 'urn:oasis:names:tc:SAML:2.0:metadata',
            'ds' => 'http://www.w3.org/2000/09/xmldsig#',
            'shibmd' => 'urn:mace:shibboleth:metadata:1.0',
            'ec' => 'http://www.w3.org/2001/10/xml-exc-c14n#',
        );

        foreach ($namespaces AS $key => $value) {
            $xpath->registerNamespace($key, $value);
        }
        
        // Get signature and digest value
        $signatureValue = base64_decode($xpath->query('ds:Signature[1]/ds:SignatureValue[1]', $context)->item(0)->textContent);
        $digestValue    = base64_decode($xpath->query('ds:Signature[1]/ds:SignedInfo[1]/ds:Reference[1]/ds:DigestValue[1]', $context)->item(0)->textContent);
        $id = $xpath->query('@ID', $context)->item(0)->value;

        $signedElement  = $context;
        $signature      = $xpath->query("ds:Signature[1]", $signedElement)->item(0);    
        $signedInfo     = $xpath->query("ds:SignedInfo[1]", $signature)->item(0)->C14N(true, false);
        $signature->parentNode->removeChild($signature);

        // Include namespaces in canonicalization
        if ($xpath->query("ds:SignedInfo[1]/ds:Reference[1]/ds:Transforms[1]/ds:Transform/ec:InclusiveNamespaces[1]", $signature)->length == 1) {
            $canonicalXml = $signedElement->C14N(false, false);
        } else {
            $canonicalXml = $signedElement->C14N(true, false);
        }; 

        // Get IdP certificate
        $publicKey = openssl_get_publickey("-----BEGIN CERTIFICATE-----\n" . chunk_split($certificate, 64) . "-----END CERTIFICATE-----");
        if (!$publicKey) {           
            throw new \Exception('Invalid public key used'); 
        }

        // Verify signature
        if (!((sha1($canonicalXml, true) == $digestValue) && openssl_verify($signedInfo, $signatureValue, $publicKey) == 1)) {
            throw new \Exception('Error verifying signature' . PHP_EOL . openssl_error_string() . PHP_EOL . 'Metadata: ' . print_r(htmlspecialchars($canonicalXml), 1));
        } 
        return true;
    }
}
