<?php
class Cors{
    static function setCORS(){
        $origin = isset($_ENV["CORS_ORIGIN"]) ? $_ENV["CORS_ORIGIN"] : "*";
        header("Access-Control-Allow-Origin: {$origin}");
    }
}