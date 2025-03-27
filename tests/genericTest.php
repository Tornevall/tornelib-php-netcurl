<?php
/** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpDeprecationInspection */

use PHPUnit\Framework\TestCase;
use TorneLIB\Exception\Constants;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Helpers\NetUtils;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Network\NetWrapper;
use TorneLIB\MODULE_CURL;

require_once(__DIR__ . '/../vendor/autoload.php');

class genericTest extends TestCase
{
    /**
     * @throws ExceptionHandler
     */
    public function testGetGitTagsNetcurl()
    {
        static::assertGreaterThan(2, (new NetUtils())->getGitTagsByUrl("https://bitbucket.tornevall.net/scm/lib/tornelib-php-netcurl.git"));
    }

    public function testGetGitTagsNetcurlBucket()
    {
        try {
            $tags = (new NetUtils())->getGitTagsByUrl("https://bitbucket.org/resursbankplugins/resurs-ecomphp/src/master/");
            static::assertGreaterThan(2, $tags);
        } catch (Exception $e) {
            static::markTestSkipped(sprintf("Skipped %s due to exception %s (%s).\n" . "If this is a pipeline test, this could be the primary cause of the problem.", __FUNCTION__, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * Get list of tags between two versions.
     * @throws ExceptionHandler
     */
    public function testGetGitTagsByVersion()
    {
        $info = (new NetUtils())->getGitTagsByVersion('https://bitbucket.tornevall.net/scm/lib/tornelib-php-netcurl.git', '6.0.8', '6.0.13');

        static::assertGreaterThan(2, count($info));
    }

    /**
     * Test version tags from chosen version and get a list with versions higher than current.
     * @throws ExceptionHandler
     */
    public function testGetHigherVersions()
    {
        $info = (new NetUtils())->getHigherVersions('https://bitbucket.tornevall.net/scm/lib/tornelib-php-netcurl.git', '6.0.9');

        static::assertGreaterThan(2, count($info));
    }

    /**
     * @throws ExceptionHandler
     */
    public function testGetVersionTrueFalse()
    {
        static::assertFalse((new NetUtils())->getVersionLatest('https://bitbucket.tornevall.net/scm/lib/tornelib-php-netcurl.git', '6.0.9'));
    }

    public function testInternalServerError()
    {
        try {
            (new NetWrapper())->request('https://ipv4.fraudbl.org/http.php?code=500&message=Det+sket+sig');
        } catch (ExceptionHandler $e) {
            static::assertSame($e->getMessage(), 'Error 500 returned from server: "500 Det sket sig".');
        }
    }

    public function testMultiCurlErrorHandlingOneError()
    {
        $extendedException = null;
        try {
            (new NetWrapper())->request(['https://ipv4.fraudbl.org/http.php?code=200&message=Funktionsduglig', 'https://ipv4.fraudbl.org/http.php?code=500&message=Kass', 'https://ipv4.fraudbl.org/http.php?code=201&message=Mittemellan',]);
        } catch (ExceptionHandler $e) {
            // If one fails, responses can be extracted from here, but should normally be analyzed instead.
            /** @var ExceptionHandler $extendedException */
            $extendedException = $e->getExtendException();
        }

        if ($extendedException !== null) {
            /** @noinspection PhpUndefinedMethodInspection */
            $properParsed = $extendedException->getParsed('https://ipv4.fraudbl.org/http.php?code=200&message=Funktionsduglig');

            static::assertTrue(isset($properParsed->response_is_not_empty));
        } else {
            static::markTestIncomplete(sprintf('%s expected an exception but received null.', __FUNCTION__));
        }
    }

    /**
     * @throws ExceptionHandler
     */
    public function testMultiCurlErrorHandlingOneErrorNonAssoc()
    {
        /** @noinspection DynamicInvocationViaScopeResolutionInspection */
        /** @noinspection PhpParamsInspection */
        static::expectException(ExceptionHandler::class);
        (new NetWrapper())->setAllowInternalMulti(true)->request(['https://ipv4.fraudbl.org/http.php?code=200&message=Funktionsduglig', 'https://ipv4.fraudbl.org/http.php?code=500&message=Kass', 'https://ipv4.fraudbl.org/http.php?code=201&message=Mittemellan',]);
    }

    public function testMultiCurlErrorHandlingMultiError()
    {
        $code = 0;
        $extendedException = null;
        try {
            (new NetWrapper())->request(['https://ipv4.fraudbl.org/http.php?code=200&message=Funktionsduglig', 'https://ipv4.fraudbl.org/http.php?code=500&message=Kass', 'https://ipv4.fraudbl.org/http.php?code=500&message=Trasig', 'https://ipv4.fraudbl.org/http.php?code=201&message=Mittemellan',]);
        } catch (ExceptionHandler $e) {
            // If one fails, responses can be extracted from here, but should normally be analyzed instead.
            /** @var ExceptionHandler $extendedException */
            $extendedException = $e->getExtendException();
            $code = $e->getCode();
        }

        if ($extendedException !== null) {
            /** @noinspection PhpUndefinedMethodInspection */
            $properParsed = $extendedException->getParsed('https://ipv4.fraudbl.org/http.php?code=200&message=Funktionsduglig');

            static::assertTrue(isset($properParsed->response_is_not_empty) && $code === Constants::LIB_NETCURL_CURL_MULTI_EXCEPTION_DISCOVERY);
        } else {
            static::markTestIncomplete(sprintf('%s expected an exception but received null.', __FUNCTION__));
        }
    }

    /**
     * Instant exceptions with stop after first error.
     */
    public function testMultiCurlInstantExceptions()
    {
        $code = 0;
        $extendedException = null;
        try {
            (new NetWrapper())->setCurlMultiInstantException()->request(['https://ipv4.fraudbl.org/http.php?code=500&message=Kass', 'https://ipv4.fraudbl.org/http.php?code=200&message=Funktionsduglig',]);
        } catch (ExceptionHandler $e) {
            // If one fails, responses can be extracted from here, but should normally be analyzed instead.
            /** @var ExceptionHandler $extendedException */
            $extendedException = $e->getExtendException();
            $code = $e->getCode();
        }

        if ($extendedException !== null) {
            /** @noinspection PhpUndefinedMethodInspection */
            $properParsed = $extendedException->getParsed('https://ipv4.fraudbl.org/http.php?code=200&message=Funktionsduglig');

            static::assertTrue(is_null($properParsed) && $code === 500);
        } else {
            static::markTestIncomplete(sprintf('%s expected an exception but received null.', __FUNCTION__));
        }
    }

    public function testProhibitChain()
    {
        /** @noinspection DynamicInvocationViaScopeResolutionInspection */
        /** @noinspection PhpParamsInspection */
        static::expectException(ExceptionHandler::class);

        /** @noinspection PhpUndefinedMethodInspection */
        /** @noinspection PhpDeprecationInspection */
        (new MODULE_CURL())->setChain(true);
    }

    /**
     * Both wrapper and the exception returns errorCode when the main wrapper is placed outside the try-catch block.
     * @throws ExceptionHandler
     */
    public function regularExceptionTest()
    {
        $code = 0;
        $wrapper = new NetWrapper();
        try {
            $wrapper->request('https://ipv4.fraudbl.org/http.php?code=404&message=Error404+Generated');
        } catch (ExceptionHandler $e) {
            $code = $e->getCode();
        }
        $reqCode = $wrapper->getCode();

        static::assertTrue($reqCode === 404 && $code === 404);
    }

    public function testConfigurables()
    {
        $config = new WrapperConfig();
        $defaultStaging = $config->getStaging();
        /** @noinspection PhpUndefinedMethodInspection */
        $isStaging = $config->isStaging();

        static::assertTrue($defaultStaging === false && $isStaging === false);
    }
}
