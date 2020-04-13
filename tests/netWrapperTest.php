<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use TorneLIB\Config\Flag;
use TorneLIB\Exception\ExceptionHandler;
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
     * @test
     * Test the primary wrapper controller.
     */
    public function majorWrapperControl()
    {
        $netWrap = new NetWrapper();
        static::assertTrue(count($netWrap->getWrappers()) ? true : false);
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function noSoapClient()
    {
        $wrapper = (new NetWrapper())
            ->setAuthentication($this->rEcomPipeU, $this->rEcomPipeP)
            ->request($this->wsdl);
        $wrapper->getPaymentMethods();
        $asXml = $wrapper->getLastRequest();

        Flag::setFlag('testmode_disabled_SoapClient');

        /** @var NetWrapper $wrapper */
        $wrapper = (new NetWrapper())
            ->setAuthentication($this->rEcomPipeU, $this->rEcomPipeP)
            ->request($this->wsdl, $asXml)
            ->getParsed();

        // @todo IO-Wrapper not parsing xml from soap properly.
        static::assertTrue(
            is_object($wrapper)
        );
    }
}
