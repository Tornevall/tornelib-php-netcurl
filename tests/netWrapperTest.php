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

    /*public function noSoapClient()
    {
        try {
            $wrapper = (new NetWrapper())
                ->setAuthentication($this->rEcomPipeU, $this->rEcomPipeP)
                ->request($this->wsdl);
            $wrapper->getPaymentMethods();
            $asXml = $wrapper->getLastRequest();
        } catch (ExceptionHandler $e) {
            static::markTestSkipped(
                sprintf(
                    'Skipped test due to code %s, message %s.',
                    $e->getCode(),
                    $e->getMessage()
                )
            );
            return;
        }

        Flag::setFlag('testmode_disabled_SoapClient');

        try {
            $wrapper = (new NetWrapper())
                ->setAuthentication($this->rEcomPipeU, $this->rEcomPipeP)
                ->request($this->wsdl, $asXml)
                ->getParsed();
        } catch (ExceptionHandler $e) {
            Flag::deleteFlag('testmode_disabled_SoapClient');
            static::markTestSkipped(
                sprintf(
                    'Skipped test due to code %s, message %s.',
                    $e->getCode(),
                    $e->getMessage()
                )
            );
            return;
        }

        static::assertTrue(
            is_array($wrapper)
        );
    }*/
}
