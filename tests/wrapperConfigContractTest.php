<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Model\Type\AuthType;
use TorneLIB\Model\Type\DataType;
use TorneLIB\Model\Type\RequestMethod;
use TorneLIB\Module\Config\WrapperConfig;

require_once(__DIR__ . '/../vendor/autoload.php');

/**
 * Deterministic compatibility tests for the shared request configuration.
 *
 * These tests deliberately avoid external network dependencies. The behavior
 * covered here is part of the compatibility baseline for later NetCurl lines.
 */
class wrapperConfigContractTest extends TestCase
{
    public function testNormalGetArrayIsEncodedAsQueryData()
    {
        $config = new WrapperConfig();

        static::assertSame(
            '&foo=bar&count=2',
            $config->getRequestData(
                DataType::NORMAL,
                ['foo' => 'bar', 'count' => 2],
                RequestMethod::GET
            )
        );
    }

    public function testNormalPostArrayIsEncodedAsFormData()
    {
        $config = new WrapperConfig();

        static::assertSame(
            'foo=bar&count=2',
            $config->getRequestData(
                DataType::NORMAL,
                ['foo' => 'bar', 'count' => 2],
                RequestMethod::POST
            )
        );
    }

    public function testJsonArrayIsEncoded()
    {
        $config = new WrapperConfig();

        static::assertSame(
            '{"foo":"bar","count":2}',
            $config->getRequestData(
                DataType::JSON,
                ['foo' => 'bar', 'count' => 2],
                RequestMethod::POST
            )
        );
    }

    public function testJsonObjectIsEncoded()
    {
        $config = new WrapperConfig();
        $data = new stdClass();
        $data->foo = 'bar';
        $data->count = 2;

        static::assertSame(
            '{"foo":"bar","count":2}',
            $config->getRequestData(DataType::JSON, $data, RequestMethod::POST)
        );
    }

    public function testJsonStringPassesThroughUntouched()
    {
        $config = new WrapperConfig();
        $json = '{"foo":"bar"}';

        static::assertSame(
            $json,
            $config->getRequestData(DataType::JSON, $json, RequestMethod::POST)
        );
    }

    public function testRawStringPassesThroughForNormalData()
    {
        $config = new WrapperConfig();
        $raw = 'already=encoded&value=yes';

        static::assertSame(
            $raw,
            $config->getRequestData(DataType::NORMAL, $raw, RequestMethod::POST)
        );
    }

    public function testRawStringPassesThroughForXmlData()
    {
        $config = new WrapperConfig();
        $xml = '<request><foo>bar</foo></request>';

        static::assertSame(
            $xml,
            $config->getRequestData(DataType::XML, $xml, RequestMethod::POST)
        );
    }

    public function testXmlArrayCanBeConvertedToXml()
    {
        $config = new WrapperConfig();
        $xml = $config->getRequestData(
            DataType::XML,
            ['foo' => 'bar'],
            RequestMethod::POST
        );

        static::assertTrue(is_string($xml));
        static::assertNotFalse(strpos($xml, 'foo'));
        static::assertNotFalse(strpos($xml, 'bar'));
    }

    public function testRequestStoresUrlMethodTypeAndArrayData()
    {
        $config = new WrapperConfig();
        $config->request(
            'https://example.invalid/api',
            ['foo' => 'bar'],
            RequestMethod::PATCH,
            DataType::JSON
        );

        static::assertSame('https://example.invalid/api', $config->getRequestUrl());
        static::assertSame(RequestMethod::PATCH, $config->getRequestMethod());
        static::assertSame(DataType::JSON, $config->getRequestDataType());
        static::assertSame('{"foo":"bar"}', $config->getRequestData());
    }

    public function testRequestStoresRawStringData()
    {
        $config = new WrapperConfig();
        $raw = '{"already":"encoded"}';
        $config->request(
            'https://example.invalid/api',
            $raw,
            RequestMethod::POST,
            DataType::JSON
        );

        static::assertSame($raw, $config->getRequestData());
    }

    public function testInvalidRequestMethodFallsBackToGet()
    {
        $config = new WrapperConfig();
        $config->setRequestMethod('invalid-method');

        static::assertSame(RequestMethod::GET, $config->getRequestMethod());
    }

