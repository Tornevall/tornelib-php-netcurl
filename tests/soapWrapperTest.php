<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Helpers\Version;

try {
    Version::getRequiredVersion();
} catch (Exception $e) {
    die($e->getMessage());
}

class soapWrapperTest extends TestCase
{
    /**
     * @test
     */
    public function basicSoapClient() {
        $soapWrapper = new TorneLIB\Module\Network\Wrappers\SoapClientWrapper();
        static::assertTrue(is_object($soapWrapper));
    }
}
