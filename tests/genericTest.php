<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Exception\Constants;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Helpers\NetUtils;
use TorneLIB\Module\Network\NetWrapper;
use TorneLIB\MODULE_CURL;

require_once(__DIR__ . '/../vendor/autoload.php');

class genericTest extends TestCase
{
    /**
     * @test
     */
    public function getGitTagsNetcurl()
    {
        static::assertGreaterThan(
            2,
            (new NetUtils())->getGitTagsByUrl("https://bitbucket.tornevall.net/scm/lib/tornelib-php-netcurl.git")
        );
    }

    /**
     * @test
     */
    public function getGitTagsNetcurlBucket()
    {
        try {
            $tags = (new NetUtils())->getGitTagsByUrl("https://bitbucket.org/resursbankplugins/resurs-ecomphp/src/master/");
            static::assertGreaterThan(
                2,
                $tags
            );
        } catch (Exception $e) {
            static::markTestSkipped(
                sprintf(
                    "Skipped %s due to exception %s (%s).\n" .
                    "If this is a pipeline test, this could be the primary cause of the problem.",
                    __FUNCTION__,
                    $e->getCode(),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * @test
     * Get list of tags between two versions.
     */
    public function getGitTagsByVersion()
    {
        $info = (new NetUtils())->getGitTagsByVersion(
            'https://bitbucket.tornevall.net/scm/lib/tornelib-php-netcurl.git',
            '6.0.8',
            '6.0.13',
            '>='
        );

        static::assertGreaterThan(
            2,
            count($info)
        );
    }

    /**
     * @test
     * Test version tags from chosen version and get a list with versions higher than current.
     */
    public function getHigherVersions()
    {
        $info = (new NetUtils())->getHigherVersions(
            'https://bitbucket.tornevall.net/scm/lib/tornelib-php-netcurl.git',
            '6.0.9'
        );

        static::assertGreaterThan(
            2,
            count($info)
        );
    }

    /**
     * @test
     * Test latest tag with latest release. List should be empty.
     */
    public function getMyVersion()
    {
        $info = (new NetUtils())->getHigherVersions(
            'https://bitbucket.tornevall.net/scm/lib/tornelib-php-netcurl.git',
            (new NetWrapper())->getVersion()
        );

        static::assertCount(
            0,
            $info
        );
    }

    /**
     * @test
     */
    public function getVersionTrueFalse()
    {
        static::assertFalse(
            (new NetUtils())->getVersionLatest(
                'https://bitbucket.tornevall.net/scm/lib/tornelib-php-netcurl.git',
                '6.0.9'
            )
        );
    }

    /**
     * @test
     */
    public function fyrahundrafyra()
    {
        try {
            (new NetWrapper())->request('http://ipv4.netcurl.org/http.php?code=500&message=Det+sket+sig');
        } catch (ExceptionHandler $e) {
            static::assertTrue($e->getMessage() === 'Error 500 returned from server: "500 Det sket sig".');
        }
    }

    /**
     * @test
     */
    public function multiCurlErrorHandlingOneError() {
        $extendedException = null;
        try {
            (new NetWrapper())->request(
                [
                    'http://ipv4.netcurl.org/http.php?code=200&message=Funktionsduglig',
                    'http://ipv4.netcurl.org/http.php?code=500&message=Kass',
                    'http://ipv4.netcurl.org/http.php?code=201&message=Mittemellan'
                ]
            );
        } catch (ExceptionHandler $e) {
            // If one fails, responses can be extracted from here, but should normally be analyzed instead.
            /** @var ExceptionHandler $extendedException */
            $extendedException = $e->getExtendException();
        }

        $properParsed = $extendedException->getParsed('http://ipv4.netcurl.org/http.php?code=200&message=Funktionsduglig');

        static::assertTrue(
            isset($properParsed->response_is_not_empty)
        );
    }

    /**
     * @test
     */
    public function multiCurlErrorHandlingMultiError()
    {
        $code = 0;
        $extendedException = null;
        try {
            (new NetWrapper())->request(
                [
                    'http://ipv4.netcurl.org/http.php?code=200&message=Funktionsduglig',
                    'http://ipv4.netcurl.org/http.php?code=500&message=Kass',
                    'http://ipv4.netcurl.org/http.php?code=500&message=Trasig',
                    'http://ipv4.netcurl.org/http.php?code=201&message=Mittemellan',
                ]
            );
        } catch (ExceptionHandler $e) {
            // If one fails, responses can be extracted from here, but should normally be analyzed instead.
            /** @var ExceptionHandler $extendedException */
            $extendedException = $e->getExtendException();
            $code = $e->getCode();
        }

        $properParsed = $extendedException->getParsed('http://ipv4.netcurl.org/http.php?code=200&message=Funktionsduglig');

        static::assertTrue(
            isset($properParsed->response_is_not_empty) &&
            $code === Constants::LIB_NETCURL_CURL_MULTI_EXCEPTION_DISCOVERY
        );
    }

    /**
     * @test
     * Instant exceptions with stop after first error.
     */
    public function multiCurlInstantExceptions()
    {
        $code = 0;
        $extendedException = null;
        try {
            (new NetWrapper())->setCurlMultiInstantException()->request(
                [
                    'http://ipv4.netcurl.org/http.php?code=500&message=Kass',
                    'http://ipv4.netcurl.org/http.php?code=200&message=Funktionsduglig',
                ]
            );
        } catch (ExceptionHandler $e) {
            // If one fails, responses can be extracted from here, but should normally be analyzed instead.
            /** @var ExceptionHandler $extendedException */
            $extendedException = $e->getExtendException();
            $code = $e->getCode();
        }

        $properParsed = $extendedException->getParsed('http://ipv4.netcurl.org/http.php?code=200&message=Funktionsduglig');

        static::assertTrue(
            is_null($properParsed) &&
            $code === 500
        );
    }

    /**
     * @test
     */
    public function prohibitChain() {
        static::expectException(ExceptionHandler::class);

        (new MODULE_CURL())->setChain(true);
    }
}
