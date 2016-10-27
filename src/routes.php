<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use \Firebase\JWT\JWT;


// Authenticate route.
$app->post('/authenticate', function (Request $request, Response $response) {
    $data = $request->getParsedBody();

    $result = file_get_contents('./users.json');
    $users = json_decode($result, true);

    $login = $data['user_login'];
    $password = $data['user_password'];

    foreach ($users as $key => $user) {
        if ($user['user_login'] == $login && $user['user_pwd'] == $password) {
            $current_user = $user;
        }
    }

    if (!isset($current_user)) {
        return "No user found";
    } else {

        // Find a corresponding token.
        $sql = "SELECT * FROM tokens
            WHERE user_id = :user_id AND date_expiration >" . time();

        $token_from_db = false;
        try {
            $db = $this->db;
            $stmt = $db->prepare($sql);
            $stmt->bindParam("user_id", $current_user['user_id']);
            $stmt->execute();
            $token_from_db = $stmt->fetchObject();
            $db = null;

            if ($token_from_db) {
                echo json_encode([
                    "token"      => $token_from_db->value,
                    "user_login" => $token_from_db->user_id
                ]);
            }
        } catch (PDOException $e) {
            echo '{"error":{"text":' . $e->getMessage() . '}}';
        }

        // Create a new token if a user is found but not a token corresponding to whom.
        if (count($current_user) != 0 && !$token_from_db) {

            $key = "your_secret_key";

            $payload = array(
                "iss"     => "http://your-domain.com",
                "iat"     => time(),
                "exp"     => time() + (3600 * 24 * 15),
                "context" => [
                    "user" => [
                        "user_login" => $current_user['user_login'],
                        "user_id"    => $current_user['user_id']
                    ]
                ]
            );

            try {
                $jwt = JWT::encode($payload, $key);
            } catch (Exception $e) {
                echo json_encode($e);
            }

            $sql = "INSERT INTO tokens (user_id, value, date_created, date_expiration)
                VALUES (:user_id, :value, :date_created, :date_expiration)";
            try {
                $db = $this->db;
                $stmt = $db->prepare($sql);
                $stmt->bindParam("user_id", $current_user['user_id']);
                $stmt->bindParam("value", $jwt);
                $stmt->bindParam("date_created", $payload['iat']);
                $stmt->bindParam("date_expiration", $payload['exp']);
                $stmt->execute();
                $db = null;

                echo json_encode([
                    "token"      => $jwt,
                    "user_login" => $current_user['user_id']
                ]);
            } catch (PDOException $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        }
    }
});

// The route to get a secured data.
$app->get('/restricted', function (Request $request, Response $response) {

    $jwt = $request->getHeaders();

    $key = "example_key";

    try {
        $decoded = JWT::decode($jwt['HTTP_AUTHORIZATION'][0], $key, array('HS256'));
    } catch (UnexpectedValueException $e) {
        echo $e->getMessage();
    }

    if (isset($decoded)) {
        $sql = "SELECT * FROM tokens WHERE user_id = :user_id";

        try {
            $db = $this->db;
            $stmt = $db->prepare($sql);
            $stmt->bindParam("user_id", $decoded->context->user->user_id);
            $stmt->execute();
            $user_from_db = $stmt->fetchObject();
            $db = null;

            if (isset($user_from_db->user_id)) {
                echo json_encode([
                    "response" => "This is your secure resource !"
                ]);
            }
        } catch (PDOException $e) {
            echo '{"error":{"text":' . $e->getMessage() . '}}';
        }
    }
});

