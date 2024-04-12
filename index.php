<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once './src/router/router.php';
require_once './src/controller/cors.php';
header("Access-Control-Allow-Origin: *");
//Cors::setCORS();
$Router = new Router();
$Router->run();
