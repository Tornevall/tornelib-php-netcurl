<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Model\Type\dataType;
use TorneLIB\Model\Type\requestMethod;
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
    private $rEcomPipeU = 'tornevall';
    private $rEcomPipeP = '2suyqJRXyd8YBGxTz42xr7g1tCWW6M2R';
    private $wsdl = 'https://test.resurs.com/ecommerce-test/ws/V4/SimplifiedShopFlowService?wsdl';

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
                    isset($rss[0]) &&
                    isset($rss[0][0]) &&
                    strlen($rss[0][0]) > 5
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

    /**
     * @test
     */
    public function multiNetWrapper()
    {
        // Separate array to make it easier to see what we're doing.
        $reqUrlData = [
            'https://ipv4.netcurl.org/?1' => [
                [],
                requestMethod::METHOD_POST,
                dataType::NORMAL,
                (new WrapperConfig())->setUserAgent('Client1'),
            ],
            'https://ipv4.netcurl.org/?2' => [
                [],
                requestMethod::METHOD_POST,
                dataType::NORMAL,
                (new WrapperConfig())
                    ->setUserAgent('Client2')
                    ->setAuthentication($this->rEcomPipeU, $this->rEcomPipeP),
            ],
            $this->wsdl => [],
        ];

        $info = (new NetWrapper())->request($reqUrlData);
        $p = $info->getParsed('https://ipv4.netcurl.org/?2');

        $soapError = false;
        $paymentMethods = [];
        try {
            $paymentMethods = (($info->getWrapper($this->wsdl))->getPaymentMethods());
        } catch (ExceptionHandler $e) {
            $soapError = true;
            // Internal server error protection.
        }
        static::assertTrue(
            isset($p->HTTP_USER_AGENT) &&
            $p->HTTP_USER_AGENT === 'Client2' &&
            (
                is_array($paymentMethods) &&
                count($paymentMethods) || $soapError
            ) &&
            $info->getCode("https://ipv4.netcurl.org/?2") === 200
        );
    }
}
