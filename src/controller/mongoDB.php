<?php

class MongoDBController
{
    private $db;

    public function __construct()
    {
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
    public function getDB(){
        return $this->db;
    }
    function knowUser(string $mail, string $password, string $role): bool
    {
        if(!isset($mail) || !isset($password) || !isset($role)){
            return false;
        }
        $hashedPassword = hash('sha256', $password);
        $col = $this->db->selectCollection('userlist');
        $cursor = $col->find(["mail" => $mail, "password" => $hashedPassword, "role"=> $role]);
        $cursorToArray = $cursor->toArray();
        return (count($cursorToArray) == 0 || count($cursorToArray) > 1) ? false : true;
    }
}
