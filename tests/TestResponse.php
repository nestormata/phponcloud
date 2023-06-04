<?php

namespace Tests;

use ArrayAccess;
use Carbon\Carbon;
use Closure;
use LogicException;
use PHPUnit\Framework\ExpectationFailedException;
use ReflectionProperty;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;

class TestResponse implements ArrayAccess
{
    /**
     * The response to delegate to.
     *
     * @var \Symfony\Component\HttpFoundation\Response
     */
    public $baseResponse;


    /**
     * The streamed content of the response.
     *
     * @var string
     */
    protected $streamedContent;

    /**
     * Create a new test response instance.
     *
     * @param  \Symfony\Component\HttpFoundation\Response $response
     * @return void
     */
    public function __construct($response)
    {
        $this->baseResponse = $response;
    }

    /**
     * Create a new TestResponse from another response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return static
     */
    public static function fromBaseResponse($response)
    {
        return new static($response);
    }

    /**
     * Assert that the response has a successful status code.
     *
     * @return $this
     */
    public function assertSuccessful()
    {
        Assert::assertTrue(
            $this->isSuccessful(),
            $this->statusMessageWithDetails('>=200, <300', $this->getStatusCode())
        );

        return $this;
    }

    /**
     * Assert that the response is a server error.
     *
     * @return $this
     */
    public function assertServerError()
    {
        Assert::assertTrue(
            $this->isServerError(),
            $this->statusMessageWithDetails('>=500, < 600', $this->getStatusCode())
        );

        return $this;
    }

    /**
     * Assert that the response has the given status code.
     *
     * @param  int  $status
     * @return $this
     */
    public function assertStatus($status)
    {
        $message = $this->statusMessageWithDetails($status, $actual = $this->getStatusCode());

        Assert::assertSame($actual, $status, $message);

        return $this;
    }

    /**
     * Get an assertion message for a status assertion containing extra details when available.
     *
     * @param  string|int  $expected
     * @param  string|int  $actual
     * @return string
     */
    protected function statusMessageWithDetails($expected, $actual)
    {
        return "Expected response status code [{$expected}] but received {$actual}.";
    }

    /**
     * Assert whether the response is redirecting to a given URI.
     *
     * @param  string|null  $uri
     * @return $this
     */
    public function assertRedirect($uri = null)
    {
        Assert::assertTrue(
            $this->isRedirect(),
            $this->statusMessageWithDetails('201, 301, 302, 303, 307, 308', $this->getStatusCode()),
        );

        if (! is_null($uri)) {
            $this->assertLocation($uri);
        }

        return $this;
    }

    /**
     * Asserts that the response contains the given header and equals the optional value.
     *
     * @param  string  $headerName
     * @param  mixed  $value
     * @return $this
     */
    public function assertHeader($headerName, $value = null)
    {
        Assert::assertTrue(
            $this->headers->has($headerName), "Header [{$headerName}] not present on response."
        );

        $actual = $this->headers->get($headerName);

        if (! is_null($value)) {
            Assert::assertEquals(
                $value, $this->headers->get($headerName),
                "Header [{$headerName}] was found, but value [{$actual}] does not match [{$value}]."
            );
        }

        return $this;
    }

    /**
     * Asserts that the response does not contain the given header.
     *
     * @param  string  $headerName
     * @return $this
     */
    public function assertHeaderMissing($headerName)
    {
        Assert::assertFalse(
            $this->headers->has($headerName), "Unexpected header [{$headerName}] is present on response."
        );

        return $this;
    }

    /**
     * Assert that the response offers a file download.
     *
     * @param  string|null  $filename
     * @return $this
     */
    public function assertDownload($filename = null)
    {
        $contentDisposition = explode(';', $this->headers->get('content-disposition'));

        if (trim($contentDisposition[0]) !== 'attachment') {
            Assert::fail(
                'Response does not offer a file download.'.PHP_EOL.
                'Disposition ['.trim($contentDisposition[0]).'] found in header, [attachment] expected.'
            );
        }

        if (! is_null($filename)) {
            if (isset($contentDisposition[1]) &&
                trim(explode('=', $contentDisposition[1])[0]) !== 'filename') {
                Assert::fail(
                    'Unsupported Content-Disposition header provided.'.PHP_EOL.
                    'Disposition ['.trim(explode('=', $contentDisposition[1])[0]).'] found in header, [filename] expected.'
                );
            }

            $message = "Expected file [{$filename}] is not present in Content-Disposition header.";

            if (! isset($contentDisposition[1])) {
                Assert::fail($message);
            } else {
                Assert::assertSame(
                    $filename,
                    isset(explode('=', $contentDisposition[1])[1])
                        ? trim(explode('=', $contentDisposition[1])[1], " \"'")
                        : '',
                    $message
                );

                return $this;
            }
        } else {
            Assert::assertTrue(true);

            return $this;
        }
    }

