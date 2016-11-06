<?php

namespace Tests\Functional;

use PDO;

class RoutesTest extends BaseTestCase
{

    public function __construct()
    {
        $this->db = [
            'host' => '127.0.0.1',
            'name' => 'tokens',
            'user' => 'root',
            'password' => ''
        ];

        $this->pdo = new PDO("mysql:host=" . $this->db['host'] . ";dbname=" . $this->db['name'], $this->db['user'], $this->db['password']);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function testAuthenticateUser()
    {
        // Send post request with credential for authentication.
        $response = $this->runApp('POST', '/authenticate', ['user_login' => 'neo', 'user_password' => 'neo']);

        $this->assertEquals(200, $response->getStatusCode());

        // Then assert that we have three string sequence separated by dot.
        $this->assertRegExp('#[a-zA-Z0-9-_](\.[a-zA-Z0-9-_])*$#', json_decode($response->getBody())->token);
    }

    public function testAuthenticateAnotherUser()
    {
        $response = $this->runApp('POST', '/authenticate', ['user_login' => 'keanu', 'user_password' => 'keanu']);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertRegExp('#[a-zA-Z0-9-_](\.[a-zA-Z0-9-_])*$#', json_decode($response->getBody())->token);
    }

    public function testCredentialsNotFound()
    {
        $response = $this->runApp('POST', '/authenticate', ['user_login' => '', 'user_password' => '']);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('No user found', (string)$response->getBody());
    }


    public function testGetRestrictedResource()
    {
        // Grab a token from database.
        $sql = "SELECT * FROM tokens";

        $token_from_db = false;
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $token_from_db = $stmt->fetchObject()->value;
            $db = null;

        } catch (PDOException $e) {
            echo '{"error":{"text":' . $e->getMessage() . '}}';
        }

        // This token should be present within the database.
        $response = $this->runApp('GET', '/restricted', [], ["HTTP_AUTHORIZATION" => $token_from_db]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('This is your secure resource !', (string)$response->getBody());
    }

}