    public function testHeadersRemainAvailableThroughSharedConfig()
    {
        $config = new WrapperConfig();
        $config->setHeader('X-NetCurl-Test', 'yes', true);

        $headers = $config->getHeader();

        static::assertArrayHasKey('X-NetCurl-Test', $headers);
        static::assertSame('yes', $headers['X-NetCurl-Test']);
    }

    public function testAuthenticationStateIsStored()
    {
        $config = new WrapperConfig();
        $config->setAuthentication('username', 'password', AuthType::BASIC);

        $authentication = $config->getAuthentication();

        static::assertSame('username', $authentication['username']);
        static::assertSame('password', $authentication['password']);
        static::assertSame(AuthType::BASIC, $authentication['type']);
    }

    public function testProxyStateIsStoredIndependentlyOfTransport()
    {
        $config = new WrapperConfig();
        $config->setProxy('127.0.0.1:8080', 0);

        static::assertSame('127.0.0.1:8080', $config->getProxy());
        static::assertSame(0, $config->getProxyType());
    }

    public function testSecondConfigDoesNotInheritInstanceHeaders()
    {
        $first = new WrapperConfig();
        $second = new WrapperConfig();

        $first->setHeader('X-Instance-One', 'first');

        static::assertArrayHasKey('X-Instance-One', $first->getHeader());
        static::assertArrayNotHasKey('X-Instance-One', $second->getHeader());
    }

    public function testSecondConfigDoesNotInheritInstanceAuthentication()
    {
        $first = new WrapperConfig();
        $second = new WrapperConfig();

        $first->setAuthentication('first-user', 'first-password', AuthType::BASIC);

        static::assertSame('first-user', $first->getAuthentication()['username']);
        static::assertSame('', $second->getAuthentication()['username']);
        static::assertSame('', $second->getAuthentication()['password']);
    }

    public function testSecondConfigDoesNotInheritProxyState()
    {
        $first = new WrapperConfig();
        $second = new WrapperConfig();

        $first->setProxy('127.0.0.1:9999', 0);

        static::assertSame('127.0.0.1:9999', $first->getProxy());
        static::assertSame('', $second->getProxy());
    }

    public function testSecondConfigDoesNotInheritRequestState()
    {
        $first = new WrapperConfig();
        $second = new WrapperConfig();

        $first->request(
            'https://example.invalid/first',
            ['value' => 'first'],
            RequestMethod::POST,
            DataType::JSON
        );

        static::assertSame('https://example.invalid/first', $first->getRequestUrl());
        static::assertSame('', $second->getRequestUrl());
        static::assertSame(RequestMethod::GET, $second->getRequestMethod());
        static::assertSame(DataType::NORMAL, $second->getRequestDataType());
    }

    public function testSecondConfigDoesNotInheritTimeoutChanges()
    {
        $first = new WrapperConfig();
        $second = new WrapperConfig();

        $first->setTimeout(40);

        static::assertEquals(20, $first->getTimeout()['CONNECT']);
        static::assertEquals(40, $first->getTimeout()['REQUEST']);
        static::assertNotEquals($first->getTimeout(), $second->getTimeout());
    }

    public function testTimeoutSecondsAreStoredForAllDrivers()
    {
        $config = new WrapperConfig();
        $config->setTimeout(11, false);
        $timeout = $config->getTimeout();

        static::assertEquals(6, $timeout['CONNECT']);
        static::assertEquals(11, $timeout['REQUEST']);
        static::assertFalse($timeout['MILLISEC']);
    }

    public function testTimeoutMillisecondsAreStoredForAllDrivers()
    {
        $config = new WrapperConfig();
        $config->setTimeout(900, true);
        $timeout = $config->getTimeout();

        static::assertEquals(450, $timeout['CONNECT']);
        static::assertEquals(900, $timeout['REQUEST']);
        static::assertTrue($timeout['MILLISEC']);
    }

    public function testHasDataAcceptsObjectsAndNonEmptyArrays()
    {
        $config = new WrapperConfig();

        static::assertTrue($config->hasData((object)['foo' => 'bar']));
        static::assertTrue($config->hasData(['foo' => 'bar']));
        static::assertFalse($config->hasData([]));
    }
}