    /**
     * Asserts that the response contains the given cookie and equals the optional value.
     *
     * @param  string  $cookieName
     * @param  mixed  $value
     * @return $this
     */
    public function assertPlainCookie($cookieName, $value = null)
    {
        $this->assertCookie($cookieName, $value);

        return $this;
    }

    /**
     * Asserts that the response contains the given cookie and equals the optional value.
     *
     * @param  string  $cookieName
     * @param  mixed  $value
     * @param  bool  $encrypted
     * @param  bool  $unserialize
     * @return $this
     */
    public function assertCookie($cookieName, $value = null, $unserialize = false)
    {
        Assert::assertNotNull(
            $cookie = $this->getCookie($cookieName, $unserialize),
            "Cookie [{$cookieName}] not present on response."
        );

        if (! $cookie || is_null($value)) {
            return $this;
        }

        $cookieValue = $cookie->getValue();

        Assert::assertEquals(
            $value, $cookieValue,
            "Cookie [{$cookieName}] was found, but value [{$cookieValue}] does not match [{$value}]."
        );

        return $this;
    }

    /**
     * Asserts that the response contains the given cookie and is expired.
     *
     * @param  string  $cookieName
     * @return $this
     */
    public function assertCookieExpired($cookieName)
    {
        Assert::assertNotNull(
            $cookie = $this->getCookie($cookieName, false),
            "Cookie [{$cookieName}] not present on response."
        );

        $expiresAt = Carbon::createFromTimestamp($cookie->getExpiresTime());

        Assert::assertTrue(
            $cookie->getExpiresTime() !== 0 && $expiresAt->lessThan(Carbon::now()),
            "Cookie [{$cookieName}] is not expired, it expires at [{$expiresAt}]."
        );

        return $this;
    }

    /**
     * Asserts that the response contains the given cookie and is not expired.
     *
     * @param  string  $cookieName
     * @return $this
     */
    public function assertCookieNotExpired($cookieName)
    {
        Assert::assertNotNull(
            $cookie = $this->getCookie($cookieName, false),
            "Cookie [{$cookieName}] not present on response."
        );

        $expiresAt = Carbon::createFromTimestamp($cookie->getExpiresTime());

        Assert::assertTrue(
            $cookie->getExpiresTime() === 0 || $expiresAt->greaterThan(Carbon::now()),
            "Cookie [{$cookieName}] is expired, it expired at [{$expiresAt}]."
        );

        return $this;
    }

    /**
     * Asserts that the response does not contain the given cookie.
     *
     * @param  string  $cookieName
     * @return $this
     */
    public function assertCookieMissing($cookieName)
    {
        Assert::assertNull(
            $this->getCookie($cookieName, false),
            "Cookie [{$cookieName}] is present on response."
        );

        return $this;
    }

