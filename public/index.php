<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// On the very first visit, check the host and write the .env settings file...
require __DIR__.'/../bootstrap/FirstRunSetup.php';
FirstRunSetup::run(dirname(__DIR__), $_SERVER);

// Let Laravel find the folder the site is in (such as /blog), so its addresses include it...
$_SERVER = FirstRunSetup::withSiteEntryPoint($_SERVER);

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
