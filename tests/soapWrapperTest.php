<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Helpers\Version;
use TorneLIB\Module\Network\Wrappers\SoapClientWrapper;

try {
    Version::getRequiredVersion();
} catch (Exception $e) {
    die($e->getMessage());
}

class soapWrapperTest extends TestCase
{
    private $rEcomPipeU = 'tornevall';
    private $rEcomPipeP = '2suyqJRXyd8YBGxTz42xr7g1tCWW6M2R';
    private $wsdl = 'https://test.resurs.com/ecommerce-test/ws/V4/SimplifiedShopFlowService?wsdl';
    private $no_wsdl = 'https://test.resurs.com/ecommerce-test/ws/V4/SimplifiedShopFlowService';
    private $netcurlWsdl = 'https://tests.netcurl.org/tornevall_network/index.wsdl?wsdl';

    /**
     * @test
     */
    public function basicSoapClient()
    {
        $soapWrapper = new SoapClientWrapper();
        static::assertTrue(is_object($soapWrapper));
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getSoapUninitialized()
    {
        $soapWrapper = (new SoapClientWrapper());
        static::assertTrue(
            is_object($soapWrapper)
        );
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getStreamContext()
    {
        $soapWrapper = new SoapClientWrapper();
        $soapContext = $soapWrapper->getStreamContext();
        static::assertTrue(!empty($soapContext['http']));
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getSoapEmbeddedRandomRequest()
    {
        // Note: We could've been chaining this one, but in this case, there's other stuff to test.
        $soapWrapper = new SoapClientWrapper($this->netcurlWsdl);
        $soapWrapper->setAuthentication($this->rEcomPipeU, $this->rEcomPipeP);
        $soapWrapper->setUserAgent('That requesting client');
        $userAgent = $soapWrapper->getUserAgent();
        $soapContext = $soapWrapper->getStreamContext();
        $hasErrorOnRandom = false;
        try {
            $soapWrapper->getRandomRequest();
        } catch (Exception $e) {
            $hasErrorOnRandom = true;
        }
        static::assertTrue(
            !empty($soapContext['http']) &&
            $userAgent === 'That requesting client' &&
            $hasErrorOnRandom
        );
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getSoapEmbeddedReal()
    {
        try {
            $soapWrapper = new SoapClientWrapper($this->wsdl);
            $soapWrapper->setAuthentication($this->rEcomPipeU, $this->rEcomPipeP);
            $paymentMethods = $soapWrapper->getPaymentMethods();
            $functions = $soapWrapper->getFunctions();
            $lastRequest = $soapWrapper->getLastRequest();
            static::assertTrue(
                (
                    is_array($paymentMethods) &&
                    count($paymentMethods)
                ) &&
                is_array($functions) &&
                (
                    is_string($lastRequest) &&
                    strlen($lastRequest) > 0
                )
            );
        } catch (Exception $e) {
            static::markTestSkipped(
                sprintf(
                    'Soaptest with expected success failed. API seems to be down (%s: %s).',
                    $e->getCode(),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getSoapEmbeddedAuthFail()
    {
        try {
            $soapWrapper = new SoapClientWrapper($this->wsdl);
            $soapWrapper->setAuthentication('fail', 'doubleFail')->getPaymentMethods();
        } catch (Exception $e) {
            // Assert "Unauthorized" code (401) or error code 2 based on E_WARNING
            static::assertTrue(
                $e->getCode() === 401 ||
                $e->getCode() === 2
            );
        }
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getSoapEmbeddedNoWsdl()
    {
        try {
            // Service bails out on error 500 when ?wsdl is excluded.
            (new SoapClientWrapper($this->no_wsdl))
                ->setAuthentication(
                    $this->rEcomPipeU,
                    $this->rEcomPipeP
                )->getPaymentMethods();
        } catch (ExceptionHandler $e) {
            static::assertTrue($e->getCode() === 500);
        }
    }

    /**
     * @test
     * @testdox Delayed request.
     */
    public function getSoapEmbeddedRequest()
    {
        $wrapper = new SoapClientWrapper();
        $wrapper->setAuthentication(
            $this->rEcomPipeU,
            $this->rEcomPipeP
        );
        $result = $wrapper->request($this->wsdl)->getPaymentMethods();
        static::assertTrue(
            is_array($result) &&
            count($result)
        );
    }

    /**
     * @test
     * @testdox While testing the wsdlcache, we also testing header, body and parser.
     * @throws ExceptionHandler
     */
    public function setWsdlCache()
    {
        $wrapper = new SoapClientWrapper($this->wsdl);
        $wrapper->setWsdlCache(WSDL_CACHE_MEMORY)->setAuthentication(
            $this->rEcomPipeU,
            $this->rEcomPipeP
        )->getPaymentMethods();

        $parsed = $wrapper->getParsed();
        $body = $wrapper->getBody();
        $headers = $wrapper->getHeaders(true, true);
        static::assertTrue(
            is_array($parsed) && count($parsed) &&
            is_string($body) && strlen($body) &&
            (
                (
                    is_string($headers) && strlen($headers)
                ) || is_array($headers)
            )
        );
    }
}
