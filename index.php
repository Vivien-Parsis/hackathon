<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once './src/router/router.php';

$Router = new Router();
$Router->run();
