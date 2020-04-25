<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Model\Type\dataType;
use TorneLIB\Model\Type\requestMethod;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Network\Wrappers\SimpleStreamWrapper;

class simpleStreamWrapperTest extends TestCase
{
    private $rEcomPipeU = 'tornevall';
    private $rEcomPipeP = '2suyqJRXyd8YBGxTz42xr7g1tCWW6M2R';
    private $rEcomHost = 'https://omnitest.resurs.com';

    /**
     * @test
     */
    public function getStreamWrapper()
    {
        // Base streamwrapper (file_get_contents, fopen, etc) is only allowed if allow_url_fopen is available.
        $stream = (new SimpleStreamWrapper());

        static::assertTrue(is_object($stream));
    }

    /**
     * @test
     */
    public function getBasicUrl()
    {
        $stream = (new SimpleStreamWrapper())->setConfig(
            (new WrapperConfig())->setUserAgent('SimpleStreamWrapper')
        );
        $response = $stream->request('http://ipv4.netcurl.org/');

        $body = $response->getBody();
        $parsed = $response->getParsed();

        static::assertTrue(
            strlen($body) > 100 &&
            isset($parsed->ip) &&
            (
                isset($parsed->HTTP_USER_AGENT) &&
                $parsed->HTTP_USER_AGENT === 'SimpleStreamWrapper'
            )
        );
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getBasicPost()
    {
        $stream = (new SimpleStreamWrapper())->setConfig(
            (new WrapperConfig())->setUserAgent('SimpleStreamWrapper')
        )->setAuthentication(
            'testUser', 'hasNoEffectHere'
        );
        $response = $stream->request(
            'http://ipv4.netcurl.org/',
            [
                'postData' => ['var1' => 'val1'],
            ],
            requestMethod::METHOD_POST
        )->getParsed();

        static::assertTrue(
            isset($response->PARAMS_POST) && isset($response->PARAMS_POST->postData)
        );
    }

    /**
     * @test
     */
    public function getBasicJson()
    {
        $stream = (new SimpleStreamWrapper())->setConfig(
            (new WrapperConfig())->setUserAgent('SimpleStreamWrapper')
        )->setAuthentication(
            'testUser', 'hasNoEffectHere'
        );
        $response = $stream->request(
            'http://ipv4.netcurl.org/',
            [
                'postData' => ['var1' => 'val1'],
            ],
            requestMethod::METHOD_POST,
            dataType::JSON
        )->getParsed();

        static::assertTrue(
            isset($response->input) && strlen($response->input) > 4
        );
    }

    /**
     * @test
     * @throws ExceptionHandler
     */
    public function getBasicXml()
    {
        $stream = (new SimpleStreamWrapper())->setConfig(
            (new WrapperConfig())->setUserAgent('SimpleStreamWrapper')
        )->setAuthentication(
            'testUser', 'hasNoEffectHere'
        );
        $response = $stream->request(
            'http://ipv4.netcurl.org/',
            [
                'postData' => ['var1' => 'val1'],
            ],
            requestMethod::METHOD_POST,
            dataType::XML
        )->getParsed();

        static::assertTrue(
            isset($response->input) && strlen($response->input) > 4
        );
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
