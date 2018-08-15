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

use Exception;
use PHPUnit\Framework\TestCase;

ini_set('memory_limit', -1);    // Free memory limit, some tests requires more memory (like ip-range handling)

class curlTest extends TestCase
{
    private $StartErrorReporting;

    /** @var MODULE_NETWORK */
    private $NETWORK;
    /** @var MODULE_CURL */
    private $CURL;
    /** @var NETCURL_DRIVER_CONTROLLER $DRIVER */
    private $DRIVER;
    private $CurlVersion = null;

    /**
     * @var string $bitBucketUrl Bitbucket URL without scheme
     */
    private $bitBucketUrl = 'bitbucket.tornevall.net/scm/lib/tornelib-php-netcurl.git';

    //function tearDown() {}

    /**
     * @throws Exception
     */
    function setUp()
    {
        error_reporting(E_ALL);

        //$this->setDebug(true);
        $this->StartErrorReporting = error_reporting();
        $this->NETWORK = new MODULE_NETWORK();
        $this->CURL = new MODULE_CURL();
        $this->DRIVER = new NETCURL_DRIVER_CONTROLLER();
        //$this->CURL->setTimeout( 6 );
        $this->CURL->setUserAgent("PHPUNIT");

        if (function_exists('curl_version')) {
            $CurlVersionRequest = curl_version();
            $this->CurlVersion = $CurlVersionRequest['version'];
        }

        $this->CURL->setSslStrictFallback(false);
    }

    function tearDown()
    {
        // DebugData collects stats about the curled session.
        // $debugData = $this->CURL->getDebugData();
    }

    /**
     * @test
     * @testdox Runs a simple test to see if there is a container as it should
     * @throws Exception
     */
    function simpleGetUrl()
    {
        $this->pemDefault();
        $container = $this->simpleGet();
        static::assertTrue($this->hasBody($container));
    }

    /**
     * @throws Exception
     */
    private function pemDefault()
    {
        $this->CURL->setFlag('_DEBUG_TCURL_UNSET_LOCAL_PEM_LOCATION', false);
        $this->CURL->setSslVerify(true);
    }

