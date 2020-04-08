<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use TorneLIB\Module\Network\NetWrapper;

class netcurlTest extends TestCase
{
    /**
     * @test
     * @testdox Test the primary wrapper controller.
     */
    public function majorWrapperControl()
    {
        $netWrap = new NetWrapper();
        static::assertTrue(count($netWrap->getWrappers()) ? true : false);
    }
}
