<?php

namespace PHPOnCloud\App;

use ArrayAccess;
use DI\Container;
use DI\ContainerBuilder;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\MarkdownConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Application implements ArrayAccess
{
    /**
     * The Injection container
     */
    protected Container $container;

    public function __construct()
    {
    }

    public function setUp()
    {
        // Create a container builder
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->useAutowiring(true);
        // Create and configure the parser
        $parser_config = []; // TODO: load this from a configuration file/env file
        $environment = new Environment($parser_config);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new FrontMatterExtension());
        // Add services to the container
        $containerBuilder->addDefinitions([
            'parser' => new MarkdownConverter($environment),
        ]);
        // Build the container
        $this->container = $containerBuilder->build();
    }

    public function handle(Request $request): ?Response
    {
        // Get the path to the requested file
        $path = $request->getPathInfo();

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
            $markdownParser = $this->container->get('parser');
            $result = $markdownParser->convert($markdown);
            if ($result instanceof RenderedContentWithFrontMatter) {
                $page_information = $result->getFrontMatter();
                $content = $result->getContent();
            } else { //League\CommonMark\Output\RenderedContent
                $content = $result->getContent();
            }
            $response = new Response($content, 200);
            return $response;
        }
        $response = new Response('Page not found', 404);
        return $response;
    }

    public function terminate(Request $request, Response $response)
    {
        $response->send();
    }

    /**
     * Whether an offset exists
     * Whether or not an offset exists.
     *
     * @param mixed $offset An offset to check for.
     * @return bool Returns `true` on success or `false` on failure.
     */
    public function offsetExists($offset): bool
    {
        return $this->container->has($offset);
    }

    /**
     * Offset to retrieve
     * Returns the value at specified offset.
     *
     * @param mixed $offset The offset to retrieve.
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset): mixed
    {
        return $this->container->get($offset);
    }

    /**
     * Assigns a value to the specified offset.
     *
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $this->container->set($offset, $value);
    }

    /**
     * Unsets an offset.
     *
     * @param mixed $offset The offset to unset.
     * @return void
     */
    public function offsetUnset($offset): void
    {
        // no functionality
    }
}
