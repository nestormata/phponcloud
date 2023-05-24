<?php

namespace PHPOnCloud\App;

use ArrayAccess;
use DI\Container;
use DI\ContainerBuilder;
use Dotenv\Dotenv;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\MarkdownConverter;
use PHPOnCloud\App\Managers\TemplateManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Application implements ArrayAccess
{
    /**
     * The Injection container
     */
    protected Container $container;
    protected Dotenv $env;
    private string $base_path;

    public function __construct(string $base_path)
    {
        $this->base_path = $base_path;
        // Load environment variables
        $env = Dotenv::createImmutable(__DIR__ . '/../');
        $env->load();
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
            'app' => $this,
            'parser' => new MarkdownConverter($environment),
            'template' => new TemplateManager($this),
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
        $status = 404;
        $template_name = 'message';
        $data = ['message' => 'Page not found'];
        // If the requested file is a markdown file and exists, convert it to HTML
        if (file_exists(__DIR__ . '/../content' . $path . '.md')) {
            $markdown = file_get_contents(__DIR__ . '/../content' . $path . '.md');
            $markdownParser = $this->container->get('parser');
            $result = $markdownParser->convert($markdown);
            $data = [];
            if ($result instanceof RenderedContentWithFrontMatter) {
                $page_information = $result->getFrontMatter();
                $template_name = array_key_exists('layout', $page_information)?$page_information['layout']:'page';
                $data = array_merge($data, $page_information, ['layout' => $template_name]);
                $content = $result->getContent();
                $data['content'] = $content;
            } else { //League\CommonMark\Output\RenderedContent
                $template_name = 'page';
                $content = $result->getContent();
                $data['content'] = $content;
            }
            $status = 200;
        }
        /** @var TemplateManager */
        $template = $this['template'];
        $html = $template->render($template_name, $data);
        $response = new Response($html, $status);
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

    /**
     * Gets the application base path.
     * @return string The path that encloses all the application.
     */
    public function getBasePath(): string
    {
        return $this->base_path;
    }

    /**
     * Gets a sub path from the base path.
     * @return string The complete route to the sub path.
     */
    public function getPath(string $sub_path): string
    {
        return $this->getBasePath() . $sub_path;
    }

    /**
     * Get the full path of the templates directory.
     * @return string The complete route to the templates directory.
     */
    public function getTemplatesPath($sub_path = ''): string
    {
        return $this->getPath('templates/' . $sub_path);
    }

    /**
     * Get the full path to the cache directory.
     * @return string The complete route to the cache directory.
     */
    public function getCachePath($sub_path = ''): string
    {
        return $this->getPath('cache/' . $sub_path);
    }

    public function getPublicPath($sub_path = ''): string
    {
        return $this->getPath('public/' . $sub_path);
    }
}
