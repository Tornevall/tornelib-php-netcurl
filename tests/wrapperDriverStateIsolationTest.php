<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Model\Interfaces\WrapperInterface;
use TorneLIB\Model\Type\AuthType;
use TorneLIB\Model\Type\DataType;
use TorneLIB\Model\Type\RequestMethod;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Config\WrapperDriver;
use TorneLIB\Module\Network\NetWrapper;
use TorneLIB\Module\Network\Wrappers\CurlWrapper;
use TorneLIB\Module\Network\Wrappers\SimpleStreamWrapper;

require_once(__DIR__ . '/../vendor/autoload.php');

/**
 * Registered external wrappers are intentionally caller-owned objects.
 */
class StateIsolationExternalDriver implements WrapperInterface
{
    private $config;
    private $body = '';
    private $requests = 0;

    public function __construct()
    {
        $this->config = new WrapperConfig();
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function setConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    public function setAuthentication($username, $password, $authType = AuthType::BASIC)
    {
        $this->config->setAuthentication($username, $password, $authType);
        return $this;
    }

    public function getAuthentication()
    {
        return $this->config->getAuthentication();
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getParsed()
    {
        return $this->body;
    }

    public function getCode()
    {
        return 200;
    }

    public function getVersion()
    {
        return 'test';
    }

    public function getRequests()
    {
        return $this->requests;
    }

    public function request($url, $data = [], $method = RequestMethod::GET, $dataType = DataType::NORMAL)
    {
        $this->requests++;
        $this->body = $url;

        return $this;
    }
}

/**
 * Internal transport instances must not share mutable request state.
 */
class wrapperDriverStateIsolationTest extends TestCase
{
    public function testInternalDriverDiscoveryCreatesFreshTransportInstances()
    {
        WrapperDriver::initializeWrappers();

        foreach (['CurlWrapper', 'SimpleStreamWrapper'] as $name) {
            $first = WrapperDriver::getWrapperAllowed($name, true);
            if ($first === null) {
                // This environment may not have the matching PHP extension enabled.
                continue;
            }

            $second = WrapperDriver::getWrapperAllowed($name, true);

            static::assertNotSame($first, $second);
            static::assertNotSame($first->getConfig(), $second->getConfig());

            // Mutating one transport's settings must not change a later transport.
            $first->getConfig()->setAuthentication('client-a', 'fixture-secret-a');
            static::assertSame('', $second->getAuthentication()['username']);
        }
    }

    public function testInterleavedNetWrappersKeepTheirOwnHttpResponsesAndCredentials()
    {
        if (!extension_loaded('curl')) {
            static::markTestSkipped('This local HTTP regression exercises the cURL transport.');
        }

        if (DIRECTORY_SEPARATOR === '\\' ||
            !function_exists('proc_open') ||
            !function_exists('stream_socket_server') ||
            !function_exists('fsockopen')
        ) {
            static::markTestSkipped('Local HTTP fixture requires POSIX process and socket support.');
        }

        $listener = @stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        static::assertNotFalse($listener, 'Could not reserve a local server port: ' . $error);

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
        static::assertTrue(is_resource($server), 'Could not start local HTTP fixture.');

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
            static::assertTrue($ready, 'Local HTTP fixture did not become available.');

            $base = 'http://127.0.0.1:' . $port . '/curl-multi-response.php?case=';
            $first = new NetWrapper();
            $second = new NetWrapper();

            $first->setHeader('X-Test-Request', 'alpha');
            $second->setHeader('X-Test-Request', 'bravo');
            $first->setAuthentication('client-a', 'fixture-secret-a');
            $second->setAuthentication('client-b', 'fixture-secret-b');

            $firstResult = $first->request($base . 'first');
            static::assertTrue($firstResult instanceof CurlWrapper);
            static::assertSame(200, $first->getCode());
            static::assertSame('alpha', $first->getHeader('X-Request-ID'));
            static::assertSame('alpha', $first->getParsed()->request_id);

            $secondResult = $second->request($base . 'second');
            static::assertTrue($secondResult instanceof CurlWrapper);

            static::assertNotSame($firstResult, $secondResult);
            static::assertNotSame($firstResult->getConfig(), $secondResult->getConfig());
            static::assertSame('client-a', $firstResult->getAuthentication()['username']);
            static::assertSame('client-b', $secondResult->getAuthentication()['username']);

            // The first client must still expose its original result after the second runs.
            static::assertSame(200, $first->getCode());
            static::assertSame('alpha', $first->getHeader('X-Request-ID'));
            static::assertSame('first', $first->getParsed()->case);
            static::assertSame('alpha', $first->getParsed()->request_id);
            static::assertSame('first', json_decode($first->getBody())->case);

            static::assertSame(201, $second->getCode());
            static::assertSame('bravo', $second->getHeader('X-Request-ID'));
            static::assertSame('second', $second->getParsed()->case);
            static::assertSame('bravo', $second->getParsed()->request_id);

            // Reusing the first client must not overwrite the second client's result.
            $thirdResult = $first->request($base . 'duplicate');
            static::assertNotSame($firstResult, $thirdResult);
            static::assertSame(202, $first->getCode());
            static::assertSame('alpha', $first->getParsed()->request_id);

            static::assertSame(201, $second->getCode());
            static::assertSame('bravo', $second->getParsed()->request_id);
            static::assertSame('second', json_decode($second->getBody())->case);
        } finally {
            proc_terminate($server);
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }
            proc_close($server);
        }
    }

    public function testRegisteredExternalDriversRemainCallerOwnedAndRetainPriority()
    {
        // Restore shared registration state even on PHPUnit versions that ignore
        // legacy process-isolation annotations.
        $registry = new ReflectionProperty(WrapperDriver::class, 'externalWrapperList');
        $priority = new ReflectionProperty(WrapperDriver::class, 'useRegisteredWrappersFirst');
        $registry->setAccessible(true);
        $priority->setAccessible(true);

        $previousRegistry = $registry->getValue();
        $previousPriority = $priority->getValue();
        try {
            $external = new StateIsolationExternalDriver();
            WrapperDriver::register($external, true);

            static::assertTrue(WrapperDriver::getRegisteredWrappersFirst());
            static::assertSame($external, WrapperDriver::getWrapperAllowed(StateIsolationExternalDriver::class));
            static::assertSame($external, WrapperDriver::getWrapperAllowed(StateIsolationExternalDriver::class));

            $first = (new NetWrapper())->request('state-isolation://one');
            $second = (new NetWrapper())->request('state-isolation://two');

            static::assertSame($external, $first);
            static::assertSame($external, $second);
            static::assertSame('state-isolation://two', $external->getBody());
            static::assertSame(2, $external->getRequests());
        } finally {
            $registry->setValue($previousRegistry);
            $priority->setValue($previousPriority);
        }
    }
}
