<?php

/** @noinspection PhpUndefinedMethodInspection */

require_once(__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use TorneLIB\Exception\Constants;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Model\Type\DataType;
use TorneLIB\Model\Type\RequestMethod;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Network\NetWrapper;
use TorneLIB\Module\Network\Wrappers\CurlWrapper;
use TorneLIB\Module\Network\Wrappers\SimpleStreamWrapper;

class simpleStreamWrapperTest extends TestCase
{
    /**
     * @since 6.1.0
     * @noinspection SpellCheckingInspection
     */
    public function testGetStreamWrapper()
    {
        // Base streamWrapper (file_get_contents, fopen, etc) is only allowed if allow_url_fopen is available.
        $stream = (new SimpleStreamWrapper());

        /** @noinspection PhpUnitTestsInspection */
        static::assertTrue(is_object($stream));
    }

    /**
     * @test
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function testGetBasicUrl()
    {
        $stream = (new NetWrapper())->setConfig((new WrapperConfig())->setUserAgent('SimpleStreamWrapper'));
        $response = $stream->request('https://ipv4.fraudbl.org/');

        //$body = $response->getBody();
        $parsed = $response->getParsed();

        static::assertTrue(isset($parsed->ip, $parsed->HTTP_USER_AGENT) && $parsed->HTTP_USER_AGENT === 'SimpleStreamWrapper');
    }

    /**
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function testGetBasicPost()
    {
        $stream = (new SimpleStreamWrapper())->setConfig((new WrapperConfig())->setUserAgent('SimpleStreamWrapper'))->setAuthentication('testUser', 'hasNoEffectHere');
        $response = $stream->request('https://ipv4.fraudbl.org/', ['postData' => ['var1' => 'val1'],], RequestMethod::POST)->getParsed();

        static::assertTrue(isset($response->PARAMS_POST, $response->PARAMS_POST->postData));
    }

    /**
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function testGetBasicJson()
    {
        $stream = (new SimpleStreamWrapper())->setConfig((new WrapperConfig())->setUserAgent('SimpleStreamWrapper'))->setAuthentication('testUser', 'hasNoEffectHere');
        $response = $stream->request('https://ipv4.fraudbl.org/', ['postData' => ['var1' => 'val1'],], RequestMethod::POST, DataType::JSON)->getParsed();

        static::assertTrue(isset($response->input) && strlen($response->input) > 4);
    }

    /**
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function testGetBasicXml()
    {
        $stream = (new SimpleStreamWrapper())->setConfig((new WrapperConfig())->setUserAgent('SimpleStreamWrapper'))->setAuthentication('testUser', 'hasNoEffectHere');
        $response = $stream->request('https://ipv4.fraudbl.org/', ['postData' => ['var1' => 'val1'],], RequestMethod::POST, DataType::XML)->getParsed();

        static::assertTrue(isset($response->input) && strlen($response->input) > 4);
    }

    /**
     * To get access to simplestreamwrapper, set below:
     * allow_url_fopen = Off
     * disable_functions = curl_exec,curl_init,openssl_encrypt
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function testGetBasicNetwrapper()
    {
        $stream = (new NetWrapper());
        $stream->setIdentifiers(true);
        $stream->request('https://ipv4.fraudbl.org/')->getParsed();
        $currentWrapper = $stream->getCurrentWrapperClass(true);

        static::assertNotEmpty($currentWrapper);
    }

    /**
     * @throws ExceptionHandler
     * @since 6.1.0
     */
    public function testGetBasicNetwrapperClient()
    {
        $stream = (new NetWrapper());
        $stream->setIdentifiers(true, true); // spoofable advanced
        /** @noinspection PhpUndefinedMethodInspection */
        $stream->setUserAgent('World Dominator');
        $stream->request('https://ipv4.fraudbl.org/')->getParsed();
        $content = $stream->getParsed();

        static::assertTrue(isset($content->HTTP_USER_AGENT) && (bool)preg_match('/world dominator/i', $content->HTTP_USER_AGENT));
    }

    /**
     * @throws ExceptionHandler
     * @since 6.1.2
     */
    public function testGetParsedResponse()
    {
        /** @noinspection DynamicInvocationViaScopeResolutionInspection */
        static::expectException(ExceptionHandler::class);

        $streamWrapperRequest = new SimpleStreamWrapper();
        $streamWrapperRequest->request('https://ipv4.fraudbl.org');
        $p = $streamWrapperRequest->getParsedResponse();
        /** @noinspection ForgottenDebugOutputInspection */
        static::assertTrue(isset($p->ip));
    }

    public function testStreamWrapperDefaultRealTimeout()
    {
        $streamWrapper = new SimpleStreamWrapper();
        $streamWrapper->setTimeout(15);
        $timeout = $streamWrapper->getTimeout();
        static::assertTrue(isset($timeout['CONNECT']) && (int)$timeout['CONNECT'] === 8);
    }

    public function testStreamWrapperShortTimeout()
    {
        $streamWrapper = new SimpleStreamWrapper();
        $streamWrapper->setTimeout(1);
        $timeout = $streamWrapper->getTimeout();
        static::assertTrue(isset($timeout['CONNECT']) && (int)$timeout['CONNECT'] === 1);
    }

    /**
     * @since 6.1.2
     */
    public function testGetContextExtractor()
    {
        $wrapperConfig = new WrapperConfig();
        // All compatible assertion.
        /** @noinspection PhpUnitTestsInspection */
        static::assertTrue(is_array($wrapperConfig->getContentFromStreamContext($wrapperConfig->getStreamContext())));
    }

    /**
     * @throws ExceptionHandler
     * @since 6.1.2
     */
    public function testSetStaticHeaders()
    {
        $wrapper = new SimpleStreamWrapper();
        $wrapper->setHeader('myHeaderIsStatic', true, true);
        $parsed = $wrapper->request('https://ipv4.fraudbl.org')->getParsed();

        $secondParseRequest = $wrapper->request('https://ipv4.fraudbl.org/?secondRequest=1');
        $secondParsed = $secondParseRequest->getParsed();

        static::assertTrue(isset($parsed->HTTP_MYHEADERISSTATIC, $secondParsed->HTTP_MYHEADERISSTATIC));
    }

    /**
     * @throws ExceptionHandler
     * @since 6.1.1
     */
    public function testStreamProxy()
    {
        if (!$this->canProxy()) {
            static::markTestSkipped('Can not perform proxy tests with this client. Skipped.');
            return;
        }

        $response = (new SimpleStreamWrapper())->setProxy('212.63.208.8:80')->request('http://identifier.tornevall.net/?inTheProxy')->getParsed();

        static::assertTrue(isset($response->ip) && ($response->ip === '212.63.208.8'));
    }

    /**
     * Required by streamProxy() to find out if proxy testing can be performed.
     * Do not remove.
     *
     * @return bool
     * @throws ExceptionHandler
     */
    private function canProxy()
    {
        $return = false;

        $ipList = ['212.63.208.', '10.1.1.',];

        $wrapperData = (new CurlWrapper())->setConfig((new WrapperConfig())->setUserAgent('ProxyTestAgent'))->request('https://ipv4.fraudbl.org')->getParsed();
        if (isset($wrapperData->ip)) {
            foreach ($ipList as $ip) {
                if (preg_match('/' . $ip . '/', $wrapperData->ip)) {
                    $return = true;
                    break;
                }
            }
        }

        return $return;
    }

    /**
     * Test used for both timeouts and refusals.
     */
    public function testStdRequest()
    {
        $streamWrapper = new SimpleStreamWrapper();
        try {
            //$streamWrapper->setTimeout(3);
            $streamWrapper->request('https://test.resurs.com');
        } catch (Exception $e) {
            if ($e->getCode() === Constants::LIB_NETCURL_TIMEOUT || $e->getCode() === Constants::LIB_NETCURL_CONNECTION_REFUSED) {
                static::assertTrue($e->getCode() === Constants::LIB_NETCURL_CONNECTION_REFUSED || $e->getCode() === Constants::LIB_NETCURL_TIMEOUT);
                return;
            }

            static::markTestSkipped('Exception is accepted due to the request. Skipped.');
            return;
        }

        // Ignore above if it works. Used for testing LIB_NETCURL_CONNECTION_REFUSED, but for curl.
        // For curl, we get this in a natural way.
        static::assertTrue(true);
    }

    public function testSelfSignedRequest()
    {
        // Using one of those urls to emulate SSL problems:
        // https://dev-ssl-self.tornevall.nu
        // https://dev-ssl-mismatch.tornevall.nu

        $wrapper = new SimpleStreamWrapper();
        $sslFail = false;
        try {
            $wrapper->request('https://dev-ssl-self.tornevall.nu');
        } catch (ExceptionHandler $e) {
            $sslFail = true;
        }

        $newSsl = $wrapper->getConfig()->getSsl()->setStrictVerification(false, false);
        $wrapper->setSsl($newSsl);
        try {
            $newRequest = $wrapper->request('https://dev-ssl-mismatch.tornevall.nu');
            static::assertTrue($newRequest instanceof SimpleStreamWrapper && $sslFail);
        } catch (Exception $e) {
            static::markTestSkipped($e->getMessage());
        }
    }
}