    /**
     * @return array|null|string|MODULE_CURL|NETCURL_HTTP_OBJECT
     * @throws Exception
     */
    private function simpleGet()
    {
        return $this->CURL->doGet(\TESTURLS::getUrlSimple());
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */

    /**
     * @param $container
     * @return bool
     */
    private function hasBody($container)
    {
        if (is_array($container) && isset($container['body'])) {
            return true;
        }
        if (is_object($container)) {
            if (method_exists($container, 'getBody') && is_string($container->getBody())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @test
     * @testdox Fetch a response and immediately pick up the parsed response, from the internally stored last response
     * @throws Exception
     */
    function getParsedSelf()
    {
        $this->pemDefault();
        $this->urlGet("ssl&bool&o=json&method=get");
        $ParsedResponse = $this->CURL->getParsed();
        static::assertTrue(is_object($ParsedResponse));
    }

    /**
     * @param string $parameters
     * @param string $protocol
     * @param string $indexFile
     * @return array|null|string|MODULE_CURL|NETCURL_HTTP_OBJECT
     * @throws Exception
     */
    private function urlGet($parameters = '', $protocol = "http", $indexFile = 'index.php')
    {
        $theUrl = $this->getProtocol($protocol) . "://" . \TESTURLS::getUrlTests() . $indexFile . "?" . $parameters;

        return $this->CURL->doGet($theUrl);
    }

    /**
     * Make sure we always get a protocol
     * @param string $protocol
     * @return string
     */
    private function getProtocol($protocol = 'http')
    {
        if (empty($protocol)) {
            $protocol = "http";
        }

        return $protocol;
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */

    /**
     * @test
     * @testdox Make a direct call to the curl library
     * @throws Exception
     */
    function quickInitParsed()
    {
        $tempCurl = new MODULE_CURL("https://identifier.tornevall.net/index.php?json");
        static::assertTrue(is_object($tempCurl->getParsed()));
    }

    /**
     * @test
     * @testdox Make a direct call to the curl library and get the response code
     * @throws Exception
     */
    function quickInitResponseCode()
    {
        $tempCurl = new MODULE_CURL("https://identifier.tornevall.net/?json");
        static::assertTrue($tempCurl->getCode() == 200);
    }

    /**
     * @test
     * @testdox Make a direct call to the curl library and get the content of the body
     * @throws Exception
     */
    function quickInitResponseBody()
    {
        $tempCurl = new MODULE_CURL("https://identifier.tornevall.net/?json");
        // Some content must exists in the body
        static::assertTrue(strlen($tempCurl->getBody()) >= 10);
    }

    /**
     * @test
     * @testdox Fetch a response and immediately pick up the parsed response, from own content
     * @throws Exception
     */
    function getParsedFromResponse()
    {
        $this->pemDefault();
        $container = $this->urlGet("ssl&bool&o=json&method=get");
        $ParsedResponse = $this->CURL->getParsed($container);
        static::assertTrue(is_object($ParsedResponse));
    }

    /*function testSimpleGetProxy() {
        $this->pemDefault();
        exec( "service tor status", $ubuntuService );
        $serviceFound = false;
        foreach ( $ubuntuService as $row ) {
            // Unsafe control
            if ( preg_match( "/loaded: loaded/i", $row ) ) {
                $serviceFound = true;
            }
        }
        if ( $serviceFound ) {
            $this->CURL->setProxy( "127.0.0.1:9050", CURLPROXY_SOCKS5 );
            $container = $this->simpleGet();
            $ipType    = $this->NET->getArpaFromAddr( $this->CURL->getResponseBody( $container ), true );
            static::assertTrue( $ipType > 0 );

            return;
        }
        $this->markTestSkipped( "I can't test this simpleGetProxy since there are no tor service installed" );
    }*/

    /*	function testSimpleGetWsdlProxy() {
            $this->pemDefault();
            exec( "service tor status", $ubuntuService );
            $serviceFound = false;
            foreach ( $ubuntuService as $row ) {
                // Unsafe control
                if ( preg_match( "/loaded: loaded/i", $row ) ) {
                    $serviceFound = true;
                }
            }
            if ( $serviceFound ) {
                $this->CURL->setProxy( "127.0.0.1:9050", CURLPROXY_SOCKS5 );
                $container = $this->getBody($this->CURL->doGet("https://" . $this->Urls['soap']));
                return;
            }
            $this->markTestSkipped( "I can't test this simpleGetProxy since there are no tor service installed" );
        }*/

    /**
     * @test
     * @testdox Request a specific value from a parsed response
     * @throws Exception
     */
    function getParsedValue()
    {
        $this->pemDefault();
        $this->urlGet("ssl&bool&o=json&method=get");
        //$this->CURL->getParsed();
        $ValueFrom = $this->CURL->getValue('methods');
        static::assertTrue(is_object($ValueFrom->_REQUEST));
    }

    /**
     * @test
     * @testdox Request a nested value from a parsed response
     * @throws Exception
     */
    function getParsedSubValue()
    {
        $this->pemDefault();
        $this->urlGet("ssl&bool&o=json&method=get");
        $ValueFrom = $this->CURL->getValue(array('nesting', 'subarr4', 'child4'));
        static::assertTrue(count($ValueFrom) === 3);
    }

    /**
     * @test
     * @testdox Request a value by sending wrong value into the parser (crash test)
     * @throws Exception
     */
    function getParsedSubValueNoArray()
    {
        $this->pemDefault();
        $this->urlGet("ssl&bool&o=json&method=get");
        $ValueFrom = $this->CURL->getValue(new \stdClass());
        static::assertTrue(empty($ValueFrom));
    }

    /**
     * @test
     * @testdox Request a value that does not exist in a parsed response (Receive an exception)
     * @throws Exception
     */
    function getParsedSubValueFail()
    {
        $this->pemDefault();
        $this->urlGet("ssl&bool&o=json&method=get");
        $ExpectFailure = false;
        try {
            $this->CURL->getValue(array('nesting', 'subarrfail'));
        } catch (\Exception $parseException) {
            $ExpectFailure = true;
        }
        static::assertTrue($ExpectFailure);
    }

    /**
     * @test
     * @testdox Test if a web request has a valid body
     * @throws Exception
     */
    function getValidBody()
    {
        $this->pemDefault();
        $container = $this->simpleGet();
        $testBody = $this->getBody($container);
        static::assertTrue(!empty($testBody));
    }

    /**
     * @param $container
     * @return null
     */
    private function getBody($container)
    {
        if (is_object($container) && method_exists($container, 'getBody')) {
            return $container->getBody();
        }

        return $this->CURL->getBody();
    }

    /**
     * @test
     * @testdox Receive a standard 200 code
     * @throws Exception
     */
    function getSimple200()
    {
        $this->pemDefault();
        $this->simpleGet();
        static::assertTrue($this->CURL->getCode() == 200);
    }

    /**
     * @test
     * @testdox Test SSL based web request
     * @throws Exception
     */
    function getSslUrl()
    {
        $this->pemDefault();
        $container = $this->urlGet("ssl&bool", "https");
        $testBody = $this->getBody($container);
        static::assertTrue($this->getBody($container) && !empty($testBody));
    }

    /**
     * @test
     * @testdox Get exception on self signed certifications (we get error code 60)
     * @throws Exception
     */
    function getSslSelfSignedException()
    {
        $this->pemDefault();
        try {
            $this->CURL->doGet(\TESTURLS::getUrlSelfSigned());
        } catch (\Exception $e) {
            // CURLE_PEER_FAILED_VERIFICATION = 51
            // CURLE_SSL_CACERT = 60
            /** @noinspection PhpDeprecationInspection */
            static::assertTrue($e->getCode() == 60 || $e->getCode() == 51 || $e->getCode() == 500 || $e->getCode() === NETCURL_EXCEPTIONS::NETCURL_SETSSLVERIFY_UNVERIFIED_NOT_SET,
                $e->getCode());
        }
    }

    /**
     * @test
     * @testdox Get exception on mismatching certificates (host != certifcate host)
     * @throws Exception
     */
    function sslMismatching()
    {
        $this->pemDefault();
        try {
            $this->CURL->doGet(\TESTURLS::getUrlSelfSigned());
        } catch (\Exception $e) {
            // CURLE_PEER_FAILED_VERIFICATION = 51
            // CURLE_SSL_CACERT = 60
            /** @noinspection PhpDeprecationInspection */
            static::assertTrue($e->getCode() == 60 || $e->getCode() == 51 || $e->getCode() == 500 || $e->getCode() === NETCURL_EXCEPTIONS::NETCURL_SETSSLVERIFY_UNVERIFIED_NOT_SET);
        }
    }

    /**
     * @test
     */
    function sslSelfSignedIgnore()
    {
        try {
            $this->CURL->setSslStrictFallback(true);
            $this->CURL->setSslVerify(true, true);
            $container = $this->CURL->getParsed($this->CURL->doGet(\TESTURLS::getUrlSelfSigned() . "/tests/tornevall_network/index.php?o=json&bool"));
            if (is_object($container)) {
                static::assertTrue(isset($container->methods));
            }
        } catch (\Exception $e) {
            static::markTestSkipped("Got exception " . $e->getCode() . ": " . $e->getMessage());
        }
    }

    /**
     * @test
     * @testdox Test that initially allows unverified ssl certificates should make netcurl to first call the url in a
     *          correct way and then, if this fails, make a quite risky failover into unverified mode - silently.
     * @throws Exception
     */
    function sslSelfSignedUnverifyOnRun()
    {
        $this->pemDefault();
        try {
            $this->CURL->setSslVerify(false);
            $container = $this->CURL->getParsed($this->CURL->doGet(\TESTURLS::getUrlSelfSigned() . "/tests/tornevall_network/index.php?o=json&bool"));
            // The hasErrors function should return at least one error here
            if (is_object($container) && !$this->CURL->hasErrors()) {
                static::assertTrue(isset($container->methods));
            }
        } catch (\Exception $e) {
            static::markTestSkipped("Got exception " . $e->getCode() . ": " . $e->getMessage());
        }
    }

    /**
     * @test
     * @testdox Test parsed json response
     * @throws Exception
     */
    function getJson()
    {
        $this->pemDefault();
        $this->urlGet("ssl&bool&o=json&method=get");
        static::assertTrue(is_object($this->CURL->getParsed()->methods->_GET));
    }

    /**
     * @test
     * @testdox Check if we can parse a serialized response
     * @throws Exception
     */
    function getSerialize()
    {
        $this->pemDefault();
        $container = $this->urlGet("ssl&bool&o=serialize&method=get");
        $parsed = $this->CURL->getParsed($container);
        static::assertTrue(is_array($parsed['methods']['_GET']));
    }

    /**
     * @test
     * @testdox Test if XML/Serializer are parsed correctly
     * @throws Exception
     */
    function getXmlSerializer()
    {
        if (!class_exists('XML_Serializer')) {
            static::markTestSkipped('XML_Serializer test can not run without XML_Serializer');

            return;
        }
        $this->pemDefault();
        // XML_Serializer
        $container = $this->getParsed($this->urlGet("ssl&bool&o=xml&method=get"));
        static::assertTrue(isset($container->using) && is_object($container->using) && $container->using['0'] == "XML/Serializer");
    }

    /**
     * @param $container
     * @return null
     */
    private function getParsed($container)
    {
        if ($this->hasBody($container)) {
            if (is_object($container) && method_exists($container, 'getParsed')) {
                return $container->getParsed();
            }

            return $container['parsed'];
        }

        return null;
    }

    /**
     * @test
     * @testdox Test if SimpleXml are parsed correctly
     * @throws Exception
     */
    function getSimpleXml()
    {
        $this->pemDefault();
        // SimpleXMLElement
        $container = $this->getParsed($this->urlGet("ssl&bool&o=xml&method=get&using=SimpleXMLElement"));
        static::assertTrue(isset($container->using) && is_object($container->using) && $container->using == "SimpleXMLElement");
    }

    /**
     * @test
     * @testdox Test if a html response are converted to a proper array
     * @throws Exception
     */
    function getSimpleDom()
    {
        $this->pemDefault();
        $this->CURL->setDomContentParser(false);
        // setParseHtml is no longer necessary
        //$this->CURL->setParseHtml( true );
        $container = null;
        try {
            $container = $this->getParsed($this->urlGet("ssl&bool&o=xml&method=get&using=SimpleXMLElement", null,
                "simple.html"));
        } catch (\Exception $e) {

        }
        // ByNodes, ByClosestTag, ById
        static::assertTrue(isset($container['ById']) && count($container['ById']) > 0);
    }

    /**
     * @test
     * @throws Exception
     */
    function getSimpleDomChain()
    {

        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            static::markTestSkipped('Chaining PHP is not available in PHP version under 5.4 (This is ' . PHP_VERSION . ')');

            return;
        }

        $this->CURL->setDomContentParser(false);

        /** @var MODULE_CURL $getRequest */
        $getRequest = $this->urlGet("ssl&bool&o=xml&method=get&using=SimpleXMLElement", null, "simple.html");
        if (method_exists($getRequest, 'getParsed')) {
            $parsed = $getRequest->getParsed();
            $dom = $getRequest->getDomById();
        } else {
            static::markTestSkipped("$getRequest->getParsed() does not exist (PHP " . PHP_VERSION . ")");

            return;
        }
        static::assertTrue(isset($parsed['ByNodes']) && isset($dom['html']));
    }

    /**
     * @test
     * @testdox SSL Certificates at custom location. Expected Result: Successful lookup with verified peer
     * @throws Exception
     */
    function sslCertLocation()
    {
        $successfulVerification = false;
        try {
            $this->CURL->setSslPemLocations(array(__DIR__ . "/ca-certificates.crt"));
            $this->getParsed($this->urlGet("ssl&bool&o=json", "https"));
            $successfulVerification = true;
        } catch (\Exception $e) {
        }
        static::assertTrue($successfulVerification);
    }

    /**
     * @test
     * @throws Exception
     */
    function setInternalPemLocation()
    {
        try {
            static::assertTrue($this->CURL->setSslPemLocations(array(__DIR__ . "/ca-certificates.crt")));
        } catch (\Exception $e) {
        }
    }

    /**
     * @test
     * @throws Exception
     */
    function setInternalPemLocationBadFormat()
    {
        try {
            $this->CURL->setSslPemLocations(array(__DIR__ . "/"));
        } catch (\Exception $e) {
            static::assertTrue($e->getCode() == NETCURL_EXCEPTIONS::NETCURL_PEMLOCATIONDATA_FORMAT_ERROR);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    function unExistentCertificateBundle()
    {
        $this->CURL->setFlag('OVERRIDE_CERTIFICATE_BUNDLE', '/failCertBundle');
        $this->CURL->setTrustedSslBundles(true);
        try {
            $this->getParsed($this->urlGet("ssl&bool&o=json", "https"));
        } catch (\Exception $e) {
            $assertThis = false;
            $errorCode = $e->getCode();
            // CURLE_SSL_CACERT_BADFILE
            if (intval($errorCode) == 77) {
                $assertThis = true;
            }
            static::assertTrue($assertThis, $e->getMessage() . " (" . $e->getCode() . ")");
        }
    }

    /***************
     *  SSL TESTS  *
     **************/

    /**
     * @test
     * @testdox SSL Certificates are missing and certificate location is mismatching. Expected Result: Failing the url
     *          call
     */
    function failingSsl()
    {
        $successfulVerification = true;
        try {
            $this->CURL->setSslVerify(true);
            $this->CURL->setSslStrictFallback(false);
            $this->CURL->doGet(\TESTURLS::getUrlMismatching());
        } catch (\Exception $e) {
            $successfulVerification = false;
        }
        static::assertFalse($successfulVerification);
    }

    /**
     * @test
     * @testdox Test the customized ip address
     * @throws Exception
     */
    function customIpAddrSimple()
    {
        $this->pemDefault();
        $returnedExecResponse = $this->getIpListByIpRoute();
        // Probably a bad shortcut for some systems, but it works for us in tests
        if (!empty($returnedExecResponse) && is_array($returnedExecResponse)) {
            $NETWORK = new MODULE_NETWORK();
            $ipArray = array();
            foreach ($returnedExecResponse as $ip) {
                // Making sure this test is running safely with non locals only
                if (!in_array($ip, $ipArray) && $NETWORK->getArpaFromAddr($ip, true) > 0 && !preg_match("/^10\./",
                        $ip) && !preg_match("/^172\./", $ip) && !preg_match("/^192\./", $ip)) {
                    $ipArray[] = $ip;
                }
            }
            $this->CURL->IpAddr = $ipArray;
            $this->CURL->doGet(\TESTURLS::getUrlSimpleJson());
            static::assertNotEmpty($this->CURL->getParsed()->ip);
        }
    }

    /**
     * iproute2 ifconfig
     * @return mixed
     */
    private function getIpListByIpRoute()
    {
        // Don't fetch 127.0.0.1
        exec("ip addr|grep \"inet \"|sed 's/\// /'|awk '{print $2}'|grep -v ^127", $returnedExecResponse);

        return $returnedExecResponse;
    }

    /**
     * @test
     * @testdox Test custom ip address setup (if more than one ip is set on the interface)
     * @throws Exception
     */
    function customIpAddrAllString()
    {
        $this->pemDefault();
        $ipArray = array();
        $responses = array();
        $returnedExecResponse = $this->getIpListByIpRoute();
        if (!empty($returnedExecResponse) && is_array($returnedExecResponse)) {
            $NETWORK = new MODULE_NETWORK();
            $lastValidIp = null;
            foreach ($returnedExecResponse as $ip) {
                // Making sure this test is running safely with non locals only
                if (!in_array($ip, $ipArray) && $NETWORK->getArpaFromAddr($ip, true) > 0 && !preg_match("/^10\./",
                        $ip) && !preg_match("/^172\./", $ip) && !preg_match("/^192\./", $ip)) {
                    $ipArray[] = $ip;
                }
            }
            if (is_array($ipArray) && count($ipArray) > 1) {
                foreach ($ipArray as $ip) {
                    $this->CURL->IpAddr = $ip;
                    try {
                        $this->CURL->doGet(\TESTURLS::getUrlSimpleJson());
                    } catch (\Exception $e) {

                    }
                    if (isset($this->CURL->getParsed()->ip) && $this->NETWORK->getArpaFromAddr($this->CURL->getParsed()->ip,
                            true) > 0) {
                        $responses[$ip] = $this->CURL->getParsed()->ip;
                    }
                }
            } else {
                static::markTestSkipped("ip address array is too short to be tested (" . print_r($ipArray, true) . ")");
            }
        }
        static::assertTrue(count($responses) === count($ipArray));
    }

    /**
     * @test
     * @testdox Run in default mode, when follows are enabled
     * @throws Exception
     */
    function followRedirectEnabled()
    {
        $this->pemDefault();
        $redirectResponse = $this->CURL->doGet("http://developer.tornevall.net/tests/tornevall_network/redirect.php?run");
        $redirectedUrls = $this->CURL->getRedirectedUrls();
        static::assertTrue(intval($this->CURL->getCode($redirectResponse)) >= 300 && intval($this->CURL->getCode($redirectResponse)) <= 350 && count($redirectedUrls));
    }

    /**
     * @test
     * @testdox Run with redirect follows disabled
     * @throws Exception
     */
    function followRedirectDisabled()
    {
        $this->pemDefault();
        $this->CURL->setEnforceFollowLocation(false);
        $redirectResponse = $this->CURL->doGet("http://developer.tornevall.net/tests/tornevall_network/redirect.php?run");
        $redirectedUrls = $this->CURL->getRedirectedUrls();
        static::assertTrue($this->CURL->getCode($redirectResponse) >= 300 && $this->CURL->getCode($redirectResponse) <= 350 && !preg_match("/rerun/i",
                $this->CURL->getBody($redirectResponse)) && count($redirectedUrls));
    }

    /**
     * @test
     * @testdox Activating the flag FOLLOWLOCATION_INTERNAL will make NetCurl make its own follow recursion
     * @throws Exception
     */
    function followRedirectDisabledFlagEnabled()
    {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            static::markTestSkipped('Internal URL following may cause problems in PHP versions lower than 5.4 (' . PHP_VERSION . ')');

            return;
        }
        $this->pemDefault();
        $this->CURL->setFlag('FOLLOWLOCATION_INTERNAL');
        $this->CURL->setEnforceFollowLocation(false);
        /** @var MODULE_CURL $redirectResponse */
        $redirectResponse = $this->CURL->doGet("http://developer.tornevall.net/tests/tornevall_network/redirect.php?run");
        $redirectedUrls = $this->CURL->getRedirectedUrls();
        $responseCode = $this->CURL->getCode($redirectResponse);
        $curlBody = $this->CURL->getBody();
        static::assertTrue(intval($responseCode) >= 200 && intval($responseCode) <= 300 && count($redirectedUrls) && preg_match("/rerun/i",
                $curlBody));
    }

    /**
     * @test
     * @throws Exception
     */
    function followRedirectManualDisable()
    {
        $this->pemDefault();
        $this->CURL->setEnforceFollowLocation(false);
        $redirectResponse = $this->CURL->doGet("http://developer.tornevall.net/tests/tornevall_network/redirect.php?run");
        $redirectedUrls = $this->CURL->getRedirectedUrls();
        static::assertTrue($this->CURL->getCode($redirectResponse) >= 300 && $this->CURL->getCode($redirectResponse) <= 350 && !preg_match("/rerun/i",
                $this->CURL->getBody($redirectResponse)) && count($redirectedUrls));
    }

    /**
     * @test
     * @testdox Tests the overriding function setEnforceFollowLocation and the setCurlOpt-overrider. The expected
     *          result is to have setEnforceFollowLocation to be top prioritized over setCurlOpt here.
     * @throws Exception
     */
    function followRedirectManualEnableWithSetCurlOptEnforcingToFalse()
    {
        $this->pemDefault();
        $this->CURL->setEnforceFollowLocation(true);
        $this->CURL->setCurlOpt(CURLOPT_FOLLOWLOCATION,
            false);  // This is the doer since there are internal protection against the above enforcer
        $redirectResponse = $this->CURL->doGet("http://developer.tornevall.net/tests/tornevall_network/redirect.php?run");
        $redirectedUrls = $this->CURL->getRedirectedUrls();
        static::assertTrue($this->CURL->getCode($redirectResponse) >= 300 && $this->CURL->getCode($redirectResponse) <= 350 && count($redirectedUrls));
    }

    /**
     * @test
     * @testdox Test SoapClient by making a standard doGet()
     * @throws Exception
     */
    function wsdlSoapClient()
    {
        $assertThis = true;
        try {
            $this->CURL->setUserAgent(" +UnitSoapAgent");
            $this->CURL->doGet("http://" . \TESTURLS::getUrlSoap());
        } catch (\Exception $e) {
            $assertThis = false;
            switch ($e->getCode()) {
                case CURLE_FAILED_INIT:
                    static::markTestSkipped("Exception CURLE_FAILED_INIT: Something seem to be down on this call. This test can not run");
                    break;
                default:
                    static::markTestSkipped("Exception code " . $e->getCode() . ": " . $e->getMessage());
                    break;
            }

        }
        static::assertTrue($assertThis);
    }

    /**
     * @test
     * @testdox Test Soap by internal controllers
     * @throws Exception
     */
    function hasSoap()
    {
        static::assertTrue($this->CURL->hasSoap());
    }

    /**
     * @test
     * @throws Exception
     */
    function throwableHttpCodes()
    {
        $this->pemDefault();
        $this->CURL->setThrowableHttpCodes();
        try {
            $this->CURL->doGet("https://developer.tornevall.net/tests/tornevall_network/http.php?code=503");
        } catch (\Exception $e) {
            static::assertTrue($e->getCode() == 503);

            return;
        }
    }

    /**
     * @test
     * @throws Exception
     */
    function failUrl()
    {
        try {
            $this->CURL->doGet("http://" . sha1(microtime(true)));
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            static::assertTrue((preg_match("/maximum tries/", $errorMessage) ? true : false));
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setCurlOpt()
    {
        $oldCurl = $this->CURL->getCurlOpt();
        $this->CURL->setCurlOpt(array(CURLOPT_CONNECTTIMEOUT => 10));
        $newCurl = $this->CURL->getCurlOpt();
        static::assertTrue($oldCurl[CURLOPT_CONNECTTIMEOUT] != $newCurl[CURLOPT_CONNECTTIMEOUT]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCurlOpt()
    {
        $newCurl = $this->CURL->getCurlOptByKeys();
        static::assertTrue(isset($newCurl['CURLOPT_CONNECTTIMEOUT']));
    }

    /**
     * @test
     * @throws Exception
     */
    function unsetFlag()
    {
        $first = $this->CURL->setFlag("CHAIN", true);
        $this->CURL->unsetFlag("CHAIN");
        $second = $this->CURL->hasFlag("CHAIN");
        static::assertTrue($first && !$second);
    }

    /**
     * @test
     * @throws Exception
     */
    function chainGet()
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            $this->CURL->setFlag("CHAIN");
            static::assertTrue(method_exists($this->CURL->doGet(\TESTURLS::getUrlSimpleJson()), 'getParsedResponse'));
            $this->CURL->unsetFlag("CHAIN");
        } else {
            static::markTestSkipped('Chaining PHP is not available in PHP version under 5.4 (This is ' . PHP_VERSION . ')');
        }
    }

    /**
     * @test
     * @throws Exception
     */
    function tlagEmptyKey()
    {
        try {
            $this->CURL->setFlag();
        } catch (\Exception $setFlagException) {
            static::assertTrue($setFlagException->getCode() > 0);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    function chainByInit()
    {
        $Chainer = new MODULE_CURL(null, null, null, array("CHAIN"));
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            static::assertTrue(is_object($Chainer->doGet(\TESTURLS::getUrlSimpleJson())->getParsed()));
        } else {
            static::markTestSkipped("Chaining can't be tested from PHP " . PHP_VERSION);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    function chainGetFail()
    {
        $this->CURL->unsetFlag("CHAIN");
        static::assertFalse(method_exists($this->CURL->doGet(\TESTURLS::getUrlSimpleJson()), 'getParsedResponse'));
    }

    /**
     * @test
     * @throws Exception
     */
    function getGitIsTooOld()
    {
        // curl module for netcurl will probably always be lower than the netcurl-version, so this is a good way of testing
        static::assertTrue($this->NETWORK->getVersionTooOld("1.0.0", "https://" . $this->bitBucketUrl));
    }

    /**
     * @test
     * @throws Exception
     */
    function getGitCurrentOrNewer()
    {
        // curl module for netcurl will probably always be lower than the netcurl-version, so this is a good way of testing
        $tags = $this->NETWORK->getGitTagsByUrl("https://" . $this->bitBucketUrl);
        $lastTag = array_pop($tags);
        $lastBeforeLast = array_pop($tags);
        // This should return false, since the current is not too old
        $isCurrent = $this->NETWORK->getVersionTooOld($lastTag, "https://" . $this->bitBucketUrl);
        // This should return true, since the last version after the current is too old
        $isLastBeforeCurrent = $this->NETWORK->getVersionTooOld($lastBeforeLast, "https://" . $this->bitBucketUrl);
        static::assertTrue($isCurrent === false || $isLastBeforeCurrent === true);
    }

    /**
     * @test
     * @throws Exception
     */
    function timeoutChecking()
    {
        $def = $this->CURL->getTimeout();
        $this->CURL->setTimeout(6);
        $new = $this->CURL->getTimeout();
        static::assertTrue($def['connecttimeout'] == 300 && $def['requesttimeout'] == 0 && $new['connecttimeout'] == 3 && $new['requesttimeout'] == 6);
    }

    /**
     * @test
     * @throws Exception
     */
    function internalException()
    {
        static::assertTrue($this->NETWORK->getExceptionCode('NETCURL_EXCEPTION_IT_WORKS') == 1);
    }

    /**
     * @test
     */
    function internalExceptionNoExists()
    {
        static::assertTrue($this->NETWORK->getExceptionCode('NETCURL_EXCEPTION_IT_DOESNT_WORK') == 500);
    }

    /**
     * @test
     * @throws Exception
     */
    function driverControlList()
    {
        $driverList = array();
        try {
            $driverList = $this->DRIVER->getSystemWideDrivers();
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        static::assertTrue(count($driverList) > 0);
    }

    /**
     * @test
     */
    function driverControlNoList()
    {
        $driverList = false;
        try {
            $driverList = $this->DRIVER->getSystemWideDrivers();
        } catch (\Exception $e) {
            echo $e->getMessage() . "\n";
        }
        static::assertTrue(is_array($driverList));
    }

    /**
     * @test
     */
    public function getCurrentProtocol()
    {
        $oneOfThenm = MODULE_NETWORK::getCurrentServerProtocol(true);
        static::assertTrue($oneOfThenm == "http" || $oneOfThenm == "https");
    }

    /**
     * @test
     * @throws Exception
     */
    function getSupportedDrivers()
    {
        static::assertTrue(count($this->DRIVER->getSystemWideDrivers()) > 0);
    }

    /**
     * @test
     * @throws Exception
     */
    function setAutoDriver()
    {
        $driverset = $this->CURL->setDriverAuto();
        static::assertTrue($driverset > 0);
    }

    /**
     * @test
     * @throws Exception
     */
    function getJsonByConstructor()
    {
        $quickCurl = new MODULE_CURL(\TESTURLS::getUrlSimpleJson());
        $identifierByJson = $quickCurl->getParsed();
        static::assertTrue(isset($identifierByJson->ip));
    }

    /**
     * @test
     * @throws Exception
     */
    function extractDomainIsGetUrlDomain()
    {
        static::assertCount(3, $this->NETWORK->getUrlDomain("https://www.aftonbladet.se/uri/is/here"));
    }

    /**
     * @test
     * @testdox Safe mode and basepath cechking without paramters - in our environment, open_basedir is empty and
     *          safe_mode is off
     */
    function getSafePermissionFull()
    {
        static::assertFalse($this->CURL->getIsSecure());
    }

    /**
     * @test
     * @testdox Open_basedir is secured and (at least in our environment) safe_mode is disabled
     */
    function getSafePermissionFullMocked()
    {
        ini_set('open_basedir', "/");
        static::assertTrue($this->CURL->getIsSecure());
        // Reset the setting as it is affecting other tests
        ini_set('open_basedir', "");
    }

    /**
     * @test
     * @testdox open_basedir is safe and safe_mode-checking will be skipped
     */
    function getSafePermissionFullMockedNoSafeMode()
    {
        ini_set('open_basedir', "/");
        static::assertTrue($this->CURL->getIsSecure(false));
        // Reset the setting as it is affecting other tests
        ini_set('open_basedir', "");
    }

    /**
     * @test
     * @testdox open_basedir is unsafe and safe_mode is mocked-active
     */
    function getSafePermissionFullMockedSafeMode()
    {
        ini_set('open_basedir', "");
        static::assertTrue($this->CURL->getIsSecure(true, true));
    }

    /**
     * @test
     * @testdox LIB-212
     */
    function hasSsl()
    {
        static::assertTrue($this->CURL->hasSsl());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getParsedDom()
    {
        /** @var MODULE_CURL $content */
        $this->CURL->setDomContentParser(false);
        $phpAntiChain = $this->urlGet("ssl&bool&o=xml&method=get&using=SimpleXMLElement", null,
            "simple.html");  // PHP 5.3 compliant
        if (method_exists($phpAntiChain, 'getDomById')) {
            $content = $phpAntiChain->getDomById();
            static::assertTrue(isset($content['divElement']));
        } else {
            static::markTestSkipped("getDomById is unreachable (PHP v" . PHP_VERSION . ")");
        }
    }

    /**
     * @test
     * @throws Exception
     */
    function responseTypeHttpObject()
    {
        $this->CURL->setResponseType(NETCURL_RESPONSETYPE::RESPONSETYPE_OBJECT);
        /** @var NETCURL_HTTP_OBJECT $request */
        $request = $this->CURL->doGet(\TESTURLS::getUrlSimpleJson());
        $parsed = $request->getParsed();
        static::assertTrue(get_class($request) == 'TorneLIB\NETCURL_HTTP_OBJECT' && is_object($parsed) && isset($parsed->ip));
    }

    /**
     * @test
     * @testdox Request urls with NETCURL_HTTP_OBJECT
     * @throws Exception
     */
    function responseTypeHttpObjectChain()
    {
        $this->CURL->setResponseType(NETCURL_RESPONSETYPE::RESPONSETYPE_OBJECT);
        /** @var NETCURL_HTTP_OBJECT $request */
        $request = $this->CURL->doGet(\TESTURLS::getUrlSimpleJson())->getParsed();
        static::assertTrue(is_object($request) && isset($request->ip));
    }

    /**
     * @test
     * @testdox Testing that switching between driverse (SOAP) works - when SOAP is not used, NetCURL should switch
     *          back to the regular driver
     * @throws Exception
     */
    function multiCallsSwitchingBetweenRegularAndSoap()
    {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            static::markTestSkipped("Multicall switching test is not compliant with PHP 5.3 - however, the function switching itself is supported");

            return;
        }

        $driversUsed = array(
            '1' => 0,
            '2' => 0
        );

        $this->disableSslVerifyByPhpVersions(true);
        try {
            $this->CURL->setAuthentication('atest', 'atest');
            $this->CURL->doGet("http://identifier.tornevall.net/?json")->getParsed();
            $driversUsed[$this->CURL->getDriverById()]++;
            /** @noinspection PhpUndefinedMethodInspection */
            $this->CURL->doGet('https://test.resurs.com/ecommerce-test/ws/V4/SimplifiedShopFlowService?wsdl')->getPaymentMethods();
            $driversUsed[$this->CURL->getDriverById()]++;
            $this->CURL->doGet("http://identifier.tornevall.net/?json")->getParsed();
            $driversUsed[$this->CURL->getDriverById()]++;
            /** @noinspection PhpUndefinedMethodInspection */
            $this->CURL->doGet('https://test.resurs.com/ecommerce-test/ws/V4/SimplifiedShopFlowService?wsdl')->getPaymentMethods();
            $driversUsed[$this->CURL->getDriverById()]++;
            $this->CURL->doGet("http://identifier.tornevall.net/?json")->getParsed();
            $driversUsed[$this->CURL->getDriverById()]++;

            static::assertTrue($driversUsed[1] == 3 && $driversUsed[2] == 2 ? true : false);
        } catch (\Exception $e) {
            if ($e->getCode() < 3) {
                static::markTestSkipped('Getting exception codes below 3 here, might indicate that your cacerts is not installed properly or the connection to the server is not responding');

                return;
            } elseif ($e->getCode() >= 500) {
                static::markTestSkipped("Got errors (" . $e->getCode() . ") on URL call, can't complete request: " . $e->getMessage());
            }
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function disableSslVerifyByPhpVersions($always = false)
    {
        if (version_compare(PHP_VERSION, '5.5.0', '<=')) {
            $this->CURL->setSslVerify(false, false);
        } elseif ($always) {
            $this->CURL->setSslVerify(false, false);
        }
    }

    /**
     * @test
     * @testdox Make sure that simplified responses returns proper data immediately on call
     * @throws Exception
     */
    function setSimplifiedResponse()
    {
        $curlobject = $this->CURL->doGet("http://identifier.tornevall.net/?json");
        $this->CURL->setSimplifiedResponse();
        $responseobject = $this->CURL->doGet("http://identifier.tornevall.net/?json");
        $callWithBody = $this->CURL->doGet("https://developer.tornevall.net/tests/tornevall_network/simple.html");

        // If we still want to see "oldstyle"-data, we can always call the core object directly
        $urlGetCode = $this->CURL->getCode();

        if (is_array($curlobject)) {
            // PHP 5.3 is unchained and gives different responses.
            static::assertTrue(is_array($curlobject) && is_object($responseobject) && isset($responseobject->ip) && is_string($callWithBody) && $urlGetCode == "200");

            return;
        }
        static::assertTrue(get_class($curlobject) == 'TorneLIB\MODULE_CURL' && is_object($responseobject) && isset($responseobject->ip) && is_string($callWithBody) && $urlGetCode == "200");
    }

    /**
     * @test
     * @testdox Another way to extract stuff on
     * @throws Exception
     */
    function soapIoParse()
    {
        if (version_compare(PHP_VERSION, '5.4.0', '<=')) {
            static::markTestSkipped("Test might fail in PHP 5.3 and lower, due to incompatibility");

            return;
        }
        if (!class_exists('TorneLIB\MODULE_IO')) {
            static::markTestSkipped("MODULE_IO is missing, this test is skipped");

            return;
        }
        try {
            $this->disableSslVerifyByPhpVersions(true);
            $this->CURL->setAuthentication('atest', 'atest');
            $php53UnChainified = $this->CURL->doGet('https://test.resurs.com/ecommerce-test/ws/V4/SimplifiedShopFlowService?wsdl');
            /** @noinspection PhpUndefinedMethodInspection */
            $php53UnChainified->getPaymentMethods();
            $IO = new MODULE_IO();
            // Chaining this might segfaultify something
            $php53Bodified = $this->CURL->getBody();
            $XML = $IO->getFromXml($php53Bodified, true);
            $id = (isset($XML[0]) && isset($XML[0]->id) ? $XML[0]->id : null);
            static::assertTrue(strlen($id) > 0 ? true : false);
        } catch (\Exception $e) {
            if ($e->getCode() < 3) {
                static::markTestSkipped('Getting exception codes below 3 here, might indicate that your cacerts is not installed properly or the connection to the server is not responding');

                return;
            } elseif ($e->getCode() >= 500) {
                static::markTestSkipped("Got errors (" . $e->getCode() . ") on URL call, can't complete request: " . $e->getMessage());
            }
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    function setTimeout()
    {
        $this->CURL = new MODULE_CURL();
        $this->CURL->setTimeout(1);
        $startTime = time();
        try {
            print_R($this->CURL->doGet("imap://failing:account@imap.tornevall.net")->getHeader());
        } catch (\Exception $e) {
        }
        $timeWasted = time() - $startTime;
        static::assertLessThan(5, $timeWasted);
    }

    /**
     * @test
     * @testdox Packagist content-type test
     */
    function packagistContentTypeFailures()
    {
        $packagistUsername = "";
        $packagistToken = "";
        $repoUrl = '';

        if (empty($packagistUsername) || empty($packagistToken) || empty($repoUrl)) {
            static::markTestSkipped("This test is written to check packagist content-type errors, so you need to set up a username, token and git repo url above to make it work properly");
        }

        $packagistUrl = 'https://packagist.org/api/bitbucket?username=' . $packagistUsername . '&apiToken=' . $packagistToken;
        $postData = json_decode('{"repository":{"url":"' . $repoUrl . '"}}', true);
        $initCurl = new MODULE_CURL();
        $initialError = 0;
        try {
            $initCurl->doPost($packagistUrl, $postData, NETCURL_POST_DATATYPES::DATATYPE_JSON);
        } catch (\Exception $e) {
            $initialError = $e->getCode();
        }
        $initCurl->setContentType("application/json");
        try {
            $initCurl->doPost($packagistUrl, $postData, NETCURL_POST_DATATYPES::DATATYPE_JSON)->getBody();
            static::assertTrue($initCurl->getParsed()->status == "success" && $initialError == 406);
        } catch (\Exception $e) {
        }
    }

    /**
     * @param bool $setActive
     */
    private function setDebug($setActive = false)
    {
        if (!$setActive) {
            error_reporting(E_ALL);
        } else {
            error_reporting($this->StartErrorReporting);
        }
    }

    /**
     * @param array $parameters
     * @param string $protocol
     * @param string $indexFile
     * @return array|null|string|MODULE_CURL|NETCURL_HTTP_OBJECT
     * @throws Exception
     */
    private function urlPost($parameters = array(), $protocol = "http", $indexFile = 'index.php')
    {
        $theUrl = $this->getProtocol($protocol) . "://" . \TESTURLS::getUrlTests() . $indexFile;

        return $this->CURL->doPost($theUrl, $parameters);
    }

}
