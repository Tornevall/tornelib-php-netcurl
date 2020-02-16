<?php

namespace TorneLIB;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once(__DIR__ . '/../vendor/autoload.php');
}

if (file_exists(__DIR__ . "/../tornelib.php")) {
    // Work with TorneLIBv5
    /** @noinspection PhpIncludeInspection */
    require_once(__DIR__ . '/../tornelib.php');
}
require_once(__DIR__ . '/testurls.php');

use PHPUnit\Framework\TestCase;

ini_set('memory_limit', -1);    // Free memory limit, some tests requires more memory (like ip-range handling)

class cookieTest extends TestCase
{

    private $CURL;
    private $NETWORK;

    function __setUp()
    {
        $this->CURL = new MODULE_CURL();
        $this->NETWORK = new MODULE_NETWORK();
    }

    /**
     * @test
     * @testdox Activation of storing cookies locally
     */
    public function enableLocalCookiesInSysTemp()
    {
        $this->__setUp();
        $this->CURL->setLocalCookies(true);
        try {
            $this->CURL->setFlag('NETCURL_COOKIE_TEMP_LOCATION', true);
        } catch (\Exception $e) {
        }
        // For Linux based systems, we go through /tmp
        static::assertStringStartsWith("/tmp/netcurl", $this->CURL->getCookiePath());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function enableLocalCookiesInSysTempProhibited()
    {
        $this->__setUp();
        $this->CURL->setLocalCookies(true);
        static::assertEquals('', $this->CURL->getCookiePath());
    }

    /**
     * @test
     * @testdox Set own temporary directory (remove it first so tests gives correct responses) - also testing directory
     *          creation
     * @throws \Exception
     */
    public function enableLocalCookiesSelfLocated()
    {
        $this->__setUp();
        $this->CURL->setLocalCookies(true);
        if (file_exists("/tmp/netcurl_self")) {
            @rmdir("/tmp/netcurl_self");
        }
        $this->CURL->setFlag('NETCURL_COOKIE_LOCATION', '/tmp/netcurl_self');
        // For Linux based systems, we go through /tmp
        static::assertStringStartsWith("/tmp/netcurl_self", $this->CURL->getCookiePath());
        @rmdir("/tmp/netcurl_self");
    }
}
