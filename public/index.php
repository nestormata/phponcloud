<?php
use Symfony\Component\HttpFoundation\Request;

// Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load the bootstrap file
$app = require __DIR__ . '/../system/bootstrap.php';

$request = Request::createFromGlobals();
$response = $app->handle($request);

$app->terminate($request, $response);
