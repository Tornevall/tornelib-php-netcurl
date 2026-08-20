<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Exception\Constants;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Model\Interfaces\WrapperInterface;
use TorneLIB\Model\Type\AuthType;
use TorneLIB\Model\Type\DataType;
use TorneLIB\Model\Type\RequestMethod;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Config\WrapperDriver;
use TorneLIB\Module\Network\NetWrapper;

require_once(__DIR__ . '/../vendor/autoload.php');

/**
 * Local driver used to verify the external driver contract without network I/O.
 */
class NetCurlContractWrapper implements WrapperInterface
{
    private $config;
    private $body;
    private $code = 0;

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
        return $this->code;
    }

    public function getVersion()
    {
        return 'contract-test';
    }

    public function request($url, $data = [], $method = RequestMethod::GET, $dataType = DataType::NORMAL)
    {
        if (strpos($url, 'contract://') !== 0) {
            return null;
        }

        $this->body = [
            'url' => $url,
            'data' => $data,
            'method' => $method,
            'dataType' => $dataType,
        ];
        $this->code = 200;

        return $this;
    }
}

class wrapperDriverContractTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testExternalWrapperMustImplementContract()
    {
        try {
            WrapperDriver::register(new stdClass());
            static::fail('WrapperDriver accepted a class without WrapperInterface.');
        } catch (ExceptionHandler $e) {
            static::assertSame(Constants::LIB_CLASS_UNAVAILABLE, $e->getCode());
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testExternalWrapperCanBeRegistered()
    {
        $wrapper = new NetCurlContractWrapper();

        static::assertSame(WrapperDriver::class, WrapperDriver::register($wrapper, false));

        $externalWrappers = WrapperDriver::getExternalWrappers();
        static::assertArrayHasKey(NetCurlContractWrapper::class, $externalWrappers);
        static::assertSame($wrapper, $externalWrappers[NetCurlContractWrapper::class]);
        static::assertFalse(WrapperDriver::getRegisteredWrappersFirst());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRegisteredWrapperCanTakePriorityWithoutChangingCallerApi()
    {
        $wrapper = new NetCurlContractWrapper();
        $netWrapper = new NetWrapper();
        $netWrapper->register($wrapper, true);

        $response = $netWrapper->request(
            'contract://local-driver',
            ['foo' => 'bar'],
            RequestMethod::PATCH,
            DataType::JSON
        );

        static::assertSame($wrapper, $response);
        static::assertSame(200, $response->getCode());
        static::assertSame('contract://local-driver', $response->getBody()['url']);
        static::assertSame(['foo' => 'bar'], $response->getBody()['data']);
        static::assertSame(RequestMethod::PATCH, $response->getBody()['method']);
        static::assertSame(DataType::JSON, $response->getBody()['dataType']);
        static::assertTrue(WrapperDriver::getRegisteredWrappersFirst());
    }
}