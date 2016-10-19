<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use \Firebase\JWT\JWT;

require __DIR__ . '/../vendor/autoload.php';

// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);

$container = $app->getContainer();

// Setting up the db
$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=tokens", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $pdo;
};

$app->post('/connexion', function (Request $request, Response $response) {
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
        echo json_encode('No user found');
        exit;
    }

    $key = "example_key";

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

    // Find an existing token.
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

    if (count($current_user) != 0 && !$token_from_db) {

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
});

$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

$app->get('/secured-data', function (Request $request, Response $response) {

    $jwt = $request->getHeaders();

    $key = "example_key";

    try {
        $decoded = JWT::decode($jwt['HTTP_AUTHORIZATION'][0], $key, array('HS256'));
    } catch (UnexpectedValueException $e) {
        var_dump($e->getMessage());
    }

    if (isset($decoded)) {
        $sql = "SELECT * FROM tokens WHERE user_id = :user_id";
        $user_from_db = new StdClass();

        try {
            $db = $this->db;
            $stmt = $db->prepare($sql);
            $stmt->bindParam("user_id", $decoded->context->user->user_id);
            $stmt->execute();
            $user_from_db = $stmt->fetchObject();
            $db = null;

            if (isset($user_from_db->user_id)) {
                echo json_encode([
                    "response" => "This is your secure ressource !"
                ]);
            }
        } catch (PDOException $e) {
            echo '{"error":{"text":' . $e->getMessage() . '}}';
        }
    }
});


$app->run();
