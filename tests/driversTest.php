<?php

namespace TorneLIB;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once(__DIR__ . '/../vendor/autoload.php');
}

require_once(__DIR__ . "/testurls.php");

use PHPUnit\Framework\TestCase;

class driversTest extends TestCase
{

    /** @var NETCURL_DRIVER_CONTROLLER $DRIVERCLASS */
    private $DRIVERCLASS;

    /** @var MODULE_CURL $CURL */
    private $CURL;

    function setUp()
    {
        error_reporting(E_ALL);
        $this->DRIVERCLASS = new NETCURL_DRIVER_CONTROLLER();
        try {
            $this->CURL = new MODULE_CURL();
        } catch (\Exception $e) {
        }
    }

    /**
     * @test
     */
    public function getSystemWideDrivers()
    {
        if ($this->DRIVERCLASS->getSystemWideDrivers()) {
            static::assertTrue(count($this->DRIVERCLASS->getSystemWideDrivers()) >= 1 ? true : false);
        } else {
            static::markTestSkipped("Test is built to return an array of available drivers. Your system seem to miss at least one driver, to trig this test. This test is skipped.");
        }
    }

    /**
     * @test
     */
    public function getDisabledFunctions()
    {
        static::assertTrue(is_array($this->DRIVERCLASS->getDisabledFunctions()));
    }

    /**
     * @test
     */
    public function getIsDisabled()
    {
        $UPPERCASE = $this->DRIVERCLASS->getIsDisabled('CURL_INIT, CURL_EXEC');
        $simple = $this->DRIVERCLASS->getIsDisabled('curl_init');
        $array = $this->DRIVERCLASS->getIsDisabled(['curl_init', 'curl_exec']);
        if ($UPPERCASE && $simple && $array) {
            static::assertTrue($UPPERCASE && $simple && $array);
        } else {
            static::markTestSkipped("Vital functions that should trigger this test is not disabled.");
        }
    }

    /**
     * @test
     */
    public function getStaticCurl()
    {
        static::assertTrue(NETCURL_DRIVER_CONTROLLER::getCurl());
    }

    /**
     * @test
     */
    public function getDriverAvailable()
    {
        static::assertTrue($this->DRIVERCLASS->getIsDriver(NETCURL_NETWORK_DRIVERS::DRIVER_CURL));
    }

    /**
     * @test
     * @throws \Exception
     */
    public function getGuzzleDriver()
    {
        if (!$this->DRIVERCLASS->getIsDriver(NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP)) {
            static::markTestSkipped("Guzzle is unavailable for this test");

            return;
        }
        static::assertTrue(is_object($this->DRIVERCLASS->getDriver(NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP)));
    }

    /**
     * @test
     * @throws \Exception
     */
    public function getDriverGuzzle()
    {
        if ($this->DRIVERCLASS->getIsDriver(NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP)) {
            $this->CURL->setDriver(NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP);
            $returnedDriver = $this->CURL->getDriver();
            static::assertStringEndsWith("GUZZLEHTTP", get_class($returnedDriver));
        } else {
            static::markTestSkipped("Can not test guzzle without guzzle");
        }
    }

    /**
     * @test
     * @testdox Auto detection of drivers (choose "next available")
     * @throws \Exception
     */
    public function autoDetect()
    {
        $this->CURL->setDriverAuto();
        $driverIdentification = $this->CURL->getDriver();
        if (is_object($driverIdentification)) {
            static::markTestSkipped(print_r($driverIdentification, true) . "/isObject/another driver than curl");
        } else {
            static::assertTrue($this->CURL->getDriver() === NETCURL_NETWORK_DRIVERS::DRIVER_CURL, "internalDriver");
        }
    }

    /**
     * @test
     * @throws \Exception
     */
    public function setGuzzle()
    {
        if ($this->DRIVERCLASS->getIsDriver(NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP) || $this->DRIVERCLASS->getIsDriver(NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP_STREAM)) {
            $this->CURL->setDriver(NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP_STREAM);
            static::assertTrue(is_object($this->CURL->getDriver()));
        } else {
            static::markTestSkipped("Can not test guzzle without guzzle");
        }
    }

    /**
     * @test
     * @throws \Exception
     */
    public function doCurl()
    {
        $php53AntiChain = $this->CURL->doGet("https://identifier.tornevall.net/?json");
        if (method_exists($php53AntiChain, 'getParsedResponse')) {
            /** @var MODULE_CURL $requestContent */
            $requestContent = $php53AntiChain->getParsed();
            static::assertTrue(is_object($requestContent) && isset($requestContent->ip));
        } else {
            static::markTestSkipped("This test is disabled as most of the testing is based on chaining, which is not available from PHP 5.3 (" . PHP_VERSION . ")");
        }
    }
}
