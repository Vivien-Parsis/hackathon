<?php
require_once './src/controller/createToken.php';
require_once './src/controller/parseToken.php';

class UserRouter{
    public function run($db, $info){
        if ($info['endpoint'] == "/user/get" && $info['method'] == "GET"){
            $this->get($db, $info);
            return;
        }
        if ($info['endpoint'] == "/user/signup" && $info['method'] == "POST"){
            $this->signUp($db, $info);
            return;
        }
        if ($info['endpoint'] == "/user/signin" && $info['method'] == "POST"){
            $this->signIn($db, $info);
            return;
        }
        if ($info['endpoint'] == "/user/delete" && $info['method'] == "POST"){
            $this->delete($db, $info);
            return;
        }
        if ($info['endpoint'] == "/user/update" && $info['method'] == "POST"){
            $this->update($db, $info);
            return;
        }
        header("Content-Type: application/json");
        echo "{\"error\":\"bad request\"}";
        http_response_code(404);
    }
    public function signIn($db, $info){
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
    public function signUp($db, $info){
        $input = file_get_contents('php://input', true);
        $body = json_decode($input);
        if (!isset($body->nom) || !isset($body->mail) || !isset($body->password)) {
            echo "{\"error\":\"missing argument\"}";
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
    public function delete($db, $info){
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
    public function get($db, $info){
        if (!checkJWT()) {
            echo "{\"error\":\"not Authorized\"}";
            http_response_code(401);
            return;
        }
        if (isset($info["query"]["mail"])) {
            $col = $db->selectCollection('userlist');
            $cursor = $col->find(["mail" => $info["query"]["mail"]], ["projection" => ["nom" => 1, "_id" => 0, "mail" => 1, "role" => 1]]);
            $res = [];
            foreach ($cursor as $user) {
                $res[] = $user;
            };
            echo json_encode($res);
            return;
        }
        echo "{\"error\":\"missing argument\"}";
        return;
    }
    public function update($db, $info){

    }
}