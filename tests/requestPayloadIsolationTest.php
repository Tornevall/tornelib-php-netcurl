<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Model\Type\DataType;
use TorneLIB\Model\Type\RequestMethod;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Network\Wrappers\CurlWrapper;
use TorneLIB\Module\Network\Wrappers\SimpleStreamWrapper;

require_once(__DIR__ . '/../vendor/autoload.php');

/**
 * Regression coverage for request data retained across consecutive calls.
 */
class requestPayloadIsolationTest extends TestCase
{
    public function testConfigurationResetsBodyEvenWhenNextPayloadIsEmpty()
    {
        $config = new WrapperConfig();
        $config->setAuthentication('client', 'fixture-password');
        $config->setTimeout(10);

        $config->request('https://example.invalid/first', ['secret' => 'form-sentinel'], RequestMethod::POST);
        static::assertSame('secret=form-sentinel', $config->getRequestData());
        static::assertSame('secret=form-sentinel', $config->getRequestDataContainer());

        $config->request('https://example.invalid/empty-post', [], RequestMethod::POST);
        static::assertSame('', $config->getRequestData());
        static::assertSame('', $config->getRequestDataContainer());
        static::assertSame(RequestMethod::POST, $config->getRequestMethod());

        $config->request('https://example.invalid/empty-get', [], RequestMethod::GET);
        static::assertSame('', $config->getRequestData());
        static::assertSame(RequestMethod::GET, $config->getRequestMethod());

        $config->request('https://example.invalid/json', ['secret' => 'json-sentinel'], RequestMethod::POST, DataType::JSON);
        static::assertSame('{"secret":"json-sentinel"}', $config->getRequestData());

        $config->request('https://example.invalid/empty-json', [], RequestMethod::POST, DataType::JSON);
        static::assertSame('[]', $config->getRequestData());

        $config->request('https://example.invalid/empty-string', '', RequestMethod::POST, DataType::JSON);
        static::assertSame('', $config->getRequestData());

        $config->request('https://example.invalid/explicit-null', null, RequestMethod::GET);
        static::assertSame('', $config->getRequestData());
        static::assertSame('client', $config->getAuthentication()['username']);
        static::assertSame(5, $config->getTimeout()['CONNECT']);
    }

    public function testReusedCurlWrapperNeverResendsAnEarlierPayload()
    {
        if (!extension_loaded('curl')) {
            static::markTestSkipped('cURL extension is unavailable.');
        }

        $this->withLocalHttpFixture(function ($url) {
            $this->assertPayloadSequence(new CurlWrapper(), $url);
        });
    }

    public function testReusedStreamWrapperNeverResendsAnEarlierPayload()
    {
        $this->withLocalHttpFixture(function ($url) {
            $this->assertPayloadSequence(new SimpleStreamWrapper(), $url);
        });
    }

    private function assertPayloadSequence($wrapper, $url)
    {
        $cases = [
            ['POST', RequestMethod::POST, DataType::NORMAL, ['secret' => 'form-sentinel'], 'secret=form-sentinel'],
            ['POST', RequestMethod::POST, DataType::NORMAL, [], ''],
            ['GET', RequestMethod::GET, DataType::NORMAL, [], ''],
            ['POST', RequestMethod::POST, DataType::JSON, ['secret' => 'json-sentinel'], '{"secret":"json-sentinel"}'],
            ['POST', RequestMethod::POST, DataType::JSON, [], '[]'],
            ['POST', RequestMethod::POST, DataType::JSON, '', ''],
            ['POST', RequestMethod::POST, DataType::XML, '<root><secret>xml-sentinel</secret></root>', '<root><secret>xml-sentinel</secret></root>'],
            ['POST', RequestMethod::POST, DataType::XML, '', ''],
            ['GET', RequestMethod::GET, DataType::NORMAL, [], ''],
        ];

        foreach ($cases as $index => $case) {
            $response = $wrapper->request($url . '?step=' . $index, $case[3], $case[1], $case[2]);
            $payload = json_decode($response->getBody());

            static::assertTrue(is_object($payload), 'Fixture returned invalid JSON at step ' . $index);
            static::assertSame($case[0], $payload->method, 'HTTP method mismatch at step ' . $index);
            static::assertSame($case[4], $payload->body, 'Request body mismatch at step ' . $index);
        }
    }

    private function withLocalHttpFixture($callback)
    {
        if (DIRECTORY_SEPARATOR === '\\' || !function_exists('proc_open') ||
            !function_exists('stream_socket_server') || !function_exists('fsockopen')
        ) {
            static::markTestSkipped('Local HTTP fixture requires process and socket support on POSIX.');
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
        static::assertTrue(is_resource($server), 'Failed to start local PHP HTTP server.');

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
            static::assertTrue($ready, 'Local PHP server did not become available.');
            call_user_func($callback, 'http://127.0.0.1:' . $port . '/request-payload.php');
        } finally {
            proc_terminate($server);
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }
            proc_close($server);
        }
    }
}
