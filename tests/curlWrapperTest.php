<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use TorneLIB\Exception\Constants;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Flags;
use TorneLIB\Helpers\Browsers;
use TorneLIB\Helpers\Version;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Network\Wrappers\CurlWrapper;

define('LIB_ERROR_HTTP', true);

try {
    Version::getRequiredVersion();
} catch (Exception $e) {
    die($e->getMessage());
}

/**
 * Class curlWrapperTest
 * @version 6.1.0
 */
class curlWrapperTest extends TestCase
{
    private $curlWrapper;
    private $rEcomPipeU = 'tornevall';
    private $rEcomPipeP = '2suyqJRXyd8YBGxTz42xr7g1tCWW6M2R';
    private $rEcomHost = 'https://omnitest.resurs.com';

    /**
     * @test
     */
    public function initialCurlWrapper()
    {
        try {
            $curlWrapperArgs = new CurlWrapper(
                'https://ipv4.netcurl.org',
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

        /** @noinspection PhpUndefinedMethodInspection */
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
            sprintf('https://ipv4.netcurl.org/ip.php?func=%s', __FUNCTION__),
            sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__),
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
            sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__),
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
        $wrapper = (new CurlWrapper())->request(sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__));
        $parsed = $wrapper->getCurlRequest()->getParsed();
        if (isset($parsed->ip)) {
            static::assertTrue(filter_var($parsed->ip, FILTER_VALIDATE_IP) ? true : false);
        }
    }

    /**
     * @test
     * @throws \TorneLIB\Exception\ExceptionHandler
     */
    public function basicGetLowTLS()
    {
        $tlsResponse = (new CurlWrapper())->
        setConfig((new WrapperConfig())->setOption(CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_0))->
        request(
            sprintf('https://ipv4.netcurl.org/?func=%s',
                __FUNCTION__
            )
        )->getParsed();


        if (isset($tlsResponse->ip)) {
            static::assertTrue(
                filter_var($tlsResponse->ip, FILTER_VALIDATE_IP) ? true : false &&
                    $tlsResponse->SSL->SSL_PROTOCOL === 'TLSv1'
            );
        }
    }

    /**
     * @test
     * @throws \TorneLIB\Exception\ExceptionHandler
     */
    public function basicGetTLS11()
    {
        $tlsResponse = (new CurlWrapper())->
        setConfig((new WrapperConfig())->setOption(CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_1))->
        request(
            sprintf('https://ipv4.netcurl.org/?func=%s',
                __FUNCTION__
            )
        )->getParsed();

        if (isset($tlsResponse->ip)) {
            static::assertTrue(
                filter_var($tlsResponse->ip, FILTER_VALIDATE_IP) ? true : false &&
                    $tlsResponse->SSL->SSL_PROTOCOL === 'TLSv1.1'
            );
        }
    }

    /**
     * @test
     * @throws \TorneLIB\Exception\ExceptionHandler
     */
    public function basicGetTLS13()
    {
        if (defined('CURL_SSLVERSION_TLSv1_3')) {
            try {
                $tlsResponse = (new CurlWrapper())->
                setConfig((new WrapperConfig())->setOption(CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_3))->
                request(
                    sprintf('https://ipv4.netcurl.org/?func=%s',
                        __FUNCTION__
                    )
                )->getParsed();

                if (isset($tlsResponse->ip)) {
                    static::assertTrue(
                        filter_var($tlsResponse->ip, FILTER_VALIDATE_IP) ? true : false &&
                            $tlsResponse->SSL->SSL_PROTOCOL === 'TLSv1.3'
                    );
                }
            } catch (Exception $e) {
                // Getting connect errors here may indicate that the netcurl server is missing TLS 1.3 support.
                // TLS 1.3 is supported from Apache 2.4.37
                // Also be aware of the fact that not all PHP releases support it.
                if ($e->getCode() === CURLE_SSL_CONNECT_ERROR) {
                    // 14094410
                    static::markTestSkipped($e->getMessage());
                }
            }
        } else {
            static::markTestSkipped('TLSv1.3 is not available on this platform.');
        }
    }

    /**
     * @test
     * @throws \TorneLIB\Exception\ExceptionHandler
     */
    public function basicGetHeader()
    {
        $data = (new CurlWrapper())->request(sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__));

        static::assertTrue($data->getHeader('content-type') === 'application/json');
    }

    /**
     * @test
     * @throws \TorneLIB\Exception\ExceptionHandler
     */
    public function basicPostWithGetData()
    {
        $data = (new CurlWrapper())->request(
            sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__),
            ['hello' => 'world'],
            \TorneLIB\Model\Type\requestMethod::METHOD_POST)
            ->getParsed();

        static::assertTrue(
            isset($data->PARAMS_REQUEST->hello) &&
            isset($data->PARAMS_GET->func) &&
            $data->PARAMS_GET->func === __FUNCTION__
        );
    }

    /**
     * @test
     * @throws \TorneLIB\Exception\ExceptionHandler
     */
    public function basicGetWithPost()
    {
        $data = (new CurlWrapper())->request(
            sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__),
            ['hello' => 'world'],
            \TorneLIB\Model\Type\requestMethod::METHOD_GET)
            ->getParsed();

        static::assertTrue(!isset($data->PARAMS_REQUEST->hello));
    }

    /**
     * @test
     * @throws \TorneLIB\Exception\ExceptionHandler
     */
    public function basicPost()
    {
        $data = (new CurlWrapper())->request(
            sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__),
            ['hello' => 'world'],
            \TorneLIB\Model\Type\requestMethod::METHOD_POST)
            ->getParsed();

        static::assertTrue(isset($data->PARAMS_POST->hello));
    }

    /**
     * @test
     * @throws \TorneLIB\Exception\ExceptionHandler
     */
    public function basicGetHeaderUserAgent()
    {
        $curlRequest =
            (new CurlWrapper())
                ->setConfig((new WrapperConfig())->setOptions([CURLOPT_USERAGENT => 'ExternalClientName']))
                ->request(
                    sprintf('https://ipv4.netcurl.org/?func=%s', __FUNCTION__)
                );

        static::assertTrue($curlRequest->getHeader('content-type') === 'application/json');
    }

    /**
     * @test
     * @throws \TorneLIB\Exception\ExceptionHandler
     */
    public function multiGetHeader()
    {
        $firstMultiUrl = sprintf(
            'https://ipv4.netcurl.org/?func=%s&php=%s',
            __FUNCTION__,
            rawurlencode(PHP_VERSION)
        );
        $secondMultiUrl = sprintf(
            'https://ipv4.netcurl.org/ip.php?func=%s&php=%s', __FUNCTION__,
            rawurlencode(PHP_VERSION)
        );
        $data = (new CurlWrapper())->request(
            [
                $firstMultiUrl,
                $secondMultiUrl,
            ]
        );

        static::assertTrue(
            $data->getHeader('content-type', $firstMultiUrl) === 'application/json' &&
            strlen($data->getHeader(null, $secondMultiUrl)) > 1
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
                'https://ipv4.netcurl.org/?func=%s&php=%s',
                __FUNCTION__,
                rawurlencode(PHP_VERSION)
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
                'https://ipv4.netcurl.org/?func=%s&php=%s',
                __FUNCTION__,
                rawurlencode(PHP_VERSION)
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
     */
    public function unInitializedCurlWrapperNoConfig()
    {
        try {
            $wrapper = new CurlWrapper();
            $wrapper->getCurlRequest();
        } catch (\Exception $e) {
            static::assertTrue($e->getCode() == Constants::LIB_EMPTY_URL);
        }
    }

    /**
     * @test
     * Certificate errors like this can obviously render two different kind of errors.
     */
    public function sslCurlePeerCert51()
    {
        try {
            (new CurlWrapper('https://dev-ssl-mismatch.tornevall.nu'))->getCurlRequest();
        } catch (Exception $e) {
            static::assertTrue(
                $e->getCode() === CURLE_SSL_PEER_CERTIFICATE ||
                $e->getCode() === CURLE_SSL_CONNECT_ERROR
            );
        }
    }

    /**
     * @test
     * Certificate errors like this can obviously render two different kind of errors.
     */
    public function sslCurleCacert60()
    {
        try {
            (new CurlWrapper('https://dev-ssl-self.tornevall.nu'))->getCurlRequest();
        } catch (Exception $e) {
            static::assertTrue(
                $e->getCode() === CURLE_SSL_CACERT ||
                $e->getCode() === CURLE_SSL_CONNECT_ERROR
            );
        }
    }

    /**
     * @test
     * @throws \TorneLIB\Exception\ExceptionHandler
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
     * @throws \TorneLIB\Exception\ExceptionHandler
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
    public function setConfigData()
    {
        $config = new WrapperConfig();

        // No need to pre-set any timeout as WrapperConfig makes sure the timeouts are properly set.
        $gTimeout = $config->getTimeout();
        try {
            $config->getEmptySetting();
        } catch (\TorneLIB\Exception\ExceptionHandler $e) {
            static::assertTrue(
                $e->getCode() === Constants::LIB_CONFIGWRAPPER_VAR_NOT_SET &&
                (int)$gTimeout['REQUEST'] === 6 &&
                (int)$gTimeout['CONNECT'] === 3
            );
        }
    }

    /**
     * @test
     */
    public function setMilliTimeout()
    {
        $config = new WrapperConfig();

        $config->setTimeout(100, true);
        // No need to pre-set any timeout as WrapperConfig makes sure the timeouts are properly set.
        $gTimeout = $config->getTimeout();
        try {
            $config->getEmptySetting();
        } catch (\TorneLIB\Exception\ExceptionHandler $e) {
            // The last request will make WrapperConfig throw an exception as getEmptySetting does not exist
            // in the magics setup. So we'll check the other values from here. Floats are returned regardless
            // of seconds and milliseconds, so we'll cast the values into integers here.
            static::assertTrue(
                $e->getCode() === Constants::LIB_CONFIGWRAPPER_VAR_NOT_SET &&
                (int)$gTimeout['REQUEST'] === 100 &&
                (int)$gTimeout['CONNECT'] === 50 &&
                (bool)$gTimeout['MILLISEC']
            );
        }
    }
}
