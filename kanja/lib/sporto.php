<?php

/**
 * sporto.php is a minimal sp php implementation for use in a hub federation as wayf.dk.
 * It 'embeds' the information needed to:
 * - send a signed AuthnRequest to an idp - only one idp supported
 * - receive and verify a signed SAMLResponse 
 * - it accepts an optional list of idpproviderids used for scoping
 * 
 * it returns an array of the attributes in the AttributeStatement of the response
 *
 * Issues:
 * - check timingvalidation
*/
class config {
    // WAYF test IdP
    public $idp_certificate = "MIIEZzCCA0+gAwIBAgILAQAAAAABID3xVZIwDQYJKoZIhvcNAQEFBQAwajEjMCEGA1UECxMaT3JnYW5pemF0aW9uIFZhbGlkYXRpb24gQ0ExEzARBgNVBAoTCkdsb2JhbFNpZ24xLjAsBgNVBAMTJUdsb2JhbFNpZ24gT3JnYW5pemF0aW9uIFZhbGlkYXRpb24gQ0EwHhcNMDkwMzI1MTMwNTE0WhcNMTIwNTA5MDcwNzU3WjCBgzELMAkGA1UEBhMCREsxETAPBgNVBAgTCE9kZW5zZSBNMREwDwYDVQQHEwhPZGVuc2UgTTEbMBkGA1UECxMSV0FZRiAtIFNlY3JldGFyaWF0MR0wGwYDVQQKExRTeWRkYW5zayBVbml2ZXJzaXRldDESMBAGA1UEAxQJKi53YXlmLmRrMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDBsuiyO84OVwkKR0TL6w8viWV4jMg+Jy7LgiEtYfHdnVBCvdM9XJJetS0MiJtulBH4/4ZWrfeGeHgLPvSjp6FiRdI1nDg/33ofc0TdNytxX4tBCzvxM0C4yCCaEXda+tqXJmGua+mVubMhS8kizHjL+s7A8xUqXoEFqOMHtgqoAQIDAQABo4IBdjCCAXIwHwYDVR0jBBgwFoAUfW0q7Garp1E2qwJp8XCPxFkLmh8wSQYIKwYBBQUHAQEEPTA7MDkGCCsGAQUFBzAChi1odHRwOi8vc2VjdXJlLmdsb2JhbHNpZ24ubmV0L2NhY2VydC9vcmd2MS5jcnQwPwYDVR0fBDgwNjA0oDKgMIYuaHR0cDovL2NybC5nbG9iYWxzaWduLm5ldC9Pcmdhbml6YXRpb25WYWwxLmNybDAdBgNVHQ4EFgQUvlkjTc0iuzcvi752QgktLT01obgwCQYDVR0TBAIwADAOBgNVHQ8BAf8EBAMCBaAwKQYDVR0lBCIwIAYIKwYBBQUHAwEGCCsGAQUFBwMCBgorBgEEAYI3CgMDMEsGA1UdIAREMEIwQAYJKwYBBAGgMgEUMDMwMQYIKwYBBQUHAgEWJWh0dHA6Ly93d3cuZ2xvYmFsc2lnbi5uZXQvcmVwb3NpdG9yeS8wEQYJYIZIAYb4QgEBBAQDAgbAMA0GCSqGSIb3DQEBBQUAA4IBAQCKPVJYHjKOrzWtjPBTEJOwIzE0wSIcA+9+GNR5Pvk+6OTf2QTUDDHpXiiIEcYPL1kN/BEvA+N2y+7qyI5MlL7DNIu9clx1lcqhXiQ0lWcu7Bmb7VNPKq5WS1W81GhbZrO6BJtsQctU6odDXMoORay7FxnaxGHOaJlCSQDgT7QrRhzyd80X8NxrSV25byCTb31du8xoO+WagnqAp6xbKs6IsESDw2r/i3rLOXbL37B7lnbjcLC963xN6j7+kiyqiCjvrP0GLfSV4/FN9i9hWrdMlcbnvr23yz5Jflc1oFPtJx7GZqtV0uTijGxCr+aRaUzBPqc3kyavHJcCsn5TcL1t";    
    public $sso = 'https://testbridge.wayf.dk/saml2/idp/SSOService.php';
    public $sp_key = "MIIEpAIBAAKCAQEA4zabvUIPjducOdh5sIcN/mqZX2T0WtJsyo5dduZwDQ9LVUyL6AP7NMbAtqs4bMDlIZiRzRwZqJ5BYq+rg0zwKHgeeiiW59yg+1xZoZvJTqavduA/t9/91fAJgXlL4oNdqxug4T0xNgXtir9NQ/mN3XNcAFQXC8ky0WzQyyeVIOYAPn9qgNeHpfI0DZzZYRPk5axZyTDwfolFkJLDl9JEqEaxKrmeKmv3AfTwM8kUKuohTHgoTJf02SMJNT4v1yGFA5a8+7T/9JyVkI6ST1HwovrxIYGGtlvhXB3XefvzoIh+BEZKmhyiI4aH95Ji4rmDAfdsjleuvtTmGy4n7xY8kQIDAQABAoIBABJ4zhCE3l7aC8loH/yJ/klAwVdQDc2UbePJzy9rbJCjPayhnH4wVKvlvKUdJmkxBW/Fx5S1RCDBPO+Y+IikKKIhodislBglh7DnwSGyWJtcUceIU6XPQAO0kBFGmzS/GU5f4KRvJSEPOlCdXNq53x6x0jj0nSsIIGmGPzNE9IrIAOVTmyu7Lk1wL4e0c1vw6Lm84G07R3Sw2WCQicM4htLQvWSmJvkoi2VtumoLtJQePZI91EhHoDGJwvUXysQBv8fpmBGJEXiXZdgjtD3yuj85n3n2NLF4jJHARVb5IwHp8ml8O8ydX7z6OIaSMNqAiHBlrdklyh3DqOKDnoZ7OkECgYEA9B1njd9Rl8s0ZhUHshDmtE7ZIL9Me4Vt3XwgqP21NBQ3V6KY4kKmFL0UgnLmHtCBuqCArspPWo33ONliDEMhaUOxfdd+zYLhnxlkbVuGnIM6FWaPB2uSlulfZ+BIT5xat8TRGgRFPdwluUIz29+/3mAZK4r0ARgME4WQhKlUVCsCgYEA7kaL7AnFQzxynGQE3LlK8pN7GEPBoxnD3BiU+Jh3S/9S/coSVnYEtD2E8+obJQZ6AGb/nh3FgUCy5rVKFGeUTHyP6grJp/xzitwu7kpUi2GueGMI2yzv0stN2uFFOin+q/HgkEGa1Y8p9eCslcbx0GhNCnyuF5IicvXhPU9IaDMCgYAn3AlFnBpJi8+Rf4uTIrlY5asEctf9L0tCJ/t5PHvy9f4XXCBUiYduyXTo2/QBQhB8taptX/FXGuksKiqdT/TLqFb58k7tJZrLjKzeQlyXf8HjLqzaDFGSPKbsYi3Mef5CFWwDMIFR2Xl7Z3jqRP2iRNS3TGgNKa0HHv76+l8fowKBgQCh6976MEmVP2bDUFTdii2cWwyzmJfmNoQa9bmGllW7l27WeJOtndXkhknrV5PKvXP7AgODd8fX5hetIFWPODRXJe7GpT4UokICg06BMJHzhhYCSYqjJw5yuSXXMG7S7+bZAi4Q2gRWTEu/g0bFIcUCU17HWaMU8YHnjZ/bAh26cQKBgQDa4+d0KJEJvWhwVeV5NtZDpBUDRAQtNZLqe9RIDeyIn5WSFv+QZBCnkYhgip5xrXKpnepG5qkc4ThQng1YX7kStgBv0Xtrfq7KJzCJXTVsAQVM2Yc3ntsasJ6azkJivvNeY9XUdv+Z5e797+UIIu75SMCBuJb26Om1coMmaHyjJw==";
    public $asc = 'http://kanja2.test.wayf.dk/dyntabs.php';
	public $sp =  'http://kanja2.test.wayf.dk/sporto.php';
}

