<?php

// Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Create a container
$containerBuilder = new \DI\ContainerBuilder();
$containerBuilder->useAutowiring(true);

// Create and configure the parser
$parser_config = []; // TODO: load this from a configuration file/env file
$environment = new League\CommonMark\Environment\Environment($parser_config);
$environment->addExtension(new \League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension());
$environment->addExtension(new \League\CommonMark\Extension\FrontMatter\FrontMatterExtension());

// Add services to the container
$containerBuilder->addDefinitions([
    'parser' => new League\CommonMark\MarkdownConverter($environment),
]);

// Build the container
$container = $containerBuilder->build();

// Set the container as the default container for the Symfony Dependency Injection component
//\Symfony\Component\DependencyInjection\ContainerBuilder::setDefaultLenient(true);
//\Symfony\Component\DependencyInjection\ContainerBuilder::setInstance($container);
