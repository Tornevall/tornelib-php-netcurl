<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Model\Type\DataType;
use TorneLIB\Model\Type\RequestMethod;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Network\NetWrapper;
use TorneLIB\Module\Network\Wrappers\CurlWrapper;
use TorneLIB\Module\Network\Wrappers\SimpleStreamWrapper;

require_once(__DIR__ . '/../vendor/autoload.php');

/**
 * Deterministic GET query behavior across both built-in HTTP transports.
 */
class getQueryParametersTest extends TestCase
{
    public function testQueryComposerPreservesExistingPairsAndFragments()
    {
        $config = new WrapperConfig();
        $original = 'https://example.invalid/lookup?tag=first&tag=second#results';
        $data = ['term' => 'one & two', 'tag' => ['third', 'fourth']];
        $config->request($original, $data, RequestMethod::GET);

        $expected = 'https://example.invalid/lookup?tag=first&tag=second&' .
            'term=one+%26+two&tag%5B0%5D=third&tag%5B1%5D=fourth#results';
        static::assertSame($expected, $config->getRequestUrlWithQuery());
        static::assertSame($original, $config->getRequestUrl());

        // Empty query, URLs ending with a delimiter, and preformatted repeated keys.
        static::assertSame('https://example.invalid/lookup?a=1', $config->getRequestUrlWithQuery(
            'https://example.invalid/lookup',
            ['a' => 1],
            RequestMethod::GET,
            DataType::NORMAL
        ));
        static::assertSame('https://example.invalid/lookup?a=1', $config->getRequestUrlWithQuery(
            'https://example.invalid/lookup?',
            ['a' => 1],
            RequestMethod::GET,
            DataType::NORMAL
        ));
        static::assertSame('https://example.invalid/lookup?old=1&a=1', $config->getRequestUrlWithQuery(
            'https://example.invalid/lookup?old=1&',
            ['a' => 1],
            RequestMethod::GET,
            DataType::NORMAL
        ));
        static::assertSame('https://example.invalid/lookup?a=1&a=2#frag', $config->getRequestUrlWithQuery(
            'https://example.invalid/lookup#frag',
            'a=1&a=2',
            RequestMethod::GET,
            DataType::NORMAL
        ));
        static::assertSame('https://example.invalid/lookup?old=1#frag', $config->getRequestUrlWithQuery(
            'https://example.invalid/lookup?old=1#frag',
            [],
            RequestMethod::GET,
            DataType::NORMAL
        ));
        static::assertSame('https://example.invalid/lookup', $config->getRequestUrlWithQuery(
            'https://example.invalid/lookup',
            ['a' => 1],
            RequestMethod::POST,
            DataType::NORMAL
        ));

        // Typed JSON/XML payloads are explicitly not ordinary form GET params.
        static::assertSame('https://example.invalid/raw', $config->getRequestUrlWithQuery(
            'https://example.invalid/raw',
            ['a' => 1],
            RequestMethod::GET,
            DataType::JSON
        ));
    }

    public function testCurlGetUsesUrlQueriesWithoutBodies()
    {
        if (!extension_loaded('curl')) {
            static::markTestSkipped('cURL extension is required.');
        }

        $this->withLocalHttpFixture(function ($url) {
            $this->assertClientRequests(new CurlWrapper(), $url);
        });
    }

    public function testStreamGetUsesUrlQueriesWithoutBodies()
    {
        $this->withLocalHttpFixture(function ($url) {
            $this->assertClientRequests(new SimpleStreamWrapper(), $url);
        });
    }

    public function testNetWrapperRoutesOrdinaryGetQueryDataToTransport()
    {
        $this->withLocalHttpFixture(function ($url) {
            $client = new NetWrapper();
            $response = $client->request($url, ['source' => 'net-wrapper'], RequestMethod::GET);
            $json = json_decode($response->getBody());
            static::assertSame('GET', $json->method);
            static::assertSame('source=net-wrapper', $json->raw_query);
            static::assertSame('', $json->body);
        });
    }

