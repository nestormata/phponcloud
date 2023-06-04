<?php

test('index page content', function () {
    // create page
    $index_path = self::$app->getContentPath('index.md');
    $handle = fopen($index_path, 'w');
    $content = <<<CONTENT
    ---
    layout: frontpage
    title: Experiment PHP on the cloud
    ---
    # This is the homepage
    Important things goes here
CONTENT;
    fwrite($handle, $content);
    fclose($handle);

    get('/')->assertSee('Experiment PHP on the cloud');
    expect(true)->toBeTrue();
});
