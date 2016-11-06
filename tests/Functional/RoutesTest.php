<?php

namespace Tests\Functional;

use PDO;

class RoutesTest extends BaseTestCase
{

    public function testATokenShouldBeReturnAfterAValidUserAuthentication()
    {
        // Send post request with credential for authentication.
        $response = $this->runApp('POST', '/authenticate', ['user_login' => 'neo', 'user_password' => 'neo']);

        $this->assertEquals(200, $response->getStatusCode());

        // Then assert that we have three string sequence separated by dot returned as a token.
        $this->assertRegExp('#[a-zA-Z0-9-_](\.[a-zA-Z0-9-_])*$#', json_decode($response->getBody())->token);
    }

    public function testAValidUserShouldHaveAccessToARestrictedEndPoint()
    {
        // Authenticate first
        $response = $this->runApp('POST', '/authenticate', ['user_login' => 'keanu', 'user_password' => 'keanu']);

        $this->assertEquals(200, $response->getStatusCode());

        // Use the returned token to request the restricted endpoint.
        $response = $this->runApp('GET', '/restricted', [],
            ["HTTP_AUTHORIZATION" => json_decode($response->getBody())->token]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('This is your secure resource !', (string)$response->getBody());

    }

    public function testANoneRegisteredUserShouldNotBeAuthenticated()
    {
        $response = $this->runApp('POST', '/authenticate', ['user_login' => 'michael', 'user_password' => '123pass']);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('No user found', (string)$response->getBody());
    }

}