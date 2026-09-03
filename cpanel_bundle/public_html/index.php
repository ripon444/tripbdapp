<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if application is in maintenance mode
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
} elseif (file_exists($maintenance = __DIR__.'/../tripbd_backend/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register Composer autoloader (supports both standalone public/ and cPanel public_html/ structure)
if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    require __DIR__.'/../vendor/autoload.php';
} elseif (file_exists(__DIR__.'/../tripbd_backend/vendor/autoload.php')) {
    require __DIR__.'/../tripbd_backend/vendor/autoload.php';
} else {
    die('Composer autoloader not found. Please run "composer install" in the tripbd_backend directory.');
}

// Bootstrap Laravel and handle the request
if (file_exists(__DIR__.'/../bootstrap/app.php')) {
    $app = require_once __DIR__.'/../bootstrap/app.php';
} elseif (file_exists(__DIR__.'/../tripbd_backend/bootstrap/app.php')) {
    $app = require_once __DIR__.'/../tripbd_backend/bootstrap/app.php';
} else {
    die('Laravel bootstrap file (bootstrap/app.php) not found.');
}

$app->handleRequest(Request::capture());
