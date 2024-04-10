<?php

use MongoDB\Client;
use MongoDB\Driver\ServerApi;

require_once __DIR__ . '/vendor/autoload.php';

//Dotenv\Dotenv::createUnsafeImmutable(__DIR__)->safeLoad();

header("Content-Type: application/json");

$uri = $_ENV["DB_URI"];
$apiVersion = new ServerApi(ServerApi::V1);
$sslContext = stream_context_create([
    'ssl' => [
        'allow_self_signed' => false,
        'verify_peer' => true,
        'verify_peer_name' => true
    ]
]);
$info = [
    "method" => $_SERVER["REQUEST_METHOD"],
    "endpoint" => explode("?", $_SERVER['REQUEST_URI'])[0]
];
function callMongoDB($info, $sslContext, $uri, $apiVersion): void
{
    try {
        $client = new Client($uri, [], ['serverApi' => $apiVersion], [], false, $sslContext);
        $client->selectDatabase('admin')->command(['ping' => 1]);
        $db = $client->selectDatabase('hackathon');
        if ($info['endpoint'] == "/user/get" && $info['method'] == "GET") {
            $col = $db->selectCollection('userlist');
            $cursor = $col->find([]);
            $res = [];
            foreach ($cursor as $user) {
                $res[] = $user;
            };
            echo json_encode($res);
            return;
        }
        if ($info['endpoint'] == "/user/signup" && $info['method'] == "POST") {
            $input = file_get_contents('php://input', true);
            $body = json_decode($input);
            if (!isset($body->nom) || !isset($body->mail) || !isset($body->password)) {
                echo "{\"error\":\"missing argument\"}";
                return;
            }
            $hashedPassword = hash('sha256', $body->password);
            $col = $db->selectCollection('userlist');
            $cursor = $col->insertOne([
                "nom" => $body->nom,
                "mail" => $body->mail,
                "password" => $hashedPassword
            ]);
            echo json_encode($body);
            return;
        }
        if ($info["endpoint"] == "/user/delete" && $info["method"]== "POST"){
            $input = file_get_contents('php://input', true);
            $col = $db->selectCollection('userlist');
            $body = json_decode($input);
            if(!isset($body->mail) || !isset($body->password)) {
                echo "{\"error\":\"missing argument\"}";
                return;
            }
            $hashedPassword = hash('sha256', $body->password);
            $col = $db->selectCollection('userlist');
            $cursor = $col->deleteOne([
                "mail" => $body->mail,
                "password" => $hashedPassword
            ]);
            echo "{\"mail\":\"{$body->mail}\"}";
            return;
        }
        
        echo "{\"error\":\"bad request\"}";
        http_response_code(404);
        
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}

callMongoDB($info, $sslContext, $uri, $apiVersion);
