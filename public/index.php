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
// Handle index page
if ($path === '/') {
    $path = '/index';
}

// TODO: Move this to a default controller instead
// If the requested file is a markdown file and exists, convert it to HTML
if (file_exists(__DIR__ . '/../content' . $path . '.md')) {
    $markdown = file_get_contents(__DIR__ . '/../content' . $path . '.md');
    $markdownParser = $container->get('parser');
    $result = $markdownParser->convert($markdown);
    if ($result instanceof League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter) {
        $page_information = $result->getFrontMatter();
        $content = $result->getContent();
    } else { //League\CommonMark\Output\RenderedContent
        $content = $result->getContent();
    }
    echo $content;
    exit;
}

// Otherwise, return a 404 error
http_response_code(404);
echo 'Page not found.';
