<?php

class imageRouter{
    public function get($url):void{
        $img = "./src".$url;
        if(file_exists($img)){
            $mime_type = "";
            if(str_ends_with($img,".ico")){
                $mime_type = "image/x-icon";
            }
            if(str_ends_with($img,".png")){
                $mime_type = "image/png";
            }
            if(str_ends_with($img,".svg")){
                $mime_type = "image/svg+xml";
            }
            header("Content-Type: $mime_type");
            echo file_get_contents($img);
            return;
        }
        header("Content-Type: application/json");
        ErrorController::http_error(404);
    }
}