<?php
require_once './src/controller/createToken.php';
require_once './src/controller/parseToken.php';

class UserRouter
{
    public function run($db, $info)
    {
        if ($info['endpoint'] == "/user/get" && $info['method'] == "GET") {
            $this->get($db);
            return;
        }
        if ($info['endpoint'] == "/user/signup" && $info['method'] == "POST") {
            $this->signUp($db);
            return;
        }
        if ($info['endpoint'] == "/user/signin" && $info['method'] == "POST") {
            $this->signIn($db);
            return;
        }
        if ($info['endpoint'] == "/user/delete" && $info['method'] == "POST") {
            $this->delete($db);
            return;
        }
        if ($info['endpoint'] == "/user/update" && $info['method'] == "POST") {
            $this->update($db);
            return;
        }
        header("Content-Type: application/json");
        echo "{\"error\":\"bad request\"}";
        http_response_code(404);
    }
    public function signIn($db)
    {
        $input = file_get_contents('php://input', true);
        $body = json_decode($input);
        if (!isset($body->mail) || !isset($body->password)) {
            echo "{\"error\":\"missing argument\"}";
            return;
        }
        $hashedPassword = hash('sha256', $body->password);
        $col = $db->selectCollection('userlist');
        $cursor = $col->find(["mail" => $body->mail, "password" => $hashedPassword]);
        if (count($cursor->toArray()) == 0) {
            echo "{\"error\":\"user doesn't exist or incorect \"}";
            return;
        }
        $cursor = $col->findOne(
            ["mail" => $body->mail, "password" => $hashedPassword],
            ["projection" => ["nom" => 1, "password" => 1, "_id" => 0, "mail" => 1, "role" => 1]]
        );
        $client = $cursor["role"];
        $token = createToken($body->mail, $body->nom, $body->password, $client);
        echo "{\"jwt\":\"{$token}\"}";
        return;
    }
    public function signUp($db)
    {
        $input = file_get_contents('php://input', true);
        $body = json_decode($input);
        if (!isset($body->nom) || !isset($body->mail) || !isset($body->password)) {
            echo "{\"error\":\"missing argument\"}";
            return;
        }
        if(!filter_var($body->mail, FILTER_VALIDATE_EMAIL)){
            echo "{\"error\":\"invalid mail format\"}";
            return;
        }
        $hashedPassword = hash('sha256', $body->password);
        $col = $db->selectCollection('userlist');
        $cursor = $col->find(["mail" => $body->mail]);
        if (count($cursor->toArray()) >= 1) {
            echo "{\"error\":\"already exist\"}";
            return;
        }
        $cursor = $col->insertOne([
            "nom" => $body->nom,
            "mail" => $body->mail,
            "password" => $hashedPassword,
            "role" => "client"
        ]);
        $token = createToken($body->mail, $body->nom, $body->password, "client");
        echo "{\"jwt\":\"{$token}\"}";
        return;
    }
    public function delete($db)
    {
        if (!checkJWT()) {
            echo "{\"error\":\"not Authorized\"}";
            http_response_code(401);
            return;
        }
        $input = file_get_contents('php://input', true);
        $col = $db->selectCollection('userlist');
        $body = json_decode($input);
        if (!isset($body->mail) || !isset($body->password)) {
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
    public function get($db)
    {
        if (!checkJWT()) {
            echo "{\"error\":\"not Authorized\"}";
            http_response_code(401);
            return;
        }
        $header = apache_request_headers();
        $jwtToken = trim(explode(" ", $header["Authorization"], 2)[1]);
        $claim = getClaimsJWT(trim($jwtToken));
        $col = $db->selectCollection('userlist');
        $cursor = $col->find(["mail" => $claim["mail"]], ["projection" => ["nom" => 1, "_id" => 0, "mail" => 1, "role" => 1]]);
        $res = [];
        foreach ($cursor as $user) {
            $res[] = $user;
        };
        echo json_encode($res);
        return;
    }
    public function update($db)
    {
        if (!checkJWT()) {
            echo "{\"error\":\"not Authorized\"}";
            http_response_code(401);
            return;
        }
        $input = file_get_contents('php://input', true);
        $col = $db->selectCollection('userlist');
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
            $cursor = $col->findOne(
                ["mail" => $body->mail, "password" => $hashedPassword],
                ["projection" => ["nom" => 1, "password" => 1, "_id" => 0, "mail" => 1, "role" => 1]]
            );
            $token = createToken($cursor["mail"], $cursor["nom"], $body->password, $cursor["role"]);
            echo "{\"jwt\":\"{$token}\"}";
            return;
        }
        if (isset($body->newNom)) {
            $cursor = $col->updateOne(
                ['password' => $hashedPassword, 'nom' => $body->nom],
                ['$set' => ['nom' => $body->newNom]]
            );
            $cursor = $col->findOne(
                ["mail" => $body->mail, "password" => $hashedPassword],
                ["projection" => ["nom" => 1, "password" => 1, "_id" => 0, "mail" => 1, "role" => 1]]
            );
            $token = createToken($cursor["mail"], $cursor["nom"], $body->password, $cursor["role"]);
            echo "{\"jwt\":\"{$token}\"}";
            return;
        }
        echo "{\"error\":\"missing argument\"}";
        return;
    }
}
