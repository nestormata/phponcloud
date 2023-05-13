<?php

/**
 * This file is the entry point for all the CMS requests.
 * It uses the bootstrap and handles the request and response.
 */

use Symfony\Component\HttpFoundation\Request;

// Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load the bootstrap file
$app = require __DIR__ . '/../system/bootstrap.php';

// Create a request and pass it to the application for handling.
$request = Request::createFromGlobals();
$response = $app->handle($request);

// Process the response to send the final output to the browser.
$app->terminate($request, $response);