function sporto($config, $providerids = array()) {
	if (isset($_POST['SAMLResponse'])) {
		$message = base64_decode($_POST['SAMLResponse']);
	    $document = new DOMDocument();
    	$document->loadXML($message);
    	$xp = new DomXPath($document);
    	$xp->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
    	$xp->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');
    	$xp->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
		#verifySignature($config->idp_certificate, $xp, false);
		verifySignature($config->idp_certificate, $xp, true);
		verifyTimingEtc($config, $xp);
		return extractAttributes($xp);
	} else {
		$id =  '_' . sha1(uniqid(mt_rand(), true));
      	$issueInstant = gmdate('Y-m-d\TH:i:s\Z', time());
      	$sp = $config->sp;
      	$asc = $config->asc;
      	$sso = $config->sso;
		$scoping = '';
		foreach($providerids as $provider) {
        	$scoping .= "<samlp:IDPEntry ProviderID=\"$provider\"/>";
        }
		if ($scoping) $scoping = '<samlp:Scoping><samlp:IDPList>'.$scoping . '</samlp:IDPList></samlp:Scoping>';

		$request = <<<eof
<?xml version="1.0"?>
<samlp:AuthnRequest
	ID="$id"
	Version="2.0"
	IssueInstant="$issueInstant"
	Destination="$sso"
	AssertionConsumerServiceURL="$asc" 
	ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" 
	xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol">
	<saml:Issuer xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">$sp</saml:Issuer>
	$scoping
</samlp:AuthnRequest>
eof;

		$queryString = "SAMLRequest=" . urlencode(base64_encode(gzdeflate($request)));;
		$queryString .= '&SigAlg=' . urlencode('http://www.w3.org/2000/09/xmldsig#rsa-sha1');

		$key = openssl_pkey_get_private("-----BEGIN RSA PRIVATE KEY-----\n" . chunk_split($config->sp_key, 64) ."-----END RSA PRIVATE KEY-----");

		$signature = "";
		openssl_sign($queryString, $signature, $key, OPENSSL_ALGO_SHA1);
		openssl_free_key($key);

        header('Location: ' .  $config->sso . "?" . $queryString . '&Signature=' . urlencode(base64_encode($signature)));
        exit;
	}
}

