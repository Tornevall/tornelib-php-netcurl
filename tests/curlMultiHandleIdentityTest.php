<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Module\Network\Wrappers\CurlWrapper;

require_once(__DIR__ . '/../vendor/autoload.php');

/**
 * Regression coverage for PHP 7 resource / PHP 8 CurlHandle identity in curl_multi.
 */
class curlMultiHandleIdentityTest extends TestCase
{
    private function requireCurl()
    {
        if (!extension_loaded('curl')) {
            static::markTestSkipped('cURL extension is required for cURL multi tests.');
        }
    }

    private function getPrivateProperty($propertyName)
    {
        $property = new ReflectionProperty(CurlWrapper::class, $propertyName);
        $property->setAccessible(true);

        return $property;
    }

    private function getPrivateMethod($methodName)
    {
        $method = new ReflectionMethod(CurlWrapper::class, $methodName);
        $method->setAccessible(true);

        return $method;
    }

    public function testMultiHandlesHaveDistinctIdentitiesIncludingUnknownHandles()
    {
        $this->requireCurl();

        $wrapper = new CurlWrapper();
        $handles = [curl_init(), curl_init(), curl_init()];
        $unknown = curl_init();

        try {
            $this->getPrivateProperty('curlMultiHandleObjects')->setValue($wrapper, $handles);
            $lookup = $this->getPrivateMethod('getHandleByResourceId');

            foreach ($handles as $index => $handle) {
                static::assertSame($index, $lookup->invoke($wrapper, $handle));
            }

            static::assertNull($lookup->invoke($wrapper, $unknown));

            // PHP 7 supplies resources; PHP 8 supplies CurlHandle objects.
            if (PHP_VERSION_ID >= 80000) {
                static::assertTrue(is_object($handles[0]));
            } else {
                static::assertTrue(is_resource($handles[0]));
            }

            $this->getPrivateProperty('isCurlMulti')->setValue($wrapper, true);
            $callback = $this->getPrivateMethod('getCurlHeaderRow');

            foreach ($handles as $index => $handle) {
                $callback->invoke($wrapper, $handle, "HTTP/1.1 200 OK\r\n");
                $callback->invoke($wrapper, $handle, 'X-Request-ID: handle-' . $index . "\r\n");
            }

            // Unknown handles must not overwrite the first request's headers.
            $callback->invoke($wrapper, $unknown, "X-Request-ID: unknown\r\n");

            foreach ($handles as $index => $handle) {
                static::assertSame('handle-' . $index, $wrapper->getHeader('X-Request-ID', $index));
            }
        } finally {
            foreach ($handles as $handle) {
                curl_close($handle);
            }
            curl_close($unknown);
        }
    }

    public function testLocalConcurrentMultiRequestsKeepHeadersStatusesAndParsedBodies()
    {
        $this->requireCurl();

        if (DIRECTORY_SEPARATOR === '\\' || !function_exists('proc_open') ||
            !function_exists('stream_socket_server') || !function_exists('fsockopen')
        ) {
            static::markTestSkipped('Local fixture requires process and socket support on POSIX.');
        }

        $listener = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errorMessage);
        if (!$listener) {
            static::fail('Cannot reserve a local HTTP port: ' . $errorMessage);
        }

        $address = stream_socket_get_name($listener, false);
        $port = (int)substr($address, strrpos($address, ':') + 1);
        fclose($listener);

        $command = 'exec ' . escapeshellarg(PHP_BINARY) .
            ' -S 127.0.0.1:' . $port . ' -t ' . escapeshellarg(__DIR__ . '/fixtures');

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $pipes = [];
        $server = proc_open($command, $descriptors, $pipes);
        if (!is_resource($server)) {
            static::fail('Failed to launch local PHP HTTP fixture.');
        }

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

            static::assertTrue($ready, 'Local PHP HTTP fixture did not start.');

            $baseUrl = 'http://127.0.0.1:' . $port . '/curl-multi-response.php';
            $firstUrl = $baseUrl . '?case=first';
            $secondUrl = $baseUrl . '?case=second';
            $duplicateUrl = $baseUrl . '?case=duplicate';

            $cases = [
                ['url' => $firstUrl, 'id' => 'alpha', 'case' => 'first', 'code' => 200, 'type' => 'application/json'],
                ['url' => $secondUrl, 'id' => 'bravo', 'case' => 'second', 'code' => 201, 'type' => 'application/json; charset=utf-8'],
                ['url' => $duplicateUrl, 'id' => 'charlie', 'case' => 'duplicate', 'code' => 202, 'type' => 'application/json'],
                ['url' => $duplicateUrl, 'id' => 'delta', 'case' => 'duplicate', 'code' => 202, 'type' => 'application/json'],
            ];

            $requests = [];
            foreach ($cases as $case) {
                $requests[] = [
                    'url' => $case['url'],
                    'headers' => ['X-Test-Request' => $case['id']],
                ];
            }

            $wrapper = (new CurlWrapper())->request($requests);

            foreach ($cases as $index => $case) {
                static::assertSame($case['id'], $wrapper->getHeader('X-Request-ID', $index));
                static::assertSame($case['case'], $wrapper->getHeader('X-Response-Case', $index));
                static::assertSame($case['type'], $wrapper->getHeader('content-type', $index));
                static::assertSame($case['code'], $wrapper->getCode($index));
            }

            // The existing multi-response API consumes bodies in request order.
            foreach ($cases as $case) {
                $parsed = $wrapper->getParsed();
                static::assertTrue(is_object($parsed));
                static::assertSame($case['case'], $parsed->case);
                static::assertSame($case['id'], $parsed->request_id);
                static::assertSame('GET', $parsed->method);
            }
        } finally {
            proc_terminate($server);
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }
            proc_close($server);
        }
    }
}
