<?php

namespace Tests;

use PHPOnCloud\App\Application;
use PHPUnit\Framework\TestCase as BaseTestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use function PHPUnit\Framework\directoryExists;

/**
 * Base class with the required functionality to facilitate testing with Pest.
 */
abstract class TestCase extends BaseTestCase
{
    protected static $app;
    /**
     * Additional headers for the request.
     *
     * @var array
     */
    protected $defaultHeaders = [];
    /**
     * The latest test response (if any).
     *
     * @var TestResponse|null
     */
    public static $latestResponse;

    /**
     * Additional server variables for the request.
     *
     * @var array
     */
    protected $serverVariables = [];

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

    /**
     * Define additional headers to be sent with the request.
     *
     * @param  array  $headers
     * @return $this
     */
    public function withHeaders(array $headers)
    {
        $this->defaultHeaders = array_merge($this->defaultHeaders, $headers);

        return $this;
    }

    /**
     * Add a header to be sent with the request.
     *
     * @param  string  $name
     * @param  string  $value
     * @return $this
     */
    public function withHeader(string $name, string $value)
    {
        $this->defaultHeaders[$name] = $value;

        return $this;
    }

    /**
     * Define a set of server variables to be sent with the requests.
     *
     * @param  array  $server
     * @return $this
     */
    public function withServerVariables(array $server)
    {
        $this->serverVariables = $server;

        return $this;
    }


    /**
     * Transform headers array to array of $_SERVER vars with HTTP_* format.
     *
     * @param  array  $headers
     * @return array
     */
    protected function transformHeadersToServerVars(array $headers)
    {
        return collect(array_merge($this->defaultHeaders, $headers))->mapWithKeys(function ($value, $name) {
            $name = strtr(strtoupper($name), '-', '_');

            return [$this->formatServerHeaderKey($name) => $value];
        })->all();
    }

    /**
     * Format the header name for the server array.
     *
     * @param  string  $name
     * @return string
     */
    protected function formatServerHeaderKey($name)
    {
        if (! str_starts_with($name, 'HTTP_') && $name !== 'CONTENT_TYPE' && $name !== 'REMOTE_ADDR') {
            return 'HTTP_'.$name;
        }

        return $name;
    }

    /**
     * Extract the file uploads from the given data array.
     *
     * @param  array  $data
     * @return array
     */
    protected function extractFilesFromDataArray(&$data)
    {
        $files = [];

        foreach ($data as $key => $value) {
            if ($value instanceof UploadedFile) {
                $files[$key] = $value;
                unset($data[$key]);
            }
            if (is_array($value)) {
                $files[$key] = $this->extractFilesFromDataArray($value);

                $data[$key] = $value;
            }
        }

        return $files;
    }

        /**
     * Set the referer header and previous URL session value in order to simulate a previous request.
     *
     * @param  string  $url
     * @return $this
     */
    public function from(string $url)
    {
        $this->app['session']->setPreviousUrl($url);

        return $this->withHeader('referer', $url);
    }

    /**
     * Visit the given URI with a GET request.
     *
     * @param  string  $uri
     * @param  array  $headers
     * @return \Illuminate\Testing\TestResponse
     */
    public function get($uri, array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);
        $cookies = $this->prepareCookiesForRequest();

