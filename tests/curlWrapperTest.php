<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Exception\Constants;
use TorneLIB\Flags;
use TorneLIB\Helpers\Browsers;
use TorneLIB\Helpers\Version;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Network\NetWrapper;
use TorneLIB\Module\Network\Wrappers\CurlWrapper;

define('LIB_ERROR_HTTP', true);
require_once(__DIR__ . '/../vendor/autoload.php');

try {
    Version::getRequiredVersion();
} catch (Exception $e) {
    die($e->getMessage());
}

/**
 * Class curlWrapperTest
 */
class curlWrapperTest extends TestCase
{
    private $curlWrapper;

    /**
     * @test
     * @testdox Test the primary wrapper controller.
     */
    public function majorWrapperControl()
    {
        $netWrap = new NetWrapper();
        static::assertTrue(count($netWrap->getWrappers()) ? true : false);
    }

    /**
     * @test
     */
    public function initialCurlWrapper()
    {
        try {
            $curlWrapperArgs = new CurlWrapper(
                'https://identifier.tornevall.net',
                [],
                \TorneLIB\Model\Type\requestMethod::METHOD_GET,
                [
                    'flag1' => 'present',
                    'flag2' => 'available',
                ]
            );

            // Initialize primary curlwrapper to test with.
            $this->curlWrapper = new CurlWrapper();
        } catch (\Exception $e) {
            static::markTestIncomplete(sprintf(
                'Skipped test on exception %s: %s',
                $e->getCode(),
                $e->getMessage()
            ));
            return;
        }

        static::assertTrue(
            (
                is_object($curlWrapperArgs) &&
                is_object($this->curlWrapper) &&
                is_object($this->curlWrapper->getConfig()) &&
                count(Flags::_getAllFlags()) === 2
            ) ? true : false
        );
    }

    /**
     * @test
     */
    public function safeMode()
    {
        $security = new \TorneLIB\Utils\Security();
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            static::assertFalse($security->getSafeMode());
        } else {
            static::assertTrue($security->getSafeMode());
        }
    }

    /**
     * @test
     */
    public function secureMode()
    {
        $security = new \TorneLIB\Utils\Security();
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            static::assertFalse($security->getSecureMode());
        } else {
            static::assertTrue($security->getSecureMode());
        }
    }

    /**
     * @test
     */
    public function browserSet()
    {
        // Making sure output lloks the same in both ends.
        static::assertTrue(
            preg_match('/^mozilla|^netcurl/i', (new Browsers())->getBrowser()) ? true : false &&
                preg_match('/^mozilla|^netcurl/i', (new WrapperConfig())->getOptions()['10018'])
        );
    }

    /**
     * @test
     * @throws \TorneLIB\Exception\ExceptionHandler
     */
    public function basicMultiGet()
    {
        $wrapper = (new CurlWrapper())->request([
            sprintf('https://identifier.tornevall.net/ip.php?func=%s', __FUNCTION__),
            sprintf('https://identifier.tornevall.net/?func=%s', __FUNCTION__),
        ]);

        // This test is relatively inactive.
        static::assertTrue(is_object($wrapper));
    }

    /**
     * @test
     * @throws \TorneLIB\Exception\ExceptionHandler
     */
    public function curlWrapperConstructor()
    {
        /** @var CurlWrapper $curlRequest */
        $curlRequest = (new CurlWrapper(
            sprintf('https://identifier.tornevall.net/?func=%s', __FUNCTION__),
            [],
            \TorneLIB\Model\Type\requestMethod::METHOD_GET,
            [
                'flag1' => 'present',
                'flag2' => 'available',
            ]
        ));

        $curlRequest->setOptionCurl($curlRequest->getCurlHandle(), CURLOPT_USERAGENT, __FUNCTION__);
        $curlRequest->getCurlRequest();

        static::assertTrue(is_object($curlRequest));
    }

    /**
     * @test
     * @throws \TorneLIB\Exception\ExceptionHandler
     */
    public function basicGet()
    {
        $wrapper = (new CurlWrapper())->request(sprintf('https://identifier.tornevall.net/?func=%s', __FUNCTION__));
        $parsed = $wrapper->getCurlRequest()->getParsed();
        if (isset($parsed->ip)) {
            static::assertTrue(filter_var($parsed->ip, FILTER_VALIDATE_IP) ? true : false);
        }
    }

    /**
     * @test
     * @throws \TorneLIB\Exception\ExceptionHandler
     */
    public function basicGetHeader()
    {
        $data = (new CurlWrapper())->request(sprintf('https://identifier.tornevall.net/?func=%s', __FUNCTION__));

        static::assertTrue($data->getHeader('content-type') === 'application/json');
    }

    /**
     * @test
     * @throws \TorneLIB\Exception\ExceptionHandler
     */
    public function multiGetHeader()
    {
        $firstMultiUrl = sprintf('https://identifier.tornevall.net/?func=%s&php=%s', __FUNCTION__, PHP_VERSION);
        $secondMultiUrl = sprintf('https://identifier.tornevall.net/ip.php?func=%s&php=%s', __FUNCTION__, PHP_VERSION);
        $data = (new CurlWrapper())->request(
            [
                $firstMultiUrl,
                $secondMultiUrl,
            ]
        );

        static::assertTrue(
            $data->getHeader('content-type', $firstMultiUrl) === 'application/json' &&
            count(strlen($data->getHeader(null, $secondMultiUrl)) > 1)
        );
    }

    /**
     * @test
     *
     * Initialize empty curlwrapper - set url after init and request an uninitialized wrapper. Expected result
     * is self initialized wrapper of curl.
     *
     * @throws \TorneLIB\Exception\ExceptionHandler
     */
    public function unInitializedCurlWrapperByConfig()
    {
        $wrapper = new CurlWrapper();
        $wrapper->getConfig()->setRequestUrl(
            sprintf(
                'https://identifier.tornevall.net/?func=%s&php=%s',
                __FUNCTION__,
                PHP_VERSION
            )
        );
        $wrapper->setOptionCurl($wrapper->getCurlHandle(), CURLOPT_USERAGENT, __FUNCTION__);
        $parsed = $wrapper->getCurlRequest()->getParsed();
        if (isset($parsed->ip)) {
            static::assertTrue(filter_var($parsed->ip, FILTER_VALIDATE_IP) ? true : false);
        }
    }

    /**
     * @test
     * @throws \TorneLIB\Exception\ExceptionHandler
     */
    public function unInitializedCurlWrapperMinorConfig()
    {
        $wrapper = new CurlWrapper();
        $wrapper->getConfig()->setRequestUrl(
            sprintf(
                'https://identifier.tornevall.net/?func=%s&php=%s',
                __FUNCTION__,
                PHP_VERSION
            )
        );
        $parsed = $wrapper->getCurlRequest()->getParsed();
        if (isset($parsed->ip)) {
            static::assertTrue(filter_var($parsed->ip, FILTER_VALIDATE_IP) ? true : false);
        }
    }

    /**
     * @test
     *
     * Lowest initializer level, where nothing can be initiated since there is no defined url.
     *
     * @throws \TorneLIB\Exception\ExceptionHandler
     */
    public function unInitializedCurlWrapperNoConfig()
    {
        try {
            $wrapper = new CurlWrapper();
            $wrapper->getCurlRequest();
        } catch (\Exception $e) {
            static::assertTrue($e->getCode() === Constants::LIB_EMPTY_URL);
        }
    }
}
