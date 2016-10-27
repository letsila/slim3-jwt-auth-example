<?php

namespace Tests\Functional;

class RoutesTest extends BaseTestCase
{
    public function testGetRestrictedResource()
    {
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC95b3VyLWRvbWFpbi5jb20iLCJpYXQiOjE0NzY3ODc1NTgsImV4cCI6MTQ3ODA4MzU1OCwiY29udGV4dCI6eyJ1c2VyIjp7InVzZXJfbG9naW4iOiJicmlhemFtIiwidXNlcl9pZCI6IjE2NyJ9fX0.wE8D79lgvtwTFC2i5zfCfRpb2xeoO66IPvQer3k9Adc";
        $response = $this->runApp('GET', '/restricted', [], ["HTTP_AUTHORIZATION" => $token]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('This is your secure resource !', (string)$response->getBody());
    }

    public function testAuthenticateUser()
    {
        $response = $this->runApp('POST', '/authenticate', ['user_login' => 'briazam', 'user_password' => 'briazam']);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertRegExp('#[a-zA-Z0-9-_](\.[a-zA-Z0-9-_])*$#', json_decode($response->getBody())->token);
    }

    public function testAuthenticateAnotherUser()
    {
        $response = $this->runApp('POST', '/authenticate', ['user_login' => 'cybelar', 'user_password' => 'cybelar']);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertRegExp('#[a-zA-Z0-9-_](\.[a-zA-Z0-9-_])*$#', json_decode($response->getBody())->token);
    }

    public function testCredentialsNotFound()
    {
        $response = $this->runApp('POST', '/authenticate', ['user_login' => '', 'user_password' => '']);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('No user found', (string)$response->getBody());
    }
}