        return $this->call('GET', $uri, [], $cookies, [], $server);
    }

    /**
     * Visit the given URI with a GET request, expecting a JSON response.
     *
     * @param  string  $uri
     * @param  array  $headers
     * @param  int  $options
     * @return \Illuminate\Testing\TestResponse
     */
    public function getJson($uri, array $headers = [], $options = 0)
    {
        return $this->json('GET', $uri, [], $headers, $options);
    }

    /**
     * Visit the given URI with a POST request.
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Testing\TestResponse
     */
    public function post($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);
        $cookies = $this->prepareCookiesForRequest();

        return $this->call('POST', $uri, $data, $cookies, [], $server);
    }

    /**
     * Visit the given URI with a POST request, expecting a JSON response.
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @param  int  $options
     * @return \Illuminate\Testing\TestResponse
     */
    public function postJson($uri, array $data = [], array $headers = [], $options = 0)
    {
        return $this->json('POST', $uri, $data, $headers, $options);
    }

    /**
     * Visit the given URI with a PUT request.
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Testing\TestResponse
     */
    public function put($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);
        $cookies = $this->prepareCookiesForRequest();

        return $this->call('PUT', $uri, $data, $cookies, [], $server);
    }

    /**
     * Visit the given URI with a PUT request, expecting a JSON response.
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @param  int  $options
     * @return \Illuminate\Testing\TestResponse
     */
    public function putJson($uri, array $data = [], array $headers = [], $options = 0)
    {
        return $this->json('PUT', $uri, $data, $headers, $options);
    }

    /**
     * Visit the given URI with a PATCH request.
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Testing\TestResponse
     */
    public function patch($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);
        $cookies = $this->prepareCookiesForRequest();

        return $this->call('PATCH', $uri, $data, $cookies, [], $server);
    }

    /**
     * Visit the given URI with a PATCH request, expecting a JSON response.
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @param  int  $options
     * @return \Illuminate\Testing\TestResponse
     */
    public function patchJson($uri, array $data = [], array $headers = [], $options = 0)
    {
        return $this->json('PATCH', $uri, $data, $headers, $options);
    }

    /**
     * Visit the given URI with a DELETE request.
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Testing\TestResponse
     */
    public function delete($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);
        $cookies = $this->prepareCookiesForRequest();

        return $this->call('DELETE', $uri, $data, $cookies, [], $server);
    }

    /**
     * Visit the given URI with a DELETE request, expecting a JSON response.
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @param  int  $options
     * @return \Illuminate\Testing\TestResponse
     */
    public function deleteJson($uri, array $data = [], array $headers = [], $options = 0)
    {
        return $this->json('DELETE', $uri, $data, $headers, $options);
    }

    /**
     * Visit the given URI with an OPTIONS request.
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Testing\TestResponse
     */
    public function options($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);

        $cookies = $this->prepareCookiesForRequest();

        return $this->call('OPTIONS', $uri, $data, $cookies, [], $server);
    }

    /**
     * Visit the given URI with an OPTIONS request, expecting a JSON response.
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @param  int  $options
     * @return \Illuminate\Testing\TestResponse
     */
    public function optionsJson($uri, array $data = [], array $headers = [], $options = 0)
    {
        return $this->json('OPTIONS', $uri, $data, $headers, $options);
    }

    /**
     * Visit the given URI with a HEAD request.
     *
     * @param  string  $uri
     * @param  array  $headers
     * @return \Illuminate\Testing\TestResponse
     */
    public function head($uri, array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);

        $cookies = $this->prepareCookiesForRequest();

        return $this->call('HEAD', $uri, [], $cookies, [], $server);
    }

    /**
     * Call the given URI with a JSON request.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @param  int  $options
     * @return \Illuminate\Testing\TestResponse
     */
    public function json($method, $uri, array $data = [], array $headers = [], $options = 0)
    {
        $files = $this->extractFilesFromDataArray($data);

        $content = json_encode($data, $options);

        $headers = array_merge([
            'CONTENT_LENGTH' => mb_strlen($content, '8bit'),
            'CONTENT_TYPE' => 'application/json',
            'Accept' => 'application/json',
        ], $headers);

        return $this->call(
            $method,
            $uri,
            [],
            $this->prepareCookiesForJsonRequest(),
            $files,
            $this->transformHeadersToServerVars($headers),
            $content
        );
    }


    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $files = array_merge($files, $this->extractFilesFromDataArray($parameters));

        $request = Request::create(
            self::$app->getUrl($uri), $method, $parameters,
            $cookies, $files, array_replace($this->serverVariables, $server), $content
        );

        $response = self::$app->handle($request);

        self::$app->terminate($request, $response);

        // TODO: handle redirects

        return static::$latestResponse = $this->createTestResponse($response);
    }

        /**
     * Create the test response instance from the given response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return TestResponse
     */
    protected function createTestResponse($response)
    {
        $response = TestResponse::fromBaseResponse($response);
        return $response;
    }

}
