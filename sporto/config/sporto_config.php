<?php
$sporto_config = array(
    // IdP metadata - WAYF QA IdP
    'idp_certificate' => "MIIE0TCCA7mgAwIBAgIDAz0zMA0GCSqGSIb3DQEBBQUAMDwxCzAJBgNVBAYTAlVTMRcwFQYDVQQKEw5HZW9UcnVzdCwgSW5jLjEUMBIGA1UEAxMLUmFwaWRTU0wgQ0EwHhcNMTEwOTE5MTIxMTU2WhcNMTMwOTIxMjE0MTA3WjCB5zEpMCcGA1UEBRMgQndiYjFwanExcnVxVjY1SkQtYVQxbG56MkhSNEloNmwxCzAJBgNVBAYTAkRLMRkwFwYDVQQKExBiZXRhd2F5Zi53YXlmLmRrMRMwEQYDVQQLEwpHVDQxNDkzMzk0MTEwLwYDVQQLEyhTZWUgd3d3LnJhcGlkc3NsLmNvbS9yZXNvdXJjZXMvY3BzIChjKTExMS8wLQYDVQQLEyZEb21haW4gQ29udHJvbCBWYWxpZGF0ZWQgLSBSYXBpZFNTTChSKTEZMBcGA1UEAxMQYmV0YXdheWYud2F5Zi5kazCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAKrI9JW7o6Ze5KxjzcQxhWzSt24p2Q5Ml60/8YEo592X0Q21Beuc4S985eXBdB9bHalM93zas48QkTjfc7l6utym9oQ/74azqiVhLRhZYZCiYisXOPc+23eIiNZQha3M0hzKlaW9ClTQwtkwMAYngxj4RUFxvekKabfKVzt902oDpGKC4LnkC3GK4h+AgRMJ/1vcmctYqP3csovySxHJk4YaCrPIv2QmcjTEjyPC1tI2cvdK2tPRNub5YaeWK6i2pqtljTmplWsvkPqrV4R+FNqp0qFIVbSzHAE/ElC9TF1HBoc7gKFqZRS4zpddwCD7KwzDrthqEFt0okq6yWIUAF0CAwEAAaOCAS4wggEqMB8GA1UdIwQYMBaAFGtpPWoYQkrdjwJlOf01JIZ4kRYwMA4GA1UdDwEB/wQEAwIFoDAdBgNVHSUEFjAUBggrBgEFBQcDAQYIKwYBBQUHAwIwGwYDVR0RBBQwEoIQYmV0YXdheWYud2F5Zi5kazBDBgNVHR8EPDA6MDigNqA0hjJodHRwOi8vcmFwaWRzc2wtY3JsLmdlb3RydXN0LmNvbS9jcmxzL3JhcGlkc3NsLmNybDAdBgNVHQ4EFgQUxY35xmL5h0aTrWnAyCo0yuLjhnYwDAYDVR0TAQH/BAIwADBJBggrBgEFBQcBAQQ9MDswOQYIKwYBBQUHMAKGLWh0dHA6Ly9yYXBpZHNzbC1haWEuZ2VvdHJ1c3QuY29tL3JhcGlkc3NsLmNydDANBgkqhkiG9w0BAQUFAAOCAQEAJ3mSATIjbik/sN/yY79Gf+FkVSzhNywgJADn+IdrqQ31243wBHWr+qbpwWCGD2TFqiwuVOf9pghJH8/S9zRnGD6qtW2Wj0BuuvXuTDTmkNpas4cYoWgvRTKynmwuKjsXcQRnPzYk2twsHncwgndWdAqIWbKkDS6F14+NKpa44jsZcXiQ/s6A7XiD/WizGkFFKqvbP6CoMWH1m9AXbmfnTMZ9t/vm0D0PrZEl4jzjKVLjGDhIR7q5xxy41q8iOFxMAYaDxuVmsKJ0xNVhFpFIJi9QxRMXVmil9DSndRIQSt1Vc5Qdt7PGo64AXUQIoRmJ53aKf3xDxOmP79rXVGdKqA==",
    'sso' => 'https://betawayf.wayf.dk/saml2/idp/SSOService.php',

    // Private metadata
    'private_key' => "MIIEowIBAAKCAQEAwp1om6W/BUKZppbkbJHGnElzDtghDNoJxEcfbc6IOh0rgv+W/tUXoR4+g5D/D2UjO0bzXvWNjKDFc9o8qSqn4NbDKZn1EbRQ8gVGEiDGysYKT9FvOEp6B9Ri7wf9EDzk74IRD9YOSA2Llclf41bpQJ2jL07MbVWYhIVftFYDPbaVmgIHkQ8wV/gcyoIlRrOuc/KnAPkuvrORvk31t0eHL+gFd3XR8KYVqSVJ2NhqTx90l++eWzMWhM9ACwrfwshJ0A1aziFldjRWU5yr8T8LlZvu0GUhvap2nwkw6GwoFGDa05t0b1iDFmGVyfke4Ld4BTpGjA6lvGRnHDb6ka80fwIDAQABAoIBACja3qD+NJGoH5VnS+C24ZjhmnPdT2LhSveXbrOgjdyVTxMbENnCZkl+jeUUxVa4BqNlC6Y9qk+BWLIveFiTCdcbfrD3Dwl+bxe/n6wikVj0JioHb2/DwsZuAa7oYGnOPslA5tAKQclCfrEdKzIQhrr33NALnMK/G1uGnEbBX8DOe8fdXYYjfsoXfjQZKTlFU5hMo8G5GlLbOwL6kIZhR7L0bBiSWdy6dktvx3tqQ0iB5biJDQJ4ifcqNhjSsQ7lMatQos9CePH3IO/gA9krR5wZ522Er5WiMngsoSxCPgnFNtvozlE3lrYOr3nvAzZgBzLIhrrjHfS61Lh3sdHULCECgYEA7YK3GNRTGDpRLlPZa9+W2g+18NhvxjDg4xaBBLueOKlvNAl5aCTk4dL+AeoClmn63vq9uSQqX+J1xSso/7Ht1cp9PI1gOCIkxUgvzwxGxd4vQwHFKmzTZ8F5ZBTI5gDEVtgQ7yz7ZDEBiaIT/l7VVLpzymbrJ3JWFGtTAS+6iikCgYEA0cPWA+vw9YOKa2ahoPtdpD4f+xLACerxRnNMaTTKIO7R2nRN3kIEWyMcseXyGRAam0zCch5oAx2tAOcWWadrW1bCgSvtfvgjm8fv8C/TJ58O0wrMh140R2zH24wVsBVDQ1HZJqi0ubc9e9NfDuqLlZnABMVs3XM87RIWbpSkbmcCgYAQ9tovGtNIkrnDrleEPfcfYinjpwHszQbzEWNrvB7j+y4nMFoMlz4F5zUfW+CNb8psbMpqU+v250z0JU4LXWEYeRsS3SI3QDESKkLH7h+L+H+1sIWtrxI2gfoyrM1gqENd1Jb5DmRyVpG+i+YTsCBaqeqlVU6Mhb0iPjwyhH81aQKBgQDNf5mOxfqNy4wzo5v8ZcVbPjF+euP/01cDubjF6J2MneqgpQgUEYDK+B8IeUkwHIK0WgK0Ye1sAAqYs8tFkaqvFNQAT9SfauEXoEwDdhba3gxb3Fx60WNOBdfV0er9UhdPQEQIh4Zl2oo1YOHjbwvIR5PCGNeKK3comWu4cY6VSQKBgCsWBtIMiDwm3VwHM1YQdALhpqT1yhFIh/l6R7fie/a3r3jTvJa6vg2j9LkfrAH0VErZ5YUydimfGOIcLz3EC3CjZvwar/Suml47CgjcwqMTrLPm7zU6mBIULF2GY4o24TRpyQGdwvrlDEegjSeDOCHT8A2/6BlQwnCZrGGvuTKo",
    'public_key' => "MIIEmDCCA4CgAwIBAgIJAPozXCUfQIeoMA0GCSqGSIb3DQEBBQUAMIGOMQswCQYDVQQGEwJESzETMBEGA1UECBMKU29tZS1TdGF0ZTEiMCAGA1UEChMZV0FZRiAtIFdoZXJlIEFyZSBZb3UgRnJvbTEMMAoGA1UECxMDUm5EMRswGQYDVQQDExJKYWNvYiBDaHJpc3RpYW5zZW4xGzAZBgkqhkiG9w0BCQEWDGphY2hAd2F5Zi5kazAeFw0xMjA1MTEwODM4NTRaFw0yMjA1MTEwODM4NTRaMIGOMQswCQYDVQQGEwJESzETMBEGA1UECBMKU29tZS1TdGF0ZTEiMCAGA1UEChMZV0FZRiAtIFdoZXJlIEFyZSBZb3UgRnJvbTEMMAoGA1UECxMDUm5EMRswGQYDVQQDExJKYWNvYiBDaHJpc3RpYW5zZW4xGzAZBgkqhkiG9w0BCQEWDGphY2hAd2F5Zi5kazCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAMKdaJulvwVCmaaW5GyRxpxJcw7YIQzaCcRHH23OiDodK4L/lv7VF6EePoOQ/w9lIztG8171jYygxXPaPKkqp+DWwymZ9RG0UPIFRhIgxsrGCk/RbzhKegfUYu8H/RA85O+CEQ/WDkgNi5XJX+NW6UCdoy9OzG1VmISFX7RWAz22lZoCB5EPMFf4HMqCJUazrnPypwD5Lr6zkb5N9bdHhy/oBXd10fCmFaklSdjYak8fdJfvnlszFoTPQAsK38LISdANWs4hZXY0VlOcq/E/C5Wb7tBlIb2qdp8JMOhsKBRg2tObdG9YgxZhlcn5HuC3eAU6RowOpbxkZxw2+pGvNH8CAwEAAaOB9jCB8zAdBgNVHQ4EFgQUzeFPVta/srdUDB7MMOZ2Rpr/NrMwgcMGA1UdIwSBuzCBuIAUzeFPVta/srdUDB7MMOZ2Rpr/NrOhgZSkgZEwgY4xCzAJBgNVBAYTAkRLMRMwEQYDVQQIEwpTb21lLVN0YXRlMSIwIAYDVQQKExlXQVlGIC0gV2hlcmUgQXJlIFlvdSBGcm9tMQwwCgYDVQQLEwNSbkQxGzAZBgNVBAMTEkphY29iIENocmlzdGlhbnNlbjEbMBkGCSqGSIb3DQEJARYMamFjaEB3YXlmLmRrggkA+jNcJR9Ah6gwDAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQUFAAOCAQEAhAkfaSzaPFhpQIQlgdW7oSMtNahcIkjeu+55u1lEsiuexrKhpp3sOkMYpgDTPW1gC12QPFW0RbzsLGTJHDnv0yt7B+1xu4U9mmTfLcA3nu2SUjVil9108wl1XevP+ujgIXYsLRqFrTb2ACAqwSo+qtPu9do/mbZeKjSjkn5RUCCyebmQgyAa+0vyckRtKQ+pw+u3UgMycwgCBCPjqH5hPK40EvkcYYBIQeIcqTCFj24HyuuLpI+lXqDbgZgjppr3whKkAEOMvs3A4+nGsXsoNOqAvt1uH33x6La8DIlcnlwVZIjOIGtvcVQIxaKvNWHDm6XGvEp6exFanPoqg15Ciw==",
    'asc' => 'http://newstat.test.wayf.dk/',
	'entityid' =>  'http://newstat.test.wayf.dk/',
);
