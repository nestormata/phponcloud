<?php

use PHPOnCloud\App\Application;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Initialize application
$app = new Application();
$app->setUp();

return $app;