    public function testCurlMultiGetPerUrlDataDoesNotLeakAcrossHandles()
    {
        if (!extension_loaded('curl')) {
            static::markTestSkipped('cURL extension is required.');
        }

        $this->withLocalHttpFixture(function ($url) {
            $wrapper = new CurlWrapper();
            $wrapper->request([
                ['url' => $url . '?original=1', 'data' => ['key' => 'first'], 'requestMethod' => RequestMethod::GET],
                ['url' => $url . '?key=old&key=older#fragment', 'data' => ['key' => 'second'], 'requestMethod' => RequestMethod::GET],
                ['url' => $url, 'data' => ['payload' => 'posted'], 'requestMethod' => RequestMethod::POST],
                ['url' => $url, 'data' => [], 'requestMethod' => RequestMethod::GET],
            ]);

            $expected = [
                ['GET', 'original=1&key=first', ''],
                ['GET', 'key=old&key=older&key=second', ''],
                ['POST', '', 'payload=posted'],
                ['GET', '', ''],
            ];

            foreach ($expected as $index => $case) {
                $json = json_decode($wrapper->getBody());
                static::assertTrue(is_object($json), 'Invalid multi response at index ' . $index);
                static::assertSame($case[0], $json->method, 'HTTP method at index ' . $index);
                static::assertSame($case[1], $json->raw_query, 'URL query at index ' . $index);
                static::assertSame($case[2], $json->body, 'HTTP body at index ' . $index);
            }
        });
    }

    private function assertClientRequests($client, $url)
    {
        $cases = [
            [$url, [], RequestMethod::GET, '', ''],
            [$url, ['page' => 2], RequestMethod::GET, 'page=2', ''],
            [$url . '?existing=1', ['term' => 'a & b'], RequestMethod::GET, 'existing=1&term=a+%26+b', ''],
            [$url . '?tag=old&tag=older#fragment', ['tag' => ['new', 'again']], RequestMethod::GET,
                'tag=old&tag=older&tag%5B0%5D=new&tag%5B1%5D=again', ''],
            [$url, 'a=1&a=2', RequestMethod::GET, 'a=1&a=2', ''],
            [$url . '?existing=1#fragment', [], RequestMethod::GET, 'existing=1', ''],
            [$url, ['post' => 'sentinel'], RequestMethod::POST, '', 'post=sentinel'],
            [$url . '?other=3', ['next' => 'get'], RequestMethod::GET, 'other=3&next=get', ''],
            [$url, ['put' => 'payload'], RequestMethod::PUT, '', 'put=payload'],
            [$url, ['patch' => 'payload'], RequestMethod::PATCH, '', 'patch=payload'],
            [$url, [], RequestMethod::GET, '', ''],
        ];

        foreach ($cases as $index => $case) {
            $response = $client->request($case[0], $case[1], $case[2], DataType::NORMAL);
            $json = json_decode($response->getBody());
            static::assertTrue(is_object($json), 'Invalid fixture JSON at case ' . $index);
            static::assertSame($this->getMethodName($case[2]), $json->method, 'Method at case ' . $index);
            static::assertSame($case[3], $json->raw_query, 'Query at case ' . $index);
            static::assertSame($case[4], $json->body, 'Body at case ' . $index);
            static::assertFalse(strpos($json->uri, '#') !== false, 'Fragment leaked at case ' . $index);
        }
    }

    private function getMethodName($method)
    {
        switch ($method) {
            case RequestMethod::POST:
                return 'POST';
            case RequestMethod::PUT:
                return 'PUT';
            case RequestMethod::PATCH:
                return 'PATCH';
            default:
                return 'GET';
        }
    }

    private function withLocalHttpFixture($callback)
    {
        if (DIRECTORY_SEPARATOR === '\\' || !function_exists('proc_open') ||
            !function_exists('stream_socket_server') || !function_exists('fsockopen')
        ) {
            static::markTestSkipped('Local HTTP fixture requires POSIX process and socket support.');
        }

        $listener = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errorMessage);
        static::assertNotFalse($listener, 'Cannot reserve a localhost HTTP port: ' . $errorMessage);
        $address = stream_socket_get_name($listener, false);
        $port = (int)substr($address, strrpos($address, ':') + 1);
        fclose($listener);

        $command = 'exec ' . escapeshellarg(PHP_BINARY) .
            ' -S 127.0.0.1:' . $port . ' -t ' . escapeshellarg(__DIR__ . '/fixtures');
        $pipes = [];
        $server = proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);
        static::assertTrue(is_resource($server), 'Failed to start localhost PHP HTTP server.');

        try {
            $ready = false;
            for ($attempt = 0; $attempt < 40; $attempt++) {
                $connection = @fsockopen('127.0.0.1', $port, $socketError, $socketMessage, 0.1);
                if ($connection) {
                    fclose($connection);
                    $ready = true;
                    break;
                }
                usleep(50000);
            }

            static::assertTrue($ready, 'Local PHP HTTP server did not become available.');
            call_user_func($callback, 'http://127.0.0.1:' . $port . '/request-query.php');
        } finally {
            proc_terminate($server);
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }
            proc_close($server);
        }
    }
}
