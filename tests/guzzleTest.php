<?php

namespace TorneLIB;

require_once(__DIR__ . "/../vendor/autoload.php");
require_once(__DIR__ . '/testurls.php');

use PHPUnit\Framework\TestCase;

class guzzleTest extends TestCase
{
    /** @var MODULE_CURL $CURL */
    private $CURL;

    /**
     * @throws \Exception
     */
    function __setUp() {
        error_reporting(E_ALL);
        $this->CURL = new MODULE_CURL();
    }

    /**
     * @param bool $useStream
     *
     * @return bool
     * @throws \Exception
     */
    private function hasGuzzle($useStream = false)
    {
        $returnThis = null;
        try {
            if (!$useStream) {
                $returnThis = is_object($this->CURL->setDriver(NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP)) ? true : false;
            } else {
                $returnThis = is_object($this->CURL->setDriver(NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP_STREAM)) ? true : false;
            }
        } catch (\Exception $e) {
            static::markTestSkipped("Can not test guzzle driver without guzzle (" . $e->getMessage() . ")");

            return false;
        }

        return $returnThis;
    }

    /**
     * @test
     * @throws \Exception
     */
    public function enableGuzzle()
    {
        $this->__setUp();
        if ($this->hasGuzzle()) {
            $info = $this->CURL->doPost("https://" . \TESTURLS::getUrlTests() . "?o=json&getjson=true&var1=HasVar1",
                ['var2' => 'HasPostVar1'])->getParsed();
            //$this->CURL->getExternalDriverResponse();
            //$parsed = $this->CURL->getParsedResponse( $info );
            static::assertTrue($info->methods->_REQUEST->var1 === "HasVar1");
        } else {
            static::markTestSkipped("Can not test guzzle driver without guzzle");
        }
    }

    /**
     * @test
     * @throws \Exception
     */
    public function enableGuzzleStream()
    {
        $this->__setUp();
        if ($this->hasGuzzle(true)) {
            $info = $this->CURL->doPost("https://" . \TESTURLS::getUrlTests() . "?o=json&getjson=true&getVar=true",
                [
                    'var1' => 'HasVar1',
                    'postVar' => "true",
                ])->getParsed();
            //$parsed = $this->CURL->getParsedResponse( $info );
            static::assertTrue($info->methods->_REQUEST->var1 === "HasVar1");
        } else {
            static::markTestSkipped("Can not test guzzle driver without guzzle");
        }
    }

    /**
     * @test
     * @throws \Exception
     */
    public function enableGuzzleStreamJson()
    {
        $this->__setUp();
        if ($this->hasGuzzle(true)) {
            $info = $this->CURL->doPost("https://" . \TESTURLS::getUrlTests() . "?o=json&getjson=true&getVar=true",
                [
                    'var1' => 'HasVar1',
                    'postVar' => "true",
                    'asJson' => 'true',
                ], NETCURL_POST_DATATYPES::DATATYPE_JSON)->getParsed();
            //$parsed = $this->CURL->getParsedResponse( $info );
            static::assertTrue($info->methods->_REQUEST->var1 === "HasVar1");
        } else {
            static::markTestSkipped("Can not test guzzle driver without guzzle");
        }
    }

    /**
     * @test
     */
    public function enableGuzzleWsdl()
    {
        $this->__setUp();
        try {
            if ($this->hasGuzzle()) {
                // Currently, this one will fail over to SimpleSoap
                $info = $this->CURL->doGet("http://" . \TESTURLS::getUrlSoap());
                static::assertTrue(is_object($info));
            } else {
                static::markTestSkipped("Can not test guzzle driver without guzzle");
            }
        } catch (\Exception $e) {
            if ($e->getCode() < 3) {
                static::markTestSkipped('Getting exception codes below 3 here, might indicate that your cacerts is not installed properly or the connection to the server is not responding');

                return;
            } elseif ($e->getCode() >= 500) {
                static::markTestSkipped("Got errors (" . $e->getCode() . ") on URL call, can't complete request: " . $e->getMessage());
            }
        }
    }

    /**
     * @test
     */
    public function enableGuzzleErrors()
    {
        $this->__setUp();
        if ($this->hasGuzzle()) {
            try {
                $this->CURL->doPost(\TESTURLS::getUrlTests() . "&o=json&getjson=true", ['var1' => 'HasVar1']);
            } catch (\Exception $wrapError) {
                static::assertTrue($wrapError->getCode() == 404);
            }
        } else {
            static::markTestSkipped("Can not test guzzle driver without guzzle");
        }
    }
}
