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
     * @test
     * Test the primary wrapper controller.
     */
    public function majorWrapperControl()
    {
        $netWrap = new NetWrapper();
        $realWrap = WrapperDriver::getWrappers();
        $hasWrappers = count($netWrap->getWrappers()) > 0;
        $hasWrappersReal = count($realWrap) > 0;
        static::assertTrue($hasWrappers && $hasWrappersReal);
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function basicGet()
    {
        $wrapper = (new NetWrapper())
            ->setConfig($this->setTestAgent())
            ->request(sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__));

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
        return (new WrapperConfig())->setUserAgent(
            sprintf('netcurl-%s', NETCURL_VERSION)
        );
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function extremelyBasic()
    {
        $wrapper = new NetWrapper();
        $wrapper->request(sprintf('https://ipv4.netcurl.org/'));
        $parsed = $wrapper->getParsed();
        static::assertNotEmpty(filter_var($parsed->ip, FILTER_VALIDATE_IP));
    }

    /**
     * @test
     */
    public function extremelyBasicOneLiner()
    {
        try {
            $parsed = (new NetWrapper())->request(sprintf('https://ipv4.netcurl.org/'))->getParsed();
            static::assertNotEmpty(filter_var($parsed->ip, FILTER_VALIDATE_IP));
        } catch (Exception $e) {
            static::markTestSkipped(
                sprintf(
                    'Non critical exception in %s: %s (%s).',
                    __FUNCTION__,
                    $e->getMessage(),
                    $e->getCode()
                )
            );
        }
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function sigGet()
    {
        WrapperConfig::setSignature('Korven skriker.');
        $wrapper = (new NetWrapper())
            ->request(sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__));
        $parsed = $wrapper->getParsed();
        WrapperConfig::deleteSignature();

        static::assertSame($parsed->HTTP_USER_AGENT, 'Korven skriker.');
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getParsedResponse()
    {
        static::expectException(ExceptionHandler::class);

        $netWrapperRequest = new NetWrapper();
        $netWrapperRequest->request('https://ipv4.netcurl.org');
        $p = $netWrapperRequest->getParsedResponse();
        /** @noinspection ForgottenDebugOutputInspection */
        static::assertTrue(isset($p->ip));
    }

    /**
     * @test
     */
    public function rssBasic()
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
                static::assertTrue(
                    isset($rss[0][0]) && strlen($rss[0][0]) > 5
                );
            } else {
                static::assertTrue(
                    method_exists($rss, 'getTitle')
                );
            }
        } catch (Exception $e) {
            static::markTestSkipped(
                sprintf(
                    'Non critical exception in %s: %s (%s).',
                    __FUNCTION__,
                    $e->getMessage(),
                    $e->getCode()
                )
            );
        }
    }

    /**
     * @return NetWrapper
     */
    private function getBasicWrapper()
    {
        return (new NetWrapper())->setConfig($this->setTestAgent());
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function setStaticHeaders()
    {
        $wrapper = new NetWrapper();
        $wrapper->setHeader('myHeaderIsStatic', true, true);
        $parsed = $wrapper->request(
            'https://ipv4.netcurl.org'
        )->getParsed();

        $secondParsed = $wrapper->request(
            'https://ipv4.netcurl.org/?secondRequest=1'
        )->getParsed();

        static::assertTrue(
            isset($parsed->HTTP_MYHEADERISSTATIC, $secondParsed->HTTP_MYHEADERISSTATIC)
        );
    }

    /**
     * @test
     * @throws ExceptionHandler
     * @throws ReflectionException
     */
    public function setSignature()
    {
        $theName = sprintf('NETCURL-%s', (new NetWrapper())->getVersion());
        $uAgent = [
            sprintf('NETCURL-%s', (new NetWrapper())->getVersion()),
        ];
        WrapperConfig::setSignature($uAgent);
        try {
            $parseRequest = (new NetWrapper())->request('https://ipv4.netcurl.org')->getParsed();
            WrapperConfig::deleteSignature();
            static::assertEquals($theName, $parseRequest->HTTP_USER_AGENT);
        } catch (Exception $e) {
            WrapperConfig::deleteSignature();
            static::markTestSkipped(
                sprintf(
                    '%s is currently not working due to server errors: %s (%d)',
                    __FUNCTION__,
                    $e->getMessage(),
                    $e->getCode()
                )
            );
        }
    }

    /**
     * @test
     */
    public function wrapperDefaultMisconfiguredTimeout()
    {
        $netWrapper = new NetWrapper();
        $timeout = $netWrapper->getTimeout();
        static::assertTrue(isset($timeout['CONNECT']) && (int)$timeout['CONNECT'] === 5);
    }

    /**
     * @test
     */
    public function wrapperDefaultRealTimeout()
    {
        $netWrapper = new NetWrapper();
        $netWrapper->setTimeout(15);
        $timeout = $netWrapper->getTimeout();
        static::assertTrue(isset($timeout['CONNECT']) && (int)$timeout['CONNECT'] === 8);
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function wrapperDefaultZeroTimeout()
    {
        static::expectException(ExceptionHandler::class);
        $netWrapper = new NetWrapper();
        $netWrapper->setTimeout(1, true);
        $netWrapper->request('https://ipv4.netcurl.org');
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function netWrapperProxy()
    {
        if (!$this->canProxy()) {
            static::markTestSkipped('Can not perform proxy tests with this client. Skipped.');
            return;
        }

        /** @var NetWrapper $wrapper */
        $response = $this->getBasicWrapper()
            ->setProxy('212.63.208.8:80')
            ->request('http://identifier.tornevall.net/?inTheProxy')
            ->getParsed();

        static::assertTrue(
            isset($response->ip) &&
            $response->ip === '212.63.208.8'
        );
    }

    /**
     * @return bool
     * @throws ExceptionHandler
     * @noinspection DuplicatedCode
     */
    private function canProxy()
    {
        $return = false;

        $ipList = [
            '212.63.208.',
            '10.1.1.',
        ];

        $wrapperData = (new CurlWrapper())
            ->setConfig((new WrapperConfig())->setUserAgent('ProxyTestAgent'))
            ->request('https://ipv4.netcurl.org')->getParsed();
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
     * @test
     * @throws ExceptionHandler
     * @since 6.1.5
     */
    public function selfSignedRequest()
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
            static::assertTrue(
                $newRequest instanceof CurlWrapper && $sslFail
            );
        } catch (Exception $e) {
            static::markTestSkipped($e->getMessage());
        }
    }
}
