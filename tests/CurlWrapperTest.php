<?php

use TorneLIB\Module\Network\Wrappers\CurlWrapper;
use PHPUnit\Framework\TestCase;

class CurlWrapperTest extends TestCase
{
    private $curlWrapper;

    protected function setUp()
    {
        parent::setUp();
    }

    /**
     * @test
     */
    public function curlWrapper()
    {
        $this->curlWrapper = new CurlWrapper();
        print_r($this->curlWrapper);
    }

}
