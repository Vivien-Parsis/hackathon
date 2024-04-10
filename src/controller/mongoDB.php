<?php

require_once './src/controller/createToken.php';
require_once './src/controller/parseToken.php';
class MongoDBController
{
    private $info;
    private $db;

    public function __construct()
    {
        $this->info = [
            "method" => $_SERVER["REQUEST_METHOD"],
            "endpoint" => explode("?", $_SERVER['REQUEST_URI'])[0]
        ];
        $ssl = stream_context_create([
            'ssl' => [
                'allow_self_signed' => false,
                'verify_peer' => true,
                'verify_peer_name' => true
            ]
        ]);
        $client = new MongoDB\Client($_ENV["DB_URI"], [], ['serverApi' => new MongoDB\Driver\ServerApi(MongoDB\Driver\ServerApi::V1)], [], false, $ssl);
        $this->db = $client->selectDatabase('hackathon');
    }

    function callMongoDB(): void
    {
        try {
            if ($this->info["endpoint"] == "/") {
                echo "hackathon api";
                return;
            }
            if ($this->info['endpoint'] == "/user/get" && $this->info['method'] == "GET") {
                if (!checkJWT()) {
                    echo "{\"error\":\"not Authorized\"}";
                    http_response_code(401);
                    return;
                }
                $col = $this->db->selectCollection('userlist');
                $cursor = $col->find([], ["projection" => ["nom" => 1, "password" => 1, "_id" => 0, "mail" => 1]]);
                $res = [];
                foreach ($cursor as $user) {
                    $res[] = $user;
                };
                echo json_encode($res);
                return;
            }
            if ($this->info['endpoint'] == "/user/signup" && $this->info['method'] == "POST") {
                $input = file_get_contents('php://input', true);
                $body = json_decode($input);
                if (!isset($body->nom) || !isset($body->mail) || !isset($body->password)) {
                    echo "{\"error\":\"missing argument\"}";
                    return;
                }
                $hashedPassword = hash('sha256', $body->password);
                $col = $this->db->selectCollection('userlist');
                $cursor = $col->find(["mail" => $body->mail]);
                if (count($cursor->toArray()) >= 1) {
                    echo "{\"error\":\"already exist\"}";
                    return;
                }
                $cursor = $col->insertOne([
                    "nom" => $body->nom,
                    "mail" => $body->mail,
                    "password" => $hashedPassword
                ]);
                $token = createToken($body->mail, $body->nom, $body->password);
                echo "{\"jwt\":\"{$token}\"}";
                return;
            }
            if ($this->info['endpoint'] == "/user/signin" && $this->info['method'] == "POST") {
                $input = file_get_contents('php://input', true);
                $body = json_decode($input);
                if (!isset($body->mail) || !isset($body->password)) {
                    echo "{\"error\":\"missing argument\"}";
                    return;
                }
                $hashedPassword = hash('sha256', $body->password);
                $col = $this->db->selectCollection('userlist');
                $cursor = $col->find(["mail" => $body->mail, "password" => $hashedPassword]);
                if (count($cursor->toArray()) == 0) {
                    echo "{\"error\":\"user doesn't exist or incorect \"}";
                    return;
                }
                $cursor = $col->find(
                    ["mail" => $body->mail, "password" => $hashedPassword],
                    ["projection" => ["nom" => 1, "password" => 1, "_id" => 0, "mail" => 1]]
                );
                $token = createToken($body->mail, $body->nom, $body->password);
                echo "{\"jwt\":\"{$token}\"}";
                return;
            }
            if ($this->info["endpoint"] == "/user/delete" && $this->info["method"] == "POST") {
                $input = file_get_contents('php://input', true);
                $col = $this->db->selectCollection('userlist');
                $body = json_decode($input);
                if (!isset($body->mail) || !isset($body->password)) {
                    echo "{\"error\":\"missing argument\"}";
                    return;
                }
                $hashedPassword = hash('sha256', $body->password);
                $col = $this->db->selectCollection('userlist');
                $cursor = $col->deleteOne([
                    "mail" => $body->mail,
                    "password" => $hashedPassword
                ]);
                echo "{\"mail\":\"{$body->mail}\"}";
                return;
            }
            if ($this->info["endpoint"] == "/user/update" && $this->info["method"] == "POST") {
                $input = file_get_contents('php://input', true);
                $col = $this->db->selectCollection('userlist');
                $body = json_decode($input);
                if (!isset($body->mail) || !isset($body->password)) {
                    echo "{\"error\":\"missing argument\"}";
                    return;
                }
                $hashedPassword = hash('sha256', $body->password);

                if (isset($body->newMail)) {
                    $cursor = $col->updateOne(
                        ['password' => $hashedPassword, 'mail' => $body->mail],
                        ['$set' => ['mail' => $body->newMail]]
                    );
                    $cursor = $col->find(
                        ["mail" => $body->mail, "password" => $hashedPassword],
                        ["projection" => ["nom" => 1, "password" => 1, "_id" => 0, "mail" => 1]]
                    );
                    foreach ($cursor as $login) {
                        echo json_encode($login);
                    }
                    return;
                }
                if (isset($body->newNom)) {
                    $cursor = $col->updateOne(
                        ['password' => $hashedPassword, 'nom' => $body->nom],
                        ['$set' => ['nom' => $body->newNom]]
                    );
                    $cursor = $col->find(
                        ["mail" => $body->mail, "password" => $hashedPassword],
                        ["projection" => ["nom" => 1, "password" => 1, "_id" => 0, "mail" => 1]]
                    );
                    foreach ($cursor as $login) {
                        echo json_encode($login);
                    }
                    return;
                }
                echo "{\"error\":\"missing argument\"}";
                return;
            }
            echo "{\"error\":\"bad request\"}";
            http_response_code(404);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    function knowUser(string $mail, string $password): bool
    {
        $hashedPassword = hash('sha256', $password);
        $col = $this->db->selectCollection('userlist');
        $cursor = $col->find(["mail" => $mail, "password" => $hashedPassword]);
        $cursorToArray = $cursor->toArray();
        if (count($cursorToArray) == 0 || count($cursorToArray) > 1) {
            echo "{\"error\":\"user doesn't exist or incorect \"}";
            return false;
        }
        return true;
    }
}
