<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Config\WrapperDriver;
use TorneLIB\Module\Network\NetWrapper;
use TorneLIB\Module\Network\Wrappers\CurlWrapper;

/**
 * Class netcurlTest
 * Tests for entire netcurl package, via NetWrapper.
 */
class netWrapperTest extends TestCase
{
    /**
     * @return bool
     * @throws ExceptionHandler
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
                if (preg_match('/' . $ip . '/', $wrapperData->ip)) {
                    $return = true;
                    break;
                }
            }
        }

        return $return;
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
     * @return NetWrapper
     */
    private function getBasicWrapper()
    {
        return (new NetWrapper())->setConfig($this->setTestAgent());
    }

    /**
     * @test
     * Test the primary wrapper controller.
     */
    public function majorWrapperControl()
    {
        $netWrap = new NetWrapper();
        $realWrap = WrapperDriver::getWrappers();
        static::assertTrue(
            (
            count($netWrap->getWrappers()) > 0 ? true : false &&
            count($realWrap) > 0 ? true : false
            ) ? true : false
        );
    }

    /**
     * @test
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

        static::assertTrue($parsed->HTTP_USER_AGENT === 'Korven skriker.');
    }

    /**
     * @test
     */
    public function rssBasic()
    {
        /** @var CurlWrapper $wrapper */
        $wrapper = $this->getBasicWrapper();
        $rss = $wrapper->request('https://www.tornevalls.se/feed/')->getParsed();
        // Weak assertion.
        static::assertTrue(
            isset($rss[0]) &&
            isset($rss[0][0]) &&
            strlen($rss[0][0]) > 5
        );
    }

    /**
     * @test
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
}
