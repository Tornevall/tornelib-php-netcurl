<?php

namespace TorneLIB;

require_once(__DIR__ . "/../vendor/autoload.php");

use PHPUnit\Framework\TestCase;

class sslTest extends TestCase
{

    /** @var MODULE_SSL */
    private $SSL;

    function __setUp() {
        error_reporting(E_ALL);
        $this->SSL = new MODULE_SSL();
    }

    /**
     * @test
     * @testdox Get a certificate bundle
     */
    public function getSslCertificate()
    {
        $this->__setUp();
        // Make sure the open_basedir is reset after other tests
        ini_set('open_basedir', "");
        static::assertTrue(strlen($this->SSL->getSslCertificateBundle(true)) > 0);
    }

    /**
     * @test
     * @testdox If SSL is available, this will be a positive test
     */
    public function getCurlSslAvailable()
    {
        $this->__setUp();
        $sslAvailable = MODULE_SSL::getCurlSslAvailable();
        static::assertCount(0, $sslAvailable);
    }

    /**
     * @test
     * @testdox SSL hardening - nothing is allowed except for a correct SSL setup
     */
    public function strictStream()
    {
        $this->__setUp();
        $sslArray = $this->SSL->getSslStreamContext();
        static::assertTrue($sslArray['verify_peer'] == 1 && $sslArray['verify_peer_name'] == 1 && $sslArray['verify_host'] == 1 && $sslArray['allow_self_signed'] == 1);
    }

    /**
     * @test
     * @testdox Make SSL validation sloppy, allow anything
     */
    public function unStrictStream()
    {
        $this->__setUp();
        $this->SSL->setStrictVerification(false, true);
        $sslArray = $this->SSL->getSslStreamContext();
        static::assertTrue($sslArray['verify_peer'] == false && $sslArray['verify_peer_name'] == false && $sslArray['verify_host'] == false && $sslArray['allow_self_signed'] == true);
    }

    /**
     * @test
     * @testdox Make SSL validation strict but allow self signed certificates
     */
    public function strictStreamSelfSignedAllowed()
    {
        $this->__setUp();
        $this->SSL->setStrictVerification(true, true);
        $sslArray = $this->SSL->getSslStreamContext();
        static::assertTrue($sslArray['verify_peer'] == true && $sslArray['verify_peer_name'] == true && $sslArray['verify_host'] == true && $sslArray['allow_self_signed'] == true);
    }

    /**
     * @test
     * @testdox Get a generated context stream prepared for the SSL configuration
     */
    public function sslStream()
    {
        $this->__setUp();
        $streamContext = $this->SSL->getSslStream();
        static::assertTrue(is_resource($streamContext['stream_context']));
    }
}