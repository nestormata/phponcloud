<?php

/**
 * The bootrstrap is in charge of setting up the basic application and configuration
 * and returning it to the index.
 */

use PHPOnCloud\App\Application;

// Initialize application
$app = new Application(__DIR__ . '/../');
$app->setUp();

return $app;