    /**
     * Get the given cookie from the response.
     *
     * @param  string  $cookieName
     * @param  bool  $unserialize
     * @return \Symfony\Component\HttpFoundation\Cookie|null
     */
    public function getCookie($cookieName, $unserialize = false)
    {
        foreach ($this->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $cookieName) {
                return $cookie;
            }
        }
    }

    /**
     * Assert that the given string matches the response content.
     *
     * @param  string  $value
     * @return $this
     */
    public function assertContent($value)
    {
        Assert::assertSame($value, $this->content());

        return $this;
    }

    /**
     * Assert that the given string matches the streamed response content.
     *
     * @param  string  $value
     * @return $this
     */
    public function assertStreamedContent($value)
    {
        Assert::assertSame($value, $this->streamedContent());

        return $this;
    }

    /**
     * Assert that the given string or array of strings are contained within the response.
     *
     * @param  string|array  $value
     * @param  bool  $escape
     * @return $this
     */
    public function assertSee($value, $escape = true)
    {
        $value = is_array($value) ? $value : [$value];

        $values = $escape ? array_map('e', $value) : $value;

        foreach ($values as $value) {
            Assert::assertStringContainsString((string) $value, $this->getContent());
        }

        return $this;
    }

    /**
     * Assert that the given string or array of strings are contained within the response text.
     *
     * @param  string|array  $value
     * @param  bool  $escape
     * @return $this
     */
    public function assertSeeText($value, $escape = true)
    {
        $value = is_array($value) ? $value : [$value];

        $values = $escape ? array_map('e', $value) : $value;

        $content = strip_tags($this->getContent());

        foreach ($values as $value) {
            Assert::assertStringContainsString((string) $value, $content);
        }

        return $this;
    }

    /**
     * Assert that the given string or array of strings are not contained within the response.
     *
     * @param  string|array  $value
     * @param  bool  $escape
     * @return $this
     */
    public function assertDontSee($value, $escape = true)
    {
        $value = is_array($value) ? $value : [$value];

        $values = $escape ? array_map('e', $value) : $value;

        foreach ($values as $value) {
            Assert::assertStringNotContainsString((string) $value, $this->getContent());
        }

        return $this;
    }

    /**
     * Assert that the given string or array of strings are not contained within the response text.
     *
     * @param  string|array  $value
     * @param  bool  $escape
     * @return $this
     */
    public function assertDontSeeText($value, $escape = true)
    {
        $value = is_array($value) ? $value : [$value];

        $values = $escape ? array_map('e', $value) : $value;

        $content = strip_tags($this->getContent());

        foreach ($values as $value) {
            Assert::assertStringNotContainsString((string) $value, $content);
        }

        return $this;
    }

    /**
     * Dump the content from the response and end the script.
     *
     * @return never
     */
    public function dd()
    {
        $this->dump();

        exit(1);
    }

    /**
     * Dump the headers from the response and end the script.
     *
     * @return never
     */
    public function ddHeaders()
    {
        $this->dumpHeaders();

        exit(1);
    }

    /**
     * Dump the session from the response and end the script.
     *
     * @param  string|array  $keys
     * @return never
     */
    public function ddSession($keys = [])
    {
        $this->dumpSession($keys);

        exit(1);
    }

    /**
     * Dump the content from the response.
     *
     * @param  string|null  $key
     * @return $this
     */
    public function dump($key = null)
    {
        $content = $this->getContent();

        $json = json_decode($content);

        if (json_last_error() === JSON_ERROR_NONE) {
            $content = $json;
        }

        if (! is_null($key)) {
            dump($content[$key]);
        } else {
            dump($content);
        }

        return $this;
    }

    /**
     * Dump the headers from the response.
     *
     * @return $this
     */
    public function dumpHeaders()
    {
        dump($this->headers->all());

        return $this;
    }

    /**
     * Dump the session from the response.
     *
     * @param  string|array  $keys
     * @return $this
     */
    public function dumpSession($keys = [])
    {
        $keys = (array) $keys;

        if (empty($keys)) {
            dump($this->session()->all());
        } else {
            dump($this->session()->only($keys));
        }

        return $this;
    }

    /**
     * Get the streamed content from the response.
     *
     * @return string
     */
    public function streamedContent()
    {
        if (! is_null($this->streamedContent)) {
            return $this->streamedContent;
        }

        if (! $this->baseResponse instanceof StreamedResponse) {
            Assert::fail('The response is not a streamed response.');
        }

        ob_start(function (string $buffer): string {
            $this->streamedContent .= $buffer;

            return '';
        });

        $this->sendContent();

        ob_end_clean();

        return $this->streamedContent;
    }

    /**
     * Append a message to an exception.
     *
     * @param  string  $message
     * @param  \Throwable  $exception
     * @return \Throwable
     */
    protected function appendMessageToException($message, $exception)
    {
        $property = new ReflectionProperty($exception, 'message');

        $property->setAccessible(true);

        $property->setValue(
            $exception,
            $exception->getMessage().PHP_EOL.PHP_EOL.$message.PHP_EOL
        );

        return $exception;
    }

    /**
     * Dynamically access base response parameters.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->baseResponse->{$key};
    }

    /**
     * Proxy isset() checks to the underlying base response.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->baseResponse->{$key});
    }

    /**
     * Determine if the given offset exists.
     *
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return $this->responseHasView()
                    ? isset($this->original->gatherData()[$offset])
                    : isset($this->json()[$offset]);
    }

    /**
     * Get the value for a given offset.
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->responseHasView()
                    ? $this->viewData($offset)
                    : $this->json()[$offset];
    }

    /**
     * Set the value at the given offset.
     *
     * @param  string  $offset
     * @param  mixed  $value
     * @return void
     *
     * @throws \LogicException
     */
    public function offsetSet($offset, $value): void
    {
        throw new LogicException('Response data may not be mutated using array access.');
    }

    /**
     * Unset the value at the given offset.
     *
     * @param  string  $offset
     * @return void
     *
     * @throws \LogicException
     */
    public function offsetUnset($offset): void
    {
        throw new LogicException('Response data may not be mutated using array access.');
    }

    /**
     * Handle dynamic calls into macros or pass missing methods to the base response.
     *
     * @param  string  $method
     * @param  array  $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        return $this->baseResponse->{$method}(...$args);
    }
}