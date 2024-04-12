<?php

class ErrorController{
    static public function http_error(int $code):void{
        if($code == 401){
            http_response_code(401);
            echo "{\"error\":\"not Authorized\"}";
            return;
        }
        if($code == 404){
            http_response_code(404);
            echo "{\"error\":\"not found\"}";
            return;
        }
        if($code == 405){
            http_response_code(405);
            echo "{\"error\":\"Method Not Allowed\"}";
            return;
        }
        http_response_code(400);
        echo "{\"error\":\"Bad Request\"}";
    }
}