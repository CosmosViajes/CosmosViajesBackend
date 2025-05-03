<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

#header("Access-Control-Allow-Origin: http://localhost:4200");
#header("Access-Control-Allow-Credentials: true");
#header("Access-Control-Allow-Methods: PUT, POST, DELETE, OPTIONS");
#header("Access-Control-Max-Age: 1000");
#header("Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authorization, X-Requested-With, X-CSRF-TOKEN");

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
