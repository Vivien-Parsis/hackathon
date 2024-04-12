<?php
header("Access-Control-Allow-Origin: *");
require_once './src/controller/createToken.php';
require_once './src/controller/parseToken.php';
require_once './src/controller/error.php';

class UserRouter
{
    public function run($db, $info)
    {
        if ($info['endpoint'] == "/user/get") {
            if($info['method'] != "GET"){
                ErrorController::http_error(405);
                return;
            }
            $this->get($db);
            return;
        }
        if ($info['endpoint'] == "/user/signup") {
            if($info['method'] == "POST" || $info['method'] == "OPTIONS"){
                $this->signUp($db);
                return;
            }
            ErrorController::http_error(405);
            return;
        }
        if ($info['endpoint'] == "/user/signin") {
            if($info['method'] == "POST" || $info['method'] == "OPTIONS"){
                $this->signIn($db);
                return;
            }
            ErrorController::http_error(405);
            return;
        }
        if ($info['endpoint'] == "/user/delete") {
            if($info['method'] != "POST"){
                ErrorController::http_error(405);
                return;
            }
            $this->delete($db);
            return;
        }
        if ($info['endpoint'] == "/user/update") {
            if($info['method'] != "POST"){
                ErrorController::http_error(405);
                return;
            }
            $this->update($db);
            return;
        }
        if ($info['endpoint'] == "/user/jwt") {
            if($info['method'] != "POST"){
                ErrorController::http_error(405);
                return;
            }
            $this->jwt($db);
            return;
        }
        header("Content-Type: application/json");
        ErrorController::http_error(404);
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
        if (!filter_var($body->mail, FILTER_VALIDATE_EMAIL)) {
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
        http_response_code(201);
        echo "{\"jwt\":\"{$token}\"}";
        return;
    }
    public function delete($db)
    {
        if (!checkJWT()) {
            ErrorController::http_error(401);
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
            ErrorController::http_error(401);
            return;
        }
        $header = apache_request_headers();
        $jwtToken = trim(explode(" ", $header["Authorization"], 2)[1]);
        $claim = getClaimsJWT(trim($jwtToken));
        $col = $db->selectCollection('userlist');
        $hashedPassword = hash('sha256', $claim["password"]);
        $cursor = $col->find(["mail" => $claim["mail"], "password" => $hashedPassword], ["projection" => ["nom" => 1, "_id" => 0, "mail" => 1, "role" => 1]]);
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
            ErrorController::http_error(401);
            return;
        }
        $header = apache_request_headers();
        $jwtToken = trim(explode(" ", $header["Authorization"], 2)[1]);
        $claim = getClaimsJWT(trim($jwtToken));
        $input = file_get_contents('php://input', true);
        $col = $db->selectCollection('userlist');
        $body = json_decode($input);
        $hashedPassword = hash('sha256', $claim["password"]);
        if (isset($body->newMail)) {
            $cursor = $col->updateOne(
                ['password' => $hashedPassword, "mail" => $claim["mail"]],
                ['$set' => ['mail' => $body->newMail]]
            );
            $cursor = $col->findOne(
                ["mail" => $claim["mail"], "password" => $hashedPassword],
                ["projection" => ["nom" => 1, "password" => 1, "_id" => 0, "mail" => 1, "role" => 1]]
            );
            $token = createToken($cursor["mail"], $cursor["nom"], $body->password, $cursor["role"]);
            echo "{\"jwt\":\"{$token}\"}";
            return;
        }
        if (isset($body->newNom)) {
            $cursor = $col->updateOne(
                ['password' => $hashedPassword, "mail" => $claim["mail"]],
                ['$set' => ['nom' => $body->newNom]]
            );
            $cursor = $col->findOne(
                ["mail" => $claim["mail"], "password" => $hashedPassword],
                ["projection" => ["nom" => 1, "password" => 1, "_id" => 0, "mail" => 1, "role" => 1]]
            );
            $token = createToken($cursor["mail"], $cursor["nom"], $body->password, $cursor["role"]);
            echo "{\"jwt\":\"{$token}\"}";
            return;
        }
        echo "{\"error\":\"missing argument\"}";
        return;
    }
    public function jwt($db)
    {
        if (!checkJWT()) {
            ErrorController::http_error(401);
            return;
        }
        $header = apache_request_headers();
        $jwtToken = trim(explode(" ", $header["Authorization"], 2)[1]);
        $claim = getClaimsJWT(trim($jwtToken));
        $input = file_get_contents('php://input', true);
        $col = $db->selectCollection('userlist');
        $hashedPassword = hash('sha256', $claim["password"]);
        $cursor = $col->findOne(
            ["mail" => $claim["mail"], "password" => $hashedPassword, "role" => $claim["role"]],
            ["projection" => ["nom" => 1, "_id" => 0, "mail" => 1, "role" => 1, "password" => 1]]
        );
        if (!isset($cursor)) {
            echo "{\"error\":\"unknown jwt\"}";
            return;
        }
        echo "{\"jwt\":\"{$jwtToken}\"}";
        return;
    }
}
