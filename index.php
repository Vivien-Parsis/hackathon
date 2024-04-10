<?php

require_once './src/controller/mongoDB.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once './src/controller/mongoDB.php';
//Dotenv\Dotenv::createUnsafeImmutable(__DIR__)->safeLoad();

header("Content-Type: application/json");
$mongoDB = new MongoDBController();
$mongoDB->callMongoDB();
