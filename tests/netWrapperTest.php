<?php

/** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpUndefinedClassInspection */
require_once(__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use TorneLIB\Config\Flag;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Config\WrapperDriver;
use TorneLIB\Module\Network\NetWrapper;
use TorneLIB\Module\Network\Wrappers\CurlWrapper;

/** @noinspection PhpUndefinedMethodInspection */
Flag::setFlag('strict_resource', false);

/**
 * Class netcurlTest
 * Tests for entire netcurl package, via NetWrapper.
 */
class netWrapperTest extends TestCase
{
    /**
     * Test the primary wrapper controller.
     */
    public function testMajorWrapperControl()
    {
        $netWrap = new NetWrapper();
        $realWrap = WrapperDriver::getWrappers();
        $hasWrappers = count($netWrap->getWrappers()) > 0;
        $hasWrappersReal = count($realWrap) > 0;
        static::assertTrue($hasWrappers && $hasWrappersReal);
    }

    /**
     * @throws ExceptionHandler
     */
    public function testBasicGet()
    {
        $wrapper = (new NetWrapper())->setConfig($this->setTestAgent())->request(sprintf('https://ipv4.fraudbl.org/?func=%s', __FUNCTION__));

        $parsed = $wrapper->getParsed();

        if (isset($parsed->ip)) {
            static::assertTrue(filter_var($parsed->ip, FILTER_VALIDATE_IP) ? true : false);
        }
    }

    /**
     * @return WrapperConfig
     */
    private function setTestAgent()
    {
        return (new WrapperConfig())->setUserAgent(sprintf('netcurl-%s', NETCURL_VERSION));
    }

    /**
     * @throws ExceptionHandler
     */
    public function testExtremelyBasic()
    {
        $wrapper = new NetWrapper();
        $wrapper->request(sprintf('https://ipv4.fraudbl.org/'));
        $parsed = $wrapper->getParsed();
        static::assertNotEmpty(filter_var($parsed->ip, FILTER_VALIDATE_IP));
    }

    public function testExtremelyBasicOneLiner()
    {
        try {
            $parsed = (new NetWrapper())->request(sprintf('https://ipv4.fraudbl.org/'))->getParsed();
            static::assertNotEmpty(filter_var($parsed->ip, FILTER_VALIDATE_IP));
        } catch (Exception $e) {
            static::markTestSkipped(sprintf('Non critical exception in %s: %s (%s).', __FUNCTION__, $e->getMessage(), $e->getCode()));
        }
    }

    /**
     * @throws ExceptionHandler
     */
    public function testSigGet()
    {
        WrapperConfig::setSignature('Korven skriker.');
        $wrapper = (new NetWrapper())->request(sprintf('https://ipv4.fraudbl.org/?func=%s', __FUNCTION__));
        $parsed = $wrapper->getParsed();
        WrapperConfig::deleteSignature();

        static::assertSame($parsed->HTTP_USER_AGENT, 'Korven skriker.');
    }

    /**
     * @throws ExceptionHandler
     */
    public function testGetParsedResponse()
    {
        static::expectException(ExceptionHandler::class);

        $netWrapperRequest = new NetWrapper();
        $netWrapperRequest->request('https://ipv4.fraudbl.org');
        $p = $netWrapperRequest->getParsedResponse();
        /** @noinspection ForgottenDebugOutputInspection */
        static::assertTrue(isset($p->ip));
    }

    public function testRssBasic()
    {
        try {
            if (!class_exists('Laminas\Feed\Reader\Feed\Rss')) {
                static::markTestSkipped('Laminas\Feed\Reader\Feed\Rss is not available for this test.');
                return;
            }
            /** @var CurlWrapper $wrapper */
            $wrapper = $this->getBasicWrapper();
            $rss = $wrapper->request('https://www.tornevalls.se/feed/')->getParsed();

            // Class dependent request.
            if (is_array($rss)) {
                // Weak assertion.
                static::assertTrue(isset($rss[0][0]) && strlen($rss[0][0]) > 5);
            } else {
                static::assertTrue(method_exists($rss, 'getTitle'));
            }
        } catch (Exception $e) {
            static::markTestSkipped(sprintf('Non critical exception in %s: %s (%s).', __FUNCTION__, $e->getMessage(), $e->getCode()));
        }
    }

    /**
     * @throws ExceptionHandler
     */
    public function testSetStaticHeaders()
    {
        $wrapper = new NetWrapper();
        $wrapper->setHeader('myHeaderIsStatic', true, true);
        $parsed = $wrapper->request('https://ipv4.fraudbl.org')->getParsed();

        $secondParsed = $wrapper->request('https://ipv4.fraudbl.org/?secondRequest=1')->getParsed();

        static::assertTrue(isset($parsed->HTTP_MYHEADERISSTATIC, $secondParsed->HTTP_MYHEADERISSTATIC));
    }

    /**
     * @throws ExceptionHandler
     * @throws ReflectionException
     */
    public function testSetSignature()
    {
        $theName = sprintf('NETCURL-%s', (new NetWrapper())->getVersion());
        $uAgent = [sprintf('NETCURL-%s', (new NetWrapper())->getVersion()),];
        WrapperConfig::setSignature($uAgent);
        try {
            $parseRequest = (new NetWrapper())->request('https://ipv4.fraudbl.org')->getParsed();
            WrapperConfig::deleteSignature();
            static::assertEquals($theName, $parseRequest->HTTP_USER_AGENT);
        } catch (Exception $e) {
            WrapperConfig::deleteSignature();
            static::markTestSkipped(sprintf('%s is currently not working due to server errors: %s (%d)', __FUNCTION__, $e->getMessage(), $e->getCode()));
        }
    }

    public function testWrapperDefaultMisconfiguredTimeout()
    {
        $netWrapper = new NetWrapper();
        $timeout = $netWrapper->getTimeout();
        static::assertTrue(isset($timeout['CONNECT']) && (int)$timeout['CONNECT'] === 5);
    }

    public function testWrapperDefaultRealTimeout()
    {
        $netWrapper = new NetWrapper();
        $netWrapper->setTimeout(15);
        $timeout = $netWrapper->getTimeout();
        static::assertTrue(isset($timeout['CONNECT']) && (int)$timeout['CONNECT'] === 8);
    }

    /**
     * @throws ExceptionHandler
     */
    public function testWrapperDefaultZeroTimeout()
    {
        static::expectException(ExceptionHandler::class);
        $netWrapper = new NetWrapper();
        $netWrapper->setTimeout(1, true);
        $netWrapper->request('https://ipv4.fraudbl.org');
    }

    /**
     * @throws ExceptionHandler
     */
    public function testNetWrapperProxy()
    {
        if (!$this->canProxy()) {
            static::markTestSkipped('Can not perform proxy tests with this client. Skipped.');
            return;
        }

        /** @var NetWrapper $wrapper */
        $response = $this->getBasicWrapper()->setProxy('212.63.208.8:80')->request('http://identifier.tornevall.net/?inTheProxy')->getParsed();

        static::assertTrue(isset($response->ip) && $response->ip === '212.63.208.8');
    }

    /**
     * @return bool
     * @throws ExceptionHandler
     * @noinspection DuplicatedCode
     */
    private function canProxy()
    {
        $return = false;

        $ipList = ['212.63.208.', '10.1.1.',];

        $wrapperData = (new CurlWrapper())->setConfig((new WrapperConfig())->setUserAgent('ProxyTestAgent'))->request('https://ipv4.fraudbl.org')->getParsed();
        if (isset($wrapperData->ip)) {
            foreach ($ipList as $ip) {
                if ((bool)preg_match('/' . $ip . '/', $wrapperData->ip)) {
                    $return = true;
                    break;
                }
            }
        }

        return $return;
    }

    /**
     * @throws ExceptionHandler
     * @since 6.1.5
     */
    public function testSelfSignedRequest()
    {
        // Using one of those urls to emulate SSL problems:
        // https://dev-ssl-self.tornevall.nu
        // https://dev-ssl-mismatch.tornevall.nu
        $wrapper = new NetWrapper();
        $sslFail = false;
        try {
            $failRequest = $wrapper->request('https://dev-ssl-mismatch.tornevall.nu');
        } catch (ExceptionHandler $e) {
            $sslFail = true;
        }

        $newSsl = $wrapper->getConfig()->getSsl()->setStrictVerification(false, false);
        $wrapper->setSsl($newSsl);
        try {
            $newRequest = $wrapper->request('https://dev-ssl-mismatch.tornevall.nu');
            static::assertTrue($newRequest instanceof CurlWrapper && $sslFail);
        } catch (Exception $e) {
            static::markTestSkipped($e->getMessage());
        }
    }

    /**
     * @return NetWrapper
     */
    private function getBasicWrapper()
    {
        return (new NetWrapper())->setConfig($this->setTestAgent());
    }
}