function extractAttributes($xp)
{
	$res = array();
    $attributes  = $xp->query("/samlp:Response/saml:Assertion/saml:AttributeStatement/saml:Attribute");
  	foreach($attributes as $attribute) {
  		$valuearray = array();
		$values = $xp->query('./saml:AttributeValue', $attribute);
		foreach($values as $value) {
			$valuearray[] = $value->textContent;
		}
		$res[$attribute->getAttribute('Name')] = $valuearray;
	}
	return $res;
}

function verifySignature($publicKey, $xp, $assertion = true)
{
	if ($assertion) $context = $xp->query('/samlp:Response/saml:Assertion')->item(0);
	else $context = $xp->query('/samlp:Response')->item(0);


	//validateElement($context);

    $signatureValue = base64_decode($xp->query('ds:Signature/ds:SignatureValue', $context)->item(0)->textContent);
    $digestValue    = base64_decode($xp->query('ds:Signature/ds:SignedInfo/ds:Reference/ds:DigestValue', $context)->item(0)->textContent);
    #print_r(base64_encode($signatureValue)); exit;
	$id = $xp->query('@ID', $context)->item(0)->value;

    $signedElement  = $context;
    $signature      = $xp->query("ds:Signature", $signedElement)->item(0);    
    $signedInfo     = $xp->query("ds:SignedInfo", $signature)->item(0)->C14N(true, false);
    $signature->parentNode->removeChild($signature);
    $canonicalXml = $signedElement->C14N(true, false);
    #print_r($canonicalXml); exit;

    $publicKey = openssl_get_publickey("-----BEGIN CERTIFICATE-----\n" . chunk_split($publicKey, 64) . "-----END CERTIFICATE-----");

	if (!((sha1($canonicalXml, TRUE) == $digestValue) && openssl_verify($signedInfo, $signatureValue, $publicKey) == 1)) {
		throw new Exception('Error verifying incoming SAMLResponse' . PHP_EOL . openssl_error_string() . PHP_EOL . 'SAMLResponse: ' . print_r(htmlspecialchars($canonicalXml), 1));
	}
}

function verifyTimingEtc($config, $xp)
{
	$skew = 60;
	$aShortWhileAgo = gmdate('Y-m-d\TH:i:s\Z', time() - $skew);
	$inAShortWhile = gmdate('Y-m-d\TH:i:s\Z', time() + $skew);
	$issues = array();

	$destination = $xp->query('/samlp:Response/@Destination')->item(0)->value;
   	if ($destination != null && $destination != $config->asc) { // Destination is optional
   		$issues[] = "Destination: {$message['_Destination']} is not here; message not destined for us";
	}

	$assertion = $xp->query('/samlp:Response/saml:Assertion')->item(0);

	$subjectConfirmationData_NotBefore = $xp->query('./saml:Subject/saml:SubjectConfirmation/saml:SubjectConfirmationData/@NotBefore', $assertion);
	if ($subjectConfirmationData_NotBefore->length  && $aShortWhileAgo < $subjectConfirmationData_NotBefore->item(0)->value) {
		$issues[] = 'SubjectConfirmation not valid yet';
	}

	$subjectConfirmationData_NotOnOrAfter = $xp->query('./saml:Subject/saml:SubjectConfirmation/saml:SubjectConfirmationData/@NotOnOrAfter', $assertion);
	if ($subjectConfirmationData_NotOnOrAfter->length && $inAShortWhile >= $subjectConfirmationData_NotOnOrAfter->item(0)->value) {
		$issues[] = 'SubjectConfirmation too old';
	}

	$conditions_NotBefore = $xp->query('./saml:Conditions/@NotBefore', $assertion);
	if ($conditions_NotBefore->length && $aShortWhileAgo > $conditions_NotBefore->item(0)->value) {
			$issues[] = 'Assertion Conditions not yet valid';
	}

	$conditions_NotOnOrAfter = $xp->query('./saml:Conditions/@NotOnOrAfter', $assertion);
	if ($conditions_NotOnOrAfter->length && $aShortWhileAgo >= $conditions_NotOnOrAfter->item(0)->value) {
			$issues[] = 'Assertions Condition too old';
	}

	$authStatement_SessionNotOnOrAfter = $xp->query('./saml:AuthStatement/@SessionNotOnOrAfter', $assertion);
	if ($authStatement_SessionNotOnOrAfter->length && $aShortWhileAgo >= $authStatement_SessionNotOnOrAfter->item(0)->value) {
		$issues[] = 'AuthnStatement Session too old';
	}

	if (!empty($issues)) {
		throw new Exception('Problems detected with response. ' . PHP_EOL. 'Issues: ' . PHP_EOL . implode(PHP_EOL, $issues));
	}
}
