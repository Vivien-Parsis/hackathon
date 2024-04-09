<?php

use MongoDB\Client;
use MongoDB\Driver\ServerApi;

require_once __DIR__ . '/vendor/autoload.php';

//Dotenv\Dotenv::createUnsafeImmutable(__DIR__)->safeLoad();

header("Content-Type: application/json");

$uri = "mongodb+srv://{$_ENV["DB_USER"]}:{$_ENV["DB_PASSWORD"]}@cluster0.5bdccxd.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0&ssl=true";
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
            $col = $db->selectCollection('userlist');
            $body = json_decode($input);
            if (!isset($body->nom) || !isset($body->mail)) {
                echo "{'error':'missing argument'}";
                return;
            }
            $cursor = $col->insertOne([
                "nom" => $body->nom,
                "mail" => $body->mail
            ]);
            echo "nom:{$body->nom}|mail:{$body->mail}";
        }
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}

callMongoDB($info, $sslContext, $uri, $apiVersion);
