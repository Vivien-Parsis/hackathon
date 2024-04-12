<?php
class Cors{
    static function setCORS(){
        $origin = isset($_ENV["CORS_ORIGIN"]) ? $_ENV["CORS_ORIGIN"] : "*";
        $origin = "*";
        $method = isset($_ENV["CORS_METHOD"]) ? $_ENV["CORS_METHOD"] : "GET, POST";
        header("Access-Control-Allow-Origin: {$origin}");
        header("Access-Control-Allow-Methods: {$method}");
    }
}