<?php

use TorneLIB\Module\Network\Wrappers\CurlWrapper;
use PHPUnit\Framework\TestCase;

define('LIB_ERROR_HTTP', true);

class CurlWrapperTest extends TestCase
{
    private $curlWrapper;

    protected function setUp()
    {
        parent::setUp();
    }

    /**
     * @test
     * @testdox Test the primary wrapper controller.
     */
    public function majorWrapperControl()
    {
        $netWrap = new \TorneLIB\Module\Network\NetWrapper();
        static::assertTrue(count($netWrap->getWrappers()) ? true : false);
    }

    /**
     * @test
     */
    public function curlWrapper()
    {
        try {
            $this->curlWrapper = new CurlWrapper();
        } catch (\Exception $e) {
            echo $e->getCode();
        }
    }

}
