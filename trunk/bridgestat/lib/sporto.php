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
    // WAYFQA IdP
    public $idp_certificate = "MIIE0TCCA7mgAwIBAgIDAz0zMA0GCSqGSIb3DQEBBQUAMDwxCzAJBgNVBAYTAlVTMRcwFQYDVQQKEw5HZW9UcnVzdCwgSW5jLjEUMBIGA1UEAxMLUmFwaWRTU0wgQ0EwHhcNMTEwOTE5MTIxMTU2WhcNMTMwOTIxMjE0MTA3WjCB5zEpMCcGA1UEBRMgQndiYjFwanExcnVxVjY1SkQtYVQxbG56MkhSNEloNmwxCzAJBgNVBAYTAkRLMRkwFwYDVQQKExBiZXRhd2F5Zi53YXlmLmRrMRMwEQYDVQQLEwpHVDQxNDkzMzk0MTEwLwYDVQQLEyhTZWUgd3d3LnJhcGlkc3NsLmNvbS9yZXNvdXJjZXMvY3BzIChjKTExMS8wLQYDVQQLEyZEb21haW4gQ29udHJvbCBWYWxpZGF0ZWQgLSBSYXBpZFNTTChSKTEZMBcGA1UEAxMQYmV0YXdheWYud2F5Zi5kazCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAKrI9JW7o6Ze5KxjzcQxhWzSt24p2Q5Ml60/8YEo592X0Q21Beuc4S985eXBdB9bHalM93zas48QkTjfc7l6utym9oQ/74azqiVhLRhZYZCiYisXOPc+23eIiNZQha3M0hzKlaW9ClTQwtkwMAYngxj4RUFxvekKabfKVzt902oDpGKC4LnkC3GK4h+AgRMJ/1vcmctYqP3csovySxHJk4YaCrPIv2QmcjTEjyPC1tI2cvdK2tPRNub5YaeWK6i2pqtljTmplWsvkPqrV4R+FNqp0qFIVbSzHAE/ElC9TF1HBoc7gKFqZRS4zpddwCD7KwzDrthqEFt0okq6yWIUAF0CAwEAAaOCAS4wggEqMB8GA1UdIwQYMBaAFGtpPWoYQkrdjwJlOf01JIZ4kRYwMA4GA1UdDwEB/wQEAwIFoDAdBgNVHSUEFjAUBggrBgEFBQcDAQYIKwYBBQUHAwIwGwYDVR0RBBQwEoIQYmV0YXdheWYud2F5Zi5kazBDBgNVHR8EPDA6MDigNqA0hjJodHRwOi8vcmFwaWRzc2wtY3JsLmdlb3RydXN0LmNvbS9jcmxzL3JhcGlkc3NsLmNybDAdBgNVHQ4EFgQUxY35xmL5h0aTrWnAyCo0yuLjhnYwDAYDVR0TAQH/BAIwADBJBggrBgEFBQcBAQQ9MDswOQYIKwYBBQUHMAKGLWh0dHA6Ly9yYXBpZHNzbC1haWEuZ2VvdHJ1c3QuY29tL3JhcGlkc3NsLmNydDANBgkqhkiG9w0BAQUFAAOCAQEAJ3mSATIjbik/sN/yY79Gf+FkVSzhNywgJADn+IdrqQ31243wBHWr+qbpwWCGD2TFqiwuVOf9pghJH8/S9zRnGD6qtW2Wj0BuuvXuTDTmkNpas4cYoWgvRTKynmwuKjsXcQRnPzYk2twsHncwgndWdAqIWbKkDS6F14+NKpa44jsZcXiQ/s6A7XiD/WizGkFFKqvbP6CoMWH1m9AXbmfnTMZ9t/vm0D0PrZEl4jzjKVLjGDhIR7q5xxy41q8iOFxMAYaDxuVmsKJ0xNVhFpFIJi9QxRMXVmil9DSndRIQSt1Vc5Qdt7PGo64AXUQIoRmJ53aKf3xDxOmP79rXVGdKqA==";
    public $sso = 'https://betawayf.wayf.dk/saml2/idp/SSOService.php';
    public $sp_key = "MIIEpAIBAAKCAQEA4zabvUIPjducOdh5sIcN/mqZX2T0WtJsyo5dduZwDQ9LVUyL6AP7NMbAtqs4bMDlIZiRzRwZqJ5BYq+rg0zwKHgeeiiW59yg+1xZoZvJTqavduA/t9/91fAJgXlL4oNdqxug4T0xNgXtir9NQ/mN3XNcAFQXC8ky0WzQyyeVIOYAPn9qgNeHpfI0DZzZYRPk5axZyTDwfolFkJLDl9JEqEaxKrmeKmv3AfTwM8kUKuohTHgoTJf02SMJNT4v1yGFA5a8+7T/9JyVkI6ST1HwovrxIYGGtlvhXB3XefvzoIh+BEZKmhyiI4aH95Ji4rmDAfdsjleuvtTmGy4n7xY8kQIDAQABAoIBABJ4zhCE3l7aC8loH/yJ/klAwVdQDc2UbePJzy9rbJCjPayhnH4wVKvlvKUdJmkxBW/Fx5S1RCDBPO+Y+IikKKIhodislBglh7DnwSGyWJtcUceIU6XPQAO0kBFGmzS/GU5f4KRvJSEPOlCdXNq53x6x0jj0nSsIIGmGPzNE9IrIAOVTmyu7Lk1wL4e0c1vw6Lm84G07R3Sw2WCQicM4htLQvWSmJvkoi2VtumoLtJQePZI91EhHoDGJwvUXysQBv8fpmBGJEXiXZdgjtD3yuj85n3n2NLF4jJHARVb5IwHp8ml8O8ydX7z6OIaSMNqAiHBlrdklyh3DqOKDnoZ7OkECgYEA9B1njd9Rl8s0ZhUHshDmtE7ZIL9Me4Vt3XwgqP21NBQ3V6KY4kKmFL0UgnLmHtCBuqCArspPWo33ONliDEMhaUOxfdd+zYLhnxlkbVuGnIM6FWaPB2uSlulfZ+BIT5xat8TRGgRFPdwluUIz29+/3mAZK4r0ARgME4WQhKlUVCsCgYEA7kaL7AnFQzxynGQE3LlK8pN7GEPBoxnD3BiU+Jh3S/9S/coSVnYEtD2E8+obJQZ6AGb/nh3FgUCy5rVKFGeUTHyP6grJp/xzitwu7kpUi2GueGMI2yzv0stN2uFFOin+q/HgkEGa1Y8p9eCslcbx0GhNCnyuF5IicvXhPU9IaDMCgYAn3AlFnBpJi8+Rf4uTIrlY5asEctf9L0tCJ/t5PHvy9f4XXCBUiYduyXTo2/QBQhB8taptX/FXGuksKiqdT/TLqFb58k7tJZrLjKzeQlyXf8HjLqzaDFGSPKbsYi3Mef5CFWwDMIFR2Xl7Z3jqRP2iRNS3TGgNKa0HHv76+l8fowKBgQCh6976MEmVP2bDUFTdii2cWwyzmJfmNoQa9bmGllW7l27WeJOtndXkhknrV5PKvXP7AgODd8fX5hetIFWPODRXJe7GpT4UokICg06BMJHzhhYCSYqjJw5yuSXXMG7S7+bZAi4Q2gRWTEu/g0bFIcUCU17HWaMU8YHnjZ/bAh26cQKBgQDa4+d0KJEJvWhwVeV5NtZDpBUDRAQtNZLqe9RIDeyIn5WSFv+QZBCnkYhgip5xrXKpnepG5qkc4ThQng1YX7kStgBv0Xtrfq7KJzCJXTVsAQVM2Yc3ntsasJ6azkJivvNeY9XUdv+Z5e797+UIIu75SMCBuJb26Om1coMmaHyjJw==";
    public $asc = 'http://newstat.test.wayf.dk/stat.php';
	public $sp =  'http://newstat.test.wayf.dk/stat.php';
}
/*
class config {
        public $idp_certificate = 'MIIE0TCCA7mgAwIBAgIDAz0zMA0GCSqGSIb3DQEBBQUAMDwxCzAJBgNVBAYTAlVTMRcwFQYDVQQKEw5HZW9UcnVzdCwgSW5jLjEUMBIGA1UEAxMLUmFwaWRTU0wgQ0EwHhcNMTEwOTE5MTIxMTU2WhcNMTMwOTIxMjE0MTA3WjCB5zEpMCcGA1UEBRMgQndiYjFwanExcnVxVjY1SkQtYVQxbG56MkhSNEloNmwxCzAJBgNVBAYTAkRLMRkwFwYDVQQKExBiZXRhd2F5Zi53YXlmLmRrMRMwEQYDVQQLEwpHVDQxNDkzMzk0MTEwLwYDVQQLEyhTZWUgd3d3LnJhcGlkc3NsLmNvbS9yZXNvdXJjZXMvY3BzIChjKTExMS8wLQYDVQQLEyZEb21haW4gQ29udHJvbCBWYWxpZGF0ZWQgLSBSYXBpZFNTTChSKTEZMBcGA1UEAxMQYmV0YXdheWYud2F5Zi5kazCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAKrI9JW7o6Ze5KxjzcQxhWzSt24p2Q5Ml60/8YEo592X0Q21Beuc4S985eXBdB9bHalM93zas48QkTjfc7l6utym9oQ/74azqiVhLRhZYZCiYisXOPc+23eIiNZQha3M0hzKlaW9ClTQwtkwMAYngxj4RUFxvekKabfKVzt902oDpGKC4LnkC3GK4h+AgRMJ/1vcmctYqP3csovySxHJk4YaCrPIv2QmcjTEjyPC1tI2cvdK2tPRNub5YaeWK6i2pqtljTmplWsvkPqrV4R+FNqp0qFIVbSzHAE/ElC9TF1HBoc7gKFqZRS4zpddwCD7KwzDrthqEFt0okq6yWIUAF0CAwEAAaOCAS4wggEqMB8GA1UdIwQYMBaAFGtpPWoYQkrdjwJlOf01JIZ4kRYwMA4GA1UdDwEB/wQEAwIFoDAdBgNVHSUEFjAUBggrBgEFBQcDAQYIKwYBBQUHAwIwGwYDVR0RBBQwEoIQYmV0YXdheWYud2F5Zi5kazBDBgNVHR8EPDA6MDigNqA0hjJodHRwOi8vcmFwaWRzc2wtY3JsLmdlb3RydXN0LmNvbS9jcmxzL3JhcGlkc3NsLmNybDAdBgNVHQ4EFgQUxY35xmL5h0aTrWnAyCo0yuLjhnYwDAYDVR0TAQH/BAIwADBJBggrBgEFBQcBAQQ9MDswOQYIKwYBBQUHMAKGLWh0dHA6Ly9yYXBpZHNzbC1haWEuZ2VvdHJ1c3QuY29tL3JhcGlkc3NsLmNydDANBgkqhkiG9w0BAQUFAAOCAQEAJ3mSATIjbik/sN/yY79Gf+FkVSzhNywgJADn+IdrqQ31243wBHWr+qbpwWCGD2TFqiwuVOf9pghJH8/S9zRnGD6qtW2Wj0BuuvXuTDTmkNpas4cYoWgvRTKynmwuKjsXcQRnPzYk2twsHncwgndWdAqIWbKkDS6F14+NKpa44jsZcXiQ/s6A7XiD/WizGkFFKqvbP6CoMWH1m9AXbmfnTMZ9t/vm0D0PrZEl4jzjKVLjGDhIR7q5xxy41q8iOFxMAYaDxuVmsKJ0xNVhFpFIJi9QxRMXVmil9DSndRIQSt1Vc5Qdt7PGo64AXUQIoRmJ53aKf3xDxOmP79rXVGdKqA==';
 	    //public $sso = 'https://testbridge.wayf.dk/saml2/idp/SSOService.php';
 	    public $sso = 'https://betawayf.wayf.dk/saml2/idp/SSOService.php';

        public $sp_key = "MIIEpAIBAAKCAQEA4zabvUIPjducOdh5sIcN/mqZX2T0WtJsyo5dduZwDQ9LVUyL6AP7NMbAtqs4bMDlIZiRzRwZqJ5BYq+rg0zwKHgeeiiW59yg+1xZoZvJTqavduA/t9/91fAJgXlL4oNdqxug4T0xNgXtir9NQ/mN3XNcAFQXC8ky0WzQyyeVIOYAPn9qgNeHpfI0DZzZYRPk5axZyTDwfolFkJLDl9JEqEaxKrmeKmv3AfTwM8kUKuohTHgoTJf02SMJNT4v1yGFA5a8+7T/9JyVkI6ST1HwovrxIYGGtlvhXB3XefvzoIh+BEZKmhyiI4aH95Ji4rmDAfdsjleuvtTmGy4n7xY8kQIDAQABAoIBABJ4zhCE3l7aC8loH/yJ/klAwVdQDc2UbePJzy9rbJCjPayhnH4wVKvlvKUdJmkxBW/Fx5S1RCDBPO+Y+IikKKIhodislBglh7DnwSGyWJtcUceIU6XPQAO0kBFGmzS/GU5f4KRvJSEPOlCdXNq53x6x0jj0nSsIIGmGPzNE9IrIAOVTmyu7Lk1wL4e0c1vw6Lm84G07R3Sw2WCQicM4htLQvWSmJvkoi2VtumoLtJQePZI91EhHoDGJwvUXysQBv8fpmBGJEXiXZdgjtD3yuj85n3n2NLF4jJHARVb5IwHp8ml8O8ydX7z6OIaSMNqAiHBlrdklyh3DqOKDnoZ7OkECgYEA9B1njd9Rl8s0ZhUHshDmtE7ZIL9Me4Vt3XwgqP21NBQ3V6KY4kKmFL0UgnLmHtCBuqCArspPWo33ONliDEMhaUOxfdd+zYLhnxlkbVuGnIM6FWaPB2uSlulfZ+BIT5xat8TRGgRFPdwluUIz29+/3mAZK4r0ARgME4WQhKlUVCsCgYEA7kaL7AnFQzxynGQE3LlK8pN7GEPBoxnD3BiU+Jh3S/9S/coSVnYEtD2E8+obJQZ6AGb/nh3FgUCy5rVKFGeUTHyP6grJp/xzitwu7kpUi2GueGMI2yzv0stN2uFFOin+q/HgkEGa1Y8p9eCslcbx0GhNCnyuF5IicvXhPU9IaDMCgYAn3AlFnBpJi8+Rf4uTIrlY5asEctf9L0tCJ/t5PHvy9f4XXCBUiYduyXTo2/QBQhB8taptX/FXGuksKiqdT/TLqFb58k7tJZrLjKzeQlyXf8HjLqzaDFGSPKbsYi3Mef5CFWwDMIFR2Xl7Z3jqRP2iRNS3TGgNKa0HHv76+l8fowKBgQCh6976MEmVP2bDUFTdii2cWwyzmJfmNoQa9bmGllW7l27WeJOtndXkhknrV5PKvXP7AgODd8fX5hetIFWPODRXJe7GpT4UokICg06BMJHzhhYCSYqjJw5yuSXXMG7S7+bZAi4Q2gRWTEu/g0bFIcUCU17HWaMU8YHnjZ/bAh26cQKBgQDa4+d0KJEJvWhwVeV5NtZDpBUDRAQtNZLqe9RIDeyIn5WSFv+QZBCnkYhgip5xrXKpnepG5qkc4ThQng1YX7kStgBv0Xtrfq7KJzCJXTVsAQVM2Yc3ntsasJ6azkJivvNeY9XUdv+Z5e797+UIIu75SMCBuJb26Om1coMmaHyjJw==";
	// not really needed - but handy for 'exporting' metadata
	//const sp_certificate = "MIIEWjCCA0KgAwIBAgIJAMRqkZpQwwy9MA0GCSqGSIb3DQEBBQUAMHsxCzAJBgNVBAYTAkRLMRMwEQYDVQQIEwpTb21lLVN0YXRlMQwwCgYDVQQHEwNDcGgxDjAMBgNVBAoTBUthbmphMRswGQYDVQQDExJrYW5qYS50ZXN0LndheWYuZGsxHDAaBgkqhkiG9w0BCQEWDWZyZWVrQHdheWYuZGswHhcNMTEwNDA1MTEwMjU3WhcNMTQwNDA0MTEwMjU3WjB7MQswCQYDVQQGEwJESzETMBEGA1UECBMKU29tZS1TdGF0ZTEMMAoGA1UEBxMDQ3BoMQ4wDAYDVQQKEwVLYW5qYTEbMBkGA1UEAxMSa2FuamEudGVzdC53YXlmLmRrMRwwGgYJKoZIhvcNAQkBFg1mcmVla0B3YXlmLmRrMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA4zabvUIPjducOdh5sIcN/mqZX2T0WtJsyo5dduZwDQ9LVUyL6AP7NMbAtqs4bMDlIZiRzRwZqJ5BYq+rg0zwKHgeeiiW59yg+1xZoZvJTqavduA/t9/91fAJgXlL4oNdqxug4T0xNgXtir9NQ/mN3XNcAFQXC8ky0WzQyyeVIOYAPn9qgNeHpfI0DZzZYRPk5axZyTDwfolFkJLDl9JEqEaxKrmeKmv3AfTwM8kUKuohTHgoTJf02SMJNT4v1yGFA5a8+7T/9JyVkI6ST1HwovrxIYGGtlvhXB3XefvzoIh+BEZKmhyiI4aH95Ji4rmDAfdsjleuvtTmGy4n7xY8kQIDAQABo4HgMIHdMB0GA1UdDgQWBBRM9BkvNiEuGbx7YRIa5Jp92JoB4jCBrQYDVR0jBIGlMIGigBRM9BkvNiEuGbx7YRIa5Jp92JoB4qF/pH0wezELMAkGA1UEBhMCREsxEzARBgNVBAgTClNvbWUtU3RhdGUxDDAKBgNVBAcTA0NwaDEOMAwGA1UEChMFS2FuamExGzAZBgNVBAMTEmthbmphLnRlc3Qud2F5Zi5kazEcMBoGCSqGSIb3DQEJARYNZnJlZWtAd2F5Zi5ka4IJAMRqkZpQwwy9MAwGA1UdEwQFMAMBAf8wDQYJKoZIhvcNAQEFBQADggEBACK+nl5+VI/PySUux10GrFj/yzxt9rakGtpzd9Rv7r1851ufqNVkcuElZNFzXzEulyj/z9GGYfmIVbNUIA0vV/OPkErSQqTnGIfI1TUrG2WQKYzxvm6eeOBmfIHg3OMFXk1hJL9ZpXvOlZBUiRyVqFQgrcLDVGkkFCrmSAYupo3c4sixvL48KL3VyIEdaLFNPHPobRjph/9M4ZztQJgoDv6U/UdNafpqik4Y79uaVLmlrLrBDOpX6Gu3YijYPlS0SpyjeccSrdm2rfdR3eHEYjnNaKWGKiilOFZYc6eSBFZkAMnI6BqITjYpkl4uysYJL5e5O6fIHOmSY1er5gW46rI=";

        /*
	// could be computed - but handy for 'exporting' metadata
	public $asc = 'http://kanja.test.wayf.dk/dyntabs.php';
	public $sp =  'http://kanja.test.wayf.dk/sporto.php';
    public $asc = 'http://newstat.test.wayf.dk/';
	public $sp =  'http://newstat.test.wayf.dk/';
}
 */
function sporto($config, $providerids = array()) {
	if (isset($_POST['SAMLResponse'])) {
		$message = base64_decode($_POST['SAMLResponse']);
	    $document = new DOMDocument();
    	$document->loadXML($message);
    	$xp = new DomXPath($document);
    	$xp->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
    	$xp->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');
    	$xp->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
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
	$id = $xp->query('@ID', $context)->item(0)->value;

    $signedElement  = $context;
    $signature      = $xp->query("ds:Signature", $signedElement)->item(0);    
    $signedInfo     = $xp->query("ds:SignedInfo", $signature)->item(0)->C14N(true, false);
    $signature->parentNode->removeChild($signature);
    $canonicalXml = $signedElement->C14N(true, false);

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
