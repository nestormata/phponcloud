<?php

// Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Create a container
$containerBuilder = new \DI\ContainerBuilder();
$containerBuilder->useAutowiring(true);

// Add services to the container
$containerBuilder->addDefinitions([
    'markdownParser' => \DI\create(League\CommonMark\CommonMarkConverter::class),
]);

// Build the container
$container = $containerBuilder->build();

// Set the container as the default container for the Symfony Dependency Injection component
//\Symfony\Component\DependencyInjection\ContainerBuilder::setDefaultLenient(true);
//\Symfony\Component\DependencyInjection\ContainerBuilder::setInstance($container);
