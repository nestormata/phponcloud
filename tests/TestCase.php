<?php

namespace Tests;

use PHPOnCloud\App\Application;
use PHPUnit\Framework\TestCase as BaseTestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function PHPUnit\Framework\directoryExists;

abstract class TestCase extends BaseTestCase
{
    protected static $app;
        /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass(): void
    {
        // Initialize application
        self::$app = new Application(__DIR__ . '/../');
        self::$app->setUp();
        self::createTestContentDirectory();
    }

    /**
     * This method is called after the last test of this test class is run.
     */
    public static function tearDownAfterClass(): void
    {
        self::cleanTestContentDirectory();
    }

    protected static function createTestContentDirectory()
    {
        // Make sure the test content directory exists, if not, create it.
        $content_dir = self::$app->getContentPath();
        if (!file_exists($content_dir)) {
            mkdir($content_dir);
        }
    }

    protected static function cleanTestContentDirectory()
    {
        // Destroy the test content directory
        $content_dir = self::$app->getContentPath();
        if (!file_exists($content_dir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($content_dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $fileinfo) {
                $action = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $action($fileinfo->getRealPath());
            }
            rmdir($content_dir);
        }
    }
}
