<?php
require_once './src/controller/mongoDB.php';
require_once './src/router/userRouter.php';
require_once './src/router/imageRouter.php';

class Router
{
    private $RequestInfo;
    private $mongoDB;
    public function __construct()
    {
        $this->RequestInfo = [
            "method" => $_SERVER["REQUEST_METHOD"],
            "endpoint" => explode("?", $_SERVER['REQUEST_URI'])[0],
            "query" => $_GET
        ];
        $this->mongoDB = new MongoDBController();
    }

    public function run(): void
    {
        if (str_starts_with($this->RequestInfo["endpoint"], "/user")) {
            header("Content-Type: application/json");
            $userRouter = new UserRouter();
            $MongoDBController = new MongoDBController();
            try {
                $userRouter->run($MongoDBController->getDB(),$this->RequestInfo);
            } catch (Exception $e) {
                echo $e->getMessage();
            }
            return;
        }
        if (str_starts_with($this->RequestInfo["endpoint"], "/assets/img/")&&$this->RequestInfo["endpoint"]!="/assets/img/") {
            $imageRouter = new imageRouter();
            $imageRouter->get($this->RequestInfo["endpoint"]);
            return;
        }
        header("Content-Type: application/json");
        echo "{\"error\":\"bad request\"}";
        http_response_code(404);
    }
}
