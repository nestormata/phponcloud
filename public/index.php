<?php

// Load the bootstrap file
require __DIR__ . '/../system/bootstrap.php';

// Get the path to the requested file
$path = $_SERVER['REQUEST_URI'];

// Remove any query string parameters
$path = strtok($path, '?');

// Make sure we don't have a trailing / for the check of the md file name
if (substr($path, -1) === '/') {
    $path = substr($path, 0, strlen($path));
}

// If the requested file is a markdown file and exists, convert it to HTML
if (file_exists(__DIR__ . '/../content' . $path . '.md')) {
    $markdown = file_get_contents(__DIR__ . '/../content' . $path . '.md');
    $markdownParser = $container->get('markdownParser');
    $html = $markdownParser->convertToHtml($markdown);
    echo $html;
    exit;
}

// Otherwise, return a 404 error
http_response_code(404);
echo 'Page not found.';