<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Module\Network\Wrappers\CurlWrapper;
use TorneLIB\Module\Network\Wrappers\SimpleStreamWrapper;

class resursWrappingTest extends TestCase
{
    private $rEcomPipeU = 'tornevall';
    private $rEcomPipeP = '2suyqJRXyd8YBGxTz42xr7g1tCWW6M2R';
    private $rEcomHost = 'https://omnitest.resurs.com';

    /**
     * @test
     * Testing a simple "real world" rest request
     * @throws ExceptionHandler
     */
    public function getRestWithAuth()
    {
        $curlWrapper = new CurlWrapper();

        try {
            $wrapperRequest = $curlWrapper->setAuthentication($this->rEcomPipeU, $this->rEcomPipeP)->request(
                sprintf('%s/callbacks', $this->rEcomHost)
            );

            $parsed = $wrapperRequest->getParsed();
            static::assertTrue(is_array($parsed));
        } catch (Exception $e) {
            // 35 when ssl version set to 4.
            static::markTestIncomplete(
                sprintf(
                    '%s exception %s: %s',
                    __FUNCTION__,
                    $e->getCode(),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * @test
     * Real world rest request with specific body errors.
     * @throws ExceptionHandler
     */
    public function getThrowablesByBody()
    {
        $curlWrapper = new CurlWrapper();

        try {
            $curlWrapper->setAuthentication(
                $this->rEcomPipeU, $this->rEcomPipeP)->request(
                sprintf(
                    '%s/payment/nonExistingReference',
                    $this->rEcomHost
                )
            );
        } catch (ExceptionHandler $e) {
            $body = $curlWrapper->getParsed();
            /** @var CurlWrapper $curlWrapperException */
            $curlWrapperException = $e->getExtendException()->getParsed();

            // ExceptionHandler will give more information without the need to put the wrapperRequest
            // outside the try/catch, nor initialize it from there. In this test we will be able to
            // trigger an error and the continue requesting body/parsed content with the help from exception.
            //
            // Alternatively it is also possible to do the opposite of this comment - initialize
            // the curlwrapper, and then continue request for the information from there.
            static::assertTrue(
                (int)$e->getCode() === 404 &&
                (int)$body->code === 404 &&
                !empty($curlWrapperException->message)
            );
        }
    }

    /**
     * @test
     */
    public function getBasicRestTest()
    {
        $response = [];

        $stream = (new SimpleStreamWrapper())->setAuthentication(
            $this->rEcomPipeU,
            $this->rEcomPipeP
        );

        try {
            $response = $stream->request(
                sprintf(
                    '%s/callbacks',
                    $this->rEcomHost
                )
            )->getParsed();
        } catch (ExceptionHandler $e) {
            static::markTestSkipped(
                sprintf(
                    'Exception marking this test skipped: (%s) %s',
                    $e->getCode(),
                    $e->getMessage()
                )
            );
        }

        static::assertTrue(
            count($response) > 0
        );
    }
}
